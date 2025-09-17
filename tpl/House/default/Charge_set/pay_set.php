<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Charge_set/pay_set')}">社区缴费设置</a>
            </li>
            <li class="active">基本设置</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="tabbable">
                    <ul id="myTab" class="nav nav-tabs">
                        <li class="active">
                            <a href="javascript:;" data-toggle="tab">收费设置</a>
                        </li>
                        <li>
                            <a href="{pigcms{:U('Charge_set/pay_type_list',$_GET)}">线下支付设置</a>
                        </li>
                    </ul>
                </div>
                <form id="edit_form" method="post" class="form-horizontal" action="{pigcms{:U('Charge_set/village_edit')}" enctype="multipart/form-data">
                    <div class="tab-content">
                        <div class="tab-pane active" id="basicinfo">
                            <div class="tab-pane" id="txtstore">
                                <!--<div class="form-group">
                                    <label class="col-sm-1"><label for="property_price">物业费单价</label></label>
                                    <input class="col-sm-1" size="10" name="property_price" id="property_price" type="text" value="{pigcms{$village_info.property_price|floatval=###}"/>
                                    <span class="form_tips">{pigcms{$config['Currency_txt']}/平方米/月 （支持两位小数，设置为0表示不支持）</span>
                                </div>-->
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="water_price">水费单价</label></label>
                                    <input class="col-sm-1" size="10" name="water_price" id="water_price" type="text" value="{pigcms{$village_info.water_price|floatval=###}" />
                                    <span class="form_tips">{pigcms{$config['Currency_txt']}/立方米 （支持两位小数，设置为0表示不支持）</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="electric_price">电费单价</label></label>
                                    <input class="col-sm-1" size="10" name="electric_price" id="electric_price" type="text" value="{pigcms{$village_info.electric_price|floatval=###}" />
                                    <span class="form_tips">{pigcms{$config['Currency_txt']}/千瓦时(度) （支持两位小数，设置为0表示不支持）</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="gas_price">燃气费单价</label></label>
                                    <input class="col-sm-1" size="10" name="gas_price" id="gas_price" type="text" value="{pigcms{$village_info.gas_price|floatval=###}"  />
                                    <span class="form_tips">{pigcms{$config['Currency_txt']}/立方米 （支持两位小数，设置为0表示不支持）</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="park_price">停车位单价</label></label>
                                    <input class="col-sm-1" size="10" name="park_price" id="park_price" type="text" value="{pigcms{$village_info.park_price|floatval=###}" />
                                    <span class="form_tips">{pigcms{$config['Currency_txt']}/月 （支持两位小数，设置为0表示不支持）</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2"><label for="refund_cycle">收银台已缴纳账单退款期限：收款后</label></label>
                                    <input class="col-sm-1" size="10" name="refund_cycle" id="refund_cycle" type="text" value="{pigcms{$village_info.refund_cycle}" />
                                    <span class="form_tips">（天）可进行退款</span>
                                </div>

                                <div class="form-group" style="display:none">
                                    <label class="col-sm-1"><label for="express_collection_price">快递代送价格</label></label>
                                    <input class="col-sm-1" size="10" name="express_collection_price" id="express_collection_price" type="text" value="{pigcms{$village_info.express_collection_price|floatval=###}" />
                                    <span class="form_tips">{pigcms{$config['Currency_txt']} （不支持小数。）</span>
                                </div>
                                <!-- <div class="form-group">
                                    <label class="col-sm-1"><label for="property_warn_day">物业提醒时间</label></label>
                                    <input class="col-sm-1" size="10" name="property_warn_day" id="property_warn_day" type="text" value="{pigcms{$village_info.property_warn_day|floatval=###}" />
                                    <span class="form_tips">天 （不支持小数。开通后，即可向业主手动提前相应天数进行提醒发送模板消息。）</span>
                                </div> -->

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="has_property_pay">自定义物业缴费</label></label>
                                    <div class="radio">
                                        <label style="padding-left:0px;padding-right:20px;"><input name="has_property_pay" value="1" type="radio" class="ace" <if condition="$village_info.has_property_pay eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                        <label style="padding-left:0px;"><input name="has_property_pay" value="0" type="radio" class="ace" <if condition="$village_info.has_property_pay eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
                                        <span class="form_tips">开通后，物业费可自定义上缴月份</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="has_custom_pay">临时费用</label></label>
                                    <div class="radio">
                                        <label style="padding-left:0px;padding-right:20px;"><input name="has_custom_pay" value="1" type="radio" class="ace" <if condition="$village_info.has_custom_pay eq 1">checked</if>/><span class="lbl" style="z-index: 1">支持</span></label>
                                        <label style="padding-left:0px;"><input name="has_custom_pay" value="0" type="radio" class="ace" <if condition="$village_info.has_custom_pay eq 0">checked</if>/><span class="lbl" style="z-index: 1">不支持</span></label>
                                        <span class="form_tips">开通后，用户可以自定义名称、费用向物业发起缴费！方便物业上门维修等自定义收取费用</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="send_date">自动催缴日期</label></label>
                                    <div style="width: 92%;margin-left: 8%">
                                    每月
                                    <if condition="$village_info['send_date']">
                                        <input type="text" name="send_date" class="input-text" value="{pigcms{$village_info.send_date}" onfocus="WdatePicker({isShowClear:true,dateFmt:'d'})" placeholder="默认不通知" autocomplete="new-password" style="background-color: #FFFFFF!important;" readonly onfocus="this.removeAttribute('readonly');" onblur="this.setAttribute('readonly',true);"/>
                                        <else/>
                                        <input type="text" name="send_date" class="input-text" value="" onfocus="WdatePicker({isShowClear:true,dateFmt:'d'})" placeholder="默认不通知" autocomplete="new-password" style="background-color: #FFFFFF!important;" readonly onfocus="this.removeAttribute('readonly');" onblur="this.setAttribute('readonly',true);"/>
                                    </if>
                                    号
                                    <span class="form_tips">水费，电费，燃气费，停车费的自动催缴日期设置，不设置则不发送微信通知；物业费、自定义缴费的自动催缴则根据每个缴费周期到期时间来自动微信通知；以上微信通知均在当天的早上9:30分通知。</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="send_user_type">自动催缴发送人</label></label>
                                    <select name="send_user_type" id="send_user_type" style="width: 250px">
                                        <option value="1" <if condition="$village_info['send_user_type'] eq 1">selected="selected"</if> >仅业主</option>
                                        <option value="2" <if condition="$village_info['send_user_type'] eq 2">selected="selected"</if> >业主和<if condition="$config['open_house_user_name']">亲友<else/>家属</if></option>
                                        <option value="3" <if condition="$village_info['send_user_type'] eq 3">selected="selected"</if> >仅<if condition="$config['open_house_user_name']">亲友<else/>家属</if></option>
                                    </select>
                                    <span class="form_tips">自动催缴微信通知发送人，默认仅发给业主</span>
                                </div>
                                <!--2021/2/6 积分抵扣物业费日期设置-->
                                <if condition="$config['integral_deduction_propertyfee'] eq 1">
                                    <div class="form-group">
                                        <label class="col-sm-1" style="width: 200px"><label for="send_date">积分抵扣物业费提醒日期设置</label></label>
                                        <div style="width: 92%;margin-left: 8%;margin-top: -3px">
                                            每月
                                            <if condition="$village_info['points_deduction_day_set']">
                                                <input type="text" name="points_deduction_day_set" class="input-text" value="{pigcms{$village_info.points_deduction_day_set}" onfocus="WdatePicker({isShowClear:true,dateFmt:'d'})" placeholder="默认不通知" autocomplete="new-password" style="background-color: #FFFFFF!important;" readonly onfocus="this.removeAttribute('readonly');" onblur="this.setAttribute('readonly',true);"/>
                                                <else/>
                                                <input type="text" name="points_deduction_day_set" class="input-text" value="" onfocus="WdatePicker({isShowClear:true,dateFmt:'d'})" placeholder="请选择日期" autocomplete="new-password" style="background-color: #FFFFFF!important;" readonly onfocus="this.removeAttribute('readonly');" onblur="this.setAttribute('readonly',true);"/>
                                            </if>
                                            号
                                            <input type="text" name="points_deduction_time_set" class="input-text" value="{pigcms{$village_info.points_deduction_time_set}" onfocus="WdatePicker({isShowClear:true,dateFmt:'H'})" placeholder="请选择时间" autocomplete="new-password" style="background-color: #FFFFFF!important;" readonly onfocus="this.removeAttribute('readonly');" onblur="this.setAttribute('readonly',true);"/>
                                            点
                                            <span class="form_tips">设置后，在每月该日期提醒业主账户有效积分可抵扣多少物业费</span>
                                        </div>
                                    </div>
                                </if>
                            </div>
                        </div>
                        <div class="clearfix form-actions">
                            <div class="col-md-offset-3 col-md-9">
                                <if condition="in_array(3,$house_session['menus'])">
                                    <button type="submit" class="btn btn-info">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        保存
                                    </button>
                                    <else/>
                                    <button type="button" class="btn btn-info disabled" disabled="disabled" style="cursor: not-allowed;">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        保存
                                    </button>
                                </if>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
<script>
    $('#edit_form').submit(function(){

        var property_price = $("#property_price").val();
        // if(property_price<0){
        //     alert('物业费单价填写不合法！');
        //     return false;
        // }
        var water_price = $("#water_price").val();
        if(water_price<0){
            alert('水费单价填写不合法！');
            return false;
        }
        var electric_price = $("#electric_price").val();
        if(electric_price<0){
            alert('电费单价填写不合法！');
            return false;
        }
        var gas_price = $("#gas_price").val();
        if(gas_price<0){
            alert('燃气费单价填写不合法！');
            return false;
        }
        var park_price = $("#park_price").val();
        if(park_price<0){
            alert('停车费单价填写不合法！');
            return false;
        }
        var property_warn_day = $("#property_warn_day").val();
        if(property_warn_day<0){
            alert('物业提醒时间不合法！');
            return false;
        }

        $('#edit_form button[type="submit"]').prop('disabled',true).html('保存中...');
        if (lock = 1) {
            return;
        }
        lock = 1;


        $.post("{pigcms{:U('Charge_set/village_edit')}",$('#edit_form').serialize(),function(result){
            lock = 0;
            if(result.status == 1){
                window.location.href = "{pigcms{:U('Charge_set/pay_set')}";
            }else{
                $('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
                alert(result.info);
            }
        })
        return false;
    });
</script>

