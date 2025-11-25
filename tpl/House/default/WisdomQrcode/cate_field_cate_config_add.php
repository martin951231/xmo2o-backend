<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - 小区管理中心</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
		<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	</head>
<style type="text/css">
	.jqstooltip {
		position: absolute;
		left: 0px;
		top: 0px;
		visibility: hidden;
		background: rgb(0, 0, 0) transparent;
		background-color: rgba(0, 0, 0, 0.6);
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000,endColorstr=#99000000);
		-ms-filter:"progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000, endColorstr=#99000000)";
		color: white;
		font: 10px arial, san serif;
		text-align: left;
		white-space: nowrap;
		padding: 5px;
		border: 1px solid white;
		z-index: 10000;
	}

	.jqsfield {
		color: white;
		font: 10px arial, san serif;
		text-align: left;
	}

	.statusSwitch, .orderValidSwitch, .unitShowSwitch, .authTypeSwitch {
		display: none;
	}

	#shopList .shopNameInput, #shopList .tagInput, #shopList .orderPrefixInput
		{
		font-size: 12px;
		color: black;
		display: none;
		width: 100%;
	}
	.fl{ float:left;}
	.fr{ float:right;}
		a{
			margin-right:5px;
		}
	#bind_info_btn{
		float:left ;
		margin-left:10px;
		margin-bottom: 5px;
		margin-top: 5px;
	    border: 1px solid #87b87f;
	    color:#87b87f;
	}
	#bind_info_btn span{
		padding-top: 5px;
	    color:#FFF
	}
</style>
	<form id="myform" action="{pigcms{:U('cate_field_cate_config_add')}" method='post'>
		<input type="hidden" name="field_cate_id" value="{pigcms{$_GET['field_cate_id']}" >

		<table>

			<tr>
				<th width="80">字段名称</th>
				<td><input type="text" class="input fl" name="title" id="title" size="25" placeholder="" validate="maxlength:20,required:true" tips="123456"/>
					<font color="red">*必填</font>
				</td>
			</tr>
			<tr>
				<th width="15%">类型</th>
				<td width="85%">
					<select name="type">
						<option value="1">文本</option>
						<option value="2">图片</option>
						<option value="3">日期</option>

					</select>
				</td>
			</tr>

			<tr>
				<th width="15%">排序</th>
				<td width="85%">
					<input type="text" style="padding-left: 0px" class="input fl" name="field_sort" id="sort" size="25" placeholder="" validate="maxlength:20,required:true" tips="排序"/>
				</td>
			</tr>

			<tr>
				<td colspan="4"><button class="chk_express" style=" margin:0 auto; display:block;float:right">保存</button></td>
			</tr>
		</table>
	</form>
	</body>


<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>


<script>
	var static_path = "{pigcms{$static_path}";
</script>


<script>


</script>
</body>
</html>