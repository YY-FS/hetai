<?php
/**
 * Created by PhpStorm.
 * User: liangweibin
 * Date: 18/5/15
 * Time: 下午4:16
 */

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4MessageWechat extends BaseModel
{
    public $connection = 'plat';
    protected $table = 'platv4_message_wechat';
    public $timestamps = false;

    const COMMON_STATUS_DELETE = -1;
    const COMMON_STATUS_PUSHED = 2;
    const COMMON_STATUS_PUSHING = 1;
    const COMMON_STATUS_UNPUSH = 0;

    const ROUTE_ARTICLE = 'article';
    const ROUTE_CUSTOMER_SERVICE = 'customer_service';
    const ROUTE_TEMPLATE = 'template';

    static $commonStatusText = [
        self::COMMON_STATUS_DELETE => '删除',
        self::COMMON_STATUS_PUSHED => '已推送',
        self::COMMON_STATUS_PUSHING => '准备推送',
        self::COMMON_STATUS_UNPUSH => '未推送',
    ];

    static $routeText = [
        self::ROUTE_ARTICLE => '推文',
        self::ROUTE_CUSTOMER_SERVICE => '客服消息',
        self::ROUTE_TEMPLATE => '模板消息'
    ];


    public static function rapydGrid(){
        return $a = DB::connection('plat')->table('platv4_message_wechat as mw')
            ->leftJoin('platv4_message_wechat_type as mwt','mw.message_wechat_type_id','=','mwt.id')
            ->leftJoin('platv4_item_to_user_group as i2ug',function($join){
                $join->on('mw.id','=','i2ug.item_id')->where('item_table','=','platv4_message_wechat');
            })
            ->leftJoin('platv4_user_group_v2 as ug','i2ug.user_group_id','=','ug.id')
            ->select([
                'mw.*',
                'mwt.name as inform_type',
                'mwt.alias',
                DB::connection('plat')->raw('GROUP_CONCAT(ug.name) as user_group'),
                DB::connection('plat')->raw('SUM(ug.user_total) as user_sum')
            ])
            ->where('mw.status','>',self::COMMON_STATUS_DELETE)
            ->groupBy('mw.id');
    }
}