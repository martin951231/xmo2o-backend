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
					<form enctype="multipart/form-data" class="form-horizontal" method="post" action="">
						<div class="tab-content card_new">
							
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("持卡人姓名")}</label></label>
								<input type="text" class="px" name="account_name" value="{pigcms{$bank.account_name}" placeholder="{pigcms{:L_("请填写持卡人姓名")}"  />
								
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("银行卡号")}</label></label>
								<input type="text" class="px" name="account" value="{pigcms{$bank.account}" placeholder="{pigcms{:L_('请填写卡号')}"/>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("所属银行")}</label></label>
								<input type="text" class="px" style="width:300px" name="remark" value="{pigcms{$bank.remark}" placeholder="{pigcms{:L_("请填写所属银行")}" />
								<span class="tip"></span>
							</div>
							<div class="form-group">
								<label class="col-sm-1 tiplabel" style="margin-left:8px;width: 145px"><label>{pigcms{:L_("开户城市")}</label></label>
								<fieldset id="choose_cityarea" level="2" province_id="{pigcms{$bank.province_id}" city_id="{pigcms{$bank.city_id}"></fieldset>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("开户网点")}</label></label>
								<input type="text" class="px" style="width:300px" name="branch" value="{pigcms{$bank.branch}" placeholder="{pigcms{:L_("请填写所属银行")}" />
								<span class="tip"></span>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("是否默认")}</label></label>
									<input type="radio" class="px" name="is_default" value="1" <if condition="$bank.is_default eq 1">checked</if>/>{pigcms{:L_("是")}
									<input type="radio" class="px" name="is_default" value="0" <if condition="$bank.is_default eq 0">checked</if>/>{pigcms{:L_("否")}
								
							</div>
							<input type="hidden" name="bank_id" value="{pigcms{$bank.id}">
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
<script type="text/javascript">
var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}",choose_market="{pigcms{:U('Area/ajax_market')}",choose_cat_fid="{pigcms{:U('Merchant_category/ajax_cat_fid')}",choose_cat_id="{pigcms{:U('Merchant_category/ajax_cat_id')}";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
<script>
$(document).ready(function() {
		var paytype = $('#sys_card_bg').val();
		withdraw_type(paytype)
	$('#sys_card_bg').change(function(event) {
		var paytype = $(this).val();
		withdraw_type(paytype)
	});
	
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

function withdraw_type(paytype){
	if(paytype==2){
			$('.paytype').hide();
		}else if (paytype==1){
			$('#alipay').show();
			$('#bank').hide();

		}else if(paytype==0){
			$('#alipay').hide();
			$('#bank').show();
		}
}
	
</script>


<link rel="stylesheet" href="{pigcms{$static_path}css/card_new.css"/>
<include file="Public:footer"/>