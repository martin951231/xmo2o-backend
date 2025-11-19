<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("会员卡编辑")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<style>
			a:hover,a:visited{color:#666;}
		</style>
	</head>
	<body>
		<form id="myform" method="post" action="{pigcms{:U('Card_new/add_user')}" frame="true" refresh="true" autocomplete="off" onsubmit="return false;">
			<table>
				<tr> 
					<th width="20%">{pigcms{:L_("实体卡号")}</th>
					<td width="80%" colspan="3"><input type="text" class="input"  name="physical_id" value="{pigcms{$card.physical_id}"><span style="margin-left: 20px;color: red">实体卡号/工号/学号，可以为空</span></td>
				</tr>
				<tr>
					<th width="20%">{pigcms{:L_("姓名")}</th>
					<td width="80%" colspan="3"><input type="text" class="input" name="name"/>&nbsp;&nbsp;&nbsp;</td>
				</tr>
				<tr>
					<th width="20%">{pigcms{:L_("性别")}</th>
					<td width="80%" colspan="3">
						<select name="sex">
							<option value="0">未知</option>
							<option value="1">男</option>
							<option value="2">女</option>
						</select>
					</td>
				</tr>
				<tr>
					<th width="20%">{pigcms{:L_("手机号")}</th>
					<td width="80%" colspan="3"><input type="text" class="input" name="phone"/>&nbsp;&nbsp;&nbsp;若填写手机号，会自动生成用户且绑定上了手机号。若需要用户扫微信码领卡，请不要填写。</td>
				</tr>
				<if condition="$config.merchant_card_recharge_offline eq 1">
				<tr>
					<th width="20%">{pigcms{:L_("会员卡余额初始值")}</th>
					<td width="80%" colspan="3"><input type="text" class="input" name="card_money_give" size="10" validate="number:true" tips="{pigcms{:L_("会员卡余额初始值")}"/></td>
				</tr>
				</if>
				<tr>
					<th width="20%">{pigcms{:L_("会员卡")}{pigcms{$config['score_name']}{pigcms{:L_("初始值")}</th>
					<td width="80%" colspan="3"><input type="text" class="input" name="card_score" size="10" validate="number:true" tips="{pigcms{:L_("会员卡")}{pigcms{$config['score_name']}{pigcms{:L_("初始值")}"/></td>
				</tr>
				<if condition="$card_group">
					<tr>
						<th width="15%">{pigcms{:L_("用户分组")}</th>
						<td width="85%" colspan="3">
							<select name="gid">
								<option value="{pigcms{$vo.id}">请选择用分组</option>
								<volist name="card_group" id="vo">
								<option value="{pigcms{$vo.id}">{pigcms{$vo.name}</option>
								</volist>
							</select>
						</td>
					</tr>
				</if>
				<tr>
					<th width="20%">{pigcms{:L_("备注")}</th>
					<td width="80%" colspan="3"><textarea name="remark" rows='4' cols='30'></textarea></td>
				</tr>
				<tr>
					<th width="20%">{pigcms{:L_("状态")}</th>
					<td width="80%" colspan="3">
						<select name="status">
							<option value="1" selected="selected">{pigcms{:L_("正常")}</option>
							<option value="0">{pigcms{:L_("禁止")}</option>
						</select>
					</td>
				</tr>		
			</table>
			<div class="btn">
				<button id="submit" type="submit">{pigcms{:L_("确定")}</button>
			</div>
		</form>
		<script>
			$(function(){
				$('#submit').click(function(){
					$.ajax({
						url: "{pigcms{:U('Card_new/add_user')}",
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