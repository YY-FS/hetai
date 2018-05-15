<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-3-12
 * Time: 下午6:27
 */
namespace App\Admin\Controllers;

use App\Models\Platv4CustomerVip;
use App\Models\Platv4User;
use App\Models\Platv4UserToCustomerVip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\Url;

class UserVipController extends BaseController
{
    public function index()
    {
        $this->route = '/user/vip';

        $title = '会员用户管理';

        $where = [];
        if (!Input::get('search', null)) {
            $date = date('Y-m-d', time());
            $tomorrow = date('Y-m-d', time()+24 * 60 * 60);
            //默认取出当天的开通的会员用户
            $where = [
                ['v.create_time', '>=',$date],
                ['v.create_time', '<',$tomorrow]
            ];
        }

        $filter = DataFilter::source(Platv4UserToCustomerVip::rapdyGrid($where));
        $filter->add('uid', '用户ID', 'number')
            ->scope(function ($query, $value) {
                return $value ? $query->where('u.id', $value) : $query;
            });
        $filter->add('username', '账号', 'text')
            ->scope(function ($query, $value) {
                return $value ? $query->where('u.username', $value) : $query;
            });
        $filter->add('create_time', '开通时间', 'daterange')
            ->scope(function ($query, $value) {
                $value = explode('|', $value);
                if (!empty($value[0]))
                    $query = $query->where('v.create_time', '>=', $value[0]);
                if (!empty($value[1])) {
                    $value[1] = date('Y-m-d', strtotime($value[1]) + 24 * 60 * 60);//增加一天，date_paid比后一天的00:00:00小就好
                    $query = $query->where('v.create_time', '<=', $value[1]);
                }
                return $query;
            })->format('Y-m-d', 'zh-CN');
        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);
        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('uid', 'UID', false);
        $grid->add('username', '账号', false);
        $grid->add('status', '会员状态', false);
        $grid->add('customer_vip_name', '会员版本', false);
        $grid->add('create_time', '开通时间', true);
        $grid->add('start_date', '生效时间', true);
        $grid->add('end_date', '到期时间', true);
        $grid->add('operation', '操作', false);
        $grid->orderBy('start_date', 'asc');

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.perfix') . '/user/vip/edit', '添加', 'TR', ['class' => 'btn btn-success']);

        $grid->row(function ($row) use (&$title) {
            //状态颜色判断
            if ($row->data->status !== 1)
                $row->cell('status')->style('color:red;');
            //状态正确判断
            if (in_array($row->data->status, [0, 1]))
                $row->cell('status')->value = Platv4UserToCustomerVip::$statusText[$row->data->status];
            else
                $row->cell('status')->value = '发生错误，请检查审核状态是否正确!!!';

            $row->cell('operation')->value = $this->getEditBtn($row->data->id);
        });
        if (Input::get('export') == 1) {
            $grid->build();
            return $grid->buildCSV($title, 'Ymd');
        } else {
            $grid->paginate(self::DEFAULT_PER_PAGE);
            $grid->build();
            return view('rapyd.filtergrid', compact('filter', 'grid', 'title'));
        }
    }

    public function anyEdit()
    {
        $edit = DataEdit::source(new Platv4UserToCustomerVip());
        //检查是否是修改页面
        $modifyId = Input::get('modify', null);

        if ($modifyId) {
            $username = DB::connection('plat')->table('platv4_user')->where('id', $edit->model->uid)->pluck('username')->first();
            $edit->model->username = $username;
            //修改时，UID不可修改
            $edit->add('uid', '用户UID', 'text')->attributes(['readOnly' => true])->rule('required')->insertValue($edit->model->uid);
            $edit->add('username', '账户', 'text')->attributes(['readOnly' => true])->rule('required')->insertValue($edit->model->username);
        } else {
            $edit->add('uid', '用户UID', 'number')->rule('required|exists:plat.platv4_user,id');
        }

        $edit->label('会员用户');
        //返回查出来的结果的地址
        $edit->link(url()->previous(), '列表', 'TR')->back();
        $edit->add('customer_vip_id', '会员类型', 'select')->rule('required|unique:plat.platv4_user_to_customer_vip,customer_vip_id,' . $edit->model->customer_vip_id . ',customer_vip_id,uid,' . $edit->model->uid)->options(['' => '全部类型'] + Platv4CustomerVip::pluck('name', 'id')->toArray());
        $edit->add('start_date', '开始时间', 'date')->rule('required')->placeholder('输入格式如：2018-03-15');
        $edit->add('end_date', '结束时间', 'date')->rule('required')->placeholder('输入格式如：2018-03-15');
        $edit->add('status', '状态', 'select')->rule('required')->options(Platv4UserToCustomerVip::$statusText);

        $edit->saved(function () use ($edit) {
            try {
                //清除redis缓存
                Redis::del('QUERYSERVICE:TYPE:VIP:CONFIG:' . $edit->model->uid);
                Redis::hdel('CUSTOMER_VIP_USER_LEASE_TYPE', $edit->model->uid);

                DB::connection('plat')->beginTransaction();
                $vipId = Input::post('customer_vip_id');
                $vipName = Platv4CustomerVip::find($vipId)->toArray();
                $edit->model->customer_vip_name = $vipName['name'];
                $edit->model->save();
                DB::connection('plat')->commit();
                return redirect(config('admin.route.prefix') . '/user/vip?uid=' . $edit->model->uid . '&search=1');//提交完后返回上一个界面
            } catch (\Exception $e) {
                \Log::error('出现错误:' . $e->getMessage());
                DB::connection('plat')->rollback();
                return redirect('error')->with([
                    'to' => config('admin.route.prefix') . '/user/vip',
                    'msg' => '会员用户编辑失败：' . $e->getMessage()
                ]);
            }
        });

        $edit->build();
        return $edit->view('rapyd.edit', compact('edit'));
    }
}