<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4Banner extends BaseModel
{
    protected $table = 'platv4_banners_v2';
    protected $connection = 'plat';

    const STATUS_DELETE = -1;
    const STATUS_OFFLINE = 0;
    const STATUS_END = 1;
    const STATUS_READY = 2;
    const STATUS_PROGRESS = 3;

    const TARGET_LINK = 'link';
    const TARGET_MAKA = 'maka';
    const TARGET_POSTER = 'poster';
    const TARGET_VIDEO = 'video';
    const TARGET_CATEGORY = 'category';

    public static $statusText = [
//        self::STATUS_DELETE => '删除',
        self::STATUS_OFFLINE => '已下线',
        self::STATUS_END => '已结束',
        self::STATUS_READY => '未开始',
        self::STATUS_PROGRESS => '进行中'
    ];

    public static $targetText = [
        self::TARGET_LINK => '链接',
        self::TARGET_MAKA => 'H5',
        self::TARGET_POSTER => '海报',
        self::TARGET_VIDEO => '视频',
        self::TARGET_CATEGORY => '更多分类'
    ];

    const SCRIPT = "$('.user-group').on('click', function(event){
                          event.preventDefault();
                          var that = this;
                          var uri = this.href;
                          console.log(this.href);
                          $.ajax({
                            'url':uri,
                            'dataType':'text',
                            'type':'get',
                            'success':function(data){
                              layer.tips('覆盖用户数：'+data,that,{tips:1});
                            }
                          });
                        });";


    public static function rapydGrid($layout)
    {
        return  DB::connection('plat')->table('platv4_layout as l')
            ->leftJoin('platv4_banners_v2 as b',function($join){
                $join->on('b.layout_id','=','l.id')
                    ->where('b.status','>',-1);
            })
            ->leftJoin('platv4_banner_to_terminal as b2t','b.id','=','b2t.banner_id')
            ->leftJoin('platv4_terminals as t','b2t.terminal','=','t.name')
            ->leftJoin('platv4_item_to_user_group as i2dug',function($join){
                $join->on('b.customer_vip_discount_id','=', 'i2dug.item_id')
                    ->where('i2dug.item_table','=','platv4_customer_vip_discounts');
            })
            ->leftJoin('platv4_user_group_v2 as dug','i2dug.user_group_id','=','dug.id')
            ->leftJoin('platv4_item_to_user_group as i2bug',function($join){
                $join->on('b.id','=','i2bug.item_id')
                    ->where('i2bug.item_table','=','platv4_banners_v2');
            })
            ->leftJoin('platv4_user_group_v2 as bug','i2bug.user_group_id','=','bug.id')
            ->leftJoin('platv4_template_sets as ts','b.template_set_id','=','ts.id')
            ->select([
                'b.id',
                'b.sort',
                'b.status',
                'l.name as position',
                'layout_id',
                'b.thumb',
                'b.title',
                'b.url',
                'ts.name as template_set_name',
                DB::connection('plat')->raw('GROUP_CONCAT(distinct t.`description`) as terminal'),
                DB::connection('plat')->raw('GROUP_CONCAT(distinct dug.`name`) as discount_group'),
                DB::connection('plat')->raw('GROUP_CONCAT(distinct bug.`name`) as banner_group'),
                'b.comment',
                'b.start_time',
                'b.end_time',
                'b.created_at'
            ])
            ->where('l.alias',$layout)
            ->groupBy('b.id');
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
            if($start >= $end && $start && $end) return [];

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
                DB::connection('plat')->table('platv4_banners_v2')
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

    //for getUserGroupCover api 获取banner用户分群覆盖人数
    public static function getUserGroupCover($bannerId)
    {
        $coverData =  DB::connection('plat')->table('platv4_banners_v2 as b')
            ->leftJoin('platv4_item_to_user_group as i2dug',function($join){
                $join->on('b.customer_vip_discount_id','=', 'i2dug.item_id')
                    ->where('i2dug.item_table','=','platv4_customer_vip_discounts');
            })
            ->leftJoin('platv4_user_group_v2 as dug','i2dug.user_group_id','=','dug.id')
            ->leftJoin('platv4_item_to_user_group as i2bug',function($join){
                $join->on('b.id','=','i2bug.item_id')
                    ->where('i2bug.item_table','=','platv4_banners_v2');
            })
            ->leftJoin('platv4_user_group_v2 as bug','i2bug.user_group_id','=','bug.id')
            ->select([
                DB::connection('plat')->raw('SUM(bug.user_total) as banner_group_count'),
                DB::connection('plat')->raw('SUM(dug.user_total) as discount_group_count'),
            ])
            ->where('b.id',$bannerId)
            ->first();
        $sum = $coverData->discount_group_count?$coverData->discount_group_count:$coverData->banner_group_count;
        !$sum && $sum = 0;
        return $sum;
    }
}