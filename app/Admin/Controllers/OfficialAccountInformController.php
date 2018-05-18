<?php
/**
 * Created by PhpStorm.
 * User: liangweibin
 * Date: 18/5/15
 * Time: 下午4:13
 */

namespace App\Admin\Controllers;


use App\Models\Platv4ItemToUserGroup;
use App\Models\Platv4MessageWechat;
use App\Models\Platv4MessageWechatType;
use App\Models\Platv4UserGroup;
use Illuminate\Support\Facades\Input;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataGrid\DataGrid;

class OfficialAccountInformController extends BaseController
{
    public function index(){
        $this->route = '/official_account';
        $title = '公众号通知管理';
        $filter = DataFilter::source(Platv4MessageWechat::rapydGrid());
        $filter->add('id', '通知id', 'text')->scope(function($query,$value){
            $q =  $value?$query->where('mw.id',$value):$query;
            return $q;
        });

        $filter->add('title', '通知标题', 'text')->scope(function($query,$value){
            return $value?$query->where('mw.title','like','%'.$value.'%'):$query;
        });
        $filter->add('inform_type', '通知类型', 'select')->options([''=>'请选择'] + Platv4MessageWechatType::get()->pluck('name','alias')->toArray())
        ->scope(function($query,$value){
            return $value?$query->where('mwt.alias','like','%'.$value.'%'):$query;
        });

        $filter->add('status', '通知状态', 'select')->options([''=>'请选择'] + Platv4MessageWechat::$commonStatusText)
            ->scope(function($query,$value){
                return ($value!==null && $value!=='')?$query->where('mw.status',$value):$query;
            });

        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);
        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->link(config('admin.route.prefix') . '/official_account/article/create', '添加推文', 'TR', ['class' => 'btn btn-success']);
        $grid->link(config('admin.route.prefix') . '/official_account/customer_service/create', '添加客服消息', 'TR', ['class' => 'btn btn-success']);
        $grid->link(config('admin.route.prefix') . '/official_account/template/create', '添加模板消息', 'TR', ['class' => 'btn btn-success']);
        $grid->add('id', 'ID', false);
        $grid->add('inform_type', '类型', false);
        $grid->add('title', '标题', false);
        $grid->add('user_group', '用户分群', false);
        $grid->add('user_sum', '覆盖数', false);
        $grid->add('send_time', '发送时间', false);
        $grid->add('send_count', '发送数', true);
        $grid->add('open_count', '打开数', true);
        $grid->add('operation', '操作', false);

        $grid->row(function ($row) use ($grid){
            $this->route = '/official_account/'.$row->data->alias;
            if($row->data->status == Platv4MessageWechat::COMMON_STATUS_UNPUSH ){
                $row->cell('operation')->value = $this->getEditBtn($row->data->id);
                $sendTime = strtotime($row->data->send_time);
                $now = time();
                if($now<$sendTime){
                    $row->cell('operation')->value .= $this->getConfirmBtn('确定推送吗？',config('admin.route.prefix') . $this->route .'/edit?push='.$row->data->id.'&send='.$row->data->send_time,'推送','btn-warning');
                }
            }
            if($row->data->status == Platv4MessageWechat::COMMON_STATUS_PUSHING){
                $row->cell('operation')->value = "<a class='btn btn-success' href='".config('admin.route.prefix') . $this->route ."/edit?modify=".$row->data->id."&info=1'>详情</a>";
                $sendTime = strtotime($row->data->send_time);
                $now = time();
                if($now<$sendTime) {
                    $row->cell('operation')->value .= $this->getConfirmBtn('确定撤回吗？',config('admin.route.prefix') . $this->route .'/edit?cancel='.$row->data->id.'&send='.$row->data->send_time,'撤回','btn-info');;
                }
            }
            $row->cell('operation')->value .= $this->getDeleteBtn($row->data->id);

        });

        $grid->orderBy('id', 'desc');
        $grid->paginate(self::DEFAULT_PER_PAGE);
        $grid->build();

