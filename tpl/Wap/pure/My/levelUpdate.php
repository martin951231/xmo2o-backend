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

    	body{background-color: #fff}
        #app{
            padding-bottom: 100px;
        }
    	.card{top: 50%;left: 50%;transform: translate(-50%, -50%);-webkit-transform:translate(-50%, -50%);width: 86%;}
	    .avatar{width: 1.4rem;height: 1.4rem;margin-right: 12px;border-radius: 50%;object-fit: cover;}

	    .level_box {width: 100%;height: 2.6rem;}
	    .level_box .img-box img{width: 35px;height: 35px;}
	    .level_box .line{
            width:220%;
            height: 2px;
            background: #3e3d40;
            top: 34%;
            left: 0.7rem;
            z-index: -1;
            display: block;
        }
        .level_box .swiper-wrapper .swiper-slide:last-child .line{display: none;}

	    .ad_box{width: 100%;height: 4.3rem;background:#d9b479;border-radius: 10px;overflow: hidden;}
	    
	    .swiper-pagination-bullet {width: 6px;height: 6px;display: inline-block;border-radius: 3px;background: #fff;opacity: .7;}
	    .swiper-pagination-bullet-active {background: #fff;opacity: 1;width: 12px;height: 6px;}

	    button{outline: none;border: none;}
        .swiper-wrapper{height: 100%;}
	    .button-box{
            position: fixed;
            bottom: 40px;
            left: 14px;
            right: 14px;
            z-index: 99;
            background-size: 100% 100%;
            background-repeat: no-repeat;
        }
        .button-box button{
            background: transparent;

        }

	    .arrow{width: 0.2rem;height: 0.28rem;padding:0 0 2px 2px;}
	    .level_icon{width: 0.45rem;height: 0.45rem;background:#fff;border-radius: 50%;margin-right: 0.1rem;}
	    .progress{height: 0.135rem;border-radius: 0.6rem;background:#bb9967;}
	    .progress>div{top: 0;left: 0;width: 50%;height: 100%;border-radius: 0.6rem;}

        .triangle{position: absolute;top: -4px;z-index: 99;width: 30px;left: 30px;}
        .rel_bg{
            background-repeat:no-repeat;
            background-size:100% 100%;
            padding: 18px 0;
            margin: 0 14px;
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
            position: fixed;
            top: 70%;
            right: 15px;
            width: 45px;
            height: 45px;
            z-index: 10;
        }
        .get_level_gift img{
            width: 100%;
            height: 100%;
        }

        .header-bj{
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: 320px;
            background-size: 100% 100%;
            background-repeat: no-repeat;
        }
        .color-fff{
            color: #fff !important;
        }
        .ks-clear{
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-gap: 22px;
        }
        .ks-clear .icon{
            width: 40px;
            height: 40px;
            margin-bottom: 14px;
        }
        .ks-clear .item{
            text-align: center;
        }
        .ks-clear .title{
            font-weight: bold;
            font-size: 14px;
            color: #222222;
            margin-bottom: 8px;
        }
        .ks-clear .text{
            font-weight: 500;
            font-size: 12px;
            color: #AFBED7;
        }
        .triangle{
            width: 0;
            height: 0;
            border-left: 20px solid transparent;  /* 左边透明 */
            border-right: 20px solid transparent; /* 右边透明 */
            border-bottom: 20px solid #fff;      /* 朝上的三角形颜色 */
            margin-top: -3px;
            margin-left: -4px;
        }
        .active .show_msg{
            color: #B89460 !important;
        }
        .lname{
            font-weight: bold;
            font-size: 15px;
            color: #000000;
            font-style: italic;
            background: linear-gradient(0deg, #ED7D20 0%, #FFE659 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-left: 5px;
        }
        .activation_code_tip{
            background: linear-gradient(0deg, #999999 0%, #F7C66F 0%, #FFF3DB 100%);
            font-weight: 500;
            font-size: 13px;
            color: #F9C85D;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;

        }
	</style>
</head>

<body class="color_0">
<div id="app" style="display: none;">
    <div class="pd_t20 bg_3"></div>
	<div class="rel rel_bg" style="position: relative;z-index: 1;">
		<div class="abs card">
			<div class="flex" style="margin-bottom: 1.2rem;">
                <if condition="$levelarr[$now_user['level']]['icon']">
                    <img class="avatar" src="{pigcms{$levelarr[$now_user['level']]['icon']|replace_file_domain=###}">
                    <else />
                    <img class="avatar" style="border-radius: 0;object-fit:contain" src="{pigcms{$static_path}my/img/vip-logo.png">
                </if>
				<div class="color_3 color-fff">
					<div class="mg_b5" style="font-weight: bold;font-size: 20px;">
                        {pigcms{$now_user['nickname']}
                        <if condition="$levelarr[$now_user['level']]['lname']">
                            <span class="lname">{pigcms{$levelarr[$now_user['level']]['lname']}</span>
                        </if>
                    </div>
                    <php>if(isset($levelarr[$now_user['level']]) && $now_user['level']>0){</php>
                        <if condition="$config['level_balance'] eq 1">
                            <div class="flex" style="margin-top:10px;">
                                <if condition="$levelarr[$now_user['level']]['validity'] gt 0">到期时间:
                                    <span id='expire_date'>{pigcms{$now_user['level_time'] + ($levelarr[$now_user['level']]['validity'] * 86400)|date="Y-m-d",### }</span>
                                    <else/>永久有效
                                </if>
                            </div>
                        </if>                    
                    <php>   }else{
						if(!isset($_GET['nextlevel'])){$nextlevel=1;}</php>
						<div class="font_size12" style="padding-top: 4px;">{pigcms{:L_("还未开通会员等级权益")}</div>
                    <php>  } </php>

				</div>
			</div>
            <if condition="$now_user['level'] gt 0">
                    <?php if($config['level_update_auto'] > 0){ ?>
                    <div>
                        <div class="flex flex_justify color-fff font_size12 mg_b5"><span>{pigcms{$levelarr[$now_user['level']]['lname']}</span><span>{pigcms{$levelarr[$now_user['level']+1]['lname']}</span></div>
                        <div class="rel w100 mg_b15 progress">
                            <div class="abs bg_f"></div>
                        </div>
                        <if condition="$nextlevel gt 0">
                            <div class="color-fff">再获得<span class="update_gap">{pigcms{$levelarr[$nextlevel]['integral']-$now_user['score_count']}</span>积分即可升级为{pigcms{$levelarr[$nextlevel]['lname']}</div>
                        <else />
                            <div class="color-fff">{pigcms{:L_("已经是最高等级")}</div>
                        </if>
                    </div>
                    <?php }else{ ?>
                        <div class="color-fff font_size12" style="float: left;">
                            <span>{pigcms{$card_count}</span>
                            <span>{pigcms{:L_("X1人已开卡",['X1'=>''])}
                        </div>
                    <?php } ?>
                    <div class="color-fff font_size12" style="float: right;">
                        <div class="activation_code_tip">{pigcms{:L_("输入激活码")}</div>
                    </div>
            <else />
                <div class="flex flex_justify flex_baseline">
                    <div class="color-fff font_size12"><span>{pigcms{$card_count}</span><span>{pigcms{:L_("X1人已开卡",['X1'=>''])}</span></div>
                    <div class="activation_code_tip">{pigcms{:L_("输入激活码")}</div>
                </div>
            </if>
		</div>
	</div>

    <div class="header-bj" style="background-image: url({pigcms{$static_path}my/img/page-bg.png);"></div>
    
    <!-- 礼物 -->
    <if condition="$show_gift == '1'">
        <div class="get_level_gift img-box" id="get_level_gift">
            <img src="{pigcms{$static_path}my/img/level_gift.png">
        </div>
    </if>

	<!-- Swiper -->
    <div class="bg_f" style="background: transparent !important;margin: 5px 0 16px 0">
        <div class="swiper-container bg_f color_0 mg_b10 level_box" style="background: transparent !important">
            <div class="swiper-wrapper">
                <volist name="levelarr" id="level" key="i">
                    <div class="swiper-slide flex flex_center" data-level="{pigcms{$level['level']}" data-money="{pigcms{$level['use_money']}" data-score="{pigcms{$level['integral']}">
                        <div class="align-center rel">
                            <div class="mg_b5 img-box"><if condition="$now_user['level'] EGT $level['level']"><img src="{pigcms{$static_path}my/img/level1.png"><else/><img src="{pigcms{$static_path}my/img/level2.png"></if></div>
                            <div class="font_size14 align_center show_msg" style="color: #4D4D55;font-weight: 500;font-size: 15px;">{pigcms{$level.lname}</div>
                            <div class="abs line"></div>
                        </div>
                    </div>
                </volist>
            </div>
        </div>
    </div>
	
    <div style="position: relative;width: 100%;height: 12px;margin-top: -12px"><div class="triangle"></div></div>
    <div style="position: relative;margin-top: -12px;overflow: hidden;border-radius: 6px 6px 0 0;background: #f7f9fb;">
        <div  v-if="list.length" class="rel bg_f" style="padding: 26px 24px;margin-bottom: 10px;padding-bottom: 14px;">
            <div class="br_10">
                <div style="font-weight: bold;font-size: 17px;color: #222222;margin-bottom: 20px;">{pigcms{:L_("会员特权")}</div>
                <div class="ks-clear" style="margin-top: 10px;">
                    <div class="item" v-for="item in list" :key="item.id">
                        <img class="icon" :src="item.icon">
                        <div class="title">{{item.title}}</div>
                        <div class="text">{{item.description}}</div>
                    </div>
                </div> 
            </div>
        </div>

        <div class="bg_f" style="padding: 26px 24px;">
            <div style="font-weight: bold;font-size: 17px;color: #222222;margin-bottom: 20px;">{pigcms{:L_("其他介绍")}</div>
            <p class="pd10 font_size14 description" style="font-weight: 500;font-size: 12px;color: #B3B3C3;padding: 0"></p>
        </div>
    </div>

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

    <div>
    <if condition="C('config.level_update_auto') neq 1">
        <div class="flex button-box" style="background-image: url({pigcms{$static_path}my/img/footer-bg.png);" v-show="showBtn">
            <button class="h100 pd10 use_score" style="flex: 1;">
                <div class="font_size14 font_b color-fff" style="margin-bottom: 6px;">{pigcms{:L_("使用X1开通",['X1'=>$config['score_name']])}</div>
                <div style="color: #F1EFEF;opacity: 0.7;font-size: 10px">
                    <span>消耗{pigcms{$config.score_name}：{{levelItem.score}}</span>
                </div>
            </button>
            <button class="h100 pd10 use_money" style="flex:1">
                <div class="font_size14 font_b" style="color: #734718;margin-bottom: 6px;">{pigcms{:L_("使用余额开通")}</div>
                <div style="color: #734718;font-size: 10px">
                    <span>{pigcms{:L_("消耗余额")}：{{levelItem.money}}</span>
                </div>
            </button>
        </div>
    </if>
    </div>


    <div class="activation_code_box" id="codeBox">
        <ul>
            <li><input class="txt max" placeholder="{pigcms{:L_('请填写激活码')}" id="userCode"></li>
            <li class="btns_wrap">
                <div><a href="javascript:void(0);" class="comm_btn higher disabled" id="cancleRemark">{pigcms{:L_('取消')}</a></div>
                <div><a href="javascript:void(0);" class="comm_btn higher" id="saveRemark">{pigcms{:L_('确认')}</a></div>
            </li>
        </ul>
    </div>

    <form id="recharge" action="{pigcms{:U('recharge')}" method="post" style="display:none">
        <input type="hidden" id="money" name="money" value="">
        <input type="hidden" id="label" name="label" value="wap_level_">
        <input type="hidden" id="label_level" name="label_level" value="0">
    </form>
</div>
</body>
	<script src="https://apps.bdimg.com/libs/jquery/1.7.0/jquery.min.js"></script>
    <!-- Swiper JS -->
    <script src="{pigcms{$static_path}js/swiper.min.js"></script>
    <!-- Initialize Swiper -->
<script type="text/javascript" src="{pigcms{$static_path}js/dialog.js?t=001" charset="utf-8"></script>
<link rel="stylesheet" href="{pigcms{$static_path}css/vant@2.12.47.css"/>
	<script src="{pigcms{$static_path}js/vue@2.6.14.min.js" type="text/javascript" charset="utf-8"></script>
	<script src="{pigcms{$static_path}js/vant@2.12.14.min.js" type="text/javascript" charset="utf-8"></script>
	<script src="{pigcms{$static_path}js/common.js?t={pigcms{:time()}" type="text/javascript"></script>
<script src="{pigcms{$static_path}js/common.js?t={pigcms{:time()}" type="text/javascript"></script>
    <script>
        var _this = '';
        var now_level = "{pigcms{$now_user['level']}";
        var levelarr = '{pigcms{$levelarr|json_encode}';
        var mall_id = "{pigcms{$_GET['mall_id']}";
        levelarr = $.parseJSON(levelarr.replace(/\n/g,"\\n").replace(/\r/g,"\\r"));
		// 实例化vue
		var app = new Vue({
			el: '#app',
			data: {	
				list: [],
                level: '',
                mall_id: '',
                levelItem: {},
			},
			created() {
				$("#app").show();
				_this = this;
                let mallInfo = window.localStorage.getItem('shopping_mall')
                if(mallInfo){
                    mallInfo = JSON.parse(mallInfo).data
                    this.mall_id = mallInfo.mall_id
                }
                if(mall_id){
                    this.mall_id = mall_id
                }
                if(levelarr[now_level] && levelarr[now_level].level){
                    this.level = levelarr[now_level].level
                }else{
                    var show_level = now_level>0 ? now_level - 1 : 0;
                    this.level = $('.level_box .swiper-slide').eq(show_level).data('level')
                }
				this.getLevelBenefits();
			},
            computed:{
                showBtn(){
                    return this.level > now_level
                }
            },
			methods: {
				getLevelBenefits() {
                    //获取会员权益
                    v20Axios('/building/api.home/levelBenefits',{mall_id: this.mall_id,level: this.level,},res=>{
                        this.list = res
                    })
				},
                //购买会员
                levelBuy(type){
                    var now_money = "{pigcms{$now_user['now_money']}";
                    var now_score = "{pigcms{$now_user['score_count']}";
                    if(type == 'money'){
                        const recharge = ()=>{
                            vant.Dialog.confirm({
                                title: '提示',
                                message: '您当前余额不足，是否去充值？',
                                confirmButtonColor: '#0e79ff',
                                confirmButtonText: '去充值',
                                cancelButtonText: '取消',
                            })
                            .then(() => {
                                var recharge_money = this.levelItem.money - now_money;
                                var num = recharge_money;
                                $('#money').val(num);
                                $('#label').val($('#label').val()+num)
                                $('#label_level').val(this.levelItem.level);
                                $("#recharge").submit();
                            })
                        }
                        if(now_money == 0 || Number(now_money) < this.levelItem.money){
                            recharge()
                            return
                        }
                    }else if(type == 'score'){
                        if(now_score == 0 || Number(now_score) < this.levelItem.score){
                            vant.Toast("您当前{pigcms{$config['score_name']}不足！");
                            return
                        }
                    }

                    vant.Dialog.confirm({
                        title: '确认开通会员？',
                        message: '开通后将无法取消，请确认',
                        confirmButtonColor: '#0e79ff',
                    })
                    .then(() => {
                        let params = {
                            use_money: type == 'money' ? 1 : 0,
                            use_score: type == 'score' ? 1 : 0,
                            nextlevel: this.levelItem.level,
                        }
                        vant.Toast.loading({
                            message: '',
                            forbidClick: true,
                        });

                        $.post("{pigcms{$config['site_url']}/index.php?g=User&c=Level&a=levelUpdate",params,function(ret){
                            vant.Toast.clear();
                            if (ret && ret['error']==0) {
                                vant.Dialog.alert({
                                    message: ret.msg,
                                    confirmButtonColor: '#0e79ff',
                                }).then(() => {
                                    sessionStorage.setItem('levelUpdate_refresh', 1);
                                    history.back();
                                });
                            } else {
                                alert(ret.msg);
                                window.location.reload();
                            }
                        },'JSON');
                    })
                }
			}
		})

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
            _this.levelItem = {
                level: $('.level_box .swiper-slide').eq(show_level).data('level'),
                money: $('.level_box .swiper-slide').eq(show_level).data('money'),
                score: $('.level_box .swiper-slide').eq(show_level).data('score'),
            }
            $('.level_box .swiper-slide:last-child .line').hide()
	        $('.level_box .swiper-slide').eq(show_level).find('.img-box img').attr('src', '{pigcms{$static_path}my/img/level1.png');
            $('.level_box .swiper-slide').eq(show_level).addClass('active');
        },

        initClick:function(){
            var _self=this;
        },
    }
    window.View=View;
    View.init();

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

                _this.levelBuy('score');
                // window.location.href=chooseLevel+"&type=score";
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
                _this.levelBuy('money');
                // window.location.href=chooseLevel+"&type=money";
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
            var percent = parseFloat(parseFloat(my_score) / parseFloat(next_score)).toFixed(2) * 100;

            if(update_gap>0){
                $('.abs.bg_f').css('width',percent+'%');
            }else if(update_gap<=0){
                $('.abs.bg_f').css('width','100%');
            }
            if(now_level==0){
                now_level=1;
            }
            if(typeof levelarr[now_level] != 'undefined'){
                if(nextlevel){
					$('.description').html(levelarr[nextlevel]['description'] || '暂无');
                }
            } 

            $(document).on('click','.level_box .swiper-slide',function(){

                // 点击图标滚动
                var index = $(this).index()
                var length = $('.level_box .swiper-slide').length
                var item_width= $('.level_box .swiper-slide').eq(0).width()
                var max_distance = item_width * length + 30 * (length - 1) - $(document).width()
                var distance = item_width * (index + 1) + 30 * (index - 1) - $(document).width()/2
                if(length > 3){
                    if ($(this).index() == 0 || -distance > 0) {
                        distance = 0
                    } else if (distance > max_distance) {
                        distance = -max_distance + 'px'
                    } else {
                        distance = -distance + 'px'
                    }
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
                // 移除所有其他元素的 class
                $('.level_box .swiper-slide').removeClass('active');
                $(this).addClass('active');
                _this.level = $(this).data('level');
                _this.levelItem = {
                    level: $(this).data('level'),
                    money: $(this).data('money'),
                    score: $(this).data('score'),
                }
                _this.getLevelBenefits()
                $('.description').html(levelarr[$(this).data('level')]['description'] || '暂无');
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