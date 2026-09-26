<?php

/**
 * Add this array under the 'meta' key inside config/services.php
 * (merge into the existing returned array, don't overwrite the file).
 */

'meta' => [
    'app_id' => env('META_APP_ID'),
    'app_secret' => env('META_APP_SECRET'),
    'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
    'ai_fallback_enabled' => env('META_AI_FALLBACK_ENABLED', false),
],