        return view('rapyd.filtergrid', compact('grid', 'filter', 'title'));
    }

    public function anyEdit($alias = Platv4MessageWechat::ROUTE_ARTICLE){
        $this->route = '/official_account';
        $extraData = [];
        $edit = DataEdit::source(new Platv4MessageWechat());
        $edit->label(Platv4MessageWechat::$routeText[$alias].'通知');
        $edit->link(config('admin.route.prefix') .$this->route, '返回列表', 'TR', ['class' => 'btn btn-primary']);

        //软删除
        $deleteId = Input::get('delete', null);
        if ($deleteId) {
            Platv4MessageWechat::where('id', $deleteId)->update(['status' => Platv4MessageWechat::COMMON_STATUS_DELETE]);
            return redirect()->back();
        }
        //推送
        $pushId = Input::get('push', null);
        if ($pushId) {
            $sendTime = strtotime(Input::get('send', null));
            $now = time();
            if($sendTime > $now){
                Platv4MessageWechat::where('id', $pushId)->update(['status' => Platv4MessageWechat::COMMON_STATUS_PUSHING]);
            }
            return redirect()->back();
        }
        //撤回
        $cancelId = Input::get('cancel', null);
        if ($cancelId) {
            $sendTime = strtotime(Input::get('send', null));
            $now = time();
            if($sendTime > $now) {
                Platv4MessageWechat::where('id', $cancelId)->update(['status' => Platv4MessageWechat::COMMON_STATUS_UNPUSH]);
            }
            return redirect()->back();
        }
        //修改
        if(Input::get('modify',null)){
            //选中分群
            Input::offsetSet('user_group',Platv4ItemToUserGroup::where('item_id', $edit->model->id)->where('item_table', 'platv4_message_wechat')->pluck('user_group_id')->toArray());
            $extraData = json_decode($edit->model->extra_data,true);
            //推文通知选中客服转发
            if(isset($extraData['transfer']) && $extraData['transfer'] == 1)
                Input::OffsetSet('transfer','1');
            //模板消息 动作选中
            if(isset($extraData['mini_program_id']) && !empty($extraData['mini_program_id']))
                Input::OffsetSet('action','mini_program');
        }
        //详情页
        if(Input::get('info',null)){
            \Rapyd::script("
            var toolbar = $('.btn-toolbar').first().find('a.btn-default');
            toolbar.remove();
            $('#div_user_group label').hide();
            $('#div_user_group input:checked').parent().show();
            $('input[type=submit]').remove();
            $('input').attr('disabled','true');
            ");
        }

        $edit->add('title','标题','text')->rule('required');
        $edit->add('message_wechat_type_id','通知类型','hidden')->insertValue(Platv4MessageWechatType::where('alias',$alias)->first()['id']);
        if($alias == Platv4MessageWechat::ROUTE_CUSTOMER_SERVICE || $alias == Platv4MessageWechat::ROUTE_ARTICLE){
            $edit->add('material_id','素材id','text')->placeholder('请输入素材id')->attributes(['class'=>'push service'])->insertValue('');
        }
        if($alias == Platv4MessageWechat::ROUTE_ARTICLE){
            $edit->add('transfer','转客服','checkboxgroup')->options(['1'=>'48小时内活跃用户自动转客服消息发送']);
        }
        if($alias == Platv4MessageWechat::ROUTE_TEMPLATE){
            $edit->add('template','模板','select')->options([''=>'暂未加载'])->attributes(['class'=>'template']);
            $edit->add('action','动作','select')->options(['link'=>'跳转链接','mini_program'=>'小程序'])->attributes(['class'=>'template']);
            $edit->add('extra[url]','URL','text')->insertValue('http://')->attributes(['class'=>'template link action']);
            $edit->add('extra[mini_program_id]','小程序id','text')->placeholder('请输入小程序id')->attributes(['class'=>'template mini_program mini_program_id action']);
            $edit->add('extra[mini_program_path]','路径','text')->placeholder('请输入小程序路径')->attributes(['class'=>'template mini_program mini_program_path action']);
        }
        $edit->add('user_group','用户分群','checkboxgroup')->options(Platv4UserGroup::where('status',Platv4UserGroup::COMMON_STATUS_NORMAL)->get()->pluck('name','id')->toArray());
        $edit->add('send_time','发送时间','text');


        $edit->saved(function() use ($edit,$alias,$extraData){
            //同步分群信息 到 platv4_item_to usergroup
            $groupData = [];
            $groups = Input::post('user_group', null);
            if ($groups) {
                $row = [];
                foreach ($groups as $g) {
                    $row['user_group_id'] = $g;
                    $row['item_table'] = 'platv4_message_wechat';
                    $row['item_id'] = $edit->model->id;
                    $groupData[] = $row;
                }
                Platv4ItemToUserGroup::where('item_id', $edit->model->id)->where('item_table', 'platv4_message_wechat')->delete();
                Platv4ItemToUserGroup::insert($groupData);
            }

            if($alias == Platv4MessageWechat::ROUTE_ARTICLE){
                $extraData['transfer'] = Input::post('transfer','0')[0];
            }else if($alias == Platv4MessageWechat::ROUTE_TEMPLATE){
                $extra = Input::post('extra',[]);
                //模板中动作选择跳转链接
                if(Input::post('action')=='link'){
                    unset($extra['mini_program_id']);
                    unset($extra['mini_program_path']);
                }else if(Input::post('action')=='mini_program'){
                    unset($extra['url']);
                }
                $extraData = $extra;
                $extraData['template_id'] = Input::post('template','');
                $extraData['action'] = Input::post('action','');
            }

            $extraJson = json_encode((object)$extraData);
            Platv4MessageWechat::where('id',$edit->model->id)->update(['extra_data'=>$extraJson]);
            return redirect($this->route);
        });

        $edit->build();
        return $edit->view('officialaccount.edit',compact('edit','alias','extraData'));
    }

    public function getTpls(){
        $app = app('wechat.official_account.lwb_test');
        $result = $app->template_message->getPrivateTemplates();
        dd($result);
    }

    public function getTpl($app = null)
    {
        // 微信模板服务
        $app = app('wechat.official_account.default');
        $tpls = $app->template_message->getPrivateTemplates();
        $data = [];
        $tplOpt = [];
        foreach ($tpls['template_list'] as $k => $tpl) {
            $tplOpt['template_id'] = $tpl['template_id'];
            $tplOpt['tpl_title'] = $tpl['title'];
            preg_match_all('/{{(.*?)\\.DATA}}/', $tpl['content'], $key);
            preg_match_all('/}}\s*(.*?){{/', $tpl['content'], $label);
            $keys = $this->_formatTplKey($key[1], $label[1]);
            $row[$tpl['template_id']] = $keys;
            $data['tpl_opt'][] = $tplOpt;
            $data['tpl_item'] = $row;
        }
        return $data;
    }

    private function _formatTplKey($key, $label)
    {
        $keys = array();
        for ($i = 0; $i < count($key); $i++) {
            if (empty($key[$i])) {
                continue;
            }
            if ($i == 0) {
                $keys[$i]['label'] = "首标题：";
                $keys[$i]['key'] = $key[$i];
                continue;
            }
            if ($i == count($key) - 1) {
                $keys[$i]['label'] = "尾备注：";
                $keys[$i]['key'] = $key[$i];
                continue;
            }
            $keys[$i]['label'] = $label[$i - 1];
            $keys[$i]['key'] = $key[$i];
        }
        return $keys;
    }

}