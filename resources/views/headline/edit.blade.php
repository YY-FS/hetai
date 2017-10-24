@extends('admin.index')
@section('content')
    {!! Rapyd::head() !!}
    <div style="padding:2%">
        <div class="rpd-edit">
            {!! $edit !!}
        </div>
    </div>
    <script>

//        封面图处理
        var btnOss = '<br><div id="photo-preview"></div><span class="pull-right" id="count-photo">0</span><button onclick="loadOss()" type="button" class="pull-left btn btn-primary">选择封面图</button><br><hr>';
        $('#div_thumb').append(btnOss);
        function loadOss() {
            layer.open({
                type: 2,
                title: '封面图选取',
                shadeClose: true,
                area: ['860px', '640px'],
                content: '/headlines/oss/{!! $id !!}'
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
                layer.msg('删除预览图成功', {icon: 2});
            });
        }
    </script>
@endsection
