<include file="Public:header"/>
	<form id="myform" method="post" action="" onsubmit="return false;" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<volist name="store_list" id="vo">
				<tr>
					<td style="height:50px;line-height:50px;padding:0px 20px;">
						<label for="store_{pigcms{$vo.store_id}"><input type="checkbox" name="store_id[]" value="{pigcms{$vo.store_id}" id="store_{pigcms{$vo.store_id}" <if condition="in_array($vo['store_id'],$store_ids)">checked="checked"</if>/>&nbsp;&nbsp;&nbsp;{pigcms{$vo.store_name}</label>
						<span style="display:none;" class="store_name_{pigcms{$vo.store_id}">{pigcms{$vo.store_name}</span>
					</td>
				</tr>
			</volist>
		</table>
		<div class="btn">
			<input type="button" name="dosubmit" id="dosubmit" value="选择" class="button" />
		</div>
		<div class="tip" style="margin:20px 0 0 20px;">点击 “ 选择 ” 后并非直接保存，需要在列表页面点击上线该优惠。</div>
	</form>
    <script type="text/javascript">
    $(function(){
		
		if($('input[type="checkbox"]').size() == 0){
			parent.layer.alert('暂无店铺',{
				offset: '300px',
				end:function(){
					parent.layer.closeAll();
				}
			});
		}
		
		$('#dosubmit').click(function(){
			var formData = $('#myform').serializeArray();
			var store_ids = [];
			var store_names = [];
			for(var i in formData){
				if(formData[i].name == 'store_id[]'){
					store_ids.push(formData[i].value);
					store_names.push($('.store_name_'+formData[i].value).html());
				}
			}
			var store_ids_str = store_ids.join(',');
			var store_names_str = store_names.join('，');
			parent.choose_store_callfunc(store_ids,store_names_str,{pigcms{$_GET.mer_id});
		});
    });
    </script>
<include file="Public:footer"/>