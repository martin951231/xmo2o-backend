<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('index')}">{pigcms{:L_("商家")}{pigcms{$config.gift_alias_name}{pigcms{:L_("积分商城首页配置")}</a>
			</li>
		</ul>
	</div>
    <style>
        .flex {
            display: flex !important;
            display: -webkit-box;
            display: -moz-box;
            display: -ms-flexbox;
            display: -webkit-flex;
            align-items: center;
        }
    </style>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area flex" style="padding: 30px;">
            <div>
                <a href="{pigcms{:U('Integral_mall/index_advert', array('type_key' => 'index_top_advert'))}" class="btn btn-success" style="margin-left:10px;background-color:#0099cc;">{pigcms{:L_("wap站商家积分商城首页轮播图")}</a>
            </div>
            <div>
                <a href="{pigcms{:U('Integral_mall/index_slider')}" class="btn btn-success" style="margin-left:10px;background-color:#0099cc;">{pigcms{:L_("wap站商家积分商城首页导航")}</a>
            </div>
            <div>
                <a href="{pigcms{:U('Integral_mall/index_advert', array('type_key' => 'index_middle_single_advert'))}" class="btn btn-success" style="margin-left:10px;background-color:#0099cc;">{pigcms{:L_("wap站商家积分商城首页中部单图")}</a>
            </div>
		</div>
	</div>
</div>
<include file="Public:footer"/>