@extends('admin.index')
@section('content')
    <div style="padding:2%;background-color: #ffffff;margin: 2px 20px 0 20px;border-radius: 5px;">


        <table class="table table-bordered table-striped table-hover">
            <tr>
                <td>包名</td>
                <td>APPID</td>
                <td>审核中的version</td>
                <td>操作</td>
            </tr>
            @foreach($bundles as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['app_id'] }}</td>
                    <td>{{ $item['version'] }}</td>
                    <td>
                        <input id="{{ $item['app_id'] }}" value="{{ $item['version'] }}">
                        <button class="btn btn-default" onclick="editVersion('{{ $item['app_id'] }}')">修改</button>
                    </td>
                </tr>
            @endforeach
        </table>

    </div>

    <script>
        function editVersion(appId) {

            data = {
                'app_id': appId,
                'version': $('#' + appId).val()
            }
            $.ajax({
                type: 'POST',
                url: '/mina/jx_version',
                data: data,
                dataType: "json",
                success: function (data) {
                    layer.alert('更新成功');
                    location.reload();
                },
                error: function (data) {
                    alert('更新失败');
                }
            });
        }
    </script>

@endsection
