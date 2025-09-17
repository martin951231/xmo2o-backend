<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('activity_modify')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">选择商品</th>
				<td>
					<input type="button" value="选择商品" class="button" id="chooseProductBtn" style="margin-left:0px;margin-right:10px;"/>
					<span style="display:none;">
						<input type="hidden" name="id" value="{pigcms{$now_active.id}" validate="maxlength:20"/>
						<input type="hidden" name="other_id" value="{pigcms{$now_active.other_id}" validate="maxlength:20,required:true"/>
						<input type="hidden" name="group_id" value="<if condition="$now_active">{pigcms{$now_active.group_id}<else/>{pigcms{$_GET.group_id}</if>"/>
						<input type="hidden" name="active_img" value="{pigcms{$now_active.active_img}"/>
						<input type="hidden" name="active_type" value="{pigcms{$now_active.active_type}"/>
						<input type="hidden" name="store_id" value="{pigcms{$now_active.store_id}"/>
						<input type="hidden" name="mer_id" value="{pigcms{$now_active.mer_id}"/>
						<input type="hidden" name="lng" value="{pigcms{$now_active.lng}"/>
						<input type="hidden" name="lat" value="{pigcms{$now_active.lat}"/>
					</span>
				</td>
			</tr>
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" name="active_name" value="{pigcms{$now_active.active_name}" size="60" validate="maxlength:50,required:true" tips="名称"/></td>
			</tr>
			<tr>
				<th width="80">简介</th>
				<td><input type="text" class="input fl" name="active_desc" value="{pigcms{$now_active.active_desc}" size="60" validate="maxlength:100,required:true" tips="简介"/></td>
			</tr>
<!--			<tr>-->
<!--				<th width="80">描述</th>-->
<!--				<td><input type="text" class="input fl" name="active_info" value="{pigcms{$now_active.active_info}" size="60" validate="maxlength:100" tips="描述"/></td>-->
<!--			</tr>-->
			<tr>
				<th width="80">现价</th>
				<td><input type="text" class="input fl" name="active_price" value="{pigcms{$now_active.active_price}" size="10" validate="maxlength:20,required:true" tips="现价"/></td>
			</tr>
			<tr>
				<th width="80">原价</th>
				<td><input type="text" class="input fl" name="old_price" value="{pigcms{$now_active.old_price}" size="10" validate="maxlength:20,required:true" tips="原价"/></td>
			</tr>
<!--			<tr>-->
<!--				<th width="80">标注</th>-->
<!--				<td><input type="text" class="input fl" name="active_label" value="{pigcms{$now_active.active_label}" size="10" validate="maxlength:20" tips="标注"/></td>-->
<!--			</tr>-->
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="sort" value="{pigcms{$now_active.sort}" size="10" validate="maxlength:20" placeholder="" tips="值越高，排序越前"/></td>
			</tr>
			<tr>
				<th width="80">状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_active['status'] eq 1 || !isset($now_active['status'])">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$now_active['status'] eq 1 || !isset($now_active['status'])">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_active['status'] heq '0'">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$now_active['status'] heq '0'">checked="checked"</if>/></label></span>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script>
		$('#chooseProductBtn').click(function(){
			window.top.artiframe('{pigcms{:U('choose_product',array('city_id'=>$now_group['city_id']))}&from_page='+window.name,'选择商品',1000,600,true,false,false,false,'choose_plat_product',true);
		});
		function choose_product(data){
			$('input[name="active_desc"]').val('');
			$('input[name="old_price"]').val('');
			console.log(data);
			$('input[name="other_id"]').val(data.product_id);
			$('input[name="active_type"]').val(data.product_type);
			$('input[name="active_img"]').val(data.product_img);
			$('input[name="store_id"]').val(data.store_id);
			$('input[name="active_price"]').val(data.product_price);
			$('input[name="old_price"]').val(data.old_price ? data.old_price : data.product_price);
			$.post('{pigcms{:U('store_info')}',{store_id:data.store_id},function(result){
				console.log('result',result);
				$('input[name="lng"]').val(result.info.long);
				$('input[name="lat"]').val(result.info.lat);
				$('input[name="mer_id"]').val(result.info.mer_id);
				if(data.product_type == 'group' || data.product_type == 'shop' || data.product_type == 'mall' || data.product_type == 'new_mall'){
					$('input[name="active_name"]').val(result.info.name);
					$('input[name="active_desc"]').val(data.product_name);
				}else{
					$('input[name="active_name"]').val(data.product_name);
					$('input[name="active_desc"]').val(data.product_desc);
				}
			});
		}
	</script>
<include file="Public:footer"/>