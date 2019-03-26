<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-3-8
 * Time: 下午5:14
 */
namespace App\Models;

use Illuminate\Support\Facades\DB;

class HtSensor extends BaseModel
{
    protected $table = "ht_sensor";
    public $timestamps = false;

    //0一般状态，1成功提交订单，－1取消订单
    public static $machineText = [
        1 => "1号机",
        2 => "2号机"
    ];

    public static function rapydGrid()
    {
        $result = DB::table('ht_sensor')
            ->select([
                'id',
                'created_at',
                'machine',
                'temperature',
                'humidity',
                'pressure',
                'airquality'
            ]);
//        if (!empty($where)) $result->where($where);
//        $result->groupBy('op.order_id');
        return $result;
    }
}