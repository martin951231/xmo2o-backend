<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('InternalCard/user_card_list')}" frame="true" refresh="true">
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
                <th>一卡通名称</th>
                <th>一卡通号</th>
                <th>可用余额</th>
                <th>开卡时间</th>
            </tr>
            </thead>
            <tbody>
            <if condition="is_array($card_list)">
                <volist name="card_list" id="vo">
                    <tr>
                        <td>{pigcms{$vo.card_name}</td>
                        <td>{pigcms{$vo.card_id}</td>
                        <td>{pigcms{$vo.balance_money}</td>
                        <td>{pigcms{$vo.active_time|date='Y-m-d H:i:s',###}</td>
                    </tr>
                </volist>
                <tr><td class="textcenter pagebar" colspan="6">{pigcms{$pagebar}</td></tr>
                <else/>
                <tr><td class="textcenter red" colspan="7">列表为空！</td></tr>
            </if>
            </tbody>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>