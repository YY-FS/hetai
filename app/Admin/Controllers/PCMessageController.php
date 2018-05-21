<?php
/**
 * Created by PhpStorm.
 * User: Liaodi
 * Date: 18-5-18
 * Time: 下午12:04
 */
namespace App\Admin\Controllers;

use App\Models\Platv4MessageReceiver;
use App\Models\Platv4MessageV2;
use App\Models\Platv4UserGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\DataFilter\DataFilter;

class PCMessageController extends BaseController
{
    private $pageType = '';

    public static $paramNames = array(
        Platv4MessageReceiver::RECEIVER_TYPE_ALL => 'user_all',
        Platv4MessageReceiver::RECEIVER_TYPE_GROUP => 'user_group',
    );

    // overwrite edit button
    public function getEditBtn($id, $frame = false)
    {
        return '<button class="btn btn-primary" onclick="window.location.href = \'' . config('admin.route.prefix') . $this->route . "/{$this->pageType}/edit?modify=" . $id . '\'">编辑</button>';
    }

    // overwritedelete button
    public function getDeleteBtn($id)
    {
        return '<button class="btn btn-danger" onclick="layer.confirm( \'确定删除吗？！\',{ btn: [\'确定\',\'取消\'] }, function(){ window.location.href = \'' . config('admin.route.prefix') . $this->route . "/{$this->pageType}/edit?delete=" . $id . '\'})">删除</button>';
    }

    // push button
    public function getPushBtn($id, $frame = false)
    {
        return '<button class="btn btn-danger" onclick="layer.confirm( \'确定推送吗？！\',{ btn: [\'确定\',\'取消\'] }, function(){ window.location.href = \'' . config('admin.route.prefix') . $this->route . "/{$this->pageType}/edit?push=" . $id . '\'})">推送</button>';
    }

