<include file="Public:header"/>
<form id="myform" method="post" action="{pigcms{:U('Service/auto_goods_modify')}" frame="true" refresh="true">
	<input type="hidden" name="id" value="{pigcms{$info.id}"/>
	<input type="hidden" name="cid" value="{pigcms{$cid}"/>
	<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
		<tr>
			<th width="80">商品名称</th>
			<td>
				<input type="text" class="input fl" name="name" id="name" size="25" value="{pigcms{$info.name}" placeholder="" validate="maxlength:20,required:true" tips=""/>
			</td>
		</tr>
		<tr>
			<th width="80">商品排序</th>
			<td>
				<input type="text" class="input fl" name="sort" value="{pigcms{$info.sort}" size="10" placeholder="分类排序" validate="maxlength:6,required:true,number:true" tips="默认添加时间排序！手动排序数值越大，排序越前。"/>
			</td>
		</tr>
	</table>
	<div class="btn hidden">
		<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
		<input type="reset" value="取消" class="button" />
	</div>
</form>
<include file="Public:footer"/>
