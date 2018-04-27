<?php
namespace App\Admin\Controllers;

use App\Models\Platv4Invoice;
use App\Models\Platv4User;
use App\Models\Platv4UserInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Naux\Mail\SendCloudTemplate;
use Symfony\Component\HttpFoundation\Response;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataForm\DataForm;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\Url;

class InvoiceController extends BaseController
{
    //发票列表
    /*
     * @param type string electron|common|special
     * */
    public function index($type)
    {
        $this->route = '/invoice';
        $title = Platv4Invoice::$routeText[$type] . '发票';
        $filter = DataFilter::source(Platv4Invoice::rapydGrid($type));

        $filter->add('status', '状态', 'select')->options(['' => '请选择状态'] + Platv4Invoice::$statusText['text'])
            ->scope(function ($query, $value) {
                return $value !== null ? $query->where('v.status', $value) : $query;
            });
        $filter->add('id', '申请单号', 'text')
            ->scope(function ($query, $value) {
                return $value !== null ? $query->where('v.id', $value) : $query;
            });
        $filter->add('invoice_no', '发票编码', 'text');
        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);
        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', '申请单号', false);
        $grid->add('uid', 'UID', false);
        $grid->add('invoice_title', '发票抬头', false);
        $grid->add('tax_no', '税号', false);
        $grid->add('content', '发票内容', false);
        $grid->add('total', '金额', false);
        $grid->add('phone', '联系电话', false);
        $grid->add('contact_name', '联系人', false);
        $grid->add('invoice_no', '发票编码', false);
        if ($type == Platv4Invoice::ROUTE_ELECTRON)
            $grid->add('email', '收件邮箱', false);
        $grid->add('status', '状态', false);
        $grid->add('comment', '备注', false);
        $grid->add('operation', '操作', false);

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), '导出Excel', 'TR', ['class' => 'btn btn-export', 'target' => '_blank']);

        $grid->row(function ($row) use ($type) {
            //状态
            $row->cell('status')->value = Platv4Invoice::$statusText['text'][$row->data->status];
            $row->cell('status')->style(Platv4Invoice::$statusText['style'][$row->data->status]);
            //操作
            $auditLink = config('admin.route.prefix') . $this->route . '/' . $type . "/audit?modify=" . $row->data->id;
            $auditBtn = $this->getFrameBtn($auditLink, ['btn_text' => '审核', 'btn_class' => 'btn btn-primary'], true, 940, 780);
            $sendLink = config('admin.route.prefix') . $this->route . '/' . $type . "/send?modify=" . $row->data->id;
            $sendBtn = $this->getFrameBtn($sendLink, ['btn_text' => '快递详情', 'btn_class' => 'btn btn-warning'], true, 1180, 780);
            if($type == Platv4Invoice::ROUTE_ELECTRON){
                $sendBtn = "<a style='cursor:pointer' class=\" btn btn-warning \" onclick=\"layer.open({
                                                                                type: 2, 
                                                                                title: ['', false], 
                                                                                area: ['1180px', '780px'], 
                                                                                shadeClose: false,
                                                                                scrollbar: false,
                                                                                content: '" . $sendLink . "',
                                                                                cancel: function(index, layero){".
                                                    "var email = $(layero).find('iframe')[0].contentWindow.email.value;".
                                                    "var isUpload = $(layero).find('iframe')[0].contentWindow.isUpload.value;".
                                                    "if(!email || !isUpload) return true;".
                                                    "var path = 'upload/'+email;".
                                                    "$.ajax({".
                                                        "url:'/invoice/upload',".
                                                        "type:'post',".
                                                        "dataType:'json',".
                                                        "data:{delDir:path,_token:'".csrf_token()."'},".
                                                        "success:function(data){".
                                                            "if(data.success){".
                                                            "layer.msg('已删除附件');".
                                                            "window.location.reload();".
                                                            "}".
                                                        "}".
                                                    "});".
                                                "}".
                                            "})\">发送邮件</a>";

            }
            if($row->data->status < Platv4Invoice::STATUS_AUDIT_SUCCESS)   $sendBtn = null;  //审核不通过不显示发出按钮
            $editLink = config('admin.route.prefix') . $this->route . '/' . $type . "/edit?modify=" . $row->data->id;
            $editBtn = $this->getFrameBtn($editLink, ['btn_text' => '编辑', 'btn_class' => 'btn btn-info'], true, 750, 650);
            $row->cell('operation')->value = $auditBtn . $sendBtn . $editBtn;
        });

        if (Input::get('export') == 1) {
            $grid->build();
            return $grid->buildCSV($title, 'Ymd');
        } else {
            $grid->paginate(self::DEFAULT_PER_PAGE);
            $grid->build();
            return view('rapyd.filtergrid', compact('title', 'filter', 'grid'));
        }

    }

    //发票审核页面
    public function anyAudit($type)
    {
        config([
            'rapyd.data_edit.button_position.save' => 'BR',
            'rapyd.data_edit.button_position.modify' => 'BL'
        ]);

        $title = '审核' . Platv4Invoice::$routeText[$type] . '发票';
        $edit = DataEdit::source(new Platv4Invoice());
        $invoiceId = $edit->model->id;
        //渲染表格
        $orderQueryBuilder = Platv4Invoice::getOrders($invoiceId);
        $count = $orderQueryBuilder->count();
        $orders = $orderQueryBuilder->paginate(self::DEFAULT_PER_PAGE);

        //展示详细信息
        $invoice = Platv4Invoice::getAudit($invoiceId);

        $detail = [];

        $postAddr = [
            'receive_man' => ['label' => '收件人', 'content' => $invoice->receive_man],
            'post_addr' => ['label' => '收件地址', 'content' => $invoice->province . $invoice->city . $invoice->district . $invoice->addr],
            'postcode' => ['label' => '邮编', 'content' => $invoice->postcode],
            'receive_phone' => ['label' => '收件电话', 'content' => $invoice->receive_phone],
            'comment' => ['label' => '备注', 'content' => $invoice->comment],
        ];

        $detail[Platv4Invoice::ROUTE_ELECTRON] = [
            'invoice_title' => ['label' => '发票抬头', 'content' => $invoice->invoice_title],
            'tax_no' => ['label' => '税号', 'content' => $invoice->tax_no],
            'content' => ['label' => '发票内容', 'content' => Platv4Invoice::$contentText[$invoice->content]],
            'total' => ['label' => '发票金额', 'content' => $invoice->total],
            'contact_phone' => ['label' => '联系电话', 'content' => $invoice->contact_phone],
            'contact_man' => ['label' => '联系人', 'content' => $invoice->contact_man],
            'email' => ['label' => '电子邮件', 'content' => $invoice->email],
        ];

        $detail[Platv4Invoice::ROUTE_COMMON] = $detail[Platv4Invoice::ROUTE_ELECTRON];
        unset($detail[Platv4Invoice::ROUTE_COMMON]['email']);

        $detail[Platv4Invoice::ROUTE_SPECIAL] = [
            'invoice_title' => ['label' => '发票抬头', 'content' => $invoice->invoice_title],
            'tax_no' => ['label' => '税号', 'content' => $invoice->tax_no],
            'content' => ['label' => '发票内容', 'content' => Platv4Invoice::$contentText[$invoice->content]],
            'total' => ['label' => '发票金额', 'content' => $invoice->total],
            'register_addr' => ['label' => '公司注册地址', 'content' => $invoice->register_addr],
            'register_phone' => ['label' => '公司注册电话', 'content' => $invoice->register_phone],
            'bank_name' => ['label' => '开户银行名称', 'content' => $invoice->bank_name],
            'bank_account' => ['label' => '银行账户', 'content' => $invoice->bank_account],
        ];

        $invoiceInfo = $detail[$type];

        $edit->add('status', '审核结果', 'radiogroup')
            ->options([Platv4Invoice::STATUS_AUDIT_FAILED => '不通过', Platv4Invoice::STATUS_AUDIT_SUCCESS => '通过'])->rule('required');
        $edit->add('invoice_no', '发票编码', 'text');
        $edit->add('reason', '拒绝原因', 'textarea');

        $edit->build();

        return $edit->view('invoice.gridEdit', compact('title', 'orders', 'edit', 'count', 'invoiceInfo', 'postAddr'));
    }

    //发票发送邮件(快递)
    public function anySend($type)
    {
        if ($type == Platv4Invoice::ROUTE_ELECTRON) {     //邮件
            $request = request();
            if ($request->isMethod('get')) {
                $title = '发送邮件';
                $invoiceId = Input::get('modify', 0);
                $invoice = Platv4Invoice::find($invoiceId);
                $tips = date('Y/m/d', strtotime($invoice->created_at)) . '&nbsp;&nbsp;&nbsp;&nbsp;申请单号：' . $invoice->id;
                return view('invoice.emailFrameEdit', compact('title', 'invoice', 'tips'));
            }

            if ($request->isMethod('post')) {
                //调用sendCloud
                $email = Input::post('email',null);
                $invoiceId = Input::get('invoice_id',null);
                $templateName = 'invoice_maka';
                $from = env('SEND_CLOUD_FROM','infos@maka.im');
                $fromName = env('SEND_CLOUD_FROM_NAME','MAKA');
                if($email){
                    try {
                        $bindData = ['bind' => 'nothing'];
                        $template = new SendCloudTemplate($templateName, $bindData);
                        Mail::raw($template, function ($message) use ($email,$from,$fromName) {
                            $message->from($from, $fromName);
                            $message->to(trim($email));
                            $attachmentDir = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . $email;
                            $handel = opendir($attachmentDir);
                            while (($file = readdir($handel)) !== false) {
                                if ($file != "." && $file != "..") {
                                    $filePath = $attachmentDir . DIRECTORY_SEPARATOR . $file;
                                    $message->attach($filePath);
                                }
                            }
                        });
                        Platv4Invoice::where('id',$invoiceId)->update(['status'=>Platv4Invoice::STATUS_SENT]);
                        return $this->respData();
                    }catch(\Exception $e){
                        return $this->respFail($e->getMessage());
                    }
                }
            }
        } else {      //快递
            //配置保存和撤销改动的位置
            config([
                'rapyd.data_edit.button_position.save' => 'BR',
                'rapyd.data_edit.button_position.modify' => 'BL'
            ]);
            $edit = DataEdit::source(new Platv4Invoice());
            //构建 tips  2018/1/31 普通纸质发票 申请单号：33300001112
            $tips = date('Y/m/d', strtotime($edit->model->created_at));
            $userInvoice = Platv4UserInvoice::find($edit->model->user_invoice_id, ['invoice_type']);
            if ($userInvoice->invoice_type === 'special')
                $tips .= ' 专用纸质发票';
            else
                $tips .= ' 普通纸质发票';
            $tips .= ' 申请单号：' . $edit->model->id;
            $edit->label('快递状态更新');
            $edit->add('express', '* 物流', 'text')->rule('required|max:20');
            $edit->add('express_no', '* 单号', 'text')->rule('required|max:64');
            $edit->add('express_time', '* 发出时间', 'datetime')->rule('required');
            $edit->saved(function () use ($edit) {
                if ($edit->model->express && $edit->model->express_no) {
                    $edit->model->status = Platv4Invoice::STATUS_SENT;
                    $edit->model->save();
                }
            });
            $edit->build();
            return $edit->view('invoice.deliveryFrameEdit', compact('edit', 'tips'));
        }

    }

    public function anyEdit($type)
    {
        $edit = DataEdit::source(new Platv4Invoice());
        $edit->label(Platv4Invoice::$routeText[$type] . '发票编辑');
        $edit->add('invoice_no', '发票编号', 'text');
        if($type == Platv4Invoice::ROUTE_ELECTRON)
            $edit->add('email','邮箱','text');
        $edit->add('comment', '备注', 'textarea');

        $edit->build();
        return $edit->view('rapyd.frameEdit', compact('edit'));
    }

    public function upload(Request $request)
    {
        //删除单个文件
        if($delFile = Input::post('delFile',null)){
            $res = unlink($delFile);
            if(!$res){
                return $this->respFail('删除文件失败');
            }
            return $this->respData(['delPath'=>$delFile],'删除成功');
        }
        //删除附件目录
        if($delDirPath = Input::post('delDir',null)){
            if(is_dir($delDirPath)){
                if($this->delDir($delDirPath))
                    return $this->respData(['delDirPath'=>$delDirPath],'删除目录成功');
                return $this->respFail('删除目录失败');
            }
            return $this->respFail($delDirPath.' 不是目录');
        }
        //上传文件
        $email = Input::post('email',null);
        $dir = 'upload'.DIRECTORY_SEPARATOR.$email;
        if(!$email)
            return $this->respFail('缺少邮箱',Response::HTTP_NOT_ACCEPTABLE);
        if(!is_dir($dir))
            mkdir($dir,0777,true);

        $file = $request->file('file');
        //chmod('upload',0777);    //upload文件夹位于public下，需要0777权限
        $fileName = $file->getClientOriginalName();
        $file->move($dir, $fileName);

        $filePath = $dir.DIRECTORY_SEPARATOR.$fileName;    //服务器删除文件路径
        $url = '/'.$dir.'/'.$fileName;  //浏览器访问路径
        if(is_file($dir.DIRECTORY_SEPARATOR.$fileName)){
            return $this->respData(['url'=>$url,'fileName'=>$fileName,'filePath'=>$filePath]);
        }
        //失败返回
        $fail = [
            'code'=>Response::HTTP_INTERNAL_SERVER_ERROR,
            'success'=>false,
            'data'=>[],
            'msg'=>'上传失败'
        ];
        return response()->json($fail);
    }

}