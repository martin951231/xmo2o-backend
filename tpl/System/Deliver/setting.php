<include file="Public:header"/>
<style>
    .w30_input{
        width: 40px;
        padding: 5px;
        border-radius: 3px;
    }
    .desc{
        margin-left: 30px;
    }
</style>
<form id="myform" method="post" action="{pigcms{:U('Deliver/setting')}" frame="true" refresh="true">
    <input type="hidden" name="area_id" value="{pigcms{$areaInfo.area_id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="90"><strong>配送员到店规则</strong></th>
            <td>
                </strong>开启该功能，配送员接单后，在距离商家&nbsp;<input type="text" class="w30_input" name="pick_miles_range" value="{pigcms{$areaInfo.pick_miles_range|getFormatNumber}"/>&nbsp;公里以内才可操作到店取货
                <span class="desc">0表示不限制</span>
            </td>
        </tr>
        <tr>
            <th width="90"><strong>配送员送达规则</strong></th>
            <td>
                开启该功能，配送员取单后，在距离顾客&nbsp;<input type="text" class="w30_input" name="finish_miles_range" value="{pigcms{$areaInfo.finish_miles_range|getFormatNumber}"/>&nbsp;公里以内才可操作确认送达
                <span class="desc">0表示不限制</span>
            </td>
        </tr>
        <tr>
            <th width="90"><strong>是否开启配送员之间的转单</strong></th>
            <td>
                <span class="cb-enable"><label class="cb-enable <if condition="$areaInfo['open_transfer'] eq 1">selected</if>"><span>启用</span><input type="radio" name="open_transfer" value="1" <if condition="$areaInfo['open_transfer'] eq 1">checked="checked"</if> /></label></span>
                <span class="cb-disable"><label class="cb-disable <if condition="$areaInfo['open_transfer'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="open_transfer" value="0" <if condition="$areaInfo['open_transfer'] eq 0">checked="checked"</if>/></label></span>
            </td>
        </tr>
        <tr>
            <th width="90"><strong>抢单时长</strong></th>
            <td>
                &nbsp;<input type="text" class="w30_input" name="no_grab_min"  value="{pigcms{$areaInfo.no_grab_min|getFormatNumber}"/>&nbsp;分钟以内无人抢单，则当前订单会自动通过智能调度来派单
                <span class="desc">0为跳过</span>
            </td>
        </tr>
        <tr>
            <th width="90"><strong>智能调度时长</strong></th>
            <td>
                &nbsp;<input type="text" class="w30_input" name="smart_deliver_min" value="{pigcms{$areaInfo.smart_deliver_min|getFormatNumber}"/>&nbsp;分钟以内无最合适的智能调度配送员，则当前订单会自动通过最后调度模式规则来派单
                <span class="desc">0为跳过</span>
            </td>
        </tr>
        <tr>
            <th width="90"><strong>最后调度模式</strong></th>
            <td>
                &nbsp;智能调度时长内无最合适的智能调度配送员，则按照
                <select name="last_deliver_model" class="valid">
                    <option value="1" <if condition="$areaInfo['last_deliver_model'] eq 1">selected="selected"</if>>手动调度</option>
                    <option value="2" <if condition="$areaInfo['last_deliver_model'] eq 2">selected="selected"</if>>就近指派</option>
                </select>
                规则来派单
            </td>
        </tr>

    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>

<include file="Public:footer"/>