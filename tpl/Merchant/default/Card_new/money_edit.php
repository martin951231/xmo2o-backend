<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("余额批量管理")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<style>
			a:hover,a:visited{color:#666;}
		</style>
	</head>
	<body>
	<form id="myform" method="post" action="{pigcms{:U('Card_new/money_edit')}" frame="true" refresh="true" autocomplete="off" onsubmit="return false;" >
		<input type="hidden" name="gid" value="{pigcms{$card_group.id}">
		<input type="hidden" name="ids" value="{pigcms{$_GET.ids}">
		<table>
			<tr>
				<td colspan="2" >
					<div>用户组别：{pigcms{$card_group['name']}</div>
					<div>选择人数：{pigcms{$user_count}</div>
				</td>
				
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("余额管理")}</th>
				<td width="85%" colspan="3">
					<select name="type">
						<option value="1">批量增加</option>
						<option value="2">批量减少</option>
					</select>
					<input type="text" class="input" name="give_money" value=""/>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="color: red;">批量减少只能减少用户非自充余额，个别余额不足扣减时将操作失败</td>
				
			</tr>
		</table>
		
	
		<div class="btn">
			<button id="submit" type="submit">{pigcms{:L_("确定")}</button>
			<button id="reset" type="reset">{pigcms{:L_("取消")}</button>
		</div>
		</form>
		<script>
			$(function(){
				
				
				$('#group_id').change(function(){
					$('#frmselect').submit();
				});
				$('#submit').click(function(){
					$.ajax({
						url: '{pigcms{:U('Card_new/money_edit')}',
						type: 'POST',
						dataType: 'json',
						data: $('#myform').serialize(),
						success:function(date){
							if(date.status){
								alert(date.info);
								parent.location.reload();   
							}else{
								alert(date.info);
							}
						}
					});
				});
				
				$('#reset').click(function(){
				 parent.location.reload();   

				});
			});
		</script>
	</body>
</html>