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
<title>{pigcms{$config.site_name} - {pigcms{:L_("企业中心")}</title>
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
<link rel="stylesheet" href="{pigcms{$static_path}css/skin/jedate.css">
<script type="text/javascript" src="{pigcms{$static_path}js/jedate.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.ba-bbq.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/ace-extra.min.js"></script>
<!-- {pigcms{:L_("拷贝")} -->
<script type="text/javascript" src="{pigcms{$static_path}js/clipboard.min.js"></script>

<!--{pigcms{:L_("日历")}-->
<link rel="stylesheet" href="{pigcms{$static_path}css/datepicker.css">
<!--{pigcms{:L_("日历")}-->
<script type="text/javascript" src="{pigcms{$static_path}js/zlDate.js"></script>

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
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
<script type="text/javascript" src="./tpl/System/Static/js/area_pca.js"></script>

<!-- swiper -->
<link rel="stylesheet" href="{pigcms{$static_path}css/swiper-3.3.1.min.css">
<script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js"></script>

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
</style>
<script type="text/javascript">
	try{ace.settings.check('navbar' , 'fixed')}catch(e){}
	try{ace.settings.check('main-container' , 'fixed')}catch(e){}
	try{ace.settings.check('sidebar' , 'fixed')}catch(e){}
	try{ace.settings.check('breadcrumbs' , 'fixed')}catch(e){}
</script>
<if condition="MODULE_NAME eq 'Express' || MODULE_NAME eq 'Market_express'">
<link rel="stylesheet" href="{pigcms{$static_path}css/freight.css" />
</if>
</head>
<body class="no-skin <if condition="$_GET['iframe_window']">iframe_window</if>">
    <if condition="$merchant_session['store_id']">
		<include file="Public:clerk_nav"/>
		<div class="main-container" id="main-container">
			<include file="Public:clerk_left"/>
    <else />
		<include file="Public:nav"/>
		<div class="main-container" id="main-container">
		<include file="Public:left"/>
    </if>