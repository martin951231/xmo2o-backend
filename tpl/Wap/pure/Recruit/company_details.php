<!DOCTYPE html>
<html lang="zh-CN">
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
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/mui.min.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">  
    <script src="{pigcms{$static_path}js/mui.min.js"></script>
    <script src="{pigcms{$static_path}js/mui.zoom.js"></script>
    <script src="{pigcms{$static_path}js/mui.previewimage.js"></script> 
    
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}/recruit/js/recruit.js"></script> 
    <title>企业详情</title> 
    <style> 
    	body{
    		padding: 0;
    		margin: 0;
    	}
    	.center{
    		text-align: center;
    	}
    	.indexDetailsBox{
    		/*background: #06c1ae;*/ 
    		padding: 20px 0;
    	}
    	.indexDetailsTop{
    		background: #fff;
    		box-shadow:0 2px 20px 0px #eee;  
    		margin: 20px;
    		padding:50px 20px 20px 20px;
    		border-radius: 5px; 
    		position: relative;
    	}
    	.indexDetailsTopPhoto{
    		width: 60px;
    		height: 60px;
    		position: absolute;
    		left: 50%;
    		margin-left: -30px;
    		top:-23px; 
    	}
    	.indexDetailsTopPhoto img{
    		width: 60px;
    		height: 60px;
    	}
    	.indexDetailsRecruit .weui-flex__item{
    		border-right: 1px solid #eee;
    		margin: 20px 0 0 0;
    		color: #999;
    	}
    	.indexDetailsRecruit .weui-flex__item:last-child{
    		border-right: none;
    	}
    	.indexDetailsRecruit .weui-flex__item .p1 label{
    		font-weight: bold;
    		color: #000;
    	}
    	.indexDetailsTopInfo p{
    		color: #999;
    	}
    	.indexDetailsTopInfo p label{
    		margin-left: 10px;
    	}
    	.indexDetailsTil{
    		padding: 0 20px;
    		font-weight: bold;
    		margin:10px 0;
    	}
    	.indexDetailsBlock .p1{
    		padding: 10px 0 0 0;
    	}
    	.indexDetailsBlock .p1 span{
    		padding-right: 10px;
    	}
    	.indexDetailsBlock .p1 label{
    		margin-left: 10px;
    	}
    	.indexDetailsBlock .p2{
    		padding: 10px 0;
    	}
    	.indexDetailsBlock .p2 span{
    		border: 1px solid #6382ca;
    		color: #6382ca;
    		margin-right: 5px;
    		padding: 0 5px;
    	}
    	.indexDetailsBlock .address_lab{
    		color: #999;
    		position: absolute;
    		right: 10px;
    		top:20px;
    	}
    	.indexDetailsBlock .chat_lab{
    		position: absolute;
    		bottom: 25px;
    		right: 20px;
    		padding: 2px 10px;
    		border: 1px solid #ff552e;
    		color: #ff552e;
    		border-radius: 5px;
    	}
    	.indexDetailsBlock{
    	  border-bottom: 1px solid #f5f5f5; 
    	}
    	.weui-media-box:before{
    		border-top: 1px solid #fff; 
    		height: 0!important; 
    	}
    	.compaybox{
    		padding: 10px 0;
    	}
        
                .mui-preview-image.mui-fullscreen {
            position: fixed;
            z-index: 999;
            background-color: #000;
        }
        .mui-preview-header,
        .mui-preview-footer {
            position: absolute;
            width: 100%;
            left: 0;
            z-index: 10;
        }
        .mui-preview-header {
            height: 44px;
            top: 0;
        }
        .mui-preview-footer {
            height: 50px;
            bottom: 0px;
        }
        .mui-preview-header .mui-preview-indicator {
            display: block;
            line-height: 25px;
            color: #fff;
            text-align: center;
            margin: 15px auto 4;
            width: 70px;
            background-color: rgba(0, 0, 0, 0.4);
            border-radius: 12px;
            font-size: 16px;
        }
        .mui-preview-image {
            display: none;
            -webkit-animation-duration: 0.5s;
            animation-duration: 0.5s;
            -webkit-animation-fill-mode: both;
            animation-fill-mode: both;
        }
        .mui-preview-image.mui-preview-in {
            -webkit-animation-name: fadeIn;
            animation-name: fadeIn;
        }
        .mui-preview-image.mui-preview-out {
            background: none;
            -webkit-animation-name: fadeOut;
            animation-name: fadeOut;
        }
        .mui-preview-image.mui-preview-out .mui-preview-header,
        .mui-preview-image.mui-preview-out .mui-preview-footer {
            display: none;
        }
        .mui-zoom-scroller {
            position: absolute;
            display: -webkit-box;
            display: -webkit-flex;
            display: flex;
            -webkit-box-align: center;
            -webkit-align-items: center;
            align-items: center;
            -webkit-box-pack: center;
            -webkit-justify-content: center;
            justify-content: center;
            left: 0;
            right: 0;
            bottom: 0;
            top: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            -webkit-backface-visibility: hidden;
        }
        .mui-zoom {
            -webkit-transform-style: preserve-3d;
            transform-style: preserve-3d;
        }
        .mui-slider .mui-slider-group .mui-slider-item img {
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 100%;
        }
        .mui-android-4-1 .mui-slider .mui-slider-group .mui-slider-item img {
            width: 100%;
        }
        .mui-android-4-1 .mui-slider.mui-preview-image .mui-slider-group .mui-slider-item {
            display: inline-table;
        }
        .mui-android-4-1 .mui-slider.mui-preview-image .mui-zoom-scroller img {
            display: table-cell;
            vertical-align: middle;
        }
        .mui-preview-loading {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            display: none;
        }
        .mui-preview-loading.mui-active {
            display: block;
        }
        .mui-preview-loading .mui-spinner-white {
            position: absolute;
            top: 50%;
            left: 50%;
            margin-left: -25px;
            margin-top: -25px;
            height: 50px;
            width: 50px;
        }
        .mui-preview-image img.mui-transitioning {
            -webkit-transition: -webkit-transform 0.5s ease, opacity 0.5s ease;
            transition: transform 0.5s ease, opacity 0.5s ease;
        }
        @-webkit-keyframes fadeIn {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }
        @keyframes fadeIn {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }
        @-webkit-keyframes fadeOut {
            0% {
                opacity: 1;
            }
            100% {
                opacity: 0;
            }
        }
        @keyframes fadeOut {
            0% {
                opacity: 1;
            }
            100% {
                opacity: 0;
            }
        }
    </style>
