<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>{pigcms{:L_("社区拼团发货单")}</title>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/style.css" />
	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE')}"></script>
	<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
	<style>
		body{
			background-color: #fff !important;
		}
		.table{border:1px solid #303030;border-collapse: collapse;text-align:center;}
		.table tr td {border:1px solid #303030;}
		.table tr th {border:1px solid #303030;padding:5px;}
		.table>tbody>tr>td{border:1px solid #303030;}
	</style>
</head>
	<body width="100%" >
		<div class="mainbox">			
			<div id="print_table" class="print_table">				
				<div id="right_381" style="width: 100%;float: left;text-align: center;font-size: 23px;margin-bottom: 8px;">{pigcms{:L_("发货单打印")}</div>
				<div id="right_381" style="width: 100%;float: left;margin-bottom: 8px;">{pigcms{:L_("日期")} :{pigcms{$_GET.begin_time} {pigcms{:L_("至")} {pigcms{$_GET.end_time}</div>
				<table width="100%" cellspacing="0" class="table" >
					<colgroup>
						<col/>
						<col/>
						<col/>
						<col/>
						<col/>
						<col/>
						<col/>
						<col/>
						<col/>
						<col/>
						<col/>
                        <col/>
                        <col/>
                        <col/>
					</colgroup>
					<thead>
						<tr>
							<th style="width:40px;">{pigcms{:L_("订单")}ID</th>
							<th style="width:60px;">{pigcms{:L_("团长信息")}</th>
							<th>{pigcms{:L_("用户信息")}</th>
							<th style="width:80px;">{pigcms{:L_("自提地址")}</th>
							<th style="width:180px;">{pigcms{:L_("商品名称")}</th>
							<th style="width:40px;">{pigcms{:L_("进货总金额")}</th>
							<th style="width:30px;">{pigcms{:L_("总金额")}</th>
							<th style="width:30px;">{pigcms{:L_("购买数量")}</th>
							<th>{pigcms{:L_("支付时间")}</th>
							<th style="width:50px;">{pigcms{:L_("订单状态")}</th>
							<th style="width:60px;">{pigcms{:L_("配送状态")}</th>
                            <th style="width:100px;">{pigcms{:L_("详细信息")}</th>
							<th style="width:120px;">{pigcms{:L_("备注")}</th>

						</tr>
					</thead>
					<tbody>
                    <php>if (is_array($start_list)){</php>
							<volist name="start_list" id="vvo" >
								<volist name="vvo" id="vo" key="kk">
									<tr>

										<if condition="$kk eq 1">
											<td class="order" rowspan="{pigcms{:count($vvo)}">{pigcms{$vo['order_id']}</td>
											<td rowspan="{pigcms{:count($vvo)}">
												{pigcms{:L_("团长")}:{pigcms{$vo.header_info.name}<br>
												{pigcms{:L_("手机")}:{pigcms{$vo.header_info.phone}<br>
											</td>
											<td rowspan="{pigcms{:count($vvo)}">
												{pigcms{:L_("用户")}id:{pigcms{$vo.uid}<br>
												{pigcms{:L_("用户手机")}:{pigcms{$vo.phone}<br>
												{pigcms{:L_("用户昵称")}:{pigcms{$vo.nickname}<br>

												<if condition="$vo['pick_name']">
													<br>
													{pigcms{:L_("提货人姓名")}:{pigcms{$vo.pick_name}<br>
													{pigcms{:L_("提货人手机号")}:{pigcms{$vo.pick_phone}<br>
												</if>
											</td>
                                            <td rowspan="{pigcms{:count($vvo)}">
                                                <if condition="$vo['pick_addr'] neq ''">
                                                    {pigcms{$vo.pick_addr}
                                                    <else/>
                                                    {pigcms{$vo.header_info.pick_address.pick_addr}
                                                </if>
                                            </td>
										</if>
										<if condition="!$_GET['goods_id']">
											<td>{pigcms{$vo.name}</td>
										</if>
										<td>{pigcms{$vo.all_purchase_price|floatval}</td>
										<td>{pigcms{$vo.all_price|floatval}</td>
										<td>{pigcms{$vo.all_num|floatval}</td>
										<td>{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</td>
										<td>	
											<if condition="$vo.status eq 2 OR $vo.status eq 13">
												{pigcms{:L_("已完成")}
												<elseif condition="$vo['status'] egt 1 AND $vo['status'] lt 10" />
												{pigcms{:L_("已支付")}
												<elseif condition="$vo['status'] eq 11 " />
												{pigcms{:L_("已取消")}
												<elseif condition="$vo['status'] eq 10 " />
												{pigcms{:L_("超时未支付")}
												<elseif condition="$vo['status'] eq 0 " />
												{pigcms{:L_("未支付")}
											</if>
										</td>
										<td>
                                            <if condition="$vo.is_delay eq 1">
                                                {pigcms{:L_("已延期")}
                                                <elseif condition="$vo.deliver_status eq 0" />
												{pigcms{:L_("未发货")}
												<elseif condition="$vo.deliver_status eq 1" />
												{pigcms{:L_("已发货")}
												<elseif condition="$vo.deliver_status eq 2" />
												{pigcms{:L_("团长收货 待自提")}
												<elseif condition="$vo.deliver_status eq 3" />
												{pigcms{:L_("已取货")}
                                                <elseif condition="$vo.deliver_status eq 4"/>
                                                {pigcms{:L_("已取货")}
                                                <elseif condition="$vo.deliver_status eq 5" />
                                                {pigcms{:L_("已评价")}
                                                <elseif condition="$vo.deliver_status eq 6" />
                                                {pigcms{:L_("已延期")}
											</if>
										</td>
                                        <td>
                                            <if condition="$vo['village_group_type'] eq 1">
                                                <if condition="$vo['d_send_time'] gt 0">
                                                    {pigcms{:L_("期望派送:")}{pigcms{$vo['d_send_time']|date="Y-m-d",###}<br>
                                                </if>
                                                <if condition="$vo['real_send_time'] gt 0">
                                                    {pigcms{:L_("实际派送:")}{pigcms{$vo['real_send_time']|date="Y-m-d",###}<br>
                                                </if>
                                                <if condition="$vo['service_time'] gt 0">
                                                    {pigcms{:L_("实际送达:")}{pigcms{$vo['service_time']|date="Y-m-d",###}<br>
                                                </if>
                                                <if condition="$vo['date_num'] gt 0">
                                                    {pigcms{:L_("周期购期数:")}{pigcms{$vo['date_num']}<br>
                                                </if>
                                                <else/>
                                                {pigcms{:L_("类型:普通")}<br>
                                                截单时间:{pigcms{$vo['arrive_cutting_time']|date="Y-m-d H:i",###}<br>
                                                到达时间:{pigcms{$vo['arrive_time']|date="Y-m-d H:i",###}<br>
                                            </if>
                                        </td>
										<if condition="$kk eq 1">
											<td rowspan="{pigcms{:count($vvo)}">
                                                <if condition="$vo['village_group_type'] eq 1">
                                                    {pigcms{:L_("类型")}:{pigcms{:L_("周期购")}<if condition="$vo['delivery_periods'] gt 0">
                                                        ({pigcms{$vo['delivery_periods']}{pigcms{:L_(期)})
                                                    </if><br>
                                                    <if condition="$vo['newest_delivery_periods'] gt 0">
                                                        {pigcms{:L_("当前期数:")}{pigcms{$vo['newest_delivery_periods']}<br>
                                                    </if>
                                                    <else/>
                                                    {pigcms{:L_("类型")}:{pigcms{:L_("普通")}<br>
                                                </if>
											</td>
										</if>
									</tr>
								</volist>
							</volist>
                            <tr><td class="textcenter pagebar" colspan="15">{pigcms{$pagebar}</td></tr>
                    <php>}</php>
					</tbody>
				</table>
			</div>
			<div style="text-align:center;margin-top:55px;" class="printBtn">
				<a href="javascript:void(0);" class="button" onclick="PrintPage()" >{pigcms{:L_("打印")}</a>
			</div>
		</div>
	</body>
	<script>	
		var hkey_root,hkey_path,hkey_key;
		hkey_root="HKEY_CURRENT_USER";
		hkey_path="\\Software\\Microsoft\\Internet Explorer\\PageSetup\\";

		// "设置页眉页脚为空")}
		function PageSetup_Null()
		{
			try{
				var RegWsh = new ActiveXObject("WScript.Shell") ;
				hkey_key="header" ;
				RegWsh.RegWrite(hkey_root+hkey_path+hkey_key,"") ;
				hkey_key="footer" ;
				RegWsh.RegWrite(hkey_root+hkey_path+hkey_key,"") ;
			}
			catch(e){}
		}

	    // "设置页眉页脚为默认值")}
	    function PageSetup_Default()
	    {
	    	try{
	    		var RegWsh = new ActiveXObject("WScript.Shell") ;
	    		hkey_key="header" ;
	    		RegWsh.RegWrite(hkey_root+hkey_path+hkey_key,"&w&b{pigcms{:L_("页码，")}&p/&P") ;
	    		hkey_key="footer" ;
	    		RegWsh.RegWrite(hkey_root+hkey_path+hkey_key,"&u&b&d") ;
	    	}
	    	catch(e){}
	    }

	    // "打印")}
	    function PrintPage()
	    {
	    	PageSetup_Null() ;
	    	if (window.PrePrint != null) window.PrePrint();
	    	try{
	            print.portrait   =  false    ;//"横向打印")} 
	        }catch(e){
	            // alert("{pigcms{:L_("不支持此方法")}");
	        }
	        var bdhtml=window.document.body.innerHTML;//"获取当前页的html代码")}
	        var prnhtml = $('.print_table').html();//"获取要打印的内容")}
	        window.document.body.innerHTML=prnhtml;
	        window.print();
	        // setTimeout("window.close();", 0)
	        window.document.body.innerHTML=bdhtml;
	        // PageSetup_Default() ;
	    }
	</script>
</html>