<?php
namespace App\Admin\Controllers;

use App\Models\Platv4Banner;
use App\Models\Platv4BannerToTerminal;
use App\Models\Platv4CustomerVipDiscount;
use App\Models\Platv4ItemToUserGroup;
use App\Models\Platv4Layout;
use App\Models\Platv4Terminal;
use App\Models\Platv4UserGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\Url;

class BannerController extends BaseController
{
    public function index()
    {
        $this->route = '/banners';
        $title = 'Banner列表';
        $filter = DataFilter::source(Platv4Banner::rapydGrid());
        $filter->add('id','Banner id','text')
            ->scope(function($query,$value){
                return $value?$query->where('b.id',$value):$query;
        });
        $filter->add('title','Banner标题','text');
        $filter->add('status','Banner状态','select')
            ->options(['' => '全部状态'] + Platv4Banner::$statusText)
            ->scope(function($query,$value){
                return (int)$value!==''?$query->where('b.status',$value):$query;
        });
        $filter->add('terminal','平台','select')
            ->options([''=>'全部终端']+Platv4Terminal::pluck('description','name')->toArray())
            ->scope(function($query,$value){
                return $value?$query->where('t.terminal',$value):$query;
            });
        $filter->add('position','位置','select')
            ->options([''=>'全部位置']+Platv4Layout::pluck('name','id')->toArray())
            ->scope(function($query,$value){
                return $value?$query->where('layout_id',$value):$query;
            });

        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);
        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id','ID',false);
        $grid->add('sort','优先级',true);
        $grid->add('status','状态',false);
        $grid->add('terminal','平台',false);
        $grid->add('position','位置',false);
        $grid->add('thumb','缩略图',false);
        $grid->add('title','标题',false);
        $grid->add('url','url',false);
        $grid->add('user_group','用户分群',false);
        $grid->add('cover','覆盖人数',false);
        $grid->add('comment','备注',false);
        $grid->add('start_time','开始时间',true);
        $grid->add('end_time','结束时间',true);
        $grid->add('created_at','创建时间',true);
        $grid->add('operation','操作',false);

        $grid->row(function($row) use ($grid){
            $result = Platv4Banner::checkStatus($row);
            if(empty($result)){
                $row->cell('status')->value = '发生错误，请检查各时间是否正确!!!';
                $row->cell('status')->style("color:red;");
                $row->cell('operation')->value = $this->getEditBtn($row->data->id);
            }else{
                $row->cell('status')->value = Platv4Banner::$statusText[$result['status']];
                $toStatus = $result['toStatus'];

                $row->cell('status')->style($result['style']);
                $statusText = $result['toStatusText'];

                $row->cell('operation')->value = $this->getEditBtn($row->data->id) . $this->getStatusBtn($row->data->id, $toStatus, $statusText);
                if($toStatus == Platv4Banner::STATUS_READY || $toStatus == Platv4Banner::STATUS_PROGRESS){
                    $row->cell('operation')->value.= $this->getDeleteBtn($row->data->id);
                }
            }

            $row->cell('thumb')->value = "<img src='http://".env('ALI_OSS_PLAT_VIEW_DOMAIN').'/'.$row->data->thumb."' height=40 width=auto>";

            $sum = 0;
            $sum += array_sum(explode(',',$row->data->discount_group_total));
            $sum += array_sum(explode(',',$row->data->banner_group_total));
            $row->cell('cover')->value = $sum;

            $group = '全量';
            if($row->data->discount_group) $group = $row->data->discount_group;
            if($row->data->banner_group) $group = $row->data->banner_group;
            $row->cell('user_group')->value = $group;

        });

