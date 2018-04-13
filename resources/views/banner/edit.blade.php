@extends('admin.index')
@section('content')
    {!! Rapyd::head() !!}
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
            allStartTime = '';
            allEndTime = '';
            init();
            if($('input[value=group]').is(':checked')){
                groupStartTime = $('#fg_start_time input').val();
                groupEndTime = $('#fg_end_time input').val();
                selectGroup();
            }else if($('input[value=discount]').is(':checked')){
                selectDiscount();
            }else if($('input[value=all]').is(':checked')) {
                allStartTime = $('#fg_start_time input').val();
                allEndTime = $('#fg_end_time input').val();
                selectAll();
            }

            function init(){
                $('#fg_template_set_id').hide();
                $('#fg_url').hide();
                $('#fg_discount_id').hide();
                $('#fg_group').hide();
                targetChange();
            }

            function selectGroup(){
                $('#fg_start_time input').val(groupStartTime);
                $('#fg_end_time input').val(groupEndTime);
                $('#fg_group').show();
                $('#fg_discount_id').hide();
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
                $('#fg_discount_id').show();
            }

            function selectAll(){
                $('#fg_discount_id').hide();
                $('#fg_group').hide();
                $('#fg_start_time input').val(allStartTime);
                $('#fg_end_time input').val(allEndTime);
                $('#fg_start_time input').attr('readonly',false);
                $('#fg_end_time input').attr('readonly',false);
            }

            function discountChange(){
                var discountID = $('#div_discount_id select').val();
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

            function targetChange(){
                var targetVal = $('#div_target select').val();
                if(targetVal == 'link'){
                    $('#fg_template_set_id').hide();
                    $('#template_set_id').val('0');
                    $('#fg_url').show();
                }else if(targetVal == 0){
                    $('#fg_template_set_id').hide();
                    $('#fg_url').hide();
                }else{
                    $('#fg_template_set_id').show();
                    $('#url').val(' ');
                    $('#fg_url').hide();
                }
            }

            $('input[value=group]').change(selectGroup);
            $('input[value=discount]').change(selectDiscount);
            $('input[value=all]').change(selectAll);
            $('#div_discount_id select').change(discountChange);
            $('#div_target select').change(targetChange);



            //拼接html标签时，结束标签要分成'</'和'div>'来拼接，不然会报错或者结束标签被屏蔽，影响后面加载js
            var btnOss = '<br>' +
                '<div id="photo-preview"></'+'div>' +
                '<span class="pull-right" id="count-photo">0</'+'span>' +
                '<button onclick="loadOss()" type="button" class="pull-left btn btn-primary">选择封面图</'+'button>' +
                '<span style="font-size: 12px; color: #999999; line-height: 50px;">点击图片删除</'+'span>' +
                '<br><hr>';
            $('#div_thumb').append(btnOss);

            function loadOss(){
                layer.open({
                    type: 2,
                    title: '封面图选取',
                    shadeClose: true,
                    scrollbar: false,
                    area: ['1024px', '640px'],
                    content: '/banners/oss?dir=BANNER/IMAGES/{!! $imageDir !!}/&single=true'
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
//        ---------------- 模态窗图预览 ---------------------
            @if(!empty($edit->model->thumb))
            $('#count-photo').text({!! count(explode(',', $edit->model->thumb)) !!});

            //        预览
            @foreach(explode(',', $edit->model->thumb) as $key => $item)
            var img = '<img '+
                'id="photo-{!! $key !!}" '+
                'onclick="delPhoto(\'http://{!! env('ALI_OSS_PLAT_VIEW_DOMAIN') !!}/{!! $item !!}\', \'photo-{!! $key !!}\')" '+
                'style="border: 1px solid #3c8dbc; border-radius: 5px;padding: 2px; height: 50px;width: auto;margin-right: 3px" '+
                'src="http://{!! env('ALI_OSS_PLAT_VIEW_DOMAIN') !!}/{!! $item !!}">';
            $('#photo-preview').append(img);
            @endforeach
            @endif


    </script>
@endsection
