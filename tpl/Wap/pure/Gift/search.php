 <!DOCTYPE html>
 <html>
 <head>
     <meta charset="utf-8"/>
 	<title>搜索</title>
     <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
 	<meta name="apple-mobile-web-app-capable" content="yes">
 	<meta name='apple-touch-fullscreen' content='yes'>
 	<meta name="apple-mobile-web-app-status-bar-style" content="black">
 	<meta name="format-detection" content="telephone=no">
 	<meta name="format-detection" content="address=no">
	<include file="Public:gift_header" />
	<style> 
		.flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; align-items: center;  } 
		.search{padding: 5px 10px; background: #fff;	}
		.searcht{  position: fixed; top: 0px; left: 0px; width: 100%; padding: 0px; z-index: 99 }
		.searcht_n{ padding: 8px 10px; position: relative; width: 100%; padding-right: 40px; box-sizing: border-box;  }
		.searcht .se_input{ background: #f4f4f4; border-radius: 20px; border:1px solid #ddd;   background-size: 15px;padding:0 10px; height: 30px;  font-size: 14px; width: 90%; box-sizing: border-box;  }
		.searcht a{ color: #fff; background: #f23030; text-decoration: none; display: inline-block; padding:0 8px; font-size: 14px;  border-radius: 5px; line-height: 30px; }
		#search{
			position: absolute;
			top:8px;
			right:10px;
			z-index: 99;
		} 
		input{outline:medium;}
		.content-padded{
			margin: 50px auto;
			background: #fff;
		}
	</style> 
	<script type="text/javascript" language="javascript">
		var ajax_gift_list_url = "{pigcms{:U('ajax_gift_list')}";
		var gift_list_url = "{pigcms{:U('gift_list')}";
		var gift_detail_url = "{pigcms{:U('gift_detail')}"
		var  score_name = "{pigcms{$config.score_name}";
		var currency_symbol = "{pigcms{$config.currency_symbol|default='元'}";
	</script>
 </head>
 <body>
	 <section class="search searcht">
	 	<div class="searcht_n flex">
	 		<input type="text" placeholder="请输入商品或店铺名称" class="se_input flex_one" value="" /> 
	 		<a href="javascript:void(0)" id="search" >搜索</a>
	 	</div>
	 </section>  
	 <div class="content-padded">
	     <div class="row no-gutter" id="gift_list">
	     </div>
	 </div>
 </body>
 <script src="{pigcms{:C('JQUERY_FILE')}"></script>
 <script>  
	 $('#search').click(function(){ 
		 var keyword = $('.se_input').val(); 
		 if(keyword.length>0){
			$.post(ajax_gift_list_url,{'keyword':keyword},function(data){
			if(data['status']){
				   var gift_list = data['gift_list']['list'];
				   var shtml = '';
				   for(var i in gift_list){
					   var gift_url = gift_detail_url+'&gift_id='+gift_list[i]['gift_id']
					   shtml += '<div class="col-50 item wrapImgItem" onclick="location.href=\''+gift_url+'\'"><div class="wrap wrapImg"><div class="i-pic wrapImgPic"><img src="' + gift_list[i]['wap_pic_list'][0] + '"></div><h2>'+gift_list[i]['gift_name']+'</h2>';
					   
					   if((gift_list[i]['exchange_type'] == 0) || (gift_list[i]['exchange_type'] == 2)){
						   shtml += '<p>'+ gift_list[i]['payment_pure_integral'] +' <em>'+score_name+'</em></p>';
					   }else{
						   shtml +='<p>'+ gift_list[i]['payment_integral'] +' <em>'+score_name+'</em> + '+ gift_list[i]['payment_money'] + currency_symbol +'</p>';
					   }
					 // <a href="javascript:void(0)" class="aButton">马上兑换</a
					   shtml +='</div></div>';
				   }
				   
				   // var thisUrl = gift_list_url + "&cat_id="+cat_fid;
				   // shtml +='<div class="showMore"><a href="'+thisUrl+'">查看更多</a></div>';
				   
				   $('#gift_list').empty().html(shtml);
			   }else{
				   var shtml='<p style="text-align:center; padding:60px 0">暂无礼品</p>'
				   $('#gift_list').empty().html(shtml);
			   }
			},'json')  
		 }
		
	 })
	
 </script>
 </html>