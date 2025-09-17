<include file="Public:header"/>
<style>
    .a {color: red}
</style>
<include file="nav"/>
<div class="mainbox">
	<form name="myform" id="myform" action="" method="post">
		<!--
		<div style="margin: 5px;height: 30px;line-height: 30px;">
			<input type="submit" style="margin-right:20px;" value="新增" class="button" onclick="window.top.artiframe('{pigcms{:U('mobileAppAdd')}','新增',680,500,true,false,false,editbtn,'add',true);"/>
		</div>
		-->
		<div class="table-list">
			<table width="100%" cellspacing="0">
				<colgroup>
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
						<th>名称</th>
						<th>类型</th>
						<th>Android包名</th>
						<th>IOS包名</th>
						<th>添加时间</th>
						<th>更新时间</th>
						<th class="textcenter">操作</th>
					</tr>
				</thead>
				<tbody>
					<if condition="is_array($list) && !empty($list)">
						<volist name="list" id="vo">
							<tr>
								<td>{pigcms{$vo.pack_id}</td>
								<td>{pigcms{$vo.name}</td>
								<td>{pigcms{$vo.type_name}</td>
								<td>{pigcms{$vo.package_name}</td>
								<td>{pigcms{$vo.package_name2}</td>
								<td>{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</td>
								<td>
									<if condition="$vo['update_time'] gt 0">
										{pigcms{$vo.update_time|date="Y-m-d H:i:s",###}
										<else/>
										-
									</if>
								</td>
								<td class="textcenter">
									<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('mobileAppAdd',[\'pack_id\'=>$vo[\'pack_id\']])}','查看/编辑',680,500,true,false,false,editbtn,'add',true);">查看/编辑</a>
									&nbsp;&nbsp;|&nbsp;&nbsp;
									<a href="{pigcms{:U('mobileApp',array('pack_id'=>$vo['pack_id']))}" style="color: {pigcms{$vo['tip_color']}">详细信息</a>
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