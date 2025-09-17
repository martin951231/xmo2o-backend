<include file="Public/header"/>
    <section class="search">
        <div class="w1200 clr">
            <div class="fl">
                <div class="logo fl">
                    <a href="{pigcms{:U('Index/index')}"><img src="{pigcms{$config['site_logo']}" width=160 height=43></a>
                </div>
            </div>
            <div class="fr">
                <ul class="arrow clr">
                    <li class="clr on">
                        <i>1</i>
                        <span>在线充值</span>
                    </li>
                    <li class="clr ou">
                        <i>2</i>
                        <span>在线支付</span>
                    </li>
                    <li class="clr">
                        <i>3</i>
                        <span>支付成功</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <section class="order">
        <div class="w1200">
            <div class="infor">
                <h2 class="title back_h2 clr">
                    <span class="back_span fl">在线充值</span>
                    <div class="infor_moy fr">
                        <span class="stay">待支付</span>
                        <span class="price">￥<em>{pigcms{$now_order.order_price}</em></span>
                    </div>
                </h2>
            </div>
		<form id="myform" action="{pigcms{:C('config.site_url')}/index.php?g=Index&c=Pay&a=go_pay" method="post" >
            <div class="bank clr">
                <div class="clr bank_top">
                    <h2 class="fl">支付平台</h2>
                </div>
                <div class="method treasure">
                    <dl class="clr">
						<volist name="pay_method" id="vo">
                        <dd data-name="{pigcms{$key}">
							<input type="radio" name="pay_type" value="{pigcms{$key}" style="display:none">
                            <img src="{pigcms{$static_public}images/pay/{pigcms{$key}.gif" width="112" height="32" alt="{pigcms{$vv.name}" title="{pigcms{$vv.name}"/>
                        </dd>
						</volist>
                       
                    </dl>
                </div>
            </div>

            <div class="stays clr">
				
					<input type="hidden" name="order_id" value="{pigcms{$now_order.order_id}">
					<input type="hidden" name="order_type" value="{pigcms{$now_order.order_type}">
					
					<div class="fl">
						<span class="stay">待支付</span>
						<span class="price">￥<em>{pigcms{$now_order.order_price}</em></span>
					</div>
					<a href="javascript:void(0)" class="fr" id="go_pay">确认支付</a>
            </div>
        </div>
		</form>
    </section>

<script type="text/javascript">
	//支付
	$(".method dd").click(function(){
		$(this).addClass("on").siblings("dd").removeClass("on");
		$(this).parents(".bank").css("border","#fe6903 1px solid");
		$(this).find('input[name="pay_type"]').attr('checked','checked');
	})
	
	$('#go_pay').click(function(){
		var pay_type= $('input[name="pay_type"]:checked').val();
		if(pay_type=='weixin'){
			art.dialog({
				title: '提示信息',
				id: 'weixin_pay_tip',
				opacity:'0.4',
				lock:true,
				fixed: true,
				resize: false,
				content: '正在获取微信支付相关信息，请稍等...'
			});
			$.post($('#myform').attr('action'),$('#myform').serialize(),function(result){
				art.dialog.list['weixin_pay_tip'].close();			
				if(result.status == 1){
					orderid = result.orderid;
					art.dialog({
						title: '请使用微信扫码支付',
						id: 'weixin_pay_qrcode',
						width:'350px',
						opacity:'0.4',
						lock:true,
						fixed: true,
						resize: false,
						content: '<p style="margin-top:20px;margin-bottom:20px;text-align:center;font-size:16px;color:black;">请使用微信扫描二维码进行支付</p><p style="text-align:center;"><img src="/index.php?c=Recognition&a=get_own_qrcode&qrCon='+result.info+'" style="width:240px;height:240px;"></p><p style="text-align:center;margin-top:20px;margin-bottom:20px;"><input id="J-order-weixin-button" type="button" class="btn btn-large btn-pay" value="已支付完成"/></p>',
						cancel: function(){
							$("#J-order-pay-button").val("去付款");
							$("#J-order-pay-button").removeAttr("disabled");
						},
					});
				}else{
					
					art.dialog({
						title: '错误提示：',
						id: 'weixin_pay_error',
						opacity:'0.4',
						lock:true,
						fixed: true,
						resize: false,
						content: result.info
					});
					
				}
			});
		}else{			
			$('#myform').submit();
		}
	});
</script>
<include file="Public/footer"/>








