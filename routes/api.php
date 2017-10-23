<?php

use Illuminate\Http\Request;

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

Route::get('cities', function (Request $request) {
    $provinceId = $request->get('province_id', 0);
    return \App\Models\Platv4City::where('province_id', $provinceId)->get(['city_id', 'name']);
});

Route::get('districts', function (Request $request) {
    $cityId = $request->get('city_id', 0);
    return \App\Models\Platv4District::where('city_id', $cityId)->get(['district_id', 'name']);
});