</head>

<body> 
   <div class="indexDetailsBox" style="    background: #fff;">
   	  <div class="indexDetailsTop">
   	  	 <div class="indexDetailsTopPhoto">
   	  	 	<img src="{pigcms{:file_domain().$company_detail['company_pic'][0]}" />
   	  	 </div>
   	  	 <div class="center indexDetailsTopInfo">
            <!-- <h4>{pigcms{$company_detail['legal_person_name']}</h4> -->
   	  	 	<p>{pigcms{$company_detail['company_name']}</p>
   	  	 </div>
   	  	 <!-- <div class="weui-flex indexDetailsRecruit center">
			  <div class="weui-flex__item">
			  	 <p class="p1"><label>35</label>%</p>
			  	 <p>近7天回复</p>
			  </div>
			  <div class="weui-flex__item">
			  	 <p class="p1"><label>35</label>%</p>
			  	 <p>简历反馈率</p>
			  </div>
			  <div class="weui-flex__item">
			  	 <p class="p1"><label>35</label>%</p>
			  	 <p>平均响应时间</p>
			  </div> 
		</div> -->
   	  </div>
   	  
   	  <!--在招职位-->
   	  <div class="nowJob">
   	  	 <div class="indexDetailsTil">
   	  	 	在招职位（{pigcms{$position_count}）
   	  	 </div>
   	  	 <volist name="position_list" id="vo">
            <a href="{pigcms{:U('position_detail',array('position_id'=>$vo['position_id']))}" class="weui-media-box weui-media-box_appmsg indexDetailsBlock link" data-url="{pigcms{:U('position_detail',array('position_id'=>$vo['position_id']))}"> 
                <div class="weui-media-box__bd">
                <label class="address_lab">{pigcms{$vo['province']} {pigcms{$vo['city']} {pigcms{$vo['area']}</label>
                <if condition="$config['is_im']">
                <label class="chat_lab link newWin" data-url="{pigcms{:U('My/go_im',array('hash'=>'group_user'.$company_detail['uid'],'title'=>urlencode($company_detail['nickname'])))}">
               聊聊</label>
                </if>
                <h4 class="weui-media-box__title">{pigcms{$vo['position_name']}</h4>
                <p class="weui-media-box__desc p1">
                    <span  style="color:#ff552e">{pigcms{$vo['salary']}</span>
                     | 

                    <volist name="vo['welfare_text']" id="welfare">
                        <label>{pigcms{$welfare}</label>
                    </volist>
                </p>
                <div class="weui-media-box__desc p2">
                    <?php if ($vo['work_years']): ?>
                        <span>{pigcms{$vo['work_years']}</span>
                    <?php endif ?>
                    <?php if ($vo['qualifications']): ?>
                    <span>{pigcms{$vo['qualifications']}</span>
                    <?php endif ?>
                </div>
                </div>
            </a>
         </volist>
   	  </div>
   	  <!--在招职位 end-->
   	  
   <!-- 	  <div class="compaybox">
   	  	 <div class="indexDetailsTil">
   	  	 	公司介绍
   	  	 </div>
   	  	 <p style="color: #999; text-indent: 2em; padding:0 20px;"> 
   	  	 	你可以和其他在线的人聊天。你可以和其他在线的人聊天。你可以和其他在线的人聊天。你可以和其他在线的人聊天。你可以和其他在线的人聊天。你可以和其他在线的人聊天。你可以和其他在线的人聊天。
   	  	 </p>
   	  </div>  -->
   	  
        <div class="compaybox">
            <div class="indexDetailsTil">
            	公司地址
            </div>
            <p style="color: #999;   padding:0 20px;"> 
            	{pigcms{$company_detail['province']}{pigcms{$company_detail['city']}{pigcms{$company_detail['area']}{pigcms{$company_detail['address']} {pigcms{$company_detail['detail']}
                <volist name="company_detail['company_pic']" id="vo">
                    <img src="{pigcms{:file_domain().$vo}" style="max-width:100%" data-preview-src="" data-preview-group="2">
                </volist>

			</p>
        </div>   	  
   </div>     
   <script> 
       mui.previewImage();
   </script>
</body>
</html>