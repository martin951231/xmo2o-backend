<include file="Public:header"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <colgroup>
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
				<th>用户姓名</th>
				<th>充值金额</th>
				<th>操作人员</th>
				<th>描述</th>
				<th>充值时间</th>
            </tr>
            </thead>
            <tbody>
            <if condition="is_array($log_list)">
                <volist name="log_list" id="vo">
                    <tr>
                            <td>{pigcms{$vo.id}</td>
                            <td>{pigcms{$vo.card_name}</td>
                            <td>{pigcms{$vo.card_id}</td>
                            <td>{pigcms{$vo.user_name}</td>
                            <td>{pigcms{$vo.money}</td>
                            <td>{pigcms{$vo.admin_name}</td>
                            <td>{pigcms{$vo.describe}</td>
                            <td>{pigcms{$vo.create_time|date='Y-m-d H:i:s',###}</td>
                        </tr>
                </volist>
                <tr><td class="textcenter pagebar" colspan="6">{pigcms{$pagebar}</td></tr>
                <else/>
                <tr><td class="textcenter red" colspan="7">列表为空！</td></tr>
            </if>
            </tbody>

		</table>
<include file="Public:footer"/>
