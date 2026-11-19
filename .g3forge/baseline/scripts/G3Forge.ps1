#requires -Version 5.1
<# Acceso directo a la consola G3Forge desde el repositorio cliente. #>
$ErrorActionPreference = 'Stop'
# Usar la consola de este workspace: un CLI global anterior podría conservar
# operaciones de entregas ya retiradas de esta instalación.
$Repo = Split-Path -Parent $PSScriptRoot
$Consola = Join-Path $PSScriptRoot 'consola.py'
try {
    [Console]::OutputEncoding = New-Object System.Text.UTF8Encoding($false)
    $env:PYTHONIOENCODING = 'utf-8'
} catch { }
if (Get-Command py -ErrorAction SilentlyContinue) {
    & py -3 $Consola --repo $Repo
} elseif (Get-Command python -ErrorAction SilentlyContinue) {
    & python $Consola --repo $Repo
} else {
    Write-Host 'Se requiere Python 3.11 o superior.' -ForegroundColor Red
    exit 1
}
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
