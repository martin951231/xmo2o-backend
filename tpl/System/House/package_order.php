<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('House/property_edit')}" frame="true" refresh="true">
		<input type="hidden" name="id" value="{pigcms{$now_village.id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">

            <tr>
                <td rowspan="1" style="color: #0a0a0a;font-size: 14px;">功能套餐</td>
            </tr>

            <tr>
                <th width="120">套餐名称</th>
                <td>
                    {pigcms{$package_order['package_title']}
                </td>
            </tr>
            <tr>
                <th width="120">所含房间数(间)</th>
                <td>
                    {pigcms{$package_order['room_num']}
                </td>
            </tr>
            <if condition="$package_order['use_status'] eq 1">
                <tr>
                    <th width="120">套餐价格（{pigcms{$config['Currency_txt']}）</th>
                    <td>
                        {pigcms{$config['Currency_symbol']}{pigcms{$package_order['package_price']}/年（366天）
                    </td>
                </tr>
                <tr>
                    <th width="120">购买周期</th>
                    <td>
                        {pigcms{$package_order['num']} 年
                    </td>
                </tr>
                <tr>
                    <th width="120">套餐状态</th>
                    <td>
                        {pigcms{$package_order['tip']}
                    </td>
                </tr>
                <if condition="$package_order['package_end_time_txt']">
                    <tr>
                        <th width="120">套餐到期时间</th>
                        <td>
                            {pigcms{$package_order['package_end_time_txt']}
                        </td>
                    </tr>
                </if>
                <else/>
                <tr>
                    <th width="120">套餐状态</th>
                    <td>
                        {pigcms{$package_order['tip']}
                    </td>
                </tr>
                <tr>
                    <th width="120">套餐试用到期时间</th>
                    <td>
                        {pigcms{$package_order['package_try_end_time_txt']}
                    </td>
                </tr>
            </if>


            <tr>
                <td rowspan="1" style="color: #0a0a0a;font-size: 14px;">房间数量套餐</td>
            </tr>
            <tr>
                <th width="120" style="color: #0a0a0a;font-size: 13px;">房间总数量（间）</th>
                <td>
                    {pigcms{$package_room_total}
                </td>
            </tr>
            <if condition="$package_room_order">
                <volist name="package_room_order" id="item">
                    <tr>
                        <th width="120">套餐名称</th>
                        <td>
                            {pigcms{$item['room_title']}
                        </td>
                    </tr>
                    <tr>
                        <th width="120">套餐价格（{pigcms{$config['Currency_txt']}）</th>
                        <td>
                            {pigcms{$config['Currency_symbol']}{pigcms{$item['room_prcie']}
                        </td>
                    </tr>
                    <tr>
                        <th width="120">购买数量</th>
                        <td>
                            {pigcms{$item['num']}
                        </td>
                    </tr>
                    <tr>
                        <th width="120">房间数量</th>
                        <td>
                            {pigcms{$item['room_num']}
                        </td>
                    </tr>
                    <tr>
                        <td rowspan="1">&nbsp;</td>
                    </tr>
                </volist>
                <else/>
                <tr>
                    <td rowspan="1">
                        暂无
                    </td>
                </tr>
            </if>

		</table>
	</form>
<include file="Public:footer"/>