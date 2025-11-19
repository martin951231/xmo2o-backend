 $(function(){ 
 	$(".pubwrap ul.ul_menu li").hover(function() { 
	        $(this).find('.second_box').stop().slideDown('300');
	    }, function() {
	        $(this).find('.second_box').stop().hide();
	 }); 
	 
	 //点击展开
	$('.ul_newsfr li:first-child  .list_con').show();
	$('.ul_newsfr li .list_p').hover(function(){ 
		$(this).parent().find('.list_con').stop().show().parent().siblings().find('.list_con').hide();
	}) 
	
	//导航
	 var url=location.href;
	$(".ul_menu  li a.selecta").each(function(){
	    if(url.lastIndexOf($(this).attr("href").replace("/",""))!=-1){
	        $(this).addClass("cur");  
	      } 
	 });
	 
	 $('.contact_ul li.icon_contact3 a').hover(function(){
	 	$('.tan_weib').addClass('tan_weiFix');
	 }, function() {
	     $('.tan_weib').removeClass('tan_weiFix');
	 });
	  $('.contact_ul li.icon_contact2 a').hover(function(){
	 	$('.tan_weia').addClass('tan_weiFix2');
	 }, function() {
	     $('.tan_weia').removeClass('tan_weiFix2');
	 })

   //代理切换
  var $tab_li = $('.ul_daili li');
  $tab_li.hover(function(){
    $(this).addClass('cur').siblings().removeClass('cur');
    var index = $tab_li.index(this);
    $('div.daili_con > .daili_block').eq(index).show().siblings().hide();
  }); 

  var $details_tabBox_li = $('.details_tabBox a');
  $details_tabBox_li.click(function(){
    $(this).addClass('cur').siblings().removeClass('cur');
    var index = $details_tabBox_li.index(this);
    $('div.news_fl> .news_flBox').eq(index).show().siblings().hide();
  }); 

    var $app_centerSelect = $('.app_centerSelect a');
  $app_centerSelect.click(function(){
    $(this).addClass('cur').siblings().removeClass('cur');
    var index = $app_centerSelect.index(this);
    $('div.appRR> .appRRblock').eq(index).show().siblings().hide();
  }); 


	
 })
 
 $(window).scroll(function () {
       var topp = $(document).scrollTop(); 
       if(topp>1200){
          $('.contact_ul li.icon_contact4').show();
       }
       else{
          $('.contact_ul li.icon_contact4').hide();
       }
       
       //新闻详情的滑动置顶
       if(topp>410){
         	$('.news_fr').addClass('postion_news');
       }else{
       		$('.news_fr').removeClass('postion_news');
       }
	
       
  }) 



//
function gotoTop(acceleration,stime) { 
   acceleration = acceleration || 0.1;
   stime = stime || 10;
   var x1 = 0;
   var y1 = 0;
   var x2 = 0;
   var y2 = 0;
   var x3 = 0;
   var y3 = 0; 
   if (document.documentElement) {
       x1 = document.documentElement.scrollLeft || 0;
       y1 = document.documentElement.scrollTop || 0;
   }
   if (document.body) {
       x2 = document.body.scrollLeft || 0;
       y2 = document.body.scrollTop || 0;
   }
   var x3 = window.scrollX || 0;
   var y3 = window.scrollY || 0;
 
   // 滚动条到页面顶部的水平距离
   var x = Math.max(x1, Math.max(x2, x3));
   // 滚动条到页面顶部的垂直距离
   var y = Math.max(y1, Math.max(y2, y3));
 
   // 滚动距离 = 目前距离 / 速度, 因为距离原来越小, 速度是大于 1 的数, 所以滚动距离会越来越小
   var speeding = 1 + acceleration;
   window.scrollTo(Math.floor(x / speeding), Math.floor(y / speeding));
 
   // 如果距离不为零, 继续调用函数
   if(x > 0 || y > 0) {
       var run = "gotoTop(" + acceleration + ", " + stime + ")";
       window.setTimeout(run, stime);
   }
}
//index end

 

 
 

 
