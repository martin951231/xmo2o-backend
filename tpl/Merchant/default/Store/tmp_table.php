<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>

		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>

		<script type="text/javascript" src="{pigcms{$static_path}js/swiper-3.3.1.jquery.min.js"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/swiper-3.3.1.min.css"/>

		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/storestaffBase.js"></script>

		<script type="text/javascript">
			var table_lock_url = "{pigcms{:U('tmp_table_lock')}";
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/storestaffTable.js?123"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>

		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/font-awesome.min.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css?111"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/market.css?{pigcms{$_SERVER['REQUEST_TIME']}"/>
		<style type="text/css">
			@media screen and (max-width: 1280px){
				.tableMain .table_body li{padding: 10px;margin-bottom: 10px;}
				.tableMain .table_body li .cat_name{font-size: 28px;height: 36px;}
				.tableMain .table_body li .cat_btn {font-size: 14px!important;}
			}
		</style>
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
						<li class="urlLink cur" data-url="{pigcms{:U('tmp_table')}">
							<div class="icon table"></div>
							<div class="text">{pigcms{:L_("桌台列表")}</div>
						</li>
						<li class="urlLink" data-url="{pigcms{:U('queue')}">
							<div class="icon queue"></div>
							<div class="text">{pigcms{:L_("排号列表")}</div>
						</li>
						<li class="urlLink" data-url="{pigcms{:U('foodshop')}">
							<div class="icon order"></div>
							<div class="text">{pigcms{:L_("订单列表")}</div>
						</li>
						<if condition="$staff_session['type'] eq 2">
						<li class="urlLink" data-url="{pigcms{:U('foodshop_report')}">
							<div class="icon order"></div>
							<div class="text">{pigcms{:L_("日结账单")}</div>
						</li>
						</if>
					<else/>
						<li class="urlLink" data-url="{pigcms{:U('foodshop')}">
							<div class="icon order"></div>
							<div class="text">{pigcms{:L_("订单列表")}</div>
						</li>
						<li class="urlLink cur" data-url="{pigcms{:U('tmp_table')}">
							<div class="icon table"></div>
							<div class="text">{pigcms{:L_("桌台列表")}</div>
						</li>
						<li class="urlLink" data-url="{pigcms{:U('queue')}">
							<div class="icon queue"></div>
							<div class="text">{pigcms{:L_("排号列表")}</div>
						</li>
						<if condition="$staff_session['type'] eq 2">
						<li class="urlLink" data-url="{pigcms{:U('foodshop_report')}">
							<div class="icon order"></div>
							<div class="text">{pigcms{:L_("日结账单")}</div>
						</li>
						</if>
					</if>
				</ul>
			</div>
			<div class="rightMain tableMain">
				<div class="swiper-button-prev"></div>
				<div class="tabele_cat swiper-container" style="width:auto;">
					<ul class="swiper-wrapper">
						<volist name="table_type" id="vo">
							<li class="swiper-slide <if condition="$vo['id'] eq $now_table['id']">cur</if>" data-href="{pigcms{:U('tmp_table',array('type_id'=>$vo['id']))}">{pigcms{$vo.name}</li>
						</volist>
					</ul>
				</div>
				<div class="swiper-button-next"></div>

				<div class="table_tab_box">
					<div class="table_tab">
						<div class="tab_all cur">{pigcms{:L_("所有")}</div>
						<div class="tab_lock"><if condition="$config['store_open_table_type'] eq 1">{pigcms{:L_("就餐中")}<else/>{pigcms{:L_("锁定")}</if></div>
						<div class="tab_unlock"><if condition="$config['store_open_table_type'] eq 1">{pigcms{:L_("空桌")}<else/>{pigcms{:L_("解锁")}</if></div>
					</div>
					<div class="table_search">
						<input type="text" placeholder="{pigcms{:L_("请输入桌台名称查询")}"/>
					</div>
				</div>

				<div class="table_body">
					<ul>
					<if condition="$config['store_open_table_type'] eq 1">
						<volist name="table_list" id="vo">
							<li class="<if condition="!$vo['order_id']">unlocked<else/>locked</if>" data-id="{pigcms{$vo.id}">
								<div class="cat_name">{pigcms{$vo.name}</div>
								<div class="cat_status">{pigcms{:L_('当前状态：')}
									<if condition="$vo['order_id'] gt 0">
										{pigcms{:L_('就餐中')}
										<else/>
										{pigcms{:L_('空桌')}
									</if>
								</div>
								<div class="cat_btn">
									<div class="cat_status_btn" data-table_id="{pigcms{$vo.id}" data-table_type="{pigcms{$vo.tid}"><if condition="!$vo['order_id']"> {pigcms{:L_("点击使用")}<else/>{pigcms{:L_("点击处理")}</if></div>
									<div class="cat_info_btn">
									<a title="{pigcms{:L_("查看餐桌预定详情")}"  data-title="{pigcms{:L_("餐桌预定详情")}" class="handle_btn" data-layer_id="edit_order" data-title="{pigcms{:L_("编辑订单")}" data-box_width="95%" data-box_height="95%" href="{pigcms{:U('book_list',array('table_id' => $vo['id']))}">
											{pigcms{:L_("查看预定")}
									</a>
									</div>
								</div>
							</li>
						</volist>
					<else/>
						<volist name="table_list" id="vo">
							<li class="<if condition="$vo['status'] eq 0">unlocked<else/>locked</if>" data-id="{pigcms{$vo.id}">
								<div class="cat_name">{pigcms{$vo.name}</div>
								<div class="cat_status">{pigcms{:L_('当前状态：')}
									<if condition="$vo['status'] eq 0">
										{pigcms{:L_('解锁')}
										<else/>
										{pigcms{:L_('锁定')}
									</if>
								</div>
								<div class="cat_btn">
									<div class="cat_status_btn" data-table_id="{pigcms{$vo.id}" data-table_type="{pigcms{$vo.tid}"><if condition="$vo['status'] eq 0">{pigcms{:L_("点击锁定")}<else/>{pigcms{:L_("点击解锁")}</if></div>
									<div class="cat_info_btn">
									<a title="{pigcms{:L_("查看餐桌预定详情")}"  data-title="{pigcms{:L_("餐桌预定详情")}" class="handle_btn" data-layer_id="edit_order" data-title="{pigcms{:L_("编辑订单")}" data-box_width="95%" data-box_height="95%" href="{pigcms{:U('book_list',array('table_id' => $vo['id']))}">
											{pigcms{:L_("查看预定")}
									</a>
									</div>
								</div>
							</li>
						</volist>
					</if>
					</ul>
				</div>
			</div>
		</div>

		<div class="fix container slcontainer counter only_counter" data-type="0">
			<div class="con_pos">
				<h2>{pigcms{:L_("请输入数量")}</h2>
				<input type="text" id="text" name="text" class="text" />
				<div class="buttons">
					<input type="button" value="7" class="button show">
					<input type="button" value="8" class="button show">
					<input type="button" value="9" class="button show">
					<input type="button" value="{pigcms{:L_("退格")}" class="button funback">
					<input type="button" value="4" class="button show">
					<input type="button" value="5" class="button show">
					<input type="button" value="6" class="button show">
					<input type="button" value="{pigcms{:L_("取消")}" class="button cancel funclear">
					<input type="button" value="1" class="button show">
					<input type="button" value="2" class="button show">
					<input type="button" value="3" class="button show">
					<input type="button" value=" " class="button"  style="border: none; background: none;" >
					<input type="button" value="00" class="button show">
					<input type="button" value="0" class="button show">
					<input type="button" value="." class="button point" id="intdd1">
					<input type="button" value="{pigcms{:L_("确认")}" class="button confirm getResult" >
				</div>
				<input type="button"  class="jt funclear">
				<input type="hidden"  id="table_id" value="0">
				<input type="hidden"  id="table_type" value="0">
			</div>
		</div>
		<div class="shadow_two"></div>

		<div class="time_content" style="position: fixed;top: 0;left: 0;bottom: 0;right: 0;background: rgba(0, 0, 0, .6);display: none;">
			<section class="elastic" style="width: 350px;height: 380px; overflow: hidden;position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);">
			<div class="swiper-container swiper-container2 swiper-container-horizontal">
				<div class="swiper-wrapper">
				</div>
				<div class="swiper-button-prev"></div>
				<div class="swiper-button-next"></div>
			</div>
			<div class="Switch" style="height: 270px;">
			</div>
			<div class="Resersub" style="width: 350px;position: static;"><input type="submit" value="{pigcms{:L_("确认选择")}" class=""></div>
			</section>
		</div>
	</body>
	<script type="text/javascript" src="{pigcms{$static_public}js/laytpl.js"></script>
	<script type="text/html" id="table_html">
		<ul>
		{{# for(var i in d){ }}
			<li class="{{#if (d[i].store_open_table_type == '1'){ }}{{#if (!d[i].order_id){ }}unlocked{{# }else{ }}locked{{# } }}" data-id="{{ d[i].id }}{{# }else{ }}{{#if (d[i].status==0){ }}unlocked{{# }else{ }}locked{{# } }}{{# } }}" data-id="{{ d[i].id }}">
				<div class="cat_name">{{ d[i].name }}</div>
				<div class="cat_status">
					{pigcms{:L_("当前状态：")}
					{{#if (d[i].store_open_table_type == '1'){ }}
						{{#if (d[i].order_id){ }}
							{pigcms{:L_("就餐中")}
						{{# }else{ }}
							{pigcms{:L_("空桌")}
						{{# } }}
					{{# }else{ }}
						{{#if (d[i].status == '0'){ }}
							{pigcms{:L_("解锁")}
						{{# }else{ }}
							{pigcms{:L_("锁定")}
						{{# } }}
					{{# } }}</div>
				<div class="cat_btn">
					<div class="cat_status_btn" data-table_id="{{ d[i].id }}" data-table_type="{{ d[i].tid }}">
						{{#if (d[i].store_open_table_type == '1'){ }}
							{{#if (!d[i].order_id){ }}
								{pigcms{:L_("点击使用")}
							{{# }else{ }}
								{pigcms{:L_("点击处理")}
							{{# } }}
						{{# }else{ }}
							{{#if (d[i].status == '0'){ }}
								{pigcms{:L_("点击锁定")}
							{{# }else{ }}
								{pigcms{:L_("点击解锁")}
							{{# } }}
						{{# } }}
					</div>
					<div class="cat_info_btn">
					<a title="{pigcms{:L_("查看餐桌预定详情")}"  data-title="{pigcms{:L_("餐桌预定详情")}" class="handle_btn" data-layer_id="edit_order" data-title="{pigcms{:L_("编辑订单")}" data-box_width="95%" data-box_height="95%" href="{{ d[i].url }}">
							{pigcms{:L_("查看预定")}
					</a>
					</div>
				</div>
			</li>
		{{# } }}
		</ul>
	</script>
	<script type="text/javascript">
	var foodshop_add_order_url = "{pigcms{:U('foodshop_add_order')}";
	var foodshop_order_url = "{pigcms{:U('foodshop_order')}";
	var ajax_get_table_order_url = "{pigcms{:U('ajax_get_table_order')}";
	var store_open_table_type = parseInt("{pigcms{$config['store_open_table_type']}");
	var store_open_table_bind_time = {pigcms{$config['store_open_table_bind_time']|intval};
	var get_appoint_time_url = "{pigcms{:U('get_appoint_time')}";
	var ajax_get_now_appoint_time_url = "{pigcms{:U('ajax_get_now_appoint_time')}";
	var foodshop_order_before_url = "{pigcms{:U('foodshop_order_before')}";
	var ajax_tmp_table_url = "{pigcms{:U('ajax_tmp_table')}";
	var foodshop_url = "{pigcms{:U('foodshop', array('status' => 11))}";
	var type_id = "{pigcms{$_GET['type_id']}";

	$(".shadow_two").click(function(){
		$(".xgcontainer").hide();
		$(this).hide();
	});

	$(function(){
		/*{pigcms{:L_("店铺状态")}*/
		updateStatus(".statusSwitch .ace-switch", ".statusSwitch", "OPEN", "CLOSED", "shopstatus");

		var total_width = $('.table_body').width();
		var item_width = $('.table_body li').width() + 30;
		var num = parseInt( total_width / item_width );
		var padding_num =( total_width - item_width * num) / 2 + 15

		$('.table_body').css('padding-left', padding_num+'px');




	});
	function updateStatus(dom1, dom2, status1, status2, attribute){
		$(dom1).each(function(){
			if($(this).attr("data-status")==status1){
				$(this).attr("checked",true);
			}else{
				$(this).attr("checked",false);
			}
			$(dom2).show();
		}).click(function(){
			var _this = $(this),
				type = 'open',
				id = $(this).attr("data-id");
			_this.attr("disabled",true);
			if(_this.attr("checked")){	//"开启")}
				type = 'open';
			}else{		//"关闭")}
				type = 'close';
			}
			$.ajax({
				url:"{pigcms{:U('Store/table_status')}",
				type:"post",
				data:{"type":type,"id":id,"status1":status1,"status2":status2,"attribute":attribute},
				dataType:"text",
				success:function(d){
					if(d != '1'){		//"失败")}
						if(type=='open'){
							_this.attr("checked",false);
						}else{
							_this.attr("checked",true);
						}
						bootbox.alert("{pigcms{:L_("操作失败")}");
					}
					_this.attr("disabled",false);
				}
			});
		});
	}
	</script>
	<script type="text/javascript" src="{pigcms{$static_path}js/storestaff_foodshop_calll.js"> </script>
</html>