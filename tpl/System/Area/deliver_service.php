<include file="Public:header"/>

    <div id="nav" class="mainnav_title">
        <ul>
            <a href="javascript:void(0);" class="change buy on" data-type="buy">帮我买</a>|
            <a href="javascript:void(0);" class="change give" data-type="give">帮我送</a>
        </ul>
    </div>
	<form id="myform" method="post" action="{pigcms{:U('Area/deliver_service_amend')}" frame="true" refresh="true">
		<input type="hidden" name="area_id" value="{pigcms{$area_id}"/>
        <div class="buy-info">
            <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">

                <tr>
                    <th width="90">开启帮我买配送设置</th>
                    <td>
                        <select name="buy_status" class="valid" tips="是否开启按区域来设置跑腿配送费功能，如果开启，下面的设置才有用，如果关闭那么以上级区域设置为准，直至平台的默认设置
">
                            <option value="0" <if condition="$deliverSet['buy_status'] eq 0">selected</if>>关闭</option>
                            <option value="1" <if condition="$deliverSet['buy_status'] eq 1">selected</if>>开启</option>
                        </select>
                    </td>
                </tr>
                <if condition="$config['service_buy_deliver_times_switch'] neq 1">
                    <tr class="open_own_buy">
                        <th colspan="2" style="color:red">配送基础设置</th>
                    </tr>
                    <tr class="open_own_buy free_type">
                        <th width="90">起步配送费</th>
                        <td><input type="text" class="input fl" name="buy_base_fee" value="{pigcms{$deliverSet.buy_base_fee|floatval}" size="10" tips="起步距离范围内的配送费（单位:{pigcms{$config['Currency_txt']}）"/></td>
                    </tr>
                    <tr class="open_own_buy free_type">
                        <th width="90">起步配送距离</th>
                        <td><input type="text" class="input fl" name="buy_base_distance" value="{pigcms{$deliverSet.buy_base_distance|floatval}" size="10" tips="起步距离（单位:公里）"/></td>
                    </tr>
                    <tr class="open_own_buy free_type">
                        <th width="90">每公里的配送费</th>
                        <td><input type="text" class="input fl" name="buy_per_km_price" value="{pigcms{$deliverSet.buy_per_km_price|floatval}" size="10" tips="超出起步距离的路程每公里的单价，如果超出部分不是整数的情况下舍去零头取整数，距离是按直线距离算的（单位:{pigcms{$config['Currency_txt']}）"/></td>
                    </tr>
                </if>


                <if condition="$config['service_buy_deliver_times_switch'] eq 1">
                    <tr class="open_own_buy">
                        <th colspan="2" style="color:red">配送时间段一的设置</th>
                    </tr>
                    <tr class="open_own_buy">
                        <th width="160">配送时间段：</th>
                        <td>
                            <input type="text" class="input-text valid" name="buy_time1_start" value="{pigcms{$deliverSet.buy_time1_start}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">&nbsp;-&nbsp;&nbsp;
                            <input type="text" class="input-text" name="buy_time1_end" value="{pigcms{$deliverSet.buy_time1_end}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">
                        </td>
                    </tr>
                    <tr class="open_own_buy free_type">
                        <th width="90">起步配送费</th>
                        <td><input type="text" class="input fl" name="buy_base_fee1" value="{pigcms{$deliverSet.buy_base_fee1|floatval}" size="10" tips="起步距离范围内的配送费（单位:{pigcms{$config['Currency_txt']}）"/></td>
                    </tr>
                    <tr class="open_own_buy free_type">
                        <th width="90">起步配送距离</th>
                        <td><input type="text" class="input fl" name="buy_base_distance1" value="{pigcms{$deliverSet.buy_base_distance1|floatval}" size="10" tips="起步距离（单位:公里）"/></td>
                    </tr>
                    <tr class="open_own_buy free_type">
                        <th width="90">每公里的配送费</th>
                        <td><input type="text" class="input fl" name="buy_per_km_price1" value="{pigcms{$deliverSet.buy_per_km_price1|floatval}" size="10" tips="超出起步距离的路程每公里的单价，如果超出部分不是整数的情况下舍去零头取整数，距离是按直线距离算的（单位:{pigcms{$config['Currency_txt']}）"/></td>
                    </tr>
                    <tr class="open_own_buy" >
                        <th colspan="2" style="color:red">配送时间段二的设置</th>
                    </tr>
                    <tr class="open_own_buy">
                        <th width="160">配送时间段：</th>
                        <td>
                            <input type="text" class="input-text valid" name="buy_time2_start" value="{pigcms{$deliverSet.buy_time2_start}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">&nbsp;-&nbsp;&nbsp;
                            <input type="text" class="input-text" name="buy_time2_end" value="{pigcms{$deliverSet.buy_time2_end}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">
                        </td>
                    </tr>
                    <tr class="open_own_buy free_type2">
                        <th width="90">起步配送费</th>
                        <td><input type="text" class="input fl" name="buy_base_fee2" value="{pigcms{$deliverSet.buy_base_fee2|floatval}" size="10" tips="起步距离范围内的配送费（单位:{pigcms{$config['Currency_txt']}）"/></td>
                    </tr>
                    <tr class="open_own_buy free_type2">
                        <th width="90">起步配送距离</th>
                        <td><input type="text" class="input fl" name="buy_base_distance2" value="{pigcms{$deliverSet.buy_base_distance2|floatval}" size="10" tips="起步距离（单位:公里）"/></td>
                    </tr>
                    <tr class="open_own_buy free_type2">
                        <th width="90">每公里的配送费</th>
                        <td><input type="text" class="input fl" name="buy_per_km_price2" value="{pigcms{$deliverSet.buy_per_km_price2|floatval}" size="10" tips="超出起步距离的路程每公里的单价，如果超出部分不是整数的情况下舍去零头取整数，距离是按直线距离算的（单位:{pigcms{$config['Currency_txt']}）"/></td>
                    </tr>
                </if>
            </table>
        </div>
        <div class="give-info" style="display: none;">
            <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">

                <tr>
                    <th width="90">开启帮我送配送设置</th>
                    <td>
                        <select name="give_status" class="valid" tips="是否开启按区域来设置跑腿配送费功能，如果开启，下面的设置才有用，如果关闭那么以上级区域设置为准，直至平台的默认设置
