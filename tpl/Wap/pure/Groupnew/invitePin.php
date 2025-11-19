<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>拼团详情</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/category.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/invitePin.css?t={pigcms{:time()}"/>
        <script type="text/javascript">
            var getShareUrl = "{pigcms{:U('share',array('group_id'=>$now_group['group_id'],'order_id'=>$now_order['order_id']))}";
        </script>
        <style>
            .detail_mask{
                position: fixed;
                top: 0px;
                left: 0px;
                background: rgba(0,0,0,0.7);
                z-index: 1088;
                width: 100%;
                height: 100%;
                display: none;
            }
            .guide {
                position: absolute;
                z-index: 1099;
                width: 100%;
                display: none;
            }
        </style>
	</head>
	<body>
        <div class="detail_mask cursor"></div>
        <div class="guide none"><img src='{pigcms{$static_path}images/guide1.png'/></div>
		<div class="pageBg"></div>
		<scrollView class="page y " id="two_category_page">
			<div class="container">
				<div class="pinDetailBox">
					<div class="pinDetail">
						<div class="product cf">
							<div class="fl">
								<div class="goods-img">
									<img src="{pigcms{$now_order.list_pic}"/>
								</div>
							</div>
							<div class="fl flex flex-zTopBottom flex-justify goods-desc">
								<div class="cf">
									<div class="fl ellipsis title">{pigcms{$now_group.name} | {pigcms{$now_group.s_name}</div>
									<div class="fl detail-tag link" data-url="{pigcms{:U('detail',array('group_id'=>$now_group['group_id'],'page_num'=>$_GET['page_num']+1))}">详情</div>
								</div>
								<p class="detail-desc">{pigcms{$now_group.intro}</p>
								<div><span class="now-price">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.price}</span><span class="old-price">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.old_price}</span></div>
							</div>
						</div>

						<div class="avatar-list flex flex-zCenter">
                            <volist name="buyer" id="vo">
                                <div class="avatar-img"><img src="<if condition="$vo['avatar'] neq ''">{pigcms{$vo.avatar}<else />{pigcms{$static_public}img/images/nohead.png</if>"><if condition="$vo.is_head gt 0"><span class="user-tag">团长</span><elseif condition="$vo.uid eq $user_session['uid']"/><span class="user-tag">我</span></if></div>
                            </volist>
							<div class="avatar-img empty">？</div>
						</div>

						<div class="time-box">
							还差<i class="person-num">{pigcms{$my_group_join['complete_num']-$my_group_join['num']}</i>人 剩余 <span class="time_d time" data-time="{pigcms{$effective_time}">00:00:00</span> 结束
						</div>

						<div class="share-btn" id="inviteBtn"><button>邀请好友</button></div>

						<p class="text-desc">好东西就是要分享，马上找好友来帮忙</p>
					</div>
				</div>

				<if condition="$category_group_list">
                    <div class="otherGroupBox boxBlock goodListBox list" id="otherGroupBox" style="margin-bottom:0px;">
                        <div class="centerTitle">More Good Life</div>
                        <div class="centerDesc">更多美好生活</div>
                        <scrollView class="goodList">

                            <volist name="category_group_list" id="vo">
                                <div class="goodItem newWin link" data-url="{pigcms{$vo.detail_url}" >
                                    <div class="goodImage">
                                        <img src="{pigcms{$vo['list_pic']}"/>
                                    </div>
                                    <div class="goodName">{pigcms{$vo.name} | {pigcms{$vo.s_name}</div>
                                    <div class="goodPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vo.price}<span class="unit">/份</span><span class="oldPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vo.old_price}</span></div>
                                </div>
                            </volist>
                        </scrollView>
                    </div>
                </if>



				<!-- 蒙层 -->
				<div class="mask"></div>
				<div class="mask-content">
					<div class="pinDetail">
						<div class="product cf">
							<div class="fl">
								<div class="goods-img">
									<img src="{pigcms{$now_order.list_pic}"/>
								</div>
							</div>
							<div class="fl goods-desc">
								<div class="time-box2"><span class="time" data-time="{pigcms{$effective_time}">00:00:00</span></div>
								<div class="rest-box">仅剩 <i class="rest-num">{pigcms{$my_group_join['complete_num']-$my_group_join['num']}</i> 个名额</div>
								<div><span class="now-price">{pigcms{$config['Currency_symbol']}{pigcms{$now_group['price']}</span><span class="old-price">{pigcms{$config['Currency_symbol']}{pigcms{$now_group['old_price']}</span></div>
								<div class="limited-tag">限时抢购</div>
							</div>
						</div>

						<div class="other-box">
							<div class="avatar-list flex flex-zCenter">
                                <volist name="buyer" id="vo">
                                    <div class="avatar-img"><img src="<if condition="$vo['avatar'] neq ''">{pigcms{$vo.avatar}<else />{pigcms{$static_public}img/images/nohead.png</if>"><if condition="$vo.is_head gt 0"><span class="user-tag">团长</span><elseif condition="$vo.uid eq $user_session['uid']"/><span class="user-tag">我</span></if></div>
                                </volist>
								<div class="avatar-img empty">？</div>
							</div>

							<div class="share-btn share_msg" style="padding-top: 40px;" id="inviteBtn"><button>邀请好友 <span class="time" data-time="{pigcms{$effective_time}">00:00:00</span> 后结束</button></div>

							<p class="text-desc">分享3个群，成团率高达98%</p>
						</div>

						
					</div>
				</div>

				
			</div>
		</scrollView>
		<div id="hideLoad" class="hide"></div>

        <script type="text/javascript">
            var is_app_browser = <if condition="$is_app_browser">true<else/>false</if>;
            var app_browser_type = "{pigcms{$app_browser_type}";
        </script>
		<script type="text/javascript">
	            window.shareData = {
	                "moduleName":"Home",
	                "moduleID":"0",
	                "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
	                "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Groupnew/detail',array('group_id'=>$now_order['group_id'],'gid' => $my_group_join['id']))}",
	                "tTitle": "我推荐「 {pigcms{$now_group['merchant_name']} | {pigcms{$now_group['s_name']}」",
	                "tContent": "{pigcms{$config.site_name}"
	            };
		</script>
		{pigcms{$shareScript}
		
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		
		<!-- minirefresh -->
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/minirefresh.min.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}minirefresh/minirefresh.min.js" charset="utf-8"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.js" charset="utf-8"></script>

		<!-- layer -->
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>

        <script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}groupnew/js/invitePin.js?t={pigcms{:time()}" charset="utf-8"></script>

		
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>

		<script type="text/javascript">
			goodList_before()

			$('.mask').height($(window).height() > $('.container').height() ? $(window).height() : $('.container').height());
			$('.mask').on('click', function(){
				$('.mask, .mask-content').hide()
			})

            // if ("{pigcms{$my_group_join['status']}"=='0' && "{pigcms{$effective_time}") {
            //     var h="{pigcms{$effective_time['h']}";
            //     var m="{pigcms{$effective_time['m']}";
            //     var s="{pigcms{$effective_time['s']}";
            //     time = setInterval("run()", 1000);
            //
            //     function run() {
            //         --s;
            //         if (s < 0) {
            //             --m;
            //             s = 59;
            //         }
            //         if (m < 0) {
            //             --h;
            //             m = 59
            //         }
            //         if (h < 0) {
            //             s = 0;
            //             m = 0;
            //             window.location.reload();
            //         }
            //         $('.time-box2').html('<i>' + h + '</i>:<i>' + m + '</i>:<i>' + s + '</i>');
            //         $('.time_d').html('<i>' + h + '</i>:<i>' + m + '</i>:<i>' + s + '</i>');
            //         $('.share_msg button').html('邀请好友 <i>' + h + '</i>:<i>' + m + '</i>:<i>' + s + '</i> 后结束');
            //     }
            // }
		</script>
	</body>
</html>