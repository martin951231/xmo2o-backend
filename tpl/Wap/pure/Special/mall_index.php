<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
        <title>{pigcms{$now_special.name}</title>     
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
        <style>
            #containers {
                position: absolute;
                z-index: 1;
                top:0px;
                bottom:0px;
                left: 0;
                width: 100%;
                overflow: hidden;
            }
        </style>        
		<style type="text/css">
			.goods_box{
				overflow: auto;
			}
			.goods_box .item{
				float:left;
				width:1rem;
				margin:2% 2% 0 1%;
				background: #fff;
				position:relative;
			}
			.goods_box .item .goods_down{
				position: absolute;
				width:100%;
				line-height: 24px;
				color: #fff;
				background: rgba(0,0,0,.6);
				text-align: center;
			}
			.goods_box .item.odd{
				margin:2% 1% 0 2%;
				clear: both;
			}
			.goods_box .item img{
				width:100%;
				height:1rem;
				display:block;
			}
			.goods_box .item .content{
				padding: 10px 10px;
				line-height: 20px;
				font-size: 12px;
			}
			.goods_box .item .content p{
				box-sizing:border-box;
				overflow: hidden;
			}
			.goods_box .item .content p.title{
				font-size: 15px;
				margin-bottom: 5px;
				width: 100%;
				white-space: nowrap;
				text-overflow: ellipsis;
			}
			.goods_box .item del{color: #999;}
			/* 标签盒子 */
			.label_box{
			    display:block;
			}
			.label_box label{
			    display: inline-block;
			    color:#fff;
			    font-size:12px;
			    padding: 1px 2px;
			}
			.label_box label.group{
			    background-color: #E5686C
			}
			 .label_box label.seckill{
			    background-color: #FA9732;
    			margin: 5px 0 0px 0;
			}
			.label_box label.cut{
			    background-color: #E5686C;
			}
			/* 活动盒子 */
			.act_box{
			    display:block;
			    overflow: auto;
			    font-size:13px;
			    margin-top:5px;
			    clear: both;
			}
			.act_box span{display: inline-block;}
			.act_box span.price{float:left;color:#f00;line-height:22px;}
			.act_box span.btn{float:right;background: #f00;color:#fff;padding:1px 4px;font-size: 12px;}
			/* 自营标签 */
			i.self_tag{
				margin: 0;
				vertical-align: bottom;
				display: inline-block;
				background: #f00;
				color: #fff;
				font-size: 11px;
				padding: 0 7px;
				border-radius: 3px;
				display: inline-block;
			}
			/* 自营标签 */
			i.vip_tag{
				margin: 0;
				vertical-align: bottom;
				display: inline-block;
				background: #f00;
				color: #fff;
				font-size: 11px;
				padding: 0 7px;
				border-radius: 3px;
				display: inline-block;
				position: absolute;
				left: 0;
				top: 0;
			}
		</style>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210"/>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}/layer/layer.m.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>	
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/special.css?213"/>

		<script type="text/javascript" charset="utf-8">			
			var getCouponUrl = "{pigcms{:U('ajax_get_coupon_by_ids')}";
			var deliverName = "{pigcms{$config['deliver_name']}";
			var currency_symbol = "{pigcms{$config.Currency_symbol}";
			//专题页面类型
			type="{pigcms{$now_special.type|default=1}";
			type=parseInt(type);
			if(type==3){
				var getShopUrl = "{pigcms{:U('ajax_get_mall_goods_by_ids')}";
			}else{
				var getShopUrl = "{pigcms{:U('ajax_get_shop_by_ids')}";
			}
			var userPhone = "{pigcms{$user_session.phone}";
			var receiveCouponUrl = "{pigcms{:U('Systemcoupon/had_pull')}";
			var LoginUrl = "{pigcms{:U('Login/index')}";
			var BindPhoneUrl = "{pigcms{:U('My/bind_user')}";
			var hasLogin = <if condition="$user_session">true<else/>false</if>;
			var is_scroller = true;
			<if condition="$long_lat">var user_long = "{pigcms{$long_lat.long}",user_lat = "{pigcms{$long_lat.lat}";<else/>var user_long = '0',user_lat  = '0';</if>
			$(function(){
				var w=$('body').width();
				w=w*0.47;
				$('html').css('font-size',w);	
			});
			
		</script>
		<script>
		var currency_symbol = "{pigcms{$config.Currency_symbol}";
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/special.js?1" charset="utf-8"></script>
		<script charset="utf-8">var currency_symbol = "{pigcms{$config.Currency_symbol}";</script>
		<script type="text/javascript" src="{pigcms{$static_path}shop/js/shopBase.js?1" charset="utf-8"></script>
	</head>
	<body style="max-width:640px;margin:0 auto;">
		<div class="container pageSliderHide" id="containers" style="background:{pigcms{$now_special.bgcolor};max-width:640px;margin:0 auto;padding-bottom:8px;">
            <div id="scroller">
                <div id="pullDown">
                    <span class="pullDownIcon"></span><span class="pullDownLabel">下拉可以刷新</span>
                </div>
			<section id="listHeader" class="roundBg" style="background-color:rgba(6, 193, 174);display:none;">
				<div id="listBackBtn" class="listBackBtn hide"><div></div></div>
				<div id="locationBtn" class="page-link" data-url="address" data-url-type="openRightFloatWindow">
					<span class="location"></span>
					<span id="locationText">正在定位</span>
					<span class="go"></span>
				</div>
				<div id="searchBtn" class="listSearchBtn page-link" data-url="shopSearch"><div></div></div>
			</section>
			<if condition="$now_special['image']">
				<section class="custom-image-swiper">
					<img src="{pigcms{$now_special.image}"/>
				</section>
			</if>
			<if condition="$now_special['coupon']">
				<section class="coupon_list clearfix" data-coupon="{pigcms{$now_special.coupon_id}"></section>
			</if>		
			<div class="addCategoryBtn col-{pigcms{:count($now_special['product_list'])}" <if condition="count($now_special['product_list']) eq 1">style="display:none;"</if>>
				<ul>
					<volist name="now_special['product_list']" id="vo">
						<li data-product="{pigcms{$now_special['product_id_arr'][$key]}">{pigcms{$vo.name}</li>
					</volist>
				</ul>
			</div>
			<div class="productBox goods_box">
			</div>

		<script id="productTpl" type="text/html">
			{{# for(var i in d){ }}
				{{# if(d[i]['cat_fid']>0){ }}
					<div class="app_new_link app-fields ui-sortable productRow cat-{{ nowIndex }} item  {{# if((sort+i)%2==0){  }}odd{{# } }}" data-url="{pigcms{:C('config.site_url')}{pigcms{:U('Mall/detail')}&goods_id={{ d[i].goods_id }}">
				{{# }else{ }}					
					<div class="app-fields ui-sortable productRow cat-{{ nowIndex }}  item  {{# if((sort+i)%2==0){  }}odd{{# } }}" style='opacity:0.6;'>
				{{# } }}
					{{# if(d[i]['cat_fid']<=0){ }}
						<p class='goods_down'>已下架</p>
					{{# } }}
					<img src="{{ d[i]['image']  }}" title="{{ d[i]['name']  }}  " />
					{{# if (d[i].vip_show){}}
						<i class="vip_tag">{{d[i].vip_tag}}</i>
					{{# } }}
					
					<div class="content">
						<p class="title">
							{{# if(d[i].is_self==1){ }}
								<i class="self_tag">自营</i>
							{{# } }}
							{{ d[i]['name'] }}
						</p>
						<p>
							{{# if (d[i].vip_show){}}
								<span style=' font-size: .1rem;  color: #f00; font-weight: bold;'>{pigcms{$config['Currency_symbol']}{{ d[i]['vip_price'] }}</span>
								<del>{pigcms{$config['Currency_symbol']}{{ d[i]['price'] }}</del>
							{{# }else { }}
								{{# if (d[i].is_seckill_price){}}
									<span style='font-size: .1rem;  color: #f00;float: left;  font-weight: bold;'>{pigcms{$config['Currency_symbol']}{{ d[i]['seckill_price'] }}</span>
									{{# if (parseFloat(d[i]['price']) > parseFloat(d[i]['seckill_price'])){}}
										<del>{pigcms{$config['Currency_symbol']}{{ d[i]['price'] }}</del>
									{{# } }}
								{{# }else{ }}
									<span style=' font-size: .1rem;  color: #f00;float: left;  font-weight: bold;'>{pigcms{$config['Currency_symbol']}{{ d[i]['price'] }}</span>	
								{{# } }}
							{{# } }}
							{{# if(d[i].sell_count>0){  }}
								<span style='float:right;'>销量{{d[i].sell_count}}件</span>
							{{# } }}
						</p>
						{{# if (!d[i].act_info){ }}
						<p class='label_box'>
							{{# if(d[i].sell_count==0 && d[i].is_new==1){  }}	
			                	<label class='seckill' style='background-color:#CDB795;'>新品</label>
			                {{# } }}
			                {{# if (d[i].is_seckill_price){}}
			                    <label class='seckill'>{pigcms{$config.seckill_alias_name|default='限时优惠'}</label>
			                {{# } }}
			            </p>
			            {{# } }}
						{{# if (d[i].act_info && d[i].act_info.price){ }}
			                <div class='act_box' style='height:23px'>
			                    <span class='price'>{pigcms{$config['Currency_symbol']}<i>{{ d[i].act_info.price }}</i></span>
			                    {{# if (d[i].act_info.is_group==1){ }}
			                        <span class='btn'>{{ d[i].act_info.num }}人拼团</span>
			                    {{# }else if (d[i].act_info.is_cut==1){ }}
			                        <span class='btn'>砍价拿</span>
			                    {{# } }}
			                </div>
						 {{# }else{ }}
						  <div class='act_box' style='height:23px;display:{{ View.height(d[i],d,i) }}'>  </div>
						{{# } }}
					</div>
				</div>
			{{# } }}
		</script>
		<script id="couponTpl" type="text/html">
			{{# for(var i in d){ }}
				<a href="javascript:;" class="couponRow {{# if(d[i].can == false){ }}coupon_on1{{# } }}" data-id="{{ d[i].id }}" data-name="{{ d[i].name }}" data-order_money="{{ d[i].order_money }}" data-discount="{{ d[i].discount }}">
					<p class="coupon_name pull-left"><span>{{ d[i].name }}</span></p>
					<p class="coupon_monery pull-left">{{ d[i].discount }}</p>
					<p class="coupon_use pull-left oneline">满{{ d[i].order_money }}{pigcms{$config['Currency_txt']}可用</p>
					<span class="icon2"></span>
				</a>
			{{# } }}
		</script>
            <div id="moress" style="text-align:center;padding:10px;">正在加载...</div>
            <div id="enddate" style="text-align:center;padding:10px;display:none;">没有更多数据了</div>
            <div id="pullUp" style="bottom:-60px;">
                <img src="{pigcms{$config.site_logo}" style="width:130px;height:40px;margin-top:10px"/>
            </div>
        </div>
    </div>
<script type="text/javascript">
var sort=0;
$('.addCategoryBtn li').click(function(){
	var key=$(this).index();
	sort=$('.goods_box .cat-'+key).length;
});
</script>
<script type="text/javascript">
window.shareData = {  
	"moduleName":"Special",
	"moduleID":"0",
	"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>", 
	"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Special/index',array('id'=>$now_special['pigcms_id']))}",
	"tTitle": "{pigcms{$now_special.name}",
	"tContent": "{pigcms{$now_special.desc}"
};
</script>
{pigcms{$shareScript}
<script>
$(document).on('click','.app_new_link',function(e){
	e.preventDefault();
	e.stopPropagation();
	var url = $(this).attr('data-url');
	if(url.indexOf('http://')===-1 && url.indexOf('https://')===-1){
		url = 'https://' + document.domain + url;
	}
	if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase()))){
		var reg = /versioncode=(\d+),/;
		var arr = reg.exec(navigator.userAgent.toLowerCase());
		if(arr == null){
			
		}else{
			var version = parseInt(arr[1]);
			if(version >= 1400){
				if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
					var iosUrl = window.btoa(url);
					iosUrl = iosUrl.replace('/','&');
					window.webkit.messageHandlers.appJSBridgeMethod.postMessage({name:'createwebview', url:iosUrl, webUrl: url});
					return false;
				}else{
					window.lifepasslogin.createwebview(url);
					return false;
				}
			}
		}
	}else if(typeof wx !=='undefined' && url.indexOf('javascript')===-1 && window.__wxjs_environment === 'miniprogram'){
		if(url == location.href){
			return false;
		}
		wx.miniProgram.navigateTo({url:'/pages/webview/webview?webview_url='+encodeURIComponent(url)+'&webview_title='+encodeURIComponent('打开页面')});
		return false;
	}
	location.href = url;
});
</script>
	</body>
</html>