<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Live/shop_goods')}" class="on">外卖</a>
				<a href="{pigcms{:U('Live/group_goods')}">团购</a>
				<a href="{pigcms{:U('Live/mall3_goods')}">商城</a>
			</ul>
		</div>
		<table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('Live/shop_goods')}" method="get">
						<input type="hidden" name="c" value="Live"/>
						<input type="hidden" name="a" value="shop_goods"/>
						
						<!-- 搜索类型 -->
						<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder='请输入商品名称'/>
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
						<col width="180" align="center"/>
					</colgroup>
					<thead>
						<tr>
							<th>门店</th>
							<th>商品名称</th>
							<th>价格</th>
							<th>返佣比例</th>
							<th>添加时间</th>
						</tr>
					</thead>
					<tbody>
						<if condition="$goods_list">
							<volist name="goods_list" id="vo">
                                <tr>
                                	<td>{pigcms{$vo.store_name}</td>
                                    <td>{pigcms{$vo.name}</td>
									<td>{pigcms{$vo.price|floatval}</td>
									<td>{pigcms{$vo.percent_txt}</td>
									<td>{pigcms{$vo.create_time}</td>
								</tr>
							</volist>
							<tr><td class="textcenter pagebar" colspan="15">{pigcms{$pagebar}</td></tr>
						<else/>
							<tr><td class="textcenter red" colspan="15">列表为空！</td></tr>
						</if>
					</tbody>
				</table>
			</div>
		</form>
	</div>
<script>
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<include file="Public:footer"/>