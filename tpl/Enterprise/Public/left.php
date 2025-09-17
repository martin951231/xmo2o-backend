<if condition="MODULE_NAME eq 'Tmpls'">
    <link href="{pigcms{$static_path}css/style-1.css?id=103" rel="stylesheet" type="text/css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/style_2_common.css?BPm">
    <link rel="stylesheet" href="/static/kindeditor/themes/default/default.css" />
    <link rel="stylesheet" href="/static/kindeditor/plugins/code/prettify.css" />
    <script src="/static/kindeditor/kindeditor.js" type="text/javascript"></script>
    <script src="/static/kindeditor/lang/zh_CN.js" type="text/javascript"></script>
    <script src="/static/kindeditor/plugins/code/prettify.js" type="text/javascript"></script>
    <script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
    <script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>


    <link href="/static/tmpls/css/style.css" rel="stylesheet" type="text/css" />
    <link href="/static/tmpls/css/product.css" rel="stylesheet" type="text/css" />
    <script src="/static/tmpls/js/jquery.tools.min.js" type="text/javascript"></script>
    <script src="/static/tmpls/js/jquery.mixitup.min.js" type="text/javascript"></script>
    <script src="/static/tmpls/js/jquery.lazyload.min.js" type="text/javascript"></script>
    <style>
        li .mbtip {
            display: none;
        }
        .cateradio li:hover .mbtip {
            background-color: #000000;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 7px;
            box-shadow: 0 0 10px 2px rgba(0, 0, 0, 0.15);
            color: #FFFFFF;
            display: block;
            padding: 6px;
            float:right;
            /* position:relative;
             right:-140px;
             top:-325px;	*/
            width: 130px;
            text-align: left;
            z-index: 9999;
        }
    </style>
</if>
<div id="sidebar" class="sidebar responsive" <if condition="C('butt_open') || $no_sidebar">style="display:none;"</if>>
<if condition="empty($config['single_system'])">
    <div class="sidebar-shortcuts" id="sidebar-shortcuts">
        <div class="sidebar-shortcuts-large" id="sidebar-shortcuts-large">
            <a class="btn btn-success" href="{pigcms{:U('Config/merchant')}" title="{pigcms{:L_("商家设置")}">
            <i class="ace-icon fa fa-gear"></i>
            </a>&nbsp;
            <a class="btn btn-info" href="{pigcms{:U('Foodshop/index')}" title="{pigcms{$config.meal_alias_name}{pigcms{:L_("管理")}">
            <i class="ace-icon fa fa-cubes"></i>
            </a>&nbsp;
            <a class="btn btn-warning" href="{pigcms{:U('Group/index')}" title="{pigcms{$config.group_alias_name}{pigcms{:L_("管理")}">
            <i class="ace-icon fa fa-desktop"></i>
            </a>&nbsp;
            <a class="btn btn-danger" href="{pigcms{:U('Card_new/index')}" title="{pigcms{:L_("会员管理")}">
            <i class="ace-icon fa fa-group"></i>
            </a>
        </div>
        <div class="sidebar-shortcuts-mini" id="sidebar-shortcuts-mini">
            <span class="btn btn-success"></span> <span class="btn btn-info"></span>
            <span class="btn btn-warning"></span> <span class="btn btn-danger"></span>
        </div>
    </div>
</if>
<ul class="nav nav-list" style="top: 0px;">
    <volist name="merchant_menu" id="vo">
        <li <if condition="$vo['is_active']">class="open"</if>>
        <a <if condition="$vo['menu_list']">href="#" class="dropdown-toggle"<elseif condition='$vo["id"] eq 1' />href="{pigcms{$vo.url}"</if>>
        <i class="menu-icon fa {pigcms{$vo.icon}"></i>
        <span class="menu-text">{pigcms{$vo.name} </span>
        <if condition="$vo['menu_list']">
            <b class="arrow fa fa-angle-down"></b>
        </if>
        </a>

        <b class="arrow"></b>
        <if condition="$vo['menu_list']">
            <ul class="submenu" <if condition="$vo['is_active']"> style="display:block"</if>>
            <volist name="vo['menu_list']" id="voo">
                <li <if condition="$voo['is_active']">class="show open"</if>>
                <a <if condition="$voo['menu_list']">href="#" class="dropdown-toggle"<else/>href="{pigcms{$voo.url}"</if>>
                <i class="menu-icon fa fa-caret-right"></i>
                {pigcms{$voo.name}
                <if condition="$voo['menu_list']">
                    <b class="arrow fa fa-angle-down"></b>
                </if>
                </a>

                <b class="arrow"></b>

                <ul <if condition="$voo['is_active']">class="submenu nav-show open"  style="display: block;"<else />class="submenu nav-hide" style="display: none;"</if>>
                <volist name="voo['menu_list']" id="val">
                    <li <if condition="$val['is_active']">class="nav-show open"</if>>
                    <a href="{pigcms{$val.url}">
                        <i class="menu-icon fa"></i>
                        {pigcms{$val.name}
                    </a>

                    <b class="arrow"></b>
                    </li>
                </volist>
</ul>
</li>
</volist>
</ul>
</if>
</li>
</volist>
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
        <volist name="merchant_menu" id="vo">
            <php>if($vo['menu_list'] || $vo['url']){</php>
            <li class="<if condition="$vo['is_active']">open active<elseif condition="$vo['menu_list']"/>not-open</if>">
            <a <if condition="$vo['menu_list']">href="javascript:;" class="dropdown-toggle"<else/>href="{pigcms{$vo.url}"</if> <if condition="$vo['module'] eq 'House'">target="_blank"</if>>
            <i class="menu-icon fa {pigcms{$vo.icon}"></i>
            <span class="menu-text">{pigcms{$vo.name}</span>
            </a>
            <b class="arrow"></b>
            <if condition="$vo['menu_list']">
                <div class="submenu-box">
                    <div class="second-sidebar-title">{pigcms{$vo.name}</div>
                    <ul class="submenu">
                            <volist name="vo['menu_list']" id="voo">
                            <li <if condition="$voo['is_active']">class="show open"</if>>
                            <a <if condition="$voo['menu_list']">href="javascript:;" class="dropdown-toggle"<else/>href="{pigcms{$voo.url}"</if>><span class="menu-text">{pigcms{$voo.name}</span></a>
                            <ul <if condition="$voo['is_active']">class="submenu nav-show open"<else />class="submenu nav-hide"</if>>
                            <volist name="voo['menu_list']" id="val">
                                <li <if condition="$val['is_active']">class="nav-show open"</if>>
                                <a href="{pigcms{$val.url}"><span class="menu-text">{pigcms{$val.name}</span></a>
                                </li>
                            </volist>
                    </ul>
                    </li>
         </volist>
    </ul>
</div>
</if>
</li>
<php>}</php>
</volist>
</ul>
<!-- /.nav-list -->
</div>
<div id="new-third-sidebar">
    <div class="swiper-container" id='nav-scroll'>
        <div class="ui-nav clearfix swiper-wrapper"></div>
    </div>
</div>