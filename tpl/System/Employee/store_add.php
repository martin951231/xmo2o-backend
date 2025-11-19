<include file="Public:header"/>
<style>
    .tab-content>.tab-pane {
        display: none;
    }
    .tab-content>.active {
        display: block;
    }
    .input-text {
        font-size: 16px;
        padding: 10px;
    }
</style>
<form id="myform" method="post" action="{pigcms{:U('Employee/store_modify')}" frame="true" refresh="true">
    <input type="hidden" name="mer_id" value="{pigcms{$merchant.mer_id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">店铺名称</th>
            <td><input type="text" class="input fl" name="name" size="25" placeholder="店铺名称" validate="maxlength:20,required:true"/></td>
        </tr>
        <if condition="$config.international_phone eq 1">
            <tr>
                <th width="80">区号</th>
                <td>

                    <pigcms:national_phone id="phone_country_type" class="fl" style="margin-right:5px;">
                        <option value="{pigcms{$vo.code}"  <if condition="$config.qcloud_sms_default_country eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
                    </pigcms:national_phone>

                </td>
            </tr>
        </if>
        <tr>
            <th width="80">联系电话</th>
            <td>

                <input type="text" class="input fl" name="phone" size="25" placeholder="店铺的电话" validate="required:true" tips="多个电话号码以空格分开"/></td>
        </tr>
        <tr>
            <th width="80">店铺经纬度</th>
            <td id="choose_map"></td>
        </tr>
        <tr>
            <th width="80">店铺分类</th>
            <td id="choose_category"></td>
        </tr>
        <if condition="$system_session['area_type'] neq 3">
            <tr>
                <th width="80">店铺所在地</th>
                <td id="choose_cityarea"></td>
            </tr>
            <else />
            <tr>
                <th width="160">所在区域</th>
                <td>
                    {pigcms{$merchant.province_name} {pigcms{$merchant.city_name} {pigcms{$merchant.area_name}
                    <input type="hidden" name="area_id"  value = "{pigcms{$system_session['area_id']}">
                    <input type="hidden" name="city_id"  value = "{pigcms{$merchant['city_id']}">
                    <input type="hidden" name="province_id"  value = "{pigcms{$merchant['province_id']}">
                </td>
            </tr>
        </if>
        <tr>
            <th width="80">店铺地址</th>
            <td><input type="text" class="input fl" name="adress" id="adress" size="25" placeholder="店铺的地址" validate="required:true"/></td>
        </tr>
        <tr>
            <th width="80">店铺排序</th>
            <td><input type="text" class="input fl" name="sort" size="5" value="0" validate="required:true,number:true,maxlength:6" tips="默认添加顺序排序！手动调值，数值越大，排序越前"/></td>
        </tr>
        <tr>
            <th width="80">店铺描述</th>
            <td>
                <textarea style="width: 80%;" rows="5" name="txt_info"></textarea>
            </td>
        </tr>
    </table>
    <div class="tabbable">
        <div id="nav" class="mainnav_title">
            <ul>
                <a class="change_time on" href="#shop_time_1" data-id="shop_time_1">营业时间段1</a>
                <a class="change_time" href="#shop_time_2" data-id="shop_time_2">营业时间段2</a>
                <a class="change_time" href="#shop_time_3" data-id="shop_time_3">营业时间段3</a>
            </ul>
        </div>
        <div class="tab-content">
            <div id="shop_time_1" class="tab-pane active">
                <div style="padding: 10px;">
                    <input type="text" class="input-text" name="open_1" value="08:00" size="10" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})"/>&nbsp;至&nbsp;&nbsp;
                    <input type="text" class="input-text" name="close_1" value="20:00" size="10" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})"/>
                </div>
            </div>
            <div id="shop_time_2" class="tab-pane">
                <div style="padding: 10px;">
                    <input type="text" class="input-text" name="open_2" value="00:00" size="10" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})"/>&nbsp;至&nbsp;&nbsp;
                    <input type="text" class="input-text" name="close_2" value="00:00" size="10" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})"/>
                </div>
            </div>
            <div id="shop_time_3" class="tab-pane">
                <div style="padding: 10px;">
                    <input type="text" class="input-text" name="open_3" value="00:00" size="10" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})"/>&nbsp;至&nbsp;&nbsp;
                    <input type="text" class="input-text" name="close_3" value="00:00" size="10" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})"/>
                </div>
            </div>
        </div>
    </div>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>

<script type="text/javascript">
    var choose_cat_fid="{pigcms{:U('Employee/ajax_cat_fid')}",choose_cat_id="{pigcms{:U('Employee/ajax_cat_id')}";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/merchant_category.js"></script>
<script>
    console.log("{pigcms{$static_path}/js/merchant_category.js");
    $('.change_time').click(function () {
        $('.change_time').removeClass('on');
        $(this).addClass('on');
        var id=$(this).attr("data-id");
        $('.tab-content>.tab-pane').removeClass('active');
        $('#' + id).addClass('active');
    });
</script>
<include file="Public:footer"/>