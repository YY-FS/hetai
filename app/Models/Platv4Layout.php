<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4Layout extends BaseModel
{
    protected $table = 'platv4_layout';
    protected $connection = 'plat';
    public $timestamps = false;

    const TYPE_EVERY_TIME = 'every_time';
    const TYPE_DAILY = 'daily';
    const TYPE_ONCE = 'once';

    static $typeArr = [
        self::TYPE_DAILY => '每天显示一次',
        self::TYPE_EVERY_TIME => '每次打开都显示',
        self::TYPE_ONCE => '只显示一次'
    ];

    public static function rapydGrid()
    {
        return DB::connection('plat')->table('platv4_layout')->select(['*']);
    }

    public static function getStyle()
    {
        return DB::connection('plat')->table('platv4_layout')->groupBy('style')->pluck('style','style')->toArray();
    }

    public static function timeToSec($time)
    {
        if(!$time) return '';
        $pattern = "/^\d{0,2}(：|\:)\d{2}((：|\:)\d{0,2})?/";
        $content = trim($time);

        if(!preg_match($pattern,$content,$arr))  return $time;

        $timeArr = count(explode(':',$time))>=2?explode(':',$time):explode('：',$time);
        $sec = intval($timeArr[0])*3600+intval($timeArr[1])*60;
        return $sec;
    }

    public static function secToTime($sec)
    {
        if(!$sec) return '';
        $pattern = "/^\d+$/";
        $content = trim($sec);

        if(!preg_match($pattern,$content))  return $sec;
        $sec = (int)$sec;

        $h = floor($sec/3600)>9?floor($sec/3600):'0'.floor($sec/3600);
        $i = floor($sec%3600/60)>9?floor($sec%3600/60):'0'.floor($sec%3600/60);
        return $h.':'.$i.':00';
    }
}