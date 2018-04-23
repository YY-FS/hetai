<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-4-16
 * Time: 下午12:04
 */
namespace App\Admin\Controllers;

use App\Models\Platv4Message;
use App\Services\UmengMessageService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Input;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\Url;

class MessageController extends BaseController
{
    public function index()
    {
        $this->route = '/message';
        $title = '主动通知管理';

        $where = [];
        if (!Input::get('search', null)) {
            $date = date('Y-m-d', time());
            $tomorrow = date('Y-m-d', time() + 24 * 60 * 60);
            $where = [
                ['create_time', '>=', $date],
                ['create_time', '<', $tomorrow],
            ];
        }

        $filter = DataFilter::source(Platv4Message::rapydGrid($where));
        $filter->add('title', '标题', 'text');
        $filter->add('device', '平台', 'select')->options(['' => '全部类型'] + Platv4Message::where('device', '<>', 'pc')->pluck('device', 'device')->toArray())
            ->scope(function ($query, $value) {
                return $value ? $query->where('device', $value) : $query;
            });
        $filter->add('start_time', '开始时间', 'daterange')
            ->scope(function ($query, $value) {
                $value = explode('|', $value);
                if (!empty($value[0]))
                    $query = $query->where('start_time', '>=', $value[0]);
                if (!empty($value[1])) {
                    $value[1] = date('Y-m-d', strtotime($value[1]) + 24 * 60 * 60);//增加一天，date_paid比后一天的00:00:00小就好
                    $query = $query->where('start_time', '<', $value[1]);
                }
                return $query;
            })->format('Y-m-d', 'zh-CN');
        $filter->add('end_time', '结束时间', 'daterange')
            ->scope(function ($query, $value) {
                $value = explode('|', $value);
                if (!empty($value[0]))
                    $query = $query->where('end_time', '>=', $value[0]);
                if (!empty($value[1])) {
                    $value[1] = date('Y-m-d', strtotime($value[1]) + 24 * 60 * 60);//增加一天，date_paid比后一天的00:00:00小就好
                    $query = $query->where('end_time', '<', $value[1]);
                }
                return $query;
            })->format('Y-m-d', 'zh-CN');
        $filter->add('create_time', '创建时间', 'daterange')
            ->scope(function ($query, $value) {
                $value = explode('|', $value);
                if (!empty($value[0]))
                    $query = $query->where('create_time', '>=', $value[0]);
                if (!empty($value[1])) {
                    $value[1] = date('Y-m-d', strtotime($value[1]) + 24 * 60 * 60);//增加一天，date_paid比后一天的00:00:00小就好
                    $query = $query->where('create_time', '<', $value[1]);
                }
                return $query;
            })->format('Y-m-d', 'zh-CN');
        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);
        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', '编号', false);
        $grid->add('status', '编号', false);
        $grid->add('device', '平台', false);
        $grid->add('title', '标题', false);
        $grid->add('start_time', '开始时间', true);
        $grid->add('end_time', '结束时间', true);
        $grid->add('create_time', '创建时间', true);
        $grid->add('popup', '通知样式', false);
        $grid->add('operation', '操作', false);
        $grid->row(function ($row) {
            //操作 编辑 启用 禁用 删除 发送 友盟发送
            $editBtn = [
                'btn_class' => 'btn btn-primary',
                'btn_text' => '编辑',
            ];
            $editlink = config('admin.route.prefix') . $this->route . "/edit?modify=" . $row->data->id;
            $row->cell('operation')->value = $this->getFrameBtn($editlink, $editBtn, true);
            //状态
            if ($row->cell('status')->value !== 1)
                $row->cell('status')->style('color:red');
            if (in_array($row->data->status, [0, 1])) {
                $row->cell('status')->value = Platv4Message::$statusText[$row->data->status];
                if ($row->data->status)//==1
                    $row->cell('operation')->value .= $this->getStatusBtn($row->data->id, Platv4Message::COMMON_STATUS_OFFLINE, Platv4Message::$statusText[Platv4Message::COMMON_STATUS_OFFLINE]);
                else
                    $row->cell('operation')->value .= $this->getStatusBtn($row->data->id, Platv4Message::COMMON_STATUS_NORMAL, Platv4Message::$statusText[Platv4Message::COMMON_STATUS_NORMAL])
                        . $this->getDeleteBtn($row->data->id);
            } else
                $row->cell('status')->value = '发生错误，请检查审核状态是否正确！！！';
            //通知样式
            $row->cell('popup')->value = Platv4Message::$popupText[$row->data->popup];

            //发送之后改为已发送，并且没有发送的按钮，手机端则是友盟发送
        });
        $url = new Url();
        $grid->link($url->append('export', 1)->get(), '导出Excel', 'TR', ['class' => 'btn btn-export', 'target' => '_blank']);

