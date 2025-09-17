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
		<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
	</head>
	<body>
		<div class="headerBox">
			<div class="txt">{pigcms{$config.meal_alias_name}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_("返回首页")}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_("刷新页面")}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<if condition="$config['store_open_table_type'] eq 1">
						<if condition="!$config['open_meal_electronic_menu'] || !$store['open_from_shop']">
							<li class="urlLink" data-url="{pigcms{:U('tmp_table')}">
								<div class="icon table"></div>
								<div class="text">{pigcms{:L_("桌台列表")}</div>
							</li>
							<li class="urlLink" data-url="{pigcms{:U('queue')}">
								<div class="icon queue"></div>
								<div class="text">{pigcms{:L_("排号列表")}</div>
							</li>
						</if>
						<li class="urlLink cur" data-url="{pigcms{:U('foodshop')}">
							<div class="icon order"></div>
							<div class="text">{pigcms{:L_("订单列表")}</div>
						</li>
						<if condition="$staff_session['type'] eq 2 &&(!$config['open_meal_electronic_menu'] || !$store['open_from_shop'])">
						<li class="urlLink" data-url="{pigcms{:U('foodshop_report')}">
							<div class="icon order"></div>
							<div class="text">{pigcms{:L_("日结账单")}</div>
						</li>
						</if>
					<else/>
						<li class="urlLink cur" data-url="{pigcms{:U('foodshop')}">
							<div class="icon order"></div>
							<div class="text">{pigcms{:L_("订单列表")}</div>
						</li>
						<if condition="!$config['open_meal_electronic_menu'] || !$store['open_from_shop']">
							<li class="urlLink" data-url="{pigcms{:U('tmp_table')}">
								<div class="icon table"></div>
								<div class="text">{pigcms{:L_("桌台列表")}</div>
							</li>
							<li class="urlLink" data-url="{pigcms{:U('queue')}">
								<div class="icon queue"></div>
								<div class="text">{pigcms{:L_("排号列表")}</div>
							</li>
						</if>
						<if condition="$staff_session['type'] eq 2 && (!$config['open_meal_electronic_menu'] || !$store['open_from_shop'])">
						<li class="urlLink" data-url="{pigcms{:U('foodshop_report')}">
							<div class="icon order"></div>
							<div class="text">{pigcms{:L_("日结账单")}</div>
						</li>
						</if>
					</if>
				</ul>
			</div>
			<div class="rightMain">
                <div class="alert waring" style="background-color:#f9cdcd;border-color:#f9cdcd;color:#8c2a2a;display:none;">
                    <i class="ice-icon fa fa-volume-up bigger-130"></i>
                    <p>{pigcms{:L_("您有部分商品库存小于10,请及时")} <a title="{pigcms{:L_("库存报警商品列表")}"  data-title="{pigcms{:L_("库存报警商品列表")}" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('foodshop_goods_stock')}">{pigcms{:L_("查看")}</a>！</p>
                </div>					
                <if condition="(!$config['open_meal_electronic_menu'] || !$store['open_from_shop'])">

					<button class="btn btn-success handle_btn" data-layer_id="add_order" data-title="{pigcms{:L_("创建订单")}" data-box_width="620px" data-box_height="450px" href="{pigcms{:U('foodshop_order_before')}">{pigcms{:L_("创建订单")}</button>
				</if>
				<br/>
				<br/>
				<br/>
				<div class="alert alert-block alert-success">
					<p>
						{pigcms{:L_("注意:在每行的输入框里可以通过输入您想要搜索的订单的关键词")}<br/>
						{pigcms{:L_("在对应的标题下输入对应的关键词后按【Enter】即可搜索")}<br/>
						<br/>
						{pigcms{:L_("页面每5秒会请求一次是否有需要店员操作的订单，若有会一直语音提醒，并有弹层提示，关闭提示会关闭语音。")}
					</p>
				</div>
				<div class="form-group">
					<form action="{pigcms{:U('Stroe/foodshop')}" method="get">
						<input type="hidden" name="c" value="Store"/>
						<input type="hidden" name="a" value="foodshop"/>
						<input type="hidden" name="appoint_id" value="{pigcms{$_GET.appoint_id}"/>
						
						{pigcms{:L_("搜索")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						<select name="searchtype">
							<option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
							<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>{pigcms{:L_("支付流水号")}</option>
							<option value="third_id" <if condition="$_GET['searchtype'] eq 'third_id'">selected="selected"</if>>{pigcms{:L_("第三方支付流水号")}</option>
							<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("客户名称")}</option>
							<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("客户电话")}</option>
						</select>
						<font color="#000">{pigcms{:L_("日期筛选：")}</font>
						<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						{pigcms{:L_("订单状态筛选")}: 
							<select id="operate_status" name="operate_status" <if condition="!$config['open_meal_electronic_menu'] OR !$now_store['open_from_shop']">style="display:none;"</if>>
								<volist name="operate_status_list" id="vo">
									<option value="{pigcms{$key}" <if condition="$key eq $operate_status">selected="selected"</if>>{pigcms{$vo}</option>
								</volist>
								<option value="88" <if condition="'88' eq $operate_status">selected="selected"</if>>未接单/已接单</option>
							</select>

							<select id="status" name="status" <if condition="$config['open_meal_electronic_menu'] && $store['open_from_shop']">style="display:none;"</if>>
								<volist name="status_list" id="vo">
									<option value="{pigcms{$key}" <if condition="$key eq $status">selected="selected"</if>>{pigcms{$vo}</option>
								</volist>
							</select>
						　
				
						{pigcms{:L_("支付方式筛选")}: 
						<select id="pay_type" name="pay_type">
								<option value="" <if condition="'' eq $pay_type">selected="selected"</if>>{pigcms{:L_("全部支付方式")}</option>
							<volist name="pay_method" id="vo">
								<option value="{pigcms{$key}" <if condition="$key eq $pay_type">selected="selected"</if>>{pigcms{$vo.name}</option>
							</volist>
						</select>
						<input type="submit" value="{pigcms{:L_("查询")}" class="btn btn-success" style="padding:2px 14px;"/>　
						<a href="javascript:void(0)" onclick="exports()" class="btn btn-success" style="float:right;margin-right: 10px;">{pigcms{:L_("导出订单")}</a>
					</form>
					
				</div>
                <div class="alert alert-block alert-success" style="margin:10px 0;">
                    <b>{pigcms{:L_("应收总金额：")}{pigcms{$total_price|floatval}</b>　
                    <b>{pigcms{:L_("在线支付总额：")}{pigcms{$online_price|floatval}</b>　
                    <b>{pigcms{:L_("线下支付总额：")}{pigcms{$offline_price|floatval}</b>
                </div>
				<div class="grid-view">
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th width="8%">{pigcms{:L_("订单编号")}</th>
								<th width="5%">{pigcms{:L_("客户姓名")}</th>
								<th width="5%">{pigcms{:L_("客户电话")}</th>
								<th width="5%">{pigcms{:L_("预订金")}</th>
								<th width="10%">{pigcms{:L_("预订时间")}</th>
								<th width="10%">{pigcms{:L_("桌台类型")}</th>
								<th width="5%">{pigcms{:L_("桌台名称")}</th>
								<th width="5%">{pigcms{:L_("状态")}</th>
								<th width="5%">{pigcms{:L_("订单总价")}</th>
								<th width="8%" >{pigcms{:L_("支付方式")}</th>
								<if condition="$config['open_meal_electronic_menu'] && $store['open_from_shop']">
								<th width="10%" class="button-column">{pigcms{:L_("操作")}</th>
								</if>
								<th width="5%" class="button-column">{pigcms{:L_("查看订单详情")}</th>
							</tr>
						</thead>
						<tbody>
							<volist name="order_list" id="vo">
								<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
									<td>{pigcms{$vo.real_orderid}</td>
									<td>{pigcms{$vo.name}</td>
									<td>{pigcms{$vo.phone}</td>
									<td class="button-column">{pigcms{$vo.book_price|floatval}</td>
									<if condition="$vo['is_foodshop_new'] neq 1">
										<if condition="$vo['book_time']">
										<td>{pigcms{$vo.book_time|date='Y-m-d H:i:s',###}</td>
										<else />
										<td>--</td>
										</if>
									<else/>
										<td>{pigcms{$vo.create_time|date='Y-m-d H:i:s',###}</td>										
									</if>
									<td class="button-column">{pigcms{$vo.table_type_name}</td>
									<td class="button-column">{pigcms{$vo.table_name}</td>
									<td class="button-column">
										<if condition="!$vo['is_foodshop_new']">
											<if condition="$vo['status'] eq 0">
											<span>{pigcms{:L_("订单生成")}</span>
											<elseif condition="$vo['status'] eq 1" />
											<span style="color:green">{pigcms{:L_("已付定金")}</span>
											<elseif condition="$vo['status'] eq 2" />
											<span style="color:red">{pigcms{:L_("使用中")}</span>
											<elseif condition="$vo['status'] eq 3" />
											<span style="color:blue">{pigcms{:L_("已买单")}</span>
											<elseif condition="$vo['status'] eq 4" />
											<span style="color:green">{pigcms{:L_("已评价")}</span>
											<elseif condition="$vo['status'] eq 5" />
											<del style="color:red">{pigcms{:L_("已退款")}</del>
											</if>
										<else/>
											<if condition="$vo['operate_status'] eq 0">
											<span>{pigcms{:L_("未接单")}</span>
											<elseif condition="$vo['operate_status'] eq 1" />
											<span style="color:green">{pigcms{:L_("已接单")}</span>
											<elseif condition="$vo['operate_status'] eq 2" />
											<span style="color:blue">{pigcms{:L_("已完成")}</span>
											<elseif condition="$vo['operate_status'] eq 3" />
											<del style="color:red">{pigcms{:L_("已退款")}</del>
											</if>
										</if>
									</td>
									<td class="button-column">
										<if condition="$vo['status'] eq 5">
											{pigcms{:L_("已退款")}
										<elseif condition="$vo['status'] egt 3" />
											{pigcms{$vo.price|floatval}
										<elseif condition="$vo['status'] egt 1" />
											{pigcms{:L_("已付定金")}
										<elseif condition="$vo['status'] lt 1"/>	
											{pigcms{:L_("暂未支付")}
										</if>
									</td>
									<td class="button-column">
									{pigcms{$vo['pay_type_show']}
									</td>
									<if condition="$config['open_meal_electronic_menu'] && $store['open_from_shop'] && $vo['is_foodshop_new']">
										<td class="button-column">
										<if condition="$vo['operate_status'] eq 0">
											<button class="btn btn-success" style="display:inline;padding: 1px 4px;border-radius: 3px;" onclick="change_status(1,{pigcms{$vo.order_id});">{pigcms{:L_("接单")}</button>&nbsp;&nbsp;
											<button class="btn btn-success" style="display:inline;padding: 1px 4px;border-radius: 3px;" onclick="change_status(3,{pigcms{$vo.order_id});">{pigcms{:L_("拒单")}</button>
										<elseif  condition="$vo['operate_status'] eq 1"/>
											<button class="btn btn-success" style="display:inline;padding: 1px 4px;border-radius: 3px;" onclick="change_status(2,{pigcms{$vo.order_id});">{pigcms{:L_("完成订单")}</button>&nbsp;&nbsp;
											<button class="btn btn-success" style="display:inline;padding: 1px 4px;border-radius: 3px;" onclick="change_table({pigcms{$vo.order_id});">{pigcms{:L_("修改桌台号")}</button>
										<else/>
										无
										</if>
										</td>
									</if>
									<td class="button-column">
										<a title="{pigcms{:L_("查看订单详情")}"  data-title="{pigcms{:L_("订单详情")}" class="green handle_btn" style="padding-right:8px;" data-layer_id="edit_order" data-title="{pigcms{:L_("编辑订单")}" data-box_width="<if condition="$vo['status'] lt 3">95%<else />45%</if>" data-box_height="95%" href="{pigcms{:U('foodshop_order',array('order_id'=>$vo['order_id']))}">
											<i class="shortBtn">{pigcms{:L_("查看详情")}</i>
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
		
	</body>
	<script>
		var foodshop_url = "{pigcms{:U('foodshop', array('store_id' => $now_store['store_id'], 'type' => $type, 'sort' => $sort))}&status=11";
		var change_foodshop_status_url = "{pigcms{:U('change_foodshop_status')}";
		var get_table_list_url = "{pigcms{:U('get_table_list')}";
		var change_table_url = "{pigcms{:U('change_table')}";
		$(function(){
            check_foodshop_goods_stock();
			$('#status').change(function(){
				location.href = "{pigcms{:U('foodshop', array('store_id' => $now_store['store_id'], 'type' => $type, 'sort' => $sort))}&status=" + $(this).val();
			});	
		});

		document.onkeydown = function(event_e) {
			if(window.event) event_e = window.event;  
			var int_keycode = event_e.charCode||event_e.keyCode;  
			if(int_keycode ==13 && ($('#order_id').val() != '' || $('#phone').val() != '' || $('#name').val() != '' || $('#meal_pass').val() != '')) $('#queryForList').submit();
		} 

		function change_status(status,order_id) {
			var text = '';
			if (status==1) {
				text = '{pigcms{:L_("是否确认接单？")}';
			}else if(status==2){
				text = '{pigcms{:L_("是否确认完成订单？")}';
			}else{
				text = '{pigcms{:L_("拒单后，用户支付金额将原路退回，无法恢复，是否确认拒单？")}';
			}
			layer.confirm(text, {
			  btn: ['确认', '取消'] 
			}, function(index, layero){
			 	$.post(change_foodshop_status_url,{order_id:order_id,status:status},function(data){
			 		if (data.err_code) {
			 			layer.alert(data.msg);
			 		}else{
			 			layer.alert('{pigcms{:L_("修改成功")}',function(){
			 				location.reload();
			 			})
			 		}
			 	},'json')

			}, function(index){
			  //按钮【按钮二】的回调
			});
		}

		function change_table(order_id) {
			$.post(get_table_list_url,{order_id:order_id},function(data){
		 		var html = '<div id="change_table">\
			<span>选择桌台号</span>\
			<select name="table_id" class="table_id">';
		 		if (data.length>0) {
		 			for (var i = 0; i < data.length; i++) {
		 				html += '<option value="'+data[i].id+'">'+data[i].name+'</option>';
		 			}
		 		}
		 		html += '</select></div>'
		 		// $('.table_id').html(html);
		 		layer.open({
				  content:html
				  ,btn: ['确定', '取消']
				  ,yes: function(index, layero){
				  	var table_id = $('.table_id').val();
				  	// console.log(table_id)
				  	// alert(table_id);return;
				  	$.post(change_table_url,{order_id:order_id,table_id:table_id},function(data){
			 			if (data.err_code) {
				 			layer.alert(data.msg);
				 		}else{
				 			layer.alert('{pigcms{:L_("修改成功")}',function(){
				 				location.reload();
				 			})
				 		}
				 	},'json')
				  }
				  ,cancel: function(){ 
				  }
				});
		 	},'json')
			
    
		}
		
		/*function getNewOrder(time){
			$.post("{pigcms{:U('Store/ajax_foodshop_storestaff_order')}",{time:time},function(result){
				if(result.status == 1){
					playMp3Tip();
					layer.confirm('{pigcms{:L_("您有新的订单需要处理，请问是否刷新页面处理？")}<br/>{pigcms{:L_("点击按钮均可关闭声音。")}', {
					  btn: ['{pigcms{:L_("确认','关闭")}'] //"按钮")}
					}, function(){
						closeMp3Tip();
						location.href = "{pigcms{:U('foodshop', array('store_id' => $now_store['store_id'], 'type' => $type, 'sort' => $sort))}&status=11";
					}, function(){
					  closeMp3Tip();
					});
				}else{
					setTimeout(function(){
						getNewOrder(result.info);
					},5000);
				}
			});
		}*/
		function playMp3Tip(){
			$('body').append('<audio style="display:none;" id="playMp3Tip" controls="true" loop="loop" src="{pigcms{$static_public}file/new_order.mp3"></audio>');
			$('#playMp3Tip').trigger('play');
		}
		function closeMp3Tip(){
			$('#playMp3Tip').trigger('pause');
			$('#playMp3Tip').remove();
		}
		function check_foodshop_goods_stock()
		{
			$.get("{pigcms{:U('Store/check_foodshop_goods_stock')}", function(result){
				if(result.status == 1){
					$('.waring').show();
				} else {
					$('.waring').hide();
				}
				setTimeout(function(){
				    check_foodshop_goods_stock();
				},6000);
			}, 'json');
		}
		$(document).ready(function(){
			// getNewOrder("{pigcms{$_SERVER.REQUEST_TIME}");
		});
	 var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('foodshop_export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<script type="text/javascript" src="{pigcms{$static_path}js/storestaff_foodshop_calll.js"> </script>
</html>