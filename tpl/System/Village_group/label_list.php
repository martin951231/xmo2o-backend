<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Village_group/label_list')}" class="on">标签列表</a>
				
				</ul>
			</div>
			<input type="button" value="添加拼团标签" class="button" onclick="window.top.artiframe('{pigcms{:U('add_label')}','添加拼团商品','500px','450px',true,false,false,addbtn,'add',true);" style="margin-bottom:10px;"/>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
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
								
								<th>ID</th>
								<th>名称</th>
								<th>类型</th>
								<th>颜色、图片</th>
								<th>状态</th>
								<th class="textcenter">操作</th>
							
						
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($label_list)">
								<volist name="label_list" id="vo">
									<tr>
										
										<td >{pigcms{$vo.id}</td>
										<td >{pigcms{$vo.name}</td>
										<td>
                                            <if condition="$vo.type eq 1">图片<else />文字</if>
                                        </td>
										<td>
											<if condition="$vo.type eq 1"><img src="{pigcms{:file_domain()}{pigcms{$vo.image}" style="width:30px;height:30px;"><else />
											
											<p style="background:linear-gradient(to bottom, {pigcms{$vo.color} , {pigcms{$vo.color2});width:30px;height:30px;float:left;margin-right:10px;color:#fff;text-align:center;line-height:30px;" >{pigcms{$vo.name}</p>
											<p></p></if>
										</td>
										<td><if condition="$vo.status eq 1"><font color="green">正常</font><else /><font color="red">禁止</font></if></td>
										<td class="textcenter">
											<a href="javascript:void(0)"
											onclick="window.top.artiframe('{pigcms{:U('Village_group/add_label',array('id'=>$vo['id']))}','编辑标签',550,450,true,false,false,editbtn,'add',true);">编辑</a> | <a class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Village_group/del_label')}">删除</a>
										</td>
									</tr>
									
								</volist>
								<tr><td class="textcenter pagebar" colspan="6">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="6">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>


<include file="Public:footer"/>