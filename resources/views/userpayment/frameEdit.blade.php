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

        .delivery-text {
            display: block;
            width: 100%;
            height: 34px;
            padding: 7px 12px;
            font-size: 14px;
            line-height: 1.42857143;
            color: #555;
        }
    </style>
    <div style="padding:2%">
        <div class="rpd-edit">
            {!! $edit !!}
        </div>
    </div>
    <script type="application/javascript">
        $('.btn-default').remove();
        $('.btn-primary').remove();
        //拼凑收货人界面
        @if(isset($edit->model))
            @php
                $paymentDetail =  \App\Models\Platv4OrderTotal::paymentDetail($edit->model->id);
            @endphp
            var br = $('.btn-toolbar').first().next();
            @foreach($paymentDetail as $pd)
            var paymentDetail = '<div class="form-group clearfix"><label class="col-sm-2 control-label required" style="width:200px;">{!! $pd->title !!}</'
                        + 'label><div class="col-sm-10" style="display:inline-block;"><div class="delivery-text">{!! $pd->value !!}</' + 'div></' + 'div></' + 'div>';
            br.after(paymentDetail);
            @endforeach
        @endif
    </script>
@endsection