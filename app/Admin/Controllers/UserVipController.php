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

        $filter = DataFilter::source(Platv4UserToCustomerVip::rapdyGrid());
        $filter->add('uid', '用户ID', 'number')
            ->scope(function ($query, $value) {
                return $value ? $query->orWhere('u.id', $value) : $query;
            });
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
        $grid->add('start_date', '生效时间', false);
        $grid->add('end_date', '到期时间', false);
        $grid->add('operation', '操作', false);
        $grid->orderBy('start_date', 'asc');

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.perfix') . '/user/vip/edit', '添加', 'TR', ['class' => 'btn btn-primary']);

        $grid->row(function ($row) use (&$title) {
            $result = Platv4UserToCustomerVip::checkStatus($row);
            if (empty($result)) {
                $row->cell('status')->value = '发生错误，请检查各时间是否正确!!!';
                $row->cell('status')->style('color:red;');
            } else {
                if ($result['status'] != 1) {
                    $row->cell('status')->style('color:red;');
                }
                $row->cell('status')->value = Platv4UserToCustomerVip::$statusText[$result['status']];
            }
            $row->cell('operation')->value = $this->getEditBtn($row->data->id);
        });

        $grid->build();
        if (Input::get('export') == 1) {
            return $grid->buildCSV($title, 'Ymd');
        }
        return view('rapyd.filtergrid', compact('filter', 'grid', 'title'));
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
            $edit->add('uid', '用户UID', 'number')->rule('required');
        }

        $edit->label('会员用户');
        $edit->link(config('admin.route.perfix') . '/user/vip', '列表', 'TR')->back();
        $edit->add('customer_vip_id', '会员类型', 'select')->rule('required')->options(['' => '全部类型'] + Platv4CustomerVip::pluck('name', 'id')->toArray());
        $edit->add('start_date', '开始时间', 'text')->rule('required')->placeholder('输入格式如：2018-03-15');
        $edit->add('end_date', '结束时间', 'text')->rule('required')->placeholder('输入格式如：2018-03-15');
        $edit->add('status', '状态', 'select')->rule('required')->options([Platv4UserToCustomerVip::$statusText]);

        $edit->saved(function () use ($edit) {
            try {
                //清除redis缓存
                Redis::del('QUERYSERVICE:TYPE:VIP:CONFIG:{$edit->model->uid}');
                Redis::hdel('CUSTOMER_VIP_USER_LEASE_TYPE', $edit->model->uid);
                
                DB::connection('plat')->beginTransaction();
                $vipId = Input::post('customer_vip_id');
                $vipName = Platv4CustomerVip::find($vipId)->toArray();
                $edit->model->customer_vip_name = $vipName['name'];
                $edit->model->save();
                DB::connection('plat')->commit();
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