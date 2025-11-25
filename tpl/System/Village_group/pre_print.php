<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={pigcms{:C('DEFAULT_CHARSET')}" />
		<title>社区拼团发货单</title>
		<script type="text/javascript">
			/*<if condition="!C('butt_open')">
				if(self==top){window.o2oTop.location.href="{pigcms{:U('Index/index')}";}
			<else/>
				if(self==top){window.o2oTop.location.href="{pigcms{:C('butt_system_url')}";}
			</if>*/
			var kind_editor=null,static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",system_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}",choose_market="{pigcms{:U('Area/ajax_market')}",choose_map="{pigcms{:U('Map/frame_map')}",get_firstword="{pigcms{:U('Words/get_firstword')}",frame_show=<if condition="$_GET['frame_show']">true<else/>false</if>;
 var  meal_alias_name = "{pigcms{$config.meal_alias_name}",parentShowHelpParam = [],parentShowIndex = false,choose_provincess="{pigcms{:U('Area/ajax_province')}",choose_cityss="{pigcms{:U('Area/ajax_city')}";
		</script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/style.css" />
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/jquery.form.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/jquery.cookie.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/jquery.validate.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/jquery.colorpicker.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/extend/layer.ext.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}/js/area_adver.js"></script>
	</head>
	<body width="100%" <if condition="$bg_color">style="background:{pigcms{$bg_color};"</if>>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">

			
				<div id="print_table">
				
					<style>
					body{
						background-color: #fff !important;
					}

					/* .table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;border-color:#000;}
					.table-list th{border-color:#000;} */
					.table{border:1px solid #303030;border-collapse: collapse;text-align:center;}
					.table tr td {border:1px solid #303030;}
					.table tr th {border:1px solid #303030;padding:5px;}
					.table>tbody>tr>td{border:1px solid #303030;}

					
					</style>
					 <!--style media="print">
						@page {
						  size: auto;  /* auto is the initial value */
						  margin: 5mm; /* this affects the margin in the printer settings */
						}
					</style-->
					<div id="right_381" style="width: 100%;float: left;text-align: center;font-size: 23px;margin-bottom: 8px;">发货单打印</div>
					<div id="right_381" style="width: 100%;float: left;margin-bottom: 8px;">日期 :{pigcms{$_GET.begin_time} 至 {pigcms{$_GET.end_time}</div>
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
						</colgroup>
						<thead>
							<tr>
								<th style="width:40px;">订单ID</th>
								<th style="width:60px;">团长信息</th>
								<th>用户信息</th>
								<th style="width:80px;">自提地址</th>
								<th style="width:180px;">商品名称</th>
								<th style="width:40px;">进货总金额</th>
								<th style="width:30px;">总金额</th>
								<th style="width:30px;">购买数量</th>
								<th>支付时间</th>
								<th style="width:50px;">订单状态</th>
                                <th style="width:60px;">配送状态</th>
                                <th style="width:100px;">详细信息</th>
								<th style="width:120px;">订单类型</th>
                                <th style="width:120px;">备注</th>
						
							</tr>
						</thead>
						<tbody>
                        <php>if (is_array($start_list)){</php>
                                <volist name="start_list" id="vvo">
                                    <volist name="vvo" id="vo" key="kk">
                                        <php> $orderid = $vo['order_id'];</php>
                                        <tr>

                                            <if condition="$kk eq 1">
                                                <td class="order" rowspan="{pigcms{:count($vvo)}">{pigcms{$orderid}</td>
                                                <td rowspan="{pigcms{:count($vvo)}">
                                                    团长:{pigcms{$vo.header_info.name}<br>
                                                    手机:{pigcms{$vo.header_info.phone}<br>
                                                </td>
                                                <td rowspan="{pigcms{:count($vvo)}">
                                                    用户id:{pigcms{$vo.uid}<br>
                                                    用户手机:{pigcms{$vo.phone}<br>
                                                    用户昵称:{pigcms{$vo.nickname}<br>
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
                                                <td>{pigcms{$vo.name}</td>


                                            <td>{pigcms{$vo.all_purchase_price|floatval}</td>
                                            <td>{pigcms{$vo.all_price|floatval}</td>
                                            <td>{pigcms{$vo.all_num|floatval}</td>
                                            <td>{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</td>
                                            <td>
                                                <if condition="$vo.status eq 2 OR $vo.status eq 13">
                                                    已完成
                                                    <elseif condition="$vo['status'] egt 1 AND $vo['status'] lt 10"/>
                                                    已支付
                                                    <elseif condition="$vo['status'] eq 11 "/>
                                                    已取消
                                                    <elseif condition="$vo['status'] eq 10 "/>
                                                    超时未支付
                                                    <elseif condition="$vo['status'] eq 0 "/>
                                                    未支付
                                                </if>
                                            </td>
                                            <td>
                                                <if condition="$vo.is_delay eq 1">
                                                    已延期
                                                    <elseif condition="$vo.deliver_status eq 0" />
                                                    未发货
                                                    <elseif condition="$vo.deliver_status eq 1"/>
                                                    已发货
                                                    <elseif condition="$vo.deliver_status eq 2"/>
                                                    团长收货 待自提
                                                    <elseif condition="$vo.deliver_status eq 3"/>
                                                    已取货
                                                    <elseif condition="$vo.deliver_status eq 4"/>
                                                    已取货
                                                    <elseif condition="$vo.deliver_status eq 5"/>
                                                    已评价
                                                    <elseif condition="$vo.deliver_status eq 6"/>
                                                    已延期
                                                </if>
                                            </td>
                                            <td>
                                                <if condition="$vo['village_group_type'] eq 1">
                                                    <if condition="$vo['d_send_time'] gt 0">
                                                        期望派送:{pigcms{$vo['d_send_time']|date="Y-m-d",###}<br>
                                                    </if>
                                                    <if condition="$vo['real_send_time'] gt 0">
                                                        实际派送:{pigcms{$vo['real_send_time']|date="Y-m-d",###}<br>
                                                    </if>
                                                    <if condition="$vo['service_time'] gt 0">
                                                        实际送达:{pigcms{$vo['service_time']|date="Y-m-d",###}<br>
                                                    </if>
                                                    <if condition="$vo['date_num'] gt 0">
                                                        周期购期数:{pigcms{$vo['date_num']}<br>
                                                    </if>
                                                    <else/>
                                                    <notempty name="vo['send_time']">派送时间:{pigcms{$vo['send_time']|date="Y-m-d",###}<br></notempty>
                                                </if>
                                            </td>
                                            <if condition="$kk eq 1">
                                                <td rowspan="{pigcms{:count($vvo)}">
                                                    <if condition="$vo['village_group_type'] eq 1">
                                                        类型:周期购<if condition="$vo['delivery_periods'] gt 0">
                                                            ({pigcms{$vo['delivery_periods']}期)
                                                        </if><br>
                                                        <if condition="$vo['newest_delivery_periods'] gt 0">
                                                            当前期数:{pigcms{$vo['newest_delivery_periods']}<br>
                                                        </if>
                                                        <else/>
                                                        类型:普通<br>
                                                    </if>
                                                </td>
                                            </if>
                                            <if condition="$kk eq 1">
                                             <td rowspan="{pigcms{:count($vvo)}">{pigcms{$vo.note}</td>
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
					<a href="javascript:void(0);" class="button" onclick="PrintPage()" >打印</a>
				</div>
		</div>
		
		<script>
		
			var hkey_root,hkey_path,hkey_key;
			hkey_root="HKEY_CURRENT_USER";
			hkey_path="\\Software\\Microsoft\\Internet Explorer\\PageSetup\\";
			
			// 设置页眉页脚为空
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

    // 设置页眉页脚为默认值
    function PageSetup_Default()
    {
        try{
            var RegWsh = new ActiveXObject("WScript.Shell") ;
            hkey_key="header" ;
            RegWsh.RegWrite(hkey_root+hkey_path+hkey_key,"&w&b页码，&p/&P") ;
            hkey_key="footer" ;
            RegWsh.RegWrite(hkey_root+hkey_path+hkey_key,"&u&b&d") ;
        }
        catch(e){}
    }
	
    // 打印
    function PrintPage()
    {
        PageSetup_Null() ;
        if (window.PrePrint != null) window.PrePrint();
        try{
            print.portrait   =  false    ;//横向打印 
        }catch(e){
            // alert("不支持此方法");
        }
        var bdhtml=window.document.body.innerHTML;//获取当前页的html代码
        // var sprnstr="<!--begin-->";//设置打印开始区域    
        // var eprnstr="<!--end-->";//设置打印结束区域    
        // var prnhtml=bdhtml.substring(bdhtml.indexOf(sprnstr)); //从开始代码向后取html    
        // var prnhtml=prnhtml.substring(0,prnhtml.indexOf(eprnstr));//从结束代码向前取html    
        var prnhtml=$('#print_table').html();//从结束代码向前取html    
        window.document.body.innerHTML=prnhtml;
        window.print();
        // setTimeout("window.close();", 0)
       	window.document.body.innerHTML=bdhtml;
        // PageSetup_Default() ;
    }
	
		// setTimeout("PrintPage();", 10)

		</script>
<include file="Public:footer"/>