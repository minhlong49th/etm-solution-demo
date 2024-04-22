<?php

use Illuminate\Support\Facades\Route;
use Modules\Whatsapp\App\Http\Controllers\WhatsappController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([], function () {
    Route::resource('whatsapp', WhatsappController::class)->names('whatsapp');
});

Route::get('/webhook', 'WebhookController@verifyWebhook');
Route::post('/webhook', 'WebhookController@receiveWebhook');
// Route::get('/send-message', 'WebhookController@sendMessage');
