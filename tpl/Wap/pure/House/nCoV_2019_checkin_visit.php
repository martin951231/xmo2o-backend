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
		<style>
			input[readonly]{color:#999;}
			.weui-navbar__item.weui-bar__item_on {
				background-color: {pigcms{$config.mobile_head_color};
				color: white;
			}
		</style>
	</head>
<body>
	<div class="weui-navbar">
		<div class="weui-navbar__item <if condition="$_GET['from'] eq 1">weui-bar__item_on</if>" onclick="location.replace('{pigcms{:U('nCoV_2019_checkin_visit', array_merge($_GET, ['from' => '1']))}')">进入</div>
		<div class="weui-navbar__item <if condition="$_GET['from'] eq 2">weui-bar__item_on</if>" onclick="location.replace('{pigcms{:U('nCoV_2019_checkin_visit', array_merge($_GET, ['from' => '2']))}')">离开</div>
	</div>
    <div class="page__hd add_hd" style="padding:60px 15px 10px;">
        <p class="page__desc"><a href="{pigcms{:U('empty_village_unit_list', ['village_id' => $now_village['village_id']])}">入住智慧小区，享在线智慧生活 ></a></p>
		<if condition="$last_in || $last_out">
			<div style="margin-top:20px;">
				<if condition="$last_in">
					<p class="page__desc">上次进入：{pigcms{$last_in.add_time|date='Y-m-d H:i:s',###}</p>
				</if>
				<if condition="$last_out">
					<p class="page__desc">上次离开：{pigcms{$last_out.add_time|date='Y-m-d H:i:s',###}</p>
				</if>
			</div>
		</if>
    </div>
    <form method="post" id="check_form" action="{pigcms{:U('nCoV_2019_save', ['village_id' => $_GET['village_id']])}">
        <div class="page__bd">
            <div class="weui-cells weui-cells_form" style="font-size:14px;">
				<div class="weui-cell weui-cell_select weui-cell_select-after">
					<div class="weui-cell__hd">
						<label for="" class="weui-label">身份</label>
					</div>
					<div class="weui-cell__bd">
						<select class="weui-select" name="user_type" id="user_type">
							<option value="0">请选择</option>
							<volist name="nCov_2019_user_type" id="vo">
								<option value="{pigcms{$key}" <if condition="$key eq $last_user_info['user_type']">selected="selected"</if>>{pigcms{$vo}</option>
							</volist>
						</select>
					</div>
				</div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">姓名</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="name" id="name" type="text" placeholder="请输入姓名" value="{pigcms{$last_user_info.name}"/>
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">房屋</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="address" id="address" type="text" placeholder="楼号，单元，房间号" value="{pigcms{$last_user_info.address}"/>
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
                        <input class="weui-input" name="id_card" id="id_card" type="text" placeholder="必填，15或18位身份证" value="{pigcms{$last_user_info.id_card}"/>
                    </div>
                </div>
                <input type="hidden" name="check_type" value="{pigcms{$_GET.from}"/>
                <if condition="$_GET['from'] eq 1">
					<div class="weui-cell weui-cell_active weui-cell_select weui-cell_select-after">
						<div class="weui-cell__hd">
							<label for="" class="weui-label">进入原因</label>
						</div>
						<div class="weui-cell__bd">
							<select class="weui-select" name="check_reason" id="check_reason">
								<option value="回家">回家</option>
								<option value="送快递">送快递</option>
								<option value="送外卖">送外卖</option>
								<option value="送水/气">送水/气</option>
								<option value="其他">其他（备注中填写）</option>
							</select>
						</div>
					</div>
				<else/>
					<div class="weui-cell weui-cell_active weui-cell_select weui-cell_select-after">
						<div class="weui-cell__hd">
							<label for="" class="weui-label">离开原因</label>
						</div>
						<div class="weui-cell__bd">
							<select class="weui-select" name="check_reason" id="check_reason">
								<option value="采购">采购</option>
								<option value="上班">上班</option>
								<option value="送快递离开">送快递离开</option>
								<option value="送外卖离开">送外卖离开</option>
								<option value="送水/气离开">送水/气离开</option>
								<option value="其他">其他（备注中填写）</option>
							</select>
						</div>
					</div>
				</if>
				<if condition="$_GET['from'] eq 1">
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
				</if>
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
	<div class="page__bd">
		<div class="weui-panel weui-panel_access" style="margin-top:2em;">
			<div class="weui-panel__ft">
				<a href="{pigcms{:U('nCov_2019_history', ['village_id' => $now_village['village_id']])}" class="weui-cell weui-cell_active weui-cell_access weui-cell_link">
					<div class="weui-cell__bd">查看历史进出记录</div>
					<span class="weui-cell__ft"></span>
				</a>    
			</div>
		</div>
    </div>
	<div class="weui-footer" style="margin:20px 0;">
		<p class="weui-footer__links">
            <if condition="$config['wechat_qrcode'] && $config['wechat_qrcode'] neq ''">
                <a href="javascript:showQroce();" class="weui-footer__link">由 {pigcms{$config.site_name} 无偿提供</a>
                <else/>
                <a href="javascript:void(0);" class="weui-footer__link">由 {pigcms{$config.site_name} 无偿提供</a>
            </if>
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
		if($('#cannot_out').size() > 0){
			$('#check_form').remove();
		}
		$("#check_form").submit(function(){
			$('#user_type').val($.trim($('#user_type').val()));
            if($('#user_type').val() == '0'){
                alert('请选择您的身份');
                return false;
            }
			
			$('#id_card').val($.trim($('#id_card').val()));
            if($('#id_card').val().length != 15 && $('#id_card').val().length != 18){
                alert('请输入15位或18位的身份证号');
                return false;
            }
			
            $.post($('#check_form').attr('action'), $('#check_form').serialize(), function(response){
                if(response.status == 1){
                    location.replace("{pigcms{:U('nCoV_2019_show', ['village_id' => $_GET['village_id']])}&id=" + response.info);
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