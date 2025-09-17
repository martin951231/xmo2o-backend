<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - 派发优惠券</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<style>
			a:hover,a:visited{color:#666;}
			table{border-style:solid;border-width:1px 1px 0 0;border-color:#ccc;}
			table td{border-style:solid;border-width:0 0 1px 1px;border-color:#ccc;}
		</style>
	</head>
	<body>
		<p style="color: red;" id="loading_window">正在派发优惠券，请勿关闭窗口</p>
		<table style="overflow:auto" border="none;" cellpadding="0" cellspacing="0">
			<thead>
				<tr>
					<th>用户ID</th>
					<th>用户手机号</th>
					<th>用户名</th>
					<th>派送状态</th>
				</tr>

			</thead>
			<tbody id="sendResult">

			</tbody>
		</table>
		
	
		<!--<div class="btn">
			<button id="submit" type="submit">确定</button>
			<button id="reset" type="reset">取消</button>
		</div>-->

		<script>
			var coupon_list = "{pigcms{$coupon_list}";
			var condition = "{pigcms{$condition}";
			var type = "{pigcms{$type}";
			var uids_type = "{pigcms{$uids_type}";
			var send_num = "{pigcms{$send_num}";
			function loop_send_coupon(lastuid){
				$.post('{pigcms{:U("ajax_send_new")}', {coupon_list:coupon_list,condition:condition,type:type,lastuid:lastuid,uids_type:uids_type,send_num:send_num
				}, function(data){
					if(data.status == 1){
						var str = '<ul>';
						for(var i = 0 ;i< data.data.length; i++){
							str += '<li>优惠券(<font color="blue">'+data.data[i].coupon_name+'</font>)<font color="'+(data.data[i].send_code != 0 ? 'red' : 'green')+'">'+(data.data[i].send_code != 0 ? '派送失败' : '派送成功')+'</font>'+data.data[i].error_msg+'</li>';
						}
						str += '</ul>';
						$("#sendResult").append('<tr>'+
							'<td >'+data.uinfo.uid+'</td>'+
							'<td >'+data.uinfo.phone+'</td>'+
							'<td >'+data.uinfo.account+'</td>'+
							'<td class="content">'+str+'</td>'+
						'</tr>');
						setTimeout("loop_send_coupon("+data.uinfo.uid+")", 100);
					}
					else{
						$("#loading_window").text(data.msg);
					}
				},'json');
			}

			loop_send_coupon(0);
		</script>
	</body>
</html>