<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
</style>
<div class="page-content" style="background-color: white;">
    <div class="row" style="background-color: white;">
        <div class="col-xs-12">
            <form id="add_info" class="form-horizontal" method="post" action="__SELF__">
                <input type="hidden" name="m_id" value="{pigcms{$info.m_id}">
                <div class="tab-content">
                    <div class="form-group">
                        <label class="col-sm-1">是否是合作店铺:</label>
                        <label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition="$info.type eq 1"> checked="" </if> class="ace" value="1" name="type"><span style="z-index: 1" class="lbl">是</span></label>
                        <label style="padding-left:0px;"><input type="radio" <if condition="$info.type eq 0"> checked="" </if> class="ace" value="0" name="type"><span style="z-index: 1" class="lbl">否</span></label>
                    </div>
                    <div class="form-group coop" <if condition="$info.type eq 0"> style="display: none" </if>>
                        <label class="col-sm-1"><label for="housesize">选择合作店铺：</label></label>
                        <input class="col-sm-2 m_name" readonly size="20" id="m_name" name="m_name" onclick="search()" placeholder="请选择店铺" type="text" value="{pigcms{$info.m_name}"/>
                        <input class="col-sm-2" size="20" id="bind_m_id" name="bind_m_id"  type="hidden" value="{pigcms{$info.bind_m_id}"/>
                    </div>
                <div class="form-group not_coop" <if condition="$info.type eq 1"> style="display: none" </if> >
                        <label class="col-sm-1"><label for="housesize">店铺名称：</label></label>
                        <input class="col-sm-2 m_name" size="20" name="m_names" placeholder="请输入店铺名称" type="text" value="{pigcms{$info.m_name}"/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="housesize">备注：</label></label>
                        <input class="col-sm-2" size="20" name="remark" id="remark" placeholder="请输入备注内容" type="text" value="{pigcms{$info.remark}"/>
                    </div>
                </div>
                <div class="space"></div>
                <div class="clearfix form-actions">
                    <div class="col-md-offset-3 col-md-9">
                        <button class="btn btn-info" type="submit">
                            <i class="ace-icon fa fa-check bigger-110"></i>
                            提交
                        </button>
                    </div>
                </div>
        </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    function search()
    {
        var url = "{pigcms{:U('Park/shop_search')}";
        layer.open({
            type: 2,
            title:'选择店铺',
            content: url,
            area: ['550px', '560px'],
            maxmin: false,
            btn:['确定','取消'],
            shadeClose: false,
            closeBtn: 0,
            yes:function(index,layero){
                var son_msg = $(layero).find("iframe")[0].contentWindow.callBackMsg();
                console.log(son_msg);
                $('#m_name').val(son_msg.name);
                $('#bind_m_id').val(son_msg.id);
                layer.close(index);
            }
        });
    }
    $('input:radio').change(function () {
        var is_coop = $('input:radio:checked').val();
        if(is_coop == 0){
            $('.coop').hide();
            $('.not_coop').show();
        }else{
            $('.coop').show();
            $('.not_coop').hide();
        }
    });
    $('#add_info').submit(function(){
        $.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
            console.log('result', result);
            if (result && result.status==1) {
                layer.open({
                    content: '修改成功!',
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        parent.location.href="{pigcms{:U('Park/set_user_receive_coupon')}&type=basicinfo";
                        parent.location.reload();
                    }
                })
            } else if (result.info) {
                layer.open({
                    content: result.info,
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['知道了']
                })
            } else {
                layer.open({content: '修改失败'});
            }
        });
        return false;
    });

</script>
<include file="Publics:footer"/>

