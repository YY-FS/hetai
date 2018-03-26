<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-3-23
 * Time: 上午11:02
 */
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4SigninImage extends BaseModel
{
    protected $table = 'platv4_signin_image';
    protected $connection = 'plat';
    public $timestamps = false;

    public static function rapydGrid()
    {
        return DB::connection('plat')->table('platv4_signin_image')
            ->where('status', '>', -1)
            ->select([
                'id',
                'date',
                'thumb',
                'share',
                'title',
                'status',
                'created_at',
            ])
            ->orderBy('date','desc');
    }
}