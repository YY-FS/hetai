<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-3-9
 * Time: 上午11:27
 */
namespace App\Models;

class Platv4PayPlatform extends BaseModel
{
    protected $table = 'platv4_pay_platform';
    protected $connection = 'plat';
    public $timestamps = false;
}