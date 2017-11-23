@extends('admin.index')
@section('content')
    {!! Rapyd::head() !!}

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
            <a href="/headlines/edit?modify={!! $id !!}" class="pull-right btn btn-primary">编辑信息</a>
            <a href="/headlines"class="pull-right btn btn-default">返回列表</a>
            <br>
            <form id="edit-content" action="/headlines/html" method="POST">
                <label for="redactor_content">头条内容</label>
                {{--<textarea id="redactor_content" name="content" style="height: 560px;">{!! $content !!}</textarea>--}}
                <script id="redactor_content" name="content" type="text/plain">
                </script>
                <input hidden="hidden" name="id" value="{!! $id !!}" />
                <input hidden="hidden" name="image_dir" value="{!! $imageDir !!}" />
                <hr>
                <button onclick="loading()" class="pull-right btn btn-primary" type="submit">提交修改</button>
                <br>
                <br>
            </form>
        </div>
    </div>

    <script>
//        $('#redactor_content').redactor();
//        var editor = new Simditor({
//            textarea: $('#redactor_content')
//            //optional options
//        });

        var ue = UE.getEditor("redactor_content");
        ue.ready(function() {
            //设置编辑器的内容
            ue.setContent('{!! $content !!}');
        });

//        loading
        function loading() {
            var index = layer.load(0, {
                shade: [0.3,'#000']
            });
        }
    </script>
@endsection
