<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Allinpay/discount_modify')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr><td>优惠规则</td></tr>
            <tr>
                <th width="160"><span style="color:red;">*</span>活动时间</th>
                <td>
                    <input type="text" class="hasDatepicker input fl" id="startdate" value="" name="startdate" onClick="WdatePicker()" />
                    到
                    <input type="text" class="hasDatepicker input" id="enddate" value="" name="enddate" onClick="WdatePicker()" />
                </td>
            </tr>
            <tr>
                <th width="90">优惠类型</th>
                <td>
                    <select name="type" class="valid" tips="用户一次下单的金额满足了最低消费得到相应的优惠">
                        <option value="0">满减</option>
                        <option value="1">折扣</option>
                        <option value="2">随机立减</option>
                    </select>
                </td>
            </tr>
			<tr>
				<th width="90">最低消费</th>
				<td><input type="text" class="input fl" name="reach_money" id="reach_money" size="25" placeholder="0表示任意金额都可" validate="maxlength:20,required:true" tips="满足该最低消费才能有相应的优惠"/></td>
			</tr>
			<tr class="reduce_money">
				<th width="90">优惠的金额</th>
				<td><input type="text" class="input fl" name="reduce_money" id="reduce_money" size="25" placeholder="优惠金额大于0" validate="maxlength:20" tips="减免的金额"/></td>
			</tr>
            <tr class="discount" style="display: none;">
                <th width="90">优惠折扣</th>
                <td><input type="text" class="input fl" name="discount"  size="25" placeholder="填入优惠折扣" validate="maxlength:20" tips="单位是百分比，例如90就是9折优惠"/></td>
            </tr>
            <tr class="discount" style="display: none;">
                <th width="90">折扣最高优惠金额</th>
                <td><input type="text" class="input fl" name="discount_max_money"  size="25" placeholder="填入金额" validate="maxlength:20" tips="0表示不限制"/></td>
            </tr>
            <tr class="random_reduce" style="display: none;">
                <th width="90">随机立减金额</th>
                <td><textarea  name="random_reduce" style="width: 200px;height: 400px;" placeholder="填入金额" tips="换行填入下一个金额"></textarea></td>
            </tr>
            <tr>
                <th width="90">活动总优惠金额</th>
                <td><input type="text" class="input fl" name="max_money"  size="25" placeholder="填入金额" validate="maxlength:20,required:true" tips="0表示不限制"/></td>
            </tr>
			<tr>
				<th width="90">使用状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="status" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0" /></label></span>
				</td>
			</tr>

            <tr><td>商家规则</td></tr>
            <tr>
                <th width="90">使用区域</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable selected"><span>全区域可用</span><input type="radio" name="use_area" value="0" checked="checked" /></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>指定区域可用</span><input type="radio" name="use_area" value="1" /></label></span>
                </td>
            </tr>
            <tr>
                <th width="90">使用商家</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable selected"><span>全部商家可用</span><input type="radio" name="use_type" value="0" checked="checked" /></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>指定商家可用</span><input type="radio" name="use_type" value="1" /></label></span>
                </td>
            </tr>
            <tr>
                <th width="90">优惠名额</th>
                <td><input type="text" class="input fl" name="p_num"  size="25" placeholder="填入数量" validate="maxlength:20,required:true" tips="0表示不限制"/></td>
            </tr>
            <tr>
                <th width="90">名额类型</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable selected"><span>商家平均名额</span><input type="radio" name="p_type" value="0" checked="checked" /></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>商家共享名额</span><input type="radio" name="p_type" value="1" /></label></span>
                </td>
            </tr>
            <tr><td>用户规则</td></tr>
            <tr>
                <th width="90">银行卡限制</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable selected"><span>全卡BIN支持</span><input type="radio" name="card_limit" value="0" checked="checked" /></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>指定卡BIN</span><input type="radio" name="card_limit" value="1" /></label></span>
                </td>
            </tr>
            <tr>
                <th width="90">单卡优惠次数</th>
                <td><input type="text" class="input fl" name="single_num"  size="25" placeholder="填入数量" validate="maxlength:20,required:true" tips="0表示不限制"/></td>
            </tr>
            <tr>
                <th width="90">次数周期</th>
                <td>
                    <select name="cycle_type" class="valid" tips="单卡优惠的次数周期">
                        <option value="0">不限</option>
                        <option value="1">每月</option>
                        <option value="2">每周</option>
                        <option value="3">每日</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th width="90">优惠总次数</th>
                <td><input type="text" class="input fl" name="total_num" size="25" placeholder="填入数量" validate="maxlength:20,required:true" tips="0表示不限制"/></td>
            </tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
    <script type="text/javascript">
    $(function(){
        $('select[name=type]').change(function(){
            if ($(this).val() == 0) {
                $('.discount').hide();
                $('.random_reduce').hide();
                $('.reduce_money').show();
            } else if($(this).val() == 1) {
                $('.discount').show();
                $('.random_reduce').hide();
                $('.reduce_money').hide();
            }else if($(this).val() == 2) {
                $('.discount').hide();
                $('.random_reduce').show();
                $('.reduce_money').hide();
            }
        });
    });
    </script>
<script type="text/javascript" src="/static/js/date/WdatePicker.js"></script>
<include file="Public:footer"/>