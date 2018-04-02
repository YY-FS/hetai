<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4Invoice extends BaseModel
{
    protected $table = 'platv4_invoice';
    protected $connection = 'plat';

    const STATUS_AUDIT_WAIT = 0;
    const STATUS_AUDIT_FAILED = -1;
    const STATUS_AUDIT_SUCCESS = 1;
    const STATUS_SENT = 2;

    const ROUTE_ELECTRON = 'electron';
    const ROUTE_COMMON = 'common';
    const ROUTE_SPECIAL = 'special';

    const CONTENT_SERVICE = 'service';
    const CONTENT_DEVELOPMENT = 'development';
    const CONTENT_ADVERT = 'advert';
    const CONTENT_TECHNIQUE = 'technique';
    const CONTENT_DESIGN = 'design';

    static $statusText = [
        'text'=>[
            self::STATUS_AUDIT_WAIT => '待审核',
            self::STATUS_AUDIT_FAILED => '不通过',
            self::STATUS_AUDIT_SUCCESS => '待发出',
            self::STATUS_SENT => '已发出'
        ],
        'style'=>[
            self::STATUS_AUDIT_WAIT => 'color:blue;',
            self::STATUS_AUDIT_FAILED => 'color:red;',
            self::STATUS_AUDIT_SUCCESS => 'color:green;',
            self::STATUS_SENT => 'color:grey;'
        ]
    ];

    static $routeText = [
        self::ROUTE_COMMON => '普通纸质',
        self::ROUTE_ELECTRON => '电子',
        self::ROUTE_SPECIAL => '增值税专用'
    ];

    static $contentText = [
        self::CONTENT_SERVICE => '软件服务',
        self::CONTENT_DEVELOPMENT => '软件制作费',
        self::CONTENT_ADVERT => '广告服务费',
        self::CONTENT_TECHNIQUE => '技术服务费',
        self::CONTENT_DESIGN => '设计服务费'
    ];

    protected static $queryArr = [
        'electron'=>['v.deliver_type'=>self::ROUTE_ELECTRON],
        'common'=>['v.deliver_type'=>'paper'],
        'special'=>['uv.invoice_type'=>self::ROUTE_SPECIAL]
    ];

    public static function rapydGrid($type)
    {
        $result =  DB::connection('plat')->table('platv4_invoice as v')
            ->leftJoin('platv4_user_invoice as uv','v.user_invoice_id','uv.id')
            ->leftJoin('platv4_user_address as ua','v.user_address_id','ua.id')
            ->leftJoin('platv4_province as p','ua.province_id','p.province_id')
            ->leftJoin('platv4_city as c','ua.city_id','c.city_id')
            ->leftJoin('platv4_district as d','ua.district_id','d.district_id')
            ->select([
                'v.status',
                'v.id',
                'v.created_at',
                'v.uid',
                'uv.invoice_title',
                'uv.tax_no',
                'v.content',
                'v.total',
                'v.contact as phone',
                'v.contact_name',
                'v.invoice_no',
                'v.express',
                'v.express_no',
                'v.email',
                'v.comment',
                'p.name',
                'c.name',
                'd.name',
                'd.name',
                'ua.address',
                'ua.name',
                'ua.contact as receive_tel',
            ])
            ->where(self::$queryArr[$type]);
        if($type == self::ROUTE_COMMON)
            $result->whereIn('uv.invoice_type',['person','company']);
        return $result;
    }

    public static function getOrders($invoiceId)
    {
        return DB::connection('plat')->table('platv4_invoice_to_order_product as i2op')
            ->leftJoin('platv4_order_product as op','i2op.order_product_id','op.id')
            ->leftJoin('platv4_user_payment as up','op.order_id','up.order_id')
            ->leftJoin('platv4_pay_platform as pp','up.order_type','pp.alias')
            ->select([
                'up.pay_date',
                'up.order_id',
                'op.name as purchase',
                'op.total as price',
                'pp.name as pay_way',
            ])
            ->where('i2op.invoice_id',$invoiceId)
            ->orderBy('up.pay_date','desc');
    }

    public static function getAudit($invoiceId)
    {
       return DB::connection('plat')->table('platv4_invoice as v')
            ->leftJoin('platv4_user_invoice as uv','v.user_invoice_id','uv.id')
            ->leftJoin('platv4_user_address as ua','v.user_address_id','ua.id')
            ->leftJoin('platv4_province as p','ua.province_id','p.province_id')
            ->leftJoin('platv4_city as c','ua.city_id','c.city_id')
            ->leftJoin('platv4_district as d','ua.district_id','d.district_id')
            ->select([
                'uv.invoice_title',
                'uv.tax_no',
                'v.content',
                'v.total',
                'v.contact as contact_phone',
                'v.contact_name as contact_man',
                'v.email',
                'uv.address as register_addr',
                'uv.phone as register_phone',
                'uv.bank_name',
                'uv.bank_account',
                'ua.name as receive_man',
                'ua.contact as receive_phone',
                'ua.postcode',
                'p.name as province',
                'c.name as city',
                'd.name as district',
                'ua.address as addr',
                'ua.comment'
            ])
            ->where('v.id',$invoiceId)
            ->first();
    }

}