<div id="sidebar" class="sidebar responsive">
	<ul class="nav nav-list" style="top: 0px;">
		<li class="hsub <if condition="(strtolower(MODULE_NAME) eq 'newproperty') && in_array(ACTION_NAME, array('index_new'))">open active</if>">
			<a href="{pigcms{:U('NewProperty/index_new')}">
				<i class="menu-icon fa fa-home"></i>
				<span class="menu-text">首页</span>
			</a>
			<b class="arrow"></b>
		</li>
        <li class="hsub <if condition="(strtolower(MODULE_NAME) eq 'newproperty') && in_array(ACTION_NAME, array('config','config_edit'))">open active</if>">
        <a href="{pigcms{:U('NewProperty/config')}" >
            <i class="menu-icon fa fa-gear"></i>
            <span class="menu-text">基本信息</span>
        </a>
        <b class="arrow"></b>

        </li>
        <li class="hsub <if condition="(strtolower(MODULE_NAME) eq 'newproperty') && in_array(ACTION_NAME, array('index','add'))">open active</if>">
        <a href="{pigcms{:U('NewProperty/index')}" >
            <i class="menu-icon fa fa-building-o"></i>
            <span class="menu-text">小区列表</span>
        </a>
        <b class="arrow"></b>

        </li>
        <if condition="$property['login_identify'] == 1">
            <li class="hsub <if condition="(strtolower(MODULE_NAME) eq 'propertygroup') && in_array(ACTION_NAME, array('index'))">open active</if>">
            <a href="{pigcms{:U('PropertyGroup/index')}" >
                <i class="menu-icon fa fa-sliders"></i>
                <span class="menu-text">组织架构</span>
            </a>
            <b class="arrow"></b>
            </li>
            <li class="hsub <if condition="(strtolower(MODULE_NAME) eq 'propertyrole') && in_array(ACTION_NAME, array('role_list', 'role_add', 'role_edit','group_list', 'group_add', 'group_edit'))">open active</if>">
            <a href="{pigcms{:U('PropertyRole/role_list')}" >
                <i class="menu-icon fa fa-th-large"></i>
                <span class="menu-text">权限管理</span>
            </a>
            <b class="arrow"></b>
            </li>
        </if>


        <li class="hsub <if condition="(in_array(MODULE_NAME ,array('Bill','PropertyWater')))">open active</if>">
        <a href="#" class="dropdown-toggle">
            <i class="menu-icon fa fa-book"></i>
            <span class="menu-text">账单服务</span>
            <b class="arrow fa fa-angle-down"></b>
        </a>
        <b class="arrow"></b>
        <div class="submenu-box">
            <div class="second-sidebar-title">账单服务</div>
            <ul class="submenu">
                <li <if condition="MODULE_NAME eq 'Bill' && (in_array(ACTION_NAME,array('index_list')))">class="active"</if>>
                <a href="{pigcms{:U('Bill/index_list')}">
                    <i class="menu-icon fa fa-caret-right"></i> 收支记录
                </a>
                <b class="arrow"></b>
                </li>
                <li <if condition="MODULE_NAME eq 'PropertyWater' && (in_array(ACTION_NAME,array('money_list')))">class="active"</if>>
                <a href="{pigcms{:U('PropertyWater/money_list')}">
                    <i class="menu-icon fa fa-caret-right"></i>物业流水
                </a>
                <b class="arrow"></b>
                </li>


            </ul>
        </div>
        </li>
        <if condition="in_array(1,$package_application_id)">
        <li class="hsub <if condition="(in_array(MODULE_NAME ,array('Merchant')))">open active</if>">
        <a href="#" class="dropdown-toggle">
            <i class="menu-icon fa iconfont icon-shangjia"></i>
            <span class="menu-text">商家管理</span>
            <b class="arrow fa fa-angle-down"></b>
        </a>
        <b class="arrow"></b>
        <div class="submenu-box">
            <div class="second-sidebar-title">商家管理</div>
            <ul class="submenu">
                <li <if condition="MODULE_NAME eq 'Merchant' && (in_array(ACTION_NAME,array('index')))">class="active"</if>>
                <a href="{pigcms{:U('Merchant/index')}">
                    <i class="menu-icon fa fa-caret-right"></i> 商家管理
                </a>
                <b class="arrow"></b>
            </ul>
        </div>
        </li>
        </if>
        <li class="hsub <if condition="(in_array(MODULE_NAME ,array('PropertyApplication')))">open active</if>">
        <a href="{pigcms{:U('PropertyApplication/index')}" class="dropdown-toggle">
            <i class="menu-icon fa iconfont icon-shangjia"></i>
            <span class="menu-text">功能应用库</span>
            <b class="arrow fa fa-angle-down"></b>
        </a>
        <b class="arrow"></b>
        <div class="submenu-box" style="display: none;">
            <div class="second-sidebar-title">功能应用</div>
            <ul class="submenu">
                <li <if condition="MODULE_NAME eq 'Merchant' && (in_array(ACTION_NAME,array('index','detail')))">class="active"</if>>
                <a href="{pigcms{:U('PropertyApplication/index')}">
                    <i class="menu-icon fa fa-caret-right"></i> 功能应用
                </a>
                <b class="arrow"></b>
            </ul>
        </div>
        </li>
        <if condition="in_array(35,$package_application_id)">
        <li class="hsub <if condition="(MODULE_NAME eq 'NewWeixin')">open active</if>">
        <a href="#" class="dropdown-toggle">
            <i class="menu-icon fa fa-wechat"></i>
            <span class="menu-text">公众号</span>
            <b class="arrow fa fa-angle-down"></b>
        </a>
        <b class="arrow"></b>
        <div class="submenu-box">
            <div class="second-sidebar-title">公众号</div>
            <ul class="submenu">
                <li <if condition="MODULE_NAME eq 'NewWeixin' && (in_array(ACTION_NAME,array('index')))">class="active"</if>>
                <a href="{pigcms{:U('NewWeixin/index')}">
                    <i class="menu-icon fa fa-caret-right"></i> 公众号绑定
                </a>
                <b class="arrow"></b>
                </li>

                <li <if condition="MODULE_NAME eq 'NewWeixin' && (in_array(ACTION_NAME,array('template')))">class="active"</if>>
                <a href="{pigcms{:U('NewWeixin/template')}">
                    <i class="menu-icon fa fa-caret-right"></i> 模板消息
                </a>
                <b class="arrow"></b>
                </li>

                <li <if condition="MODULE_NAME eq 'NewWeixin' && (in_array(ACTION_NAME,array('auto')))">class="active"</if>>
                <a href="{pigcms{:U('NewWeixin/auto')}">
                    <i class="menu-icon fa fa-caret-right"></i> 自动回复
                </a>
                <b class="arrow"></b>
                </li>

                <li <if condition="MODULE_NAME eq 'NewWeixin' && (in_array(ACTION_NAME,array('menu')))">class="active"</if>>
                <a href="{pigcms{:U('NewWeixin/menu')}">
                    <i class="menu-icon fa fa-caret-right"></i>自定义菜单
                </a>
                <b class="arrow"></b>
                </li>

                <li <if condition="MODULE_NAME eq 'NewWeixin' && (in_array(ACTION_NAME,array('txt', 'reply_txt', 'img', 'reply_img')))">class="active"</if>>
                <a href="{pigcms{:U('NewWeixin/txt')}">
                    <i class="menu-icon fa fa-caret-right"></i> 关键词回复
                </a>
                <b class="arrow"></b>
                </li>

                <li <if condition="MODULE_NAME eq 'NewWeixin' && (in_array(ACTION_NAME,array('article', 'one', 'multi')))">class="active"</if>>
                <a href="{pigcms{:U('NewWeixin/article')}">
                    <i class="menu-icon fa fa-caret-right"></i> 图文素材
                </a>
                <b class="arrow"></b>
                </li>
            </ul>
        </div>
        </li>
        </if>

        <if condition="$config['open_property_minapp'] neq 1 && in_array(36,$package_application_id)">
            <li class="hsub <if condition="(MODULE_NAME eq 'NewWeixinApp')">open active</if>">
            <a href="#" class="dropdown-toggle">
                <i class="menu-icon fa iconfont icon-xiaochengxu"></i>
                <span class="menu-text">小程序管理</span>
                <b class="arrow fa fa-angle-down"></b>
            </a>
            <b class="arrow"></b>
            <div class="submenu-box">
                <div class="second-sidebar-title">小程序管理</div>
                <ul class="submenu">
                    <li <if condition="MODULE_NAME eq 'NewWeixinApp' && (in_array(ACTION_NAME,array('index')))">class="active"</if>>
                    <a href="{pigcms{:U('NewWeixinApp/index')}">
                        <i class="menu-icon fa fa-caret-right"></i> 小程序设置
                    </a>
                    <b class="arrow"></b>
                    </li>
                </ul>
            </div>
            </li>
        </if>
        <if condition="$config['enterprise_wx_corpid'] neq '' && in_array(37,$package_application_id)">
            <li class="hsub <if condition="(in_array(MODULE_NAME ,array('EnterpriseWeixin')))">open active</if>">
            <a href="#" class="dropdown-toggle">
                <i class="menu-icon fa iconfont icon-qiyeweixin"></i>
                <span class="menu-text">企业微信</span>
                <b class="arrow fa fa-angle-down"></b>
            </a>
            <b class="arrow"></b>
            <div class="submenu-box">
                <div class="second-sidebar-title">企业微信管理</div>
                <ul class="submenu">
                    <li <if condition="MODULE_NAME eq 'EnterpriseWeixin' && (in_array(ACTION_NAME,array('index')))">class="active"</if>>
                    <a href="{pigcms{:U('EnterpriseWeixin/index')}">
                        <i class="menu-icon fa fa-caret-right"></i> 企业微信管理
                    </a>
                    <b class="arrow"></b>
                </ul>
            </div>
            </li>
        </if>
    </ul>
	<!-- /.nav-list -->

	<!-- #section:basics/sidebar.layout.minimize -->
	<div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
		<i class="ace-icon fa fa-angle-double-left"
			data-icon1="ace-icon fa fa-angle-double-left"
			data-icon2="ace-icon fa fa-angle-double-right"></i>
	</div>

	<!-- /section:basics/sidebar.layout.minimize -->
	<script type="text/javascript">
		try {
			ace.settings.check('sidebar', 'collapsed')
		} catch (e) {
		}
	</script>
</div>
<div id="new-sidebar">
	<ul class="new-nav new-nav-list">
		
	</ul>
	<!-- /.nav-list -->
</div>
<div id="new-third-sidebar">
	<div class="swiper-container" id='nav-scroll'>
		<div class="ui-nav clearfix swiper-wrapper"></div>
	</div>
</div>