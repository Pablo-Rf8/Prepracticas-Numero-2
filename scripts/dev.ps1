param([int]$Port = 8002)
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'Activate-Project.ps1')
Set-Location (Join-Path (Split-Path -Parent $PSScriptRoot) 'backend')
php artisan serve --host=127.0.0.1 --port=$Port
