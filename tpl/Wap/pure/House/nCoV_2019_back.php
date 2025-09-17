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
		<style>
			input[readonly]{color:#999;}
			.red{color:red;}
			.weui-label{width:125px;}
		</style>
	</head>
<body>
    <form method="post" id="check_form" action="{pigcms{:U('nCoV_2019_back_save', ['village_id' => $_GET['village_id']])}">
        <div class="page__bd">
			<div class="weui-cells__title">用户信息</div>
            <div class="weui-cells weui-cells_form" style="font-size:14px;">
				<div class="weui-cell weui-cell_select weui-cell_select-after">
					<div class="weui-cell__hd">
						<label for="" class="weui-label">身份 <span class="red">*</span></label>
					</div>
					<div class="weui-cell__bd">
						<select class="weui-select" name="user_type" id="user_type" style="direction:rtl;">
							<option value="0" style="direction:ltr;">请选择</option>
							<option value="1" style="direction:ltr;">业主</option>
							<option value="2" style="direction:ltr;">租客</option>
						</select>
					</div>
				</div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">姓名 <span class="red">*</span></label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="name" id="name" type="text" placeholder="请输入姓名" value="{pigcms{$now_user_info.name}" <if condition="$now_user_info['name']">readonly="readonly"</if>/>
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">手机号 <span class="red">*</span></label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="phone" id="phone" type="tel" placeholder="请输入手机号" value="{pigcms{$user_session.phone}" <if condition="$user_session['phone']">readonly="readonly"</if>/>
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">身份证号 <span class="red">*</span></label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="id_card" id="id_card" type="text" placeholder="必填，输入后无法修改" value="{pigcms{$now_user_info.id_card}" <if condition="$now_user_info['id_card']">readonly="readonly"</if>/>
                    </div>
                </div>
				<div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">房屋 <span class="red">*</span></label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="address" id="address" type="text" placeholder="楼号，单元，房间号" value="{pigcms{$now_user_info.address}" <if condition="$now_user_info['address']">readonly="readonly"</if>/>
                    </div>
                </div>
            </div>
			
			<div class="weui-cells__title">工作信息</div>
            <div class="weui-cells weui-cells_form" style="font-size:14px;">
				<div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">单位名称</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="work_name" id="work_name" type="text" placeholder="请输入您的单位名称" value=""/>
                    </div>
                </div>
				<div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">单位电话</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="work_phone" id="work_phone" type="text" placeholder="请输入您的单位电话" value=""/>
                    </div>
                </div>
            </div>
			
			<div class="weui-cells__title">健康情况</div>
            <div class="weui-cells weui-cells_form" style="font-size:14px;">
				<div class="weui-cell weui-cell_active weui-cell_select weui-cell_select-after">
					<div class="weui-cell__hd">
						<label for="" class="weui-label">体温 ℃</label>
					</div>
					<div class="weui-cell__bd">
						<select class="weui-select" name="temperature" id="temperature" style="direction:rtl;">
							<option value="-1" style="direction:ltr;">小于 37.3 ℃</option>
							<option value="-2" style="direction:ltr;">大于 37.3 ℃</option>
						</select>
					</div>
				</div>
				<div class="weui-cell weui-cell_select weui-cell_select-after">
					<div class="weui-cell__hd">
						<label for="" class="weui-label">身体状况</label>
					</div>
					<div class="weui-cell__bd">
						<select class="weui-select" name="health_info" id="health_info" style="direction:rtl;">
							<volist name="nCov_2019_health_type" id="vo">
								<option value="{pigcms{$key}" style="direction:ltr;">{pigcms{$vo}</option>
							</volist>
						</select>
					</div>
				</div>
            </div>
			
			<div class="weui-cells__title">返程信息</div>
            <div class="weui-cells weui-cells_form" style="font-size:14px;">
				<div class="weui-cell weui-cell_active weui-cell_select weui-cell_select-after" style="padding:10px 15px;">
					<div class="weui-cell__hd">
						<label for="" class="weui-label">返程出发地 <span class="red">*</span></label>
					</div>
					<div class="weui-cell__bd" style="margin-right:14px;">
						<input class="weui-input" name="back_city" readonly id="back_city" type="text" placeholder="请选择" value="" style="text-align:right;color:black;"/>
					</div>
				</div>
				<div class="weui-cell weui-cell_select weui-cell_select-after">
					<div class="weui-cell__hd">
						<label for="" class="weui-label">交通工具 <span class="red">*</span></label>
					</div>
					<div class="weui-cell__bd">
						<select class="weui-select" name="back_traffic" id="back_traffic" style="direction:rtl;">
							<option value="" style="direction:ltr;">请选择</option>
							<option value="火车" style="direction:ltr;">火车</option>
							<option value="大巴" style="direction:ltr;">大巴</option>
							<option value="自驾" style="direction:ltr;">自驾</option>
							<option value="飞机" style="direction:ltr;">飞机</option>
						</select>
					</div>
				</div>
				<div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">车牌号/车次/航班 <span class="red">*</span></label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="car_number" id="car_number" type="text" placeholder="请输入" value="" style="text-align:right;color:black;"/>
                    </div>
                </div>
				<div class="weui-cell weui-cell_select weui-cell_select-after">
					<div class="weui-cell__hd">
						<label for="" class="weui-label">途经疫区 <span class="red">*</span></label>
					</div>
					<div class="weui-cell__bd">
						<select class="weui-select" name="pass_hubei" id="pass_hubei" style="direction:rtl;">
							<option value="" style="direction:ltr;">请选择</option>
							<option value="否" style="direction:ltr;">否</option>
							<option value="是" style="direction:ltr;">是</option>
						</select>
					</div>
				</div>
				<div class="weui-cell weui-cell_select weui-cell_select-after">
					<div class="weui-cell__hd">
						<label for="" class="weui-label">接触疑似病例 <span class="red">*</span></label>
					</div>
					<div class="weui-cell__bd">
						<select class="weui-select" name="near_case" id="near_case" style="direction:rtl;">
							<option value="" style="direction:ltr;">请选择</option>
							<option value="否" style="direction:ltr;">否</option>
							<option value="是" style="direction:ltr;">是</option>
						</select>
					</div>
				</div>
				<div class="weui-cell weui-cell_select weui-cell_select-after">
					<div class="weui-cell__hd">
						<label for="" class="weui-label">接触重点地区人员 <span class="red">*</span></label>
					</div>
					<div class="weui-cell__bd">
						<select class="weui-select" name="near_hubei" id="near_hubei" style="direction:rtl;">
							<option value="" style="direction:ltr;">请选择</option>
							<option value="否" style="direction:ltr;">否</option>
							<option value="是" style="direction:ltr;">是</option>
						</select>
					</div>
				</div>
            </div>
			
			<div class="weui-cells__title">备注信息</div>
            <div class="weui-cells weui-cells_form" style="font-size:14px;">
                <div class="weui-cell">
                    <div class="weui-cell__bd">
						<textarea class="weui-textarea" name="check_mark" id="check_mark" placeholder="请输入需要特别输入的内容，可不填" rows="3"></textarea>
                    </div>
                </div>
            </div>
			
            <div class="weui-btn-area mt3em">
                <button type="submit" class="weui-btn weui-btn_primary ">保存<span style="font-size:12px;">（出示给工作人员）</span></button>
            </div>
        </div>
    </form>
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
	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
	
	<!-- 城市插件 -->
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}ydui-citys/css/ydui.css?216"/>
	<script type="text/javascript" src="{pigcms{$static_path}ydui-citys/js/ydui.flexible.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}ydui-citys/js/ydui.citys.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}ydui-citys/js/ydui.js" charset="utf-8"></script>

	<script type="text/javascript">
		$("#check_form").submit(function(){
			
			if($('#user_type').val() == '0'){
                alert('请选择您的身份');
                return false;
            }
			
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
			
			$('#address').val($.trim($('#address').val()));
            if($('#address').val().length < 5){
                alert('请输入正确的房屋地址，最少5个字。（楼号，单元，房间号）');
                return false;
            }
			
			$('#back_city').val($.trim($('#back_city').val()));
            if($('#back_city').val() == ''){
                alert('请选择返程出发地');
                return false;
            }
			
			$('#back_city').val($.trim($('#back_city').val()));
            if($('#back_city').val() == ''){
                alert('请选择返程出发地');
                return false;
            }
			
			if($('#back_traffic').val() == ''){
                alert('请选择返程使用的交通工具');
                return false;
            }
			
			if($('#car_number').val() == ''){
                alert('请输入车牌号/车次/航班。若无，填写“无”');
                return false;
            }
			
			if($('#pass_hubei').val() == ''){
                alert('请选择是否途经疫区');
                return false;
            }
			
			if($('#near_case').val() == ''){
                alert('请选择是否接触疑似病例');
                return false;
            }
			
			if($('#near_hubei').val() == ''){
                alert('请选择是否接触重点地区人员');
                return false;
            }
			
			
			// console.log($('#check_form').serialize());
			// return false;
			
            $.post($('#check_form').attr('action'), $('#check_form').serialize(), function(response){
                if(response.status == 1){
                    location.replace("{pigcms{:U('nCoV_2019_back_show', ['village_id' => $_GET['village_id']])}&id=" + response.info);
                }else{
                     alert(response.info);
                }
            });
			return false;
		});
		
		//返程城市选择
		var $target = $('#back_city');
		$target.citySelect();
		$target.on('click', function (event) {
			event.stopPropagation();
			$target.citySelect('open');
		});
		$target.on('done.ydui.cityselect', function (ret) {
			console.log(ret)
			$(this).val(ret.country + ' ' + ret.provance + ' ' + ret.city + ' ' + ret.area);
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
</body>
</html>