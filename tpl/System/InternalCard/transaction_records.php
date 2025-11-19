<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('InternalCard/translation_records')}" frame="true" refresh="true">
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
                <th>时间</th>
                <th>卡名称</th>
                <th>卡号</th>
                <th>店铺</th>
                <th>业务类型</th>
                <th>详情</th>
                <th>交易金额</th>
            </tr>
            </thead>
            <tbody>
            <if condition="is_array($log_list)">
                <volist name="log_list" id="vo">
                    <tr>
                        <td>{pigcms{$vo.create_time}</td>
                        <td>{pigcms{$vo.card_name}</td>
                        <td>{pigcms{$vo.card_id}</td>
                        <td>{pigcms{$vo.store_name}</td>
                        <td>{pigcms{$vo.business_type}</td>
                        <td>{pigcms{$vo.describe}</td>
                        <td>{pigcms{$vo.money}</td>
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
