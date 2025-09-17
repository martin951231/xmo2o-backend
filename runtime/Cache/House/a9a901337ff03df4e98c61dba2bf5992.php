<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><meta charset="utf-8"><link rel="stylesheet" type="text/css" href="<?php echo ($static_path); ?>css/styles.css"><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.ba-bbq.min.js"></script><?php if($config['site_favicon']){ ?><link rel="shortcut icon" href="<?php echo ($config["site_favicon"]); ?>"/><?php } ?><title><?php echo ($config["site_name"]); ?> - 物业后台</title><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/bootstrap.min.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/font-awesome.min.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/jquery-ui.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/jquery-ui.min.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/ace-fonts.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/ace.min.css" id="main-ace-style"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/ace-skins.min.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/ace-rtl.min.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/global.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/jquery-ui-timepicker-addon.css"><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.ba-bbq.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/ace-extra.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/bootstrap.min.js"></script><!-- page specific plugin scripts --><script type="text/javascript" src="<?php echo ($static_path); ?>js/bootbox.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery-ui.custom.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery-ui.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.ui.touch-punch.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.easypiechart.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.sparkline.min.js"></script><!-- ace scripts --><script type="text/javascript" src="<?php echo ($static_path); ?>js/ace-elements.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/ace.min.js"></script><script type="text/javascript" src="<?php echo ($static_public); ?>js/date/WdatePicker.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.yiigridview.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery-ui-i18n.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery-ui-timepicker-addon.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/echarts.min.js"></script><link rel="stylesheet" href="<?php echo ($static_path); ?>layer/css/layui.css"  media="all"><script src="<?php echo ($static_path); ?>layer/layer.js"></script><?php if($_GET['iframe']){ ?><link rel="stylesheet" href="<?php echo ($static_path); ?>css/new-base.css?t=<?php echo time();?>" /><?php } ?><style type="text/css">.jqstooltip {
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
</style><style>		.BMap_cpyCtrl{display:none;}
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
	</style><script type="text/javascript" src="<?php echo ($static_public); ?>kindeditor/kindeditor.js"></script><script type="text/javascript">	try{ace.settings.check('navbar' , 'fixed')}catch(e){}
	try{ace.settings.check('main-container' , 'fixed')}catch(e){}
	try{ace.settings.check('sidebar' , 'fixed')}catch(e){}
	try{ace.settings.check('breadcrumbs' , 'fixed')}catch(e){}
</script></head><body class="no-skin"><?php if(!$_GET['iframe']){ ?><link rel="stylesheet" href="<?php echo ($static_path); ?>css/new-base.css?t=<?php echo time();?>" /><link rel="stylesheet" href="//at.alicdn.com/t/font_1016180_nbqgawx25fh.css" /><script type="text/javascript" src="<?php echo ($static_public); ?>layer/layer.js"></script><script type="text/javascript" src="<?php echo ($static_public); ?>js/qweb/qwebchannel.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/new-base.js?t=<?php echo time();?>"></script><style>    .rights {
        float: right;
        padding-right: 25px;
        background-size: 20px;
        cursor: pointer;
    }
    .ui-button-datastatistics {
        background-color: #1686ef;
        background-image: url("<?php echo ($static_path); ?>images/house_data.png");
        background-size:15px;
        background-repeat:no-repeat;
        background-position: 5% 50%;
        border-radius: 6px;
        text-align:right;
        border: 0;
        color: #fff;
    }
</style><div id="new_navbar" class="new_navbar"><div class="left"><div class="logo" onclick="location.href='<?php echo U('NewProperty/index');?>'"><img src="<?php echo replace_file_domain($config['site_logo']);?>"/></div><div class="search-box" title="点击搜一搜"><img src="<?php echo ($static_path); ?>images/new/search_tip.png" class="searchIco"/><span>搜一搜</span></div></div><div class="right"><div class="logo"><?php if($merchant_session['logo']){ ?><img src="<?php echo ($merchant_session["logo"]); ?>"/><?php }elseif($config['wechat_share_img']){ ?><img src="<?php echo ($config["wechat_share_img"]); ?>"/><?php }else{ ?><img src="<?php echo ($static_public); ?>images/user.jpg"/><?php } ?></div><div class="username"><?php echo mb_strlen($property['property_name'])>12 ? preg_replace("/(.{1,4}).(.*)/iu","$1...",$property['property_name']) : $property['property_name']; ?></div><div class="nav-sub"><!--			<div class="wrap link-url" data-url="<?php echo U('Index/config');?>">--><!--				<div class="item">--><!--					<p>社区设置</p>--><!--				</div>--><!--			</div>--><div class="wrap" id="logout-btn" data-url="<?php echo U('Login/logout');?>"><div class="item"><p><?php if($pack_qweb_app){ ?>退出账号<?php }else{ ?>退出登录<?php } ?></p><div class="logout-logo"></div></div></div><?php if($pack_qweb_app){ ?><div class="wrap" id="min_window"><div class="item"><p>最小化窗口</p><div class="min-window"></div></div></div><div class="wrap" id="close_window"><div class="item"><p>关闭软件</p><div class="close-window"></div></div></div><?php } ?></div></div><div class="rights"><input class="ui-button-datastatistics" id="button-datastatistics" type="button" value="可视化大数据" style="margin-right: 25px;text-align:center;line-height: 32px;padding: 0 10px 0 25px;"></div></div><div id="new_navbar-place"></div><div class="search-layer-wrap"><div class="search-layer"></div><div class="search-block"><div class="search-input"><input type="text" value="" placeholder="请输入你要使用的功能"/><i class="zenticon zenticon-close-circle search-close-icon"></i><div class="search-btn"></div></div><div class="search-result-block"><p class="search-shortcut-title">快捷功能</p><div class="search-shortcut"></div></div></div></div><div id="navbar" class="navbar navbar-default"><div class="navbar-container" id="navbar-container"><button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler"><span class="sr-only">Toggle sidebar</span><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></button><div class="navbar-header pull-left"><a href="<?php echo U('Index/index');?>" class="navbar-brand" style="padding: 5px 0 0 0;"><small><img src="<?php if($config['wechat_share_img']){ echo ($config["wechat_share_img"]); }else{ echo ($config["site_merchant_logo"]); } ?>" style="height:38px;width:38px;"/><?php echo ($config["site_name"]); ?> - 物业后台
				</small></a></div><div class="navbar-buttons navbar-header pull-right" role="navigation"><ul class="nav ace-nav"><li class="light-blue"><a data-toggle="dropdown" href="#" class="dropdown-toggle"><img class="nav-user-photo" src="<?php echo ($static_public); ?>images/user.jpg" alt="Jason&#39;s Photo" /><span class="user-info" style="max-width: 108px;"><small>欢迎您，<?php if (isset($house_session['user_name'])){ echo mb_strlen($house_session['user_name'])>12 ? preg_replace("/(.{1,4}).(.*)/iu","$1...",$house_session['user_name']) : $house_session['user_name']; }else{ echo mb_strlen($house_session['account'])>12 ? preg_replace("/(.{1,4}).(.*)/iu","$1...",$house_session['account']) : $house_session['account']; }?></small><?php echo ($house_session["village_name"]); ?></span><i class="ace-icon fa fa-caret-down"></i></a><ul class="user-menu pull-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close"><li><a href="<?php echo ($config["site_url"]); ?>" target="_blank"><i class="ace-icon fa fa-link"></i> 网站首页
							</a></li><li><a href="<?php echo U('Index/config');?>"><i class="ace-icon fa fa-user"></i> 小区设置
							</a></li><li class="divider"></li><li><a href="<?php echo U('Login/logout');?>"><i class="ace-icon fa fa-power-off"></i> 退出
							</a></li></ul></li></ul></div></div></div><script>    $('#button-datastatistics').click(function () {
        window.open('<?php echo ($config['site_url']); ?>/v20/public/platform/#/property/property/communityData');
    })
</script><div class="main-container" id="main-container"><div id="sidebar" class="sidebar responsive"><ul class="nav nav-list" style="top: 0px;"><li class="hsub <?php if((strtolower(MODULE_NAME) == 'newproperty') && in_array(ACTION_NAME, array('index_new'))){ ?>open active<?php } ?>"><a href="<?php echo U('NewProperty/index_new');?>"><i class="menu-icon fa fa-home"></i><span class="menu-text">首页</span></a><b class="arrow"></b></li><li class="hsub <?php if((strtolower(MODULE_NAME) == 'newproperty') && in_array(ACTION_NAME, array('config','config_edit'))){ ?>open active<?php } ?>"><a href="<?php echo U('NewProperty/config');?>" ><i class="menu-icon fa fa-gear"></i><span class="menu-text">基本信息</span></a><b class="arrow"></b></li><li class="hsub <?php if((strtolower(MODULE_NAME) == 'newproperty') && in_array(ACTION_NAME, array('index','add'))){ ?>open active<?php } ?>"><a href="<?php echo U('NewProperty/index');?>" ><i class="menu-icon fa fa-building-o"></i><span class="menu-text">小区列表</span></a><b class="arrow"></b></li><?php if($property['login_identify'] == 1){ ?><li class="hsub <?php if((strtolower(MODULE_NAME) == 'propertygroup') && in_array(ACTION_NAME, array('index'))){ ?>open active<?php } ?>"><a href="<?php echo U('PropertyGroup/index');?>" ><i class="menu-icon fa fa-sliders"></i><span class="menu-text">组织架构</span></a><b class="arrow"></b></li><li class="hsub <?php if((strtolower(MODULE_NAME) == 'propertyrole') && in_array(ACTION_NAME, array('role_list', 'role_add', 'role_edit','group_list', 'group_add', 'group_edit'))){ ?>open active<?php } ?>"><a href="<?php echo U('PropertyRole/role_list');?>" ><i class="menu-icon fa fa-th-large"></i><span class="menu-text">权限管理</span></a><b class="arrow"></b></li><?php } ?><li class="hsub <?php if((in_array(MODULE_NAME ,array('Bill','PropertyWater')))){ ?>open active<?php } ?>"><a href="#" class="dropdown-toggle"><i class="menu-icon fa fa-book"></i><span class="menu-text">账单服务</span><b class="arrow fa fa-angle-down"></b></a><b class="arrow"></b><div class="submenu-box"><div class="second-sidebar-title">账单服务</div><ul class="submenu"><li <?php if(MODULE_NAME == 'Bill' && (in_array(ACTION_NAME,array('index_list')))){ ?>class="active"<?php } ?>><a href="<?php echo U('Bill/index_list');?>"><i class="menu-icon fa fa-caret-right"></i> 收支记录
                </a><b class="arrow"></b></li><li <?php if(MODULE_NAME == 'PropertyWater' && (in_array(ACTION_NAME,array('money_list')))){ ?>class="active"<?php } ?>><a href="<?php echo U('PropertyWater/money_list');?>"><i class="menu-icon fa fa-caret-right"></i>物业流水
                </a><b class="arrow"></b></li></ul></div></li><?php if(in_array(1,$package_application_id)){ ?><li class="hsub <?php if((in_array(MODULE_NAME ,array('Merchant')))){ ?>open active<?php } ?>"><a href="#" class="dropdown-toggle"><i class="menu-icon fa iconfont icon-shangjia"></i><span class="menu-text">商家管理</span><b class="arrow fa fa-angle-down"></b></a><b class="arrow"></b><div class="submenu-box"><div class="second-sidebar-title">商家管理</div><ul class="submenu"><li <?php if(MODULE_NAME == 'Merchant' && (in_array(ACTION_NAME,array('index')))){ ?>class="active"<?php } ?>><a href="<?php echo U('Merchant/index');?>"><i class="menu-icon fa fa-caret-right"></i> 商家管理
                </a><b class="arrow"></b></ul></div></li><?php } ?><li class="hsub <?php if((in_array(MODULE_NAME ,array('PropertyApplication')))){ ?>open active<?php } ?>"><a href="<?php echo U('PropertyApplication/index');?>" class="dropdown-toggle"><i class="menu-icon fa iconfont icon-shangjia"></i><span class="menu-text">功能应用库</span><b class="arrow fa fa-angle-down"></b></a><b class="arrow"></b><div class="submenu-box" style="display: none;"><div class="second-sidebar-title">功能应用</div><ul class="submenu"><li <?php if(MODULE_NAME == 'Merchant' && (in_array(ACTION_NAME,array('index','detail')))){ ?>class="active"<?php } ?>><a href="<?php echo U('PropertyApplication/index');?>"><i class="menu-icon fa fa-caret-right"></i> 功能应用
                </a><b class="arrow"></b></ul></div></li><?php if(in_array(35,$package_application_id)){ ?><li class="hsub <?php if((MODULE_NAME == 'NewWeixin')){ ?>open active<?php } ?>"><a href="#" class="dropdown-toggle"><i class="menu-icon fa fa-wechat"></i><span class="menu-text">公众号</span><b class="arrow fa fa-angle-down"></b></a><b class="arrow"></b><div class="submenu-box"><div class="second-sidebar-title">公众号</div><ul class="submenu"><li <?php if(MODULE_NAME == 'NewWeixin' && (in_array(ACTION_NAME,array('index')))){ ?>class="active"<?php } ?>><a href="<?php echo U('NewWeixin/index');?>"><i class="menu-icon fa fa-caret-right"></i> 公众号绑定
                </a><b class="arrow"></b></li><li <?php if(MODULE_NAME == 'NewWeixin' && (in_array(ACTION_NAME,array('template')))){ ?>class="active"<?php } ?>><a href="<?php echo U('NewWeixin/template');?>"><i class="menu-icon fa fa-caret-right"></i> 模板消息
                </a><b class="arrow"></b></li><li <?php if(MODULE_NAME == 'NewWeixin' && (in_array(ACTION_NAME,array('auto')))){ ?>class="active"<?php } ?>><a href="<?php echo U('NewWeixin/auto');?>"><i class="menu-icon fa fa-caret-right"></i> 自动回复
                </a><b class="arrow"></b></li><li <?php if(MODULE_NAME == 'NewWeixin' && (in_array(ACTION_NAME,array('menu')))){ ?>class="active"<?php } ?>><a href="<?php echo U('NewWeixin/menu');?>"><i class="menu-icon fa fa-caret-right"></i>自定义菜单
                </a><b class="arrow"></b></li><li <?php if(MODULE_NAME == 'NewWeixin' && (in_array(ACTION_NAME,array('txt', 'reply_txt', 'img', 'reply_img')))){ ?>class="active"<?php } ?>><a href="<?php echo U('NewWeixin/txt');?>"><i class="menu-icon fa fa-caret-right"></i> 关键词回复
                </a><b class="arrow"></b></li><li <?php if(MODULE_NAME == 'NewWeixin' && (in_array(ACTION_NAME,array('article', 'one', 'multi')))){ ?>class="active"<?php } ?>><a href="<?php echo U('NewWeixin/article');?>"><i class="menu-icon fa fa-caret-right"></i> 图文素材
                </a><b class="arrow"></b></li></ul></div></li><?php } if($config['open_property_minapp'] != 1 && in_array(36,$package_application_id)){ ?><li class="hsub <?php if((MODULE_NAME == 'NewWeixinApp')){ ?>open active<?php } ?>"><a href="#" class="dropdown-toggle"><i class="menu-icon fa iconfont icon-xiaochengxu"></i><span class="menu-text">小程序管理</span><b class="arrow fa fa-angle-down"></b></a><b class="arrow"></b><div class="submenu-box"><div class="second-sidebar-title">小程序管理</div><ul class="submenu"><li <?php if(MODULE_NAME == 'NewWeixinApp' && (in_array(ACTION_NAME,array('index')))){ ?>class="active"<?php } ?>><a href="<?php echo U('NewWeixinApp/index');?>"><i class="menu-icon fa fa-caret-right"></i> 小程序设置
                    </a><b class="arrow"></b></li></ul></div></li><?php } if($config['enterprise_wx_corpid'] != '' && in_array(37,$package_application_id)){ ?><li class="hsub <?php if((in_array(MODULE_NAME ,array('EnterpriseWeixin')))){ ?>open active<?php } ?>"><a href="#" class="dropdown-toggle"><i class="menu-icon fa iconfont icon-qiyeweixin"></i><span class="menu-text">企业微信</span><b class="arrow fa fa-angle-down"></b></a><b class="arrow"></b><div class="submenu-box"><div class="second-sidebar-title">企业微信管理</div><ul class="submenu"><li <?php if(MODULE_NAME == 'EnterpriseWeixin' && (in_array(ACTION_NAME,array('index')))){ ?>class="active"<?php } ?>><a href="<?php echo U('EnterpriseWeixin/index');?>"><i class="menu-icon fa fa-caret-right"></i> 企业微信管理
                    </a><b class="arrow"></b></ul></div></li><?php } ?></ul><!-- /.nav-list --><!-- #section:basics/sidebar.layout.minimize --><div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse"><i class="ace-icon fa fa-angle-double-left"
			data-icon1="ace-icon fa fa-angle-double-left"
			data-icon2="ace-icon fa fa-angle-double-right"></i></div><!-- /section:basics/sidebar.layout.minimize --><script type="text/javascript">		try {
			ace.settings.check('sidebar', 'collapsed')
		} catch (e) {
		}
	</script></div><div id="new-sidebar"><ul class="new-nav new-nav-list"></ul><!-- /.nav-list --></div><div id="new-third-sidebar"><div class="swiper-container" id='nav-scroll'><div class="ui-nav clearfix swiper-wrapper"></div></div></div><?php }else{ ?><div class="main-container" id="main-container"><?php } ?><div class="main-content"><!-- 内容头部 --><div class="breadcrumbs" ><ul class="breadcrumb"><li><i class="ace-icon fa fa-user"></i><a href="<?php echo U('NewProperty/index',array('iframe'=>true));?>"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>管理</a></li><li class="active">添加<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?></li></ul></div><!-- 内容头部 --><style>		.line-span { line-height:34px;}
		.none { display:none}
        /*.title_right { text-align: right;}*/
	</style><div class="page-content" style="background-color: white;"><div class="row" style="background-color: white;"><div class="col-xs-12"><form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__"><div class="tab-content">							添加<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?></div><div class="tab-content"><div class="form-group"><label class="col-sm-2 title_right"><label for="housesize"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>名称：</label></label><input class="col-sm-2" size="20" name="village_name" id="village_name" type="text" value=""/></div><div class="form-group"><label class="col-sm-2 title_right"><label><?php echo (D('House_village')->getHouseAlias('house_name') ? :'小区').L_("地址").'：'?></label></label><!--								<fieldset id="choose_cityarea"></fieldset>--><fieldset id="choose_street" province_idss="<?php echo ($village["province_id"]); ?>" city_idss="<?php echo ($village["city_id"]); ?>" area_id="<?php echo ($village["area_id"]); ?>" street_idss="<?php echo ($village["street_id"]); ?>" community_idss="<?php echo ($village["community_id"]); ?>" style="display:inline"></fieldset></div><div class="form-group"><label class="col-sm-2 title_right"><label for="phone"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>详细地址：</label></label><input class="col-sm-2" size="20" name="village_address" id="village_address" type="text" value=""/></div><div class="form-group"><label class="col-sm-2 title_right"><label for="phone">该<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区').(D('House_village')->getHouseAlias('single_name')?:'楼栋')?>是否超过100<?php echo (D('House_village')->getHouseAlias('single_name')?:'栋')?>：</label></label><label style="padding-left:0px;padding-right:20px;"><input type="radio" class="ace" value="3" name="village_single_support_digit"><span style="z-index: 1" class="lbl">是</span></label><label style="padding-left:0px;"><input checked="" type="radio" class="ace" value="2" name="village_single_support_digit"><span style="z-index: 1" class="lbl">否</span></label><span class="form_tips" style="color: gray;margin-left:5px;">选择是，<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋')?>号即可支持三位， 添加后不可变更</span></div><div class="form-group"><label class="col-sm-2 title_right"><label for="plot_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>占地面积：</label></label><input class="col-sm-2" size="20" name="plot_area" value="" type="text" style=""/><span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span></div><div class="form-group"><label class="col-sm-2 title_right"><label for="building_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>建筑面积：</label></label><input class="col-sm-2" size="20" name="building_area" value="" type="text" style=""/><span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span></div><div class="form-group"><label class="col-sm-2 title_right"><label for="public_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>公共场所面积：</label></label><input class="col-sm-2" size="20" name="public_area" value="" type="text" style=""/><span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span></div><div class="form-group"><label class="col-sm-2 title_right"><label for="green_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>绿化面积：</label></label><input class="col-sm-2" size="20" name="green_area" value="" type="text" style=""/><span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span></div><div class="form-group"><label class="col-sm-2 title_right"><label for="garage_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>车库面积：</label></label><input class="col-sm-2" size="20" name="garage_area" value="" type="text" style=""/><span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span></div><div class="form-group"><label class="col-sm-2 title_right"><label for="design_parking_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>设计车位面积：</label></label><input class="col-sm-2" size="20" name="design_parking_area" value="" type="text" style=""/><span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span></div><div class="form-group"><label class="col-sm-2 title_right"><label for="cars_park_num">车位数：</label></label><input class="col-sm-2" size="20" name="cars_park_num" value="" type="text" style=""/></div><div class="form-group"><label class="col-sm-2 title_right"><label for="remark">备注：</label></label><textarea class="col-sm-2" size="20" name="remark" placeholder="请填写备注"></textarea></div></div><div class="space"></div><div class="clearfix form-actions"><div class="col-md-offset-3 col-md-9"><button class="btn btn-info" type="submit"><i class="ace-icon fa fa-check bigger-110"></i>										添加
									</button></div></div></div></form></div></div></div><!--<script type="text/javascript" src="<?php echo ($static_path); ?>js/area.js"></script>--><script>	var choose_provincess="<?php echo U('Area/ajax_province');?>",choose_cityss="<?php echo U('Area/ajax_city');?>",choose_area="<?php echo U('Area/ajax_area');?>",choose_circle="";
	var choose_street="<?php echo U('Area/ajax_street');?>",choose_community="<?php echo U('Area/ajax_community');?>";
</script><script type="text/javascript" src="<?php echo ($static_public); ?>js/area_street.js?t=13"></script><script type="text/javascript">	var static_public="<?php echo ($static_public); ?>",static_path="<?php echo ($static_path); ?>",merchant_index="<?php echo U('Index/index');?>",choose_province="<?php echo U('Area/ajax_province');?>",choose_city="<?php echo U('Area/ajax_city');?>",choose_area="<?php echo U('Area/ajax_area');?>",choose_circle="";

 let submit = false;
function check_submit(){
	if($('#village_name').val()==''){
		alert("<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>"+'名称不能为空！');
		return false;
	}
	if($('#village_address').val()==''){
		alert("<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>"+'详细不能为空！');
		return false;
	}
    if (!submit){
        submit = true;
        return  true;
    }
    return  false;
}
</script><!--	<div id="orderAlert" style="position: fixed; z-index: 999999; bottom: 5px; right: 5px; background: #e5e5e5; display: none;">--><!--		<div style="text-align: center; margin-top: 10px; font-size: 20px; color: red;">--><!--			<b>新订单来啦!</b><a class="oaright" href="javascript:closeoa()">[关闭]</a>--><!--		</div>--><!--		<div style="margin: 20px 30px 5px 30px; cursor: pointer;" onclick="tourl()">--><!--			您好：有<span class="label label-info" id="oanum"></span>笔新订单来了！--><!--		</div>--><!--		<div style="margin: 5px 30px 5px 30px; cursor: pointer;" onclick="tourl()">--><!--			截止目前，一共有<span class="label label-info" id="oatnum"></span>笔订单未处理--><!--		</div>--><!--		<div class="oaright" style="bottom: 10px; margin: 5px 30px 5px 30px;">--><!--			时间：<a id="oatime" style="text-decoration: none;"></a>--><!--		</div>--><!--	</div>--><!--	<div style="position: fixed; top: -9999px; right: -9999px; display: none;" id="soundsw"></div>--><!--	<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">--><!--		<i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>--><!--	</a>--></div><script>function newalert(title){
	bootbox.dialog({
		message: title, 
		buttons: {
			"success" : {
				"label" : "确认",
				"className" : "btn-sm btn-primary"
			}
		}
	});
}

