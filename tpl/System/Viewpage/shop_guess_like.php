<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('shop_guess_like_post')}" frame="true" refresh="true" class="frame_form ">
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab8">
			<tr>
				<th width="80">是否展示</th>
				<td class="radio_box">
					<span class="cb-enable">
						<label class="cb-enable <if condition="$config['shop_index_find_good_goods_show'] eq 1">selected</if>">
							<span>展示</span>
							<input id="yes" type="radio" name="shop_index_find_good_goods_show" value="1" <if condition="$config['shop_index_find_good_goods_show'] eq 1">checked="checked"</if>/>
						</label>
					</span>
					<span class="cb-disable">
						<label class="cb-disable <if condition="$config['shop_index_find_good_goods_show'] eq 0">selected</if>">
							<span>不展示</span>
							<input id="no" type="radio" name="shop_index_find_good_goods_show" value="0" <if condition="$config['shop_index_find_good_goods_show'] eq 0">checked="checked"</if>/>
						</label>
					</span>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="外卖首页是否显示发现好菜。" style="margin-top:1px;">
				</td>
			</tr>
			<tr>
				<th width="80">主标题</th>
				<td class="radio_box">
					<input type="text" class="input fl" name="shop_index_find_good_goods_title" size="20" placeholder="请输入主标题" validate="maxlength:20,required:true" value="{pigcms{$config.shop_index_find_good_goods_title}" />
				</td>
			</tr>
			<tr>
				<th width="80">管理分类/商品</th>
				<td>
					<!--<a class="button" href="{pigcms{:U('Diy_category/index',['type'=>2])}" >管理分类/商品</a>-->
					<a class="button" class="iframe_close" href="javascript:;" onclick="test();">管理分类/商品</a>
					<!--<input type="button" value="添加商品" class="button" onclick="select_goods()" style="margin-left:0px;margin-right:10px;margin-bottom:10px;">-->
					<a></a>
					<!--
					<table style="width:800px;" class="table_form" id='goods_spec'>
						<thead>
						<tr >
							<th style="text-align: left;">ID</th>
							<th style="text-align: left;">商品名称</th>
							<th style="text-align: left;">店铺名称</th>
							<th style="text-align: left;">商品价格</th>
							<th style="text-align: left;">操作</th>
						</tr>
						</thead>
						<tbody>
						<volist name="goods_list" id="vo">
							<tr>
								<input type="hidden" name="relation_goods_id[]" value="{pigcms{$vo.goods_id}" />
								<td>{pigcms{$vo.goods_id}</td>
								<td>{pigcms{$vo.name}</td>
								<td>{pigcms{$vo.store_name}</td>
								<td>{pigcms{$vo.price}</td>
								<td>{pigcms{$vo.store_name}/{pigcms{$vo.mer_name}</td>
								<td>
									<button type="button" onClick="delGoods(this,{pigcms{$vo.id})">移除</button>
								</td>
							</tr>
						</volist>
						</tbody>
					</table>-->
				</td>
			</tr>
		</table>		
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>

	<script>
		function test(){
                parent.addIframe('外卖发现好菜-自定义分类','diy_category_2','{pigcms{:U('Diy_category/index',['type'=>2])}');
                window.o2oTop.closeiframe();
        }
		var _selected_goods = '{pigcms{$goods_ids}';
		function select_goods(){
			layer.open({
				type: 2,
				area: ['90%', '90%'],
				fixed: false,
				offset:'10px',
				maxmin: true,
				content: '{pigcms{:U("select_goods")}'+'&selected='+_selected_goods,
				btn: ['确定', '取消'],
				yes: function(index, layero){
					var frameId = $(layero).find("iframe").attr('id');
					var selected = $.trim($(window.frames[frameId].document).find('[name="selected"]').val());
					_selected_goods = selected;
					$("#goods_spec tbody").empty();

					// 渲染商品
					ajaxGetGoods(selected);

				    layer.close(index);
				},
				btn2: function(index){
					layer.close(index);
				}
			});
		}

		// 渲染商品
		function ajaxGetGoods(selected){
			$.post("{pigcms{:U('ajaxGetGoods')}",{selected:selected},function(data){
				if(data.length > 0){
					for(var i = 0; i< data.length; i++){
						$("#goods_spec tbody").append('<tr>'+
							'<input type="hidden" name="relation_goods_id[]" value="'+data[i].goods_id+'" />'+
							'<td>'+data[i].goods_id+'</td>'+
							// '<td>'+data[i].model+'</td>'+
							'<td>'+data[i].name+'</td>'+
							'<td>'+data[i].store_name+'</td>'+
							'<td>'+data[i].price+'</td>'+
							'<td>'+
									'<button type="button" onClick="delGoods(this)">{pigcms{:L_("移除")}</button>'+
							'</td>'+
						'</tr>');
					}
				}
			},'json');
		}

		// 初始化加载商品列表
		//ajaxGetGoods(_selected_goods);

		// 移除商品
		function delGoods(t){
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

		var click = true;
		$('.cb-enable,.cb-disable').click(function(argument) {
			if (!click) {
				return false;
			}
			click = false
			// var shop_index_find_good_goods_show = $('input[name=shop_index_find_good_goods_show]:checked').val();
			var shop_index_find_good_goods_show = $(this).find('input').val();
			$.post('/admin.php?g=System&c=Config&a=amend',{shop_index_find_good_goods_show:shop_index_find_good_goods_show},function(res){
				if (res.status==1) {
					click = true
					layer.msg('切换成功')
				}
			})
		})
	</script>
<include file="Public:footer"/>