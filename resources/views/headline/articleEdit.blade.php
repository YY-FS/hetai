@extends('admin.index')
@section('content')
    <style>
        #div_style label {
            width: 100px;
        }

        #fg_tags label {
            margin-left: 30px;
            width: 120px;
        }

        input[type=checkbox] {
            margin-right: 5px;
        }
    </style>

    {{--百度 UEditor--}}
    <script type="text/javascript" src="{{ asset ("/js/ueditor/ueditor.config.js") }}"></script>
    <script type="text/javascript" src="{{ asset ("/js/ueditor/ueditor.all.min.js") }}"></script>

    {{--jQuery 轻量级redactor--}}
    {{--<link rel="stylesheet" href="{{ asset("/packages/zofe/rapyd/assets/redactor/css/redactor.css") }}">--}}
    {{--<script type="text/javascript" src="{{ asset ("/packages/zofe/rapyd/assets/redactor/jquery.browser.min.js") }}"></script>--}}
    {{--<script type="text/javascript" src="{{ asset ("/packages/zofe/rapyd/assets/redactor/redactor.js") }}"></script>--}}

    {{--<link rel="stylesheet" href="{{ asset("/js/simditor/styles/simditor.css") }}">--}}

    {{--<script type="text/javascript" src="{{ asset ("/js/simditor/scripts/jquery.min.js") }}"></script>--}}
    {{--<script type="text/javascript" src="{{ asset ("/js/simditor/scripts/module.js") }}"></script>--}}
    {{--<script type="text/javascript" src="{{ asset ("/js/simditor/scripts/hotkeys.js") }}"></script>--}}
    {{--<script type="text/javascript" src="{{ asset ("/js/simditor/scripts/uploader.js") }}"></script>--}}
    {{--<script type="text/javascript" src="{{ asset ("/js/simditor/scripts/simditor.js") }}"></script>--}}

    <div style="padding:2%">
        <div class="rpd-edit">
            {!! $edit !!}
        </div>
    </div>

    {{--<script type="text/javascript">--}}
    {{--$('#div_link').html('').attr("name","link");--}}
    {{--UE.getEditor("div_link");--}}
    {{--</script>--}}

    <script>
        @if($edit->model->type == \App\Models\Platv4Headline::TYPE_ARTICLE)
        //            $('#link').html('').attr("name", "link").redactor();
        $('#div_link').html('').attr("name", "link");
        var ue = UE.getEditor("div_link");
        ue.ready(function () {
            //设置编辑器的内容
            ue.setContent('{!! $content !!}');
        });

        //        var editor = new Simditor({
        //            textarea: $('#link')
        //            //optional options
        //        });
        //            上传HTML
        var btn = '<br><button onclick="uploadHtml()" type="button" class="pull-left btn btn-primary">上传文章图片</button><br><hr>';
        setTimeout(function () {
            $('#div_link').append(btn);
        }, 1000);

        @endif

        function uploadHtml() {
//            loading
            var index = layer.load(0, {
                shade: [0.3, '#000']
            });
            var data = {
                'image_dir': '{!! $imageDir !!}',
                'content': $('#link').val(),
                'ajax': 1 // 标识是ajax请求，服务端才不会redirect
            };
            $.ajax({
                type: 'POST',
                url: '/headlines/jx_html',
                data: data,
                dataType: "json",
                success: function (data) {
                    var content = data.data.content;
                    layer.alert('上传成功');
                    $('#link').val(content);
                    layer.close(index);
                },
                error: function (data) {
                    alert('上传失败，请重新上传');
                    layer.close(index);
                }
            });
        }

        //        ---------------- 封面图处理 ---------------------
        var btnOss = '<br>' +
            '<div id="photo-preview"></' + 'div>' +
            '<span class="pull-right" id="count-photo">0</' + 'span>' +
            '<button onclick="loadOss()" type="button" class="pull-left btn btn-primary">选择封面图</' + 'button>' +
            '<span style="font-size: 12px; color: #999999; line-height: 50px;">点击图片删除</' + 'span>' +
            '<br><hr>';
        $('#div_thumb').append(btnOss);
        function loadOss() {
            layer.open({
                type: 2,
                title: '封面图选取',
                shadeClose: true,
                scrollbar: false,
                area: ['1024px', '640px'],
                content: '/headlines/oss?dir=HEADLINE/IMAGES/{!! $imageDir !!}/&single=false'
            });
        }

        //        删封面图
        function delPhoto(url, photoId) {
            layer.confirm('确认删除该封面图？', {
                btn: ['确认', '取消'] //按钮
            }, function () {
//                预览删除
                $('#' + photoId).remove();

                var thumb = '';
//                Input框
                $("#photo-preview img").each(function (i) {
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
        //        初始化封面图预览和计数
        $('#count-photo').text({!! count(explode(',', $edit->model->thumb)) !!});

        //        预览
                @foreach(explode(',', $edit->model->thumb) as $key => $item)
        var img = '<img ' +
                'id="photo-{!! $key !!}" ' +
                'onclick="delPhoto(\'{!! $item !!}\', \'photo-{!! $key !!}\')" ' +
                'style="border: 1px solid #3c8dbc; border-radius: 5px;padding: 2px; height: 50px;width: auto;margin-right: 3px" ' +
                'src="{!! $item !!}">';
        $('#photo-preview').append(img);
        @endforeach
    </script>
@endsection
