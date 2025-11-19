<include file="Public:header" />
<div class="main-content">
	<style>
		.swiper-slide:hover {
			box-shadow: 0 0 30px #e9e8eb;
			-webkit-transform: translateY(-10px);
			-moz-transform: translateY(-10px);
			-ms-transform: translateY(-10px);
			-o-transform: translateY(-10px);
			transform: translateY(-10px);
			transition: transform .3s linear
		}

		/*.mo_mg{margin-left:10px}*/
		.mgr10 {
			margin-right: 10px !important;
		}

		.mgr0 {
			margin-right: 0px !important;
		}

		.outTime {
			padding: 0px 25px;
			background: #FB7A7A;
			height: 45px;
			line-height: 45px;
			display: inline-block;
			color: #fff;
			width: 100%;
			margin-bottom: 10px;
		}

		.outTime a {
			margin-left: 30px;
			padding: 3px 10px;
			background: #fff;
			color: #FB7A7A;
			cursor: pointer;
		}

		.swiper-button-next,
		.swiper-container-rtl .swiper-button-prev {
			background-image: url(/tpl/Merchant/default/static/images/new/xzt_03s.png);
		}

		.swiper-button-prev,
		.swiper-container-rtl .swiper-button-next {
			background-image: url(/tpl/Merchant/default/static/images/new/xzt_04s.png);
		}

		.page-home .home-aside {
			display: block !important;
			flex: none !important;
		}

		#text-nowrap {
			display: inline-block;
			white-space: nowrap;
			word-wrap: break-word;
			word-break: break-all;
			text-overflow: ellipsis;
			overflow: hidden;
			max-width: calc(100% - 450px);
			line-height: 1;

		}
		.layui-layer-content{
			white-space: pre-wrap
		}
	</style>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content page-home">
	<div class="home-main" style="100%">
		
		<if condition="$store_list">
			<div class="outTime" style="background:#E6F7FF;color:#333;overflow: hidden;width:100%;">
				<img style="width: 25px;margin-right:5px;" src="{pigcms{$static_path}images/icons/1.png" alt="" />
				{pigcms{:L_("您有X1家店铺",count($store_list))}
				（
				<div id="text-nowrap">

					<volist name="store_list" id="vo">
						{pigcms{$vo.name};
					</volist>
				</div>
				）
				{pigcms{:L_("即将到期，请前往续费！")}
				<a onclick="window.top.addIframe('', '', '/merchant/store.merchant/StoreList', '')" style="background:#1991FF;color:#fff;">{pigcms{:L_("立即续费")}</a>
			</div>
		</if>
		<if condition="$config.buy_merchant_auth eq 1">
		<if condition="($now_group.is_default eq 1) OR ($merchant.merchant_end_time eq 0)">

		<elseif condition="$merchant.is_try eq 1 AND $merchant['authority_day'] elt 3 AND $merchant['authority_day'] egt 0 "/>
			<div class="outTime">{pigcms{:L_("您的系统试用时间还剩")}{pigcms{$merchant.authority_day|intval}{pigcms{:L_("天，到期后系统将无法为您提供正常服务。建议您尽快订购套餐以免影响正常使用")}<a href="{pigcms{:U('Merchant_money/buy_merchant_service')}">{pigcms{:L_("立即续费")}</a></div>
		<elseif condition="$merchant['authority_day'] elt 3 AND intval($merchant['authority_day']) egt 0 "/>
		<div class="outTime">
			{pigcms{:L_("您的系统使用时间还剩")}{pigcms{$merchant.authority_day|intval}{pigcms{:L_("天，到期后系统将无法为您提供正常服务。建议您尽快续费套餐以免影响正常使用")}<a href="{pigcms{:U('Merchant_money/buy_merchant_service')}">{pigcms{:L_("立即续费")}</a></div>
		<elseif condition="$merchant.merchant_end_time lt time()" />
		<div class="outTime">{pigcms{:L_("您的系统试用时间已到期，系统将无法为您提供正常服务。请先订购套餐以免影响正常使用")}<a href="{pigcms{:U('Merchant_money/buy_merchant_service')}">{pigcms{:L_("立即续费")}</a></div>
		</if>
		</if>
        <if condition="$merchant['is_merchant_money'] || $merchant['is_card_new'] || $merchant['is_lottery'] || $merchant['is_store']">
			<div class="row">
				<div class="am-margin-bottom">
					<div class="widget am-cf">
                        <div class="widget-head">
                            <div class="widget-title">{pigcms{:L_("数据统计")}</div>
                        </div>
						<div class="widget-body clearfix">
                            <if condition="$merchant['is_merchant_money']">
                                <div class="am-u-sm-12 am-u-md-6 am-u-lg-3" <if condition="$show_merchant_score">style="width:20%"</if>>
                                    <div class="widget-card card__blue am-cf" style="cursor:pointer;" onclick="location.href='{pigcms{:U('Merchant_money/index')}'">
                                        <div class="card-header">{pigcms{:L_("商家余额")}</div>
                                        <div class="card-body">
                                            <div class="card-value">{pigcms{$all_money|number_format=###,2}</div>
                                            <div class="card-description">{pigcms{:L_("当前商家余额总数")}</div>
                                            <span class="card-icon iconfont icon-yue"></span>
                                        </div>
                                    </div>
                                </div>
                            </if>
                            <if condition="$show_merchant_score">
                                <div class="am-u-sm-12 am-u-md-6 am-u-lg-3" <if condition="$show_merchant_score">style="width:20%"</if>>
                                    <div class="widget-card card__blue am-cf" style="cursor:pointer;" onclick="location.href='{pigcms{:U('Merchant_score/log_list')}'">
                                        <div class="card-header">{pigcms{:L_("可用积分")}</div>
                                        <div class="card-body">
                                            <div class="card-value">{pigcms{$merchant.merchant_score}</div>
                                            <div class="card-description">{pigcms{:L_("锁定积分")}:{pigcms{$lock_score}</div>
                                            <span class="card-icon iconfont icon-yue"></span>
                                        </div>
                                    </div>
                                </div>
                            </if>
                            <if condition="$merchant['is_card_new']" >
                                <div class="am-u-sm-12 am-u-md-6 am-u-lg-3" <if condition="$show_merchant_score">style="width:20%"</if> onclick="location.href='{pigcms{:U('Card_new/index')}'">
                                    <div class="widget-card card__red am-cf" style="cursor:pointer;">
                                        <div class="card-header">{pigcms{:L_("会员总量")}</div>
                                        <div class="card-body">
                                            <div class="card-value">{pigcms{$card_count|number_format=###}</div>
                                            <div class="card-description">{pigcms{:L_("当前会员总数量")}</div>
                                            <span class="card-icon iconfont icon-huiyuan"></span>
                                        </div>
                                    </div>
                                </div>
                            </if>
                            <if condition="$merchant['is_lottery']">
                                <div class="am-u-sm-12 am-u-md-6 am-u-lg-3" <if condition="$show_merchant_score">style="width:20%"</if> onclick="location.href='{pigcms{:U('Lottery/index')}'">
                                    <div class="widget-card card__violet am-cf" style="cursor:pointer;">
                                        <div class="card-header">{pigcms{:L_("活动总量")}</div>
                                        <div class="card-body">
                                            <div class="card-value">{pigcms{$lottery_count}</div>
                                            <div class="card-description">{pigcms{:L_("已创建微活动总数量")}</div>
                                            <span class="card-icon iconfont icon-huodongqizhibiaoji"></span>
                                        </div>
                                    </div>
                                </div>
                            </if>
                           <if condition="$merchant['is_store']">
                                    <if condition="C('open_bd_spread_new') eq 1">
									<div class="am-u-sm-12 am-u-md-6 am-u-lg-3" style="cursor:pointer; <if condition="$show_merchant_score">;width:20%</if>" <if condition="$buy_store_count gt 0">onclick="window.top.addIframe('', '', '/new_marketing/merchant/storeUseDetail', '')"<else/>onclick="window.top.addIframe('', '', '/merchant/store.merchant/StoreList', '')"</if>>
										<div class="widget-card card__primary am-cf">
											<div class="card-header">{pigcms{:L_("店铺总量")}</div>
											<div class="card-body">
												<div class="card-value">
													{pigcms{$store_count}
													<if condition="$buy_store_count gt 0">
														/{pigcms{$buy_store_count}
													</if>
												</div> 
												<div class="card-description"><if condition="$buy_store_count gt 0">{pigcms{:L_("购买店铺资格总数量以及使用情况")}<else/>{pigcms{:L_("店铺总数量")}</if></div>
												<span class="card-icon iconfont icon-dianpu"></span>
											</div>
										</div>
									</div>
                                    <else/>
                                        <div class="am-u-sm-12 am-u-md-6 am-u-lg-3" style="cursor:pointer;<if condition="$show_merchant_score">;width:20%</if>" onclick="window.top.addIframe('', '', '/merchant/store.merchant/StoreList', '')">
                                        <div class="widget-card card__primary am-cf">
                                            <div class="card-header">{pigcms{:L_("店铺总量")}</div>
                                            <div class="card-body">
                                                <div class="card-value">
                                                    {pigcms{$store_count}
                                                </div>
                                                <div class="card-description">{pigcms{:L_("店铺总数量")}</div>
                                                <span class="card-icon iconfont icon-dianpu"></span>
                                            </div>
                                        </div>
                                    </div>
                                    </if>
								</if>

							</div>
						</div>
					</div>
				</div>
			</if>
			<div class="row">
				<div class="am-margin-bottom">
					<div class="widget clearfix">
						<div class="widget-head">
							<div class="widget-title">{pigcms{:L_("实时概况")}<if condition="$merchant_session['sub_mch_id']"><span style="margin-left:6px;font-size:12px;color:#999;">({pigcms{:L_("自有支付不计入")})</span></if>
							</div>
						</div>
						<div class="widget-body clearfix">
							<div class="am-u-sm-6 am-u-md-6 am-u-lg-3">
								<div class="widget-outline dis-flex flex-y-center">
									<div class="outline-left">
										<img src="{pigcms{$static_path}images/new/order_count.png" alt="" />
									</div>
									<div class="outline-right dis-flex flex-dir-column flex-x-between">
										<div style="color: rgb(102, 102, 102); font-size: 1.3rem;">{pigcms{:L_("今日收入")}({pigcms{$config['Currency_txt']})</div>
										<div style="color: rgb(51, 51, 51); font-size: 2.4rem;">{pigcms{$today_order_money|number_format=###,2}</div>
										<div style="color: rgb(153, 153, 153); font-size: 1.2rem;">{pigcms{:L_("昨日")}：{pigcms{$yesterday_order_money|number_format=###,2}</div>
									</div>
								</div>
							</div>
                            <if condition="C('customization.internal_membership_card')">
                                <div class="am-u-sm-6 am-u-md-6 am-u-lg-3">
                                    <div class="widget-outline dis-flex flex-y-center">
                                        <div class="outline-left">
                                            <img src="{pigcms{$static_path}images/new/order_count.png" alt="" />
                                        </div>
                                        <div class="outline-right dis-flex flex-dir-column flex-x-between">
                                            <div style="color: rgb(102, 102, 102); font-size: 1.3rem;">{pigcms{:L_("今日一卡通收入")}({pigcms{$config['Currency_txt']})</div>
                                            <div style="color: rgb(51, 51, 51); font-size: 2.4rem;">{pigcms{$today_ecard_money|number_format=###,2}</div>
                                            <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">{pigcms{:L_("昨日")}：{pigcms{$yesterday_ecard_money|number_format=###,2}</div>
                                        </div>
                                    </div>
                                </div>
                            </if>
                            
							<div class="am-u-sm-6 am-u-md-6 am-u-lg-3">
								<div class="widget-outline dis-flex flex-y-center">
									<div class="outline-left">
										<img src="{pigcms{$static_path}images/new/new_user.png" alt="" />
									</div>
									<div class="outline-right dis-flex flex-dir-column flex-x-between">
										<div style="color: rgb(102, 102, 102); font-size: 1.3rem;">{pigcms{:L_("今日订单数")}</div>
										<div style="color: rgb(51, 51, 51); font-size: 2.4rem;">{pigcms{$today_order_count|number_format=###}</div>
										<div style="color: rgb(153, 153, 153); font-size: 1.2rem;">{pigcms{:L_("昨日")}：{pigcms{$yesterday_order_count|number_format=###}</div>
									</div>
								</div>
							</div>
							<div class="am-u-sm-6 am-u-md-6 am-u-lg-3">
								<div class="widget-outline dis-flex flex-y-center">
									<div class="outline-left">
										<img src="{pigcms{$static_path}images/new/new_user.png" alt="" />
									</div>
									<div class="outline-right dis-flex flex-dir-column flex-x-between">
										<div style="color: rgb(102, 102, 102); font-size: 1.3rem;">{pigcms{:L_("新增会员数")}</div>
										<div style="color: rgb(51, 51, 51); font-size: 2.4rem;">{pigcms{$today_card_user|number_format=###}</div>
										<div style="color: rgb(153, 153, 153); font-size: 1.2rem;">{pigcms{:L_("昨日")}：{pigcms{$yesterday_card_user|number_format=###}</div>
									</div>
								</div>
							</div>
							<div class="am-u-sm-6 am-u-md-6 am-u-lg-3">
								<div class="widget-outline dis-flex flex-y-center">
									<div class="outline-left">
										<img src="{pigcms{$static_path}images/new/new_user.png" alt="" />
									</div>
									<div class="outline-right dis-flex flex-dir-column flex-x-between">
										<div style="color: rgb(102, 102, 102); font-size: 1.3rem;">{pigcms{:L_("下单用户数")}</div>
										<div style="color: rgb(51, 51, 51); font-size: 2.4rem;">{pigcms{$today_new_order_user|number_format=###}</div>
										<div style="color: rgb(153, 153, 153); font-size: 1.2rem;">{pigcms{:L_("昨日")}：{pigcms{$yesterday_new_order_user|number_format=###}</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- {pigcms{:L_("近七日交易走势")} -->
			<div class="row">
				<div class="am-margin-bottom">
					<div class="widget clearfix">
						<div class="widget-head">
							<div class="widget-title">{pigcms{:L_("近七日收入走势")}</div>
						</div>
						<div class="widget-body clearfix">
							<div id="echarts-trade" class="widget-echarts"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="home-aside">
			<!-- <a href="http://wpa.qq.com/msgrd?v=3&uin={pigcms{$config.site_qq}&site=qq&menu=yes" target="_blank"> -->
				<div class="style__style--3Izek">
					<div class="style__style--3zzla">
						<div class="icon icon-headset" style="background-image:url({pigcms{$static_path}images/new/headset.png);"></div>
						<h4 class="title">{pigcms{:L_("在线客服")}</h4>
						<p class="desc">{pigcms{:L_("电话")}：{pigcms{$config.site_phone}</p>
					</div>
				</div>
			<!-- </a> -->

			<div class="style__style--3Izek style__style--j3wmV">
				<if condition="$im_jmessage_open eq 1">
					<div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;" onclick="window.open('plugin.php?g=System&c=Kefu&a=login&plugin=im')" title="{pigcms{:L_(" 网页在线客服")}">
						<else />
						<div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;" onclick="location.href='{pigcms{:U('Customer/service')}'" title="{pigcms{:L_(" 网页在线客服")}">
				</if>
				<div class="style__style--3zzla">
					<div class="icon icon-im" style="background-image:url({pigcms{$static_path}images/new/desktop.png);"></div>
					<h4 class="title">{pigcms{:L_("网页在线客服")}</h4>
					<p class="desc">{pigcms{:L_("即时应答客户在线咨询")}</p>
				</div>
			</div>
			<if condition="$appConfig['mer_android_vcode']">
				<div class="zent-popover-wrapper zent-pop-wrapper" id="showMerchantAppPop" style="display:inline-block;">
					<div class="style__style--3zzla">
						<div class="icon icon-app" style="background-image:url({pigcms{$static_path}images/new/mobile.png);"></div>
						<h4 class="title">{pigcms{:L_("移动客户端")}</h4>
						<p class="desc">{pigcms{:L_("使用手机、Pad管理店铺")}</p>
					</div>
					<div class="zent-portal zent-pop zent-popover zent-popover-internal-id-4 zent-popover-position-left-top hide" id="downMerchantAppPop">
						<div class="zent-popover-content">
							<div class="zent-pop-inner">
								<div class="app__style--2j6ge">
									<div class="header-box">{pigcms{:L_("扫码下载")}</div>
									<div class="applications">
										<div class="app-item">
											<img src="/index.php?c=Recognition&a=get_own_qrcode&qrCon={pigcms{$config['site_url']}/topic/app_wap_merchant.html" alt="{pigcms{:L_(" 手机客户端")}" />
											<p>{pigcms{:L_("手机商家客户端")}</p>
										</div>
									</div>
								</div>
							</div>
							<i class="zent-pop-arrow"></i>
						</div>
					</div>
				</div>
			</if>
		</div>
		<div class="style__style--2KBWx">
			<div class="header-box">
				<h4 class="header-title">{pigcms{:L_("商家公告")}</h4>
			</div>
			<ul class="list">
				<volist name="news_list" id="vo">
					<li>
						<a class="<if condition=" $vo['is_top'] eq 1">red</if> layer-iframe" href="{pigcms{:U('Index/news',array('id'=>$vo['id'],'iframe_window'=>'1'))}" data-title="{pigcms{:L_("商家公告")}">{pigcms{$vo.title}</a>
					</li>
				</volist>
			</ul>
		</div>
		<if condition="$config.buy_merchant_auth eq 1">
			<div class="style__style--2KBWx">
				<div class="header-box">
					<h4 class="header-title">{pigcms{:L_("当前套餐")}</h4>
					<a class="header-link" href="{pigcms{:U('Merchant_money/buy_merchant_service')}" target="_self">{pigcms{:L_("查看")}</a>
				</div>
				<ul class="list">
					<li>
						{pigcms{$config.Currency_symbol}{pigcms{$now_group.price},{pigcms{$now_group.name}
					</li>
					<li>
						{pigcms{:L_("到期时间：")}<if condition="($now_group.is_default eq 1) OR  ($merchant.merchant_end_time eq 0)">{pigcms{:L_("永久")}
							<else />{pigcms{$merchant.merchant_end_time|date="Y-m-d H:i:s",###}
						</if>
					</li>
				</ul>
			</div>
		</if>
		<div class="style__style--2KBWx">
			<div class="header-box">
				<h4 class="header-title">{pigcms{:L_("平台快报")}</h4>
				<a class="header-link" href="{pigcms{$config.site_url}/news/" target="_blank">{pigcms{:L_("更多")}</a>
			</div>
			<ul class="list">
				<volist name="plat_news_list" id="vo">
					<li>
						<a class="<if condition=" $i eq 1">red</if>" href="/news/{pigcms{$vo.id}.html" target="_blank" title="【<if condition="$now_cat">{pigcms{$now_cat.name}
								<else />{pigcms{$vo.name}
							</if>】{pigcms{$vo.title}">【<if condition="$now_cat">{pigcms{$now_cat.name}
								<else />{pigcms{$vo.name}
							</if>】{pigcms{$vo.title}</a>
					</li>
				</volist>
			</ul>
		</div>
	</div>
</div>

<!-- {pigcms{:L_("弹层")} -->

<div id="model" <if condition="$config['buy_merchant_auth'] eq 0 OR ($merchant['authority_group_id'] gt 0 AND $merchant['is_try'] eq 0) OR ($merchant['is_try'] eq 1 AND $merchant['try_end_time'] gt time() ) OR empty($group)">style="display:none;"</if>>
	<div class="model_mask"></div>
	<div class="hide_model">
		<button type="button" class="el-button  el-button--medium">
			<span>{pigcms{:L_("退出登录")}</span>
		</button>
	</div>
	<div class="model_dialog">
		<div class="swiper-button-prev" style="    left: -100px;"></div>
		<!--{pigcms{:L_("左箭头")}-->
		<div class="swiper-button-next" style="    right: -100px;"></div>
		<!--{pigcms{:L_("右箭头")}-->
		<div class="swiper-container swiper-container-model">
			<div class="swiper-wrapper">

				<volist name="group" id="vo">
					<div class="swiper-slide <if condition='$key eq 0'>mo_mg</if>">
						<div data-v-455236be="" class="el-col el-col-6">
							<div data-v-455236be="" class="grid-content bg-purple" style="    box-shadow: unset;">
								<div data-v-455236be="" class="createtopbg">
									<if condition="$vo.is_default eq 1"><img src="/tpl/Merchant/default/static/images/new/my_free.png" /></if>
									<div data-v-455236be="" class="tc pt30 fontbai f24">{pigcms{$vo.name}</div>
									<div data-v-455236be="" class="tc p20 fontbai f14">{pigcms{$vo.des}</div>
								</div>
								<div data-v-455236be="" class="createContent">
									<div data-v-455236be="" class="p30 pl40 createFont">
										<ul data-v-455236be="">
											<volist name="vo.detail" id="vv">
												<if condition="$vv">
													<li data-v-455236be="">
														<img data-v-455236be="" src="/tpl/Merchant/default/static/images/new/createico1.png">
														{pigcms{$vv}
													</li>
												</if>
											</volist>

										</ul>
									</div>
								</div>
								<div data-v-455236be="" class="createPrice mt10">
									<div data-v-455236be="" class="f30 tc price">
										<span data-v-455236be="" class="f20">{pigcms{$config.Currency_symbol}</span>{pigcms{$vo.price}<if condition="$vo.is_default eq 0">/<span data-v-455236be="">{pigcms{$time_type[$vo['time_type']]}</if></span>
									</div>
								</div>
								<div data-v-455236be="" class="oldPrice">
									<div data-v-455236be="" class="f12 tc">
										<span data-v-455236be="" class="f14">{pigcms{:L_("平台抽成比例")}{pigcms{$vo.percent}%</span>
									</div>
								</div>
								<div data-v-455236be="" class="tc  model_button" onclick="artconfirms({pigcms{$vo.id},1<if condition=" $vo.is_try eq 1 AND $merchant.authority_group_id eq 0">,1</if>);">
									<button data-v-455236be="" type="button" class="el-button el-button--primary el-button--medium">
										<span>
											<if condition="$vo.is_try eq 1 AND $merchant.authority_group_id eq 0">{pigcms{:L_("免费试用")}
												<else />{pigcms{:L_("立即使用")}
											</if>
										</span>
									</button>
									<if condition="$vo.is_try eq 1 AND $merchant.authority_group_id eq 0">
										<p style="color:#FD65CA;padding-top:6px">{pigcms{:L_("可免费使用")}{pigcms{$vo.try_time}{pigcms{$time_type[$vo['try_time_type']]}</p>
									</if>
								</div>
							</div>
						</div>
					</div>
				</volist>

			</div>
		</div>

	</div>
</div>

</div>


</div>
<script src="{pigcms{$static_public}echarts/echarts.min.js"></script>
<script src="{pigcms{$static_public}echarts/echarts-walden.js"></script>
<script>
	if (window == top) {
		location.href = '/merchant.php';
	}

	var echartsTrade = null;

	$(function() {
		$('.ant-layout-content', window.parent.document).css('z-index', 1);
		/**
		 * {pigcms{:L_("近七日交易走势")}
		 */
		$.post("{pigcms{:U('get_week_order_list')}", function(echart_data) {
			echartsTrade = echarts.init(document.getElementById('echarts-trade'), 'walden');
			echartsTrade.setOption({
				tooltip: {
					trigger: 'axis'
				},
				legend: {
					data: ['{pigcms{:L_("成交量")}', '{pigcms{:L_("成交额")}']
				},
				toolbox: {
					show: true,
					showTitle: false,
					feature: {
						mark: {
							show: true
						},
						magicType: {
							show: true,
							type: ['line', 'bar']
						}
					}
				},
				calculable: true,
				xAxis: {
					type: 'category',
					boundaryGap: false,
					data: echart_data.info.xAxis_txt
				},
				yAxis: {
					type: 'value'
				},
				series: [{
						name: '{pigcms{:L_("成交额")}',
						type: 'line',
						data: echart_data.info.order_money_list
					},
					{
						name: '{pigcms{:L_("成交量")}',
						type: 'line',
						data: echart_data.info.order_count_list
					}
				]
			}, true);
		});
		$(window).resize(function() {
			if (echartsTrade) {
				echartsTrade.resize();
			}
		});

		if ($(".swiper-slide").length == 1) {
			$(".swiper-slide").eq(0).addClass("mgr0")

		}
		if ($(".swiper-slide").length == 2) {
			$(".swiper-slide").eq(1).addClass("mgr10")

		}


		//"弹层")}
		var mySwiperModel = new Swiper('.swiper-container-model', {
			slidesPerView: 3,
			spaceBetween: 30,
			slidesPerGroup: 1,
			loop: false,
			loopFillGroupWithBlank: false,
			navigation: {
				nextEl: '.swiper-button-next',
				prevEl: '.swiper-button-prev'
			},
		})

		$(".hide_model button").click(function() {

			layer.open({
				content: '{pigcms{:L_("确定退出吗？")}',
				btn: ['{pigcms{:L_("确定")}', '{pigcms{:L("取消")}'],
				shadeClose: false,
				yes: function(index) {
					window.location.href = "{pigcms{:U('Login/logout')}"
				},
				no: function(index) {

					$("#model").hide()
					layer.closeAll();
				}
			});
		})

	});


	function artconfirms(auth_id, type, is_try) {
		// if(typeof(type)!='undefined'){
		// notice_msg = '{pigcms{:L_("如果您的商家余额充足将直接使用商家余额购买，不足请充值后购买。并且购买后，现有的商家后台权限会被购买的套餐权限替换掉，确定购买？")}';
		// }else{
		notice_msg = '{pigcms{:L_("购买后，您将拥有该套餐里的所有权限？")}';
		// }
		layer.confirm(notice_msg, {
			btn: ['{pigcms{:L_("去购买")}', '{pigcms{:L("再看看")}']
		}, function() {
			if (typeof(type) != 'undefined') {
				window.location.href = "{pigcms{$config.site_url}/merchant.php?c=Merchant_money&a=pay_merchant_service&menu_group=" + auth_id + '&is_try=' + is_try;
			} else {
				// window.location.href="{pigcms{:U('pay_merchant_service')}&auth_id="+auth_id
			}
		}, function() {
			window.location.reload()
		});
	}
</script>
<!-- 密码为123456弹窗 -->
<?php if ($merchant_session['pwd'] == 'e10adc3949ba59abbe56e057f20f883e' && $config['pwd_verify'] == 1) : ?>
	<div class="notice_login">
		<p class="p_til">{pigcms{:L_('您的登录密码是系统默认密码，为了账户安全，请您设置新的登录密码')}</p>
		<!--   <div class="input_block">
            <input type="text" placeholder="{pigcms{:L_('请输入原密码')}" name="old_pass"/>  
        </div> -->
		<div class="input_block">
			<input type="password" placeholder="{pigcms{:L_('请输入6位以上的新密码')}" name="new_pass" />
		</div>
		<div class="input_block">
			<input type="password" placeholder="{pigcms{:L_('请再次输入新密码')}" name="re_pass" />
		</div>
		<div class="btn_submit">
			<a href="javascript:;" class="btn_ok">{pigcms{:L_('确认修改')}</a>
			<a href="javascript:;" class="btn_pass">{pigcms{:L_('跳过')}</a>
		</div>
	</div>
	<style>
		.notice_login {
			padding: 20px;
			background: #fff;
			border-radius: 10px;
			display: none;
		}

		.notice_login .p_til {
			font-size: 14px;
			padding-bottom: 10px;
			color: #999;
		}

		.input_block input {
			border: 1px solid #eee;
			height: 30px;
			line-height: 30px;
			padding: 5px;
			width: 100%;
			margin: 10px 0;
		}

		.btn_submit {
			width: 100%;
			margin: 0 auto;
		}

		.btn_submit a {
			width: 100%;
			display: block;
			height: 36px;
			line-height: 36px;
			text-align: center;
			border: 1px solid #4c69fc;
			/* margin: 15px 1% 0 1%; */
			margin-top: 15px;
			border-radius: 8px;
			font-size: 14px;
			display: inline-block;
			float: left;
		}

		.btn_submit a.btn_ok {
			background: #4c69fc;
			color: #fff;
		}

		.btn_submit a.btn_pass {
			color: #4c69fc;
		}

		.input_block input {
			font-size: 14px;
			border-radius: 4px;
		}

		.btn_code {
			background: #4c69fc;
			color: #fff;
			border: none;
			border: 1px solid #eee;
			height: 30px;
			padding: 5px;
			border-radius: 4px;
			width: 90px;
			margin: 10px 0;
		}

		.fl {
			float: left;
		}

		.fr {
			float: right;
		}

		.clear {
			clear: both;
		}
	</style>
	<script>
		var refresh = "{pigcms{$_GET['refresh']|default=''}";
		if (refresh) {
			if (window != top) {
				window.parent.location.reload();
			}
		}

		var is_login_from = "{pigcms{$_SESSION['is_login_from']}";
		<?php $_SESSION['is_login_from'] = 0; ?>

		if (is_login_from == 1) {
			layer.open({
				type: 1,
				title: false,
				closeBtn: 1,
				shadeClose: true,
				skin: 'notice_login',
				content: $('.notice_login'),
				success: function() {
					$('.notice_login').show();
				}
			});
		}

		$('.btn_ok').click(function() {
			// var old_pass = $('input[name=old_pass]').val();
			var new_pass = $('input[name=new_pass]').val();
			var re_pass = $('input[name=re_pass]').val();

			$.post("{pigcms{:U('Config/changePwd')}", {
				new_pass: new_pass,
				re_pass: re_pass
			}, function(data) {
				console.log(data)
				if (data.error > 0) {
					layer.alert(data.msg);
				} else {
					layer.alert(data.msg, function() {
						window.location.reload();
					});

				}
			}, 'json')
		})

		$('.btn_pass').click(function() {
			layer.closeAll()
			$('.notice_login').hide();
			$('#layui-layer-shade1').hide();
		})
	</script>
<?php endif ?>
<!-- 密码为123456弹窗 end -->

<script type="text/javascript">
    var contract_sign_alert = '{pigcms{$contract["contract_sign_alert"]}';
    var contract_sign_tip = `{pigcms{$contract["contract_sign_tip"]}`;
    var mer_id = '{pigcms{$contract["mer_id"]}';

    if (contract_sign_alert == '1') {

        layer.open({
            title:'{pigcms{:L_("温馨提示")}',
            btnAlign: 'c',
            offset: '200px',
            content: contract_sign_tip
            , btn: ['{pigcms{:L_("去签署合同")}']
            , shadeClose: false
            , yes: function (index) {
                layer.closeAll();
                show_contract();
            }
        });
    }else if(contract_sign_alert == '2'){
        layer.open({
            title: '{pigcms{:L_("温馨提示")}'
            ,content: contract_sign_tip
        });
    }

    function show_contract(){
        layer.open({
            title: '合同预览',
            type: 2,
            offset: '50px',
            area: ['850px', '600px'], //宽高
            content: '{pigcms{:U("Index/contact")}' + '&mer_id='+mer_id,
            zIndex: layer.zIndex,
            btn1: function () {
            },
            success: function (layero, index) {
            }
        });
    }

    /*function show_sign_panel(){

    }*/

</script>
<include file="Public:footer"/>