        $pushlink = config('admin.route.perfix') . $this->route . '/push';
        $push = "layer.open({
                        type: 2,
                        title: ['测试APP通知', false],
                        area: ['800px', '800px'],
                        shadeClose: true,
                        scrollbar: false,
                        content:'" . $pushlink . "'
                 });";
        $grid->button('测试APP通知', 'TR', ['class' => 'btn btn-warning', 'onclick' => $push]);

        $editlink = config('admin.route.perfix') . $this->route . '/edit';

        $edit = "layer.open({
                        type: 2,
                        title: ['添加APP通知', false],
                        area: ['900px', '750px'],
                        shadeClose: true,
                        scrollbar: false,
                        content:'" . $editlink . "',
//                        end: function(index, layero){
//                                window.location.reload();
//                                return false;
//                            }
                 });";
        $grid->button('添加APP通知', 'TR', ['class' => 'btn btn-success', 'onclick' => $edit]);

        if (Input::get('export') == 1) {
            $grid->build();
            return $grid->buildCSV();
        } else {
            $grid->paginate(self::DEFAULT_PER_PAGE);
            $grid->build();
            return view('rapyd.filtergrid', compact('filter', 'grid', 'title'));
        }
    }

    public function anyEdit()
    {
        //软删除
        if ($deleteId = Input::get('delete', null)) {
            Platv4Message::where('id', $deleteId)->update(['status' => Platv4Message::STATUS_DELETE]);
            return redirect()->back();
        }
        //启用禁用
        if (!is_null($status = Input::get('status', null)) && !is_null($statusId = Input::get('id', null))) {
            Platv4Message::where('id', $statusId)->update(['status' => $status]);
            return redirect()->back();
        }
        //配置保存和撤销改动的位置
        config([
            'rapyd.data_edit.button_position.save' => 'BR',
            'rapyd.data_edit.button_position.modify' => 'BL'
        ]);
        $edit = DataEdit::source(new Platv4Message());
        $edit->label('APP通知信息编辑');
        $edit->add('title', '标题', 'text')->rule('required|max:100');
        $edit->add('content', '内容', 'textarea')->rule('required');
        $edit->add('device', '平台', 'select')->rule('required')->options(Platv4Message::$deviceText);
        $edit->add('type', '通知类型', 'select')->rule('required')->options(Platv4Message::$typeText);
        $edit->add('label', '标签', 'select')->rule('required')->options(Platv4Message::$labelText);
        $edit->add('create_time', '创建时间', 'datetime')->rule('required');
        //不定时的话，就跟创建时间没什么区别了，
//            开始时间
//            结束时间
        $edit->build();
        return $edit->view('rapyd.frameEdit', compact('edit'));
    }
    //测试app通知原代码
