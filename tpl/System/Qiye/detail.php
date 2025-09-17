<include file="Public:header"/>
	<style>
		.sub_mch{
			display:none
		}
	</style>
	<form id="myform" method="post" action="{pigcms{:U('Qiye/handle')}" frame="true" refresh="true">
        <input type="hidden" name="id" value="{pigcms{$info.id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="160">企业名称</th>
				<td>{pigcms{$info.qiye_name}</td>
			</tr>
			<tr>
				<th width="160">员工名称</th>
				<td>{pigcms{$info.staff_name}</td>
			</tr>
			<tr>
				<th width="160">操作</th>
				<td><if condition="$info['operate'] EQ 1">预存<else/>扣减</if></td>
			</tr>

            <tr>
                <th width="160">金额</th>
                <td>{pigcms{$info.money|floatval}</td>
            </tr>

            <tr>
                <th width="160">备注</th>
                <td>{pigcms{$info.remark|default='无'}</td>
            </tr>

            <tr>
                <th width="160">申请时间</th>
                <td>{pigcms{$info.time|date='Y-m-d H:i:s',###}</td>
            </tr>

            <tr>
                <th width="160">IP</th>
                <td>{pigcms{$info.ip}</td>
            </tr>

            <tr>
                <th width="160">状态</th>
                <td>
                    <if condition="$info['status'] EQ 0">
                        待审核
                    <elseif condition="$info['status'] EQ 1"/>
                        通过
                    <else/>
                        驳回
                    </if>
                </td>
            </tr>

            <if condition="$info['status'] NEQ 0">
                <tr>
                    <th width="160">审核备注</th>
                    <td>{pigcms{$info.handle_remark}</td>
                </tr>

                <tr>
                    <th width="160">审核时间</th>
                    <td>{pigcms{$info.handle_time|date='Y-m-d H:i:s',###}</td>
                </tr>

                <tr>
                    <th width="160">审核人</th>
                    <td>{pigcms{$info.admin_account}</td>
                </tr>
            </if>
		</table>
	</form>
	<script>

	</script>
	
<include file="Public:footer"/>