<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Shop/index')}" <if condition="!$category">class="on"</if>>分类列表</a>|
				<if condition="$category">
				<a href="{pigcms{:U('Shop/cat_renovation',array('parentid'=>$category['cat_id']))}" class="on">{pigcms{$category.cat_name} - 页面装修</a>|
				</if>
			</ul>
		</div>
		<div class="table-list">
			<div class="fitment_div">
				<div class="form-group default_tpl">
					<div class="group-title">
						<span class="name">背景颜色</span>
						<div class="right_div">
							<input type="text" class="input-text" id="config_bgcolor" value="{pigcms{$shop_decoration_bgcolor}" readonly="readonly" style="text-indent:1000px;width:24px;cursor:default;background-color:{pigcms{$shop_decoration_bgcolor};" />
							<a href="javascript:void(0);" id="choose_color_box_txtactivecolor" style="line-height:28px;">点击选择颜色</a>
						</div>
					</div>
					<div class="group-content">频道页头部背景颜色</div>
				</div>
				<div class="form-group default_tpl">
					<div class="group-title">
						<span class="name">文本与图标颜色</span>
						<div class="right_div">
							<input type="text" class="input-text" id="config_txtcolor" value="{pigcms{$shop_decoration_text_icon}" readonly="readonly" style="text-indent:1000px;width:24px;cursor:default;background-color:{pigcms{$shop_decoration_text_icon}"/>
							<a href="javascript:void(0);" id="choose_color_box_txtcolor" style="line-height:28px;">点击选择颜色</a>
						</div>
					</div>
					<div class="group-content">频道页头部文本与图标颜色</div>
				</div>
				<div class="form-group default_tpl">
					<div class="group-title">
						<span class="name">热门搜索词</span>
						<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Shop/renovation_hot_mall', array('parentid' => $category['cat_id']))}','添加搜索词',660,350,true,false,false,addbtn,'add',true);"  class="btn fitment2">装修</a>
					</div>
					<div class="group-content">频道页专属热门搜索词</div>
				</div>
				<div class="form-group default_tpl">
					<div class="group-title">
						<span class="name">轮播图</span>
						<button type="button" class="btn fitment" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'shop_decoration_carousel','parentid'=>$category['cat_id']))}">装修</button>
					</div>
					<div class="group-content">尺寸为 640*338。</div>
				</div>
				<div class="form-group">
					<div class="group-title">
						<span class="name">导航管理</span>
						<button type="button" class="btn fitment" data-url="{pigcms{:U('Slider/slider_list',array('cat_key'=>'shop_decoration_slider','parentid'=>$category['cat_id']))}">装修</button>
					</div>
					<div class="group-content">每行展示5个，不设置不展示。</div>
				</div>
				<div class="form-group">
					<div class="group-title">
						<span class="name">爆款推荐</span>
						<button type="button" class="btn fitment" data-url="{pigcms{:U('Shop/shop_list',array('parentid'=>$category['cat_id']))}" data-target="_blank" data-page_id="plat_active">装修</button>
					</div>
					<div class="group-content">推荐店铺下的热门商品到首页；最多展示6个店铺，一个店铺最多展示3个商品</div>
				</div>
				<div class="form-group">
					<div class="group-title">
						<span class="name">店铺列表展示热销商品</span>
						<span class="switch-<if condition="$config['shop_decoration_hot_goods'] eq '1'">on<else/>off</if>" id="plat_news"></span>
					</div>
					<div class="group-content">每个店铺下方是否展示该店铺下的热销商品，展示该店铺内排序值最高的前三个商品</div>
				</div>
			</div>

			<div class="page-style-wrapper">
				<div class="page-style">
					<iframe src="{pigcms{$config.site_url}/wap.php?a=index_new&viewpage=true" id="viewpageIframe" style="width:100%;height:100%;border:0;"></iframe>
				</div>
				<div class="btn-box"><button data-url="{pigcms{:U('Shop/cat_renovation',array('parentid'=>$category['cat_id']))}">刷新</button>(如果“装修”后样式未改变，请点击此刷新按钮)</div>
			</div>
		</div>
	</div>
	<style>
		/**flex**/
		.rel{position: relative;}
		.flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; display: box; align-items: center;  }
		.flex_justify{-webkit-box-pack: justify;-webkit-justify-content: space-between;-ms-flex-pack: justify;justify-content: space-between;box-pack: center;}
		.flex-align{-webkit-box-align: center;-moz-align-items: center;-webkit-align-items: center; align-items: center;box-align: center;}
		.flex_one{flex: 1;-webkit-box-flex: 1;-webkit-flex: 1;}
		.flex-wrap{-webkit-flex-wrap: wrap;-moz-flex-wrap: wrap;-ms-flex-wrap: wrap;-o-flex-wrap: wrap;flex-wrap:wrap;}

		@media screen and (max-width: 1050px) {
		    .page-style-wrapper{
		    	position: static;
		    	margin: 20px;
		    }
		}

		.page-style-wrapper{position: absolute;left: 650px;top: 10px;}
		.page-style-wrapper .btn-box{margin: 20px 0;color: #999;font-size: 14px;}
		.page-style-wrapper .btn-box button {background-color: #438eb9;border-radius: 3px;border: none;padding: 6px 12px;color: white; cursor: pointer; outline: none;display: inline-block;margin-right: 10px;}
		.page-style-wrapper .btn-box button:active{background: #05679f;}
		.page-style{width: 335px;height: 490px;background: #fff;overflow:hidden;box-shadow: 0 0 10px 2px #ddd;}


		.table-list{
			position: relative;
		}

		.fitment_div {
			width: 520px;
			margin-left: 20px;
		}
		.form-group {
			margin-bottom: 10px;
			border-bottom: 1px solid #EEE;
			padding: 10px 0;
		}
		.form-group:last-child{
			border-bottom:none;
		}
		.right_div{
			position: absolute!important;
			right: 0px;
			top: 0px;
			font-size:12px;
		}
		.group-title {
			position: relative;
			font-size: 16px;
			color: black;
			margin-bottom: 10px;
		}
		.group-content {
			width: 380px;
			line-height: 1.3;
			letter-spacing: 0.5px;
			font-size: 12px;
			color: #999;
		}
		.group-title .btn.fitment {
			background-color: #438eb9!important;
			border-radius: 3px;
			border: none;
			position: absolute;
			right: 0px;
			top: 0px;
			padding:0 12px;
			color:white;
			cursor:pointer;
			outline:0;
			-webkit-user-select: none;
			-webkit-touch-callout: none;
		}
		.group-title .btn.fitment2 {
			background-color: #438eb9!important;
			border-radius: 3px;
			border: none;
			position: absolute;
			right: 0px;
			top: 0px;
			padding:0 12px;
			color:white;
			cursor:pointer;
			outline:0;
			font-size: 12px;
			-webkit-user-select: none;
			-webkit-touch-callout: none;
		}
		.group-title .switch-off,.group-title .switch-on{
			position: absolute!important;
			right: 0px;
			top: 0px;
		}
		.group-title .right_change{
			position: absolute!important;
			right: 0px;
			top: 0px;
		}
	</style>
	<!-- swiper -->
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css" />
	<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>
	<!-- honeySwitch -->
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}js/honeySwitch/honeySwitch.css" />
	<script type="text/javascript" src="{pigcms{$static_public}js/honeySwitch/honeySwitch.js" charset="utf-8"></script>
	<script type="text/javascript" charset="utf-8">
		$(function(){

			// var swiper1 = new Swiper('.swiper-container.default-header', {
		    //     pagination: '.default-header .swiper-pagination',
		    //     paginationClickable: true,
		    //     observer:true,
		    //     observeParents:true
		    // });
		    // var swiper2 = new Swiper('.swiper-container.slider-small', {
		    //     pagination: '.slider-small .swiper-pagination',
		    //     paginationClickable: true,
		    //     observer:true,
		    //     observeParents:true
		    // });
			
			// $('#change_tpl').change(function(){
			// 	var tpl = $(this).val();
			// 	$.post("{pigcms{:U('change_index_tpl')}",{tpl:tpl},function(result){
			// 		if(result.status != 1){
			// 			layer.alert('切换失败',{
			// 				offset: ($('body').height() - 150) / 2
			// 			});
			// 		}else{
			// 			if(tpl == 'default'){
			// 				$('.default_tpl, .default-header').show();
			// 				$('.pure_tpl, .pure-header').hide();
							
			// 			}else{
			// 				$('.default_tpl, .default-header').hide();
			// 				$('.pure_tpl, .pure-header').show();
			// 			}
			// 			$('#viewpageIframe').attr('src', $('#viewpageIframe').attr('src'));
			// 		}
			// 	});
			// });

			// 页面弹窗
			$('.fitment').click(function(){
				var _this = $(this);
				if($(this).data('target')){
					top.addIframe(_this.siblings('.name').text(),_this.data('page_id'),_this.data('url'));
				}else{
					window.o2oTop.artiframe(_this.data('url')+'&frame_show=true&from_page=viewpage',_this.siblings('.name').text(),1000,600,true,false,false,false,'viewpage',true,
					function(){
						if (_this.data('refresh')=="1") {
							window.location.reload();
						}
					});
				}
			});

			// // 页面弹窗
			// $('.fitment2').click(function(){
			// 	var _this = $(this);
			// 	if($(this).data('target')){
			// 		top.addIframe(_this.siblings('.name').text(),_this.data('page_id'),_this.data('url'));
			// 	}else{
			// 		window.o2oTop.artiframe(_this.data('url')+'&frame_show=true&from_page=viewpage',_this.siblings('.name').text(),660,400,true,false,false,false,'viewpage',true,
			// 		function(){
			// 			if (_this.data('refresh')=="1") {
			// 				window.location.reload();
			// 			}
			// 		});
			// 	}
			// });

			// 链接挑战
			$('.page-style-wrapper .btn-box button').on('click', function(){
				window.location.href = $(this).data('url');
			})

			// 店铺列表展示热销商品
			switchEvent("#plat_news",function(){
				console.log('开了');
				change_plat_news(1);
			},function(){
				console.log('关了');
				change_plat_news(0);
			});
			function change_plat_news(value){
				$.post("{pigcms{:U('change_plat_news_show')}",{value:value},function(result){
					if(result.status != 1){
						layer.alert('切换失败',{
							offset: ($('body').height() - 150) / 2
						});
					}
				});
			}

			// 背景颜色
			$("#choose_color_box_txtactivecolor, #config_bgcolor").click(function(){
				change_bgcolor();
			});
			function change_bgcolor(){
				window.showTopColorPanel("leftmenu_Viewpage_index",$("#choose_color_box_txtactivecolor").offset().top,$("#choose_color_box_txtactivecolor").height(),$("#choose_color_box_txtactivecolor").offset().left,"config_bgcolor",function(){
					var bgcolor = $('#config_bgcolor').val();
					$('.page-style').css('background-color', bgcolor);
					save_change_color('shop_decoration_bgcolor',bgcolor);
				});
			}

			// 文本与图标颜色
			$("#choose_color_box_txtcolor, #config_txtcolor").click(function(){
				change_txtcolor();
			});
			function change_txtcolor(){
				window.showTopColorPanel("leftmenu_Viewpage_index",$("#choose_color_box_txtcolor").offset().top,$("#choose_color_box_txtcolor").height(),$("#choose_color_box_txtcolor").offset().left,"config_txtcolor",function(){
					var txtcolor = $('#config_txtcolor').val();
					$('.page-style').css('color', txtcolor);
					save_change_color('shop_decoration_text_icon',txtcolor);
				});
			}

			// 修改配置颜色
			function save_change_color(name,color){
				var parentid = {pigcms{$_GET['parentid']};
				$.post("{pigcms{:U('change_footer_color')}",{name:name,parentid:parentid,color:color},function(result){
					if(result.status != 1){
						layer.alert('切换失败',{
							offset: ($('body').height() - 150) / 2
						});
					}
				});
			}
		});
	</script>
<include file="Public:footer"/>