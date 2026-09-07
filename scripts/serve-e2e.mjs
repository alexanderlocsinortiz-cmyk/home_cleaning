import { spawn, spawnSync } from 'node:child_process';
import { closeSync, mkdirSync, openSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = fileURLToPath(new URL('..', import.meta.url));
const databasePath = resolve(projectRoot, 'database/e2e.sqlite');
const port = process.env.E2E_PORT || '8001';
const environment = {
    ...process.env,
    APP_ENV: 'testing',
    APP_DEBUG: 'true',
    APP_URL: `http://127.0.0.1:${port}`,
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
    CACHE_STORE: 'array',
    SESSION_DRIVER: 'file',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'array',
    BCRYPT_ROUNDS: '4',
};

mkdirSync(dirname(databasePath), { recursive: true });
closeSync(openSync(databasePath, 'a'));

const migration = spawnSync('php', ['artisan', 'migrate:fresh', '--seed', '--force'], {
    cwd: projectRoot,
    env: environment,
    stdio: 'inherit',
});

if (migration.status !== 0) {
    process.exit(migration.status ?? 1);
}

const e2eSeed = spawnSync('php', ['artisan', 'db:seed', '--class=E2EUserSeeder', '--force'], {
    cwd: projectRoot,
    env: environment,
    stdio: 'inherit',
});

if (e2eSeed.status !== 0) {
    process.exit(e2eSeed.status ?? 1);
}

const server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${port}`], {
    cwd: projectRoot,
    env: environment,
    stdio: 'inherit',
});

const stop = () => {
    if (! server.killed) {
        server.kill('SIGTERM');
    }
};

process.on('SIGINT', stop);
process.on('SIGTERM', stop);
server.on('exit', (code) => process.exit(code ?? 0));
