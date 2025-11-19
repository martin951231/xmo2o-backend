<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店铺管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
	</head>
	<style>
		.aui_footer{
			display:none;
		}
	</style>
	<body>
	<form id="myform" method="post" action="{pigcms{:U('Integral_mall/slider_add')}" enctype="multipart/form-data">
		<input type="hidden" name="type_key" value="index_slider"/>
		<input type="hidden" name="id" value="{pigcms{$_GET['id']}"/>
		
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">{pigcms{:L_("导航名称")}</th>
				<td><input type="text" class="input fl" name="name" size="20" placeholder="{pigcms{:L_("请输入名称")}" value="{pigcms{$slider.name}" validate="maxlength:20,required:true"/></td>
			</tr>
			<if condition="$slider['pic']">
				<tr>
					<th width="80">{pigcms{:L_("导航现图")}</th>
					<td><img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$slider.pic}" style="width:80px;height:80px;" class="view_msg"/></td>
				</tr>
			</if>
			<tr>
				<th width="80">{pigcms{:L_("导航图片")}</th>
				<td>
				<input type="file" class="input fl" name="pic" style="width:180px;" placeholder="{pigcms{:L_("请上传图片")}"/>
				{pigcms{:L_("图标建议尺寸")} 640*285
				</td>
			</tr>
			<tr>
				<th width="80">{pigcms{:L_("链接地址")}</th>
				<td>
				<input type="text" class="input fl" name="url" id="url" style="width:180px;" value="{pigcms{$slider.url}" placeholder="{pigcms{:L_("请填写链接地址")}" validate="maxlength:200,required:true,url:true"/>
				
				<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLinks('url', 0)" data-toggle="modal">{pigcms{:L_("从功能库选择")}</a>
				
				</td>
			</tr>
			<tr>
				<th width="80">{pigcms{:L_("导航排序")}</th>
				<td><input type="text" class="input fl" name="sort" style="width:80px;" value="{pigcms{$slider.sort}" validate="maxlength:10,required:true,number:true"/></td>
			</tr>
			<tr>
				<th width="20%">{pigcms{:L_("导航状态")}</th>
				<td width="80%" colspan="3">
					<select name="status">
						<option value="1" <if condition="$slider.status eq 1 OR !isset($_GET['id'])">selected="selected"</if>>{pigcms{:L_("正常")}</option>
						<option value="0" <if condition=" $slider AND $slider.status eq 0 ">selected="selected"</if>>{pigcms{:L_("禁止")}</option>
					</select>
				</td>
			</tr>
		</table>
		<div class="btn">
			<button type="submit">{pigcms{:L_("提交")}</button>
		
		</div>
	</form>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>

function addLinks(domid,iskeyword){
			art.dialog.data('domid', domid);
			art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'{pigcms{:L_("插入链接或关键词',width:800,height:500,yesText:'关闭")}',background: '#000',opacity: 0.45});
		}
		
$('.aui_footer').hide();
</script>
	</body>
</html>