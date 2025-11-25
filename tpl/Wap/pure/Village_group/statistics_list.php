<?php if (!defined('PigCms_VERSION')) {
    exit('deny access!');
} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>数据统计 </title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=yes"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/> 
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/index.css?t={pigcms{:time()}"/>
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/mescroll.min.css"/> 
		<style>
			body{
				background:#f9f9f9; 
			} 
            
		</style>
	</head>
	<body> 
	   <div class="statistics_tab">
	   	 <a href="javascript:void(0)" class="cur" name="1">统计概况<i></i></a>
	   	 <a href="javascript:void(0)" name='2'>统计明细<i></i></a> 
	   </div>
	   <div class="statistics_con">
            <div class="statistics_con_tab">
				<div class="statistics_block">
					<div class="title">今日</div>
					<div class="flexBox">
						<a href="javascript:void(0)" class="link newWin" data-url="{pigcms{:U('leaderOrder')}"><span <php>if($data['today_order_count']!=0){</php>style="color:#e63c22"<php>}</php>>{pigcms{$data.today_order_count|floatval}</span><p>订单数</p></a> 
						<a href="javascript:void(0)" class="link newWin" data-url="{pigcms{:U('leaderOrder')}"><span <php>if($data['today_order_momey']!=0){</php>style="color:#e63c22"<php>}</php>>{pigcms{$data.today_order_momey|floatval}</span><p>订单金额（元）</p></a>
						<a href="javascript:void(0)"  class="link newWin" data-url="{pigcms{:U('pro_sales_details',array('start_time'=>$data['today_time'],'end_time'=>$data['today_time']))}"><span <php>if($data['data.today_goods_count']!=0){</php>style="color:#e63c22"<php>}</php>>{pigcms{$data.today_goods_count|floatval}</span><p>商品数</p></a>
					</div>
				</div>

				<div class="statistics_block">
					<div class="title">本月</div>
					<div class="flexBox">
					  	<a href="javascript:void(0)" class="link newWin" data-url="{pigcms{:U('leaderOrder')}"><span <php>if($data['month_order_count']!=0){</php>style="color:#e63c22"<php>}</php>>{pigcms{$data.month_order_count|floatval}</span><p>订单数</p></a> 
						<a href="javascript:void(0)" class="link newWin" data-url="{pigcms{:U('leaderOrder')}"><span <php>if($data['month_order_momey']!=0){</php>style="color:#e63c22"<php>}</php>>{pigcms{$data.month_order_momey|floatval}</span><p>订单金额（元）</p></a>
						<a href="javascript:void(0)"  class="link newWin" data-url="{pigcms{:U('pro_sales_details',array('start_time'=>$data['month_start_time'],'end_time'=>$data['month_end_time']))}"><span <php>if($data['data.month_goods_count']!=0){</php>style="color:#e63c22"<php>}</php>>{pigcms{$data.month_goods_count|floatval}</span><p>商品数</p></a>
					</div>
				</div>

				<div class="statistics_block">
					<div class="title">累计</div>
					<div class="flexBox"> 
					  	<a href="javascript:void(0)" class="link newWin" data-url="{pigcms{:U('leaderOrder')}}"><span <php>if($data['all_order_count']!=0){</php>style="color:#e63c22"<php>}</php>>{pigcms{$data.all_order_count|floatval}</span><p>订单数</p></a> 
						<a href="javascript:void(0)" class="link newWin" data-url="{pigcms{:U('leaderOrder')}"><span  <php>if($data['all_order_momey']!=0){</php>style="color:#e63c22"<php>}</php>>{pigcms{$data.all_order_momey|floatval}</span><p>订单金额（元）</p></a>
						<a href="javascript:void(0)"  class="link newWin" data-url="{pigcms{:U('pro_sales_details')}"><span <php>if($data['all_goods_count']!=0){</php>style="color:#e63c22"<php>}</php>>{pigcms{$data.all_goods_count|floatval}</span><p>商品数</p></a> 
					</div>
				</div>

			</div>

			<div class='statistics_con_tab'>
			    <div class="statistics_block">
					<div class="title flexBox"> 
						<div class='static2'>月份</div>
						<div class='static1'>订单数</div>
						<div class='static1'>订单金额</div>
						<div class>商品数</div>
					</div>
                     <div id="mescroll" class="mescroll">   
                       <div class="details_box"> 
                          <div class="statistics_box"> 
                       	</div> 
                       </div>  
                    </div>  
					
				</div>
			</div> 
	   </div>
	    <script src="{pigcms{:C('JQUERY_FILE')}"></script>
        <script type="text/javascript" src="{pigcms{$static_path}villagegroup/js/mescroll.min.js" charset="utf-8"></script> 
        <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}villagegroup/js/base.js" charset="utf-8"></script>
          
	   <script>
		   $('.statistics_tab a').click(function(){  
				var _index = $(this).index(); 
				$(".statistics_con>div").eq(_index).show().siblings().hide(); 
				$(this).addClass("cur").siblings().removeClass("cur"); 
		   })  
           
          $(function(){ 
              //创建MeScroll对象
              var mescroll = new MeScroll("mescroll", {
                  down: {
                      auto: true, //是否在初始化完毕之后自动执行下拉回调callback; 默认true
                      isBounce: true,
                      callback: downCallback //下拉刷新的回调
                  },
                  up: {
                      auto: false, //是否在初始化时以上拉加载的方式自动加载第一页数据; 默认
                      isBounce: true, //此处禁止ios回弹,解析(务必认真阅读,特别是最后一点): http://www.mescroll.com/qa.html#q10
                      callback: upCallback, //上拉回调,此处可简写; 相当于 callback: function (page) { upCallback(page); }
                      toTop:{ //配置回到顶部按钮
                          src : "{pigcms{$static_path}/villagegroup/images/goup.png", //默认滚动到1000px显示,可配置offset修改
                          //offset : 1000
                      },
					  page: {
							num: 1, //当前页 默认0,回调之前会加1; 即callback(page)会从1开始
					  		size: 10 //每页数据条数,默认10
					  },					
                  }
              });
              
              /*下拉刷新的回调 */
              function downCallback(){
                  getListDataFromNet(1, 10, function(data){
                      mescroll.endSuccess();
                      setListData(data.data, false);
                  }, function(){
                      mescroll.endErr();
                  });
              }
              
              /*上拉加载的回调 page = {num:1, size:10}; num:当前页 从1开始, size:每页数据条数 */
              function upCallback(page){
                  //联网加载数据
                  getListDataFromNet(page.num, page.size, function(curPageData){
					  var hasNext = curPageData.data.pagetotal > page.num ? true: false;
                      mescroll.endSuccess(curPageData.data.list.length, hasNext);
                      setListData(curPageData.data, true);
                  }, function(){
                      mescroll.endErr();
                  });
              }
              
              /*设置列表数据*/
              function setListData(curPageData, isAppend) { 
                  var html = ''; 
                  if(curPageData){
                      var dataArray=curPageData.list;   
                      for(let i=0; i<dataArray.length;i++){ 
                          html +=' <div class="title flexBox">'
                           	html +='<a href="javascript:void(0)"  class="static2">'+dataArray[i].date+'</a> '
                            if(dataArray[i].count != 0){
                              html +='<a href="javascript:void(0)"  style="color:#e63c22" class="link newWin static1" data-url="{pigcms{:U('leaderOrder')}" ><span if>'+dataArray[i].count+'</span></a> '  
                            }else{
                                html +='<a href="javascript:void(0)" class="link newWin static1" data-url="{pigcms{:U('leaderOrder')}" ><span if>'+dataArray[i].count+'</span></a> '  
                            }
                            
                            if(dataArray[i].total_money !=0 ){
                                html +='<a href="javascript:void(0)" style="color:#e63c22" class="link newWin static1" data-url="{pigcms{:U('leaderOrder')}"><span>'+dataArray[i].total_money+'</span></a>' 
                            }else{
                               html +='<a href="javascript:void(0)" class="link newWin static1" data-url="{pigcms{:U('leaderOrder')}"><span>'+dataArray[i].total_money+'</span></a>' 
                            }
                            
                            if(dataArray[i].num !=0 ){
                              html +='<a href="javascript:void(0)" style="color:#e63c22" class="link newWin" data-url="{pigcms{:U('pro_sales_details')}&start_time='+dataArray[i].start_time+'&end_time='+dataArray[i].end_time+'"><span>'+dataArray[i].num+'</span></a>' 
                            }else{
                              html +='<a href="javascript:void(0)" ><span class="link newWin" data-url="{pigcms{:U('pro_sales_details')}&start_time='+dataArray[i].start_time+'&end_time='+dataArray[i].end_time+'">'+dataArray[i].num+'</span></a>'  
                            }
                           	 
                           	
                           html +='</div>' 
                      }
					  if(isAppend) {
						  $('.statistics_box').append(html);
					  }else {
						   $('.statistics_box').empty();
						   $('.statistics_box').append(html);
					  }
                  }    
              }  
               function getListDataFromNet(pageNum,pageSize,successCallback,errorCallback){  
                  var html=''
                  $.ajax({
                      url:'{pigcms{:U("ajax_statistics_list")}',
                      type:'post',
                      data:{page:pageNum},
                      dataType:'json', 
                      success: function(data){
                          console.log(data);
                          successCallback&&successCallback(data);
                      },
                      error:function(){
                          console.log('errorCallback');
                          errorCallback&&errorCallback();
                      }
                  })
              }
              
              
          }) 
        
	   </script>
	</body> 
	
</html>
