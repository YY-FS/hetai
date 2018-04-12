<?php
namespace App\Admin\Controllers;

use App\Models\Platv4HeadlineTag;
use App\Models\Platv4IndustryToHeadlineTag;
use App\Models\Platv4UserFilterType;
use App\Models\Platv4UserGroupToFilter;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
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
        $filter = DataFilter::source(Platv4UserGroup::where('status','<>',-1)->orderBy('id','desc'));
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
        $grid->add('rise_time','生成时间',false);
        $grid->add('{{ $duration }}s','生成耗时',false);
        $grid->add('operation','操作',false);

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.prefix') .'/user/groups/create','添加','TR',['class'=>'btn btn-success']);

        $grid->row(function($row){
            $btnEdit = '<a href=\''.config('admin.route.prefix') .'/user/groups/edit?modify='.$row->data->id.'\' class=\'btn btn-primary\'>编辑</a>';
            $btnDelete = '<button class="btn btn-danger" onclick="layer.confirm( \'确定删除吗？！\',{ btn: [\'确定\',\'取消\'] }, function(){ window.location.href = \'' . config('admin.route.prefix') . "/user/groups/edit?delete=" . $row->data->id . '\'})">删除</button>';
            $btnSearch = $this->getFrameBtn(config('admin.route.prefix') .'/user/groups/check_member?user_group_id='.$row->data->id,['btn_text'=>'搜索成员','btn_class'=>'btn btn-warning'],false,500,400);
            $row->cell('operation')->value =  $btnEdit . $btnSearch . $btnDelete;
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
        $res = Platv4UserFilterType::getFilterNType();

        $form = DataForm::source(new Platv4UserGroup());
        $form->label('添加分群');
        $form->link(config('admin.route.prefix') . "user/groups", "列表", "TR")->back();
        $form->add('name','分群名称','text')->rule('required');
        $form->add('comment','分群备注','textarea')->rule('required');
        $form->add('mode','分群方式','radiogroup')->options(Platv4UserGroup::$groupMode)->rule('required');

        foreach($res as $re){
            $form->add($re->alias,$re->label,'checkboxgroup')
                ->options(array_combine(explode('|',$re->uf_id),explode('|',$re->uf_name)));
        }

        $form->saved(function() use ($form){
            //处理自动分组
            $group = Platv4UserGroup::find($form->model->id);
            if($group->mode == Platv4UserGroup::GROUP_MODE_AUTO){
                //同步到user_group_to_filter表
                self::groupToFilter($form);
                return redirect(config('admin.route.prefix').'user/groups');
            }
            //处理手动分组
            $groupIds = request('groupCollect',null);
            if($groupIds) {
                $userGroupId = $form->model->id;
                $needle = ',';
                if (strpos($groupIds, '，')) {
                    $needle = '，';
                }
                $groupUser = explode($needle, $groupIds);
                $userCount = count($groupUser);
                Platv4UserGroup::where('id', $userGroupId)->update(['user_total' => $userCount]);

                $cacheKey = 'CMS:CMD:USER_GROUP:ID:' . $userGroupId;
                Redis::del($cacheKey);
                Redis::sadd($cacheKey, ...$groupUser);
                return redirect(config('admin.route.prefix').'user/groups');
            }
        });

        $form->submit('保存');
        $form->build();

        return $form->view('usergroup.form', ['obj'=>$form,'res'=>$res]);
    }

    public function anyEdit()
    {
        $deleteID = $id = Input::get('delete', 0);
        if($deleteID){
            $delete = Platv4UserGroup::find($deleteID);
            if($delete){
                $delete->status = -1;
                $delete->save();
            }
            return redirect(config('admin.route.prefix').'user/groups');
        }

        $id = Input::get('modify', 0);
        if ($id) {
            $groupFilters = Platv4UserGroupToFilter::getUserGroupFilter($id)->pluck('filter_ids','filter_type_alias')->toArray();
            foreach($groupFilters as $k=>$v){
                $groupFilters[$k] = explode(',',$v);
                Input::offsetSet($k,$groupFilters[$k]);   // 选中各种属性
            }
        }

        $edit = DataEdit::source(new Platv4UserGroup());
        $edit->label('编辑分群');
        $edit->link(config('admin.route.prefix') . "user/groups", "列表", "TR")->back();
        $edit->add('name','分群名称','text')->rule('required');
        $edit->add('comment','分群备注','textarea')->rule('required');
        $edit->add('mode','分群方式','radiogroup')->options(Platv4UserGroup::$groupMode)->rule('required');

        $res = Platv4UserFilterType::getFilterNType()->toArray();

        foreach($res as $re){
            $edit->add($re->alias,$re->label,'checkboxgroup')
                ->options(array_combine(explode('|',$re->uf_id),explode('|',$re->uf_name)));
        }

        $userGroupId = $edit->model->id;
        $cacheKey = 'CMS:CMD:USER_GROUP:ID:' . $userGroupId;
        $groupIds = Redis::smembers($cacheKey)?implode(',',Redis::smembers($cacheKey)):null;

        $edit->saved(function() use ($edit,$cacheKey){
            $group = Platv4UserGroup::find($edit->model->id);
            if($group->mode == Platv4UserGroup::GROUP_MODE_AUTO){
                //同步到user_group_to_filter表
                self::groupToFilter($edit);
            }
            //将自动改为手动时更新status
            if($group->status == Platv4UserGroup::STATUS_GROUPING && $group->mode == Platv4UserGroup::GROUP_MODE_HAND){
                $group->status = Platv4UserGroup::STATUS_NORMAL;
            }
            //处理手动分组更新
            $groupIds = request('groupCollect',null);
            if($groupIds) {
                $needle = ',';
                if (strpos($groupIds, '，')) {
                    $needle = '，';
                }
                $groupUser = explode($needle, $groupIds);
                $userCount = count($groupUser);
                $group->user_total = $userCount;

                Redis::del($cacheKey);
                Redis::sadd($cacheKey, ...$groupUser);
            }
            $group->save();
        });

        $edit->build();
        return $edit->view( 'usergroup.form', ['obj'=>$edit,'res'=>$res,'groupIds'=>$groupIds]);

    }

    public static function groupToFilter($obj)
    {
        $input = Input::all();
        $fields = Platv4UserFilterType::getFilterNType()->pluck('alias')->toArray();
        $temp = [];
        foreach ($input as $k=>$v){
            if(in_array($k,$fields)){
                $temp[$k] = $v;
            }
        }
        try{
            //插入数据
            DB::beginTransaction();
            $groupID = $obj->model->id;
            $data = [];
            foreach ($temp as $value){
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

            if($obj instanceof DataEdit){
                Platv4UserGroupToFilter::where('user_group_id',$obj->model->id)->delete();
            }
            $re = Platv4UserGroupToFilter::insert($data);
            if($re){
                Platv4UserGroup::where('id',$obj->model->id)->update(['status'=>0]);
            }
            DB::commit();

        }catch (Exception $e){
            \Log::error($e->getMessage());
            DB::rollback();
            Platv4UserGroup::where('id',$obj->model->id)->update(['status'=>-2]);
            $obj->message('** <h3>【ERROR】</h3>用户群组属性保存失败 **：' . $e->getMessage());
            $obj->link(config('admin.route.prefix') . '/groups',"返回列表");
        }
    }

    public function checkMember()
    {
        $userGroupId = Input::get('user_group_id',null);
        if($userGroupId){
            return view('usergroup.search',compact('userGroupId'));
        }
    }

}