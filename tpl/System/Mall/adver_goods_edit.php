<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Mall/adver_goods_edit')}" frame="true" refresh="true">
		<input type="hidden" name="id" value="{pigcms{$info.id}"/>
		<input type="hidden" name="goods_id" value="{pigcms{$goods_id}"/>
		<input type="hidden" name="act_id" value="{pigcms{$act_id}"/>
		<input type="hidden" name="type" value="{pigcms{$type}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="90">是否推荐到首页</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$info['is_promote'] eq 1">selected</if>"><span>是</span><input type="radio" name="is_promote" value="1"  <if condition="$info['is_promote'] eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$info['is_promote'] eq 0">selected</if>"><span>否</span><input type="radio" name="is_promote" value="0"  <if condition="$info['is_promote'] eq 0">checked="checked"</if> /></label></span>
				</td>
			</tr>
			<tr>
				<th width="90">设置推荐时间</th>
				<td>推荐到<input type="text" class="input" name="promote_end_time" id="promote_end_time" value="{pigcms{$info.promote_end_time|default=''|date='Y-m-d H:i',###}" size="25" placeholder="" validate="maxlength:20,required:true" tips="" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>结束</td>
			</tr>
			<tr>
				<th width="90">排序</th>
				<td><input type="text" class="input fl" name="sort" value="{pigcms{$info.sort}" size="10" placeholder="分类排序" validate="maxlength:6,required:true,number:true" tips="数值越大越靠前，仅用于商城首页--导航区域下方的活动列表排序"/></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>