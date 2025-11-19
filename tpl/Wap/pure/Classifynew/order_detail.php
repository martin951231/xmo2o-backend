<!doctype html>
<html>
<head>
	<include file="header"/>
	<link href="{pigcms{$static_path}shop/css/order_detail.css?11111" rel="stylesheet"/>
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
		.foot_dobble .go_pay{
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

		.mod-feed-tag{margin-bottom: 0;}
	</style>
</head>
<body>
	<div class="weui-pull-to-refresh__layer">
		<div class='weui-pull-to-refresh__arrow'></div>
		<div class='weui-pull-to-refresh__preloader'></div>
		<div class="down">下拉刷新</div>
		<div class="up">释放刷新</div>
		<div class="refresh">正在刷新</div>
	</div>
	<div class="page__bd">
		<if condition="!$is_weixin_browser && !$is_app_browser">
			<header class="x_header bgcolor_11 cl f15">
				<a class="z f14" href="<if condition='$_GET["backurl"]'>javascript:window.location.href='{pigcms{$_GET['backurl']}';<else/>javascript:window.history.go(-1);</if>"><i class="iconfont icon-fanhuijiantou w15"></i>返回</a>
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
				<li><a href="{pigcms{:U('my')}"><i class="vm iconfont icon-xiaolian2 "></i> 我的</a></li>
			</ul>
		</div>
		<div class="weui-cells mt0 before_none after_15">
			<div class="weui-cell">
				<div class="weui-cell__hd" style="position: relative;margin-right: 10px;">
					<a href="{pigcms{:U('member',array('uid'=>$user['uid']))}">
						<img src="{pigcms{$user.avatar|default='./static/images/user_avatar.jpg'}" class="v_head"/>
					</a>
				</div>
				<div class="weui-cell__bd">
					<a class="a da f14" href="{pigcms{:U('member',array('uid'=>$user['uid']))}">{pigcms{$user.nickname}</a>
					<div class="secp">
						<div class="mod-lv is-green ml0">{pigcms{$detail.cat_name}</div>
						<if condition="$detail['topsort']">
							<div class="mod-lv is-star ml0">置顶</div>
						</if>
						<if condition="$detail['redpack_count']">
							<div class="mod-lv is-hot ml0">红包</div>
						</if>
						<span class="c9 f12 inblock">{pigcms{$detail.addtime|date='m-d H:i',###} 发布</span>
					</div>
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

		<if condition="$detail['is_assure']">
			<div class="weui-flex " style="padding-left:10px; padding-top:10px;background:#fff;   align-items: center;flex-wrap: wrap;">
				<div class="mod-lv is-hot ml0" style="margin-right: 10px;">平台担保</div>
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

        <article class="weui-article mt0" style="margin-bottom: 10px;">
			<section style="margin-bottom: 0;">
				<p>{pigcms{$detail.description}</p>
				<if condition="$detail['address']&&$detail['longitude']">
				<div class="">
					<a class="weui-cell weui-cell_access" style="padding:10px 0px " href="wap.php?g=Wap&c=Classifynew&a=addressinfo&id={pigcms{$detail['id']}">
						<div class="weui-cell__bd">
							<p>{pigcms{$detail.address}</p>
						</div>
						<div class="weui-cell__ft">查看位置</div>
					</a>
				</div>
				</if>
				<if condition="$content">
					<div class="cl mb8">
						<volist name="content" id="vo">
							<if condition="!empty($vo['vv']) && is_string($vo['vv'])">
								<span class="block"><span class="main_color">{pigcms{$vo['tn']}：</span>{pigcms{$vo.vv}</span>
							</if>
						</volist>
					</div>
				</if>
				<if condition="$content_label">
					<div class="cl">
						<volist name="content_label" id="vo">
							<span class="mod-feed-tag b-color{pigcms{$key}">{pigcms{$vo}</span>
						</volist>
					</div>
				</if>
				<p class="cl feed-preview-pic feed_inview">
					<volist name="imglist" id="vo">
						<span class="imgloading">
							<img src="{pigcms{$vo}" alt=""/>
						</span>
					</volist>
				</p>
            </section>
			<div class="r" id="r_{pigcms{$detail.id}" <if condition="!$usercollect_info_list">style="display:none"</if>></div>
			<div class="cmt-wrap" id="cmt_wrap_{pigcms{$detail.id}" <if condition="!$usercollect_info_list">style="display:none"</if>>
				<div class="like cl">
					<span class="likeuser z likeinview" id="praise_list_{pigcms{$detail.id}">
						<a class="likeinview_prev"><em id="praises_{pigcms{$detail.id}">{pigcms{:count($usercollect_info_list)}</em><br>藏</a>
						<volist name="usercollect_info_list" id="vo">
							<a><img class="uavatar" src="{pigcms{$vo.avatar|default='./static/images/user_avatar.jpg'}" /></a>
						</volist>
					</span>
				</div>
			</div>
		</article>

		<div class="infor">
            <ul>
                <li class="clr first">
                    <div class="fl match">配送信息</div>
                </li>
                <li class="clr">
                    <div class="fl">配送方式</div>
					<if condition="$order_detail['is_pick_in_store']==1">
                    	<div class="fr">到店自取</div>
                    <else/>
                    	<div class="fr">快递配送</div>
                	</if>
                </li>
               <!--  <li class="clr">
                    <div class="fl">期望时间</div>
                    <div class="fr">2019-01-18 16:14</div>
                </li> -->
				<if condition="$order_detail['is_pick_in_store']!=1">
                <li class="clr">
                    <div class="fl">收货信息</div>
                    <div class="p90">
                        <p>{pigcms{$order_detail['address']}</p>
                        <p>{pigcms{$order_detail['contact_name']} {pigcms{$order_detail['phone']}</p>
                    </div>
                </li>
                <li class="clr">
                    <div class="fl">快递详情</div>
                    <dl class="kd_dl kd_dls">
                        <dd>
                        	<if condition="($order_detail['paid'] == 0)">
                                <h2 class="endt">未付款</h2>
							<elseif condition="($order_detail['logistics_status'] == 1) " />
								<h2 class="endt">已发货：{pigcms{$express_list['name']} <a>{pigcms{$order_detail['express_id']}</a></h2>
                                <p><a href="http://m.kuaidi100.com/index_all.html?type={pigcms{$order_detail['express_type']}&postid={pigcms{$order_detail['express_id']}&callbackurl=<?php echo 'http://'.urlencode($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);?>" target="_blank" >查看物流 ></a></p>
							<elseif condition=" ($order_detail['logistics_status'] == 3)" />
								<h2 class="endt">已收货：{pigcms{$express_list['name']} <a>{pigcms{$order_detail['express_id']}</a></h2>
                                <p><a href="http://m.kuaidi100.com/index_all.html?type={pigcms{$order_detail['express_type']}&postid={pigcms{$order_detail['express_id']}&callbackurl=<?php echo 'http://'.urlencode($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);?>" target="_blank" >查看物流 ></a></p>
							<elseif condition="($order_detail['logistics_status'] == 2) " />
                                <h2 class="endt">暂未发货！</h2>
							</if>
                        </dd>
                    </dl>
                </li>
            	</if>
            </ul>
        </div>

 <!--        <div class="infor">
            <ul>
                <li class="clr first">
                    <div class="fl book">订单信息</div>
                </li>
                <li class="clr">
                    <div class="fl">订单编号</div>
                    <div class="fr">{pigcms{$order_detail['phone']}</div>
                </li>
                <li class="clr">
                    <div class="fl">下单时间</div>
                    <div class="fr">2019-01-18 15:23:59</div>
                </li>
                <li class="clr">
                    <div class="fl">备注信息</div>
                    <div class="fr"></div>
                </li>
            </ul>
        </div> -->

        <div class="infor" style="margin-bottom: 0;">
            <ul>
    	        <li class="clr first">
    	            <div class="fl branch">支付信息</div>
    	        </li>
                <if condition="$order_detail['paid'] == 1">
	                <li class="clr">
	                    <div class="fl">支付时间</div>
	                    <div class="fr">{pigcms{$order_detail['order_time']|date="Y-m-d H:i:s",###}</div>
	                </li>
	                <li class="clr">
	                    <div class="fl">支付方式</div>
	                    <div class="fr">余额支付</div>
	                </li>
	    	        <li class="clr">
	    	            <div class="fl">应收总额</div>
	    	            <div class="p90">
							<p class="e2c">{pigcms{$config['Currency_symbol']}{pigcms{$order_detail['total_price']}</p>        	             
						</div>
	    	        </li>
	    	        <li class="clr">
	                    <div class="fl">平台余额支付</div>
	                    <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_detail['total_price']}</div>
	                </li>
	            <else/>
	    	        <li class="clr">
	                    <div class="fl">未支付</div>
	                </li>
                </if>                               
            </ul>
        </div>
	</div>

	<if condition="$is_weixin_browser">
		<script type="text/javascript">
			window.shareData = {
				"moduleName":"Home",
				"moduleID":"0",
				"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Classifynew/view',array('id'=>$detail['id']))}",
				"tTitle": "{pigcms{$page_title|str_replace=PHP_EOL,'',###}",
				"tContent": "{pigcms{$detail.description|str_replace='<br/>','',###}"
			};
		</script>
		{pigcms{$shareScript}
	</if>
	<if condition="$is_app_browser">
		<script type="text/javascript">
			if("{pigcms{$app_browser_type}" == 'android'){
				window.lifepasslogin.shareLifePass("{pigcms{$page_title}", "{pigcms{$detail.description}", "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>", "{pigcms{$config.site_url}{pigcms{:U('Classifynew/view',array('id'=>$detail['id']))}");
			}
		</script>
	</if>
	<include file="footer"/>
	<if condition="$config['is_demo_domain']">
		<script>
			var isShow = hb_getcookie('classifyViewDemoTip');
			if(!isShow){
				demoDomain_tip("内容发布者可以选择置顶信息、刷新信息(<span style='font-size:12px;'>系统后台可单独设置刷新信息是否收费</span>)，发布者还可以设置查看信息抢红包(<span style='font-size:12px;'>抢到的红包会存入平台余额，平台可设置提现最低金额</span>)", "内容页面使用提醒");
				hb_setcookie('classifyViewDemoTip','1',86400*365);
			}
		</script>
	</if>
	<script>
		load_common_list('{pigcms{:U('getReplyList',array('vid'=>$detail['id']))}', 'comment_ul_{pigcms{$detail.id}');
		var cpage = 1;
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
			var act = [];
			act.push({
				text: '电话联系', onClick: function () {
					location.href = 'tel:{pigcms{$detail.lxtel}';
				}
			});
			act.push({
				text: '在线交流', onClick: function () {
					hb_jump("{pigcms{:U('My/go_im',array('hash'=>'group_user'.$user['uid'],'title'=>urlencode($user['nickname'])))}");
				}
			});
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
	</script>
</body>
</html>
