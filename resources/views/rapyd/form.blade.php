@extends('admin.index')
@section('content')
    {!! Rapyd::head() !!}
    <div style="padding:2%">
        <div class="rpd-edit">
            {!! $form !!}
        </div>
    </div>
@endsection
