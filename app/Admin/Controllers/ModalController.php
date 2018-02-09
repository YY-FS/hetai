<?php
namespace App\Admin\Controllers;

use App\Models\Platv4CustomerVipDiscount;
use App\Models\Platv4ItemToUserGroup;
use App\Models\Platv4Modal;
use App\Models\Platv4ModalToTerminal;
use App\Models\Platv4Terminal;
use App\Models\Platv4UserGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Mockery\Exception;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataForm\DataForm;
use Zofe\Rapyd\Url;

class ModalController extends BaseController
{
    public function index()
    {
        $this->route = 'modal';

        $title = '模态窗管理';
        $filter = DataFilter::source(Platv4Modal::rapydGrid());
        $filter->add('id','ID','text')
            ->scope(function($query,$value){
                if($value){
                    return $query->where('m.id',$value);
                }else{
                    return $query;
                }
            });
        $filter->add('name','弹窗名称','text')
            ->scope(function($query,$value){
                if($value){
                    return $query->where('m.name','like','%'.$value.'%');
                }else{
                    return $query;
                }
            });
        $filter->add('status', '状态', 'select')->options(['' => '全部状态'] + Platv4Modal::$statusText)
            ->scope(function($query,$value){
                if($value){
                    return $query->where('m.status',$value);
                }else{
                    return $query;
                }
            });

        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);

        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id','ID',false);
        $grid->add('sort','排序',true);
        $grid->add('status','状态',false);
        $grid->add('name','弹窗名称',false);
        $grid->add('{{ $discount_group or $modal_group }}','用户分群',false);
        $grid->add('comment','备注',false);
        $grid->add('start_time','弹窗开始时间',false);
        $grid->add('end_time','弹窗结束时间',false);
        $grid->add('created_at','创建时间',false);
        $grid->add('operation','操作',false);

        $grid->orderBy('sort', 'asc');

        $grid->link(config('admin.route.prefix') . '/modal/edit', '添加', 'TR', ['class' => 'btn btn-primary']);

        $grid->row(function($row) use (&$title){

            $result = Platv4Modal::checkStatus($row);
            if(empty($result)){
                $row->cell('status')->value = '发生错误，请检查各时间是否正确!!!';
                $row->cell('status')->style("color:red;");
                $row->cell('operation')->value = $this->getEditBtn($row->data->id);
            }else{
                $row->cell('status')->value = Platv4Modal::$statusText[$result['status']];
                $toStatus = $result['toStatus'];

                $row->cell('status')->style($result['style']);
                $statusText = $result['toStatusText'];

                $row->cell('operation')->value = $this->getEditBtn($row->data->id) . $this->getStatusBtn($row->data->id, $toStatus, $statusText);
                if($toStatus == Platv4Modal::STATUS_READY){
                    $row->cell('operation')->value.= $this->getDeleteBtn($row->data->id);
                }
            }

        });

        $grid->paginate(self::DEFAULT_PER_PAGE);
        $grid->build();

