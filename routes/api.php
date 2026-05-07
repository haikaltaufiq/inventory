<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MidtransWebhookController;

Route::any('/midtrans/webhook', [MidtransWebhookController::class, 'handle']);
