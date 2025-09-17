<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8"/>
        <title>{pigcms{$config['house_name']}列表</title>
        <meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
        <meta http-equiv="pragma" content="no-cache"/>
        <meta name="apple-mobile-web-app-capable" content="yes"/>
        <meta name='apple-touch-fullscreen' content='yes'/>
        <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
        <meta name="format-detection" content="telephone=no"/>
        <meta name="format-detection" content="address=no"/>
        <link href="{pigcms{$static_path}village_list/css/pigcms.css" rel="stylesheet"/>
        <style>
            .ui-list a{    margin-left: 0px !important;padding: 0.25rem 0.2rem 0.25rem 0.2rem !important;}
            .choice_list li h2 {    background: unset !important;}
        </style>
    </head>
    <body>
        <section class="choice">
            <div class="input">
            	<div class="input_n">
                <input type="text" class="village-key"/>
                </div>
            </div>
            
            <div class="choice_list load_village_search">
                <ul>
                	<volist name='village_list' id='row'>
                    <li class="ui-list">
                        <a href="javascript:viod(0);" class="link-url" data-url="{pigcms{:U('empty_village_unit_list',array('village_id'=>$row['village_id']))}">
                            <div style="align-items: center; display: flex;"> 
                                <img class="avater" src="<if condition="$row['village_logo']">{pigcms{:thumb($row['village_logo'], 96)}<else/>{pigcms{$static_public}images/wap_house/village_logo.png</if>"  style=" width: 0.8rem; height: 0.8rem;  margin-right: 10px;"/>
                                <div style="flex:1">
                                    <h2>{pigcms{$row['village_name']}</h2>
                                    <p>{pigcms{$row['village_address']}</p>
                                </div>
                            </div>

                        </a>
                    </li>
                    </volist>
                    
                   
                </ul>
            </div>
        </section>
        <script src="{pigcms{$static_path}js/jquery-1.8.3.min.js"></script>
        <script src="{pigcms{$static_path}village_list/js/common.js"></script>
        
        <script type="text/html" id="load_village_search_view">
			{{# for(var i = 0, len = d.length; i < len; i++){ }}
			<li class="ui-list">
                <a href="/wap.php?g=Wap&c=House&a=empty_village_unit_list&village_id={{ d[i].village_id}}">

                    <div style="align-items: center; display: flex;">
                        <img class="avater" src="{{ d[i].village_logo }}"  style=" width: 0.8rem; height: 0.8rem;  margin-right: 10px;">
                        <div style="flex:1">
                            <h2>{{ d[i].village_name }}</h2>
                            <p>{{ d[i].village_address}}</p>
                        </div>
                    </div>
                </a>
			</li>
			{{# } }}
		</script>
		
        
        <script type="text/javascript">
			$(".village-key").on('blur' , function(){
				var village_key = $(this).val();
				if(village_key.length<2){
					return false;	
				}else{
					//修改列表小区显示
					$.post("{pigcms{:U('ajax_empty_village_list')}" , {village_key:village_key} , function(dataVal){
						
						var tpl = $("#load_village_search_view").html(); //读取模版
						laytpl(tpl).render(dataVal, function(html){
						  $(".load_village_search ul").html(html);
						});
							
					},"json");	
				}
				
				//document.write(village_key.length);	
			});
			$(".village-key").on('keyup' , function(){
				var village_key = $(this).val();
				if(village_key.length<1){
					//修改列表小区显示
					$.post("{pigcms{:U('ajax_empty_village_list')}" , {village_key:""} , function(dataVal){
						
						var tpl = $("#load_village_search_view").html(); //读取模版
						laytpl(tpl).render(dataVal, function(html){
						  $(".load_village_search ul").html(html);
						});
							
					},"json");	
				}else{
					//修改列表小区显示
					$.post("{pigcms{:U('ajax_empty_village_list')}" , {village_key:village_key} , function(dataVal){
						
						var tpl = $("#load_village_search_view").html(); //读取模版
						laytpl(tpl).render(dataVal, function(html){
						  $(".load_village_search ul").html(html);
						});
							
					},"json");	
				}
				
				//document.write(village_key.length);	
			});
			
		</script>
        
        
    </body>
</html>
<script>
    $(".choice_list").css({"height":$(window).height()-115*per,"overflow-y": "auto","-webkit-overflow-scrolling" : "touch" });
	
	$(".input_n input").focus(function(){
        $(".input_n").addClass("on");
    })
	
</script>