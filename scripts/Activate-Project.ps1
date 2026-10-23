$projectRoot = Split-Path -Parent $PSScriptRoot
$env:PATH = (Join-Path $projectRoot '.tools/php') + ';' + $env:PATH
$env:COMPOSER_HOME = Join-Path $projectRoot '.tools/composer-home'
$env:COMPOSER_CACHE_DIR = Join-Path $projectRoot '.tools/cache/composer'
