<include file="Public:header"/>
<style type="text/css">
	.add_mer{
		border: 1px solid #666;
	    padding: 5px;
	    border-radius: 7px;
	}
</style>
<form id="myform"   frame="true" refresh="true">
	<input type="hidden" name="mer_id" value="{pigcms{$_GET.mer_id}"/>
	<table cellpadding="0" cellspacing="0" class="frame_form ListProduct" width="100%" align="center">
		<thead>
			<tr>
			<th>商家名称</th>
			<th>会员数</th>
			<th>当前会员卡状态</th>
			<th style=" width:80px;">操作 <span class="tooltips" ><span>
			</span></span></th>
			</tr>
		</thead>
		<tbody>
			<if condition="$merchant_list">
			<volist name="merchant_list" id="m">
			<tr class="user">
				<input type="hidden" name="mer_ids[]" value="{pigcms{$m.union_mer_id}" />
				<td>{pigcms{$m.name}</td>
				<td>{pigcms{$m.user_count}</td>
				<td><if condition="$m['status'] eq 1">已启用<else/>未启用</if></td>
				<td class="norightborder">
					<button type="button" onClick="delThis(this)">{pigcms{:L_("删除")}</button>
				</td>
			</tr>
			</volist>
			<else/>
				<tr class="user">
					<td colspan="4">
						暂未添加任何商家					
					</td>
				</tr>
			</if>
		</tbody>
	</table>
	<div style="text-align: center;">
		<label class="add_mer" onclick="select_mer()">+添加商家</label>
	</div>
	<div class="btn">
		<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
	</div>
</form>
<script>
	var _selected_mer = '{pigcms{$selected_mer_ids}';
	function select_mer(){
		layer.open({
			type: 2,
			area: ['95%', '95%'],
			fixed: false,
			maxmin: true,
			content: '{pigcms{:U("mer_list")}&mer_id={pigcms{$_GET["mer_id"]}'+'&selected='+_selected_mer,
			btn: ['确定', '取消'],
			yes: function(index, layero){
				var frameId = $(layero).find("iframe").attr('id');
				var selected = $.trim($(window.frames[frameId].document).find('[name="selected"]').val());
				_selected_mer = selected;
				$(".ListProduct tbody").empty();
				$.get("{pigcms{:U('ajaxGetMerchant')}",{selected:selected},function(data){
					if(data.length > 0){
						for(var i = 0; i< data.length; i++){
							$(".ListProduct tbody").append('<tr>'+
										'<input type="hidden" name="mer_ids[]" value="'+data[i].mer_id+'" />'+
										'<td>'+data[i].name+'</td>'+
										'<td>'+data[i].user_count+'</td>'+
										'<td>'+(data[i].status == 1 ? '已启用' : '未启用')+'</td>'+
										'<td>'+
												'<button type="button" onClick="delThis(this)">{pigcms{:L_("删除")}</button>'+
										'</td>'+
									'</tr>');
						}
					}
				},'json');
			    layer.close(index);
			},
			btn2: function(index){
				layer.close(index);
			}
		});
	}

	function delThis(t){
		if(confirm('{pigcms{:L_("你确定要删除此商家吗？")}')){
			var mer_id = $(t).parents("tr").find('[name="mer_id[]"]').val();
			var _selected_mer_arr = _selected_mer.split(',');
			var _selected_mer_arr_new = [];
			for(var i = 0;i< _selected_mer_arr.length;i++){
				if(_selected_mer_arr[i] != mer_id){
					_selected_mer_arr_new.push(_selected_mer_arr[i]);
				}
			}
			_selected_mer = _selected_mer_arr_new.join(",");
			$(t).parents("tr").eq(0).remove();
		}
	}
</script>
<include file="Public:footer"/>