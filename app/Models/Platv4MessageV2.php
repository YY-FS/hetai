<?php
/**
 * Created by PhpStorm.
 * User: liaodi
 * Date: 2018/5/18
 * Time: 下午6:41
 */

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4MessageV2 extends BaseModel
{
    protected $connection = 'plat';
    protected $table = 'platv4_message_v2';
    public $timestamps = false;

    const MESSAGE_TYPE_CENTER = 1;
    const MESSAGE_TYPE_TRUMPET = 2;

    public static $messageTypeDesc = [
        self::MESSAGE_TYPE_CENTER=> '通知中心',
        self::MESSAGE_TYPE_TRUMPET => '小喇叭',
    ];

    public static $messageStatusDesc = [
        0 => '草稿',
        1 => '发送',
        2 => '删除',
    ];

    public static function rapydGrid($where)
    {
        $result = DB::connection('plat')->table('platv4_message_v2 as mess')
            ->select([
                'mess.id',
                'mess.title',
                'mess.status',
                DB::raw('from_unixtime(mess.create_time) as create_time'),
                DB::raw('from_unixtime(mess.start_time) as start_time'),
                DB::raw('from_unixtime(mess.end_time) as end_time'),
                DB::raw("ifnull(group_concat(recv.receiver_type, '-', recv.receiver_id), '') as receiver_info"),
                DB::raw("ifnull(group_concat(dev.device), '') as device_info")
            ])
            ->leftJoin('platv4_message_receiver AS recv', 'mess.id', '=', 'recv.message_id')
            ->leftJoin('platv4_message_device AS dev', 'mess.id', '=', 'dev.message_id')
            ->where('status', '<>', 2)
            ->groupBy('mess.id');
        if (!empty($where)) $result->where($where);
        return $result;
    }

    public function getCreateTimeAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    public function getStartTimeAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    public function getEndTimeAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    public function setCreateTimeAttribute($value)
    {
        $this->attributes['create_time'] = $value ? strtotime($value) : 0;
    }

    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = $value ? strtotime($value) : 0;
    }

    public function setEndTimeAttribute($value)
    {
        $this->attributes['end_time'] = $value ? strtotime($value) : 0;
    }
}