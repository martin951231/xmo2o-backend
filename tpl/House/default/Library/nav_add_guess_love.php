<include file="Publics:header"/>
<style>
	.frame_form .input{
		height:30px;
	}
</style>

	<form id="myform" method="post"   <if condition="!empty($detail) AND $detail['id'] gt 0"> action="{pigcms{:U('Library/nav_amend_new')}"  <else /> action="{pigcms{:U('Library/nav_modify_new')}" </if>  enctype="multipart/form-data">
		<input type="hidden" name="row_type" value="{pigcms{$row_type}"/>
        <input type="hidden"  name="block_type_id" value="{pigcms{$block_type_id}"/>
        <input type="hidden"  name="is_community_platform" value="{pigcms{$is_community_platform}"/>
        <input type="hidden"  name="id" value="{pigcms{$detail.id}"/>
        
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <if condition="$block_type_id eq 'business_classify_info'">
        	<tr>
				<th width="100">邻里分类</th>
				<td>
                     <select name="business_cat_id" id="business_cat_id" style="width:300px;" tips="请选择分类信息分类数据" validate="required:true" required>
	                   	<option value="">请选择分类数据</option>
	                   	<volist name='classify_category_list' id='vo' >
	                   		<option value="{pigcms{$vo.cid}" <if condition="$detail['business_cat_id'] eq $vo['cid']"> selected="true" </if> >{pigcms{$vo.cat_name}</option>
	                   	</volist>
                   </select>
                
                </td>
			</tr>
			<tr>
				<th width="100">副标题</th>
				<td><input type="text" class="input fl" name="name" value="{pigcms{$detail.name}" style="width:300px;"  placeholder="请输入名称4个字以内"  maxlength="4" required validate="maxlength:4,required:true" /></td>
			</tr>
        </if>

        <if condition="$block_type_id eq 'business_store_info'">
        	<tr>
				<th width="100">标题</th>
				<td><input type="text" class="input fl" name="name" value="{pigcms{$detail.name}" style="width:300px;"  placeholder="请输入名称6个字以内"  maxlength="6" required validate="maxlength:6,required:true" /></td>
			</tr>
            <if condition="!empty($detail) AND  $detail['img']">
            <tr>
				<th width="80">现图</th>

				<td>
					<img src="{pigcms{$detail['img']}" style="width:150px;height:150px;" class="view_msg"/>
				</td>
			</tr>
            </if>
            <tr>
				<th width="80">图标</th>
				<td><input type="file" class="input fl" name="img" style="width:200px;height:35px;" placeholder="请上传图片" tips="不修改请不上传！上传新图片，老图片会被自动删除！" <if condition="empty($detail) OR !isset($detail['img']) OR !$detail['img']"> validate="required:true" required </if> /></td>
			</tr>
        	<tr>
				<th width="100">店铺分类</th>
				<td>
                     <select name="business_cat_id" id="business_cat_id" style="width:300px;" tips="请选择分类信息分类数据" validate="required:true" required>
	                   	<volist name='classify_category_list' id='vo' >
	                   		<option value="{pigcms{$vo.cid}" <if condition="$detail['business_cat_id'] eq $vo['cid']"> selected="true" </if> >{pigcms{$vo.cat_name}</option>
	                   	</volist>
                   </select>
                
                </td>
			</tr>
           <tr>
				<th width="100">展示店铺数量</th>
				<td><input type="number" class="input fl" name="business_num" max="10000" min="0" step="1" value="{pigcms{$detail.business_num}" style="width:300px;"    required validate="maxlength:6,required:true" /></td>
			</tr>
        </if>
			<tr>
				<th width="100">排序</th>
				<td><input type="text" class="input fl" name="sort" style="width:100px;" value="{pigcms{$detail.sort}"/></td>
			</tr>
			<tr>
				<th width="100">是否开启</th>
				<td>
					<span class="cb-enable"><label class="cb-enable  <if condition="empty($detail) OR !isset($detail['status']) OR ($detail.status eq 1)">selected"</if>" ><span>开启</span><input type="radio" name="status" value="1"  <if condition="empty($detail) OR !isset($detail['status']) OR ($detail.status eq 1)">checked="checked"</if>  /></label></span>
					<span class="cb-disable"><label class="cb-disable  <if condition="!empty($detail) AND isset($detail['status']) AND $detail.status eq 0">selected"</if>"><span>关闭</span><input type="radio" name="status" value="0"  <if condition="!empty($detail) AND isset($detail['status']) AND $detail.status eq 0">checked="checked"</if>  /></label></span>
				</td>
			</tr>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>




<script>


</script>
<include file="Publics:footer"/>