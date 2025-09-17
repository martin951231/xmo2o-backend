<include file="Public:header"/>
		<div class="mainbox">
            <include file="card_manage_header"/>
			<table class="search_table" width="100%">
				<tr>
					<td>
                        <form action="{pigcms{:U('InternalCard/operation_log')}" method="get">
                            <input type="hidden" name="c" value="InternalCard"/>
                            <input type="hidden" name="a" value="operation_log"/>
							区域：
							<div id="choose_pca" street_id="-1" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                            卡ID: <input type="text" name="card_id" class="input-text" value="{pigcms{$_GET['card_id']}"/>
                            卡名称: <input type="text" name="card_name" class="input-text" value="{pigcms{$_GET['card_name']}"/>
                            姓名: <input type="text" name="user_name" class="input-text" value="{pigcms{$_GET['user_name']}"/>
                            手机号: <input type="text" name="phone" class="input-text" value="{pigcms{$_GET['phone']}"/>
							操作员:
                            <select name="system_id">
                                <option value="" <if condition="$_GET['system_id'] eq ' '">selected="selected"</if>>全部</option>
								<volist name='admins' id='a'>
									<option value="{pigcms{$a.id}" <if condition="$_GET['system_id'] eq $a['id']">selected="selected"</if>>{pigcms{$a.realname}</option>
								</volist>
                            </select>
                            <font color="#000">操作时间范围：</font>
                            <input type="text" class="input-text" name="start_active_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_active_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            <input type="text" class="input-text" name="end_active_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_active_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>

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
							<col  align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
								<th>一卡通名称</th>
								<th>一卡通ID</th>
								<th>区域</th>
								<th>用户姓名</th>
								<th>系统管理员</th>
								<th>描述</th>
                                <th>添加时间</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($log_list)">
								<volist name="log_list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.card_name}</td>
										<td>{pigcms{$vo.card_id}</td>
										<td>{pigcms{$vo.region_str}</td>
										<td>{pigcms{$vo.user_name}</td>
										<td>{pigcms{$vo.admin_name}</td>
										<td>{pigcms{$vo.describe}</td>
                                        <td>{pigcms{$vo.create_time|date='Y-m-d H:i:s',###}</td>
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
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>