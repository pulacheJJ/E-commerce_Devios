<?php

$cloudName = (string) env('CLOUDINARY_CLOUD_NAME', '');
$apiKey = (string) env('CLOUDINARY_API_KEY', '');
$apiSecret = (string) env('CLOUDINARY_API_SECRET', '');
$cloudUrl = env('CLOUDINARY_URL');

if (! $cloudUrl && $cloudName !== '' && $apiKey !== '' && $apiSecret !== '') {
    $cloudUrl = sprintf(
        'cloudinary://%s:%s@%s',
        rawurlencode($apiKey),
        rawurlencode($apiSecret),
        rawurlencode($cloudName)
    );
}

return [
    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL'),
    'cloud_url' => $cloudUrl,
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
    'upload_route' => env('CLOUDINARY_UPLOAD_ROUTE'),
    'upload_action' => env('CLOUDINARY_UPLOAD_ACTION'),
];
