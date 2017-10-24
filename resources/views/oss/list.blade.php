@extends('style')
<br>
<div style="padding:2%;background-color: #ffffff;margin: 2px 20px 0 20px;border-radius: 5px;">
    <button class="btn btn-primary pull-right">上传图片</button>
    <table class="table table-striped">
        <thead>
        <tr>
            <td class="tiny">预览</td>
            <td class="small">大小</td>
            <td class="small">操作</td>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $item)
            <tr>
                <td>
                    <img style="height: 70px; width: auto" src="{!! $item['url'] !!}">
                </td>
                <td class="small">{!! $item['size'] !!}</td>
                <td class="small">
                    <button onclick="selectPhoto('{!! $item['url'] !!}', 'photo-{!! $item['auto_id'] !!}')" class="btn btn-primary">选取</button>
                </td>
            </tr>

        @endforeach
        </tbody>
    </table>
</div>
<script>
    function selectPhoto(url, photoId){
        var index = parent.layer.getFrameIndex(window.name); //获取窗口索引

//        计算选的封面图数量
        parent.$('#count-photo').text(parseInt(parent.$('#count-photo').text()) + 1);

//        入库数据
        var thumb = parent.$('#thumb').val();
        if (thumb == '') parent.$('#thumb').val(url);
        else parent.$('#thumb').val(thumb + ',' + url);

//        预览
        var img = '<img ' +
                    'id="' + photoId + '" ' +
                    'onclick="delPhoto(\'' + url + '\', \'' + photoId + '\')" ' +
                    'style="border: 1px solid #3c8dbc; border-radius: 5px;padding: 2px; height: 50px;width: auto;margin-right: 3px" ' +
                    'src="' + url + '">';
        parent.$('#photo-preview').append(img);
//        parent.layer.close(index);
    }
</script>

