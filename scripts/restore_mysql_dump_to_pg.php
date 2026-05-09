<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$projectRoot = dirname(__DIR__);
$defaultDumpPath = dirname($projectRoot).DIRECTORY_SEPARATOR.'hc_web.sql';
$options = getopt('', [
    'dump::',
    'host::',
    'port::',
    'database::',
    'username::',
    'password::',
    'dry-run',
]);

if (file_exists($projectRoot.'/.env')) {
    Dotenv::createImmutable($projectRoot)->safeLoad();
}

$dumpPath = $options['dump'] ?? $defaultDumpPath;
$dbHost = $options['host'] ?? envValue('DB_HOST', '127.0.0.1');
$dbPort = $options['port'] ?? envValue('DB_PORT', '5432');
$dbName = $options['database'] ?? envValue('DB_DATABASE', 'hc_web');
$dbUser = $options['username'] ?? envValue('DB_USERNAME', 'postgres');
$dbPassword = $options['password'] ?? envValue('DB_PASSWORD', '');

if (! is_file($dumpPath)) {
    fwrite(STDERR, "Dump file not found: {$dumpPath}\n");
    exit(1);
}

$tablesToRestore = [
    'users',
    'staff',
    'services',
    'devices',
    'sessions',
    'bookings',
    'booking_locations',
    'notifications',
    'ratings',
    'attendance_logs',
    'device_enrollment_requests',
];

$truncateTables = [
    'attendance_logs',
    'booking_locations',
    'ratings',
    'notifications',
    'device_enrollment_requests',
    'bookings',
    'sessions',
    'devices',
    'staff',
    'services',
    'users',
];

$sequenceTables = [
    'attendance_logs',
    'bookings',
    'booking_locations',
    'devices',
    'device_enrollment_requests',
    'notifications',
    'ratings',
    'services',
    'staff',
    'users',
];

$parsed = parseDump($dumpPath, $tablesToRestore);

foreach ($tablesToRestore as $table) {
    $rowCount = count($parsed[$table]['rows'] ?? []);
    echo sprintf("Parsed %-26s %5d rows\n", $table.':', $rowCount);
}

if (array_key_exists('dry-run', $options)) {
    echo "\nDry run completed. No database changes were made.\n";
    exit(0);
}

$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $dbHost, $dbPort, $dbName);
$pdo = new PDO($dsn, $dbUser, $dbPassword, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$pdo->beginTransaction();

try {
    $truncateSql = 'TRUNCATE TABLE '
        .implode(', ', array_map(static fn (string $table): string => quoteIdentifier($table), $truncateTables))
        .' RESTART IDENTITY CASCADE';
    $pdo->exec($truncateSql);

    foreach ($tablesToRestore as $table) {
        $columns = $parsed[$table]['columns'] ?? [];
        $rows = $parsed[$table]['rows'] ?? [];

        if ($columns === [] || $rows === []) {
            continue;
        }

        $insertSql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            quoteIdentifier($table),
            implode(', ', array_map('quoteIdentifier', $columns)),
            implode(', ', array_fill(0, count($columns), '?'))
        );

        $statement = $pdo->prepare($insertSql);

        foreach ($rows as $row) {
            $statement->execute($row);
        }
    }

    foreach ($sequenceTables as $table) {
        $sequenceName = $pdo->query(
            "SELECT pg_get_serial_sequence('public.".$table."', 'id')"
        )->fetchColumn();

        if (! $sequenceName) {
            continue;
        }

        $pdo->exec(
            'SELECT setval('
            .$pdo->quote((string) $sequenceName)
            .', COALESCE((SELECT MAX(id) FROM '.quoteIdentifier($table).'), 1), '
            .'COALESCE((SELECT MAX(id) FROM '.quoteIdentifier($table).'), 0) > 0)'
        );
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Restore failed: {$e->getMessage()}\n");
    exit(1);
}

echo "\nRestore completed successfully.\n";

function envValue(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return $value;
}

function quoteIdentifier(string $identifier): string
{
    return '"'.str_replace('"', '""', $identifier).'"';
}

/**
 * @param  array<int, string>  $tablesToRestore
 * @return array<string, array{columns: array<int, string>, rows: array<int, array<int, mixed>>}>
 */
function parseDump(string $dumpPath, array $tablesToRestore): array
{
    $targetTables = array_flip($tablesToRestore);
    $parsed = [];

    foreach ($tablesToRestore as $table) {
        $parsed[$table] = ['columns' => [], 'rows' => []];
    }

    $handle = fopen($dumpPath, 'rb');

    if (! $handle) {
        throw new RuntimeException("Unable to open dump file: {$dumpPath}");
    }

    $activeTable = null;
    $activeColumns = [];
    $buffer = '';

    try {
        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);

            if ($activeTable === null) {
                if (! str_starts_with($trimmed, 'INSERT INTO `')) {
                    continue;
                }

                if (! preg_match('/^INSERT INTO `([^`]+)` \((.+)\) VALUES$/', $trimmed, $matches)) {
                    continue;
                }

                $table = $matches[1];

                if (! isset($targetTables[$table])) {
                    continue;
                }

                $activeTable = $table;
                $activeColumns = parseColumns($matches[2]);
                $buffer = '';

                continue;
            }

            $buffer .= $line;

            if (! str_ends_with($trimmed, ';')) {
                continue;
            }

            $parsed[$activeTable]['columns'] = $activeColumns;
            $parsed[$activeTable]['rows'] = parseValuesBlock($buffer);
            $activeTable = null;
            $activeColumns = [];
            $buffer = '';
        }
    } finally {
        fclose($handle);
    }

    return $parsed;
}

