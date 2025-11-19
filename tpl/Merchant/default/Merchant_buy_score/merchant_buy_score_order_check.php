<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/styles.css">
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<title>{pigcms{$config.site_name} - {pigcms{:L_("商家中心")}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<script src="{pigcms{$static_public}js/layer/layer.js"></script>
</head>

<body class="no-skin" style="background-color: #FFFFFF;">
<div class="main-content">
	
	<style>
		.payment-bank {
			margin-top: 10px;
			border: 1px solid #DFDFDF;
			padding: 5px 0 10px 20px;
			background-color: #F5F5F5;
		}
		.payment-banktit {
			height: 20px;
			line-height: 20px;
			margin-top: 5px;
			padding: 5px 0;
			font-family: \5b8b\4f53;
			cursor: pointer;
		}
		.payment-banktit b {
			display: inline-block;
			height: 20px;
			padding-left: 17px;
			color: #333;
			font-size: 14px;
		}
		.payment-bankcen {
			padding-top: 10px;
		}
		.bank {
			width: 786px;
			padding: 15px 0 0 20px;
		}
		.payment-bankcen .bank{
			padding-top: 0;
			width: 1210px;
		}
		.imgradio li {
			padding-left: 20px;
			width: 112px;
			height: 32px;
			float: left;
			position: relative;
			margin: 0 25px 15px 0;
			_display: inline;
			_zomm: 1;
		}
        .imgradio li input {
            position: absolute;
            left: 0;
            top: 3px;
        }
		.imgradio li label{
			cursor:pointer;
		}
		.payment-bankcen .bank .imgradio li {
			margin-right: 45px;
		}
		.clr {
			height: 0;
			font-size: 0;
			line-height: 0;
			clear: both;
			overflow: hidden;
		}
		.form-submit {
			margin: 30px 0 20px;
		}
		
		
#bd {
  width: 1210px;
  margin: 0 auto;
  padding: 10px 0 65px;
    border-top: 3px solid #fe5842;
  margin-top:20px;
}

#content {
  float: left;
  width: 1210px;
  _display: inline;
  padding: 0;
}
.cf {
  zoom: 1;
}		
		
.sysmsgw {
  width: 1150px;
  margin: 10px auto 0;
}
		
.common-tip {
  position: relative;
  margin-bottom: 10px;
  padding: 10px 30px;
  border: 1px #F5D8A7 solid;
  border-radius: 2px;
  background: #FFF6DB;
  font-size: 14px;
  text-align: center;
  color: #666;
  zoom: 1;
}
.mainbox {
  border: none;
  padding: 0;
  padding-bottom: 60px;
}	
.summary-table {
    font-size: 12px;
    overflow: visible;
    margin: 0 0 10px;
}

