 <!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/storestaffBase.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
        <style>
            .worker-list-wrap {
                max-height: 100%;
                overflow-y: auto;
            }
            .worker-list-wrap .worker-list-item {
                padding: 8px 12px;
                margin: 12px 16px;
                border-radius: 5px;
                background-color: #f8f9fa;
                color: #333;
                /* white-space: pre-wrap; */
                word-break: break-all;
                word-wrap: break-word;
                list-style: none;
                cursor: pointer;
                transition: all 0.3s;
            }
            .worker-list-wrap .worker-list-item:hover {
                background-color: rgba(135,184,127,0.3);
            }
            .worker-list-wrap .worker-list-item.active{
                background-color: #87b87f;
                color: #fff;
            }
			.worker-list-item span{
				width: 30%;
				display: inline-block;
			}
            .layui-layer-btn .custom-layer-btn {
                background-color: #87b87f; /* 修改按钮的背景色 */
                color: #ffffff; /* 修改按钮的字体颜色 */
                border-color:  #87b87f;
            }
            .layui-layer-btn .layui-layer-btn1:hover {
                color:#87b87f;
            }
        </style>
	</head>
	<body>
		<div class="headerBox">
			<div class="txt">{pigcms{$config.appoint_alias_name}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_('返回首页')}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_('刷新页面')}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink cur" data-url="{pigcms{:U('appoint_list')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("订单列表")}</div>
					</li>
					<li class="urlLink" data-url="{pigcms{:U('appoint_find')}">
						<div class="icon search"></div>
						<div class="text">{pigcms{:L_("查找订单")}</div>
					</li>
					<li class="urlLink" data-url="{pigcms{:U('allot_appoint_list')}">
						<div class="icon merchant"></div>
						<div class="text">{pigcms{:L_("商家派发订单")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<form action="{pigcms{:U('Store/appoint_list')}" method="get">
					<input type="hidden" name="c" value="Store"/>
					<input type="hidden" name="a" value="appoint_list"/>
					<input type="hidden" name="appoint_id" value="{pigcms{$_GET.appoint_id}"/>

					{pigcms{:L_("搜索")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
					<select name="searchtype">
                        <option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>{pigcms{:L_("订单ID")}</option>
                        <option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
						<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>{pigcms{:L_("订单流水号")}</option>
						<option value="third_id" <if condition="$_GET['searchtype'] eq 'third_id'">selected="selected"</if>>{pigcms{:L_("第三方支付流水号")}</option>
						<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("客户名称")}</option>
						<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("客户电话")}</option>
					</select>
					<font color="#000">{pigcms{:L_("日期筛选：")}</font>
					<select name="searchTimeType">
						<option value="order_time" <if condition="$_GET['searchTimeType'] eq 'order_time'">selected="selected"</if>>{pigcms{:L_("下单时间")}</option>
						<option value="user_pay_time" <if condition="$_GET['searchTimeType'] eq 'user_pay_time'">selected="selected"</if>>{pigcms{:L_("尾款支付时间")}</option>
						<option value="verify_time" <if condition="$_GET['searchTimeType'] eq 'verify_time'">selected="selected"</if>>{pigcms{:L_("完成订单时间")}</option>
					</select>
					<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
					<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						　
                    <font color="#000">{pigcms{:L_("支付状态：")}</font>
                    <select name="paid">
                        <option value="-1" <if condition="$_GET['paid'] eq '-1'">selected="selected"</if>>全部</option>
                        <option value="0" <if condition="$_GET['paid'] eq '0'">selected="selected"</if>>未支付</option>
                        <option value="1" <if condition="$_GET['paid'] eq '1'">selected="selected"</if>>已支付</option>
                        <option value="2" <if condition="$_GET['paid'] eq '2'">selected="selected"</if>>已退款</option>
                        <option value="3" <if condition="$_GET['paid'] eq '3'">selected="selected"</if>>已取消</option>
                        <option value="-10" <if condition="$_GET['paid'] eq '-10'">selected="selected"</if>>尾款待支付</option>
                    </select>
					<font color="#000">{pigcms{:L_("服务状态：")}</font>
					<select name="service_status">
						<option value="-1" <if condition="$_GET['service_status'] eq '-1'">selected="selected"</if>>{pigcms{:L_("全部")}</option>
						<option value="0" <if condition="$_GET['service_status'] eq '0'">selected="selected"</if>>{pigcms{:L_("未服务")}</option>
						<option value="1" <if condition="$_GET['service_status'] eq '1'">selected="selected"</if>>{pigcms{:L_("已服务")}</option>
						<option value="2" <if condition="$_GET['service_status'] eq '2'">selected="selected"</if>>{pigcms{:L_("已评价")}</option>
						<option value="11" <if condition="$_GET['service_status'] eq '11'">selected="selected"</if>>{pigcms{:L_("申请退款")}</option>
					</select>

					{pigcms{:L_("支付方式筛选")}:
					<select id="pay_type" name="pay_type">
							<option value="" <if condition="'' eq $pay_type">selected="selected"</if>>{pigcms{:L_("全部支付方式")}</option>
						<volist name="pay_method" id="vo">
							<option value="{pigcms{$key}" <if condition="$key eq $pay_type">selected="selected"</if>>{pigcms{$vo.name}</option>
						</volist>
							<option value="balance" <if condition="'balance' eq $pay_type">selected="selected"</if>>{pigcms{:L_("余额支付")}</option>
					</select>
					<input type="submit" value="{pigcms{:L_("查询")}" class="btn btn-success" style="padding:2px 14px;"/>　
					<a href="javascript:void(0)" onclick="exports()" class="btn btn-success" style="float:right;margin-right: 10px;">{pigcms{:L_("导出订单")}</a>
				</form>
				<p>&nbsp;</p>{pigcms{:L_("此页面只列出已归属到此店铺的订单。若想验证新订单或查找订单，请点击上面按钮。")}
				<div class="grid-view">
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th>{pigcms{:L_("订单ID")}</th>
								<th width="8%">{pigcms{:L_("预约名称")}</th>
								<th>{pigcms{:L_("订单信息")}</th>
								<th>{pigcms{:L_("服务状态")}</th>
								<th>{pigcms{:L_("用户信息")}</th>
								<th>{pigcms{:L_("订单状态")}</th>
								<th>{pigcms{:L_("订单备注")}</th>
								<th class="button-column">{pigcms{:L_("操作")}</th>
							</tr>
						</thead>
						<tbody>
						<if condition="!empty($order_list)">
							<volist name="order_list" id="vo">
								<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
						        <td width="100">{pigcms{$vo.order_id}</td>

									<td width="200"><a href="{pigcms{$config.site_url}/index.php?g=Appoint&c=Detail&appoint_id={pigcms{$vo.appoint_id}" target="_blank">{pigcms{$vo.appoint_name}</a></td>
									<td width="200">
										{pigcms{:L_("定金：")}{pigcms{:floatval($vo['payment_money'])}{pigcms{$config['Currency_txt']}<br/>
										{pigcms{:L_("总价：")}
										<if condition="$staff_session['is_change']==1 AND $vo['can_change_price'] eq 1">
											<input type="text" name="price" value="<if condition='$vo["product_id"] gt 0'> {pigcms{$vo.product_price}<else/>{pigcms{$vo.appoint_price}</if>" style="width: 80px;">&nbsp;{pigcms{$config['Currency_txt']}
											<button class="btn btn-success" style="float:right;display:inline;padding: 1px 4px;border-radius: 3px;" onclick="change_price(this,{pigcms{$vo.order_id});">{pigcms{:L_("修改价格")}</button>
										<else/>
											<if condition='$vo["product_id"] gt 0'> {pigcms{$vo.product_price}<else/>{pigcms{$vo.appoint_price}</if>{pigcms{$config['Currency_txt']}
										</if><br/>
										<if condition='$vo["type"] != 2'>{pigcms{:L_("预约时间：")}{pigcms{$vo.appoint_date}&nbsp;{pigcms{$vo.appoint_time}</if>

									</td>
									<td width="150">
                                        <if condition="$vo['service_status'] eq 0">
                                            <span class="red">{pigcms{:L_("未完成订单")}</span>
                                            <else/>
                                            <font color="green">{pigcms{:L_("已服务")}</font><br/>
                                            <php>if($vo['last_staff']){</php>{pigcms{:L_("操作店员：")}{pigcms{$vo['last_staff']}<br/><php>}</php>
                                            <php>if($vo['last_time']>0){</php>{pigcms{:L_("消费时间：")}{pigcms{$vo['last_time']|date='Y-m-d H:i:s',###}<br/><php>}</php>
                                        </if>
									</td>
									<td width="180">
										{pigcms{:L_("用户ID：")}{pigcms{$vo.uid}<br/>
										{pigcms{:L_("用户名：")}{pigcms{$vo.nickname}<br/>
										{pigcms{:L_("用户手机号：")}{pigcms{$vo.phone}<br/>
									</td>
									<td width="200">
									<if condition="$vo['paid'] == 0" >
										<font color="red">{pigcms{:L_("未支付")}</font>
										<if condition="$vo['service_status'] == 0">
											<font color="red">{pigcms{:L_("未服务")}</font>
                                            <php>if (($vo["is_del"] == 0) && ($vo["payment_status"] == 0)) {</php>
                                            <a href="{pigcms{:U('Store/appoint_verify',array('order_id'=>$vo['order_id']))}" data-payment_status="{pigcms{:$vo['payment_status']}" data-service_status="{pigcms{:$vo['service_status']}" class="group_verify_btn">{pigcms{:L_("完成订单")}</a>
                                            <php>}</php>
										<elseif condition="$vo['service_status'] == 1" />
											<font color="green">{pigcms{:L_("已服务")}</font>
										<elseif condition="$vo['service_status'] == 2" />
											<font color="green">{pigcms{:L_("已评价")}</font>
										</if>
									<elseif condition="$vo['paid'] == 1" />
                    <if condition="$vo['is_apply_refund'] eq 1">
                      <span style="color:blue">{pigcms{:L_("已申请退款")}</span>
                    </if>
                                        <php>if ($vo['payment_status']!=1) {</php>
                                            <span style="color:green">{pigcms{:L_("已支付")}</span>
                                        <php>} elseif ($vo['service_status']<=0 && $vo['is_initiative']<=0) {</php>
                                            <span style="color:green">定金已支付</span><span style="color:red">尾款待支付</span>
                                        <php>} elseif ($vo['service_status']>0 && $vo['is_initiative']<=0) {</php>
                                            <span style="color:green">已服务</span><span style="color:red">尾款未支付</span>
                                        <php>} else {</php>
                                            <span style="color:green">{pigcms{:L_("已支付")}</span>
                                        <php>}</php>


										<if condition='$vo["complete_source"] eq 2'>

                                            <php>if ($vo["service_status"] == 1) {</php>
                                            <font color="green">{pigcms{:L_("已服务")}</font>
                                            <php>} elseif($vo['is_initiative']){</php>
                      <font color="red"><?php echo $config['appoint_worker_name'] ? $config['appoint_worker_name'] : L_('技师'); ?>{pigcms{:L_("已服务，待验证")}</font>
                                            <php>}else {</php>
                                            <font color="red"><?php echo $config['appoint_worker_name'] ? $config['appoint_worker_name'] : L_('技师'); ?>{pigcms{:L_("已服务，用户未付余款")}</font>
                                            <php>}</php>
                                            <php>if ($vo["service_status"] == 0) {</php>
                                            <a href="{pigcms{:U('Store/appoint_verify',array('order_id'=>$vo['order_id']))}" data-payment_status="{pigcms{:$vo['payment_status']}" data-service_status="{pigcms{:$vo['service_status']}" class="group_verify_btn">{pigcms{:L_("完成订单")}</a>
                                            <php>}</php>
										<elseif condition="$vo['service_status'] == 0" />
											<font color="red">{pigcms{:L_("未服务")}</font>
                                            <php>if ($vo["is_del"] == 0) {</php>
                                            <a href="{pigcms{:U('Store/appoint_verify',array('order_id'=>$vo['order_id']))}" data-payment_status="{pigcms{:$vo['payment_status']}" data-service_status="{pigcms{:$vo['service_status']}" class="group_verify_btn">{pigcms{:L_("完成订单")}</a>
                                            <php>}</php>
										<elseif condition="$vo['service_status'] == 1" />
											<font color="green">{pigcms{:L_("已服务")}</font>
										<elseif condition="$vo['service_status'] == 2" />
											<font color="green">{pigcms{:L_("已评价")}</font>
										</if>
									<elseif condition="$vo['paid'] == 2" />
										<font color="red">{pigcms{:L_("已退款")}</font>
									<elseif condition="$vo['paid'] == 3" />
										<font color="orange">{pigcms{:L_("用户已取消")}</font>
                                        <php>if ($vo['payment_status']==1 && $vo['service_status']<=0) {</php>
                                           <span style="color:red">尾款待支付</span>
                                        <php>}</php>

									<else />
										<font color="red">{pigcms{:L_("订单异常")}</font>
									</if>
										<br/>
										{pigcms{:L_("下单时间：")}{pigcms{$vo['order_time']|date='Y-m-d H:i:s',###}<br/>
										<if condition="$vo['pay_time']">{pigcms{:L_("付款时间：")}{pigcms{$vo['pay_time']|date='Y-m-d H:i:s',###}<br/></if>
										<if condition='$vo["is_del"] neq 0'>
											<font color="red">
												<switch name='vo["is_del"]'>
													<case value="1">{pigcms{:L_("已取消【用户】【PC端】")}</case>
													<case value="2">{pigcms{:L_("已取消【平台】")}</case>
													<case value="3">{pigcms{:L_("已取消【商家】")}</case>
													<case value="4">{pigcms{:L_("已取消【店员】")}</case>
													<case value="5">{pigcms{:L_("已取消【用户】【WAP端】")}</case>
												</switch>
											</font>
										</if>

										<?php if ($vo['user_pay_time']==0): ?>
											<a class="btn btn-success pay " onclick="pay_price(this,{pigcms{$vo.order_id});" style="padding: 1px 4px;border-radius: 3px;">扫码支付</a>
										<?php endif ?>


									</td>
						<td width="200">
							{pigcms{:L_("备注：")}
								<input type="text" name="note" value="{pigcms{$vo.note}" style="width: 80px;">
							<button class="btn btn-success" style="float:right;display:inline;padding: 1px 4px;border-radius: 3px;" onclick="change_note(this,{pigcms{$vo.order_id});">{pigcms{:L_("提交")}</button>


						</td>
									<td class="button-column" width="40">
										<a title="{pigcms{:L_("查看订单详情")}"  data-title="{pigcms{:L_("订单详情")}" class="green handle_btn"
										href="{pigcms{:U('appoint_detail',array('order_id'=>$vo['order_id']))}">
											<i class="shortBtn">{pigcms{:L_("查看详情")}</i>
										</a>
									   <if condition='($vo["is_del"] eq 0) && ($vo["paid"] eq 0)'>
											<a href="javascript:void(0)" data-order-id="{pigcms{$vo['order_id']}" class="appoint_del" style="margin-top:20px;display:block;" class="red" title="{pigcms{:L_("取消订单")}">
												<i class="shortBtn">{pigcms{:L_("取消订单")}</i>
											</a>
									   </if>
                                       <if condition='($vo["service_status"] eq 0) && ($vo["merchant_worker_id"] != 0)'>
											<a href="javascript:void(0)" data-order-id="{pigcms{$vo['order_id']}" data-merchant_worker_id="{pigcms{$vo['merchant_worker_id']}" class="appoint_change_worker appoint_change_worker_{pigcms{$vo['order_id']}" style="margin-top:20px;display:block;" title="{pigcms{:L_('更换技师')}">
												<i class="shortBtn">{pigcms{:L_("更换技师")}</i>
											</a>
									   </if>
									</td>
								</tr>
							</volist>
								<tr><td class="textcenter pagebar" colspan="11">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="11">{pigcms{:L_("列表为空！")}</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</body>
	<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
	<script>
	$(function(){
		$('.group_verify_btn').live('click',function(){
			var verify_btn = $(this);
			var data = $(this).data()
			//尾款未支付，二次确认框
			if(data.payment_status == 1 && data.service_status <= 0){
				layer.alert('当前订单有尾款未支付，是否确认完成订单，完成订单后，用户将不能支付尾款！',{
					btn: ['确认','取消'],
					title: '提示',
				},function(index){
					layer.close(index);
					verify();
				});
				return false
			}
			verify();
			function verify(){
				verify_btn.html('{pigcms{:L_("验证中")}..');
				$.get(verify_btn.attr('href'),function(result){
					var content = result.info;
					if(result.status == 1){
						layer.alert(content,{
							btn: ['确认'],
							title: '提示',
						},function(index){
							layer.close(index);
							window.location.reload()
						});
					}else{
						var icon = 'error';
						var button = [{name:'{pigcms{:L_("关闭")}'}];
						verify_btn.html('{pigcms{:L_("完成订单")}');
					}
				});
			}
			return false;
		});
	});
	$('.appoint_del').click(function(){
		var url ="{pigcms{:U('ajax_staff_del')}";
		var order_id = $(this).data('order-id');
		if(confirm('{pigcms{:L_("取消后，将无法恢复，是否确认取消？")}')){
			$.post(url,{'order_id':order_id},function(data){
				alert(data.msg);
				if(data.status){
					location.reload();
				}
			},'json')
		}

	});
 	var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('appoint_export')}"

    function change_price(obj,order_id){
		var price = $(obj).siblings('input[name="price"]').val();
		$.post('{pigcms{:U("change_appoint_order_price")}',{order_id:order_id,price:price},function(date){
			alert(date.msg)
			window.location.reload();
		},'json');
	}

	function change_note(obj,order_id){
		var note = $(obj).siblings('input[name="note"]').val();
		$.post('{pigcms{:U("change_appoint_order_note")}',{order_id:order_id,note:note},function(date){
			alert(date.msg)
			window.location.reload();
		},'json');
	}

	function pay_price(obj,order_id){
		$.post('{pigcms{:U("pay_appoint_order")}',{order_id:order_id},function(response){
			if(response.status!=0){
				layer.open({
					title:'{pigcms{:L_("查看支付二维码")}',
					area:['430px','483px'],
					type: 2,
					shade: 0.4,
					content: response.pay_qrcode_url,
				});
				return false;
			} else {
                layer.alert(response.msg);
			}


		},'json')
	}

	//线上支付
//	$(".pay").click(function(){
//		check_pay_time = setInterval(function(){
//			$.post(shop_arrival_check, {order_id:order_id}, function(result){
//				if(result.status == 1){
//					layer.msg('支付成功！');
//					clearInterval(check_pay_time);
//					shop_mall.pay_success_back();
//					$(".payment, .shadow_two, .shadow, .settlement").hide();
//				}
//			});
//		},3000);
//		$(".payment, .shadow_two").show();
//	});
    var workerListHtmlStr = ''
    var merchant_worker_id = ''
    var v20_ticket = localStorage.getItem('pro__storestaff_access_token') || ''
    if(v20_ticket) {
        v20_ticket = JSON.parse(v20_ticket).value
    }
    // 获取技师列表
    function getWorkerList(order_id) {
        $.post('appapi.php?c=Storestaff&a=storeWorkerList',{v20_ticket:v20_ticket, order_id},function(response){
            var listWrap = $(`<ul class="worker-list-wrap"></ul>`)
            var liStr = ''
            if(response && response.errorCode == 0) {
                var list = response.result || []
                if(list.length) {
                    $.each(list, function(index, item) {
                        liStr+=`<li data-worker_id=${item.merchant_worker_id} class="worker-list-item">
							<span>姓名：${item.name}</span>
							<span>联系方式：${item.mobile}</span>
							<span>待服务单量：${item.receive_total}</span>
						</li>`
                    });
                    workerListHtmlStr = `<ul class="worker-list-wrap">${liStr}</ul>`
                }
            } else {
                workerListHtmlStr = ''
            }
            layer.open({
                title:'{pigcms{:L_("更换技师")}',
                area:['35%','50vh'],
                type: 1,
                shade: 0.4,
                btn: ['确认','取消'],
                content: workerListHtmlStr,
                success: function(layero, index) {
                    layero.find(".layui-layer-btn0").addClass("custom-layer-btn");
                    layero.find("li").on("click", function() {
                        var clickedIndex = $(this).data("index");
                        merchant_worker_id = $(this).data("worker_id");
                        layero.find("li").removeClass('active')
                        $(this).addClass('active')
                    });
                },
                end: function () {
                    merchant_worker_id = ''
                    order_id = ''
                },
                yes: function(index, layero){
                    if(!workerListHtmlStr) {
                        layer.msg('暂无可更换的技师');
                        return
                    }
                    if(!merchant_worker_id) {
                        layer.msg('请选择需要更换的技师');
                        return
                    }
                    var old_merchant_worker_id = $(`.appoint_change_worker_${order_id}`).data('merchant_worker_id')
                    if(old_merchant_worker_id && old_merchant_worker_id == merchant_worker_id) {
                        layer.msg('更换技师成功')
                        window.location.reload();
                        return
                    }
                    var params = {
                        merchant_worker_id: merchant_worker_id,
                        order_id: order_id,
                        v20_ticket:v20_ticket
                    }
                    $.post('appapi.php?c=Storestaff&a=changeWorker',params,function(response){
                        if(response && response.errorCode == 0) {
                            layer.msg('更换技师成功')
                            window.location.reload();
                        } else {
                            response.errorMsg && layer.msg(response.errorMsg)
                        }
                    },'json')
                    return false
                },
                btn2: function(index, layero){
                    //return false 开启该代码可禁止点击该按钮关闭
                    merchant_worker_id = ''
                    order_id = ''
                }
            });
		},'json')
    }

    // 更换技师
    $('.appoint_change_worker').click(function(){
		var order_id = $(this).data('order-id');
        getWorkerList(order_id)
	});
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
</html>