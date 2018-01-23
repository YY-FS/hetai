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
            switch(option){
                case 'REGISTER:EXPIRED':
                    rateLabel.text('折扣比例');
                    $('#fg_rate').show();
                    $('#rate').val('');
                    break;
                case 'GIVE:TIME':
                    rateLabel.text('赠送时长比例');
                    $('#rate').val('100');
                    $('#fg_rate').hide();
                    break;
                case 'PRICE:DISCOUNT':
                    rateLabel.text('折扣比例');
                    $('#fg_rate').show();
                    $('#rate').val('');
                    break;
                case 'CHARGE:BACK':
                    rateLabel.text('返送余额比例');
                    $('#fg_rate').show();
                    $('#rate').val('');
                    break;
                case 'FIRST:CAHRGE':
                    rateLabel.text('赠送时长比例');
                    $('#rate').val('100');
                    $('#fg_rate').hide();
                    break;
                default:
                    rateLabel.text('折扣比例');
                    $('#fg_rate').show();
                    $('#rate').val('');
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
            ->select([
                'cvd.*',
                'cvdt.description AS type_name',
                DB::connection('plat')->raw('GROUP_CONCAT(cvdtt.terminal) AS terminals'),
                DB::connection('plat')->raw('GROUP_CONCAT(distinct ug.name) AS user_groups'),
                DB::connection('plat')->raw('GROUP_CONCAT(ug.id) AS user_group_ids'),
                DB::connection('plat')->raw('SUM(distinct ug.user_total) AS target_count')
            ])
            ->where('cvd.status','>=',0)
            ->groupBy('cvd.id');

        if($id){
            $result->where('cvd.id',$id);
        }

        return $result;
    }
}
