<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('User/index')}">SYSTEM_USER_PRIVILEGED_RULE_1</a>
					<a href="{pigcms{:U('User/label_index')}">SYSTEM_USER_PRIVILEGED_RULE_2</a>
					<a href="{pigcms{:U('User/privileged_user')}" class="on">SYSTEM_USER_PRIVILEGED_RULE_3</a>
					<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('User/privileged_import')}','SYSTEM_USER_PRIVILEGED_RULE_4',600,600,true,false,false,closebtn,'lang_add',true);">SYSTEM_USER_PRIVILEGED_RULE_4</a>
					<a href="{pigcms{:U('User/set_privileged_rule')}">设置特权派发规则</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('User/privileged_user')}" method="get">
							<input type="hidden" name="c" value="User"/>
							<input type="hidden" name="a" value="privileged_user"/>
							<if condition="$admin_area neq 3 && $config['many_city']">
								<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" style="display:inline"></div>
							</if>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="uid" <if condition="$_GET['searchtype'] eq 'uid'">selected="selected"</if>>用户ID</option>
								<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>昵称</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>手机号</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type="submit" style="margin-right:20px;" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
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
							<col/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
								<th>昵称</th>
								<th>手机号</th>
								<if condition="$config['many_city']">
									<th>所在城市</th>
								</if>
								<th class="textcenter">特权类型</th>
								<th class="textcenter">开始发送<if condition="file_exists('conf/is_hake.no_delete')">hark<else />SYSTEM_USER_PRIVILEGED_1</if>特权</th>
								<th class="textcenter">结束发送<if condition="file_exists('conf/is_hake.no_delete')">hark<else />SYSTEM_USER_PRIVILEGED_1</if>特权</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($user_list)">
								<volist name="user_list" id="vo">
									<tr>
										<td>{pigcms{$vo.uid}</td>
										<td>{pigcms{$vo.nickname}</td>
										<td>{pigcms{$vo.phone}</td>
										<if condition="$config['many_city']">
											<td>{pigcms{$vo.city_name|default='<font color="#bbb">未知</font>'}</td>
										</if>
										<td class="textcenter">{pigcms{$vo.type}</td>
										<td class="textcenter">{pigcms{$vo.promotion_from}</td>
										<td class="textcenter">{pigcms{$vo.promotion_to}</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="7">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="7">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>