<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><meta charset="utf-8"><link rel="stylesheet" type="text/css" href="<?php echo ($static_path); ?>css/styles.css"><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.ba-bbq.min.js"></script><?php if($config['site_favicon']){ ?><link rel="shortcut icon" href="<?php echo ($config["site_favicon"]); ?>"/><?php } ?><title><?php echo ($config["site_name"]); ?> - 小区中心</title><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/bootstrap.min.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/font-awesome.min.css?t=01"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/jquery-ui.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/jquery-ui.min.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/ace-fonts.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/ace.min.css" id="main-ace-style"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/ace-skins.min.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/ace-rtl.min.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/global.css"><link rel="stylesheet" href="<?php echo ($static_path); ?>css/jquery-ui-timepicker-addon.css"><?php if(!$_GET['old_version']){ ?><link rel="stylesheet" href="<?php echo ($static_path); ?>css/new-base.css?t=<?php echo time();?>" /><?php } ?><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.ba-bbq.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/ace-extra.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/bootstrap.min.js"></script><!-- page specific plugin scripts --><script type="text/javascript" src="<?php echo ($static_path); ?>js/bootbox.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery-ui.custom.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery-ui.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.ui.touch-punch.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.easypiechart.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.sparkline.min.js"></script><!-- ace scripts --><script type="text/javascript" src="<?php echo ($static_path); ?>js/ace-elements.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/ace.min.js"></script><script type="text/javascript" src="<?php echo ($static_public); ?>js/date/WdatePicker.js?t=01"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery.yiigridview.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery-ui-i18n.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/jquery-ui-timepicker-addon.min.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/echarts.min.js"></script><link rel="stylesheet" href="<?php echo ($static_path); ?>layer/css/layui.css"  media="all"><script type="text/javascript" src="<?php echo ($static_public); ?>js/jquery.colorpicker.js"></script><script src="<?php echo ($static_path); ?>layer/layer.js"></script><style type="text/css">.jqstooltip {
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
#HouseHelpBox{
    width:30px;
    background:#39CDBE;
    z-index:9999;
    position: absolute;
    right:0px;
    top: 80px;
    padding: 5px;
    border-top-left-radius:10px;
    border-bottom-left-radius:10px;
}
#HouseHelpBox .helpTitle{
    font-size: 18px;
    font-weight: 400;
    color: white;
    line-height:30px;
    text-align:center;
    background:#39CDBE;
    border-top-left-radius:10px;
    border-bottom-left-radius:10px;
}
</style><script type="text/javascript">	try{ace.settings.check('navbar' , 'fixed')}catch(e){}
	try{ace.settings.check('main-container' , 'fixed')}catch(e){}
	try{ace.settings.check('sidebar' , 'fixed')}catch(e){}
	try{ace.settings.check('breadcrumbs' , 'fixed')}catch(e){}


