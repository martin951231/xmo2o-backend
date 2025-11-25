<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					
				
					<a href="{pigcms{:U('Village_group/index')}" class="on">社区团购列表</a> |
				
				
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<input type="button" value="添加拼团商品" class="button" onclick="window.o2oTop.artiframe('{pigcms{:U('add_group_product')}&group_id={pigcms{$_GET['group_id']}','添加拼团商品',800,480,true,false,false,addbtn,'add',true);"/>　　
					</td>
			
				
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					.content{width:80px;overflow: hidden;
text-overflow:ellipsis;
white-space: nowrap;}
					</style>
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="250" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>编号</th>
								<th>原始团购编号</th>
								<th>名称</th>
								<th>描述</th>
								<th>图片</th>
								<th>数量</th>
								<th>进价</th>
								<th>售价</th>
								<th>销量</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($group_list)">
								<volist name="group_list" id="vo">
									<tr>
										<td>{pigcms{$vo.goods_id}</td>
										<td>{pigcms{$vo.mer_group_id}</td>
										<td>{pigcms{$vo.name}</td>
										<td class="content">{pigcms{$vo.des|mb_substr=###,0,20,'utf-8'}</td>
										<td><img src="{pigcms{$vo.image}" style="width:120px;height:80px;"></td>
										<td>{pigcms{$vo.stock_num|floatval}</td>
										<td>{pigcms{$vo.purchase_price|floatval}</td>
										<td>{pigcms{$vo.price|floatval}</td>
										<td>{pigcms{$vo.sell_count|floatval}</td>
										
									
										<td class="textcenter">
										
										<a href="javascript:void(0)" onclick="window.o2oTop.artiframe('{pigcms{:U('Village_group/add_group_product',array('goods_id'=>$vo['goods_id'],'group_id'=>$_GET['group_id']))}','编辑',800,480,true,false,false,editbtn,'edit',true);">编辑</a>  | <a href="{pigcms{:U('Village_group/del_group',array('group_id'=>$vo['group_id']))}">删除</a>
										
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="11">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="11">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>