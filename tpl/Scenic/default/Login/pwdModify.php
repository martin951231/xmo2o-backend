<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta charset="utf-8">
<title>小猪cms</title>
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
                                <li class="on">
                                    <h2>2</h2>
                                    <p>重置密码</p>
                                </li>
                                <li>
                                    <h2>3</h2>
                                    <p>完成</p>
                                </li>
                            </ul>
                        </div>
                        <div class="verify_end">
                            <div class="clr ver_list mb50">
                                <span class="fl">新密码</span>
                                <div class="p73">
                                    <div class="ver_input">
                                        <input id="password" type="text" placeholder="请输入新密码">
                                    </div>
                                </div>
                            </div>
                            <div class="clr ver_list">
                                <span class="fl">确认密码</span>
                                <div class="p73">
                                    <div class="ver_input">
                                        <input id="password_s" type="text" placeholder="请输入确认密码">
                                    </div>
                                </div>
                            </div>
                            <div id="submit" class="next">下一步</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
<include file="Public/footer"/>
<script type="text/javascript">
	var islock=false;
	$("#submit").click(function(){
		var newpwd	=	$("#password").val();
		if(!newpwd){
			alert('新密码不能为空');
			return false;
		}
		var new_pwd	=	$("#password_s").val();
		if(!new_pwd){
			alert('确认密码不能为空');
			return false;
		}
		if(newpwd != new_pwd){
			alert('新密码和确认密码必须一样');
			return false;
		}
		if(islock==false){
			islock	=	true;
			$.ajax({
				type : "post",
				url : "{pigcms{:U('Login/pwdModifying')}&pm={pigcms{$pm}",
				dataType : "json",
				data:{
					newpwd	:	newpwd,
					new_pwd	:	new_pwd,
				},
				async:false,
				success : function(data){
					if(!data.error_code){
						window.location.href='{pigcms{:U("Login/pwd_success")}';
						return false;
					}else{
						islock=false;
						alert(data.msg);
						return false;
					}
				},
				error:function(){
					islock=false;
					alert('发送失败，请重试！！');
					return false;
				}
			})
		}
		return false;
	})
</script>