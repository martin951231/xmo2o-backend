<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$now_village.village_name}</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}weui/weui.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/city_car.css?216"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		<style>input[readonly]{color:#999;}</style>
	</head>
<body>
    <form method="post" id="check_form" action="{pigcms{:U('nCoV_2019_health_save', ['village_id' => $_GET['village_id']])}">
        <div class="page__bd">
			<div class="weui-cells__title">用户信息</div>
            <div class="weui-cells weui-cells_form" style="font-size:12px;">
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">姓名</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="name" id="name" type="text" placeholder="请输入姓名" value="{pigcms{$now_user_info.name}" <if condition="$now_user_info['name']">readonly="readonly"</if>/>
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">手机号</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="phone" id="phone" type="tel" placeholder="请输入手机号" value="{pigcms{$user_session.phone}" <if condition="$user_session['phone']">readonly="readonly"</if>/>
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">身份证号</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="id_card" id="id_card" type="text" placeholder="必填，输入后无法修改" value="{pigcms{$now_user_info.id_card}" <if condition="$now_user_info['id_card']">readonly="readonly"</if>/>
                    </div>
                </div>
				<div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">房屋</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="address" id="address" type="text" placeholder="楼号，单元，房间号" value="{pigcms{$now_user_info.address}" <if condition="$now_user_info['address']">readonly="readonly"</if>/>
                    </div>
                </div>
            </div>
			<div class="weui-cells__title">健康情况</div>
            <div class="weui-cells weui-cells_form" style="font-size:12px;">
				<div class="weui-cell weui-cell_select weui-cell_select-after" style="height:44px;line-height:44px;">
					<div class="weui-cell__hd">
						<label for="health_info" class="weui-label">1. 目前健康情况</label>
					</div>
				</div>
				<div class="weui-cell weui-cell_select weui-cell_select-after">
					<div class="weui-cell__bd">
						<select class="weui-select" name="health_info" id="health_info" style="direction:rtl;">
							<volist name="nCov_2019_health_type" id="vo">
								<option value="{pigcms{$key}" style="direction:ltr;">{pigcms{$vo}</option>
							</volist>
						</select>
					</div>
				</div>
                <div class="weui-cell weui-cell_select weui-cell_select-after" style="height:44px;line-height:44px;">
					<div class="weui-cell__hd">
						<label for="" class="weui-label" style="width:auto;">2. 过去15天内，是否到过疫区（含转车路过）</label>
					</div>
				</div>
				<div class="weui-cell weui-cell_active weui-cell_switch">
					<div class="weui-cell__bd"></div>
					<div class="weui-cell__ft">
						<input class="weui-switch" type="checkbox" name="go_hubei" value="1"/>
					</div>
				</div>
				<div class="weui-cell weui-cell_select weui-cell_select-after" style="height:44px;line-height:44px;">
					<div class="weui-cell__hd">
						<label for="health_info" class="weui-label" style="width:auto;">3. 过去15天内，是否外出其他省、市</label>
					</div>
				</div>
				<div class="weui-cell weui-cell_active weui-cell_switch">
					<div class="weui-cell__bd"></div>
					<div class="weui-cell__ft">
						<input class="weui-switch" type="checkbox" name="out_province" value="1"/>
					</div>
				</div>
				<div class="weui-cell weui-cell_select weui-cell_select-after" style="height:44px;line-height:44px;">
					<div class="weui-cell__hd">
						<label for="health_info" class="weui-label" style="width:auto;">4. 过去15天内，是否接触疫区及其他省市人员</label>
					</div>
				</div>
				<div class="weui-cell weui-cell_active weui-cell_switch">
					<div class="weui-cell__bd"></div>
					<div class="weui-cell__ft">
						<input class="weui-switch" type="checkbox" name="near_hubei_people" value="1"/>
					</div>
				</div>
			</div>
			<div class="weui-cells__title">其他信息</div>
            <div class="weui-cells weui-cells_form" style="font-size:12px;">
				<div class="weui-cell weui-cell_active weui-cell_select weui-cell_select-after">
					<div class="weui-cell__hd">
						<label for="" class="weui-label">体温 ℃</label>
					</div>
					<div class="weui-cell__bd">
						<select class="weui-select" name="temperature" id="temperature">
							<option value="-1">小于 37.3 ℃</option>
							<option value="-2">大于 37.3 ℃</option>
						</select>
					</div>
				</div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">备注</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="check_mark" id="check_mark" type="text" placeholder="可不输入"/>
                    </div>
                </div>
            </div>
            <div class="weui-btn-area mt3em">
                <button type="submit" class="weui-btn weui-btn_primary ">保存<span style="font-size:12px;">（出示给工作人员）</span></button>
            </div>
        </div>
    </form>
	<if condition="$cannot_out">
		<div class="page__hd add_hd" id="cannot_out">
			<div style="margin-top:20px;">
				<p class="page__desc" style="color:red;">您外出的次数已达上限，无法再次外出</p>
			</div>    
		</div>
	</if>
	<!--div class="page__bd">
		<div class="weui-panel weui-panel_access" style="margin-top:2em;">
			<div class="weui-panel__ft">
				<a href="{pigcms{:U('nCov_2019_history', ['village_id' => $now_village['village_id']])}" class="weui-cell weui-cell_active weui-cell_access weui-cell_link">
					<div class="weui-cell__bd">查看历史进出记录</div>
					<span class="weui-cell__ft"></span>
				</a>    
			</div>
		</div>
    </div-->
	<div class="weui-footer" style="margin:20px 0;">
		<p class="weui-footer__links">
			<a href="javascript:showQroce();" class="weui-footer__link">由 {pigcms{$config.site_name} 无偿提供</a>
		</p>
		<p class="weui-footer__text">共抗疫情，你我同在</p>
		<if condition="$privacy_policy_url">
			<p class="weui-footer__links">
				<a href="{pigcms{$privacy_policy_url}" class="weui-footer__link" style="color:#999999;font-size:12px;">您的信息受《隐私协议》保护</a>
			</p>
		</if>
	</div>
</body>
	<script type="text/javascript">
		$("#check_form").submit(function(){
			$('#name').val($.trim($('#name').val()));
            if($('#name').val().length < 2){
                $('#name').focus();
                alert('请输入姓名，最少2位');
                return false;
            }
			
			$('#phone').val($.trim($('#phone').val()));
            if($('#phone').val().length < 2){
                $('#phone').focus();
                alert('请输入手机号，最少2位');
                return false;
            }
			
			
            $('#id_card').val($.trim($('#id_card').val()));
            if($('#id_card').val().length != 15 && $('#id_card').val().length != 18){
                alert('请输入15位或18位的身份证号');
                return false;
            }
			// console.log($('#check_form').serialize());
			// return false;
            $.post($('#check_form').attr('action'), $('#check_form').serialize(), function(response){
                if(response.status == 1){
                    location.replace("{pigcms{:U('nCoV_2019_health_show', ['village_id' => $_GET['village_id']])}&id=" + response.info);
                }else{
                     alert(response.info);
                }
            });
			return false;
		});
	</script>
	<script type="text/javascript">
		function showQroce(){
			layer.open({
				title: '长按识别关注我们'
				,content: '<div class="showQroce"><img src="{pigcms{$config.wechat_qrcode}" style="width:250px;"/></div>'
			});
		}
		window.shareData = {  
			"moduleName":"Village",
			"moduleID":"0",
			"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>", 
			"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('nCoV_2019',array('village_id' => $now_village['village_id']))}",
			"tTitle": "在线抗击新冠肺炎【{pigcms{$now_village.village_name}】",
			"tContent": "该功能由{pigcms{$config.site_name}无偿提供"
		};
	</script>
	{pigcms{$shareScript}
</html>