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
                    <input type="hidden" id="mid" name="mid" value="{pigcms{$mid}">
                    <div class="form-group">
                        <label class="col-sm-1"><label for="housesize"><span style="color: red;">*</span> 优惠券：</label></label>
                        <select name="cid" id="cid" style="width: 180px;" onchange="search(this.value)">
                            <option value="">请选择优惠券</option>
                            <volist name="coupons_list" id="vo">
                                <option value="{pigcms{$vo.c_id}">{pigcms{$vo.c_title}</option>
                            </volist>
                        </select>
                        <input type="hidden" id="c_price" value="">
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="housesize">每次停车免费金额：</label></label>
                        <input class="col-sm-2 free_money" size="20" readonly name="free_money" type="text" value=""/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="housesize"><span style="color: red;">*</span>添加数量：</label></label>
                        <input class="col-sm-2 num" size="20" name="num" onchange="numMoney(this.value)" placeholder="请输入添加数量" type="number" min="1" value=""/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="housesize">应收金额：</label></label>
                        <input class="col-sm-2 receivable_money" size="20" name="receivable_money" readonly type="text" value=""/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="housesize"><span style="color: red;">*</span> 实收金额：</label></label>
                        <input class="col-sm-2 paid_money" size="20" name="paid_money" placeholder="请输入实收金额" type="number" min="0" value=""/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1">二维码状态:</label>
                        <label style="padding-left:0px;"><input type="radio" class="ace" value="1" name="status"><span style="z-index: 1" class="lbl">静态码</span></label>&nbsp;&nbsp;
                        <label style="padding-left:0px;padding-right:20px;"><input type="radio" checked="" class="ace" value="2" name="status"><span style="z-index: 1" class="lbl">动态码</span></label>&nbsp;&nbsp;
                        <span>静态码可下载打印，固定给用户扫码领取；动态码每次领取后会实时刷新二维码，防止重复领取</span>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="housesize">备注：</label></label>
                        <input class="col-sm-2" size="20" name="remark" id="remark" placeholder="请输入备注内容" type="text" value=""/>
                    </div>

                </div>
                <div class="space"></div>
                <div class="clearfix form-actions">
                    <div class="col-md-offset-3 col-md-9">
                        <button class="btn btn-info" type="submit">
                            <i class="ace-icon fa fa-check bigger-110"></i>
                            添加
                        </button>
                    </div>
                </div>
        </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    function search(cid)
    {
        var params = {
            'cid':cid,
        };
        $.post("{pigcms{:U('Park/getCoupons')}", params, function (data) {
            console.log(data)
            if (data.status == 1) {
                $('.free_money').val(data.coupons.c_free_price);
                $('#c_price').val(data.coupons.c_price);
            } else {
                layer.alert(data.info);
            }
        });
    }
    function numMoney(num)
    {
        var c_price = $('#c_price').val();
        if(c_price){
            var money = c_price*num;
            $('.receivable_money').val(money);
        }else{
            $('.num').val('');
            layer.alert('请先选择优惠券');
        }
    }
    $('#add_info').submit(function(){
        var mid = $('#mid').val();
        var cid = $('#cid').val();
        var free_money = $('.free_money').val();
        var num = $('.num').val();
        var receivable_money = $('.receivable_money').val();
        var paid_money = $('.paid_money').val();
        var status = $('input:radio:checked').val();
        if(!mid){
            layer.alert('参数异常');
            return false;
        }
        if(!cid){
            layer.alert('请选择优惠券');
            return false;
        }
        if(!free_money) {
            layer.alert('每次停车免费金额异常');
            return false;
        }
        if(!num) {
            layer.alert('请输入添加数量');
            return false;
        }
        if(!receivable_money) {
            layer.alert('应收金额异常');
            return false;
        }
        if(!paid_money) {
            layer.alert('请输入实付金额');
            return false;
        }
        $.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
            console.log('result', result);
            if (result && result.status==0) {
                layer.open({
                    content: '添加成功!',
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
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
                layer.open({content: '添加失败'});
            }
        });
        return false;
    });

</script>
<include file="Publics:footer"/>



