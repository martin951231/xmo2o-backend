<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>退款订单列表</title>
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
        <div class="content">退款订单列表</div>
    </section>
    <div class="contanir">
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
                    <a href="{pigcms{:U('Village_group/refundDetail', array('refund_id' => $rowset['id']))}">详细状态 ></a>
                </div>
            </div>

            <p class="tui">退款金额:
                <span>{pigcms{$config['Currency_symbol']}{pigcms{$rowset['price']}</span>

            </p>



        </div>
        </volist>
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
