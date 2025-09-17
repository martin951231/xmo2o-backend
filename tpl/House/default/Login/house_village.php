<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><if condition="$config.seo_title">{pigcms{$config.seo_title}<else/>智慧社区</if></title>
    <meta  name="keywords" content="<if condition="$config.seo_keywords">{pigcms{$config.seo_keywords}<else/>智慧社区</if>">
    <meta name="description" content="<if condition="$config.seo_description">{pigcms{$config.seo_description}<else/>智慧社区</if>">
    <link rel="stylesheet" href="{pigcms{$static_path}house_village/css/index.css">
</head>
<body>
    <div class="bg-img">
        <div class="top-title">
            <img src="{pigcms{:replace_file_domain($config['wechat_share_img'])}" alt="" class="login-icon" style="width: 60px;height: 60px;">
            <div class="btn" onclick="login()">登录管理后台</div>
        </div>
        <div class="center-box">
            <div class="title">{pigcms{$config.site_name}</div>
            <if condition="$user_bind_count gt 0">
            <div class="text">{pigcms{$user_bind_count}位业主已加入</div>
            <else/>
                <div class="text" style="height: 80px;"></div>
            </if>
            <div class="app-box">
                <if condition="$config.wechat_qrcode">
                    <div class="box">
                        <div class="left">
                            <img src="{pigcms{$static_path}house_village/images/wechat.png" alt="" class="app-img">
                            <div class="text1">微信端</div>
                        </div>
                        <img src="{pigcms{$static_path}house_village/images/QRcode.png" alt="" class="QRcode-img">
                    </div>
                </if>
                <if condition="$app_config['android_download_url'] || $app_config['ios_download_url']">
                    <div class="box1">
                        <div class="left">
                            <img src="{pigcms{$static_path}house_village/images/android.png" alt="" class="app-img">
                            <div class="text1">Android版</div>
                        </div>
                        <img src="{pigcms{$static_path}house_village/images/QRcode.png" alt="" class="QRcode-img">
                    </div>
                    <div class="box2">
                        <div class="left">
                            <img src="{pigcms{$static_path}house_village/images/apple.png" alt="" class="app-img">
                            <div class="text1">iPhone版</div>
                        </div>
                        <img src="{pigcms{$static_path}house_village/images/QRcode.png" alt="" class="QRcode-img">
                    </div>
                </if>
            </div>
        </div>
    </div>
    <div class="popup">
        <div class="popup-box">
            <img src="{pigcms{$config.wechat_qrcode}" alt="" class="popup-img">
        </div>
    </div>
    <div class="popup1">
        <div class="popup-box">
            <img src="{pigcms{$android_ios_url}" alt="" class="popup-img">
        </div>
    </div>
    <div class="popup2">
        <div class="popup-box">
            <img src="{pigcms{$android_ios_url}" alt="" class="popup-img">
        </div>
    </div>
	<div class="footer_txt" style="position:fixed;bottom:20px;text-align:center;">
		<if condition="$config['site_icp']">
			<a target="_blank" href="https://beian.miit.gov.cn/" style="display:inline-block;text-decoration:none;height:20px;line-height:20px;color:#939393;margin-right:30px;">备案号：{pigcms{$config.site_icp}</a>
		</if>
        <if condition="$config['village_lx_phone']">
            <span style="color:#bdb5b5;">联系电话：{pigcms{$config.village_lx_phone}</span>
        </if>
        <div>
		    {pigcms{:nl2br($config['site_show_footer'],'<a>')}
        </div>
	</div>
    <script src="{pigcms{$static_path}house_village/js/jquery-3.7.1.js"></script>
	<script src="{pigcms{$static_path}house_village/js/swiper.js"></script>
	<script src="{pigcms{$static_path}house_village/js/jquery.color.js"></script>
	<script>
		$(document).ready(function () {
			$('.popup,.popup1,.popup2').hide();
			
			$('.box').click(function () {
				$('.popup').removeClass('zoomOut').show().stop().addClass('animated  zoomIn').css({ animationDuration: "500ms" });
				$("html,body").css({ overflow: "hidden" })
            })
            $('.box1').click(function () {
                $('.popup1').removeClass('zoomOut').show().stop().addClass('animated  zoomIn').css({ animationDuration: "500ms" });
                $("html,body").css({ overflow: "hidden" })
            })
            $('.box2').click(function () {
                $('.popup2').removeClass('zoomOut').show().stop().addClass('animated  zoomIn').css({ animationDuration: "500ms" });
                $("html,body").css({ overflow: "hidden" })
            })
            $('.popup,.popup1,.popup2').click(function () {
                $('.popup,.popup1,.popup2').addClass('animated  zoomOut').removeClass('zoomIn');
                $('.popup,.popup1,.popup2').hide();
				$("html,body").css({ overflow: "auto" })
			})
		})

        function login(){
           window.location.href="{pigcms{:U('Login/new_login')}";
        }
	</script>
</body>
</html>