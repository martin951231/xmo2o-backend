<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0" />
    <meta content="yes" name="apple-mobile-web-app-capable" />
    <title>确认订单</title>
       <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont.css">
       <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont/iconfont.css">
    <script src="{pigcms{:C('JQUERY_FILE_190')}"></script>
    <script src="{pigcms{$static_path}village_group/js/resize.js"></script>
	<script src="{pigcms{$static_path}layer/layer.m.js"></script>
    <style>
        body{width: 100%;height:100%;background: #f8f8f8;overflow-x: hidden;color: #333;}
        .list{background: #fff;padding: 0.2rem 0;margin-bottom: 0.2rem;}
        .list li{float: left;width: 30%;text-align: center;box-sizing: border-box;}
        .list li div>i{color: #f52525;font-size: 0.56rem;}
        .list li.arrow{width: 5%;margin-top: 0.24rem;}
        .list li.arrow i{font-size: 0.24rem;}

        .border-b{position: relative;}
        .border-b.lf:after{left: 0.2rem;}
        .line{font-size: 0.28rem;padding: 0.2rem;background: #fff;line-height: 0.6rem;}
        .line .left{float: left;width: calc(100% - 0.4rem);}
        .line .left i{margin-right: 0.1rem;font-size: 0.4rem;}
        .line .right{float: right;}
        .line .right i{font-size: 0.28rem;margin-left: 0.04rem;}
		
		.line input{display: inline-block;width: calc(100% - 1.6rem);color: #333;}
		.line input.name{width: calc(100% - 1.4rem);}

        .mb10{margin-bottom: 0.2rem;}

        .goods-info{background: #fff;}
        .goods-info .line{background: transparent;line-height: normal;padding: 0.3rem 0.2rem;}
        .goods-info .line .left{width:auto;}
        .goods-info .title{padding: 0.2rem;font-size: 0.28rem;}
        .goods-info .title i{font-size: 0.46rem;margin-right: 0.1rem;vertical-align: middle;color: #666;}
        .goods-info .goods-box {padding: 0 0.2rem;height: 1.6rem;}
        .goods-info .goods-box img{width: 1.6rem;height: 100%;float: left;}
        .goods-info .goods-box .desc-box{margin:0 -0.2rem 0 1.6rem;padding: 0.2rem;background: #efefef;color: #777;height: 100%;box-sizing: border-box;}
        .goods-info .goods-box .desc-box .line{padding: 0;}
		
        .total-box{font-size: 0.32rem;padding: 0; position:fixed;bottom:0;width:100%;}
        .total-box .price{color: #e73810;}
        .total-box button{background: #e73810;color: #fff;display: inline-block;padding: 0.3rem;margin-left: 0.3rem;}

        .ellipsis{overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;margin-bottom: 0.2rem;}

    </style>
</head>
<body>
    <div class="page">
        <ul class="list ks-clear">
            <li>
                <div><i class="icon iconfont">&#xe602;</i></div>
                <div>1.开团/参团</div>
            </li>
            <li class="arrow"><i class="icon iconfont" >&#xe603;</i></li>
            <li>
                <div><i class="icon iconfont" style="color: #42b9e9;">&#xe61b</i></div>
                <div>2.邀请好友参团</div>
            </li>
            <li class="arrow"><i class="icon iconfont">&#xe603;</i></li>
            <li>
                <div><i class="icon iconfont" style="color: #44b909;">&#xe606;</i></div>
                <div>3.人满成团</div>
            </li>
        </ul>

        <div class="border-b lf">
            <div class="line ks-clear">
                <div class="left">提货人：<input class="name" type="text" name="name" value="{pigcms{$now_user.nickname}" readOnly style="color:#ccc"></div>
            </div>
        </div>
        <div class="border-b lf">
            <div class="line ks-clear">
                <div class="left">手机号码：<input type="text" name="phone" value="{pigcms{$now_user.phone}"></div>
            </div>
        </div>
        <div class="border-b lf">
            <div class="line ks-clear">
                <div class="left">提货地址：<input type="text" name="address" value="{pigcms{$header.pick_address.pick_addr}"></div>
                <div class="right"><!--i class="icon iconfont">&#xe603</i--></div>
            </div>
        </div>
        <!--div class="border-b lf">
            <div class="line ks-clear">
                <div class="left">购买优惠：<input type="text" name="discount" value="暂无优惠券"></div>
                <div class="right"><i class="icon iconfont">&#xe603</i></div>
            </div>
        </div-->
        <div class="line ks-clear mb10">
            <div class="left">卖家留言：<input type="text" id="comment" name="message" value=""></div>
        </div>

        <div class="goods-info">
            <div class="title"><i class="icon iconfont">&#xe908;</i>{pigcms{$now_group.name}</div>
            <div class="goods-box">
                <img src="{pigcms{$now_group.share_image}" alt="">
                <div class="desc-box">
                    <div class="ellipsis">{pigcms{$now_group.share_desc}</div>
                   
                </div>
            </div>
            <div class="border-b lf">
                <div class="line ks-clear">
                    <div class="left">配送方式</div>
                    <div class="right">自提</div>
                </div>
            </div>
            <!--div class="border-b lf">
                <div class="line ks-clear">
                    <div class="left">运费</div>
                    <div class="right">{pigcms{$config['Currency_symbol']} 0.00</div>
                </div>
            </div-->
            
        </div>
		<div class="line ks-clear total-box">
			<div class="right ">
				<!--text>合计：</text>
				<text class="price">{pigcms{$config['Currency_symbol']} 109.00</text-->
				<button id="start_group">提交订单</button>
			</div>
		</div>
    </div>
	
	<script>
		$('#start_group').click(function(){
			$.post('{pigcms{:U('start_group_order')}',{group_id:'{pigcms{$now_group.group_id}',comment:$('#comment').val()},function(data){
				if(data.status==1){
					layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info,btn:['好的'],shadeClose:false,yes:function(){
						window.location.href= data.url;
					}});
				}
			},'json');
		});
		
	</script>
</body>
</html>