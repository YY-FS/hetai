<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4CustomerVipDiscount extends BaseModel
{
    protected $connection = 'plat';
    public $timestamps = false;

    const SCRIPT = <<<EOT
        var url = window.location.href;
        
        if(url.indexOf('modify=')>=0||url.indexOf('update=')>=0){
            var select = $('#type option:selected');
            $('#type').children().remove();
            $('#type').append(select);
            return;
        }else{
            changeRateField();
            $('#type').change(changeRateField);
        }
        
        function changeRateField(){
            var rateLabel = $('#fg_rate>label');
            var option = $('#type').val();
            $('#rate').val('100');
            switch(option){
                case 'REGISTER:EXPIRED':
                    rateLabel.text('折扣比例');
                    break;
                case 'GIVE:TIME':
                    rateLabel.text('赠送时长比例');
                    break;
                case 'PRICE:DISCOUNT':
                    rateLabel.text('折扣比例');
                    break;
                case 'CHARGE:BACK':
                    rateLabel.text('返送余额比例');
                    break;
                case 'FIRST:CAHRGE':
                    rateLabel.text('首充赠送时长比例');
                    break;
                default:
                    rateLabel.text('折扣比例');
                    break;
            }
        }
EOT;


    public static function rapydGrid($id = null)
    {
        $result = DB::connection('plat')->table('platv4_customer_vip_discounts AS cvd')
            ->leftJoin('platv4_item_to_user_group AS itug',function($join){
                    $join->on('cvd.id','=','itug.item_id')
                        ->where('itug.item_table','=','platv4_customer_vip_discounts');
            })
            ->leftJoin('platv4_user_group_v2 AS ug','ug.id','=','itug.user_group_id')
            ->leftJoin('platv4_customer_vip_discount_types AS cvdt','cvd.type','=','cvdt.name')
            ->leftJoin('platv4_customer_vip_discount_to_terminal AS cvdtt','cvd.id','=','cvdtt.customer_vip_discount_id')
            ->leftJoin('platv4_terminals AS t','t.name','=','cvdtt.terminal')
            ->leftJoin('platv4_modal as m',function($join){
                $join->on('cvd.id','=','m.customer_vip_discount_id')
                    ->where('m.status','>',-1);
            })
            ->leftJoin('platv4_banners_v2 as b',function($join){
                $join->on('cvd.id','=','b.customer_vip_discount_id')
                    ->where('b.status','>',-1);
            })
            ->select([
                'cvd.*',
                'cvdt.description AS type_name',
                'm.id AS modal_id',
                'b.id AS banner_id',
                DB::connection('plat')->raw('GROUP_CONCAT(distinct t.description) AS terminals'),
                DB::connection('plat')->raw('GROUP_CONCAT(distinct cvdtt.terminal) AS terminalOpts'),
                DB::connection('plat')->raw('GROUP_CONCAT(distinct ug.name) AS user_groups'),
                DB::connection('plat')->raw('GROUP_CONCAT(ug.id) AS user_group_ids'),
                DB::connection('plat')->raw('SUM(distinct ug.user_total) AS target_count'),
               // DB::connection('plat')->raw('COUNT(m.customer_vip_discount_id) as modal')
            ])
            ->where('cvd.status','>=',0)
            ->groupBy('cvd.id');

        if($id){
            $result->where('cvd.id',$id);
        }

        return $result;
    }

    public static function getData($id = null)
    {
        $result =$result = DB::connection('plat')->table('platv4_customer_vip_discounts AS cvd')
            ->leftJoin('platv4_user_payment AS up','up.coupon_id','=','cvd.id')
            ->leftJoin('platv4_order_product AS op','op.order_id','=','up.order_id')
            ->leftJoin('platv4_customer_vips AS cv','op.product_id','=','cv.id')
            ->select([
                'cvd.name',
                'cvd.start_time',
                'cvd.end_time',
                DB::connection('plat')->raw('date(up.date_paid) as date'),
                DB::connection('plat')->raw('sum(case `cv`.`alias` when \'maka\' then 1 else 0 end) as maka_sale'),
                DB::connection('plat')->raw('sum(case `cv`.`alias` when \'maka\' then `op`.`total` else 0 end) as maka_price'),
                DB::connection('plat')->raw('sum(case `cv`.`alias` when \'poster\' then 1 else 0 end) as poster_sale'),
                DB::connection('plat')->raw('sum(case `cv`.`alias` when \'poster\' then `op`.`total` else 0 end) as poster_price'),
                DB::connection('plat')->raw('sum(case `cv`.`alias` when \'video\' then 1 else 0 end) as video_sale'),
                DB::connection('plat')->raw('sum(case `cv`.`alias` when \'video\' then `op`.`total` else 0 end) as video_price'),
                DB::connection('plat')->raw('sum(case `cv`.`alias` when \'senior\' then 1 else 0 end) as senior_sale'),
                DB::connection('plat')->raw('sum(case `cv`.`alias` when \'senior\' then `op`.`total` else 0 end) as senior_price'),
                DB::connection('plat')->raw('sum(case `cv`.`alias` when \'super\' then 1 else 0 end) as super_sale'),
                DB::connection('plat')->raw('sum(case `cv`.`alias` when \'super\' then `op`.`total` else 0 end) as super_price'),
                DB::connection('plat')->raw('count(`op`.`id`) as all_sale'),
                DB::connection('plat')->raw('sum(`op`.`total`) as all_price'),
            ])
            ->where('op.pay_purpose','=',"customer_vip")
            ->where('up.status', 1)
            ->where('cvd.id',$id);

        return $result;
    }

    public static function getDetail($id = null)
    {
        $result = self::getData($id)
            ->groupBy(DB::connection('plat')->raw('date(up.date_paid)'))
            ->orderBy('date','desc');

            return $result;
    }
}