        $layouts = Platv4Layout::all();
        $cleanCache = "layer.confirm( '确定清理缓存吗？！',{ btn: ['确定','取消'] }, function(){ 
            $.get('"  . $this->route . "/cache',
                function (data) {
                    console.log(data);
                    if(data.success === true) {
                        layer.msg('清理成功');
                    } else {
                        layer.msg('清理失败');
                    }
                });
            })";

        foreach($layouts as $l){
            $grid->button('清【'.$l->name.'】缓存','TR',['class'=>'btn btn-small btn-warning','onclick'=>$cleanCache]);
        }
        $grid->link('/banners/create','添加','TR',['class'=>'btn btn-success']);
        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->orderBy('created_at','desc');
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
        //        软删除
        $deleteId = Input::get('delete', null);
        if ($deleteId) {
            Platv4Banner::where('id', $deleteId)->update(['status' => Platv4Banner::STATUS_DELETE]);
            return redirect()->back();
        }

        //        上下线
        if (!is_null($status = Input::get('status', null)) && !is_null($id = Input::get('id', null))) {
            Platv4Banner::where('id', $id)->update(['status' => $status]);
            return redirect()->back();
        }

        $edit = DataEdit::source(new Platv4Banner());
        //如果是修改页面进入的
        $modifyId = Input::get('modify',0);
        if($modifyId){
            //选中选项
            $terminal = Platv4BannerToTerminal::where('banner_id',$edit->model->id)->pluck('terminal')->toArray();
            Input::offsetSet('terminal',$terminal);
            $group = Platv4ItemToUserGroup::where('item_id',$edit->model->id)->where('item_table','platv4_banners_v2')->pluck('user_group_id')->toArray();
            if(!empty($group)){
                Input::offsetSet('group',$group);
                $way = 'group';
            }else{
                $way = $edit->model->customer_vip_discount_id > 0?'discount':'all';
            }
            Input::offsetSet('way',$way);

            Input::offsetSet('discount_id',$edit->model->customer_vip_discount_id);
        }

        $edit->label('banner信息');
        $edit->link(config('admin.route.prefix') . "/banners/list", "列表", "TR");
        $edit->add('title','banner标题','text')->rule('required');
        $edit->add('thumb','banner图片','text')->attributes(['readOnly' => true])->rule('required');
        $edit->add('terminal','平台','select')
            ->options([''=>'请选择终端']+Platv4Terminal::pluck('description','name')->toArray());
        $edit->add('layout_id','位置','select')->options(['请选择位置']+Platv4Layout::pluck('name','id')->toArray());
        $edit->add('url','跳转链接','text');
        $edit->add('way','banner策略','radiogroup')->rule('required')
            ->options(['group'=>'用户分群','discount'=>'绑定活动','all'=>'全量']);
        $edit->add('group','用户分群','checkboxgroup')
            ->options(Platv4UserGroup::where('status','<>',-1)->get()->pluck('name','id')->toArray());
        $edit->add('discount_id','活动列表','select')
            ->options(['请选择活动']+Platv4CustomerVipDiscount::all()->pluck('name','id')->toArray());

        $edit->add('start_time','开始时间','date')->format('Y-m-d', 'zh-CN');
        $edit->add('end_time','结束时间','date')->format('Y-m-d', 'zh-CN');

        $edit->add('sort','排序','number')->placeholder('数值越小排序越靠前');
        $edit->add('comment','备注','textarea')->rule('required');

        $edit->saved(function() use ($edit){
            try {
                DB::connection('plat')->beginTransaction();

                $way = request('way', null);
                $discountID = Input::post('discount_id', null);
                //如果绑定的是活动
                if ($way == 'discount' && $discountID > 0 && $edit->model->customer_vip_discount_id != $discountID) {
                    $edit->model->customer_vip_discount_id = $discountID;

                    Platv4ItemToUserGroup::where('item_id', $edit->model->id)->where('item_table', 'platv4_banners_v2')->delete();
                }

                //如果绑定的是分群
                if ($way == 'group') {
                    $groups = Input::post('group', null);
                    if ($groups) {
                        $row = [];
                        foreach ($groups as $g) {
                            $row['user_group_id'] = $g;
                            $row['item_table'] = 'platv4_banners_v2';
                            $row['item_id'] = $edit->model->id;
                            $groupData[] = $row;
                        }
                        Platv4ItemToUserGroup::where('item_id', $edit->model->id)->where('item_table', 'platv4_banners_v2')->delete();
                        Platv4ItemToUserGroup::insert($groupData);
                    }

                    $edit->model->customer_vip_discount_id = 0;
                }

                //绑定的是全量
                if($way == 'all'){
                    $edit->model->customer_vip_discount_id = 0;

                    Platv4ItemToUserGroup::where('item_id', $edit->model->id)->where('item_table', 'platv4_banners_v2')->delete();
                }

                $edit->model->save();

                //更新平台
                $terminal = Input::post('terminal',null);
                if($terminal){
                    $row = [];
                    $row['banner_id'] = $edit->model->id;
                    $row['terminal'] = $terminal;
                }
                Platv4BannerToTerminal::where('banner_id', $edit->model->id)->delete();
                Platv4BannerToTerminal::insert($row);

                DB::connection('plat')->commit();
                return redirect('/banners/list');
            }catch(\Exception $e){
                \Log::error('出现错误：'.$e->getMessage());
                DB::connection('plat')->rollback();
                return redirect('error')->with([
                    'to'=>config('admin.route.prefix') . '/banners/list',
                    'msg'=>'Banner保存失败:'.$e->getMessage()
                ]);
            }
        });


        $edit->build();

        $imageDir ='U' . \Admin::user()->id;
        return $edit->view('banner.edit',compact('edit','imageDir'));
    }

    public function cleanCache()
    {
        $layout = Input::get('layout','*');
        $list = Redis::keys("QS:DEVICE:*:Layout:{$layout}:BANNER");
        foreach ($list AS $value) {
            Redis::del($value);
        }
        return $this->respData();
    }
}