<!--<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">-->
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={pigcms{:C('DEFAULT_CHARSET')}" />
		<title>网站后台管理</title>
		<script type="text/javascript">
			/*<if condition="!C('butt_open')">
				if(self==top){window.top.location.href="{pigcms{:U('Index/index')}";}
			<else/>
				if(self==top){window.top.location.href="{pigcms{:C('butt_system_url')}";}
			</if>*/
			var kind_editor=null,static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",system_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}",choose_market="{pigcms{:U('Area/ajax_market')}",choose_map="{pigcms{:U('Map/frame_map',array('city_id'=>$map_default_city_id))}",get_firstword="{pigcms{:U('Words/get_firstword')}",frame_show=<if condition="$_GET['frame_show']">true<else/>false</if>;
 var  meal_alias_name = "{pigcms{$config.meal_alias_name}",parentShowHelpParam = [],parentShowIndex = false,choose_provincess="{pigcms{:U('Area/ajax_province')}",choose_cityss="{pigcms{:U('Area/ajax_city')}";
            var choose_street="{pigcms{:U('Area/ajax_street')}",choose_community="{pigcms{:U('Area/ajax_community')}",choose_house_village="{pigcms{:U('Area/ajax_house_village')}";
        </script>
<!--		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/style.css" />-->
		<link rel="stylesheet" type="text/css" href="/tpl/System/Static/css/style.css" />
        <link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
        <link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css?t=01">
        <link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.css">
        <link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.min.css">
        <link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
        <link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
        <link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
        <link rel="stylesheet" href="{pigcms{$static_path}css/ace-rtl.min.css">
        <link rel="stylesheet" href="{pigcms{$static_path}css/global.css">
        <link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui-timepicker-addon.css">

		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/jquery.form.js"></script>

		<script type="text/javascript" src="{pigcms{$static_public}js/jquery.cookie.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/jquery.validate.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/jquery.colorpicker.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
        <link rel="stylesheet" href="{pigcms{$static_public}layui/css/layui.css"  media="all">
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/extend/layer.ext.js"></script>
		<script type="text/javascript" src="/tpl/System/Static/js/common.js?t=123647"></script>
<!--		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?t=1"></script>-->
<!--		<script type="text/javascript" src="{pigcms{$static_path}js/area_adver.js?t=1"></script>-->
<!--		<script type="text/javascript" src="static/js/artdialog/jquery.artDialog.js"></script>-->

		<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>

		<script type="text/javascript" src="{pigcms{$static_path}js/bootstrap.min.js"></script>

		<style>
           input[readonly] {
                color: #939192;
                background: #f5f5f5;
                cursor: default
            }
           .form-group input[readonly] {
                color: #939192;
                background: #f5f5f5;
                cursor: default
            }
			.BMap_cpyCtrl{display:none;}
			input.ke-input-text {
				background-color: #FFFFFF;
				background-color: #FFFFFF!important;
				font-family: "sans serif",tahoma,verdana,helvetica;
				font-size: 12px;
				line-height: 24px;
				height: 24px;
				padding: 2px 4px;
				border-color: #848484 #E0E0E0 #E0E0E0 #848484;
				border-style: solid;
				border-width: 1px;
				display: -moz-inline-stack;
				display: inline-block;
				vertical-align: middle;
				zoom: 1;
			}
			.form-group>label{font-size:12px;line-height:24px;}
			#upload_pic_box{margin-top:20px;height:150px;}
			#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
			#upload_pic_box img{width:100px;height:70px;border:1px solid #ccc;}
		</style>
		<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
		<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
		<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
		<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
		<script type="text/javascript" src="./static/js/date/WdatePicker.js"></script>
	</head>
	<body width="100%" <if condition="$bg_color">style="background:{pigcms{$bg_color};"</if>>