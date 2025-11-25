<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>申请售后</title>
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width">
<meta http-equiv="pragma" content="no-cache">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-touch-fullscreen" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="format-detection" content="telephone=no">
<meta name="format-detection" content="address=no">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}shop/css/schedule.css" />
<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/common.js"></script>
<style type="text/css">
    .no-list{
        text-align: center;
        margin-top: 150px;
        color: #999;
        display: none;
    }
</style>
</head>
<body>
    <div class="contanir" style="margin-top: 0">
        <volist name="refund_list" id="rowset">
        <div class="scheAlls">
            <h5>
                <i></i>{pigcms{$rowset['applytime']|date="Y-m-d H:i:s",###}
            </h5>
            <div class="item">
                <div class="itemLeft">
                    <dl>
                        <dt style='color:#2ecc71'>{pigcms{$rowset['showStatus']}</dt>
                        <dd>退款编号: {pigcms{$rowset['id']}</dd>
                    </dl>
                </div>
                <div class="itemRight">
                    <a href="{pigcms{:U('Shop/refundLog', array('refund_id' => $rowset['id']))}">更多状态 ></a>
                </div>
            </div>
            <p class="teu">退款明细</p>
            <ul class="foods">
                <volist name="rowset['goodsList']" id="goods">
                <li>
                    <div class="liLeft">
                        <img src="{pigcms{$goods['image']}" />
                        <dl>
                            <dt>{pigcms{$goods['name']}</dt>
                            <dd>{pigcms{$config['Currency_symbol']}{pigcms{$goods['price']|floatval}</dd>
                            <dt style="font-size: 12px;color: #666;padding-top: 5px;">{pigcms{$goods['spec']}</dt>
                        </dl>
                    </div>
                    <div class="liRight">×{pigcms{$goods['num']}份</div>
                </li>
                    <if condition="$goods['jd_sku_id'] gt 0">
                    <p class="tui">京东服务单: {pigcms{$goods['jd_afs_id']}</p>
                    <p class="tui">京东服务状态: {pigcms{$goods['approvedResultName']}</p>
                    <p class="tui">是否可以取消退货: {pigcms{$goods['cancel']}</p>
                        <if condition="$goods['canExpress'] eq 1">
                            <p class="tui">填写京东发运信息：<a href="javascript:;" class="refund_express" style="color: #A51F1F;" data-afs_id="{pigcms{$goods['jd_afs_id']}">填写发运信息</a></p>
                        </if>
                    </if>
                </volist>
            </ul>
            <p class="tui">退款金额:
                <span>{pigcms{$config['Currency_symbol']}{pigcms{$rowset['price']}</span>
                <span class="siz1">不含配送费、打包费、优惠金额</span>
            </p>
            <p class="tui">退款说明: {pigcms{$rowset['reason']}</p>
            <php> if (!empty($rowset['image'])) { </php>
            <p class="tui">退款凭证</p>
            <ul class="imgs">
                <php>foreach ($rowset['image'] as $image) {</php>
                <li>
                    <img src="{pigcms{$image}" />
                </li>
                <php>}</php>
            </ul>
            <php> } </php>
            <if condition="$rowset['status'] eq 0">
            <div class="qu active">
                <a href="javascript:;" class="cancel" data-refund_id="{pigcms{$rowset['id']}">取消退款</a>
            </div>
            </if>
        </div>
        </volist>
        <div class="no-list" <if condition="!$refund_list">style="display: block"</if>>暂无退款进度</div>
    </div>
<script type="text/javascript">
$(document).ready(function(){
    $('.cancel').click(function(){
        var refund_id = $(this).data('refund_id');
        layer.open({
            content: '您确定要取消退款吗？'
            ,btn: ['确定', '按错了']
            ,yes: function(index){
                $.post('{pigcms{:U("Shop/cancelRefund")}',{'refund_id':refund_id}, function(response){
                    layer.close(index);
                    if (response.errcode == 1) {
                        layer.open({content:response.msg, skin:'msg', time:2});
                    } else {
                        location.reload();
                    }
                }, 'json');
            }
          });
    });

    $('.refund_express').click(function () {
        var afs_id = $(this).data('afs_id');
        express_layer = layer.open({
            type: 1,
            title: '填写发运信息',
            shadeClose: true,
            shade: 1,
            offset:'100px',
            btn:['提交','取消'],
            style:'width:80%;height:30%;text-align:center;border: 1px solid #eee;color:#333',
            content: '快递公司：<input type="text" name="express_name" style="margin-top:10px;"><br>快递单号：<input type="text" name="express_num" style="margin-top:10px;"><br>快递费用：<input type="text" name="freightMoney" style="margin-top:10px;">',
            success: function(elem){
                $.get('{pigcms{:U("Shop/refundExpress")}',{'afs_id':afs_id},function (e) {
                    if(e.express_name!=""){
                        $('input[name="express_name"]').val(e.express_name);
                        $('input[name="express_num"]').val(e.express_num);
                        $('input[name="freightMoney"]').val(e.freightMoney);
                    }
                },'json')
            },
            yes: function(index){
               var express_name = $('input[name="express_name"]').val();
               var express_num = $('input[name="express_num"]').val();
               var freightMoney = $('input[name="freightMoney"]').val();
               $.post('{pigcms{:U("Shop/refundExpress")}',{'afs_id':afs_id,'express_name':express_name,'express_num':express_num,'freightMoney':freightMoney},function (data) {
                   if(data.errcode==1){
                       layer.open({content:data.msg, skin:'msg', time:3});
                   }else{
                       layer.open({content:data.msg, skin:'msg', time:2});
                       layer.close(index);
                   }
               },'json')
            }
        });
    })
});
</script>
</body>
</html>
