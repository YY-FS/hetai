@extends('admin.index')
@section('content')
    {!! Rapyd::head() !!}
    <br>
    <div style="padding:2%;background-color: #ffffff;margin: 2px 20px 0 20px;border-radius: 5px;">
        <h4 class="pull-left" style="border-left: 3px solid #2cc6ba;padding-left: 7px">{!! $title !!}</h4>
        <br>
        <br>
        <br>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                <tr>
                    <th>media_id</th>
                    <th>图片名称</th>
                    <th>图片链接</th>
                </tr>
                </thead>
                <tbody id="image">
                </tbody>
            </table>
        </div>
        <div class="btn-toolbar">
            <div class="pull-left">
                {{ $paginator->render() }}
            </div>
            <div class="pull-right rpd-total-rows">
                {!! $image['total_count'] !!}
            </div>
        </div>
    </div>
    <script>
        var tbody = $('#image');
        @if($image['item_count']>0)
        @foreach($image['item'] as $img)
        var tr = "<tr><td>{!! $img['media_id'] !!}</" + "td><td>{!! $img['name'] !!}</"
                + "td><td><a class=\"btn btn-primary\" href=\"{!! $img['url'] !!}\" target=\"_blank\">图片预览</"
                + "a></" + "td></" + "tr>"
        tbody.append(tr);
        @endforeach
        @endif
    </script>
@endsection