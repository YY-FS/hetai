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

    public $maxUid;

    public function __construct()
    {
        $this->maxUid = DB::connection('plat')->table('platv4_user')->orderBy('id', 'desc')->first()->id;
    }

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


    /**
     * @param $queryBuild
     * @param $filter
     * @param $file
     * @param $options  @max_id @field
     * @return bool
     */
    private function _putContents($queryBuild, $filter, $file, $options)
    {
        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }
        if (empty($options['max_id'])) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty max_id');
            \Log::error((array)$filter);
            \Log::error((array)$options);
            return false;
        }
        if (empty($options['field'])) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty field');
            \Log::error((array)$filter);
            return false;
        }

        $maxId = $options['max_id'];
        $field = $options['field'];

        $startTime = microtime(true);
        $page = 1;
        $totalUser = 0;

        $queryBuild->where($field, '>', 0)->where($field, '<=', self::PER_PAGE);
        while ($page !== null) {

            $offset = ($page - 1) * self::PER_PAGE;
            if ($offset > $maxId) {
                \Log::info('-----  id分页器，offset 大于最大用户ID，page: ' . $page);
                break;
            }
            $endSet = (int)$offset + (int)self::PER_PAGE;

            $bindings = $queryBuild->getRawBindings()['where'];
            $newBind = array_slice($bindings, 0, -2);
            $newBind[] = $offset;
            $newBind[] = $endSet;
            $queryBuild->setBindings($newBind);

            $result = $queryBuild->get()->toArray();
//            var_dump($queryBuild->toSql());
//            var_dump($queryBuild->getBindings());

            $ids = array_column((array)$result, 'uid');
            $inputData = implode(',', $ids);
            if ($inputData) {
                file_put_contents($file, $inputData, FILE_APPEND);
            }

//            if (empty($result)) break;

            $totalUser += count($result);
            $page++;

        }

        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        $size = memory_get_peak_usage();
        $top = @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
        echo 'PAGE: ' . $page . ', TOP: ' . $top, PHP_EOL;

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

        $options = [
            'max_id' => $this->maxUid,
            'field' => 'id'
        ];

        switch ($filter->filter_alias) {
            case 'true':
                $queryBuild = DB::connection('plat')->table('platv4_user_viporder_v2')
                    ->select('uid')
                    ->where('status', 1)
                    ->groupBy('uid');
                $options['max_id'] = DB::connection('plat')->table('platv4_user_viporder_v2')->orderBy('id', 'desc')->first()->id;
                break;
            case 'expired':
                $queryBuild = DB::connection('plat')->table('platv4_user_viporder_v2')
                    ->select('uid')
                    ->where('status', 0)
                    ->groupBy('uid');
                $options['max_id'] = DB::connection('plat')->table('platv4_user_viporder_v2')->orderBy('id', 'desc')->first()->id;
                break;
            case 'false':
                $queryBuild = DB::connection('plat')->table('platv4_user AS u')
                    ->leftJoin('platv4_user_viporder_v2 AS uv', 'uv.uid', '=', 'u.id')
                    ->select('u.id AS uid')
                    ->where(['uv.id' => null])
                    ->groupBy('u.id');
                $options['field'] = 'u.id';
                break;
            default:
                break;
        }

        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file, $options);
        return true;
    }

    public function industry($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        $options = [
            'max_id' => 0,
            'field' => 'id'
        ];

        $queryBuild = DB::connection('plat')->table('platv4_user')
            ->select('id AS uid')
            ->where(['industry' => $filter->filter_alias]);

        $options['max_id'] = $this->maxUid;

        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file, $options);
        return true;
    }

    public function totalEvent($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        $options = [
            'max_id' => $this->maxUid,
            'field' => 'id'
        ];

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

            $options['field'] = 'u.id';

            $result = $this->_putContents($queryBuild, $filter, $file, $options);

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

        $options = [
            'max_id' => 0,
            'field' => 'id'
        ];

        if (empty($filter->filter_remark)) return false;
        $times = explode(',', $filter->filter_remark);
        $startDate = date('Y-m-d', strtotime('-' . ($times[0] - 1 ) . ' days'));
        $endDate = ($times[1] === 'null') ? null : date('Y-m-d', strtotime('-' . ($times[1] - 1) . ' days'));

        $queryBuild = DB::connection('plat')->table('platv4_user')
            ->select('id AS uid')
            ->where('date', '<', $startDate);
        if ($endDate !== null) $queryBuild->where('date', '>=', $endDate);


        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file, $options);
        return true;
    }

    public function lastLogin($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        $options = [
            'max_id' => $this->maxUid,
            'field' => 'id'
        ];

        if (empty($filter->filter_remark)) return false;
        $times = explode(',', $filter->filter_remark);
        $startDate = date('Y-m-d', strtotime('-' . ($times[0] - 1) . ' days'));
        $endDate = ($times[1] === 'null') ? null : date('Y-m-d', strtotime('-' . ($times[1] - 1) . ' days'));

        $queryBuild = DB::connection('plat')->table('platv4_user')
            ->select('id AS uid')
            ->where('login_time', '<', $startDate);
        if ($endDate !== null) $queryBuild->where('login_time', '>=', $endDate);

        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file, $options);
        return true;

    }

    public function lastPay($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        $options = [
            'max_id' => $this->maxUid,
            'field' => 'id'
        ];

        if (empty($filter->filter_remark)) return false;
        $times = explode(',', $filter->filter_remark);
        $startDate = date('Y-m-d', strtotime('-' . ($times[0] - 1) . ' days'));
        $endDate = ($times[1] === 'null') ? null : date('Y-m-d', strtotime('-' . ($times[1] - 1) . ' days'));

        $queryBuild = DB::connection('plat')->table('platv4_user_payment')
            ->select('uid')
            ->where('status', 1)
            ->where('pay_amount', '>', 0)
            ->groupBy('uid')
            ->having(DB::connection('plat')->raw('MAX(pay_date)'), '<', $startDate);

        if ($endDate !== null) $queryBuild->having(DB::connection('plat')->raw('MAX(pay_date)'), '>=', $endDate);

        $options['max_id'] = DB::connection('plat')->table('platv4_user_payment')->orderBy('id', 'desc')->first()->id;

        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file, $options);
        return true;

    }

    public function totalPay($filter)
    {
        echo __FUNCTION__ . PHP_EOL;
        $file = $this->_getFile(__FUNCTION__, $filter->filter_id);

        $options = [
            'max_id' => $this->maxUid,
            'field' => 'id'
        ];

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

        $options['field'] = 'u.id';

        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file, $options);
        return true;
    }

    private function _queryBuildCustomerVip($customerVipId, $filter, $file)
    {
        $options = [
            'max_id' => $this->maxUid,
            'field' => 'id'
        ];

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

                $options['max_id'] = DB::connection('plat')->table('platv4_user_to_customer_vip')->orderBy('id', 'desc')->first()->id;
                $options['field'] = 'u2v.id';
                break;
            case 'sub':
                $queryBuild = DB::connection('plat')->table('platv4_user_to_customer_vip')
                    ->select('uid')
                    ->where('customer_vip_id', $customerVipId)
                    ->where('auto_renewal', (int)$filter->filter_remark)
                    ->groupBy('uid');
                $options['max_id'] = DB::connection('plat')->table('platv4_user_to_customer_vip')->orderBy('id', 'desc')->first()->id;

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
                $options['max_id'] = DB::connection('plat')->table('platv4_user_to_customer_vip')->orderBy('id', 'desc')->first()->id;

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
                $options['max_id'] = DB::connection('plat')->table('platv4_user_to_customer_vip')->orderBy('id', 'desc')->first()->id;
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
                $options['field'] = 'u.id';
                break;
            default:
                break;
        }

        if (empty($queryBuild)) {
            \Log::error(__FUNCTION__ . ' Gen Error: Empty QueryBuild');
            \Log::error((array)$filter);
            return false;
        }

        $this->_putContents($queryBuild, $filter, $file, $options);
        return true;
    }

}