</script></head><body class="no-skin <?php if($_GET['iframe_window']){ ?>iframe_window<?php } ?>"><?php if ($_GET['old_version'] && (!$_GET['application_type'] || 'house'!=$_GET['application_type'])) { ?><link rel="stylesheet" href="<?php echo ($static_path); ?>css/new-base.css?t=<?php echo time();?>" /><link rel="stylesheet" href="//at.alicdn.com/t/font_1016180_iml9nmd0eg.css" /><script type="text/javascript" src="<?php echo ($static_public); ?>layer/layer.js"></script><script type="text/javascript" src="<?php echo ($static_public); ?>js/qweb/qwebchannel.js"></script><script type="text/javascript" src="<?php echo ($static_path); ?>js/new-base.js?t=<?php echo time();?>"></script><style>	#HouseHelpBox{
		width:30px;
		background:#39CDBE;
		z-index:9999;
		position: absolute;
		right:0px;
		top: 80px;
		padding: 5px;
		border-top-left-radius:10px;
		border-bottom-left-radius:10px;
	}
	#HouseHelpBox .helpTitle{
		font-size: 18px;
		font-weight: 400;
		color: white;
		line-height:30px;
		text-align:center;
		background:#39CDBE;
		border-top-left-radius:10px;
		border-bottom-left-radius:10px;
	}
    .rights {
        float: right;
        padding-right: 25px;
        background-size: 20px;
        cursor: pointer;
    }
    .ui-button-app {
        background-color: #1686ef;
        background-image: url("<?php echo ($static_path); ?>images/download_icos.png");
        background-size:15px;
        background-repeat:no-repeat;
        background-position: 5% 50%;
        border-radius: 6px;
        text-align:right;
        border: 0;
        color: #fff;
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
    .qrcode_box{
        margin: 0 auto;
        position: relative;
        top: 50%;
        transform: translateY(-50%);
        -moz-box-shadow: 0px 0px 5px rgba(238,241,245, 0.74);
        -webkit-box-shadow: 0px 0px 5px rgba(238,241,245, 0.74);
        box-shadow: 0px 0px 5px rgba(238,241,245, 0.74);
        border: 1px solid #ccc;
        background-color: #fff;
        background-repeat:no-repeat;
        background-image: url('./tpl/House/default/static/images/down.png');
        width: 350px;
        height: 392.5px;
        text-align: center;
    }
    .qrcode {
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 99999999;
        display: none;
        font-size: 18px;
    }
</style><div id="new_navbar" class="new_navbar"><div class="left"><div class="logo" onclick="location.href='<?php echo U('Index/index');?>'"><img src="<?php echo replace_file_domain($config['site_logo']);?>"/></div><div class="search-box" title="点击搜一搜"><img src="<?php echo ($static_path); ?>images/new/search_tip.png" class="searchIco"/><span>搜一搜</span></div></div><div class="right"><div class="logo"><?php if($house_session['logo']){ ?><img src="<?php echo ($house_session["logo"]); ?>"/><?php }elseif($config['wechat_share_img']){ ?><img src="<?php echo ($config["wechat_share_img"]); ?>"/><?php }else{ ?><img src="<?php echo ($static_public); ?>images/user.jpg"/><?php } ?></div><div class="username"><?php echo mb_strlen($house_session['village_name'])>12 ? preg_replace("/(.{1,4}).(.*)/iu","$1...",$house_session['village_name']) : $house_session['village_name']; ?></div><div class="nav-sub"><div class="wrap link-url" data-url="<?php echo U('Index/config');?>"><div class="item"><p>小区设置</p></div></div><?php if($property && $property['id'] && $house_session['is_system']==2){ ?><div class="wrap link-url" data-url="<?php echo U('Login/property_login',array('property_id'=>$property['id']));?>"><div class="item"><p>返回物业后台</p></div></div><?php } ?><div class="wrap" id="logout-btn" data-url="<?php echo U('Login/logout');?>"><div class="item"><p><?php if($pack_qweb_app){ ?>退出账号<?php }else{ ?>退出登录<?php } ?></p><div class="logout-logo"></div></div></div><?php if($pack_qweb_app){ ?><div class="wrap" id="min_window"><div class="item"><p>最小化窗口</p><div class="min-window"></div></div></div><div class="wrap" id="close_window"><div class="item"><p>关闭软件</p><div class="close-window"></div></div></div><?php } ?></div></div><div class="rights"><input class="ui-button-datastatistics" id="button-datastatistics" type="button" value="可视化大数据" style="margin-right: 25px;text-align:center;line-height: 32px;padding: 0 10px 0 25px;"><input class="ui-button-app" id="button" type="button" value="移动管理端" style="text-align:center;line-height: 32px;padding: 0 10px 0 25px;"></div><div class="qrcode" id="qrcode" style="display: none"><div class="qrcode_box" style=""><img style="width: 250px;margin-top:70px" src="/index.php?c=Recognition&a=get_own_qrcode&qrCon=<?php echo ($config['site_url']); ?>/topic/app_wap_house_manager.html" class="popup-img"></div></div><script type="text/javascript" src="<?php echo ($static_public); ?>js/artdialog/jquery.artDialog.js"></script><script type="text/javascript" src="<?php echo ($static_public); ?>js/artdialog/iframeTools.js"></script><script>        $('#button').click(function () {
            // $('#qrcode').attr('style','display:block')
            var href = "<?php echo U('Index/see_qrcode');?>";
            art.dialog.open(href,{
                init: function(){
                    var iframe = this.iframe.contentWindow;
                    window.top.art.dialog.data('iframe_handle',iframe);
                },
                id: 'handle',
                title:'<?php echo L_("查看二维码");?>',
                padding: 0,
                width: 900,
                height: 333,
                lock: true,
                resize: false,
                background:'black',
                button: null,
                fixed: false,
                close: null,
                left: '50%',
                top: '38.2%',
                opacity:'0.4'
            });
            return false;
        })
        $('#button-datastatistics').click(function () {
            location.href='<?php echo ($config['site_url']); ?>/v20/public/platform/#/user/village/datastatistics';
        })
        $(document).mouseup(function(e) {
            var  pop = $('.page-home');
            if(!pop.is(e.target) && pop.has(e.target).length === 0) {
                // 可以在这里关闭弹窗
                $("#qrcode").hide();
            }
        });
    </script><?php if($application_url){ ?><div id="HouseHelpBox"><a class="helpTitle" href="<?php echo ($application_url); ?>"><div>应</div><div>用</div><div>详</div><div>情</div></a></div><?php } ?></div><div id="new_navbar-place"></div><div class="search-layer-wrap"><div class="search-layer"></div><div class="search-block"><div class="search-input"><input type="text" value="" placeholder="请输入你要使用的功能"/><i class="zenticon zenticon-close-circle search-close-icon"></i><div class="search-btn"></div></div><div class="search-result-block"><p class="search-shortcut-title">快捷功能</p><div class="search-shortcut"></div></div></div></div><div id="navbar" class="navbar navbar-default"><div class="navbar-container" id="navbar-container"><button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler"><span class="sr-only">Toggle sidebar</span><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></button><div class="navbar-header pull-left"><a href="<?php echo U('Index/index');?>" class="navbar-brand" style="padding: 5px 0 0 0;"><small><img src="<?php if($config['wechat_share_img']){ echo ($config["wechat_share_img"]); }else{ echo ($config["site_merchant_logo"]); } ?>" style="height:38px;width:38px;"/><?php echo ($config["site_name"]); ?> - 小区中心
				</small></a></div><div class="navbar-buttons navbar-header pull-right" role="navigation"><ul class="nav ace-nav"><li class="light-blue"><a data-toggle="dropdown" href="#" class="dropdown-toggle"><img class="nav-user-photo" src="<?php echo ($static_public); ?>images/user.jpg" alt="Jason&#39;s Photo" /><span class="user-info" style="max-width: 108px;"><small>欢迎您，<?php if (isset($house_session['user_name'])){ echo mb_strlen($house_session['user_name'])>12 ? preg_replace("/(.{1,4}).(.*)/iu","$1...",$house_session['user_name']) : $house_session['user_name']; }else{ echo mb_strlen($house_session['account'])>12 ? preg_replace("/(.{1,4}).(.*)/iu","$1...",$house_session['account']) : $house_session['account']; }?></small><?php echo ($house_session["village_name"]); ?></span><i class="ace-icon fa fa-caret-down"></i></a><ul class="user-menu pull-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close"><li><a href="<?php echo ($config["site_url"]); ?>" target="_blank"><i class="ace-icon fa fa-link"></i> 网站首页
							</a></li><li><a href="<?php echo U('Index/config');?>"><i class="ace-icon fa fa-user"></i> 小区设置
							</a></li><li class="divider"></li><li><a href="<?php echo U('Login/logout');?>"><i class="ace-icon fa fa-power-off"></i> 退出
							</a></li></ul></li></ul></div></div></div><script>            function reminder(){
                var url = "<?php echo U('Index/reminder');?>";
                $.post(url,{},function(data){
                    if (data.arr) {
                        for(var i=0;i<data.arr.length;i++){
                            var url = data.arr[i].url;
                            var index = layer.open({
                                type: 1,
                                shade: false,
                                area: ['300px','150px'],
                                maxmin: true,
                                content: '<div style="text-align;center; margin-left: 77px; margin-top: 20px;">'+data.arr[i].msg+'</div>',
                                zIndex: layer.zIndex, //重点1
                                success: function(layero){
                                    layer.setTop(layero); //重点2
                                },
                                btn: ['查看', '关闭'],
                                yes: function(index, layero){
                                    window.top.location.href = url;
                                    layer.closeAll();
                                }
                            });
                            layer.style(index, {
                                top: '50px'
                            });
                        }
                    }
                },'json');
            }
            window.setInterval(reminder,60000);
        </script><div class="main-container" id="main-container"><div id="new-sidebar"><ul class="new-nav new-nav-list"><?php if(is_array($house_menu)){ $i = 0; $__LIST__ = $house_menu;if( count($__LIST__)==0 ){ echo "" ; }else{ foreach($__LIST__ as $key=>$vo){ $mod = ($i % 2 );++$i; if($vo['menu_list'] || $vo['url']){ ?><li class="<?php if($vo['is_active']){ ?>open active<?php }elseif($vo['menu_list']){ ?>not-open<?php } ?>"><a data-jump_type="<?php echo ($vo["jump_type"]); ?>" <?php if($vo['menu_list']){ ?>href="javascript:;" class="dropdown-toggle"<?php }else{ ?>href="<?php echo ($vo["url"]); ?>"<?php } if($vo['module'] == 'House'){ ?>target="_blank"<?php } ?>><i class="menu-icon fa <?php echo ($vo["icon"]); ?>"></i><span class="menu-text"><?php echo ($vo["name"]); ?></span></a><b class="arrow"></b><?php if($vo['menu_list']){ ?><div class="submenu-box"><div class="second-sidebar-title"><?php echo ($vo["name"]); ?></div><ul class="submenu"><?php if(is_array($vo['menu_list'])){ $i = 0; $__LIST__ = $vo['menu_list'];if( count($__LIST__)==0 ){ echo "" ; }else{ foreach($__LIST__ as $key=>$voo){ $mod = ($i % 2 );++$i;?><li <?php if($voo['is_active']){ ?>class="show open"<?php } ?>><a data-jump_type="<?php echo ($voo["jump_type"]); ?>" <?php if($voo['menu_list']){ ?>href="javascript:;" class="dropdown-toggle"<?php }else{ ?>href="<?php echo ($voo["url"]); ?>"<?php } ?>><span class="menu-text"><?php echo ($voo["name"]); ?></span></a><ul <?php if($voo['is_active']){ ?>class="submenu nav-show open"<?php }else{ ?>class="submenu nav-hide"<?php } ?>><?php if(is_array($voo['menu_list'])){ $i = 0; $__LIST__ = $voo['menu_list'];if( count($__LIST__)==0 ){ echo "" ; }else{ foreach($__LIST__ as $key=>$val){ $mod = ($i % 2 );++$i;?><li <?php if($val['is_active']){ ?>class="nav-show open"<?php } ?>><a data-jump_type="<?php echo ($val["jump_type"]); ?>" href="<?php echo ($val["url"]); ?>"><span class="menu-text"><?php echo ($val["name"]); ?></span></a></li><?php } } } else { echo "" ;} ?></ul></li><?php } } } else { echo "" ;} ?></ul></div><?php } ?></li><?php } } } } else { echo "" ;} ?></ul><!-- /.nav-list --></div><div id="new-third-sidebar"><div class="swiper-container" id='nav-scroll'><div class="ui-nav clearfix swiper-wrapper"></div></div></div><?php } else { ?><script>                function reminder(){
                    var url = "<?php echo U('Index/reminder');?>";
                    $.post(url,{},function(data){
                        if (data.arr) {
                            for(var i=0;i<data.arr.length;i++){
                                var url = data.arr[i].url;
                                var index = layer.open({
                                    type: 1,
                                    shade: false,
                                    area: ['300px','150px'],
                                    maxmin: true,
                                    content: '<div style="text-align;center; margin-left: 77px; margin-top: 20px;">'+data.arr[i].msg+'</div>',
                                    zIndex: layer.zIndex, //重点1
                                    success: function(layero){
                                        layer.setTop(layero); //重点2
                                    },
                                    btn: ['查看', '关闭'],
                                    yes: function(index, layero){
                                        window.top.location.href = url;
                                        layer.closeAll();
                                    }
                                });
                                layer.style(index, {
                                    top: '50px'
                                });
                            }
                        }
                    },'json');
                }
                window.setInterval(reminder,60000);
            </script><div class="main-container" id="main-container"><?php if($application_url){ ?><div id="HouseHelpBox"><a class="helpTitle" href="<?php echo ($application_url); ?>"><div>应</div><div>用</div><div>详</div><div>情</div></a></div><?php } } ?><div class="main-content"><!-- 内容头部 --><div class="breadcrumbs" id="breadcrumbs"><ul class="breadcrumb"><li><i class="ace-icon fa fa-gear gear-icon"></i><a href="<?php echo U('Library/set_send_price_config');?>">代送基本设置</a></li><li class="active">基本信息设置</li></ul></div><!-- 内容头部 --><div class="page-content"><div class="page-content-area"><style>				.ace-file-input a {display:none;}
			</style><div class="row"><div class="col-xs-12"><form id="edit_form" method="post" class="form-horizontal" action="<?php echo U('Library/set_send_price_config');?>" enctype="multipart/form-data"><div class="tab-content"><div class="form-group express_send_price"><label class="col-sm-1"><label for="express_send_price">代发价格</label></label><input class="col-sm-1" size="10" name="express_send_price" id="express_send_price" type="text" placeholder="请设置代发价格" value="<?php echo ($village_info["express_send_price"]); ?>" /><span class="form_tips" style="line-height: 30px;">请设置代发价格</span></div><div class="clearfix form-actions"><div class="col-md-offset-3 col-md-9"><?php if(in_array(3,$house_session['menus'])){ ?><button type="submit" class="btn btn-info"><i class="ace-icon fa fa-check bigger-110"></i>										保存
									</button><?php }else{ ?><button type="button" class="btn btn-info disabled" disabled="disabled" style="cursor: not-allowed;"><i class="ace-icon fa fa-check bigger-110"></i>										保存
									</button><?php } ?></div></div></div></form></div></div></div></div></div></div><script>	$('#edit_form').submit(function(){

		var express_send_price = $("#express_send_price").val();
		if(express_send_price<0){
			alert('请设置不小于0的代发价格！');
			return false;
		}

		$('#edit_form button[type="submit"]').prop('disabled',true).html('保存中...');
		if (lock = 1) {
			return;
		}
		lock = 1;


		$.post("<?php echo U('Library/set_send_price_config');?>",$('#edit_form').serialize(),function(result){
			lock = 0;
			if(result.status == 1){
				window.location.href = "<?php echo U('Library/set_send_price_config');?>";
			}else{
				$('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
				alert(result.info);
			}
		})
		return false;
	});
</script><div id="orderAlert" style="position: fixed; z-index: 999999; bottom: 5px; right: 5px; background: #e5e5e5; display: none;"><div style="text-align: center; margin-top: 10px; font-size: 20px; color: red;"><b>新订单来啦!</b><a class="oaright" href="javascript:closeoa()">[关闭]</a></div><div style="margin: 20px 30px 5px 30px; cursor: pointer;" onclick="tourl()">			您好：有<span class="label label-info" id="oanum"></span>笔新订单来了！
		</div><div style="margin: 5px 30px 5px 30px; cursor: pointer;" onclick="tourl()">			截止目前，一共有<span class="label label-info" id="oatnum"></span>笔订单未处理
		</div><div class="oaright" style="bottom: 10px; margin: 5px 30px 5px 30px;">			时间：<a id="oatime" style="text-decoration: none;"></a></div></div><div style="position: fixed; top: -9999px; right: -9999px; display: none;" id="soundsw"></div><a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse"><i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i></a></div><script>function newalert(title){
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
</script><div style="position: fixed; width: 100%; height: 100%; top: 0px; left: 0px; display: none;" id="popalertwindow"><div style="width: 100%; height: 100%; background: #eeeeee; filter: alpha(opacity = 50); -moz-opacity: 0.5; -khtml-opacity: 0.5; opacity: 0.5; position: absolute; z-index: 9999;"></div><div style="position: relative; width: 500px; height: 200px; margin: 200px auto; filter: alpha(opacity = 100); -moz-opacity: 1; -khtml-opacity: 1; opacity: 1; z-index: 10000; background: #ffffff; -webkit-border-radius: 8px; -moz-border-radius: 8px; border-radius: 8px; -webkit-box-shadow: #666 0px 0px 10px; -moz-box-shadow: #666 0px 0px 10px; box-shadow: #666 0px 0px 10px;"><div style="height: 40px;"></div><div style="width: 400px; height: 90px; margin: 0px auto; color: #999999; text-align: center; font-size: 20px;"><table style="width: 400px; height: 90px;"><tbody><tr><td id="popalertwindowcontent"></td></tr></tbody></table></div><div style="height: 20px;"></div><div style="width: 80px; height: 40px; background: #eeeeee; margin: 0 auto; line-height: 40px; text-align: center; font-size: 20px; border: 1px solid #999999; cursor: pointer;" onclick="$(&#39;#popalertwindow&#39;).hide();">确认</div></div></div><?php if($config['open_help'] && 1==2){ ?><link href="/static/help_xuanfu/css/zuoce.css" type="text/css" rel="stylesheet"/><div class="zuoce zuoce_clear"><div id="Layer1"><a href="javascript:"><img src="/static/help_xuanfu/images/ou_03.png"/></a></div><div id="Layer2" style="display:none;height:400px;overflow-y:scroll;"><p class="xiangGuan zuoce_clear">相关帮助</p><span class="help_content"></span><span class="loading" ><img  style="margin-left:50px;" src="./static/images/loading.gif" /> 正在加载帮助教程...
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