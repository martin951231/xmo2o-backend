<include file="Public:header"/>
<style>
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
	<!-- <div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('Deliver/user_edit')}&uid={pigcms{$_GET['uid']}&frame={pigcms{$_GET['frame']}" class="on">配送员详细信息</a>|
			<a href="{pigcms{:U('Deliver/user_registry')}&uid={pigcms{$_GET['uid']}&frame={pigcms{$_GET['frame']}">注册信息</a>
		</ul>
	</div> -->
	<form id="myform" method="post" action="{pigcms{:U('Deliver/user_edit')}" frame="true" refresh="true">
		<input type="hidden" name="uid" value="{pigcms{$now_user.uid}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="15%">姓名</th>
				<td width="35%"><input type="text" class="input fl" name="name" size="20" validate="maxlength:100,required:true" value="{pigcms{$now_user.name}"/></td>
				<th width="15%">手机号</th>
				<td width="35%">
					<php>if(!$config['is_network_demo_domain']){</php>
						<pigcms:national_phone id="phone_country_type" style="float:left;margin-right:5px;">
							<option value="{pigcms{$vo.code}"  <if condition="$now_user.phone_country_type eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
						</pigcms:national_phone>
						<input type="text" class="input fl" name="phone" size="20" validate="<if condition="!$config['international_phone']">mobile:true,</if>required:true" value="{pigcms{$now_user.phone}"/>
					<php>}</php>
				</td>
			</tr>
			<tr>
				<th width="15%">密码</th>
				<td width="35%"><input type="text" class="input fl" name="pwd" size="20" value="" tips="不修改则不填写" /></td>
				<th width="15%">状态</th>
				<td width="35%" class="radio_box">
					<span class="cb-enable"><label class="cb-enable <if condition="$now_user['status'] eq 1">selected</if>"><span>正常</span><input type="radio" name="status" value="1"  <if condition="$now_user['status'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_user['status'] eq 0">selected</if>"><span>禁止</span><input type="radio" name="status" value="0"  <if condition="$now_user['status'] eq 0">checked="checked"</if>/></label></span>
				</td>
			</tr>
            <tr>
                <th width="15%">是否开启扔回功能</th>
                <td width="35%" class="radio_box">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_user['is_cancel_order'] eq 1">selected</if>"><span>开启</span><input type="radio" name="is_cancel_order" value="1"  <if condition="$now_user['is_cancel_order'] eq 1">checked="checked"</if>/></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_user['is_cancel_order'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="is_cancel_order" value="0"  <if condition="$now_user['is_cancel_order'] eq 0">checked="checked"</if>/></label></span>
                </td>
                <th width="15%">配送费抽成比例</th>
                <td width="35%"><input type="text" class="input fl" name="take_percent" size="20" value="{pigcms{$now_user.take_percent}" tips="请填写0-100的数字，填写5.0即5%,0.5即5‰；即平台对配送员的抽成，计算方式为：配送费*抽成比例" /></td>
            </tr>
			<tr>
				<th width="15%">配送员手中待配送完成的订单数小于多少单后才可继续抢单</th>
				<td width="35%"><input type="number" class="input fl" name="max_num" size="20" value="{pigcms{$now_user.max_num}" tips="只可填写大于等于0的整数，0表示不限制" /></td>
                <th width="15%">排序</th>
                <td width="35%"><input type="text" class="input fl" name="sort" size="20" value="{pigcms{$now_user.sort}" tips="正整数，数字越大排在越前" /></td>
			</tr>
			<tr>
			 	<th width="15%">所在地</th>

				<td id="choose_cityarea" colspan=3 street_id="{pigcms{$now_user.street_id}"  province_id="{pigcms{$now_user.province_id}" city_id="{pigcms{$now_user.city_id}" area_id="{pigcms{$now_user.area_id}" circle_id="-1"></td>
			<tr>

            <if condition="$now_user['face_recognition_images']">
                <tr>
                    <th width="15%">人脸认证</th>
                    <td colspan=3>
                        <a target="_blank" href="{pigcms{$now_user.face_recognition_images|replace_file_domain=###}"><img src="{pigcms{$now_user.face_recognition_images|replace_file_domain=###}" style="max-height: 100px"/></a>
                        <a href="javascript:void(0);" class="delete_row" parameter="uid={pigcms{$now_user.uid}" url="{pigcms{:U('Deliver/delFaceImg')}">删除</a>
                    </td>
                </tr>
            </if>
			<tr>
				<th width="15%">配送范围</th>
				<td>
                    <input type="hidden" id="delivery_range_polygon" name="delivery_range_polygon" value="{pigcms{$now_user.delivery_range_polygon}">
                    <select id="delivery_range_type" name="delivery_range_type" >
                    <option value="0" <if condition="$now_user['delivery_range_type'] eq 0">selected</if>>半径范围</option>
                    <option value="1" <if condition="$now_user['delivery_range_type'] eq 1">selected</if>>自定义范围</option>
                    </select>
                </td>
                <th width="15%" class="range" <if condition="$now_user['delivery_range_type'] eq 1">style="display:none"</if>>半径距离</th>
                <td class="range" <if condition="$now_user['delivery_range_type'] eq 1">style="display:none"</if>>
                    <input type="text" class="input fl" name="range" size="20" validate="required:true" value="{pigcms{$now_user.range}"/>公里
                </td>
                <th width="15%" class="diy" id="diy_label" <if condition="$now_user['delivery_range_type'] eq 0">style="display:none"</if>>选择自定义范围</th>
                <td class="diy" id="diy_input" <if condition="$now_user['delivery_range_type'] eq 0">style="display:none"</if>>
                    <select name="custom_id">
                        <option area="0">请选择</option>
                        <volist name="deliverCustoms" id="dc">
                            <option area="{pigcms{$dc.area_id}" value="{pigcms{$dc.id}" for-map="{pigcms{$dc.delivery_range_polygon_for_map}" <if condition="$now_user['custom_id'] eq $dc['id']">selected</if> <if condition="$dc['show'] eq 0">style="display:none"</if>>{pigcms{$dc.name}</option>
                        </volist>
                    </select>
                    <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="您可在配送管理-配送配置-配送区域管理Tab页中添加或管理配送员的配送区域">
                    <em for="long_lat" generated="true" class="error tips">必填项</em>
                </td>
			</tr>
			<tr>
				<th width="15%">配送员常驻地区</th>
				<td width="35%">
					<input type="text" class="input fl" readonly="readonly" name="adress" id="adress" validate="required:true" value="{pigcms{$now_user.site}"/>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="无法编辑，拖拽地图后会自动填充内容"/>
				</td>
				<th width="15%">配送员经纬度</th>
				<td width="35%" class="radio_box"><input class="input fl" size="20" name="long_lat" id="long_lat" type="text" validate="required:true" value="{pigcms{$now_user.lng},{pigcms{$now_user.lat}"/><span class="sure" style="border: solid 1px #ddd;padding: 5px;margin-left: 5px;cursor: pointer;">确定使用</span></td>
			</tr>
			<if condition="$config['open_deliver_level']">
			<tr>
				<th width="15%">配送员类型</th>
				<td width="35%">
					  <span class="cb-enable"><label class="cb-enable <if condition="$now_user['user_type'] eq 0">selected</if>"><span>自有配送员</span><input type="radio" name="user_type" value="0"  <if condition="$now_user['user_type'] eq 0">checked="checked"</if>/></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_user['user_type'] eq 1">selected</if>"><span>兼职配送员</span><input type="radio" name="user_type" value="1"  <if condition="$now_user['user_type'] eq 1">checked="checked"</if>/></label></span>
				</td>
				<th width="15%">配送员等级积分</th>
				<td width="35%"><input type="text" class="input fl" name="score" value="{pigcms{$now_user.score}" />
				<p style="line-height: 4px;margin-bottom: 20px;">当前等级：{pigcms{$now_user.level_name}</p>
				<a href="javascript:void(0);" style="border: 1px solid #ccc;padding: 4px;" onclick="window.o2oTop.artiframe('{pigcms{:U('Deliver/user_score_log',array('uid'=>$now_user['uid']))}','积分变动日志',680,560,true,false,false,null,'user_score_log',true);">积分变动日志</a></td>
			</tr>
			</if>
			<tr>
				<th width="15%">配送交通工具</th>
				<td width="35%">
					<select name="traffic">
	                    <option value="" selected>不选择</option>
	                    <volist name="traffic" id="vo">
	                    <option value="{pigcms{$key}" <if condition="$key eq $now_user['traffic']">selected="selected"</if>>{pigcms{$vo.text}</option>
	                	</volist>
                    </select>
				</td>
				<th width="15%">
					配送时速
				</th>
				<td width="35%">
					<input type="text" class="input fl" name="speed_per_hour" size="20" validate="required:true" value="{pigcms{$now_user.speed_per_hour}"/>km/h
				</td>
			</tr>

            <tr>
                <th width="15%">配送单类型</th>
                <td colspan=3>
                    <div class="show-select-value" style="">
                        <div class="select-list">
                            <if condition="strpos($now_user['order_type'],'1') !== false">
                                <div class="item" data-id="1">商家单<span class="icon">×</span></div>
                            </if>
                            <if condition="strpos($now_user['order_type'],'2') !== false">
                                <div class="item" data-id="2">帮买单<span class="icon">×</span></div>
                            </if>
                            <if condition="strpos($now_user['order_type'],'3') !== false">
                                <div class="item" data-id="3">帮送单<span class="icon">×</span></div>
                            </if>
                            <if condition="strpos($now_user['order_type'],'4') !== false">
                                <div class="item" data-id="4">社区团购<span class="icon">×</span></div>
                            </if>
                        </div>
                        <span class="select-icon"></span>
                        <ul class="list">
                            <li class="item" data-id="1">商家单</li>
                            <li class="item" data-id="2">帮买单</li>
                            <li class="item" data-id="3">帮送单</li>
                            <li class="item" data-id="4">社区团购</li>
                        </ul>

                    </div>
                    <input type="hidden" name="order_type" value="{pigcms{$now_user['order_type']}" validate="required:true">
                </td>
            <tr>

            <tr>
                <th width="15%">是否显示隐私号</th>
                <td width="35%" class="radio_box">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_user['show_phone_status'] eq 1">selected</if>"><span>显示</span><input type="radio" name="show_phone_status" value="1"  <if condition="$now_user['show_phone_status'] eq 1">checked="checked"</if>/></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_user['show_phone_status'] eq 0">selected</if>"><span>隐藏</span><input type="radio" name="show_phone_status" value="0"  <if condition="$now_user['show_phone_status'] eq 0">checked="checked"</if>/></label></span>
                </td>
            </tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<div class="modal-content" style="width:100%;">
		<div class="modal-header no-padding" style="width:100%;">
			<div class="table-header" style="padding-left:10px;">拖动红色图标，经纬度框内将自动填充经纬度。</div>
		</div>
		<div class="modal-body no-padding" style="width:100%;">
			<form id="map-search" style="margin:10px;">
				<input id="map-keyword" type="textbox" style="width:300px;border:1px solid #ccc;height:24px;line-height:24px;padding-left:6px;" placeholder="尽量填写城市、区域、街道名" value="{pigcms{$now_user.site}"/>
				<input type="submit" value="搜索" class="button" style="margin-left:0px;"/>
			</form>
			<div style="width:750px;height:400px;margin-left:10px;" id="cmmap"></div>
		</div>
	</div>
	<script type="text/javascript">
	var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}";
    var default_lng = '{pigcms{$now_user.lng}';
    var default_lat = '{pigcms{$now_user.lat}';
    var count = 1;
    var default_deliver_id = '{pigcms{$now_user.custom_id}';
    var disable_edit_map = 1;
    var zoom = 14;

    $('#diy_input>select').on('change', function () {
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
    <if condition="$config['tianditu_web_key']">
        <script src="https://api.tianditu.gov.cn/api?v=4.0&tk={pigcms{$config.tianditu_web_key}" type="text/javascript"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/map_world.js"></script>
    <else />
        <if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
            <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places,drawing&key={pigcms{$config.google_map_ak}&language=en"></script>
            <script type="text/javascript">var polygonMap = 1;</script>
            <script type="text/javascript" src="{pigcms{$static_path}js/map_google.js?t=1111"></script>
            <else />
            <!--<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>-->
            <script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>
            <script type="text/javascript" src="https://api.map.baidu.com/library/DrawingManager/1.4/src/DrawingManager_min.js"></script>
            <script type="text/javascript">var polygonMap = 1;</script>
            <script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=3"></script>
        </if>
    </if>
<include file="Public:footer"/>