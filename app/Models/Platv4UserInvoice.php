<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4UserInvoice extends BaseModel
{
    protected $table = 'platv4_user_invoice';
    protected $connection = 'plat';
    public $timestamps = false;

    //0：待审核 1：已通过 -1：不通过
    public static $statusText=[
        self::COMMON_STATUS_OFFLINE=>'待审核',//0
        self::COMMON_STATUS_DELETE=>'不通过',
        self::COMMON_STATUS_NORMAL=>'已通过'//1
    ];

    public static function rapydGrid($invoiceType)
    {
        $result = DB::connection('plat')->table('platv4_user_invoice')
            ->select([
                'id',//id
                'uid',
                'status',//状态
                'created_at',//申请日期
                'uid',//UID
                'invoice_title',//发票抬头
                'tax_no',//税号
                'contact_name',//联系方式
                'contact',//联系人
                'reason',//备注
            ])
            ->where('invoice_type',$invoiceType)
            ->orderBy('created_at','desc');
        return $result;
    }
}

