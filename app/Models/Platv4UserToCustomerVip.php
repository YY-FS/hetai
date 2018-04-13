<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-3-12
 * Time: 下午6:37
 */
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4UserToCustomerVip extends BaseModel
{
    protected $table = "platv4_user_to_customer_vip";
    protected $connection = 'plat';

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    const STATUS_OFFLINE = 1;//有效
    const STATUS_END = 0;//失效

    public static $statusText = [
        self::STATUS_OFFLINE => '有效',
        self::STATUS_END => '失效',
    ];

    public static function rapdyGrid($where)
    {
        $result = DB::connection('plat')->table('platv4_user_to_customer_vip AS v')
            ->leftJoin('platv4_user AS u', 'u.id', '=', 'v.uid')
            ->select([
                'v.uid',
                'u.username',
                'v.id',
                'v.customer_vip_id',
                'v.customer_vip_name',
                'v.create_time',
                'v.start_date',
                'v.end_date',
                'v.status'
            ])
            ->where('v.status', '<>', -1);
        if (!empty($where)) $result->where($where);
        return $result;
    }
}