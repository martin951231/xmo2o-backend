<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config.group_index_name|default='注册成为团长'}</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/apply.css?t={pigcms{:time()}"/>
	</head>
	<body>
		<div class="container">
			<div class="content">
				<h2 class="title">注册成为团长</h2>
				<h2 class="title small">引领品质生活</h2>

				<form onclick="return false;">
					<div class="input-box">
						<input type="text" name="name" placeholder="请输入您的姓名">
						<i class="icon iconfont gou" style="display: none">&#xe611;</i>
						<i class="icon iconfont cha" style="display: none">&#xe61f;</i>
					</div>
					<div class="input-box">
						<input type="text" name="address" placeholder="请输入您所在城市与区县名称">
						<i class="icon iconfont gou" style="display: none">&#xe611;</i>
						<i class="icon iconfont cha" style="display: none">&#xe61f;</i>
					</div>
					<div class="input-box">
                        <if condition="!empty($config['house_name'])">
                            <input type="text" name="home" placeholder="请输入您的{pigcms{$config['house_name']}名称">
                        <else />
                            <input type="text" name="home" placeholder="请输入您的小区名称">
                        </if>
						<i class="icon iconfont gou" style="display: none">&#xe611;</i>
						<i class="icon iconfont cha" style="display: none">&#xe61f;</i>
					</div>
					<div class="input-box">
						<input type="text" name="phone" placeholder="请输入您的手机号码">
						<i class="icon iconfont gou" style="display: none">&#xe611;</i>
						<i class="icon iconfont cha" style="display: none">&#xe61f;</i>
					</div>
					
					<div class="input-box">
						<input type="text" name="wx_number" placeholder="请输入您的微信号">
						<i class="icon iconfont gou" style="display: none">&#xe611;</i>
						<i class="icon iconfont cha" style="display: none">&#xe61f;</i>
					</div>
					<div class="input-box">
						<input class="submit" type="submit" id="submit" value="立即提交">
					</div>
				</form>

				<div class="input-box">
					<p>想成为团长吗？提交您的信息我们将尽快与您联系~</p>
					<!-- <a tel="{pigcms{$config.site_phone}"><p>联系电话：{pigcms{$config.site_phone}</p></a> -->
				</div>
			</div>
			
		</div>	

		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
		<script type="text/javascript">
			$(function(){
				var wh = $(window).height()
				$('body').height(wh);
				
				$("input").blur(function(){
					type_name = $(this).attr('name');
					check_account(type_name,$(this))
				});
				
				$('#submit').click(function(){
					window.scroll(0, 0);
					if(check_form()==0){
						$.post('{pigcms{:U('apply')}',$('form').serialize(),function(data){
							layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info,btn:['好的'],shadeClose:false,yes:function(){
								window.location.href= data.url;
							}});
						},'json')
					}
					
				})
				
			})
			
			function check_form(){
				var error_count = 0;
				$('input').each(function(i,val){
					console.log(check_account($(val).attr('name'),val))
					if(!check_account($(val).attr('name'),val)){
						error_count++;
					}
				})
				
				return error_count;
			}
			
			function check_account(type_name,obj){
				if((type_name=='name' || type_name=='address' ||type_name=='home' ||type_name=='wx_number') && $(obj).val()==''){
					$(obj).siblings('.gou').hide();
					$(obj).siblings('.cha').show();
					return false;
				}else if(type_name=='phone' && !/^[0-9]{11}$/.test($(obj).val()) ){
					$(obj).siblings('.gou').hide();
					$(obj).siblings('.cha').show();
					return false;
				}else{
					$(obj).siblings('.gou').show();
					$(obj).siblings('.cha').hide();
				}
				return true;
				
			}

		</script>
		<script type="text/javascript">
		window.shareData = {
			"moduleName":"Home",
			"moduleID":"0",
			"imgUrl": "<if condition="$config['village_group_wx_share_img']">{pigcms{$config.village_group_wx_share_img}<elseif condition="$config['wechat_share_img']"/>{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
			"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Village_group/apply')}",
			"tTitle": "注册成为团长",
			"tContent": "{pigcms{$config.site_name}"
		};
		</script>
		{pigcms{$shareScript}
	</body>
</html>