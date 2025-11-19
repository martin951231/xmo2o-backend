<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('index')}" class="on">自定义分类列表</a>|
					<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('add',['type'=>$type])}','创建分类',480,300,true,false,false,addbtn,'add',true);">创建分类</a>
					<!--<a href="{pigcms{:U('add',['type'=>$type])}">创建分类</a>|-->
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
							<if condition="$system_session['level'] eq 2 || $system_session['level'] eq 0">
								<col width="180" align="center"/>
							</if>
						</colgroup>
						<thead>
							<tr>
								<th>编号</th>
								<th>城市</th>
								<th>分类名称</th>
								<th>开始时间</th>
								<th>结束时间</th>
								<th>商品列表</th>
								<th>状态</th>
								<th class="textcenter">操作</th>	
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>
										<td>{pigcms{$vo.pigcms_id}</td>
										<td>{pigcms{$vo.city_name}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.begin_time|date='Y-m-d H:i',###}</td>
										<td>{pigcms{$vo.end_time|date='Y-m-d H:i',###}</td>
										<td>
											<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('category_goods',['category_id'=>$vo['pigcms_id']])}','商品详情',900,520,true,false,false,editbtn,'add',true);">商品管理（{pigcms{$vo.goods_count}）</a>
											<!--<a href="{pigcms{:U('Category_goods',['category_id'=>$vo['pigcms_id']])}">商品管理（{pigcms{$vo.goods_count}）</a>-->
										</td>
										<td>
											<if condition="$vo.status eq '1'">
												<font color="green">启用</font>
											<else/>
												<font color="red">关闭</font>
											</if>
										</td>
										<td class="textcenter">
											<!--<a href="{pigcms{:U('edit',array('id'=>$vo['pigcms_id']))}">编辑</a>--> 
											<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('edit',array('id'=>$vo['pigcms_id']))}','编辑活动分类',480,370,true,false,false,editbtn,'add',true);">编辑</a>| 
											<a href="javascript:void(0);" class="delete_row" parameter="pigcms_id={pigcms{$vo.pigcms_id}" url="{pigcms{:U('del')}">删除</a>
										</td>	
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="8">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="8">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>