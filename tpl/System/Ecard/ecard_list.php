<include file="Public:header"/>
		<div class="mainbox">
			<include file='ecard_header' />
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Ecard/ecard_list')}" method="get">
							<input type="hidden" name="c" value="Ecard"/>
							<input type="hidden" name="a" value="ecard_list"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="cardid" <if condition="$_GET['searchtype'] eq 'cardid'">selected="selected"</if>>卡ID</option>
								<option value="card_name" <if condition="$_GET['searchtype'] eq 'card_name'">selected="selected"</if>>卡名</option>
								<option value="card_price" <if condition="$_GET['searchtype'] eq 'card_price'">selected="selected"</if>>面值</option>
							</select>
							<input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
								<th>E卡名称</th>
								<th>封面图</th>
								<th>面值</th>
								<th>绑定商品</th>
								<th>当前商品库存/原始商品库存</th>
								<!-- <th class="textcenter">卡片数量</th> -->
								<th >状态</th>
								<th class="textcenter">编辑</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.card_name}</td>
										<td>
											<notempty name='vo.card_bg'>
												<img src="{pigcms{$vo.card_bg}" style="max-height:80px;padding:5px 0;" />
											</notempty>
										</td>
										<td>
											{pigcms{$config.Currency_symbol}{pigcms{$vo.card_price}
										</td>
										<td>
											<gt name='vo.bind_goods_id' value='0'>
												是({pigcms{$vo.bind_goods_id}/{pigcms{$vo.goods_name})
											<else/>
												否
											</gt>
										</td>
										<td>
											<span style='width:80px;display:inline-block'>
												<gt name="vo.make_num" value='0'>
													<egt name='vo.sales_num' value='$vo["card_num"]'>
														<span style='color:red;'>卡片不足</span>
													<else/>
														{pigcms{$vo['ecard_sku']-$vo['sales_num']}
													</egt>/{pigcms{$vo.ecard_sku}
												<else/>
													<span style='color:red;'>暂未制卡</span>
												</gt>	
											</span>
											<a href="{pigcms{:U('Ecard/ecard_coupon_list',array('card_id'=>$vo['id'],'make_status'=>0))}" class="button">管理</a>
										</td>
										<!-- <td  class="textcenter">{pigcms{$vo.card_num}</td> -->
										<td class="textcenter" data-id='{pigcms{$vo.id}'>
											<span class="cb-enable">
												<label class="cb-enable <eq name='vo.status' value='1'>selected</eq>">
													<span>开启</span>
													<input type="radio" name="status" value="1"  >
												</label>
											</span>
											<span class="cb-disable">
												<label class="cb-disable <eq name='vo.status' value='0'>selected</eq>">
													<span>关闭</span>
													<input type="radio" name="status" value="0" <eq name='vo.status' value='0'>checked="checked"</eq> >
												</label>
											</span>
										</td>
										<td class="textcenter">
											<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Ecard/ecard_edit',array('id'=>$vo['id']))}','编辑{pigcms{$config.ecard_alias_name}',580,320,true,false,false,editbtn,'edit',true);">编辑</a>|
											<a href="{pigcms{:U('Ecard/ecard_del',array('id'=>$vo['id']))}" class="del">删除</a>
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="20">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="20">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script type="text/javascript">
function tip(msg,e){
	layer.alert(msg,{offset:'250px'},function(index){
		layer.close(index);
		typeof e=='function' && e();
	});
}
</script>
<script type="text/javascript">
$(function(){
	/*更新卡券的状态*/
	var src='{pigcms{:U("Ecard/save_ecard_status")}';
	$('input[name="status"]').click(function(){
		var val=$(this).val();
		var id=$(this).parents('td').data('id');
		$.post(src,{id:id,status:val},function(res){
			console.log(res);
		});
	});
	$('.del').click(function(){
		var href=$(this).attr('href');
		layer.open({
			content:'确认删除！',
			btn:['确定','取消'],
			offset:'250px',
			yes:function(index){
				layer.close(index);
				$.get(href,function(res){
					tip(res.info);
					if(res.status){
						window.location.reload();
					}
				});
			}
		});
		return false;
	});
});
</script>
<include file="Public:footer"/>