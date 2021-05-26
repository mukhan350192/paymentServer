<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/payBox',[PaymentController::class,'payboxPayment']);
Route::get('/qiwiCheck',[PaymentController::class,'qiwiCheck']);
Route::get('/astanaPlat',[PaymentController::class,'astanaPlat']);
Route::get('/testPayment',[PaymentController::class,'testPayment']);
