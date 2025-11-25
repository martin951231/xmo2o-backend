<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>退款订单详情</title>
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
</head>
<body>
    <section class="public">
        <a class="return link-url" id="goBackUrl" href="javascript:window.history.go(-1);"></a>
        <div class="content">退款订单详情</div>
    </section>
    <div class="contanir">

        <div class="scheAlls">
            <h5>
                <i></i>{pigcms{$refund_list['applytime']|date="Y-m-d H:i:s",###}
            </h5>
            <div class="item">
                <div class="itemLeft">
                    <dl>
                        <dt style='color:#2ecc71'>{pigcms{$refund_list['showStatus']}</dt>
                        <dd>退款编号: {pigcms{$refund_list['id']}</dd>
                    </dl>
                </div>
                <div class="itemRight">

                </div>
            </div>
            <p class="teu">退款明细</p>
            <ul class="foods">
                <volist name="refund_list['goodsList']" id="goods">
                    <li>
                        <div class="liLeft">
                            <img src="{pigcms{$goods['image']}" />
                            <dl>
                                <dt>{pigcms{$goods['name']}</dt>
                                <dt style="font-size: 12px;color: #666;padding-top: 5px;">{pigcms{$goods['spec']}</dt>
                            </dl>
                        </div>
                        <div class="liRight">×{pigcms{$goods['num']}份</div>
                    </li>

                </volist>
            </ul>
            <p class="tui">退款金额:
                <span>{pigcms{$config['Currency_symbol']}{pigcms{$refund_list['price']}</span>
<!--                <span class="siz1">不含配送费、打包费、优惠金额</span>-->
            </p>
            <p class="tui">退款说明: {pigcms{$refund_list['reason']}</p>
            <php> if (!empty($refund_list['image'])) { </php>
            <p class="tui">退款凭证</p>
            <ul class="imgs">
                <php>foreach ($refund_list['image'] as $image) {</php>
                <li>
                    <img src="{pigcms{$image}" />
                </li>
                <php>}</php>
            </ul>
            <php> } </php>
<!--            <if condition="$rowset['status'] eq 0">-->
<!--            <div class="qu active">-->
<!--                <a href="javascript:;" class="cancel" data-refund_id="{pigcms{$rowset['id']}">取消退款</a>-->
<!--            </div>-->
<!--            </if>-->
        </div>

    </div>
<script type="text/javascript">
$(document).ready(function(){
    $('.cancel').click(function(){
        var refund_id = $(this).data('refund_id');
        layer.open({
            content: '您确定要取消退款吗？'
            ,btn: ['确定', '按错了']
            ,yes: function(index){
                $.post('{pigcms{:U("Village_group/cancelRefund")}',{'refund_id':refund_id}, function(response){
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


});
</script>
</body>
</html>