">
                            <option value="0" <if condition="$deliverSet['give_status'] eq 0">selected</if>>关闭</option>
                            <option value="1" <if condition="$deliverSet['give_status'] eq 1">selected</if>>开启</option>
                        </select>
                    </td>
                </tr>
                <tr class="open_own_give">
                    <th colspan="2" style="color:red">配送时间段一的设置</th>
                </tr>
                <tr class="open_own_give">
                    <th width="160">配送时间段：</th>
                    <td>
                        <input type="text" class="input-text valid" name="give_time1_start" value="{pigcms{$deliverSet.give_time1_start}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">&nbsp;-&nbsp;&nbsp;
                        <input type="text" class="input-text" name="give_time1_end" value="{pigcms{$deliverSet.give_time1_end}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">
                    </td>
                </tr>
                <tr class="open_own_give free_type">
                    <th width="90">起步配送费</th>
                    <td><input type="text" class="input fl" name="give_base_fee1" value="{pigcms{$deliverSet.give_base_fee1|floatval}" size="10" tips="起步距离范围内的配送费（单位:{pigcms{$config['Currency_txt']}）"/></td>
                </tr>
                <tr class="open_own_give free_type">
                    <th width="90">起步配送距离</th>
                    <td><input type="text" class="input fl" name="give_base_distance1" value="{pigcms{$deliverSet.give_base_distance1|floatval}" size="10" tips="起步距离（单位:公里）"/></td>
                </tr>
                <tr class="open_own_give free_type">
                    <th width="90">每公里的配送费</th>
                    <td><input type="text" class="input fl" name="give_per_km_price1" value="{pigcms{$deliverSet.give_per_km_price1|floatval}" size="10" tips="超出起步距离的路程每公里的单价，如果超出部分不是整数的情况下舍去零头取整数，距离是按直线距离算的（单位:{pigcms{$config['Currency_txt']}）"/></td>
                </tr>
                <tr class="open_own_give" >
                    <th colspan="2" style="color:red">配送时间段二的设置</th>
                </tr>
                <tr class="open_own_give">
                    <th width="160">配送时间段：</th>
                    <td>
                        <input type="text" class="input-text valid" name="give_time2_start" value="{pigcms{$deliverSet.give_time2_start}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">&nbsp;-&nbsp;&nbsp;
                        <input type="text" class="input-text" name="give_time2_end" value="{pigcms{$deliverSet.give_time2_end}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">
                    </td>
                </tr>
                <tr class="open_own_give free_type2">
                    <th width="90">起步配送费</th>
                    <td><input type="text" class="input fl" name="give_base_fee2" value="{pigcms{$deliverSet.give_base_fee2|floatval}" size="10" tips="起步距离范围内的配送费（单位:{pigcms{$config['Currency_txt']}）"/></td>
                </tr>
                <tr class="open_own_give free_type2">
                    <th width="90">起步配送距离</th>
                    <td><input type="text" class="input fl" name="give_base_distance2" value="{pigcms{$deliverSet.give_base_distance2|floatval}" size="10" tips="起步距离（单位:公里）"/></td>
                </tr>
                <tr class="open_own_give free_type2">
                    <th width="90">每公里的配送费</th>
                    <td><input type="text" class="input fl" name="give_per_km_price2" value="{pigcms{$deliverSet.give_per_km_price2|floatval}" size="10" tips="超出起步距离的路程每公里的单价，如果超出部分不是整数的情况下舍去零头取整数，距离是按直线距离算的（单位:{pigcms{$config['Currency_txt']}）"/></td>
                </tr>
            </table>
        </div>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<script>
$(document).ready(function(){
    // 切换
    $('.change').click(function () {
        var text = $(this).data('type');
        if (text == 'give') {
            $(this).addClass('on');
            $('.buy').removeClass('on');
            $('.buy-info').css('display', 'none');
            $('.give-info').css('display', 'block');
        } else {
            $(this).addClass('on');
            $('.give').removeClass('on');
            $('.buy-info').css('display', 'block');
            $('.give-info').css('display', 'none');
        }
    });


    var s_is_open_own_buy = $('select[name=buy_status]').val(),s_is_open_own_give = $('select[name=give_status]').val();
    if (s_is_open_own_buy == 1) {
        $('.open_own_buy').show();
    } else {
        $('.open_own_buy').hide();
    }
    if (s_is_open_own_give == 1) {
        $('.open_own_give').show();
    } else {
        $('.open_own_give').hide();
    }
    $('select[name=buy_status]').change(function(){
        if ($(this).val() == 1) {
            $('.open_own_buy').show();
        } else {
            $('.open_own_buy').hide();
        }
    });
    $('select[name=give_status]').change(function(){
        if ($(this).val() == 1) {
            $('.open_own_give').show();
        } else {
            $('.open_own_give').hide();
        }
    });
});
</script>
<include file="Public:footer"/>