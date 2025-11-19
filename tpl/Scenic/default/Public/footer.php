	<footer class="bottom">
		<a href="{pigcms{$footer_advert['advert_url']}"><div class="remind" style="background:url({pigcms{$footer_advert['advert_img']}) center no-repeat;"></div></a>
		<div class="bottom_list">
			<div class="w1200">
				<div class="bottom_top clr">
					<div class="fl bottom_url">
						<ul class="clr">
							<volist name="about_index" id="about_footer">
								<li>
									<h2>{pigcms{$about_footer['com_title']}</h2>
									<volist name="about_footer['about']" id="about_footer_son">
										<a href="{pigcms{:U('Index/about',array('com_id'=>$about_footer['com_id'],'about_id'=>$about_footer_son['about_id']))}">{pigcms{$about_footer_son['about_title']}</a>
									</volist>
								</li>
							</volist>
						</ul>
					</div>
					<div class="fl clr ewm">
						<div class="fl">
							<h2>智慧景区微信版</h2>
							<div class="img">
								<img src="{pigcms{$config['wechat_qrcode']}" width=72 height=72 >
							</div>
						</div>
						<!--<div class="fl">
							<h2>手机APP下载</h2>
							<div class="img">
								<img src="{pigcms{$config['wechat_qrcode']}" width=72 height=72 >
							</div>
						</div>-->
					</div>
					<div class="fr photo">
						<h2>24小时客户服务电话</h2>
						<p>{pigcms{$config['site_phone']}</p>
					</div>
				</div>
				<div class="bottom_end">
					友情链接：&nbsp;
					<volist name="footer_flink" id="flink">
						<a href="{pigcms{$flink['url']}">{pigcms{$flink['name']}</a>&nbsp;&nbsp;
					</volist>
				</div>
			</div>
		</div>
		<style type="text/css">
			.footer_txt {
				text-align: center;
				font-size: 12px;
				line-height: 24px;
			}
			.footer_txt a{
				color:#151515;
			}
		</style>
		<div class="bottom_bot">
			<div class="footer_txt">{pigcms{:nl2br($config['site_show_footer'],'<a>')}</div>
		</div>
	</footer>
	<!-- 导航 -->
	<section class="scan">
		<ul>
			<li class="code">
				<div class="display">
					<h2>扫描二维码</h2>
					<p>关注微信 下单优惠更多</p>
					<img src="{pigcms{$config['wechat_qrcode']}" width=122 height=122>
				</div>
			</li>
			<li class="Return"></li>
		</ul>
	</section>
	<!-- 登陆  -->
	<section class="board" style="z-index:1900;">
		<div class="board_top">
			<ul class="clr">
				<li class="icons on">登录</li>
				<li class="icons">注册</li>
<!--				<li class="slider"></li>-->
			</ul>
		</div>
		<div class="board_end board_end1">
			<div class="pc">
				<div class="transport">
					<i class="dl"></i>
					<input id="submit_phone" type="text" placeholder="请输入登录手机号">
				</div>
				<div class="transport">
					<i class="zc"></i>
					<input id="submit_pw" type="password" placeholder="请输入登录密码">
				</div>
				<div class="sport_auto clr">
					<div class="fl clr">
						<input type="checkbox" class="input_check">
						<span>下次自动登录</span>
					</div>
					<a href="{pigcms{:U('Login/forgetpwd')}" class="fr">忘记密码</a>
				</div>
				<input id="only_submit" type="submit" value="立即登录" class="sport_six"/>
				<a href="javascript:void(0)" class="dimension"></a>
			</div>
			<div class="pho" style="display:none;">
				<img id="wechat_code" src="{pigcms{$static_path}images/jqtb_03.jpg" width="196" height="196">
				<h2>扫描二维码登录智慧景区</h2>
				<p>请打开微信使用扫一扫 扫码二维码完成登录</p>
				<a href="javascript:void(0)" class="dimenpho"></a>
			</div>
		</div>
		<div class="board_end board_end2" style="display:none;">
			<div class="register">
				<div class="transport">
					<i class="dl"></i>
					<input id="submit_register_phone" type="text" placeholder="请输入登录手机号">
				</div>
				<div class="transport clr">
					<i class="mm"></i>
					<input id="receive_code" type="text" placeholder="请输入验证码" class="yzm fl">
					<div id="send_code" class="code fr">发送验证码</div>
				</div>
				<div class="transport">
					<i class="zc"></i>
					<input id="submit_register_pw" type="password" placeholder="请输入登录密码">
				</div>
				<input id="only_register_submit" type="submit" value="立即注册" class="sport_six sport_40">
			</div>
		</div>
		<div class="close"></div>
	</section>
	<div class="mark" style="z-index:1555;"></div>
	<script src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
	<script src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
	<script type="text/javascript">
		/*底部返回顶部*/
		$(window).scroll(function(){
			if ($(window).scrollTop() > 200){
				$(".Return").fadeIn();
			}else{
				$(".Return").fadeOut(500);
			}
		});
		$(".Return").click(function(){
			$('body,html').animate({
				scrollTop: 0
			},
			500);
			return false;
		});
		/*登陆页面*/
		$(".indexnav .navlogon").click(function(){
			$(".board").show();
			$(".mark").show();
			$(".board_top li:nth-child(1)").addClass("on").siblings("li").removeClass("on");
			$(".board_end").show().siblings(".board_end2").hide();
		})
		$("#all_order").click(function(){
			$(".board").show();
			$(".mark").show();
			$(".board_top li:nth-child(1)").addClass("on").siblings("li").removeClass("on");
			$(".board_end").show().siblings(".board_end2").hide();
		})
		$("#my_trip").click(function(){
			$(".board").show();
			$(".mark").show();
			$(".board_top li:nth-child(1)").addClass("on").siblings("li").removeClass("on");
			$(".board_end").show().siblings(".board_end2").hide();
		})
		$("#my_collection").click(function(){
			$(".board").show();
			$(".mark").show();
			$(".board_top li:nth-child(1)").addClass("on").siblings("li").removeClass("on");
			$(".board_end").show().siblings(".board_end2").hide();
		})
		/*注册页面*/
		$(".indexnav .navregister").click(function(){
			$(".board").show();
			$(".mark").show();
			$(".board_top li:nth-child(2)").addClass("on").siblings("li").removeClass("on");
			$(".board_end2").show().siblings(".board_end").hide();
		})
		/*关闭登录*/
		$(".board .close").click(function(){
			$(".board").hide();
			$(".mark").hide();
		})
		$(".board_top li.icons").click(function(){
			$(this).addClass("on").siblings().removeClass("on");
        	var index=$(this).index();
        	$(".board_end").eq(index).show(500).siblings(".board_end").hide(500);
		});
		$('.dimension').click(function(){
			art.dialog.open("{pigcms{$recognition_url}&"+Math.random(),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('login_iframe_handle',iframe);
				},
				id: 'login_handle',
				title:'请使用微信扫描二维码登录',
				padding: 0,
				width: 430,
				height: 433,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: null,
				left: '55%',
				top: '47%',
				opacity:'0.4',
				zIndex:'20000',
			});
			return false;
		});
		/*账号登陆*/
		$(".dimenpho").click(function(){
			$(".pho").hide().siblings(".pc").show();
		})
		/*登陆*/
		var generate_status	=	true;
		$("#only_submit").click(function(){
			login();
		})
		/*回车键登录*/
		$(".board_end1").keydown(function(event){
			if (event.keyCode == "13") {//keyCode=13是回车键
				login();
			}
		});
		function login(){
			var submit_phone	=	$("#submit_phone").val();
			if(!submit_phone){
				alert('手机号不能空为');
				return false;
			}
			var phone	=	checkPhone(submit_phone);
			if(phone == false){
				alert('手机号不正确');
				return false;
			}
			var submit_pw	=	$("#submit_pw").val();
			if(generate_status == true){
				generate_status	=	false;
				$.ajax({
					type : "post",
					url : "{pigcms{:U('Login/index_json')}",
					dataType : "json",
					data:{
						submit_phone	:	submit_phone,
						submit_pw	:	submit_pw,
					},
					async:false,
					success : function(date){
						if(date.error_code){
							alert(date.msg);
							generate_status	=	true;
							return false;
						}else{
							var action_name	=	"{pigcms{$action_name}";
							if(action_name == 'pwd_success'){
								window.location.href="{pigcms{:U('Index/index')}";
							}else{
								window.location.reload();
							}
						}
					},
					error:function(){
						alert('网络错误');
						generate_status	=	true;
						return false;
					}
				})
			}
		}
		/*注册*/
		$("#only_register_submit").click(function(){
			register();
		})
		/*回车键注册*/
		$(".board_end2").keydown(function(event){
			if (event.keyCode == "13") {//keyCode=13是回车键
				register();
			}
		});
		function register(){
			var submit_phone	=	$("#submit_register_phone").val();
			if(!submit_phone){
				alert('手机号不能空为');
				return false;
			}
			var phone	=	checkPhone(submit_phone);
			if(phone == false){
				alert('手机号不正确');
				return false;
			}
			var submit_pw	=	$("#submit_register_pw").val();
			var receive_code	=	$("#receive_code").val();
			if(generate_status == true){
				generate_status	=	false;
				$.ajax({
					type : "post",
					url : "{pigcms{:U('Login/reg_json')}",
					dataType : "json",
					data:{
						submit_phone	:	submit_phone,
						submit_pw		:	submit_pw,
						receive_code	:	receive_code,
					},
					async:false,
					success : function(date){
						if(date.error_code){
							alert(date.msg);
							generate_status	=	true;
							return false;
						}else{
							window.location.reload();
						}
					},
					error:function(){
						alert('网络错误');
						generate_status	=	true;
						return false;
					}
				})
			}
		}
		/*发送短信*/
		var countdown = 60;
		var countdown_status	=	true;
		var error_code	=	true;
		$("#send_code").click(function(){
			if(countdown_status == true){
				countdown_status	=	false;
				var submit_phone	=	$("#submit_register_phone").val();
				if(!submit_phone){
					countdown_status	=	true;
					alert('手机号不能空为');
					return false;
				}
				var phone	=	checkPhone(submit_phone);
				if(phone == false){
					countdown_status	=	true;
					alert('手机号不正确');
					return false;
				}
				if(countdown==60){
					$.ajax({
						url: '{pigcms{$config.site_url}/index.php?g=Index&c=Smssend&a=sms_send',
						type: 'POST',
						dataType: 'json',
						data: {phone: submit_phone,reg:1},
						success:function(date){
							if(date.error_code){
								countdown_status	=	true;
								error_code	=	date.error_code;
								alert(date.msg);
							}
						}
					});
				}
//				if(error_code == false){
					if (countdown == 0) {
						generate_status	=	true;
						$("#send_code").css("background","#fe4403");
						$("#send_code").text("发送验证码");
					} else {
						var intDiff = parseInt(countdown);//倒计时总秒数量
						var intDiffs	= timer(intDiff);
					}
//				}
				error_code	=	true;
			}
		})
		/*退出*/
		$("#only_quit").click(function(){
			if(generate_status == true){
				generate_status	=	false;
				$.ajax({
					type : "post",
					url : "{pigcms{:U('Login/logout')}",
					dataType : "json",
					data:{
					},
					async:false,
					success : function(result){
						if(result.errorCode == 0){
							location.href	=	result.result;
						}else{
							alert(result.errorMsg);
							generate_status	=	true;
							return false;
						}
					},
					error:function(){
						alert('网络错误');
						generate_status	=	true;
						return false;
					}
				})
			}
		})
		function checkPhone(phone){
			if(!(/^1[34578]\d{9}$/.test(phone))){
				return false;
			}
		}
		function checkCardId(socialNo){
			if(socialNo == ""){
				alert("输入身份证号码不能为空!");
				return (false);
			}
			if (socialNo.length != 15 && socialNo.length != 18){
				alert("输入身份证号码格式不正确!");
				return (false);
			}
			var area={11:"北京",12:"天津",13:"河北",14:"山西",15:"内蒙古",21:"辽宁",22:"吉林",23:"黑龙江",31:"上海",32:"江苏",33:"浙江",34:"安徽",35:"福建",36:"江西",37:"山东",41:"河南",42:"湖北",43:"湖南",44:"广东",45:"广西",46:"海南",50:"重庆",51:"四川",52:"贵州",53:"云南",54:"西藏",61:"陕西",62:"甘肃",63:"青海",64:"宁夏",65:"新疆",71:"台湾",81:"香港",82:"澳门",91:"国外"};
			if(area[parseInt(socialNo.substr(0,2))]==null){
				alert("身份证号码不正确(地区非法)!");
				return (false);
			}
			if (socialNo.length == 15){
				pattern= /^\d{15}$/;
				if (pattern.exec(socialNo)==null){
					alert("15位身份证号码必须为数字！");
					return (false);
				}
				var birth = parseInt("19" + socialNo.substr(6,2));
				var month = socialNo.substr(8,2);
				var day = parseInt(socialNo.substr(10,2));
				switch(month) {
					case '01':
					case '03':
					case '05':
					case '07':
					case '08':
					case '10':
					case '12':
						if(day>31) {
							alert('输入身份证号码不格式正确!');
							return false;
						}
						break;
					case '04':
					case '06':
					case '09':
					case '11':
						if(day>30) {
							alert('输入身份证号码不格式正确!');
							return false;
						}
						break;
					case '02':
						if((birth % 4 == 0 && birth % 100 != 0) || birth % 400 == 0){
							if(day>29) {
								alert('输入身份证号码不格式正确!');
								return false;
							}
						}else{
							if(day>28){
								alert('输入身份证号码不格式正确!');
								return false;
							}
						}
						break;
					default:
						alert('输入身份证号码不格式正确!');
						return false;
				}
				var nowYear = new Date().getYear();
				if(nowYear - parseInt(birth)<15 || nowYear - parseInt(birth)>100) {
					alert('输入身份证号码不格式正确!');
					return false;
				}
				return (true);
			}

			var Wi = new Array(
				7,9,10,5,8,4,2,1,6,
				3,7,9,10,5,8,4,2,1
			);
			var   lSum        = 0;
			var   nNum        = 0;
			var   nCheckSum   = 0;
			for (i = 0; i < 17; ++i){
				if(socialNo.charAt(i) < '0' || socialNo.charAt(i) > '9'){
					alert("输入身份证号码格式不正确!");
					return (false);
				}
				else{
					nNum = socialNo.charAt(i) - '0';
				}
				lSum += nNum * Wi[i];
			}
			if(socialNo.charAt(17) == 'X' || socialNo.charAt(17) == 'x'){
				lSum += 10*Wi[17];
			}else if(socialNo.charAt(17) < '0' || socialNo.charAt(17) > '9'){
				alert("输入身份证号码格式不正确!");
				return (false);
			}else{
				lSum += ( socialNo.charAt(17) - '0' ) * Wi[17];
			}
			if((lSum % 11) == 1){
				return true;
			}else{
				alert("输入身份证号码格式不正确!");
				return (false);
			}
		}
		/*倒计时*/
		function timer(intDiff){
			window.setInterval(function(){
				if(intDiff > 0){
					$("#send_code").css("background","#999");
					$("#send_code").text("已发送"+intDiff);
				}
				intDiff--;
				if(intDiff == -1){
					countdown_status	=	true;
					$("#send_code").css("background","#fe4403");
					$("#send_code").text("发送验证码");
				}
			}, 1000);
		}
	</script>
 	</body>
</html>