function alertshow(content){
	$('#popalertwindowcontent').html(content);
	$('#popalertwindow').show();
}
</script><div style="position: fixed; width: 100%; height: 100%; top: 0px; left: 0px; display: none;" id="popalertwindow"><div style="width: 100%; height: 100%; background: #eeeeee; filter: alpha(opacity = 50); -moz-opacity: 0.5; -khtml-opacity: 0.5; opacity: 0.5; position: absolute; z-index: 9999;"></div><div style="position: relative; width: 500px; height: 200px; margin: 200px auto; filter: alpha(opacity = 100); -moz-opacity: 1; -khtml-opacity: 1; opacity: 1; z-index: 10000; background: #ffffff; -webkit-border-radius: 8px; -moz-border-radius: 8px; border-radius: 8px; -webkit-box-shadow: #666 0px 0px 10px; -moz-box-shadow: #666 0px 0px 10px; box-shadow: #666 0px 0px 10px;"><div style="height: 40px;"></div><div style="width: 400px; height: 90px; margin: 0px auto; color: #999999; text-align: center; font-size: 20px;"><table style="width: 400px; height: 90px;"><tbody><tr><td id="popalertwindowcontent"></td></tr></tbody></table></div><div style="height: 20px;"></div><div style="width: 80px; height: 40px; background: #eeeeee; margin: 0 auto; line-height: 40px; text-align: center; font-size: 20px; border: 1px solid #999999; cursor: pointer;" onclick="$(&#39;#popalertwindow&#39;).hide();">确认</div></div></div><?php if($config['open_help'] && 0){ ?><link href="/static/help_xuanfu/css/zuoce.css" type="text/css" rel="stylesheet"/><div class="zuoce zuoce_clear"><div id="Layer1"><a href="javascript:"><img src="/static/help_xuanfu/images/ou_03.png"/></a></div><div id="Layer2" style="display:none;height:400px;overflow-y:scroll;"><p class="xiangGuan zuoce_clear">相关帮助</p><span class="help_content"></span><span class="loading" ><img  style="margin-left:50px;" src="./static/images/loading.gif" /> 正在加载帮助教程...
			</span><!--p class="anNiuo clear"><a href="#">进入帮助中心</a></p--><p class="anNiut zuoce_clear"><a href="http://wpa.qq.com/msgrd?v=3&uin=<?php echo ($config["site_qq"]); ?>&site=qq&menu=yes" target="_blank">在线客服</a></p></div></div><script type="text/javascript">		window.onload = function(){
			var oDiv1 = document.getElementById('Layer1');
			var oDiv2 = document.getElementById('Layer2');
			var flag = true;
			oDiv1.onclick = function(){
				oDiv2.style.display = oDiv2.style.display == 'block' ? 'none' : 'block';
				if(flag) {
					$.ajax({
						type : 'GET',
						url : '<?php echo U('Index/ajax_help', array('group'=>GROUP_NAME,'module'=>MODULE_NAME, 'action'=>ACTION_NAME)); ?>',
						dataType : 'html',
						success : function (data) {
							if (data) {
								$('.help_content').html(data);
							}
							flag = false;
							$('.loading').hide();
						}
					});
				}
			}
		}
		function openwin(url,iHeight,iWidth){
			var iTop = (window.screen.availHeight-30-iHeight)/2,iLeft = (window.screen.availWidth-10-iWidth)/2;
			window.open(url, "newwindow", "height="+iHeight+", width="+iWidth+", toolbar=no, menubar=no,top="+iTop+",left="+iLeft+",scrollbars=yes, resizable=no, location=no, status=no");
		}

		var origin = {X: document.body.clientWidth/2, Y: document.body.clientHeight/2}, o = document.querySelector('.zuoce');
		o.addEventListener('mousedown', function(e) {
			var offsetStart = {x: this.offsetLeft, y:this.offsetTop};
			var mouseStart = {x:e.pageX, y:e.pageY};
			function mv(e) {
				o.style.top = offsetStart.y + e.pageY - mouseStart.y + 'px';
			}
			document.addEventListener('dragstart', function(e) {e.preventDefault()});
			document.addEventListener('mousemove', mv);
			document.addEventListener('mouseup', function() {
				o.style.top = offsetStart.y;
				document.removeEventListener('mousemove', mv);
			});
		});
	</script><?php } ?></body></html>