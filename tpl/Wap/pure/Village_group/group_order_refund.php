<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
	<title>取消订单</title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name='apple-touch-fullscreen' content='yes'>
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
    <link href="{pigcms{$static_path}css/wap_pay_check.css" rel="stylesheet"/>
	<style>
    .btn-wrapper {
        /*margin: .28rem .2rem;*/
        bottom: 40%;
        background-color: #f0efed;
    }
    .hotel-price {
        color: #ff8c00;
        font-size: 12px;
        display: block;
    }
    .dealcard .line-right {
        display: none;
    }
    .agreement li {
        display: inline-block;
        width: 50%;
        box-sizing: border-box;
        color: #666;
    }

    .agreement li:nth-child(2n) {
        padding-left: .14rem;
    }

    .agreement li:nth-child(1n) {
        padding-right: .14rem;
    }

    .agreement ul.agree li {
        height: .32rem;
        line-height: .32rem;
    }

    .agreement ul.btn-line li {
        vertical-align: middle;
        margin-top: .06rem;
        margin-bottom: 0;
    }

    .agreement .text-icon {
        margin-right: .14rem;
        vertical-align: top;
        height: 100%;
    }

    .agreement .agree .text-icon {
        font-size: .4rem;
        margin-right: .2rem;
    }


    #deal-details .detail-title {
        background-color: #F8F9FA;
        padding: .2rem;
        font-size: .3rem;
        color: #000;
        border-bottom: 1px solid #ccc;
    }

    #deal-details .detail-title p {
        text-align: center;
    }

    #deal-details .detail-group {
        font-size: .3rem;
        display: -webkit-box;
        display: -ms-flexbox;
    }

    .detail-group .left {
        -webkit-box-flex: 1;
        -ms-flex: 1;
        display: block;
        padding: .28rem 0;
        padding-right: .2rem;
    }

    .detail-group .right {
        display: -webkit-box;
        display: -ms-flexbox;
        -webkit-box-align: center;
        -ms-box-align: center;
        width: 1.2rem;
        padding: .28rem .2rem;
        border-left: 1px solid #ccc;
    }

    .detail-group .middle {
        display: -webkit-box;
        display: -ms-flexbox;
        -webkit-box-align: center;
        -ms-box-align: center;
        width: 1.7rem;
        padding: .28rem .2rem;
        border-left: 1px solid #ccc;
    }

    ul.ul {
        list-style-type: initial;
        padding-left: .4rem;
        margin: .2rem 0;
    }

    ul.ul li {
        font-size: .3rem;
        margin: .1rem 0;
        line-height: 1.5;
    }
    .coupons small{
        float: right;
        font-size: .28rem;
    }
    strong {
        color: #FDB338;
    }

</style>
</head>
<body>
        <div id="tips" class="tips"></div>
        <div class="wrapper-list">
			<h4>取消订单明细</h4>
			<dl class="list">
			    <dd>
			        <dl>
						<dd class="kv-line-r dd-padding">
							<h6>购买数量：</h6>
                            <p>
                                {pigcms{$goodsCount|0}

                            </p>
						</dd>

			            <dd class="kv-line-r dd-padding">
			                <h6>商品总金额(会员等级优惠后)：</h6><p><strong class="highlight-price">{pigcms{$goodsMonery} {pigcms{$config['Currency_txt']}</strong></p>
			            </dd>
						
			        </dl>
			    </dd>
			</dl>
			<dl class="list">
			    <dd>
			        <dl>
<!--                            <dd class="kv-line-r dd-padding">-->
<!--                                <h6>实付金额：</h6><p>{pigcms{$villageGroupOrder['discount_price']|floatval}{pigcms{$config['Currency_txt']}</p>-->
<!--                            </dd>-->
							<dd class="kv-line-r dd-padding">
								<h6>优惠：</h6><p style="color：red;">-{pigcms{$villageGroupOrder['Discount']|floatval}{pigcms{$config['Currency_txt']}</p>
							</dd>

			        </dl>
			    </dd>
			</dl>

				<dl class="list">
					<dd>
						<dl>
							<dd class="kv-line-r dd-padding">
								<h6>总应退金额：</h6><p><strong class="highlight-price">{pigcms{$plat_money}{pigcms{$config['Currency_txt']}</strong></p>
							</dd>
						</dl>
					</dd>
				</dl>

			<div class="btn-wrapper" style="line-height:1.5;color:#666;position: static;border-top: none;">{pigcms{$village_group_unshipped_cancel_order_desc}</div>
			<div class="btn-wrapper">
				<span id="cancel"  class="btn btn-larger btn-block btn-strong" style="margin-bottom:15px;">确定取消</span>
			</div>
		</div>
    	<script src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script src="{pigcms{$static_path}js/common_wap.js"></script>	
        <script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script>
			$(function(){
//				$('#cancel_order').click(function(){
//					if(confirm('您确定取消订单吗？取消后不能恢复！')){
//						window.location.href = "{pigcms{:U('My/group_order_del',array('order_id'=>$now_order['order_id']))}";
//					}
//				});
				var cancel_ = true;
				$('#cancel').click(function(){
                    layer.open({
                        title:['提示','background-color:#fff;color:#000;'],
                        content:'<p style="width:180px;color:red;">您确定取消订单吗？取消后不能恢复！</p>',
                        btn: ['确定','取消'],
                        yes: function(index){
                            if(cancel_){
                                $(this).css('background-color','#ccc');
                                window.location.href='{pigcms{:U('My/village_group_order_check_refund',array('order_id'=>$villageGroupOrder['order_id']))}';
                                cancel_ = false;
                            }
                            layer.close(index);
                        }
                    });
				});
			});
		</script>
{pigcms{$hideScript}
</body>
</html>