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

Route::get('discount_time',function(Request $request){
    $discountId = $request->get('discount_id', 0);
    return \App\Models\Platv4CustomerVipDiscount::where('id', $discountId)->select(['start_time', 'end_time'])->first();
});

Route::get('banners/cover',function(Request $request){
    $bannerId = $request->get('banner_id', 0);
    return \App\Models\Platv4Banner::getUserGroupCover($bannerId);
});

Route::get('user/groups/member',function(Request $request){
    $uid = $request->get('uid', null);
    if(!$uid)    return [];
    $userGroupId = $request->get('user_group_id', 0);
    $cacheKey = 'CMS:CMD:USER_GROUP:ID:' . $userGroupId;
    $res = Illuminate\Support\Facades\Redis::sismember($cacheKey,$uid);
    return ['uid'=>$uid,'res'=>$res];
});

Route::any('wechat/template',function(){
    $controller = new \App\Admin\Controllers\OfficialAccountInformController();
    $result = $controller->getTpl();
    return $result;
});
