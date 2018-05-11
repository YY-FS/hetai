<?php
/**
 * Created by PhpStorm.
 * User: milkmeowo
 * Date: 2018/5/11
 * Time: 下午5:44
 */

namespace App\Models;


class Platv4WechatSubscriber extends BaseModel
{
    protected $table = "platv4_wechat_subscribers";
    protected $connection = 'plat';

    public function platv4User()
    {
        return $this->hasOne(Platv4User::class, 'weixin', 'unionid');
    }
}