<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffIndex.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/ace.min.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}js/qweb/qwebchannel.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/storestaffIndex.js"></script>
		<script type="text/javascript">var countArr = [], titleArr = [],listArr=[];</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/new-base.js?t={pigcms{:time()}"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}"></script>
		<script type="text/javascript">
			var oldmd5langkey = localStorage.getItem('oldmd5langkey');
			if(typeof md5langkey != 'undefined'){
				if(oldmd5langkey != md5langkey && oldmd5langkey != null){
					localStorage.removeItem(oldmd5langkey);//去掉旧的，防止溢出
				}
				localStorage.setItem('oldmd5langkey',md5langkey);
				var _lang = localStorage.getItem(md5langkey);
				if(_lang == null){
					$.post("./appapi.php?c=Lang&a=js",{lang:'{pigcms{$config.system_lang}'},function(data){
						localStorage.setItem(data.key,data.lang);
						_lang = JSON.parse(data.lang);
					},'json');
				}
				_lang = JSON.parse(_lang);		
			}
			function L_(str){
				str = $.trim(str);
				if(typeof _lang == 'undefined'){
					return str;
				}
				if(str == ''){
				  return str;
				} else if(typeof _lang[str] != 'undefined'){
					return _lang[str] != '' && _lang[str] ? _lang[str] : str;	
				} else{
					$.post('./appapi.php?c=Lang&a=add',{key:str},function(data){});
					return str;
				}
			}
		</script>
		<style>
			.qweb_handle{
				position: fixed;
				top:10px;
				right: 10px;
				background: rgba(255,255,255,0.7);
				border-radius: 20px;
				padding: 5px 10px;
			}
			.qweb_handle div{
				padding: 0;
				display:inline-block;
				cursor:pointer;
			}
			.qweb_handle div:hover{
				background: rgba(0,0,0,0.2);
				border-radius:30px;
			}
			.qweb_handle .min_btn{
				margin-right: 5px;
			}
			.qweb_handle .close_btn{
				margin-left: 5px;
			}
			.qweb_handle img{
				width: 30px;
			}
			
		</style>
	</head>
	<body>
		<if condition="$pack_qweb_app">
			<div class="qweb_handle">
				<div class="min_btn">
					<img src="{pigcms{$static_public}js/qweb/min_web.png"/>
				</div>
				<div class="close_btn">
					<img src="{pigcms{$static_public}js/qweb/close_web.png"/>
				</div>
			</div>
		</if>
		<div class="infoBox">
			<div class="logo">
				<img src="<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_merchant_logo}</if>"/>
			</div>
			<div class="text">
				{pigcms{$staff_session.name}<if condition="$staff_session['type_name']">({pigcms{$staff_session['type_name']})</if>
				<br/>
				<span>{pigcms{$store.name}</span>
				<if condition="$lang_list">
					<!--语言环境切换-->
					<select id="change_lang" data-url="{pigcms{:U('Public/change_lang')}">
						<volist name="lang_list" id="vo">
							<option value="{pigcms{$vo.val}" <if condition="$config['system_lang'] eq $vo['val']">selected="selected"</if>>{pigcms{$vo.display}</option>
						</volist>
					</select>
				</if>
			</div>
		</div>
		<div class="pageBg"></div>
		<div class="pageLink">
			<ul>
				<if condition="$store['have_group']">
					<li class="group" data-url="{pigcms{$config.site_url}{pigcms{:U('group_list')}">
						<div class="icon"></div>
						<div class="text">{pigcms{$config.group_alias_name}</div>
						<div class="loader hide" id="group_loader"><em></em></div>
					</li>
					<script type="text/javascript">countArr.push('group'), titleArr.push('{pigcms{$config.group_alias_name}');</script>
				</if>
				<if condition="$staff_session['is_verify_ticket'] eq 1">
					<li class="scenic" data-url="{pigcms{$config.site_url}{pigcms{:U('ticket_order_list')}">
						<div class="icon"></div>
						<div class="text">{pigcms{$config.scenic_alias_name}</div>
						<div class="loader hide" id="scenic_loader"><em></em></div>
					</li>
					<script type="text/javascript">countArr.push('scenic'), titleArr.push('{pigcms{$config.scenic_alias_name}');</script>
				</if>
				<if condition="$store['have_meal']">
					<if condition="$config['store_open_table_type'] eq 1">
						<li class="meal" data-url="{pigcms{$config.site_url}{pigcms{:U('tmp_table')}">
					<else/>
						<li class="meal" data-url="{pigcms{$config.site_url}{pigcms{:U('foodshop')}">
					</if>
						<div class="icon"></div>
						<div class="text">{pigcms{$config.meal_alias_name}</div>
						<div class="loader hide" id="foodshop_loader"><em></em></div>
					</li>
					<script type="text/javascript">listArr.push('foodshop_call_service'),countArr.push('foodshop'), titleArr.push('{pigcms{$config.meal_alias_name}');</script>
				</if>
				<if condition="$store['have_shop']">
					<li class="shop" data-url="{pigcms{$config.site_url}{pigcms{:U('shop_list')}">
						<div class="icon"></div>
						<div class="text">{pigcms{$config.shop_alias_name}</div>
						<div class="loader hide" id="shop_loader"><em></em></div>
					</li>
					<script type="text/javascript">countArr.push('shop'), titleArr.push('{pigcms{$config.shop_alias_name}');</script>
				</if>
				<if condition="$store['have_mall']">
					<li class="mall" data-url="{pigcms{$config.site_url}{pigcms{:U('shop_list',array('shop_type'=>2))}">
						<div class="icon"></div>
						<div class="text">{pigcms{$config.mall_alias_name}</div>
						<div class="loader hide" id="mall_loader"><em></em></div>
					</li>
					<script type="text/javascript">countArr.push('mall'), titleArr.push('{pigcms{$config.mall_alias_name}');</script>
				</if>
				<if condition="$store['have_appoint']">
					<li class="appoint" data-url="{pigcms{$config.site_url}{pigcms{:U('appoint_list')}">
						<div class="icon"></div>
						<div class="text">{pigcms{$config.appoint_alias_name}</div>
						<div class="loader hide" id="appoint_loader"><em></em></div>
					</li>
					<script type="text/javascript">countArr.push('appoint'), titleArr.push('{pigcms{$config.appoint_alias_name}');</script>
				</if>
				<if condition="$config['is_cashier'] OR $config['pay_in_store']">
					<li class="store" data-url="{pigcms{$config.site_url}{pigcms{:U('store_order')}">
						<div class="icon"></div>
						<div class="text">{pigcms{$config.cash_alias_name}</div>
						<div class="loader hide" id="cash_loader"><em></em></div>
					</li>
					<li class="arrival" data-url="{pigcms{$config.site_url}{pigcms{:U('store_arrival')}">
						<div class="icon"></div>
						<div class="text">{pigcms{:L_("店内收银")}</div>
						<div class="loader hide" id="store_loader"><em></em></div>
					</li>
					<script type="text/javascript">countArr.push('cash'), titleArr.push('{pigcms{$config.cash_alias_name}');</script>
				</if>
				<if condition="!$config['open_multilingual']">
					<!-- 开启多语言不显示 -->
					<if condition="!$config['single_system']">
						<li class="coupon" data-url="{pigcms{$config.site_url}{pigcms{:U('coupon_list')}">
							<div class="icon"></div>
							<div class="text">{pigcms{:L_("优惠券")}</div>
						</li>
					</if>
					<if condition="$store['have_meal'] OR $config['is_cashier'] OR $config['pay_in_store']">
						<li class="report" data-url="{pigcms{$config.site_url}{pigcms{:U('report')}">
							<div class="icon"></div>
							<div class="text">{pigcms{:L_("报表统计")}</div>
						</li>
					</if>
					<if condition="!$config['single_system']">
						<li class="physical_card" data-url="{pigcms{$config.site_url}{pigcms{:U('physical_card')}">
							<div class="icon"></div>
							<div class="text">{pigcms{:L_("实体卡管理")}</div>
						</li>
					</if>
				</if>
				<if condition="$staff_session['type'] eq 2">
					<li class="store_manage" data-url="{pigcms{:U('Config/store', '', true, false, true)}">
						<div class="icon"></div>
						<div class="text">{pigcms{:L_("店铺管理")}</div>
					</li>
				</if>
				<if condition="$store['store_wx_sub_mchid'] neq '' OR $store['store_alipay_sub_mchid'] neq ''">
					<li class="store_manage" data-url="{pigcms{:U('money_list', '', true, false, true)}">
						<div class="icon"></div>
						<div class="text">{pigcms{:L_("店铺余额")}</div>
					</li>
				</if>
				
				<if condition="$config['open_sub_card'] eq 1">
					<li class="sub_card" data-url="{pigcms{:U('sub_card', '', true, false, true)}">
						<div class="icon"></div>
						<div class="text">{pigcms{:L_("免单套餐")}</div>
					</li>
				</if>
				
				<li class="printer" style="display:none;">
					<div class="icon"></div>
					<div class="text">{pigcms{:L_("打印机参数")}</div>
				</li>
				<li class="logout" data-confirm="{pigcms{:L_("您确定要退出吗？")}" data-url="{pigcms{$config.site_url}{pigcms{:U('logout')}">
					<div class="icon"></div>
					<div class="text">{pigcms{:L_("退出")}</div>
				</li>
			</ul>
		</div>
	</body>
	<script type="text/javascript">
		var notification = null;
		$('body').append('<div id="checkWindowStatus" class="show"></div>');
		window.onfocus = function(){
			$('#checkWindowStatus').addClass('show');
			if(notification != null){
				notification.close();
			}
		}
		window.onblur = function(){
			$('#checkWindowStatus').removeClass('show');
		} 
		$('body').append('<audio style="display:none;" id="playMp3Tip" controls="true" loop="loop" src="{pigcms{$config.site_url}/static/file/new_order.mp3"></audio>');
		var nowIndex = 0;
		var timeArr = {};
		var playMp3Tip = null;
		var newOrderTip = null;
		var listTimeArr = {};
		var nowListIndex = 0;
		var playMp3Tip1 = null;
		
		if(window.Notification){
			if(Notification.permission === 'granted'){
				//any thing
			}else{
				Notification.requestPermission();
			}
		}
		
		setInterval(function(){
			var nowType = countArr[nowIndex];
			$('.loader').removeClass('on');
			$('#'+nowType+'_loader').addClass('on').show();
			$.post('/store.php?g=Merchant&c=Store&a=' + nowType + '_count', {time:timeArr[nowType]}, function(response){
				var data = response.data;
				timeArr[nowType] = data.time;
				$('#'+nowType+'_loader em').html(data.count);
				$('.loader').removeClass('on');
				
				if(data.count > 0){
					if(newOrderTip != null){
						layer.close(newOrderTip);
					}
					if(pack_qweb_app){
						max_window();
					}
					
					if(playMp3Tip == null){
						if(pack_qweb_app){
							play_mp3($('#playMp3Tip').attr('src'),'new_order',1000);
						}else if(checkAndroidApp()){
							window.pigcmspackapp.voicetip($('#playMp3Tip').attr('src'),1000);
						}else{
							$('#playMp3Tip').trigger('play');
						}
					}
					
					var tipTitle = '{pigcms{:L_("您有新的")}' + titleArr[nowIndex] + '{pigcms{:L_("订单需要处理。")}';
					
					/*{pigcms{:L_("音乐播放5分钟")}*/
					playMp3Tip = setTimeout(function(){
						if(pack_qweb_app){
							stop_mp3();
						}else if(checkAndroidApp()){
							window.pigcmspackapp.voicetip_stop();
						}else{
							$('#playMp3Tip').trigger('pause');
						}
					}, 300000);
					newOrderTip = layer.open({
						title:'{pigcms{:L_("新订单提示")}'
						,content:tipTitle
						,btn: ['{pigcms{:L_("确定")}']
						,end: function(index){
							if(pack_qweb_app){
								stop_mp3();
							}else if(checkAndroidApp()){
								window.pigcmspackapp.voicetip_stop();
							}else{
								$('#playMp3Tip').trigger('pause');
							}
							clearTimeout(playMp3Tip);
							playMp3Tip = null;
							newOrderTip = null;
						}
					});
					if(!$('#checkWindowStatus').hasClass('show')){
						notification = new Notification(tipTitle,{body:"{pigcms{$config.site_name}",icon:"<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>"});
						notification.onclick = function(){
							notification.close();
							notification = null;
							$('#checkWindowStatus').addClass('show');
							alert(tipTitle);
						}
					}
				}
				if(nowIndex+1 == countArr.length){
					nowIndex = 0;
				}else{
					nowIndex++;
				}
			}, 'json');

			var nowListType = listArr[nowListIndex];

			if(typeof( nowListType) !='undefined'){
				$.post('/store.php?g=Merchant&c=Store&a=' + nowListType + '_count', {time:listTimeArr[nowListType]}, function(response){
					var data = response.data;
					listTimeArr[nowListType] = data.time;
					if(data.count > 0){
						if(pack_qweb_app){
							max_window();
						}
						var tipTitle = '';
								console.log(playMp3Tip1)
						// "多个通知，多个弹窗")}
						if (data.list) {
							for (var i = 0; i < data.list.length; i++) {
								

								tipTitle = '【' + data.list[i].name+ '{pigcms{:L_("】的客人呼叫服务，请您及时提供服务")}';
								if(pack_qweb_app){
									max_window();
								}
								// if(playMp3Tip1 == null){
									if ( $('#playMp3Tip1').length > 0 ) {
										$('#playMp3Tip1').attr('src',data.list[i].voice_mp3);
									}else{
										$('body').append('<audio style="display:none;" id="playMp3Tip1" controls="true" loop="loop" src="'+data.list[i].voice_mp3+'"></audio>');
									}
									if(pack_qweb_app){
										play_mp3($('#playMp3Tip1').attr('src'),'new_order',1000);
									}else{
										$('#playMp3Tip1').trigger('play');
										console.log('-----play------')
									}
								// }
								/*{pigcms{:L_("音乐播放5分钟")}*/
								playMp3Tip1 = setTimeout(function(){
									if(pack_qweb_app){
										stop_mp3();
									}else{
										$('#playMp3Tip1').trigger('pause');
									}
								}, 300000);

								layer.open({
									type: 1,
									area: ['300px','150px'],
									title:'{pigcms{:L_("呼叫服务通知")}',
									content: '<div style="text-align:center; font-size: 14px; margin-top: 15px;">'+tipTitle+'</div>',
									zIndex: layer.zIndex, //"重点")}1
									success: function(layero){
										layer.setTop(layero); //"重点")}2
									},
									btn: ['{pigcms{:L_("确定")}'],
									end: function(index, layero){
										if(pack_qweb_app){
											stop_mp3();
										}else{
											$('#playMp3Tip1').trigger('pause');
										}
										clearTimeout(playMp3Tip1);
										playMp3Tip1 = null;
									}
								});
							}
						}
					}
					if(nowListIndex+1 == listArr.length){
						nowListIndex = 0;
					}else{
						nowListIndex++;
					}
				}, 'json');
			}

		},5000);
		
		//"判断在pc打包软件中打开")}
		if(pack_qweb_app){
			stop_mp3();
			change_window("0","0");
			
			check_soft("print_soft.exe","no_tip",1,"");
			setInterval(function(){
				check_soft("print_soft.exe","no_tip",1,"");
			},3000);
			
			$('.qweb_handle .min_btn').click(function(){
				min_window();
			});
			$('.qweb_handle .close_btn').click(function(){
				layer.open({
					title:'{pigcms{:L_("确认提醒：")}'
					,content:'{pigcms{:L_("您确定要关闭软件吗？")}'
					,btn: ['{pigcms{:L_("确定','取消")}']
					,yes: function(index){
						close_window();
					}
				});
			});
		}
	</script>
	<if condition="$pack_qweb_app && empty($_SESSION['check_soft_version'])">
		<script type="text/javascript">
			if(pack_qweb_app){
				get_soft_name('soft_name_callback');
			}
			function soft_name_callback(name){
				check_soft('up.exe',name,'1','');
			}
		</script>
	</if>
	<php>$_SESSION['check_soft_version']=true;</php>
    <!-- 密码为123456弹窗 -->
	<?php if ($staff_session['password']=='e10adc3949ba59abbe56e057f20f883e' && $config['pwd_verify']==1): ?>
        <div class="notice_login">
            <p class="p_til">{pigcms{:L_("您的登录密码是系统默认密码，为了账户安全，请您设置新的登录密码")}</p> 
            <div class="input_block">
                <input type="password" placeholder="{pigcms{:L_('请输入6位以上的新密码')}" name="password" />
            </div>
            <div class="input_block">
                <input type="password" placeholder="{pigcms{:L_('请再次输入新密码')}" name="password1" />
            </div>
            <div class="btn_submit">
                <a href="javascript:;" class="btn_ok">{pigcms{:L_("确认修改")}</a>
                <a href="javascript:;" class="btn_pass">{pigcms{:L_("跳过")}</a> 
            </div> 
        </div>  
        <style>
            .notice_login{
                padding: 20px; 
                background: #fff;
                border-radius: 10px; 
                display: none;
            }
            .notice_login .p_til{ 
                font-size: 14px;
                padding-bottom: 10px;
                color: #999;
            }
            .input_block input{
                border: 1px solid #eee;
                height: 30px;
                line-height: 30px;
                padding: 5px;
                width: 100%;
                margin: 10px 0;
            }
            .btn_submit{
                width: 100%;
                margin: 0 auto;
            }
            .btn_submit a{
                width: 100%;
                display: block;
                height: 36px;
                line-height: 36px;
                text-align: center;
                border:1px solid #4c69fc;
               /* margin: 15px 1% 0 1%; */
                margin-top: 15px;
                border-radius: 8px;
                font-size: 14px;
                display: inline-block;
                float: left;
				text-decoration: none;
            }
            .btn_submit a.btn_ok{
                background: #4c69fc;
                color: #fff;
            }
            .btn_submit a.btn_pass{
                color: #4c69fc;
            }
            .input_block input{
                font-size: 14px;
                border-radius: 4px;
            }
        </style>

		<script>
			var is_login_from = "{pigcms{$_SESSION['is_login_from']}";
			<?php $_SESSION['is_login_from']=0; ?>
			if (is_login_from==1) {
		    	layer.open({
					type: 1,
					title: false,
					closeBtn: 1,
					shadeClose: true,
					skin: 'notice_login',
					content: $('.notice_login'),
					success:function(){
						$('.notice_login').show();
					}
			   	}); 
			}

			$('.btn_ok').click(function(){
				var password = $('input[name=password]').val();
				var password1 = $('input[name=password1]').val();
				if (password!=password1) {
					layer.alert('两次输入密码不一样');
					return false;
				}
				$.post("{pigcms{:U('changePwd')}",{password:password},function (data) {
					console.log(data)
					if (data.error>0) {
						layer.alert(data.msg);
					}else{
						layer.alert(data.msg,function(){
							window.location.reload();
						});
						
					}
				},'json')
		   	})
		   
			$('.btn_pass').click(function(){
       			layer.closeAll()
				$('.notice_login').hide();
				$('#layui-layer-shade1').hide();
			})
		</script>
	<?php endif ?>
    <!-- 密码为123456弹窗 end -->
</html>