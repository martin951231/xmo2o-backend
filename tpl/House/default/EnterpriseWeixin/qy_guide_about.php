<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .guide-about .regster-view{
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-align: center;
        -ms-flex-align: center;
        align-items: center;
        float: left;
        height: 100%;
        width: calc(50% - 1px);
        text-align: center;
    }
    .guide-about .register-content {
        width: 100%;
    }
    .guide-about .title {
        font-size: 20px;
        font-weight: 600;
        color: #1890ff;
    }
    .guide-about .desc {
        font-size: 16px;
        font-weight: 400;
        color: #666;
        margin: 50px 0 87px 0;
    }
</style>
<div class="guide-about-page-content" style="background-color: white;">
    <div class="guide-about" style="background-color: rgb(255, 255, 255); box-shadow: rgba(0, 0, 0, 0.12) 0px 8px 16px; width: 100%; height: 100%;">
        <div class="regster-view" style="border-right: 1px solid rgb(240, 240, 240);">
            <div class="register-content">
                <div class="title">
                    还没有企业微信？
                </div>
                <div class="desc">
                    注册包含物业应用的企业微信——用企微，连接业主
                </div>
                <a href="javascript:;" class="qyClick" data-type="register" data-randomnumber="{pigcms{$qyRegisterInfo['qyRegisterId']}" data-url="{pigcms{$qyRegisterInfo['url']}">
                    <img src="https://open.work.weixin.qq.com/service/img?id=ww21001813f8f04566&t=register&c=blue&s=large" srcset="https://open.work.weixin.qq.com/service/img?id=ww21001813f8f04566&t=register&c=blue&s=large@2x 2x" referrerpolicy="unsafe-url" alt="企业微信">
                </a>
            </div>
        </div>
        <div class="regster-view">
            <div class="register-content">
                <div class="title">
                    已经有企业微信？
                </div>
                <div class="desc">
                    极速安装物业应用到企业微信——用企微，连接业主
                </div>
                <a href="javascript:;" class="qyClick" data-type="install" data-randomnumber="{pigcms{$qyInstallInfo['qyInstallId']}" data-url="{pigcms{$qyInstallInfo['url']}">
                    <img src="https://open.work.weixin.qq.com/service/img?id=ww21001813f8f04566&t=install&c=white&s=large" srcset="https://open.work.weixin.qq.com/service/img?id=ww21001813f8f04566&t=install&c=white&s=large@2x 2x" referrerpolicy="unsafe-url" alt="企业微信">
                </a>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

    var login_timer;
    $('.qyClick').click(function () {
        var type = $(this).data('type');
        var url = $(this).data('url');
        var randomNumber = $(this).data('randomnumber');
        console.log(type, randomNumber, url);
        login_timer=setInterval(function () {
            run(type, randomNumber);
        },3000);
        window.open(url);
    });
    function run(type, randomNumber) {
        $.ajax({
            url: '{pigcms{:U('ajaxGyGuideAbout')}',
            type: 'POST',
            dataType: 'json',
            data: {type: type,randomNumber: randomNumber},
            success:function(data){
                console.log('data', data)
                var list = data.list;
                console.log('type', type)
                if (type=='install') {
                    console.log('1install', list.msg)
                    console.log('2install', list.install)
                    console.log('3install', list.ticket)
                    console.log('4install', list.jump_url)
                    if (list.msg) {
                        layer.msg(list.msg);
                        setTimeout(function(){
                            window.top.GenerateRoutes();
                        },2000);
                    } else if (list.install) {
                        console.log('5-install', list.jump_url)
                        if (list.jump_url) {
                            window.top.GenerateRoutes();
                        }
                    }
                } else if (type=='register') {
                    console.log('1register', list.msg)
                    console.log('2register', list.register)
                    console.log('4register', list.jump_url)
                    if (list.msg) {
                        layer.msg(list.msg);
                        setTimeout(function(){
                            window.top.GenerateRoutes();
                        },2000);
                    } else if (list.register) {
                        console.log('5-register', list.jump_url)
                        if (list.jump_url) {
                            window.top.GenerateRoutes();
                        }
                    }
                }
            }
        });
    }

</script>
<include file="Publics:footer"/>
