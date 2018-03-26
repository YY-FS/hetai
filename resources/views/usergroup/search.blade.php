@extends('style')
{!! Rapyd::head() !!}
<style>
    #fg_tags label{
        margin-left: 30px;
        width: 120px;
    }
    input[type=checkbox] {
        margin-right: 5px;
    }
</style>
<script src="{{ asset ("/packages/admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js") }}"></script>
<div style="padding:2%">
    <div class="rpd-edit">
        <div class="rpd-dataform">
            <div class="form-group clearfix" id="fg_alias">
                <label for="uid" class="col-sm-2 control-label">用户uid</label>
                <div class="col-sm-10" id="div_uid">
                    <input class="form-control" placeholder="请输入用户uid" type="text" id="uid" name="uid">
                </div>
            </div>
            <div class="btn-toolbar" role="toolbar">
                <div class="pull-center">
                    <button class="btn btn-primary" id="btn_search">搜索</button>
                </div>
            </div>
            <div>
                <p id="result" style="color:red;"></p>
            </div>
        </div>
    </div>
</div>

<script>
    $('#btn_search').click(function(){
        var uid = $('#uid').val().trim();
        if(!uid) {
            $('#result').text('请输入用户id');
            return false;
        }
        $.ajax({
            url:'/api/user/groups/member?user_group_id={!! $userGroupId !!}&uid='+uid,
            dataType:'json',
            type:'get',
            success:function(data){
                if(data.res == 0){
                    $('#result').text('用户 '+data.uid+' 不在该分组');
                }else{
                    $('#result').css('color','green');
                    $('#result').text('用户 '+data.uid+' 为该分组成员');
                }
            }
        });
    });

</script>
