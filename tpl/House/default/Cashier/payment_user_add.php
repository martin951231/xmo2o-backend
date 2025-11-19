<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - 社区管理中心</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
	</head>
    <style type="text/css">
    .green{ color:green}
     .input-text::-webkit-input-placeholder {
            /*color: #b5b5b5;*/
            font-size: 6px;   
        }

    </style>
	<body>
		<form id="myform" action="{pigcms{:U('payment_user_add')}" method='post'>
			<table>
				<tr>
					<th width="15%">收费项目</th>
					<td width="85%">
						{pigcms{$payment_info['payment_name']}

						<input type="hidden" name="payment_id" value="{pigcms{$_GET['payment_id']}">
					</td>
				</tr>


				<tr>
					<th width="15%">收费项目</th>
					<td width="85%">
						{pigcms{$standard_txt}

						<input type="hidden" name="standard_id" value="{pigcms{$_GET['standard_id']}">
						<input type="hidden" name="metering_mode_type" value="{pigcms{$standard_info['metering_mode_type']}" style="line-height: 20px;" >
					</td>
				</tr>

				<if condition="$standard_info['pay_type'] eq 2 && $standard_info['metering_mode_type'] eq 2">
					<tr>
						<th width="15%">{pigcms{$standard_info['metering_mode']}</th>
						<td width="85%">
							<input type="text" name="metering_mode_val" onkeyup="if(isNaN(value))execCommand('undo')" value="" style="line-height: 20px;" >
						</td>
					</tr>
				</if>
				
				<tr>
					<th width="15%">计费开始时间</th>
					<td width="85%">
						<input style="height: 25px; " type="text" name="start_time" class="input-text" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="不填写表示从今天开始计费"/>
						<font color="red">* 费用账单开始生成时间</font>
					</td>
				</tr>

				<tr>
					<th width="15%">计费结束周期</th>
					<td width="85%">
						<input style="height: 25px; font-size: 8px;" type="text" name="cycle_sum" id="cycle_sum" value="" placeholder="不填写表示按最大周期计算" />
						<font color="red">&nbsp;*超过结束周期，不再生成费用账单，业主也无需再缴纳费用</font>
					</td>
				</tr>

				<tr>
					<th width="15%">标准备注</th>
					<td width="85%">
						<input style="height: 25px; font-size: 8px;" type="text" name="remarks" value="" />
					</td>
				</tr>
				<input type="hidden" id="max_cycle" value="{pigcms{$standard_info['max_cycle']}" />
				<tr>
					<td colspan="2"><button class="chk_express" style=" margin:0 auto; display:block;">保存</button></td>
				</tr>
			 
			</table>
		</form>
	</body>
	<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>

	<script>
	$(".chk_express").click(function(){


	    var cycle_sum = parseInt($("#cycle_sum").val());
	    var max_cycle = parseInt($("#max_cycle").val());
	    if(cycle_sum > max_cycle){
	    	alert('您输入的周期超过最大周期了请重新填写！');
	    	return false;
	    }
	})

	</script>
</html>