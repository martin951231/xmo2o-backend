<html xmlns="http://www.w3.org/1999/xhtml"><head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{pigcms{$$static_path}{pigcms{:L_("电脑版聊天登录")} - {pigcms{$config.site_name}</title>
    <if condition="$config['site_favicon']">
        <link rel="shortcut icon" href="{pigcms{$config.site_favicon}"/>
    </if>
    <link rel="stylesheet" type="text/css" href="//at.alicdn.com/t/font_1016180_jlx05xsvtif.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$config.site_url}/tpl/Merchant/default/static/login/new/login.css?t=1571363561">
    <script type="text/javascript" src="{pigcms{$config.site_url}/tpl/Merchant/default/static/js/jquery.min.js"></script>
    <script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang=chinese"></script>
    <script type="text/javascript">
        var oldmd5langkey = localStorage.getItem('oldmd5langkey');
        if(typeof md5langkey != 'undefined'){
            if(oldmd5langkey != md5langkey && oldmd5langkey != null){
                localStorage.removeItem(oldmd5langkey);//去掉旧的，防止溢出
            }
            localStorage.setItem('oldmd5langkey',md5langkey);
            var _lang = localStorage.getItem(md5langkey);
            if(_lang == null){
                $.post("./appapi.php?c=Lang&a=js",{lang:'chinese'},function(data){
                    localStorage.setItem(data.key,data.lang);
                    _lang = JSON.parse(data.lang);
                },'json');
            }
            _lang = JSON.parse(_lang);
        }
        function L_(str){
            str = $.trim(str);
            if(typeof _lang == 'undefined'){
                return str;
            }
            if(str == ''){
                return str;
            }else if(typeof _lang[str] != 'undefined'){
                return _lang[str] != '' && _lang[str] ? _lang[str] : str;
            }else{
                $.post('./appapi.php?c=Lang&a=add',{key:str},function(data){});
                return str;
            }
        }
    </script>
    <style type="text/css">
        #change_lang{
            border-radius:3px !important;
            padding : 3px 4px;
            height: 30px;
            box-shadow:none !important;
            color:#858585;
            background-color:#fff;
            border:1px solid #d5d5d5;
        }

    </style>
    <link rel="stylesheet" href="{pigcms{$config.site_url}/static/layer/skin/default/layer.css?v=3.0.11110" id="layuicss-skinlayercss"></head>
<body>
<div id="app">
    <div class="agreement" data-v-12bb8c1f="">
        <div class="pubHeader">
            <div class="pubWrap clearfix">
                <div class="fl">
                    <a href="{pigcms{$config.site_url}" class="logoicon mr10 fl" style="background-image:url({pigcms{$config.site_logo})" target="_blank"></a>
                    <span class="pl30 register">{pigcms{:L_("客服登录")}</span>
                </div>
                <if condition="$config['is_demo_domain']">
                    <div class="fr" style="color:#999;font-size:12px;margin-left:20px;">
                        {pigcms{:L_("小猪O2O致力于为快速构建本地生活服务平台提供专业的解决方案")}
                    </div>
                </if>
            </div>
        </div>
        <div class="login-form" style="top: 218px;">
            <div class="login-tab login-tab-l" style="width: 100%">
                <a href="javascript:void(0)" class="checked"> 扫码登录</a>
            </div>

            <div class="qrcode-login" style="display:block;visibility:visible;">
                <div class="mc">
                    <div class="qrcode-error-2016">
                        <div class="qrcode-error-mask"></div>
                        <p class="err-cont">{pigcms{:L_("二维码已失效")}</p>
                        <a href="javascript:void(0)" class="refresh-btn" style="outline: rgb(109, 109, 109) none 0px;">{pigcms{:L_("刷新")}</a>
                    </div>
                    <div class="qrcode-main">
                        <div class="qrcode-img">
                            <img src="{pigcms{$static_public}images/impc_qrcode.jpg" alt="" id="wx_qrcode"/>
                            <div class="qrcode-error02 hide" id="J-qrcoderror" style="display: none;">
                                <a href="#none">
                                    <span class="error-icon"></span>
                                    <div class="txt">{pigcms{:L_("网络开小差咯")}<span class="ml10">{pigcms{:L_("刷新二维码")}</span></div>
                                </a>
                            </div>
                        </div>
                        <div class="qrcode-help" style="display:none;"></div>
                    </div>
                    <div class="qrcode-panel">
                        <ul>
                            <li class="fore1">
                                <span>{pigcms{:L_("打开")}</span>
                                <a href="http://weixin.qq.com" target="_blank" clstag="pageclick|keycount|201607144|9">
                                    <span class="red">{pigcms{:L_("手机微信")}</span>
                                </a>
                            </li>
                            <li>{pigcms{:L_("扫描二维码")}</li>
                        </ul>
                    </div>
                    <div class="coagent qr-coagent" id="qrCoagent">
                        <ul>
                            <li><b></b><em>{pigcms{:L_("免输入")}</em></li>
                            <li><b class="faster"></b><em>{pigcms{:L_("更快")}&nbsp;</em></li>
                            <li><b class="more-safe"></b><em>{pigcms{:L_("更安全")}</em></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="coagent" id="kbCoagent">

            </div>
        </div>
    </div>
</div>
<div class="copyright">
    Copyright &copy; {pigcms{:date('Y')} {pigcms{$config.site_name}{pigcms{$config.top_domain}{pigcms{:L_("版权所有")} <if condition="!empty($config['site_icp'])"><a href="http://www.miibeian.gov.cn/" target="_blank">{pigcms{$config.site_icp}</a></if>
</div>
<if condition="$_SESSION['system']">
    <div class="system_page_error"></div>
</if>
<script type="text/javascript" src="./tpl/Merchant/default/static/js/jquery.min.js"></script>
<script type="text/javascript" src="./static/layer/layer.js"></script>

<script type="text/javascript">
    var qrcode_id = 0;
    var wx_qrcode_url = $('#wx_qrcode').attr('src');
    $('.qrcode-error-2016 .refresh-btn').click(function () {
        $('#wx_qrcode').attr('src', wx_qrcode_url);
        $('.qrcode-error-2016').hide();
        $('.system_page_error').empty().attr('style', '');
        $.post('./index.php?g=Index&c=Recognition&a=see_login_qrcode&qrcode_type=new_im_kefu&content_type=json', function (result) {
            if (result.status == 1) {
                $('#wx_qrcode').attr('src', result.info.ticket);
                qrcode_id = result.info.id;
                setTimeout(function () {
                    ajax_weixin_login();
                }, 2500);
            } else {
                $('.qrcode-error-2016').show().find('.err-cont').html(L_('服务器异常'));
                $('.system_page_error').html('<span>' + L_('服务器返回：') + '</span>' + result.info);
            }
        });
    });
    $('.qrcode-error-2016 .refresh-btn').trigger('click');

    function ajax_weixin_login() {
        $('.system_page_error.login_tip').remove();
        $.get("./index.php?c=Login&a=ajax_im_weixin_login&longhttp=false", {qrcode_id: qrcode_id}, function (result) {
            if (result.error_code == 'ok') {
                //登录成功跳转
                setTimeout(function(){
                    location.href = "{pigcms{:U('Kefu/index')}"
                },1000);
            } else {
                setTimeout(function () {
                    ajax_weixin_login();
                }, 2500);
            }
        });
    }
</script>


</body></html>