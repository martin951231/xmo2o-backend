<include file="Public:header" />
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
    <tr>
        <th width="100">设备类别</th>
        <th>打卡时间</th>
    </tr>
    <volist name="list" id="vo">
        <tr>
            <td width="100">
                <if condition="$vo['clock_type'] eq 1">
                    上线
                    <elseif condition="$vo['clock_type'] eq 2" />
                    <font color='red'>休息</font>
                    <else />
                    --
                </if>
            </td>
            <td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
        </tr>
    </volist>
</table>
<include file="Public:footer" />