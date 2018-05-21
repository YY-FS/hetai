<?php
/**
 * Created by PhpStorm.
 * User: liaodi
 * Date: 2018/5/18
 * Time: 下午6:41
 */

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4MessageReceiver extends BaseModel
{
    const RECEIVER_TYPE_ALL = 0;
    const RECEIVER_TYPE_GROUP = 1;

    public static $receiverTypeDesc = [
        self::RECEIVER_TYPE_ALL => '所有用户',
        self::RECEIVER_TYPE_GROUP => '用户分群',
    ];

    protected $table = 'platv4_message_receiver';

    protected $connection = 'plat';
    public $timestamps = false;

    public static function getMessageReceiver($messageId)
    {
        return DB::connection('plat')->table('platv4_message_receiver')
            ->select([
                'receiver_type',
                DB::raw("ifnull(group_concat(receiver_id), '') as receiver_ids")
            ])
            ->where('message_id', $messageId)
            ->groupBy('receiver_type')
            ->get();
    }
}