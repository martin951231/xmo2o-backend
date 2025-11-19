<include file="Public:header"/>
<style>
    .sub_mch{
        display:none
    }
    .show-select-value{position: relative;float: left;border: 1px solid #d5d5d5;width: 400px;min-height: 30px;border-radius: 2px;background: #fff;}
    .show-select-value .select-list{margin: 10px}
    .show-select-value .select-list .item{float: left;border:1px solid #d5d5d5;background: #f2f2f2;padding:0 5px;margin: 0 10px 10px 0;border-radius: 2px;}
    .show-select-value .select-list .item .icon{padding: 0 5px;font-weight: bold;cursor: pointer;}
    .show-select-value .select-icon{position: absolute;right: 10px;top: 17px;vertical-align: middle;width: 0;height: 0;border: 5px solid transparent;border-top-color:#666;}
    .show-select-value .list{position: absolute;bottom: -100px;width: 400px;border: 1px solid #1686ef;background: #fff;border-radius: 2px;display: none;}
    .show-select-value .list.active{display: block;}
    .show-select-value .list .item{padding: 2px 10px}
    .show-select-value .list .item:hover{background: #1686ef;color: #fff;}
</style>
<div id="nav" class="mainnav_title">
    <ul>
        <a href="{pigcms{:U('Deliver/handleRegister')}&id={pigcms{$_GET['id']}&frame={pigcms{$_GET['frame']}" class="on">配送员审核信息</a>|
        <!-- <a href="{pigcms{:U('Deliver/handleRegisterFields')}&id={pigcms{$_GET['id']}&frame={pigcms{$_GET['frame']}">注册信息</a> -->
    </ul>
</div>
<form id="myform" method="post" action="{pigcms{:U('Deliver/handleRegister')}" frame="true" refresh="true">
    <input type="hidden" name="id" value="{pigcms{$register.id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="15%">姓名</th>
            <td colspan="3">{pigcms{$register.truename}</td>
        </tr>
        <tr>
            <th width="15%">手机号</th>
            <td colspan="3">{pigcms{$register.phone}</td>
        </tr>
        <tr>
            <th width="15%">身份证号</th>
            <td colspan="3">{pigcms{$register.card_number}</td>
        </tr>
        <tr>
            <th width="15%">意向工作城市</th>
            <td colspan="3">{pigcms{$register.area_name|default='-'}</td>
        </tr>
        <tr>
            <th width="15%">意向工作地址</th>
            <td colspan="3">{pigcms{$register.adress|default='-'}</td>
        </tr>
        <if condition="$register['face_recognition_images']">
            <tr>
                <th width="15%">人脸认证</th>
                <td colspan="3">
                    <a target="_blank" href="{pigcms{$register.face_recognition_images|replace_file_domain=###}"><img src="{pigcms{$register.face_recognition_images|replace_file_domain=###}" style="max-height: 100px"/></a>
                </td>
            </tr>
        </if>
        <tr>
            <th width="15%">申请时间</th>
            <td colspan="3">{pigcms{$register.apply_time|date='Y-m-d H:i:s',###}</td>
        </tr>
        <tr>
            <th width="15%">IP</th>
            <td colspan="3">{pigcms{$register.ip}</td>
        </tr>
        <tr>
            <th width="15%">审核</th>
            <td colspan="3">
                <select id="handle_status" name="handle_status">
                    <option value="1" selected>通过</option>
                    <option value="2">驳回</option>
                </select>
            </td>
        </tr>

        <tr>
            <th width="15%">审核备注</th>
            <td colspan="3"><textarea name="handle_reason" style="width: 60%"></textarea></td>
        </tr>

        <tr class="deliver_user_detail">
            <th colspan="4"><b>配送员详细信息</b></th>
        </tr>
        <tr class="deliver_user_detail">
            <th width="15%">姓名</th>
            <td width="35%"><input type="text" class="input fl" name="name" size="20" validate="maxlength:100,required:true" value="{pigcms{$register.truename}" disabled/></td>
            <th width="15%">手机号</th>
            <td width="35%">
                <pigcms:national_phone id="phone_country_type" style="float:left;margin-right:5px;">
                    <option value="{pigcms{$vo.code}"  <if condition="$config.qcloud_sms_default_country eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
                </pigcms:national_phone>
                <input disabled type="text" class="input fl" name="phone" size="20" validate="<if condition="!$config['international_phone']">mobile:true,</if>required:true" value="{pigcms{$register.phone}"/>
            </td>
        </tr>
        <tr class="deliver_user_detail">
            <th width="15%">密码</th>
            <td width="35%"><input type="text" class="input fl" name="pwd" size="20" value=""  validate="required:true" tips="不修改则不填写"/></td>
            <th width="15%">状态</th>
            <td width="35%" class="radio_box">
                <span class="cb-enable"><label class="cb-enable <if condition="$now_user['status'] eq 1">selected</if>"><span>正常</span><input type="radio" name="status" value="1"  <if condition="$now_user['status'] eq 1">checked="checked"</if>/></label></span>
                <span class="cb-disable"><label class="cb-disable <if condition="$now_user['status'] eq 0">selected</if>"><span>禁止</span><input type="radio" name="status" value="0"  <if condition="$now_user['status'] eq 0">checked="checked"</if>/></label></span>
            </td>
        </tr>
        <tr class="deliver_user_detail">
            <th width="15%">是否开启扔回功能</th>
            <td width="35%" class="radio_box">
                <span class="cb-enable"><label class="cb-enable <if condition="$now_user['is_cancel_order'] eq 1">selected</if>"><span>开启</span><input type="radio" name="is_cancel_order" value="1"  <if condition="$now_user['is_cancel_order'] eq 1">checked="checked"</if>/></label></span>
                <span class="cb-disable"><label class="cb-disable <if condition="$now_user['is_cancel_order'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="is_cancel_order" value="0"  <if condition="$now_user['is_cancel_order'] eq 0">checked="checked"</if>/></label></span>
            </td>
            <th width="15%">配送费抽成比例</th>
            <td width="35%"><input type="text" class="input fl" name="take_percent" size="20" value="{pigcms{$now_user.take_percent}" tips="请填写0-100的数字，填写5.0即5%,0.5即5‰；即平台对配送员的抽成，计算方式为：配送费*抽成比例" /></td>
        </tr>
        <tr class="deliver_user_detail">
            <th width="15%">配送员手中待配送完成的订单数小于多少单后才可继续抢单</th>
            <td width="35%"><input type="number" class="input fl" name="max_num" size="20" value="{pigcms{$now_user.max_num}" tips="只可填写大于等于0的整数，0表示不限制" /></td>
            <th width="15%">排序</th>
            <td width="35%"><input type="text" class="input fl" name="sort" size="20" value="{pigcms{$now_user.sort}" tips="正整数，数字越大排在越前" /></td>
        </tr>
        <tr class="deliver_user_detail">
            <th width="15%">所在地</th>
            <td id="choose_cityarea" province_id='{pigcms{$register.province_id}' city_id='{pigcms{$register.city_id}' colspan=3 show_please_select='1' circle_id="-1" street_id="0"></td>
        <tr>
        <tr class="deliver_user_detail">
            <th width="15%">配送范围</th>
            <td>
                <input type="hidden" id="delivery_range_polygon" name="delivery_range_polygon" value="">
                <select id="delivery_range_type" name="delivery_range_type" >
                    <option value="0" selected>半径范围</option>
                    <option value="1">自定义范围</option>
                </select>
            </td>
            <th width="15%" class="range" id="range_label">半径距离</th>
            <td class="range" id="range_input">
                <input type="text" class="input fl" name="range" size="20" validate="required:true" value="5"/>公里
            </td>
            <th width="15%" class="diy" id="diy_label" style="display: none">选择自定义范围</th>
            <td class="diy" id="diy_input" style="display: none">
                <select name="custom_id">
                    <option area="0">请选择</option>
                    <volist name="deliverCustoms" id="dc">
                        <option area="{pigcms{$dc.area_id}" value="{pigcms{$dc.id}" for-map="{pigcms{$dc.delivery_range_polygon_for_map}" <if condition="$dc['show'] eq 0">style="display:none"</if>>{pigcms{$dc.name}</option>
                    </volist>
                </select>
                <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="您可在配送管理-配送配置-配送区域管理Tab页中添加或管理配送员的配送区域"/>
                <em for="long_lat" generated="true" class="error tips">必填项</em>
            </td>
        </tr>
        <tr class="deliver_user_detail">
            <th width="15%">配送员常驻地区</th>
            <td width="35%">
                <input type="text" class="input fl" readonly="readonly" name="adress" value="{pigcms{$register.adress}" id="adress" validate="required:true"/>
                <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="无法编辑，拖拽地图后会自动填充内容"/>
            </td>
            <th width="15%">配送员经纬度</th>
            <td width="35%" class="radio_box"><input class="input fl" size="20" name="long_lat" id="long_lat" <if condition="abs($register['lng']) gt 0">value="{pigcms{$register.lng},{pigcms{$register.lat}"</if> type="text" validate="required:true"/><span class="sure" style="border: solid 1px #ddd;padding: 5px;margin-left: 5px;cursor: pointer;">确定使用</span></td>
        </tr>
        <if condition="$config['open_deliver_level']">
            <tr class="deliver_user_detail">
                <th width="15%">配送员等级积分</th>
                <td colspan="3" width="35%"><input type="text" class="input fl" name="score"/></td>
            </tr>
        </if>
        <tr class="deliver_user_detail">
            <th width="15%">配送交通工具</th>
            <td width="35%">
                <select name="traffic">
                    <option value="" selected>不选择</option>
                    <volist name="traffic" id="vo">
                        <option value="{pigcms{$key}">{pigcms{$vo.text}</option>
                    </volist>
                </select>
            </td>
            <th width="15%">
                配送时速
            </th>
            <td width="35%">
                <input type="text" class="input fl" name="speed_per_hour" size="20" value="" validate="required:true"/>km/h
            </td>
        </tr>

        <tr>
            <th width="15%">配送单类型</th>
            <td colspan=3>
                <div class="show-select-value" style="">
                    <div class="select-list">
                        <div class="item" data-id="1">商家单<span class="icon">×</span></div>
                        <div class="item" data-id="2">帮买单<span class="icon">×</span></div>
                        <div class="item" data-id="3">帮送单<span class="icon">×</span></div>
                        <div class="item" data-id="4">社区团购<span class="icon">×</span></div>
                    </div>
                    <span class="select-icon"></span>
                    <ul class="list">
                        <li class="item" data-id="1">商家单</li>
                        <li class="item" data-id="2">帮买单</li>
                        <li class="item" data-id="3">帮送单</li>
                        <li class="item" data-id="4">社区团购</li>
                    </ul>
                </div>
                <input type="hidden" name="order_type" value="1,2,3,4" validate="required:true">
            </td>
        <tr>


    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<div class="modal-content deliver_user_detail" style="width:100%;">
    <div class="modal-header no-padding" style="width:100%;">
        <div class="table-header" style="padding-left:10px;">拖动红色图标，经纬度框内将自动填充经纬度。</div>
    </div>
    <div class="modal-body no-padding" style="width:100%;">
        <form id="map-search" style="margin:10px;">
            <input id="map-keyword" type="textbox" style="width:300px;border:1px solid #ccc;height:24px;line-height:24px;padding-left:6px;" placeholder="尽量填写城市、区域、街道名"/>
            <input type="submit" value="搜索" class="button" style="margin-left:0px;"/>
        </form>
        <div style="width:750px;height:400px;margin-left:10px;" id="cmmap"></div>
    </div>
</div>
<script type="text/javascript">
    $('#handle_status').on('change', function () {
        var handle_status = $(this).val();
        if (handle_status == 1) {
            $('.deliver_user_detail').show();
        } else {
            $('.deliver_user_detail').hide();
        }
    })


    var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}";
    var default_lng = 117.2285309;
    var default_lat = 31.8291397;
    var count = 1;
    var default_deliver_id = 0;
    var disable_edit_map = 1;

    var ignoreValidate = ":hidden";

    $('#diy_input>select').on('change',function(){
        $('#delivery_range_polygon').val($(this).find('option:selected').attr('for-map'));
        paint_map()
    })

    $('#choose_city').live('change', function () {
        update_deliver_customs_select();
    })

    function update_deliver_customs_select() {
        var city_id = $('#choose_city').val();
        if (city_id == 0 || city_id == undefined) {
            $('.diy').find('option').show();
        } else {
            $('.diy').find('option').removeAttr('selected').hide()
            $('.diy').find('option[area=' + city_id + ']').show()
        }
        if(count == 1 && default_deliver_id > 0){
            $('.diy').find('option[value=' + default_deliver_id + ']').attr('selected', 'selected');
        }
        count++;
    }
    $(document).on('click', '.show-select-value', function(){
        if ($(this).find('.list').attr('class').indexOf('active') != -1) {
            $(this).find('.list').removeClass('active');
            return
        }
        $(this).find('.list').addClass('active').css('bottom', -$(this).find('.list').height()-2)
    })

    $(document).on('click', '.show-select-value .list .item', function(){
        var label_id = $(this).data('id');

        var label_ids = $('input[name=order_type]').val();
        if (label_ids != '') {
            var label_ids_arr = label_ids.split(',');
            var selected = 0;
            $.each(label_ids_arr,function (index,j) {
                if(label_id == j){
                    selected = 1;
                }
            })
            if (selected==0) {
                label_ids_arr.push(label_id);
                label_ids = label_ids_arr.join(',');
                $('input[name=order_type]').val(label_ids);
            }else{
                $('.show-select-value .list').removeClass('active');
                return false;
            }
        }else{
            $('input[name=order_type]').val(label_id);
        }
        $('.show-select-value .select-list').append('<div class="item" data-id="'+label_id+'" >'+ $(this).html() +'<span class="icon">×</span></div>');
        $('.show-select-value .list').removeClass('active');
        return false
    })

    $(document).on('click', '.show-select-value .select-list .item .icon', function(){
        console.log($(this).parent().attr("data-id"));
        var label_id = $(this).parent().attr("data-id");
        var label_ids = $('input[name=order_type]').val();
        var label_ids_arr = label_ids.split(',');

        label_ids_arr.splice($.inArray(label_id,label_ids_arr),1);
        label_ids = label_ids_arr.join(',');
        console.log(label_ids)
        $('input[name=order_type]').val(label_ids);
        $(this).parents('.item').remove()
        return false
    })
</script>
<if condition="$default_city['area_lng']">
    <script type="text/javascript">
        default_lng = {pigcms{$default_city.area_lng};
        default_lat = {pigcms{$default_city.area_lat};
    </script>
</if>
<if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places,drawing&key={pigcms{$config.google_map_ak}&language=en"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/map_google.js"></script>
    <else />
    <script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>
    <script type="text/javascript" src="https://api.map.baidu.com/library/DrawingManager/1.4/src/DrawingManager_min.js"></script>
    <script type="text/javascript">var polygonMap = 1;</script>
    <script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=1"></script>
</if>

<include file="Public:footer"/>