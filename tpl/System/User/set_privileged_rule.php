<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('User/index')}">用户列表</a>
					<a href="{pigcms{:U('User/label_index')}">用户标签管理</a>
					<a href="{pigcms{:U('User/privileged_user')}">特权用户</a>
					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/privileged_import')}','导入特权用户',600,600,true,false,false,closebtn,'lang_add',true);">导入特权用户</a>
					<a href="{pigcms{:U('User/set_privileged_rule')}" class="on">设置特权派发规则</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('User/set_privileged_rule')}" method="get">
							<input type="hidden" name="c" value="User"/>
							<input type="hidden" name="a" value="set_privileged_rule"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<input type="submit" style="margin-right:20px;" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<span style="color:red;">需要先增加“特权优惠券”，再编辑派发规则</span>
			<br><br>
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
								<th>城市</th>
								<th>派发时间</th>
								<th>派发券</th>
							</tr>
						</thead>
						<tbody>
							<volist name="city_data" id="city">
								<tr>
									<td>{pigcms{$city.area_name}<input type="hidden" name="cityid[{pigcms{$city.area_id}]" value="{pigcms{$city.area_id}" /></td>
									<td>
										<select name="city_time[{pigcms{$city.area_id}]">
											<volist name="timearr" id="t">
											<option value="{pigcms{$t.val}" <if condition="$t['val'] == $city['timeint']">selected="selected"</if>>{pigcms{$t.text}</option>
											</volist>
										</select>
									</td>
									<td>
										<notempty name="city.coupon_arr">
										<volist name="city.coupon_arr" id="c">
											&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="checkbox" name="city_coupon[{pigcms{$city.area_id}][]" value="{pigcms{$c.coupon_id}" {pigcms{$c.checked} />{pigcms{$c.name}</label>
										</volist>
										<else />
										该城市没有可派发的优惠券
										</notempty>
									</td>
								</tr>
							</volist>
							<tr>
								<td colspan="3"><button class="button" onclick="myform.submit();return false;">保存派发规则</button></td>
							</tr>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>