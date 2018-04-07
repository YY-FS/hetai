@extends('style')
@section('content')
    {!! Rapyd::head() !!}
    <style>
        label{
            text-align: right;
        }
    </style>
    <div style="padding:2%;background-color: #ffffff;margin: 2px 20px 0 20px;border-radius: 5px;">
        @if(!empty($title))
            <h4 class="pull-left" style="border-left: 3px solid #2cc6ba;padding-left: 7px">{!! $title !!}</h4>
        @else
            <h4 class="pull-left">列表</h4>
        @endif

        @if(isset($tips))
            <br>
            <br>
            <p style="color: #969696;font-size: 16px">{!! $tips !!}</p>
        @endif
        <div class="rpd-edit">
            <form>
                <div class="form-group clearfix" id="fg_email">
                    <label for="email" class="col-sm-2 control-label">收件人</label>
                    <div class="col-sm-10" id="div_email">
                        <input class="form-control" type="text" id="email" value="{{ $invoice->email }}" >
                    </div>
                </div>
                <div class="form-group clearfix" id="fg_content">
                    <label for="content" class="col-sm-2 control-label">sendCloud模板</label>
                    <div class="col-sm-10" id="div_content">
                        <input class="form-control" type="text" value="invoice_maka" disabled>
                    </div>
                </div>
                <div class="form-group clearfix" id="fg_invoice_img">
                    <label for="invoice_img" class="col-sm-2 control-label">附件</label>
                    <div class="col-sm-10" id="div_invoice_img">

                        <div class="upload">
                            <button type="button" class="btn btn-primary" id="selectFiles">选择文件</button>
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <div class="upload-list">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr><th>文件名</th>
                                            <th>大小</th>
                                            <th>上传进度</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody id="filesList">

                                    </tbody>
                                </table>
                                <br>
                                <input type="hidden" value="" id="isUpload">  <!-- 用于标识是否有上传文件 -->
                                <button type="button" class="btn btn-success" id="sendBtn">确认发送</button>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>

    <script type="text/javascript" src="{{ asset ("/js/oss/lib/plupload-2.1.2/js/plupload.full.min.js") }}"></script>
    <script>
        //设置ajax全局csrf
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        var tr = '';
        var uploader = new plupload.Uploader({ //实例化一个plupload上传对象
            runtimes : 'html5,flash,silverlight,html4',
            browse_button: 'selectFiles',
            url: '/invoice/upload',
            multipart_params:{
                _token: '{{ csrf_token() }}' ,
                email: $('#email').val()
            },
            flash_swf_url : '/js/oss/lib/plupload-2.1.2/js/Moxie.swf',
            silverlight_xap_url : '/js/oss/lib/plupload-2.1.2/js/Moxie.xap',
            filters: {
                mime_types: [ //只允许上传图片、压缩文件、pdf文件
                    { title : "Image files", extensions : "jpg,gif,png,bmp,jpeg" },
                    { title : "Zip files", extensions : "zip,rar" },
                    { title : "pdf files", extensions : "pdf" }
                ],
                max_file_size : '10mb', //最大只能上传10mb的文件
                prevent_duplicates : false //允许选取重复文件
            }
            });
        uploader.init(); //初始化

        //选择文件后自动上传
        uploader.bind('FilesAdded', function (uploader, files) {
            if(!$('#email').val()){
                uploader.trigger('Error',{code:406,message:'邮箱未指定'});
                return false;
            }
            plupload.each(files, function(file) { //遍历文件
                 tr = '';
                 tr = "<tr><td>"+file.name+"</td>" +
                        "<td>"+plupload.formatSize(file.size)+"</td>" +
                        "<td id='"+file.id+"_percent'> 0 %</td>" +
                        "<td id='"+file.id+"_operation'></td></tr>"
                $('#filesList').parent().append(tr);
            });
            uploader.start();
        });

        //上传进度
        uploader.bind('UploadProgress',function(uploader,file){
            $('#'+file.id+'_percent').text(file.percent+' %');
        });

        //上传成功
        uploader.bind('FileUploaded',function(uploader, file, info){
            var result = eval("("+info.response+")");  //成功返回例子 {url:url_param,fileName:filename}
            if(!result.success){
                uploader.trigger('Error',{code:500,message:'文件'+file.name+'上传失败',file:file});
            }
            var previewBtn = "<a class='btn btn-success btn-small' href='"+result.data.url+"' target='_blank'>预览</a>";
            var deleteBtn = "<a class='btn btn-danger btn-small' href='javascript:void(0)' onclick='delFile(\""+result.data.filePath+"\",event)'>删除</a>";
            $('#'+file.id+'_operation').html(previewBtn+deleteBtn);
            $('#isUpload').val(1);
        });

        //上传错误
        uploader.bind('Error',function(uploader,errObj){
            layer.msg(errObj.message, {icon: 5});
            if(errObj.file){
                $('#'+errObj.file.id+'_percent').text('上传失败');
                $('#'+errObj.file.id+'_percent').css('color','red');
                $('#'+errObj.file.id+'_operation').html('');
            }
        });

        //上传文件列表中删除已上传文件
        function delFile(path,event){
            layer.confirm('确定删除吗？', {
                btn: ['确定','取消'] //按钮
            }, function(){
                $.ajax({
                    url:'/invoice/upload',
                    type:'post',
                    dataType:'json',
                    data:{
                        delFile: path
                    },
                    success:function(data){
                        if(data.success){
                            $(event.target).parent().parent().remove();
                            layer.msg('删除成功', { icon:6 });
                        }
                    },
                    error:function(jqXHR,textStatus,errorThrown){
                        layer.msg(jqXHR.responseJSON.message,{ icon:5 });
                    }
                });
            });

        }

        //点击确认发送时发送邮件
        $('#sendBtn').click(function(){
            if(!$('#email').val()){
                layer.msg('邮件不能为空');
                return false;
            }
            if(!$('#isUpload').val()){
                layer.msg('未上传任何文件');
                return false;
            }
            $.ajax({
                url:'/invoice/electron/send',
                type:'post',
                dataType:'json',
                data:{
                    email:$('#email').val()
                },
                beforeSend:function(){
                    loadWait('安排发送中，请勿点击关闭按钮');
                    setTimeout(function(){
                        layer.msg('安排发送成功，可关闭窗口',{icon:6,time:1500});
                    },3000);
                },
                success:function(data){
                    if(data.success){
                        $('#sendBtn').text('已发送');
                        $('#sendBtn').attr('class','btn btn-info');
                        $('#sendBtn').attr('disabled','disabled');
                        layer.msg('发送成功',{icon:6,time:1500});
                    }
                },
                error:function(jqXHR,textStatus,errorThrown){
                    layer.msg(jqXHR.responseJSON.message,{ icon:5 });
                }
            });
        });

        //加载层
        function loadWait(waitText){
            waitText=''+waitText;
            var max=arguments[1]||0;//自动关闭时间
            var index=0;
            index=layer.msg(
                '<h4 class="WaitIng">'+waitText+'</h4>'//样式需要自己定义，或者直接写内容
                ,{
                    zIndex:9527//更改窗口层次
                    ,icon: 16
                    ,time:max//默认不自动关闭
                    ,anim:1
                    ,shade:[0.4,'#CCC']
                }
            );
            return index;
        }
    </script>
@endsection