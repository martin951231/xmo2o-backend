<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
		<meta http-equiv="Expires" content="-1">
		<meta http-equiv="Cache-Control" content="no-cache">
		<meta http-equiv="Pragma" content="no-cache">
		<meta charset="utf-8">
		<title>活动列表</title>
		<link href="{pigcms{$static_path}scenic/css/css_whir.css" rel="stylesheet"/>
		<script src="{pigcms{$static_path}scenic/js/jquery-1.8.3.min.js"></script>
		<!--[if lte IE 9]>
		<script src="scripts/html5shiv.min.js"></script>
		<![endif]-->
	</head>
<body>
	<div class="Hfarea hfjd">
		<ul class="clr red">
			<li class="on">正在进行</li>
			<a href="{pigcms{:U('inside_overdue_activity',array('scenic_id'=>$_GET['scenic_id']))}"><li>往期活动</li></a>
		</ul>
	</div>
	<div class="Description activities m48">
		<dl id="newList">
		</dl>
		<div id="list"></div>
	</div>
	<script type="text/javascript">
		var page = 0;
		list();
		$(window).scroll(function(){
			if($(window).scrollTop() == $(document).height() - $(window).height()){
				if($('#is_null').length < 1){
					var jia	=	'';
    				jia	+=	'<div id="jia" style="text-align:center">正在加载</div>';
    				$('#list').append(jia);
					list();
				}else{
					$('#list').remove();
				}
			}
		});
		function list(){
			$.ajax({
				type : "post",
				url : "{pigcms{:U('inside_activity_json')}",
				dataType : "json",
				data:{
					page	:	page,
					status	:	1,
					scenic_id	: "{pigcms{$_GET['scenic_id']}"
				},
				async:false,
				success : function(result){
					var rideList	=	'';
					if(result.errorCode == 0){
						var	ride_list	=	result.result;
						if(ride_list){
							page = page+10;
							var	ride_list_length	=	ride_list.length;
							for(var x=0;x<ride_list_length;x++){
								rideList	+='<dd onclick="details('+ride_list[x].activity_id+')">';
								rideList	+='	<div class="activities_top clr">';
								rideList	+='		<div class="activities_left fl"><img src="'+ride_list[x].pic+'"><span class="ks"></span></div>';
								rideList	+='		<div class="activities_right">';
								rideList	+='			<h3>'+ride_list[x].activity_title+'</h3>';
								rideList	+='			<p>'+ride_list[x].activity_stitle+'</p>';
								rideList	+='		</div>';
								rideList	+='	</div>';
								rideList	+='	<div class="activities_end clr">';
								rideList	+='		<ul>';
								rideList	+='			<li class="on">'+ride_list[x].start_time+'</li>';
								rideList	+='			<li class="add">已报名'+ride_list[x].sign_number+'人</li>';
								rideList	+='		</ul>';
								if(ride_list[x].is_enroll == 1){
									rideList	+='		<a href="#">您已报名</a>';
								}else{
									rideList	+='		<a href="#">我要报名</a>';
								}
								rideList	+='	</div>';
								rideList	+='</dd>';
							}
						}else{
							if(page == 0){
								rideList	+=	'<div id="is_null" style="text-align:center;margin-top:20px;">该景区没有活动</div>';
							}
						}
						if(ride_list_length < 10){
							$('#list').remove();
						}
					}else{
						rideList	+=	'<div id="is_null"></div>';
					}
					$('#newList').append(rideList);
				},
				error:function(){
				}
			})
		}
		function details(url){
			location.href = "{pigcms{:U('inside_activity_details')}"+"&activity_id="+url;
		}
	</script>
<include file="Public/scenic_footer"/>