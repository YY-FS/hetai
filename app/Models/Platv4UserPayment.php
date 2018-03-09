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

    //状态
    const STATUS_READY = 0;
    const STATUS_DONE = 1;

    public static $statusText = [
        self::STATUS_DONE => "已完成",
        self::STATUS_READY => "未完成"
    ];

    //支付类型
    const PAY_alipay = 'alipay';
    const PAY_payoffline = 'payoffline';
    const PAY_iap = 'iap';
    const PAY_wechatpay = 'wechatpay';
    const PAY_wxpub = 'wxpub';
    const PAY_free = 'free';
    const PAY_job = 'job';
    const PAY_voucher = 'voucher';
    const PAY_balance = 'balance';
    const PAY_wechat_maka_gzh = 'wechat_maka_gzh';
    const PAY_wechat_maka_pc = 'wechat_maka_pc';
    const PAY_wechat_app = 'wechat_app';
    const PAY_redress = 'redress';
    const PAY_wechat_mini_program = 'wechat_mini_program';

    public static $orderTypeText = [
        self::PAY_alipay => "支付宝",
        self::PAY_payoffline => "线下",
        self::PAY_iap => "苹果内购",
        self::PAY_wechatpay => "旧平台微信支付",
        self::PAY_wxpub => "旧平台公众号",
        self::PAY_free => "免费订单",
        self::PAY_job => "job处理",
        self::PAY_voucher => "模板兑换券",
        self::PAY_balance => "余额",
        self::PAY_wechat_maka_gzh => "MAKA公众号",
        self::PAY_wechat_maka_pc => "MAKA PC扫码",
        self::PAY_wechat_app => "微信app",
        self::PAY_redress => "补偿",
        self::PAY_wechat_mini_program => "微信小程序",
    ];

    //设备
    const SOURCE_wap = 'wap';
    const SOURCE_ios = 'ios';
    const SOURCE_pc = 'pc';
    const SOURCE_android = 'android';
    const SOURCE_mini_program_ios = 'mini_program_ios';
    const SOURCE_mini_program_android = 'mini_program_android';
    const SOURCE_mini_program_other = 'mini_program_other';

    public static $sourceText = [
        self::SOURCE_wap=>'wap端',
        self::SOURCE_ios=>'iOS端',
        self::SOURCE_pc=>'PC端',
        self::SOURCE_android=>'Android端',
        self::SOURCE_mini_program_ios=>'小程序-iOS',
        self::SOURCE_mini_program_android=>'小程序-Android',
        self::SOURCE_mini_program_other=>'小程序-其他',
    ];


    public static function rapydGrid()
    {
        $result = DB::connection('plat')->table('platv4_user_payment as up')
            ->leftJoin('platv4_order_product AS op', 'up.order_id', '=', 'op.order_id')
            ->leftJoin('platv4_pay_platform AS pp','up.order_type', '=', 'pp.alias')
            ->leftJoin('platv4_terminals AS t','up.pay_source', '=', 't.name')
            ->select([
                'up.id',
                'up.order_id',
                'up.uid',
                'pp.name',
                'up.order_amount',
                'up.pay_amount',
                'up.status',
                't.description',
                'up.pay_channel',
                'up.bundle_id',
                'up.app_version',
                'up.date_paid',
                'up.create_time',
                DB::connection('plat')->raw('GROUP_CONCAT( op.`product_id`) as product_id'),
                DB::connection('plat')->raw('GROUP_CONCAT( op.`name`) as product_name'),
                DB::connection('plat')->raw('GROUP_CONCAT( op.`quantity`) as product_quantity'),
                DB::connection('plat')->raw('GROUP_CONCAT( op.`price`) as product_price'),
                DB::connection('plat')->raw('GROUP_CONCAT( op.`total`) as product_total'),
                DB::connection('plat')->raw('GROUP_CONCAT( op.`pay_purpose`) as product_purpose'),
            ])
            ->where('up.status', '>', -1)
            ->groupBy('op.order_id');
        return $result;
        //separator \n
    }
}