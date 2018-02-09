@extends('admin.index')
@section('content')

    <div style="padding:2%">
        <div class="rpd-edit">
            <div class="rpd-dataform">

                    <div class="alert alert-success">
                        @if(!empty(session('msg')))
                            {{session('msg')}}
                        @endif
                    </div>

                    <div class="btn-toolbar" role="toolbar">

                        <div class="pull-left">
                            <a href="
                                @if(!empty(session('to')))
                                    {{session('to')}}
                                @endif
                                    " class="btn btn-default">返回列表</a>
                        </div>


                    </div>
                    <br>
            </div>

        </div>
    </div>

@endsection