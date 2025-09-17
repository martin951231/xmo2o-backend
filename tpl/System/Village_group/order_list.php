<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Village_group/order_list')}" class="on">订单列表</a>
                    <a href="{pigcms{:U('Village_group/send_list')}">发货单</a>
                    <if condition="$config['village_group_delivery_switch'] eq 1">
                        <a href="{pigcms{:U('Village_group/delivery_order_list')}">配送发货单</a>
                    </if>
				
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
				
			
					<td>
						<form action="{pigcms{:U('Village_group/order_list')}" method="get">
							<input type="hidden" name="c" value="Village_group"/>
							<input type="hidden" name="a" value="order_list"/>
							<input type="hidden" name="mer_id" value="{pigcms{$_GET.mer_id}"/>
                            <if condition="$admin_area neq 3">
                                <div id="choose_pca" street_id="-1" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                            </if>　　　
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
                                <option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>订单号</option>
								<option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>订单id</option>
								<option value="user_phone" <if condition="$_GET['searchtype'] eq 'user_phone'">selected="selected"</if>>用户电话</option>
								<option value="user_id" <if condition="$_GET['searchtype'] eq 'user_id'">selected="selected"</if>>用户id</option>
								<option value="header_phone" <if condition="$_GET['searchtype'] eq 'header_phone'">selected="selected"</if>>团长电话</option>
								<option value="header_name" <if condition="$_GET['searchtype'] eq 'header_name'">selected="selected"</if>>团长名称</option>
								<option value="header_uid" <if condition="$_GET['searchtype'] eq 'header_uid'">selected="selected"</if>>团长id</option>			
							</select>
							订单状态：			
							<select name="status" id="status">
								<option value="-1" <if condition="$_GET['status'] eq -1">selected="selected"</if>>全部</option>
								<option value="0" <if condition="$_GET['status'] eq '0'">selected="selected"</if>>未支付</option>
								<option value="11" <if condition="$_GET['status'] eq '11'">selected="selected"</if>>已取消</option>
								<option value="10" <if condition="$_GET['status'] eq '10'">selected="selected"</if>>超时未支付</option>
								<option value="1" <if condition="$_GET['status'] eq '1'">selected="selected"</if>>已支付</option>
								<option value="2" <if condition="$_GET['status'] eq '2'">selected="selected"</if>>已完成</option>
								<option value="12" <if condition="$_GET['status'] eq '12'">selected="selected"</if>>申请退货</option>
								<option value="15" <if condition="$_GET['status'] eq '15'">selected="selected"</if>>已退款</option>
                                <option value="200" <if condition="$_GET['status'] eq '200'">selected="selected"</if>>部分退款</option>
							</select>
							
							<font class="deliver_status">配送状态：</font>
							<select name="deliver_status" class="deliver_status" <if condition="$_GET['status'] neq -1 AND $_GET['status'] neq 1 AND isset($_GET['status'])">style="display:none"</if>>
								<option value="-1" <if condition="$_GET['deliver_status'] eq '-1'">selected="selected"</if>>全部</option>
								<option value="1" <if condition="$_GET['deliver_status'] eq '1'">selected="selected"</if>>待发货</option>
								<option value="7" <if condition="$_GET['deliver_status'] eq '7'">selected="selected"</if>>部分已发货</option>
								<option value="5" <if condition="$_GET['deliver_status'] eq '5'">selected="selected"</if>>全部已发货</option>
								<option value="8" <if condition="$_GET['deliver_status'] eq '8'">selected="selected"</if>>部分已到货</option>
								<option value="6" <if condition="$_GET['deliver_status'] eq '6'">selected="selected"</if>>全部已到货</option>
								<option value="3" <if condition="$_GET['deliver_status'] eq '3'">selected="selected"</if>>部分已自提</option>
								<option value="2" <if condition="$_GET['deliver_status'] eq '2'">selected="selected"</if>>全部已自提</option>
							
							</select>
							
                            &nbsp;&nbsp;&nbsp;&nbsp;
							<!--<font color="#000">日期筛选：</font>-->
                            <select name="searchtimetype" id="searchtimetype">
                                <option value="add_time" <if condition="$_GET['searchtimetype'] eq 'add_time'">selected="selected"</if>>下单时间</option>
                                <option value="pay_time" <if condition="$_GET['searchtimetype'] eq 'pay_time'">selected="selected"</if>>支付时间</option>
                            </select>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							
							支付方式筛选: 
							<select id="pay_type" name="pay_type">
									<option value="" <if condition="!$_GET['pay_type']">selected="selected"</if>>全部支付方式</option>
								<volist name="pay_method" id="vo">
									<option value="{pigcms{$key}" <if condition="$key eq $_GET['pay_type']">selected="selected"</if>>{pigcms{$vo.name}</option>
								</volist>
									<option value="balance" <if condition="'balance' eq $_GET['pay_type']">selected="selected"</if>>余额支付</option>
							</select>
							<input type="submit" value="查询" class="button"/>
							
						<a  href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出订单</a>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					</style>
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="250" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
					
								<th>团长信息</th>
								<th>总金额</th>
								<th>用户信息</th>
								<th>添加时间</th>
								<th>订单状态</th>
								<th>配送状态</th>
								<th>支付时间</th>
								<th>支付方式</th>
								<th>完成时间</th>
								<th>购买数量</th>
								<th>备注</th>
								
							
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($start_list)">
								<volist name="start_list" id="vo">
									<tr>
										<td>{pigcms{$vo.order_id}</td>
									
										<td>
                                            团长:{pigcms{$vo.header_info.name}<br>
                                            手机:{pigcms{$vo.header_info.phone}<br>
                                            <a href="javascript:void(0)"
											onclick="window.top.artiframe('{pigcms{:U('Village_group/add_header',array('id'=>$vo['start_uid']))}','订单详情',800,600,true,false,false,false,'store_add',true);">查看</a> </td>
										<td>
                                            {pigcms{$vo.total_money|floatval}
                                        </td>
										<td>
										用户id:{pigcms{$vo.uid}<br>
										用户手机:{pigcms{$vo.phone}<br>
										用户昵称:{pigcms{$vo.nickname}<br>
                                        收货地址:{pigcms{$vo.header_info.address}<br>
										</td>
										<td>{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</td>
										<td>
											<if condition="$vo.status eq 2 OR $vo['status'] eq 13">
												<font color="green">已完成</font>
											<elseif condition="$vo['status'] egt 1 AND $vo['status'] lt 10 AND $vo['status'] neq 4" />
												<font color="green">已支付</font>
											<elseif condition="$vo['status'] eq 11 " />
												<font color="red">已取消</font>
											<elseif condition="$vo['status'] eq 10 " />
												<font color="red">超时未支付</font>
											<elseif condition="$vo['status'] eq 0 " />
												<font color="red">未支付</font>
											<elseif condition="$vo['status'] eq 4 OR $vo['status'] eq 12" />
												<font color="red">已退款</font>
											</if>
										</td>
										<td style="text-align:center">	
											
											<if condition="$vo['status'] eq '1'">
												<font color="red">待发货</font>
											<elseif condition="$vo.status eq 7" />
												<font color="red">部分已发货</font>
											<elseif condition="$vo.status eq 5" />
												<font color="red">全部已发货</font>
											<elseif condition="$vo.status eq 4 OR $vo.status eq 12" />
												<font color="red">已退款</font>
											<elseif condition="$vo.status eq 8" />
												<font color="red">部分已到货</font>
											<elseif condition="$vo.status eq 6" />
												<font color="red">全部已到货</font>
											<elseif condition="$vo.status eq 3" />
												<font color="red">部分已自提</font>
											<elseif condition="$vo.status eq 2" />
												<font color="red">全部已自提</font>
											<elseif condition="$vo.status eq 13" />
												<font color="red">已评价</font>
											<elseif condition="$vo.status eq 11" />
												<font >-</font>
											</if>
										</td>
										<td><if condition="$vo['pay_time'] gt 0">{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</if></td>
										<td>
											<notempty name="vo.pay_type">
											<volist name="pay_method" id="vo2">
													<eq name="vo.pay_type" value="$key">
														{pigcms{$vo2.name}
													</eq>
											</volist>
											<else/>
											<gt name='vo.system_balance' value='0'>
												余额支付
											</gt>
											</notempty>
										</td>
										<td><if condition="$vo['bill_time'] gt 0">{pigcms{$vo.bill_time|date="Y-m-d H:i:s",###}</if></td>
										<td>{pigcms{$vo.num|floatval}</td>

										<td style="width:60px;">{pigcms{$vo.note}</td>
										<td class="textcenter">
											<if condition="$vo.status eq 1">
											<a href="javascript:void(0)"
											onclick="window.top.artiframe('{pigcms{:U('Village_group/change_leader',array('order_id'=>$vo['order_id']))}','更换团长',1200,600,true,false,false,editbtn,'edit',true);">更换团长</a>
											|
											</if>
											<a href="javascript:void(0)"
											onclick="window.top.artiframe('{pigcms{:U('Village_group/join_order_detail',array('order_id'=>$vo['order_id']))}','订单详情',800,600,true,false,false,false,'detail',true);">订单详情</a>
										</td>

									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="12">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="12">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script>
   var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('export')}"
	
   $('.send_goods').click(function(){
       var now_dom = $(this);
       window.top.art.dialog({
           icon: 'question',
           title: '请确认',
           id: 'msg' + Math.random(),
           lock: true,
           fixed: true,
           opacity:'0.4',
           resize: false,
           content: '你确定这样操作吗？操作后可能不能恢复！' + (now_dom.attr('tip') ? '<br/><br/><div style="color:red;">'+now_dom.attr('tip')+'</div>' : ''),
           ok:function (){
               $.post(now_dom.attr('url'),now_dom.attr('parameter'),function(result){
                   if(result.status == 1){
                       window.top.msg(1,result.info,true);
                       window.location.reload();
                   }else{
                       window.top.msg(0,result.info);
                   }
               });
           },
           cancel:true
       });
       return false;
   });
   
   $('#status').change(function(){
	   if($(this).val()!=-1 && $(this).val()!=1){
		   $('.deliver_status').hide();
	   }else{
		   $('.deliver_status').show();
	   }
	   
	   
   });
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>