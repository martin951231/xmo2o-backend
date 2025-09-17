<link rel="stylesheet" href="{pigcms{$static_path}css/new-base.css?t={pigcms{:time()}" />
<link rel="stylesheet" href="//at.alicdn.com/t/font_1016180_iml9nmd0eg.css" />
<script type="text/javascript" src="{pigcms{$static_public}layer/layer.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/qweb/qwebchannel.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/new-base.js?t={pigcms{:time()}"></script>
<style>
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
    .rights {
        float: right;
        padding-right: 25px;
        background-size: 20px;
        cursor: pointer;
    }
    .ui-button-app {
        background-color: #1686ef;
        background-image: url("{pigcms{$static_path}images/download_icos.png");
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
        background-image: url("{pigcms{$static_path}images/house_data.png");
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
</style>
<div id="new_navbar" class="new_navbar">
	<div class="left">
		<div class="logo" onclick="location.href='{pigcms{:U('Index/index')}'">
			<img src="{pigcms{:replace_file_domain($config['site_logo'])}"/>
		</div>
		<div class="search-box" title="点击搜一搜">
			<img src="{pigcms{$static_path}images/new/search_tip.png" class="searchIco"/>
			<span>搜一搜</span>
		</div>
	</div>
	<div class="right">
		<div class="logo">
			<if condition="$house_session['logo']">
				<img src="{pigcms{$house_session.logo}"/>
			<elseif condition="$config['wechat_share_img']"/>
				<img src="{pigcms{$config.wechat_share_img}"/>
			<else/>
				<img src="{pigcms{$static_public}images/user.jpg"/>
			</if>
		</div>
		<div class="username"><?php echo mb_strlen($house_session['village_name'])>12 ? preg_replace("/(.{1,4}).(.*)/iu","$1...",$house_session['village_name']) : $house_session['village_name']; ?></div>
		<div class="nav-sub">
            <div class="wrap link-url" data-url="{pigcms{:U('Index/config')}">
                <div class="item">
                    <p>小区设置</p>
                </div>
            </div>
            <if condition="$property && $property['id'] && $house_session['is_system']==2">
                <div class="wrap link-url" data-url="{pigcms{:U('Login/property_login',array('property_id'=>$property['id']))}">
                    <div class="item">
                        <p>返回物业后台</p>
                    </div>
                </div>
            </if>
			<div class="wrap" id="logout-btn" data-url="{pigcms{:U('Login/logout')}">
				<div class="item">
					<p><if condition="$pack_qweb_app">退出账号<else/>退出登录</if></p>
					<div class="logout-logo"></div>
				</div>
			</div>
			<if condition="$pack_qweb_app">
				<div class="wrap" id="min_window">
					<div class="item">
						<p>最小化窗口</p>
						<div class="min-window"></div>
					</div>
				</div>
				<div class="wrap" id="close_window">
					<div class="item">
						<p>关闭软件</p>
						<div class="close-window"></div>
					</div>
				</div>
			</if>
		</div>
	</div>
    <div class="rights">
        <input class="ui-button-datastatistics" id="button-datastatistics" type="button" value="可视化大数据" style="margin-right: 25px;text-align:center;line-height: 32px;padding: 0 10px 0 25px;">

        <input class="ui-button-app" id="button" type="button" value="移动管理端" style="text-align:center;line-height: 32px;padding: 0 10px 0 25px;">
    </div>
    <div class="qrcode" id="qrcode" style="display: none">
        <div class="qrcode_box" style="">
            <img style="width: 250px;margin-top:70px" src="/index.php?c=Recognition&a=get_own_qrcode&qrCon={pigcms{$config['site_url']}/topic/app_wap_house_manager.html" class="popup-img">
        </div>
    </div>
    <script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
    <script>
        $('#button').click(function () {
            // $('#qrcode').attr('style','display:block')
            var href = "{pigcms{:U('Index/see_qrcode')}";
            art.dialog.open(href,{
                init: function(){
                    var iframe = this.iframe.contentWindow;
                    window.top.art.dialog.data('iframe_handle',iframe);
                },
                id: 'handle',
                title:'{pigcms{:L_("查看二维码")}',
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
            location.href='{pigcms{$config['site_url']}/v20/public/platform/#/user/village/datastatistics';
        })
        $(document).mouseup(function(e) {
            var  pop = $('.page-home');
            if(!pop.is(e.target) && pop.has(e.target).length === 0) {
                // 可以在这里关闭弹窗
                $("#qrcode").hide();
            }
        });
    </script>
	<if condition="$application_url">
		<div id="HouseHelpBox">
			<a class="helpTitle" href="{pigcms{$application_url}">
				<div>应</div>
				<div>用</div>
				<div>详</div>
				<div>情</div>
			</a>
		</div>
	</if>
</div>
<div id="new_navbar-place"></div>
<div class="search-layer-wrap">
	<div class="search-layer"></div>
	<div class="search-block">
		<div class="search-input">
			<input type="text" value="" placeholder="请输入你要使用的功能"/>
			<i class="zenticon zenticon-close-circle search-close-icon"></i>
			<div class="search-btn"></div>
		</div>
		<div class="search-result-block">
			<p class="search-shortcut-title">快捷功能</p>
			<div class="search-shortcut"></div>
		</div>
	</div>
</div>
<div id="navbar" class="navbar navbar-default">
	<div class="navbar-container" id="navbar-container">
		<button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler">
			<span class="sr-only">Toggle sidebar</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</button>
		<div class="navbar-header pull-left">
			<a href="{pigcms{:U('Index/index')}" class="navbar-brand" style="padding: 5px 0 0 0;"> 
				<small> 
					<img src="<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_merchant_logo}</if>" style="height:38px;width:38px;"/> {pigcms{$config.site_name} - 小区中心
				</small>
			</a>
		</div>
		<div class="navbar-buttons navbar-header pull-right" role="navigation">
			<ul class="nav ace-nav">
				<li class="light-blue">
					<a data-toggle="dropdown" href="#" class="dropdown-toggle"> 
						<img class="nav-user-photo" src="{pigcms{$static_public}images/user.jpg" alt="Jason&#39;s Photo" /> 
						<span class="user-info" style="max-width: 108px;"> <small>欢迎您，<?php if (isset($house_session['user_name'])){ echo mb_strlen($house_session['user_name'])>12 ? preg_replace("/(.{1,4}).(.*)/iu","$1...",$house_session['user_name']) : $house_session['user_name']; }else{ echo mb_strlen($house_session['account'])>12 ? preg_replace("/(.{1,4}).(.*)/iu","$1...",$house_session['account']) : $house_session['account']; }?></small> {pigcms{$house_session.village_name}</span> 
						<i class="ace-icon fa fa-caret-down"></i>
					</a>
					<ul class="user-menu pull-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
						<li>
							<a href="{pigcms{$config.site_url}" target="_blank">
								<i class="ace-icon fa fa-link"></i> 网站首页
							</a>
						</li>
						<li>
							<a href="{pigcms{:U('Index/config')}">
								<i class="ace-icon fa fa-user"></i> 小区设置
							</a>
						</li>
						<li class="divider"></li>
						<li>
							<a href="{pigcms{:U('Login/logout')}"> 
								<i class="ace-icon fa fa-power-off"></i> 退出
							</a>
						</li>
					</ul>
				</li>
			</ul>
		</div>
	</div>
</div>