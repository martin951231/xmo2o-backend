<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Village_group/village_list')}" class="on">社区列表</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td width="100">
						<input type="button" value="添加社区" class="button" onclick="window.o2oTop.artiframe('{pigcms{:U('add_village')}','添加社区',550,450,true,false,false,addbtn,'store_add',true);"/>
					</td>
			
					<td>
						<form action="{pigcms{:U('Village_group/village_list')}" method="get">
							<input type="hidden" name="c" value="Village_group"/>
							<input type="hidden" name="a" value="village_list"/>
							
							
							<input type="hidden" name="mer_id" value="{pigcms{$_GET.mer_id}"/>
							筛选: 
<!--							<if condition="$admin_area neq 3">-->
<!--							<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>-->
<!--							</if>-->
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>社区名称</option>
								<option value="header_name" <if condition="$_GET['searchtype'] eq 'header_name'">selected="selected"</if>>团长名称</option>
								<option value="header_phone" <if condition="$_GET['searchtype'] eq 'header_phone'">selected="selected"</if>>团长电话</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							
							<input type="submit" value="查询" class="button"/>
							
						
						</form>
					</td>
				</tr>
			</table>
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
							<col/>
							<col/>
							<col/>
							<col width="250" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>编号</th>
								<th>社区名称</th>
								<th>社区地址</th>
								<th>团长ID</th>
								<th>团长名称</th>
								<th>团长电话</th>
								<th>团长发货单</th>
								<th>状态</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($address)">
								<volist name="address" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.pick_addr}</td>
								
										<td>{pigcms{$vo.header_uid}</td>
										<td>{pigcms{$vo.header_name}</td>
										<td>{pigcms{$vo.header_phone}</td>
										<td><a href="javascript:void(0)" onclick="window.o2oTop.artiframe('{pigcms{:U('leader_print')}&leader_id={pigcms{$vo['header_uid']}','打印预览','80%','80%',true,false,false,'','add',true);">打印发货单</a></td>
						
										<td><if condition="$vo['status'] eq 1"><font color="green">正常</font><elseif condition="$vo['status'] eq 0"/><font color="red">禁止</font></if></td>
										
										<td class="textcenter">
											<a href="javascript:void(0)"
											onclick="window.o2oTop.artiframe('{pigcms{:U('Village_group/add_village',array('id'=>$vo['id']))}','编辑社区',550,450,true,false,false,editbtn,'store_add',true);">编辑</a> | <a class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Village_group/del_village')}">删除</a>
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