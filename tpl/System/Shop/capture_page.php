<include file="Public:header"/>
<style type="text/css">
.box{
	text-align: center;
	height:300px;
	margin:150px auto;
}
.box .tip{
	font-size:24px;
	margin-bottom: 100px;
}
.box button{
	border:none;
	color:#fff;
	display:inline-block;
	background-color: #0097D1;
	padding:10px 0;
	width: 80px;
	margin:0 auto;
	cursor: pointer;
}
.box button.stop{
	background-color: red;
}
.box button.close{
	background-color: #12AB9C;
	display:none;
}
</style>
<div class="box">
	<p class="tip">抓取中。。。</p>
	<p>
		<button class="switch_btn stop" data-status='0'>停止抓取</button>
		<button class="close">关闭</button>	
	</p>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
$(function(){
	$('.close').click(function(){
		if(window.location.host=="o2otest.pigcms.com"){
			top.main.location.reload();
		}else{
			top.leftmenu_Shop_sys_goods.location.reload();
		}		
		top.art.dialog({id:'capture_store_goods'}).close();
	});
})	
</script>
<script type="text/javascript">
var stime="{pigcms{$stime}";
var ftime="{pigcms{$ftime}";
var store_str="{pigcms{$store_str}";
var store_arr=store_str.split(',');
var max=store_arr.length;
var x=0;
var store_num=0;
var goods_num=0;
var url="{pigcms{:U('Shop/capture_store_goods_api')}";
var currentAjax=[];
capture_goods(store_arr[x]);
function capture_goods(store_id){
	currentAjax[x]=$.ajax({
		type:'post',
		url:url,
		data:{
			stime:stime,
			ftime:ftime,
			store_id:store_id
		},
		async: true,//使用同步的方式,true为异步方式
		timeout:3000,
		success:function(res){
			if(res.status){
				store_num++;
				goods_num+=res.count;
				var text='';
				if(max-1==x){
					text='抓取完成，总共成功抓取'+store_num+'个店铺，'+goods_num+'个商品。';
					$('.box').find('button.switch_btn').remove();
					$('.box').find('button.close').show();				
				}else{
					text="正在抓取中，已为您抓取"+store_num+"个店铺,"+goods_num+"个商品。。。";
				}
				console.log(x,store_id,text);
				$('.box').find('.tip').text(text);
			}
		},
		complete:function(xhr,textStatus){
			if($('.switch_btn').data('status')){
				return false;
			}
			x=x+1;
			// 抓取下一个店铺的商品
			if(x<max){
				store_id=store_arr[x];
				console.log(x,store_id);
				capture_goods(store_id);
			}else if(textStatus!='success'){
				$('.box').find('button.switch_btn').remove();
				$('.box').find('button.close').show();
				$('.box').find('.tip').text('抓取完成！');
			}
		}
	});
}
/*抓取开关*/
var clock=0;
$(document).on('click','.switch_btn',function(){
	if(clock==1)return false;
	clock=1;
	var status=$(this).data('status');
	var new_status=Math.abs(status-1);
	$(this).data('status',new_status);

	var btn_tip='';
	if(status==0){
		if(currentAjax[x]){
			currentAjax[x].abort();
			currentAjax[x]='';
		}
		btn_tip='继续抓取';
		$('.box').find('button.close').show();
		$(this).removeClass('stop');
	}else if(status==1){		
		if(x<max){
			$('.box').find('button.close').hide();
			store_id=store_arr[x];
			console.log('重新开始抓取',x,store_id);
			capture_goods(store_id);
		}else{
			$('.box').find('button.switch_btn').remove();
			$('.box').find('button.close').show();
			$('.box').find('.tip').text('抓取完成！');
		}
		$(this).addClass('stop');
		btn_tip='停止抓取';
	}	
	$(this).text(btn_tip).data('status',new_status);
	clock=0;
});
</script>
