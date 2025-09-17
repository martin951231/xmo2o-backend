<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>修改昵称</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/weui.min.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<style type="text/css">
			.input-wrapper{
				padding: 10px 10px 0;
			}

			.input-wrapper .input-box{
				padding-bottom: 5px;
				border-bottom: 1px solid #efefef;
				font-size: 16px;
				color: #333;
			}

			.input-wrapper .input-box .desc{
				display: inline-block;
				width: 70px;
				text-align:justify;
				text-justify:inter-ideograph;
				height: 20px;
				overflow: hidden;
			}

			.input-wrapper .input-box .desc:after{
				content:'';
				display:inline-block;
			    padding-left:100%;  
			}

			.input-wrapper .input-box input{
				width: calc(100% - 90px);
				padding: 0 10px;
				
			}

			.input-wrapper .tip{
				font-size: 12px;
				color: #999;
				margin-left: 70px;
			}

			.btn-box{
				padding: 40px 10px 10px;
			}

			.btn-box button{
				width: 100%;
				border: none;
				outline: none;
				background: #ff532c;
				color: #fff;
				font-size: 16px;
				line-height: 2.5;
				border-radius: 5px;
			}

			.btn-box button:active{
				background: #fc3b0f;
			}
		</style>
	</head>
	<body>
		<form id="editform" onclick="return false;">
		
			<div class="input-wrapper">
				<div class="input-box"><span class="desc">昵称：</span><input class="nickname" type="text" name="name" placeholder="" value="{pigcms{$header.name}"></div>
				<!-- <div class="tip hidden"><i class="icon iconfont mgr5">&#xe615;</i>以英文字母或汉字开头，限定4-20个字符</div> -->
			</div>

			<div class="input-wrapper">
				<div class="input-box"><span class="desc">微信号：</span><input class="nickname" type="text" name="wx_number" placeholder="" value="{pigcms{$header.wx_number}"></div>
				<div class="tip hidden"><i class="icon iconfont mgr5">&#xe615;</i>以英文字母或汉字开头，限定4-16个字符</div>
			</div>
			
			<div class="btn-box"><button id="submit">保存</button></div>

		</form>


		<script src="{pigcms{$static_path}villagegroup/js/weui.min.js"></script>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript">
			$(function(){

				$('#submit').click(function(){
                   
					if($("input[name='name']").val() == ''){ 
						alertStr1("昵称不能为空！"); 
					// } else if ( !/^[\u4E00-\u9FA5A-Za-z].{3,19}$/.test($("input[name='name']").val())) {
					// 	alertStr("昵称要以英文字母或汉字开头，限定4-20个字符！")
					} else if ($("input[name='wx_number']").val() == '') {
						alertStr1("微信号不能为空！")
					}else{
						

						$.post("{pigcms{:U('editNickname')}",$('#editform').serialize(),function(data){
							if(data.status==1){
								alertStr("保存成功！",data.url);
								
							}else{   
								alertStr1("保存失败！"); 
							}
						},'json')
					}
					
				})
			})

			function alertStr(contentStr,url) {
				weui.alert(contentStr, {
				    title: '提示',
				    buttons: [{
				        label: '好的',
						onClick: function(){ 
							window.location.href=url  
						 }
				    }]
				});

				return false;
			}

			function alertStr1(contentStrl) {
				weui.alert(contentStrl, {
				    title: '提示',
				    buttons: [{
				        label: '好的',
						onClick: function(){ 
							
						 }
				    }]
				});

				return false;
			}


			 
		</script>
	</body>
	
</html>