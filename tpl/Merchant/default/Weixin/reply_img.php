<include file="Public:header" />
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li><i class="ace-icon fa fa-wechat"></i> <a
				href="{pigcms{:U('Weixin/index')}">{pigcms{:L_("公众号设置")}</a></li>
			<li>{pigcms{:L_("关键词回复")}</li>
			<li class="active">{pigcms{:L_("图文回复")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
			.ace-file-input a {
				display: none;
			}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li><a href="{pigcms{:U('Weixin/txt')}"> {pigcms{:L_("文字回复")} </a></li>
							<li class="active"><a href="{pigcms{:U('Weixin/img')}">{pigcms{:L_("图文回复")} </a></li>
							<!--li><a href="javascript:;">{pigcms{:L_("系统功能回复")} </a></li-->
						</ul>
						<div class="tab-content">
							<div>
								<a href="{pigcms{:U('Weixin/img')}" class="btn btn-success" style="margin-bottom: 20px;">{pigcms{:L_("返回列表")}</a>
							</div>
							<div class="form">
								<form class="well" id="food-form" action="" method="post">
									<p class="note">
										{pigcms{:L_("标有")}<span class="required" style="color: red;">*</span>{pigcms{:L_("的为必填选项")}
									</p>
									<div class="alert alert-danger" id="food-form_es_" <if condition="empty($error)">style="display:none"</if>><p>{pigcms{:L_("请更正下列输入错误")}:</p>
										<ul><li>{pigcms{$error}</li></ul>
									</div><br>
									<br> {pigcms{:L_("关键词")}<span class="required" style="color: red;">*</span>：<br>
									<input class="span3" size="30" maxlength="30" name="keyword" id="keyword" type="text" value="{pigcms{$keyword['keyword']}"><br>
									<br> <input id="pigcms_id" type="hidden" value="{pigcms{$keyword['pigcms_id']}" name="pigcms_id">
									<br>
									<br>{pigcms{:L_("选择图文消息")}<br>
									<select name="source_id" id="source_id">
									<volist name="list" id="vo">
									<option value="{pigcms{$vo['pigcms_id']}" <if condition="$keyword['from_id'] eq $vo['pigcms_id']">selected</if>>{pigcms{$vo['list'][0]['title']}<if condition="$vo['type']">{pigcms{:L_("（多图）")}<else />{pigcms{:L_("（单图）")}</if></option>
									</volist>
									</select><br>
									<div class="form-actions">
										<button class="btn btn-info" type="submit"><i class="ace-icon fa fa-check bigger-110"></i> {pigcms{:L_("提交")}</button>
									</div>
								</form>
							</div>
							<!-- form -->
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<include file="Public:footer" />