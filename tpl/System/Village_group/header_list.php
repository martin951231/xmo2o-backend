<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Village_group/header_list')}" class="on">团长列表</a> 
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td width="100">
						<input type="button" value="添加团长" class="button" onclick="window.top.artiframe('{pigcms{:U('add_header')}','添加团长',580,400,true,false,false,addbtn,'add',true);"/>
					</td>
					<td>
						<form action="{pigcms{:U('Village_group/header_list')}" method="get">
							<input type="hidden" name="c" value="Village_group"/>
							<input type="hidden" name="a" value="header_list"/>
							<input type="hidden" name="mer_id" value="{pigcms{$_GET.mer_id}"/>
                            <if condition="$admin_area neq 3">
                                <div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                            </if>　　　
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>团长昵称</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>团长电话</option>
							</select>
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
								<th>团长ID</th>
								<th>团长名称</th>
								<th>联系电话</th>
								<th>绑定uid</th>
								<th>取货时间</th>
					
								<th>团长佣金</th>
								<th>销售额</th>
								<th>状态</th>
								<th>绑定社区名称</th>
								<th>自提地址</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($header)">
								<volist name="header" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.phone}</td>
										<td>{pigcms{$vo.uid}</td>
										<td>{pigcms{$vo.start_time|substr=0,-3}-{pigcms{$vo.end_time|substr=0,-3}</td>
							
										<td>{pigcms{$vo.header_money|floatval}</td>
										<td>{pigcms{$vo.sale_num|floatval}</td>
										<td>	
											<if condition="$vo['status'] eq 1">
												<if condition="$vo.pick_name neq ''">
													<font color="green">正常</font>
												<else />
													<font color="red">未绑定</font>
												</if>
											
											<elseif condition="$vo['status'] eq 3"/>
											
												<font color="red">待审核</font>
											<elseif condition="$vo['status'] eq 0"/>
												<font color="red">禁止</font>
											</if>
										</td>
										
										<if condition="empty($vo['pick_name'])">
										<!--td>{pigcms{$vo.home}</td>
										<td>{pigcms{$vo.address}</td-->	
										<td>-</td>
										<td>-</td>
										<else />
										<td>{pigcms{$vo.pick_name}</td>
										<td>{pigcms{$vo.pick_addr}</td>
										</if>
										<td class="textcenter"><a href="javascript:void(0)" onclick="window.top.artiframe('{pigcms{:U('Village_group/add_header',array('id'=>$vo['id']))}','编辑团长',580,400,true,false,false,editbtn,'edit',true);">编辑</a> | <a class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Village_group/del_leader')}">删除</a>
										
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
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<include file="Public:footer"/>