//    //测试APP通知界面
//    public function push()
//    {
//        $edit = DataEdit::source();
//        $edit->label('测试APP通知');
//        $edit->add('messid', '推送的通知编号', 'number')->insertValue(Cookie::get('messid', null));
//        $edit->add('uid', '接受uid', 'number')->insertValue(Cookie::get('uid', null));
//        $edit->build();
//        return $edit->view('message.frameEdit', compact('edit'));
//    }
//
//    //测试APP通知ajax接口
//    public function pushTest()
//    {
//        $this->requestValidate([
//            'messid' => 'required|numeric',
//            'uid' => 'required|numeric',
//        ]);
//        $messid = Input::get('messid', null);
//        $uid = Input::get('uid', null);
//        if ($messid !== null && $uid !== null) {
//            Cookie::queue('messid', $messid);
//            Cookie::queue('uid', $uid);
//        }
//        $messageService = new UmengMessageService();
//        $return = $messageService->umengPush($messid, $uid);
//        if ($return['code'] == 200) return $this->respData('', '数据推送成功');
//        $result = [
//            'code' => 500,
//            'data' => [],
//            'msg' => '数据推送失败'
//        ];
//        return response()->json($result);
//    }
    //测试APP通知界面
    public function push()
    {
//  banner_id：唯一标识
//  type：maka/poster/link/danye/category。当为link时url存在，当为maka/poster/danye时setId存在
//  当为category时即跳转到分类选择页面
//  url：网页url或原生路由（type为link）
//  template_set_id：模版集合id
        $edit = DataEdit::source();
        $edit->label('测试APP通知');
        $edit->add('uid', 'uid', 'number')->insertValue(Cookie::get('uid', null));
        $edit->add('banner_id', '*banner_id', 'number')->insertValue(Cookie::get('banner_id', null));
        $edit->add('device', '*设备', 'select')->options(Platv4Message::$deviceText)->insertValue(Cookie::get('device','app'));
        $edit->add('type', '*类型', 'select')->options(Platv4Message::$typeTestText)->insertValue(Cookie::get('type','maka'));
        $edit->add('title', '*标题', 'text')->insertValue(Cookie::get('title', null));
        $edit->add('description', '描述', 'textarea')->insertValue(Cookie::get('description', null));
        $edit->add('url', '*url', 'text')->insertValue(Cookie::get('url', 'http://'));
        $edit->add('template_set_id', '*模版集合id', 'number')->insertValue(Cookie::get('template_set_id', null));
        $edit->build();
        return $edit->view('message.frameTestEdit', compact('edit'));
    }

    //测试APP通知ajax接口
    public function pushTest()
    {
        $this->requestValidate([
            'uid' => 'numeric',
            'banner_id' => 'required|numeric',
            'device' => 'required',
            'type' => 'required',
            'title' => 'required',
        ]);

        $type = Input::get('type', null);
        if (in_array($type, ['maka', 'poster', 'danye'])) {
            $this->requestValidate([
                'template_set_id' => 'required|numeric'
            ]);
        } elseif ($type == 'link') {
            $this->requestValidate([
                'url' => 'required|url'
            ]);
        }
        $uid = Input::get('uid', null);
        $device = Input::get('device', null);
        $banner_id = Input::get('banner_id', null);
        $title = Input::get('title', null);
        $description = Input::get('description', null);
        $url = Input::get('url', null);
        $template_set_id = Input::get('template_set_id', null);
        
        //暂时保存数据
        Cookie::queue('uid', $uid, 10);
        Cookie::queue('banner_id', $banner_id, 10);
        Cookie::queue('device', $device, 10);
        Cookie::queue('type', $type, 10);
        Cookie::queue('title', $title, 10);
        Cookie::queue('description', $description, 10);
        Cookie::queue('url', $url, 10);
        Cookie::queue('template_set_id', $template_set_id, 10);
        
        $messageService = new UmengMessageService();
        $return = $messageService->umengPush($uid,$banner_id,$device,$type,$title,$description,$url,$template_set_id);
        if ($return['code'] == 200) return $this->respData('', '数据推送成功');
        $result = [
            'code' => 500,
            'data' => [],
            'msg' => '数据推送失败'
        ];
        return response()->json($result);
    }
}