<include file="Public:header"/>
<style type="text/css">
    .required{
        color: #FF0000;
    }
</style>
<form id="myform" method="post" action="{pigcms{:U('MerchantSpread/setting')}"  enctype="multipart/form-data">
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">开通vip推广商功能</th>
            <td>
                <span class="cb-enable"><label class="cb-enable  <if condition="$configIndex['open_vip_merchant_spread'] eq 1">selected</if>"><span>开启</span><input name="open_vip_merchant_spread" value="1" <if condition="$configIndex['open_vip_merchant_spread'] eq 1">checked="checked"</if> type="radio"></label></span>
                <span class="cb-disable" ><label class="cb-disable <if condition="$configIndex['open_vip_merchant_spread'] eq 0">selected</if>"><span>关闭</span><input name="open_vip_merchant_spread" value="0" <if condition="$configIndex['open_vip_merchant_spread'] eq 0">checked="checked"</if> type="radio"></label></span>
            </td>
        </tr>
        <tr>
            <th width="80">开通城市推广商功能</th>
            <td>

                <span class="cb-enable"><label class="cb-enable  <if condition="$configIndex['open_city_merchant_spread'] eq 1">selected</if>"><span>开启</span><input name="open_city_merchant_spread" value="1" <if condition="$configIndex['open_city_merchant_spread'] eq 1">checked="checked"</if> type="radio"></label></span>
                <span class="cb-disable" ><label class="cb-disable <if condition="$configIndex['open_city_merchant_spread'] eq 0">selected</if>"><span>关闭</span><input name="open_city_merchant_spread" value="0" <if condition="$configIndex['open_city_merchant_spread'] eq 0">checked="checked"</if> type="radio"></label></span>
            </td>
        </tr>
        <if condition="C('config.open_vip_merchant_spread') eq 1">
        <tr>
            <th width="35%"><span class="required">*</span>vip推广商缴纳金额</th>
            <td><input style="width: 80%" type="text" class="input fl" name="vip_merchant_spread_price" size="50" validate="min:0.01,required:true" value="{pigcms{$vipSpread.spread_price|getFormatNumber}" placeholder="请填写大于0的数字，最大保留2位小数"  tips=""/></td>
        </tr>
        <tr>
            <th width="35%"><span class="required">*</span>vip推广商直推奖励比例</th>
            <td><input style="width: 80%" type="text" class="input fl" name="vip_merchant_spread_percent" size="50" validate="min:0.01,required:true" value="{pigcms{$vipSpread.spread_percent|getFormatNumber}"  placeholder="请填写数字，单位：%" tips=""/>&nbsp;%</td>
        </tr>
        </if>

        <if condition="C('config.open_city_merchant_spread') eq 1">
        <tr>
            <th width="35%"><span class="required">*</span>城市推广商缴纳金额</th>
            <td><input style="width: 80%" type="text" class="input fl" name="city_merchant_spread_price" size="50" validate="min:0.01,required:true" value="{pigcms{$citySpread.spread_price|getFormatNumber}" placeholder="请填写大于0的数字，最大保留2位小数" tips=""/></td>
        </tr>
        <tr>
            <th width="35%"><span class="required">*</span>城市推广商直推奖励比例</th>
            <td><input style="width: 80%" type="text" class="input fl" name="city_merchant_spread_percent" size="50" validate="min:0.01,required:true" value="{pigcms{$citySpread.spread_percent|getFormatNumber}" placeholder="请填写数字，单位：%" tips=""/>&nbsp;%</td>
        </tr>
        </if>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<include file="Public:footer"/>