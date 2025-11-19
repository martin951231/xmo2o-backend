<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("批量新增会员")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<style>
			a:hover,a:visited{color:#666;}
		</style>
	</head>
	<body>
		<form id="myform" method="post" action="{pigcms{:U('import_user')}" enctype="multipart/form-data">
			<table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
				<tr>
					<th width="80">示例表格</th>
					<td><a target="_blank" href="{pigcms{$static_public}file/card_user_sample.xls" class="button" style="margin-left:0px;">点击下载</a></td>
				</tr>
                <tr>
                    <th width="80">Excel导入</th>
                    <td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传excel表格" validate="required:true"/></td>
                </tr>
				<tr>
					<th width="80">注意事项</th>
					<td><span style="color: green;">手机号一栏只允许填写一个手机号</span></td>
				</tr>
			</table>
			<div class="clearfix form-actions">
				<div class="col-md-offset-3 col-md-9">
					<button class="btn btn-info" type="submit">
						<i class="ace-icon fa fa-check bigger-110"></i>
						保存
					</button>
				</div>
			</div>
		</form>
		<script>
			$(function(){
				$('.handle_btn').live('click',function(){
					art.dialog.open($(this).attr('href'),{
						init: function(){
							var iframe = this.iframe.contentWindow;
							window.top.art.dialog.data('iframe_handle',iframe);
						},
						id: 'handle',
						title:'{pigcms{:L_("编辑")}',
						padding: 0,
						width: 720,
						height: 520,
						lock: true,
						resize: false,
						background:'black',
						button: null,
						fixed: false,
						close: null,
						left: '50%',
						top: '38.2%',
						opacity:'0.4'
					});
					return false;
				});
				
				$('#group_id').change(function(){
					$('#frmselect').submit();
				});
			});
		</script>
	</body>
</html>