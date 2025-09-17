<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8"/>
        <title>全部板块</title>
        <meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
        <meta http-equiv="pragma" content="no-cache"/>
        <meta name="apple-mobile-web-app-capable" content="yes"/>
        <meta name='apple-touch-fullscreen' content='yes'/>
        <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
        <meta name="format-detection" content="telephone=no"/>
        <meta name="format-detection" content="address=no"/>
        <link href="{pigcms{$static_path}css/app.css" rel="stylesheet"/>
        <link href="{pigcms{$static_path}css/whole.css" rel="stylesheet"/>
        <script src="{pigcms{$static_path}scripts/jquery-1.8.3.min.js"></script>
        <script src="{pigcms{$static_path}scripts/common.js"></script>
        <script src="{pigcms{$static_path}scripts/whole.js"></script>
    </head>
    <body>
        <view class="container">
            <div class="scrollList fl">
                <view class="leftNav">
                <volist name="cateory" id="ca">
                    <view>{pigcms{$ca.name}</view>
                  </volist>  
                </view>
            </div>

            <div class="contentList">
			<volist name="cateory" id='yihao' >
                <view class="rightnav">         
					<volist name="arr[$yihao['id']]" id="myarr" >
						<if condition="$myarr['key'] eq 5">
							<view class="level link-url" data-url="details.html">
								<view class="p123">
									<view class="h2">{pigcms{$myarr.name}</view>                     
								</view>
							</view>
						<else/>
							<view class="level link-url" data-url="{pigcms{:U('Mycircle/details',array('id'=>$myarr['id']))}">
								<view class="img fl">
									<img src="{pigcms{:file_domain()}/upload/adver/000/000/030/585c88be076d9.png"/>
								</view>
								<view class="p123">
									<view class="h2">{pigcms{$myarr.name}</view>
								</view>
						<?php if(in_array($myarr['id'],$circle_arr)){ ?>
						<view class="join">已加入</view>
					  <?php }else{ ?>
						 <view class="join myjoin" style="cursor:pointer;"  data="{pigcms{$myarr.id}">加入</view>
					  <?php } ?>
							</view>
						</if>
					</volist>
                </view>
			</volist>
           </div>
        </view>
		<script>
			var join_url = "{pigcms{:U('Mycircle/join_circle')}";
			$(function(){
				$('.myjoin').click(function(){
					var circle_id = $(this).attr('data');
				   $.post(join_url,{'id':circle_id},function(data){
					//alert(data.error);
					if(data.error == 1){
					alert (data.msg);
					window.location.reload(true); 
					}else{
					alert(data.msg);
					}
				  },'json');
				});
			});
        </script>
    </body>
</html>
