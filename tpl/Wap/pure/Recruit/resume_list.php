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
		<title>个人求职简历</title> 
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/index.css?212">
	    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
	    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/style.css?3">
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/dropload.css">  
        <!-- <script src="{pigcms{$static_path}recruit/js/base.js?13566" ></script> -->
	    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script> 
         <!-- <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js?222"></script> -->
        <script type="text/javascript" src="{pigcms{$static_path}js/common.js?213" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}recruit/js/dropload.min.js"></script>
	    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
	    <!-- <script src="{pigcms{$static_public}activity/assets/js/common.js" type="text/javascript" charset="UTF-8"></script> -->
	    <script type="text/javascript">var now_sex=0,now_years=0;now_area=0;</script>
		<script src="{pigcms{$static_path}recruit/js/dropdown.js" type="text/javascript"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js?222" charset="utf-8"></script> 
        
		<style>
		 .weui-panel{
			background:#f3f3f3
		 }
		 .weui-panel__bd a{ 
			border-bottom:1px solid #eee;
		 }
		 .weui-panel__bd .blockData{
			background:#fff;
			margin:10px 10px 0 10px;
		 }

		 .weui-panel__bd .blockData .message_last{
			 padding:10px;
			 display:block;
			 height:30px;
		 }
		 .weui-media-box__title .age{
			 margin-left:10px;
			 padding-left:10px;
			 position: relative;
		 }
		 .weui-media-box__title .age:after{
			position: absolute;
			content:'';
			width:1px;
			height:14px;
			background:#333;
			left:0;
			top:6px;
		 }
		 .ago_time{
			 color:#999;
			 position: absolute;
			 right:0;
			 top:0;
			 font-size:12px;
		 }
		 .online_chat{
			position: absolute;
			right:0;
			top:30px;
			border:1px solid #666;
			padding:0 10px;
			font-size:12px;
		 } 
		 .address{
			background:url('{pigcms{$static_path}recruit/images/adress.png') left center no-repeat;
			background-size:20px auto;
			padding-left:25px;
			color:#999;
			font-size:12px;
		 }  
		 .weui-media-box_appmsg .weui-media-box__hd{
			 width:55px;
			 height:55px;
			 border-radius: 100%; 
			 position: relative;
		 }
		 .weui-panel{
			 margin-top:0;
		 }
		 .weui-media-box_appmsg .weui-media-box__thumb.imgSex{ 
			position: absolute;
			bottom:2px;
			right:5px;
			z-index:1;
			width:20px;
			border:1px solid #fff;
		 } 
		 .nodata{
			 padding:10px 0;
			 color:#999;
		 }
		</style>
	</head>
	<body style="background:#f3f3f3">  
		<header <if condition="$config['many_city']">class="hasManyCity"</if> style="z-index:111;<if condition='$config["mobile_head_color"]'>background-color: {pigcms{$config["mobile_head_color"]};</if>">
				<if condition="$config['many_city']">
					<div id="cityBtn" class="  openUrl newWin" data-url="{pigcms{:U('Changecity/index')}&resume_list=1&only_city=1" style='max-width:120px'>{pigcms{:msubstr($now_select_city['area_name'],0,5)}
					</div>
					<div id="searchBox" data-ff="2" style=" margin-right:45px;margin-left: 0px;">
						<a href="{pigcms{:U('Recruit/search',array('type'=>'resume'))}" <if condition="$is_weixin_browser">style='float: left;width: 80%;'</if>>
							<i class="icon-search"></i>
							<span>请输入您想找的内容</span>
							<if condition="$is_weixin_browser">  <a href="javascript:;" style="width:20%;float:right;" class="smallVioce"><i class="icon-hua"></i></a></if>
						</a>
					</div>
			<else />
					<div id="cityBtn" class=" openUrl newWin" data-name="{pigcms{$config['now_select_city']['area_name']}" data-url="{pigcms{:U('Changecity/index')}&resume_list=1&only_city=1" style='max-width:120px'>{pigcms{:msubstr($now_select_city['area_name'],0,5)}
					</div>

					<div id="searchBox" data-ff="23"  style="<if condition="msubstr($config['now_select_city']['area_name'],0,2) neq $config['now_select_city']['area_name']">margin: 10px 77px 0px 0px;<else/> margin-right:49px;margin-left: 0px</if>">
						<a href="{pigcms{:U('Recruit/search',array('type'=>'resume'))}" <if condition="$is_weixin_browser">style='float: left;width: 80%;'</if>>

							<i class="icon-search"></i>
							<span>请输入您想找的内容</span>
							<if condition="$is_weixin_browser">
								<a href="javascript:;" style="width: 20%; float: right;" class="smallVioce"><i class="icon-hua"></i></a>
							</if>
						</a>
					</div>
			</if>
		</header> 
		<div class="">  
		<section class="navBox pageSliderHide">
			<ul>
			    <li class="dropdown-toggle caret area" data-nav="area">
					<span class="nav-head-name">区域</span>
				 </li>
				<li class="dropdown-toggle caret sex subway" data-nav="biz">
					<span class="nav-head-name">性别不限</span>
				</li>
				<li class="dropdown-toggle caret category" data-nav="category">
					<span class="nav-head-name">经验不限</span>
				</li>
			</ul>
			
			<div class="dropdown-wrapper biz active">
				<div class="dropdown-module" style="">
					<div class="scroller-wrapper">
						<div id="dropdown_scroller" class="dropdown-scroller">
							<div>
								<ul> 
								   <li class="area-wrapper"> 
										<ul class="dropdown-list" style="max-height:285px; overflow-y: scroll;"> 
										</ul>
									</li>	 
									<li class="biz-wrapper"> 
										<ul class="dropdown-list">
											<li data-sex-id="defaults" onclick="list_location($(this));return false;"><span data-name="性别不限">性别不限</span></li>
											<li data-sex-id="1" onclick="list_location($(this));return false;"><span data-name="只看男">只看男</span></li>
											<li data-sex-id="2" onclick="list_location($(this));return false;"><span data-name="只看女">只看女</span></li> 
										</ul>
									</li>									
								   <li class="category-wrapper">
										<ul class="dropdown-list">
											<volist name="work_years_arr" id="vo">
												<li data-category-id="{pigcms{$key}" class="" onclick="list_location($(this));return false;"><span data-name="{pigcms{$vo}">{pigcms{$vo}</span></li>
											</volist>
										</ul>
									</li>
								</ul>
							</div>
						  </div>
						 
					 </div>
				 </div>
			 </div>
				
			<section class="storeListBox listBox"><div class="shade"></div></section>
		 </section> 
		 
		 <!---->  
		  <div class="weui-panel weui-panel_access content">
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
		 
		<script id="listCategoryListTpl" type="text/html">
				<li data-area-id="defaults" onclick="list_location($(this));return false;"><span data-name="全部">全部</span></li>
			{{# for(var i = 0, len = d.length; i < len; i++){ }}
				<li data-area-id="{{ d[i].area_id }}" {{# if(d[i].son_list && d[i].son_list.length > 0){ }}data-has-sub="true"{{# }else{ }} onclick="list_location($(this));return false;" {{# } }} class="listCat-{{ d[i].cat_url }} {{# if(d[i].son_list && d[i].son_list.length > 0){ }}right-arrow-point-right{{# } }} {{# if(i == 0){ }}active{{# } }}">
					<span data-name="{{ d[i].area_name }}">{{ d[i].area_name }}</span>
					{{# if(d[i].son_list && d[i].son_list.length > 0){ }}
						<span class="quantity"><b></b></span>		
						<div class="sub_cat hide">
							<ul class="dropdown-list sub-list">
								<li class="listCat-{{ d[i].cat_url }} isSon" data-cat_id="{{ d[i].cat_id }}" data-cat_url="{{ d[i].cat_url }}" onclick="list_location($(this));return false;"><div><span class="sub-name" data-name="{{ d[i].cat_name }}">全部</span></div></li>
								{{# for(var j = 0, jlen = d[i].son_list.length; j < jlen; j++){ }}
									<li class="listCat-{{ d[i].son_list[j].cat_url }} isSon" data-cat_id="{{ d[i].son_list[j].cat_id }}" data-cat_url="{{ d[i].son_list[j].cat_url }}" onclick="list_location($(this));return false;"><div><span class="sub-name" data-name="{{ d[i].son_list[j].cat_name }}">{{ d[i].son_list[j].cat_name }}</span></div></li>
								{{# } }}
							</ul>
						</div>
					{{# } }}
				</li>
			{{# } }}
		</script>
		<script type="text/javascript">  
			var ajax_resume_list_url = "{pigcms{:U('ajax_resume_list')}";
			var resume_detail_url = "{pigcms{:U('resume_detail')}";
			// 页数
			var page = 0;
			// 每页展示10个
			var count = 10;   
			// dropload 
			var dropload = $('.content').dropload({
				scrollArea : window,
				loadDownFn : function(me){
					page++;
					// 拼接HTML
					var result = '';  
					$.ajax({
						type: 'GET',
						url: ajax_resume_list_url,
						data:{
						'sex':now_sex,
						'work_years':now_years,
						'area_id':now_area,
						'page':page,
						'pageSize':count
						},
						dataType: 'json',
						success: function(data){   
							var dateArr = data.result.list;
							if(dateArr.length > 0){ 
								for(var i=0;i<dateArr.length;i++){ 
									result+='<div class="blockData"  >'
                                    // resume_detail
									result+='<a href="'+resume_detail_url+'&resume_id='+dateArr[i].resume_id+'" class="weui-media-box weui-media-box_appmsg "  data-resume_id="'+dateArr[i].resume_id+'" data-can_view="'+dateArr[i].can_view+'" >';
									if (dateArr[i].avatar) {
										result+='	<div class="weui-media-box__hd" style="background:url({pigcms{:file_domain()}'+dateArr[i].avatar+') center  no-repeat;background-size: cover;">'; 
									}else{
										result+='	<div class="weui-media-box__hd" style="background:url({pigcms{$static_path}recruit/images/default_avatar.png) center  no-repeat;background-size: cover;">'; 
									}
									
									if(dateArr[i].sex == 1){
										result+=' <img class="weui-media-box__thumb imgSex" src="{pigcms{$static_path}recruit/images/man.png" alt="">'; 
									}else{
										result+=' <img class="weui-media-box__thumb imgSex" src="{pigcms{$static_path}recruit/images/woman.png" alt="">'; 
									}
									result+='	</div>';
									result+='	<div class="weui-media-box__bd">';
									result+='		<h4 class="weui-media-box__title">'+dateArr[i].name+'<label class="age">'+dateArr[i].age+'岁</label>'+'</h4>';
									result+='		<span class="ago_time">'+dateArr[i].update_time_format+'</span>';
									result+='		<span class="online_chat" style="border-color: {pigcms{$config["mobile_head_color"]}; color:{pigcms{$config["mobile_head_color"]}">直聊</span>';
									result+='		<p class="resume_label">';

									if(dateArr[i].work_years){
									result+='			<label>'+dateArr[i].work_years+'</label>';
									}
									if(dateArr[i].qualifications){
									result+='			<label>'+dateArr[i].qualifications+'</label>';
									}
									if(dateArr[i].salary){
									result+='			<label>'+dateArr[i].salary+'</label>';
									}
									result+='		</p>'; 
									result+='	</div>';
									result+='</a>'; 
									result+=' <div class="message_last">'; 
									result+='			<span class="fl"><label style="color:#666">求职意向：</label>'+dateArr[i].position_name+'</span>';
									result+='			<span class="address fr">'+dateArr[i].area_name+'</span>';
									result+='			<span class="clear"> </span>';  
									result+='  </div> ';
									result+='</div>' 
								}
							}else{ 
								me.lock(); // 锁定 
								me.noData(); // 无数据 
								$('.nodata').hide();
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
				},
				domDown:{
					domClass : 'dropload-down',
					domRefresh : '<div class="dropload-refresh">↑上拉加载更多</div>',
					domLoad : '<div class="dropload-load">加载中...</div>',
					domNoData : '<div class="dropload-noData"><p class="nodata">加载完毕</p></div>'
				}
				
			}); 
			function list_location(obj){   
				close_dropdown();
				if(obj.attr('data-category-id')){
					obj.addClass('active').siblings('li').removeClass('active');
					$('.dropdown-toggle.category .nav-head-name').html(obj.find('span').data('name'));
					now_years = obj.attr('data-category-id');
				}else if(obj.attr('data-sex-id')){
					obj.addClass('active').siblings('li').removeClass('active');
					$('.dropdown-toggle.sex .nav-head-name').html(obj.find('span').data('name'));
					now_sex = obj.attr('data-sex-id');
				}else if(obj.attr('data-area-id')){
					obj.addClass('active').siblings('li').removeClass('active');
					$('.dropdown-toggle.area .nav-head-name').html(obj.find('span').data('name'));
					now_area = obj.attr('data-area-id');
				}
				
				// 重新加载数据
				console.log(dropload)
				page = 0;
				$('.weui-panel__bd').html(''); 
				dropload.unlock();
				dropload.noData(false);
				dropload.resetload(); 
			}

			View={
				init:function(){
					this.click()
					this.refresh()
				},
				refresh:function(){
					get_area({pigcms{$now_select_city['area_id']});
				},
				click:function(){
					var _this=this
					// $(document).on("click",'.resume_detail',function(){
					// 	var location_url = $(this).data('url');
					// 	window.location.href=location_url;
					// })
				}
			}
			window.View=View;
			View.init()
			function get_area(area_id){
				$.ajax({
					type : "post",
					url : "{pigcms{:U('get_area')}",
					dataType : "json",
					data:{area_id:area_id},
					async:false,
					success : function(data){
						console.log(data.result)
						laytpl($('#listCategoryListTpl').html()).render(data.result, function(html){
							$('.area-wrapper ul').html(html);
						}); 
						
					},
					error:function(res){
						console.log(res)
					}
				})
			} 
   
			</script>
             <div class="share_html">{pigcms{$shareScript}</div>
		</body> 
	</html>
