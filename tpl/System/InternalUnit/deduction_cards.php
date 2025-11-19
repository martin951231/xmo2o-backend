<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('InternalCards/deductionCards')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
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
                <th>一卡通ID</th>
                <th>充值金额</th>
                <th>操作人员</th>
                <th>描述</th>
                <th>原金额</th>
                <th>现金额</th>
                <th>添加时间</th>
            </tr>
            </thead>
            <tbody>
            <if condition="is_array($data['list'])">
                <volist name="data.list" id="vo">
                    <tr>
                        <td>{pigcms{$vo.id}</td>
                        <td>{pigcms{$vo.card_id}</td>
                        <td>{pigcms{$vo.money}</td>
                        <td>{pigcms{$vo.admin_name}</td>
                        <td>{pigcms{$vo.describe}</td>
                        <td>{pigcms{$vo.before_balance}</td>
                        <td>{pigcms{$vo.after_balance}</td>
                        <td>{pigcms{$vo.create_time|date='Y-m-d H:i:s',###}</td>
                    </tr>
                </volist>
                <tr><td class="textcenter pagebar" colspan="6">{pigcms{$data.pagebar}</td></tr>
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
