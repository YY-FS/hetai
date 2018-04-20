<?php

namespace App\Admin\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;

class PublicController extends BaseController
{

    public $bundles = [
        [
            'name' => '主包',
            'app_id' => 'wx7f55d4adc3f67408',
        ],
        [
            'name' => '马甲包1',
            'app_id' => 'wxd8dd8817998ee451',
        ],
        [
            'name' => '马甲包2',
            'app_id' => 'wxa55d79409650299e',
        ],
        [
            'name' => 'viewer包',
            'app_id' => 'wx8bb864b3d04c25a3',
        ],
    ];

    public function minaVersion()
    {
        $bundles = $this->bundles;

        foreach ($bundles as &$bundle) {
            $bundle['version'] = Redis::hget('MINA_AUDIT_VERSION', $bundle['app_id']);
        }

        return view('redis.minaVersion', compact('bundles'));
    }


    public function editMinaVersion()
    {
        $this->requestValidate([
            'app_id' => 'required',
            'version' => 'required'
        ]);

        Redis::hset('MINA_AUDIT_VERSION', Input::get('app_id'), Input::get('version'));

        return $this->respData();
    }


    public function platConfig()
    {

        return view('redis.sign');
    }


    public function cleanSign($uid)
    {
        $month = date('Y-m');
        $firstSignDay = $month . '-01';
        $signCacheKey = 'MN:USER_DAILY_SIGN_IN:' . $month . ':' . $uid;
        $shareCacheKey = 'MN:USER_DAILY_SIGN_SHARE:' . $month . ':' . $uid;
        $today = date('Y-m-d');

        $todayTime = strtotime($today);
        $firstSignTime = strtotime($firstSignDay);

        $signOffset = ($todayTime - $firstSignTime) / 86400;

        Redis::setbit($signCacheKey, $signOffset, 0);
        Redis::setbit($signCacheKey, $signOffset, 0);
        Redis::del('MN:USER_SIGN_IN:' . $uid);
        Redis::del('MN:USER_SIGN_IN_LIST:' . $today . ':' . $uid);

        return $this->respData();
    }


    public function cleanLogin($username)
    {
        $today = date('Y-m-d');
        $cacheKey = 'QS:LOGIN_USERNAME_FAIL:' . $today;

        Redis::hdel($cacheKey, $username);

        return $this->respData();
    }

}
