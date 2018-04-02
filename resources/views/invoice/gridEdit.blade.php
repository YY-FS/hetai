@extends('style')
@section('content')
    {!! Rapyd::head() !!}
    <br>
    <div style="padding:2%;background-color: #ffffff;margin: 2px 20px 0 20px;border-radius: 5px;">
        @if(!empty($title))
            <h4 class="pull-left" style="border-left: 3px solid #2cc6ba;padding-left: 7px">{!! $title !!}</h4>
        @else
            <h4 class="pull-left">列表</h4>
        @endif

        @if(isset($tips))
            <br>
            <br>
            <p style="color: #cecece;font-size: 12px">{!! $tips !!}</p>
        @endif
        <br>
        <br>
        <br>

        <div class="table-responsive">
            <p>申请时间：{{ $edit->model->created_at }}&nbsp;&nbsp;&nbsp;&nbsp;申请单号：{{ $edit->model->id }}</p>
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                    <th>日期</th><th>账单ID</th><th>购买项</th><th>金额</th><th>支付方式</th>
                </tr>
                </thead>
                <tbody>
                @foreach($orders as $o)
                    <tr>
                        <td>{{ $o->pay_date }}</td>
                        <td>{{ $o->order_id }}</td>
                        <td>{{ $o->purchase }}</td>
                        <td>￥{{ $o->price/100 }}</td>
                        <td>{{ $o->pay_way }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p>共 {{ $count }} 项商品,金额共计 <span style="color:red;">￥{{$edit->model->total}}</span></p>
            {{ $orders->appends(['modify'=>$edit->model->id])->links() }}
        </div>

        <div class="rpd-edit" style="padding: 1% 5% !important;">
            <h3>发票信息</h3>
            @foreach($invoiceInfo as $k=>$v)
            <div class="form-group clearfix" id="fg_{{ $k }}" >
                <label for="{{ $k }}" class="col-sm-2 control-label" style="text-align:right;">{{ $v['label'] }}</label>
                <div class="col-sm-10" id="div_{{ $k }}">
                    <div class="help-block">{{ $v['content'] }}&nbsp;</div>
                </div>
            </div>
            @endforeach
            @if(!empty($postAddr['receive_phone']['content']))
                <h3>邮寄地址</h3>
                @foreach($postAddr as $key=>$val)
                    <div class="form-group clearfix" id="fg_{{ $key }}">
                        <label for="{{ $key }}" class="col-sm-2 control-label" style="text-align:right;">{{ $val['label'] }}</label>
                        <div class="col-sm-10" id="div_{{ $key }}">
                            <div class="help-block">{{ $val['content'] }}&nbsp;</div>
                        </div>
                    </div>
                @endforeach
            @endif
            {!! $edit !!}
            <p id='warning' style='color: #aa1111'></p>
        </div>
    </div>

<script>
    if($('input[value="-1"]').is(':checked')){
        refuse();
    }else if($('input[value="1"]').is(':checked')){
        pass();
    }
    function pass(){
        $('#fg_invoice_no').show();
        $('#fg_reason').hide();
    }
    function refuse(){
        $('#fg_invoice_no').hide();
        $('#fg_reason').show();
    }
    $('input[value="-1"]').change(refuse);
    $('input[value="1"]').change(pass);

    $('input[type="submit"]').click(function(event){
        event.preventDefault();
        if($('input[value="1"]').is(':checked') && !$('#invoice_no').val()){
            $('#warning').text('发票编码必须填写');
        }else if($('input[value="-1"]').is(':checked') && !$('#reason').val()){
            $('#warning').text('拒绝原因必须填写');
        }else if($('input[value="1"]').is(':checked')){
            $('#reason').val('');
            $('form').submit();
        }else if($('input[value="-1"]').is(':checked')){
            $('#invoice_no').val('');
            $('form').submit();
        }
    });
</script>
@endsection