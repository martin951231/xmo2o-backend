<include file="Public:header"/>
<style>
    .layui-btn-normal{
        color: #fff;
        margin-top: 5px;
        background-color: #009688;
        border-color: #009688;
        min-width: 52px;
        padding: 0px 2px;
    } 
    .layui-btn-radius{
        border-radius: 5px;
    }
    .layui-btn-danger{
        color: #fff;
        margin-top: 5px;
        background-color: #FF5722;
        border-color: #FF5722;
    }
</style>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Deliver/deliverList')}" class="on">配送列表</a>|
					<a href="{pigcms{:U('Deliver/desk')}" target="_blank">调度控制台</a>|
					<a href="{pigcms{:U('Deliver/deliver_model')}">调度模式设置</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<div class="deliver_search">
                            <if condition="$admin_area neq 3">
                                <div id="choose_pca" street_id="{pigcms{$_GET.street_id}" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline;margin-bottom: 10px;"></div>
                            </if>　　　

                            搜索: <input style="margin-bottom: 10px;" type="text" name="keyword" id="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                            <select name="searchtype" id="searchtype" style="margin-bottom: 10px;">
                                <option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>订单编号</option>
                                <option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>流水单号</option>
                                <option value="s_name" <if condition="$_GET['searchtype'] eq 's_name'">selected="selected"</if>>店铺名称</option>
                                <option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>客户名称</option>
                                <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>客户电话</option>
                                <option value="d_name" <if condition="$_GET['searchtype'] eq 'd_name'">selected="selected"</if>>配送员姓名</option>
                            </select>
							<!-- <span>
								店铺名称：
									<select id="store" name="store">
										<option value="0">全部</option>
										<volist name="stores" id="store">
											<option <if condition="$selectStoreId eq $store['store_id']">selected</if> value="{pigcms{$store['store_id']}">{pigcms{$store['name']}</option>
										</volist>
									</select>
							</span> -->
							<span class="mar_l_10" style="margin-bottom: 10px;">订单来源：
                                <select id="order_from" name="order_from">
                                    <option value="-1" <if condition="$order_from eq -1">selected</if> >全部</option>
                                    <php>if ($config['mall_alias_name_new']) {</php>
										<option value="8" <if condition="$order_from eq 8">selected</if> >{pigcms{$config.mall_alias_name_new}</option>
                                    <php>}</php>
									<php>if ($config['shop_alias_name']) {</php>
										<option value="0" <if condition="$order_from eq 0">selected</if> >{pigcms{$config.shop_alias_name}</option>
									<php>}</php>
									<!-- <php>if ($config['mall_alias_name']) {</php>
										<option value="12" <if condition="$order_from eq 12">selected</if> >{pigcms{$config.mall_alias_name}</option>
									<php>}</php> -->
									<php>if ($config['eleme_app_key']) {</php>
										<option value="1" <if condition="$order_from eq 1">selected</if> >饿了么</option>
									<php>}</php>
									<php>if ($config['meituan_sign_key']) {</php>
										<option value="2" <if condition="$order_from eq 2">selected</if> >美团</option>
									<php>}</php>
									<php>if ($config['paotui_service']) {</php>
										<option value="3" <if condition="$order_from eq 3">selected</if> >帮送</option>
										<option value="4" <if condition="$order_from eq 4">selected</if> >帮买</option>
									<php>}</php>
                                    <if condition="$config['village_group_delivery_switch'] eq 1">
										<option value="10" <if condition="$order_from eq 10">selected</if> >{pigcms{$config['village_group_alias']}</option>
                                    </if>
                                </select>
							</span>
                            <!--<span class="mar_l_10">配送状态：<select id="status" name="deliver" style="margin-bottom: 10px;">
                                        <option value="-1" <if condition="$status eq -1">selected</if> >全部</option>
                                        <option value="1" <if condition="$status eq 1">selected</if> >等待接单</option>
                                        <option value="2" <if condition="$status eq 2">selected</if> >已接单</option>
                                        <option value="3" <if condition="$status eq 3">selected</if> >已取货</option>
                                        <option value="4" <if condition="$status eq 4">selected</if> >开始配送</option>
                                        <option value="5" <if condition="$status eq 5">selected</if> >已完成</option>
                                        <option value="6" <if condition="$status eq 6">selected</if> >已评价</option>
                                        <option value="0" <if condition="$status eq 0">selected</if> >已失效</option>
                                    </select>
                            </span>-->
                            <span class="mar_l_10">配送状态：<select id="deliver_status" name="deliver_status" style="margin-bottom: 10px;">
                                        <option value="-1" <if condition="$deliver_status eq -1">selected</if> >全部</option>
                                    <option value="1" <if condition="$deliver_status eq 1">selected</if> >等待接单</option>
                                    <option value="2" <if condition="$deliver_status eq 2">selected</if> >已接单</option>
                                    <option value="3" <if condition="$deliver_status eq 3">selected</if> >我已到店</option>
                                    <option value="4" <if condition="$deliver_status eq 4">selected</if> >我已取货</option>
                                    <option value="5" <if condition="$deliver_status eq 5">selected</if> >我已送达</option>
                                    <option value="6" <if condition="$deliver_status eq 6">selected</if> >已评价</option>
                                    <option value="0" <if condition="$deliver_status eq 0">selected</if> >已失效</option>
                                    </select>
                            </span>
							<br/>
                            <span style="margin-bottom: 10px;"><select id="timetype" name="timetype">
                                        <option value="0" <if condition="$timetype eq 0">selected</if> >店员接单多少分钟以上</option>
                                        <option value="1" <if condition="$timetype eq 1">selected</if> >配送员接单多少分钟以上</option>
                                        <option value="2" <if condition="$timetype eq 2">selected</if> >配送时长多少分钟以上</option>
                                        <option value="3" <if condition="$timetype eq 3">selected</if> >支付到配送完多少分钟以上</option>
                                        <option value="4" <if condition="$timetype eq 4">selected</if> >配送超时多少分钟以上</option>
                                    </select>
                                    <input type="text" id="time" name="time" <if condition="$time">value="{pigcms{$time}" </if> placeholder="单位：分钟">
                            </span>
<!--							<span class="mar_l_10" style="margin-bottom: 10px;">用户手机号：<input type="text" id="phone" name="phone" <if condition="$phone">value="{pigcms{$phone}" </if>></span>-->
							<span style="margin-bottom: 10px;">开始时间筛选：</span>
							<div style="display:inline-block;margin-bottom: 10px;">
								<select class='custom-date' id="time_value" name='select'>
									<option value='1' <if condition="$day eq 1">selected</if>>今天</option>
									<option value='7' <if condition="$day eq 7">selected</if>>7天</option>
									<option value='30' <if condition="$day eq 30">selected</if>>30天</option>
									<option value='180' <if condition="$day eq 180">selected</if>>180天</option>
									<option value='365' <if condition="$day eq 365">selected</if>>365天</option>
									<option value='custom' <if condition="$period">selected</if>>{pigcms{$period|default='自定义'}</option>
								</select>
							</div>
							<span class="mar_l_10"><button id="search" class="button">搜索</button></span>
						</div>
					</td>
                    <td>
                        <a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出订单</a>
                    </td>
<!--                    <td>-->
<!--                        <a href="{pigcms{:U('Deliver/export', array('status' => $status, 'timetype' => $timetype, 'time' => $time, 'day' => $day, 'period' => $period, 'order_from' => $order_from, 'province_id'=>$province_id, 'city_id' => $city_id, 'area_id' => $area_id, 'keyword' => $keyword, 'searchtype'=>$searchtype))}" class="button" style="float:right;margin-right: 10px;">导出订单</a>-->
<!--                    </td>-->
				</tr>
                <tr>
                    <td>
                    <b>该筛选条件下总记录数：{pigcms{$res_count.0.count}</b>　
                    <b>总配送费：{pigcms{$config['Currency_symbol']}{pigcms{$res_count.0.total_freight|getFormatNumber|default='0.00'}</b>　
                    <b>订单总价格：{pigcms{$config['Currency_symbol']}{pigcms{$res_count.0.total_money|getFormatNumber|default='0.00'}</b>　
                    <b>小费总额：{pigcms{$config['Currency_symbol']}{pigcms{$res_count.0.tip_price|getFormatNumber|default='0.00'}</b>　
                    <b>配送员佣金总额：{pigcms{$config['Currency_symbol']}{pigcms{$platform_count.0.total_deliver_user_fee|getFormatNumber|default='0.00'}</b>　
                    <b>平台服务费总额：{pigcms{$config['Currency_symbol']}{pigcms{$res_count.0.total_platform_fee|getFormatNumber|default='0.00'}</b>　
                </tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
                                <th>配送ID</th>
                                <th>订单号</th>
								<th>业务</th>
								<!--th>配送员类型</th-->
								<th>店铺名称</th>

                                <!--<th>取单号</th>-->
								<th>客户信息</th>
								<!-- <th>支付方式</th>
								<th>支付状态</th> -->
								<th>订单价格</th>
								<th>应收现金</th>
								<th>配送状态</th>
								<th>配送员信息</th>
								<th>开始时间</th>
								<th>送达时间</th>
								<th>店员接单时长</th>
								<th>配送员接单时长</th>
								<th>配送时长</th>
								<th>支付到送完时长</th>
								<th>接单类型</th>
								<th>分配配送员</th>
								<th>修改配送状态</th>
								<th>操作</th>
								<!--th>创建时间</th-->
								
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($supply_info)">
								<volist name="supply_info"  id="vo">
									<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        <td width="50">{pigcms{$vo.supply_id}</td>
                                        <td width="100" class="u-text-wrap">
                                            {pigcms{$vo.order_sn}
                                            <if condition="$vo.fetch_number gt 0"><br/>取单号：{pigcms{$vo.fetch_number}</if>
                                        </td>
                                        
										<td width="60">
                                            <if condition="$vo['order_from'] eq 0">
                                                {pigcms{$config.shop_alias_name}
                                                <elseif condition="$vo['order_from'] eq 12" />
                                                {pigcms{$config.mall_alias_name}
                                                <elseif condition="$vo['order_from'] eq 8" />
                                                {pigcms{$config.mall_alias_name_new}
                                                <elseif condition="$vo['order_from'] eq 1" />
                                                饿了么
                                                <elseif condition="$vo['order_from'] eq 2" />
                                                美团
                                                <elseif condition="$vo['order_from'] eq 3" />
                                                    帮我送
                                                    <if condition="$vo['third_type'] eq 'maiyatian'">
                                                        <button type="button" class="layui-btn layui-btn-danger layui-btn-radius">麦芽田</button>
                                                        <button type="button" class="layui-btn layui-btn-normal layui-btn-radius">{pigcms{$vo['order_source']}</button>
                                                    </if>
                                                <elseif condition="$vo['order_from'] eq 4 OR $vo['order_from'] eq 5" />
                                                帮我买
                                                <elseif condition="$vo['order_from'] eq 10" />
                                                {pigcms{$config['village_group_alias']}
												<elseif condition="$vo['order_from'] eq 12" />
												{pigcms{$config.mall_alias_name}
                                            </if>
                                        </td>
										<!--td width="50">{pigcms{$vo.group}</td-->
										<td width="80"><if condition="$vo['item'] eq 3 OR $vo['order_from'] eq 10">{pigcms{$vo.from_site}<else/>{pigcms{$vo.storename}</if></td>
                                        <!--<td width="80">{pigcms{$vo.fetch_number}</td>-->
										<td width="150">{pigcms{$vo.username}<br/>{pigcms{$vo.userphone|phone_demo_show}<br/>{pigcms{$vo.aim_site}</td>
                                        <!-- <td width="60">
                                            {pigcms{$vo.paytypestr}
                                        </td>
										<td width="60">{pigcms{$vo.paid}</td> -->
										<td width="60">{pigcms{$vo.money|floatval}</td>
										<td width="60">{pigcms{$vo.deliver_cash|floatval}</td>
										<td width="60">{pigcms{$vo.order_status}</td>
										<td width="50">
                                            {pigcms{$vo.name}<br/>{pigcms{$vo.phone|phone_demo_show}</td>
										<td width="80">{pigcms{$vo.start_time}</td>
										<td width="80">{pigcms{$vo.end_time}</td>
										<td width="58">
                                            <if condition="$vo['item'] neq 8">
                                                {pigcms{$vo.confirm_time}
                                                <else/>
                                                -
                                            </if>
                                        </td>
										<td width="58">{pigcms{$vo.grab_time}</td>
										<td width="58">{pigcms{$vo.deliver_use_time}</td>
										<td width="58">{pigcms{$vo.pay_deliver_use_time}</td>
										<td width="80">
										<if condition="$vo['status'] lt 2">
										---
										<elseif condition="$vo['get_type'] eq 0" />
										抢单
                                        <elseif condition="$vo['get_type'] eq 4" />
                                        智能分单指派
										<else />
										系统派单
										</if>
										</td>
										
										<td width="80">
								
										<if condition="$vo['status'] eq 0" >
										<font color="red">订单失效</font>
										<elseif condition="$vo['status'] eq 1" />
										<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Deliver/appoint_deliver',array('supply_id' => $vo['supply_id']))}','指派配送员',480,380,true,false,false,editbtn,'edit',true);">指派配送员</a>
										<elseif condition="$vo['status'] lt 5" />
										<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Deliver/appoint_deliver',array('supply_id' => $vo['supply_id']))}','更换配送员',480,380,true,false,false,editbtn,'edit',true);" style="color:red">更换配送员</a>
                                        <elseif condition="$vo['status'] eq 7" />
                                        <font color="red">退款失败</font>
                                        <else />
										<font color="green">配送完成</font>
										</if>
										</td>
                                        <td width="80">
                                        <if condition="!in_array($vo['status'], array(2,3,4))">
                                        ---
                                        <else />
                                        <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Shop/edit_supply_status',array('supply_id'=>$vo['supply_id']))}','修改配送状态',500,200,true,false,false,false,'edit',true);" style="color:green" data-supply="{pigcms{$vo['supply_id']}" class="change">修改配送状态</a>
                                        </if>
                                        </td>

                                        <td width="80">
                                            <if condition="$vo['item'] eq 3">
                                                <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Service/offer_info',array('publish_id' => $vo['order_id'],'frame_show'=>true))}','查看订单信息',850,700,true,false,false,false,'detail',true);">查看</a>
                                                <elseif condition="$vo['item'] eq 2" />
                                                    <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Shop/order_detail',array('order_id'=>$vo['order_id'],'frame_show'=>true))}','查看{pigcms{$config.shop_alias_name}订单详情',850,700,true,false,false,false,'detail',true);">查看</a>
                                                <elseif condition="$vo['item'] eq 4" />
                                                    <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('/v20/public/platform/#/dialog/platform.mall/orderDetail?order_id={pigcms{$vo.order_id}','查看{pigcms{$config.mall_alias_name_new}订单详情','65%','75%',true,false,false,false,'detail',true);">查看</a>
                                                <elseif condition="$vo['item'] eq 8" />
                                                    <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Village_group/join_order_detail',array('order_id'=>$vo['order_id'],'frame_show'=>true))}','查看{pigcms{$config.village_group_alias}订单详情',850,700,true,false,false,false,'detail',true);">查看</a>
                                            </if>

                                            <if condition="($vo['uid'] gt 0) AND ($vo['deliver_status'] egt 2) AND ($vo['deliver_status'] lt 5)">
                                                &nbsp;|&nbsp;<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Deliver/map',array('supply_id' => $vo['supply_id'],'uid' => $vo['uid'],'frame_show'=>true))}','坐标预览',850,700,true,false,false,false,'detail',true);">坐标</a>
                                            </if>
                                          
										</td>
										<!--td width="50">{pigcms{$vo.create_time}</td-->
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="24">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="24">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script>
	var selectStoreId = {pigcms{:$selectStoreId? $selectStoreId: 0};
	var selectUserId = {pigcms{:$selectUserId? $selectUserId: 0};
	$(function(){
		$("#store").change(function(){
			selectStoreId = $("#store").val();
			selectUserId = 0;
			search();
		});
		$("#deliver").change(function(){
			selectStoreId = 0;
			selectUserId = $("#deliver").val();
			search();
		});
		$("#search").click(function(){
			search();
		});
		function search() {
			var status = $('#status').val(), timetype = $('#timetype').val(), time = $('#time').val(), order_from = $('#order_from').val(),deliver_status = $('#deliver_status').val();
			var province_idss = $("#choose_provincess").val(), city_idss = $('#choose_cityss').val(), area_id = $('#choose_areass').val(), searchtype = $('#searchtype').val(), keyword = $('#keyword').val();
            var street_id = $("#choose_streetss").val();


			var day = '', period = '';
			if($('#time_value option:selected').attr('value')=='custom'){
				period = $('#time_value option:selected').html();
			}else{
				day = $('#time_value option:selected').attr('value');
			}
			var param = "&period="+period+"&day="+day+"&order_from="+order_from+"&timetype="+timetype+"&time="+time+"&deliver_status="+deliver_status;
            if (province_idss) {
                param += '&province_idss='+province_idss;
            }
            if (city_idss) {
                param += '&city_idss='+city_idss;
            }
            if (area_id) {
                param += '&area_id='+area_id;
            }
            if (street_id) {
                param += '&street_id='+street_id;
            }
            if (keyword) {
                param += '&keyword='+keyword;
            }
            if (searchtype) {
                param += '&searchtype='+searchtype;
            }
			location.href = "{pigcms{:U('Merchant/Deliver/deliverList')}"+ param;
		}
	});

    var ii,file_date,file_url;

    var url = "{pigcms{$config.site_url}";
    var export_url = "{pigcms{:U('Deliver/export')}";
    function exports(){
        var status = $('#status').val(), timetype = $('#timetype').val(), time = $('#time').val(), order_from = $('#order_from').val();
        var province_idss = $("#choose_provincess").val(), city_idss = $('#choose_cityss').val(), area_id = $('#choose_areass').val(), searchtype = $('#searchtype').val(), keyword = $('#keyword').val();
        var deliver_status = $('#deliver_status').val();

        var day = '', period = '';
        if($('#time_value option:selected').attr('value')=='custom'){
            period = $('#time_value option:selected').html();
        }else{
            day = $('#time_value option:selected').attr('value');
        }
        var param = {};
        param['period'] = period;
        param['day'] = day;
        param['status'] = status;
        param['order_from'] = order_from;
        param['timetype'] = timetype;
        param['time'] = time;
        param['deliver_status'] = deliver_status;
        if (province_idss) {
            param['province_idss'] = province_idss;
        }
        if (city_idss) {
            param['city_idss'] = city_idss;
        }
        if (area_id) {
            param['area_id'] = area_id;
        }
        if (keyword) {
            param['keyword'] = keyword;
        }
        if (searchtype) {
            param['searchtype'] = searchtype;
        }

        $.post(export_url,param,function(date){
            ii= layer.msg('加载中,请耐心等待,数量越多时间越长', {
                icon: 16
                ,shade: [0.3, '#000']
                ,time:0
            });
            //此处用setTimeout演示ajax的回调
            file_url = url+'/index.php?g=Index&c=ExportFile&a=download_export_file&id='+date.export_id;
            file_date = date;
            CheckStatus()



        },'json')
    }

    function CheckStatus()
    {
        date = file_date;
        $.post(file_url,{id:date.export_id},function(result){
            if(result.error_code==0){
                layer.close(ii);
                layer.open({
                    type: 1,
                    title:'下载导出文件',
                    area: ['600px', '120px'],
                    content: '\<p style="height:18px"\>\<\/p\>\<\a style="font-size:18px;" href="'+url+'/index.php?g=Index&c=ExportFile&a=download_export_file&id='+date.export_id+'" download="'+date.file_name+'">点击下载导出文件\<\/a>'
                });
                $('.layui-layer-content').css('text-align','center');
                $('.layui-layer .layui-anim .layui-layer-page').css('top','200px');

            }else{
                setTimeout("CheckStatus()",1000);
            }



        },'json')

        $('.group_verify_btn').click(function(){

        })
    }

</script>
<style>
.table-list thead th {
    line-height: 14px;
}
.u-text-wrap {
	white-space: pre-wrap;
	word-break: break-all;
	word-wrap: break-word;
}
.drp-popup{top:90px !important}
.deliver_search input{height: 20px;}
.deliver_search select{height: 24px;}
.deliver_search .mar_l_10{margin-left: 10px;}
.deliver_search .btn{height: 23px;line-height: 16px; padding: 0px 12px;}
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/date-picker/index.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}js/date-picker/index.css" />
<include file="Public:footer"/>