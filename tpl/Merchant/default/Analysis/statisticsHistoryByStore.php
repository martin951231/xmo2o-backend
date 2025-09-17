<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="MSThemeCompatible" content="Yes" />
<link rel="stylesheet" type="text/css" href="/tpl/Merchant/default/static/css/style_2_common.css" />
<link href="/tpl/Merchant/default/static/css/style.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="/tpl/Merchant/default/static/css/cymain.css" />

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
	<if condition="$_GET['business_type'] eq 'market'">
		<thead>
			<tr>
				<th>{pigcms{:L_("日期")}</th>
				<th>{pigcms{:L_("店铺信息")}</th>
				<th>{pigcms{:L_("活动总营业额")}</th>
				<th>{pigcms{:L_("活动总支出")}</th>
				<th>{pigcms{:L_("带动订单")}</th>
				<th>{pigcms{:L_("活动盈利")}</th>
				<th>{pigcms{:L_("活动客单价")}</th>
				<th>{pigcms{:L_("活动老客数")}</th>
				<th>{pigcms{:L_("活动新客数")}</th>
				<th>{pigcms{:L_("投入产出比")}</th>
				<th>{pigcms{:L_("领券数量")}</th>
				<th>{pigcms{:L_("核销率")}</th>
			</tr>
		</thead>
		<volist name="storeArr" id="vo">
			<tr>
			<td>{pigcms{$vo.time_show}</td>
			<td>{pigcms{$vo.name}</td>
			<td>{pigcms{$vo.market_total_money}（{pigcms{$vo.market_total_money_text}）</td>
			<td>{pigcms{$vo.mer_total_money}（{pigcms{$vo.mer_total_money_text}）</td>
			<td>{pigcms{$vo.market_count}（{pigcms{$vo.market_count_text}）</td>
			<td>{pigcms{$vo.mer_income}（{pigcms{$vo.mer_income_text}）</td>
			<td>{pigcms{$vo.unit_price}（{pigcms{$vo.unit_price_text}）</td>
			<td>{pigcms{$vo.regular_customer}（{pigcms{$vo.regular_customer_text}）</td>
			<td>{pigcms{$vo.new_customer}（{pigcms{$vo.new_customer_text}）</td>
			<td>{pigcms{$vo.input_output_rate}%（{pigcms{$vo.input_output_rate_text}）</td>
			<td>{pigcms{$vo.card_new_coupon_hadpull}（{pigcms{$vo.card_new_coupon_hadpull_text}）</td>
			<td>{pigcms{$vo.coupon_use_rate}%（{pigcms{$vo.coupon_use_rate_text}）</td>
			</tr>
		</volist>
	<else/>
		<thead>
			<tr>
				<th>{pigcms{:L_("日期")}</th>
				<th>{pigcms{:L_("店铺信息")}</th>
				<th>{pigcms{:L_("营业额")}</th>
				<th>{pigcms{:L_("订单收入")}</th>
				<th>{pigcms{:L_("有效订单")}</th>
				<th>{pigcms{:L_("活动补贴")}</th>
				<th>{pigcms{:L_("无效订单")}</th>
				<th>{pigcms{:L_("损失")}</th>
			</tr>
		</thead>
		<volist name="storeArr" id="vo">
			<tr>
			<td>{pigcms{$vo.time_show}</td>
			<td>{pigcms{$vo.name}</td>
			<td>{pigcms{$vo.total_money}（{pigcms{$vo.total_money_text}）</td>
			<td>{pigcms{$vo.mer_income}（{pigcms{$vo.mer_income_text}）</td>
			<td>{pigcms{$vo.effective_count_0}（{pigcms{$vo.effective_count_text}）</td>
			<td>{pigcms{$vo.discount_money_0}{pigcms{$vo.discount_money_1}（{pigcms{$vo.discount_money_text}）</td>
			<td>{pigcms{$vo.invalid_count_0}（{pigcms{$vo.invalid_count_text}）</td>
			<td>{pigcms{$vo.loss_money_0}{pigcms{$vo.loss_money_1}（{pigcms{$vo.loss_money_text}）</td>
			</tr>
		</volist>
	</if>
	
</table>
<div class="footactions pagebar" style="padding-left:10px">
  <div class="js-page-list">{pigcms{$page_bar}</div>
</div>
<script>
	var type = window.parent.document.getElementById("date_type").value;
	var start_time = window.parent.document.getElementById("start_time").value;
	var end_time = window.parent.document.getElementById("end_time").value;
	console.log(window.parent.document.getElementById("date_type"))
    var business_type = "{pigcms{$_GET['business_type']}";
    var order_type = "{pigcms{$_GET['order_type']}";
    var store_list = "{pigcms{$_GET['store_list']}";

	

	$('.js-page-list a').live('click',function(e){
		if(!$(this).hasClass('active')){
			var input_val = $('.js-modal-search-input').val();
			$('body').html('<div class="loading-more"><span></span></div>');
			location.href = "{pigcms{:U('statisticsHistoryByStore',array('store_list'=>$_GET['store_list']))}"+'&type='+type+'&start_time='+start_time+'&end_time'+end_time+'&page='+$(this).data('page-num')+'&business_type='+business_type+'&order_type='+order_type;
			// $('body').load("{pigcms{:U('statisticsHistoryByStore',array('store_id'=>$_GET['store_id']))}",{type:date_type,start_time:start_time,end_time:end_time,page:$(this).data('page-num')},function(){
			// 	$('.js-modal iframe',parent.document).height($('body').height());
			// });
		}
	});
</script>
</body>
</html>