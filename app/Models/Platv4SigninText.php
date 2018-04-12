<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-4-12
 * Time: 上午9:42
 */
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4SigninText extends BaseModel
{
    protected $table = 'platv4_signin_text';
    protected $connection = 'plat';
    public $timestamps = false;

    public static function rapydGrid()
    {
        return DB::connection('plat')->table('platv4_signin_text')
            ->select([
                'id',
                'date',
                'text',
                'status'
            ])
            ->where('status','<>',-1)
            ->orderBy('date','desc');
    }

}