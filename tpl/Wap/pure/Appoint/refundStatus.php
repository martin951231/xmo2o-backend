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
        <div class="scheAlls">

            <h5>
                <i></i>{pigcms{$refund['applytime']|date="Y-m-d H:i:s",###}
            </h5>
            <div class="item">
                <div class="itemLeft">
                    <dl>
                        <dt style='color:#2ecc71'>{pigcms{$refund['showStatus']}</dt>
                        <dd>退款编号: {pigcms{$refund['id']}</dd>
                    </dl>
                </div>

            </div>
            <p class="teu">退款明细</p>
            <ul class="foods">

                <li>
                    <div class="liLeft">
                        <img src="{pigcms{$appoint['pic']}" />
                        <dl>
                            <dt>{pigcms{$appoint['appoint_name']}</dt>

                        </dl>
                    </div>

                </li>


            </ul>
            <p class="tui">退款金额:
                <span>{pigcms{$config['Currency_symbol']}{pigcms{$refund['price']}</span>

            </p>
            <p class="tui">退款说明: {pigcms{$refund['reason']}</p>
            <php> if (!empty($imageList)) { </php>
            <p class="tui">退款凭证</p>
            <ul class="imgs">
                <php>foreach ($imageList as $image) {</php>
                <li>
                    <img src="{pigcms{$image}" />
                </li>
                <php>}</php>
            </ul>
            <php> } </php>
            <if condition="$refund['status'] eq 2"> 
               <p class="tui">拒绝理由: {pigcms{$refund['reply_content']}</p>
            </if>
<!--            <if condition="$rowset['status'] eq 0">-->
<!--            <div class="qu active">-->
<!--                <a href="javascript:;" class="cancel" data-refund_id="{pigcms{$rowset['id']}">取消退款</a>-->
<!--            </div>-->
<!--            </if>-->
        </div>

        <div class="no-list" <if condition="!$refund">style="display: block"</if>>暂无退款进度</div>
    </div>
<script type="text/javascript">
$(document).ready(function(){

//    if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase()))){
//        if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
//            window.webkit.messageHandlers.PackAppJSBridgeMethod.postMessage({name:'hideWebViewHeader', show_head: '0'});
//        }else{
//            window.lifepasslogin.hideWebViewHeader(false)
//        }
//    }

    $('.cancel').click(function(){
        var refund_id = $(this).data('refund_id');
        layer.open({
            content: '您确定要取消退款吗？'
            ,btn: ['确定', '按错了']
            ,yes: function(index){
                $.post('{pigcms{:U("Appoint/cancelRefund")}',{'refund_id':refund_id}, function(response){
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
