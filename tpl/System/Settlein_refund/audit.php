<include file="Public:header"/>
<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
                    <a href="{pigcms{:U('Settlein_refund/index')}">待审核记录</a>|
                    <a href="{pigcms{:U('Settlein_refund/audit')}" class="on">已审核记录</a>
                </ul>
			</div>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th>编号</th>
								<th>商家名称</th>
								<th>申请时间</th>
								<th>退款金额</th>
								<th>备注</th>   
								<th>退款状态</th>
								<th>理由</th>   
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>
                                        <td>{pigcms{$vo.order_id}</td>
                                        <td>{pigcms{$vo.mer_name}</td>
                                        <td>{pigcms{$vo.create_time|date="Y-m-d H:i:s",###}</td>
                                        <td>{pigcms{$vo.money}</td>
                                        <td>{pigcms{$vo.remark}</td>
                                        <td>
                                            <if condition="$vo.status eq 21 "><span style="color: #009800">已退款</span></if>
                                            <if condition="$vo.status eq 22 "><span style="color: #ff0000">已驳回</span></if>
                                        </td>
                                        <td>{pigcms{$vo.msg}</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="10">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="10">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
</div>
<include file="Public:footer"/>