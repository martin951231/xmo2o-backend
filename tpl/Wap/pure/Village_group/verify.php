<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0" />
    <meta content="yes" name="apple-mobile-web-app-capable" />
    <title>验证消费</title>
   <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont.css">
   <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont/iconfont.css">
    <script src="{pigcms{:C('JQUERY_FILE_190')}"></script>
    <script src="{pigcms{$static_path}village_group/js/resize.js"></script>
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
		
    </style>
</head>
<body>
    <div class="page">
       

		<div class="line ks-clear address link" data-url="{pigcms{:U('start_list')}">
            <div class="left"><i class="icon iconfont" style="color: #58e6ff;">&#xe60b</i>拼团订单</div>
            <div class="right"><i class="icon iconfont">&#xe603</i></div>
        </div>
		
        <div class="line ks-clear address link" data-url="{pigcms{:U('header_address')}">
            <div class="left"><i class="icon iconfont" style="color: #58e6ff;">&#xe60b</i>收货地址</div>
            <div class="right"><i class="icon iconfont">&#xe603</i></div>
        </div>
		
		<div class="line ks-clear address link" data-url="{pigcms{:U('verify')}">
            <div class="left"><i class="icon iconfont" style="color: #58e6ff;">&#xe60b</i>验证消费</div>
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
                <div class="right"><i class="icon iconfont">123&#xe603</i></div>
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

	</script>
</body>
</html>