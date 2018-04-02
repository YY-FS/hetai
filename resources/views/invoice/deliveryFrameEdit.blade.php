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
                @if(isset($tips))
        var h2 = $('.pull-left').find('h2');
        var tips = '<p style="color: #808080;font-size: 14px">{!! $tips !!}</' + 'p>';
        h2.after(tips);
        @endif
        //拼凑收货人界面
                @if(isset($edit->model))
                @php
                    $address =  \App\Models\Platv4UserAddress::getInvoiceAddress($edit->model->user_address_id);
                @endphp
                @foreach($address as $ad)
        var delivery = '<div class="form-group clearfix"><label class="col-sm-2 control-label required">{!! $ad['title'] !!}</'
            + 'label><div class="col-sm-10"><div class="delivery-text">{!! $ad['value'] !!}</' + 'div></' + 'div></' + 'div>';
        $('#fg_express').before(delivery);
        @endforeach
        @endif
    </script>
@endsection