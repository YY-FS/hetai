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
    const STATUS_DELETE = -1;//删除

    public static $statusText = [
        self::STATUS_OFFLINE => '有效',
        self::STATUS_END => '失效',
        self::STATUS_DELETE => '删除'
    ];

    public static function rapdyGrid()
    {
        $result = DB::connection('plat')->table('platv4_user AS u')
            ->leftJoin('platv4_user_to_customer_vip AS v', 'u.id', '=', 'v.uid')
            ->select([
                'u.id AS uid',
                'u.username',
                'v.id',
                'v.customer_vip_id',
                'v.customer_vip_name',
                'v.create_time',
                'v.start_date',
                'v.end_date',
                'v.status'
            ])
            ->where([
                ['v.status', '<>', -1],
                ['u.id', '=', 0],
            ]);
        return $result;
    }

    public static function checkStatus($row)
    {
        $now = time();
        $start = strtotime($row->data->start_date);
        $end = strtotime($row->data->end_date);

        $status = self::STATUS_OFFLINE;//默认有效
        $dbStatus = Platv4UserToCustomerVip::find($row->data->id, ['status']);
        if ($start > $end) return [];//时间错误

        if ($now > $end)//失效
            $status = self::STATUS_END;
        if ($dbStatus->status !== 1)//失效
            $status = self::STATUS_END;


        if ($row->data->status != $status) {
            DB::connection('plat')->table('platv4_user_to_customer_vip')
                ->where('id', $row->data->id)
                ->update(['status' => $status]);
        }
        $result = [];
        $result['status'] = $status;

        return $result;
    }
}