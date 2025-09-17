<!DOCTYPE html>
<html lang="en">
<head>
    <title>分类信息订单</title>
<include file="Public:gift_header" />
	<link rel="stylesheet" href="{pigcms{$static_path}classifynew/css/weui.css"/>
</head>
<style>
.ormx_pro { padding: .1rem 0; border-bottom: 1px solid #ddd; -webkit-box-sizing: border-box; box-sizing: border-box; display: -webkit-box; display: -webkit-flex; display: flex; background:#fff;padding:.5rem .2rem .2rem .5rem; }
.ormx_pro img { display: block; padding-right: .1rem; width:80px; height:80px}
.ormxpm_price { left: 0; bottom: 0; font-size: 22px; color: #ff472e; font-weight: 700 }
.weui-cells_radio .weui-check:checked + .weui-icon-checked:before {
	color: #ff6634;
}
	.weui-cells_radio{cursor:pointer}
.weui-cells_radio .weui-check + .weui-icon-checked:before {
	color: #bbb;

}
.border-t:before {
	content: " ";
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	height: 1px;
	border-top: 1px solid #e5e5e5;
	color: #e5e5e5;
	-webkit-transform-origin: 0 0;
	transform-origin: 0 0;
	-webkit-transform: scaleY(0.5);
	transform: scaleY(0.5);
	z-index: 2;
}
.layermbtn span[type="1"] {
    color: #ff4d00;
}
.layermbtn span:first-child {
    background-color: transparent;
}
</style>
<body>

<form action="{pigcms{:U('buy')}" method="post" id="form"  enctype="multipart/form-data">
<section class="order" style="margin-top:0px">
    <section class="list-block">
        <ul>
			<li>
                <a href="{pigcms{:U('My/adress',array('classify_userinput_id'=>$_GET['classify_userinput_id'],'current_id'=>$now_user_adress['adress_id']))}" class="item-link item-content" class="item-link item-content">
                    <div class="item-inner">
                        <div class="item-title">更换送货地址</div>
                    </div>
                </a>
            </li>
        </ul>
    </section>

	<if condition="!empty($now_user_adress)">
		<section class="address" <if condition='!empty($_GET["address_id"])'>style="display:none"<else />style="display:block"</if>>
			<ul>
				<li>
					<div class="wrap">
						<div class="textDesc">
							<div class="rowCell">
								<span class="fr">{pigcms{$now_user_adress['phone']}</span>
								<span>收货人：{pigcms{$now_user_adress['name']}</span>
							</div>
							<div class="rowCell">
								收货地址：{pigcms{$now_user_adress['province_txt']} {pigcms{$now_user_adress['city_txt']} {pigcms{$now_user_adress['area_txt']} {pigcms{$now_user_adress['adress']} {pigcms{$now_user_adress['detail']}
							</div>
						</div>
					</div>
				</li>
			</ul>
		</section>
		
		<section class="address" <if condition='empty($_GET["address_id"])'>style="display:none"<else />style="display:block"</if>>
			<ul>
				<li>
					<div class="wrap">
						<div class="textDesc">
							<div class="rowCell">
								<span class="fr">{pigcms{$now_user_adress['phone']}</span>
								<span>收货人：{pigcms{$now_user_adress['name']}</span>
							</div>
							<div class="rowCell">
								收货地址：{pigcms{$now_user_adress['province_txt']} {pigcms{$now_user_adress['city_txt']} {pigcms{$now_user_adress['area_txt']} {pigcms{$now_user_adress['adress']} {pigcms{$now_user_adress['detail']}
							</div>
						</div>
					</div>
				</li>
			</ul>
		</section>
	</if>
	
    <section class="orderDetails">
        <div class="ormx_pro" style="border-bottom: unset;">
            <if condition='$classify_userinput_detail["imgs"]'><img src="{pigcms{$classify_userinput_detail["imgs"][0]}"></if>
            <div class="ormxp_mess">
                <p class="ormxpm_tit">{pigcms{:msubstr($classify_userinput_detail['description'] , 0 , 30)}</p>
                <p class="ormxpm_price"><i>{pigcms{$config['Currency_symbol']}</i>{pigcms{$classify_userinput_detail["assure_money"]}
                </p>
            </div>
        </div>
        <div class="orderRow">
			<div class="weui-cell " >
				<div class="weui-cell__hd"><label class="weui-label">配送方式</label></div>
				<div class="weui-cell__bd weui-cells_radio" style="display:flex">
					<div class="weui-cell__bd" style="    text-align: right;">
						<label for="x11">
							<span>快递</span>
							<input type="radio" class="weui-check is_baoyou" name="is_pick_in_store" id="x11" value="0" checked="checked">
							<span class="weui-icon-checked"></span>
						</label>
					</div>
					<div class="weui-cell__bd" style="    text-align: right;">
						<label for="x12">
							<span>到店自取</span>
							<input type="radio" class="weui-check is_baoyou" name="is_pick_in_store" id="x12"  value="1">
							<span class="weui-icon-checked"></span>
						</label>
					</div>
				</div>
			</div>
			<div class="no_bao border-t" style="">
				<div class="weui-cell   " >
					<div class="weui-cell__hd"><label class="weui-label">运费</label></div>
					<div class="weui-cell__bd">
						<if condition="$classify_userinput_detail['is_post_free']==0  ">
							<span style="color: #ff472e; float: right;">{pigcms{$config['Currency_symbol']}{pigcms{$classify_userinput_detail['postage']}</span>
						<else/>
						<span style="color: #ff472e; float: right;">包邮</span>
						</if>
					</div>
				</div>
			</div>

        </div>
    </section>
</section>

<section class="ftHeight"></section>
<footer class="deatilFtBtn">
    <div class="number tc fl">
        <span class="dib tit">合计：</span><div class="jf-bidNum dib">
			<p><font class="payment_money">{pigcms{$classify_userinput_detail["total_price"]}</font> <em>{pigcms{$config['Currency_txt']}</em></p>
        </div>
    </div>
    <div class="buyNow fl">
		<input type="hidden" name="classify_userinput_id" value="{pigcms{$_GET['classify_userinput_id']}" />
		<input type="hidden" name="is_source" value="1" />
		<input type="hidden" name="address_id" value="{pigcms{$_GET['adress_id'] ? $_GET['adress_id'] : $now_user_adress['adress_id']}" />
		<input type="hidden" name="num" value="1" />
		<button id="pay" type="submit">立即付款</button>
    </div>
</footer>
</form>

<include file="Public:gift_footer" />
<script type="text/javascript">
var assure_money = {pigcms{$classify_userinput_detail["assure_money"]|floatval};
var postage = {pigcms{$classify_userinput_detail["postage"]|floatval};
var is_post_free = {pigcms{$classify_userinput_detail["is_post_free"]|intval};

 $("input[name=is_pick_in_store]").change(function(){
	 if($(this).val()==1){
		 $(".no_bao").hide();
		 $(".payment_money").html(assure_money.toFixed(2));

	 }else{
		$(".no_bao").show();
		if (is_post_free==0) {
			$(".payment_money").html((assure_money+postage).toFixed(2))
		}else{
			$(".payment_money").html(assure_money)

		}
	 }
 })

$(document).on('submit', '#form', function () {
    var that = $(this);
    layer.open({
      	content: '是否确认支付'
      	,btn: ['确认','取消']
      	,yes: function(index, layero){
        	$.ajax({
				type: 'post',
				url: "{pigcms{:U('buy')}",
				data: that.serialize(),
				dataType:'json',
				success: function (data) {
				    $('#pay').removeAttr('disabled');
					if(data.status != 1){
				        if (data.url && data.status == 3) {
				            layer.open({
				              	content: data.msg
				              	,btn: ['去充值','取消']
				              	,yes: function(index, layero){
				                	window.location.href = data.url;
				              	}
				            });
				        }else{
				        	layer.open({
				              	content: data.msg 
				              	,btn: ['确定','取消']
				              	,yes: function(index, layero){
				              		if (data.url) {
				          				window.location.href = data.url;
				              		}else{
				              			window.location.reload();
				              		}
				              	}
				            });
				        }
					}else{ // 支付成功
						layer.open({
				          	content: data.msg 
				          	,btn: ['确定']
				          	,yes: function(index, layero){
				          		window.location.href = data.url+'&backurl='+data.backurl;
				          	}
				        });
					}
				},
				error: function () {
				    $.hideLoading();
				    $('#pay').removeAttr('disabled');
				}
			});
      	}
    });
    return false;
});
</script>
</body>

</html>