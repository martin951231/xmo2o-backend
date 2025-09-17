<include file="Public:header"/>


<div class="main-content">

	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-comments-o comments-o-icon"></i>
			<li class="active"><a href="{pigcms{:U('Analysis/jingdong')}" class="on">{pigcms{:L_("交易信息")}</a></li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				 <div class="form-group" style="margin-bottom:0px">
					<form action="{pigcms{:U('Appoint/order_list')}" method="get" id="myform1">
						<input type="hidden" name="c" value="Analysis"/>
						<input type="hidden" name="a" value="jingdong"/>


							<span class="fl">{pigcms{:L_("日期筛选：")}</span>
							<input type="text" class="input-text fl" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text fl" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							
							<div class="fl time-choice-wrap cf stat-time-choice" style="float: left;">
								<span class="time-btn time-choice  <if condition="$_GET['selectTimeType'] eq 1">time-current</if>" timetype="1" id="choice-today">
								{pigcms{:L_("今天")}</span>
								<span class="time-btn time-choice bor-gray <if condition="$_GET['selectTimeType'] eq 7">time-current</if>" id="choice-week" timetype="7" >
								{pigcms{:L_("近7天")}</span>
								<span class="time-btn time-choice <if condition="$_GET['selectTimeType'] eq 30">time-current</if>" id="choice-month" timetype="30" >
								{pigcms{:L_("近30天")}</span>
								<input type="hidden" id="_selectTimeType" name="selectTimeType" value="{pigcms{$_GET['selectTimeType']}">
							</div>
                        <input type="submit" value="{pigcms{:L_("查询")}" class="button"/>　　
					</form>
				</div>
				<div class="col-xs-12" style="padding-left:0px;padding-right:0px;">
					<div class="white-bg receivables-statist cf" style="padding: 0px 5	px;margin-bottom: 15px;">
						<h3 class="payment-titile clearfix" style="font-size:16px;">
							<span class="title-text">{pigcms{:L_("关键指标")} <i id="layer-tip" style="    font-size: 16px;border-radius: 50%;border: 1px solid orange;padding: 0px 7px;color: orange;font-style: normal;">?</i></span>
							<div class="com-layer-box">
								<i class="icon-question-sign com-layer" ></i>
								<span class="com-layer-text dn" style="display:none">
									<p>1. {pigcms{:L_("账单总额：即支付成功")}+{pigcms{:L_("退款失败的订单金额之和")};</p>
									<p>2. {pigcms{:L_("营业单数：即支付成功")}+{pigcms{:L_("退款失败的订单数之和")};</p>
									<p>3. {pigcms{:L_("商品总额：即支付成功")}+{pigcms{:L_("退款失败的商品售出金额之和")};</p>
									<p>4. {pigcms{:L_("运费总额：即支付成功")}+{pigcms{:L_("退款失败的京东运费之和")};</p>
									<p>5. {pigcms{:L_("进价总额：售出商品的京东进价之和")}</p>
								</span>
							</div>
						</h3>
						
						<div class="statist-wrap-list">
								<div class="statist-wrap fl" style="float:left;margin-right:50px;">
									<p><span class="statist-wrap-title sta-total">{pigcms{:L_("帐单总额")}</span></p>
									<p class="sta-mon">{pigcms{$total_money_sum|floatval}</p>
									<p class="sta-num">{pigcms{$total_money_count|floatval}笔</p>
								</div>
								<div class="statist-wrap fl"  style="float:left;margin-right:50px;">
									<p><span class="statist-wrap-title sta-trade">{pigcms{:L_("商品总额")}</span></p>
									<p class="sta-mon">{pigcms{$total_goods_money_sum|floatval}</p><p class="sta-num">{pigcms{$total_money_count|floatval}笔</p>
                                </div>
                                <div class="statist-wrap fl"  style="float:left;margin-right:50px;">
                                    <p><span class="statist-wrap-title sta-trade">{pigcms{:L_("运费总额")}</span></p>
                                    <p class="sta-mon">{pigcms{$total_freight_money_sum|floatval}</p><p class="sta-num">{pigcms{$total_freight_money_count|floatval}笔</p>
                                </div>
								<div class="statist-wrap fl statist-wrap-spe"  style="float:left">
									<p><span class="statist-wrap-title sta-refund">{pigcms{:L_("进价总额")}</span></p>
									<p class="sta-mon">{pigcms{$total_cost_money_sum|floatval}</p><p class="sta-num">{pigcms{$total_money_count|floatval}笔</p>
								</div>
						</div>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
						
						<thead>
							<tr>
                                <th>{pigcms{:L_("订单号")}</th>
								<th>{pigcms{:L_("时间")}</th>
								<th>{pigcms{:L_("商家信息")}</th>
								<th>{pigcms{:L_("店铺信息")}</th>
								<th>{pigcms{:L_("订单金额")}</th>
								<th>{pigcms{:L_("运费金额")}</th>
								<th>{pigcms{:L_("商品总计")}</th>
								<th>{pigcms{:L_("进价总计")}</th>
								<th>{pigcms{:L_("京东单号")}</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($sale_date)">
								<volist name="sale_date" id="vo">
									<tr>
                                        <td>{pigcms{$vo.real_orderid}</td>
										<td>{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</td>
										<td>{pigcms{$vo.mer_name}</td>
										<td>{pigcms{$vo.store_name}</td>
										<td>{pigcms{$vo.total|floatval}</td>
										<td>{pigcms{$vo.jd_freight|floatval}</td>
										<td>{pigcms{$vo.income|floatval}</td>
										<td>{pigcms{$vo.costPay|floatval}</td>
										<td>{pigcms{$vo.jd_order_id}</td>
										
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="9">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="9">{pigcms{:L_("列表为空！")}</td></tr>
							</if>
						</tbody>
					
						</table>
			
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<style>
.statist-wrap{width:15%;margin-right:2.3%;padding:0 15px;border:1px solid #ccc;border-radius:5px;margin-bottom:15px}.sta-mon{font-size:25px;color:#333;width:100%;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1}.sta-num{font-size:18px;color:#999;width:100%;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.sta-total{background:#1c84c6;padding:2px 7px;color:#fff}.sta-trade{background:#23c6c8;padding:2px 7px;color:#fff}.sta-refund{background:#ed5565;padding:2px 7px;color:#fff}.time-choice-wrap{margin:0 10px;border-radius:3px;border:1px solid #ccc}.time-choice{float:left;display:block;padding:0 10px;line-height:29px;font-size:12px;color:#333;outline:0;cursor:pointer}.time-choice.time-current{color:#fff;background:#1ab394}.bor-gray{border-left:1px solid #ccc;border-right:1px solid #ccc}.fl{margin-right:5px;margin-bottom:5px;}.plat_form{display:none;} 
</style>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
	$(function(){
		
        $('.time-btn').click(function(){
            $('.time-btn').removeClass('time-current')
            $(this).addClass('time-current');
            $('input[name="begin_time"]').val('')
            $('input[name="end_time"]').val('')
            $('#_selectTimeType').val($(this).attr('timetype'))
            $('#myform1').submit();
        })

	});
    $('#layer-tip').on('mouseover',function(){
        //$('.com-layer-text').show();
        layer.tips($('.com-layer-text').html(), '#layer-tip',{
            tips: 1
        });
    });
    $('#layer-tip').on('mouseout',function(){
        layer.closeAll()
    });

	// $('#merchant_worker_id').change(function(){
		// var merchant_worker_id = $(this).val();
		// var Url="{pigcms{:U('Appoint/order_list')}"+'&merchant_worker_id='+merchant_worker_id;
		// location.href=Url;
	// });

	
    var url = "{pigcms{$config.site_url}"

</script>
<include file="Public:footer"/>
