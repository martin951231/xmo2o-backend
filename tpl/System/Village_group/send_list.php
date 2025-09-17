<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
                <if condition="$is_menu_hide neq 1">
				<ul>
					<a href="{pigcms{:U('Village_group/order_list')}">订单列表</a>
                    <a href="{pigcms{:U('Village_group/send_list')}" class="on">发货单</a>
                    <if condition="$config['village_group_delivery_switch'] eq 1">
                        <a href="{pigcms{:U('Village_group/delivery_order_list')}">配送发货单</a>
                    </if>
				
				</ul>
                </if>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Village_group/order_list')}" method="get" id="form">
							<input type="hidden" name="c" value="Village_group"/>
							<input type="hidden" name="a" value="send_list"/>
							<input type="hidden" name="goods_id" id="goods_id" value="{pigcms{$_GET.goods_id}"/>
                            <if condition="$admin_area neq 3">
                                <div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                            </if>　　　
						
							<select name="searchtimetype" id="searchtimetype">
								<option value="pay_time" <if condition="$_GET['searchtimetype'] eq 'pay_time'">selected="selected"</if>>支付时间</option>
								<option value="arrive_cutting_time" <if condition="$_GET['searchtimetype'] eq 'arrive_cutting_time'">selected="selected"</if>>截单时间</option>
								<option value="arrive_time" <if condition="$_GET['searchtimetype'] eq 'arrive_time'">selected="selected"</if>>到达时间</option>
							</select>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>
							筛选: <input type="text" name="keyword" id="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype" id="searchtype">
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>商品标题</option>
								<option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>订单id</option>
								<option value="goods_id" <if condition="$_GET['searchtype'] eq 'goods_id'">selected="selected"</if>>商品编号</option>
								<option value="village_name" <if condition="$_GET['searchtype'] eq 'village_name'">selected="selected"</if>>社区名称</option>
								<option value="mer_id" <if condition="$_GET['searchtype'] eq 'mer_id'">selected="selected"</if>>商家编号</option>
								<option value="mer_name" <if condition="$_GET['searchtype'] eq 'mer_name'">selected="selected"</if>>商家名称</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							
							<input type="submit" value="查询" class="button"/>
							<!-- <a href="{pigcms{:U('Village_group/send_list')}&time=1&begin_time={pigcms{:date('Y-m-d')}&end_time={pigcms{:date('Y-m-d')}&goods_id={pigcms{$_GET['goods_id']}" class="nativea <if condition="$_GET['time'] eq 1">on</if>">今天发货单</a>
							
							<a href="{pigcms{:U('Village_group/send_list')}&time=2&begin_time={pigcms{:date("Y-m-d",strtotime("+1 day"))}&end_time={pigcms{:date("Y-m-d",strtotime("+1 day"))}&goods_id={pigcms{$_GET['goods_id']}" class="nativea <if condition="$_GET['time'] eq 2">on</if>">明天发货单</a> -->
							
							<a  href="javascript:void(0)" class="button send_goods" url="{pigcms{:U('send_selected')}" parameter="send_all=1">一键发货</a>
							<a  href="javascript:void(0)" class="button send_goods" url="{pigcms{:U('get_selected')}" parameter="get_all=1">一键到货</a>
							<a  href="javascript:void(0)" class="button" onclick="PrePrint();">打印当前发货单</a>
							<a  href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出发货单</a>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					</style>
					<table width="100%" cellspacing="0" id="print_table">
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
							<col/>
						</colgroup>
						<thead>
							<tr>
								<th><input type="checkbox" name="checkAll" value='1' />全选</th>
								<if condition="!$_GET['goods_id']">
									<th>商品名称</th>
								</if>
								<th>商家</th>
								<th>订单ID</th>
								<th>团长信息</th>
								<th>用户信息</th>
								<th>自提地址</th>
								<th>进货总金额</th>
								<th>总金额</th>
								<th>购买数量</th>
								<th>支付时间</th>
								<th>订单状态</th>
                                <th>详细信息</th>
								<th width="100">备注</th>
								<th>配送状态</th>
						
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($start_list)">
								<volist name="start_list" id="vo">
									<tr>
										<td><if condition="in_array($vo['deliver_status'],[0,1]) AND $vo['status'] egt 1 AND $vo['status'] lt 10"><input type="checkbox" name="checkThis" value='{pigcms{$vo.order_id}={pigcms{$vo.goods_id}' /></if></td>
										<if condition="!$_GET['goods_id']">
											<td>{pigcms{$vo.name}</td>
										</if>
										<td class="order"><if condition="$vo['mer_id'] neq 0">{pigcms{$vo.merchant_name}(ID:{pigcms{$vo.mer_id})<else />--</if></td>
										<td class="order">{pigcms{$vo.order_id}</td>
										<td>
                                            团长:{pigcms{$vo.header_info.name}<br>
                                            手机:{pigcms{$vo.header_info.phone}<br>
                                        </td>
										<td>
											用户id:{pigcms{$vo.uid}<br>
											用户手机:{pigcms{$vo.phone}<br>
											用户昵称:{pigcms{$vo.nickname}<br>
										</td>
                                        <if condition="$vo['pick_addr'] neq ''">
                                            <td>{pigcms{$vo.pick_addr}</td>
                                            <else/>
                                            <td>{pigcms{$vo.header_info.pick_address.pick_addr}</td>
                                        </if>
										<td>{pigcms{$vo.all_purchase_price|floatval}</td>
										<td>{pigcms{$vo.all_price|floatval}</td>
										<td>{pigcms{$vo.all_num|floatval}</td>
										<td>{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</td>
										<td>	
											<if condition="$vo.status eq 2 ">
												<font color="green">已完成</font>
											<elseif condition="$vo['status'] egt 1 AND $vo['status'] lt 10" />
												<font color="green">已支付</font>
											<elseif condition="$vo['status'] eq 11 " />
												<font color="red">已取消</font>
											<elseif condition="$vo['status'] eq 10 " />
												<font color="red">超时未支付</font>
											<elseif condition="$vo['status'] eq 0 " />
												<font color="red">未支付</font>
											</if>
										</td>
                                        <td>
                                            <if condition="$vo['village_group_type'] eq 1">
                                                类型:周期购<br>
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
                                                    {pigcms{:L_("当前期数")}:{pigcms{$vo['date_num']}<br>
                                                </if>
                                                <if condition="$vo['delivery_periods'] gt 1">
                                                    {pigcms{:L_("已派送期数")}:
                                                    {pigcms{$vo['date_num']==$vo['delivery_periods'] && $vo['deliver_status'] >3 ? $vo['date_num'] : ($vo['date_num']-1)}/{pigcms{$vo['delivery_periods']}<br>
                                                </if>
                                                <else/>
                                                类型:普通<br>
                                                <!-- 派送时间:{pigcms{$vo['send_time']|date="Y-m-d",###}<br> -->
                                                截单时间:{pigcms{$vo['arrive_cutting_time']|date="Y-m-d H:i",###}<br>
                                                到达时间:{pigcms{$vo['arrive_time']|date="Y-m-d H:i",###}<br>
                                            </if>
                                        </td>
										<td width="100"><div style=" width:100px;word-wrap:break-word">{pigcms{$vo.note}</div></td>
										<td>
											<if condition="$vo.deliver_status eq 0 AND $vo['status'] egt 1 AND $vo['status'] lt 10 AND $vo['is_delay'] eq 0">
												<a href="javascript:void(0);" class="send_goods button" data-id="{pigcms{$vo['order_id']}={pigcms{$vo['goods_id']}" parameter="" url="{pigcms{:U('send_selected')}">
                                                    去发货
                                                </a>
											<elseif condition="$vo.deliver_status eq 1 AND $vo['is_delay'] eq 0" />
												<font color="green">已发货</font>
												<a href="javascript:void(0);" class="send_goods button" data-id="{pigcms{$vo['order_id']}={pigcms{$vo['goods_id']}" parameter="" url="{pigcms{:U('get_selected')}">
                                                    已到货
                                                </a>
											<elseif condition="$vo.deliver_status eq 2 AND $vo['is_delay'] eq 0" />
												<font color="green">团长收货 待自提</font>
                                            <elseif condition="$vo.deliver_status eq 3 || $vo.deliver_status eq 4" />
                                                <font color="green">已取货</font>
                                            <elseif condition="$vo.deliver_status eq 6 || $vo['is_delay'] eq 1" />
                                                <font color="gray">已延期-{pigcms{$vo['delay_time']|date="Y-m-d",###}</font>
                                            <elseif condition="$vo['status'] eq 3" />
                                                <font color="gray">已取消</font>
											</if>
										</td>
										<!--td class="textcenter">
											<a href="javascript:void(0)" onclick="window.top.artiframe('{pigcms{:U('Village_group/join_order_detail',array('order_id'=>$vo['order_id']))}','订单详情',800,600,true,false,false,editbtn,'store_add',true);">订单详情</a>  
										</td-->
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="13">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
		<style>
			.nativea{
				color: #004499;
				margin: 0 5px;
				padding: 4px 7px;
				background: #EFEFEF;
			}
			.nativea.on{
				background: #498CD0;
				color: #FFF;
				text-decoration: none;
			}
			
		</style>
<script>
	var url = "{pigcms{$config.site_url}";
	var export_url = "{pigcms{:U('send_export')}";
	var check_all = 0;//是否选择全部（筛选的全部）
	
	$('[name="checkAll"]').bind("click", function(){
		if($(this)[0].checked){
			$('[name="checkThis"]').each(function(){
				this.checked = true
			})
            check_all = 1;
			alert('请注意：您已选择了筛选范围内的所有订单，请勿翻页操作，否则会取消您的选择。')
		}
		else{
			$('[name="checkThis"]').each(function(){
				this.checked = false
			})
            check_all = 0;
		}
	})

   $('.send_goods').click(function(){
       var now_dom = $(this);
	   var other = [];
	   if(now_dom.attr('parameter')=='send_all=1' || now_dom.attr('parameter')=='get_all=1'){
		   $('[name="checkThis"]').each(function(i,val){
		   	if(this.checked) other.push($(this).val());			   
		   })
			   
	   }
	   else{
	   		other.push(now_dom.data('id'));
	   }
	   if(other.length == 0 && check_all==0){
	   	alert("您还没有选中任何数据！");
	   	return false;
	   }
		other = '&send_list='+other.join(",");
       other += '&checkAll='+check_all;

       get_str = $('form').serialize();
       get_str = get_str.replace(/c=\w+&/,'',get_str)
       get_str = get_str.replace(/a=\w+&/,'',get_str)
       other += '&'+get_str;
       
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
               $.post(now_dom.attr('url'),now_dom.attr('parameter')+other,function(result){
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
   

	
	function PrePrint(){
		var str = "";
		if(typeof $('#choose_provincess').val() != 'undefined'){
			str += "&province_idss="+$('#choose_provincess').val();
		}
		if(typeof $('#choose_cityss').val() != 'undefined'){
			str += "&city_idss="+$('#choose_cityss').val();
		}
		if(typeof $('#choose_areass').val() != 'undefined'){
			str += "&area_id="+$('#choose_areass').val();
		}
		window.top.artiframe('{pigcms{:U('pre_print')}'+str+'&goods_id='+$('#goods_id').val()+'&searchtimetype='+$('#searchtimetype').find(":selected").val()+'&begin_time='+$('#begin_time').val()+'&end_time='+$('#end_time').val()+'&searchtype='+$('#searchtype').val()+'&keyword='+$('#keyword').val()+"&page={pigcms{$_GET['page']}",'打印预览','80%','80%',true,false,false,'','add',true);
	}



</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>