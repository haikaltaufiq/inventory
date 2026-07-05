<?php

/*
 * This file is part of the Laravel Cloudinary package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Core Credentials
    |--------------------------------------------------------------------------
    | Used by ProductService to upload/delete product images.
    | Set these in your .env (or Railway environment variables).
    */
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key'    => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Notification / Webhook URL
    |--------------------------------------------------------------------------
    */
    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL'),

    /*
    |--------------------------------------------------------------------------
    | Cloudinary SDK cloud_url (used by the Laravel Cloudinary package)
    |--------------------------------------------------------------------------
    */
    'cloud_url' => env('CLOUDINARY_URL', 'cloudinary://'.env('CLOUDINARY_API_KEY').':'.env('CLOUDINARY_API_SECRET').'@'.env('CLOUDINARY_CLOUD_NAME')),

    /**
     * Upload Preset From Cloudinary Dashboard
     */
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),

    /**
     * Route to get cloud_image_url from Blade Upload Widget
     */
    'upload_route' => env('CLOUDINARY_UPLOAD_ROUTE'),

    /**
     * Controller action to get cloud_image_url from Blade Upload Widget
     */
    'upload_action' => env('CLOUDINARY_UPLOAD_ACTION'),
];
