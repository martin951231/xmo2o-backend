<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-comments-o comments-o-icon"></i>
			<li class="active"><a href="{pigcms{:U('Group/index')}">{pigcms{$config.group_alias_name}{pigcms{:L_("列表")}</a></li>
			<if condition="$now_group">
			<li>{pigcms{$now_group.s_name}</li>
			</if>
			<li>{pigcms{:L_("订单列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<form action="{pigcms{:U('Group/order_list')}" method="get">
					<input type="hidden" name="c" value="Group"/>
                    <input type="hidden" name="a" value="order_list"/>
                    <input type="hidden" name="group_id" value="{pigcms{$_GET['group_id']}"/>
					
					<select name="searchtype">
						<option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
						<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>{pigcms{:L_("支付流水号")}</option>
						<option value="third_id" <if condition="$_GET['searchtype'] eq 'third_id'">selected="selected"</if>>{pigcms{:L_("第三方支付流水号")}</option>
						<option value="s_name" <if condition="$_GET['searchtype'] eq 's_name'">selected="selected"</if>>{pigcms{$config.group_alias_name}{pigcms{:L_("名称")}</option>
						<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("客户名称")}</option>
						<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("客户电话")}</option>
					</select>
					<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
					<font color="#000" style="margin-left:10px;">{pigcms{:L_("日期筛选：")}</font>
					<select name="searchTimeType">
						<option value="add_time" <if condition="$_GET['searchTimeType'] eq 'add_time'">selected="selected"</if>>{pigcms{:L_("下单时间")}</option>
						<option value="pay_time" <if condition="$_GET['searchTimeType'] eq 'pay_time'">selected="selected"</if>>{pigcms{:L_("支付时间")}</option>
                        <option value="verify_time" <if condition="$_GET['searchTimeType'] eq 'verify_time'">selected="selected"</if>>{pigcms{:L_("消费时间")}</option>
                        <option value="refund_time" <if condition="$_GET['searchTimeType'] eq 'refund_time'">selected="selected"</if>>{pigcms{:L_("退款时间")}</option>
					</select>
					<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
					<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>

					{pigcms{:L_("订单状态筛选")}: 
					<select id="status" name="status" style="margin-right:10px;">
						<volist name="status_list" id="vo">
							<option value="{pigcms{$key}" <if condition="$key eq $status">selected="selected"</if>>{pigcms{$vo}</option>
						</volist>
					</select>
					{pigcms{:L_("支付方式筛选")}: 
						<select id="pay_type" name="pay_type">
							<option value="" <if condition="$_GET['pay_type'] eq $pay_type">selected="selected"</if>>{pigcms{:L_("全部支付方式")}</option>
						<volist name="pay_method" id="vo">
							<option value="{pigcms{$key}" <if condition="$key eq $pay_type">selected="selected"</if>>{pigcms{$vo.name}</option>
						</volist>
							<option value="balance" <if condition="$_GET['pay_type'] eq 'balance'">selected="selected"</if>>{pigcms{:L_("余额支付")}</option>
					</select>
					<input type="submit" value="{pigcms{:L_("查询")}" class="button"/>　　
					
					<a class="btn btn-success"  href="javascript:void(0)" onclick="exports()"  style="float:right;">{pigcms{:L_("导出订单")}</a>
				</form>
				<div class="col-xs-12" style="padding-left:0px;padding-right:0px;">
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("订单编号")}</th>
									<th>{pigcms{$config.group_alias_name}{pigcms{:L_("名称")}</th>
									<th>{pigcms{:L_("订单信息")}</th>
									<th>{pigcms{:L_("订单类型")}</th>
									<th>{pigcms{:L_("用户信息")}</th>
									<th>{pigcms{:L_("订单备注")}</th>
									<th>{pigcms{:L_("订单状态")}</th>
									<th class="button-column">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<volist name="order_list" id="vo">
									<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
										<td width="100">{pigcms{$vo.real_orderid}</td>

                                        <if condition="$config['single_system_type'] eq 'village_group'">
                                            <td width="200">{pigcms{$vo.s_name}</td>
                                            <else/>
                                            <td width="200"><a href="{pigcms{$config.site_url}/index.php?g=Group&c=Detail&group_id={pigcms{$vo.group_id}" target="_blank" data-title="{pigcms{$vo.name}" data-pic="{pigcms{$vo.list_pic}" class="group_name">{pigcms{$vo.s_name}</a></td>
                                        </if>
										<td width="150">
											{pigcms{:L_("数量：")}{pigcms{$vo.num}<br/>
											{pigcms{:L_("总价：")}{pigcms{$vo.total_money}<br/>
										</td>
										<td width="200">
											<if condition="$vo['is_group_combine'] eq '1'">
												{pigcms{:L_("优惠组合")}
											<else/>
												<switch name="vo['tuan_type']">
													<case value="0">{pigcms{$config.group_alias_name}券</case>
													<case value="1">{pigcms{:L_("代金券")}</case>
													<case value="2">{pigcms{:L_("实物")}</case>
												</switch>
											</if>
										</td>
										<td width="200">
											{pigcms{:L_("用户ID：")}{pigcms{$vo.uid}<br/>
											{pigcms{:L_("用户名：")}{pigcms{$vo.nickname}<br/>
											<!--{pigcms{:L_("用户手机号：")}{pigcms{$vo.phone}<br/-->
											{pigcms{:L_("订单手机号：")}{pigcms{$vo.group_phone}<br/>
										</td>
									<td width="200">
										{pigcms{:L_("备注：")}
										<input type="text" name="note_info" value="{pigcms{$vo.note_info}" style="width: 80px;">
										<button class="btn btn-success" style="float:right;display:inline;padding: 1px 4px;border-radius: 3px;" onclick="change_note(this,{pigcms{$vo.order_id});">{pigcms{:L_("提交")}</button>


									</td>
										<td width="200">
										<if condition="$vo['status'] eq 3">
												<font color="red">{pigcms{:L_("已退款")}</font>
											<elseif condition="$vo['paid']" />
												<if condition="$vo['pay_type'] eq 'offline' AND empty($vo['third_id'])" >
													<font color="red">{pigcms{:L_("线下支付")}&nbsp;{pigcms{:L_("未付款")}</font>
												<elseif condition="$vo['status'] eq 0" />
													<font color="green">{pigcms{:L_("已付款")}</font>&nbsp;
                                                    <php>if($vo['tuan_type'] != 2 && $vo['is_pay_bill'] == 0){</php>
                                                        <font color="red">{pigcms{:L_("未消费")}</font>
                                                    <php>}else if ($vo['tuan_type'] != 2 && $vo['is_pay_bill'] == 1){</php>
                                                        <font color="red">{pigcms{:L_("部分消费")}</font>
													<php>}else{</php>
														<php>if($vo['is_pick_in_store']){</php>
															<font color="red">{pigcms{:L_("未取货")}</font>
														<php>}else{</php>
																<php>if($vo['express_id']){</php>	
																	<font color="red">{pigcms{:L_("未确认收货")}</font>
																<php>}else{</php>
																	<font color="red">{pigcms{:L_("未发货")}</font>
																<php>}</php>
														<php>}</php>
													<php>}</php>
												<elseif condition="$vo['status'] eq 1"/>
													<php>if($vo['tuan_type'] != 2){</php>
														<font color="green">{pigcms{:L_("已消费")}</font>
													<php>}else{</php>
														<php>if($vo['is_pick_in_store']){</php>
															<font color="green">{pigcms{:L_("已取货")}</font>
														<php>}else{</php>
															<font color="green">{pigcms{:L_("已收货")}</font>
														<php>}</php>
													<php>}</php>&nbsp;
													<font color="red">{pigcms{:L_("待评价")}</font>
												<elseif condition="$vo['status'] eq 3"/>
													<font color="red">{pigcms{:L_("已退款")}</font>
												<elseif condition="$vo['status'] eq 4"/>
													<font color="red">{pigcms{:L_("用户已取消")}</font>
                                                <elseif condition="$vo['status'] eq 5"/>
                                                    <font color="red">{pigcms{:L_("未完全消费")}</font>
                                                <elseif condition="$vo['status'] eq 6"/>
                                                    <font color="red">{pigcms{:L_("已取消")}</font>&nbsp;
                                                <elseif condition="$vo['status'] eq 7"/>
                                                    <font color="red">{pigcms{:L_("退款失败")}</font>&nbsp;
												<else/>
													<font color="green">{pigcms{:L_("已完成")}</font>
												</if>
											<else/>
                                                <if condition="$vo['status'] eq 4">
                                                    <font color="red">{pigcms{:L_("未付款且已取消")}</font>
                                                    <else />
                                                    <font color="red">{pigcms{:L_("未付款")}</font>
                                                </if>
											</if><br/>
											{pigcms{:L_("下单时间：")}{pigcms{$vo['add_time']|date='Y-m-d H:i:s',###}<br/>
											<if condition="$vo['paid']">{pigcms{:L_("付款时间：")}{pigcms{$vo['pay_time']|date='Y-m-d H:i:s',###}</if>
										</td>
										<td class="button-column" width="40">
											<a title="{pigcms{:L_("操作订单")}" data-title="{pigcms{:L_("操作订单")}" class="green layer-iframe" style="padding-right:8px;" href="{pigcms{:U('Group/order_detail',array('order_id'=>$vo['order_id']))}">
												<i class="ace-icon fa fa-search bigger-130"></i>
											</a>
										</td>
									</tr>
								</volist>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/date-picker/index.js"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}js/date-picker/index.css" />
<script>
	function change_note(obj,order_id){
		var note_info = $(obj).siblings('input[name="note_info"]').val();
		$.post('{pigcms{:U("Store/change_group_order_note")}',{order_id:order_id,note_info:note_info,is_mer:true},function(date){
			alert(date.msg)
			window.location.reload();
		},'json');
	}
	$(function(){
		$('#group_id').change(function(){
			$('#frmselect').submit();
		});
		
	});
	   var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Group/export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>
