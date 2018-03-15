@extends('style')
<br>
<div style="padding:2%;background-color: #ffffff;margin: 2px 20px 0 20px;border-radius: 5px;">
    {{--<div id="container">--}}
        {{--<button onclick="set_upload_param(uploader, 'test', false, 'HEADLINE/4');" class="btn btn-primary">上传图片</button>--}}
    {{--</div>--}}
    <div>
        <form name=theform>
            <input type="radio" name="myradio" value="local_name" checked=true/> 上传文件名字保持本地文件名字
            <input type="radio" name="myradio" value="random_name"/> 上传文件名字是随机文件名, 后缀保留
        </form>

        <h4>您所选择的文件列表：</h4>
        <div id="ossfile">你的浏览器不支持flash,Silverlight或者HTML5！</div>

        <br/>

        <div id="container">
            <a id="selectfiles" href="javascript:void(0);" class='btn btn-primary'>选择文件</a>
            <button id="postfiles" onclick="set_upload_param(uploader, '', false, '{!! $dir !!}')"
                    class='btn btn-primary'>开始上传
            </button>
        </div>

        <pre id="console"></pre>

        <p>&nbsp;</p>
    </div>

    <table class="table table-striped">
        <thead>
        <tr>
            <td class="tiny">预览</td>
            <td class="small">大小</td>
            <td class="small">操作</td>
        </tr>
        </thead>
        <tbody>

        @foreach($list as $item)
            <tr>
                <td>
                    <img style="height: 70px; width: auto" src="{!! $item['url'] !!}">
                </td>
                <td class="small">{!! $item['size'] !!}</td>
                <td class="small">
                    <button onclick="selectPhoto('{!! $item['key'] !!}', 'photo-{!! $item['auto_id'] !!}',{{ $single }})" class="btn btn-primary">选取</button>
                </td>
            </tr>

        @endforeach
        </tbody>
    </table>
</div>

<script>

/*配置是否为单文件选择，勿删*/
single = {{ $single }};

function selectPhoto(url, photoId,single){
        single = single || false;

        var index = parent.layer.getFrameIndex(window.name); //获取窗口索引

//        预览
        var img = '<img ' +
            'id="' + photoId + '" ' +
            'onclick="delPhoto(\'' + url + '\', \'' + photoId + '\')" ' +
            'style="border: 1px solid #3c8dbc; border-radius: 5px;padding: 2px; height: 50px;width: auto;margin-right: 3px; max-width: 300px" ' +
            'src="' + 'http://'+'{!! $ossViewDomain !!}/' + url + '">';

        var insert = true; //是否可插入图片和图片路径
        var imgs = parent.$('#photo-preview img');
        if(imgs){
            $.each(imgs,function(i,image){
                if($(image).attr('id') == photoId){
                    insert = false;
                }
            });
        }

        if(insert){
            if(single){
                parent.$('#photo-preview').html(img);
                parent.$('#count-photo').text(1);
                parent.$('#thumb').val(url);
            }else{
                parent.$('#photo-preview').append(img);
                //        计算选的封面图数量
                parent.$('#count-photo').text(parseInt(parent.$('#count-photo').text()) + 1);
                //        入库数据
                var thumb = parent.$('#thumb').val();
                if (thumb == '') parent.$('#thumb').val(url);
                else parent.$('#thumb').val(thumb + ',' + url);
            }

        }

    }
</script>
<script type="text/javascript" src="{{ asset ("/js/oss/lib/plupload-2.1.2/js/plupload.full.min.js") }}"></script>
<script type="text/javascript" src="{{ asset ("/js/oss/upload.js") }}"></script>
