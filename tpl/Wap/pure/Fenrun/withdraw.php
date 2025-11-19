<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>提现</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}fenrun/css/fenrun.css?215"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<link rel="stylesheet" href="{pigcms{$static_path}css/vant@2.12.47.css"/>
		<script src="{pigcms{$static_path}js/vue@2.6.14.min.js" type="text/javascript" charset="utf-8"></script>
		<script src="{pigcms{$static_path}js/vant@2.12.14.min.js" type="text/javascript" charset="utf-8"></script>
		<style>
			.balance .whole .input:after{
				content:'{pigcms{$config['Currency_symbol']}';
			}
			.layermbtn span:last-child{
				color: {pigcms{$config.mobile_head_color};
			}
			.layermchild .layermbtn span{
			max-height: 39px !important;
			}
		</style>
	</head>
	<body>
		<div id="app">
		<section class="balance" style='height:100%;overflow:hidden'>
			<form class="my_from">
				<div class="present bw">
					<div class="fl">提现人</div>
					<div class="p208">
						<input type="text" placeholder="请填写真实姓名" name="truename"/>
					</div>
				</div>
				
				<div class="present bw">提现金额</div>
				<div class="whole bw">
					<div class="input">
						<input type="number" placeholder="请输入提现金额" class="import" name="money" />
						<div class="click">全部提现</div>
					</div>
					<p class="remind" data-num="{pigcms{$config.company_pay_user_percent|floatval}" >当前余额{pigcms{$config['Currency_symbol']}{pigcms{$user_info['now_money']|getFormatNumber=###}，可提现：{pigcms{$config['Currency_symbol']}{pigcms{$user_info.can_withdraw_money|getFormatNumber=###}<if condition="$config.company_pay_user_percent gt 0">,提现服务费率{pigcms{$config.company_pay_user_percent}%</if></p>
				</div>
				
				<div class="present bw">
					<div class="fl">提现至</div>
					<div class="p208">
						<div class="select">
							<select name="pay_type" id="withdraw_type">
								<if condition="$config.company_bank_pay eq 1 OR $config.company_alipay_pay eq 1 OR $config.company_weixin_pay eq 1"><option hidden>请选择提现方式</option></if>
								<if condition="$config.company_bank_pay eq 1"><option value="0">银行卡<if condition="$config['open_real_time_withdrawal']">（1-3日到账，最快次日）</if></option></if>
								<if condition="$config.company_alipay_pay eq 1"><option value="1">支付宝<if condition="$config['open_real_time_withdrawal']">（实时到账）</if></option></if>
								<if condition="$config.company_weixin_pay eq 1"><option value="2">微信<if condition="$config['open_real_time_withdrawal']">（实时到账）</if></option></if>
							</select>  
						</div> 
					</div>
				</div>

				<div class="from bw">
					<ul class="bank">
						<li class="select" style="width:100%;    padding-left: 0.45rem;">
							<select name="bank_id" id="bank_list"  style="width:100%">
								<volist name="bank_list" id="vo">
									<option value="{pigcms{$vo.id}" data-name="{pigcms{$vo.account_name}">【{pigcms{$vo.remark}】<php> echo  '**** **** **** '.substr($vo['account'],-4); if($vo['is_default']){ echo '【默认】';}</php> </option>	
								</volist>
							</select>
						</li>
						<li>
							<a href="{pigcms{:U('create_bank_account')}&f=1&from=withdraw" style="height: 1.58rem;color: #00c4ac;font-size: 0.26rem;">+新建</a>
						</li>
					</ul>
					<ul class="alipay">
						<li class="luestion">
							<input type="text" placeholder="请填写支付宝账号" name="alipay_account"/>
							<i></i>
						</li>
					</ul>
				</div>
			</form>
            <div class="confirm on">提现</div>
        </section> 
        <section class="prompt">
            <p>提现到支付宝，第三方银行卡均为手动打款方式，请正确填写支付宝账号或银行卡持有人、卡号、所属银行等信息，以免影响正常提现。</p>
            <div class="know">知道了</div>
        </section>
        <div class="mask"></div>
		<van-popup v-model="show" position="center" :style="{ width: '80%' }" round="8" :close-on-click-overlay="false">
			<div style="padding: 20px 20px;text-align: center;">
				<h3>申请中</h3>
				<div style="font-size: 16px;margin-top: 20px;line-height: 1.5">提现金额审核中，请关注【{pigcms{$config.wechat_name}】微信公众号消息，收到提醒后
					<span style="color: red;">需手动点击模版消息确认</span>
				</div>
				<img @click="imgView('{pigcms{$static_path}images/withdraw/withdraw.png')" style="margin: 20px 0;width: 100%" src="{pigcms{$static_path}images/withdraw/withdraw.png" alt="">

				<van-button color="#00c4ac" block :disabled="btnDisabled" @click="()=>{show=false;window.location.replace('wap.php?g=Wap&c=My&a=transaction')}">我知道了{{btnDisabled?`(${second})`:''}}</van-button>
			</div>
		</van-popup>
		</div>
		
		
        <script src="{pigcms{$static_path}fenrun/js/fenrun.js"></script>
		<script src="{pigcms{$static_path}js/common_wap.js"></script>
		<script>
		var _this = '';
		// 实例化vue
		var app = new Vue({
			el: '#app',
			data: {	
				show: false,
				timer: null,
				second: 5,
				btnDisabled: true
			},
			created() {
				_this = this
			},
			methods: {
				openPop(){
					clearInterval(this.timer)
					this.show = true;
					this.timer = setInterval(()=>{
						this.second--;
						if(this.second == 0){
							clearInterval(this.timer)
							this.btnDisabled = false
						}
					},1000)
				},
				imgView(img){
					vant.ImagePreview([img]);
				}
			}
		})
		window.addEventListener('pageshow',function(event){
			var withdraw_refresh = sessionStorage.getItem('withdraw_refresh');
			var widthdraw_goBack = sessionStorage.getItem('widthdraw_goBack');
			if (widthdraw_goBack && widthdraw_goBack == 1) {
				sessionStorage.setItem('mymoney_refresh', 1);
				sessionStorage.removeItem('widthdraw_goBack');
				history.back();
			}
			if(withdraw_refresh == '1'){
				sessionStorage.removeItem('withdraw_refresh');
				location.reload();
			}
		});
		
		if($('input[name="truename"]').val()!='' && typeof($('input[name="truename"]').val())!='undefined' ){
			window.location.reload();
		}
		var now_money = Number('{pigcms{$user_info.can_withdraw_money|floatval}');
		var flag = true;
		$(".import").bind('input', function(e){
			var key = $.trim($(this).val());
			var html='';
			if(key.length > 0){    
			    if(parseFloat(key) > now_money){
			        key = now_money;
			        $(this).val(key);
			    }
				var discount=$(".remind").data("num");
				var t = key*discount < 1 && key*discount>0 ? 1 : key*discount;//最少一分钱
				var jg = (t/100).toFixed(2);
				
				html='额外扣除<i class="penny">{pigcms{$config['Currency_symbol']}'+jg+'</i>手续费，手续费率<i>'+ discount +'%</i>';
			}else{
				html='当前余额{pigcms{$config['Currency_symbol']}{pigcms{$user_info['now_money']}，可提现：{pigcms{$user_info.can_withdraw_money}{pigcms{$config['Currency_txt']}<if condition="$config.company_pay_user_percent gt 0">,提现服务费率{pigcms{$config.company_pay_user_percent}%</if>';
			}
			$(".remind").html(html);
		});

		// 选择下拉框
		$(document).on('change', 'select[name=pay_type]', function(e){
			if($("option:selected").val() == 0){
				$(".bank").show().siblings("ul").hide();
			}else if($("option:selected").val() == 1){
				$(".alipay").show().siblings("ul").hide();
			}else{
				$(".from").find("ul").hide();
			}
		});
		
		$('.click').click(function(){
			$(".import").val($.trim(now_money))
			console.log(now_money)
			var key = $.trim($('.import').val());
			var html='';
			if(key.length > 0){
				$(".confirm").addClass("on");
				var discount=$(".remind").data("num");
				var jg = (key*discount/100).toFixed(2);
				html='额外扣除<i class="penny">{pigcms{$config['Currency_symbol']}'+jg+'</i>手续费，手续费率<i>'+ discount +'%</i>';
			}
			$(".remind").html(html);
		});
		
		$('#bank_list').change(function(){
			var bank_id = $(this).val()
			$("#bank_list option").each(function(index,val){
				if($(val).attr('value')==bank_id){
					$('input[name="truename"]').val($(val).data('name'));
				}
			})
		})
		
		$('input[name="card_number"]').change(function(){
			$.ajax({
				url: '{pigcms{:U('get_bank_name')}',
				type: 'POST',
				dataType: 'json',
				data: {card_number: $('input[name="card_number"]').val()},
				success:function(data){
					if(data.status){
						$('input[name="bank"]').val(data.info)
					}else{
						var layerIndex = layer.open({
							content:'没有查询到相关银行，请手动输入',
							btn: ['我知道了'],
							yes: function(index){
								layer.close(layerIndex);
							}
						});
					}
				}
			});
		});
		// 支付宝弹窗

		$(document).on('click', '.luestion i', function(){
			$(".prompt,.mask").show();
		})
		$(document).on('click', '.prompt .know,.mask', function(){
			$(".prompt,.mask").hide();
		})
			
		$('.confirm').click(function(){
			window.scroll(0, 0);
			var money = $(".import").val();
			if($('input[name="truename"]').val()=='' || typeof($('input[name="truename"]').val())=='undefined'){
				var layerIndex = layer.open({
					content:'请填写真实姓名',
					btn: ['我知道了'],
					yes: function(index){
						layer.close(layerIndex);
					}
				});
				return false;
			}
			
			
			if(money==''){
				var layerIndex = layer.open({
					content:'请输入金额',
					btn: ['我知道了'],
					yes: function(index){
						layer.close(layerIndex);
					}
				});
				return false;
			}
			if(money<=0){
				var layerIndex = layer.open({
					content:'金额输入有误',
					btn: ['我知道了'],
					yes: function(index){
						layer.close(layerIndex);
					}
				});
				return false;
			}
			if(money>now_money){
				var layerIndex = layer.open({
					content:'提现金额超出范围',
					btn: ['我知道了'],
					yes: function(index){
						layer.close(layerIndex);
					}
				});
				return false;
			}
			console.log($('#withdraw_type').val())
			if($('#withdraw_type').val()=='请选择提现方式'){
				var layerIndex = layer.open({
					content:'请选择提现方式',
					btn: ['我知道了'],
					yes: function(index){
						layer.close(layerIndex);
					}
				});
				return false;
			}


			if(flag){
				var withdraw_url = '{pigcms{:U('withdraw')}'
				if($('#withdraw_type').val()==2){
					var withdraw_url = '{pigcms{:U('My/withdraw')}'
				}
					
				$.ajax({
					url: withdraw_url,
					type: 'POST',
					dataType: 'json',
					data:$('.my_from').serialize(),
					beforeSend:function(){
						flag = false;
					},
					success:function(data){
						if(data.status == 2){
							flag = true;
							//去实名认证
							layer.open({
								content: data.info,
								btn: ['去认证', '取消'],
								shadeClose: false,
								yes: function(){
				                     location.href = data.url;
								}
							});
						}else if(data.status == 3){
							vant.Dialog.confirm({
								title: '提现失败',
								message: "微信提现需要先关注【{pigcms{$config.wechat_name}】微信公众号，才可以申请提现",
								confirmButtonText: '公众号二维码',
								cancelButtonText: '关闭',
								confirmButtonColor: '#00c4ac',
							})
							.then(() => {
								vant.ImagePreview(["{pigcms{$config['wechat_qrcode']}"]);
							}).catch(() => {});
                            flag = true;
						}else{
							if($('#withdraw_type').val() == 2) {
								_this.openPop();
								flag = true;
							}else{
								var layerIndex = layer.open({
									content:data.info,
									shadeClose:false,
									btn: ['我知道了'],
									yes: function(index){
										console.log(data.url)
										window.sessionStorage.setItem("widthdraw_goBack", 1);
										if(data.status == 1){
											window.location.href=data.url;
										}else{
											layer.close(layerIndex);
											flag = true;
										}
									}
								});
							}
						}
					}
				});
			}
		});
	</script>
    </body>

</html>