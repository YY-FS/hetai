<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4IndustryToHeadlineTag extends BaseModel
{
    protected $table='platv4_industry_to_headline_tag';
    public $timestamps = false;

    public static function getHeadlineTagsName($industryId)
    {
        $tagName = '无';
        $list = DB::table('platv4_industry_to_headline_tag AS i2t')
            ->leftJoin('platv4_headline_tags AS ht', 'i2t.headline_tag_id', '=', 'ht.id')
            ->select(
                'ht.*'
            )
            ->where('i2t.industry_id', $industryId)
            ->get()->toArray();

        if ($list) {
            $tagName = implode(',', array_column($list, 'name'));
        }

        return $tagName;
    }
}

