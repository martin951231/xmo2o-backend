<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8"/>
    <title>访客开门密码</title>
    <meta name="viewport"
          content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name='apple-touch-fullscreen' content='yes'/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="format-detection" content="address=no"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/village.css?211"/>
    <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}js/jquery.cookie.js?210" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
</head>
<style>
    .open {
        margin-top: 30%;
        padding: 20px 20% 40px 20%;
    }

    .erweima {
        padding: 10px 0px;
        text-align: center;
    }

    .erweima img {
        width: 250px;
        height: 250px
    }

    .open p {
        text-align: center
    }

    .open span {
        font-weight: bold;
    }
    .erweima{
        position: fixed;
        left: 50%;
        top: 43%;
        bottom: auto;
        -webkit-transform: translate(-50%, -50%);
        transform: translate(-50%, -50%);
        width: 274px;
        box-sizing: border-box;
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
        background: transparent;
        -webkit-transition: -webkit-transform .3s;
        transition: -webkit-transform .3s;
        transition: transform .3s;
        transition: transform .3s, -webkit-transform .3s;
    } 
    .pwdInput span{
        width: 30px;
        height: 40px;
        line-height: 40px;
        padding: 0 2px; 
        background: rgb(240,240,240);
        text-align: center;
        font-size: 20px;
        border: 1px solid rgba(112,112,112,1);
        border-radius: 6px;
        margin: 0 5px;
        display: block;
        float: left; 
        -webkit-text-security:disc;
        text-security:disc; 
    } 
    .pwdInput{
        position: relative; 
        width: 284px;
        margin: 90px auto 10px auto;
    }
    .clear{
        clear:both;
    }
    .copyPwd{
        font-size:16px;
        text-align:center;
        padding:10px 0;
        color:#06c1ae;
        display: none; 
        border:none;
        background:none; 
        margin:0 auto
    } 
    .gantan{
        padding-left:30px;
        background:url('{pigcms{$static_path}images/gantan.png') center left no-repeat;
        background-size:20px auto;
        width:80%; 
        color:#999;
        margin:0 auto;
    }
    #create_pwd{
        margin:30px auto 0 auto;
        display:block; 
    } 
    .bt_bottom{
        position:fixed;
        background:url('{pigcms{$static_path}images/homeBg1.png') center bottom no-repeat;
        background-size:100%;
        bottom:0;
        left:0;
        width:100%; 
        padding:150px 0 160px 0;
    }

    .bt_bottom #create_pwd{
        background:url('{pigcms{$static_path}images/open_door.png') center  no-repeat;
        background-size:auto 140px;
        width:140px;
        height:140px;
        border:none; 
    } 
</style>
<body style="background:#fff"> 
    <if condition="!$is_app_browser">
    <header class="pageSliderHide">
        <div id="backBtn" onclick="history.back()"></div>
        访客开门密码
    </header>
    </if>
    <!-- <span>您目前有以下门禁的开门权限,开门时效为20分钟</span>--> 
    <div class="pwdInput center">
        <div class="input_pwd">
            <span class="pwd_Txt" id="pwd_Txt0">&nbsp;</span>
            <span class="pwd_Txt" id="pwd_Txt1">&nbsp;</span> 
            <span class="pwd_Txt" id="pwd_Txt2">&nbsp;</span>
            <span class="pwd_Txt" id="pwd_Txt3">&nbsp;</span>
            <span class="pwd_Txt" id="pwd_Txt4">&nbsp;</span>
            <span class="pwd_Txt" id="pwd_Txt5">&nbsp;</span> 
            <div class="clear"></div>  
        </div>   
        <div style="text-align: center;">
            <input type="text" class="share-input"   style="opacity: 0; " id="copy-content"/>
            <p><a href="javascript:void(0);" class="copyPwd"   onclick="copyContent();"> 点击复制密码</a></p>
            
        </div> 
    </div> 

    <div class="bt_bottom">
        <div class='gantan'>点击下方按钮获取开门密码</div>   
        <div id="create_pwd"> </div> 
    </div>  
   
  <script language="javascript">   
    $('#create_pwd').click(function(){  
        layer.open({
            type: 2,
            shadeClose:false,
            content: '获取密码中'
        });  
        $.post('{pigcms{:U("ajax_visitor_open_door_pwd")}', {}, function(data){   
            layer.closeAll();
            if (data.tip>0) { 
                motify.log(data.tip_info) 
            }else{  
                $('.copyPwd').show(); 
                var passwd=data.data.passwd;
                $('#copy-content').attr('value',passwd);
                input_array=String(passwd).split(''); 
                input_array.forEach(function( val, index ) {
                    $('.input_pwd #pwd_Txt'+index+'').text(input_array[index]);  
                    $('.input_pwd #pwd_Txt'+index+'').css('-webkit-text-security','none'); 
                })   
            }
        },'json')
    }) 

    function copyContent(){  
        var copyobject=document.getElementById("copy-content"); 
        copyobject.select();
        document.execCommand("Copy");  
        motify.log('已复制成功哦~');
    };
    </script> 
</body>
</html>