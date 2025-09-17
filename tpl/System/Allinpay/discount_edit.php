<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Allinpay/discount_amend')}" frame="true" refresh="true">
		<input type="hidden" name="id" value="{pigcms{$now_discount.id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr><td>优惠规则</td></tr>
            <tr>
                <th width="160"><span style="color:red;">*</span>活动时间</th>
                <td style="width:30%;">
                    <input type="text" class="hasDatepicker input" id="startdate"  value="<if condition="$now_discount['startdate'] neq ''">{pigcms{$now_discount.startdate|date="Y-m-d",###}</if>" name="startdate" onClick="WdatePicker()" />
                </td>
                <td colspan="2">
                    <span style="margin-left: -25px;margin-right: 5px;">到</span>
                    <input type="text" class="hasDatepicker input" id="enddate" value="<if condition="$now_discount['enddate'] neq ''">{pigcms{$now_discount.enddate|date="Y-m-d",###}</if>" name="enddate" onClick="WdatePicker()" />
                </td>
            </tr>
            <tr>
                <th width="90">优惠类型</th>
                <td colspan="2">
                    <select name="type" class="valid" tips="新单：就是用户在平台的{pigcms{$config.shop_alias_name}第一次下单享受的优惠；满减：就是用户一次下单的金额满足了条件得到相应的优惠；配送：指用户一次下单满足多少钱后减免指定金额的配送费，最多减至配送费是0">
                        <option value="0" <if condition="$now_discount['type'] eq 0">selected</if>>满减</option>
                        <option value="1" <if condition="$now_discount['type'] eq 1">selected</if>>折扣</option>
                        <option value="2" <if condition="$now_discount['type'] eq 2">selected</if>>随机立减</option>
                    </select>
                </td>
            </tr>
			<tr>
				<th width="90">最低消费</th>
				<td colspan="2"><input type="text" class="input fl" name="reach_money" value="{pigcms{$now_discount.reach_money}" id="reach_money" size="25" placeholder="0表示任意金额都可" validate="maxlength:20" tips="达到最低消费才能有相应的优惠"/></td>
			</tr>
            <tr class="reduce_money">
                <th width="90">优惠的金额</th>
                <td colspan="2"><input type="text" class="input fl" name="reduce_money" value="{pigcms{$now_discount.reduce_money}" id="reduce_money" size="25" placeholder="优惠金额大于0" validate="maxlength:20" tips="减免的金额"/></td>
            </tr>
            <tr class="discount">
                <th width="90">优惠折扣</th>
                <td colspan="2"><input type="text" class="input fl" name="discount"  value="{pigcms{$now_discount.discount}" size="25" placeholder="填入优惠折扣" validate="maxlength:20" tips="单位是百分比，例如90就是9折优惠"/></td>
            </tr>
            <tr class="discount">
                <th width="90">折扣最高优惠金额</th>
                <td colspan="2"><input type="text" class="input fl" name="discount_max_money" value="{pigcms{$now_discount.discount_max_money}"  size="25" placeholder="填入金额" validate="maxlength:20" tips="0表示不限制"/></td>
            </tr>
            <tr class="random_reduce">
                <th width="90">随机立减金额</th>
                <td colspan="2"><textarea  name="random_reduce" style="width: 200px;height: 400px;" placeholder="填入金额" tips="换行填入下一个金额">{pigcms{$now_discount.random_reduce}</textarea></td>
            </tr>
            <tr>
                <th width="90">活动总优惠金额</th>
                <td colspan="2"><input type="text" class="input fl" name="max_money" value="{pigcms{$now_discount.max_money}" size="25" placeholder="填入金额" validate="maxlength:20,required:true" tips="0表示不限制"/></td>
            </tr>
			<tr>
				<th width="90">使用状态</th>
				<td colspan="2">
					<span class="cb-enable"><label class="cb-enable <if condition="$now_discount['status'] eq 1">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$now_discount['status'] eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_discount['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$now_discount['status'] eq 0">checked="checked"</if>/></label></span>
				</td>
			</tr>

            <tr><td>商家规则</td></tr>
            <tr>
                <th width="90">使用区域</th>
                <td colspan="2">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_discount['use_area'] eq 0">selected</if>"><span>全区域可用</span><input type="radio" name="use_area" value="0" <if condition="$now_discount['use_area'] eq 0">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_discount['use_area'] eq 1">selected</if>"><span>指定区域可用</span><input type="radio" name="use_area" value="1" <if condition="$now_discount['use_area'] eq 1">checked="checked"</if>/></label></span>
                </td>
            </tr>
            <tr>
                <th width="90">使用商家</th>
                <td colspan="2">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_discount['use_type'] eq 0">selected</if>"><span>全部商家可用</span><input type="radio" name="use_type" value="0" <if condition="$now_discount['use_type'] eq 0">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_discount['use_type'] eq 1">selected</if>"><span>指定商家可用</span><input type="radio" name="use_type" value="1" <if condition="$now_discount['use_type'] eq 1">checked="checked"</if>/></label></span>
                </td>
            </tr>
            <tr>
                <th width="90">优惠名额</th>
                <td colspan="2"><input type="text" class="input fl" name="p_num"  size="25" value="{pigcms{$now_discount.p_num}" placeholder="填入数量" validate="maxlength:20,required:true" tips="0表示不限制"/></td>
            </tr>
            <tr>
                <th width="90">名额类型</th>
                <td colspan="2">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_discount['p_type'] eq 0">selected</if>"><span>商家平均名额</span><input type="radio" name="p_type" value="0" <if condition="$now_discount['p_type'] eq 0">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_discount['p_type'] eq 1">selected</if>"><span>商家共享名额</span><input type="radio" name="p_type" value="1" <if condition="$now_discount['p_type'] eq 1">checked="checked"</if> /></label></span>
                </td>
            </tr>

            <tr><td>用户规则</td></tr>
            <tr>
                <th width="90">使用卡BIN</th>
                <td colspan="2">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_discount['card_limit'] eq 0">selected</if>"><span>全部卡BIN可用</span><input type="radio" name="card_limit" value="0" <if condition="$now_discount['card_limit'] eq 0">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_discount['card_limit'] eq 1">selected</if>"><span>指定卡BIN可用</span><input type="radio" name="card_limit" value="1" <if condition="$now_discount['card_limit'] eq 1">checked="checked"</if>/></label></span>
                </td>
            </tr>
            <tr>
                <th width="90">单卡优惠次数</th>
                <td colspan="2"><input type="text" class="input fl" name="single_num"  size="25" value="{pigcms{$now_discount.single_num}" placeholder="填入数量" validate="maxlength:20,required:true" tips="0表示不限制"/></td>
            </tr>
            <tr>
                <th width="90">次数周期</th>
                <td colspan="2">
                    <select name="cycle_type" class="valid" tips="单卡优惠的次数周期">
                        <option value="0" <if condition="$now_discount['cycle_type'] eq 0">selected</if>>不限</option>
                        <option value="1" <if condition="$now_discount['cycle_type'] eq 1">selected</if>>每月</option>
                        <option value="2" <if condition="$now_discount['cycle_type'] eq 2">selected</if>>每周</option>
                        <option value="3" <if condition="$now_discount['cycle_type'] eq 3">selected</if>>每日</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th width="90">优惠总次数</th>
                <td colspan="2"><input type="text" class="input fl" name="total_num" size="25" value="{pigcms{$now_discount.total_num}" placeholder="填入数量" validate="maxlength:20,required:true" tips="0表示不限制"/></td>
            </tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
    <script type="text/javascript">
        var type = "{pigcms{$now_discount.type}";
        $(function(){
            if(type==0){
                $('.discount').hide();
                $('.random_reduce').hide();
                $('.reduce_money').show();
            }else if(type==1){
                $('.discount').show();
                $('.random_reduce').hide();
                $('.reduce_money').hide();
            }else if (type==2){
                $('.discount').hide();
                $('.random_reduce').show();
                $('.reduce_money').hide();
            }
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