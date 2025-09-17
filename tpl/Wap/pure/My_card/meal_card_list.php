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
	<title>饭票充值账单</title> 
	<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}css/weui.min.css"/>
	<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}css/idangerous.swiper.css"/> 
	<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}my_card/css/card_new.css"/>
	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>   
	<script src="{pigcms{$static_path}js/idangerous.swiper.min.js"></script> 
	<!--[if lte IE 9]>
	<script src="scripts/html5shiv.min.js"></script>
	<![endif]-->  
</head>
<body style="background: #fff; zoom: 0;">
	 <div class="top_header">
		 <div class="go_backFF" onClick="javascript:history.back()"></div> 
		 <span class="fp_til">饭票充值账单</span> 
	 </div>
	 
	 <ul class="select_box">
		 <li class="select_order" data-merId = ''><span> </span></li>
		 <li class="select_time"><span>  </span></li>
		 <div class="clear"></div>
	 </ul>
	 <div class="leiji_til">
		 <span>累计充值: <label class="total_money"></label>元</span>
		 <span>获得米粒: <label class="total_moneyNum"></label>个</span> 
	 </div>
	 <ul class="ul_zhangList">
		
	 </ul>
	 
	 <p class="noData">加载完毕！</p>
	  
</body> 
<script>
var ajax_url = "{pigcms{:U('get_meal_card_record')}"; 
var merchant_select_url = "{pigcms{:U('merchant_select')}";
var year=new Date().getFullYear(); 
var month=new Date().getMonth()+1;
var day=new Date().getDate();
var merchant_list_data = null
var select_order_list = []
var select_merId = null 
var flagNoData = false; //false 
console.log(month,day)
var page = 1 
var dateTime = year +'-'+ month 
 
$('.select_time span').html(year +'年'+ month +'月');
init( )
function init(){   
	$.post(merchant_select_url,'', function(data) { 
		var select_order_html = ''   
		if(data){ 
			data.forEach(function(value,index){
				console.log(value.name)
				if(index == 0){
					select_order = value.name
					select_merId = value.mer_id
					 
				}   
			   select_order_list.push({
				   label: value.name,
				   value: value.mer_id
			   })
			})
		} 
		$('.select_order span').html(select_order);
		$('.select_order').attr('data-merId',select_merId)
		funfungetList(1)
		 
	});
}

function funfungetList(page){ 
	var param = {
		date:dateTime,
		mer_id:select_merId,
		page:page,
		page_size:10	
	} 	
    // $('.noData').hide()
	var contentHtml= '' 
	$.post(ajax_url, param, function(data) { 
		console.log(data)   
		if(page >= data.total_page){ //当前页码大于等于总页码
			flagNoData = true;  
		  };
		
		console.log(page,'page')
		var recharge_list = data.recharge_list
		if(recharge_list.length>0){
			for(let i=0;i<recharge_list.length;i++){
			 contentHtml+='<li><a href='+recharge_list[i].info_url+'>' 
			 	contentHtml+='<div class="zhangList1">'
			 		contentHtml+='<span class="fl">'+recharge_list[i].meal_name+'</span>'
			 		contentHtml+='<span class="fr">+'+recharge_list[i].pay_money+'</span>'
			 		contentHtml+='<div class="clear"></div>'
			 	contentHtml+='</div>'
			 	contentHtml+='<div class="zhangList2">'
			 		contentHtml+='<span class="fl">'+recharge_list[i].pay_time_str+'</span>'
			 		contentHtml+='<span class="fr">+'+ parseFloat(recharge_list[i].pay_money)+'米粒</span>'
			 		contentHtml+='<div class="clear"></div>'
			 	contentHtml+='</div></a>' 
			 contentHtml+='</li> '	
			}
		}else{
			$('.noData').html('没有数据')
		}
		
		 
		$('.total_money').html(data.total_money)
		$('.total_moneyNum').html(parseFloat(data.total_money))
		if(page!=1){
			$('.ul_zhangList').append(contentHtml)
		}else{
			$('.ul_zhangList').html(contentHtml)
		}
		
	}) 
}
$(window).scroll(function (){ 
	var pageHeight = Math.max(document.body.scrollHeight,document.body.offsetHeight);
	//视窗的高度
	var viewportHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight || 0;
	//隐藏的高度
	var scrollHeight = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
	    if(flagNoData){ //数据全部加载完了  
		  return; 
	   }else if(pageHeight - viewportHeight - scrollHeight < 10){    //如果满足触发条件，执行 
		page = page + 1
		funfungetList(page);
	}
})
 	
$(function(){  
	$('.select_order').on('click', function () { 
		flagNoData = false;
		weui.picker(select_order_list, {
			onChange: function (result) {
				console.log(result); 
			},
			onConfirm: function (result) { 
				$('.select_order span').html(result[0].label);
				$('.select_order').attr('data-merId',result[0].mer_id)
				select_merId = result[0].value
				 funfungetList(1)
			}
		});
	});
	$('.select_time').on('click', function () { 
		var defaultValueTime = []
		flagNoData = false;
		defaultValueTime = dateTime.split("-")
		weui.datePicker({  
		    start: new Date().getFullYear()-50,
		    end: new Date().getFullYear(),  
			depth:2,
			defaultValue: [defaultValueTime[0],defaultValueTime[1]],  
			onConfirm: function(result){  
				console.log(result[0].label)   
		       $('.select_time span').html(result[0].label+result[1].label)
			   dateTime = result[0].label.substring(0, result[0].label.length - 1) +'-' +result[1].label.substring(0, result[1].label.length - 1);  
			   funfungetList(1)
			},
			onChange:function(result){   
			  
			},
			id: 'datePicker',
			className: 'expire_timer'
		}); 
	})
	
}) 
</script> 
<script src="{pigcms{$static_path}js/weui.min.js"></script>
</html>