    /**
     * @desc 通知管理主页
     * @param $pageType 通知类型 1通知中心 2小喇叭
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index($pageType)
    {
        $this->pageType = $pageType = isset(Platv4MessageV2::$messageTypeDesc[$pageType]) ? $pageType : Platv4MessageV2::MESSAGE_TYPE_CENTER;
        $this->route = '/pc_message';
        $title = $this->pageType == Platv4MessageV2::MESSAGE_TYPE_CENTER ? 'PC通知管理' : 'PC小喇叭管理';
        $userGroups = Platv4UserGroup::select(['id', 'name'])->get()->toArray();
        $userGroupsAssoc = array();
        foreach($userGroups as $v){
            $userGroupsAssoc[$v['id']] = $v['name'];
        }
        unset($userGroups);
        $where['type'] = $pageType;
        $filter = DataFilter::source(Platv4MessageV2::rapydGrid($where));

        $filter->add('id', 'ID', 'text')->scope(function ($query, $value) {
            if ($value == '') {
                return $query;
            } else {
                return $query->where('mess.id', $value);
            }
        });
        $filter->add('title', '标题', 'text');
        if ($pageType == Platv4MessageV2::MESSAGE_TYPE_CENTER){
            $filter->add('create_time', '发送日期', 'daterange')->scope(function ($query, $value) {
                $value = explode('|', $value);
                if (!empty($value[0])) {
                    $query = $query->where('mess.create_time', '>=', strtotime($value[0]));
                }

                if (!empty($value[1])) {
                    $query = $query->where('mess.create_time', '<=', strtotime($value[1]));
                }
                return $query;
            })->format('Y-m-d', 'zh-CN');
        }
        $filter->set('status', $pageType, false, false);
        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);

        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', 'ID', true);
        $grid->add('title', '标题', false);
        $grid->add('receiver_info', '用户分群', false);
        if ($pageType == Platv4MessageV2::MESSAGE_TYPE_CENTER){
            $grid->add('create_time', '发送时间', true);
        }
        else {
            $grid->add('start_time', '开始时间', false);
            $grid->add('end_time', '结束时间', false);
        }
        $grid->add('status', '状态', false);
        $grid->add('operation', '操作', false);
        if ($pageType == Platv4MessageV2::MESSAGE_TYPE_CENTER) {
            $grid->orderBy('create_time', 'desc');
        }

        $grid->link(config('admin.route.prefix') .'pc_message/'.$this->pageType.'/create','添加','TR',['class'=>'btn btn-success']);

        $grid->row(function ($row) use ($userGroupsAssoc) {
            $receiverInfo = $row->data->receiver_info;
            $receiverInfo = $receiverInfo ? explode(',', $receiverInfo) : array();
            $receiverNames = array();
            foreach($receiverInfo as $v){
                list($type, $receiverId) = explode('-', $v);
                if (isset($type) && isset($receiverId)){
                    if ($type == Platv4MessageReceiver::RECEIVER_TYPE_ALL){
                        $receiverNames[] = '所有用户';
                        break;
                    }
                    else if ($type == Platv4MessageReceiver::RECEIVER_TYPE_GROUP && isset($userGroupsAssoc[$receiverId])){
                        $receiverNames[] = $userGroupsAssoc[$receiverId];
                    }
                    else if ($type == Platv4MessageV2::RECEIVER_TYPE_PERSONAL){
                        $receiverNames[] = '用户' . $receiverId;
                    }
                }
            }
            $row->cell('receiver_info')->value = $receiverNames ? implode('、', $receiverNames) : '';
            $row->cell('status')->value = isset(Platv4MessageV2::$messageStatusDesc[$row->data->status]) ? Platv4MessageV2::$messageStatusDesc[$row->data->status] : '未知';
            $btnEdit = $this->getEditBtn($row->data->id);
            $btnPush = $row->data->status == 0 ? $this->getPushBtn($row->data->id) : '';
            $btnDelete = $this->getDeleteBtn($row->data->id);
            $row->cell('operation')->value = $btnEdit.$btnPush.$btnDelete;
        });

        $grid->paginate(self::DEFAULT_PER_PAGE);
        $grid->build();
        return view('rapyd.filtergrid', compact('filter', 'grid', 'title'));
    }

    /**
     * @desc 编辑已有通知
     * @param $pageType 通知类型 1通知中心 2小喇叭
     * @return \Illuminate\Support\Facades\Redirect|\Illuminate\Support\Facades\View
     */
    public function anyEdit($pageType)
    {
        $this->pageType = $pageType = isset(Platv4MessageV2::$messageTypeDesc[$pageType]) ? $pageType : Platv4MessageV2::MESSAGE_TYPE_CENTER;
        $deleteID = Input::get('delete', 0);
        if($deleteID){
            $delete = Platv4MessageV2::where('id', $deleteID)->where('type', $pageType)->first();
            if($delete){
                $delete->status = 2;
                $delete->save();
            }
            return redirect(config('admin.route.prefix')."/pc_message/{$pageType}");
        }
        $pushID = Input::get('push', 0);
        if ($pushID){
            $push = Platv4MessageV2::where('id', $pushID)->where('type', $pageType)->first();
            if($push){
                $push->status = 1;
                $push->save();
            }
            return redirect(config('admin.route.prefix')."/pc_message/{$pageType}");
        }
        $id = Input::get('modify', 0);
        if ($id && !Platv4MessageV2::where('id', $id)->where('type', $pageType)->first()){
            return redirect(config('admin.route.prefix')."/pc_message/{$pageType}");
        }
        if ($id) {
            $receiver_ids = Platv4MessageReceiver::getMessageReceiver($id)->pluck('receiver_ids', 'receiver_type')->toArray();
            foreach($receiver_ids as $k=>$v){
                if (!isset(self::$paramNames[$k])){
                    continue;
                }
                if ($k === Platv4MessageReceiver::RECEIVER_TYPE_ALL){
                    Input::offsetSet(self::$paramNames[$k], 1);
                }
                else{
                    $receiver_ids[$k] = explode(',',$v);
                    Input::offsetSet(self::$paramNames[$k],$receiver_ids[$k]);   // 选中各种属性
                }
            }

        }
        $edit = DataEdit::source(new Platv4MessageV2);
        $edit->label($pageType == Platv4MessageV2::MESSAGE_TYPE_CENTER ? '编辑PC通知' : '编辑PC小喇叭');
        $edit->link(config('admin.route.prefix') . "pc_message/{$pageType}", "列表", "TR")->back();
        $edit->add('title','通知标题','text')->rule('required');
        if ($pageType == Platv4MessageV2::MESSAGE_TYPE_CENTER){
            $edit->add('content','通知内容','redactor');
            $edit->add('create_time','发送时间','datetime');
        }
        else{
            $edit->add('content','通知内容','textarea');
            $edit->add('start_time','开始时间','datetime');
            $edit->add('end_time','结束时间','datetime');
        }
        $edit->set('type', $pageType, true, false);
        $userGroups = Platv4UserGroup::select(['id', 'name'])->get()->toArray();
        $userGroupsAssoc = array();
        foreach($userGroups as $v){
            $userGroupsAssoc[$v['id']] = $v['name'];
        }
        $edit->add(self::$paramNames[Platv4MessageReceiver::RECEIVER_TYPE_ALL], Platv4MessageReceiver::$receiverTypeDesc[Platv4MessageReceiver::RECEIVER_TYPE_ALL], 'checkbox');
        $edit->add(self::$paramNames[Platv4MessageReceiver::RECEIVER_TYPE_GROUP], Platv4MessageReceiver::$receiverTypeDesc[Platv4MessageReceiver::RECEIVER_TYPE_GROUP],'checkboxgroup')->options( $userGroupsAssoc);

        $edit->saved(function() use ($edit){
            self::saveMessageReceiver($edit);
        });

        $edit->build();
        return $edit->view( 'rapyd.edit', compact('edit'));
    }

    public static function saveMessageReceiver($obj)
    {
        $input = Input::all();
        try{
            //插入数据
            DB::beginTransaction();
            $messageId = $obj->model->id;
            $data = [];
            foreach ($input as $k=>$v){
                if($k == self::$paramNames[Platv4MessageReceiver::RECEIVER_TYPE_ALL]){
                    $data[] = array(
                        'message_id' => $messageId,
                        'receiver_type' => Platv4MessageReceiver::RECEIVER_TYPE_ALL,
                        'receiver_id' => 0
                    );
                }
                else if ($k == self::$paramNames[Platv4MessageReceiver::RECEIVER_TYPE_GROUP]){
                    foreach($v as $sv){
                        $data[] = array(
                            'message_id' => $messageId,
                            'receiver_type' => Platv4MessageReceiver::RECEIVER_TYPE_GROUP,
                            'receiver_id' => $sv
                        );
                    }
                }
            }
            Platv4MessageReceiver::where('message_id',$obj->model->id)->delete();
            Platv4MessageReceiver::insert($data);
            DB::commit();

        }catch (Exception $e){
            \Log::error($e->getMessage());
            DB::rollback();
            $obj->message('** <h3>【ERROR】</h3>通知保存失败 **：' . $e->getMessage());
            $obj->link(config('admin.route.prefix') . '/pc_message}',"返回列表");
        }
    }
}