<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-4-16
 * Time: 下午12:05
 */
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4Message extends BaseModel
{
    protected $connection = 'plat';
    protected $table = 'platv4_message';
    public $timestamps = false;

//    const SEND_PASSWORD = 'Gelaiminmaka';

    const STATUS_DELETE = 2;
    public static $statusText = [
        self::COMMON_STATUS_OFFLINE => '禁用',
        self::COMMON_STATUS_NORMAL => '启用',
    ];
    public static $popupText = [
        '1' => '通知中心',
        '2' => '小喇叭',
        '3' => '右侧弹窗',
        '4' => '模态弹窗'
    ];
    //通知类型
    public static $typeText = [
        -1 => '普通消息',
        0 => '热门项目',
        1 => '热门项目列表',
        2 => 'H5页面',
        3 => '专题模板',
        4 => '专题模版列表',
    ];
    //标签
    public static $labelText = [
        '无' => '无',
        '重要' => '重要',
    ];
    //通知类型
    public static $deviceText = [
        'app' => 'app',
        'android' => 'android',
        'ios' => 'ios',
    ];

    //通知类型
    public static $typeTestText = [
        'maka' => 'maka',
        'poster' => 'poster',
        'link' => 'link',
        'danye' => 'danye'
    ];

    public static function rapydGrid($where)
    {
        $result = DB::connection('plat')->table('platv4_message')
            ->select([
                'id',
                'title',
                'device',
                'start_time',
                'end_time',
                'create_time',
                'popup',
                'status'
            ])
            ->where('status', '<>', 2)
            ->where('device', '<>', 'pc')
            ->orderBy('create_time', 'desc');
        if (!empty($where)) $result->where($where);
        return $result;
    }
}