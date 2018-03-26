<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-3-8
 * Time: 下午5:14
 */
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4UserPayment extends BaseModel
{
    protected $table = "platv4_user_payment";
    protected $connection = 'plat';

    //0一般状态，1成功提交订单，－1取消订单
    public static $statusText = [
        self::COMMON_STATUS_OFFLINE => "未支付",
        self::COMMON_STATUS_NORMAL => "已支付",
        self::COMMON_STATUS_DELETE => "取消订单",
    ];

    public static function rapydGrid($where)
    {
        $result = DB::connection('plat')->table('platv4_user_payment as up')
            ->leftJoin('platv4_order_product AS op', 'up.order_id', '=', 'op.order_id')
            ->leftJoin('platv4_pay_platform AS pp', 'up.order_type', '=', 'pp.alias')
            ->leftJoin('platv4_terminals AS t', 'up.pay_source', '=', 't.name')
            ->select([
                'up.id',
                'up.order_id',
                'up.uid',
                'pp.name AS type_name',
                'up.order_amount',
                'up.pay_amount',
                'up.status',
                't.description',
                'up.pay_channel',
                'up.bundle_id',
                'up.app_version',
                'up.date_paid',
                'up.create_time',
                DB::connection('plat')->raw('GROUP_CONCAT( op.`product_id` ORDER BY op.`product_id` SEPARATOR "\n") as product_id'),
                DB::connection('plat')->raw('GROUP_CONCAT( op.`name` ORDER BY op.`product_id` SEPARATOR "\n") as product_name'),
                DB::connection('plat')->raw('GROUP_CONCAT( op.`quantity` ORDER BY op.`product_id` SEPARATOR "\n") as product_quantity'),
                DB::connection('plat')->raw('GROUP_CONCAT( op.`price` ORDER BY op.`product_id` SEPARATOR "\n") as product_price'),
                DB::connection('plat')->raw('GROUP_CONCAT( op.`total` ORDER BY op.`product_id` SEPARATOR "\n") as product_total'),
                DB::connection('plat')->raw('GROUP_CONCAT( op.`pay_purpose` ORDER BY op.`product_id` SEPARATOR "\n") as product_purpose'),
            ]);
        if (!empty($where)) $result->where($where);
        $result->groupBy('op.order_id');
        return $result;
    }
}