<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('activity_modify_adver')}" enctype="multipart/form-data">
		<span style="display:none;">
			<input type="hidden" name="id" value="{pigcms{$now_active.id}" validate="maxlength:20"/>
			<input type="hidden" name="group_id" value="<if condition="$now_active">{pigcms{$now_active.group_id}<else/>{pigcms{$_GET.group_id}</if>"/>
		</span>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" name="active_name" value="{pigcms{$now_active.active_name}" size="60" validate="maxlength:50,required:true" tips="活动名称"/></td>
			</tr>
			<tr>
				<th width="80">图片</th>
				<td><input type="file" class="input fl" name="active_img" style="width:200px;" placeholder="请上传图片" <empty name="now_active['active_name']">validate="required:true"</empty> tips="图片尺寸 223*343"/></td>
			</tr>
			<tr>
				<th width="80">链接</th>
				<td>
					<input type="text" class="input fl" name="active_link" id="active_link" value="{pigcms{$now_active.active_link}" size="50" validate="url:true" placeholder="" tips="跳转的链接"/>
					 <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('active_link', 0, 0)" data-toggle="modal">从功能库选择</a>
				</td>
			</tr>
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
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
	<script>
		var pageDomId = ''
		function addLink(domid, iskeyword, type){
			pageDomId = domid;
			art.dialog.data('domid', domid);
			parent.setLinkBases(window, 'platform', 'h5');
		}
		//链接库回调方法
		function getLinkBasesUrl(url){
			$('#' + pageDomId).val(url);
		}

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