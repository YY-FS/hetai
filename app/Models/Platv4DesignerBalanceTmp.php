<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4DesignerBalanceTmp extends BaseModel
{
    protected $table='platv4_designer_balance_tmp';
    protected $connection = 'plat';
    public $timestamps = false;

    const BALANCE_TYPE_INCOME = 1;
    const BALANCE_TYPE_WITHDRAW = 2;
    const BALANCE_TYPE_ADMIN = 3;

    public static $balanceTypeText = [
        self::BALANCE_TYPE_INCOME => '模板收入',
        self::BALANCE_TYPE_WITHDRAW => '提现',
        self::BALANCE_TYPE_ADMIN => '管理员操作',
    ];

}
