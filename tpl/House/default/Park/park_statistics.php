<include file="Public:header"/>
<style>
    .details{
        width: 30px;
        cursor: pointer;
    }
</style>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Index/index')}">社区管理</a>
            </li>
            <li class="active">基本信息设置</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">

        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div class="tabbable">
                        <if condition="$_GET['type'] eq 'electriccar_statistics'">
                                <ul id="myTab" class="nav nav-tabs">
                                    <li>
                                        <a href="{pigcms{:U('Electric_device/index')}&iframe=true">智能电瓶车</a>
                                    </li>
                                    <li class="active">
                                        <a href="javascript:void(0);">电瓶车进出统计</a>
                                    </li>
                                </ul>
                            <else/>
                                <ul id="myTab" class="nav nav-tabs">
                                    <if condition="$village_park_config['park_versions'] eq 1 || $village_park_config['park_sys_type'] neq 'D1'">
                                        <li <if condition="!$_GET['type'] || $_GET['type'] eq 'basicinfo'"> class="active" </if> >
                                        <a href="{pigcms{:U('Park/park_statistics')}&type=basicinfo">车辆进出统计</a>
                                        </li>
                                    </if>
                                    <li <if condition="$_GET['type'] eq 'txtstore' "> class="active" </if>>
                                    <a href="{pigcms{:U('Park/park_statistics')}&type=txtstore">停车费统计</a> <!--data-toggle="tab"-->
                                    </li>
                                    <if condition="$electriccar_has">
                                        <li <if condition="$_GET['type'] eq 'electriccar_statistics' "> class="active" </if>>
                                        <a href="{pigcms{:U('Park/park_statistics')}&type=electriccar_statistics">电瓶车进出统计</a>
                                        </li>
                                    </if>
                                </ul>
                        </if>
                    </div>
                    <div class="tab-content">
                        <div <if condition="!$_GET['type'] || $_GET['type'] eq 'basicinfo'"> class="tab-pane active" <else/> class="tab-pane" </if> id="basicinfo">
							<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
								<form method="get" action="{pigcms{:U('Park/park_statistics')}" id="find-form" onSubmit="return check_submit()">
									<input type="hidden" name="c" value="Park"/>
									<input type="hidden" name="a" value="park_statistics"/>
									<input type="hidden" name="type" value="basicinfo"/>
									<select name="param">
										<option value="car_number" <if condition="$_GET['param'] eq 'car_number'"> selected </if>>车辆号码</option>
										<option value="position_num" <if condition="$_GET['param'] eq 'position_num'"> selected </if>>车位号</option>
										<option value="car_user_name" <if condition="$_GET['param'] eq 'car_user_name'"> selected </if>>车主姓名</option>
										<option value="car_user_phone" <if condition="$_GET['param'] eq 'car_user_phone'"> selected </if>>车主手机号</option>
									</select>
									<input value="{pigcms{$_GET['name']}" name="name" id="name" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
									&nbsp;&nbsp;
									进场类型：
									<select name="is_out" id="is_out" style="width: 100px;">
										<option value="">全部</option>
										<option value="1" <if condition="$_GET['is_out'] eq 1"> selected </if> >进场</option>
										<option value="2" <if condition="$_GET['is_out'] eq 2"> selected </if> >出场</option>
									</select>
									&nbsp;&nbsp;
									时间筛选：
									<input type="text" id="start_time" name="start_time" class="input-text" value="{pigcms{$_GET['start_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始"/>
									&nbsp;—&nbsp;
									<input type="text" id="end_time" name="end_time" class="input-text" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束"/>&nbsp;&nbsp;&nbsp;&nbsp;
									<input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
									<button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('Park/park_statistics')}&type=basicinfo'">重置</button>
								</form>
							</div>
							<a onclick="location.href='{pigcms{:U('in_out_car_export',$_GET)}'" class="btn btn-success">EXCEL导出</a><br/>

                        <div style="color: green;margin-top: 15px;">1. 信息展示：同一辆车一次进场和出场会整合为一条数据，如果只有进场也展示为一条数据；</div>
                        <div style="color: green;margin-top: 2px;">2. 二次进场记录更替：没有获取到出场消息的情况下，拿到了对应车辆的入场新消息，将原来进场消息更替为已出场；</div>
                        <div style="color: green;margin-top: 2px;">3. 进出场动作（进场）：对应车辆只有进场没有出场时候显示进场和进场时间，无出场时间；</div>
                        <div style="color: green;margin-top: 2px;">4. 进出场动作（进场-出场）：进场后车辆出场后 显示 进场-出场 并展示对应进出场时间和所在场内时间，【二次进场记录更替】情况例外，无出场时间；</div>
                        <div style="color: green;margin-top: 2px;">5. 进出场动作（出场）：出场信息没有对应进场数据展示 出场 并展示对应出场时间，无入场时间；</div>
                        <div style="color: red;margin-top: 2px;">6. 搜索条件为“进场" 那就会和初始进入一样以入场为主和时间以入场时间为主进行查询；</div>
                        <div style="color: red;margin-top: 2px;">7. 搜索条件为“出场" 那就会以出场为主和时间以出场时间为主进行查询；</div>
                        <div style="color: green;margin-top: 2px;">8. 导出数据建议以时间为条件进行导出避免数量过多导出失败，点击导出后请勿退出该页面，导出成功后对应文件会自动下载，文件下载完成即导出完成；</div>

                        <div style="margin-top: 10px;color: #2681f3;">目前在场车辆：<b style="font-size: 18px;">{pigcms{$list.in_car_num}</b>；今日进场数：<b style="font-size: 18px;">{pigcms{$list.in_count}</b>；今日出场数：<b style="font-size: 18px;">{pigcms{$list.out_count}</b></div>

							<div id="shopList" class="grid-view">
								<table class="table table-striped table-bordered table-hover">
									<thead>
									<tr>
										<th width="10%">车辆号码</th>
										<th width="10%">车位号</th>
										<th width="10%">车主姓名</th>
										<th width="10%">车主手机号</th>
										<th width="10%">进出场动作</th>
                                        <th width="15%">进场时间</th>
                                        <th width="15%">出场时间</th>
                                        <th width="10%">所在场内时间</th>
                                        <th width="10%">当前订单</th>
                                        <if condition="$village_park_config['park_versions'] eq 2 && $village_park_config['park_sys_type'] eq 'D5'">
                                        <th width="10%">操作</th>
                                        </if>
									</tr>
									</thead>
									<tbody>
									<if condition="$list['list']">
										<volist name="list['list']" id="vo">
											<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
												<td><div class="tagDiv">{pigcms{$vo.car_number}</div></td>
												<td><div class="tagDiv">{pigcms{$vo.position_num}</div></td>
												<td><div class="tagDiv">{pigcms{$vo.car_user_name}</div></td>
												<td><div class="tagDiv">{pigcms{$vo.car_user_phone}</div></td>
												<td><div class="tagDiv">{pigcms{$vo.is_out_text}</div></td>
                                                <td><div class="tagDiv">{pigcms{$vo.in_time}</div></td>
                                                <td><div class="tagDiv">{pigcms{$vo.out_time}</div></td>
                                                <td><div class="tagDiv">{pigcms{$vo.standing_time}</div></td>
                                                <td><div class="tagDiv">{pigcms{$vo.order_id}</div></td>
                                          <if condition="$village_park_config['park_versions'] eq 2 && $village_park_config['park_sys_type'] eq 'D5'">
                                                <td><div class="tagDiv details" onclick="details({pigcms{$vo.id})">详情</div></td>
                                          </if>
											</tr>
										</volist>
									<else/>
									<tr class="odd"><td class="button-column"  <if condition="$village_park_config['park_versions'] eq 2 && $village_park_config['park_sys_type'] eq 'D5'">colspan="10" <else/>colspan="9" </if> >没有任何数据。</td></tr>
									</if>
									</tbody>
								</table>
								{pigcms{$list.pagebar}
							</div>
						</div>
						<div <if condition="$_GET['type'] eq 'txtstore'"> class="tab-pane active" <else/> class="tab-pane" </if> id="txtstore">
							<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
								<form method="get" action="{pigcms{:U('Park/park_statistics')}" id="find-form">
									<input type="hidden" name="c" value="Park"/>
									<input type="hidden" name="a" value="park_statistics"/>
									<input type="hidden" name="type" value="txtstore"/>
									<select name="param">
										<option value="car_number" <if condition="$_GET['param'] eq 'car_number'"> selected </if>>车辆号码</option>
										<option value="car_user_name" <if condition="$_GET['param'] eq 'car_user_name'"> selected </if>>业主姓名</option>
										<option value="car_user_phone" <if condition="$_GET['param'] eq 'car_user_phone'"> selected </if>>联系方式</option>
									</select>
									<input value="{pigcms{$_GET['title']}" name="title" id="title" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
									&nbsp;&nbsp;
									缴费类型：
									<select name="car_type" style="width: 100px;">
										<option value="">全部</option>
										<option value="temporary_type" <if condition="$_GET['car_type'] eq 'temporary_type'"> selected </if>>临时车缴费</option>
										<option value="month_type" <if condition="$_GET['car_type'] eq 'month_type'"> selected </if>>月租卡充值</option>
										<option value="stored_type" <if condition="$_GET['car_type'] eq 'stored_type'"> selected </if>>停车卡储值</option>
									</select>
									&nbsp;&nbsp;
									支付方式：
									<select name="pay_type" style="width: 100px;">
										<option value="">全部</option>
										<option value="2" <if condition="$_GET['pay_type'] eq 2"> selected </if> >在线支付</option>
										<option value="1" <if condition="$_GET['pay_type'] eq 1"> selected </if> >线下支付</option>
									</select>
									&nbsp;&nbsp;
									时间筛选：
									<input type="text" name="o_start_time" class="input-text" value="{pigcms{$_GET['o_start_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始"/>
									&nbsp;—&nbsp;
									<input type="text" name="o_end_time" class="input-text" value="{pigcms{$_GET['o_end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束"/>&nbsp;&nbsp;&nbsp;&nbsp;
									<input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
									<button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('Park/park_statistics')}&type=txtstore'">重置</button>
								</form>
							</div>
							<a onclick="location.href='{pigcms{:U('park_cost_export',$_GET)}'" class="btn btn-success">EXCEL导出</a><br/>
							<span>费用总计：{pigcms{$park_cost.money}元</span>
                            <span>费用合计：{pigcms{$park_cost.money1}元</span>
							<div id="shopList" class="grid-view">
								<table class="table table-striped table-bordered table-hover">
									<thead>
									<tr>
										<th width="10%">订单编号</th>
										<th width="15%">车辆号码</th>
                                        <th width="15%">储值卡余额</th>
										<th width="15%">缴费金额</th>
										<th width="10%">缴费类型</th>
										<th width="10%">支付方式</th>
										<th width="10%">支付时间</th>
										<th width="10%">业主</th>
										<th width="10%">联系方式</th>
									</tr>
									</thead>
									<tbody>
									<if condition="$park_cost['list']">
										<volist name="park_cost['list']" id="vo">
											<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
												<td><div class="tagDiv">{pigcms{$vo.order_num}</div></td>
												<td><div class="tagDiv">{pigcms{$vo.car_number}</div></td>
                                                <td><div class="tagDiv">{pigcms{$vo.stored_balance}</div></td>
												<td><div class="tagDiv">{pigcms{$vo.money}</div></td>
												<td><div class="tagDiv">{pigcms{$vo.car_type_text}</div></td>
												<td><div class="tagDiv">
														<if condition="$vo['pay_type'] eq 0 && !$vo['cashier_id']">
															<strong style="color: green">在线支付</strong><if condition="$vo.online_pay_type">({pigcms{$vo.online_pay_type})</if>
															<elseif condition="$vo['pay_type'] eq 0 && $vo['cashier_id'] gt 0" />
															<strong style="color: green">在线支付</strong>(收银台扫码)
															<elseif condition="$vo['pay_type'] eq 1" />
															线下支付<if condition="$vo['pay_type_name']">({pigcms{$vo.pay_type_name})</if>
														</if>
													</div></td>
												<td><div class="tagDiv">{pigcms{$vo.pay_time}</div></td>
												<td><div class="tagDiv">{pigcms{$vo.car_user_name}</div></td>
												<td><div class="tagDiv">{pigcms{$vo.car_user_phone}</div></td>
											</tr>
										</volist>
									<else/>
									<tr class="odd"><td class="button-column" colspan="8" >没有任何数据。</td></tr>
									</if>
									</tbody>
								</table>
								{pigcms{$park_cost.pagebar}
							</div>
						</div>
						<div <if condition="$_GET['type'] eq 'electriccar_statistics'"> class="tab-pane active" <else/> class="tab-pane" </if> id="electriccar_statistics">
							<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
								<form method="get" action="{pigcms{:U('Park/park_statistics')}" id="find-form">
									<input type="hidden" name="c" value="Park"/>
									<input type="hidden" name="a" value="park_statistics"/>
									<input type="hidden" name="type" value="electriccar_statistics"/>
									<select name="param">
										<option value="car_number" <if condition="$_GET['param'] eq 'car_number'"> selected </if>>车辆号码</option>
										<option value="car_stop_num" <if condition="$_GET['param'] eq 'car_stop_num'"> selected </if>>停车卡号</option>
										<option value="car_user_name" <if condition="$_GET['param'] eq 'car_user_name'"> selected </if>>车主姓名</option>
										<option value="car_user_phone" <if condition="$_GET['param'] eq 'car_user_phone'"> selected </if>>车主手机号</option>
									</select>
									<input value="{pigcms{$_GET['name']}" name="name" id="name" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
									&nbsp;&nbsp;
									时间筛选：
									<input type="text" id="start_time" name="start_time" class="input-text" value="{pigcms{$_GET['start_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始"/>
									&nbsp;—&nbsp;
									<input type="text" id="end_time" name="end_time" class="input-text" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束"/>&nbsp;&nbsp;&nbsp;&nbsp;
									<input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
									<button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('Park/park_statistics')}&type=basicinfo'">重置</button>
								</form>
							</div>
							<div id="shopList" class="grid-view">
								<table class="table table-striped table-bordered table-hover">
									<thead>
									<tr>
										<th width="10%">车辆号码</th>
										<th width="15%">车主姓名</th>
                                        <th width="15%">车主手机号</th>
                                        <th width="15%">设备id</th>
                                        <th width="15%">设备名称</th>
										<th width="15%">记录时间</th>
									</tr>
									</thead>
									<tbody>
									<if condition="$in_park_data">
										<volist name="in_park_data" id="vo">
											<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
												<td><div class="tagDiv">{pigcms{$vo.car_number}</div></td>
												<td><div class="tagDiv">{pigcms{$vo.car_user_name}</div></td>
                                                <td><div class="tagDiv">{pigcms{$vo.car_user_phone}</div></td>
                                                <td><div class="tagDiv">{pigcms{$vo.device_id}</div></td>
                                                <td><div class="tagDiv">{pigcms{$vo.device_name}</div></td>
												<td><div class="tagDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s', ###}</div></td>
											</tr>
										</volist>
									<else/>
										<tr class="odd"><td class="button-column" colspan="7" >没有任何数据。</td></tr>
									</if>
									</tbody>
								</table>
								{pigcms{$pagebar}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
</div>

<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    function check_submit()
    {
        var start_time = $('#start_time').val();
        var end_time = $('#end_time').val();
        var is_out = $("#is_out").val();
        if( (start_time || end_time) && !is_out){
            layer.alert('请选择进出场类型');
            return false;
        }
    }

    function details(id){
        var url = "{pigcms{:U('Park/park_details')}";
        var href  = url + '&id='+id
        layer.open({
            type: 2,
            title:'查看',
            content: href,
            area: ['850px', '650px'],
            maxmin: false
        });

    }
</script>

<include file="Public:footer"/>