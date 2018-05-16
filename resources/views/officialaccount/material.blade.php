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
                {{--标题 图文链接 原文地址--}}
                <tr>
                    <th>media_id</th>
                    <th>标题</th>
                    <th>图文链接</th>
                    <th>原文地址</th>
                </tr>
                </thead>
                <tbody id="material">
                </tbody>
            </table>
        </div>
        <div class="btn-toolbar">
        <div class="pull-left">
            {{ $paginator->render() }}
        </div>
         <div class="pull-right rpd-total-rows">
                {!! $material['total_count'] !!}
         </div>
        </div>
    </div>
    <script>
        var tbody = $('#material');
        @if($material['item_count']>0)
            @foreach($material['item'] as $mval)
            var title='';
            var url='';
            var content_source_url='';
            var tr = "<tr><td>{!! $mval['media_id'] !!}</" + "td>";
                @foreach($mval['content']['news_item'] as $ckey=>$cval)
                    title += "({!! $ckey !!}) {!! $cval['title'] !!}<br>";
                    url += '({!! $ckey !!}) <a class="btn btn-primary" href="{!! $cval['url'] !!}" target="_blank">图文预览</'+'a><br>';
                    @if($cval['content_source_url']!='')
                        content_source_url += '({!! $ckey !!}) <a class="btn btn-success" href="+surl+" target="_blank">阅读原文</'+'a><br>'
                    @else
                        content_source_url +='({!! $ckey !!}) <br>'
                    @endif
                @endforeach
                tr += "<td>"+title+"</" + "td><td>"+url+"</"+"td><td>"+content_source_url
                    +"</" + "td></" + "tr>";
                tbody.append(tr);
            @endforeach
        @endif
    </script>
@endsection