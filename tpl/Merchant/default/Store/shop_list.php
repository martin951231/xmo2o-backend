<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}js/qweb/qwebchannel.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/storestaffBase.js?1"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>

	</head>
	<body>
		<div class="headerBox">
			<eq name='shop_type' value='2'>
				<div class="txt">{pigcms{$config.mall_alias_name|default=L_("商城")}</div>
			<else/>
				<div class="txt">{pigcms{$config.shop_alias_name}</div>			
			</eq>		
			
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_('返回首页')}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_('刷新页面')}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink cur" data-url="{pigcms{:U('shop_list',array('shop_type'=>$shop_type))}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("订单列表")}</div>
					</li>
					<li class="urlLink" data-url="{pigcms{:U('goods',array('shop_type'=>$shop_type))}">
						<div class="icon list"></div>
						<div class="text">{pigcms{:L_("待发商品清单")}</div>
					</li>
					<li class="urlLink" data-url="{pigcms{:U('goods_sale',array('shop_type'=>$shop_type))}">
						<div class="icon list"></div>
						<div class="text">{pigcms{:L_("商品销售统计")}</div>
					</li>
                    <if condition="$config['eleme_app_key'] OR $config['meituan_sign_key']">
                    <li class="urlLink" data-url="{pigcms{:U('shop_order_report_form',array('shop_type'=>$shop_type))}">
                        <div class="icon list"></div>
                        <div class="text">{pigcms{:L_("各平台订单统计")}</div>
                    </li>
                    </if>
					<if condition="$config['pay_in_store']">
					<li class="urlLink" data-url="{pigcms{:U('market',array('shop_type'=>$shop_type))}">
						<div class="icon list"></div>
						<div class="text">{pigcms{:L_("线下零售")}</div>
					</li>
					</if>
				</ul>
			</div>
			<div class="rightMain">
				<div class="alert waring" style="background-color:#f9cdcd;border-color:#f9cdcd;color:#8c2a2a;display:none;">
					<i class="ice-icon fa fa-volume-up bigger-130"></i>
					<p>{pigcms{:L_("您有部分商品库存小于10,请及时")} <a title="{pigcms{:L_("库存报警商品列表")}"  data-title="{pigcms{:L_("库存报警商品列表")}" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('shop_goods_stock',array('shop_type'=>$shop_type))}">{pigcms{:L_("查看")}</a>！</p>
				</div>
				<div class="alert alert-block alert-success">
					<p>
						{pigcms{:L_("注意:在每行的输入框里可以通过输入您想要搜索的订单的关键词")}<br/>
						{pigcms{:L_("在对应的标题下输入对应的关键词后按【Enter】即可搜索")}<br/>
						{pigcms{:L_("自提类订单可使用扫码枪在“订单编号”输入框扫用户订单详情中的自提二维码来快速查询。")}
						<br/>
						<br/>
						{pigcms{:L_("页面每5秒会请求一次是否有需要接单的新订单，若有会一直语音提醒，并有弹层提示，关闭提示会关闭语音。")}
					</p>
				</div>
				<div class="form-group clearfix">
					<form action="{pigcms{:U('Store/shop_list')}" method="get">
						<input type="hidden" name="c" value="Store"/>
						<input type="hidden" name="a" value="shop_list"/>
						<input type="hidden" name="shop_type" value="{pigcms{$shop_type}"/>
						<input type="hidden" name="appoint_id" value="{pigcms{$_GET.appoint_id}"/>

						<div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
							<select name="searchtype">
								<option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
								<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>{pigcms{:L_("支付流水号")}</option>
								<option value="third_id" <if condition="$_GET['searchtype'] eq 'third_id'">selected="selected"</if>>{pigcms{:L_("第三方支付流水号")}</option>
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("客户名称")}</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("客户电话")}</option>
								<option value="fetch_number" <if condition="$_GET['searchtype'] eq 'fetch_number'">selected="selected"</if>>{pigcms{:L_("取单号")}</option>
							</select>
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" style="width:200px;"/>
						</div>
						<div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
							<font color="#000">{pigcms{:L_("日期筛选：")}</font>
							<select name="searchTimeType">
								<option value="create_time" <if condition="$_GET['searchTimeType'] eq 'create_time'">selected="selected"</if>>{pigcms{:L_("下单时间")}</option>
								<option value="pay_time" <if condition="$_GET['searchTimeType'] eq 'pay_time'">selected="selected"</if>>{pigcms{:L_("支付时间")}</option>
                                <option value="use_time" <if condition="$_GET['searchTimeType'] eq 'use_time'">selected="selected"</if>>{pigcms{:L_("核销时间")}</option>
							</select>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;-&nbsp;			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						</div>
						<div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
							<font color="#000">{pigcms{:L_("订单状态筛选：")}</font>
							<select id="status" name="status" >
								<volist name="status_list" id="vo">
									<option value="{pigcms{$key}" <if condition="$key eq $status">selected="selected"</if>>{pigcms{$vo}</option>
								</volist>
							</select>
						</div>
						<div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
							<font color="#000">{pigcms{:L_("支付方式筛选：")}</font> 
							<select id="pay_type" name="pay_type">
									<option value="" <if condition="'' eq $pay_type">selected="selected"</if>>{pigcms{:L_("全部支付方式")}</option>
								<volist name="pay_method" id="vo">
									<option value="{pigcms{$key}" <if condition="$key eq $pay_type">selected="selected"</if>>{pigcms{$vo.name}</option>
								</volist>
									<option value="balance" <if condition="'balance' eq $pay_type">selected="selected"</if>>{pigcms{:L_("余额支付")}</option>
							</select>
						</div>
						<div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
							<input type="submit" value="{pigcms{:L_("查询")}" class="btn btn-success" style="padding:2px 14px;"/>　
							<a  href="javascript:void(0)" onclick="exports()"  class="down_excel" style="float:right;padding:8px 14px;border:1px solid #629b58;color:#629b58;">{pigcms{:L_("导出订单")}</a>
						</div>
					</form>				
				</div>
					
				<div class="alert alert-block alert-success" style="margin:10px 0;">{pigcms{:L_("统计已支付且非取消和退款的订单：")}
					<b>{pigcms{:L_("应收总金额：")}{pigcms{$total_price|floatval}</b>　
					<b>{pigcms{:L_("在线支付总额：")}{pigcms{$online_price|floatval}</b>　
					<b>{pigcms{:L_("线下支付现金总额：")}{pigcms{$offline_price|floatval}</b>
				</div>
				<div class="grid-view">
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>									
								<th id="shopList_c1" width="50">{pigcms{:L_("取单号")}</th>
								<th id="shopList_c1" width="50">{pigcms{:L_("订单号")}</th>
								<th id="shopList_c1" width="80" class="hide_col">{pigcms{:L_("下单人信息")}</th>
								<th id="shopList_c0" width="80">{pigcms{:L_("订单来源")}/{pigcms{:L_("取货方式")}</th>
								<th id="shopList_c5" width="100">{pigcms{:L_("总价")}/{pigcms{:L_("应收现金")}</th>
								<th id="shopList_c5" width="100">{pigcms{:L_("线下现金支付")}</th>
								<th id="shopList_c3" width="90">
									<if condition="$type eq 'pay_time'">
										<if condition="$sort eq 'ASC'">
											<a href="{pigcms{:U('Store/shop_list', array('type' => 'pay_time', 'sort' => 'DESC', 'status' => $status,'shop_type'=>$_GET['shop_type']))}">{pigcms{:L_("支付时间")}<i class="menu-icon fa fa-sort-desc"></i></a>
										<elseif condition="$sort eq 'DESC'" />
											<a href="{pigcms{:U('Store/shop_list', array('type' => 'pay_time', 'sort' => 'ASC', 'status' => $status,'shop_type'=>$_GET['shop_type']))}">{pigcms{:L_("支付时间")}<i class="menu-icon fa fa-sort-asc"></i></a>
										<else />
											<a href="{pigcms{:U('Store/shop_list', array('type' => 'pay_time', 'sort' => 'DESC', 'status' => $status,'shop_type'=>$_GET['shop_type']))}">{pigcms{:L_("支付时间")}<i class="menu-icon fa fa-sort"></i></a>
										</if>
									<else />
										<a href="{pigcms{:U('Store/shop_list', array('type' => 'pay_time', 'sort' => 'DESC', 'status' => $status,'shop_type'=>$_GET['shop_type']))}">{pigcms{:L_("支付时间")}<i class="menu-icon fa fa-sort"></i></a>
									</if>
								</th>
								<th id="shopList_c3" width="80">{pigcms{:L_("期望送达时间")}</th>
                                <th id="shopList_c3" width="80">{pigcms{:L_("核销时间")}</th>
								<th id="shopList_c4" width="120" class="hide_col">{pigcms{:L_("支付状态")}</th>
								<th id="shopList_c4" width="70" class="hide_col">{pigcms{:L_("订单备注")}</th>
                                <if condition="empty($now_store['miaomaiyun_erp_storeid'])">
								<th id="shopList_c4" width="100">{pigcms{:L_("处理订单")}</th>
                                </if>
								<th id="shopList_c5" width="60" >{pigcms{:L_("查看商品详情")}</th>
							</tr>
						</thead>
						<tbody>
							<if condition="$order_list">
								<volist name="order_list" id="vo">
									<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
										<td><div class="tagDiv"><if condition="$vo['fetch_number']">{pigcms{$vo.fetch_number}</if></div></td>
										<td style="text-align:center;"><div class="tagDiv">{pigcms{$vo.real_orderid}</div></td>
										<td class="hide_col">
											<div class="tagDiv">
												<if condition="$vo['username'] || $vo['pick_username']">
													{pigcms{:L_("姓名：")}{pigcms{$vo.username|default=$vo['pick_username']}<br/>
													{pigcms{:L_("电话：")}
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
                                                    <br/>
                                                    {pigcms{:L_("收货地址：")}{pigcms{$vo.address|default=$vo['address']}<br/>
												</if>
											</div>
										</td>
										<td style="text-align:center;">
										<if condition="$vo['platform'] eq 1">{pigcms{:L_("饿了么")}/
										<php>if ($vo['is_pick_in_store'] == 4) {
                        echo "机器人配送";
                      }elseif ($vo['is_pick_in_store'] == 5) {</php>{pigcms{$vo.deliverinfo}<php>} else {</php>--<php>}</php><elseif condition="$vo['platform'] eq 2"/>{pigcms{:L_("美团")}/<php>if ($vo['is_pick_in_store'] == 5) {</php>{pigcms{$vo.deliverinfo}<php>} else {</php>--<php>}</php><elseif condition="$vo['order_from'] eq 6"/>{pigcms{:L_("线下购买")}/{pigcms{$vo.deliverinfo}
										<elseif condition="$vo['order_from'] eq 1" />
										{pigcms{:L_("商城")}/{pigcms{$vo.deliverinfo}
										<else />{pigcms{$config.shop_alias_name}/{pigcms{$vo.deliverinfo}</if></td>
                    <td style="text-align:center;">{pigcms{$vo.total_price|floatval} / <span style="color:green;"><strong>{pigcms{$vo['offline_price']|floatval}</strong><if condition="$vo['change_price_reason'] neq ''"><span style="color: #666;">({pigcms{$vo['change_price_reason']})</span></if>　
											<if condition="$vo['status'] eq 0 AND $vo['paid'] eq 0 AND $is_change eq 1 AND $vo['order_from'] neq 6">
                        <br/><br/>
												<a title="{pigcms{:L_("修改价格")}"  data-title="{pigcms{:L_("修改价格")}" class="green handle_btn" data-box_width="30%" data-box_height="95%" style="padding-right:8px;" href="{pigcms{:U('shop_change_price',array('order_id'=>$vo['order_id']))}">
													<i class="shortBtn">{pigcms{:L_("改价")}</i>
												</a>
											</if></span>
										</td>
                    <if condition="$vo['pay_type'] eq 'offline' AND third_id neq ''">
                      <td style="text-align:center;">{pigcms{$vo['offline_price']|floatval}</td>
                      <else />
                      <td style="text-align:center;">0</td>
                    </if>
										<if condition="$vo['pay_time']">
											<td style="text-align:center;">{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</td>
										<else />
											<td style="text-align:center;">{pigcms{:L_("未支付")}</td>
										</if>
										<if condition="$vo['expect_use_time']">
											<td style="text-align:center;">{pigcms{$vo.expect_use_time|date="Y-m-d H:i:s",###}</td>
										<else />
											<td style="text-align:center;">{pigcms{:L_("尽快")}</td>
										</if>

                                        <if condition="$vo['use_time']">
                                            <td style="text-align:center;">{pigcms{$vo.use_time|date="Y-m-d H:i:s",###}</td>
                                        <else />
                                            <td style="text-align:center;">--</td>
                                        </if>

										<td class="hide_col">
											<if condition="$vo['paid']==0 && $vo['pay_type']=='offline'">
												<button class="btn btn-success" style="float:right;display:inline;padding: 1px 4px;border-radius: 3px;" onclick="confirm_pay({pigcms{$vo.order_id});">{pigcms{:L_("确认支付")}</button>
												<else/>
												{pigcms{$vo.pay_status}<br/>
											</if>
											
											<if condition="$vo['platform'] eq 0">{pigcms{$vo.pay_type_str}</if>
										</td>
							<td width="160">
								{pigcms{:L_("备注：")}
								<input type="text" name="note" value="{pigcms{$vo.note}" style="width: 80px;">
								<button class="btn btn-success" style="float:right;display:inline;padding: 1px 4px;border-radius: 3px;" onclick="change_note(this,{pigcms{$vo.order_id});">{pigcms{:L_("提交")}</button></td>
                            <if condition="empty($now_store['miaomaiyun_erp_storeid'])">			
                            <td style="text-align: center;">
										<if condition="in_array($vo['status'],[0,7]) && $vo['paid'] eq 1 && $vo['order_status'] eq 0 &&($vo['is_pick_in_store'] eq 3 or $vo['is_pick_in_store'] eq 2)">
											<a class="green edit_btn" style="padding-right:8px;" href="javascript:;" onclick="take_order({pigcms{$vo['order_id']});">{pigcms{:L_("店员接单")}</a>
										<elseif condition="$vo['status'] eq 0 && $vo['paid'] eq 1"/>
											<if condition="($vo['is_pick_in_store'] eq 2 OR $vo['is_pick_in_store'] eq 1) AND $now_store['is_open_pick'] eq 1">
												<a  data-title="{pigcms{:L_("分配自提点")}" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('Store/pick',array('order_id'=>$vo['order_id']))}">{pigcms{:L_("分配自提点")}</a>
											<elseif condition="$vo['order_from'] eq 1 and $vo['is_pick_in_store'] eq 3 && in_array($now_store['deliver_type'],array(0,1,3,4)) && $config['mall_deliver_to_expess']"/>	<!-- {pigcms{:L_("商城订单")} -->
													<a title="{pigcms{:L_("查看订单详情")}" data-title="{pigcms{:L_("修改配送方式")}" class="green handle_btn" data-box_width="60%" data-box_height="95%" style="padding-right:8px;" href="{pigcms{:U('mall_order_detail',array('order_id'=>$vo['order_id']))}">{pigcms{:L_("更换成")}<if condition="$now_store['deliver_type'] eq 0 OR $now_store['deliver_type'] eq 3">{pigcms{$config.deliver_name}<elseif condition="$now_store['deliver_type'] eq 1 OR $now_store['deliver_type'] eq 4"/>{pigcms{:L_("商家配送")}</if></a>
											<elseif condition="$vo['order_from'] neq 1 and $vo['is_pick_in_store'] eq 3 AND $now_store['deliver_type'] neq 5 && $config['mall_deliver_to_expess']" />
												<a title="{pigcms{:L_("查看订单详情")}" data-title="{pigcms{:L_("修改配送方式")}" class="green handle_btn" data-box_width="60%" data-box_height="95%" style="padding-right:8px;" href="{pigcms{:U('mall_order_detail',array('order_id'=>$vo['order_id']))}">{pigcms{:L_("更换成")}<if condition="$now_store['deliver_type'] eq 0 OR $now_store['deliver_type'] eq 3">{pigcms{$config.deliver_name}<else/>{pigcms{:L_("商家配送")}</if></a>
                                            <elseif condition="$vo['order_status'] eq 1" />
                                                <if condition="$vo['supply'] gt 0 || $vo['platform'] gt 0 || $vo['is_pick_in_store'] gt 0 || !C('customization.again_create_distribution_order')">
                                                    <a data-title="{pigcms{:L_("已接单")}" class="green edit_btn" style="padding-right:8px;" href="javascript:;" >{pigcms{:L_("已接单")}</a>
                                                <else />
                                                    <a data-title="{pigcms{:L_("已接单(配送订单生成失败)")}" class="green edit_btn js-again-order js-again-order-{pigcms{$vo.order_id}" style="padding-right:8px;" href="javascript:;" js-order="{pigcms{$vo.order_id}">{pigcms{:L_("已接单")}<span style="font-size: 12px;color: #fd3434">(配送订单生成失败)</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;点击重试</a>
                                                </if>
                                            <elseif condition="$vo['is_pick_in_store'] neq 3" />
                        <if condition="C('customization.property_robot_jsdhwy')">
                          <a data-title="{pigcms{:L_("骑手送单")}" class="green edit_btn js-add-order js-add-order-{pigcms{$vo.order_id}" style="padding-right:8px;" href="javascript:;" js-order="{pigcms{$vo.order_id}">{pigcms{:L_("骑手送单")}</a>
                          <else />
                          <a data-title="{pigcms{:L_("接单")}" class="green edit_btn js-add-order js-add-order-{pigcms{$vo.order_id}" style="padding-right:8px;" href="javascript:;" js-order="{pigcms{$vo.order_id}">{pigcms{:L_("接单")}</a>
                        </if>
												
											</if>
										<elseif condition="$vo['status'] eq 1" />
                                            <if condition="$vo['express_id'] gt 0">
                                                <a data-title="{pigcms{:L_("已发货")}" class="green edit_btn" style="padding-right:8px;" href="javascript:;" >{pigcms{:L_("已发货")}</a>
                                                <else/>
                                                <if condition="$vo['supply'] gt 0 || $vo['platform'] gt 0 || $vo['is_pick_in_store'] gt 0 || !C('customization.again_create_distribution_order')">
                                                    <a data-title="{pigcms{:L_("已接单")}" class="green edit_btn" style="padding-right:8px;" href="javascript:;" >{pigcms{:L_("已接单")}</a>
                                                <else />
                                                    <a data-title="{pigcms{:L_("已接单(配送订单生成失败)")}" class="green edit_btn js-again-order js-again-order-{pigcms{$vo.order_id}" style="padding-right:8px;" href="javascript:;" js-order="{pigcms{$vo.order_id}">{pigcms{:L_("已接单")}<span style="font-size: 12px;color: #fd3434">(配送订单生成失败)</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;点击重试</a>
                                                </if>
                                            </if>
										<elseif condition="$vo['status'] eq 2" />
										<strong style="color: green">{pigcms{:L_("已消费")}</strong>
										<elseif condition="$vo['status'] eq 3" />
										<strong style="color: green">{pigcms{:L_("已评价")}</strong>
										<elseif condition="$vo['status'] eq 4" />
										<strong style="color: red">{pigcms{$vo['status_str']}</strong>
										<elseif condition="$vo['status'] eq 5" />
										<strong style="color: red">{pigcms{:L_("已取消")}</strong>
										<elseif condition="$vo['status'] eq 7 AND $vo['paid'] eq 1" />
										<a data-title="{pigcms{:L_("发货到自提点")}" class="green edit_btn js-send-order js-add-order-{pigcms{$vo.order_id}" style="padding-right:8px;" href="javascript:;" js-order="{pigcms{$vo.order_id}">{pigcms{:L_("发货到自提点")}</a>
										<elseif condition="$vo['status'] eq 8" />
										<strong style="color: green">{pigcms{:L_("已发货")}</strong>
										<elseif condition="$vo['status'] eq 9" />
										<strong style="color: green">{pigcms{:L_("自提点接货")}</strong>
										<elseif condition="$vo['status'] eq 10" />
										<strong style="color: green">{pigcms{:L_("配送中")}</strong>
										<elseif condition="$vo['status'] eq 11" />
										<strong style="color: red">{pigcms{:L_("申请退货")}</strong>
										<else />
										<strong style="color: red">{pigcms{:L_("未支付，不接单")}</strong>
										</if>
                      <if condition="C('customization.property_robot_jsdhwy') && $vo['order_status'] eq 0 && $vo['is_pick_in_store'] eq 0 && $vo['status'] eq 0 " >
                                        <!-- 机器人配送 -->
                        <br/><br/>
                                        <a title="{pigcms{:L_("机器人配送")}" data-title="{pigcms{:L_("选择配送地址")}" class="green handle_btn" style="padding-right:8px;" href="{pigcms{$config['site_url']}/v20/public/platform/#/dialog/storestaff.shopNew/thirdRobotDelivery?store_id={pigcms{$vo['store_id']}&order_id={pigcms{$vo['order_id']}&mer_id={pigcms{$vo['mer_id']}">
												{pigcms{:L_("机器人配送")}
											</a>
                      </if>
                      <if condition="C('customization.property_robot_jsdhwy') && !empty($vo['robot_order'])" >
                          <if condition="$vo['robot_order']['status'] eq 30" >
                                        <a data-title="{pigcms{:L_("确认机器人到达店铺")}" class="green thirdRobotDelivery" style="padding-right:8px;" href="javascript:;" data-order_id="{pigcms{$vo.order_id}" data-store_id="{pigcms{$vo.store_id}" data-status="1">{pigcms{:L_("确认到达")}</a>
                          <elseif condition="$vo['robot_order']['status'] eq 40" />
                                        <a data-title="{pigcms{:L_("确认机器人开始配送")}" class="green thirdRobotDelivery" style="padding-right:8px;" href="javascript:;" data-order_id="{pigcms{$vo.order_id}" data-store_id="{pigcms{$vo.store_id}" data-status="2">{pigcms{:L_("开始配送")}</a>
                          <elseif condition="$vo['robot_order']['status'] eq 50" />
                                        <a data-title="{pigcms{:L_("确认机器人到达收货地")}" class="green thirdRobotDelivery" style="padding-right:8px;" href="javascript:;" data-order_id="{pigcms{$vo.order_id}" data-store_id="{pigcms{$vo.store_id}" data-status="3">{pigcms{:L_("确认送达")}</a>
                          </if>
                      </if>
										</td>
                            </if>
										<td style="text-align:center;">
											<a title="{pigcms{:L_("查看订单详情")}" data-title="{pigcms{:L_("订单详情")}" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('shop_order_detail',array('order_id'=>$vo['order_id']))}">
												<i class="shortBtn">{pigcms{:L_("查看详情")}</i>
											</a>
										</td>
									</tr>
								</volist>
							<else/>
								<tr class="odd"><td class="button-column" colspan="30" >{pigcms{:L_("您的店铺暂时还没有订单。")}</td></tr>
							</if>
						</tbody>
					</table>
					{pigcms{$pagebar}
				</div>
			</div>
		</div>
	</body>
<script>
	function take_order(order_id){     
	    $.post("{pigcms{:U('Store/take_order')}",{id:order_id},function(result){
	            result = eval('(' + result + ')');
	            alert(result.info);
	            location.reload();
	        });   
	}
	function change_note(obj,order_id){
		var note = $(obj).siblings('input[name="note"]').val();
		$.post('{pigcms{:U("change_shop_order_note")}',{order_id:order_id,note:note},function(date){
			alert(date.msg)
			window.location.reload();
		},'json');
	}
	function confirm_pay(order_id){
		$.post('{pigcms{:U("confirm_pay")}',{order_id:order_id},function(date){
			alert(date.msg)
			window.location.reload();
		},'json');
	}
$(function(){
	if($(window).width() <= 1024){
		$('.hide_col').remove();
	}
	$('input[name="keyword"]').focus();
});

document.onkeydown = function(event_e) {
	if(window.event) event_e = window.event;  
	var int_keycode = event_e.charCode||event_e.keyCode;  
	if(int_keycode ==13 && ($('#order_id').val() != '' || $('#phone').val() != '' || $('#name').val() != '' || $('#meal_pass').val() != '')) $('#queryForList').submit();
} 

//updateStatus(".statusSwitch .ace-switch", ".statusSwitch", "OPEN", "CLOSED", "shopstatus");

var notification = null;
$('body').append('<div id="checkWindowStatus" class="show"></div>');
window.onfocus = function(){
	$('#checkWindowStatus').addClass('show');
	if(notification != null){
		notification.close();
	}
}
window.onblur = function(){
	$('#checkWindowStatus').removeClass('show');
}
if(window.Notification){
	if(Notification.permission === 'granted'){
		//any thing
	}else{
		Notification.requestPermission();
	}
}

function getNewOrder(time){
	$.post("{pigcms{:U('Store/ajax_shop_neworder',array('shop_type'=>$shop_type))}",{time:time},function(result){
		if(result.status == 1){
			if(!$('#checkWindowStatus').hasClass('show')){
				notification = new Notification('{pigcms{:L_("您有新的")}{pigcms{$config.shop_alias_name}{pigcms{:L_("订单需要处理")}',{body:"{pigcms{$config.site_name}",icon:"<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>"});
				notification.onclick = function(){
					notification.close();
					notification = null;
					$('#checkWindowStatus').addClass('show');
					alert('{pigcms{:L_("您有新的")}{pigcms{$config.shop_alias_name}{pigcms{:L_("订单需要处理")}');
				}
			}
			playMp3Tip();
			layer.confirm('{pigcms{:L_("您有新的订单需要处理，请问是否刷新页面处理？")}<br/>{pigcms{:L_("点击按钮均可关闭声音。")}', {
			  btn: ['{pigcms{:L_("确认")}','{pigcms{:L_("关闭")}'] //"按钮")}
			}, function(){
			  closeMp3Tip();
			  location.href="{pigcms{:U('Store/shop_list',array('shop_type'=>$shop_type))}&status=0";
			}, function(){
			  closeMp3Tip();
			});
		}else{
			setTimeout(function(){
				getNewOrder(result.info);
			},5000);
		}
	});
}

function getRefundOrder(time){
    $.post("{pigcms{:U('Store/ajax_shop_refundorder',array('shop_type'=>$shop_type))}",{time:time},function(result){
        if(result.status == 1){
            playMp3Tip();
            layer.confirm('{pigcms{:L_("您有新的退款订单需要处理！")}', {
              btn: ['{pigcms{:L_("确认")}','{pigcms{:L_("关闭")}'] //"按钮")}
            }, function(){
              closeMp3Tip();
              location.href="{pigcms{:U('Store/shop_list',array('shop_type'=>$shop_type))}&status=11";
            }, function(){
              closeMp3Tip();
            });
        }else{
            setTimeout(function(){
                getRefundOrder(result.info);
            },5000);
        }
    });
}

function check_shop_goods_stock()
{
	$.get("{pigcms{:U('Store/check_shop_goods_stock',array('shop_type'=>$shop_type))}", function(result){
		if(result.status == 1){
			$('.waring').show();
		} else {
			$('.waring').hide();
		}
		setTimeout(function(){
			check_shop_goods_stock();
		},6000);
	}, 'json');
}
function playMp3Tip(){
	if(pack_qweb_app){
		play_mp3('{pigcms{$config.site_url}/static/file/new_order.mp3','new_order',100);
	}else{
		$('body').append('<audio style="display:none;" id="playMp3Tip" controls="true" loop="loop" src="{pigcms{$config.site_url}/static/file/new_order.mp3"></audio>');
		$('#playMp3Tip').trigger('play');
	}
}
function closeMp3Tip(){
	if(pack_qweb_app){
		stop_mp3();
	}else{
		$('#playMp3Tip').trigger('pause');
		$('#playMp3Tip').remove();
	}
}

$(document).ready(function(){
	check_shop_goods_stock();
	getNewOrder("{pigcms{$_SERVER.REQUEST_TIME}");
	getRefundOrder("{pigcms{$_SERVER.REQUEST_TIME}");
	$('.js-add-order').click(function(){
		var order_id = $(this).attr('js-order');
		$('.js-add-order-'+order_id).html('{pigcms{:L_("处理中")}');
		$.post("{pigcms{:U('Store/check_shop')}",{order_id:order_id,status:1},function(result){
			if(result.status == 1){
				$('.js-add-order-'+order_id).unbind('click');
				$('.js-add-order-'+order_id).html(result.info);
				$('.js-add-order').removeClass('js-add-order');
			}else{
				layer.msg(result.info);
				$('.js-add-order-'+order_id).html('{pigcms{:L_("接单")}');
			}
		});
	});
	$('.js-again-order').click(function(){//重试生成配送单
		var order_id = $(this).attr('js-order');
		$('.js-again-order-'+order_id).html('{pigcms{:L_("处理中")}');
		$.post("{pigcms{:U('Store/check_shop_again')}",{order_id:order_id,status:1},function(result){
			if(result.status == 1){
				$('.js-again-order-'+order_id).unbind('click');
				$('.js-again-order-'+order_id).html(result.info);
				$('.js-again-order').removeClass('js-again-order');
			}else{
				layer.msg(result.info);
				$('.js-add-order-'+order_id).html('{pigcms{:L_("已接单")}<span style="font-size: 12px;color: #fd3434">(配送订单生成失败)</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;点击重试');
			}
		});
	});
	$('.js-send-order').click(function(){
		var order_id = $(this).attr('js-order');
		$('.js-add-order-'+order_id).html('{pigcms{:L_("处理中")}');
		$.post("{pigcms{:U('Store/deliver_goods')}",{order_id:order_id,status:1},function(result){
			if(result.status == 1){
				$('.js-add-order-'+order_id).unbind('click');
				$('.js-add-order-'+order_id).html(result.info);
				$('.js-add-order').removeClass('js-add-order');
			}else{
				layer.msg(result.info);
				$('.js-add-order-'+order_id).html('{pigcms{:L_("发货到自提点")}');
			}
		});
	});

    // ID1015232 机器人配送状态变更 1 机器人到达召唤地 2 机器人开始配送 3 机器人到达收货地
    $('.thirdRobotDelivery').click(function(){
		var order_id = $(this).data('order_id');
        var status = $(this).data('status') || '';
        var store_id = $(this).data('store_id') || '';
        var msg = {
            1: '是否确认机器人到达？',
            2: '是否确认机器人开始配送？',
            3: '是否确认机器人到达收货地？'
        }
        var ticket = localStorage.getItem('pro__storestaff_access_token') || ''
        if(ticket && JSON.parse(ticket)) {
            ticket = JSON.parse(ticket).value
        }
        console.log('ticket',ticket)
        var params = {
            order_id: order_id,
            status: status,
            ticket: ticket,
            store_id: store_id
        }
        layer.open({
            content: msg[status],
            btn: ['确认','取消'],
            yes: function(index) {
                $.ajax("{pigcms{$config['site_url']}/v20/public/index.php/thirdRobot/storestaff.Robot/changeRobotStatus",
                {
                    type: 'post',
                    data:params,
                    headers: {
                        ticket: ticket
                    },
                    success:function(result){
                        if(result.status == 1000){
                            layer.close(index)
                            window.location.reload();
                        }else{
                            result.msg && layer.msg(result.msg);
                        }
                    }
                });
            },
            btn2: function(index) {
                layer.close(index)
            }
        })
		
	});
});
 var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('shop_export')}"

window.reload = function() {
    window.location.reload();
}


</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
</html>