<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta charset="utf-8">
<title>密码找回成功</title>
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
                    <div class="fl tryto">密码找回成功</div>
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
                                <li class="on">
                                    <h2>3</h2>
                                    <p>完成</p>
                                </li>
                            </ul>
                        </div>
                        <div class="verify_end text_cen">
                            <div class="text">
                                <h2>新密码设置成功！</h2>
                                <div id="submit" class="well">请牢记您新设置的密码。<a href="javascript:void(0)">点击登录</a></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
<include file="Public/footer"/>
<script type="text/javascript">
	$("#submit").click(function(){
		$(".board").show();
		$(".mark").show();
	})
</script>