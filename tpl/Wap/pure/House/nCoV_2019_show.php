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
			.green{color:green;}
			.red{color:red;}
            .form_con{width:100vw;}
		</style>
	</head>
<body>
    <div class="page__hd add_hd">
        <h1 class="page__title">新型冠状病毒防护登记</h1>
        <p class="page__desc">登记成功，请将此页面展示给工作人员查看，感谢您的配合！请您日常勤洗手，注意防范病毒感染。</p>
    </div>
    <div class="form_con">
        <form method="post" id="check_form" action="{pigcms{:U('nCoV_2019_save', ['village_id' => $_GET['village_id']])}">
            <div class="page__bd">
                <div class="weui-form-preview">
                    
                    <div class="weui-form-preview__hd">
                        <div class="weui-form-preview__item">
                            <label class="weui-form-preview__label">姓名</label>
                            <em class="weui-form-preview__value">{pigcms{$now_ncov_2019.name}</em>
                        </div>
                    </div>
                    <div class="weui-form-preview__bd">
                        <if condition="$now_ncov_2019['user_type']">
                            <div class="weui-form-preview__item">
                                <label class="weui-form-preview__label">身份</label>
                                <span class="weui-form-preview__value">{pigcms{$nCov_2019_user_type[$now_ncov_2019['user_type']]}</span>
                            </div>
                        </if>
                        <div class="weui-form-preview__item">
                            <label class="weui-form-preview__label">楼号</label>
                            <span class="weui-form-preview__value">{pigcms{$now_ncov_2019.address}</span>
                        </div>
                        <div class="weui-form-preview__item">
                            <label class="weui-form-preview__label">手机号</label>
                            <span class="weui-form-preview__value">{pigcms{$now_ncov_2019.phone}</span>
                        </div>
                        <div class="weui-form-preview__item">
                            <label class="weui-form-preview__label">身份证</label>
                            <span class="weui-form-preview__value">{pigcms{$now_ncov_2019.id_card}</span>
                        </div>
                        <div class="weui-form-preview__item">
                            <label class="weui-form-preview__label">进出类别</label>
                            <span class="weui-form-preview__value"><if condition="$now_ncov_2019['check_type'] eq 1">进入<elseif condition="$now_ncov_2019['check_type'] eq 2"/>离开</if></span>
                        </div>
                        <div class="weui-form-preview__item">
                            <label class="weui-form-preview__label"><if condition="$now_ncov_2019['check_type'] eq 1">进入<elseif condition="$now_ncov_2019['check_type'] eq 2"/>离开</if>事由</label>
                            <span class="weui-form-preview__value">{pigcms{$now_ncov_2019.check_reason}</span>
                        </div>
                        <if condition="$now_ncov_2019['check_type'] eq 1">
                            <div class="weui-form-preview__item">
                                <label class="weui-form-preview__label">体温 ℃</label>
                                <span class="weui-form-preview__value">
                                    <if condition='$now_ncov_2019["temperature"] eq -1 || ($now_ncov_2019["temperature"] gt 0 && $now_ncov_2019["temperature"] lt 37.3)'>
                                        <span class="green">小于 37.3 ℃</span>
                                    <elseif condition='$now_ncov_2019["temperature"] eq -2 || ($now_ncov_2019["temperature"] gt 0 && $now_ncov_2019["temperature"] egt 37.3)'/>
                                        <span class="red">大于 37.3 ℃</span>
                                    <else/>
                                        <span class="red">{pigcms{$now_ncov_2019.temperature} ℃</span>
                                    </if>
                                </span>
                            </div>
                        </if>
                        <div class="weui-form-preview__item">
                            <label class="weui-form-preview__label">备注</label>
                            <span class="weui-form-preview__value">{pigcms{$now_ncov_2019.check_mark}</span>
                        </div>
                        <div class="weui-form-preview__item">
                            <label class="weui-form-preview__label">时间</label>
                            <span class="weui-form-preview__value">{pigcms{$now_ncov_2019.add_time|date='Y-m-d H:i:s',###}</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
	<if condition="$_GET['from'] != 'history'">
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
	</if>
</body>
	<script type="text/javascript">
		$("#check_form").submit(function(){
		
            $('#id_card').val($.trim($('#id_card').val()));
            if($('#id_card').val().length != 15 && $('#id_card').val().length != 18){
                $('#id_card').focus();
                alert('请输入15位或18位的身份证号码');
                return false;
            }
            
            $('#id_card').val($.trim($('#id_card').val()));
            if($('#id_card').val().length != 15 && $('#id_card').val().length != 18){
                $('#id_card').focus();
                alert('请输入15位或18位的身份证号码');
                return false;
            }
            
            $('#check_reason').val($.trim($('#check_reason').val()));
            if($('#check_reason').val().length < 2){
                $('#check_reason').focus();
                alert('请输入' + $('#reason_label').text() + '，最少2位');
                return false;
            }
            
            
            $('#temperature').val($.trim($('#temperature').val()));
            if($('#temperature').val() < 2){
                $('#temperature').focus();
                alert('请输入体温');
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
</html>