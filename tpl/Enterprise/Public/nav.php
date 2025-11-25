<link rel="stylesheet" href="{pigcms{$static_path}css/new-base.css?t={pigcms{:time()}" />
<link rel="stylesheet" href="//at.alicdn.com/t/font_1016180_iml9nmd0eg.css" />
<script type="text/javascript" src="{pigcms{$static_public}layer/layer.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/qweb/qwebchannel.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/new-base.js?t={pigcms{:time()}"></script>
<div id="new_navbar" class="new_navbar">
	<div class="left">
		<div class="logo" onclick="location.href='{pigcms{:U('Index/index')}'">
			<img src="{pigcms{$config.site_logo}"/>
		</div>
		<div class="search-box" title="{pigcms{:L_("点击搜一搜")}">
			<img src="{pigcms{$static_path}images/new/search_tip.png" class="searchIco"/>
			<span>{pigcms{:L_("搜一搜")}</span>
		</div>
	</div>
	<div class="right">
		<div class="logo" style="float: left;">
			<if condition="$merchant_session['logo']">
				<img src="{pigcms{:replace_file_domain($merchant_session['logo'])}"/>
			<elseif condition="$config['wechat_share_img']"/>
				<img src="{pigcms{$config.wechat_share_img}"/>
			<else/>
				<img src="{pigcms{$static_public}images/user.jpg"/>
			</if>
		</div>
		<div class="username">{pigcms{$merchant_session.name}</div>
		<div class="nav-sub">
			<!--<div class="wrap link-url" data-url="{pigcms{:U('Config/merchant')}">
				<div class="item">
					<p>{pigcms{:L_("商家设置")}</p>
				</div>
			</div>-->
			<div class="wrap" id="logout-btn" data-url="{pigcms{:U('Login/logout')}">
				<div class="item">
					<p>{pigcms{:L_("退出登录")}</p>
					<div class="logout-logo"></div>
				</div>
			</div>
			<if condition="$pack_qweb_app">
				<div class="wrap" id="min_window">
					<div class="item">
						<p>{pigcms{:L_("最小化窗口")}</p>
						<div class="min-window"></div>
					</div>
				</div>
				<div class="wrap" id="close_window">
					<div class="item">
						<p>{pigcms{:L_("关闭软件")}</p>
						<div class="close-window"></div>
					</div>
				</div>
			</if>
		</div>
	</div>
	<!--语言环境切换-->
	<if condition="$config['open_multilingual'] eq '1'">
	<div style="float: right;">
		<select id="change_lang" data-url="{pigcms{:U('Public/change_lang')}">
			<volist name="lang_list" id="vo">
			<option value="{pigcms{$vo.val}" <if condition="$config['system_lang'] eq $vo['val']">selected="selected"</if>>{pigcms{$vo.display}</option>
			</volist>
		</select>
	</div>
	</if>
</div>
<div id="new_navbar-place"></div>
<div class="search-layer-wrap">
	<div class="search-layer"></div>
	<div class="search-block">
		<div class="search-input">
			<input type="text" value="" placeholder="{pigcms{:L_("请输入你要使用的功能")}"/>
			<i class="zenticon zenticon-close-circle search-close-icon"></i>
			<div class="search-btn"></div>
		</div>
		<div class="search-result-block">
			<p class="search-shortcut-title">{pigcms{:L_("快捷功能")}</p>
			<div class="search-shortcut"></div>
		</div>
	</div>
</div>
<div id="navbar" class="navbar navbar-default" <if condition="C('butt_open') || $no_sidebar">style="display:none;"</if>>
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
					<img src="{pigcms{$config.site_merchant_logo}" style="height:38px;width:38px;"/> {pigcms{$config.site_name} - {pigcms{:L_("商家中心")}
				</small>
			</a>
		</div>
		<div class="navbar-buttons navbar-header pull-right" role="navigation">
			<ul class="nav ace-nav">
				<li class="light-blue">
					<a data-toggle="dropdown" href="#" class="dropdown-toggle">
						<img class="nav-user-photo" src="{pigcms{$static_public}images/user.jpg" alt="Jason&#39;s Photo" />
						<span class="user-info"> <small>{pigcms{:L_("欢迎您")}，</small> {pigcms{$merchant_session.name}</span>
						<i class="ace-icon fa fa-caret-down"></i>
					</a>
					<ul class="user-menu pull-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
						<li>
							<a href="{pigcms{$config.site_url}" target="_blank">
								<i class="ace-icon fa fa-link"></i> {pigcms{:L_("网站首页")}
							</a>
						</li>
						<li>
							<a href="{pigcms{:U('Config/merchant')}">
								<i class="ace-icon fa fa-user"></i> {pigcms{:L_("商家设置")}
							</a>
						</li>
						<li class="divider"></li>
						<li>
							<a href="{pigcms{:U('Login/logout')}">
								<i class="ace-icon fa fa-power-off"></i> {pigcms{:L_("退出")}
							</a>
						</li>
					</ul>
				</li>
			</ul>
		</div>
	</div>
</div>