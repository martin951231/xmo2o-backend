<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="MSThemeCompatible" content="Yes" />
<link rel="stylesheet" type="text/css" href="/tpl/Merchant/default/static/css/style_2_common.css" />
<link href="/tpl/Merchant/default/static/css/style.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="/tpl/Merchant/default/static/css/cymain.css" />
<link href="/tpl/Merchant/default/static/diypage/css/customField.css" type="text/css" rel="stylesheet"/>

<script src="/tpl/Merchant/default/static/js/common.js" type="text/javascript"></script>
<script src="/tpl/Merchant/default/static/js/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<style>
body{line-height:180%;}
ul.modules li{padding:4px 10px;margin:4px;background:#efefef;float:left;width:27%;}
ul.modules li div.mleft{float:left;width:40%}
ul.modules li div.mright{float:right;width:55%;text-align:right;}

.logo{
    display:inline-block;
    width:10px;
    height:10px;
}
.pagebar{
    float: right;
    margin-top: 10px;
}

.pagebar a.active {
    background-color: #6faed9;
    border-color: #6faed9;
    color: #fff;
    text-shadow: 0 -1px 0 rgba(0,0,0,.25);
    z-index: 2;
}

.pagebar a {
    border-radius: 0!important;
    color: #2283c5;
    background-color: #fafafa;
    margin: 0 -1px 0 0;
    position: relative;
    z-index: auto;
    border: 1px solid #ddd;
    padding: 5px 14px;
}
.pagebar a.active {
    background-color: #6faed9;
    border-color: #6faed9;
    color: #fff;
    text-shadow: 0 -1px 0 rgba(0,0,0,.25);
    z-index: 2;
}
.pagebar a.fetch_page:hover {
    background-color: #EFEFEF;
}
.pagebar input{    
	border: 1px solid #ddd;
    height: 26px;
}
</style>
</head>
<body style="background:#fff;padding:20px 20px;">
<table class="ListProduct" border="0" cellSpacing="0" cellPadding="0" width="100%">
	<thead>
		<if condition="$_GET['business_type'] eq 'shop' OR $_GET['business_type'] eq ''">
			<tr>
				<th>{pigcms{:L_("日期")}</th>
				<th>{pigcms{:L_("商家名称")}</th>
				<th>{pigcms{:L_("营业额")}</th>
				<th>{pigcms{:L_("商家收入")}</th>
				<th>{pigcms{:L_("平台活动补贴")}</th>
				<th>{pigcms{:L_("商家活动补贴")}</th>
				<th>{pigcms{:L_("平台盈利")}</th>
				<th>{pigcms{:L_("平台抽成")}</th>
				<th>{pigcms{:L_("平台配送费")}</th>
				<th>{pigcms{:L_("有效订单")}</th>
				<th>{pigcms{:L_("无效订单")}</th>
				<th>{pigcms{:L_("损失")}</th>
			</tr>
		<elseif condition="$_GET['business_type'] eq 'deliver'" />
			<tr>
				<th>日期</th>
				<th>商家名称</th>
				<th>平台总配送费</th>
				<th>平台配送费抽成</th>
				<th>有效配送单数</th>
				<th>活动补贴</th>
				<th>配送超时订单数</th>
				<th>配送超时超时率</th>
				<th>催单数</th>
				<th>催单率</th>
				<th>差评订单数</th>
				<th>差评率</th>
				<th>无效配送单数</th>
				<th>损失</th>
			</tr>
		<elseif condition="$_GET['business_type'] eq 'market'" />
			<tr>
				<th>日期</th>
				<th>商家名称</th>
				<th>活动总营业额</th>
				<th>活动总支出</th>
				<th>带动订单</th>
				<th>活动盈利</th>
				<th>活动商家收入</th>
				<th>活动客单价</th>
				<th>活动老客数</th>
				<th>活动新客数</th>
				<th>投入产出比</th>
			</tr>
		<elseif condition="$_GET['business_type'] eq 'dining'" />
			<tr>
				<th>{pigcms{:L_("日期")}</th>
				<th>{pigcms{:L_("商家名称")}</th>
				<th>{pigcms{:L_("营业额")}</th>
				<th>{pigcms{:L_("商家收入")}</th>
				<th>{pigcms{:L_("平台活动补贴")}</th>
				<th>{pigcms{:L_("商家活动补贴")}</th>
				<th>{pigcms{:L_("平台盈利")}</th>
				<th>{pigcms{:L_("平台抽成")}</th>
				<th>{pigcms{:L_("有效订单")}</th>
				<th>{pigcms{:L_("无效订单")}</th>
			</tr>
		</if>
	</thead>
	<if condition="$merchantArr">
		<volist name="merchantArr" id="vo">
			<if condition="$_GET['business_type'] eq 'shop' OR $_GET['business_type'] eq ''">
				<tr>
					<td>{pigcms{$vo.time_show}</td>
					<td>{pigcms{$vo.name}</td>
					<td>{pigcms{$vo.total_money}（{pigcms{$vo.total_money_text}）</td>
					<td>{pigcms{$vo.mer_income}（{pigcms{$vo.mer_income_text}）</td>
					<td>{pigcms{$vo.plat_total_money}（{pigcms{$vo.plat_total_money_text}）</td>
					<td>{pigcms{$vo.mer_total_money}（{pigcms{$vo.mer_total_money_text}）</td>
					<td>{pigcms{$vo.plat_income}（{pigcms{$vo.plat_income_text}）</td>
					<td>{pigcms{$vo.plat_get_commission}（{pigcms{$vo.plat_get_commission_text}）</td>
					<td>{pigcms{$vo.plat_freight_charge}（{pigcms{$vo.plat_freight_charge_text}）</td>
					<td>{pigcms{$vo.effective_count}（{pigcms{$vo.effective_count_text}）</td>
					<td>{pigcms{$vo.invalid_count_0}（{pigcms{$vo.invalid_count_text}）</td>
					<td>{pigcms{$vo.loss_money}（{pigcms{$vo.loss_money_text}）</td>
				</tr>
			<elseif condition="$_GET['business_type'] eq 'deliver'" />
				<tr>
					<td>{pigcms{$vo.time_show}</td>
					<td>{pigcms{$vo.name}</td>
					<td>{pigcms{$vo.total_money}（{pigcms{$vo.total_money_text}）</td>
					<td>{pigcms{$vo.plat_freight_charge}（{pigcms{$vo.plat_freight_charge_text}）</td>
					<td>{pigcms{$vo.effective_count}（{pigcms{$vo.effective_count_text}）</td>
					<td>{pigcms{$vo.discount_money}（{pigcms{$vo.discount_money_text}）</td>
					<td>{pigcms{$vo.overtime_count}（{pigcms{$vo.overtime_count_text}）</td>
					<td>{pigcms{$vo.overtime_rate}%（{pigcms{$vo.overtime_rate_text}）</td>
					<td>{pigcms{$vo.reminder_count}（{pigcms{$vo.reminder_count_text}）</td>
					<td>{pigcms{$vo.reminder_rate}%（{pigcms{$vo.reminder_rate_text}）</td>
					<td>{pigcms{$vo.bad_count}（{pigcms{$vo.bad_count_text}）</td>
					<td>{pigcms{$vo.bad_rate}%（{pigcms{$vo.bad_rate_text}）</td>
					<td>{pigcms{$vo.invalid_count}（{pigcms{$vo.invalid_count_text}）</td>
					<td>{pigcms{$vo.loss_money}（{pigcms{$vo.loss_money_text}）</td>
				</tr>
			<elseif condition="$_GET['business_type'] eq 'market'" />
				<tr>
					<td>{pigcms{$vo.time_show}</td>
					<td>{pigcms{$vo.name}</td>
					<td>{pigcms{$vo.market_total_money}（{pigcms{$vo.market_total_money_text}）</td>
					<td>{pigcms{$vo.plat_total_money}（{pigcms{$vo.plat_total_money_text}）</td>
					<td>{pigcms{$vo.market_count}（{pigcms{$vo.market_count_text}）</td>
					<td>{pigcms{$vo.plat_income}（{pigcms{$vo.plat_income_text}）</td>
					<td>{pigcms{$vo.mer_income}（{pigcms{$vo.mer_income_text}）</td>
					<td>{pigcms{$vo.unit_price}（{pigcms{$vo.unit_price_text}）</td>
					<td>{pigcms{$vo.regular_customer}（{pigcms{$vo.regular_customer_text}）</td>
					<td>{pigcms{$vo.new_customer}（{pigcms{$vo.new_customer_text}）</td>
					<td>{pigcms{$vo.input_output_rate}%（{pigcms{$vo.input_output_rate_text}）</td>
					<td>
				</tr>
			<elseif condition="$_GET['business_type'] eq 'dining'" />
				<tr>
					<td>{pigcms{$vo.time_show}</td>
					<td>{pigcms{$vo.name}</td>
					<td>{pigcms{$vo.total_money}（{pigcms{$vo.total_money_text}）</td>
					<td>{pigcms{$vo.mer_income}（{pigcms{$vo.mer_income_text}）</td>
					<td>{pigcms{$vo.plat_total_money}（{pigcms{$vo.plat_total_money_text}）</td>
					<td>{pigcms{$vo.mer_total_money}（{pigcms{$vo.mer_total_money_text}）</td>
					<td>{pigcms{$vo.plat_income}（{pigcms{$vo.plat_income_text}）</td>
					<td>{pigcms{$vo.plat_get_commission}（{pigcms{$vo.plat_get_commission_text}）</td>
					<td>{pigcms{$vo.effective_count}（{pigcms{$vo.effective_count_text}）</td>
					<td>{pigcms{$vo.invalid_count_0}（{pigcms{$vo.invalid_count_text}）</td>
				</tr>
			</if>
		</volist>
	<else/>
		<tr><td colspan="20">暂无数据</td></tr>
	</if>
</table>
<div class="footactions pagebar" style="padding-left:10px">
  <div class="js-page-list">{pigcms{$page_bar}</div>
</div>
<script>
	var type = window.parent.document.getElementById("date_type").value;
	var start_time = window.parent.document.getElementById("start_time").value;
	var end_time = window.parent.document.getElementById("end_time").value;
    // var province = window.parent.document.getElementById('choose_provincess').value;
    // var city = window.parent.document.getElementById('choose_cityss').value;
    // var area = window.parent.document.getElementById('choose_areass').value;
    var business_type = "{pigcms{$_GET['business_type']}";
    var order_type = "{pigcms{$_GET['order_type']}";
    var province_id = "{pigcms{$_GET['province_id']}";
    var city_id = "{pigcms{$_GET['city_id']}";
    var area_id = "{pigcms{$_GET['area_id']}";

	$('.js-page-list a').live('click',function(e){
		if(!$(this).hasClass('active')){
			var input_val = $('.js-modal-search-input').val();
			$('body').html('<div class="loading-more"><span></span></div>');
			location.href = "{pigcms{:U('statisticsHistoryByMer',array('store_id'=>$_GET['store_id']))}"+'&type='+type+'&start_time='+start_time+'&end_time='+end_time+'&page='+$(this).data('page-num')+'&province_id='+province_id+'&city_id='+city_id+'&area_id='+area_id+'&business_type='+business_type+'&order_type='+order_type;
			// $('body').load("{pigcms{:U('statisticsHistoryByStore',array('store_id'=>$_GET['store_id']))}",{type:date_type,start_time:start_time,end_time:end_time,page:$(this).data('page-num')},function(){
			// 	$('.js-modal iframe',parent.document).height($('body').height());
			// });
		}
	});
</script>
</body>
</html>