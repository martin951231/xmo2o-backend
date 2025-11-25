<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta http-equiv="Expires" content="-1">
	<meta http-equiv="Cache-Control" content="no-cache">
	<meta http-equiv="Pragma" content="no-cache">
	<meta name="format-detection" content="telephone=no">
	<meta charset="utf-8">
	<title>商城分类</title>
	<link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet">
	<link href="{pigcms{$static_path}mall/css/load.css" rel="stylesheet" />
	<link href="{pigcms{$static_path}mall/css/style.css"  rel="stylesheet">
	<link href="./tpl/Wap/default/static/bbs/css/iscroll.css" rel="stylesheet">
	<link href="{pigcms{$static_path}mall/css/swiper.min.css" rel="stylesheet">
	<link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet">
	<link href="{pigcms{$static_path}mall/css/category.css" rel="stylesheet">
	<script src="{pigcms{$static_path}mall/js/jquery.min.js"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/common.js?t=12955" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}mall/js/iscroll.min.js?t=12955" charset="utf-8"></script>
	<script>var $home=true</script>
	<style type="text/css">
	.ui-grid-trisect>ul li.active{width:auto;min-width:26%;margin:0 2% 2% 0;background:#f4f4f4;border-radius:5px;padding:11px 2%;}
	.foot_left .foodnav li a {  border-left: unset;  color: #555;  }
	.foot_left .foodnav li a.on {  border-left: unset;  background: unset;  }
	.foot_left .foodnav li {  padding: 18px 10px 18px 5px;  }
	.foot_left {  width: 105px  }
	.btn{color:#555;}
	.b_l{ margin-left: 0px; width: 0px;  height: 20px;}
	.btn.on .b_l{border-left: 3px solid #ff4d45; color:#ff4d45}
	.btn.on a{color:#ff4d45 !important;}
	.btn.on{background: #fff;}
	#pageLoadTip{display:none !important;}
	</style>
</head>
<body class="bg_f">
	<div class="demo-block search-box">
		<div class="ui-searchbar-wrap ui-border-b ">
			<div class="ui-searchbar tjt-searchbar align_left">
				<i class="ui-icon-search"></i>
				<!-- <div class="ui-searchbar-text cursor " style="-webkit-box-flex: 1">搜索商品</div> -->
				<input class="ui-searchbar-text cursor " style="-webkit-box-flex: 1"  placeholder="搜索商品" id="search"/>
				<!-- <div class="ui-searchbar-input search_input"><input value="" type="text" placeholder="搜索商品" autocapitalize="off"></div>
				<i class="ui-icon-close cursor "></i> -->
			</div>
			<button class="ui-searchbar-cancel search_input">取消</button>
		</div>
	</div>
  	<div class="contenr" style="position:fixed;bottom:49px;top: 46px;overflow:hidden;width:100%">
		<div class="foot_left ">
			<ul class="foodnav align_center" style="    background: #f3f3f3;">
			<volist name='category_list' id='vo' key='i'>
				<li class="btn flex flex_baseline <eq name="i" value='1'>on</eq>" data-fid="{pigcms{$vo.id}" >
					<div class="b_l  " ></div>
					<a class="font_size14 flex_one  " href="javascript:;" >{pigcms{$vo.name}</a>
				</li>
			</volist>
			</ul>
		</div>
       	<div class="foot_right  pl10 pr10 pt10" id='page'>
       		<volist name='category_list' id='vo' key='k'>
   			<div <gt name='k' value='1'>style="display:none;"</gt> class="menu_{pigcms{$vo.id}">
   				<notempty name="vo.banner_info.image">
		       		<div class="w100">
		       			<a href="{pigcms{$vo.banner_info.url}">
							<img src="{pigcms{$vo.banner_info.image}" title='{pigcms{$vo.name}' class="w100 h100"/>
						</a>
					</div>
				</notempty>
				<div class="demo-item">
					<p class="demo-desc color_6 pl0">{pigcms{$vo.name}</p>
					<div class="demo-block">
						<div class="ui-grid ui-grid-trisect">
							<ul class="">
								<volist name='vo.son_list' id='vo2'>
									<if condition="$icon_arr[$k] eq 1">
										<li class="mb_10">
											<a href="{pigcms{:U('Mall/goods_list', array('cat_fid' => $vo['id'], 'cat_id' => $vo2['id']))}" class="app_new_link">
												<div class="ui-img-vertical">
													<notempty name='vo2.icon_path'>
														<span>
															<img src="{pigcms{$vo2.icon_path}" title="{pigcms{$vo2.name}" class="sub_cat_logo" style="width:80%;height:80%;margin-left:10%;"/>
														</span>
													</notempty>
												</div>
												<div class="ui-grid-info">
													<p class=" pd0 align_center">{pigcms{$vo2.name}</p>
												</div>
											</a>
										</li>
									<else/>
										<li class="active">
											<a href="{pigcms{:U('Mall/goods_list', array('cat_fid' => $vo['id'], 'cat_id' => $vo2['id']))}" target="_blank">
												<div class="ui-grid-info active">
													<p class=" pd0 align_center">{pigcms{$vo2.name}</p>
												</div>
											</a>
										</li>
									</if>
								</volist>
							</ul>
						</div>
					</div>
				</div>
			</div>
			</volist>
       	</div>
      	<div style="clear:both"></div>
  	</div>
	<include file="footer"/>
<script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
<script src="{pigcms{$static_path}mall/js/swiper.min.js"></script>
<script>
    var View={
        init:function(){
         	this.showInit();
         	this.initClick();
        },
		setting:{
			l_h:0,
			max_h:0
		},
        showInit:function(){
			if (!$('.footerMenu').height()) {
				$('.contenr').css('bottom', '0px')
			}
			
			$('.sub_cat_logo').height($('.sub_cat_logo').width());
			
			var self=this;
            var h=$(window).height();
			var se=$(".search-box").height();
			var f=$(".footerMenu").height();
			console.log(se,f)
            $(".foot_left").css("height",(h-se-f)+'px')
			//$(".swiper-container").css("height",(h-se-f)+'px')
			$(" .foot_right").css("height",(h-se-f-10)+'px');
			this.setting.l_h=h-se-f;

			/*var len=$(".foodnav li").length;
			 if(len>0){
				 var one=(parseFloat($(".foodnav li").eq(0).css("height"))+35)*len;
				 if((h-se-f)<one){
					 $(".foodnav li").eq(len-1).css('padding-bottom','35px')
				 }
			 }*/
			$(".foot_left  ul>li").each(function(e,item){
				self.setting.max_h+=item.offsetHeight

			})
			/*var myScroll = new IScroll('.foot_left', {
				eventPassthrough: true,
				scrollX: false,
				scrollY: true,
				preventDefault: false
			});*/
			APP.hideLoad()
        },
        initClick:function(){
			var self=this;
        	$('.btn').click(function(){
				var scTop=$(".foot_left ul").scrollTop();
        		var fid=$(this).data('fid');
        		if(fid>0){
        			$(this).addClass('on').siblings().removeClass('on');
        			$('.foot_right>div').hide();
        			$('.foot_right>div.menu_'+fid).show();
        		}
				var oh=this.offsetHeight;
				var top=this.offsetTop;
				console.log(this.offsetTop,this.offsetHeight)
				if(self.setting.max_h>self.setting.l_h){
					if(this.offsetTop<=(self.setting.l_h)/2){
						 if(scTop>0){
							 $(".foot_left ul").animate({"scrollTop":top-oh},200,'linear',function(){

							 });
						 }else{
							 $(".foot_left ul").animate({"scrollTop":0},200,'linear',function(){

							 });
						 }

					}else{

							    if((self.setting.max_h-this.offsetTop)-(self.setting.l_h-this.offsetTop)>=this.offsetHeight){

									$(".foot_left ul").animate({"scrollTop":oh+scTop},200,'linear',function(){

									});
								}else{
									$(".foot_left ul").animate({"scrollTop":self.setting.max_h-self.setting.l_h-+scTop},200,'linear',function(){

									});

								}


						}

				}

        		if ($(this).position().top > 100) {
        			$('.foot_left').scrollTop( $(this).position().top - $('.foot_left li').eq(1).position().top )
        		}
        		if ($(this).offset().top < 100) {
					console.log($(this).position().top,$('.foot_left li').eq(2).position().top)
        			$('.foot_left').scrollTop($(this).position().top - $('.foot_left li').eq(2).position().top)
        		}
				
				$('.sub_cat_logo').height($('.menu_' + fid + ' .sub_cat_logo').width());
        	});
        }
    }
    window.View=View;
    View.init();
</script>
<script type="text/javascript">
	$(document).on('click','.app_new_link',function(e){
			e.preventDefault();
			e.stopPropagation();
			var url = $(this).attr('href');
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
			}
			location.href = url;
		});
$(function(){
    var url="{pigcms{:U('Mall/search')}";
    $(document).on("click",'#search',function(){
        window.location.href=url;
    });
	$('#search').keydown(function(e){
		if(e.keyCode==13){
			var val=$(this).val();
			if(val!=''){
				location.href="{pigcms{:U('Mall/search')}&key="+val;
			}
		}
	})
})
</script>
<script type="text/javascript">
    window.shareData = {
        "moduleName":"Mall",
        "moduleID":"0",
        "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/index')}",
        "tTitle": "{pigcms{$config.mall_alias_name|default='商城'}",
        "tContent": "{pigcms{$config.site_name}"
    };
</script>
{pigcms{$shareScript}
</body>
</html>