<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta name="format-detection" content="telephone=no"/>
<meta charset="utf-8">
<title>{pigcms{$store['name']}</title>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}mall/css/iconfont/iconfont.css"/>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/mall.css"/>
<link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}mall/css/index.css"/>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/swiper.min.css"/>
<link href="{pigcms{$static_path}mall/css/photo.css" rel="stylesheet" />
<!-- <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script> -->
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
	<script>var $home=false</script>
	<script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
<script src="{pigcms{$static_path}mall/js/simplite.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>

<script src="{pigcms{$static_path}mall/js/cate_complite.js" type="text/javascript" ></script>
<script src="{pigcms{$static_path}mall/js/iscroll.min.js" type="text/javascript" ></script>
<script>var ajax_url = "{pigcms{:U('Mall/ajax_goods', array('store_id' => $store['id']))}", shopReplyUrl = "{pigcms{$config.site_url}/index.php?g=Index&c=Reply&a=ajax_get_list&order_type=3&parent_id={pigcms{$store['id']}";
var open_extra_price =Number("{pigcms{$config.open_extra_price}");
var extra_price_name ="{pigcms{$config.extra_price_alias_name}";</script>
<!--[if lte IE 9]>
<script src="scripts/html5shiv.min.js"></script>
<![endif]-->
<script type="text/javascript" src="{pigcms{$static_path}mall/js/mallstore.js?5555" charset="utf-8"></script>
<style type="text/css">
	.color_f{color:#fff !important;}
	.bd_a a {  margin: auto !important  }
	.mg
	.flex{display:flex;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; align-items: center}
	.flex_justify{-webkit-box-pack: justify;-webkit-justify-content: space-between;-ms-flex-pack: justify;justify-content: space-between;}
	.tjt_border_t:before{  top: -1px !important;  }
	.tjt_border_b:before{  bottom: -1px !important;  }
	.tjt_border_r:before{  right: -1px !important;  }
	.tjt_border_l:before{  left: -1px !important;  }
	.coupon-box{background:#fff;}
	.coupon-box .swiper-slide{padding: 10px;background: #fff;box-sizing: border-box;}
	.swiper-pagination-bullet{background: rgba(0, 0, 0, .5);}
	.coupon-box .swiper-pagination-bullet-active{background: #06c1ae;}
	.coupon-box .swiper-slide .item{background-image: url("{pigcms{$static_path}mall/images/store_coupon.png");background-size: 100% 100%;position: relative;}
	.coupon-box .swiper-slide .item .left{width: 71.5%;height: 100%;}
	.coupon-box .swiper-slide .item .right{width: 28.5%;height: 100%;}
	.coupon-box .swiper-slide .item .left, .coupon-box .swiper-slide .item .right{position: relative;float: left;}
	.coupon-box .swiper-slide .item .left .content, .coupon-box .swiper-slide .item .right .content{position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);-webkit-transform: translate(-50%, -50%);text-align: center;width: 85%;}
	.coupon-box .swiper-slide .item .left .content .discount{color: #06c1ae;}
	.coupon-box .swiper-slide .item .left .content .discount span{font-size: 22px;font-weight: bold;}
	.coupon-box .swiper-slide .item .left .content .desc-box .text{font-size: 15px;font-weight: bold;color: #333;}
	.coupon-box .swiper-slide .item .left .content .desc-box .price{text-align: left;color: #999;font-size: 12px;}
	.coupon-box .swiper-slide .item .iconfont{vertical-align: middle;}
     .layermcont div{display:none !important;}
	.layermcont {  padding: 20px 15px !important;  }
	.layermbox2 .layermcont i {  width: .1rem !important;  height: .1rem !important; }
	.coupon-box .swiper-slide .item .right .content{width: 80%;padding:5% 0;background:#06c1ae;color: #fff;border-radius: 15px;font-size: 12px;}
	.coupon-box .swiper-slide .item .right .content.disabled{  background: #ccc;  }
	.coupon-box .swiper-slide .rule{padding: 5px;box-shadow: -2px 2px 6px #efefef;border-right: 1px solid #efefef;font-size: 12px;color: #999;display: none;}
	ul.pics{overflow: auto;}
	ul.pics>li{width:80px;height:80px;margin:0px 10px 10px 0;float:left;}
	ul.pics>li img{width:80px;height:80px;}
	#pageLoadTip{display:none !important;}
	.hand_list.active{position: fixed;top: 0;left: 0;right: 0;background-color: #fff;z-index: 99;border-bottom: 1px solid #f4f4f4;}
	#goodssList.active{padding-top: 40px;}
    .tip_yu{
        position: absolute;
        bottom: 0;
        left: 0;
        height: 30px;
        line-height: 30px;
        background-color: rgba(0,0,0,0.6);
        color: #fff;
        width: 100%;
        text-align: center;
        z-index: 999;
    }
</style>
</head>
<body>
	<section class="Florist home" style="margin-top:0px;padding:0px;background: url(<if condition="$store['background']">{pigcms{$store['background']}<else />{pigcms{$static_path}images/ff_02.jpg</if>) center no-repeat; background-size: 100%;">
		<div class="Florist_top clr" style=" padding: 55px 20px 20px 20px;">
			<div class="fl">
				<img src="{pigcms{$store['image']}" width=49 height=49>
			</div>
			<div class="p53">
				<h2>
					<a href="javascript:void(0)" class="clr purl" >
						<span class="fl title">{pigcms{$store['name']}</span>
						<div class="fl">
							<span class="fl markjt"></span>
						</div>
					</a>
				</h2>
				<p style=" color: #fff;">{pigcms{$store['store_notice']}</p>
			</div>
			<div class="all" style=" top: 50px;">
				<h3 style="color:#fff">{pigcms{$goods_count}</h3>
				<p style="color:#fff">全部商品</p>
			</div>
		</div>
		<a href="{pigcms{:U('Mall/search', array('store_id' => $store['id']))}" class="Icon"></a>
	</section>
	
	<notempty name='coupon_list'>
	<section>
		<div class="swiper-container coupon-box" >
            <div class="swiper-wrapper">
            	<volist name='coupon_list' id='vo'>
	                <div class="swiper-slide">
	                	<a href="javascript:;">
	                		<div class="item">
	                			<div class="left">
	                				<div class="content flex flex_justify">
	                					<eq name='vo.is_discount' value='1'>
	                						<div class="discount"><span>{pigcms{$vo.discount_value|floatval}</span>折</div>
										<else/>
											<div class="discount">{pigcms{$config['Currency_symbol']}<span>{pigcms{$vo.discount|floatval}</span></div>
	                					</eq>
	                					
		                				<div class="desc-box">
		                					<div class="text">
		                						<if condition="$vo.is_system eq 1">平台券<else />商家券</if>
		                					</div>
		                					<div class="price">满{pigcms{$vo.order_money|floatval}可用<i class="icon iconfont">&#xe603;</i></div>
		                				</div>
	                				</div>
	                			</div>
	                			<if condition="$vo['receive_status'] eq 2">
	                				<div class="right"><span data-coupon_id='{pigcms{$vo.coupon_id}' class="content disabled">已领完</span></div>
	                			<elseif condition="$vo['receive_status'] eq 1"/>
	                				<div class="right" data-coupon_id='{pigcms{$vo.coupon_id}'><span class="content disabled">已领取</span></div>
	                			<else/>
	                				<div class="right receive_card" data-system="{pigcms{$vo.is_system}" data-coupon_id='{pigcms{$vo.coupon_id}' data-cate_name="{pigcms{$vo.cate_name}"  data-wxcardid="{pigcms{$vo.wx_cardid}" data-tickettime="{pigcms{$vo.wx_ticket_addtime}" data-sign="{pigcms{$vo.cardsign}"  data-status="{pigcms{$vo.status}" data-allow_new="{pigcms{$vo.allow_new}"><span class="content">去领券</span></div>
	                			</if>
	                		</div>
	                	</a>
	                	<div class="rule">
	                		<div>描述：{pigcms{$vo.des}</div>
	        				<div>使用平台：{pigcms{$vo.platform}</div>
	        			</div>
	                </div>
                </volist>                
            </div>
            <div class="swiper-pagination"></div> 
        </div>
	</section>
	</notempty>

	<section class="menus">
		<div class="switch">
			<ul class="clr">
				<li class="icons on"  data-type="goods">全部商品</li>
				<li class="icons" data-type="reply">评价</li>
				<li class="icons"  data-type="detail">商家详情</li>
				<li class="slider"></li>
			</ul>
			<input type="hidden" id="isLoadReply" value="0"/>
		</div>
		<div class="hand"> 
			<div class="hand_list" id='fixedNav'>
				<ul class="clr">
					<li class="sort <if condition="$now_sort['sort_id']">on</if>">
						<span data-sort_id="{pigcms{$now_sort['sort_id']}">{pigcms{$now_sort['sort_name']}</span>
						<div class="halist_n">
							<dl>
								<dd data-sort_id="0"><div class="agio_top">全部</div></dd>
								<volist name="sort_list" id="sort">
								<dd data-sort_id="{pigcms{$sort['sort_id']}">
									<div class="agio_top">{pigcms{$sort['sort_name']}</div>
									<if condition="$sort['sort_discount']">
									<div class="agio_end">
										<div class="disink clr">
											<em class="agio fl">{pigcms{$sort['sort_discount']}折</em>
											<em class="fl">折扣不同享</em>
										</div>
									</div>
									</if>
								</dd>
								</volist>
							</dl>
						</div>
					</li>
					<li class="sorts on" data-sort="1">
						<span>销量</span>
					</li>
					<li class="sorts" data-sort="2">
						<span>价格</span>
					</li>
					<!--li class="sorts" data-sort="3">
						<span>评分</span>
					</li-->
				</ul>
			</div>
			<div class="bd_a clr ui-grid bg_f" id="goodssList">

			</div>
			<div class="demo-list" id="check_item">
			   <div class="ui-panel ui-grid ui-grid-trisect pt0 pb0  mb_0" id="youxuan" ><ul id="" class="clr"></ul></div>
			</div>
		</div>
		
		<div class="hand" style="display: none;">
			<div class="hand_discuss">
				<ul class="clr">
					<li class="on" data-tab="">
						<h2>全部评价</h2>
						<p>0</p>
					</li>
					<li data-tab="good">
						<h2>满意</h2>
						<p>0</p>
					</li>
					<li data-tab="wrong">
						<h2>不满意</h2>
						<p>0</p>
					</li>
				</ul>
			</div>
			
			<section class="evaluate evaluates">
				<dl>

				</dl>
			</section> 
		</div>

		<div class="hand" style="display: none;">
			<div class="hand_dz">
				<ul>
					<li class="photo more">
						<a href="javascript:void(0);">
							<span></span>
							<div class="p25 phone" data-phone="{pigcms{$store['phone']}">店铺电话：{pigcms{$store['phone']}</div>
						</a>
					</li>
					<li class="dp more">
						<a href="{pigcms{:U('Mall/addressinfo', array('store_id' => $store['id']))}">
							<span></span>
							<div class="p25">店铺地址：{pigcms{$store['adress']}</div>
						</a>
					</li>
					<!--<li class="time">
						<span></span>
						<div class="p25">营业时间：{pigcms{$store['time']}</div>
					</li>-->

					<li class="gog">
						<span></span>
						<div class="p25">店铺公告：{pigcms{$store['store_notice']}</div>
					</li>
				</ul>
			</div>
			<if condition="$store['coupon_list']">
			<div class="hand_dz">
				<ul>
					<if condition="isset($store['coupon_list']['invoice']) AND $store['coupon_list']['invoice']">
					<li class="cc">
						<span>
							<em class="color_f">票</em>
						</span>
						<div class="p25">满{pigcms{$store['coupon_list']['invoice']}{pigcms{$config['Currency_txt']}支持开发票，请在下单时填写发票抬头</div>
					</li>
					</if>
					<if condition="isset($store['coupon_list']['discount']) AND $store['coupon_list']['discount']">
					<li class="aa">
						<span>
							<em class="color_f">折</em>
						</span>
						<div class="p25">店内全场{pigcms{$store['coupon_list']['discount']}折</div>
					</li>
					</if>
					<if condition="$store['txt_discount']['sys_newuser']">
					<li class="ff">
						<span>
							<em class="color_f">首</em>
						</span>
						<div class="p25">平台首单{pigcms{$store['txt_discount']['sys_newuser']}</div>
					</li>
					</if>
					<if condition="$store['txt_discount']['sys_minus']">
					<li class="cc">
						<span>
							<em class="color_f">减</em>
						</span>
						<div class="p25">平台满减{pigcms{$store['txt_discount']['sys_minus']}</div>
					</li>
					</if>
					<if condition="$store['txt_discount']['mer_newuser']">
					<li class="ff">
						<span>
							<em class="color_f">首</em>
						</span>
						<div class="p25">店铺首单{pigcms{$store['txt_discount']['mer_newuser']}</div>
					</li>
					</if>
					<if condition="$store['txt_discount']['mer_minus']">
					<li class="cc">
						<span>
							<em class="color_f">减</em>
						</span>
						<div class="p25">店铺满减{pigcms{$store['txt_discount']['mer_minus']}</div>
					</li>
					</if>
					<notempty name="store['auth_files']">
					<li class="">
						<span></span>
						<div class="p25">资质证照
							
						</div>
						<div class="p25" style="margin-top: 5px;">
							<volist name="store['auth_files']" id="vo">
							<img style="width:100px;height: 100px;" src="{pigcms{$vo}" class="pimg" />
							</volist>
						</div>
					</li>
					</notempty>
				</ul>
			</div>
			</if>
		</div>
	</section>
	<div class="pageSliderHide"></div>
	<!-- 浮动tabble -->
	<include file="floatTabble"/>
	<!-- 店铺资质证照 点击查看大图 start-->
	<div id="outerdiv" style="position:fixed;top:0;left:0;background:rgba(0,0,0,0.7);z-index:2;width:100%;height:100%;display:none;">
	    <div id="innerdiv" style="position:absolute;">
	        <img id="bigimg" style="border:5px solid #fff;" src="" />
	    </div>
	</div>
	<script type="text/javascript">
		    $(function(){  
        $(".pimg").click(function(){  
            var _this = $(this);//将当前的pimg元素作为_this传入函数  
            imgShow("#outerdiv", "#innerdiv", "#bigimg", _this);  
        });  
    });  
 
    function imgShow(outerdiv, innerdiv, bigimg, _this){  
        var src = _this.attr("src");//获取当前点击的pimg元素中的src属性  
        $(bigimg).attr("src", src);//设置#bigimg元素的src属性  
      
            /*获取当前点击图片的真实大小，并显示弹出层及大图*/  
        $("<img/>").attr("src", src).load(function(){  
            var windowW = $(window).width();//获取当前窗口宽度  
            var windowH = $(window).height();//获取当前窗口高度  
            var realWidth = this.width;//获取图片真实宽度  
            var realHeight = this.height;//获取图片真实高度  
            var imgWidth, imgHeight;  
            var scale = 0.8;//缩放尺寸，当图片真实宽度和高度大于窗口宽度和高度时进行缩放  
              
            if(realHeight>windowH*scale) {//判断图片高度  
                imgHeight = windowH*scale;//如大于窗口高度，图片高度进行缩放  
                imgWidth = imgHeight/realHeight*realWidth;//等比例缩放宽度  
                if(imgWidth>windowW*scale) {//如宽度扔大于窗口宽度  
                    imgWidth = windowW*scale;//再对宽度进行缩放  
                }  
            } else if(realWidth>windowW*scale) {//如图片高度合适，判断图片宽度  
                imgWidth = windowW*scale;//如大于窗口宽度，图片宽度进行缩放  
                            imgHeight = imgWidth/realWidth*realHeight;//等比例缩放高度  
            } else {//如果图片真实高度和宽度都符合要求，高宽不变  
                imgWidth = realWidth;  
                imgHeight = realHeight;  
            }  
                    $(bigimg).css("width",imgWidth);//以最终的宽度对图片缩放  
              
            var w = (windowW-imgWidth)/2;//计算图片与窗口左边距  
            var h = (windowH-imgHeight)/2;//计算图片与窗口上边距  
            $(innerdiv).css({"top":h, "left":w});//设置#innerdiv的top和left属性  
            $(outerdiv).fadeIn("fast");//淡入显示#outerdiv及.pimg  
        });  
          
        $(outerdiv).click(function(){//再次点击淡出消失弹出层  
            $(this).fadeOut("fast");  
        });  
    }  
	</script>
	<!-- 店铺资质证照 点击查看大图 end-->
</body>
<script type="text/html" id="goodsListBoxTpl">
<& for(var i=0;i< _this.goods_list.length;i++) {&>
<& var _item=_this.goods_list[i] &>
<li class="w47 ui-border-t tjt_border_t  <&= i%2==0?'clear':'' &>" style="margin:<&= i%2==0?'2% 1% 0% 2%;':' 2% 2% 0% 1%;' &>">
	<a href="<&= _item.url&>" style='width:100%;border:none' target="_blank">
		<div class="pt0 pb0  bg_f  ui-border-l tjt_border_l">
			<div class="ui-border-r tjt_border_r">
				<div class="ui-img-vertical" style="padding-top:100%">
					<span class="pd0">
						<img src="<&= _item.image&>"  style="width:<&= View.setting.pic_w&>px;height:<&= View.setting.pic_w&>px"/>
					</span>
                    <& if(_item.is_book==1){ &>
                    <div class="tip_yu" style="background-color: #06c1ae; opacity:0.85">预售</div>
                    <& } &>
				</div>
				<div class="ui-grid-info  m_0  ui-border-b"   style="height:<&= View.height(_this.goods_list,_item,i) &>" >
					<h4 class="ui-nowrap-multi pl5 " style="height:<&= View.height1(_this.goods_list,_item,i) &>" >
						<& if(_item.is_self==1){ &>    
                            <i class="self_tag">自营</i>
                        <& } &>
						<&= _item.name&>
						<& if(_item.act_info && _item.act_info.spec_str){ &>
						<span class='font_size13 color_7 m_0'>(<&= _item.act_info.spec_str &>)</span>
						<& } &>
					</h4>

					<& if((_item.price&& _item.price!='') ||(_item.sell_count && _item.sell_count!='')){ &>
					<p class="pl5 pb0">
						<span class="font_size18 color_red font-weight" >
                            	<& if(_item.price_type == 2){ &>
                                    {pigcms{$config['Currency_symbol']}<&= _item.account_money&>+<&= _item.account_score&><span style="font-size: 12px">{pigcms{$config['score_name']}</span>
                                <& }else{ &>
                                    {pigcms{$config['Currency_symbol']}<&= _item.price&>
                            	<& } &>
                        </span>
						<& if(_item.is_seckill_price){ &>
						<span class="font_size2  color_9 border_center rel" style="font-size:12px">原价{pigcms{$config['Currency_symbol']}<&= _item.old_price &></span>
						<& } &>
						<& if(_item.sell_count>0){ &>
						<span class="font_size12 color_7  right pt5">已售<&= _item.sell_count&></span>
						<& } &>
					</p>
					<& } &>

					<& if(_item.act_info && _item.act_info.price){ &>
					<p class="pb5 pl5">
						<span class="font_size16 color_red ">{pigcms{$config['Currency_symbol']}<&= _item.act_info.price &></span>
						<& if(_item.act_info.is_group==1){ &>
						<span class="font_size12 color_f bg_r pd1_3 fr" ><&= _item.act_info.num &>人拼团价</span>
						<& }else if(_item.act_info.is_cut==1){ &>
						<span class="font_size12 color_f bg_r pd1_3 fr">砍价拿</span>
						<& } &>
					</p>
					<& }else if((_item.sell_count==0 && _item.is_new==1) ||(_item.is_seckill_price && _item.is_seckill_price!='')){ &>
					<p class=" pb0 pl5">
						<& if(_item.sell_count==0 && _item.is_new==1){ &>
						<span class="font_size12 color_f bg_795 pd1_3 left" style="margin-right:10px">新品</span>
						<& } &>
						<& if(_item.is_seckill_price){ &>
						<span class="font_size12 color_f bg_fa pd1_3 left">{pigcms{$config.seckill_alias_name|default='限时优惠'}</span>
						<& } &>
					</p>
					<& } &>
				</div>
			</div>
		</div>
	</a>
</li>
<& }&>
</script>

<script id="shopReplyTpl" type="text/html">
{{# for(var i = 0, len = d.length; i < len; i++){ }}
<dd>
	<div class="title clr">
		<h2 class="fl clr">
			<img src="{{# if(d[i].avatar!= ''){}}{{ d[i].avatar }}{{# }else{ }}/static/images/portrait.jpg{{# } }}" width=20 height=20 class="fl">
			<span class="fl">{{ d[i].nickname }}</span>
		</h2>
		<div class="atar_Show fr">
			<p tip="{{ d[i].score }}"></p>
		</div>
	</div>
	<div class="content">{{ d[i].comment }}</div>
	{{# if(d[i].pics && d[i].pics.length>0){ }}
		<ul class='pics'>
			{{# for(var j=0;j<d[i].pics.length;j++){ }}
				<li data-index="{{j}}">
					<img src="{{d[i]['pics'][j]['image']}}" data-index="{{j}}" data-pics="{{d[i]['pics'][j]['image']}}" />
				</li>
			{{# } }}	
		</ul>
	{{# } }}

	{{# if(d[i].goods){ }}
		{{# var tmpGoods = d[i].goods; }}
		<div class="attr">点赞商品：
		{{# for(var k in tmpGoods){ }}
			{{ tmpGoods[k] }} 
		{{# } }}
		</div>
	{{# } }}
	<div class="attr clr">
		<span>发表日期：{{ d[i].add_time_hi }}</span>
	</div>
	{{# if(d[i].merchant_reply_time != '0'){ }}
	<div class="date">
		<div class="data_n">
			<div class="data_top clr">
				<h2 class="fl">商家回复</h2>
				<span class="fr">{{ d[i].merchant_reply_time_hi }}</span>
			</div>
			<p>{{ d[i].merchant_reply_content }}</p>
		</div>
	</div>
	{{# } }}
</dd>
{{# } }}
</script>
<script type="text/javascript">
	window.shareData = {
		"moduleName":"Mall",
		"moduleID":"0",
		"imgUrl": "{pigcms{$store['image']}",
		"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/store', array('store_id' => $store['id']))}",
		"tTitle": "{pigcms{$store['name']}",
		"tContent": "{pigcms{$store['name']}"
	};

	// 大图轮播
	$(document).ready(function(){
	    var myswiper = new Swiper('.coupon-box', {
	        // pagination: '.coupon-box .swiper-pagination',
	        paginationClickable :true,
	        autoplayDisableOnInteraction : false,
	        slidesPerView: 1.5,
      		spaceBetween: -10,
	    });
	  	var wh = $('.coupon-box .swiper-slide').eq(0).width() * 110 / 420 + 15
	    $(".coupon-box .item").css("height",wh+'px')

	    $(document).on('click', '.coupon-box .price', function(){
	    	$(this).parents('.swiper-slide').find('.rule').slideToggle('normal')

	    	setTimeout(() => {
	    		if ($(this).parents('.swiper-slide').find('.rule').css('display') == 'block') {
	    			$(this).find('i').html('&#xe6ea;')
	    		} else {
	    			$(this).find('i').html('&#xe603;')
	    		}
	    	}, 601)
	    	
	    })
	});
    

</script>
<script>
	<if condition="!$is_weixin_browser">
		var is_weixin_browser = false;
	<else />
		var is_weixin_browser = true;
	</if>
</script>
<script type="text/javascript">
	/*预览图片*/
$("body").on("click",".pics img",function(){
    var album_array = [];
    var  index=parseInt($(this).attr('data-index'));
    $(this).closest(".pics").find('img').each(function(n,v){
        var pics=$(this).data('pics');
        if(pics){
            album_array[n]=pics;
        }
    });
    var pb1 = APP.Photos({
        items:album_array,
        onSlideChange: function(index) {
            console.log(this, index);
        },
        onOpen: function() {
            console.log("onOpen", this);
        },
        onClose: function() {
            console.log("onClose", this);
        }
    });
    pb1.open(index);
});
// 关闭图片大图浏览
function close_swiper(){
    $('.albumContainer').remove();
}

$(function(){
	var fixedTop = $('#fixedNav').offset().top
	$(window).on("scroll", function(){
		if ( $(window).scrollTop() >= fixedTop) {
			$('#fixedNav, #goodssList').addClass('active')
		} else {
			$('#fixedNav, #goodssList').removeClass('active')
		}
    })
})

</script>
<!-- 优惠券领取 -->
<script type="text/javascript">
var is_new =Number("{pigcms{$isnew}");
</script>
<script src="{pigcms{$static_path}mall/js/receive_card.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
{pigcms{$shareScript}
<if condition="$is_app_browser">
<script type="text/javascript">
	if("{pigcms{$app_browser_type}" == 'android'){
		window.lifepasslogin.shareLifePass("{pigcms{$store['name']}", "{pigcms{$store['name']}", "{pigcms{$store['image']}", "{pigcms{$config.site_url}{pigcms{:U('Mall/store', array('store_id' => $store['id']))}");
	} else {
		iosCommon.iosFunction('shareLifePass', {name:'shareLifePass', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
	}
    
</script>
</if>
</html>