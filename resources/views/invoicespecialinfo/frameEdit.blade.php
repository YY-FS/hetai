@extends('style')
@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {!! Rapyd::head() !!}
    <style>
        #fg_tags label {
            margin-left: 30px;
            width: 120px;
        }

        input[type=checkbox] {
            margin-right: 5px;
        }
    </style>
    <div style="padding:2%">
        <div class="rpd-edit">
            {!! $edit !!}
        </div>
    </div>
    <script type="text/javascript">
        //为之后的所有ajax作csrf防护
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        //营业执照
        @if(!empty($edit->model->business_license))
        loadImg('{!! $edit->model->business_license !!}', 'business_license');
        @endif
        //纳税人资格证
        @if(!empty($edit->model->certificate))
         loadImg('{!! $edit->model->certificate !!}', 'certificate');
        @endif
        //开户许可证
        @if(!empty($edit->model->account_license))
        loadImg('{!! $edit->model->account_license !!}', 'account_license');
        @endif
        function loadImg(src, id) {
            //拼接html标签时，结束标签要分成'</'和'div>'来拼接，不然会报错或者结束标签被屏蔽，影响后面加载js
            var imgBtn = '<br><div style="width:300px;height: auto;">' +
                    '<div  style="margin:68px 10px 0px 0px;float: right;"><button onclick="downloadImg(\'' + src + '\',\'' + id + '\')" type="button" class="pull-left btn btn-primary">下载图片</' +
                    'button></' + 'div><div id="photo-preview-' + id + '"></' + 'div>' +
                    '<div style="clear: both;"></'
                    + 'div></' + 'div><hr  style="margin: 20px 0px 5px;">';
            $('#div_' + id).append(imgBtn);
            var img = '<img ' +
                    'style="border: 1px solid #3c8dbc; border-radius: 5px;padding: 2px; height:100px;width: auto;" ' +
                    'src="' + src + '">';
            $('#photo-preview-' + id).append(img);
        }
        function downloadImg(src, id) {
            @if(!empty($edit->model->invoice_title))
            $.ajax({
                url: '/invoice/special/info/download',
                type: 'POST',
                async:false,//将异步改成同步，否则windows.open会被拦截
                dataType: 'json',
                data: {
                    src: src, id: id, invoice_title: '{!! $edit->model->invoice_title !!}'
                },
                success: function (e) {
                    window.open("download?file="+e.data);
                    if (e.success)
                        layer.msg('下载成功', {time: 2000});
                }
            });
            @endif
        }
    </script>
@endsection