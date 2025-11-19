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
<form id="myform" method="post" action="{pigcms{:U('Employee/check_store_amend')}" frame="true" refresh="true">
    <input type="hidden" name="store_id" value="{pigcms{$store.store_id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">店铺名称</th>
            <td><input type="text" class="input fl" value="{pigcms{$store.name}" size="25" placeholder="店铺名称" readonly/></td>
        </tr>
        <if condition="$config.international_phone eq 1">
        <tr>
            <th width="80">区号</th>
            <td>

                 <pigcms:national_phone id="phone_country_type" class="fl" style="margin-right:5px;" readonly>
                    <option value="{pigcms{$vo.code}"  <if condition="$store.phone_country_type eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
                </pigcms:national_phone>

            </td>
        </tr>

        </if>
        <tr>
            <th width="80">联系电话</th>
            <td>

            <input type="text" class="input fl" size="25" value="{pigcms{$store.phone}" readonly/></td>
        </tr>
        <tr>
            <th width="80">店铺经纬度</th>
            <td><input type="text" class="input fl" value="{pigcms{$store.long},{pigcms{$store.lat}" size="25" placeholder="店铺名称" readonly/></td>
            </td>
        </tr>
        <tr>
            <th width="80">店铺分类</th>
            <td id="choose_category" cat_fid="{pigcms{$store.cat_fid}" cat_id="{pigcms{$store.cat_id}"></td>
        </tr>
        <tr>
            <th width="80">店铺所在地</th>
            <td id="choose_cityarea" province_id="{pigcms{$store.province_id}" city_id="{pigcms{$store.city_id}" area_id="{pigcms{$store.area_id}" circle_id="{pigcms{$store.circle_id}" market_id="{pigcms{$store.market_id}"></td>
        </tr>
        <tr>
            <th width="80">店铺地址</th>
            <td><input type="text" class="input fl" value="{pigcms{$store.adress}" readonly/></td>
        </tr>
        <tr>
            <th width="80">店铺排序</th>
            <td><input type="text" class="input fl" value="{pigcms{$store.sort}" readonly/></td>
        </tr>
        <tr>
            <th width="80">店铺状态</th>
            <td>
               审核中
            </td>
        </tr>
        <tr>
            <th width="80">店铺描述</th>
            <td>
                <textarea style="width: 80%;" rows="5" readonly>{pigcms{$store.txt_info}</textarea>
            </td>
        </tr>
        <tr>
            <th width="80">营业时间段1</th>
            <td>
                <input type="text" class="input-text" value="{pigcms{$store.open_1}&nbsp;至&nbsp;&nbsp;{pigcms{$store.close_1}" readonly/>
            </td>
        </tr>
        <tr>
            <th width="80">营业时间段2</th>
            <td>
                <input type="text" class="input-text" value="{pigcms{$store.open_2}&nbsp;至&nbsp;&nbsp;{pigcms{$store.close_2}" readonly/>
            </td>
        </tr>
        <tr>
            <th width="80">营业时间段3</th>
            <td>
                <input type="text" class="input-text" value="{pigcms{$store.open_3}&nbsp;至&nbsp;&nbsp;{pigcms{$store.close_3}" readonly/>
            </td>
        </tr>
        <tr>
            <th width="80">审核是否通过</th>
            <td>
                <if condition="$store['claim_status'] eq 1">
                    <font color="green">启用</font>
                    <elseif condition="$store['claim_status'] eq 2"/>
                    <font color="red">审核中</font>
                    <elseif condition="$store['claim_status'] eq 3"/>
                    <font color="red">审核拒绝</font>
                    <else/>
                    <font color="red">关闭</font>
                </if>
            </td>
        </tr>
        <tr>
            <th width="80">审核备注</th>
            <td>
                <input type="text" class="input fl" name="check_remark" id="check_remark" value="{pigcms{$store.check_remark}"/>
            </td>
        </tr>
    </table>
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
    $('.change_time').click(function () {
        $('.change_time').removeClass('on');
        $(this).addClass('on');
        var id=$(this).attr("data-id");
        $('.tab-content>.tab-pane').removeClass('active');
        $('#' + id).addClass('active');
    });
</script>
<include file="Public:footer"/>