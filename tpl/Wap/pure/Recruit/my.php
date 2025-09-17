<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
	    <meta name="viewport"
	          content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
	    <meta http-equiv="pragma" content="no-cache"/>
	    <meta name="apple-mobile-web-app-capable" content="yes"/>
	    <meta name='apple-touch-fullscreen' content='yes'/>
	    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
	    <meta name="format-detection" content="telephone=no"/>
	    <meta name="format-detection" content="address=no"/>
	    <title>我的</title> 
	    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/style.css?15"> 
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?2225">  
	    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script> 
	    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
		<script>
		var currency_symbol = "{pigcms{$config.Currency_symbol}";
		</script>
         <script src="{pigcms{$static_path}recruit/js/base.js?133444" ></script>
	    <!-- <script src="{pigcms{$static_public}activity/assets/js/common.js" type="text/javascript" charset="UTF-8"></script> -->
         <script type="text/javascript" src="{pigcms{$static_path}js/common.js?2105" charset="utf-8"></script>
	    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/simplite/simplite.js"></script>
	    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/jquery-form/jquery-plugin-form.js"></script>
	    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/validator.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/index.css">   
		<style>
		.headerBoxMy .avatar{
			width:52px;
			height:52px;
		}
		.contact-box .detail {
			background:#edecec;
			width:80%;
			position:absolute;
			top:50%;
			left:50%;
			transform:translate(-50%,-50%);
			padding:40px 30px 10px;
			box-sizing:border-box;
			border-radius:5px;
		}

		.contact-box .detail .item {
			background:#fff;
			margin:0 auto 20px;
			padding:6px 20px;
			font-size:16px;
			color:#06c1ae;
			display:flex;
			align-items:center;
			border-radius:5px;
		}
		.contact-box .detail .item.active {
			color:#999;
		}

		.contact-box .icon-cha {
			position:absolute;
			top:7px;
			right:7px;
			color:rgba(0, 0, 0, .3);
		}
		.contact-box .detail .item {
			background:#fff;
			margin:0 auto 20px;
			padding:6px 40rpx;
			font-size:16px;
			color:#06c1ae;
			display:flex;
			align-items:center;
			border-radius:5px;
		}
		.mask {
			background: rgba(0, 0, 0, .5);
			z-index: 99;
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			height: 100%;
		}
		</style>
	</head>
	<body>
		<div class="headerBoxMy bg-cover" style="<if condition='$config["mobile_head_color"]'>background-color: {pigcms{$config["mobile_head_color"]};</if>"">
			<div class="info-box">
			   <div class="avatar fl" style="float:left; margin-right:10px;">
	  				<img class="img" src="<if condition="$now_user['avatar']">{pigcms{$now_user.avatar}<else/>{pigcms{$static_path}images/new_my/pic-default.png</if>" alt="{pigcms{$now_user.nickname}头像"/>
					<!-- 头像上的小图标  clas类名hidden表示隐藏 --> 
				</div>
				<div class="name">
					<div class="font_size16">{pigcms{$now_user.nickname} </div>
					<if condition="$now_user.phone lt 1">
						<a href="{pigcms{:U('My/bind_user',array('referer'=>urlencode($_SERVER['REQUEST_URI'])))}">
							<p class="noPhone" style="color: red;">未绑定手机号</p>
						</a>
					<else/>
						<div>{pigcms{$now_user.phone}</div>
					</if>
                 </div>
				
			</div>
		</div>
		
		<div class="orderBox my leader">
			<div class="title  openUrl newWin" data-url="{pigcms{:U('My/my_money')}"> 
            我的钱包
				<div class="fr more">&nbsp;</div>
			</div>
			<div class="orderBiz cf">
				<div class="item openUrl newWin" style="width:33.333%" data-url="{pigcms{:U('My/my_money')}" <if condition="$config['twice_verify'] AND empty($_COOKIE['my_wallet_time'])  AND $config['twice_verify_wallet']">style="display:none;"</if>>
					 <p style="color:{pigcms{$config["mobile_head_color"]}">{pigcms{$now_user.now_money|floatval}</p>
					<div class="txt">余额</div>
				</div>
				<div class="item openUrl newWin" style="width:33.333%" data-url="{pigcms{:U('My/integral')}">
                    <p style="color:{pigcms{$config["mobile_head_color"]}">{pigcms{$now_user.score_count|floatval}</p>
					<div class="txt">{pigcms{$config.score_name}</div>
				</div>
				<if condition="$now_user.level eq 0">
					<div class="item openUrl newWin" style="width:33.333%" data-url="{pigcms{:U('My/levelUpdate')}">

	                    <p style="color:{pigcms{$config["mobile_head_color"]}">VIP{pigcms{$now_user.level}</p>
						<div class="txt">等级</div>
					</div>
				<else/>
					<div class="item openUrl newWin" style="width:33.333%" data-url="{pigcms{:U('My/levelUpdate')}">
						<p style="color:{pigcms{$config["mobile_head_color"]}">{pigcms{$now_user.lname}</p>
						<div class="txt">等级</div>
					</div>
				</if>
				<div class="clear"></div>
			</div>
		</div>
		
		<div class="orderBox">
			<div class="title">
			   我是求职者 
			</div>
			<div class="orderBiz cf">
				<div class="item openUrl newWin" data-url="{pigcms{:U('my_resume_list')}">
					<div class="icon iconfont resume"></div>
					<div class="txt">发布简历</div>
				</div>
				<div class="item openUrl newWin" data-url="{pigcms{:U('my_resume_send')}">
					<div class="icon iconfont send"></div>
					<div class="txt">投递邀约记录</div>
				</div>
				<div class="item openUrl newWin" data-url="{pigcms{:U('my_collection')}">
					<div class="icon iconfont collect"></div>
					<div class="txt">收藏</div>
				</div>
				<div class="item openUrl newWin" data-url="{pigcms{:U('my_course')}">
					<div class="icon iconfont course"></div>
					<div class="txt">我的视频</div>
				</div>
				<div class="item openUrl newWin" data-url="{pigcms{:U('score_package')}">
					<div class="icon iconfont score"></div>
					<div class="txt">购买积分</div>
				</div>
				<div class="item openUrl newWin" data-url="{pigcms{:U('My/my_spread')}">
					<div class="icon iconfont my_spread"></div>
					<div class="txt">我的推广</div>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		
		<if condition='empty($now_company)'>
		<div class="orderBox">
			<div class="title company_apply openUrl newWin" > 
					申请成为企业
				<div class="fr more newWin"> &nbsp;</div>
			</div>
		</div>
		</if>

		<if condition='$now_company'>
		<div class="orderBox">
			<div class="title">
			    我是企业招聘者
			</div>
			<div class="orderBiz cf">
				<if condition='$now_company["status"] eq 1'>
				<div class="item openUrl newWin" data-url="{pigcms{:U('my_position')}">
					<div class="icon iconfont position"></div>
					<div class="txt">职位管理</div>
				</div>
				<div class="item openUrl newWin" data-url="{pigcms{:U('my_resume_receive')}">
					<div class="icon iconfont receive"></div>
					<div class="txt">求职者管理</div>
				</div>
				<div class="item openUrl newWin" data-url="{pigcms{:U('resume_list')}">
					<div class="icon iconfont talents"></div>
					<div class="txt">个人求职简历</div>
				</div>
				<div class="item openUrl newWin" data-url="{pigcms{:U('ranking_detail')}">
					<div class="icon iconfont ranking"></div>
					<div class="txt">排名购买</div>
				</div>
				<div class="item openUrl newWin" data-url="{pigcms{:U('energy_package')}">
					<div class="icon iconfont energy"></div>
					<div class="txt">VIP年度会员</div>
				</div>
				</if>
				<div class="item openUrl newWin"  data-url="{pigcms{:U('company_detail')}">
					<div class="icon iconfont company"></div>
					<div class="txt">企业管理</div>
				</div>
				
				<div class="clear"></div>
			</div>
		</div>
		</if>
		
		<if condition="$kefu['phone']">
		<div class="orderBox">
			<div class="title system_phone">
					平台客服
				<div class="fr more"> &nbsp;</div>
			</div>
		</div>
		</if>
		<div class="orderBox">
			<div class="title logout">
					退出登录
				<div class="fr more"> &nbsp;</div>
			</div>
		</div>
<!-- 店铺联系方式弹窗 -->
<div class='mask contact-box' style="display:none">
	<div class='detail'>
		<volist name="kefu.phone" id="vo">
		<a class='item' href="tel:{pigcms{$vo}">
			<img src="/static/images/my_new/icon_tel.png" style="width:26px;height:26px"/>
			<span style="padding-left:10px">{pigcms{$vo}</span>
		</a>
		</volist>
		<!-- <if condition="$kefu['url'] neq ''">
			<a class='item'  href="{pigcms{$kefu.url}" >
				<img src="/static/images/my_new/icon_weixin1.png" style="width:26px;height:26px"/>
				<span style="padding-left:10px">在线客服</span>
			</a>
		<else/>
			<a class='item'  href="javascript:;" >
				<img src="/static/images/my_new/icon_weixin.png" style="width:26px;height:26px"/>
				<span style="padding-left:10px;color:#999" >暂未开通在线客服</span>
			</a>
		</if> -->
		<img src="/static/images/my_new/icon_del.png" style="width:26px;height:26px;cursor:pointer" class="icon-cha" />
	</div>
</div>

<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
<script type="text/javascript">
    function logout(){
    	window.location.href =	"{pigcms{:U('Recruit/logout')}";
    }
	View={
        init:function(){
            this.click();

        },
        click:function(){
            var _this=this
            $(document).on("click",'.company_apply',function(){
                //点击确认
                $.ajax({
                    type : "post",
                    url : "{pigcms{:U('ajax_check_status')}",
                    dataType : "json",
                    data:{},
                    async:false,
                    success : function(data){
                        if(data.errorMsg!='success'){
                            if (data.errorCode=='73000008') {
                                $.alert(data.errorMsg,function(){
                                    window.location.href="{pigcms{:U('Login/index')}";
                                })
                            }else if(data.errorCode=='20120035'){
                                $.alert(data.errorMsg,function(){
                                    window.location.href="{pigcms{:U('My/bind_user')}";
                                })
                            }else{
                                $.alert(data.errorMsg);
                            }
                            return false
                        }else{ 
                             
                            $('.company_apply').data('url',"{pigcms{:U('company_apply')}");
                            // window.location.href="{pigcms{:U('company_apply')}"; 
                        }
                    },
                    error:function(res){
                        console.log(res)
                    }
                })
            })

            // 平台客服
            $(document).on("click",'.system_phone',function(){
                //点击确认
               $('.contact-box').show();
            })	
		    $(".icon-cha").on('click',function(){
			  	$(".contact-box").hide()
			})

			$('.logout').on('click',function(){

        		// logout()
				var isApp = motify.checkApp();
				// alert(isApp)
				if (isApp) {
					// alert(motify.checkIos())
					if (motify.checkIos()) {
						iosCommon.iosFunction('getOutLogin'+'/logout')
					}else{

						window.lifepasslogin.getOutLogin('logout');	
					}				
				}else{
					location.href =	"{pigcms{:U('Recruit/logout')}";
				}
			});
        }
    }
    window.View=View;
    View.init(); 
</script>
 <div class="share_html">{pigcms{$shareScript}</div> 
<include file="footer"/>
