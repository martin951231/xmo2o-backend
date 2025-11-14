<include file="Public:header"/>
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('index')}" class="on">优惠时间段列表</a>|
			<a href="javascript:void(0);"  onclick="window.o2oTop.artiframe('{pigcms{:U('add')}','添加优惠时间段',800,560,true,false,false,editbtn,'edit',true);">添加优惠时间段</a>
			<a href="{pigcms{:U('area_set')}">配置设置</a>|
		</ul>
	</div>
	<form name="myform" id="myform" action="" method="post">
		<div class="table-list">
			<table width="100%" cellspacing="0">
				<thead>
					<tr>
						<th>ID</th>
						<th>优惠时间段名称</th>
						<th>优惠时间段</th>
						<th>优惠次数</th>
						<th>优惠金额</th>
						<th class="textcenter">操作</th>
					</tr>
				</thead>
				<tbody>
					<if condition="is_array($list)">
						<volist name="list" id="vo">
							<tr>
								<td>{pigcms{$vo.discount_id}</td>
								<td>{pigcms{$vo.title}</td>
								<td>{pigcms{$vo.start_time} - {pigcms{$vo.end_time}</td>
								<td>{pigcms{$vo.have_number}</td>
								<td>{pigcms{$vo.money}</td>
								<td class="textcenter">
									<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('edit',array('discount_id'=>$vo['discount_id']))}','编辑信息',800,560,true,false,false,editbtn,'edit',true);">编辑</a> | 
									<a href="javascript:void(0);" class="delete_row" parameter="discount_id={pigcms{$vo.discount_id}" url="{pigcms{:U('del')}">删除</a>
								</td>
							</tr>
						</volist>
						<tr><td class="textcenter" colspan="12">{pigcms{$pagebar}</td></tr>
					<else/>
						<tr><td class="textcenter red" colspan="12">暂无优惠时间段！</td></tr>
					</if>
				</tbody>
			</table>
		</div>
	</form>
</div>
<include file="Public:footer"/>