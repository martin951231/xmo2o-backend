<include file="Public:header"/>
<style type="text/css">
label{margin-right: 15px;}
.item{
	margin-bottom: 20px;
}
.item-title{
	margin-bottom: 10px;
	display: inline-block;
}
</style>
	<!-- <div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('User/index')}">用户列表</a>
			<a href="{pigcms{:U('User/label_index')}">用户标签管理</a>
			<if condition="$_GET['id']">
				<a href="{pigcms{:U('User/label_edit', ['id'=> $_GET['id']])}" class="on">编辑用户标签</a>
			<else/>
				<a href="{pigcms{:U('User/label_edit')}" class="on">添加用户标签</a>
			</if>
			<a href="{pigcms{:U('User/privileged_user')}">特权用户</a>
		</ul>
	</div> -->
	<form id="myform" method="post" action="{pigcms{:U('User/label_edit')}"  frame="true" refresh="true" autocomplete="off">
		<input type='hidden' name='id' value="{pigcms{$info.id}" />
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">标签名称：</td>
				<td><input type="text" class="input fl" name="name" value="{pigcms{$info['name']}" tips="" autocomplete="off" validate="maxlength:40,required:true">
				</td>
			</tr>
			<tr>
				<td width="80">是否默认：</td>
				<td>
					<label><input type="radio" value='1' name="is_default" <eq name='info.is_default' value='1'>checked='checked'</eq> >是</label>
					<label><input type="radio" value='0' name="is_default" <if condition="empty($info) || $info['is_default'] eq 0">checked='checked'</if>  >否</label>
					<span style='display:block;color:red;margin-top:5px;'>默认即新用户注册后自动归属该标签下</span>
				</td>
			</tr>
			<tr>
				<td width="80">标签类型：</td>
				<td>
					<label><input type="radio" value='0' name="type" <if condition="empty($info) || $info['type'] eq 0">checked='checked'</if>  >手动标签</label>
					<label><input type="radio" value='1' name="type" <eq name='info.type' value='1'>checked='checked'</eq> >自动标签</label>
				</td>
			</tr>
			<tr>
				<td width="80"></td>
				<td>
					<div class="content condition" <if condition="empty($info) || $info['type'] eq 0">style="display: none;"</if>>
						<div class="item">
							满足条件：
							<span> 
								<label><input type="radio" value='0' name="condition_type" <if condition="empty($info) || $info['condition_type'] eq 0">checked='checked'</if>  >满足任意一个被选中的条件即可</label>
								<label><input type="radio" value='1' name="condition_type" <eq name='info.condition_type' value='1'>checked='checked'</eq> >必须满足所有被选中的条件</label>
							</span>
						</div>
						<div class="item">
							<div class="item">
								<label class="item-title">
									<input type="checkbox" class="input checkbox" <if condition="$info['behavior'] eq 1">checked='checked'</if>  >无消费行为
									<input type="hidden"  name="behavior" value="{pigcms{$info['behavior']}" >
								</label>
								<div> 
									<label>
										<input type="radio" value='0' name="behavior_type" <if condition="empty($info) || $info['behavior_type'] eq 0">checked='checked'</if>  >
										最近
										<input type="text" name="behavior_day" class="input" value="{pigcms{$info.behavior_day}">
										天
									</label>
									<label>
										<input type="radio" value='1' name="behavior_type" <eq name='info.behavior_type' value='1'>checked='checked'</eq> >自定义
										<input type="text" class="input-text" name="behavior_start_time" style="width:120px;" id="d4311" <if condition="$info['behavior_start_time']">value="{pigcms{$info.behavior_start_time|date='Y-m-d',###}"</if> onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始日期" />			   
										<input type="text" class="input-text" name="behavior_end_time" style="width:120px;" id="d4311" <if condition="$info['behavior_end_time']">value="{pigcms{$info.behavior_end_time|date='Y-m-d',###}"</if> onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束日期" />
									</label>
								</div>
							</div>
							<div class="item">
								<label class="item-title">
									<input type="checkbox" class="input checkbox" <if condition="$info['times'] eq 1">checked='checked'</if>  >
									累计消费次数
									<input type="hidden"  name="times" value="{pigcms{$info['times']}" >
								</label>
								<div>
									<input type="number" name="times_min" class="input" value="{pigcms{$info.times_min}">次 - <input type="number" name="times_max" class="input" value="{pigcms{$info.times_max}"> 次
								</div>
							</div>
							<div class="item">
								<label class="item-title">
									<input type="checkbox" class="checkbox" <if condition="$info['money'] eq 1">checked='checked'</if>  >
									累计消费金额
									<input type="hidden"  name="money" value="{pigcms{$info['money']}" >
								</label>
								<div>
									<input type="text" name="money_min" class="input" value="{pigcms{$info.money_min}">元 - <input type="number" name="money_max" class="input" value="{pigcms{$info.money_max}"> 元
								</div>
							</div>
							<div class="item">
								<label class="item-title">
									<input type="checkbox"  class="checkbox"  <if condition="$info['spread_num'] eq 1">checked='checked'</if>  >
									推广用户数区间
									<input type="hidden"  name="spread_num" value="{pigcms{$info['spread_num']}" >
								</label>
								<div>
									<input type="number" name="spread_num_min" class="input" value="{pigcms{$info.spread_num_min}">人 - <input type="number" name="spread_num_max" class="input" value="{pigcms{$info.spread_num_max}"> 人
								</div>
							</div>
							<div class="item">
								<label class="item-title">
									<input type="checkbox"  class="input checkbox"  <if condition="$info['has_card'] eq 1">checked='checked' </if> >
									领过商家会员卡
									<input type="hidden"  name="has_card" value="{pigcms{$info['has_card']}" >
								</label>
							</div>
							<div class="item">
								<label class="item-title">
									<input type="checkbox"  class="input checkbox" <if condition="$info['buy_goods'] eq 1">checked='checked'</if>  >
									购买指定商品
									<input type="hidden"  name="buy_goods" value="{pigcms{$info['buy_goods']}" ></label>
								<table style="width:800px;" class="table table-bordered" id='goods_spec'>
									<thead>
										<tr>
											<th>{pigcms{:L_("商品编号")}</th>
											<th>{pigcms{:L_("所属业务")}</th>
											<th>{pigcms{:L_("商品名称")}</th>
											<th>{pigcms{:L_("现价")}</th>
											<th>{pigcms{:L_("操作")}</th>
										</tr>
									</thead>
									<tbody>
										<volist name="info['buy_goods_list']" id="buy">
										<tr>
											<input type='hidden' name="relation_goods_id[]" value="{pigcms{$buy.goods_id}" />
											<input type='hidden' name="relation_type[]" value="{pigcms{$buy.type}" />
											<td>{pigcms{$buy.goods_id}</td>
											<td>{pigcms{$buy.model}</td>
											<td>{pigcms{$buy.name}</td>
											<td>{pigcms{$buy.price}</td>
											<td>
												<button type="button" onClick="delThis(this)">移除</button>
											</td>
										</tr>							
										</volist>
									</tbody>
								</table>
								<a class="btn btn-success choose_good_btn" onclick="select_goods()">选择商品</a>
							</div>
						<div>
					</div>
				</td>
			</tr>
		</table>
		<div class="btn">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script type="text/javascript">
		$('input[name=type]').click(function (e) {
			if ($('input[name=type]:checked').val()=='1') {
				$('.condition').show();
			}else{
				$('.condition').hide();
			}
		})
		$('.checkbox').click(function (e) {
			if ($(this).attr('checked')) {
				$(this).siblings().val(1);
			}else{
				$(this).siblings().val(0);
			}
		})
		var _selected_goods = '{pigcms{$info["buy_goods_list_str"]}';
		function select_goods(){
			layer.open({
				type: 2,
				area: ['700px', '730px'],
				fixed: false,
				offset:'100px',
				maxmin: true,
				content: '{pigcms{:U("select_goods")}'+'&selected='+_selected_goods,
				btn: ['确定', '取消'],
				yes: function(index, layero){
					var frameId = $(layero).find("iframe").attr('id');
					var selected = $.trim($(window.frames[frameId].document).find('[name="selected"]').val());
					_selected_goods = selected;
					$("#goods_spec tbody").empty();
					$.post("{pigcms{:U('ajaxGetGoods')}",{selected:selected},function(data){
						if(data.length > 0){
							for(var i = 0; i< data.length; i++){
								$("#goods_spec tbody").append('<tr>'+
											'<input type="hidden" name="relation_goods_id[]" value="'+data[i].goods_id+'" />'+
											'<input type="hidden" name="relation_type[]" value="'+data[i].type+'" />'+
											'<td>'+data[i].goods_id+'</td>'+
											'<td>'+data[i].model+'</td>'+
											'<td>'+data[i].name+'</td>'+
											'<td>'+data[i].price+'</td>'+
											'<td>'+
													'<button type="button" onClick="delThis(this)">{pigcms{:L_("移除")}</button>'+
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
			if(confirm('{pigcms{:L_("你确定要移除此商品吗？")}')){
				var goods_id = $(t).parents("tr").find('[name="relation_goods_id[]"]').val();
				var type = $(t).parents("tr").find('[name="relation_type[]"]').val();
				var _selected_goods_arr = _selected_goods.split(',');
				var _selected_goods_arr_new = [];
				for(var i = 0;i< _selected_goods_arr.length;i++){
					if(_selected_goods_arr[i] != type+'-'+goods_id){
						_selected_goods_arr_new.push(_selected_goods_arr[i]);
					}
				}
				_selected_goods = _selected_goods_arr_new.join(",");
				$(t).parents("tr").eq(0).remove();
			}
		}
	</script>
<include file="Public:footer"/>
