<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/styles.css">
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.ba-bbq.min.js"></script>
<if condition="$config['site_favicon']">
	<link rel="shortcut icon" href="{pigcms{$config.site_favicon}"/>
</if>
<title>{pigcms{$config.site_name} - 街道/社区管理后台</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-rtl.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/global.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui-timepicker-addon.css">
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.ba-bbq.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/ace-extra.min.js"></script>


<script type="text/javascript" src="{pigcms{$static_path}js/bootstrap.min.js"></script>

<!-- page specific plugin scripts -->
<script type="text/javascript" src="{pigcms{$static_path}js/bootbox.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui.custom.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.ui.touch-punch.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.easypiechart.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.sparkline.min.js"></script>

<!-- ace scripts -->
<script type="text/javascript" src="{pigcms{$static_path}js/ace-elements.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/ace.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.yiigridview.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui-i18n.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui-timepicker-addon.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/echarts.min.js"></script>
<link rel="stylesheet" href="{pigcms{$static_path}layer/css/layui.css"  media="all">
<script src="{pigcms{$static_path}layer/layer.js"></script>
    <if condition="$_GET['iframe']">
        <link rel="stylesheet" href="{pigcms{$static_path}css/new-base.css?t={pigcms{:time()}" />
    </if>
<script type="text/javascript">
	var kind_editor=null,static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_map="{pigcms{:U('NewStreet/frame_map',array('city_id'=>$map_default_city_id))}";
	var choose_provincess="{pigcms{:U('Area/ajax_province')}",choose_cityss="{pigcms{:U('Area/ajax_city')}";
	var choose_street="{pigcms{:U('Area/ajax_street')}",choose_community="{pigcms{:U('Area/ajax_community')}";
</script>

<style type="text/css">
.jqstooltip {
	position: absolute;
	left: 0px;
	top: 0px;
	visibility: hidden;
	background: rgb(0, 0, 0) transparent;
	background-color: rgba(0, 0, 0, 0.6);
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000,endColorstr=#99000000);
	-ms-filter:"progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000, endColorstr=#99000000)";
	color: white;
	font: 10px arial, san serif;
	text-align: left;
	white-space: nowrap;
	padding: 5px;
	border: 1px solid white;
	z-index: 10000;
}

.jqsfield {
	color: white;
	font: 10px arial, san serif;
	text-align: left;
}

.statusSwitch, .orderValidSwitch, .unitShowSwitch, .authTypeSwitch {
	display: none;
}

#shopList .shopNameInput, #shopList .tagInput, #shopList .orderPrefixInput
	{
	font-size: 12px;
	color: black;
	display: none;
	width: 100%;
}
.btn.disabled,.btn[disabled],fieldset[disabled] .btn {
	pointer-events:auto;
}
.fl{ float:left;}
.fr{ float:right;}
</style>
	<style>
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
	<script type="text/javascript" src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script type="text/javascript">
	try{ace.settings.check('navbar' , 'fixed')}catch(e){}
	try{ace.settings.check('main-container' , 'fixed')}catch(e){}
	try{ace.settings.check('sidebar' , 'fixed')}catch(e){}
	try{ace.settings.check('breadcrumbs' , 'fixed')}catch(e){}
</script>
	
</head>

<body class="no-skin">
<if condition="!$_GET['iframe']">
    <include file="NewStreetPublic:nav"/>
    <div class="main-container" id="main-container">
        <include file="NewStreetPublic:left"/>
        <else/>
        <div class="main-container" id="main-container">
</if>
