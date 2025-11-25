<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>转赠</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}fenrun/css/fenrun.css?215"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
		
    <style>
        .mask {
            position: absolute;
            position: fixed;
            z-index: 9998;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.5);
        }
        .check_pwd .check-btn.on {
            background: #00c4ac;
            color: #fff;
        }
        .check_pwd .check-btn {
            margin: 1.4rem 0.3rem 0 0.3rem;
            background: #cccccc;
            font-size: 0.36rem;
            color: #999999;
            line-height: 1rem;
            border-radius: 0.1rem;
            text-align: center;
        }
        .check_pwd {
            color: #333;
            position: fixed;
            top: 20%;
            z-index: 10000;
            background-color: #f4f4f4;
            padding: 20px 10px;
            text-align: center;
            width: 70%;
            margin: 0 12.5%;
        }
        .check_pwd .title {
            font-size: 18px;
            margin-bottom: 30px;
        }
        .check_pwd input {
            width: 90%;
            text-align: center;
            height: 45px;
            line-height: 20px;
            margin-bottom: 10px;
            background-color: white;
            font-size: 18px;
        }
        .close_tip_img {
            width: 25px;
            height: 25px;
            position: absolute;
            top: 5px;
            right: 5px;
        }
	</style>
</head>

 <body>
		<section class="balance" style='height:100%;overflow:hidden'>
		<form class="my_from">
            <div class="present bw">
                <div class="fl">转赠余额给个人</div>
                <div style="text-align: right;font-size: 14px;color: #999">
                    昵称： <span style="color: #333;"> {pigcms{$other_info.nickname}</span>
                    <input type="hidden" value="{pigcms{$other_info.uid}" name="uid" />
                </div>
            </div>
            
            <div class="present bw">转赠金额</div>
            <div class="whole bw">
                <div class="input">
                    <input type="number" placeholder="请输入转赠金额" class="import" name="money" />
                    <div class="click">全部转赠</div>
                </div>
                <p class="remind">当前余额{pigcms{$config['Currency_symbol']}{pigcms{$user_info['now_money']}，可转赠：{pigcms{$user_info.can_withdraw_money}{pigcms{$config['Currency_txt']}</p>
            </div>
		</form>
            <div class="confirm on">转赠</div>
        </section>

        <div class="check_pwd" style="display: none;">
            <img class="close_tip_img quxiao" src="{pigcms{$static_path}images/new_my/close_tip.png"/>
            <div class="title">验证登录密码</div>
            <input type="password" placeholder="请输入登录密码"  id="login_pwd"/>
            <div style="color: #999999;">转赠前请先验证你的登录密码</div>
            <div class="check-btn on" style="width: 80%;margin-left: 10%;line-height: 0.8rem;">确认</div>
        </div>

        <div class="mask" style="display:none;"></div>
        


        <script src="{pigcms{$static_path}fenrun/js/fenrun.js"></script>
		<script src="{pigcms{$static_path}js/common_wap.js"></script>
		<script>
		window.addEventListener('pageshow',function(event){
			var payment_other_refresh = sessionStorage.getItem('payment_other_refresh');
			if(payment_other_refresh == '1'){
				sessionStorage.removeItem('payment_other_refresh');
				location.reload();
			}
		});
		
		if($('input[name="truename"]').val()!='' && typeof($('input[name="truename"]').val())!='undefined' ){
			
			window.location.reload();
		}
        var now_money = Number('{pigcms{$user_info.can_withdraw_money|floatval}');
		var flag = true;
        var check = true;
		$(".import").bind('input', function(e){
			var key = $.trim($(this).val());
			var html='';
			if (key > now_money) {
                layer.open({
                    content:'转赠金额不能大于可转赠金额',
                    btn: ['我知道了']
                    ,yes: function(index){
                        $(".import").val('');
                        layer.close(index);
                    }
                });
                return false;
            }
			if(key.length > 0) {
                var now_money_info = Number('{pigcms{$user_info.now_money|floatval}') - Number(key);
                now_money_info = now_money_info.toFixed(2);
                var can_withdraw_money = Number('{pigcms{$user_info.can_withdraw_money|floatval}') - Number(key);
                can_withdraw_money = can_withdraw_money.toFixed(2);
				html='当前余额{pigcms{$config['Currency_symbol']}'+now_money_info+'，可转赠：'+can_withdraw_money+'{pigcms{$config['Currency_txt']}';
			}else{
				html='当前余额{pigcms{$config['Currency_symbol']}{pigcms{$user_info['now_money']}，可转赠：{pigcms{$user_info.can_withdraw_money}{pigcms{$config['Currency_txt']}';
			}
			$(".remind").html(html);
		});
		
		$('.click').click(function(){
			$(".import").val($.trim(now_money))
			console.log(now_money)
			var key = $.trim($('.import').val());
			var html='';
            if (key > now_money) {
                layer.open({
                    content:'转赠金额不能大于可转赠金额',
                    btn: ['我知道了']
                    ,yes: function(index){
                        $(".import").val('');
                        layer.close(index);
                    }
                });
                return false;
            }
			if(key.length > 0  ){
				$(".confirm").addClass("on");
                var now_money_info = Number('{pigcms{$user_info.now_money|floatval}') - Number(key);
                now_money_info = now_money_info.toFixed(2);
                var can_withdraw_money = Number('{pigcms{$user_info.can_withdraw_money|floatval}') - Number(key);
                can_withdraw_money = can_withdraw_money.toFixed(2);
                html='当前余额{pigcms{$config['Currency_symbol']}'+now_money_info+'，可转赠：'+can_withdraw_money+'{pigcms{$config['Currency_txt']}';
			}
			$(".remind").html(html);
		
		});


        //取消按钮点击和蒙层点击效果
        $('.quxiao').click(function(){
            $('.check_pwd').hide();
            window.location.reload();
            $('.import').val('');
            window.location.reload();
        });

		$('.check-btn').click(function () {
            if(check){
                var password = $("#login_pwd").val();
                if(password=='' || typeof(password)=='undefined'){
                    layer.open({
                        content:'请输入登录密码',
                        btn: ['我知道了']
                        ,yes: function(index){
                            layer.close(index);
                        }
                    });
                    return false;
                }
                var check_user_pwd = '{pigcms{:U('check_user_pwd')}';

                $.ajax({
                    url: check_user_pwd,
                    type: 'POST',
                    dataType: 'json',
                    data:{'password': password},
                    beforeSend:function(){
                        check = false;
                    },
                    success:function(data){
                        console.log('data',data);
                        if (data.status == 1) {
                            layer.open({type: 2,content: '正在转赠...'});
                            pay();
                        } else {
                            layer.open({
                                content:data.info,
                                btn: ['我知道了']
                                ,yes: function(index){
                                    // window.location.reload();
                                    layer.close(index);
                                }
                            });
                            check = true;
                        }
                    }
                });
            }
        });
		
        function pay() {
            var money = $(".import").val();
            if($('input[name="uid"]').val()=='' || typeof($('input[name="uid"]').val())=='undefined'){
                layer.open({
                    content:'无转赠对象',
                    btn: ['我知道了']
                    ,yes: function(index){
                        window.location.reload();
                        layer.close(index);
                    }
                });
                return false;
            }


            if(money==''){
                layer.open({
                    content:'请输入金额',
                    btn: ['我知道了']
                    ,yes: function(index){
                        //  window.location.reload();
                        layer.close(index);
                    }
                });
                return false;
            }
            if(money<=0){
                layer.open({
                    content:'金额输入有误',
                    btn: ['我知道了']
                    ,yes: function(index){
                        //  window.location.reload();
                        layer.close(index);
                    }
                });
                return false;
            }
            if(money>now_money){
                layer.open({
                    content:'转赠金额超出范围',
                    btn: ['我知道了']
                    ,yes: function(index){
                        // window.location.reload();
                        layer.close(index);
                    }
                });
                return false;
            }
            if(flag){
                var payment_other = '{pigcms{:U('payment_other')}';

                $.ajax({
                    url: payment_other,
                    type: 'POST',
                    dataType: 'json',
                    data:$('.my_from').serialize(),
                    beforeSend:function(){
                        flag = false;
                    },
                    success:function(data){
                        layer.closeAll();
                        layer.open({
                            content:data.info,
                            shadeClose:false,
                            btn: ['我知道了']
                            ,yes: function(index){
                                console.log(data.url);
                                if(data.url!=''){
                                    sessionStorage.setItem('payment_other_refresh','1');
                                    window.location.href=data.url;
                                }else{
                                    // window.location.reload();
                                    flag = true;
                                    check = true;
                                    layer.close(index);
                                }
                            }
                        });

                    }
                });
            }
        }
			
		$('.confirm').click(function(){
            var money = $(".import").val();
            if($('input[name="uid"]').val()=='' || typeof($('input[name="uid"]').val())=='undefined'){
                layer.open({
                    content:'无转赠对象',
                    btn: ['我知道了']
                    ,yes: function(index){
                        window.location.reload();
                        layer.close(index);
                    }
                });
                return false;
            }


            if(money==''){
                layer.open({
                    content:'请输入金额',
                    btn: ['我知道了']
                    ,yes: function(index){
                        //  window.location.reload();
                        layer.close(index);
                    }
                });
                return false;
            }
            if(money<=0){
                layer.open({
                    content:'金额输入有误',
                    btn: ['我知道了']
                    ,yes: function(index){
                        //  window.location.reload();
                        layer.close(index);
                    }
                });
                return false;
            }
            if(money>now_money){
                layer.open({
                    content:'转赠金额超出范围',
                    btn: ['我知道了']
                    ,yes: function(index){
                        // window.location.reload();
                        layer.close(index);
                    }
                });
                return false;
            }
            $('.mask').show();
            $('.check_pwd').show();
		});
	</script>
    </body>

</html>