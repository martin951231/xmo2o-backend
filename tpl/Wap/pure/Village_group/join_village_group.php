<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0" />
    <meta content="yes" name="apple-mobile-web-app-capable" />
    <title>参团购买</title>
    <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont.css">
    <link rel="stylesheet" href="{pigcms{$static_path}village_group/css/iconfont/iconfont.css">
    <script src="{pigcms{:C('JQUERY_FILE_190')}"></script>
	<script src="{pigcms{$static_path}layer/layer.m.js"></script>
    <script src="{pigcms{$static_path}village_group/js/resize.js"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
    <script src="{pigcms{$static_path}js/common.js"></script>
    <style>
        body{width: 100%;height:100%;background: #f8f8f8;overflow-x: hidden;}
        .not-leader{display: none;}
        .not-leader .village-list .item{background: #fff;color: #333;font-size: 0.28rem;margin-bottom: 0.3rem;padding: 0.2rem;}
        .not-leader .village-list .item .name{background: #f4f4f4;display: inline-block;height: 0.68rem;line-height: 0.68rem;padding: 0 0.28rem;border-radius: 0.34rem;}
        .not-leader .village-list .item .name .icon{display: inline-block;width: 0.36rem;}
        .not-leader .village-list .item .name i{color: #06c1ae;font-size: 0.36rem;}
        .not-leader .village-list .item .content{padding: 0.2rem 0;}
        .not-leader .village-list .item .info .date{color: #999;}
        .not-leader .village-list .item .info .contact{float: right;color: #06c1ae;}


        .leader{padding-bottom: 1.8rem;color: #333;}
        .leader .time-box {background: #fff0d7;color: #ff972a;height: 0.6rem;line-height: 0.6rem;text-align: center;font-size: 0.26rem;}
        .leader .info-wrapper{background: #fff;margin-bottom: 0.3rem;padding: 0.2rem;}
        .leader .info-box{height: 1rem;position: relative;}
        .leader .info-box .avatar{width: 1rem;height: 1rem;border-radius: 50%;float: left;margin-right: 0.2rem;}
        .leader .info-box .name{font-size: 0.32rem;font-weight: 600;}
        .leader .info-box .more-btn{color: #999;margin-top: 0.1rem;}
        .leader .info-box .more-btn i{font-size: 0.24rem;margin-left: 0.04rem;}
        .leader .info-box .instruction {position: absolute;right: -0.2rem;top: 0.2rem;padding: 0.1rem 0.2rem;border-radius: 0.4rem 0 0 0.4rem;box-shadow:0 0 2px 2px #efefef;font-size: 13px;}
        .leader .info-box .instruction i{margin-right: 0.1rem;color: #398cea;font-size: 0.28rem;}
        .leader .info-wrapper .desc{display: none;}
        .leader .info-wrapper .desc div{padding-left: 1.2rem;line-height: 0.44rem;color: #666;}

        .leader .list-box {margin-bottom: 0.3rem;}
        .leader .list-box .item{padding: 0.2rem;background: #fff;height: 1.4rem;position: relative;}
        .leader .list-box .item.border-b:after{left: 0.2rem;}
        .leader .list-box .item.border-b:last-child:after{height: 0;}
        .leader .list-box .item img{width: 1.4rem;height: 100%;margin-right: 0.2rem;float: left;}
        .leader .list-box .item .name{font-size: 0.28rem;}
        .leader .list-box .item .price-box{position: absolute;bottom: 0.2rem;left: 1.8rem;color: #e73810;width: calc(100% - 2rem);}
        .leader .list-box .item .price-box text{vertical-align: sub}
        .leader .list-box .item .price-box .now-price{font-size: 0.4rem;}
        .leader .list-box .item .price-box .old-price{color: #999;text-decoration: line-through;margin-left: 0.04rem;}
        .leader .list-box .item .count-box{position: absolute;bottom: 0;right: 0;color: #333;height: 0.6rem;line-height: 0.6rem;}
        .leader .list-box .item .count-box i{font-size: 0.56rem;color: #40bc06;}
        .leader .list-box .item .count-box text{vertical-align: top;font-size: 0.32rem;}

        .leader .input-wrapper { height: 1rem;line-height: 1rem;background: #fff;padding: 0 0.2rem;font-size: 0.28rem;box-sizing: border-box;}
        .leader .input-wrapper text { width: 1.4rem;display: inline-block;}
        .leader .input-wrapper input { width: calc(100% - 1.6rem);color: #333; }
        .leader .button-wrapper{position: fixed;bottom: 0;left: 0;right: 0;padding: 0.2rem;background:#f8f8f8;text-align: center; }
        .leader .total-wrapper{height: 0.6rem;line-height: 0.6rem;font-size: 0.26rem;}
        .leader .total-wrapper>text:first-child{color: #e73810;}
        .leader .total-wrapper .price{color: #e73810;font-size: 0.4rem; }
        .leader button {background: #40bc06;color: #fff;width: 100%;height: 0.8rem;line-height: 0.8rem;border-radius: 0.08rem;font-size: 0.28rem;}

        .maskContent.explain{transform: translate(0,0);left: 10%;top: 28%;box-sizing: border-box;}
        .explain{padding: 0.24rem;display: none;}
        .explain .title{text-align: center;margin-bottom: 0.32rem;font-size: 0.3rem;}
        .explain p{text-indent:0.48rem;}
        .explain i{position: absolute;top: 0; right: 0.1rem;font-size: 0.56rem;color: #999;}

        .share{position: fixed;top: 1.8rem;right: 0.2rem;background:#fff0d7;color: #ff972a;height: 0.8rem;width: 0.8rem;text-align: center;border-radius: 50%;z-index:999}
        .share .icon{font-size: 0.32rem;}
        .share div{margin-top: -5px;font-size: 0.24rem;}

    </style>
</head>
<body>

    <div class="page">

    

        <!-- 团员 -->
        <div class="leader">
            <div class="time-box">
                <i class="icon iconfont">&#xe633</i>
                <text>距本次团购活动结束还有<em id="time"></em></text>
            </div>

            <div class="info-wrapper">
                <div class="info-box">
                    <img class="avatar" src="{pigcms{$header.avatar}">
                    <div>
                        <div class="name"><text>{pigcms{$header.name}</text>&nbsp团长</div>
                        <div class="more-btn">展开团长信息<i class="icon iconfont">&#xe60a</i></div>
                        <div class="instruction"><i class="icon iconfont">&#xe601</i>活动说明</div>
                    </div>
                    <div class="share" id="inviteBtn"><i class="icon iconfont">&#xe6e6;</i><div>转发</div></div>
                </div>
                <div class="desc">
                    <div>微信号：{pigcms{$header.wx_number}</div>
                    <div>电话：<input type="tel" value="{pigcms{$header.phone}" ></div>
                </div>
            </div>


            <div class="list-box">
                <ul>
					<volist name="now_group.goods" id="vo">
						<li class="item border-b" data-goods_id="{pigcms{$vo.goods_id}" data-max_num="{pigcms{$vo.stock_num}"  data-mer_group_id="{pigcms{$vo.mer_group_id}">
							<img src="{pigcms{$vo.image}" alt="">
							<div>
								<div class="name">{pigcms{$vo.name}</div>
								<div class="price-box">
									<text>{pigcms{$config['Currency_symbol']}</text>
									<text class="now-price">{pigcms{$vo.price|floatval}</text>
									<!--text class="old-price">{pigcms{$config['Currency_symbol']}50.00</text-->
									<div class="count-box">
										<i class="icon iconfont minus" data-goods_id="{pigcms{$vo.goods_id}">&#xe69a</i>
										<text class="number" id="good_{pigcms{$vo.goods_id}" >0</text>
										<i class="icon iconfont plus" data-goods_id="{pigcms{$vo.goods_id}">&#xe649</i>
									</div>
								</div>

							</div>
						</li>
					</volist>
                  
                </ul>
            </div>

            <div class="input-wrapper">
                <text>手机号码</text>
                <input type="text" name="phone" id="phone" value="{pigcms{$_COOKIE.login_name}" placeholder="提货时需要用到哦">
            </div>

            <div class="button-wrapper">
                <div class="total-wrapper">共<em class="num">0</em>件 合计：<text>{pigcms{$config['Currency_symbol']}</text><text class="price">0</text></div>
                <button id="join_pay">提交订单</button>
            </div>

        </div>

        <!-- 活动说明弹窗 -->
        <div class="mask"></div>
        <div class="maskContent explain">
            <div class="title">拼团规则</div>
			{pigcms{$config.village_group_rule|html_entity_decode}
            <i id="cha" class="icon iconfont">&#xe679</i>
        </div>
    </div>
    <script>
		var all_money = 0,num=0,cart_cookie='',goodsCart = {},can_pay = true;
		$(function(){
				addTimer('time',"{pigcms{$end_time}");
		});
		
		
		var addTimer = function(){
			var list = [],
			  interval;
			  
			return function(id,timeStamp){
			  if(!interval){
				interval = setInterval(go,1);
			  }
			  list.push({ele:document.getElementById(id),time:timeStamp});
			}

			function go() { 
			  for (var i = 0; i < list.length; i++) { 
				list[i].ele.innerHTML = changeTimeStamp(list[i].time); 
				if (!list[i].time) 
				  list.splice(i--, 1); 
			  } 
			}

			function changeTimeStamp(timeStamp){
			  var distancetime = new Date(timeStamp*1000).getTime() - new Date().getTime();
			  if(distancetime > 0){ 
			　　
			   // var ms = Math.floor(distancetime%1000);
				var sec = Math.floor(distancetime/1000%60);
				var min = Math.floor(distancetime/1000/60%60);
				var hour =Math.floor(distancetime/1000/60/60%24);
				var day =Math.floor(distancetime/1000/60/60/24);
				var day_txt  = '';
				if(day>=1){
					day_txt = day+'天 ';
				}
				if(sec<10){
				  sec = "0"+ sec;
				}
				if(min<10){
				  min = "0"+ min;
				}
				if(hour<10){
				  hour = "0"+ hour;
				}
				
				return day_txt+hour + ":" +min + ":" +sec ;
			  }else{
				window.location.reload();
			  }  
			}        
			}();	
		
		function randomString(len) {
			len = len || 32;
			var $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678oOLl9gqVvUuI1';
			var maxPos = $chars.length;
			var pwd = '';
			for(i = 0; i < len; i++) {
		　　　　pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
		　　}
			return pwd;
		}
		cart_cookie = $.cookie('cart_cookie');
		if(!cart_cookie){
			$.cookie('cart_cookie',randomString(32),{expires:1,path:'/'});
			cart_cookie = $.cookie('cart_cookie');
		}
		get_goods_cart();
		console.log('cart_cookie',cart_cookie);

		if($('#phone').val()!=''){
			$.cookie('login_name',$('#phone').val());
		}
		
		$('#phone').change(function(){
			$.cookie('login_name',$(this).val());
		})
		
        $('.instruction').on('click', () => {
            $('.explain').show()
            $('.mask').show()
        })

        $('#cha, .mask').on('click', () => {
            $('.explain').hide()
            $('.mask').hide()
        })

        $('.info-wrapper .info-box .more-btn').on('click', () => {
            $('.info-wrapper .desc').slideToggle('normal')
            if($('.info-wrapper .info-box .more-btn').html().indexOf('收起团长信息') === 0){
                $('.info-wrapper .info-box .more-btn').html('展开团长信息<i class="icon iconfont">&#xe60a</i>')
            } else {
                $('.info-wrapper .info-box .more-btn').html('收起团长信息<i class="icon iconfont">&#xe609</i>')
            }

        })
		
		$('.list-box .item.border-b').click(function(){
			window.location.href="{pigcms{:U('Group/detail')}&village_group=1&group_id="+$(this).data('mer_group_id')
		});
		
		$('#join_pay').click(function(){
			if(can_pay==true){				
				can_pay = false;
			}
			if($('#phone').val()==''){
				layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'请填写手机号码', btn:['好的'],shadeClose:false});
				return false;
			}
			$.post('{pigcms{:U('save_village_group_order')}',{start_id:'{pigcms{$_GET['group_id']}',cart_cookie:cart_cookie,goodsCart:goodsCart,phone:$('#phone').val()},function(data){
				if(data.status==1){
					// layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info,btn:['好的'],shadeClose:false,yes:function(){
						window.location.href=data.url
					// }});
				}else if(data.status==0){
					if(data.url!=''){
						layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:data.info,btn:['好的'],shadeClose:false,yes:function(){
							window.location.href= data.url;
						}});
						
					}else{
						
					}
				}
			});
		});
		
		$('.plus').click(function(){
			event.stopPropagation();
			var good_id = $(this).data('goods_id');
			var good_price = Number($(this).parents('li').find('.now-price').html());
			var good_num = $(this).siblings('.number').html();
			good_num = Number(good_num);
			var max_num = $(this).parents('li').data('max_num')
			max_num = Number(max_num)
			if(good_num+1<=max_num){
				var now_good_num = good_num+1;
				$(this).siblings('.number').html(now_good_num)
				all_money += good_price;
				num +=1;
				goodsCart[good_id] = {'num':now_good_num,'good_price':good_price};
				goodsCart['all'] = {'all_money':all_money,'all_num':num};
				
			}
			$('.price').html(all_money)
			$('.num').html(num)
			$.cookie('goodsCart', JSON.stringify(goodsCart));
			save_goods_cart();
			
		});
		
		$('.minus').click(function(){
			event.stopPropagation();
			var good_id = $(this).data('goods_id');
			var good_price = Number($(this).parents('li').find('.now-price').html());
			var good_num = $(this).siblings('.number').html();
			good_num = Number(good_num);
			var max_num = $(this).parents('li').data('max_num')
			max_num = Number(max_num)
			if(good_num-1>=0){
				var now_good_num = good_num-1;
				$(this).siblings('.number').html(now_good_num)
				all_money -= good_price;
				num -=1;
				goodsCart[good_id] = {'num':now_good_num,'good_price':good_price};
				goodsCart['all'] = {'all_money':all_money,'all_num':num};
			}
			$('.price').html(all_money)
			$('.num').html(num)
			
			$.cookie('goodsCart', JSON.stringify(goodsCart));
			save_goods_cart();
			
		});
		
		function save_goods_cart(){
			$.post('{pigcms{:U('ajax_cart_save')}',{start_id:'{pigcms{$_GET['group_id']}',cart_cookie:cart_cookie,goodsCart:goodsCart},function(){
			
			});
		}
		
		function get_goods_cart(){
			var all_get_num=0,all_get_money = 0;
			$.post('{pigcms{:U('ajax_cart_get')}',{cart_cookie:cart_cookie,start_id:"{pigcms{$_GET['group_id']}"},function(data){
				if(data.status==1){
					var goods_detail = data.info;
					for(var i in goods_detail ){
						console.log(goods_detail[i].count)
						$('#good_'+goods_detail[i].group_id).html(goods_detail[i].count)
						goodsCart[goods_detail[i].group_id] = {'num':goods_detail[i].count,'good_price':goods_detail[i].productParam.good_price};
						all_get_num +=parseInt(goods_detail[i].count);
						all_get_money +=parseFloat(goods_detail[i].productParam.good_price)*parseInt(goods_detail[i].count);
					}
					goodsCart['all'] = {'all_money':all_get_money,'all_num':all_get_num};
				}
					all_money = all_get_money;
					num = all_get_num;
					$('.price').html(all_get_money)
					$('.num').html(all_get_num)
			
			},'json');
		}

		
		var getShareUrl = "{pigcms{:U('share',array('group_id'=>'864'))}";
		$('#inviteBtn').click(function(){
			if(typeof wx !=='undefined' && window.__wxjs_environment === 'miniprogram'){
				var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)',shadeClose:false});
				$.post(getShareUrl,function(result){
					layer.close(layerLoadTip);
					if(result.status == 1){
						var wxappUrl = '/pages/index/share?shareImg='+encodeURIComponent(result.info.friend_img)+'&sharePath='+encodeURIComponent(result.info.wxapp_path)+'&poster='+encodeURIComponent(result.info.group_img)+'&shareTitle='+encodeURIComponent(result.info.share_title)+'&title='+encodeURIComponent(result.info.title)+'&bg='+encodeURIComponent(result.info.good_img);
						console.log('wxapp_share_url',wxappUrl);
						wx.miniProgram.navigateTo({
							url:wxappUrl
							,fail:function(res){
								layer.open({
									content: '打开分享失败，请重试'
									,btn: '好的'
								});
							}
						});
					}else{
						layer.open({
							content: result.info
							,btn: '好的'
						});
					}
				});
			}else if(typeof wx !=='undefined'){
				layer.open({
					content: '请使用微信右上角分享功能'
					,btn: '好的'
				});
			}else{
				layer.open({
					content: '请使用浏览器分享功能'
					,btn: '好的'
				});
			}
		});
		
		

    </script>
</body>
</html>