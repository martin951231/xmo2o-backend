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
<body>
    <div class="page__hd add_hd" style="padding:15px;">
        <h1 class="page__title">就餐信息登记</h1>
        <p class="page__desc">为全力做好新型肺炎的疫情防控工作，同时避免多人共用纸笔带来传染风险，请您如实填写以下信息登记。希望予以理解和配合，谢谢！</p>
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
                    <div class="weui-cell__hd"><label class="weui-label">住址</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="address" id="address" type="text" placeholder="请输入住址" value="{pigcms{$now_user.address}"/>
                    </div>
                </div>
				<div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">体温</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="temp" id="temp" type="number" step="0.01" placeholder="请输入体温" value="" />
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">备注</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" name="remark" id="check_mark" type="text" placeholder="可不输入"/>
                    </div>
                </div>
            </div>
			<div class="weui-btn-area mt3em" style="margin-top:20px;">
				<button type="submit" class="weui-btn weui-btn_primary ">提交</button>
			</div>
        </div>
    </form>
	<div class="weui-footer pay_img_box" style="margin:20px 0;">
		<p class="weui-footer__text">
			<if condition="isset($info['return_url'])">
				<a href="javascript:showUrl();" class="weui-footer__link">无接触支付餐费</a>
			<elseif condition="isset($info['pay_img_txt'])"/>
				<a href="javascript:showQroce();" class="weui-footer__link">无接触支付餐费</a>
			</if>
		</p>
	</div>
	<if condition="$adver">
		<div class="adver_box" style="margin:35px 20px 20px;">
			<a href="{pigcms{$adver.url}">
				<img src="{pigcms{$adver.pic}" style="width:100%;"/>
			</a>
		</div>
	</if>
	<div class="weui-footer weui-footer_fixed-bottom">
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
			
			if(!$('#temp').val()){
			  var tips = layer.open({
			    content: '请填写体温！',
			    btn: ['确定'],
			    end: function () {
			      layer.close(tips)
			    }
			  })
                return false;
            }
			
			if(!$('#address').val()){
			  var tips = layer.open({
			    content: '请填写住址！',
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
					$('.pay_img_box').show();
                	if (is_pay==2) {
				      	showQroce();
                	} else if (is_pay!=0) {
					  var tips = layer.open({
					    content: '提交成功，是否需要在线支付餐费？',
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
					    content: '提交成功，感谢您的配合!',
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