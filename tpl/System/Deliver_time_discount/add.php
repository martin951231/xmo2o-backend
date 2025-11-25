<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('add')}" enctype="multipart/form-data" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="15%">优惠时间段名称</th>
				<td width="35%"><input type="text" class="input fl" name="title" size="20" validate="required:true" placeholder="请填写优惠时间段名称"  tips=""/></td>
			</tr>
			<tr>
				<th width="15%">优惠时间段</th>
				<td width="35%">
					<input type="text" autocomplete="off" class="input fl" name="start_time" value="00:00" size="20" onclick="WdatePicker({dateFmt: 'HH:mm', minDate: '00:00', maxDate: '23:59' })"/>
					&nbsp;-&nbsp;
					<input type="text" autocomplete="off" class="input " name="end_time" value="00:00" size="20" onclick="WdatePicker({dateFmt: 'HH:mm', minDate: '00:00', maxDate: '23:59' })"/>
				</td>
			</tr>
			<tr>
				<th width="15%">优惠次数</th>
				<td width="35%"><input type="number" class="input fl" name="number" size="20" validate="required:true" placeholder="请填写优惠次数" tips=""/></td>
			</tr>
			<tr>
				<th width="15%">优惠金额</th>
				<td width="35%"><input type="text" class="input fl" name="money" size="20" validate="required:true" placeholder="请填写优惠金额" tips=""/></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>