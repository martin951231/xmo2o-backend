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

	<!--style type="text/css">
                    #shopList .table-striped{table-layout:fixed;word-break:keep-all;             /* {pigcms{:L_("不换行")} */
white-space:nowrap;            /* {pigcms{:L_("不换行")} */
overflow:hidden;                  /* {pigcms{:L_("内容超出宽度时隐藏超出部分的内容")} */
text-overflow:ellipsis;  }
                #shopList .table-striped td{width:100%;
word-break:keep-all;             /* {pigcms{:L_("不换行")} */
white-space:nowrap;            /* {pigcms{:L_("不换行")} */
overflow:hidden;                  /* {pigcms{:L_("内容超出宽度时隐藏超出部分的内容")} */
text-overflow:ellipsis;            /* {pigcms{:L_("当对象内文本溢出时显示省略标记(...) ；需与overflow:hidden;一起使用。")}*/}
    </style-->
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
			<div class="form-group" style="margin:0;">
					<form action="{pigcms{:U('Appoint/allot_order_list')}" method="get">
						<input type="hidden" name="c" value="Appoint"/>
						<input type="hidden" name="a" value="allot_order_list"/>
						<input type="hidden" name="appoint_id" value="{pigcms{$_GET.appoint_id}"/>
						
						
						{pigcms{:L_("搜索")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						<select name="searchtype">
                            <option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>{pigcms{:L_("订单ID")}</option>
                            <option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
							<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>{pigcms{:L_("订单流水号")}</option>
							<option value="third_id" <if condition="$_GET['searchtype'] eq 'third_id'">selected="selected"</if>>{pigcms{:L_("第三方支付流水号")}</option>
							<!--<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("客户名称")}</option>
							<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("客户电话")}</option>-->
						</select>
						<!--
						<font color="#000">{pigcms{:L_("日期筛选：")}</font>
						<select name="searchTimeType">
							<option value="order_time" <if condition="$_GET['searchTimeType'] eq 'order_time'">selected="selected"</if>>{pigcms{:L_("下单时间")}</option>
							<option value="user_pay_time" <if condition="$_GET['searchTimeType'] eq 'user_pay_time'">selected="selected"</if>>{pigcms{:L_("尾款支付时间")}</option>
							<option value="verify_time" <if condition="$_GET['searchTimeType'] eq 'verify_time'">selected="selected"</if>>{pigcms{:L_("验证服务时间")}</option>
						</select>
						<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
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
						
						{pigcms{:L_("支付方式筛选")}: 
						<select id="pay_type" name="pay_type">
								<option value="" <if condition="'' eq $pay_type">selected="selected"</if>>{pigcms{:L_("全部支付方式")}</option>
							<volist name="pay_method" id="vo">
								<option value="{pigcms{$key}" <if condition="$key eq $pay_type">selected="selected"</if>>{pigcms{$vo.name}</option>
							</volist>
								<option value="balance" <if condition="'balance' eq $pay_type">selected="selected"</if>>{pigcms{:L_("余额支付")}</option>
						</select>
						-->
						<input type="submit" value="{pigcms{:L_("查询")}" class="button"/>　
						<a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">{pigcms{:L_("导出订单")}</a>
					</form>
				</div>
				<div class="col-xs-12" style="padding-left:0px;padding-right:0px;">
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
							<tr>
								<th>{pigcms{:L_("订单编号")}</th>
								<th>{pigcms{:L_("定金")}</th>
								<!--<th>{pigcms{:L_("服务类型")}</th>-->
								<th>{pigcms{:L_("用户信息")}</th>
								<if condition="!C('butt_open')"><th>{pigcms{:L_("描述")}</th></if>
								<th>{pigcms{:L_("订单状态")}</th>
								<th class="button-column">{pigcms{:L_("操作")}</th>
							</tr>
							</thead>
							<tbody>
							<?php if(!empty($order_list)): ?>
								<volist name="order_list" id="vo">
									<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
									<td width="70">{pigcms{$vo.order_id}</td>
									<td width="50">{pigcms{$config['Currency_symbol']}{pigcms{$vo.payment_money}</td>

									<!--<td>
                                        <if condition="$vo['appoint_type'] eq 0"><span style="color:red">{pigcms{:L_("到店")}</span>
                                        <elseif condition="$vo['appoint_type'] eq 1" /><span style="color:red">{pigcms{:L_("上门")}</span>
                                        </if>
                                    </td>-->
									<td>
										{pigcms{:L_("用户ID：")}{pigcms{$vo.uid}<br/>
										{pigcms{:L_("用户名：")}{pigcms{$vo.nickname}<br/>
										{pigcms{:L_("订单手机号：")}{pigcms{$vo.phone}<br/>
									</td>
									<if condition="!C('butt_open')"><td width="290">{pigcms{$vo.content}</td></if>
									<td>
										<if condition="$vo['paid'] eq 0"><span style="color:red">{pigcms{:L_("未支付")}</span>
											<elseif condition="$vo['paid'] eq 1" /><span style="color:green">{pigcms{:L_("已支付")}</span>
											<elseif condition="$vo['paid'] eq 2" /><span style="color:red">{pigcms{:L_("已退款")}</span>
										</if>


										<if condition='$vo["complete_source"] eq 2' >
											<if condition='$vo["service_status"] eq 1'>
												<font color="green">{pigcms{:L_("已服务")}</font>
												<else />
												<font color="red"><?php echo $config['appoint_worker_name'] ? $config['appoint_worker_name'] : L_('技师'); ?>{pigcms{:L_("已服务，用户未付余款")}</font>
											</if>

											<if condition='$vo["service_status"] eq 0'><a href="{pigcms{:U('appoint_verify',array('order_id'=>$vo['order_id']))}" class="group_verify_btn">{pigcms{:L_("验证服务")}</a></if>
											<elseif condition="$vo['service_status'] == 0"  />
											<font color="red">{pigcms{:L_("未服务")}</font>
											<if condition='$vo["store_id"] && ($vo["is_del"] eq 0)&& ($vo["paid"] eq 1)'><a href="{pigcms{:U('appoint_verify',array('order_id'=>$vo['order_id']))}" class="group_verify_btn">{pigcms{:L_("验证服务")}</a></if>
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
											{pigcms{:L_("付款时间：")}<if condition="$vo['pay_time']">{pigcms{$vo['pay_time']|date='Y-m-d H:i:s',###}<else/>无</if><br/>
										<?php endif; ?>
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
									<td width="80"  style="text-align:center;">
										<a title="{pigcms{:L_("分配店铺")}" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('Appoint/allot_store',array('order_id'=>$vo['order_id']))}">
											<i class="ace-icon fa fa-search bigger-130"></i>
										</a>

										<if condition='($vo["is_del"] eq 0) && ($vo["paid"] eq 0)'>
											<a href="javascript:void(0)" data-order-id="{pigcms{$vo['order_id']}" class="appoint_del" style="padding-right:8px;" class="red" title="{pigcms{:L_("取消订单")}">
												<i class="ace-icon fa fa-times-circle-o bigger-130"></i>
											</a>
										</if>
									</td>
									</tr>
								</volist>
							<?php else : ?>
								<tr><td colspan="<if condition="!C('butt_open')">6<else/>5</if>" style="color:red;text-align:center;">{pigcms{:L_("暂无订单。")}</td></tr>
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
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
	$(function(){
		$('.handle_btn').live('click',function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("分配店铺")}',
				padding: 0,
				width: 720,
				height: 700,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '38.2%',
				opacity:'0.4'
			});
			return false;
		});

		$('#group_id').change(function(){
			$('#frmselect').submit();
		});
	});


	$('#merchant_worker_id').change(function(){
		var merchant_worker_id = $(this).val();
		var Url="{pigcms{:U('Appoint/order_list')}"+'&merchant_worker_id='+merchant_worker_id;
		location.href=Url;
	});

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

	var url = "{pigcms{$config.site_url}"
	var export_url = "{pigcms{:U('Appoint/export')}"

</script>

	<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>
