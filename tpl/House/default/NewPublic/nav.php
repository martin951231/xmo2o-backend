<link rel="stylesheet" href="{pigcms{$static_path}css/new-base.css?t={pigcms{:time()}" />
<link rel="stylesheet" href="//at.alicdn.com/t/font_1016180_nbqgawx25fh.css" />
<script type="text/javascript" src="{pigcms{$static_public}layer/layer.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/qweb/qwebchannel.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/new-base.js?t={pigcms{:time()}"></script>
<style>
    .rights {
        float: right;
        padding-right: 25px;
        background-size: 20px;
        cursor: pointer;
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
</style>
<div id="new_navbar" class="new_navbar">
	<div class="left">
		<div class="logo" onclick="location.href='{pigcms{:U('NewProperty/index')}'">
			<img src="{pigcms{:replace_file_domain($config['site_logo'])}"/>
		</div>
		<div class="search-box" title="点击搜一搜">
			<img src="{pigcms{$static_path}images/new/search_tip.png" class="searchIco"/>
			<span>搜一搜</span>
		</div>
	</div>
	<div class="right">
		<div class="logo">
			<if condition="$merchant_session['logo']">
				<img src="{pigcms{$merchant_session.logo}"/>
			<elseif condition="$config['wechat_share_img']"/>
				<img src="{pigcms{$config.wechat_share_img}"/>
			<else/>
				<img src="{pigcms{$static_public}images/user.jpg"/>
			</if>
		</div>
		<div class="username"><?php echo mb_strlen($property['property_name'])>12 ? preg_replace("/(.{1,4}).(.*)/iu","$1...",$property['property_name']) : $property['property_name']; ?></div>
		<div class="nav-sub">
<!--			<div class="wrap link-url" data-url="{pigcms{:U('Index/config')}">-->
<!--				<div class="item">-->
<!--					<p>社区设置</p>-->
<!--				</div>-->
<!--			</div>-->
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
    </div>
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
					<img src="<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_merchant_logo}</if>" style="height:38px;width:38px;"/> {pigcms{$config.site_name} - 物业后台
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
<script>
    $('#button-datastatistics').click(function () {
        window.open('{pigcms{$config['site_url']}/v20/public/platform/#/property/property/communityData');
    })
</script>