<div id="sidebar" class="sidebar responsive">
	<ul class="nav nav-list" style="top: 0px;">
        <li class="hsub <if condition="(strtolower(MODULE_NAME) eq 'newstreet') && in_array(ACTION_NAME, array('index_new'))">open active</if>">
        <a href="{pigcms{:U('NewStreet/index_new')}">
            <i class="menu-icon fa fa-home"></i>
            <span class="menu-text">首页</span>
        </a>
        <b class="arrow"></b>
        </li>
        <li class="hsub <if condition="(strtolower(MODULE_NAME) eq 'newstreet') && in_array(ACTION_NAME, array('community_index','house_index','single_index','floor_index','owner_edit','owner_index'))">open active</if>">
        <a href="{pigcms{:U('NewStreet/community_index')}">
            <i class="menu-icon fa fa-users"></i>
            <span class="menu-text">业主管理</span>
        </a>
        <b class="arrow"></b>
        </li>
        <li class="hsub <if condition="(strtolower(MODULE_NAME) eq 'newstreet') && in_array(ACTION_NAME, array('area_street_custom'))">open active</if>">
        <a href="{pigcms{:U('NewStreet/area_street_custom')}">
            <i class="menu-icon fa fa-qrcode"></i>
            <span class="menu-text">网格化管理</span>
        </a>
        <b class="arrow"></b>
        </li>
        <li class="hsub <if condition="MODULE_NAME eq 'NewsSreet' && in_array(ACTION_NAME,array('index','news_edit','cate','cate_edit','reply'))">open active</if>">
        <a href="{pigcms{:U('NewsSreet/index')}" class="dropdown-toggle">
            <i class="menu-icon fa fa-tablet"></i>
            <span class="menu-text">新闻管理</span>
            <b class="arrow fa fa-angle-down"></b>
        </a>
        <b class="arrow"></b>
        <div class="submenu-box">
            <div class="second-sidebar-title">新闻管理</div>
            <ul class="submenu">
                <li <if condition="MODULE_NAME eq 'NewsSreet' &&  in_array(ACTION_NAME,array('cate','cate_edit'))">class="active"</if>>
                <a href="{pigcms{:U('NewsSreet/cate')}">
                    <i class="menu-icon fa fa-caret-right"></i> 新闻分类
                </a>
                <b class="arrow"></b>
                </li>
                <li <if condition="MODULE_NAME eq 'NewsSreet' && in_array(ACTION_NAME,array('index','news_edit'))">class="active"</if>>
                <a href="{pigcms{:U('NewsSreet/index')}">
                    <i class="menu-icon fa fa-caret-right"></i> 新闻列表
                </a>
                <b class="arrow"></b>
                </li>
                <li <if condition="MODULE_NAME eq 'NewsSreet' && in_array(ACTION_NAME,array('reply'))">class="active"</if>>
                <a href="{pigcms{:U('NewsSreet/reply')}">
                    <i class="menu-icon fa fa-caret-right"></i> 新闻评论列表
                </a>
                <b class="arrow"></b>
                </li>
            </ul>
        </div>
        </li>

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