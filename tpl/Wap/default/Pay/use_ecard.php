<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <meta http-equiv="Expires" content="-1"/>
    <meta http-equiv="Cache-Control" content="no-cache"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta charset="utf-8" />
    <script src="{pigcms{:C('JQUERY_FILE')}"></script>
    <script src="{pigcms{$static_path}layer/layer.m.js"></script>
    <title>{pigcms{$config.ecard_alias_name}</title>
    <style type="text/css">
        *{padding: 0;margin: 0; text-decoration: none;list-style: none;}
        body{background: #efefef;}
        .box{
            background: #fff;
            padding:10px 10px;
            box-sizing:border_box;
        }
        .content_box{
            line-height: 32px;
            color: #666;
            font-size:15px;
            margin:10px 2px 20px 2px;
        }
        .content_box p{
            padding:5px 0;
        }
        #myform{
            overflow: auto;
        }
        #myform>div{
            margin: 12px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        #myform .card_box,#myform .verify_box{
            width:100%;
            padding:10px 0;
            background: #F7F7F7;
            position: relative;
        }
        #myform .card_box .close{
            width:20px;
            height:20px;
            background:#A9A9A9;
            border-radius:10px;
            box-sizing:border-box;
            position:absolute;
            right: 10px;
            top:16px;
            transform:rotate(45deg);
            display: none;
        }
        #myform .card_box .close:before{
            content:'';
            display:block;
            position: absolute;
            width: 60%;
            height:2px;
            left: 20%;
            top:9px;
            background: #fff;      
        }
        #myform .card_box .close:after{
            content:'';
            display: block;
            position: absolute;
            height: 60%;
            width:2px;
            top: 20%;
            left:9px;
            background: #fff;
        }
        #myform .verify_box{
            width:80%;
        }
        #myform input[type='text']{
            height:32px;
            line-height: 32px;
            border: none;
            width:80%;
            text-indent:10px;
            background: transparent;
            outline: none;
        }
        #myform input[type=submit]{
            width:100%;
            background: #EA3F43;
            color: #fff;
            border:none;
            height: 52px;
            font-size: 16px;
            letter-spacing:4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="content_box">
            <p>1、每张{pigcms{$config.ecard_alias_name|default='平台e卡'}只可使用一次。</p>
            <p>2、每次使用后，e卡将作废。</p>
        </div>
        <form id='myform' action="{pigcms{:U('Pay/use_ecard')}" method="post">
            <div class="card_box">
                <input type='text' name='card_password' placeholder='请输入16位E卡密码'/>
                <span class='close'></span>
            </div>
            <div>
                <p class="verify_box">
                    <input type="text" name='verify' placeholder='请输入右侧验证码'/>  
                </p>
                <img src="{pigcms{:U('Pay/get_verify')}" data-src="{pigcms{:U('Pay/get_verify')}" id='verify' />    
            </div>
            <input type="submit" value='立即兑换' />
        </form>
    </div>
<script type="text/javascript">
$(function(){
    //刷新验证码
    $('#verify').click(function(){
        var time = new Date().getTime();
        $('#verify').attr('src',$(this).data('src')+"&time="+time);
    });
    $('input[name="card_password"]').on('input propertychange',function(){
        var val=$(this).val();
        var len=val.length;
        if(len==0){
            $('.close').hide();
        }else{
            $('.close').show();    
        }
        if(len>=16){
            val=val.substr(0,16);
            $(this).val(val);
        }
    });
    $('.close').click(function(){
        $('input[name="card_password"]').val('');
        $('.close').hide();
    });
    $('#myform').submit(function(){
        var card_password=$('input[name="card_password"]').val();
        if(!card_password){
            tip_msg('请填写E卡密码！');
            return false;
        }
        var verify=$('input[name="verify"]').val();
        if(!verify){
            tip_msg('请填写验证码！');
            return false;
        }
        if(card_password.length!=16){
            tip_msg('请填写正确长度的E卡密码！');
            return false;
        }
        if(clock==1){
            return false;
        }
        var src=$(this).attr('action');
        param['card_password']=card_password;
        param['verify']=verify;
        $.ajax({
            type:'post',
            url:src,
            data:param,
            success:function(res){
                if(res.status==0){
                    tip_msg(res.info);
                    clock=0;
                }else{
                    window.location.replace(res.url);
                }
            },
            beforeSend:function(){
                clock=1;
            },
            complete:function(){

            }
        });
        return false;
    });
});
var clock=0;
var param={pigcms{$param};
function tip_msg(msg){
    msg=msg || '';
    layer.open({
        content:msg,
        btn:['确定'],
        yes:function(index){
            layer.close(index);
        }    
    });
}
</script>
</body>
</html>