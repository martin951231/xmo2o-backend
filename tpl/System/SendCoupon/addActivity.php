<include file="Public:header" />
<!---->
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('SendCoupon/index')}">{pigcms{:L_('发券功能')}</a>
            <a href="{pigcms{:U('SendCoupon/addActivity')}" class="on">{pigcms{:L_('新建活动')}</a>
        </ul>
    </div>


    <form id="myform" method="post" action="{pigcms{:U('SendCoupon/addActivity')}" enctype="multipart/form-data">
        <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr>
                <th width="80" colspan="2"><b>基本信息</b></th>
                <td rowspan="10">
                    <img style="margin-left: 40px" src="{pigcms{$static_public}/index_send_coupon_tpl.png">
                </td>
            </tr>
            <tr>
                <th width="80">{pigcms{:L_('活动名称')}</th>
                <td>
                    <input type="text" class="input fl" name="activity_name" size="20" placeholder="{pigcms{:L_('请输入活动名称')}" validate="maxlength:20,required:true" />
                </td>
            </tr>

            <tr>
                <th width="80">{pigcms{:L_('活动时间')}</th>
                <td>
                    <input type="text" class="input-text" name="start_date" style="width:120px;" id="d4311" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" />-
                    <input type="text" class="input-text" name="end_date" style="width:120px;" id="d4311" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" />
                </td>
            </tr>

            <tr>
                <th width="80">{pigcms{:L_('发券条件')}</th>
                <td>
                    <label for="send_type_1"><input type="radio" name="send_type" value="1" id="send_type_1" checked="checked" />{pigcms{:L_('新客条件')}</label>&nbsp;&nbsp;&nbsp;&nbsp;
                    <label for="send_type_2"><input type="radio" name="send_type" value="2" id="send_type_2" />{pigcms{:L_('老客条件')}</label>
                    <span style="margin-left: 30px;color:rgb(0, 51, 102)">新客条件：从未在平台有过完成订单行为的用户才能领取的优惠券；
老客：在平台有过完成订单行为的用户才能领取的优惠券</span>
                </td>
            </tr>
          <tr>
            <th width="80">{pigcms{:L_('领取方式')}</th>
            <td>
              <label for="get_way_1"><input type="radio" name="get_way" value="1" id="send_type_1" checked="checked" />{pigcms{:L_('手动领取')}</label>&nbsp;&nbsp;&nbsp;&nbsp;
              <label for="get_way_2"><input type="radio" name="get_way" value="2" id="send_type_2" />{pigcms{:L_('自动领取')}</label>
              <span style="margin-left: 30px;color:rgb(0, 51, 102)">手动领取：用户需手动点击领取才能获取到优惠券；自动领取：用户打开页面会自动领取符合领取条件的优惠券</span>
            </td>
          </tr>
            <tr>
                <th width="80">{pigcms{:L_('展示页面')}</th>
                <td>
                    <if condition="C('config.system_type') neq 'village'">
                    <label for="show_page_1"><input type="checkbox" name="show_page[]" value="1" id="show_page_1" checked="checked" />{pigcms{:L_('平台首页')}</label>&nbsp;&nbsp;&nbsp;&nbsp;
                    </if>
                    <label for="show_page_2"><input type="checkbox" name="show_page[]" value="2" id="show_page_2" checked="checked" />{pigcms{:L_('外卖首页')}</label>&nbsp;&nbsp;&nbsp;&nbsp;
                    <label for="show_page_4"><input type="checkbox" name="show_page[]" value="4" id="show_page_4" checked="checked" />{pigcms{:L_('商城首页')}</label>&nbsp;&nbsp;&nbsp;&nbsp;
                    <label for="show_page_8"><input type="checkbox" name="show_page[]" value="8" id="show_page_8" checked="checked" />{pigcms{:L_('预约首页')}</label>&nbsp;&nbsp;&nbsp;&nbsp;
                </td>
            </tr>
            <tr id="adver_currency">
                <td width="100">指定城市</td>
                <td colspan="2" class="radio_box">
                    <span class="cb-enable"><label class="cb-enable selected"><span>否</span><input id="yes" type="radio" name="is_city" value="0" checked="checked" /></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>是</span><input id="no" type="radio" name="is_city" value="1" /></label></span>
                </td>
            </tr>
            <tr id="adver_region" <if condition="$coupon['is_city'] eq 0">style="display:none;"</if>>
            <td width="100">选择城市</td>
            <td colspan="2" id="choose_cityareass" province_idss="" city_idss=""></td>
            </tr>

            <tr>
                <th width="80" colspan="2"><b>{pigcms{:L_('样式设置')}</b></th>
            </tr>

            <tr>
                <th width="80">{pigcms{:L_('预览图')}</th>
                <td>
                    <img style="max-width: 400px" src="{pigcms{$defaultBackground}"/>
                </td>
            </tr>

            <tr>
                <th width="80">{pigcms{:L_('背景图片')}</th>
                <td><input type="file" class="input fl" name="background_pic" style="width:200px;" placeholder="请上传图片" /></td>
            </tr>

            <tr>
                <th width="80">{pigcms{:L_('标题')}</th>
                <td><input type="text" class="input fl" name="title" size="20" value="红包领取" /></td>
            </tr>

            <!--<tr>
                <th width="80">{pigcms{:L_('标题样式')}</th>
                <td>
                    <label for="title_show_0"><input type="radio" name="title_show" value="1" id="title_show_0" checked="checked"/>{pigcms{:L_('默认')}</label>&nbsp;&nbsp;&nbsp;&nbsp;
                    <label for="title_show_1"><input type="radio" name="title_show" value="2" id="title_show_1"/>{pigcms{:L_('自定义')}</label>
                </td>
            </tr>-->
            <tr>
                <th class="title_mobile_head_color" width="80">{pigcms{:L_('文字颜色')}</th>
                <td class="info_mobile_head_color"><input type="text" class="input fl" name="color" id="config_mobile_head_color" value="#FFFFFF" size="20" />&nbsp;&nbsp;<a href="javascript:void(0);" id="choose_color_box1" style="line-height:28px;">点击选择颜色</a></td>
            </tr>

            <tr>
                <th class="title_mobile_head_color" width="80">{pigcms{:L_('背景颜色值')}</th>
                <td class="info_mobile_head_color"><input type="text" class="input fl" name="background_color" id="background_color" value="#E13924" size="20" />&nbsp;&nbsp;<a href="javascript:void(0);" id="choose_color_box2" style="line-height:28px;">点击选择颜色</a></td>
            </tr>


            <tr>
                <th class="title_mobile_head_color" colspan="3"><b>{pigcms{:L_('选择优惠券')}</b></th>
            </tr>
            <tr>
                <th class="title_mobile_head_color">{pigcms{:L_('优惠券信息')}</th>
                <td colspan="2">
                    <input type="button" value="添加优惠券" class="button" onclick="select_coupon()" style="margin-left: 0px;"/>
                    <table style="width:800px;margin-top:25px" class="table_form" id='coupon_list'>
						<thead>
						<tr>
                            <th style="text-align: left;">类型</th>
							<th style="text-align: left;">ID</th>
							<th style="text-align: left;">满减条件</th>
							<th style="text-align: left;">有效时间</th>
							<th style="text-align: left;">使用平台</th>
							<th style="text-align: left;">使用类别</th>
                            <th style="text-align: left;">剩余券数</th>
							<th style="text-align: left;">操作</th>
						</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
                </td>
            </tr>



        </table>
        <div class="btn">
            <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
            <input type="reset" value="取消" class="button" />
        </div>
    </form>
</div>
<script type="text/javascript">
    var merCouponids = '{pigcms{$merCouponids|default=""}';
    var platCouponids = '{pigcms{$platCouponids|default=""}';

    $("#yes").click(function(){
        $("#adver_region").hide();
    })
    $("#no").click(function(){
        $("#adver_region").show();
    })

    $("#choose_color_box1").click(function() {
        window.showTopColorPanel("leftmenu_SendCoupon_index", $(this).offset().top, $(this).height(), $(this).offset().left, "config_mobile_head_color");
    });

    $("#choose_color_box2").click(function() {
        window.showTopColorPanel("leftmenu_SendCoupon_index", $(this).offset().top, $(this).height(), $(this).offset().left, "background_color");
    });

    function select_coupon(type = '') {
        var url = '{pigcms{:U("SendCoupon/couponLists")}' + '&type=mer&select_mer=' + merCouponids + '&select_plat=' + platCouponids;
        //var scrollTop = parent.$('#Main_content').scrollTop();
        var scrollTop = 0;
        if(scrollTop == 0){
            var top = '50px';
        }else{
            var top = (scrollTop + 50 -90) + 'px';
        }
        layer.open({
            type: 2,
            area: ['80%', '700px'],
            offset: top,
            content: url,
            btn: ['确定', '取消'],
            yes: function(index, layero) {
                var data = $.parseJSON(localStorage.getItem('check_data'));
                if (data.length > 0) {
                    var html = '';
                    for (var i = 0; i < data.length; i++) {
                        console.log(data[i])
                        html += '<tr>' +
                            '<input type="hidden" name="coupon_ids[]" value="' + data[i].coupon_id + '" />'+
                            '<input type="hidden" name="coupon_type[]" value="' + data[i].type + '" />';
                        if (data[i].type == 'mer') {
                            html += '<td>商家券</td>';
                            merCouponids +=','+data[i].coupon_id;
                        } else {
                            html += '<td>平台券</td>';
                            platCouponids +=','+data[i].coupon_id;
                        }
                        html += '<td>' + data[i].coupon_id + '</td>';
                        html += '<td>' + data[i].discount_txt + '</td>';
                        html += '<td>' + data[i].time + '</td>';
                        html += '<td>' + data[i].platform_txt + '</td>' +
                            '<td>' + data[i].cate_name_txt + '</td>' +
                            '<td>' + data[i].left_num + '</td>' +
                            '<td>' +
                            '<button type="button" onClick="delThis(this)">移除</button>' +
                            '</td>' +
                            '</tr>'
                    }
                    $("#coupon_list").append(html);
                }
                layer.close(index);
            },
            btn2: function(index) {
                layer.close(index);
            }
        });
    }

    function delThis(t, type = '') {
        if (confirm('{pigcms{:L_("你确定要移除此优惠券吗？")}')) {
            $(t).parents("tr").eq(0).remove();
        }
    }

    var hide_submit_msg = true;
</script>
<include file="Public:footer" />