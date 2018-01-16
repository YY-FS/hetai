<?php
/**
 * Created by PhpStorm.
 * User: lsd
 * Date: 2018/1/15
 * Time: 17:46
 */

namespace App\Services;


use App\Models\Platv4UserGroup;
use App\Models\Platv4UserGroupToFilter;
use Illuminate\Support\Facades\Redis;

class UserGroupService
{
    const CACHE_USER_GROUP = 'CMS:CMD:USER_GROUP:ID:';

    private function _test()
    {
        $file1 = storage_path('users/filter/customerVipMAKA/') . UserFilterService::FILE_NAME  . '25';
        $file2 = storage_path('users/filter/customerVipMAKA/') . UserFilterService::FILE_NAME  . '4';
        $data1 = explode(',', file_get_contents($file1));
//        var_dump($data1);
        $unit=array('b','kb','mb','gb','tb','pb');
        $size = memory_get_peak_usage();
        $top = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
        echo 'TOP: ' . $top , PHP_EOL;

        unset($file1);
        $size = memory_get_peak_usage();
        $top = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
        echo 'TOP2: ' . $top , PHP_EOL;
    }

//    H5 会员-非兑换卡 + 非订阅   ∩   海报-非兑换卡   2，4 ∩ 27
    public function genGroupUser($userGroupId)
    {
        $groupFilters = Platv4UserGroupToFilter::getUserGroupFilter($userGroupId);
        if (empty($groupFilters)) {
            // log todo
            return false;
        }

        $groupUser = null; // 最终用户数组，用于取交集的数组
        foreach ($groupFilters as $groupFilter) {
            $filterUser = [];
            foreach (explode(',', $groupFilter->filter_ids) AS $filterId) {
                $dataFile = storage_path('users/filter/' . $groupFilter->filter_type_alias . '/') . UserFilterService::FILE_NAME . $filterId;
                $data = explode(',', file_get_contents($dataFile));
                $filterUser = array_merge($filterUser, $data);
            }
            if ($groupUser === null) {
//                初始化
                $groupUser = $filterUser;
            } else {
                $groupUser = array_intersect($groupUser, $filterUser);
            }
        }
        var_dump($groupUser);

//        存redis
        $cacheKey = self::CACHE_USER_GROUP . $userGroupId;
        Redis::del($cacheKey);
        foreach ($groupUser as $uid) {
            Redis::hset($cacheKey, $uid, 1);
        }

        $userGroup = Platv4UserGroup::find($userGroupId);
        $userGroup->user_total = count($groupUser);
        $userGroup->status = Platv4UserGroup::STATUS_NORMAL;
        $userGroup->rise_time = date('Y-m-d H:i:s');
        $userGroup->save();

    }


}