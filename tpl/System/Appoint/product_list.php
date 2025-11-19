<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Appoint/product_list')}" class="on">预约列表</a>
                    <a href="{pigcms{:U('Appoint/order_list')}">订单列表</a>
                </ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Appoint/product_list')}" method="get">
							<input type="hidden" name="c" value="Appoint"/>
							<input type="hidden" name="a" value="product_list"/>
							筛选: 
							<select name="searchtype">
								<option value="appoint_id" <if condition="$_GET['searchtype'] eq 'appoint_id'">selected="selected"</if>>服务编号</option>
								<option value="appoint_name" <if condition="$_GET['searchtype'] eq 'appoint_name'">selected="selected"</if>>服务名称</option>
								<option value="mer_name" <if condition="$_GET['searchtype'] eq 'mer_name'">selected="selected"</if>>商家名称</option>
							</select>
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							
							审核状态: 
							<select name="check_status">
								<option value="-1" <if condition="$_GET['check_status'] eq '-1'">selected="selected"</if>>全部</option>
								<option value="0" <if condition="$_GET['check_status'] eq 0">selected="selected"</if>>待审核</option>
								<option value="1" <if condition="$_GET['check_status'] eq 1">selected="selected"</if>>审核通过</option>
							</select>
							<input type="submit" value="查询" class="button"/>
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
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>编号</th>
								<th>服务名称</th>
								<th>价格</th>
								<th>预约数</th>
								<th>时间</th>
								<th>数字</th>
								<th>审核状态</th>
								<th>运行状态</th>
                                <th>预约状态</th>
                                <th>商家信息</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($appoint_list)">
								<volist name="appoint_list" id="vo">
									<tr>
										<td>{pigcms{$vo.appoint_id}</td>
										<td><a target="_blank" href="/appoint/{pigcms{$vo['appoint_id']}.html">{pigcms{$vo.appoint_name}</a></td>
										<td>
											定金：{pigcms{$config['Currency_symbol']} {pigcms{$vo.payment_money}<br/>
											全价：{pigcms{$config['Currency_symbol']} {pigcms{$vo.appoint_price}
										</td>
										<td>已预约：{pigcms{$vo.appoint_sum}</td>
										<td>
											开始时间：{pigcms{$vo.start_time|date="Y-m-d H:i:s",###}<br/>
											结束时间：{pigcms{$vo.end_time|date="Y-m-d H:i:s",###}<br/>
											添加时间：{pigcms{$vo.create_time|date="Y-m-d H:i:s",###}
										</td>
										<td>
											点击数：{pigcms{$vo.hits}<br/>
											预约数：{pigcms{$vo.appoint_sum}
										</td>
										<td>
											<if condition="$vo['check_status'] eq 0"><span style="color:red">待审核</span>
											<elseif condition="$vo['check_status'] eq 1" /><span style="color:green">通过</span>
											</if>
										</td>
										<td>
											<if condition="$vo['start_time'] gt $_SERVER['REQUEST_TIME']">
												未开始
											<elseif condition="$vo['end_time'] lt $_SERVER['REQUEST_TIME']"/>
												已结束
											<else/>
												进行中
											</if>
										</td>
										<td>
											<if condition="$vo['appoint_status'] eq 0"><span style="color:green">开启</span>
											<elseif condition="$vo['appoint_status'] eq 1" /><span style="color:red">关闭</span>
											</if>
										</td>
                                        <td>
                                            商家id： {pigcms{$vo['mer_id']}<br/>
                                            商家名称： {pigcms{$vo['mer_name']}<br/>
                                        </td>
										<td class="textcenter">
											<a href="{pigcms{:U('Appoint/order_list', array('appoint_id'=>$vo['appoint_id']))}" class="on">查看订单</a> |
									  		<a href="{pigcms{:U('Merchant/merchant_login',array('mer_id'=>$vo['mer_id'], 'appoint_id'=>$vo['appoint_id']))}">编辑</a>
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
<script>
    var appoint_edit_load = sessionStorage.getItem("appoint_edit_load");
    if (appoint_edit_load && appoint_edit_load==1) {
        sessionStorage.removeItem("appoint_edit_load");
        location.reload();
    }
</script>
<include file="Public:footer"/>