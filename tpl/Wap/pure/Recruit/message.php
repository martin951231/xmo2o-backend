<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
	    <meta name="viewport"
	          content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
	    <meta http-equiv="pragma" content="no-cache"/>
	    <meta name="apple-mobile-web-app-capable" content="yes"/>
	    <meta name='apple-touch-fullscreen' content='yes'/>
	    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
	    <meta name="format-detection" content="telephone=no"/>
	    <meta name="format-detection" content="address=no"/>
	    <title>我的消息</title>
         
	    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?222"> 
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/index.css">
	    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/style.css?1">
	    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script> 
		<script>
			var currency_symbol = "{pigcms{$config.Currency_symbol}";
		</script>
        <script src="{pigcms{$static_path}recruit/js/base.js?133" ></script>
	    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
	    <!-- <script src="{pigcms{$static_public}activity/assets/js/common.js" type="text/javascript" charset="UTF-8"></script> -->
	    <script src="{pigcms{$static_path}recruit/js/dropdown.js" type="text/javascript"></script> 
	    <style type="text/css">
	    	.fastBox{
	    		background: white;
			    border: 1px solid #F1F1F1;
			    box-sizing: border-box;
			    border-radius: 6px;
			    box-shadow: 0 5px 10px #e5e5e5;
	    	}
	    	.fastBox .orderBiz .item {
			    float: left;
			    width: 25%;
			    text-align: center;
			    padding: 20px 0;
			    color: #aaa;
   		 		font-size: 14px;
			}
			.static_online {
				padding: 0 5px;
				color: #75a7f9;
				font-size: 12px;
				position: absolute;
				right: 0px;
				top: 5px;
				background-color: #DC143C;
				color: #fff;
				border-radius: 40%;
			}
			.message_last span {
				padding-right: 10px;
				font-size: 12px;
				color: #a2a2a2;
				margin-right: 10px;
			}
			.weui-media-box_appmsg .weui-media-box__thumb {
			    height: 100%;
			}
			.dotNumber{
				position: relative;
			}
			.dotNumber i{
				position: absolute; 
				right:5px;
				top:2px;
				width: 6px;
				height: 6px; 
				border-radius: 100%;
				background: red;
				color:#fff;
				font-size: 10px;
				font-style: normal
			}
	    </style>
	</head>
	<body> 
		<div class="">
		 	<div class="fastBox">
				<div class="orderBiz cf">
					<if condition='$now_company["status"] eq 1'>
					<div class="item openUrl newWin" data-url="{pigcms{:U('my_resume_list')}">
						<div class="icon iconfont resume_c"></div>
						<div class="txt">简历管理</div>
					</div>
					<div class="item openUrl newWin" data-url="{pigcms{:U('resume_list')}">
						<div class="icon iconfont talents_c"></div>
						<div class="txt">搜索人才</div>
					</div>
					<div class="item openUrl newWin" data-url="{pigcms{:U('my_course')}">
						<div class="icon iconfont course_c"></div>
						<div class="txt">我的视频</div>
					</div>
					<div class="item openUrl newWin" data-url="{pigcms{:U('energy_package')}">
						<div class="icon iconfont energy_c"></div>
						<div class="txt">能量包</div>
					</div>
					</if>
					<div class="clear"></div>
				</div>
			</div> 
		 <!---->  
		  <div class="weui-panel weui-panel_access">
		 	 <div class="weui-panel__bd">
              </div>
		 	</div> 
		 <!--end-->
		</div>  
		<script>
			$('.top_fixed .topCategory .nav-head-name').click(function(){
				$('.fixpostionBox').toggleClass('show');
				$(this).toggleClass('active')
			})
			$('.fixpostionBox').click(function(){
				$('.fixpostionBox').removeClass('show');
				$('.top_fixed .topCategory .nav-head-name').removeClass('show');
			}) 
		</script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/dropload.css"> 
		<script type="text/javascript" src="{pigcms{$static_path}recruit/js/dropload.min.js"></script> 
		<script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
		<script type="text/javascript">
			var ajax_message_list_url = "{pigcms{:U('ajax_message_list')}";
			var message_detail_url = "{pigcms{:U('message_detail')}";
			var position_detail_url = "{pigcms{:U('position_detail')}";
			$(function(){
				// 页数
				var page = 0;
				// 每页展示10个
				var count = 10;   
				// dropload
				$('.weui-panel_access').dropload({
					scrollArea : window,
					loadDownFn : function(me){
						page++;
						// 拼接HTML
						var result = '';  
						$.ajax({
							type: 'GET',
							url: ajax_message_list_url,
							data:{
							'page':page,
							'count':count
							},
							dataType: 'json',
							success: function(data){   
								var dateArr = data.result.list;
								if(dateArr.length > 0){ 
									for(var i=0;i<dateArr.length;i++){
										if (dateArr[i].self) {
											result+='<a  class="weui-media-box weui-media-box_appmsg openUrl newWin" data-url="'+position_detail_url+'&position_id='+dateArr[i].position_id+'" >';
										}else{ 
											result+='<a  class="openUrl newWin weui-media-box weui-media-box_appmsg" data-url="'+message_detail_url+'&id='+dateArr[i].id+'"  >';
										}
										result+='	<div class="weui-media-box__hd dotNumber" style="background:url(';
										console.log(dateArr[i].avatar_old)
										if (dateArr[i].avatar_old!='') {
											result+=dateArr[i].avatar;
										}else{
											result+='{pigcms{$static_path}recruit/images/default_avatar.png';
										}

										result+=') no-repeat; background-size:cover; border-radius:100%;">';
										if(dateArr[i].status == 0){
											result+='		 <i></i>';
										} 
										result+='	</div>';
										result+='	<div class="weui-media-box__bd">';
										result+='		<h4 class="weui-media-box__title">'+dateArr[i].truename+'</h4>';
										// result+='		<span class="static_online">2</span>';
										result+='		<p class="resume_label">';
										if (dateArr[i].age) {
										result+='			<label>'+dateArr[i].age+'岁</label>';
										}
										if (dateArr[i].user_work_years) {
										result+='			<label>'+dateArr[i].user_work_years+'</label>';
										}
										if (dateArr[i].salary) {
										result+='			<label>'+dateArr[i].salary+'</label>';
										}
										result+='		</p>';
										result+='		<p class="message_last">';
										result+='			<span>在找'+dateArr[i].position_name+'</span>';
										result+='		</p> ';
										result+='	</div>';
										result+='</a>'; 
									}  
								}else{ 
									me.lock(); // 锁定
									me.noData(); // 无数据
								}
								setTimeout(function(){
									$('.weui-panel__bd').append(result); 
									me.resetload(); // 每次数据插入，必须重置 
								})
								
							},
							error: function(xhr, type){
								// alert('Ajax error!');
								// 即使加载出错，也得重置
								me.resetload();
							}
						});
					}
				});
			});
		</script> 
        <div class="share_html">{pigcms{$shareScript}</div>  
<include file="footer"/>