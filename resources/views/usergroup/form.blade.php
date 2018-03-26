@extends('admin.index')
@section('content')
    {!! Rapyd::head() !!}
    <style>
        #div_style label{
            width: 100px;
        }
        label{
            margin-left: 0px;
            width: 160px;
        }
        .auto-mode span>label:first-child,.fix-type span>label:first-child{
            margin:20px 0px 10px 0px;
            display:block;
            font-size: 18px;
        }
        input[type=checkbox] {
            margin-right: 5px;
        }
    </style>
    <div style="padding:2%">
        <div class="rpd-edit">
            <div class="rpd-form">
                {!! $obj->header !!}

                {!! $obj->message !!}

                @if(!$obj->message)
                <div class="fix-type">
                    {!! $obj->render('name') !!}

                    {!! $obj->render('comment') !!}

                    {!! $obj->render('mode') !!}

                <span id="div_collect" class="form-group clearfix" style="display:none">
                    <label for="groupCollect" >分群用户id</label>
                    <div>
                        <textarea class="form-control" type="text" id="groupCollect" name="groupCollect" cols="50" rows="5">{{ $groupIds or null }}</textarea>
                        <p style="color: #aa1111;">* 用户id请用英文逗号','相隔</p>
                        <div class='alert alert-danger' id="notice" style="display:none"><li>分群用户id不能为空</li></div>
                        @if(count($errors)>0)
                            <div class="alert alert-danger">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </span>
            </div>

                <div class="auto-mode clearfix">
                    @foreach($res as $re)

                     {!! $obj->render($re->alias) !!}

                    <br>
                    @endforeach
                </div>

                @endif

                <br />
                {!! $obj->footer !!}

            </div>
        </div>
    </div>
    <script>

        $(function(){
            $('.auto-mode').hide();
            if($('input[value=auto]').is(':checked')){
                selectAuto();
            }
            if($('input[value=manual]').is(':checked')){
                selectManual();
                $('form').submit(function(e){
                    if(!$('#groupCollect').val()){
                        e.preventDefault();
                        $('#notice').show();
                        return false;
                    }else{
                        $('#notice').hide();
                    }
                });
            }

            //$('input[value=auto]').attr('checked','checked');
            $('input[value=auto]').change(selectAuto);
            $('input[value=manual]').change(selectManual);



        });

        function selectAuto(){
            $('#div_collect').hide();
            $('.auto-mode').show();
        }
        function selectManual(){
            $('#div_collect').show();
            $('.auto-mode').hide();
        }

    </script>
@endsection

