<!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8" />
		<title>家属出入通知设置</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width" />
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name='apple-touch-fullscreen' content='yes' />
		<meta name="apple-mobile-web-app-status-bar-style" content="black" />
		<meta name="format-detection" content="telephone=no" />
		<meta name="format-detection" content="address=no" />
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css" />
<!--		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/village.css" />-->
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/bbs.css" />
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/LCalendar.css" />
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.cookie.js?210" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
		<style type="text/css">
			#container {
				bottom: -80px;
				top:0
			}
            .fabu-btn{
                -webkit-appearance: none;
                border-radius: 0;
                padding: 0;
                border-spacing: 0px !important;
                outline: medium;
                word-break: break-word;
                border: none;
                list-style: none;
            }
			.page__hd {
				padding: 15px;
			}
			.page__desc {
				margin-top: 4px;
				color: #000;
				text-align: left;
				font-size: 14px;
			}

            .notice-title {
                position: relative;
                font-size: 16px;
                color: black;
                margin-bottom: 10px;
            }
            .notice-title .switch-off, .notice-title .switch-on {
                position: absolute!important;
                right: 0px;
                top: 0px;
            }
            .react {
                margin-bottom: 10px;
                border-bottom: 1px solid #EEE;
                padding: 10px 0;
            }
            .notice-title .name{
                color: #333;
            }
		</style>
	</head>

	<body>
        <if condition="$info">
            <div>
                <div class="page__hd">
                    <p class="page__desc">为了您和您的家属有一个舒适安全的小区居住环境，可选择对象在出入小区或者在小区内的活动位置变化时通知自己。</p>
                </div>
                <div class="village_my">
                    <nav>
                        <section style="background-color: white;padding: 10px;">
                            <dd>
                                <label class="react">
                                    <div class="notice-title all_title">
                                        <p class="name">全选</p>
                                        <span class="switch-<if condition="$now_village_bind['is_all_notice'] neq 1">on<else/>off</if>" data-value="1" data-type='all'></span>
                                    </div>
                                    <volist name="info" id="item">
                                        <div class="notice-title" style="margin-top: 25px;">
                                            <p class="name">{pigcms{$item['name']}</p>
                                            <span class="switch-<if condition="!$item['notice'] || $item['notice']['is_notice'] neq 1 || $now_village_bind['is_all_notice'] neq 1">on<else/>off</if>" data-value="{pigcms{$item['pigcms_id']}" data-type='single'></span>
                                        </div>
                                    </volist>
                                </label>
                            </dd>
                        </section>
                    </nav>
                </div>
            </div>
            <else/>
            <div style="height:100%;text-align: center;padding: 0px; background-color: white;">
                <img style="margin-top: 40%;width: 75%;height: auto;" src="{pigcms{$static_public}images/house/no_data.png?t=01"/>
                <div style="padding: 15px;">抱歉，目前您的账号下暂无绑定家属和租客</div>
            </div>
        </if>
		<script type="text/javascript" src="{pigcms{$static_path}js/LCalendar.js"></script>
		<script src="{pigcms{$static_path}js/exif.js"></script>
		<script src="{pigcms{$static_path}js/imgUpload.js"></script>
        <!-- honeySwitch -->
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_public}js/honeySwitch/honeySwitch.css" />
        <script type="text/javascript" src="{pigcms{$static_public}js/honeySwitch/honeySwitch.js" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript" language="JavaScript">
            $('body').css('height', $(window).height());
            $('.all_notice').click(function () {
                if($(this).is(':checked')){
                    console.log('val',1);
                    $('.react .mt').prop('checked',true);
                } else {
                    console.log('val',0);
                    $('.react .mt').prop('checked',false);
                }
            });


            $(".notice-title span").click(function(){
                var type = $(this).data('type');
                var val = $(this).data('value');
                console.log('type',type);
                console.log('val',val);
                if ($(this).hasClass('switch-on')) {
                    console.log('关了');
                    switch_info(type,val,0)
                } else {
                    console.log('开了');
                    switch_info(type,val,1)
                }
            });


            function switch_info(type,val,is_notice){
                var my_house_tip_ajax = "{pigcms{:U('notice_set')}";
                $.post(my_house_tip_ajax,{type:type,pigcms_id:val,is_notice:is_notice},function(data){
                    if(data.error == 1){
                        layer.alert('切换失败',{
                            offset: ($('body').height() - 150) / 2
                        });
                    } else {
                        if ('all'==type && 1==is_notice) {
                            var ele_index = $(".notice-title span.switch-off");
                            honeySwitch.showOn(ele_index);
                        } else if ('all'==type && 0==is_notice) {
                            var ele_index = $(".notice-title span.switch-on");
                            honeySwitch.showOff(ele_index);
                        } else if ('all'!=type && 0==is_notice) {
                            var ele_index = $(".all_title span.switch-on");
                            honeySwitch.showOff(ele_index);
                        }
                    }
                },'json');
            }



            $('.mt').click(function () {
                if(!$(this).is(':checked')){
                    $('.react .all_notice').prop('checked',false);
                }
            });

		</script>
		{pigcms{$hideScript}

	</body>

</html>