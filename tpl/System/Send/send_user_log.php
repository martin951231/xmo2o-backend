<include file="Public:header"/>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup><col> <col> <col><col>  <col width="180" align="center"> </colgroup>
						<thead>
							<tr>
								<th>用户ID</th>
								<th>用户昵称</th>
								<th>用户头像</th>
								<th>发送状态</th>
                                <th>是否模板消息</th>
								<th>发送时间</th>
							</tr>
						</thead>
						<tbody>
							<if condition="$list">
								<volist name="list" id="vo">
									<tr>
										<td>{pigcms{$vo.uid}</td>
										<td>
                                            {pigcms{$vo.nickname}
                                            <if condition="$vo['u_status'] eq 0">
                                                <font color='red'>【用户已禁用】</font>
                                            <elseif condition="$vo['u_status'] eq 4" />
                                                <font color='red'>【用户已注销】</font>
                                            </if>
                                        </td>
                                        <td><img src="{pigcms{$vo.avatar|replace_file_domain=###}" style="width: 45px;margin:5px"></td>
                                        <td>
                                            <if condition="$vo['status'] eq 1">
                                                <font color='red'>成功</font> 
                                            <elseif condition="$vo['status'] eq 2" />
                                                <font color='red'>失败</font> 
                                            <elseif condition="$vo['status'] eq 0" />
                                                <font color='red'>发送中</font> 
                                            </if>
                                        </td>
                                        <td>
                                            <if condition="$vo['is_template'] eq 1">
                                                <font color='red'>是 </font> 
                                            <else/>
                                                否
                                            </if>
                                        </td>
                                        <td>{pigcms{$vo.sendtime|date='Y-m-d H:i:s',###}</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="9">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="5">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
		
		<style>
		.send_type{
			
		    color: #fff;
			padding: .2em .4em .3em;
			font-size: 11px;
			line-height: 1;
			height: 18px;
			background-color: #f89406;
			border-radius: 0;
		}
		</style>
<include file="Public:footer"/>