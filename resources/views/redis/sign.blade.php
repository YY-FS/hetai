@extends('admin.index')
@section('content')
    <div style="padding:2%;background-color: #ffffff;margin: 2px 20px 0 20px;border-radius: 5px;">


        <table class="table table-bordered table-striped table-hover">
            <tr>
                <td>操作</td>
            </tr>
            <tr>
                <td>
                    <input id="uid" value="">
                    <button class="btn btn-default" onclick="action()">修改</button>
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
    </script>

@endsection
