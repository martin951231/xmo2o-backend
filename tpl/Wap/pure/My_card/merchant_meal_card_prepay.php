<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
	<meta http-equiv="pragma" content="no-cache"/>
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta name='apple-touch-fullscreen' content='yes'/>
	<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
	<meta name="format-detection" content="telephone=no"/>
	<meta name="format-detection" content="address=no"/> 
	<title>充值饭票</title> 
	<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}css/weui.min.css"/>
	<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}css/idangerous.swiper.css"/> 
	<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}my_card/css/card_new.css"/>
	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>  
	<script src="{pigcms{$static_path}js/weui.min.js"></script>
	<script src="{pigcms{$static_path}js/idangerous.swiper.min.js"></script> 
	<!--[if lte IE 9]>
	<script src="scripts/html5shiv.min.js"></script>
	<![endif]--> 
	<style>
		.swiper-pagination{
			bottom: 5px;
		}
	</style>
</head>
<body style="background: #fff; zoom: 0;">
	 <div class="top_header">
		 <span class="fp_til">充值饭票</span>
		 <a href="{pigcms{:U('meal_card_list',array('mer_id'=>$now_merchant['mer_id']))}" class="icon_money"></a>
	 </div>
	 
	 <div class="swiper-container swiper-containerFp " style=" height: 198px; cursor: -webkit-grab;">
		<div class="swiper-wrapper" >
			<volist name="adver" id="ao">
		  <div class="swiper-slide slideImg" style="background-image: url( {pigcms{$ao.pic}); background-size: cover;"> </div>
			</volist>
		</div>
		<div class="swiper-pagination"></div> 
	 </div> 	
	 <div class="measlCon">
		 <div class="til_notice">您要对指定商家<label class="ffbb3c">{pigcms{$now_merchant.name}</label>进行饭票充值</div>
		 <div>
			 <span class="fl color888888">饭票余额：{pigcms{$card_info['card_money']+$card_info['card_money_give']}元</span>
			 <span class="fr ffbb3c" id="prepay_btn" >平台饭票充值</span>
			 <div class="clear"></div>
		 </div>
		 
		 <div class="moneyInput">
			 <input type="text" id="moneyInput" placeholder="请输入充值金额" />
		 </div>
		 <ul class="list_selected">
			 <volist name="meal_card.recharge_money_arr" id="vo">
			 <li data-index="{pigcms{$vo}">
				<div class="selectedBlock">
					 <label class="ffbb3c" data-vo="{pigcms{$vo}">{pigcms{$vo}元</label>
					 <p> 获{pigcms{$vo}米粒</p>
				 </div>
			 </li>
			 </volist>
			 <div class="clear"></div> 
		 </ul>
		 <div class="changData" >
			 <span class="color888888">金额：<label class="color5555 dataPrice"></label>元</span>,
			 <span class="color888888">获得米粒：<label class="color5555 dataPrice"></label>米粒</span>,
			 <span class="color888888">饭票到账：<label class="color5555 dataPrice"></label>元</span>
		 </div>
		 
		 <div class="selectAgree">
			 <div class="icon_selectAgree"></div>
			 <div>我已阅读并同意<a href="javascript:;" class="click_xieyi ffbb3c">《商家饭票充值协议》</a></div>
			 <p class="color888888" style="padding-top: 10px;">温馨提示：商家饭票充值后仅在充值商家消费使用，余额在会员卡 保留但不可提现；</p> 
		 </div>
		 
		 <button class="btn_chongzhi" id="submit">
			立即充值 
		 </button> 
	 </div>
				
      
</body>
 
<script>
	var agreement = "{pigcms{$agreement}";//充值协议
	var meal_card_id = "{pigcms{$meal_card.id}";//饭票模板id
	var mySwiper1 = $('.swiper-containerFp').swiper({
		pagination:'.swiper-pagination',
		loop:true,
		grabCursor: true,
		paginationClickable: true,
		simulateTouch:false,
		// slidesPerView : 3,
		// slidesPerGroup : 3
	});
	 
	$('.list_selected li:nth-child(1)').addClass('cur')
	$('.dataPrice').text($('.list_selected li:nth-child(1) .selectedBlock label').data('vo'))
	$('#moneyInput').bind('input propertychange', function() {
		$('.changData').show()
		$(".list_selected li").each(function(){
		   if($(this).hasClass('cur')){ 
			   $('.list_selected li').removeClass('cur') 
			 }
		 });
		 $('.dataPrice').text($('#moneyInput').val())
	}); 
	$('.list_selected li').click(function(){  
		$('.changData').show()
		 $('#moneyInput').val('') 
		if($(this).hasClass('cur')){
			$(this).removeClass('cur');
			$('.changData').hide()
		}else{
			 $('.dataPrice').text($(this).data('index'))
			$(this).addClass('cur').siblings().removeClass('cur');
		}
		
	})

	$('.selectAgree .icon_selectAgree').click(function(){
	   $(this).toggleClass('cur')
	})

	$('#submit').click(function(){
		if(!$('.selectAgree .icon_selectAgree').hasClass('cur')){
			weui.toast("你没有同意协议", "text");
			return false
		}
		console.log($('#moneyInput').val())
		var selectedIs = false
		$(".list_selected li").each(function(){
		   if($(this).hasClass('cur')){
			   selectedIs = true 
			 }
		 });
		var nowMoney = $('.list_selected li.cur').data('index');
		if($('#moneyInput').val()){
			nowMoney = $('#moneyInput').val();
		}
		if($('#moneyInput').val() == '' && !selectedIs){
			weui.toast("请输入充值金额或者选中充值金额", "text");
			return false
		}
	var store_id = "{pigcms{$_GET['store_id']}";
	window.location.href = "{pigcms{:U('merchant_recharge',array('mer_id'=>$now_merchant['mer_id']))}&money="+nowMoney+"&meal_card_id="+meal_card_id+"&store_id="+store_id;
})
	$('.click_xieyi').click(function(){
		weui.confirm(agreement, {
			title: '充值协议', 
			buttons: [{
				label: '知道了！',
				type: 'default',
				onClick: function(){

				}
			}]
		})
	});
	
	$('#prepay_btn').click(function(){
		weui.confirm('通用饭票正处于待开发状态,请前往商家饭票进行充值!',{
			title: '通用饭票', 
			buttons: [{
				label: '确定',
				type: 'default',
				onClick: function(){
	
				}
			}]
		}) 
		   
	});
 
	
</script>


</html>