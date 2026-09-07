$ErrorActionPreference = 'Stop'

$projectPath = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectPath

& php artisan queue:work database --queue=emails,default --tries=3 --timeout=120
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}
