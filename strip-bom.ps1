<#
  strip-bom.ps1

  Removes a UTF-8 BOM (if present) from the start of PHP files that
  wire-scaffold.ps1 rewrote. A BOM before "<?php" makes PHP emit those
  bytes as literal output, which caused the stray characters you saw
  before "INFO Running migrations." Run this once from the project root:

      .\strip-bom.ps1
#>

$ErrorActionPreference = "Stop"

$files = @(
    "bootstrap/app.php",
    "bootstrap/providers.php",
    "config/services.php",
    "app/Models/User.php"
)

$bom = [byte[]](0xEF, 0xBB, 0xBF)

foreach ($file in $files) {
    if (-not (Test-Path $file)) {
        Write-Host "Skipping $file (not found)" -ForegroundColor Yellow
        continue
    }

    $bytes = [System.IO.File]::ReadAllBytes($file)

    if ($bytes.Length -ge 3 -and $bytes[0] -eq $bom[0] -and $bytes[1] -eq $bom[1] -and $bytes[2] -eq $bom[2]) {
        $stripped = $bytes[3..($bytes.Length - 1)]
        [System.IO.File]::WriteAllBytes($file, $stripped)
        Write-Host "Stripped BOM from $file" -ForegroundColor Green
    } else {
        Write-Host "No BOM found in $file (already clean)" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "Done. Run 'php artisan migrate:status' to confirm PHP still parses everything cleanly." -ForegroundColor Cyan
