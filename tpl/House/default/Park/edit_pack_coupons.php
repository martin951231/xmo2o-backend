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
                <div class="tab-content">
                    <div class="form-group">
                        <label class="col-sm-1"><label for="housesize"><span style="color: red;">*</span> 优惠券名称：</label></label>
                        <input class="col-sm-2 c_title" size="20" name="c_title" placeholder="请输入优惠券名称" type="text" value="{pigcms{$info.c_title}"/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="housesize"><span style="color: red;">*</span> 单价：</label></label>
                        <input class="col-sm-2 c_price" size="20" name="c_price" placeholder="请输入单价" type="text" value="{pigcms{$info.c_price}"/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="housesize"><span style="color: red;">*</span> 每一次停车免费金额：</label></label>
                        <input class="col-sm-2 c_free_price" size="20" name="c_free_price" placeholder="请输入每一次停车免费金额" type="text" value="{pigcms{$info.c_free_price}"/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="housesize">备注：</label></label>
                        <input class="col-sm-2" size="20" name="remark" id="remark" placeholder="请输入备注内容" type="text" value="{pigcms{$info.remark}"/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1">状态:</label>
                        <label style="padding-left:0px;padding-right:20px;"><input type="radio"  class="ace" value="0" name="status" <if condition="$info.status eq 0"> checked="" </if> > <span style="z-index: 1" class="lbl">正常</span></label>
                        <label style="padding-left:0px;"><input type="radio" class="ace" value="1" name="status" <if condition="$info.status eq 1"> checked="" </if>><span style="z-index: 1" class="lbl">禁止</span></label>
                    </div>
                    <input type="hidden" name="c_id" value="{pigcms{$info.c_id}">
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
            area: ['450px', '260px'],
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
    $('#add_info').submit(function(){
        var name = $('.c_title').val();
        var c_price = $('.c_price').val();
        var c_free_price = $('.c_free_price').val();
        if(!name){
            layer.alert('请输入优惠券名称');
            return false;
        }
        if(!c_price) {
            layer.alert('请输入单价');
            return false;
        }else{
            if(c_price<=0){
                layer.alert('单价必须大于0');
                return false;
            }
        }
        if(!c_free_price) {
            layer.alert('请输入每一次停车免费金额');
            return false;
        }else{
            if(c_free_price<=0){
                layer.alert('单价必须大于0');
                return false;
            }
        }
        $.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
            console.log('result', result);
            if (result && result.status==1) {
                layer.open({
                    content: '修改成功!',
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        parent.location.href="{pigcms{:U('Park/edit_pack_coupons')}&type=basicinfo";
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
                layer.open({content: '修复失败'});
            }
        });
        return false;
    });

</script>
<include file="Publics:footer"/>


