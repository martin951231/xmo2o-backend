<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta charset="utf-8">
<title>密码找回</title>
<link href="{pigcms{$static_path}css/style.css" rel="stylesheet"/>
<script src="{pigcms{$static_path}js/jquery-1.8.3.min.js"></script>
<!--[if lte IE 9]>
<script src="scripts/html5shiv.min.js"></script>
<![endif]-->
</head>
    <body>
        <section class="back">
            <div class="back_top">
                <div class="w1200 clr">
                    <div class="fl logo">
                        <a href="{pigcms{:U('Index/index')}"><img src="{pigcms{$config['site_logo']}"  width="187" height="50"></a>
                    </div>
                    <div class="fl tryto">找回密码</div>
                </div>
            </div>
            <div class="back_end">
                <div class="w1200">
                    <div class="verify">
                        <div class="verify_top">
                            <ul class="clr">
                                <li class="on">
                                    <h2>1</h2>
                                    <p>验证身份</p>
                                </li>
                                <li>
                                    <h2>2</h2>
                                    <p>重置密码</p>
                                </li>
                                <li>
                                    <h2>3</h2>
                                    <p>完成</p>
                                </li>
                            </ul>
                        </div>
	                    <div class="verify_end" id="one">
	                        <div class="clr ver_list">
	                            <span class="fl">手机号码</span>
	                            <div class="p73">
	                                <div class="ver_input">
	                                    <input id="phone" type="text" placeholder="请输入号码">
	                                </div>
	                                <!--<div class="clr ver_yz fs">
	                                    <div class="send box5 fl" id="commit">发送验证码</div>
	                                </div>-->
	                                <div class="clr ver_yz">
	                                    <div class="send box5 fl" id="commit">发送验证码</div>
	                                    <div class="time box5 fl" id="reciprocal" style="display:none;"><i id="reciprocal_son">60</i>秒</div>
	                                </div>
	                            </div>
	                        </div>
	                        <div class="clr ver_list">
	                            <span class="fl">验证码</span>
	                            <div class="p73">
	                                <div class="ver_input">
	                                    <input id="vfycode" type="text" placeholder="请输入验证码">
	                                </div>
	                            </div>
	                        </div>
	                        <div class="next" id="submitcommit">下一步</div>
	                    </div>
                    </div>
                </div>
            </div>
        </section>
        <script type="text/javascript">
        	var flage=false;
        	var islock=false;
            $("#commit").click(function(){
            	var commit	=	$("#commit").text();
            	if(commit == '已发送'){
					return false;
            	}
            	var phone =	$("#phone").val();
            	if(phone == ''){
					alert('手机号不能为空');
					return false;
            	}
            	$.ajax({
					type : "post",
					url : "{pigcms{:U('Login/Generate')}",
					dataType : "json",
					data:{
						phone	:	phone,
						vfycode	:	'',
						tmpid	:	0,
					},
					async:false,
					success : function(data){
						if(!data.error_code){
                			$("#reciprocal").show();
                			$("#commit").text('已发送');
                			$("#commit").css('background-color','#999');
							flage=data.id;
							Reciprocal();
							return false;
						}else{
							alert(data.msg);
							return false;
						}
					},
					error:function(){
						alert('发送失败，请重试！！');
					}
				})
            })
            $("#submitcommit").click(function(){
				var phone =	$("#phone").val();
            	if(phone == ''){
					alert('手机号不能为空');
					return false;
            	}
            	var vfycode =	$("#vfycode").val();
            	if(vfycode == ''){
					alert('验证码不能为空');
					return false;
            	}
            	if(islock){
					return false;
            	}
				islock=true;
				if(flage == false){
					flage	=	1;
				}
            	$.ajax({
					type : "post",
					url : "{pigcms{:U('Login/Generate')}",
					dataType : "json",
					data:{
						phone	:	phone,
						vfycode	:	vfycode,
						tmpid	:	flage,
					},
					async:false,
					success : function(data){
						if(data.error_code == 2){
							window.location.href="{pigcms{:U('Login/pwdModify')}"+'&pm='+data.urlpm;
						}else{
							islock=false;
							alert(data.msg);
							return false;
						}
					},
					error:function(){
						islock=false;
						alert('发送失败，请重试！！');
					}
				})
				return false;
			})
			function Reciprocal(){
				$("#reciprocal_son").parent('.btn').removeClass('noact');
				var inttmp=window.setInterval(function(){
					num = $("#reciprocal_son").text();
					num = parseInt(num);
					$("#reciprocal_son").text(num-1);
					if(num==1){
						$("#reciprocal_son").parent('.btn').addClass('noact');
						//flage=0;
						$("#commit").val('重发送短信验证').prop('disabled',false);
						window.clearInterval(inttmp);
						setTimeout(function(){
                			$("#commit").css('background-color','#fe9900');
							$("#commit").text('发送短信验证');
                			$("#reciprocal").hide();
							$("#reciprocal_son").text(60);
						}, 1000);
					}
				},1000);
			}
        </script>
<include file="Public/footer"/>