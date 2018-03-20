<?php

namespace App\Services;

use App\Models\Platv4CustomerVip;
use App\Models\Platv4UserFilter;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: lsd
 * Date: 2018/1/11
 * Time: 16:08
 */
class UserFilterService
{

    const FILE_NAME = 'FILTER_ID:';

    const PER_PAGE = '50000';// 每次生成的用户数

    private function _getFile($source, $filterId)
    {
        $filePath = storage_path('users/filter/' . $source . '/');
        $file = $filePath . self::FILE_NAME . $filterId;
        @unlink($file);
        if (!is_dir($filePath)) {
            mkdir($filePath, 0777, true);
        }
        @touch($file);

        return $file;
    }

    private function _putContents($queryBuild, $filter, $file)
    {

        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $startTime = microtime(true);
        $page = 1;
        $totalUser = 0;
        $inputData = '';
        while ($page !== null) {
            $offset = ($page - 1) * self::PER_PAGE;
            $result = $queryBuild->limit(self::PER_PAGE)->offset($offset)->get()->toArray();

            $ids = array_column((array)$result, 'uid');
            $inputData .= implode(',', $ids);

            if (empty($result)) break;

            $totalUser += count($result);
            $page++;

        }
        if ($inputData) {
            $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');

            file_put_contents($file, $inputData, FILE_APPEND);

            $size = memory_get_peak_usage();
            $top = @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
            echo 'TOP: ' . $top, PHP_EOL;
        }

        $endTime = microtime(true);
        $userFilter = Platv4UserFilter::find($filter->filter_id);
        $userFilter->total_user = $totalUser;
        $userFilter->duration = $endTime - $startTime;
        $userFilter->rise_time = date('Y-m-d H:i:s');
        $userFilter->save();

        return $userFilter;
    }


    public function customerVipMaka($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        $customerVipAlias = 'maka';
        $customerVip = Platv4CustomerVip::where('alias', $customerVipAlias)->first();
        $customerVipId = $customerVip->id;
        $this->_queryBuildCustomerVip($customerVipId, $filter, $file);

        return true;

    }


    public function customerVipPoster($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        $customerVipAlias = 'poster';
        $customerVip = Platv4CustomerVip::where('alias', $customerVipAlias)->first();
        $customerVipId = $customerVip->id;
        $this->_queryBuildCustomerVip($customerVipId, $filter, $file);

        return true;
    }

    public function customerVipVideo($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        $customerVipAlias = 'video';
        $customerVip = Platv4CustomerVip::where('alias', $customerVipAlias)->first();
        $customerVipId = $customerVip->id;
        $this->_queryBuildCustomerVip($customerVipId, $filter, $file);

        return true;
    }

    public function customerVipSenior($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        $customerVipAlias = 'senior';
        $customerVip = Platv4CustomerVip::where('alias', $customerVipAlias)->first();
        $customerVipId = $customerVip->id;
        $this->_queryBuildCustomerVip($customerVipId, $filter, $file);

        return true;
    }

    public function customerVipSuper($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        $customerVipAlias = 'super';
        $customerVip = Platv4CustomerVip::where('alias', $customerVipAlias)->first();
        $customerVipId = $customerVip->id;
        $this->_queryBuildCustomerVip($customerVipId, $filter, $file);

        return true;
    }

