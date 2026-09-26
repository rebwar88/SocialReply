<?php

use App\Http\Controllers\Webhooks\MetaWebhookController;
use Illuminate\Support\Facades\Route;

// Meta hits the same URL with GET (handshake) and POST (event delivery).
// VerifyMetaSignature no-ops on GET since the handshake carries no signature.
Route::match(['get'], '/webhooks/meta/{platform}', [MetaWebhookController::class, 'verify'])
    ->middleware('meta.signature');

Route::post('/webhooks/meta/{platform}', [MetaWebhookController::class, 'handle'])
    ->middleware('meta.signature');
