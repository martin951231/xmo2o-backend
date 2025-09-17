<!doctype html>
<html>
<head>
	<include file="header"/>
	<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
	<link rel="stylesheet" href="{pigcms{$static_path}css/vant@2.12.47.css"/>
	<script src="{pigcms{$static_path}js/vue@2.6.14.min.js" type="text/javascript" charset="utf-8"></script>
	<script src="{pigcms{$static_path}js/vant@2.12.14.min.js" type="text/javascript" charset="utf-8"></script>
	<script src="{pigcms{$static_path}js/common.js?t={pigcms{:time()}" type="text/javascript"></script>
	<style>
		.b-color13:after{
			content:none;
		}
		.b-color13{
			border:1px solid #D5D5D6;
		}
		.b-color13 .needsclick{
			width:calc(100% - 4px);
		}
		.order_price{position:relative}
		.order_price:before{

			border-top: 1px solid #999;
			content: '';
			position: absolute;
			left: 10px;
			right: 0px;
			top: 56%;
			height: 1px;
		}
		.foot_dobble a{border-radius:0px;	}
		.foot_dobble{
			padding: 5px 10px 5px 0px;
			align-items: center;
		}
		.foot_dobble .right-a{
			border-bottom-right-radius: 30px !important;;
			border-top-right-radius: 30px !important;;
			height: 38px !important;
			line-height: 38px !important;
			flex: 1;
			margin-top:0px !important;
		}
		.foot_dobble #contactBtn{
			background: #FC5E77 !important;
			border-bottom-left-radius: 30px;
			border-top-left-radius: 30px;
			height: 38px !important;
			line-height: 38px !important;
			flex: 1;
		}

		.foot_dobble a:before,.foot_dobble a:after{content:none !important;}

		.layermcont{padding: 0 !important;overflow: hidden;}
		.layermcont .title_info, .layermcont .title_msg{background-color: #ff4d00;padding: 10px 15px;color: #fff;text-align: center;}
		.layermcont .title_msg{background: #fff;color: #555;}
		.layermcont .content-wrapper{padding:15px;width: 240px;}
		.layermcont .tip_info_list{line-height: 2;}
		.layermbtn span[type="1"]{color: #ff4d00;}

		.foot_dobble{padding: 0;}
		.foot_dobble .right-a:nth-child(2){margin-right: 10px;}
		.view_bottom .foot_dobble .selled{background : #999;background-color:#999 !important;}

		.foot_dobble #contactBtn.first-btn{height: 100% !important;line-height: 50px !important;border-radius: 0;background: #ff6565 !important;}
	</style>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/view-page.css?t={pigcms{:time()}">
</head>
<php>
	$house_info='';
	foreach($content as $v){
		if($v['type'] == 7){
			$house_info = json_encode($v['house_info']);
		}
	}
</php>
<body>
	<if condition="$is_weixin_browser || $is_app_browser">
		<style>
			.view_tools{
				top: unset;
				bottom: 70px;
				right: 185px;
			}
		</style>
	</if>
	<div class="page__bd" id="app" style="display: none">
		<if condition="!$is_weixin_browser && !$is_app_browser">
			<header class="x_header bgcolor_11 cl f15">
				<a class="z f14" href="javascript:window.history.go(-1);"><i class="iconfont icon-fanhuijiantou w15"></i>返回</a>
				<a class="y sidectrl view_ctrl"><i class="iconfont icon-gengduo1 f22"></i></a>
				<div class="navtitle">{pigcms{$page_title}</div>
			</header>
			<div class="x_header_fix" ></div>
		</if>
		<div class="none view_tools_mask"></div>
		<div class="none view_tools animated">
			<ul>
				<li class="border_bottom"><a href="javascript:;" style="left:-3px" class="we_share pr" data-id="{pigcms{$detail.id}"><i class="vm iconfont icon-fenxiang1 f20"></i> 分享</a></li>
				<li class="border_bottom"><a href="{pigcms{:U('category')}"><i class="vm iconfont icon-fenlei "></i> 分类</a></li>
				<li><a href="/packapp/plat/pages/my/complaint/submit?type=classifieds" style="display: flex;align-items: center;"><img style="width: 18px;height: 18px;margin-right:4px" src="{pigcms{$static_path}classifynew/images/gantanhao.png" alt=""><span>举报</span></a></li>
				<li><a href="{pigcms{:U('my')}"><i class="vm iconfont icon-xiaolian2 "></i> 我的</a></li>
			</ul>
		</div>
		<!-- 背景 -->
		<div class="bj-header"></div>
		<!-- 发布者头部信息 -->
		<div class="weui-cells before_none after_15 user-header-box view-box">
			<div class="weui-cell">
				<div class="weui-cell__hd avatar" style="position: relative;margin-right: 10px;">
					<a href="{pigcms{:U('member',array('uid'=>$user['uid']))}">
                        <php>if ($user['avatar']) {</php>
                            <img src="{pigcms{:replace_file_domain($user['avatar'])}" class="v_head"/>
                        <php>} else {</php>
                            <img src="./static/images/user_avatar.jpg" class="v_head"/>
                        <php>}</php>
					</a>
				</div>
				<div class="weui-cell__bd">
					<a class="nickname" href="{pigcms{:U('member',array('uid'=>$user['uid']))}">{pigcms{$user.nickname}</a>
					<div class="flex flex-align-center" style="flex-wrap: wrap;">
						<div class="is-green">{pigcms{$detail.cat_name}</div>
						<if condition="$detail['topsort']">
							<div class="is-star ml-5">置顶</div>
						</if>
						<if condition="$detail['redpack_count']">
							<div class="is-hot ml-5">红包</div>
						</if>
					</div>
					<div class="c9 f12 addtime">{pigcms{$detail.addtime|date='m-d H:i',###} 发布</div>
				</div>
				<if condition="$detail['redpack_count']">
					<div class="weui-cell_ft" style="text-align:right">
						<span class="color-red">
							<i class="iconfont icon-hongbao3 f18"></i>
							<span class="f12">{pigcms{$config['Currency_symbol']}</span>
							<span class="f20">{pigcms{$detail.redpack_money}</span>
						</span>
					</div>
				</if>
			</div>
		</div>
		<!-- 房源展示区域 -->
		<div class="view-box" v-if="house_info && classify_time_order==1">
			<div class="house_title">{{house_info.village_name}}</div>
			<div class="house_info">
				<div class="item">
					<div class="info_dc">{{house_info.house_type_text}}</div>
					<div class="info_title">户型</div>
				</div>
				<div class="item">
					<div class="info_dc">{{house_info.house_address}}</div>
					<div class="info_title">单元</div>
				</div>
				<div class="item">
					<div class="info_dc">{{house_info.housesize}}m²</div>
					<div class="info_title">面积</div>
				</div>
			</div>
			<div class="house_no">
				<span class="title">房号</span>
				<span class="value">{{house_info.room_number}}</span>
			</div>
			<div class="house_no flex flex-align-center">
				<span class="title">位置</span>
				<div class="value">
					<div class="house_address flex flex-align-center flex-space-between">
						<span>{{house_info.village_address}}</span>
						<img @click="toAddress" src="{pigcms{$static_path}classifynew/images/address.png" alt="">
					</div>
				</div>
			</div>
		</div>
		<!-- 平台担保以及运费信息 -->
		<if condition="$detail['is_assure']">
		<div class="weui-flex view-box" style="background:#fff;align-items: center;flex-wrap: wrap;">
			<div class="mod-lv is-hot ml0" style="margin-right: 10px;top:0">平台担保</div>
			<div class="main_color">
				<span class="f12">{pigcms{$config['Currency_symbol']}</span>
				<span class="f20">{pigcms{$detail.assure_money}</span>
			</div>
			<if condition="$detail['cost_price'] gt 0">
			<div style="padding-left:10px" class="order_price c9">
				<span class="f12">原价{pigcms{$config['Currency_symbol']}{pigcms{$detail.cost_price}</span>
			</div>
			</if>
			<div style="padding-left:10px" class="c9">
				<if condition="$detail['is_post_free']">
					<span class="f12">包邮</span>
				<else/>
					<span class="f12">运费</span>
					<span class="f12">{pigcms{$config['Currency_symbol']}{pigcms{$detail.postage}</span>
				</if>
			</div>
		</div>
		</if>
		<if condition='$detail["reward_type"] == 1 || $detail["reward_type"] == 2 || $detail["reward_look"] elt 0'>
			<if condition="$content_label_arr && $content_label">
				<volist name="content_label_arr" id="item">
					<div class="cl mb8 view-box" style="padding-bottom: 0px">
						<volist name="item" id="vo">
							<span class="mod-feed-tag">{pigcms{$vo}</span>
						</volist>
					</div>
				</volist>
			</if>
		</if>
        <article class="view-box detail-box">
			<section>
    			<if condition='$detail["reward_type"] == 1 || $detail["reward_type"] == 2 || $detail["reward_look"] elt 0'>
					<if condition="$detail['address']&&$detail['longitude']">
					<div class="">
						<a class="weui-cell weui-cell_access" style="padding:10px 0px " href="wap.php?g=Wap&c=Classifynew&a=addressinfo&id={pigcms{$_GET['id']}">
							<div class="weui-cell__bd">
								<p>{pigcms{$detail.address}</p>
							</div>
							<div class="weui-cell__ft addressInfo">查看位置</div>
						</a>
					</div>
					</if>
				</if>
				<!-- 自定义表单区域 -->
				<if condition="$content">
					<div class="cl mb8 custom-form">
						<volist name="content" id="vo">
							<!-- <php>dump($vo);</php> -->
							<if condition="!empty($vo['vv']) && is_string($vo['vv'])">
								<span class="block">
									<span class="title">{pigcms{$vo['tn']}</span>
									<span class="value">{pigcms{$vo.vv} {pigcms{$vo.unit|default=''}</span>
								</span>
							</if>
						</volist>
					</div>
				</if>
				<!-- 详情区域 -->
				<div class="desc">{pigcms{$detail.description|htmlspecialchars_decode}</div>
				<p class="cl feed-preview-pic feed_inview">
					<volist name="imglist" id="vo">
						<span class="imgloading">
							<img src="{pigcms{:replace_file_domain($vo)}" alt=""/>
						</span>
					</volist>
				</p>
            </section>
			<!-- 点赞 浏览区域 -->
    		<if condition='$detail["reward_type"] == 1 || $detail["reward_type"] == 2 || $detail["reward_look"] elt 0'>
				<div class="secp">
					<span href="javascript:void(0);" class="v_tool opt-item item-praise praise" data-id="{pigcms{$detail.id}" data-href="{pigcms{:U('collectOpt',array('vid'=>$detail['id']))}">
						<i id="praise_{pigcms{$detail.id}" class="hide pr-1 vm iconfont icon-jinlingyingcaiwangtubiao24"></i>
						<span class="heart <if condition="$classify_usercollect_info">heartAnimation</if>" id="heart"></span>
						<span class="x-praise-num" id="praises_{pigcms{$detail.id}" style="margin-top: 2px;display: inline-block;">{pigcms{:count($usercollect_info_list)}</span>
					</span>
					<if condition="$detail['shares']">
						<span class="v_tool we_share y flex flex-align-center" data-id="{pigcms{$detail.id}"><img src="{pigcms{$static_path}classifynew/images/share.png" alt="" style="wdith: 20px;height:12px"> <span class="ml-5 fz-12">分享 {pigcms{$detail.shares}</span></span>
					</if>
					<span style="margin-right: 10px" class="v_tool y flex flex-align-center"><img src="{pigcms{$static_path}classifynew/images/look.png" alt="" style="wdith: 20px;height:12px"> <span class="ml-5 fz-12">浏览量 {pigcms{$detail.views}</span></span>
				</div>
				<div class="r" id="r_{pigcms{$detail.id}" <if condition="!$usercollect_info_list">style="display:none"</if>></div>
				<div class="cmt-wrap" id="cmt_wrap_{pigcms{$detail.id}" <if condition="!$usercollect_info_list">style="display:none"</if>>
					<div class="like cl">
						<span class="collect" id="praise_list_{pigcms{$detail.id}">
							<a class="collect-sum flex flex-align-center">
								<img src="{pigcms{$static_path}classifynew/images/collect.png" alt="">
								<span class="ml-5" id="praises2_{pigcms{$detail.id}">{pigcms{:count($usercollect_info_list)}</span>
							</a>
							<volist name="usercollect_info_list" id="vo" key="index">
								<a data-uid="{pigcms{$vo.uid}" class="img-box"><img style="z-index:{pigcms{:count($usercollect_info_list) + 1 - $index}" class="uavatar" src="{pigcms{$vo.avatar|replace_file_domain|default='./static/images/user_avatar.jpg'}" /></a>
							</volist>
						</span>
					</div>
				</div>
			</if>
		</article>
    	<?php if($detail["reward_type"] == 1 || $detail["reward_type"] == 2 || $detail["reward_look"] <= 0){ ?>
			<div class="weui-cells border_none view-box">
				<div class="weui-cell weui-cell_access">
					<div class="weui-cell__bd">
						<p style="font-size: 16px;font-weight: bold;color: #000000;">{pigcms{$detail.lxname}</p>
						<p style="font-size: 12px;font-weight: 500;color: #FD5A13;margin-top:5px">联系我时，请说是在【{pigcms{$config.classify_title}】看到的</p>
					</div>
					<div class="weui-cell__ft weui-grids-nob p0" @click="toTel">
						<a class=" color-red" style="width: 35px;height:35px;display:block">
							<img style="width: 100%;height:100%" src="{pigcms{$static_path}classifynew/images/phone.png" alt="">
						</a>
					</div>
				</div>
	        </div>
			<div class="weui-cells border_none view-box">
				<a class="weui-cell weui-cell_access" href="{pigcms{:U('member',array('uid'=>$user['uid']))}">
					<div class="weui-cell__hd" style="position: relative;margin-right: 10px;">
                        <php>if ($user['avatar']) {</php>
                            <img src="{pigcms{:replace_file_domain($user['avatar'])}" class="v_head"/>
                        <php>} else {</php>
                            <img src="./static/images/user_avatar.jpg" class="v_head"/>
                        <php>}</php>
					</div>
					<div class="weui-cell__bd">
						<p style="font-size: 16px;font-weight: bold;color: #000000;">{pigcms{$user.nickname}</p>
						<p style="font-size:12px;color:#A6A6A5;margin-top: 5px">已发布{pigcms{$user_input_count}条</p>
					</div>
					<div class="f13 iconfont icon-jinrujiantou"></div>
				</a>
	        </div>
			<if condition="$detail['redpack_count']">
				<div class="view-box hot-box">
					<div class="weui-cells__title hot_title">
						<span>已抢<em class="color-red">{pigcms{$detail.redpack_count_get}</em>/{pigcms{$detail.redpack_count}份</span>
						<if condition="$detail['redpack_count_get'] gt 0">
							<a class="y" style="font-size: 13px;font-weight: 500;color: #A3A3A3;" href="{pigcms{:U('hongbao_list',array('id'=>$detail['id']))}">看看大家的手气<i class="f13 iconfont icon-jinrujiantou"></i></a>
						</if>
					</div>
					<div class="weui-cells after_none" id="hong_preview_list">
						<volist name="redpack_list" id="vo">
							<div class="weui-cell cell_hong_list">
								<div class="weui-cell__hd">
									<img src="{pigcms{$vo.avatar|replace_file_domain|default='./static/images/user_avatar.jpg'}"/>
								</div>
								<div class="weui-cell__bd">
									<p>{pigcms{$vo.nickname}</p>
								</div>
								<div class="weui-cell__ft color-red">{pigcms{$vo.money}{pigcms{$config['Currency_txt']}</div>
							</div>
						</volist>
					</div>
				</div>
			</if>
			<div class="view-box">
				<div class="weui-cells__title weui_title flex flex-align-center" style="margin-bottom: 10px;justify-content: space-between;">
					<span class="font-title">评论</span> 
					<a class="comment c9" id="comment_{pigcms{$detail.id}" data-id="{pigcms{$detail.id}" data-multi="1" style="padding: 0">
						我要评论<i class="f13 iconfont icon-jinrujiantou"></i>
					</a>
				</div>
				<div class="weui-panel_access mt0 after_none">
					<div class="weui-panel__bd comment_ul" id="comment_ul_{pigcms{$detail.id}" data-id="{pigcms{$detail.id}"></div>
				</div>
			</div>
			<if condition="$detail['redpack_count'] && ($detail['redpack_count'] - $detail['redpack_count_get'] gt 0) && !$get_redpack">
				<div class="bottom_fix"></div>
				<div class="fix-bottom" style="z-index:1000">
					<a href="javascript:;"  class="weui-btn is-red" onclick="focusHongBao();">抢红包</a>
				</div>
				<div class="hong_res animated zoomIn" id="hong_res">
					<a class="hong_close"><i class="iconfont icon-guanbijiantou f22"></i></a>
					<div class="hong_res_wrap">
						<div class="hong_res_head">
							<div class="hong_res_head_in">
								<img src="{pigcms{$user.avatar|default='./static/images/user_avatar.jpg'}"/>
							</div>
						</div>
						<div class="hong_res_cnt">
							<div class="hong_res_box">
								<p>{pigcms{$user.nickname}</p>
								<p>埋了一个红包</p>
							</div>
							<div class="hong_res_list">
								<div class="send_title"></div>
								<div class="hong_tip">恭喜您获得</div>
								<div class="money_bg">
									<p class="hong_money">
										<i>&yen;</i>
										<span id="hong_size"></span>
										<em>{pigcms{$config['Currency_txt']}</em>
									</p>
								</div>
								<a href="{pigcms{:U('My/my_money')}" class="sub_title">红包已自动放入您的余额内</a>
							</div>
						</div>
						<div class="view_oth">
							<a href="{pigcms{:U('hongbao_list',array('id'=>$detail['id']))}">去看看大家的手气</a>
						</div>
						<div class="sub_bg"></div>
					</div>
				</div>
				<div class="hong_res hong_box" id="hong_box">
					<div class="hong_box_main zoomIn animated ">
						<div class="hong_box_title">
							<div class="send_title"></div>
							<div class="hong_star"></div>
							<div class="hong_box_showname">
								<p>总计{pigcms{$detail.redpack_money}{pigcms{$config['Currency_txt']}</p>
							</div>
							<div class="hong_btn animated" id="hong_btn" onclick="showHongBox(this);">
								<div class="hong_btn_mask"></div>
								<a href="javascript:;"> </a>
							</div>
						</div>
						<div class="hong_from">
							<p>{pigcms{$user.nickname}</p>
							<p>埋了一个红包</p>
						</div>
						<div class="view_oth">
							<p>领取的红包将存入您的余额</p>
						</div>
						<div class="sub_bg"></div>
					</div>
				</div>
				<!--div class="none">
					<audio id="media" preload="preload"><source src="source/plugin/xigua_hb/static/img/haozi.mp3" type="audio/mpeg" /></audio>
				</div-->
				<script>
					var HAS_QIANG = {pigcms{:intval($get_redpack)}, Qlock =0, hbareaallow=1;
					function showHongBox(obj) {
						$('#wechat-mask').hide();
						if(Qlock || HAS_QIANG){ console.log('false'); return false;}
						Qlock = 1;
						$.ajax({
							//success|0.24||
							type: 'post',
							url: '{pigcms{:U('get_hongbao',array('id'=>$detail['id']))}',
							success: function (data) {
								if(null==data){ tip_common('error|'+ERROR_TIP); return false;}
								if(data.indexOf('success')!==-1){
									HAS_QIANG = 1;
									$('#hong_box').removeClass('show').hide();
									$('#hong_res').show();
									$('#hong_size').html(data.split('|')[1]);
									$('.fix-bottom').hide();
								}else{
									$('#hong_box').removeClass('show').hide();
									tip_common(data);
								}
							},
							error: function () {
							}
						});
					}

					function focusHongBao(){
						if(!HAS_QIANG) {
							$.ajax({
								type: 'post',
								url: '{pigcms{:U('focus_hongbao',array('id'=>$detail['id']))}',
								success: function (data) {
									if(null==data){ tip_common('error|'+ERROR_TIP); return false;}
									if(data.indexOf('success')!==-1){
										$('#hong_box').addClass('show').show();
									}else{
										$('#hong_box').removeClass('show').hide();
										tip_common(data);
									}
								}
							});
						}else{
							$.toast('您已经抢过了', 'error');
						}
					}
				</script>
			</if>

		<?php }else{ ?>
			<div class="weui-cells border_none">
				<div class="weui-cell weui-cell_access" style="text-align: center; justify-content: center; -webkit-justify-content: center;">
					 ————剩余信息需要打赏后观看————
				</div>
				<a href="javascript:fabu_reward_pay('{pigcms{$detail.id}');" class="weui-btn weui-btn_primary"  style="width: 80%;">立即打赏{pigcms{$config['Currency_symbol']}{pigcms{$detail.reward_look}</a>
	        </div>
		<?php } ?>

		<div class="footer_fix"></div>
		<div class="view_bottom weui-flex border_top">
			<div class="view_bottom_z">
				<a href="{pigcms{:U('index')}" class="weui-tabbar__item ">
					<span style="display: inline-block;position: relative;">
						<img src="{pigcms{$static_path}classifynew/images/home.png"/>
					</span>
					<p class="weui-tabbar__label">首页</p>
				</a>
			</div>
			<div class="view_bottom_z">
				<a href="{pigcms{:U('fabu')}" class="weui-tabbar__item">
					<span style="display: inline-block;position: relative;">
						<img src="{pigcms{$static_path}classifynew/images/fabu.png"/>
					</span>
					<p class="weui-tabbar__label">发布</p>
				</a>
			</div>
			<if condition="!$is_weixin_browser && !$is_app_browser">
				<div class="view_bottom_z">
					<a href="javascript:;" class="weui-tabbar__item we_share" data-id="{pigcms{$detail.id}">
						<span style="display:inline-block;position:relative;">
							<img src="{pigcms{$static_path}classifynew/images/fenxiang.png"/>
						</span>
						<p class="weui-tabbar__label">分享</p>
					</a>
				</div>
			</if>
			<if condition="$is_weixin_browser || $is_app_browser">
				<div class="view_bottom_z">
					<a href="javascript:;" class="weui-tabbar__item view_ctrl" data-id="{pigcms{$detail.id}">
						<span style="display: flex;justify-content: center;align-items: center;height: 20px;">
							<i class="iconfont icon-gengduo1 f20" style="color: #474444"></i>
						</span>
						<p class="weui-tabbar__label">更多</p>
					</a>
				</div>
			</if>
			<!--<div class="weui-flex__item view_bottom_y">
				<if condition="$config['is_im']">
					<a href="javascript:;" id="contactBtn" data-self="<if condition="$user['uid'] eq $user_session['uid']">1<else/>0</if>">联系 TA</a>
				<else/>
					<a href="tel:{pigcms{$detail.lxtel}"><i class="iconfont icon-unie607"></i> 拨打电话</a>
				</if>
			</div>-->
    		<if condition='$detail["reward_type"] == 1 || $detail["reward_type"] == 2 || $detail["reward_look"] elt 0'>
			<div class="weui-flex__item view_bottom_y foot_dobble weui-flex" >
				<?php if(($detail['is_assure'] && $detail['assure_money']) || ($detail['is_post_free'] == 0 && $detail['postage'] > 0)){ ?>
					<a href="javascript:;" class="weui-btn weui-btn_primary assure_money_contact" id="contactBtn" data-self="<if condition="$user['uid'] eq $user_session['uid']">1<else/>0</if>">联系 TA</a>
					<?php if($classify_order_info){ ?>
					<a href="javascript:;" class="weui-btn weui-btn_primary selled right-a" >已售</a>
					<?php }else{ ?>	
					<a href="javascript:;" class="weui-btn weui-btn_primary go_pay right-a">马上购买</a>
					<?php } ?>
				<?php }else{ ?>
					<a href="javascript:;" class="weui-btn weui-btn_primary first-btn contact" id="contactBtn" data-self="<if condition="$user['uid'] eq $user_session['uid']">1<else/>0</if>">联系 TA</a>
				<?php } ?>
			</div>
			<else/>
			<div class="weui-flex__item view_bottom_y foot_dobble weui-flex" >
				<a href="javascript:fabu_reward_pay('{pigcms{$detail.id}');" class="weui-btn weui-btn_primary contact" >打赏后查看</a>
			</div>
			</if>
        </div>
	<!-- vue模块start -->
	<van-popup v-model="popupShow" position="bottom" round @closed="popupClosed" :close-on-click-overlay="!btn_loading && !is_pay">
		<div class="popup-box">
			<div class="title">购买套餐后查看</div>
			<div class="trip">购买套餐后，在有效期内免费查看</div>
			<div class="package-box">
				<div class="package-item" v-for="(item,index) in packageList" :class="packageIndex === index?'package-item-active':''" :key="index" @click="sclectPackage(item,index)">
					<div class="day">{{item.name}}</div>
					<div class="price">{pigcms{$config['Currency_symbol']}{{item.price}}</div>
				</div>
			</div>
			<van-button class="mt-10" type="primary" color="#ff4d00" :block="true" :icon="is_pay?'passed':''" @click="packagePayment" loading-text="支付中..." :loading="btn_loading">{{loadingText}}</van-button>
		</div>
	</van-popup>
	<div class="pay">
		<van-dialog v-model="dialogShow" :title="dialogTitle" show-cancel-button @confirm="pay_confirm" @cancel="pay_cancel" :confirm-button-color="confirmButtonColor" :confirm-button-text="confirmButtonText" :cancel-button-text="cancelButtonText">
			<div class="pay-box" v-if="pay_order && pay_order.info">
				<div class="content-wrapper"><div class="tip_info_list">账户余额（{pigcms{$config['Currency_txt']}）：<span class="money_info">{pigcms{$config['Currency_symbol']}{{pay_order.info.now_money}}</span></div>
				<div class="tip_info_list" >{{pay_desc1}}（{pigcms{$config['Currency_txt']}）：<span class="money_info" style="color: #ff4d00">{pigcms{$config['Currency_symbol']}{{pay_order.info.reward_money}}</span></div></div>
				<div v-if="pay_order.error == 3" class="tip_info_list" >{{pay_desc2}}（{pigcms{$config['Currency_txt']}）：<span class="money_info" style="color: #FF658E;">{pigcms{$config['Currency_symbol']}{{pay_order.info.difference}}</span></div></div>
			</div>
		</van-dialog>
	</div>
	<!-- vue模块end -->
	</div>
	<div class="cl footer_fix"></div>
	<!--分享图片 -->
	<script type="text/javascript">
	var share_img="<if condition='!empty($imglist)'>{pigcms{:replace_file_domain($imglist['0'])}<elseif condition="$config['wechat_share_img']" />{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>";
	</script>
	<script type="text/javascript">
		window.shareData = {
			"moduleName":"Home",
			"moduleID":"0",
			"imgUrl": share_img,
			"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Classifynew/view',array('id'=>$detail['id']))}",
			"tTitle": "{pigcms{$page_title}",
			"tContent": "{pigcms{$page_share_content|default=$page_title}"
		};
	</script>
	{pigcms{$shareScript}
	<if condition="$is_app_browser">
		<script type="text/javascript">
			if("{pigcms{$app_browser_type}" == 'android'){
				window.lifepasslogin.shareLifePass("{pigcms{$page_title}", "{pigcms{$page_share_content|default=$page_title}",share_img, "{pigcms{$config.site_url}{pigcms{:U('Classifynew/view',array('id'=>$detail['id']))}");
			} else {
				iosCommon.iosFunction('shareLifePass', {name:'shareLifePass', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
			}
		</script>
	</if>
	<include file="footer"/>
    <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js"></script>
	<if condition="$config['is_demo_domain']">
		<script>
			var isShow = hb_getcookie('classifyViewDemoTip');
			if(!isShow){
				demoDomain_tip("<p style='text-align: left;'>1.内容发布者可以选择置顶信息、刷新信息(<span style='font-size:12px;'>系统后台可单独设置刷新信息是否收费</span>)</p><p style='text-align: left;'>2.发布者还可以设置查看信息抢红包(<span style='font-size:12px;'>抢到的红包会存入平台余额，平台可设置提现最低金额</span>)</p>", "内容页面使用提醒");
				hb_setcookie('classifyViewDemoTip','1',86400*365);
			}
		</script>
	</if>

	<script>
		var _this = '';
		// 实例化vue
		var app = new Vue({
			el: '#app',
			data: {	
				popupShow: false,//套餐弹出框
				packageIndex: 0,//套餐选中索引
				packageList: [],//套餐列表
				btn_loading: false,//支付加载中
				dialogShow: false,//支付弹窗
				is_pay: false, //是否支付
				loadingText: '支付', //支付文案
				house_info: '{pigcms{$house_info}', //房源信息
				pay_desc1: '账户余额',
				pay_desc2: '打赏金额',
				confirmButtonText: '',
				cancelButtonText: '',
				pay_order: null, //订单信息
				dialogTitle: '打赏', //弹窗框标题
				has_buy: '{pigcms{$detail.has_buy}',// 是否需要套餐支付
				pay_type: '',
				classify_time_order: 0,
				confirmButtonColor: '#FF4D00',
			},
			created() {
				$("#app").show();
				_this = this;
				this.classify_time_order = "{pigcms{:C('customization.classify_time_order')}";

				if(this.classify_time_order == 1){
					if(this.house_info){
						this.house_info = JSON.parse(this.house_info)
					}
					this.getClassifyMenuList();
				}
			},
			methods: {
				getClassifyMenuList(){
					v20Axios('/classify/api.ClassifyMenuOrder/classifyMenuList',{classify_id: '{pigcms{$detail.cid}'},res=>{
						if(res instanceof Object){
							this.packageList = res && res.classify_detail_list;
						}
                	})
				},
				//打开弹窗
				dialogOpen(){
					this.popupShow = true
				},
				sclectPackage(item,index){
					if(this.btn_loading){
						return
					}
					this.packageIndex = index;
				},
				//套餐支付点击事件
				packagePayment(){
					_this.pay_type = 'package_order'
					this.dialogTitle = '购买' + this.packageList[_this.packageIndex].name;
					let params = {
						menu_detail_id: this.packageList[_this.packageIndex].id
					}
					v20Axios('/classify/api.ClassifyMenuOrder/verifyUserMoney',params,res=>{
						if(res.error_msg){
							vant.Dialog.confirm({
								title: '提示', 
								message: res.error_msg,
								confirmButtonColor: this.confirmButtonColor,
								confirmButtonText: '去充值',

							}).then(()=>{
								location.href = "{pigcms{:U('My/my_money')}";
							}).catch(()=>{

							})
							return
						}
						this.pay_order = {info: {
							reward_money: res.classify_order_price,
							now_money: res.now_money
						}}
						_this.pay_desc1 = '支付金额'
						_this.dialogShow = true;
					},err=>{
						this.btn_loading = false
						vant.Dialog.alert({
							title: '提示', 
							message: err.msg,
							confirmButtonColor: this.confirmButtonColor,
							confirmButtonText: '确定',
						})
					})
				},
				//支付确定事件
				pay_confirm(){
					var look_reward_pay = "{pigcms{:U('Classify/look_reward_pay')}";
					let input_id = '{pigcms{$_GET['id']}'
					//余额不足
					if(this.pay_order && this.pay_order.error == 3){
						location.href = "{pigcms{:U('My/my_money')}";
						return
					}
					//去支付套餐
					if(this.pay_type == 'package_order'){
						this.btn_loading = true;
						v20Axios('/classify/api.ClassifyMenuOrder/addOrder',{menu_detail_id: this.packageList[this.packageIndex].id},res=>{
							this.btn_loading = false
							this.is_pay = true;
							this.loadingText = '支付成功'
							setTimeout(() => {
								this.popupShow = false;
							}, 2000);
						},err=>{
							this.btn_loading = false
							vant.Dialog.alert({
								title: '提示', 
								message: err.msg,
								confirmButtonColor: this.confirmButtonColor,
								confirmButtonText: '确定',
							})
						})
						return
					}
					//去打赏
					if(this.pay_order && this.pay_order.error == 5){
						$.post(look_reward_pay, {'id': input_id}, data=> {
							if (data.error == 3) {
								this.dialogTitle = data.msg;
								this.pay_desc1 = '打赏金额'
								this.pay_desc2 = '还需充值'
								this.confirmButtonText = '去充值'
								this.cancelButtonText = '取消'
								this.pay_order = data
								this.dialogShow = true;
							} else if (data.error == 1 || data.error == 2) {
								vant.Dialog.alert({
									message: data.msg,
									confirmButtonText: '确定',
									confirmButtonColor: this.confirmButtonColor,
								}).then(() => {
									window.location.href = window.location.href;
								});
							} else {
								vant.Dialog.alert({
									message: data.msg,
									confirmButtonText: '确定',
									confirmButtonColor: this.confirmButtonColor,
								}).then(() => {

								});
							}
						}, 'json');
						return
					}
				},
				pay_cancel(){

				},
				//动画结束
				popupClosed(){
					if(this.is_pay){
						location.reload(true)
					}
				},
				toTel(){
					if(this.has_buy){
						this.dialogOpen();
						return false;
					}
					location.href="tel:{pigcms{$detail.lxtel}"
				},
				toAddress(){
					location.href = `wap.php?g=Wap&c=Classifynew&a=addressinfo&id={pigcms{$_GET['id']}&lat=${this.house_info.lat}&lng=${this.house_info.lng}&address_name=${this.house_info.village_address}`
				}
			}
		})
	</script>

	<script>
		load_common_list('{pigcms{:U('getReplyList',array('vid'=>$detail['id']))}', 'comment_ul_{pigcms{$detail.id}');
		var cpage = 1;
		var is_im = "{pigcms{$is_im}"
		$(document).on('click', '#comment_ul_more', function(){
			$('#comment_ul_more').remove();
			cpage ++;
			load_common_list('{pigcms{:U('getReplyList',array('vid'=>$detail['id']))}&page='+cpage, 'comment_ul_{pigcms{$detail.id}');
		});
		$(document).on('click', '#contactBtn', function(){
			if($(this).data('self') == '1'){
				tip_common('error|不能和自己交流');
				return false;
			}
			// 需要购买套餐
			if(_this.has_buy){
				_this.dialogOpen();
				return false;
			}
			var act = [];
			act.push({
				text: '电话联系', onClick: function () {
					location.href = 'tel:{pigcms{$detail.lxtel}';
				}
			});
			if(is_im == 1){
				act.push({
					text: '在线交流', onClick: function () {
						hb_jump("{pigcms{:U('My/go_im',array('hash'=>'group_user'.$user['uid'],'title'=>urlencode($user['nickname'])))}");
					}
				});
			}
			$.actions({
				title: '联系 TA',
				actions: act
			});
		});
		$(function () {
			if(localStorage.getItem('wetip_{pigcms{$detail.id}')){
				$('.we_share').trigger('click');
				localStorage.removeItem('wetip_{pigcms{$detail.id}');
			}
		});
		function jump_download() {
			$.confirm("", function() {
				window.location.href = '';
			}, function() {});
			return false;
		}	

		$(".go_pay").click(function(){
			location.href = "/wap.php?g=Wap&c=Classify&a=order&classify_userinput_id="+"{pigcms{$_GET['id']}";
		});
		var click = false;
		function fabu_reward_pay(type) {
			let input_id = '{pigcms{$_GET['id']}'
	        if (click) return false;
	        click = true;
	        setTimeout(function () {
	            if (click) {
	                console.log('change')
	                click = false;
	            }
	        }, 2000);
	        var look_reward_pay_order = "{pigcms{:U('Classify/look_reward_pay_order')}";
			let params = {
				'id': input_id,
			}
			_this.pay_type = 'look_reward_pay_order'
	        $.post(look_reward_pay_order, params, function (data) {
	            console.log('支付信息-》  ', data)
				_this.pay_order = data;
	            click = false;
				//打赏支付逻辑
	            if (data.error == 3) {
					_this.dialogTitle = data.msg;
					_this.pay_desc1 = '打赏金额'
					_this.pay_desc2 = '还需充值'
					_this.confirmButtonText = '去充值'
					_this.cancelButtonText = '取消'
					_this.dialogShow = true;
	            } else if (data.error == 1 || data.error == 2) {
					vant.Dialog.alert({
						message: data.msg,
						confirmButtonText: '确定',
						confirmButtonColor: _this.confirmButtonColor,
					}).then(() => {
						window.location.href = window.location.href;
					});
	            } else if (data.error == 5) {
					_this.dialogTitle = '打赏';
					_this.pay_desc1 = '打赏金额'
					_this.confirmButtonText = '立即支付'
					_this.cancelButtonText = '取消'
					_this.dialogShow = true;
	            } else {
	                if (data.code == 2) {
						vant.Dialog.alert({
							message: '请先登录',
							confirmButtonText: '去登录',
							confirmButtonColor: _this.confirmButtonColor,
						}).then(() => {
							location.href = "{pigcms{:U('Login/index')}";
						});
	                } else {
						vant.Dialog.alert({
							message: data.msg,
							confirmButtonText: '确定',
							confirmButtonColor: _this.confirmButtonColor,
						}).then(() => {

						});
	                }
	            }
	        }, 'json');
	    }

	</script>
</body>
</html>
