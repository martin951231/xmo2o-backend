<include file="Public:header"/>

<div class="main-content">
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-credit-card"></i>
                <a href="{pigcms{:U('withdraw')}">{pigcms{:L_("提现")}</a>
            </li>
        </ul>
    </div>
	<div class="page-content form-horizontal ">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
					<form enctype="multipart/form-data" class="form-horizontal" method="post" action="" onsubmit="return checkSubmit()" id="">
						<div class="tab-content card_new">
							<div class="headings gengduoxian">{pigcms{:L_("您的余额为：")}{pigcms{$config['Currency_symbol']}{pigcms{$now_merchant['money']} <if condition="$config['company_pay_mer_percent'] gt 0 AND $config['merchant_withdraw_fee_type'] eq 0">{pigcms{:L_("提现手续费比例：")}{pigcms{$config['company_pay_mer_percent']}<elseif condition="$config['company_pay_mer_money'] gt 0 AND $config['merchant_withdraw_fee_type'] eq 1" />{pigcms{:L_("提现手续费金额：")}{pigcms{$config['company_pay_mer_money']}{pigcms{$config['Currency_txt']}<else />{pigcms{:L_("无手续费")}</if></div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("真实姓名：")}</label></label>
								<input type="text" class="px" name="name" id="name" value="" />
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("金额：")}</label></label>
								<input type="text" class="px" name="money" id="money" value="" />{pigcms{$config['Currency_txt']}&nbsp;<label id="percent">({pigcms{:L_("最低提现额：")}<if condition="$config['company_least_money'] gt $config['min_withdraw_money'] ">{pigcms{$config['company_least_money']}<else />{pigcms{$config['min_withdraw_money']}</if>{pigcms{$config['Currency_txt']})</label>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("提款至：")}</label></label>
								<select name="withdraw_type" id="sys_card_bg" class="pt" style="width:160px;">
									<if condition="$config.company_bank_pay eq 1"><option value="0">{pigcms{:L_("银行卡")}<if condition="$config['open_real_time_withdrawal']">（{pigcms{:L_("实时到账")}）</if></option></if>
									<if condition="$config.company_alipay_pay eq 1"><option value="1">{pigcms{:L_("支付宝")}<if condition="$config['open_real_time_withdrawal']">（{pigcms{:L_("，最快次日")}）</if></option></if>
									<if condition="$config['company_weixin_pay'] eq 1 AND ($config['city_ownpay'] eq 0 OR ($config['city_ownpay'] eq 1 AND !$can_agree ))"><option value="2">{pigcms{:L_("微信")}<if condition="$config['open_real_time_withdrawal']">（{pigcms{:L_("实时到账")}）</if></option></if> 
									<if condition="$config['open_mer_monye_to_sys_money'] eq 1 AND $user"><option value="3">{pigcms{:L_("提现到平台余额")}</option></if>
								</select>
								<span class="tip"></span>
							</div>
							<div class="form-group paytype" id="alipay">
								<label class="tiplabel"><label>{pigcms{:L_("支付宝账号：")}</label></label>
								<input type="text" class="px" name="alipay_account" value=""  placeholder="{pigcms{:L_("请填写支付宝账号")}"/>
							</div>
							<div class="form-group paytype" id="bank">
								<label class="tiplabel"><label>{pigcms{:L_("银行卡：")}</label></label>
								<select name="bank_id" id="bank_list" class="pt" style="width:350px" >
									<volist name="bank_list" id="vo">
										<option value="{pigcms{$vo.id}" data-name="{pigcms{$vo.account_name}">【{pigcms{$vo.remark}】<php> echo  '**** **** **** '.substr($vo['account'],-4);if($vo['is_default']){ echo '{pigcms{:L_("【默认】")}';}</php></option>	
									</volist>
								</select>
								<a href="{pigcms{:U('create_bank_account')}" >+{pigcms{:L_("新建")}</a>
								<!--input type="text" class="px" name="card_username" value="" placeholder="{pigcms{:L_("请填写持卡人姓名")}"  />
								<input type="text" class="px" name="card_number" value="" placeholder="{pigcms{:L_("请填写卡号")}"/>
								<input type="text" class="px" name="bank" value="" placeholder="{pigcms{:L_("请填写所属银行")}" /-->
							</div>
							<div class="form-group">
								<label class="tiplabel" style="vertical-align:top;"><label>{pigcms{:L_("备注：")}</label></label>
								<textarea name="info" id="info" class="px" style="width:410px;height:120px;"></textarea>
							</div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										{pigcms{:L_("保存")}
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<style>
	.paytype{
		display:none;
	}
	#bank input{
		margin-right:5px;
	}
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
$(document).ready(function() {
		var paytype = $('#sys_card_bg').val();
		withdraw_type(paytype)
	$('#sys_card_bg').change(function(event) {
		var paytype = $(this).val();
		withdraw_type(paytype)
	});
	
	$('#bank_list').change(function(){
			var bank_id = $(this).val()
			$("#bank_list option").each(function(index,val){
				if($(val).attr('value')==bank_id){
					$('input[name="name"]').val($(val).data('name'));
				}
			})
		})
	
	$('input[name="card_number"]').change(function(){
		$.ajax({
			url: '{pigcms{:U('get_bank_name')}',
			type: 'POST',
			dataType: 'json',
			data: {card_number: $('input[name="card_number"]').val()},
			success:function(data){
				if(data.status){
					$('input[name="bank"]').val(data.info)
				}else{
					alert('{pigcms{:L_("没有查询到相关银行，请手动输入")}')
				}
			}
		});
	});
});
var flag = true;
function checkSubmit(){
	if(!flag){
		return false;
	}
	flag = false
	// 提现到微信，验证有没有绑定微信
	if($('select[name=withdraw_type]').val() == 2){
		var uid = {pigcms{$now_merchant['uid']|intval};
		if(!is_bind()){
			var url = "{pigcms{:U('scan_bind_weixin')}"
			// 弹层扫码绑定微信
			art.dialog.open(url,{
				init: function(){
				},
				id: 'handle',
				title:'{pigcms{:L_("绑定管理账号的二维码")}',
				padding: 0,
				width: 430,
				height: 433,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: function(){},
				left: '50%',
				top: '38.2%',
				opacity:'0.4'
			});
        	flag = true
			return false;
		}
	}

    var money = $('#money').val()
    var company_least_money = "{pigcms{$config['company_least_money']|floatval}"
    var min_withdraw_money = "{pigcms{$config['min_withdraw_money']|floatval}"
    if(parseFloat(money) < min_withdraw_money){ 
        layer.alert(L_('不能低于最小商家提现金额')+min_withdraw_money)
        flag = true
        return false;
    }
    if(parseFloat(money) < company_least_money){
        layer.alert(L_('不能低于对账最小提款金额')+company_least_money)
        flag = true
        return false;
    }

    if($('#name').val() == ''){
        layer.alert(L_('真实姓名不能为空'))
        flag = true
        return false;
    }
    return true;
}

function is_bind()
{
	var flag = false;
	$.ajax({
		type: "GET",
		url: "{pigcms{:U('Config/has_bind')}",
		data: {},
		dataType: "json",
		async:false,
		success: function(response){
			if (!response.error_code) {
	        	//已绑定
	        	flag = true;
	        }else{
	        	// 未绑定
	        	flag = false;
	        }
		}
	})
	return flag;
}
function withdraw_type(paytype){
		if(paytype==2){
			$('.paytype').hide();
		}else if (paytype==1){
			$('#alipay').show();
			$('#bank').hide();

		}else if(paytype==0){
			$('#alipay').hide();
			$('#bank').show();
		}else if(paytype==3){
			$('#alipay').hide();
			$('#bank').hide();
		}
}
</script>
<link rel="stylesheet" href="{pigcms{$static_path}css/card_new.css"/>
<include file="Public:footer"/>