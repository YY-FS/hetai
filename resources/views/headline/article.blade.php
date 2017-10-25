@extends('admin.index')
@section('content')
    {!! Rapyd::head() !!}

    {{--jQuery 轻量级redactor--}}
    <link rel="stylesheet" href="{{ asset("/packages/zofe/rapyd/assets/redactor/css/redactor.css") }}">
    <script type="text/javascript" src="{{ asset ("/packages/zofe/rapyd/assets/redactor/jquery.browser.min.js") }}"></script>
    <script type="text/javascript" src="{{ asset ("/packages/zofe/rapyd/assets/redactor/redactor.js") }}"></script>

    <div style="padding:2%">
        <div class="rpd-edit">
            <a href="/headlines/edit?modify={!! $id !!}" class="pull-right btn btn-primary">编辑信息</a>
            <a href="/headlines"class="pull-right btn btn-default">返回列表</a>
            <br>
            <form id="edit-content" action="/headlines/html" method="POST">
                <label for="redactor_content">头条内容</label>
                <textarea id="redactor_content" name="content" style="height: 560px;">{!! $content !!}</textarea>
                <input hidden="hidden" name="id" value="{!! $id !!}" />
                <hr>
                <button onclick="loading()" class="pull-right btn btn-primary" type="submit">提交修改</button>
                <br>
                <br>
            </form>
        </div>
    </div>

    <script>
        $('#redactor_content').redactor();
//        loading
        function loading() {
            var index = layer.load(0, {
                shade: [0.3,'#000']
            });
        }
    </script>
@endsection
