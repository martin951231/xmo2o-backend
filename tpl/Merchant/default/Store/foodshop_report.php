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
		<style type="text/css">
			b{margin-right: 10px;}
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
						<li class="urlLink" data-url="{pigcms{:U('tmp_table')}">
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
						<li class="urlLink cur" data-url="{pigcms{:U('foodshop_report')}">
							<div class="icon order"></div>
							<div class="text">{pigcms{:L_("日结账单")}</div>
						</li>
					<else/>
						<li class="urlLink" data-url="{pigcms{:U('foodshop')}">
							<div class="icon order"></div>
							<div class="text">{pigcms{:L_("订单列表")}</div>
						</li>
						<li class="urlLink" data-url="{pigcms{:U('tmp_table')}">
							<div class="icon table"></div>
							<div class="text">{pigcms{:L_("桌台列表")}</div>
						</li>
						<li class="urlLink" data-url="{pigcms{:U('queue')}">
							<div class="icon queue"></div>
							<div class="text">{pigcms{:L_("排号列表")}</div>
						</li>
						<li class="urlLink cur" data-url="{pigcms{:U('foodshop_report')}">
							<div class="icon order"></div>
							<div class="text">{pigcms{:L_("日结账单")}</div>
						</li>
					</if>
				</ul>
			</div>
			<div class="rightMain">
                <div class="alert alert-block alert-success" style="margin:10px 0;">
                    <b>{pigcms{:L_("账单时间：")}{pigcms{$start_time} — {pigcms{$end_time}</b>　<br/><br/>

                    <b>{pigcms{:L_("消费单数：")}<span class="num"></span></b>　
                    <b>{pigcms{:L_("消费人数：")}<span class="people_num"></span></b>　
                    <b>{pigcms{:L_("人均消费：")}<span class="per_capita_consumption"></span></b>
                </div>
				<div class="grid-view">
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th width="50">{pigcms{:L_("收款方式")}</th>
								<th width="80">{pigcms{:L_("消费次数")}</th>
								<th width="80">{pigcms{:L_("订单金额")}</th>
								<th width="80">{pigcms{:L_("实付金额")}</th>
							</tr>
						</thead>
						<tbody id="report_content">
							<volist name="order_list" id="vo">
								<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
									<td>{pigcms{$vo.real_orderid}</td>
									<td>{pigcms{$vo.name}</td>
									<td>{pigcms{$vo.phone}</td>
									<td>{pigcms{$vo.phone}</td>
								</tr>
							</volist>
						</tbody>
					</table>
				</div>
 				<div class="alert alert-block alert-success discount-content" style="margin:10px 0;">
                    <b>{pigcms{:L_("消费单数：")}{pigcms{$total_price|getFormatNumber}</b>　
                    <b>{pigcms{:L_("消费人数：")}{pigcms{$online_price|getFormatNumber}</b>　
                    <b>{pigcms{:L_("人均消费：")}{pigcms{$offline_price|getFormatNumber}</b>
                    <b>{pigcms{:L_("人均消费：")}{pigcms{$offline_price|getFormatNumber}</b>
                </div>
				<button class="btn btn-success print_report">{pigcms{:L_("打印")}</button>
			</div>
		</div>
	</body>
<script>
int_html();

function int_html()
{
	var index = layer.load(0, {shade: false}); //0{pigcms{:L_("代表加载的风格，支持")}0-2
	$.post('{pigcms{:U("Store/ajax_foodshop_report")}', {}, function(response){
		if (response.error_code) {
		} else {
			var html = '';
			if (response.data.pay_type_report.length > 0) {
				$.each(response.data.pay_type_report, function(i, row){
					html += '<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">';
					html += '<td>' + row.pay_type_name + '</td>';
					html += '<td>' + row.num + '</td>';
					html += '<td>' + row.total_price + '</td>';
					html += '<td>' + row.money + '</td>';
					html += '</tr>';
				});
			} else {
				html = '<tr class="odd"><td class="button-column" colspan="4" >{pigcms{:L_("暂无订单统计记录。")}</td></tr>';
			}
			$('tbody').html(html);

			html = '';
			if (response.data.discount) {
				$.each(response.data.discount, function(i, row){
					html += '<b>' + row.name + '：' + row.money+'</b>';
				});
			}
			$('.discount-content').html(html);
			$('.num').html(response.data.num);
			$('.people_num').html(response.data.people_num);
			$('.per_capita_consumption').html(response.data.per_capita_consumption);
			layer.closeAll('loading');
		}
	},'json');
}
$('.print_report').click(function(){
		layer.confirm('{pigcms{:L_("打印日结账单。")}<br/><font color="red">{pigcms{:L_("确认打印？")}</span>', {
			title:'{pigcms{:L_("确认提醒：")}',
			btn: ['{pigcms{:L_("确认','取消")}']
		}, function(index){
			var tip_index = layer.load(0, {shade: [0.5,'#fff']});
			
			$.post('{pigcms{:U("Store/ajax_foodshop_report_print")}',function(result){
				console.log(result)
				layer.close(index);
				layer.close(tip_index);
				layer.alert(result.info);
			},'json');
		});
	});
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
</html>