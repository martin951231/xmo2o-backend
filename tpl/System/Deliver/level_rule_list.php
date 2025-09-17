<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Deliver/level_list')}">等级管理</a>|
					<a href="javascript:void(0);"  onclick="window.top.artiframe('{pigcms{:U('level_add')}','添加等级',800,560,true,false,false,editbtn,'edit',true);">添加等级</a>
					<a href="{pigcms{:U('Deliver/config_level')}">等级配置</a>|
					<a href="{pigcms{:U('Deliver/level_rule')}" class="on">等级规则配置</a>
				</ul>
			</div>
			<div style="margin-bottom: 5px;"><a href="javascript:void(0);"  onclick="window.top.artiframe('{pigcms{:U('level_rule_add')}','添加等级规则',800,560,true,false,false,editbtn,'edit',true);">添加等级规则</a><span style="color: #3283FA;margin-left: 10px;">添加的信息用于展示在配送员手机客户端的个人中心页面，供配送员查看</span></div>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th>ID</th>
								<th>规则名称</th>
								<th>规则排序</th>
								<!-- <th width="40%">规则内容</th> -->
								<th>状态</th>
								<th>添加时间</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>
										<td>{pigcms{$vo.pigcms_id}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.sort}</td>
										<!-- <td>{pigcms{$vo.content}</td> -->
										<td><if condition="$vo.status eq 1">开启<else/>关闭</if></td>
										<td>{pigcms{$vo.time|date='Y-m-d H:i:s',###}</td>
										<td class="textcenter">
											<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Deliver/level_rule_edit',array('pigcms_id'=>$vo['pigcms_id']))}','编辑规则',800,560,true,false,false,editbtn,'edit',true);">编辑</a> | 
											<a href="javascript:void(0);" class="delete_row" parameter="pigcms_id={pigcms{$vo.pigcms_id}" url="{pigcms{:U('Deliver/level_rule_del')}">删除</a>
										</td>
									</tr>
								</volist>
							<else/>
								<tr><td class="textcenter red" colspan="12">暂无等级设置！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>