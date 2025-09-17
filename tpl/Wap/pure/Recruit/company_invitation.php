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
	    <title>企业管理简历</title>
	    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
	    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/style.css">
	    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
	    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
	    <script src="{pigcms{$static_public}activity/assets/js/common.js" type="text/javascript" charset="UTF-8"></script>
	    <script type="text/javascript">var now_sex=0,now_years=0;</script>
	    <script src="{pigcms{$static_path}recruit/js/dropdown.js" type="text/javascript"></script>
	    <style type="text/css">
	        .header a{
	            font-size: 15px;
	        }
	        .header .cur a{
	            color: #06c1ae;
	            font-size: 15px;
	        }
	        .header .cur{
	            border-bottom: 2px solid #06c1ae;
	            color: #06c1ae;
	        }
		    .header span{
		        line-height: 47px;
		        margin-left: 12px;
		        padding: 3px 14px;
		    }
		    body{
		    	background: #f4f4f4;
		    }
        .message_last{font-size: 14px;}
        .weui-media-box_appmsg .weui-media-box__hd.phone{margin-top: -25px;margin-left: 10px;margin-right: 10px;}
        .phone img{width: 40px;height: 40px;}
        .time{font-size: 14px;color: #999;white-space: nowrap;line-height: 2;margin-left: -80px;}
	    </style>
	</head>
	<body> 
	    <div class="weui-panel weui-panel_access header" style="height: 40px;text-align: center;">
	        <div class="weui-panel__bd" style="height: 40px;">
	            <span>
	                <a href="{pigcms{:U('my_resume_receive')}">投递</a>
	            </span>
	              
	            <span class="cur">
	              <a href="{pigcms{:U('company_invitation')}">邀约</a>
	            </span>
	        </div>
	    </div>
		<div class="weui-panel weui-panel_access content">
		 	<div class="weui-panel__bd">
             </div>
		 </div>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}recruit/css/dropload.css"> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/dropload.min.js"></script> 
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
    <script type="text/javascript">
        var ajax_my_resume_invitation_url = "{pigcms{:U('ajax_my_resume_invitation')}";
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
                    url: ajax_my_resume_invitation_url,
                    data:{
                      'type':2,
                      'page':page,
                      'pageSize':count
                    },
                    dataType: 'json',
                    success: function(data){   
                        var dateArr = data.result.list;
                        if(dateArr.length > 0){ 
                            for(var i=0;i<dateArr.length;i++){ 
                                result+='<div class="weui-media-box weui-media-box_appmsg">';
								result+='	<div class="weui-media-box__hd">';
								result+='		<img class="weui-media-box__thumb" src="'+dateArr[i].avatar+'" alt="">';
								result+='	</div>';
								result+='	<div data-url="'+resume_detail_url+'&resume_id='+dateArr[i].resume_id+'" class="weui-media-box__bd link newWin">';
								result+='		<h4 class="weui-media-box__title">'+dateArr[i].truename+'</h4>';
								// result+='		<span class="static_online">2</span>';
								result+='		<p class="resume_label">';
								if (dateArr[i].age) {
                                result+='           <label>'+dateArr[i].age+'岁</label>';
                                }
                                if (dateArr[i].work_years&&dateArr[i].work_years!=null) {
                                result+='           <label>'+dateArr[i].work_years+'</label>';
                                }
                                if (dateArr[i].qualifications&&dateArr[i].qualifications!=null) {
                                result+='           <label>'+dateArr[i].qualifications+'</label>';
                                }
								result+='		</p>';
								result+='		<p class="message_last">';
								result+='			<span>邀约职位：'+dateArr[i].position_name+'</span>';
                                    result+='       <p class="message_last"><span>邀约时间：</span>'+dateArr[i].invitation_time_format+'</p> ';
                                    result+='       <p class="message_last"><span>邀约备注：</span>'+dateArr[i].remark+'</p> ';
								
								result+='		</p> ';
								result+='	</div>';
								result+='</a>';
                                    result+='   <div class="weui-media-box__hd phone"><div class="time">'+dateArr[i].add_time_format+'</div><a href="tel:'+dateArr[i].phone+'"><img  src="{pigcms{$static_path}recruit/images/phone.png" /></a></div>';
                                    result+='   </div>';
                             
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
            // ,
   			// domDown:{
			// 	domClass : 'dropload-down',
			// 	domRefresh : '<div class="dropload-refresh">↑上拉加载更多</div>',
			// 	domLoad : '<div class="dropload-load">加载中...</div>',
			// 	domNoData : '<div class="dropload-noData">加载完毕</div>'
			// }
            
        }); 
    </script>
	</body>
	
</html>
