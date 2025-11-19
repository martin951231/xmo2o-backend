<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config.employee_alias}列表</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210">
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/list.css?210"/>
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/index.css?1"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
		<script type="text/javascript">
			var open_extra_price =Number("{pigcms{$config.open_extra_price}");
			var extra_price_name ="{pigcms{$config.extra_price_alias_name}";
			var location_url = "{pigcms{:U('Employee/collect_ajax_list', array('keyword' => $keyword))}";
			var now_cat_url="<if condition="!empty($now_cat_url)">{pigcms{$now_cat_url}<else/>-1</if>";
			var now_area_url="<if condition="!empty($now_area_url) && $all_area_list">{pigcms{$now_area_url}<else/>-1</if>";
			var now_sort_id="<if condition="!empty($now_sort_array) AND $config.open_default_sort eq 0 AND $config.open_group_default_sort eq 0">{pigcms{$now_sort_array.sort_id}<else/>defaults</if>";
			<if condition="$long_lat">var user_long = "{pigcms{$long_lat.long}",user_lat = "{pigcms{$long_lat.lat}";<else/>var user_long = '0',user_lat  = '0';</if>

            var employee_alias = "{pigcms{$config.employee_alias}";
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}employee/js/dropdown.js?t={pigcms{:time()}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}employee/js/employee_collect_list.js?t={pigcms{:time()}" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
		<style>
            .btn-tip {
                border: 1px solid #f1f1f1;
                padding: 1px 3px;
                text-align: center;
                line-height: 1.5rem;
                border-radius: 0.3rem;
                float: left;
                margin-bottom: 10px;
                margin-right: 10px;
                min-width: 4.5rem;
            }
            .btn-tip.active {
                background-color: #06c1ae;
                color: white;
            }
            .all-tip {
                border: 1px solid #f1f1f1;
                padding: 1px 3px;
                text-align: center;
                line-height: 1.5rem;
                border-radius: 0.3rem;
                float: left;
                margin-top: 8px;
                margin-left: 42%;
                min-width: 4.5rem;
            }
            .all-tip.active {
                background-color: #06c1ae;
                color: white;
            }
            .tip-content {
                padding-left: 10px;
            }
            .clear-both-float {
                clear: both;
            }

            .cat-li:first-child {
                border-top: none !important;
                border-bottom: none !important;
            }
            .cat-li {
                border-top: 1px solid #f1f1f1;
                border-bottom: none !important;
            }
            .screen-wrapper {
                display: none;
            }
            .screen-wrapper.active {
                display: block;
            }
            .btn-chongzhi {
                float: left;
                width: 50%;
                text-align: center;
                border-right: 1px solid #f1f1f1;
            }
            .btn-finish{
                color: #06c1ae;
                text-align: center;
            }
            .btn-info{
                position: absolute;
                bottom: 0;
                width: 100%;
                background-color: white;
            }
            .title_change {
                height: 50px;
                border-bottom: 1px solid #c6c0b3;
                font-size: 18px;
                text-align: center;
            }
            .title_change > ul{
                height: 50px;
            }

            .title_change > ul>li{
                line-height: 50px;width: 50%;
                text-align: center;
            }

            .title_change > ul>.active{
                color: #06c1ae;
            }
            .title_change > ul>.active div{
                height: 47px;
                width: 30%;
                margin: 0 auto;
                border-bottom:3px solid #06c1ae;
                color: #06c1ae;
            }
            .title_boder {
                border-right: 2px solid #edebeb;
            }
            .slider {
                background-color: white;
                border-bottom: 1px solid #edebeb;
                margin-bottom: 0px;
            }
            .swiper-container {
                margin: 0 auto;
                position: relative;
                overflow: hidden;
                -webkit-backface-visibility: hidden;
                -moz-backface-visibility: hidden;
                -ms-backface-visibility: hidden;
                -o-backface-visibility: hidden;
                backface-visibility: hidden;
                z-index: 1;
            }

            element.style {
                height: 168px;
                cursor: -webkit-grab;
            }
            .swiper-container {
                margin: 0 auto;
                position: relative;
                overflow: hidden;
                -webkit-backface-visibility: hidden;
                -moz-backface-visibility: hidden;
                -ms-backface-visibility: hidden;
                -o-backface-visibility: hidden;
                backface-visibility: hidden;
                z-index: 1;
            }
            .swiper-container, .swiper-wrapper, .swiper-slide {
                width: 100%;
                height: 100%;
            }
        </style>
	</head>
	<body>
		<section class="searchBar pageSliderHide <if condition="!$is_weixin_browser">wap</if>">
			<div class="searchBox">
				<form id="search-form" action="" method="">
					<input type="search" value="{pigcms{$keyword}" id="keyword" name="keyword" placeholder="搜索收藏的{pigcms{$config['employee_alias']}" autocomplete="off"/>
				</form>
			</div>
			<div class="voiceBtn"></div>
		</section>
		<style>
			.pin_style{
				background-repeat: no-repeat;
				background-image:url({pigcms{$static_path}images/pin.png);
				width:100%;
				height:100%;
				position: absolute;
			}
			.line_m{text-decoration:line-through;display: inline;color: #ccc}
			.s_name{
                float: left;
				width: 25%;
                overflow: hidden;
                text-overflow:ellipsis;
                white-space: nowrap;
			}
            .txt_info {
                float: left;
                width: 30%;
                overflow: hidden;
                text-overflow:ellipsis;
                white-space: nowrap;
            }
            .txt_len_info {
                float: left;
                width: 45%;
                overflow: hidden;
                text-overflow:ellipsis;
                white-space: nowrap;
            }

            .clear_both_info {
                clear: both;
            }
			
			.discount{
				border: 1px solid #eda4a4;
				color: #cc0000;
				background-color: #fae6e6 !important;
			}
			.tag{
				border: 1px solid #fbd3a4;
				color: #f8a100;
				background-color:#fef3e6;
			}
            .goodList li .tip {
                margin-bottom: 2px;
            }
            .line-tip {
                color: #06c1ae;
                border: #06c1ae 1px solid;
                display: inline-block;
                border-radius: 5px;
                padding: 1px 3px;
                margin-top: 5px;
                font-size: 10px;
            }
            .goodList li .title {
                height: 16px;
                line-height: 16px;
            }
            .goodList li .tip .tip-info {
                font-size: 10px;
                max-height: 56px;
                overflow: hidden;
            }
            .juli_txt {
                position: absolute;
                bottom: 5px;
                right: 4px;
                color: #999;
                font-size: 12px;
                z-index: 99999;
            }
            .juli_txt_navigation {
                position: absolute;
                bottom: 30px;
                right: 4px;
                color: #999;
                font-size: 12px;
                z-index: 99999;
            }
            .cursor {
               cursor:pointer;
            }
		</style>
		<div id="container" style="top: 51px;">
			<div id="scroller">
				<div id="pullDown">
					<span class="pullDownIcon"></span><span class="pullDownLabel">下拉可以刷新</span>
				</div>
				<script id="ListBoxTpl" type="text/html">
					{{# for(var i = 0, len = d.list.length; i < len; i++){ }}
						<dd class="link-url" data-url="{{ d.list[i].url }}">
							<div class="dealcard-img imgbox">
								<img src="{pigcms{$config.site_url}/index.php?c=Image&a=thumb&width=276&height=168&url={{ encodeURIComponent(d.list[i].avatar) }}" alt="{{ d.list[i].true_name }}"/>
							</div>
							<div class="dealcard-block-right">
								<div class="brand">
                                    <div class="s_name">{{#if(d.list[i].true_name){}}{{ d.list[i].true_name }}{{# } else { }}{{ d.list[i].nickname }}{{# } }}</div>
                                    {{# if(d.list[i].cat_name){ }}<div class="txt_info">{{ d.list[i].cat_name }}</div>{{# } }}
                                    {{# if(d.list[i].merchant_store_name){ }}<div class="txt_len_info">{{ d.list[i].merchant_store_name }}</div>{{# } }}
                                    {{# if(d.list[i].juli_txt){ }}
                                        <div class="juli_txt_navigation navi cursor" data-lng="{{ d.list[i].long }}" data-lat="{{ d.list[i].lat }}" data-name="{{ d.list[i].address_info }}" data-address="{{ d.list[i].address_info }}">
                                          <em><img src="{pigcms{$static_path}images/dindxqt_11.png" width="18" height="20" style="position: relative; top: 5px;"/></em>
                                        </div>
                                        <span class="juli_txt navi cursor">{{ d.list[i].juli_txt }}</span>
                                    {{# } }}
                                </div>
                                <div class="clear_both_info"></div>

                                <div class="rateInfo" style="margin-top:3px;float:left;">
                                    {{# if(d.list[i].score_average > 0){ }}
                                        <div class="starIconBg">
                                            <div class="starIcon" style="width:{{ d.list[i].score_average/10 *100 +1 }}%"></div>
                                        </div>
                                        <div class="starText" style="line-height: 18px;">{{ d.list[i].score_average / 2 }}</div>
                                    {{# }else{ }}
                                        <span style="color:#999">暂无评分</span>
                                    {{# } }}
                                </div>
                                {{# if(d.list[i].thumb_num > 0){ }}
                                    <div class="title" style="padding-left:10px;line-height: 18px;margin-top: 3px;">点赞{{ d.list[i].thumb_num }}人</div>
                                {{# } }}
                                <div class="clear_both_info"></div>

								<div class="price">
                                    {{# if(d.list[i].circle_name){ }}<div class="txt_info">{{ d.list[i].circle_name }}</div>{{# } }}
                                    {{# if(d.list[i].address_info){ }}<div class="txt_len_info">{{ d.list[i].address_info }}</div>{{# } }}
                                </div>
							</div>
						</dd>
					{{# } }}
				</script>
				<section class="listBox">
					<dl></dl>
					<div class="shade hide"></div>
					<div class="no-deals hide">暂无收藏{pigcms{$config.employee_alias}信息，请前往{pigcms{$config.employee_alias}详情进行收藏！</div>
				</section>
				<div id="pullUp">
					<span class="pullUpIcon"></span><span class="pullUpLabel">上拉加载更多</span>
				</div>
			</div>
		</div>

		<script type="text/javascript">
            $(function(){
                // 语音事件 start
                $('.voiceBtn').click(function(e){
                    $('body').append('<div class="voiceContainer"><div class="close"></div><div class="title">请大声说出您想要找什么</div><div class="content"><div class="tip">点击按钮开始录音</div><div class="btn"></div></div><div class="searchBtm1"></div><div class="searchBtm2"></div></div>');
                    $('.searchBtm1').css({'background-size':$('.searchBtm1').width()+'px '+$('.searchBtm1').height()+'px'});
                    $('.searchBtm2').css({'background-size':$('.searchBtm2').width()+'px '+$('.searchBtm2').height()+'px'});

                    $('.voiceContainer .close').click(function(){
                        if(voiceBtmTimer){
                            clearInterval(voiceBtmTimer);
                        }
                        $('.voiceContainer').remove();
                        if($('.voiceContainer .btn').hasClass('start')){
                            endVoice();
                        }
                    });
                    $('.voiceContainer .btn').click(function(){
                        if($(this).hasClass('start')){
                            $('.voiceContainer .tip').html('点击按钮开始录音');
                            $(this).removeClass('start');
                            clearInterval(voiceBtmTimer);
                            endVoice();
                        }else{
                            beginVoice();
                        }
                    });
                    return false;
                });
                // 微信开始录音
                function beginVoice(){
                    wx.startRecord({
                        success: function (res){
                            $('.voiceContainer .tip').html('点击按钮结束录音');
                            $('.voiceContainer .btn').addClass('start');
                            var voiceBtmNumber = 0;
                            voiceBtmTimer = setInterval(function(){
                                if(voiceBtmNumber%5 < 3){
                                    $('.searchBtm1').css({left:(parseInt($('.searchBtm1').css('left'))-1)+'px'});
                                }else{
                                    $('.searchBtm2').css({left:(parseInt($('.searchBtm2').css('left'))-1)+'px'});
                                }
                                voiceBtmNumber += 1;
                            },15);

                        },
                        fail: function (res){
                            if(res.errMsg == 'startRecord:system permission denied'){
                                motify.log('您拒绝了授权录音');
                            }else{
                                motify.log('未成功调用录音');
                            }
                        },
                        cancel: function(){
                            motify.log('您拒绝了授权录音');
                        }
                    });
                }
                // 微信结束录音
                function endVoice(){
                    if(typeof(voice_type)=='undefined'){
                        voice_type = 'group';
                    }
                    wx.stopRecord({
                        success: function (res) {
                            voiceLocalId = res.localId;
                            // alert(voiceLocalId);
                            /* wx.playVoice({
                                localId: voiceLocalId // 需要播放的音频的本地ID，由stopRecord接口获得
                            }); */
                            wx.translateVoice({
                                localId: voiceLocalId, // 需要识别的音频的本地Id，由录音相关接口获得
                                isShowProgressTips: 1, // 默认为1，显示进度提示
                                success: function (res) {
                                    if(res.translateResult){
                                        res.translateResult = res.translateResult.replace('。','');
                                        // motify.log('<div style="color:#06c1ae;">'+res.translateResult+'</div>正在为您查询...',0); // 语音识别的结果
                                        $('.voiceContainer').remove();

                                        // $('.voiceContainer').parentNode.removeChild($('.voiceContainer'));

                                        var search_txt = res.translateResult;
                                        $('#search-form #keyword').val(search_txt);
                                        location_url ='{pigcms{:U('Employee/collect_ajax_list')}&keyword='+search_txt;
                                        now_page = 0;
                                        getList(false);
                                        // window.location.href = window.location.pathname+'?c=Search&a='+voice_type+'&w='+encodeURIComponent(res.translateResult);
                                    }else{
                                        motify.log('没有听出您说了什么，请重试');
                                    }
                                },fail: function (res) {
                                    motify.log('无法识别该段语音，请重试');
                                }
                            });
                        },fail: function (res) {

                        }
                    });
                }
                // 搜索触发事件
                $('#keyword').bind('input', function(e){
                    var search_txt = $(this).val();
                    location_url ='{pigcms{:U('Employee/collect_ajax_list')}&keyword='+search_txt;
                    now_page = 0;
                    getList(false);
                    $('.no-deals').css('display','none')
                });
                $('#search-form').submit(function(){
                    return false;
                });
            })
            window.shareData = {
                "moduleName":"employee",
                "moduleID":"0",
                "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
                "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Employee/employee_collect_list')}",
                "tTitle": "{pigcms{$config.employee_alias}收藏列表",
                "tContent": "{pigcms{$config.site_name}"
            };
            </script>
            {pigcms{$shareScript}
	</body>
</html>