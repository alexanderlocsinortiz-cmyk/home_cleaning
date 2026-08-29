$ErrorActionPreference = 'Stop'

$projectPath = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectPath

& php artisan schedule:run
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}
