<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>{pigcms{:L_("街道/社区中心")} - {pigcms{$config.site_name}</title>
		<link rel="stylesheet" type="text/css" href="//at.alicdn.com/t/font_1016180_jlx05xsvtif.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}login/new/login.css?t={pigcms{:time()}"/>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
	</head>
    <style>
        .tip_box {
            padding: 20px;
            text-align: -webkit-center;
        }

        body::-webkit-scrollbar {
            display: none;
        }
        .tip_box .tip_img {
            width: 200px
        }
        .tip_box .tip_phone {
            line-height: 20px;
            padding-top: 20px;
            width: 100%;
            text-align: -webkit-center;
            font-size: 18px;
            font-weight: 500;
            color: black;
        }
        .tip_box .tip_phone .icon-img{
            height: 20px;
            margin-right: 10px;
        }
        .tip_box .tip_msg {
            padding: 20px 25px 0;
            font-size: 12px;
        }
        .tip_box .tip_msg .tip_title {
            margin-bottom: 2px;
        }
        .tip_phone_contact .tip_phone{
            margin-top: 40px;
            padding: 20px 0;
            background-color: #2589ff;
            color: white;
            border-radius: 10px;
        }
    </style>
	<body>
		<div id="app">
			<div class="tip_box">
                <if condition="$is_tip eq 1">
                    <div class="tip_all_contact">
                        <div class="tip_img">
                            <img src="{pigcms{$platform_property_qr_code}" width="100%">
                        </div>
                        <div class="tip_phone">
                            <img class="icon-img" alt="" src="{pigcms{$static_path}login/new/images/phone.png"/>
                            <span>{pigcms{$site_phone}</span>
                        </div>
                        <div class="tip_msg">
                            <div class="tip_title">注册成功,请耐心等待审核或联系工作人员审核。</div>
                            <div>如有疑问可通过上方扫码微信或电话号码联系工作人员</div>
                        </div>
                    </div>
                    <elseif condition="$is_tip eq 2"/>
                    <div class="tip_qr_contact">
                        <div class="tip_img">
                            <img src="{pigcms{$platform_property_qr_code}" width="100%">
                        </div>
                        <div class="tip_msg">
                            <div class="tip_title">注册成功,请耐心等待审核或联系工作人员审核。</div>
                            <div>如有疑问可通过上方扫码微信联系工作人员</div>
                        </div>
                    </div>
                    <elseif condition="$is_tip eq 3"/>
                    <div class="tip_phone_contact">
                        <div class="tip_phone">
                            <img class="icon-img" alt="" src="{pigcms{$static_path}login/new/images/phone_white.png"/>
                            <span>{pigcms{$site_phone}</span>
                        </div>
                        <div class="tip_msg">
                            <div class="tip_title">注册成功,请耐心等待审核或联系工作人员审核。</div>
                            <div>如有疑问可通过上方电话号码联系工作人员</div>
                        </div>
                    </div>
                </if>
			</div>
		</div>
    <script type="text/javascript">
        var is_tip = "{pigcms{$is_tip}";
        if (0==is_tip) {
            window.parent.close_all_page();
        }
        console.log('is_tip',is_tip);
    </script>
	</body>
</html>