    public function vipClass($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        switch ($filter->filter_alias) {
            case 'true':
                $queryBuild = DB::connection('plat')->table('platv4_user_viporder_v2')
                    ->select('uid')
                    ->where('status', 1)
                    ->groupBy('uid');
                break;
            case 'expired':
                $queryBuild = DB::connection('plat')->table('platv4_user_viporder_v2')
                    ->select('uid')
                    ->where('status', 0)
                    ->groupBy('uid');
                break;
            case 'false':
                $queryBuild = DB::connection('plat')->table('platv4_user AS u')
                    ->leftJoin('platv4_user_viporder_v2 AS uv', 'uv.uid', '=', 'u.id')
                    ->select('u.id AS uid')
                    ->where(['uv.id' => null])
                    ->groupBy('u.id');
                break;
            default:
                break;
        }

        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file);
        return true;
    }

    public function industry($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);
        $queryBuild = DB::connection('plat')->table('platv4_user')
            ->select('id AS uid')
            ->where(['industry' => $filter->filter_alias]);

        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file);
        return true;
    }

    public function totalEvent($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        if (empty($filter->filter_remark)) return false;
        $total = explode(',', $filter->filter_remark);
        $min = $total[0];
        $max = ($total[1] === 'null') ? null : $total[1];

        $totalUser = 0;
        $duration = 0;

        for ($i = 0; $i <= 3; $i++) {
            $queryBuild = DB::connection('plat')->table('platv4_user AS u')
                ->leftJoin('platv4_event_' . $i . ' AS e', 'u.id', '=', 'e.uid')
                ->select('u.id as uid')
                ->where(DB::connection('plat')->raw('u.id % 4'), $i)
                ->groupBy('u.id')
                ->having(DB::connection('plat')->raw('COUNT(auto_id)'), '>=', $min);
            if ($max !== null) $queryBuild->having(DB::connection('plat')->raw('COUNT(auto_id)'), '<=', $max);

            $result = $this->_putContents($queryBuild, $filter, $file);

            $totalUser += $result->total_user;
            $duration += $result->duration;
        }

        $userFilter = Platv4UserFilter::find($filter->filter_id);
        $userFilter->total_user = $totalUser;
        $userFilter->duration = $duration;
        $userFilter->save();

        return true;

    }

    public function register($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        if (empty($filter->filter_remark)) return false;
        $times = explode(',', $filter->filter_remark);
        $startDate = date('Y-m-d', strtotime('-' . $times[0] . ' days'));
        $endDate = ($times[1] === 'null') ? null : date('Y-m-d', strtotime('-' . $times[1] . ' days'));

        $queryBuild = DB::connection('plat')->table('platv4_user')
            ->select('id AS uid')
            ->where('date', '<=', $startDate);
        if ($endDate !== null) $queryBuild->where('date', '>', $endDate);

        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file);
        return true;
    }

    public function lastLogin($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        if (empty($filter->filter_remark)) return false;
        $times = explode(',', $filter->filter_remark);
        $startDate = date('Y-m-d', strtotime('-' . $times[0] . ' days'));
        $endDate = ($times[1] === 'null') ? null : date('Y-m-d', strtotime('-' . $times[1] . ' days'));

        $queryBuild = DB::connection('plat')->table('platv4_user')
            ->select('id AS uid')
            ->where('login_time', '<=', $startDate);
        if ($endDate !== null) $queryBuild->where('login_time', '>', $endDate);

        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file);
        return true;

    }

    public function lastPay($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        if (empty($filter->filter_remark)) return false;
        $times = explode(',', $filter->filter_remark);
        $startDate = date('Y-m-d', strtotime('-' . $times[0] . ' days'));
        $endDate = ($times[1] === 'null') ? null : date('Y-m-d', strtotime('-' . $times[1] . ' days'));

        $queryBuild = DB::connection('plat')->table('platv4_user_payment')
            ->select('uid')
            ->where('status', 1)
            ->where('pay_amount', '>', 0)
            ->groupBy('uid')
            ->having(DB::connection('plat')->raw('MAX(pay_date)'), '<=', $startDate);

        if ($endDate !== null) $queryBuild->having(DB::connection('plat')->raw('MAX(pay_date)'), '>', $endDate);

        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file);
        return true;

    }

    public function totalPay($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        if (empty($filter->filter_remark)) return false;
        $total = explode(',', $filter->filter_remark);
        $min = $total[0] * 100;
        $max = ($total[1] === 'null') ? null : ($total[1] * 100);

        $queryBuild = DB::connection('plat')->table('platv4_user as u')
            ->leftJoin('platv4_user_payment as up', function ($join) {
                $join->on('up.uid', '=', 'u.id')
                    ->where('up.status', '=', 1)
                    ->where('up.pay_amount', '>', 0);
            })
            ->select('u.id as uid')
            ->groupBy('u.id');
        if ($min == 0) {
            $queryBuild->havingRaw(DB::connection('plat')->raw('SUM(pay_amount) IS NULL'));
        } else {
            $queryBuild->having(DB::connection('plat')->raw('SUM(pay_amount)'), '>=', $min);
            if ($max !== null) $queryBuild->having(DB::connection('plat')->raw('SUM(pay_amount)'), '<', $max);
        }


        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file);
        return true;
    }

    private function _queryBuildCustomerVip($customerVipId, $filter, $file)
    {
        switch ($filter->filter_alias) {
            case 'gift_code':
                $queryBuild = DB::connection('plat')->table('platv4_user_to_customer_vip AS u2v')
                    ->leftJoin('platv4_user_gift_code AS ugc', function ($join) use ($customerVipId) {
                        $join->on('u2v.uid', '=', 'ugc.uid')
                            ->where('ugc.vip_id', '=', $customerVipId);
                    })
                    ->select('u2v.uid')
                    ->where('u2v.customer_vip_id', $customerVipId)
                    ->groupBy('u2v.uid');

                if ($filter->filter_remark === '1') {
                    $queryBuild->where('ugc.id', '>', 0);
                } elseif ($filter->filter_remark === '0') {
                    $queryBuild->where(['ugc.id' => null]);
                } else {
                    \Log::error(__FUNCTION__ . ' Gen Error: Filter Remark: ' . $filter->filter_remark);
                    \Log::error((array)$filter);
                    return false;
                }

                break;
            case 'sub':
                $queryBuild = DB::connection('plat')->table('platv4_user_to_customer_vip')
                    ->select('uid')
                    ->where('customer_vip_id', $customerVipId)
                    ->where('auto_renewal', (int)$filter->filter_remark)
                    ->groupBy('uid');
                break;
            case 'remain':
                if (empty($filter->filter_remark)) break;
                $times = explode(',', $filter->filter_remark);
                $startDate = date('Y-m-d', strtotime($times[0] . ' days'));
                $endDate = ($times[1] === 'null') ? null : date('Y-m-d', strtotime($times[1] . ' days'));
                $queryBuild = DB::connection('plat')->table('platv4_user_to_customer_vip')
                    ->select('uid')
                    ->where(['customer_vip_id' => $customerVipId, 'status' => 1])
                    ->where('end_date', '>=', $startDate);
                if ($endDate !== null) $queryBuild->where('end_date', '<', $endDate);

                $queryBuild->groupBy('uid');
                break;
            case 'expired':
                if (empty($filter->filter_remark)) break;
                $times = explode(',', $filter->filter_remark);
                $startDate = date('Y-m-d', strtotime('-' . $times[0] . ' days'));
                $endDate = ($times[1] === 'null') ? null : date('Y-m-d', strtotime('-' . $times[1] . ' days'));
                $queryBuild = DB::connection('plat')->table('platv4_user_to_customer_vip')
                    ->select('uid')
                    ->where(['customer_vip_id' => $customerVipId, 'status' => 0])
                    ->where('end_date', '<=', $startDate);
                if ($endDate !== null) $queryBuild->where('end_date', '>', $endDate);

                $queryBuild->groupBy('uid');
                break;
            case 'false':
                $queryBuild = DB::connection('plat')->table('platv4_user AS u')
                    ->leftJoin('platv4_user_to_customer_vip AS u2v', function ($join) use ($customerVipId) {
                        $join->on('u2v.uid', '=', 'u.id')
                            ->where('u2v.customer_vip_id', '=', $customerVipId);
                    })
                    ->select('u.id AS uid')
                    ->where(['u2v.status' => null])
                    ->groupBy('u.id');
                break;
            default:
                break;
        }

        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file);
        return true;
    }

}