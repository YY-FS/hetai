<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4Modal extends BaseModel
{
    protected $table = 'platv4_modal';
    protected $connection = 'plat';

    const STATUS_DELETE = -1;
    const STATUS_OFFLINE = 0;
    const STATUS_END = 1;
    const STATUS_READY = 2;
    const STATUS_PROGRESS = 3;

    public static $statusText = [
//        self::STATUS_DELETE => '删除',
        self::STATUS_OFFLINE => '已下线',
        self::STATUS_END => '已结束',
        self::STATUS_READY => '未开始',
        self::STATUS_PROGRESS => '进行中'
    ];

    public static function rapydGrid()
    {
        $result = DB::connection('plat')->table('platv4_modal as m')
            ->leftJoin('platv4_item_to_user_group AS i',function($join){
                $join->on('m.id','=','i.item_id')
                    ->where('i.item_table','=','platv4_modal');
            })
            ->leftJoin('platv4_user_group_v2 AS g','i.user_group_id','=','g.id')
            ->leftJoin('platv4_item_to_user_group AS itug',function($join){
                $join->on('m.customer_vip_discount_id','=','itug.item_id')
                    ->where('itug.item_table','=','platv4_customer_vip_discounts');
            })
            ->leftJoin('platv4_user_group_v2 AS ug','itug.user_group_id','=','ug.id')
            ->select([
                'm.id',
                'm.sort',
                'm.status',
                'm.name',
                DB::connection('plat')->raw('GROUP_CONCAT( g.`name` ) as modal_group'),
                DB::connection('plat')->raw('GROUP_CONCAT( ug.`name` ) as discount_group'),
                'm.comment',
                'm.start_time',
                'm.end_time',
                'm.created_at'
            ])
            ->where('m.status','>',-1)
            ->groupBy('m.id');

        return $result;
    }

    public static function checkStatus($row)
    {
        $now = time();
        $start = strtotime($row->data->start_time);
        $end = strtotime($row->data->end_time);

        if($row->data->status == self::STATUS_OFFLINE){
            $style = 'color:#CECECE;';
            $status = self::STATUS_OFFLINE;
            $toStatus = self::STATUS_READY;
            $toStatusText = '上线';
        }else{
            if($start > $end) return [];

            if($now > $end){
                $style = 'color:#FF3300;';
                $status = self::STATUS_END;
                $toStatus = self::STATUS_OFFLINE;
            }elseif($now >= $start && $now <= $end){
                $style = 'color:#33CC33;';
                $toStatus = self::STATUS_OFFLINE;
                $status = self::STATUS_PROGRESS;
            }elseif($now < $start){
                $style = 'color:#0099CC;';
                $toStatus = self::STATUS_OFFLINE;
                $status = self::STATUS_READY;
            }

            $toStatusText = '下线';
            if($row->data->status != $status){
                DB::connection('plat')->table('platv4_modal')
                    ->where('id',$row->data->id)->update(['status'=>$status]);
            }
        }
        $result = [];
        $result['style'] = $style;
        $result['toStatus'] = $toStatus;
        $result['status'] = $status;
        $result['toStatusText'] = $toStatusText;

        return $result;
    }
}