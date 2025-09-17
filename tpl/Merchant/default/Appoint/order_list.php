<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-comments-o comments-o-icon"></i>
			<li class="active"><a href="{pigcms{:U('Appoint/index')}">{pigcms{:L_("预约列表")}</a></li>
			<!--<li>{pigcms{$now_group.appoint_name}</li>-->
			<li>{pigcms{:L_("订单列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				 <div class="form-group" style="margin:0;">
					<form action="{pigcms{:U('Appoint/order_list')}" method="get">
						<input type="hidden" name="c" value="Appoint"/>
						<input type="hidden" name="a" value="order_list"/>
						<input type="hidden" name="appoint_id" value="{pigcms{$_GET.appoint_id}"/>
						
						
						{pigcms{:L_("搜索")}: 
						<select name="searchtype">
                            <option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>{pigcms{:L_("订单ID")}</option>
                            <option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
							<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>{pigcms{:L_("订单流水号")}</option>
							<option value="third_id" <if condition="$_GET['searchtype'] eq 'third_id'">selected="selected"</if>>{pigcms{:L_("第三方支付流水号")}</option>
							<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("客户名称")}</option>
							<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("客户电话")}</option>
						</select>
						<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						<font color="#000">{pigcms{:L_("日期筛选：")}</font>
						<select name="searchTimeType">
							<option value="order_time" <if condition="$_GET['searchTimeType'] eq 'order_time'">selected="selected"</if>>{pigcms{:L_("下单时间")}</option>
							<option value="user_pay_time" <if condition="$_GET['searchTimeType'] eq 'user_pay_time'">selected="selected"</if>>{pigcms{:L_("尾款支付时间")}</option>
							<option value="verify_time" <if condition="$_GET['searchTimeType'] eq 'verify_time'">selected="selected"</if>>{pigcms{:L_("完成订单时间")}</option>
						</select>
						<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						<font color="#000"><php>echo C('config.appoint_worker_name')?:'技师';</php>：</font>
						<select id="merchant_worker_id" name="merchant_worker_id">
						<option value="0">{pigcms{:L_("工作人员列表")}</option>
							<volist name="merchant_worker_list" id="vo">
								<option value="{pigcms{$key}" <if condition="$key eq $_GET['merchant_worker_id']">selected="selected"</if>>{pigcms{$vo}</option>
							</volist>
						</select>

                        <font color="#000">支付状态：</font>
                        <select name="paid">
                            <option value="-1" <if condition="$_GET['paid'] eq '-1'">selected="selected"</if>>全部</option>
                            <option value="-2" <if condition="$_GET['paid'] eq '-2'">selected="selected"</if>>未支付</option>
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
						</select>
						
						{pigcms{:L_("支付方式")}: 
						<select id="pay_type" name="pay_type">
								<option value="" <if condition="'' eq $pay_type">selected="selected"</if>>{pigcms{:L_("全部支付方式")}</option>
							<volist name="pay_method" id="vo">
								<option value="{pigcms{$key}" <if condition="$key eq $pay_type">selected="selected"</if>>{pigcms{$vo.name}</option>
							</volist>
								<option value="balance" <if condition="'balance' eq $pay_type">selected="selected"</if>>{pigcms{:L_("余额支付")}</option>
						</select>
						<br/>
						
						<button onclick="exports()" class="button" style="float:right;padding: 6px 10px;margin-top:1rem;margin-right: 5rem;">{pigcms{:L_("导出订单")}</button>
						<input type="submit" value="{pigcms{:L_("查询")}" class="button" style="float:right;padding: 6px 10px;margin-top:1rem;"/>　

						
					</form>
					
				</div>
				<div class="col-xs-12" style="padding-left:0px;padding-right:0px;">
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("订单ID")}</th>
									<!-- <th>{pigcms{:L_("定金")}</th> -->
									<th>{pigcms{:L_("店铺名称")}</th>
									<if condition="!C('butt_open')"><th>{pigcms{:L_("店铺地址")}</th></if>
									<th>{pigcms{:L_("服务类型")}</th>
									<th>{pigcms{:L_("用户信息")}</th>
                                    <th>{pigcms{:L_("订单状态")}</th>
									<if condition="!C('butt_open')"><th>{pigcms{:L_("描述")}</th></if>
									<th>{pigcms{:L_("订单备注")}</th>
									<th class="button-column" width="200">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<?php if(!empty($order_list)): ?>
								<volist name="order_list" id="vo">
									<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
									<td width="70">{pigcms{$vo.order_id}</td>
										<!-- <td width="50"><if condition='$vo["payment_status"] gt 0'><if condition='$vo["product_id"] gt 0'>{pigcms{$config['Currency_symbol']}{pigcms{$vo.product_payment_price}<else />{pigcms{$config['Currency_symbol']}{pigcms{$vo.payment_money}</if><else/>--</if></td> -->
										<td>{pigcms{$vo.store_name}</td>
										<if condition="!C('butt_open')"><td width="150">{pigcms{$vo.store_adress}</td></if>
										<td width="80">
											<if condition="$vo['appoint_type'] eq 0"><span style="color:red">{pigcms{:L_("到店")}</span>
											<elseif condition="$vo['appoint_type'] eq 1" /><span style="color:red">{pigcms{:L_("上门")}</span>
											</if>
										</td>
										<td>
											{pigcms{:L_("用户ID：")}{pigcms{$vo.uid}<br/>
											{pigcms{:L_("用户名：")}{pigcms{$vo.nickname}<br/>
											{pigcms{:L_("订单手机号：")}{pigcms{$vo.phone}<br/>
										</td>

										<td>
                                            <if condition="$vo['paid'] eq 0"><span style="color:red">{pigcms{:L_("未支付")}</span>
                                                <elseif condition="$vo['paid'] eq 1" />
                                                    <php>if ($vo['payment_status']!=1) {</php>
                                                        <span style="color:green">{pigcms{:L_("已支付")}</span>
                                                    <php>} elseif ($vo['service_status']<=0 && $vo['is_initiative']<=0) {</php>
                                                        <span style="color:green">定金已支付</span><span style="color:red">尾款待支付</span>
                                                    <php>} elseif ($vo['service_status']>0 && $vo['is_initiative']<=0) {</php>
                                                        <span style="color:green">已服务</span>
<!--                                                        <span style="color:red">尾款未支付</span>-->
                                                    <php>} else {</php>
                                                        <span style="color:green">{pigcms{:L_("已支付")}</span>
                                                    <php>}</php>
                                                <elseif condition="$vo['paid'] eq 2" /><span style="color:blue">{pigcms{:L_("已退款")}</span>
                                                <elseif condition="$vo['paid'] eq 3" /><span style="color:green">{pigcms{:L_("已支付")}</span>
                                            </if>&nbsp;|&nbsp;
                                            <if condition="$vo['is_del'] eq 0 AND ($vo['paid'] neq 3)"><span style="color:green">{pigcms{:L_("未取消")}</span>
                                                <elseif condition="($vo['is_del'] eq 1) || ($vo['is_del'] eq 5) || ($vo['paid'] eq 3)" /><span style="color:red">{pigcms{:L_("已取消【用户】")}</span>
                                                <elseif condition="$vo['is_del'] eq 2" /><span style="color:red">{pigcms{:L_("已取消【平台】")}</span>
                                                <elseif condition="$vo['is_del'] eq 3" /><span style="color:red">{pigcms{:L_("已取消【商家】")}</span>
                                                <elseif condition="$vo['is_del'] eq 4" /><span style="color:red">{pigcms{:L_("已取消【店员】")}</span>
                                            </if>
											<if condition='$vo["complete_source"] eq 2' >
												<if condition='$vo["service_status"] gt 0'>
													<font color="green">{pigcms{:L_("已服务")}</font>
												<else />
												<font color="red"><?php echo $config['appoint_worker_name'] ? $config['appoint_worker_name'] : L_('技师'); ?>{pigcms{:L_("已服务")}
                          <if condition="$vo['is_initiative']">{pigcms{:L_("待验证")} <else/> {pigcms{:L_("用户未付余款")}</if></font>
												</if>
                                            <elseif condition="$vo['service_status'] == 0"  />
										   		<font color="red">{pigcms{:L_("未服务")}</font>
										   		<if condition='(($vo["is_del"] eq 0) && ($vo["paid"] eq 1)) ||(($vo["is_del"] eq 0) && ($vo["paid"] eq 1) && ($vo["payment_status"] eq 0))'><a href="{pigcms{:U('appoint_verify',array('order_id'=>$vo['order_id']))}" class="group_verify_btn"><span title="请确认订单是否已完成后再点击">{pigcms{:L_("完成订单")}</span></a></if>
										   	<elseif condition="$vo['service_status'] == 1" />
										   		<font color="green">{pigcms{:L_("已服务")}</font>
											<elseif condition="$vo['service_status'] == 2" />
										   		<font color="green">{pigcms{:L_("已评价")}</font>
										   	</if>
											
                                            <br/>
											{pigcms{:L_("下单时间：")}{pigcms{$vo['order_time']|date='Y-m-d H:i:s',###}<br/>
											<?php if(empty($vo['paid'])): ?>
												{pigcms{:L_("付款时间：无")}
											<?php else : ?>
												{pigcms{:L_("定金付款时间：")}<if condition="$vo['pay_time']">{pigcms{$vo['pay_time']|date='Y-m-d H:i:s',###}<else/>无</if> <br/>
												{pigcms{:L_("尾款付款时间：")}<if condition="$vo['product_real_balace_pay_time'] && $vo['is_initiative'] gt 0">{pigcms{$vo['product_real_balace_pay_time']|date='Y-m-d H:i:s',###}<elseif condition="$vo['user_pay_time'] && $vo['is_initiative'] gt 0"/>{pigcms{$vo['user_pay_time']|date='Y-m-d H:i:s',###}<else/>无</if>
											<?php endif; ?><br/>
                                            	{pigcms{:L_("订单类型：")}<if condition='($vo["type"] eq 0) or ($vo["type"] eq 2)'>{pigcms{:L_("商家")}<else/>{pigcms{:L_("平台")}</if>
												<if condition='$vo["is_del"] neq 0'>
										 <br/>
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
										</td>
									<if condition="!C('butt_open')"><td width="160">{pigcms{$vo.content}</td></if>
									<td width="200">
										<input type="text" name="note" value="{pigcms{$vo.note}" style="width: 120px;">
										<button class="btn btn-success" style="float:right;display:inline;padding: 1px 4px;border-radius: 3px;" onclick="change_note(this,{pigcms{$vo.order_id});">{pigcms{:L_("提交")}</button>
									</td>
                                    <td width="80" style="text-align:center;">
                                        <a title="{pigcms{:L_(" 操作订单")}" href="javascript:void(0);" class="green" onclick="window.top.artiframe('{pigcms{:U('Appoint/order_detail',array('order_id'=>$vo['order_id'],'frame_show'=>true))}','订单详情',920,620,true,false,false,false,'order_detail_log',true);">
                                            查看详情
                                        </a>
                                        <if condition='($vo["is_del"] eq 0) && ($vo["paid"] eq 0)'>
                                            <br/><a href="javascript:void(0)" data-order-id="{pigcms{$vo['order_id']}" class="appoint_del" style="padding-right:8px;" class="red" title="{pigcms{:L_(" 取消订单")}">
                                                取消订单
                                            </a>
                                        </if>
                                        <br/><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U("Appoint/order_trace",array("order_id"=>$vo["order_id"]))}','服务轨迹',750,600,true,false,false,closebtn,'content',true,false);">服务轨迹</a>
                                    </td>
									</tr>
								</volist>
								<?php else : ?>
									<tr><td colspan="<if condition="!C('butt_open')">9<else/>7</if>" style="color:red;text-align:center;">{pigcms{:L_("暂无订单。")}</td></tr>
								<?php endif; ?>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	$(function(){
		$('#group_id').change(function(){
			$('#frmselect').submit();
		});
	});
    var closebtn = [
        { name: '关闭' }
    ];
	$('.appoint_del').click(function(){
		var url ="{pigcms{:U('ajax_merchant_del')}";
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
	function change_note(obj,order_id){
		var note = $(obj).siblings('input[name="note"]').val();
		$.post('{pigcms{:U("change_appoint_order_note")}',{order_id:order_id,note:note},function(date){
			alert(date.msg)
			window.location.reload();
		},'json');
	}

 var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Appoint/export')}"
</script>

<style type="text/css" scoped>
	input,select,button{
		margin-right: 1rem;
		padding: 5px 4px 6px;
		line-height: 1.2rem;
	}
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>
