<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('User/index')}">用户列表</a>
					<a href="{pigcms{:U('User/user_money_verify')}" class="on">用户余额变动申请列表</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('User/user_money_verify')}" method="get">
							<input type="hidden" name="c" value="User"/>
							<input type="hidden" name="a" value="user_money_verify"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="uid" <if condition="$_GET['searchtype'] eq 'uid'">selected="selected"</if>>用户ID</option>
								<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>昵称</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>手机号</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<select name="status">
								<option value="">全部</option>
								<option value="1" <if condition="$_GET['status'] eq '1'">selected="selected"</if>>待审核</option>
								<option value="2" <if condition="$_GET['status'] eq '2'">selected="selected"</if>>审核通过</option>
								<option value="3" <if condition="$_GET['status'] eq '3'">selected="selected"</if>>审核失败</option>
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
								<th>用户编号</th>
								<th>用户昵称</th>
								<th>用户手机号</th>
								<th>修改金额</th>
								<th>修改备注</th>
								<th class="textcenter">操作管理员</th>
								<th class="textcenter">状态</th>
								<th>审核理由</th>
							</tr>
						</thead>
						<tbody>
							<if condition="!empty($data)">
								<volist name="data" id="vo">
									<tr>
										<td>{pigcms{$vo.uid}</td>
										<td>{pigcms{$vo.nickname}</td>
										<td>{pigcms{$vo.phone}</td>
										<td class="red"><if condition="$vo.type eq 1">增加<else />减少</if>{pigcms{$vo.money}</td>
										<td>{pigcms{$vo.remark}</td>
										<td class="textcenter">{pigcms{$vo.admin_name}</td>
										<if condition="$vo.status == 0">
											<td class="textcenter red" style="cursor: pointer;"  onclick="window.o2oTop.artiframe('{pigcms{:U('user_money_verify_handle',array('id'=>$vo['id']))}','审核信息',400,200,true,false,false,editbtn,'add',true);">待审核</td>
										<elseif condition="$vo.status == 1" />
											<td class="textcenter"><font color="green">审核成功</font></td>
										<else />
											<td class="textcenter">审核失败</td>
										</if>
										<td>{pigcms{$vo.reason}</td>
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