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
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.pagination.min.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
		<style>
		td{
			text-align: center;
		}
		.ui-pagination-container {
			height: 34px;
			line-height: 34px;
		}

		.ui-pagination-container .ui-pagination-page-item {
			font-size: 14px;
			padding: 4px 10px;
			background: #fff;
			border: 1px solid #c5b7b7;
			color: #888;
			margin: 0 3px;
			text-decoration: none;
		}

		.ui-pagination-container .ui-pagination-page-item:hover {
			border-color: #00c484;
			color: #00c484;
			text-decoration: none;
		}

		.ui-pagination-container .ui-pagination-page-item.active {
			background: #00c484;
			border-color: #00c484;
			color: #fff;
			cursor: default;
		}
		.fl {
			float: right;
		}
		</style>
	</head>
	<body>
		<div class="headerBox">
			<div class="txt">{pigcms{:L_("报表统计")}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_("返回首页")}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_("刷新页面")}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink <if condition="$type eq 0">cur</if>" data-url="{pigcms{:U('report', array('type' => 0))}">
						<div class="icon list"></div>
						<div class="text">{pigcms{$config.meal_alias_name}{pigcms{:L_("统计")}</div>
					</li>
					<li class="urlLink <if condition="$type eq 1">cur</if>" data-url="{pigcms{:U('report', array('type' => 1))}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("到店订单统计")}</div>
					</li>
					<li class="urlLink <if condition="$type eq 2">cur</if>" data-url="{pigcms{:U('report', array('type' => 2))}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("线下零售统计")}</div>
					</li>
					<if condition="C('customization.internal_membership_card') eq 1">
						<li class="urlLink <if condition="$type eq 3">cur</if>" data-url="{pigcms{:U('report', array('type' => 3))}">
							<div class="icon order"></div>
							<div class="text">{pigcms{:L_("日结报表")}</div>
						</li>
					</if>
				</ul>
			</div>
			<div class="rightMain">
				<div class="alert alert-block alert-success" style="position:relative;">
					<span>{pigcms{:L_("时间筛选：")}</span>
					<div style="display:inline-block;" class="timeValue">
						<select class='custom-date' id="time_value" name='select' <if condition="$type eq 3">type="newDate"</if>>
							<option  value='1'>{pigcms{:L_("今天")}</option>
							<option <if condition="!$config['open_staff_power'] || $staff_session['type'] eq 2">selected='selected'</if> value='7'>7天</option>
							<option value='30'>30天</option>
							<option value='180'>180天</option>
							<option value='365'>365天</option>
							<option value='custom'>{pigcms{:L_("自定义")}</option>
						</select>
					</div>
					<input type="button" value="{pigcms{:L_("查询")}" class="button" id="time" style="width: 60px;height: 25px;"/>
				</div>
				<div class="grid-view">
					<table class="table table-striped table-bordered table-hover">
						<if condition="$type eq 3">
							<thead>
								<tr>									
									<th id="shopList_c1" width="50">{pigcms{:L_("报表日期")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("收款总金额")}</th>
									<th id="shopList_c1" width="50" >{pigcms{:L_("总笔数")}</th>
									<th id="shopList_c1" width="50" >{pigcms{:L_("一卡通实收金额")}</th>
									<th id="shopList_c1" width="50" >{pigcms{:L_("余额支付")}</th>
									<th id="shopList_c1" width="50" >{pigcms{:L_("支付宝支付")}</th>
									<th id="shopList_c1" width="50" >{pigcms{:L_("微信支付")}</th>
									<th id="shopList_c1" width="50" >{pigcms{:L_("退货金额")}</th>
									<th id="shopList_c5" width="50" >{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
						<else/>
							<thead>
								<tr>									
									<th id="shopList_c1" width="50">{pigcms{:L_("支付类型")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("支付总额")}</th>
									<th id="shopList_c5" width="60" >{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
						</if>
						<tbody>
						</tbody>
					</table>
				</div>
				<div class="box">
					<div id="pagination" class="page fl"></div>
				</div>
			</div>
		</div>
	</body>
<script>
var day = '', period = '', type = '{pigcms{$type}';
var params = {
	page: 1,
	page_size: 10,
	total: 0,
	start_date: '',
	end_date: '',
}
var internal_membership_card = "{pigcms{:C('customization.internal_membership_card')}"
$(document).ready(function(){
	$('#time').click(function(){
		int_html();
	});
	int_html();
	// "店员 非店长只能查看今天")}
	var type = {pigcms{$staff_session['type']|intval};
	var open_staff_power = {pigcms{$config['open_staff_power']|intval};
	if (type!=2&&open_staff_power==1) {
		$('.timeValue').on('click',function(){
			layer.alert('{pigcms{:L_("您是核销员，暂无查看权限")}');return false;;;
		})
	}
});

function int_html()
{
	if($('#time_value option:selected').attr('value')=='custom'){
		period = $('#time_value option:selected').html();
	}else{
		day = $('#time_value option:selected').attr('value');
	}
	var index = layer.load(0, {shade: false}); //0{pigcms{:L_("代表加载的风格，支持")}0-2

	//一卡通定制【ID1017628】
	if(type == 3 && internal_membership_card == 1){
		var html = '';
		const token = getCookieValue('storestaff_access_token');
		let originalDate = period.split(' - ')
		// 使用 replace() 方法将斜杠替换为短横线
		const start_date = originalDate[0].replace(/\//g, '-');
		const end_date = originalDate[1].replace(/\//g, '-');
		params.start_date = start_date
		params.end_date = end_date
		// 定义自定义的请求头
		const headers = {
			'Ticket': token,
		};
		// 发送 POST 请求
		$.ajax({
			url: '/v20/public/index.php/storestaff/storestaff.index/storeDateBill',
			type: 'POST',
			data: params,
			headers: headers,
			success: function(response) {
				let data = response.data;
				params.total = data.total
				// 请求成功时的处理
				layer.closeAll('loading');
				$('tbody').empty();
				if(data.data.length > 0){
					data.data.forEach(v=>{
					html += `
						<tr class="odd">
							<td>${v.bill_date}</td>
							<td>${v.pay_money}</td>
							<td>${v.pay_order_count}</td>
							<td>${v.internal_card_pay_money}</td>
							<td>${v.balance_pay_money}</td>
							<td>${v.alipay_pay_money}</td>
							<td>${v.wechat_pay_money}</td>
							<td>${v.refund_money}</td>
							<td nowrap="">
								<a title="打印" data-title="打印" style="cursor: pointer;" data-id="${v.id}" data-bill_date="${v.bill_date}" onclick="storeDateBillPrint(event)">打印</a>
							</td>
						</tr>
					`
				})
				}else{
					html = '<tr class="odd"><td class="button-column" colspan="9" >{pigcms{:L_("暂无订单统计记录。")}</td></tr>';
				}
				$('tbody').html(html);
				$("#pagination").pagination({
					currentPage: params.page,
					totalPage: params.total,
					isShow: false,
					count: 5,
					prevPageText: "< 上一页",
					nextPageText: "下一页 >",
					callback: function(current) {
						params.page = current;
						int_html();
					}
				});
			},
			error: function(xhr, status, error) {
				// 请求失败时的处理
				html = '<tr class="odd"><td class="button-column" colspan="9" >{pigcms{:L_("暂无订单统计记录。")}</td></tr>';
				layer.closeAll('loading');
				$('tbody').html(html);
			}
		});
		return
	}
	$.post('{pigcms{:U("Store/ajax_report")}', {'type':type, 'day':day, 'period':period}, function(response){
		if (response.error_code) {
		} else {
			var html = '';
			if (response.count > 0) {
				$.each(response.data, function(i, row){
					html += '<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">';
					html += '<td>' + row.pay_type + '</td>';
					html += '<td>' + row.money + '</td>';
					html += '<td nowrap>';
					html += '<a title="{pigcms{:L_("查看订单详情")}" data-title="{pigcms{:L_("报表详情")}" class="green handle_btn" style="padding-right:8px;" href="' + row.report_detail + '">';
					html += '<i class="shortBtn">{pigcms{:L_("查看详情")}</i>';
					html += '</a>';
					if(!checkApp()){
						html += '<a href="' + row.report_export + '">';
						html += '<i class="shortBtn">{pigcms{:L_("下载报表")}</i>';
						html += '</a>';
					}
					html += '</td>';
					html += '</tr>';
				});
			} else {
				html = '<tr class="odd"><td class="button-column" colspan="3" >{pigcms{:L_("暂无订单统计记录。")}</td></tr>';
			}
			layer.closeAll('loading');
			$('tbody').html(html);
		}
	},'json');
}

//日结报表打印事件
function storeDateBillPrint(event){
	let item = $(event.target).data()
	layer.alert('{pigcms{:L_("确定要打印吗？")}', {
		btn: ['确定','取消'],
		title: '{pigcms{:L_("提示")}',
	},function(){
		const token = getCookieValue('storestaff_access_token');
		// 定义自定义的请求头
		const headers = {
			'Ticket': token,
		};
		// 发送 POST 请求
		$.ajax({
			url: '/v20/public/index.php/storestaff/storestaff.index/printStoreDateBill',
			type: 'POST',
			data: {
				bill_date: item.bill_date,
			},
			headers: headers,
			success: function(response) {
				if(response.status == 1000){
					layer.msg('{pigcms{:L_("打印成功")}');
				}else{
					layer.msg(response.msg);
				}
			},
			error: function(xhr, status, error) {
				// 请求失败时的处理
			}
		});
	});
}

// 获取指定名称的 cookie 值
function getCookieValue(cookieName) {
	var cookies = document.cookie;
	if (cookies) {
		var cookieArr = cookies.split(';');
		for (var i = 0; i < cookieArr.length; i++) {
		var cookie = cookieArr[i].trim();
		if (cookie.indexOf(cookieName + '=') === 0) {
			return decodeURIComponent(cookie.substring(cookieName.length + 1));
		}
		}
	}
	return null;
}
</script>
	<script type="text/javascript" src="{pigcms{$static_public}js/date-picker/index.js?t=1"></script>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}js/date-picker/index.css" />
</html>