<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$info['name']|default='公共防控'}</title>
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
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}prevention_control/js/control.js?t=35" charset="utf-8"></script>
		<style>
			input[readonly]{color:#999;}
			.weui-navbar__item.weui-bar__item_on {
				background-color: {pigcms{$config.mobile_head_color};
				color: white;
			}
			.icon_address {
		        position: absolute;
			    line-height: 18px;
			    padding: 11px 20px;
			    right: 0;
			    top: 0px;
			    width: 20px;
			}
			.icon_address img {
			    width: 100%;
			}
			.weui-cell{ padding: 13px 15px; }
			.address_txt {width: calc(100% - 40px);}
			body{padding-bottom:60px!important;}
		</style>
	</head>
<body style="zoom: 1;padding-bottom:60px;">
    <div class="page__hd add_hd" style="padding:15px;">
        <h1 class="page__title">乘车信息登记</h1>
        <p class="page__desc">为全力做好新型肺炎的疫情防控工作，同时避免多人共用纸笔带来传染风险，请乘客如实填写以下信息登记。希望予以理解和配合，谢谢！</p>
        <p class="page__desc">请在下车前再次扫码补充下车地点完成本次行程。</p>
    </div>
    <form method="post" id="check_form" action="{pigcms{:U('save', ['control_id' => $_GET['control_id']])}">
		<if condition="$write_info && $write_info.id">
			<input type="hidden" name="id" value="{pigcms{$write_info.id}"/>
		</if>
		<input type="hidden" name="control_id" value="{pigcms{$_GET['control_id']}"/>
        <div class="page__bd">
            <div class="weui-cells weui-cells_form" style="font-size:14px;">
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">姓名</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="name" id="name" type="text" placeholder="请输入姓名" value="{pigcms{$now_user.truename}"/>
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">联系电话</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="phone" id="phone" type="text" placeholder="请输入联系电话" value="{pigcms{$now_user.phone}" <if condition="$now_user['phone']">readonly="readonly"</if>/>
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">上车地点</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input address_txt" name="start_address" id="start_address" type="text" placeholder="请填写或者授权获取上车地点" value="{pigcms{$write_info.start_address}"/>
                        <div class="icon_address start_address_location">
							<img src="{pigcms{$static_path}prevention_control/img/icon_address.png">
                        </div>
                    </div>
                </div>
                <if condition="$write_info">
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">下车地点</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input address_txt" name="end_address" id="end_address" type="text" placeholder="请填写或者授权获取下车地点" value="{pigcms{$write_info.end_address}"/>
                        <div class="icon_address end_address_location">
							<img src="{pigcms{$static_path}prevention_control/img/icon_address.png">
                        </div>
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">备注</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="remark" id="check_mark" type="text" placeholder="可不输入"/>
                    </div>
                </div>
                </if>
            </div>
            <if condition="$write_info">
		        <div class="weui-btn-area mt3em" style="margin-top:20px;">
		            <button type="submit" class="weui-btn weui-btn_primary ">提交</button>
		        </div>
		        <else/>
		        <div class="weui-btn-area mt3em" style="margin-top:20px;">
		            <button type="submit" class="weui-btn weui-btn_primary ">保存出发点</button>
		        </div>
            </if>
        </div>
    </form>
	<if condition="$adver">
		<div class="adver_box" style="margin:35px 20px 20px;">
			<a href="{pigcms{$adver.url}">
				<img src="{pigcms{$adver.pic}" style="width:100%;"/>
			</a>
		</div>
	</if>
	<div class="weui-footer pay_img_box" style="margin:20px 0;display: none;">
		<p class="weui-footer__text">
			<if condition="isset($info['return_url'])">
				<a href="javascript:showUrl();" class="weui-footer__link">无接触支付车费</a>
				<elseif condition="isset($info['pay_img_txt'])"/>
				<a href="javascript:showQroce();" class="weui-footer__link">无接触支付车费</a>
			</if>
		</p>
	</div>
	<div class="weui-footer weui-footer_fixed-bottom" style="background-color:#f8f8f8;bottom:0;padding:5px 0;">
		<p class="weui-footer__links">
			<a href="javascript:showPlatQroce();" class="weui-footer__link">由 {pigcms{$config.site_name} 无偿提供</a>
		</p>
		<p class="weui-footer__text">共抗疫情，你我同在</p>
	</div>
</body>
	<script type="text/javascript">
		var submit_btn = true;
		$("#check_form").submit(function(){
            if(!submit_btn){
                return false;
            }
            if(!$('#name').val()){
			  var tips = layer.open({
			    content: '请填写姓名！',
			    btn: ['确定'],
			    end: function () {
			      layer.close(tips)
			    }
			  })
                return false;
            }
            if(!$('#start_address').val()){
			  var tips = layer.open({
			    content: '请最少填写上车地点',
			    btn: ['确定'],
			    end: function () {
			      layer.close(tips)
			    }
			  })
                return false;
            }
            if($('#start_address').val()==$('#end_address').val()){
			  var tips = layer.open({
			    content: '上车地点和下车地点不能一致，请修改',
			    btn: ['确定'],
			    end: function () {
			      layer.close(tips)
			    }
			  })
                return false;
            }
           
            var is_pay = "{pigcms{$is_pay}";
            var is_end = "{pigcms{$is_end}";
            $.post($('#check_form').attr('action'), $('#check_form').serialize(), function(response){
                if(response.status == 1){
                	if (is_end==1) {
                		$('.pay_img_box').show();
                	}
                	if (is_end==0) {
					  var tips = layer.open({
					    content: '出发点保存成功，请在下车前再次扫码保存终点位置并进行在线支付车费!',
					    btn: ['确定'],
					    end: function () {
					      layer.close(tips)
					    }
					  })
                	} else if (is_end==1 && is_pay==2) {
				      	showQroce();
                	} else if (is_end==1 && is_pay!=0) {
					  var tips = layer.open({
					    content: '提交成功，是否需要在线支付车费？',
					    btn: ['去支付','取消'],
					    yes: function () {
					      layer.close(tips)
					      if (is_pay==1) {
				      		showUrl();
					      } else {
					      	showQroce();
					      }
					    },
					    no: function () {
					      layer.close(tips)
					    },
					    shadeClose: false
					  })

                	} else {
					  var tips = layer.open({
					    content: '提交成功，如需再次填写，请重新扫码!',
					    btn: ['确定'],
					    end: function () {
					      layer.close(tips);
					    }
					  })
                	}
                   submit_btn = false;
                   $('.weui-btn_primary').css('background-color','#999999');
                }else{
                     alert(response.info);
                }
            });
			return false;
		});
		
	</script>
	<script type="text/javascript">
		function showUrl(){
			var url = "{pigcms{$info['return_url']}";
			window.location.href = url;
		}
		function showPlatQroce(){
			layer.open({
				title: '长按识别关注我们'
				,content: '<div class="showQroce"><img src="{pigcms{$config.wechat_qrcode}" style="width:250px;"/></div>'
			});
		}
		function showQroce(){
			layer.open({
				title: '长按识别无接触付款'
				,content: '<div class="showQroce"><img src="{pigcms{$info.pay_img_txt}" style="width:250px;"/></div>'
				,shadeClose: false
			});
		}
	</script>
	{pigcms{$hideScript}
</html>