<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Platv4Headline extends Model
{
    const STATUS_NORMAL = 1;
    const STATUS_OFFLINE = 0;
    const STATUS_DELETE = -1;

    const TYPE_ARTICLE = 'article';
    const TYPE_VIDEO = 'video';

    const STYLE_SINGLE = 'single';
    const STYLE_MULTIPLE = 'multiple';
    const STYLE_TEXT = 'text';
    const STYLE_BANNER = 'banner';

    public static $styleText = [
        self::STYLE_SINGLE => '单图',
        self::STYLE_MULTIPLE => '3图',
        self::STYLE_BANNER => '大图',
        self::STYLE_TEXT => '纯标题',
    ];

    public static $typeText = [
        self::TYPE_ARTICLE => '文章',
        self::TYPE_VIDEO => '视频',
    ];

    public static $statusText = [
        self::STATUS_NORMAL => '上线',
        self::STATUS_OFFLINE => '下线',
    ];

    public static function rapydGrid()
    {
        return DB::table('platv4_headlines AS h')
            ->leftJoin('platv4_headline_to_tag AS h2t', 'h.id', '=', 'h2t.headline_id')
            ->leftJoin('platv4_headline_tags AS ht', 'h2t.headline_tag_id', '=', 'ht.id')
            ->leftJoin('platv4_cms_admin_users AS u', 'h.admin_user_id', '=', 'u.id')
            ->select(
                'h.*',
                DB::raw('GROUP_CONCAT(ht.name) AS tags'),
                'u.name AS author'
            )
            ->where('h.status', '>=', 0)
            ->groupBy('h.id');
    }

}