        return view('rapyd.filtergrid', compact('filter', 'grid', 'title','tips'));
    }

    public function anyEdit()
    {
        //        软删除
        $deleteId = Input::get('delete', null);
        if ($deleteId) {
            Platv4Modal::where('id', $deleteId)->update(['status' => Platv4Modal::STATUS_DELETE]);
            return redirect()->back();
        }

        //        上下线
        if (!is_null($status = Input::get('status', null)) && !is_null($id = Input::get('id', null))) {
            Platv4Modal::where('id', $id)->update(['status' => $status]);
            return redirect()->back();
        }

        $edit = DataEdit::source(new Platv4Modal());

        //检查是否是修改页面
        $modifyId = Input::get('modify',0);
        if($modifyId){
            //选中选项
            $terminal = Platv4ModalToTerminal::where('modal_id',$edit->model->id)->pluck('terminal')->toArray();
            Input::offsetSet('terminal',$terminal);
            $group = Platv4ItemToUserGroup::where('item_id',$edit->model->id)->where('item_table','platv4_modal')->pluck('user_group_id')->toArray();
            empty($group) && Input::offsetSet('group',$group);
            $way = $edit->model->customer_vip_discount_id > 0?'discount':'group';
            Input::offsetSet('way',$way);
            //比较重要的字段拉出来在saved中更新
            Input::offsetSet('begin_time',$edit->model->start_time);
            Input::offsetSet('over_time',$edit->model->end_time);
            Input::offsetSet('discount_id',$edit->model->customer_vip_discount_id);
        }

        //检查是否从活动页进入
        $bindId = Input::get('bind',0);
        if($bindId){
            Input::offsetSet('way','discount');
            Input::offsetSet('customer_vip_discount_id',$bindId);
        }

        $edit->label('弹窗信息');
        $edit->link(config('admin.route.prefix') . "/modal", "列表", "TR")->back();
        $edit->add('name','弹窗名称','text')
            ->rule('required')
            ->placeholder('请输入弹窗名称');
        $edit->add('thumb', '弹窗图片', 'text')
            ->attributes(['readOnly' => true]);
        $edit->add('terminal','平台','checkboxgroup')->rule('required')
            ->options(Platv4Terminal::all()->pluck('description','name')->toArray());
        $edit->add('url','跳转链接','text')->rule('required');
        $edit->add('way','弹窗策略','radiogroup')->rule('required')
            ->options(['group'=>'用户分群','discount'=>'绑定活动']);
        $edit->add('group','用户分群','checkboxgroup')
            ->options(Platv4UserGroup::where('status','<>',-1)->get()->pluck('name','id')->toArray());
        $edit->add('discount_id','活动列表','select')
            ->options(['请选择活动']+Platv4CustomerVipDiscount::all()->pluck('name','id')->toArray());
/*
    采用下面注释中的写法时，用 date 类型会加载不出日期选择组件(cant know why)，
    而且接收数据时为两个值会为null，暂时先用text类型代替
*/
//        $edit->add('start_time','开始时间','datetime')->format('Y-m-d', 'zh-CN')->rule("required");
//        $edit->add('end_time','结束时间','datetime')->format('Y-m-d', 'zh-CN')->rule("required");
        $edit->add('begin_time','开始时间','text')->rule("required")->placeholder('输入格式如：2018-01-31');
        $edit->add('over_time','结束时间','text')->rule("required")->placeholder('输入格式如：2018-01-31');

        $edit->add('weight','权重','number');
        $edit->add('type','弹出策略','radiogroup')->rule('required')
            ->options(['every_time'=>'每次打开App弹出','daily'=>'每天弹出一次','once'=>'活动期间仅弹出一次']);
        $edit->add('sort','排序','number');
        $edit->add('comment','弹窗备注','textarea');

        $edit->saved(function() use ($edit){

            try {
                DB::connection('plat')->beginTransaction();

                $way = request('way', null);
                $discountID = Input::post('discount_id', null);
                //如果绑定的是活动
                if ($way == 'discount' && $discountID > 0 && $edit->model->customer_vip_discount_id != $discountID) {
                    $edit->model->customer_vip_discount_id = $discountID;

                    Platv4ItemToUserGroup::where('item_id', $edit->model->id)->where('item_table', 'platv4_modal')->delete();
                }

                //如果绑定的是分群
                if ($way == 'group') {
                    $groups = Input::post('group', null);
                    if ($groups) {
                        $row = [];
                        foreach ($groups as $g) {
                            $row['user_group_id'] = $g;
                            $row['item_table'] = 'platv4_modal';
                            $row['item_id'] = $edit->model->id;
                            $groupData[] = $row;
                        }
                    }

                    Platv4ItemToUserGroup::where('item_id', $edit->model->id)->where('item_table', 'platv4_modal')->delete();
                    Platv4ItemToUserGroup::insert($groupData);
                    $edit->model->customer_vip_discount_id = 0;
                }

                //更新时间
                $edit->model->start_time = Input::post('begin_time', null);
                $edit->model->end_time = Input::post('over_time', null);

                $edit->model->save();

                //更新平台
                $terminals = Input::post('terminal',null);
                if($terminals){
                    $row = [];
                    foreach($terminals as $t){
                        $row['modal_id'] = $edit->model->id;
                        $row['terminal'] = $t;
                        $terminalData[] = $row;
                    }
                }
                Platv4ModalToTerminal::where('modal_id', $edit->model->id)->delete();
                Platv4ModalToTerminal::insert($terminalData);

                DB::connection('plat')->commit();

            }catch(\Exception $e){
                \Log::error('出现错误：'.$e->getMessage());
                DB::connection('plat')->rollback();
                return redirect('error')->with([
                    'to'=>config('admin.route.prefix') . '/modal',
                    'msg'=>'模态窗属性保存失败:'.$e->getMessage()
                ]);
            }
        });

        $edit->build();

        $imageDir ='U' . \Admin::user()->id;
        return $edit->view('modal.form',compact('edit','imageDir'));
    }

}