.btn_btn {
    color: #fff;
    background-color: #2db3a6;
    border-color: #0D7B71;
    filter: progid:DXImageTransform.Microsoft.gradient(gradientType=0,
	startColorstr='#FF2EC3B4', endColorstr='#FF2DB3A6');
    background-size: 100%;
    background-image: -moz-linear-gradient(top, #2ec3b4, #2db3a6);
    background-image: -webkit-linear-gradient(top, #2ec3b4, #2db3a6);
    background-image: linear-gradient(to bottom, #2ec3b4, #2db3a6);
}

.btn_btn, .btn-hot, .btn-normal {
    display: inline-block;
    vertical-align: middle;
    padding: 7px 20px 6px;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.5;
    font-family: SimSun, Arial;
    letter-spacing: .1em;
    text-align: center;
    text-decoration: none;
    border-width: 0 0 1px;
    border-style: solid;
    background-repeat: repeat-x;
    -moz-border-radius: 2px;
    -webkit-border-radius: 2px;
    border-radius: 2px;
    -moz-user-select: -moz-none;
    -ms-user-select: none;
    -webkit-user-select: none;
    user-select: none;
    cursor: pointer;
}
.form-submit{
	overflow: hidden;
    text-align: right;
}
.table-section table td, .table-section table th {
    border-bottom: 1px dotted #e5e5e5;
    padding: 12px;
}

.table-section table {
	float: left;
    width: 100%;
}

		
	</style>

	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12">
					<form action="{pigcms{:U('Pay/go_pay')}" method="post" id="deal-buy-form" class="common-form J-wwwtracker-form" >
			            <div class="mainbox cf" style="min-height:0px;">
							<div class="table-section">
								<table class="">
					            	<tr>
					            		<td>{pigcms{:L_("平台积分价格：")}</td>
					            		<td>{pigcms{$config['Currency_symbol']}<strong id="deal-buy-total-t" class="need_pay">{pigcms{$config.merchant_buy_points} {pigcms{:L_("积分")}( 1{pigcms{$config['Currency_txt']}{pigcms{:L_("购买积分数")} )</strong></td>
					            	</tr>
					            	<tr>
					            		<td>{pigcms{:L_("购买积分数量：")}</td>
					            		<td><strong id="deal-buy-total-t" class="need_pay">{pigcms{$order_info.sore_count}</strong>({pigcms{:L_("积分")})</td>
					            	</tr>
					            	<tr>
					            		<td>{pigcms{:L_("订单编号：")}</td>
					            		<td><strong id="deal-buy-total-t" class="need_pay">{pigcms{$order_info.orderid}</strong></td>
					            	</tr>
					            </table>
							</div>
			            

							<div id="need-pay" style="text-align:right">
								<strong>{pigcms{:L_("总金额")}</strong>：
								<span class="inline-block money" style="font-size:20px;color:#EA4F01;">
									{pigcms{$config['Currency_symbol']}<strong id="deal-buy-total-t" class="need_pay">{pigcms{$order_info.payment_money}</strong>
								</span>
							</div>
					
						
						<div id="pay_bank_list" >
							<div class="payment-bank">
								<div class="payment-banktit">
									<b class="open">{pigcms{:L_("选择支付方式")}</b>
								</div>	
								<div class="payment-bankcen">
									<div class="bank morebank">
										<ul class="imgradio" style="list-style:none">
											<volist name="pay_method" id="vo">
												<li>
													<label>
														<input type="radio" name="pay_type" value="{pigcms{$key}" <if condition="$i eq 1">checked="checked"</if>>
														<img src="{pigcms{$static_public}images/pay/{pigcms{$key}.gif" width="112" height="32" alt="{pigcms{$vo.name}" title="{pigcms{$vo.name}"/>
													</label>
												</li>
											</volist>

											<li style="width: 300px">
												<label>
													<input type="radio" name="pay_type" value="yue" checked="checked">
													<span style="font-size: 18px;">{pigcms{:L_("余额支付")}</span><span>（{pigcms{$mer_info.money}）</span>
												</label>
											</li>
										</ul>
										<div class="clr"></div>
									</div>
								</div>
								<div class="clr"></div>
							</div>
						</div>
						
						<div class="form-submit">
							<input type="hidden" name="order_id" value="{pigcms{$order_info.order_id}"/>
				    		<input type="hidden" name="order_type" value="{pigcms{$order_info.order_type}"/>
			                <input id="J-order-pay-button" type="submit" class="btn_btn btn-large btn-pay" name="commit" value="{pigcms{:L_("去付款")}"/><br/>
			            </div>
			    	</form>	
				</div>
			</div>
		</div>
		
	</div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
$(function(){
	
	$("form").submit(function() {
	   $("#J-order-pay-button").val("{pigcms{:L_("正在处理")}...");
	   $("#J-order-pay-button").attr("disabled", "disabled");
	});

	
	$('#deal-buy-form').submit(function(){
        if($('input[name="pay_type"]:checked').val() == 'yue'){
            layer.confirm('{pigcms{:L_("您确定要用余额支付吗？")}', {
                btn: ['{pigcms{:L_("确定','取消")}'] //"按钮")}
            }, function(){
                var pay_yue_url = "{pigcms{:U('go_pay_yue')}";
                var order_id = "{pigcms{$_GET['order_id']}";
                $.post(pay_yue_url,{order_id:order_id},function(data){
                    if(data.error == 1){
                        layer.msg(data.msg);
                        parent.location.reload();
                    }else if(data.error == 4){
                        layer.msg(data.msg);
                        parent.location.reload();
                    }else if(data.error == 3){
                        layer.msg(data.msg);
                    }else if(data.error == 2){
                        layer.open({
                            content: '{pigcms{:L_("当前商户余额不足请充值？")}',
                            btn: ['{pigcms{:L_("去充值', '取消")}'],
                            shadeClose: false,
                            yes: function(){
                                parent.location.href = data.url;
                            }
                        });
                        $("#J-order-pay-button").val("{pigcms{:L_("去付款")}");
                        $('#J-order-pay-button').removeAttr("disabled");
                    }else{
                        $("#J-order-pay-button").val("{pigcms{:L_("去付款")}");
                        $('#J-order-pay-button').removeAttr("disabled");
                    }
                },'json')

            }, function(){
                $("#J-order-pay-button").val("{pigcms{:L_("去付款")}");
                $('#J-order-pay-button').removeAttr("disabled");
            });


        }
        return false;
	});

});

</script>
</body>
</html>
