<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-desktop"></i>
			<li class="active"><a href="{pigcms{:U('Deliver/user')}">{pigcms{:L_("配送管理")}</a></li>
			<!-- <li>{pigcms{$now_group.appoint_name}</li> -->
			<li class="active"><a href="{pigcms{:U('Deliver/user')}">{pigcms{:L_("配送员列表")}</a></li>
			<li class="active"><a href="{pigcms{:U('Deliver/log_list', array('uid'=>$user['uid']))}" class="on">【{pigcms{$user['name']}{pigcms{:L_("】的配送记录")}</a></li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12" style="padding-left:0px;padding-right:0px;">
	                <form id="myform" method="post" action="{pigcms{:U('Deliver/log_list', array('uid'=>$user['uid']))}" >
		                <div style="display:inline-block;margin-top: 8px;"><font color="#000">{pigcms{:L_("配送开始时间：")}</font></div>
		                <input type="text" class="input fl" name="begin_time" style="width:160px;" value="{pigcms{$begin_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm:ss'})"/>&nbsp;&nbsp;&nbsp;到&nbsp;&nbsp;&nbsp;
		                <input type="text" class="input fl" name="end_time" style="width:160px;" value="{pigcms{$end_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm:ss'})"/>&nbsp;&nbsp;&nbsp;

                        <div style="display:inline-block;margin-top: 8px;"><font color="#000">{pigcms{:L_("店铺：")}</font></div>
                        <select id="store_id" name="store_id" style="margin-right: 20px">
                            <option value="0">{pigcms{:L_("全部")}</option>
                            <volist name="waimai_store" id="s">
                                <option value="{pigcms{$s.store_id}" <if condition="$store_id EQ $s['store_id']">selected="selected"</if>>{pigcms{$s.name}</option>
                            </volist>
                        </select>

		                <input type="submit" style="height: 32px;width: 50px;" />
		            </form>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
	<!-- 								<th>{pigcms{:L_("订单")}ID</th> -->
									<th>{pigcms{:L_("订单来源")}</th>
									<!--th>{pigcms{:L_("配送员类型")}</th-->
									<th>{pigcms{:L_("店铺名称")}</th>
									<th>{pigcms{:L_("客户昵称")}</th>
									<th>{pigcms{:L_("客户手机")}</th>
									<th>{pigcms{:L_("客户地址")}</th>
									<!--th>{pigcms{:L_("支付方式")}</th-->
									<th>{pigcms{:L_("支付状态")}</th>
									<th>{pigcms{:L_("订单价格")}</th>
									<th>{pigcms{:L_("配送状态")}</th>
									<th>{pigcms{:L_("开始时间")}</th>
									<th>{pigcms{:L_("结束时间")}</th>
									<th>{pigcms{:L_("应收现金")}</th>
									<!--th>{pigcms{:L_("创建时间")}</th-->
									
								</tr>
							</thead>
							<tbody>
							<if condition="is_array($supply_info)">
								<volist name="supply_info"  id="vo">
									<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
<!-- 										<td width="30">{pigcms{$vo.order_id}</td> -->
										<td><if condition="$vo['item'] eq 0">{pigcms{$config.meal_alias_name}<elseif condition="$vo['item'] eq 1" />{pigcms{:L_("外送系统")}<elseif condition="$vo['item'] eq 2" />{pigcms{$config.shop_alias_name}</if></td>
										<!--td width="50">{pigcms{$vo.group}</td-->
										<td>{pigcms{$vo.storename}</td>
										<td>{pigcms{$vo.username}</td>
										<td>
											<if condition="$vo['virtual_phone_arr']">
												{pigcms{$vo['userphone']|phone_show}  
							                    <?php if($vo['virtual_phone_overtime'] >= time()){ ?>
							                         / 隐私号码
							                        {pigcms{$vo['virtual_phone_arr'][0][0]}
							                        <?php if($vo['virtual_phone_arr'][0][1]){ ?>
							                            {pigcms{:L_('转')}{pigcms{$vo['virtual_phone_arr'][0][1]}
							                        <?php } ?>
							                    <?php } ?>
							                <else/>
							                    {pigcms{$vo['userphone']}                    
							                </if>
										</td>
										<td>{pigcms{$vo.aim_site}</td>
										<!--td width="50">{pigcms{$vo.pay_type}</td-->
										<td>{pigcms{$vo.paid}</td>
										<td>{pigcms{$vo.money|floatval}</td>
										<td>{pigcms{$vo.order_status}</td>
										<td>{pigcms{$vo.start_time}</td>
										<td>{pigcms{$vo.end_time}</td>
										<td style="color:red">{pigcms{$vo.deliver_cash|floatval}</td>
<!-- 										<td width="80">{pigcms{$vo.end_time}</td> -->
										<!--td width="50">{pigcms{$vo.create_time}</td-->
									</tr>
								</volist>
							<else/>
								<tr><td class="textcenter red" colspan="16">{pigcms{:L_("列表为空！")}</td></tr>
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
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
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
		$("#order_number").focus(function(){
			$("#phone").val("");
		});
		$("#phone").focus(function(){
			$("#order_number").val("");
		});
		$("#search").click(function(){
			var orderNum = $("#order_number").val();
			var phone = $("#phone").val();
			search(orderNum, phone)
		});
		function search(orderNum, phone) {
			var orderNum =  orderNum || 0;
			var phone = phone || 0;
			location.href = "{pigcms{:U('Merchant/Deliver/deliverList')}"+"&orderNum="+orderNum+"&phone="+phone+"&selectStoreId="+selectStoreId+"&selectUserId="+selectUserId;
		}
	});
</script>
<include file="Public:footer"/>
