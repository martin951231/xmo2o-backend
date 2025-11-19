<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config['village_group_alias']}</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=yes"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/index.css?t={pigcms{:time()}"/>
		<script type="text/javascript">
            var nowPage = 'pageMy';var main_color='{pigcms{$config["mobile_head_color"]}';
            var map_config = "{pigcms{$config['map_config']}";
            var google_map_ak = "{pigcms{$config['google_map_ak']}";
            var lat = "{pigcms{$header['pick_address']['lat']}";
            var long = "{pigcms{$header['pick_address']['long']}";
            var city = "{pigcms{$now_area['area_name']}";
		</script>
		<style>
		.mask-box .content.gradeBox{ 
			 background:#fff;
		}
		#fis_elm__4 {
			width: 100%;
		}
		
		</style>
	</head>
	<body>
		<div class="pageBg"></div>
		<div id='headerFixed'></div> 
		<scrollView id="pageIndex" class="page pageIndex y hide" data-loadurl="{pigcms{:U('index',array('leader_id'=>$_GET['leader_id']))}" data-pagetitle="{pigcms{$config['village_group_alias']}"></scrollView>
		<scrollView id="pageCart" class="page pageCart y hide" data-loadurl="{pigcms{:U('cart',array('leader_id'=>$_GET['leader_id']))}" data-pagetitle="购物车" data-pagebg="{pigcms{$config.mobile_head_color}"></scrollView>
		<scrollView id="pageMy" class="page pageMy y hide" data-loadurl="{pigcms{:U('my',array('leader_id'=>$_GET['leader_id']))}" data-pagetitle="个人中心">
			<div class="container">
				<div class="headerBoxMy bg-cover" style="<if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]};<else/>background-image:url({pigcms{$static_path}villagegroup/images/bg.jpg);</if>" >
					<div class="info-box">
						<div class="name">
							<div class="font_size16"><if condition="$header">{pigcms{$header.name}<else />{pigcms{$user_session.nickname}</if><if condition="isset($header['status']) AND ($header['status'] eq 1 OR $header['status'] eq 0)"><i class="icon iconfont mgl5 rel edit link newWin" data-url="{pigcms{:U('editNickname')}">&#xe7a1;</i></if></div>
							<div>
								<if condition="$header">团长ID：{pigcms{$header.id}<else />ID：{pigcms{$user_session.uid}</if>
								<if condition="$header && $area_level"><div id="gradeClick" style='display: inline-block;margin-left:10px;padding: 0 5px 0 3px;color:{pigcms{$config["mobile_head_color"]}'><div class="icon iconfont" >&#xe61d;<label>佣金等级：<if condition="$area_level['lname']">{pigcms{$area_level.lname}<else/>{pigcms{$area_level.level}</if></label></div></div></if>
							</div> 
						</div>
						<div class="avatar">
							<img class="img" src="{pigcms{$now_user.avatar}">
							<!-- 头像上的小图标  clas类名hidden表示隐藏 -->
							<if condition="$config.village_group_avatar_head_img neq ''"><img class="icon" src="{pigcms{$config.village_group_avatar_head_img}"></if>
						</div>
					</div>
				</div>

				<!-- 点击等级弹窗 -->
                  <div class="hidden"  id='Grade'>
				   <include file="grade_mask"/> 
				  </div> 
				<!--  点击等级弹窗 end-->

				<div class="orderBox my leader">
					<div class="title  link newWin"  >
                    我的钱包
						<div class="fr">&nbsp;</div>
					</div>
					<div class="orderBiz cf">  
						<div class="item link newWin" style="width:25%" data-url="{pigcms{:U('My/my_money')}" <if condition="$config['twice_verify'] AND empty($_COOKIE['my_wallet_time'])  AND $config['twice_verify_wallet']">style="display:none;"</if>>
							 <p style="color:{pigcms{$config["mobile_head_color"]}">{pigcms{$now_user.now_money|floatval}</p>
							<div class="txt">余额</div>
						</div>
						<div class="item link newWin" style="width:25%" data-url="{pigcms{:U('My/integral')}">
                            <p style="color:{pigcms{$config["mobile_head_color"]}">{pigcms{$now_user.score_count|floatval}</p>
							<div class="txt">{pigcms{$config.score_name}</div>
						</div>

						<div class="item link newWin" style="width:25%" data-url="{pigcms{:U('My/card_list',array('coupon_type' => 'system'))}">
                            <p style="color:{pigcms{$config["mobile_head_color"]}">{pigcms{$coupon_number|floatval}</p>
							<div class="txt">优惠券</div>
						</div> 
						<div class="item link newWin" style="width:25%" data-url="{pigcms{:U('My/levelUpdate')}">
                            <if condition="$now_user.level eq 0">
                                <p style="color:{pigcms{$config["mobile_head_color"]}">VIP{pigcms{$now_user.level}</p>
                                <div class="txt">等级</div>
                                <else/>
                                <p style="color:{pigcms{$config["mobile_head_color"]}">{pigcms{$now_user.lname}</p>
                                <div class="txt">等级</div>
                            </if>
						</div> 
					</div>
				</div>

                <div class="orderBox">
					<div class="title">
						我的订单
						<div class="fr more link newWin" data-url="{pigcms{:U('myOrder')}">查看全部</div>
					</div>
					<div class="orderBiz cf">
						<div class="item link newWin" data-url="{pigcms{:U('myOrder',array('status'=>'0'))}">
							<div class="icon iconfont">&#xe60e;</div>
							<div class="txt">待付款</div>
						</div>
						<div class="item link newWin" data-url="{pigcms{:U('myOrder',array('status'=>'1,5,7'))}">
							<div class="icon iconfont">&#xe6f0;</div>
							<div class="txt">未到货</div>
						</div>
						<div class="item link newWin" data-url="{pigcms{:U('myOrder',array('status'=>'6,8'))}">
							<div class="icon iconfont">&#xe63e;</div>
							<div class="txt">待自提</div>
						</div>
						<div class="item link newWin" data-url="{pigcms{:U('myOrder',array('status'=>'2,3'))}">
							<div class="icon iconfont">&#xe626;</div>
							<div class="txt">已自提</div>
						</div>
					</div>
				</div> 
				<if condition="isset($header['status']) AND ($header['status'] eq 1 OR $header['status'] eq 0)">
				<div class="orderBox leader">
					<div class="title">
						我是团长 
						<?php if ($header['status'] == 0){ ?>
						<div class="fr font_size12"   style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};<else/>color:red</if>"  >已被禁止</div>
                        <?php } else if ($header['pick_address']){ ?>
                            <div class="fr more set_address btn">修改自提地址</div>
						<?php } ?>  
					</div>
					<div class="orderBiz cf">
						<div class="item  newWin" id='scancode'>
							<div class="icon iconfont">&#xe627;</div>
							<div class="txt">扫码核销</div>
						</div>
						<div class="item link newWin" data-url="{pigcms{:U('leaderOrder',array('leader_id'=>$_GET['leader_id']))}">
							<div class="icon iconfont">&#xe638;</div>
							<div class="txt">订单管理</div>
						</div>
						<div class="item link newWin" data-url="{pigcms{:U('reward')}">
							<div class="icon iconfont">&#xe60f;</div>
							<div class="txt">我的佣金</div>
						</div>
						<div class="item link newWin" data-url="{pigcms{:U('statistics_list')}">
							<div class="icon iconfont">&#xe61e;</div>
							<div class="txt">数据统计</div>
						</div>
					</div>
				</div>
				<else />
					<div class="orderBox apply">
						<div class="my-cells">
							<div class="my-cell <if condition="$header AND $header.status eq 3"><else />link</if> newWin <if condition="$header AND $header.status eq 3">active</if>" data-url="{pigcms{:U('apply')}"><if condition="$header AND $header.status eq 3">已申请团长<else />申请成为团长</if></div>
						</div>
					</div>
				</if>

                <if condition='$config["village_group_delivery_switch"] eq 1'>
                    <div class="orderBox apply">
                        <div class="my-cells">
                            <div class="my-cell link newWin"  data-url="{pigcms{:U('My/adress')}">我的收货地址</div>
                        </div>
                    </div>
                </if>

				<div class="orderBox qcode-box align_center">
					<if condition='$config["mobile_head_color"]'>
						<div class="title red font_size15" style="color:{pigcms{$config["mobile_head_color"]}">提货时，向团长出示二维码提货</div>
						<div class="qcode"><img src="{pigcms{:U('consumer_code')}"></div>
						<div class="color_9 font_size12" style="color:{pigcms{$config["mobile_head_color"]}">您也可以</div> 
						<div class="btn"><button style='color:{pigcms{$config["mobile_head_color"]};border-color:{pigcms{$config["mobile_head_color"]};box-shadow:none'>使用提货码</button></div>
  
					<else/>
						<div class="title red font_size15">提货时，向团长出示二维码提货</div>
						<div class="qcode"><img src="{pigcms{:U('consumer_code')}"></div>
						<div class="color_9 font_size12">您也可以</div>
						<div class="btn"><button style='color:{pigcms{$config["mobile_head_color"]};border-color:{pigcms{$config["mobile_head_color"]};box-shadow:none'>使用提货码</button></div>

					</if>

				</div>

				<div class="hidden" id='scanCodeMask'>
					<include file="code_mask"/>
				</div>
 
                <div class='addressHtml' id='addressHtml'>
				   <ul style='margin-top:0'>
						<li><i></i><p class="adress_click" id="tmp_adress_end_html">请选择小区/大厦/标志建筑</p></li>
						<li><input type="text" value="" id="address_detail" placeholder="请补充详细地址"/></li>
						<input type="hidden" id="tmp_end_adress" value=""/>
						<input type="hidden" id="tmp_end_adress_lng" value=""/>
						<input type="hidden" id="tmp_end_adress_lat" value=""/>
						<input type="hidden" id="tmp_end_adress_area_id" value=""/>
						<input type="hidden" id="tmp_end_adress_area_name" value=""/>
					</ul>
					<div style="word-wrap: break-word;padding:20px 5% 0;color: #999;">1. 为了方便团长临时变更自提地址、搬家等，可修改自提地址</div>
					<div style="word-wrap: break-word;padding:5px 5% 0;color: #999;">2. 建议自提地址坐标移动范围在同一小区范围内，即5万平方米范围内</div>
					<div class="btns">
						<button class="cancel_adress" style="margin-right: 2%; background: #fff; color: #999;">取消</button>
						<button class="confirm_adress" style='margin-left: 2%;color: #fff; background:{pigcms{$config["mobile_head_color"]}'>确认</button>
					</div>
				</div>

				<div id="searchAddress" style="z-index:100001; background:#ffffff; display: none;  position:fixed; left:0; top:0%; width:100%; height:100%;margin-top: -0;">
                    <input type="hidden" id="area_id"/>
                    <input type="hidden" id="city_id"/>
                    <input type="hidden" id="province_id"/>

                    <div id="address-widget-map" class="address-widget-map">
                        <div class="address-map-nav">
                            <!-- <div class="left-slogan" onclick="history.go(-1);">
                                <a class="left-arrow icon-arrow-left2"></a>
                            </div> -->
                            <div class="city_box" style="padding-left: 15px;">
                                <span>读取中</span>
                            </div>

                            <div class="center-title" style="padding-left: 15px;">
                                <div class="ui-suggestion-mask">
                                    <input type="text" placeholder="请输入你的地址" id="se-input-wd" autocomplete="off"/>
                                    <!-- <button class="btn show_btn" >关闭</button> -->
                                    <div class="ui-suggestion-quickdel"></div>

                                </div>
                            </div>
                            <div style="padding-left: 15px;">
                                <span onclick="$('#searchAddress').hide();$('#searchAddressTxt').val('');$('#rcv-mask').hide();">关闭</span>
                            </div>
                        </div>
                        <div id="fis_elm__3">
                            <div class="map">
                                <div class="MapHolder" id="cmmap"></div>
                                <div class="dot" style="display:block;"></div>
                            </div>
                            <div class="mapaddress" data-node="mapaddress">
                                <ul id="addressShow"> </ul>
                            </div>
                        </div>
                        <div id="fis_elm__4" style="display:none;">
                            <section class="citylistBox">
                                <dl>
                                    <volist name="all_city" id="vo">
                                        <dt id="city_{pigcms{$key}" class="cityKey" data-city_key="{pigcms{$key}">{pigcms{$key}</dt>
                                        <volist name="vo" id="voo">
                                            <dd class="city_location" data-city_url="{pigcms{$voo.area_url}">{pigcms{$voo.area_name}</dd>
                                        </volist>
                                    </volist>
                                </dl>
                            </section>
                            <div id="selectCharBox"></div>
                        </div>
                    </div>
				</div>  
				</div>  
			</div>
		</scrollView> 
		<include file="index_footer"/>  
	</body> 
</html>