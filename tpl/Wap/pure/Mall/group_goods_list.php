<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta http-equiv="Expires" content="-1">
	<meta http-equiv="Cache-Control" content="no-cache">
	<meta http-equiv="Pragma" content="no-cache">
	<meta name="format-detection" content="telephone=no">
	<meta charset="utf-8">
	<title>拼团列表</title>
	<link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet">
	<link href="{pigcms{$static_path}mall/css/swiper.min.css" rel="stylesheet" />
	<link href="{pigcms{$static_path}mall/css/style.css"  rel="stylesheet">
	<link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet">
	<link href="{pigcms{$static_path}mall/css/iconfont/iconfont.css" rel="stylesheet" />
	<link href="{pigcms{$static_path}mall/css/pullToRefresh.css" rel="stylesheet">
	<link href="{pigcms{$static_path}mall/css/swiper.min.css" rel="stylesheet">
	<style>
		#pageLoadTip{display:none !important;}
		body{background:#fff}
		#ui_list li>a{
			position: absolute;
			width: 100%;
			height: 100%;
			top:0;
			left:0;
			z-index:1;
		}

		.ping_tuan .pin_num span {
			padding: 0px 2px 2px 8px;
		}
		#refresh{
			top:0px
		}

	</style>
</head>
<body>

<div class="content">
	<div class="demo-block ping_tuan" id="refresh" >
		<div id="ui_list">
			<div class="swiper-container img-box" >
				<div class="swiper-wrapper">
					<volist name='banner_list' id='vo'>
						<div class="swiper-slide">
							<a href="{pigcms{$vo.url}">
								<img style="height: 1.4rem;display: block;" src="{pigcms{$vo.pic}" width="100%" class="h100" />
							</a>
						</div>
					</volist>
				</div>
				<div class="swiper-pagination"></div>
			</div>
			<ul class="ui-list ui-border-tb h100 list"  id='goods_box'>

			</ul>
		</div>

	</div>

</div>

<!-- 底部导航 -->
<div>
	<div class="footerMenu wap footer ui-border-t">
		<ul class="flex">
			<li class="flex_one ui-border-r">
				<a class="can active" href="javascript:;" data-url="{pigcms{:U('Mall/group_goods_list')}">
					<div class="flex w100 h100"  style="justify-content: center;">
						<em class="group_good" style="margin: unset;width:18px;height:18px"></em>
						<p style="padding:3px 0px 0px 3px">拼团商品</p>
					</div>
				</a>
			</li>
			<li class="flex_one">
				<a href="{pigcms{:U('Mall/my_group_act')}" class="juan " data-url="{pigcms{:U('Mall/my_group_act')}">
					<div class="flex w100 h100" style="justify-content: center;">
						<em class="my_act" style="margin: unset;width:18px;height:18px"></em>
						<p style="padding:3px 0px 0px 3px">我的拼团</p>
					</div>
				</a>
			</li>
		</ul>
	</div>
