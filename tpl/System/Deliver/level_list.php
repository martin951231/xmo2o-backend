<include file="Public:header"/>
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('Deliver/level_list')}" class="on">等级管理</a>|
			<a href="javascript:void(0);"  onclick="window.o2oTop.artiframe('{pigcms{:U('level_add')}','添加等级',800,560,true,false,false,editbtn,'edit',true);">添加等级</a>
			<a href="{pigcms{:U('Deliver/config_level')}">等级配置</a>|
			<a href="{pigcms{:U('Deliver/level_rule_list')}">等级规则配置</a>
		</ul>
	</div>
	<form name="myform" id="myform" action="" method="post">
		<div class="table-list">
			<table width="100%" cellspacing="0">
				<thead>
					<tr>
						<th>ID</th>
						<th>等级名称</th>
						<th>等级级别</th>
						<th>等级所需积分</th>
						<th>等级描述</th>
						<th>等级图片</th>
						<th>每日接单量</th>
						<th>添加时间</th>
						<th>最后修改时间</th>
						<th class="textcenter">操作</th>
					</tr>
				</thead>
				<tbody>
					<if condition="is_array($list)">
						<volist name="list" id="vo">
							<tr>
								<td>{pigcms{$vo.pigcms_id}</td>
								<td>{pigcms{$vo.name}</td>
								<td>{pigcms{$vo.level}</td>
								<td>{pigcms{$vo.score}</td>
								<td>{pigcms{$vo.desc}</td>
								<td><img src="{pigcms{:file_domain().'/'.$vo['image']}" style="width: 100px;"></td>
								<td>{pigcms{$vo.order_number}</td>
								<td>{pigcms{$vo.time|date='Y-m-d H:i:s',###}</td>
								<td>{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>
								
								<td class="textcenter">
									<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Deliver/level_edit',array('pigcms_id'=>$vo['pigcms_id']))}','编辑等级信息',800,560,true,false,false,editbtn,'edit',true);">编辑</a> | 
									<a href="javascript:void(0);" class="delete_row" parameter="pigcms_id={pigcms{$vo.pigcms_id}" url="{pigcms{:U('Deliver/level_del')}">删除</a>
								</td>
							</tr>
						</volist>
					<else/>
						<tr><td class="textcenter red" colspan="12">暂无等级规则！</td></tr>
					</if>
				</tbody>
			</table>
		</div>
	</form>
</div>
<include file="Public:footer"/>