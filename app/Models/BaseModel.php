<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    const COMMON_STATUS_NORMAL = 1;
    const COMMON_STATUS_OFFLINE = 0;
    const COMMON_STATUS_DELETE = -1;

    public static $commonStatusText = [
        self::COMMON_STATUS_NORMAL => '上线',
        self::COMMON_STATUS_OFFLINE => '下线',
    ];
}
