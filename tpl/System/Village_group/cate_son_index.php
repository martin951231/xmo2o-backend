<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Village_group/cate_index')}">主分类列表</a>
					<a href="{pigcms{:U('Village_group/cate_son_index')}" class="on">子分类列表</a>
				</ul>
			</div>
			
			<table class="search_table" width="100%">
				<tr>
					<td>
						<input type="button" value="添加商品子分类" class="button" onclick="window.top.artiframe('{pigcms{:U('add_son_category',array('cat_fid'=>$_GET['cat_fid']))}','添加商品子分类',800,480,true,false,false,addbtn,'add',true);"/>
						
						
					</td>
					<td>
						<form action="{pigcms{:U('Village_group/index')}" method="get">
							<input type="hidden" name="c" value="Village_group"/>
							<input type="hidden" name="a" value="index"/>
							<input type="hidden" name="searchtype" value="name"/>
						
							按商品名搜索: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						
							
							<input type="submit" value="查询" class="button"/>
						
						</form>
					</td>
			
				
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					.content{width:80px;overflow: hidden;text-overflow:ellipsis;white-space: nowrap;}
					</style>
					<table width="100%" cellspacing="0">
						<colgroup>
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
								<th>分类名称</th>
								<th>商品信息</th>
								<th>状态</th>
								<th>商品数量</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($category)">
								<volist name="category" id="vo">
									<tr>
										<td>{pigcms{$vo.cat_id}</td>
										<td>{pigcms{$vo.cat_name}</td>
										<td>
											<a href="{pigcms{:U('Village_group/index',array('cat_fid'=>$_GET['cat_fid'],'cat_id'=>$vo['cat_id']))}">查看商品信息</a>
										</td>
										<td><if condition="$vo.status eq 1"><font color="green">正常</font><else /><font color="red">禁止</font></if></td>
										<td>{pigcms{$vo.goods_count}</td>
										<td class="textcenter">
											<a href="javascript:void(0)" onclick="window.top.artiframe('{pigcms{:U('Village_group/add_son_category',array('cat_id'=>$vo['cat_id']))}','编辑',800,480,true,false,false,editbtn,'edit',true);">编辑</a>  | <a class="delete_row" parameter="cat_id={pigcms{$vo.cat_id}" url="{pigcms{:U('Village_group/del_category')}">删除</a>
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