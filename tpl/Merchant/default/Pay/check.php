<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-bar-chart-o bar-chart-o-icon"></i>
			<li class="active"><a href="{pigcms{:U('Merchant_money/index')}">{pigcms{:L_("商家余额")}</a></li>
			<li class="active">{pigcms{:L_("收入记录")}</li>

		</ul>
	</div>
	<style>
		@font-face {
			font-family: 'iconfont';  /* project id 727532 */
			src: url('//at.alicdn.com/t/font_727532_4weba3ohubc.eot');
			src: url('//at.alicdn.com/t/font_727532_4weba3ohubc.eot?#iefix') format('embedded-opentype'),
			url('//at.alicdn.com/t/font_727532_4weba3ohubc.woff2') format('woff2'),
			url('//at.alicdn.com/t/font_727532_4weba3ohubc.woff') format('woff'),
			url('//at.alicdn.com/t/font_727532_4weba3ohubc.ttf') format('truetype'),
			url('//at.alicdn.com/t/font_727532_4weba3ohubc.svg#iconfont') format('svg');
		}
		.iconfont {color:#fff;font-family:"iconfont" !important;font-size:16px;font-style:normal;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale}
		.icon-check:before {     position: absolute;
			content: "\e6b5";
			right: 0px;
			bottom: -6px;}


		.triangle_border_right{
			width:0;
			height:0;


		}
		.triangle_border_right i{
			display: block;
			width: 0;
			height: 0;
			border-width: 14px 0 14px 14px;
			border-style: solid;
			border-color: transparent transparent transparent #82ABFD;
			position: absolute;
			right: -2px;
			bottom: -9px;
			transform: rotate(45deg);
		}

		.payment-bank {
			margin-top: 10px;
			border: 1px solid #DFDFDF;
			padding: 5px 0 10px 20px;
			background-color: #F5F5F5;
		}
		.payment-banktit {
			height: 20px;
			line-height: 20px;
			margin-top: 5px;
			padding: 5px 0;
			font-family: \5b8b\4f53;
			cursor: pointer;
		}
		.payment-banktit b {
			display: inline-block;
			height: 20px;
			padding-left: 17px;
			color: #333;
			font-size: 14px;
		}
		.payment-bankcen {
			padding-top: 10px;
		}
		.bank {
			width: 786px;
			padding: 15px 0 0 20px;
		}
		.payment-bankcen .bank{
			padding-top: 0;
			width: 1210px;
		}
		.imgradio li {
			padding-left: 20px;
			width: 112px;
			height: 32px;
			float: left;
			position: relative;
			margin: 0 25px 15px 0;
			_display: inline;
			_zomm: 1;
		}
		.imgradio li input {
			position: absolute;
			left: 0;
			top: 10px;
		}
		.imgradio li label{
			cursor:pointer;
		}
		.payment-bankcen .bank .imgradio li {
			margin-right: 45px;
		}
		.clr {
			height: 0;
			font-size: 0;
			line-height: 0;
			clear: both;
			overflow: hidden;
		}
		.form-submit {
			margin: 30px 0 20px;
		}
		
		
#bd {
  width: 1210px;
  margin: 0 auto;
  padding: 10px 0 65px;
    border-top: 3px solid #fe5842;
  margin-top:20px;
}

#content {
  float: left;
  width: 1210px;
  _display: inline;
  padding: 0;
}
.cf {
  zoom: 1;
}		
		
.sysmsgw {
  width: 1150px;
  margin: 10px auto 0;
}
		
.common-tip {
  position: relative;
  margin-bottom: 10px;
  padding: 10px 30px;
  border: 1px #F5D8A7 solid;
  border-radius: 2px;
  background: #FFF6DB;
  font-size: 14px;
  text-align: center;
  color: #666;
  zoom: 1;
}
		
a.see_tmp_qrcode {
  color: #EE3968;
  text-decoration: none;
}
.mainbox {
  border: none;
  padding: 0;
  padding-bottom: 60px;
}	
.summary-table {
    font-size: 12px;
    overflow: visible;
    margin: 0 0 10px;
}
.table-section .order-table-head-row th.desc {
    width: 700px;
}

.btn_btn {
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

.w100{width:100%}
.ar{text-align:right}
.btn_btn, .btn-hot, .btn-normal {
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
.form-submit{
	overflow: hidden;
    text-align: right;
}
.table-section table td, .table-section table th {
    border-bottom: 1px dotted #e5e5e5;
    padding: 12px;
}
.table-section table th {
    background: #f7f7f7 none repeat scroll 0 0;
    font-size: 14px;
    font-weight: 700;
    padding: 6px 12px;
}
.flex{
	display: -webkit-box;
	display: -webkit-flex;
	display: flex;
	align-items: center;
}
		#buy_time{
			height: 30px;
			line-height: 30px;
			margin-top: 30px;
		}
		#buy_time p{margin:0px}
		#buy_time>div{height:100%}
		#buy_time .times span{
			padding: 3px 5px;
			background: #eee;
			margin-right: 15px;
			line-height: 23px;
			cursor:pointer;
			position:relative
		}
		#buy_time .times span.active{
			 border:1px solid #82ABFD;
			color:#82ABFD
		}
		#buy_time .times span input{
			z-index: 10;
			left: 0px;
			position: absolute;
			width: 100%;
			height: 100%;
			opacity: 0;
			cursor:pointer;
		}
		.glyphicon:before {
			content: "\e013";
		}
		.desc{width:50%}
		
		.deal-component-quantity .J-cart-quantity {
    -moz-border-bottom-colors: none;
    -moz-border-left-colors: none;
    -moz-border-right-colors: none;
    -moz-border-top-colors: none;
    border-color: #ddd -moz-use-text-color;
    border-image: none;
    border-style: solid none;
    border-width: 1px medium;
    font-size: 14px;
    height: 32px;
    line-height: 32px;
    margin: 0;
    outline: 0 none;
    padding: 0;
    text-align: center;
    vertical-align: middle;
    width: 40px;
}
.deal-component-quantity button {
    background: #f0f0f0 none repeat scroll 0 0;
    border: 1px solid #ddd;
    color: #666;
    cursor: pointer;
    display: inline-block;
    font-size: 18px;
    font-weight: 700;
    height: 34px;
    margin: 0;
    outline: 0 none;
    padding: 0 6px 2px;
    text-align: center;
    vertical-align: middle;
}
.deal-component-quantity {
    margin-bottom: 20px;
    margin-top: 15px;
}
	</style>

	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12">
					<form action="{pigcms{:U('Pay/go_pay')}" method="post" id="deal-buy-form" class="common-form J-wwwtracker-form" style="width:100%">
			            <div class="mainbox cf" style="min-height:0px;padding-bottom:0px">
			            	<div class="table-section summary-table">
			                    <table cellspacing="0" class="buy-table w100">
			                        <tr class="order-table-head-row">
			                        	<th class="desc">{pigcms{:L_("项目")}</th>
										<th class="col-total ar">{pigcms{:L_("总价")}</th>
			                    	</tr>
									<tr>
										<td class="desc">{pigcms{$order_info.order_name}</td>
										
										<td class="money total rightpadding col-total ar">
											{pigcms{$config['Currency_symbol']}<span id="J-deal-buy-total">{pigcms{$order_info.money}</span>
										</td>
									</tr>
				                   
			                        <tr>
										<if condition="$_GET.type eq 'auth'"> 
											<td>
												<div >
													<div class="flex">
														<p style="margin:0px">{pigcms{:L_("服务价格")}</p>
														<div style="padding-left:15px;color:#82ABFD;font-size:30px" class="flex ">
															{pigcms{$config['Currency_symbol']}{pigcms{$order_info.price|floatval}<if condition="$order_info.time_type_txt neq ''">/{pigcms{$order_info.time_type_txt}</if>
														</div>
													</div>
												</div>
											</td>
										</if>
				                        <td colspan="3" class="extra-fee total-fee rightpadding ar">
											<strong>{pigcms{:L_("订单总额")}</strong>：
				                            <span class="inline-block money">
				                                {pigcms{$config['Currency_symbol']}<strong id="deal-buy-total-t order_money" class="need_pay">{pigcms{$order_info.money}</strong>
												
												<!--
												<if condition="$order_info.old_auth_money gt 0">|{pigcms{:L_("当前套餐剩余")}{pigcms{$order_info.old_auth_money|floatval} {pigcms{$config['Currency_txt']}</if>-->
				                            </span>
				                        </td>
			                    	</tr>
									
									<if condition="$order_info['order_type'] eq 'auth'">
									<tr>						
										<td style="text-align:left;"  colspan="3">
											<php>if(!isset($balance_disable)){</php>
												<input type="checkbox" class="mt"  id="use_balance" name="use_balance"<if condition="$merchant['money'] eq 0 ">disabled="disabled" value="1"<else /> value="1"  </if>/>
											<php>}</php>
											<strong>{pigcms{:L_("可用余额")}</strong>：
											<span class="inline-block money" style="color:#EA4F01;">
												{pigcms{$config['Currency_symbol']}<strong id="deal-buy-total-t">{pigcms{$merchant.money}</strong>
											</span>									
										</td>
									</tr>
								</if>
			                    	
									 
			                	</table>
			            	</div>
							<if condition="$_GET.type eq 'auth'">
								<div id="buy_time" >
									<div class="flex deal-component-quantity">
										<p>{pigcms{:L_("购买周期：")}</p>&nbsp;&nbsp;&nbsp;&nbsp;
										<button for="J-cart-minus" class="minus" id="minus" data-action="-" type="button">-</button>
										<input type="text"  name="num" class="f-text J-quantity J-cart-quantity" maxlength="9"  data-min="1"  id="buy_num" data-price="{pigcms{$order_info.money}" readOnly value="{pigcms{$order_info.num}"/>
										<button for="J-cart-add" class="item plus" data-action="+" type="button" id="plus">+</button>{pigcms{$order_info.time_type_txt}
									</div>
								</div>
							</if>
			            </div>

							<div id="need-pay" style="text-align:right">
								<strong>{pigcms{:L_("总金额")}</strong>：
								<span class="inline-block money" style="font-size:20px;color:#EA4F01;">
									{pigcms{$config['Currency_symbol']}<strong id="deal-buy-total-t" class="need_pay">{pigcms{$order_info.money}</strong>
								</span>
							</div>
							
							
						
						
						<div id="pay_bank_list" <if condition="$order_info.money elt 0">style="display:none"</if>>
							<div class="payment-bank">
								<div class="payment-banktit">
									<b class="open">{pigcms{:L_("选择支付方式")}</b>
								</div>	
								<div class="payment-bankcen">
									<div class="bank morebank" style="width:auto">
										<ul class="imgradio" style="list-style:none">
											<volist name="pay_method" id="vo">
												<php>if(($pay_offline || $key != 'offline') && $key == 'weixin'){</php>
												<li>
													<label>
														<input type="radio" name="pay_type" value="{pigcms{$key}" <if condition="$order_info.money gt 0"><if condition="$i eq 1">checked="checked"</if></if>>
														<img src="{pigcms{$static_public}images/pay/{pigcms{$key}.gif" width="112" height="32" alt="{pigcms{$vo.name}" title="{pigcms{$vo.name}"/>
													</label>
												</li>
												<php>}</php>
											</volist>
										</ul>
										<div class="clr"></div>
									</div>
								</div>
								<div class="clr"></div>
							</div>
						</div>
					
						
						<div class="form-submit">
							<input type="hidden" name="order_id" value="{pigcms{$order_info.order_id}"/>
				    		<input type="hidden" name="order_type" value="{pigcms{$order_info.order_type}"/>
			                <input id="J-order-pay-button" type="submit" class="btn_btn btn-large btn-pay" name="commit" value="{pigcms{:L_("去付款")}"/><br/>
			            </div>
			    	</form>	
				</div>
			</div>
		</div>
		
	</div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
$(function(){
	 $("input[name=times]").change(function(){

		 $(this).closest('div').find("span").removeClass("active")
		 $(this).closest('span').addClass("active");
	 	  console.log("{pigcms{:L_("选择周期为")}"+$(this).val())
	 })
	$("form").submit(function() {
	   $("#J-order-pay-button").val("{pigcms{:L_("正在处理")}...");
	   $("#J-order-pay-button").attr("disabled", "disabled");
	});
	
	$('#sysmsg-error .close').click(function(){
		$('#sysmsg-error').remove();
	});
	
	$('.see_tmp_qrcode').click(function(){
		var qrcode_href = $(this).attr('href');
		art.dialog.open(qrcode_href+"&"+Math.random(),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('login_iframe_handle',iframe);
			},
			id: 'login_handle',
			title:'{pigcms{:L_("请使用微信扫描二维码")}',
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
		if($('input[name="pay_type"]:checked').val() == 'weixin' || $('input[name="pay_type"]:checked').val() == 'weifutong'){
			art.dialog({
				title: '{pigcms{:L_("提示信息")}',
				id: 'weixin_pay_tip',
				opacity:'0.4',
				lock:true,
				fixed: true,
				resize: false,
				content: '{pigcms{:L_("正在获取微信支付相关信息，请稍等")}...'
			});
			$.post($('#deal-buy-form').attr('action'),$('#deal-buy-form').serialize(),function(result){
				art.dialog.list['weixin_pay_tip'].close();			
				if(result.status == 1){
					
					orderid = result.orderid;
					art.dialog({
						title: '{pigcms{:L_("请使用微信扫码支付")}',
						id: 'weixin_pay_qrcode',
						width:'350px',
						opacity:'0.4',
						lock:true,
						fixed: true,
						resize: false,
						content: '<p style="margin-top:20px;margin-bottom:20px;text-align:center;font-size:16px;color:black;">{pigcms{:L_("请使用微信扫描二维码进行支付")}</p><p style="text-align:center;"><img src="{pigcms{$config.site_url}/index.php?c=Recognition&a=get_own_qrcode&qrCon='+result.info+'" style="width:240px;height:240px;"></p><p style="text-align:center;margin-top:20px;margin-bottom:20px;"><input id="J-order-weixin-button" type="button" class="btn_btn btn-large btn-pay" value="{pigcms{:L_("已支付完成")}"/></p>',
						cancel: function(){
							$("#J-order-pay-button").val("{pigcms{:L_("去付款")}");
							$("#J-order-pay-button").removeAttr("disabled");
						},
					});
				}else{
					$("#J-order-pay-button").val("{pigcms{:L_("去付款")}");
					$("#J-order-pay-button").removeAttr("disabled");
					art.dialog({
						title: '{pigcms{:L_("错误提示：")}',
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
		window.location.href="{pigcms{:U('Pay/weixin_back',array('order_type'=>$order_info['order_type']))}&order_id="+orderid+'&pay_type='+$('input[name="pay_type"]:checked').val();
	});
		var all_money = parseFloat("{pigcms{$order_info.money}");
		var now_money = parseFloat("{pigcms{$merchant.money}");
		var price = parseFloat("{pigcms{$order_info.money}");
			$('#plus').click(function(){
				var buy_num = parseInt($('#buy_num').val())+1;
					$('#buy_num').val(buy_num);
				$.post('{pigcms{:U('Merchant_money/check_before_buy')}',{auth_id:{pigcms{$order_info.auth_id},'num':buy_num},function(date){
					
					if(date.info>0){
						
						all_money = date.info.toFixed(2);
						$('#pay_bank_list').show()
					}else{
						all_money = (price*buy_num).toFixed(2);
					}
					$('.need_pay').html(all_money)
				},'json')
			});
			$('#minus').click(function(){
	
				var buy_num = $('#buy_num').val();
				
				if(Number(buy_num)-1>=1){
					buy_num-=1
				}else{
					buy_num = 1;
				}
					$('#buy_num').val(buy_num);
				
				$.post('{pigcms{:U('Merchant_money/check_before_buy')}',{auth_id:{pigcms{$order_info.auth_id},'num':buy_num},function(date){
					if(date.info>0){
						all_money = date.info.toFixed(2);
						$('#pay_bank_list').show()
					}else{
						all_money = (price*buy_num).toFixed(2);
					}
					
					$('.need_pay').html(all_money)
				},'json')
				
			
			});
			
	$('#use_balance').click(function(){
		var use_balance = $(this).val();
		console.log(use_balance)
		
		if($("#use_balance").is(':checked')==true){
			
			if(now_money>=all_money){
				$('input[name="pay_type"]').attr('checked',false)
				$('#pay_bank_list').hide();
			}else{
				$('input[name="pay_type"]:first').attr('checked',true)
				$('#pay_bank_list').show();
			}
			
		}else{
				$('input[name="pay_type"]:first').attr('checked',true)
			$('#pay_bank_list').show();
		}
		
	})

});






</script>
<include file="Public:footer"/>
