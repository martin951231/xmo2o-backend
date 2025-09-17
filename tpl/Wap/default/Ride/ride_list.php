<include file="web_head"/>
<style type="text/css">
.ok_search {
	transform: rotateY(0deg);
	-webkit-transform: rotateY(0deg);
	-moz-transform: rotateY(0deg);
}
.pc_search {
	background: rgba(0,0,0,0.78);
	width: 100%;
	position: fixed;
	z-index: 1000;
	top: 0;
	/*transform: rotateY(90deg);
    -webkit-transform: rotateY(90deg);*/
	-moz-transform: rotateY(90deg);
	-webkit-transition: -webkit-transform 0.3s ease-out 0s;
	-moz-transition: -moz-transform 0.3s ease-out 0s;
	transition: transform 0.3s ease-out 0s;
}
.close {
	height: 2em;
	color: #fff;
	padding-right: 0.8em;
	cursor: pointer;
	width: 15%;
	float: right;
}
.close span {
	display: inline-block;
	font-size: 1em;
	width: 1.5em;
	height: 1.5em;
	background: #fff;
	color: #333;
	float: right;
	text-align: center;
	line-height: 1.5em;
	margin-top: 0.5em;
	border-radius: 0.8em;
}
.tj {
	width: 96%;
	margin-left: 2%;
	font-size: 0.89em;
	padding-top: 1em;
}
.tj dl {
	overflow: hidden;
	margin-left: 0.8em;
	border-bottom: 1px solid #444;
}
.tj dl dt {
	display: inline-block;
	float: left;
	margin: 0.5em 0.3em;
	margin-right: 0;
	padding: 0.1em;
	color: #999;
}
.tj dl dd.cattsel {
	background: #5bc0de;
}
.tj dl dd {
	display: inline-block;
	float: left;
	color: #fff;
	margin: 0.5em 0.3em;
	padding: 0.1em 0.3em;
	cursor: pointer;
}
.tj dl dd input[type='radio'] {
	display: none;
}
.mdd {
	font-size: 0.89em;
	padding: 0.5em 0.4em;
	margin: 0.4em 0.3em;
	width: 75%;
	background: #8f8f8f;
	border: 1px solid #333;
}
.btn-tj {
	font-size: 1em;
	text-align: center;
	width: 90%;
	display: block;
	margin: 0 auto;
	color: #fff;
	border-radius: 0.3em;
	background: #008CD6;
	padding: 0.6em;
	margin-top: 1em;
}
a:visited {
	text-decoration: none;
	color: #353535;
}
#scroller li.active{
	border-bottom: 3px solid #06c1ae
}
.clearfix li {
	cursor: pointer;
}
.add_more{
	position:relative;


	width:35px;
	height:35px;

	background:#fff;
	cursor:pointer;
}
.add_more:before{
	transform: rotate(-135deg);
	position: absolute;
	width: 14px;
	height: 14px;
	top: 6px;
	right: 10px;
	content: '';
	border: 2px solid #ddd;
	border-right: 2px solid transparent;
	border-bottom: 2px solid transparent;
}
.add_more_all:before{
	transform: rotate(45deg) !important;
	top: 14px !important;
}
.add_more_detail{
	background: red;
	top: 0px;
	right: 45px;
	position: absolute;
	left: 0px;
	z-index: 2;
}
.add_more_detail ul{
	list-style: none;
	width: 100%;
	padding: 0;
	margin: 0;
}
.add_more_detail li{float:left;width:30%;}
.no_tranns{
	transform: translate(0px, 0px) translateZ(0px) !important;
}
.hotKey {
	overflow: hidden;
	margin: 0;
	padding: 0;
	list-style-type: none;
	margin: 0px 0px 0px 2%;
}
.hotKey li {
	width: 32%;
	float: left;
	margin-right: 1%;
	background-color: white;
	margin-bottom: 1.6%;
}
.hotKey li a {
	border: 0.5px solid #e4e1e1;
	padding: 8px 3px;
	display: block;
	text-align: center;
	font-size: 12px;
	color: #7E7E7E;
	border-radius: 3px;
}
.bg_active {
	background-color: rgba(245,245,245,0.9);
}
.ul{
	list-style: none;
	width: 100%;
	padding: 0;
	margin: 0;
}
.ul li{
	height: 35px;
	line-height: 35px;
	margin: 0px 5px;
	cursor: pointer;
	float:left;
}
.flex_one{  flex:1;  -webkit-box-flex: 1;  -webkit-flex: 1;  }
.border-l:after {
	border-left: 1px solid #e9e9e9;
	content: '';
	display: block;
	top: 7px;
	bottom: 7px;
	position: absolute;
	left: 0;
	-webkit-transform-origin: left top;
	-webkit-transform: scaleX(0.5);
}
.border-r:after {
	border-right: 1px solid #e9e9e9;
	content: '';
	display: block;
	bottom: 7px;
	position: absolute;
	right: 0;
	top: 7px;
	-webkit-transform-origin: right top;
	-webkit-transform: scaleX(0.5);
}
.rel{position:relative;}
.ul li.active {
	border-bottom: 3px solid #06c1ae;
}
#scroller li {
	margin:0px;
	padding: 0px 5px;
}
</style>
<div class="app-header bg-gray2 padder w-full p-t8 b-b color-while">
<!--<div id="return_index" class="icon-uniE602 pull-left margin-top3 font-size22" style="padding: 10px 20px 5px 18px;margin-left:-15px;margin-top:-6px;"></div>-->
<div id="index" class="text-lg margin-top3 color-while">{pigcms{$index}</div>
<div id="screen" class="pull-right right_img right_bottom m-t-n-md">筛选</div>
<div class="clearfix"></div>
</div>

<div class="app-content with-header text-left text-md">
<div style='    display: flex;'>
	<div class='rel border-r ui_left'>
		<ul class='ul'>
			<li class=" active  por" data-id="" data-index="0"> 全部</li><li class="   por" data-id="recent" data-index="1">
			附近出发
		</li>
		</ul>
	</div>
	<div class="news-scroller bg-w flex_one" id="wrapper" style="line-height:35px;">

		<div id="scroller" class="bg-w scroller" style="width:10000px;z-index:20">
			<ul class="clearfix clearfix_list">

			</ul>
			<div style='width:100%;height:100%;z-index:60;position:absolute;top:0px;left:0px; margin: 0px 5px;display:none' class='look_all'>全部分类 </div>
			<div style="clear:both"></div>
		</div>

	</div>
	<div class='add_more border-l rel'></div>
</div>

<div style='display:none' id='ride_all'>
	<ul class="hotKey" data-down="1" style="display: block;"></ul>
</div>

<!--	搜索	-->
<aside id="search" class="pc_search ok_search" style="display:none;">
	<form action="" method="post" id="sxForm">
		<div id="close" class="close"><span>X</span></div>
		<div id="tjsx">
			<div class="w-p-60 color-while m-l m-t">筛选条件</div>
			<div class="tj">
				<dl id="zuowei" class="block2">
					<dt>座位：</dt>
					<dd class="cattsel">不限
						<input name="driver_seat" type="radio" value="1000000" checked="">
					</dd>
					<dd>1人
						<input name="driver_seat" type="radio" value="1">
					</dd>
					<dd>2人
						<input name="driver_seat" type="radio" value="2">
					</dd>
					<dd>3人
						<input name="driver_seat" type="radio" value="3">
					</dd>
					<dd>≥4人
						<input name="driver_seat" type="radio" value="4">
					</dd>
				</dl>
				<dl id="feiyong" class="block2">
					<dt>费用：</dt>
					<dd class="cattsel">不限
						<input name="driver_price" type="radio" value="1000000" checked="">
					</dd>
					<dd>0-20{pigcms{$config['Currency_txt']}
						<input name="driver_price" type="radio" value="20">
					</dd>
					<dd>20-40{pigcms{$config['Currency_txt']}
						<input name="driver_price" type="radio" value="40">
					</dd>
					<dd>40-60{pigcms{$config['Currency_txt']}
						<input name="driver_price" type="radio" value="60">
					</dd>
					<dd>≥60{pigcms{$config['Currency_txt']}
						<input name="driver_price" type="radio" value="1000">
					</dd>
				</dl>
				<if condition="$village_id neq 0">
					<dl id="chufa" class="block2">
						<dt>出发地：</dt>
						<dd class="cattsel">不限
							<input name="driver_start" type="radio" value="1" checked="">
						</dd>
						<dd>本小区出发
							<input name="driver_start" type="radio" value="2">
						</dd>
					</dl>
				</if>
				<dl class="block2">
					<dt>出发地：</dt>
					<input id="chufa_place" type="text" name="driver_departure_place" placeholder="请填写出发地" class="mdd">
				</dl>
				<dl class="block2">
					<dt>目的地：</dt>
					<input id="mudi" type="text" name="driver_destination" placeholder="请填写目的地" class="mdd">
				</dl>
			</div>
			<div id="submit" class="text-center padding-lr5"><div class="btn btn-info w-full">提交信息</div></div>
		</div>
	</form>
</aside>
<script>
	$(".pc_search").height($("#page").height())
	$(".right_img").click(function() {
		$(".pc_search").addClass("ok_search");
	})
	$(".close").click(function() {
		$(".pc_search").removeClass("ok_search");
	})
	$(".tj dl dd").click(function() {
		$(this).attr("class", "cattsel");
		$(this).siblings("dd").removeAttr("class");
		changeAtt(this)
	})
	$("#close").click(function() {
		$("#search").hide();
	})
	$("#screen").click(function() {
		var screenHeight	=	$(document).height();
		$("#search").css("height",screenHeight);
		$("#search").show();
	})
	$('#submit').click(function(){
		page	=	1;
		var remain_number	=	$("#zuowei dd.cattsel input").val();			//座位
		var ride_price		=	$("#feiyong dd.cattsel input").val();			//车费
		var departure_place		=	$("#chufa dd.cattsel input").val();			//出发地
		var start_place = $("input[name='driver_departure_place']").val(); //出发地
		var destination		=	$("input[name='driver_destination']").val();	//目的地
		list(true,2,destination,ride_price,remain_number,departure_place,start_place);
		$("#search").hide();
	})
	function changeAtt(t) {
		t.lastChild.checked = 'checked';
	}
</script>
<!--    拼车列表	-->
<div class="padding-tb5 bg-gray"></div>
<div class="m-b-xxl"><div id="newBbsAricleList"></div></div>
<div class="m-b-xxl"><div id="newBbsAricleList1"></div></div>
</div>
<div id="footer" class="app-footer b-t bgBottom">
<div id="launch" class="btn b-r col-xs-6 padding-tb10"><img class="thumb-xxxs" src="{pigcms{$defaultImg.urlCar}" /> 发起</div>
<div id="history" class="btn b-l col-xs-6 padding-tb10"><img class="thumb-xxxs" src="{pigcms{$defaultImg.urlMe}" /> 我的</div>
</div>
<script type="text/html" id="template">
<& for(var i = 0; i< _this.res.ride_list.length;i++) { &>
<& var obj = _this.res.ride_list[i]; &>
<div class="m-b-sm">
	<a href="/<&= _this.domain_host &>?g=Wap&c=Ride&a=ride_details&ride_id=<&= obj.ride_id &>&status=1">
		<div class="padding-lr5 m-l-xs m-t-sm text-md"><&= obj.ride_title &></div>
		<div class="padding-lr5 m-l-xs pull-left text-base"><&= obj.ride_date_number==1?'':'每天' &><&= obj.start_date &>出发</div>
		<div class="pull-right m-r-sm text-sm">车主信用度：<span class="text-danger"><&= obj.proportion*1<=0?'0': obj.proportion&>%</span></div>
		<div class="clearfix"></div>
		<div class="padding-lr5 padding-tb5">
			<div class="col-xs-2 m-l-n m-t-xs" style="width:70px;">
				<img class="avatar" src="<&= obj.avatar &>">
				<div class="text-xs text-center" style="word-break: break-all;"><&= obj.owner_name &></div>
			</div>
			<div class="col-xs-7 no-padder text-base">
				<div class="m-t-sm"><img class="thumb-xxxs" src="<&= _this.res.defaultImg.urlStar &>"> <&= obj.departure_place &></div>
				<div class="m-t-sm"><img class="thumb-xxxs" src="<&= _this.res.defaultImg.urlEnd &>"> <&= obj.destination &></div>
			</div>
			<div class="col-xs-2 text-right no-padder pull-right m-t text-sm">
				<div><span class="text-md color-orange"><&= obj.ride_price &></span>&nbsp;&nbsp;{pigcms{$config['Currency_txt']}</div>
				<div class="color-green"><span class="text-md"><&= obj.remain_number &></span>&nbsp;&nbsp;座</div>
			</div>
			<div class="clearfix"></div>
		</div>
	</a>
</div>
<div class="padding-tb5 bg-gray"></div>
<& } &>
<& if(this.next(_this.res.ride_list.length)){ &>
<div id="is_null" class="text-center m-t m-b text-xs color-black2">没有顺风车了<p>点击({pigcms{$index})可以返回顶部</p></div>
<& } &>
<& else{&>
<div id="mais" class="text-center m-t m-b">上拉会有更多顺风车哦<p class="text-xs color-black2">点击({pigcms{$index})可以返回顶部</p></div>
<& } &>
</script>
<script type="text/html" id="templated">

<& for(var i = 0; i< _this.length;i++) { &>
<& var obj = _this[i]; &>
<li class="   por" data-id="<&=obj.ride_cate_id&>" data-index="<&=i+2&>">
	<&=obj.ride_cate_name&>
</li>
<& } &>

</script>
<script type="text/html" id='cate_all'>
<li><a href="javascript:void(0)"  class="bg_active" data-id="" data-index="0">全部</a></li>
<li><a href="javascript:void(0)"  class="" data-id="recent" data-index="1"> 附近出发 </a></li>
<& for(var i = 0; i< _this.length;i++) { &>
<& var obj = _this[i]; &>
<li><a href="javascript:void(0)"  class="" data-id="<&=obj.ride_cate_id&>" data-index="<&=i+2&>"><&=obj.ride_cate_name&></a></li>
<& } &>

</script>
<script type="text/javascript" >
window.shareData = {
	"moduleName":"Ride",
	"moduleID":"0",
	"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
	"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Ride/ride_list',array('plat'=>$_GET['plat']))}",
	"tTitle": "拼车-{pigcms{$config.site_name}",
	"tContent": "{pigcms{$config.site_name}拼车"
};
</script>
		{pigcms{$shareScript}

<script>
var root={
	templateId:'template',
	data:[]
}
var ride_cate_id='';
var tpl = Simplite.getTemplate(root.templateId);
// root.render()
var	wx_host	=	'{pigcms{$site_url}';
var page	=	1;
var village_id	=	'{pigcms{$village_id}';
var ride_is	=	'{pigcms{$ride_is}';
var is_indep_house = "{pigcms{:defined('IS_INDEP_HOUSE')}";
var car_status = "{pigcms{$car_status}";
if(is_indep_house){
	var domain_host = "{pigcms{:C('INDEP_HOUSE_URL')}";
}else{
	var domain_host = 'wap.php';
}

$('#return_index').on('click',function(){
	location.href =	'{pigcms{$returnUrl}';
});
var wh=$(window).width();
var myScroll =null;
var w=0;
var now_x=0;
var add_x=[];
var r_x=$(".add_more").width();
var show_x=wh-r_x-$(".ul li").width()-10;
var init=true;
//$("#wrapper").css("width",wh-35);
// 分类Wap/Ride/ride_cate_api
(function (){
	$.ajax({
		type : "post",
		url : wx_host+domain_host+'?g=Wap&c=Ride&a=ride_cate_api',
		dataType : "json",
		data:{

		},
		async:true,
		success : function(data){
			if(data.result && data.result.length>0){

				for(var i=0;i<data.result.length;i++){
					if(data.result[i].ride_cate_id=='recent'){
						data.result.splice(i,1)
					}
				}
				var tp = Simplite.getTemplate('templated');
				var tp_all=Simplite.getTemplate('cate_all');
				var compiledTpl = Simplite.compile(tp)(data.result);
				var compiledTpl_all = Simplite.compile(tp_all)(data.result);
				$('#scroller ul').html(compiledTpl);
				$('#ride_all ul').html(compiledTpl_all);
				var $scroller = $('#scroller');
				var innerWidth = 0;
				$('#wrapper').navbarscroll({
					defaultSelect:0,
					scrollerWidth:5,
					fingerClick:1,
					endClickScroll:function(obj){
						if(!init && !obj.hasClass('active')){
							$(".ul li").removeClass("active")
							obj.addClass("active").siblings().removeClass("active");
							ride_cate_id=obj.attr("data-id");
							var index=parseInt(obj.attr("data-index"));
							$("#ride_all a").removeClass("bg_active")
							$("#ride_all a").eq(index).addClass("bg_active")
							page=1;
							$('#newBbsAricleList').html('');
							list(true,1);
						}
						init=false


					}
				});
				$scroller.find('li').each(function() {
					add_x.push(innerWidth)
				});


			}



		},
		error:function(){
			a.msg('页面错误，请联系管理员');
		}
	})
})()

function  next(len){
	return len>8? true:false

}
$(document).on("click",'.ul li',function(e){
	// window.myScroll.scrollTo(0,0, 500);
	if(!$(this).hasClass("active")){
		$("#scroller li").removeClass("active");
		$(".ul li").removeClass("active");
		$(this).addClass("active");
		now_x=0;
		ride_cate_id=$(this).attr("data-id");
		$("#ride_all a").removeClass("bg_active")
		$("#ride_all a").eq(index).addClass("bg_active")
		page=1;
		$('#newBbsAricleList').html('');
		list(true,1);
	}

})
$(document).on("click",'#ride_all a',function(){
	$("#ride_all a").removeClass("bg_active")
	$(this).addClass("bg_active");
	var index=parseInt($(this).attr("data-index"))
	$(".add_more").removeClass("add_more_all")
	$("#ride_all").slideUp('fast') ;
	$(".look_all").hide();
	$(".clearfix_list").show();
	$("#scroller").removeClass("no_tranns");
	$("#scroller li").removeClass("active")
	$(".ui_left").show();
	if(index==0 || index==1){
		$(".ul li").eq(index).trigger("click")
	}else{
		// $("#scroller li").eq(index).addClass("active");
		$("#scroller li").eq(index-1).trigger("click")
	}



})

$(".add_more").on("click",function(){
	if($(this).hasClass('add_more_all')){
		$(".ui_left").show()
		$(this).removeClass("add_more_all")
		$("#ride_all").slideUp('fast') ;
		$(".look_all").hide();
		$(".clearfix_list").show();
		$("#scroller").removeClass("no_tranns")
	}else{
		$(".ui_left").hide()
		$("#ride_all").slideDown('fast') ;
		$(".look_all").show();
		$(".clearfix_list").hide();
		$("#scroller").addClass("no_tranns")
		$(this).addClass("add_more_all")
	}
})
if(ride_is	== 0){
	var rideList	=	'<div class="text-center m-t m-b">未开通顺风车功能</div>';
	$('#newBbsAricleList').append(rideList);
	$('#screen').remove();
	$('#launch').remove();
	$('#history').remove();
}else{
	list(true);
}
$('#launch').on('click',function(){
	if(!car_status){
		alert('您还未进行车主认证');
		if(is_indep_house){
			location.href = domain_host + "?g=Wap&c=House&a=car_apply";
		}else{
			location.href = "{pigcms{:U('My/car_apply')}";
		}
	}else if(car_status == 0){
		alert('您的车主认证未审核，请联系客服');
		if(is_indep_house){
			location.href = domain_host + "?g=Wap&c=House&a=car_owner";
		}else{
			location.href = "{pigcms{:U('My/car_owner')}";
		}
	}else if(car_status == 2){
		alert('您的车主认证未通过，请重新认证');
		if(is_indep_house){
			location.href = domain_host + "?g=Wap&c=House&a=car_owner";
		}else{
			location.href = "{pigcms{:U('My/car_owner')}";
		}
	}else{
		location.href = domain_host+"?g=Wap&c=Ride&a=ride_add&type=1";
	}
});
$('#history').on('click',function(){
	location.href = domain_host + "?g=Wap&c=Ride&a=ride_history";
});
$('#index').on('click',function(){
	location.href = "javascript:scroll(0,0)";
});
$(window).scroll(function(){
	if($(window).scrollTop() == $(document).height() - $(window).height()){
		$('#mais').remove();
		var jia	=	'';
		jia	+=	'<div id="jia" class="text-center m-t m-b">正在加载</div>';
		$('#newBbsAricleList').append(jia);
		if($('#is_null').length < 1){
			destination	=	$('#destination').text();
			ride_price	=	$('#ride_price').text();
			remain_number	=	$('#remain_number').text();
			departure_place		=	$("#chufa dd.cattsel input").val();			//出发地
			start_place = $("input[name='driver_departure_place']").val(); //出发地
			list(false,1,destination,ride_price,remain_number,departure_place,start_place);
		}else{
			$('#jia').remove();
		}
	}
});
function	list(is,search,destination,ride_price,remain_number,departure_place,start_place){
	a.busy();
	$.ajax({
		type : "post",
		url : wx_host+domain_host+'?g=Wap&c=Ride&a=ride_list_api',
		dataType : "json",
		data:{
			ride_price	:	ride_price,
			remain_number	:	remain_number,
			destination	:	destination,
			start_place : start_place,
			departure_place	:	departure_place,
			village_id	:	village_id,
			page	:	page,
			ride_cate_id:ride_cate_id
		},
		async:is,
		success : function(result){
			var rideList	=	'';
			if(result.result){
				var	ride_list	=	result.result.ride_list;
				var	defaultImg	=	result.result.defaultImg;
				var	ride_list_length	=	ride_list.length;
				if(ride_list_length){
					page++;
					console.log(ride_list)
					var datas={
						domain_host:domain_host,
						res:result.result
					}
					// var compiledTpl = Simplite.compile(tpl)(datas);
					// $('#newBbsAricleList1').html(compiledTpl);
					for(var x=0;x<ride_list_length;x++){
						rideList	+='<div class="m-b-sm">';
						rideList	+='		<a href="/'+domain_host+'?g=Wap&c=Ride&a=ride_details&ride_id='+ride_list[x].ride_id+'&status=1">';
						rideList	+='			<div class="padding-lr5 m-l-xs m-t-sm text-md">'+ride_list[x].ride_title+'</div>';
						if(ride_list[x].ride_date_number == 1){
							rideList	+='			<div class="padding-lr5 m-l-xs pull-left text-base">'+ride_list[x].start_date+'出发</div>';
						}else{
							rideList	+='			<div class="padding-lr5 m-l-xs pull-left text-base">每天'+ride_list[x].start_date+'出发</div>';
						}
						if(ride_list[x].proportion <= 0){
							rideList	+='			<div class="pull-right m-r-sm text-sm">车主信用度：<span class="text-danger">0%</span></div><div class="clearfix"></div>';
						}else{
							rideList	+='			<div class="pull-right m-r-sm text-sm">车主信用度：<span class="text-danger">'+ride_list[x].proportion+'%</span></div><div class="clearfix"></div>';
						}
						rideList	+='			<div class="padding-lr5 padding-tb5">';
						rideList	+='				<div class="col-xs-2 m-l-n m-t-xs" style="width:70px;">';
						rideList	+='				<img class="avatar" src="'+ride_list[x].avatar+'" />';
						rideList	+='					<div class="text-xs text-center" style="word-break: break-all;">'+ride_list[x].owner_name+'</div></div>';
						rideList	+='				<div class="col-xs-7 no-padder text-base">';
						rideList	+='					<div class="m-t-sm"><img class="thumb-xxxs" src="'+defaultImg.urlStar+'" /> '+ride_list[x].departure_place+'</div>';
						rideList	+='					<div class="m-t-sm"><img class="thumb-xxxs" src="'+defaultImg.urlEnd+'" /> '+ride_list[x].destination+'</div>';
						rideList	+='				</div>';
						rideList	+='				<div class="col-xs-2 text-right no-padder pull-right m-t text-sm">';
						rideList	+='					<div><span class="text-md color-orange">'+ride_list[x].ride_price+'</span>&nbsp;&nbsp;{pigcms{$config['Currency_txt']}</div>';
						rideList	+='					<div class="color-green"><span class="text-md">'+ride_list[x].remain_number+'</span>&nbsp;&nbsp;座</div>';
						rideList	+='				</div>';
						rideList	+='				<div class="clearfix"></div>';
						rideList	+='			</div>';
						rideList	+='		</a>';
						rideList	+='	</div>';
						rideList	+='	<div class="padding-tb5 bg-gray"></div>';
					}
					if(ride_list_length <= 9){
						rideList	+=	'<div id="is_null" class="text-center m-t m-b text-xs color-black2">没有顺风车了<p>点击({pigcms{$index})可以返回顶部</p></div>';
					}else{
						rideList	+=	'<div id="mais" class="text-center m-t m-b">上拉会有更多顺风车哦<p class="text-xs color-black2">点击({pigcms{$index})可以返回顶部</p></div>';
						rideList	+=	'<div class="hide"><div id="destination">'+destination+'</div><div id="ride_price">'+ride_price+'</div><div id="remain_number">'+remain_number+'</div><div id="departure_place">'+departure_place+'</div></div>';
					}
				}else{
					rideList	+=	'<div id="is_null" class="text-center m-t m-b text-xs color-black2">没有顺风车了<p>点击({pigcms{$index})可以返回顶部</p></div>';
				}
			}else{
				if(search == 1){
					if(page == 1){
						rideList	+=	'<div id="is_null" class="text-center m-t m-b">没有顺风车</div>';
					}else{
						rideList	+=	'<div id="is_null" class="text-center m-t m-b text-xs color-black2">没有顺风车了<p>点击({pigcms{$index})可以返回顶部</p></div>';
					}
				}else if(search == 2){
					if(page == 1){
						rideList	+=	'<div id="is_null" class="text-center m-t m-b">未搜索到顺风车</div>';
					}else{
						rideList	+=	'<div id="is_null" class="text-center m-t m-b text-xs color-black2">没有顺风车了<p>点击({pigcms{$index})可以返回顶部</p></div>';
					}
				}
			}
			$('#jia').remove();
			if(search == 2){
				$('#newBbsAricleList').html(rideList);
			}else{
				$('#newBbsAricleList').append(rideList);
			}
			a.busy(0);
		},
		error:function(){
			a.msg('页面错误，请联系管理员');
			a.busy(0);
		}
	})
}

var motify = {
	checkIos:function(){
		if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
			return true;
		}else{
			return false;
		}
	},
	checkAndroid:function(){
		if(/(android)/.test(navigator.userAgent.toLowerCase())){
			return true;
		}else{
			return false;
		}
	},
	checkLifeApp:function(){
		if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase()))){
			return true;
		}else{
			return false;
		}
	},
	getLifeAppVersion:function(){
		var reg = /versioncode=(\d+),/;
		var arr = reg.exec(navigator.userAgent.toLowerCase());
		if(arr == null){
			return 0;
		}else{
			return parseInt(arr[1]);
		}
	},
	getAndroidVersion:function(){
		var index = navigator.userAgent.indexOf("Android");
		if(index >= 0){
			var androidVersion = parseFloat(navigator.userAgent.slice(index+8));
			if(androidVersion > 1){
				return androidVersion;
			}else{
				return 100;
			}
		}else{
			return 100;
		}
	},
	iosFunction:function(paramStr, paramObj){
		var pack_version = motify.getLifeAppVersion();
		var str = parseFloat(pack_version) >= 1000
		if ( str == true) {
			window.webkit.messageHandlers.appJSBridgeMethod.postMessage(paramObj);
		} else {
			$('body').append('<iframe id="iosIframe" src="pigcmso2o://'+paramStr+'" style="display:none;"></iframe>');
		}
		
	}
}
if(motify.checkLifeApp() && motify.getLifeAppVersion() >= 50 && (motify.checkIos() || motify.checkAndroid())){
	$('#return_index').off('click');
	if(motify.checkAndroid()){
		window.lifepasslogin.hideWebViewHeader(false);
		$('#return_index').on('click',function(){
			window.lifepasslogin.webViewGoBack();
		});
	}else{
		motify.iosFunction('hideWebViewHeader/false', {name:'hideWebViewHeader', show_head:'0' })
        $('.app-header').css('height', '60px');
        $('.app-header .margin-top3').css('margin-top', '15px');
        $('.app-header #return_index.margin-top3').css('margin-top', '12px');
        $('.app .app-content.with-header').css('margin-top', '12px');
		$('#return_index').on('click',function(){
			motify.iosFunction('webViewGoBack', {name:'webViewGoBack'});
		});
	}
}
</script>
<include file="web_footer"/>