@extends('admin.index')
@section('content')
    {!! Rapyd::head() !!}

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
        var btn = '<br><button onclick="uploadHtml()" type="button" class="pull-left btn btn-primary">上传图片</button><br><hr>';
        $('#link').html('').attr("name", "link").redactor();
        $('#div_link').append(btn);
        function uploadHtml() {
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
        }
    </script>
@endsection
