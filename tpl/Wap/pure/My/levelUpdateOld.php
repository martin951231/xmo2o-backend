<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
	<title>{pigcms{:L_("我的X1",['X1'=>$config['vip_card_alias']])}</title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name='apple-touch-fullscreen' content='yes'>
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}my/css/mylevel.css">
    <link rel="stylesheet" href="{pigcms{$static_path}css/swiper.min.css">
	<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/flexible.js"></script>
    <style>

    	body{padding-bottom: 73px;}
    	.card{top: 50%;left: 50%;transform: translate(-50%, -50%);-webkit-transform:translate(-50%, -50%);width: 86%;}
	    .avatar{width: 1.4rem;height: 1.4rem;border: 0.1rem solid rgba(255, 255, 255, 0.2);border-radius: 50%;}

	    .level_box {width: 100%;height: 2.6rem;}
	    .level_box .img-box img{width: 1.2rem;height: 1.2rem;}
	    .level_box .line{width:200%;height: 0.08rem;background:#f0f0f0;top: 0.7rem;left: 0.7rem;z-index: -1;display: block;}
        .level_box .swiper-wrapper .swiper-slide:last-child .line{display: none;}

	    .ad_box{width: 100%;height: 4.3rem;background:#d9b479;border-radius: 10px;overflow: hidden;}
	    
	    .swiper-pagination-bullet {width: 6px;height: 6px;display: inline-block;border-radius: 3px;background: #fff;opacity: .7;}
	    .swiper-pagination-bullet-active {background: #fff;opacity: 1;width: 12px;height: 6px;}

	    button{outline: none;border: none;}
        .swiper-wrapper{height: 100%;}
	    .button-box{position: fixed;bottom: 0;left: 0;right: 0;width: 100%;z-index: 99;}

	    .arrow{width: 0.2rem;height: 0.28rem;padding:0 0 2px 2px;}
	    .level_icon{width: 0.45rem;height: 0.45rem;background:#fff;border-radius: 50%;margin-right: 0.1rem;}
	    .progress{height: 0.135rem;border-radius: 0.6rem;background:#bb9967;}
	    .progress>div{top: 0;left: 0;width: 50%;height: 100%;border-radius: 0.6rem;}

        .triangle{position: absolute;top: -4px;z-index: 99;width: 30px;left: 30px;background: #fff;}
        .rel_bg{
            background-repeat:no-repeat;
            background-size:100% 100%;
            padding:10px 0;
        }

        .activation_code_box {
            padding: 20px 14px;
            display: none;
        }
        .dialog {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            padding: 0 20px;
            z-index: 2000;
            -webkit-box-sizing: border-box;
            display: -webkit-box;
            -webkit-box-pack: center;
            -webkit-box-align: center;
        }
        .dialog.mask {
            background-color: rgba(0, 0, 0, .2);
        }
        .dialog_wrap {
            position: relative;
            width: 100%;
            max-width: 640px;
            border-radius: 5px;
            overflow: hidden;
            -webkit-animation: bounceIn .5s .2s ease both;
        }
        .dialog_tt {
            height: 42px;
            line-height: 42px;
            color: #fff;
            text-align: center;
            font-size: 16px;
            border-radius: 5px 5px 0 0;
        }
        .dialog_tt {
            background-image: -webkit-gradient(linear, 0 0, 100% 0, from(#ea6e4c), to(#ea381e));
            background-image: -webkit-linear-gradient(0, #ea6e4c, #ea381e);
        }
        .dialog_scroller {
            position: relative;
            float: left;
            width: 100%;
            overflow: hidden;
            background-color: #fff;
            border-radius: 0 0 5px 5px;
        }
        .activation_code_box li {
            margin-top: 20px;
        }
        .txt {
            -webkit-appearance: none;
            width: 94%;
            height: 31px;
            padding: 4px 9px;
            border-width: 1px;
            border-radius: 3px;
        }
        .activation_code_box .btns_wrap {
            color: white;
            padding-top: 20px;
            width: 100%;
            display: flex;
        }
        .activation_code_box li.btns_wrap div {
            width: 50%;
            padding: 0 4px;
        }
        .comm_btn.higher {
            height: 37px;
            line-height: 37px;
        }
        .activation_code_box .comm_btn {
            width: 80%;
            text-align: center;
        }
        .disabled {
            background-color: #b6b6b6 !important;
            cursor: default;
        }
        .comm_btn {
            display: inline-block;
            height: 31px;
            line-height: 31px;
            padding: 0 15px;
            font-size: 14px;
            color: #fff;
            background-color: #ff5f32;
            border-radius: 3px;
            white-space: nowrap;
        }
        .addres_box li div {
            vertical-align: top;
        }
        .get_level_gift {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 35px;
            height: 35px;
        }
        .get_level_gift img{
            width: 100%;
            height: 100%;
        }
	</style>
</head>

<body class="color_0">
    <div class="pd_t20 bg_3"></div>
	<div class="rel rel_bg">
        <!-- <img class="w100" src="{pigcms{$static_path}my/img/header-bg.png"> -->
		<div class="abs card">
			<div class="flex" style="margin-bottom: 1.2rem;">
                <if condition="$now_user['avatar'] neq ''">
                    <img class="mg_r5 avatar" src="{pigcms{$now_user['avatar']}">
                <else/>
				    <img class="mg_r5 avatar" src="{pigcms{$static_path}my/img/avatar.png">
                </if>
				<div class="color_3">
					<div class="font_size20 mg_b5">{pigcms{$now_user['nickname']}</div>
                    <php>if(isset($levelarr[$now_user['level']]) && $now_user['level']>0){</php>
                        <div class="flex">
							<if condition="$levelarr[$now_user['level']]['icon']">
								<img class="level_icon" src="{pigcms{$levelarr[$now_user['level']]['icon']|replace_file_domain=###}"/>
							</if>
							{pigcms{$levelarr[$now_user['level']]['lname']}
                        </div>
                        <if condition="$config['level_balance'] eq 1">
                        <div class="flex" style="margin-top:5px;<if condition="$levelarr[$now_user['level']]['icon']">margin-left:0.55rem;</if>"><if condition="$levelarr[$now_user['level']]['validity'] gt 0">到期时间:<span id='expire_date'>{pigcms{$now_user['level_time'] + ($levelarr[$now_user['level']]['validity'] * 86400)|date="Y-m-d",### }</span><else/>永久有效</if></div>
                        </if>                    
                    <php>   }else{
						if(!isset($_GET['nextlevel'])){$nextlevel=1;}</php>
						<div class="font_size12">{pigcms{:L_("还未开通会员等级权益")}</div>
                    <php>  } </php>

				</div>
                <if condition="$show_gift == '1'">
                <div class="get_level_gift img-box" id="get_level_gift">
                    <img src="{pigcms{$static_path}my/img/level_gift.png">
                </div>
                </if>
			</div>
            <if condition="$now_user['level'] gt 0">
                    <?php if($config['level_update_auto']>0){ ?>
                    <div>
                        <div class="flex flex_justify color_3 font_size12 mg_b5"><span>{pigcms{$levelarr[$now_user['level']]['lname']}</span><span>{pigcms{$levelarr[$now_user['level']+1]['lname']}</span></div>
                        <div class="rel w100 mg_b15 progress">
                            <div class="abs bg_f"></div>
                        </div>
                        <if condition="$nextlevel gt 0">
                            <div class="color_3">再获得<span class="update_gap">{pigcms{$levelarr[$nextlevel]['integral']-$now_user['score_count']}</span>积分即可升级为{pigcms{$levelarr[$nextlevel]['lname']}</div>
                        <else />
                            <div class="color_3">{pigcms{:L_("已经是最高等级")}</div>
                        </if>
                    </div>
                    <?php }else{ ?>
                        <div class="color_3 font_size12" style="float: left;"><span class="font_size20">{pigcms{$card_count}</span><span></span></div>
                    <?php } ?>
                    <div class="color_3 font_size12" style="float: right;">
                        <div class="activation_code_tip" style="color: red;border-bottom: 1px solid red;padding-bottom: 1px;">{pigcms{:L_("输入激活码")}</div>
                    </div>
            <else />
                <div class="flex flex_justify flex_baseline">
                    <div class="color_3 font_size12"><span class="font_size20">{pigcms{$card_count}</span><span>{pigcms{:L_("X1人已开卡",['X1'=>''])}</span></div>
                    <!-- <div class="color_yel font_size12">立即开通会员权益 <img class="arrow" src="{pigcms{$static_path}my/img/arrow.png"></div> -->
                    <div class="activation_code_tip" style="color: red;border-bottom: 1px solid red;padding-bottom: 1px;">{pigcms{:L_("输入激活码")}</div>
                </div>
            </if>
		</div>
	</div>
    <div class="pd_t20 bg_f mg_b10"></div>

	<!-- Swiper -->
    <div class="bg_f">
        <div class="swiper-container bg_f color_0 mg_b10 level_box">
            <div class="swiper-wrapper">
                <volist name="levelarr" id="level" key="i">
                    <div class="swiper-slide flex flex_center" data-level="{pigcms{$level['level']}">
                        <div class="align-center rel">
                            <div class="mg_b5 img-box"><if condition="$now_user['level'] EGT $level['level']"><img src="{pigcms{$static_path}my/img/level1.png"><else/><img src="{pigcms{$static_path}my/img/level2.png"></if></div>
                            <div class="font_size14 align_center show_msg">{pigcms{$level.lname}</div>
                            <div class="abs line"></div>
                        </div>
                    </div>
                </volist>
            </div>
        </div>
    </div>
	
    
    <div class="bg_f pd_b20">
        <div class="pd10 rel">
            <div class="triangle"><img class="w100" src="{pigcms{$static_path}my/img/triangle.png"></div>
            <div class="border_d br_10" style="box-shadow: 0 0 2px 2px #f8f8f8;">
                <div class="font_size16 pd10 pd_t20 color_3" style="font-weight: 600;">{pigcms{:L_("会员特权")}</div>
                <div class="ks-clear">
                    <div class="left align_center pd10 border_box lreduce" style="width: 25%;display: none;">
                        <img class="mg_b5" style="width: 1.2rem;height: 1.2rem;" src="{pigcms{$static_path}my/img/icon1.png">
                        <div class="font_size14">{pigcms{:L_("指定商品立减")}<span id="lreduce">5</span>{pigcms{$config['Currency_txt']}</div>
                    </div>
                    <div class="left align_center pd10 border_box ldiscount" style="width: 25%;display: none;">
                        <img class="mg_b5" style="width: 1.2rem;height: 1.2rem;" src="{pigcms{$static_path}my/img/icon2.png">
                        <div class="font_size14">{pigcms{:L_("享指定商品")}<span id="ldiscount">8</span>{pigcms{:L_("X1折",['X1'=>''])}</div>
                    </div>
                    <div class="left align_center pd10 border_box" style="width: 25%;display: none;">
                        <img class="mg_b5" style="width: 1.2rem;height: 1.2rem;" src="{pigcms{$static_path}my/img/icon3.png">
                        <div class="font_size14">{pigcms{:L_("充值X1自动升级",['X1'=>100])}</div>
                    </div>
                    <div class="left align_center pd10 border_box" style="width: 25%;display: none;">
                        <img class="mg_b5" style="width: 1.2rem;height: 1.2rem;" src="{pigcms{$static_path}my/img/icon4.png">
                        <div class="font_size14">{pigcms{:L_("享平台配送费减免")}</div>
                    </div>
                </div> 
            </div>
           
        </div>
    	

    	<div class="font_size16 pd10 color_3" style="font-weight: 600;padding-bottom: 0;">{pigcms{:L_("其他介绍")}</div>
    	<p class="pd10 font_size14"></p>
    	<!-- Swiper -->
        <if condition="$level_adver">
            <div class="pd10 border_box pd_b20">
                <div class="swiper-container ad_box ">
                    <div class="swiper-wrapper">
                        <volist name="level_adver" id="vo">
                            <div class="swiper-slide">
                                <a href="{pigcms{$vo.url}">
                                    <img class="w100 h100" src="{pigcms{$vo.pic}">
                                </a>
                            </div>
                        </volist>
                    </div>
                    <!-- Add Pagination -->
                    <div class="swiper-pagination"></div>
                 </div>
            </div>
        </if>


    </div>

    <if condition="C('config.level_update_auto') neq 1">
    <div class="flex button-box">
    	<button class="h100 pd10 color_yel use_score" style="background:#eedbc4;flex:1;">
    		<div class="font_size14 font_b mg_b5">{pigcms{:L_("使用X1去开通",['X1'=>$config['score_name']])}</div>
    		<div>当前{pigcms{$config.score_name}：{pigcms{$now_user['score_count']}</div>
    	</button>
		<if condition="$config['open_user_money'] || getFormatNumber($now_user['now_money'])">
			<button class="h100 color_3 pd10 use_money" style="background:#d7ac79;flex:1;">
				<div class="font_size14 font_b mg_b5">{pigcms{:L_("使用余额去开通")}</div>
				<div>{pigcms{:L_("当前余额")}：{pigcms{$now_user['now_money']}</div>
			</button>
		</if>
    </div>
    </if>


    <div class="activation_code_box" id="codeBox">
        <ul>
            <li><input class="txt max" placeholder="{pigcms{:L_('请填写激活码')}" id="userCode"></li>
            <li class="btns_wrap">
                <div><a href="javascript:void(0);" class="comm_btn higher disabled" id="cancleRemark">{pigcms{:L_('取消')}</a></div>
                <div><a href="javascript:void(0);" class="comm_btn higher" id="saveRemark">{pigcms{:L_('确认')}</a></div>
            </li>
        </ul>
    </div>

</body>
	<script src="https://apps.bdimg.com/libs/jquery/1.7.0/jquery.min.js"></script>
    <!-- Swiper JS -->
    <script src="{pigcms{$static_path}js/swiper.min.js"></script>
    <!-- Initialize Swiper -->
<script type="text/javascript" src="{pigcms{$static_path}js/dialog.js?t=001" charset="utf-8"></script>
    <script>

        window.addEventListener('pageshow',function(event){
            var gift_level = sessionStorage.getItem('gift_level');
            var levelUpdate_refresh = sessionStorage.getItem('levelUpdate_refresh');
            if (gift_level) {
                layer.open({
                    title:'',
                    content:"<div style='text-align:center;'>领取会员大礼包</div>",
                    btn:['立即领取', '取消'],
                    yes:function(index){
                        sessionStorage.removeItem('gift_level');
                        var link_url = "{pigcms{$config.site_url}{pigcms{:U('My/receive_level_gift')}";
                        console.log('link_url', link_url);
                        window.location.href = link_url;
                    },
                    no: function (index) {
                        sessionStorage.removeItem('gift_level');
                        layer.closeAll();
                        if(levelUpdate_refresh && levelUpdate_refresh == '1'){
                            sessionStorage.removeItem('levelUpdate_refresh');
                            location.reload();
                        }
                    }
                })
            } else {
                if(levelUpdate_refresh && levelUpdate_refresh == '1'){
                    sessionStorage.removeItem('levelUpdate_refresh');
                    location.reload();
                }
            }
        });

        var now_level = "{pigcms{$now_user['level']}";
        var View={
        init:function(){
            this.initDetail();
            this.initClick();
      
        },
        setting:{
            is_chccked:false
        },
        initDetail:function(){
            var swiper1 = new Swiper('.level_box', {
                slidesPerView: 4,
                paginationClickable: true,
                spaceBetween: 30,
                initialSlide :1, // 等级初始值
            });
            var show_level = now_level>0 ? now_level - 1 : 0;

            // 初始化设置三角形的left值
            var left_num =  $('.level_box .swiper-slide').eq(show_level).offset().left +( $('.level_box .swiper-slide').width() - 30 ) / 2
            $('.triangle').css('left', left_num)

            var swiper2 = new Swiper('.ad_box', {
              pagination : '.swiper-pagination',
              resistanceRatio : 0
            });

            $('.level_box .swiper-slide:last-child .line').hide()
	    $('.level_box .swiper-slide').eq(show_level).find('.img-box img').attr('src', '{pigcms{$static_path}my/img/level1.png');

        },

        initClick:function(){
            var _self=this;
        },
    }
    window.View=View;
    View.init();

    </script>
    <script>
        $(document).ready(function () {
            var nextlevel = "{pigcms{$nextlevel}";
            var now_level = "{pigcms{$now_user['level']}";
            var now_user_level = "{pigcms{$now_user_level}";
	    var level_max = '{pigcms{$level_max}';
            // 跳转领取礼包
            $('#get_level_gift').click(function(){
                var link_url = "{pigcms{$config.site_url}{pigcms{:U('My/receive_level_gift')}";
                console.log('link_url', link_url);
                window.location.href = link_url;
            });

            // 添加备注
            $('.activation_code_tip').bind('click', function(){
                console.log("====");
                $('#userCode').val('');
                $('#codeBox').dialog({title: '填写激活码'});
            });

            $('#cancleRemark').bind('click', function(){
                $('#codeBox').dialog('close');
            });

            var chooseLevel = "{pigcms{$config['site_url']}/index.php?g=Wap&c=My&a=levelUpdateBy";
            $('.use_score').click(function(){
                if(nextlevel==''){
                    layer.open({
                        content:"您已经是最高等级！",
                    });
                    return false;
                }
                
                if(level_max*1==now_user_level*1){
                    layer.open({
                        content:"您已经是最高等级！",
                    });
                    return false;
                }

                window.location.href=chooseLevel+"&type=score";
            });
            
            $('.use_money').click(function(){
                if(nextlevel==''){
                    layer.open({
                        content:"您已经是最高等级！",
                    });
                    return false;
                }

                if(level_max*1==now_user_level*1){
                    layer.open({
                        content:"您已经是最高等级！",
                    });
                    return false;
                }
                window.location.href=chooseLevel+"&type=money";
            });

            $('#saveRemark').bind('click', function(){
                // $('#remarkTxt').text($('#userCode').val());
                console.log("userCode", $('#userCode').val());
                var userCode = $('#userCode').val();
                if (!userCode) {
                    layer.open({
                        content: '请填写激活码！'
                        ,skin: 'msg'
                        ,time: 1 //1秒后自动关闭
                    });
                    return false;
                }
                $.post("{pigcms{$config.site_url}{pigcms{:U('My/bind_activation_code')}", {'activation_code':userCode}, function(res) {
                    layer.closeAll();
                    if (res.status == 0 && !res.url) {
                        layer.open({
                            content: res.info
                            ,skin: 'msg'
                            ,time: 2 //2秒后自动关闭
                        });
                    } else if(res.status == 0 && res.url) {
                        window.location.href = res.url;
                    } else {
                        layer.open({
                            content: res.info
                            ,skin: 'msg'
                            ,time: 2 //2秒后自动关闭
                        });
                        setTimeout(function () {
                            location.reload();
                        },2000);
                    }
                }, 'json');
            });

           
            $('.rel_bg').css('height',$('.card').height())
            $('.rel_bg').css('background-image','url({pigcms{$static_path}my/img/header-bg.png)') 

            
            var update_gap = $('.update_gap').text();
            var next_score = "{pigcms{$levelarr[$nextlevel]['integral']}";
            var my_score = "{pigcms{$now_user['score_count']}";
            var levelarr = '{pigcms{$levelarr|json_encode}';
            var percent = parseFloat(parseFloat(my_score) / parseFloat(next_score)).toFixed(2) * 100;

            if(update_gap>0){
                $('.abs.bg_f').css('width',percent+'%');
            }else if(update_gap<=0){
                $('.abs.bg_f').css('width','100%');
            }
            levelarr = $.parseJSON(levelarr.replace(/\n/g,"\\n").replace(/\r/g,"\\r"));
            if(now_level==0){
                now_level=1;
            }
            if(typeof levelarr[now_level] != 'undefined'){
                if(levelarr[now_level]['type']==1){
                    $('.ldiscount').show();
                    $('#ldiscount').text(levelarr[now_level]['boon'] / 10);
                }else if(levelarr[now_level]['type']==2){
                    $('.lreduce').show();
                    $('#lreduce').text(levelarr[now_level]['boon']);
                }
                if(nextlevel){
					$('.pd10.font_size14').html(levelarr[nextlevel]['description']);
                }
            } 

            $(document).on('click','.level_box .swiper-slide',function(){

                // 点击图标滚动
                var index = $(this).index()
                var length = $('.level_box .swiper-slide').length
                var item_width= $('.level_box .swiper-slide').eq(0).width()
                var max_distance = item_width * length + 30 * (length - 1) - $(document).width()
                var distance = item_width * (index + 1) + 30 * (index - 1) - $(document).width()/2
                if ($(this).index() == 0 || -distance > 0) {
                    distance = 0
                } else if (distance > max_distance) {
                    distance = -max_distance + 'px'
                } else {
                    distance = -distance + 'px'
                }
                $('.level_box .swiper-wrapper').css({
                    "transition-duration": "300ms",
                    "transform": "translate3d("+ distance +", 0px, 0px)"
                })

                // 设置三角形坐标left
                var that = this;
                setTimeout(function () {
					console.log('$(that).eq(now_level - 1)', $(that).eq(now_level - 1));
                    //var left_num =  $(that).offset().left +( $(this).width() - 30 ) / 2;
					var left_num =  $(that).offset().left +( $(that).width() - 30 ) / 2
					
                    $('.triangle').css('left', left_num);
                }, 1)
                

                $('.level_box .swiper-slide .img-box img').attr('src', '{pigcms{$static_path}my/img/level2.png');
                $(this).find('.img-box img').attr('src', '{pigcms{$static_path}my/img/level1.png');
                console.log('levelarr', levelarr);
                if(levelarr[$(this).data('level')]['type']==1){
                    $('.lreduce').hide();
                    $('.ldiscount').show();
                    $('#ldiscount').text(levelarr[$(this).data('level')]['boon'] / 10);
                }else if(levelarr[$(this).data('level')]['type']==2){
                    $('.ldiscount').hide();
                    $('.lreduce').show();
                    $('#lreduce').text(levelarr[$(this).data('level')]['boon']);
                }else if(levelarr[$(this).data('level')]['type']==0){
                    $('.ldiscount').hide();
                    $('.lreduce').hide();
                }
                $('.pd10.font_size14').html(levelarr[$(this).data('level')]['description']);
            });
        });
    </script>
<script type="text/javascript">
$(function(){
    /*会员卡过期弹层+会员卡上增加过期提醒*/
    var obj=$('#expire_date');
    if(obj.length>0){
        var expire_time={pigcms{$now_user['level_time'] + ($levelarr[$now_user['level']]['validity'] * 86400)};
        var now_time=Math.floor(new Date().getTime()/1000);
        if(now_time>=expire_time){
            var card_name="{pigcms{$config.vip_card_alias|default='平台会员卡'}";
            layer.open({
                title:'',
                content:"<div style='text-align:center;'>您的"+card_name+"已于"+obj.text()+"到期，可使用积分或余额续费"+card_name+"，享受更多优惠</div>",
                btn:['我知道了'],
                yes:function(index){
                    obj.parent().after("<div style='color:#bf4e4e;margin:6px 0 0 0.55rem;position: absolute;'>您的"+card_name+"已于"+obj.text()+"到期，可使用积分或余额续费"+card_name+"，享受更多优惠</div>");
                    layer.close(index);
                }
            })
        }
    }
});
</script>
</html>