<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('index')}">超值抢购活动列表</a>|
					<a href="{pigcms{:U('activity_list',array('group_id'=>$now_group['pigcms_id']))}" class="on">{pigcms{$now_group.name}</a>
					<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('activity_add',array('group_id'=>$now_group['pigcms_id']))}','添加商品',800,600,true,false,false,addbtn,'add',true);">添加商品</a>
				</ul>
			</div>
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
								<th>编号</th>
								<th>排序</th>
								<th>类别</th>
								<th>店铺名称</th>
								<th>名称</th>
								<th>简介</th>
								<th>价格</th>
								<th>点击次数</th>
								<th>状态</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($active_list)">
								<volist name="active_list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.sort}</td>
										<td>
											<switch name="vo['active_type']">
												<case value="group">{pigcms{$config.group_alias_name}</case>
												<case value="shop">{pigcms{$config.shop_alias_name}</case>
												<case value="mall">{pigcms{$config.mall_alias_name}</case>
                                                <case value="new_mall">{pigcms{$config.mall_alias_name_new}</case>
											</switch>
										</td>
										<td>{pigcms{$vo.store_name}</td>
										<td>
											<switch name="vo['active_type']">
												<case value="group">{pigcms{$vo.active_name}</case>
												<case value="shop">{pigcms{$vo.active_name}</case>
												<case value="mall">{pigcms{$vo.active_name}</case>
                                                <case value="new_mall">{pigcms{$vo.active_name}</case>
											</switch>

											<if condition="$vo['error_message']">
												<font color="red">({pigcms{$vo.error_message})</font>
											</if>
										</td>
										<td>{pigcms{$vo.active_desc}</td>
										<td>{pigcms{$vo.active_price}</td>
										<td>{pigcms{$vo.hits}</td>
										<td><if condition="$vo['status'] eq 1"><font color="green">正常</font><else/><font color="red">关闭</font></if></td>
										<td class="textcenter">
											<a href="javascript:window.o2oTop.artiframe('{pigcms{:U('activity_add',array('id'=>$vo['id']))}','编辑商品',800,600,true,false,false,editbtn,'edit',true);">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('activity_del')}">删除</a>
										</td>
									</tr>
								</volist>
							<else/>
								<tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>