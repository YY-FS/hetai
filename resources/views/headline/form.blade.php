@extends('admin.index')
@section('content')
    {!! Rapyd::head() !!}
    <style>
        input[type=radio] {
            margin: 4px 0 0 25px !important;
        }
    </style>

    {{--百度 UEditor--}}
    {{--<script type="text/javascript" src="{{ asset ("/js/ueditor/ueditor.config.js") }}"></script>--}}
    {{--<script type="text/javascript" src="{{ asset ("/js/ueditor/ueditor.all.min.js") }}"></script>--}}

    {{--jQuery 轻量级redactor--}}
    <link rel="stylesheet" href="{{ asset("/packages/zofe/rapyd/assets/redactor/css/redactor.css") }}">
    <script type="text/javascript" src="{{ asset ("/packages/zofe/rapyd/assets/redactor/jquery.browser.min.js") }}"></script>
    <script type="text/javascript" src="{{ asset ("/packages/zofe/rapyd/assets/redactor/redactor.js") }}"></script>

    <div style="padding:2%">
        <div class="rpd-edit">
            {!! $form !!}
        </div>
    </div>

    {{--<script type="text/javascript">--}}
        {{--$('#div_link').html('').attr("name","link");--}}
        {{--UE.getEditor("div_link");--}}
    {{--</script>--}}

    <script>
        @if($type == \App\Models\Platv4Headline::TYPE_ARTICLE)
            $('#link').html('').attr("name", "link").redactor();
//            上传HTML
            var btn = '<br><button onclick="uploadHtml()" type="button" class="pull-left btn btn-primary">上传文章图片</button><br><hr>';
            $('#div_link').append(btn);
        @endif

        function uploadHtml() {
//            loading
            var index = layer.load(0, {
                shade: [0.3,'#000']
            });
            var data = {
                'id': '{!! $nextId !!}',
                'content': $('#link').val(),
                'ajax': 1 // 标识是ajax请求，服务端才不会redirect
            };
            $.ajax({
                type: 'POST',
                url: '/headlines/html',
                data: data,
                dataType: "json",
                success: function (data) {
                    var content = data.data.content;
                    layer.alert('上传成功');
                    $('#link').val(content)
                },
                error: function (data) {
                    alert('上传失败，请重新上传')
                }
            });
            layer.close(index);
        }

//        ---------------- 封面图处理 ---------------------
        var btnOss = '<br><div id="photo-preview"></div><span class="pull-right" id="count-photo">0</span><button onclick="loadOss()" type="button" class="pull-left btn btn-primary">选择封面图</button><br><hr>';
        $('#div_thumb').append(btnOss);
        function loadOss() {
            layer.open({
                type: 2,
                title: '封面图选取',
                shadeClose: true,
                area: ['860px', '640px'],
                content: '/headlines/oss/{!! $nextId !!}'
            });
        }

//        删封面图
        function delPhoto(url, photoId) {
            layer.confirm('确认删除该封面图？', {
                btn: ['确认','取消'] //按钮
            }, function(){
//                预览删除
                $('#' + photoId).remove();

                var thumb = '';
//                Input框
                $("#photo-preview img").each(function(i){
                    thumb = thumb + $(this).attr("src") + ',';
                });
                thumb = thumb.substring(0, thumb.length - 1);
                $('#thumb').val(thumb);

//                计算选的封面图数量
                $('#count-photo').text(parseInt($('#count-photo').text()) - 1);
                layer.msg('删除预览图成功', {icon: 1});
            });
        }
//        ---------------- 封面图处理 ---------------------
    </script>
@endsection
