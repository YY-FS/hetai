@extends('admin.index')
@section('content')
    <style>
        #div_style label{
            width: 100px;
        }
        #fg_tags label{
            margin-left: 30px;
            width: 120px;
        }
        input[type=checkbox] {
            margin-right: 5px;
        }
    </style>

    <div style="padding:2%">
        <div class="rpd-edit">
            {!! $edit !!}
        </div>
    </div>

    <script>

        groupStartTime = '';
        groupEndTime = '';
        if($('input[value=group]').is(':checked')){
            groupStartTime = $('#fg_start_time input').val();
            groupEndTime = $('#fg_end_time input').val();
            selectGroup();
        }else if($('input[value=customer_vip_discount_id]').is(':checked')){
            selectDiscount();
        }else{
            $('#fg_customer_vip_discount_id').hide();
            $('#fg_group').hide();
        }

        function selectGroup(){
            $('#fg_start_time input').val(groupStartTime);
            $('#fg_end_time input').val(groupEndTime);
            $('#fg_group').show();
            $('#fg_customer_vip_discount_id').hide();
            $('#fg_start_time input').attr('readonly',false);
            $('#fg_end_time input').attr('readonly',false);
        }

        function selectDiscount(){
            groupStartTime = $('#fg_start_time input').val();
            groupEndTime = $('#fg_end_time input').val();
            discountChange();
            $('#fg_group').hide();
            $('#fg_start_time input').attr('readonly',true);
            $('#fg_end_time input').attr('readonly',true);
            $('#fg_customer_vip_discount_id').show();
        }

        function discountChange(){
            var discountID = $('#div_customer_vip_discount_id select').val();
            if(discountID>0){
                $.ajax({
                    url:'/api/discount_time?discount_id='+discountID,
                    type:'get',
                    dataType:'json',
                    success:function(data){
                        if(data) {
                            $('#fg_start_time input').val(data.start_time);
                            $('#fg_end_time input').val(data.end_time);
                        }
                    }
                });
            }else{
                $('#fg_start_time input').val('');
                $('#fg_end_time input').val('');
            }
        }

        $('input[value=group]').change(selectGroup);
        $('input[value=discount]').change(selectDiscount);
        $('#div_customer_vip_discount_id select').change(discountChange);

//        ---------------- 弹窗图处理 ---------------------
        var btnOss = '<br>' +
            '<div id="photo-preview"></div>' +
            '<span class="pull-right" id="count-photo">0</span>' +
            '<button onclick="loadOss()" type="button" class="pull-left btn btn-primary">选择封面图</button>' +
            '<span style="font-size: 12px; color: #999999; line-height: 50px;">点击图片删除</span>' +
            '<br><hr>';
        $('#div_thumb').append(btnOss);
        function loadOss() {
            layer.open({
                type: 2,
                title: '封面图选取',
                shadeClose: true,
                scrollbar: false,
                area: ['1024px', '640px'],
                content: '/modal/oss?dir=MODAL/IMAGES/{!! $imageDir !!}/&single=true'
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
