<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Sms/index')}" class="on">短信发送记录</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Sms/index')}" method="get">
							<input type="hidden" name="c" value="Sms"/>
							<input type="hidden" name="a" value="index"/>
							手机号: 
							<input type="text" name="phone" class="input-text" value="{pigcms{$_GET['phone']}"/>
							发送时间：
							<input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							
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
								<th>商家名称</th>
								<th>发送到手机</th>
								<th>发送类型</th>
								<th>发送时间</th>
								<th style="width: 20%;">发送内容</th>
								<th>订单类型</th>
								<th>发送状态</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($record_list)">
								<volist name="record_list" id="vo">
									<tr>
										<td>{pigcms{$vo.pigcms_id}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.phone}</td>
										<td><if condition="$vo['sendto'] eq 'user'">顾客<else />商家</if></td>
										<td>{pigcms{$vo.time|date="Y-m-d H:i:s",###}</td>
										<td>{pigcms{$vo.text}</td>
										<td><if condition="$vo['type'] eq 'food'">订餐<elseif condition="$vo['type'] eq 'takeout'" />外卖<elseif condition="$vo['type'] eq 'group'" />{pigcms{$config.group_alias_name}<elseif condition="$vo['type'] eq 'shop'" />{pigcms{$config.shop_alias_name}</if></td>
										<td><if condition="isset($status[$vo['status']])">{pigcms{$status[$vo['status']]}<else/>{pigcms{$vo.status}</if></td>
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