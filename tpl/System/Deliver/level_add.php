<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Deliver/level_add')}" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="15%">等级名称</th>
				<td width="35%"><input type="text" class="input fl" name="name" size="20" validate="maxlength:4,required:true" placeholder="请填写一个等级名"  tips="例如：见习骑士，白银骑士等，最多支持4字"/></td>
			</tr>
			<tr>
				<th width="15%">等级级别</th>
				<td width="35%"><input type="number" class="input fl" name="level" size="20" validate="maxlength:50,required:true" placeholder="请填写等级级别" tips="等级越大，配送员的福利也相应越大"/></td>
			</tr>
			<tr>
				<th width="15%">等级描述</th>
				<td width="35%"><input type="text" class="input fl" name="desc" size="20" validate="maxlength:8,required:true" placeholder="请填写等级描述" tips="用户描述骑士福利等信息，最多支持8字"/></td>
			</tr>
			<tr>
				<th width="15%">等级所需积分</th>
				<td width="35%"><input type="text" class="input fl" name="score" size="20" validate="maxlength:50,required:true" placeholder="请填写一个对应数字" tips="配送员想成为该等级会员所需要的积分，0为不需要积分"/></td>
			</tr>
			<tr>
				<th width="15%">每日接单量</th>
				<td width="35%"><input type="number" class="input fl" name="order_number" size="20" validate="maxlength:50,required:true" placeholder="请填写一个对应数字" tips="配送员成为该等级后每日最多可接单总数"/></td>
			</tr>
			<tr>
				<th width="15%">等级图片</th>
				<td width="35%">
                   <input type="file" class="input fl" name="image" style="width:200px;" placeholder="请上传图片" validate="required:true" tips="建议尺寸：100*100"/></td>
			</tr>			
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>