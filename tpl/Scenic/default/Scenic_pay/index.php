<include file="Public/header"/>
	<!--导航-->
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
						<span>选择景区</span>
					</li>
					<li class="clr on">
						<i>2</i>
						<span>填写订单</span>
					</li>
					<li class="clr ou">
						<i>3</i>
						<span>在线支付</span>
					</li>
					<li class="clr">
						<i>4</i>
						<span>支付成功</span>
					</li>
				</ul>
			</div>
		</div>
	</section>
	<!--订单信息-->
	<section class="order">
		<div class="w1200">
		<form action="{pigcms{:U('Scenic_pay/go_pay')}" method="post" id="deal-buy-form" class="common-form J-wwwtracker-form">
			<div class="infor">
				<input type="hidden" name="order_id" value="{pigcms{$scenic_order['order_id']}">
				<h2 class="title">订单信息</h2>
				<div class="infor_top">
					<div class="clr infor_tit">
						<h1 class="fl">{pigcms{$scenic_ticket['ticket_title']}</h1>
						<div class="infor_moy fr">
							<span class="stay">待支付</span>
							<span class="price">￥<em>{pigcms{$scenic_order['order_total']}</em></span>
						</div>
					</div>
					<ul class="infor_ul">
						<li class="clr">
							<span class="fl">景区地址：</span>
							<p class="p70">{pigcms{$scenic_list['province_name']}{pigcms{$scenic_list['city_name']}{pigcms{$scenic_list['scenic_address']}</p>
						</li>
						<li class="clr">
							<span class="fl">入园时间：</span>
							<p class="p70">{pigcms{$scenic_order['ticket_time']} {pigcms{$scenic_ticket['start_time']}-{pigcms{$scenic_ticket['end_time']}</p>
						</li>
						<li class="clr">
							<span class="fl">下单时间：</span>
							<p class="p70">{pigcms{$scenic_order['add_times']}</p>
						</li>
						<li class="clr">
							<span class="fl">景区电话：</span>
							<p class="p70">{pigcms{$scenic_list['scenic_phone'][0]} </p>
						</li>
					</ul>
				</div>
				<!--div class="infor_end">
					<h2>
						<span>出行人信息</span>
					</h2>
					<ul class="clr">
						<if condition="$scenic_ticket.ticket_group eq 2">
							<li>
								<h5>团队出行人</h5>
								<div class="end_list clr">
									<img src="{pigcms{$group_traveler_img}" class="view_img">
								</div>
								
							</li>
						
						<else />
						<volist name="scenic_family" id="vo">
							<li>
								<h5>出行人{pigcms{$key+1}</h5>
								<div class="end_list clr">
									<span class="fl">姓&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp名： </span>
									<p class="p75">{pigcms{$vo['family_name']}</p>
								</div>
								<div class="end_list clr">
									<span class="fl">身份证号：</span>
									<p class="p75">{pigcms{$vo['certificates']}</p>
								</div>
							</li>
						</volist>
						</if>
					</ul>
				</div>
				<div class="infor_end infor_take">
					<h2>
						<span>取票人信息</span>
					</h2>
					<ul class="clr">
						<li>
							<h5>取票人</h5>
							<div class="end_list clr">
								<span class="fl">姓&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp名： </span>
								<p class="p75">{pigcms{$scenic_order['truename']}</p>
							</div>
							<div class="end_list clr">
								<span class="fl">电&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp话：</span>
								<p class="p75">{pigcms{$scenic_order['phone']}</p>
							</div>
						</li>
					</ul>
				</div-->
			</div>
			<div class="tim"><i></i>请您在提交订单后<em id="minute_show">{pigcms{$count['branch']}分</em><em id="second_show">{pigcms{$count['second']}</em>秒内完成支付，逾期订单将自动取消。</div>
			<div class="tim">团体票请电话：{pigcms{$scenic_list['scenic_phone'][0]} 景区工作人员确认后再支付！谢谢！</div>
			<div class="bank clr">
				<div class="clr bank_top">
					<h2 class="fl">余额支付</h2>
					<div class="fr">
						<input type="checkbox"  class="balye" name="use_balance" id="use_balance" value="1" style="display:none">
						<label for="balbox" class="balye">使用余额支付</label>
						<span class="fr">￥<em>{pigcms{$now_user['now_money']}</em></span>
					</div>
				</div>
			</div>
			<div class="bank clr" id="paymethod">
				<div class="clr bank_top">
					<h2 class="fl">支付平台</h2>
				</div>
				<div class="method treasure">
					<dl class="clr">
						<volist name="pay_method" id="vv">
							<dd>
								<input type="radio" name="pay_type" value="{pigcms{$key}" style="display:none">
								<img src="{pigcms{$static_public}images/pay/{pigcms{$key}.gif" width="112" height="32" alt="{pigcms{$vv.name}" title="{pigcms{$vv.name}"/>
							</dd>
						</volist>
					</dl>
				</div>
			</div>
			<div class="stays clr">
				<div class="fl">
					<span class="stay">待支付</span>
					<span class="price">￥<em>{pigcms{$scenic_order['order_total']}</em></span>
				</div>
				<a href="javascript:$('#deal-buy-form').submit();" class="fr">确认支付</a>
			</div>
			</form>
		</div>
	</section>
	<style>
		.btn-large {
			padding: 5px 20px 4px;
			font-size: 20px;
			font-weight: 400;
		}

		.btn {
			color: #fff;
			background-color: #2db3a6;
			border-color: #0D7B71;
			filter: progid:DXImageTransform.Microsoft.gradient(gradientType=0,
		 startColorstr='#FF2EC3B4', endColorstr='#FF2DB3A6');
			background-size: 100%;
			background-image: -moz-linear-gradient(top, #2ec3b4, #2db3a6);
			background-image: -webkit-linear-gradient(top, #2ec3b4, #2db3a6);
			background-image: linear-gradient(to bottom, #2ec3b4, #2db3a6);
		}

		.btn, .btn-hot, .btn-normal {
			display: inline-block;
			vertical-align: middle;
			padding: 7px 20px 6px;
			font-size: 14px;
			font-weight: 700;
			line-height: 1.5;
			font-family: SimSun, Arial;
			letter-spacing: .1em;
			text-align: center;
			text-decoration: none;
			border-width: 0 0 1px;
			border-style: solid;
			background-repeat: repeat-x;
			-moz-border-radius: 2px;
			-webkit-border-radius: 2px;
			border-radius: 2px;
			-moz-user-select: -moz-none;
			-ms-user-select: none;
			-webkit-user-select: none;
			user-select: none;
			cursor: pointer;
		}
	</style>
<script type="text/javascript">
	/*银行*/
	$(".click_bank").click(function(){
		if($(".bank_dis").is(":hidden")){
			$(".bank_dis").slideDown();
			$(this).parents(".bank").addClass("on");
		}else{
			$(".bank_dis").slideUp();
			$(this).parents(".bank").removeClass("on");
		}
	})
	$(".method li").click(function(){
		$(this).addClass("on").siblings().removeClass("on");
		var index=$(this).index();
		$(".method dl").eq(index).show().siblings("dl").hide();
	});
	$(".method dd").click(function(){
		$(this).addClass("on").siblings("dd").removeClass("on");
		$(this).parents(".method dl").siblings("dl").find("dd").removeClass("on");
	})
	var count_down	=	"{pigcms{$count['down']}";
	var intDiff = parseInt(count_down);//倒计时总秒数量
	function timers(intDiff){
		window.setInterval(function(){
			var day=0,
			hour=0,
			minute=0,
			second=0;//时间默认值
			if(intDiff > 0){
				minute = Math.floor(intDiff / 60) - (day * 24 * 60) - (hour * 60);
				second = Math.floor(intDiff) - (day * 24 * 60 * 60) - (hour * 60 * 60) - (minute * 60);
			}
			if (minute <= 9) minute = '0' + minute;
			if (second <= 9) second = '0' + second;
			$('#minute_show').html('<s></s>'+minute+'分');
			$('#second_show').html('<s></s>'+second+'');
			intDiff--;
		},1000);
	}

</script>
<script src="http://hf.pigcms.com/static/js/artdialog/jquery.artDialog.js"></script>
	<script src="http://hf.pigcms.com/static/js/artdialog/iframeTools.js"></script>
	<script type="text/javascript">
		var orderid = 0;
		$(function(){

			$('.treasure dd').click(function(){
				$(this).find('input[name="pay_type"]').attr('checked','checked');
			});

			timers(intDiff);
			$("form").submit(function() {
			   $("#J-order-pay-button").val("正在处理...");
			   $("#J-order-pay-button").attr("disabled", "disabled");
			});
			$('#sysmsg-error .close').click(function(){
				$('#sysmsg-error').remove();
			});

			var order_total = Number("{pigcms{$scenic_order.order_total}");
			var now_money = Number("{pigcms{$now_user.now_money}");

			if($("#use_balance").is(':checked')==true){

				if(now_money>=order_total){
					//$('#need_pay').html(0);
					$('#paymethod').css('display','none');
					$(".method dd").removeClass('on')
					$('input:radio:checked').removeAttr('checked');
				}else{
					//$('#need_pay').html((order_total-now_money).toFixed(2));
					$('#paymethod').css('display','block');
					$(".method dd:first").addClass('on')
					$('input[type="radio"]:first').attr('checked','checked');
				}
			}else{
				//$('#need_pay').html(order_total);
				$('#paymethod').css('display','block');
				$(".method dd:first").addClass('on')
				$('input[type="radio"]:first').attr('checked','checked');
			}

			//支付平台
			$("label.balye").click(function(){
				if($(this).siblings("input.balye").is(":checked")){
					$(this).removeClass("on");
					$(this).parents(".bank").css("border","#ebebeb  1px solid")
					$('input.balye').removeAttr('checked');
				}else{
					$(this).addClass("on");
					$(this).parents(".bank").css("border","#fe6903 1px solid")
					$('input.balye').attr('checked','checked');
				}

				if($("#use_balance").is(':checked')==true){
					$('input[name="use_balance"]').val(1);
					if(now_money>=order_total){
						//$('#need_pay').html(0);
						$('#paymethod').css('display','none');
						$('input:radio:checked').removeAttr('checked');
					}else{
						//$('#need_pay').html((order_total-now_money).toFixed(2));
						$('#paymethod').css('display','block');
						$('input[type="radio"]:first').attr('checked','checked');
					}
				}else{
					$('input[name="use_balance"]').val(0);
					//$('#need_pay').html(order_total);
					$('#paymethod').css('display','block');
					$('input[type="radio"]:first').attr('checked','checked');
				}
			});



			$('.see_tmp_qrcode').click(function(){
				var qrcode_href = $(this).attr('href');
				art.dialog.open(qrcode_href+"&"+Math.random(),{
					init: function(){
						var iframe = this.iframe.contentWindow;
						window.top.art.dialog.data('login_iframe_handle',iframe);
					},
					id: 'login_handle',
					title:'请使用微信扫描二维码',
					padding: 0,
					width: 430,
					height: 433,
					lock: true,
					resize: false,
					background:'black',
					button: null,
					fixed: false,
					close: null,
					left: '50%',
					top: '38.2%',
					opacity:'0.4'
				});
				return false;
			});
			$('#deal-buy-form').submit(function(){
				if($('input[name="pay_type"]:checked').val() == 'weixin'){
					art.dialog({
						title: '提示信息',
						id: 'weixin_pay_tip',
						opacity:'0.4',
						lock:true,
						fixed: true,
						resize: false,
						content: '正在获取微信支付相关信息，请稍等...'
					});
					$.post($('#deal-buy-form').attr('action'),$('#deal-buy-form').serialize(),function(result){
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
								content: '<p style="margin-top:20px;margin-bottom:20px;text-align:center;font-size:16px;color:black;">请使用微信扫描二维码进行支付</p><p style="text-align:center;"><img src="{pigcms{:U('Recognition/get_own_qrcode')}&qrCon='+result.info+'" style="width:240px;height:240px;"></p><p style="text-align:center;margin-top:20px;margin-bottom:20px;"><input id="J-order-weixin-button" type="button" class="btn btn-large btn-pay" value="已支付请点击"/></p>',
								// cancel: function(){
									// $("#J-order-pay-button").val("去付款");
									// $("#J-order-pay-button").removeAttr("disabled");
									// window.location.href="{pigcms{$config.site_url}/scenic.php?c=Scenic_user&a=ticket_order_details&order_id={pigcms{$scenic_order.order_id}"
								// },
								cancel:false
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
					return false;
				}
			});
			$('#J-order-weixin-button').live('click',function(){
				console.log(111)
				window.location.href="{pigcms{$config.site_url}/scenic.php?c=Scenic_pay&a=weixin_back&order_type=ticket&order_id="+orderid;
			});
			
			$('.view_img').click(function(){
				window.top.art.dialog({
					padding: 0,
					title: '大图',
					content: '<img src="'+$(this).attr('src')+'" style="width:600px;height:400px;" />',
					lock: true
				});
			
			});
		});
	</script>

<include file="Public/footer"/>