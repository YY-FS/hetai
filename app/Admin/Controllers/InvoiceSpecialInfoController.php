<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-3-28
 * Time: 下午2:20
 */
namespace App\Admin\Controllers;

use App\Models\Platv4UserInvoice;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\Url;

class InvoiceSpecialInfoController extends BaseController
{
    public function index($invoiceType)
    {
        $this->route = '/invoice/'.$invoiceType.'/info';
        $title = '专用发票信息管理';
        $filter = DataFilter::source(Platv4UserInvoice::rapydGrid($invoiceType));
        $filter->add('status', '状态', 'select')->options(['' => '全部状态'] + Platv4UserInvoice::$statusText);
        $filter->add('created_at', '申请时间', 'daterange')
            ->scope(function ($query, $value) {
                $value = explode('|', $value);
                if (!empty($value[0]))
                    $query = $query->where('created_at', '>=', $value[0]);
                if (!empty($value[1])) {
                    $value[1] = date('Y-m-d', strtotime($value[1]) + 24 * 60 * 60);
                    $query = $query->where('created_at', '<=', $value[1]);
                }
                return $query;
            })->format('Y-m-d', 'zh-CN');
        $filter->add('uid', 'UID', 'number');
        $filter->add('invoice_title', '发票抬头', 'text');
        $filter->add('tax_no', '税号', 'text');
        $filter->add('contact_name', '联系人', 'text');
        $filter->add('contact', '联系方式', 'number');
        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);
        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', 'ID', true);
        $grid->add('status', '状态', false);
        $grid->add('created_at', '申请日期', true);
        $grid->add('uid', 'UID', false);
        $grid->add('invoice_title', '发票抬头', false);
        $grid->add('tax_no', '税号', false);
        $grid->add('contact_name', '联系人', false);
        $grid->add('contact', '联系方式', false);
        $grid->add('reason', '备注', false);
        $grid->add('operation', '操作', false);

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), '导出Excel', 'TR', ['class' => 'btn btn-export', 'target' => '_blank']);

        $grid->row(function ($row) use (&$title) {
            //状态颜色判断
            if ($row->data->status !== 1)
                $row->cell('status')->style('color:red;');
            //状态正确判断
            if (in_array($row->data->status, [0, 1, -1]))
                $row->cell('status')->value = Platv4UserInvoice::$statusText[$row->data->status];
            else
                $row->cell('status')->value = '发生错误，请检查审核状态是否正确!!!';
            $options = [
                'btn_class' => 'btn btn-primary',
                'btn_text' => '编辑'
            ];
            $link = config('admin.route.prefix') . $this->route . "/edit?modify=" . $row->data->id;
            $row->cell('operation')->value = $this->getFrameBtn($link, $options, true,1200,800);
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

    public function anyEdit()
    {
        //配置保存和撤销改动的位置
        config([
            'rapyd.data_edit.button_position.save' => 'BR',
            'rapyd.data_edit.button_position.modify' => 'BL'
        ]);
        $edit = DataEdit::source(new Platv4UserInvoice());
        $edit->label('增值税专票信息审核');
        $edit->add('uid', 'UID', 'text')->attributes(['readOnly' => true])->rule('required');
        $edit->add('created_at', '申请日期', 'text')->attributes(['readOnly' => true])->rule('required');
        $edit->add('invoice_title', '发票抬头', 'text')->attributes(['readOnly' => true])->rule('required');
        $edit->add('tax_no', '税号', 'text')->attributes(['readOnly' => true])->rule('required');
        $edit->add('address', '公司注册地址', 'text')->attributes(['readOnly' => true])->rule('required');
        $edit->add('phone', '公司注册电话', 'text')->attributes(['readOnly' => true])->rule('required');
        $edit->add('bank_account', '开户银行名称', 'text')->attributes(['readOnly' => true])->rule('required');
        $edit->add('bank_name', '银行账户', 'text')->attributes(['readOnly' => true])->rule('required');
        //图片部分
        $edit->add('business_license', '营业执照', 'text')->attributes(['readOnly' => true])->rule('required');
        $edit->add('certificate', '纳税人资格证', 'text')->attributes(['readOnly' => true])->rule('required');
        $edit->add('account_license', '开户许可证', 'text')->attributes(['readOnly' => true])->rule('required');

        $edit->add('contact_name', '联系人', 'text')->attributes(['readOnly' => true])->rule('required');
        $edit->add('contact', '联系方式', 'text')->attributes(['readOnly' => true])->rule('required');
        //允许修改部分
        $edit->add('status', '审核状态', 'select')->rule('required')->options(Platv4UserInvoice::$statusText);
        $edit->add('reason', '备注', 'textarea')->rule('max:250')->placeholder('请输入备注');

        $edit->build();
        return $edit->view('invoicespecialinfo.frameEdit', compact('edit'));
    }
    
    public function downloadImg()
    {
        $file = Input::get('file',null);
        if ($file)
            return response()->download($file)->deleteFileAfterSend(true);
        $title = Input::get('invoice_title', null);
        $name = Input::get('id', null);
        switch ($name) {
            case 'business_license':
                $title .= '的营业执照';
                break;
            case 'certificate':
                $title .= '的纳税人资格证';
                break;
            case 'account_license':
                $title .= '的开户许可证';
                break;
            default:
                $title .= '的文件错误';
        }
        $title .= date('Y-m-d', time());
        $src = Input::get('src', null);
        if ($src) {
            $header = array("Connection: Keep-Alive", "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8", "Pragma: no-cache", "Accept-Language: zh-Hans-CN,zh-Hans;q=0.8,en-US;q=0.5,en;q=0.3", "User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:29.0) Gecko/20100101 Firefox/29.0");
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $src);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//传递header头
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//以文件流的形式
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);//自动跟踪重定向页面
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');//编码，防止乱码
            curl_setopt($ch, CURLOPT_HEADER, 0);//不显示header
            $img = curl_exec($ch);
            $curlinfo = curl_getinfo($ch);
            curl_close($ch);
            if ($curlinfo['http_code'] == 200) {
                switch ($curlinfo['content_type']) {
                    case 'image/jpeg':
                        $title .= '.jpg';
                        break;
                    case 'image/png':
                        $title .= '.png';
                        break;
                    case 'image/gif':
                        $title .= '.gif';
                        break;
                    default:
                        $title .= '的文件错误';
                }
                //先存在本地，下载后删除
                Storage::disk('invoice')->put($title, $img);
                $file = config('filesystems.disks.invoice.root') . DIRECTORY_SEPARATOR . $title;
                chmod($file, 0777);//改变权限，否则可能下载无效
                return $this->respData($file);
            }
        }
        return $this->respFail($title . '下载失败');
    }
}