<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8"/>
        <title>{pigcms{$config.cash_alias_name}</title>
        <meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
        <meta http-equiv="pragma" content="no-cache"/>
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name='apple-touch-fullscreen' content='yes'>
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
        <meta name="format-detection" content="telephone=no"/>
        <meta name="format-detection" content="address=no"/>
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/pay.css?2151"/>
		<script>
			var mer_id = '{pigcms{$_GET['mer_id']}';
			if(mer_id){
				var can_change_store = true;
			}else{
				var can_change_store = false;
			}
			var default_money = {pigcms{$_GET['money']|default=0};;
			var vip_discount_type  = {pigcms{$store.vip_discount_type|default=0};
			var level_discount_type  = {pigcms{$level_off['type']|intval};
			var discount_v  = <if condition="$level_off['type'] eq 1">{pigcms{$level_off['vv']/10|floatval}<else />{pigcms{$level_off['vv']|floatval}</if>;
			var currency_symbol = "{pigcms{$config.Currency_symbol}";

            // 消费1元可获养老金
            var old_age_pension = {pigcms{$old_age_pension_per}
		</script>
		<style type="text/css">
			.layermcont{padding: 0 !important;overflow: hidden;}
			.layermcont .content{background-color: #fff;padding: 20px 25px;text-align: center;}
			.layermcont .message{background-color: #fff;padding: 10px 10px;text-align: center;}
			.layermcont .content a{background: #fff;color: #555;}
			.layermcont .tip_info_list{line-height: 2;}
			.layermbtn span[type="1"]{color: {pigcms{$config['mobile_head_color']};}
		</style>
       
    </head>
    <body style="background: #EBEBEB;">  
		<div id="widget_container" class="paybox">
			<form action="" method="POST" autocomplete="off">
				<section class="payment ">
					<div class="pay_top top_notice">
                         <span>
                           <i>向</i>
                           <label id="store_id" data-store_id="{pigcms{$store.store_id}"><if condition="$store">{pigcms{$store.name}<else />请手动选择门店</if></label> 
                           <if condition="$_GET['mer_id']"><em>更换</em></if>
                           <i>付款</i>  
                         </span> 
					</div>
					<input type="hidden" name="total_money" class="total_money"/>
					<input type="hidden" name="no_discount_money" class="no_discount_money"/>
					<input type="hidden" name="store_id" value="<if condition="$store">{pigcms{$store.store_id}</if>"/>
					<div class="pay_con">
                        <p class="p_jin">金额</p>
						<div class="li clr total_money">
							<div class="fl fh">{pigcms{$config["Currency_symbol"]}</div>
							<div class="fl">
								<div id="totalNumber" style="width:198px;height:40px;"></div>
							</div>
						</div>
						<div class="cik discount_div <if condition="empty($store) OR  $store.discount_type eq 0">hide</if>">输入不参与优惠金额</div>
						<div class="li li_s clr no_discount_money">
							<div class="fl">不可优惠金额：</div>
							<div class="fr">
								<div id="noDiscountNumber" style="width:168px;text-align:right;height:20px;"></div>
							</div>
						</div>
                        <div class="li clr on">
                            <div class="fl">备注：</div>
                            <div class="fl">
                                <textarea name="remark" style="width: 260px;font-size: 15px;margin-top: 3px;margin-left: 3px;" rows="2" placeholder="请输入备注"></textarea></textarea>
                            </div>
                        </div>
					</div>

					<!-- 养老金 -->
                    <if condition="$config['open_old_age_pension'] == '1'">
	                    <div class="balance">
	                        <span class="span_balance"><label class="je_label" style="line-height: 22px;">每消费{pigcms{$config.Currency_symbol}<span id="oldAgePensionOutMoney">1</span>预估可得<span id="oldAgePensionGetMoney"></span>养老金</label>
	                    	</span>
	                    </div>
                	</if>
                    <!-- 养老金 end-->
                    
                    <!-- 余额 -->
                    <if condition="$user_session && ($config['open_user_money'] || getFormatNumber($user_session['now_money']))">
	                    <div class="balance">
	                        <span class="span_balance">余额:<label class="je_label">{pigcms{$config.Currency_symbol}{pigcms{$user_session['now_money']}</label>
		                        <if condition="$recharge_discount_list">
		                        <a class="cz_label" href="{pigcms{:U('courtesy_recharge')}"><i class="cart"></i>充值有礼</a>
		                    	</if>
	                    	</span>
	                    </div>
                	</if>
                    <!-- 余额 end-->
                    
                    <div class="btn_pay ">
                        <button class="submit">确定支付 <i class="hide">{pigcms{$config['Currency_symbol']}<em class="surplus">0</em></i></button>
                    </div> 
					
					<div class="lists">
                        <php>
                            $showDiscount = false;
                            if($store['discount_type'] == 2 && $store['minus_price'] > 0){
                                $showDiscount = true;
                            }else if($store['discount_type'] == 1 && $store['discount_percent'] > 0 &&  $store['discount_percent'] < 10){
                                $showDiscount = true;
                            }
                        </php>

						<ul>
                           <if condition="$showDiscount">
                               <li class="clr discount_div <if condition="empty($store) OR  $store.discount_type eq 0">hide</if>" >

								<div class="fl man clr <if condition="!($store.discount_type eq 2 AND $store.minus_price gt 0)">hide</if>">
									<i class="fl">满</i>
									<span class="fl">每满<em class="master">{pigcms{$store.condition_price|floatval}</em>{pigcms{$config['Currency_txt']}减<em class="reduce">{pigcms{$store.minus_price|floatval}</em>{pigcms{$config['Currency_txt']}</span>
								</div>

								<div class="fl zhe clr <if condition="!($store['discount_type'] eq 1 AND $store['discount_percent'] gt 0 AND $store['discount_percent'] lt 10)">hide</if>">
                                    <i class="fl">折</i>
									<span class="fl"><em class="frac">{pigcms{$store.discount_percent}</em>折</span>
								</div>
								<span>&nbsp;&nbsp;&nbsp;{pigcms{$config.cash_alias_name}优惠</span>
								<div class="fr price hide">-<em class="che">0</em></div>
							    </li>
                            </if>
							
							<php>if(!empty($level_off)){</php>
								<li class="clr discount_div " >
									<div class="fl man clr ">
										<i class="fl"><if condition="$level_off.type eq 2 ">减<elseif condition="$level_off['type'] eq 1 " />折</if></i>
										<span class="fl"><em class="reduce_level" data-type="{pigcms{$level_off.type}"><if condition="$level_off.type eq 2 ">{pigcms{$level_off['vv']|floatval}<elseif condition="$level_off['type'] eq 1 " />{pigcms{$level_off['vv']/10|floatval}</if></em><if condition="$level_off.type eq 2 ">{pigcms{$config['Currency_txt']}<elseif condition="$level_off['type'] eq 1 " />折</if></span>
									</div>
									<span>&nbsp;&nbsp;&nbsp;会员优惠</span>
									<div class="fr price hide">-<em class="che_1">0</em></div>
								</li>
							<php>}else if(empty($_SESSION['user'])){</php>
								<li class="clr discount_div " id="bind_user" data-href="{pigcms{:U('Login/index')}&referer={pigcms{:urlencode(U('My/pay',array('store_id'=>$_GET['store_id'])))}" >						
									<div class="fl man clr ">
										<i class="fl">惠</i>
										<span class="fl"><em class="reduce_level" data-type="{pigcms{$level_off.type}"></em></span>
									</div>
									<span style="color:red" >您还没有绑定手机号，请绑定后查看优惠</span>
									<div class="fr price hide"></div>
								</li>
							<php>}else if(empty($_SESSION['user']['phone'])){</php>
								<li class="clr discount_div " id="bind_user" data-href="{pigcms{:U('My/bind_user')}&referer={pigcms{:urlencode(U('My/pay',array('store_id'=>$_GET['store_id'])))}" >						
									<div class="fl man clr ">
										<i class="fl">惠</i>
										<span class="fl"><em class="reduce_level" data-type="{pigcms{$level_off.type}"></em></span>
									</div>
									<span style="color:red" >您还没有绑定手机号，请绑定后查看优惠</span>
									<div class="fr price hide"></div>
								</li>
							<php>}</php>
						
							
							<div class="buy_can_get_score" style="display: none;">
								<li class="clr" style="display: flex;justify-content: space-between;width: 100%">
									<span class="" style="flex: 1;">{pigcms{$buy_can_get_score_txt}</span>
									<span class="value" style="padding-right: 10px;color: #28C66E">+0</span>
								</li>
							</div>
					
							<li class="clr fan <if condition="$store.vip_discount_type eq 0 OR ($store.discount_type eq 0 ) OR ( $level_off['type'] eq 0 OR $level_off['vv'] eq 0 )">hide</if>">
								<div class="fl clr">
									<i class="fl">规</i>
									<span >
									<if condition="$store.vip_discount_type eq 2">折上折。折上折的意思是如果这个用户是有平台VIP等级，平台VIP等级有折扣优惠。那么这个用户的优惠计算方式是先用店铺的优惠进行打折后，再用VIP折扣进去打折；<elseif condition="$store.vip_discount_type eq 1" />折扣最优。折扣最优是指：购买产品的总价用店铺优惠打折后的价格与总价跟VIP优惠打折后的价格进行比较，取最小值的优惠方式。</if></span>
								</div>
							</li>
							<if condition="$config.user_store_score_get gt 0">
							<li class="clr fan <if condition="$config.score_get eq 0">hide</if>">
								<div class="fl clr">
									<i class="fl">返</i>
									<span class="fl">支付此单可返支付金额一定比例的{pigcms{$config.score_name}数</span>
								</div>
							</li>
							</if>
						</ul>
					</div>
					<!-- <button class="submit" style="width: 94%;">确认支付 <i class="hide">{pigcms{$config['Currency_symbol']}<em class="surplus">0</em></i></button> -->
					<div class="choice">
						<div class="h2">请选择所在的门店</div>
						<div class="iscroll">
							<ul>
								<volist name="store_list" id="vo">
									<li>
										<h2 data-store_id="{pigcms{$vo.store_id}" data-discount_type="{pigcms{$vo.discount_type}" data-discount_percent="{pigcms{$vo.discount_percent}" data-condition_price="{pigcms{$vo.condition_price}" data-minus_price="{pigcms{$vo.minus_price}">{pigcms{$vo.name}</h2>
										<p>{pigcms{$vo.area_ip_desc} {pigcms{$vo.adress}</p>
										<if condition="$vo['range']">
											<div class="distance">距离您的位置 {pigcms{$vo.range}</div>
										</if>
									</li>
								</volist>
							</ul>
						</div>
						<div class="close">关闭</div>
					</div>
					<div class="mask"></div>
				</section>
			</form>
        </div>
		<div class="actual_pay_box" style="position:fixed;left:0;width:100%;text-align:right;height:46px;line-height:46px;background:#e5e5e5;display:none; z-index: 9999;
        bottom: 214px;">
            <span style="margin-right:10px;">实付金额：{pigcms{$config['Currency_symbol']}<span class="actual_pay_span" style="font-weight:bold;margin-left:2px;font-size:18px;">0</span></span></div>
        {pigcms{$hideScript}
      	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js?11" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/common.js?21112" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_public}number/number.js?1122" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/pay.js?16" charset="utf-8"></script>	
        <script type="text/javascript">
        	var hasLogin = <?php if(empty($_SESSION['user'])){ echo 0;}else{echo 1;} ?>;
        	var open_bind_merchant_boss = {pigcms{$config['open_bind_merchant_boss']|intval};//是否开启绑定商家老板成为商家粉丝
        	var uid = {pigcms{$_SESSION['user']['uid']|intval};
			var buy_can_get_score = {pigcms{$buy_can_get_score};
			var score_round_two = {pigcms{$score_round_two};
			var buy_can_get_score_txt = "{pigcms{$buy_can_get_score_txt}";
        	if(open_bind_merchant_boss){
        		if(!hasLogin){
	        		// 弹窗提示
	        		layer.open({content:'<div class="content"><span>注册登录后将被绑定为商家粉丝，本单立享优惠</span<div>',btn: ['去注册/登录','跳过'],
	        			yes:function(){
	        				location.href="{pigcms{:U('Login/index')}";
	        			}
	        		});

	        	}else{
	        		var spreadCheck = {pigcms{$spreadCheck|intval};
	        		var boss_uid = {pigcms{$boss_uid|intval};

	        		if(spreadCheck == 0 && boss_uid > 0){
	        			// 弹窗提示
		        		layer.open({content:'<div class="content"><span>是否愿意成为该商家粉丝，本单立即享受优惠</span<div>',btn: ['同意','取消'],
		        			yes:function(){
		        				$.post('{pigcms{$config[\'site_url\']}/appapi.php?c=Spread&a=boss_spread',{'boss_uid':boss_uid,uid:uid},function(data){
		        					if(data.errorCode == 0){
		        						layer.open({
						                    content: '<div class="message"><span>绑定成功</span<div>'
						                    ,skin: 'msg'
						                    ,anim: 'up'
						                    ,time: 2 
							                });
		        					}else{
							    		layer.open({
						                    content: '<div class="message"><span>'+data.errorMsg+'</span<div>'
						                    ,skin: 'msg'
						                    ,anim: 'up'
						                    ,time: 2 
						                });

		        					}
							    },'json');
		        			}
		        		});
	        		}
	        		
	        	}
        	}



			//微信小程序环境直接调起小程序原生支付页面
			<if condition="$is_wxapp_browser && !empty($_SESSION['user'])">
				$('.submit').on('click',function(){
					let formArr = $('form').serializeArray();
					let postParams = {};
					for(let i=0;i<formArr.length;i++){
						postParams[formArr[i].name] = formArr[i].value;
					}
					$.post("{pigcms{:U('My/pay')}",postParams,function(res){
						wx.miniProgram.navigateTo({url: '/pages/shop_new/confirmOrder/pay?order_id=' + res.order_id + '&type=' + res.type});
					})
					return false;
				})
			</if>
        	

        </script>

		<script type="text/javascript">
				var currentUrl = window.location.href;
				var storeLogo = "{pigcms{$store.logo|replace_file_domain=###}";
				window.shareData = {
					"moduleName":"Home",
					"moduleID":"0",
					"imgUrl": storeLogo,
					"sendFriendLink": currentUrl,
					"tTitle": "【{pigcms{$store.name}】{pigcms{$config.cash_alias_name}",
					"tContent": ""
				};
		</script>
		{pigcms{$shareScript}
    </body>
</html>