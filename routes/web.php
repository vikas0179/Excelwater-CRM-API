<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StaticData;
use App\Http\Controllers\CronController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/import-bwt', [StaticData::class,'load_book_water_test_data']);
Route::get('/import-lp', [StaticData::class,'load_lp_data']);
Route::get('/import-cd', [StaticData::class,'load_contact_data']);
Route::get('/emails-after-dropped', [CronController::class,'send_emails_after_lead_dropped']);
Route::get('/emails/marketing/client-interest', [CronController::class,'send_emails_after_lead_dropped_interest']);
