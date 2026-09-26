<#
  wire-scaffold.ps1

  Run this from your Laravel project root, i.e.:
      cd "D:\web\Social Media Comment"
      .\wire-scaffold.ps1

  What it does:
    1. Rewrites bootstrap/app.php to register the webhook routes and
       the two middleware aliases (meta.signature, workspace).
    2. Rewrites bootstrap/providers.php to register ReplyEngineServiceProvider.
    3. Appends the 'meta' config block into config/services.php.
    4. Appends the META_* env vars into .env.
    5. Appends the workspaces() relationship into app/Models/User.php.
    6. Runs `php artisan migrate`.

  Every step checks whether it's already been applied and skips if so,
  so it's safe to re-run this script if something fails partway through.
#>

$ErrorActionPreference = "Stop"

function Write-Step($msg) {
    Write-Host "==> $msg" -ForegroundColor Cyan
}
function Write-Ok($msg) {
    Write-Host "    OK: $msg" -ForegroundColor Green
}
function Write-Skip($msg) {
    Write-Host "    Skipped (already applied): $msg" -ForegroundColor Yellow
}
function Write-Warn($msg) {
    Write-Host "    WARNING: $msg" -ForegroundColor Red
}

# --- Sanity check: are we in a Laravel project root? ---
if (-not (Test-Path "artisan")) {
    Write-Warn "No 'artisan' file found here. Run this script from your Laravel project root."
    exit 1
}

# --- Sanity check: has the scaffold actually been copied in? ---
if (-not (Test-Path "app/Http/Middleware/VerifyMetaSignature.php")) {
    Write-Warn "app/Http/Middleware/VerifyMetaSignature.php not found."
    Write-Warn "Copy the scaffold's app/, database/migrations/, and routes/webhooks.php into this project first."
    exit 1
}

# =====================================================================
# 1. bootstrap/app.php
# =====================================================================
Write-Step "Wiring bootstrap/app.php"

$appPath = "bootstrap/app.php"
$appContent = Get-Content $appPath -Raw

if ($appContent -match "VerifyMetaSignature") {
    Write-Skip "bootstrap/app.php"
} else {
    $newAppContent = @'
<?php

use App\Http\Middleware\EnsureWorkspaceContext;
use App\Http\Middleware\VerifyMetaSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'meta.signature' => VerifyMetaSignature::class,
            'workspace' => EnsureWorkspaceContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
'@
    Set-Content -Path $appPath -Value $newAppContent -NoNewline -Encoding UTF8
    Write-Ok "bootstrap/app.php"
}

# =====================================================================
# 2. bootstrap/providers.php
# =====================================================================
Write-Step "Wiring bootstrap/providers.php"

$providersPath = "bootstrap/providers.php"
$providersContent = Get-Content $providersPath -Raw

if ($providersContent -match "ReplyEngineServiceProvider") {
    Write-Skip "bootstrap/providers.php"
} else {
    $newProvidersContent = @'
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ReplyEngineServiceProvider::class,
];
'@
    Set-Content -Path $providersPath -Value $newProvidersContent -NoNewline -Encoding UTF8
    Write-Ok "bootstrap/providers.php"
}

# =====================================================================
# 3. config/services.php
# =====================================================================
Write-Step "Wiring config/services.php"

$servicesPath = "config/services.php"
$servicesContent = Get-Content $servicesPath -Raw

if ($servicesContent -match "'meta' =>") {
    Write-Skip "config/services.php"
} else {
    $metaBlock = @'

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
        'ai_fallback_enabled' => env('META_AI_FALLBACK_ENABLED', false),
    ],

'@
    # Insert before the FINAL "];" in the file (the closing of the returned array).
    $lastIndex = $servicesContent.LastIndexOf("];")
    if ($lastIndex -lt 0) {
        Write-Warn "Could not find closing '];' in config/services.php - add the meta block manually."
    } else {
        $newServicesContent = $servicesContent.Substring(0, $lastIndex) + $metaBlock + $servicesContent.Substring($lastIndex)
        Set-Content -Path $servicesPath -Value $newServicesContent -NoNewline -Encoding UTF8
        Write-Ok "config/services.php"
    }
}

# =====================================================================
# 4. .env
# =====================================================================
Write-Step "Wiring .env"

$envPath = ".env"
$envContent = Get-Content $envPath -Raw

if ($envContent -match "META_APP_ID") {
    Write-Skip ".env"
} else {
    $envAdditions = @'

META_APP_ID=
META_APP_SECRET=
META_WEBHOOK_VERIFY_TOKEN=
META_AI_FALLBACK_ENABLED=false
'@
    Add-Content -Path $envPath -Value $envAdditions -Encoding UTF8
    Write-Ok ".env"
}

# =====================================================================
# 5. app/Models/User.php
# =====================================================================
Write-Step "Wiring app/Models/User.php"

$userPath = "app/Models/User.php"
$userContent = Get-Content $userPath -Raw

if ($userContent -match "function workspaces\(\)") {
    Write-Skip "app/Models/User.php"
} else {
    $method = @'

    public function workspaces(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Workspace::class)->withPivot('role')->withTimestamps();
    }
'@
    # Insert before the LAST closing brace in the file (end of the class).
    $lastBrace = $userContent.LastIndexOf("}")
    if ($lastBrace -lt 0) {
        Write-Warn "Could not find closing brace in User.php - add the workspaces() method manually."
    } else {
        $newUserContent = $userContent.Substring(0, $lastBrace) + $method + "`n" + $userContent.Substring($lastBrace)
        Set-Content -Path $userPath -Value $newUserContent -NoNewline -Encoding UTF8
        Write-Ok "app/Models/User.php"
    }
}

# =====================================================================
# 6. Run migrations
# =====================================================================
Write-Step "Running php artisan migrate"
php artisan migrate

Write-Host ""
Write-Host "Done. If migrate ran clean, the scaffold is fully wired." -ForegroundColor Cyan
Write-Host "Run 'php artisan serve' to confirm the app still boots." -ForegroundColor Cyan