</div>
<script type="text/html" id="detail">
	<& for(var i=0;i< _this.length;i++) {&>
	<& var _item=_this[i] &>
	<li class="ui-border-t ">
		<a href="{pigcms{:U('Mall/detail',array('store_id'=>$store_id))}&goods_id=<&= _item.goods_id&>&group_id=<&= _item.group_id&>"></a>
		<div class="ui-list-img-square">
			<span>
				<img src="<&= _item.image&>" class='w100 h100'>
			</span>
		</div>
		<div class="ui-list-info mg_r10">
			<h4 class="font_size14 mb_8 ellipsis" style="line-height: 18px;"><&= _item.name&></h4>
			<div class="color_9 font_size12" style="line-height: normal;margin-top:22px">单买价 <span class="border_center rel">{pigcms{$config['Currency_symbol']}<&= _item.price &></span></div>
			<div class="demo-block">
				<ul class="ui-list ui-list-single ui-list-link mb_0 ">
					<li class="after ml_0">
						<div class="ui-list-info pd0">
							<h4 class="ui-nowrap money"><&= _item.complete_num&>人团&nbsp;&nbsp;{pigcms{$config['Currency_symbol']}<span class="font_size20"><&= _item.group_price&></span></h4>
							<div class="ui-txt-info font_size14 color_7 " style="line-height: 24px;">
								<p class="ui-nowrap pin_num inline color_red">
									<& if(_item.act_status==0){ &>
                                        <span class="color_f inline disabled">未开始</span>
                                    <& }else if(_item.act_status==2){ &>
                                        <span class="color_f inline disabled">已结束</span>
                                    <& }else if(_item.act_status==1){ &>
                                        <& if(_item.has_stock==0){ &>
                                            <span class="color_f inline disabled">已拼完</span>
                                        <& }else{ &>
                                            <span class="color_f inline">去拼团<i class="icon iconfont color_f">&#xe616;</i>
                                        <& } &>
                                    <& } &>
								</span></p>
							</div>
						</div>
					</li>
				</ul>
			</div>
		</div>
	</li>
	<& }&>
	<& if(View.isEmpty(_this)){&>
	<li class="w100 align_center" style="width:100%">
		<div class="weui-loadmore w100" >
			<div><img src="{pigcms{$static_path}mall/images/noresult.png"/></div>
			<span class="weui-loadmore__tips" style="color: #999">暂无拼团商品</span>
		</div>
	</li>
	<&}&>
</script>
<script src="{pigcms{$static_path}mall/js/jquery.min.js"></script>
<script>var $home=false</script>
<script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
<script src="{pigcms{$static_path}mall/js/simplite.js"></script>
<script src="{pigcms{$static_path}mall/js/droprefresh.js"></script>
<script src="{pigcms{$static_path}mall/js/pullToRefresh.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/swiper.js"></script>
<script type="text/javascript">var noAnimate = true;</script>
<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
<script>
	var src="{pigcms{:U('Mall/group_goods_list',array('store_id'=>$store_id))}";
	var View={
		inits:function(){
			this.initDetail();
			this.initClick();
		},
		setting:{
			pageNo:1,
			page_size:10
		},
		isEmpty: function(list){
			var b = View.setting.pageNo == 1 && !list.length;
			if(b){
				$(".list").removeClass("ui-border-tb")
			}else{
				$(".list").addClass("ui-border-tb")
			}
			return b;
		},
		initDetail:function(){
			var _self=this;
			refresher.init({
				id:"refresh",
				pullDownAction:Refresh,
				pullUpAction:Load
			});
			function Refresh(){
				_self.get_group_goods(false,true);
			}
			function Load(){
				_self.get_group_goods(true,true);
			}
			this.get_group_goods(false,false);
			// 轮播
			var wh =$(window).width() * 140 / 375;
			$(".swiper-container img").css("height",wh+'px');
			if($(".swiper-container .swiper-slide").size()>0){
				var myswiper = new Swiper('.swiper-container', {
					pagination: '.swiper-container .swiper-pagination',
					direction : 'horizontal',
					paginationClickable :true,
					autoplay :'5000',
					autoplayDisableOnInteraction : false,
					loop: true,
					video:false
				});
			}else{
				$(".swiper-container").hide().remove()
			}
		},
		initClick:function(){
			$(document).on('click', '.footerMenu a', function(event){
                APP.fnUrlReplace($(this).data('url'));
				$(this).stopPropagation();
            })
		},
		get_group_goods:function(type,fresh){
			var _this=this;
			if(type){
				_this.setting.pageNo++
			}else{
				_this.setting.pageNo=1
			}
			$.post(src,{page:_this.setting.pageNo,limit:_this.setting.page_size},function(data){
				var res=data;
				if(typeof(res)=='string'){
					res=$.parseJSON(data)
				}
				if(res &&  res!=null && res.length>0){
				}else{
					res=[]
				}
				var checkitem = Simplite.getTemplate('detail');
				var compiled_item = Simplite.compile(checkitem)(res);
				if(type){
					$('#ui_list ul#goods_box').append(compiled_item);
				}else{
					$('#ui_list ul#goods_box').html(compiled_item);
				}
				refresher.isEmpty(res,_this.setting.pageNo)
				refresher.isNomore(res,_this.setting.pageNo,'','',res.length==0?true:false)
				setTimeout(function(){
						myScroll.refresh();
				},500)


			});
		}
	}
	window.View=View;
	View.inits();
</script>
<!-- 微信分享 -->
<script type="text/javascript">
    window.shareData = {
        "moduleName":"Mall",
        "moduleID":"0",
        "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/group_goods_list')}",
        "tTitle": "推荐您多款拼团商品",
        "tContent": "点击查看更多进行中拼团商品"
    };
</script>
{pigcms{$shareScript}
</body>
</html>