@extends('admin.index')
@section('content')
    {!! Rapyd::head() !!}
    <div style="padding:2%">
        <div class="rpd-edit">
            {!! $edit !!}
        </div>
    </div>

    <script>
        var btnSync = genSyncButton();
        $('#div_description').append(btnSync);
        
        function genSyncButton() {
            return '<button onclick="syncHeadlines()" class="btn btn-primary pull-right">上传文章内容</button>';
        }
        
        function syncHeadlines() {
            alert(1);
//            var form = '<form action="'+action + '/headlines/syncHeadlines" method="post" id="description_form">\
//                    <input type="hidden" name="description" value="' + description + '" />\
//                </form>';
//            $('body').append(form);
//            $('#description_form').submit();
        }
    </script>
@endsection
