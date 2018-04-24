@extends('style')
@section('content')
    {!! Rapyd::head() !!}
    <style>
        #fg_tags label {
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
    <script type="text/javascript">
        //为之后的所有ajax作csrf防护
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            }
        });
        $(function () {
            typeShow();
        });
        $('#type').change(function () {
            typeShow();
        });
        function typeShow() {
            let type = $('#type');
            if (type.val() == 'maka' || type.val() == 'poster' || type.val() == 'danye') {
                $('#fg_url').hide("normal");
                $('#fg_template_set_id').show("normal");
            }
            else if (type.val() == 'category') {
                $("#fg_template_set_id").hide("normal");
                $('#fg_url').hide("normal");
            }
            else if (type.val() == 'link') {
                $('#fg_url').show("normal");
                $('#fg_template_set_id').hide("normal");
            }
        }
        let submitBtn = $("input[type='submit']").parent();
        toolBar = submitBtn.parent();//全局变量
        let btn = "<div class='pull-right'><input id='pull-ok' class='btn btn-primary' value = '确定'></" + "div>";
        submitBtn.remove();//清除submit的按钮
        toolBar.append(btn);
        $('#pull-ok').click(function () {
            let uid = $('#uid').val();
            let banner_id = $('#banner_id').val();
            let device = $('#device').val();
            let type = $('#type').val();
            let title = $('#title').val();
            let description = $('#description').val();
            let url = $('#url').val();
            let template_set_id = $('#template_set_id').val();
            //前端判断是否为空
            if (banner_id == '' || title == ''||uid=='') {
                build_error('请完整填写数据');
                return false;
            } else {
                clear_error();
            }
            $.ajax({
                url: "/message/push/test",
                dataType: "json",
                method: "POST",
                data: {
                    uid: uid,
                    banner_id: banner_id,
                    device: device,
                    type: type,
                    title: title,
                    description: description,
                    url: url,
                    template_set_id: template_set_id
                },
                success: function (data) {
                    build_error(data.msg);
                }
            })
        });
        function build_error(message) {
            if ($('#data-error').length == 0) {
                let error = "<div style='color: red' id='data-error'>" + message + "</" + "div>";
                toolBar.append(error);
            }
        }
        function clear_error() {
            $('#data-error').remove();
        }
    </script>
@endsection