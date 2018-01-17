<?php
namespace App\Admin\Controllers;

use App\Models\Platv4HeadlineTag;
use App\Models\Platv4IndustryToHeadlineTag;
use App\Models\Platv4UserFilterType;
use App\Models\Platv4UserGroupToFilter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Mockery\Exception;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataForm\DataForm;
use Zofe\Rapyd\Url;
use App\Models\Platv4UserGroup;

class UserGroupController extends BaseController
{
    public function index()
    {
        $title = '用户分群';
        $filter = DataFilter::source(Platv4UserGroup::where('status','<>',-1));
        //渲染搜索框
        $filter->add('ID','ID','text');
        $filter->add('name','名称','text');

        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);
        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id','ID',true);
        $grid->add('name','名称',false);
        $grid->add('user_total','筛选人数',true);
        $grid->add('comment','备注',false);
        $grid->add('{{ \App\Models\Platv4UserGroup::$status[$status] }}','状态',false);
        $grid->add('created_at','创建时间',false);
        $grid->add('operation','操作',false);

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.prefix') .'/groups/create','添加','TR',['class'=>'btn btn-primary']);

        $grid->row(function($row){
            $btnEdit = '<a href=\''.config('admin.route.prefix') .'/groups/edit?modify='.$row->data->id.'\' class=\'btn btn-primary\'>编辑</a>';
            $btnDelete = '<button class="btn btn-danger" onclick="layer.confirm( \'确定删除吗？！\',{ btn: [\'确定\',\'取消\'] }, function(){ window.location.href = \'' . config('admin.route.prefix') . "/groups/edit?delete=" . $row->data->id . '\'})">删除</button>';
            $row->cell('operation')->value =  $btnEdit . $btnDelete;
        });

        if (Input::get('export') == 1) {
            $grid->build();
            return $grid->buildCSV($title, 'Ymd');
        } else {
            $grid->paginate(self::DEFAULT_PER_PAGE);
            $grid->build();
            return view('rapyd.filtergrid', compact('filter', 'grid', 'title', 'tips'));
        }
    }

    public function anyForm()
    {
        $res = Platv4UserFilterType::rapydForm()->get();

        $form = DataForm::source(new Platv4UserGroup());
        $form->label('添加分群');
        $form->link(config('admin.route.prefix') . "/groups", "列表", "TR")->back();
        $form->add('name','分群名称','text')->rule('required|unique:plat.platv4_user_group_v2,name');
        $form->add('comment','分群备注','textarea')->rule('required');
        $form->add('mode','分群方式','radiogroup')->options(Platv4UserGroup::$groupMode)->rule('required');

        foreach($res as $re){
            $form->add($re->alias,$re->label,'checkboxgroup')
                ->options(array_combine(explode(',',$re->uf_id),explode(',',$re->uf_name)));
        }
        $form->saved(function() use ($form){
            $group = Platv4UserGroup::find($form->model->id);
            if($group->mode == Platv4UserGroup::GROUP_MODE_AUTO){
                //同步到user_group_to_filter表
                self::groupToFilter($form);
            }
            $form->message("新建用户群成功");
            $form->link(config('admin.route.prefix') . '/groups',"返回列表");
            //redirect(config('admin.route.prefix').'/groups');
        });
        $form->submit('保存');
        $form->build();

        return view('rapyd.form', compact('form'));
    }

    public function anyEdit()
    {
//        $id = Input::get('modify', 0);
//        if ($id) {
//            $hh = Platv4UserGroupToFilter::rapydEdit($id)->pluck('choose','alias')->toArray();
//            foreach($hh as $k=>$v){
//                $hh[$k] = explode(',',$v);
//                Input::offsetSet($k,$hh[$k]);   // 选中各种属性
//            }
//        }

        $edit = DataEdit::source(new Platv4UserGroup());
        $edit->label('编辑分群');
        $edit->link(config('admin.route.prefix') . "/groups", "列表", "TR")->back();
        $edit->add('name','分群名称','text')->rule('required');
        $edit->add('comment','分群备注','textarea')->rule('required');
        $edit->add('mode','分群方式','radiogroup')->options(Platv4UserGroup::$groupMode)->rule('required');

//        $res = Platv4UserFilterType::rapydForm()->get();
//        foreach($res as $re){
//            $edit->add($re->alias,$re->label,'checkboxgroup')
//                ->options(array_combine(explode(',',$re->uf_id),explode(',',$re->uf_name)));
//        }
//        $edit->saved(function() use ($edit){
//            $group = Platv4UserGroup::find($edit->model->id);
//            if($group->mode == Platv4UserGroup::GROUP_MODE_AUTO){
//                //同步到user_group_to_filter表
//                self::groupToFilter($edit);
//            }
//            //将自动改为手动时更新status
//            if($group->status == Platv4UserGroup::STATUS_GROUPING){
//                $group->status = Platv4UserGroup::STATUS_NORMAL;
//                $group->save();
//            }
//
//            //$edit->message("编辑用户群成功");
//            //$edit->link(config('admin.route.prefix') . '/groups',"返回列表");
//        });

//        $edit->build();
        return view('rapyd.edit', compact('edit'));

    }

    public static function groupToFilter($form)
    {
        $filterField = ['name','comment','mode','_token','insert','save','process','_pjax'];
        try{
            //获取数据
            $input = Input::except($filterField);
            $fields = Platv4UserFilterType::rapydForm()->get()->pluck('uf_id','alias')->toArray();
            $mergeData = array_merge($fields,$input);
            //插入数据
            DB::beginTransaction();
            $groupID = $form->model->id;
            $data = [];
            foreach ($mergeData as $value){
                if(is_string($value)){
                    $arr = explode(',',$value);
                }else if(is_array($value)){
                    $arr = $value;
                }
                foreach ($arr as $item){
                    $row = ['user_group_id'=>$groupID,'user_filter_id'=>$item];
                    $data[] = $row;
                }
            }
            $re = Platv4UserGroupToFilter::insert($data);
            if($re){
                Platv4UserGroup::where('id',$form->model->id)->update(['status'=>0]);
            }
            DB::commit();

        }catch (Exception $e){
            \Log::error($e->getMessage());
            DB::rollback();
            Platv4UserGroup::where('id',$form->model->id)->update(['status'=>-2]);
            $form->message('** <h3>【ERROR】</h3>用户群组属性保存失败 **：' . $e->getMessage());
            $form->link(config('admin.route.prefix') . '/groups',"返回列表");
        }
    }
}