/**
 * @return array<int, string>
 */
function parseColumns(string $columnList): array
{
    preg_match_all('/`([^`]+)`/', $columnList, $matches);

    return $matches[1];
}

/**
 * @return array<int, array<int, mixed>>
 */
function parseValuesBlock(string $block): array
{
    $rows = [];
    $length = strlen($block);
    $inString = false;
    $escaped = false;
    $depth = 0;
    $tuple = '';

    for ($index = 0; $index < $length; $index++) {
        $char = $block[$index];

        if ($inString) {
            $tuple .= $char;

            if ($escaped) {
                $escaped = false;

                continue;
            }

            if ($char === '\\') {
                $escaped = true;

                continue;
            }

            if ($char === "'") {
                $inString = false;
            }

            continue;
        }

        if ($char === "'") {
            $inString = true;

            if ($depth > 0) {
                $tuple .= $char;
            }

            continue;
        }

        if ($char === '(') {
            if ($depth === 0) {
                $tuple = '';
            } else {
                $tuple .= $char;
            }

            $depth++;

            continue;
        }

        if ($char === ')') {
            $depth--;

            if ($depth === 0) {
                $rows[] = parseTuple($tuple);
                $tuple = '';

                continue;
            }

            $tuple .= $char;

            continue;
        }

        if ($depth > 0) {
            $tuple .= $char;
        }
    }

    return $rows;
}

/**
 * @return array<int, mixed>
 */
function parseTuple(string $tuple): array
{
    $values = [];
    $token = '';
    $length = strlen($tuple);
    $inString = false;
    $escaped = false;

    for ($index = 0; $index < $length; $index++) {
        $char = $tuple[$index];

        if ($inString) {
            $token .= $char;

            if ($escaped) {
                $escaped = false;

                continue;
            }

            if ($char === '\\') {
                $escaped = true;

                continue;
            }

            if ($char === "'") {
                $inString = false;
            }

            continue;
        }

        if ($char === "'") {
            $inString = true;
            $token .= $char;

            continue;
        }

        if ($char === ',') {
            $values[] = normalizeToken($token);
            $token = '';

            continue;
        }

        $token .= $char;
    }

    $values[] = normalizeToken($token);

    return $values;
}

/**
 * @return mixed
 */
function normalizeToken(string $token)
{
    $token = trim($token);

    if (strcasecmp($token, 'NULL') === 0) {
        return null;
    }

    if ($token !== '' && $token[0] === "'" && substr($token, -1) === "'") {
        return decodeMySqlString(substr($token, 1, -1));
    }

    return $token;
}

function decodeMySqlString(string $value): string
{
    $decoded = '';
    $escaped = false;
    $length = strlen($value);

    for ($index = 0; $index < $length; $index++) {
        $char = $value[$index];

        if (! $escaped) {
            if ($char === '\\') {
                $escaped = true;

                continue;
            }

            $decoded .= $char;

            continue;
        }

        $decoded .= match ($char) {
            '0' => "\0",
            'b' => "\x08",
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            'Z' => "\x1a",
            '\\' => '\\',
            "'" => "'",
            '"' => '"',
            default => $char,
        };
        $escaped = false;
    }

    if ($escaped) {
        $decoded .= '\\';
    }

    return $decoded;
}
