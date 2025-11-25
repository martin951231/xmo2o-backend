<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0" />
    <meta content="yes" name="apple-mobile-web-app-capable" />
    <title>团长页</title>
   <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont.css">
   <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont/iconfont.css">
   	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?21923"/>
    <script src="{pigcms{:C('JQUERY_FILE_190')}"></script>
    <script src="{pigcms{$static_path}village_group/js/resize.js"></script>
	<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
    <script src="{pigcms{$static_path}js/common.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>

    <style>
        body{width: 100%;height:100%;background: #f8f8f8;overflow-x: hidden;color: #333;}
        .header{background: #fff;margin-bottom: 0.2rem;}
        .header .bg{width: 100%;}
        .border-b{position: relative;}
        .border-b.lf:after{left: 0.2rem;}
        .line{font-size: 0.28rem;padding: 0.2rem;background: #fff;line-height: 0.6rem;}
        .line .left{float: left;}
        .line .left i{margin-right: 0.1rem;font-size: 0.4rem;}
        .line .right{float: right;}
        .line .right i{font-size: 0.28rem;margin-left: 2px;}
        .line .total{color: #f52525;}


        .list li{float: left;width: 25%;text-align: center;padding: 0.2rem;box-sizing: border-box;}
        .list li i{color: #f52525;font-size: 0.56rem;}

        .address{margin-bottom: 0.2rem;}
		.Cen_tx{
			    position: absolute;
				text-align: center;
				width: 100%;
				top: 12%;
				margin-top: -60px;
		}
		.Cen_tx img{
			width: 60px;
			height: 60px;
			border-radius: 100%;
			border: #fff 3px solid;
		}
		
		.Cen_tx h3{
			font-size: 18px;
			color: #fff;
			line-height: 35px;
		}
		.verify button{
		   text-align: center;
			width: 52px;
			font-size: 16px;
			color: #fff;
			border-radius: 5px;
			background: #fc4753;
			right: 0px;
			top: 0px;
			padding: 4px;
			margin-left: 6px;
			margin-top: 1px;
		}
		.verify input{
			font-size: 14px;
	
			text-indent: 30px;
			line-height: 33px;
			border: #fc4753 1px solid;
			border-radius: 5px;
			font-size: 14px;
			background-color: white;
			background: url(../images/zftp_04.png) 8px center no-repeat;
			background-size: 18px 17px;
			width: 97%;
			float: left;
			

		}
		.verify em{
			color: #58e6ff;display: block;
			
			left: 0px;
			top: 0px;
			width: 40px;
			height: 35px;
			background: url({pigcms{$static_path}village_group/images/zftp_04.png) 8px center no-repeat;
			background-size: 18px 17px;z-index: 10;
			float: left;
			    margin-top: -34px;
		}
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <img class="bg" src="{pigcms{$static_path}/images/header_bg.png" alt="">
			<div class="Cen_tx">
				<img src="{pigcms{$header.avatar}">
				<h3>Goi</h3>
<!--					<p>个性签名：人生不经历风雨，怎能见彩虹</p>-->
			</div>
            <div class="border-b">
                <div class="line ks-clear">
                    <div class="left">我是团长</div>
                    <!--div class="right total link"  data-url="{pigcms{:U('start_list')}">拼团订单<i class="icon iconfont">&#xe603</i></div-->
                </div>
            </div>

       
        </div>
		
		<div class="line ks-clear address verify ">
            <div class="left" style="width: 80%;">
			<input type="text" name="code" id="code" >
			<em class="icon iconfont scan" ></em>
			
			</div>
			<button id="verify">验证</button>
            
        </div>

		<div class="line ks-clear address link" data-url="{pigcms{:U('start_list')}">
            <div class="left"><i class="icon iconfont" style="color: #58e6ff;">&#xe60b</i>拼团订单</div>
            <div class="right"><i class="icon iconfont">&#xe603</i></div>
        </div>
		
        <div class="line ks-clear address link" data-url="{pigcms{:U('header_address')}">
            <div class="left"><i class="icon iconfont" style="color: #58e6ff;">&#xe60b</i>收货地址</div>
            <div class="right"><i class="icon iconfont">&#xe603</i></div>
        </div>
		
		

        <div class="border-b lf">
            <div class="line ks-clear">
                <div class="left"><i class="icon iconfont" style="color: #4a9dfb;">&#xe605</i>我的群二维码</div>
                <div class="right"><i class="icon iconfont">&#xe603</i></div>
            </div>
        </div>
        <div class="border-b lf link" data-url="{pigcms{:U('my_money')}">
            <div class="line ks-clear">
                <div class="left"><i class="icon iconfont" style="color: #fdb70b;">&#xe6a2</i>我的佣金</div>
                <div class="right"><i class="icon iconfont">{pigcms{$header.header_money|floatval}{pigcms{$config['Currency_txt']}&#xe603</i></div>
            </div>
        </div>
        <!--div class="border-b lf">
            <div class="line ks-clear">
                <div class="left"><i class="icon iconfont" style="color: #3bb232;">&#xe616</i>我的拼团</div>
                <div class="right"><i class="icon iconfont">&#xe603</i></div>
            </div>
        </div-->
        <div class="border-b lf link"  data-url="{pigcms{:U('start_village_group')}">
            <div class="line ks-clear">
                <div class="left"><i class="icon iconfont" style="color: #ff8234;">&#xe640</i>发起团购</div>
                <div class="right"><i class="icon iconfont">&#xe603</i></div>
            </div>
        </div>
        <div class="line ks-clear">
            <div class="left"><i class="icon iconfont" style="color: #7a7fff;">&#xe641</i>认证资质</div>
            <div class="right"><i class="icon iconfont">&#xe603</i></div>
        </div>


    </div>
	<script>
	
		 $(document).on('click','.link',function(){
			window.location.href = $(this).data('url')
		});
		
		$(document).on('click','#verify',function(){
			if($('#code').val()==''){
				motify.log('验证码不能为空');
				return false;
			}
			var confirm_before_txt = '';
			$.post("{pigcms{:U('ajax_verify')}",{code:$('#code').val()},function(data){
				if(data.status==0){
					motify.log(data.info);
					return false;
				}else{
					confirm_before_txt = data.info;
					layer.open({
						title:['提示','background-color:#FF658E;color:#fff;'],
						content:confirm_before_txt+'<p style="width:180px;color:red;">确认验证消费吗？验证后不可撤销！</p>',
						btn: ['确定','取消'],
						yes: function(index){
							layer.close(index);
							$.post("{pigcms{:U('ajax_verify')}",{code:$('#code').val(),confirm:1},function(data){
								if(data.status==1){
									layer.open({
										content: data.info
										,skin: 'msg'
										,anim: 'up'
										,time: 2 
									});
									$('#code').val('')
								}else{
									motify.log(data.info);
								}
							},'json')
						}
					});	
				}
			},'json')
			
			
		});
		
	
		 $(document).on('click','.scan',function(){
			if(motify.checkWeixin()){
				motify.log('正在调用二维码功能');
				wx.scanQRCode({
					desc:'scanQRCode desc',
					needResult:1,
					scanType:["qrCode","barCode"],
					success:function (res){
						$('#code').val(res.resultStr);
					},
					error:function(res){
						motify.log('微信返回错误！请稍后重试。',5);
					},
					fail:function(res){
						motify.log('无法调用二维码功能');
					}
				});
			}else{
				motify.log('您不是微信访问，无法使用二维码功能');
			}
		 });

	</script>
	{pigcms{$hideScript}
</body>
</html>