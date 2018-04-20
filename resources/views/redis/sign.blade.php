@extends('admin.index')
@section('content')
    <div style="padding:2%;background-color: #ffffff;margin: 2px 20px 0 20px;border-radius: 5px;">


        <table class="table table-bordered table-striped table-hover">
            <tr>
                <td>名称</td>
                <td>操作</td>
            </tr>
            <tr>
                <td>清用户日签信息</td>
                <td>
                    <input id="uid" value="">
                    <button class="btn btn-default" onclick="action()">清理当天日签</button>
                </td>
            </tr>
            <tr>
                <td>清用户登录限制信息</td>
                <td>
                    <input id="username" value="">
                    <button class="btn btn-default" onclick="login()">清理登录限制</button>
                </td>
            </tr>
        </table>

    </div>

    <script>
        function action() {

            $.ajax({
                type: 'POST',
                url: '/plat/config/jx_clean_sign/' + $('#uid').val(),
                data: {},
                dataType: "json",
                success: function (data) {
                    layer.alert('更新成功');
                },
                error: function (data) {
                    alert('更新失败');
                }
            });
        }
        function login() {

            $.ajax({
                type: 'POST',
                url: '/plat/config/jx_clean_login/' + $('#username').val(),
                data: {},
                dataType: "json",
                success: function (data) {
                    layer.alert('更新成功');
                },
                error: function (data) {
                    alert('更新失败');
                }
            });
        }
    </script>

@endsection
