@extends('admin.index')
@section('content')
    {!! Rapyd::head() !!}
    <style>
        #div_style label {
            width: 100px;
        }

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

    <script>
        var templateOpt = null;    //模板渲染选项数据
        var templateItem = null;    //模板渲染字段数据
        var extraJson = null;    //模板 值 数据

        $(function(){

            @if($alias == 'template')
            getTemplate();
            judgeAction();
            @endif

            $('#action').change(judgeAction);

            function judgeAction(){
                if($('#action').val() == 'link'){
                    //类 .mini_program .link 在后端渲染时指定 OfficialAccountInfoController->anyEdit
                    $('.mini_program').parent().parent().hide();
                    $('.link').parent().parent().show();
                }else if($('#action').val() == 'mini_program'){
                    $('.mini_program').parent().parent().show();
                    $('.link').parent().parent().hide();
                }
            }

            $('#template').change(function(){
                renderTemplateField();
            });

            //时间选择器
            $('#send_time').datetimepicker({
                format: 'YYYY-MM-DD HH:mm',
                locale: 'zh-CN',
                showTodayButton: true,
                keepOpen: true
            });

        });

         function getTemplate(){
             if(templateOpt) {
                 renderTemplateOpt();
             }else{
                 $.ajax({
                     'url':'/api/wechat/template',
                     'dataType':'json',
                     'type':'get',
                     'success':function(data){
                         templateOpt = data.tpl_opt;
                         templateItem = data.tpl_item;
                         @if(isset($extraData['template_id']))
                         var tplId = '{{ $extraData['template_id'] }}';
                         extraJson = {!! json_encode($extraData) !!};
                         valueJson = extraJson['field'];
                         var i = 0;
                         for(var j in valueJson){
                             templateItem[tplId][i].value = valueJson[j].value;
                             templateItem[tplId][i].color = valueJson[j].color;
                             i++;
                         }
                         @endif
                         renderTemplateOpt(tplId);
                         renderTemplateField();
                         renderAction();
                     },
                     'error':function(obj,message){
                         console.log(message);
                     }
                 });
             }
         }

        function renderTemplateOpt(id){
            var optTpl = "<option value=\""+0+"\">请选择模板</option>";
            //渲染模板选项
            for(var i in templateOpt){
                if(templateOpt[i].template_id == id){
                    optTpl += "<option value=\""+templateOpt[i].template_id+"\" selected>"+templateOpt[i].tpl_title+"</option>";
                }else{
                    optTpl += "<option value=\""+templateOpt[i].template_id+"\">"+templateOpt[i].tpl_title+"</option>";
                }
            }
            $('#template').html(optTpl);
        }

        function renderTemplateField(){
            var renderItemTpl = '';
            //判断选中什么模板
            var itemKey = $('#template').val();
            if(itemKey == 0){
                $('#templateItem').remove();
                return false;
            }
            //渲染模板字段
            for(var t of templateItem[itemKey]){
                var value = '';
                var color = '#000000';
                if(templateItem[itemKey][0]['value'] !== undefined && templateItem[itemKey][0]['value'] !== null){
                    value = t['value'];
                    color = t['color'];
                }
                renderItemTpl += `<div class="form-group clearfix" id="fg_${t['key']}" style="display: block;">
                    <label for="mini_program_path" class="col-sm-2 control-label">${t['label']}</`+`label>
                <div class="col-sm-5" id="div_mini_program_path">
                    <input class="template action form-control"  type="text" id="${t['key']}" name="extra[field][${t['key']}][value]" value="${value}">
                    </`+`div>
                <div class="col-sm-5" id="div_${t['key']}_color">
                    <input class="template action form-control"  type="text" id="${t['key']}_color" name="extra[field][${t['key']}][color]" value="${color}">
                    </`+`div>
                </`+`div>`;
            }

            renderItemTpl = `<div id="templateItem">${renderItemTpl}</`+`div>`
            $('#templateItem').remove();  //删除上一次渲染的字段
            $('#fg_template').after(renderItemTpl);
        }

        function renderAction(){
            //渲染url
            if(extraJson['url'])
                $('.link').val(extraJson['url']);
            if(extraJson['mini_program_id'])
                $('.mini_program_id').val(extraJson['mini_program_id']);
            if(extraJson['mini_program_id'])
                $('.mini_program_path').val(extraJson['mini_program_path']);
        }

    </script>
@endsection
