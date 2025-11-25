<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Mall/index_sort_set')}" frame="true" refresh="true">
		<input type="hidden" name="goods_id" value="{pigcms{$goods_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="90">首页排序</th>
				<td><input type="text" class="input fl" name="index_sort" value="{pigcms{$index_sort}" size="10" placeholder="分类排序" validate="maxlength:6,required:true,number:true" tips="数值越大越靠前，用于商城首页---为您优选商品列表排序、商城首页头部分类下---为您优选商品列表排序"/></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>