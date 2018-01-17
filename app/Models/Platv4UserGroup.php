<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4UserGroup extends BaseModel
{
    protected $table='platv4_user_group_v2';
    protected $connection = 'plat';

    const GROUP_MODE_AUTO = 'auto';
    const GROUP_MODE_HAND = 'manual';

    public static $groupMode = [
        self::GROUP_MODE_AUTO => '自动分群',
        self::GROUP_MODE_HAND => '手动分群',
    ];

    const STATUS_FAIL = -2;
    const STATUS_GROUPING = 0;
    const STATUS_NORMAL = 1;

    static $status=[
        self::STATUS_NORMAL=>'正常',
        self::STATUS_FAIL=>'属性保存失败',
        self::STATUS_GROUPING=>'分组进行中'
    ];
}