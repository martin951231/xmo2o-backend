<include file="Public:header"/>
	<div class="mainbox">
		<p>为了保证网页打开速度，同一分类最多只能添加50个商城商品。</p>
		<form class="frame_form" action="#" method="get">
			<input type="hidden" name="c" value="Special"/>
			<input type="hidden" name="a" value="choice_mall_goods"/>
			<input type="text" placeholder="请输入名称搜索词" value="{pigcms{$_GET.search}" class="input input-image" name="search"/>
			<button class="btnGrayS" style="height: 29px;">搜索</button>
		</form>
		<form name="myform" id="myform" action="" method="post">
			<div class="table-list">
				<table width="100%" cellspacing="0">
					<colgroup>
						<col/>
						<col/>
						<col/>
						<col width="180" align="center"/>
					</colgroup>
					<thead>
						<tr>
							<th>商品名称</th>
							<th>销量</th>
							<th>价格</th>
							<th class="textcenter">操作</th>
						</tr>
					</thead>
					<tbody>
						<if condition="$list">
							<volist name="list" id="vo">
								<tr>
									<td>{pigcms{$vo.name}</td>
									<td>{pigcms{$vo.sell_count}</td>
									<td>
										{pigcms{$config['Currency_symbol']}{pigcms{$vo.price}
									</td>
									<td class="textcenter"><input type="button" class="button select_row product-{pigcms{$vo.goods_id}" data-id="{pigcms{$vo.goods_id}" data-name="{pigcms{$vo.name}" data-price="{pigcms{$vo.price|floatval}" data-image="{pigcms{$vo.image}" value="选中"/></td>
								</tr>
							</volist>
							<tr><td class="textcenter pagebar" colspan="30">{pigcms{$pagebar}</td></tr>
						<else/>
							<tr><td class="textcenter red" colspan="10">商城商品列表为空</td></tr>
						</if>
					</tbody>
				</table>
			</div>
		</form>
	</div>
	<script type="text/javascript">
		$(function(){
			var topIframe = window.top.getTopIframe()
			var product = topIframe.get_selected_product();
			for(var i in product){
				$('.product-'+product[i]).val('已选择').prop('disabled',true);
			}
			$('.select_row').click(function(){
				$(this).val('已选择').prop('disabled',true);
				var obj=$(this);
				topIframe.select_product(obj.data('id'),obj.data('name'),obj.data('image'),obj.data('price'));
			});
		});
	</script>
<include file="Public:footer"/>