<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-home home-icon"></i>
			<li><a href="{pigcms{:U('Index/index')}">{pigcms{:L_("首页")}</a></li>
			<li class="active">{pigcms{:L_("商户公告")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12 infobox-container">
					<h3>{pigcms{$now_news.title}</h3>
					<p style="font-size:12px;">{pigcms{:L_("发布时间：")}{pigcms{$now_news.add_time|date='m-d H:i',###}</p>
					<div style="font-size:14px;text-align:left;width:80%;margin:50px auto 0;">{pigcms{:replace_file_domain_content($now_news['content'])}</div>
				</div>
			</div>
		</div>
	</div>
</div>
<include file="Public:footer"/>
