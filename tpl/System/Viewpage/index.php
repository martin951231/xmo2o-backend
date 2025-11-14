<include file="Public:header"/>
	<div class="mainbox">
		<include file="header"/>
		<div class="table-list" style="min-height: 700px">
			<div class="fitment_div">
				<if condition="$config['system_type'] != 'village'">
					<if condition="!$system_session['area_id']">
						<div class="form-group">
							<div class="group-title">模板选择
								<select id="change_tpl" class="right_change">
									<option value="default" <if condition="$config['mobile_index_tpl'] eq 'default'">selected="selected"</if>>清新版</option>
									<option value="pure" <if condition="$config['mobile_index_tpl'] eq 'pure'">selected="selected"</if>>标准版</option>
                    				<option value="simple" <if condition="$config['mobile_index_tpl'] eq 'simple'">selected="selected"</if>>简约版</option>
								</select>
							</div>
							<div class="group-content">我们提供了三套模板可以自由选择切换，切换后用户端会立即展示该模板。</div>
						</div>
					</if>
                    <if condition="$home_search_ranking_show eq 1">
                    <div class="form-group">
                        <div class="group-title">
                            <span class="name">搜索排行配置</span>
                            <button onclick="window.o2oTop.goV20Url('/common/platform/viewpage/SearchRankingConfig')" type="button" class="btn fitment" data-edit="1">装修
                            </button>
                        </div>
                        <div class="group-content">自定义排序规则与权重调整，优化用户搜索体验</div>
                    </div>
                    </if>
					<div class="form-group">
							<div class="group-title">
								<span class="name">扫一扫</span>
								<span class="switch-<if condition="$config['mobile_index_scan'] eq '1'">on<else/>off</if>" id="mobile_index_scan"></span>
							</div>
							<div class="group-content">关闭后，平台首页右上角不显示扫一扫入口</div>
						</div>
					<div class="form-group">
						<div class="group-title">
							<span class="name">导航栏“更多”图标按钮导航列表</span>
							<button type="button" class="btn fitment" data-url="{pigcms{:U('Slider/slider_list',array('cat_key'=>'plat_index_more'))}">装修</button>
						</div>
						<div class="group-content">点击之后按钮右下角会悬浮菜单列表，可设置菜单列表的导航列表。不设置则不展示此按钮。</div>
					</div>
					<div class="form-group default_tpl" style="display:<if condition="$config['mobile_index_tpl'] eq 'default'">block<else/>none</if>;">
						<div class="group-title">
							<span class="name">《清新版模板》四个大按钮导航</span>
							<button type="button" class="btn fitment" data-url="{pigcms{:U('Slider/slider_list',array('cat_key'=>'app_index_top_btn'))}">装修</button>
						</div>
						<div class="group-content">此用途一般为平台主营业务，方便用户快速进入。</div>
					</div>
					<div class="form-group pure_tpl" style="display:<if condition="in_array($config['mobile_index_tpl'], ['pure', 'simple'])">block<else/>none</if>;">
						<div class="group-title">
							<span class="name">《<if condition="$config['mobile_index_tpl'] eq 'simple'">简约版<else/>标准版</if>模板》轮播图</span>
							<button type="button" class="btn fitment" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'app_index_top'))}">装修</button>
						</div>
						<div class="group-content">尺寸为 <if condition="$config['mobile_index_tpl'] eq 'simple'"> 710*280 <else/> 640*338</if>。图片是从手机状态栏顶部开始展示，导航栏会悬浮在图片上方，需要注意图片上部分的颜色不能太艳丽，阻碍用户看到导航。</div>
					</div>
          <div class="form-group default_tpl" style="display:<if condition="$config['mobile_index_tpl'] eq 'simple'">block<else/>none</if>;">
            <div class="group-title">
              <span class="name">轮播图背景与轮播图广告设置</span>
              <button type="button" class="btn fitment" data-simple="1" data-url="{pigcms{:U('Adver/adverSet')}" >装修
              </button>
              <div class="group-content">点击后设置轮播图是否显示，是否占位；设置轮播图背景，设置轮播图下方广告。</div>
            </div>
          </div>
					<div class="form-group default_tpl" style="display:<if condition="$config['mobile_index_tpl'] eq 'default'">block<else/>none</if>;">
						<div class="group-title">
							<span class="name">《清新版模板》轮播图</span>
							<button type="button" class="btn fitment" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'wap_index_top'))}">装修</button>
						</div>
						<div class="group-content">尺寸为 688*150。</div>
					</div>
					<div class="form-group">
						<div class="group-title">
							<span class="name">导航栏导航列表</span>
							<button type="button" class="btn fitment" data-url="{pigcms{:U('Slider/slider_list',array('cat_key'=>'wap_slider'))}">装修</button>
						</div>
						<div class="group-content">每行展示5个，不设置不展示。</div>
					</div>
          <div class="form-group default_tpl" style="display:<if condition="$config['mobile_index_tpl'] eq 'simple'">block<else/>none</if>;">
          <div class="group-title">
            <span class="name">导航栏背景透明度</span>
            <button type="button" class="btn fitment" data-opacity="1" data-url="{pigcms{:U('Adver/sliderOpacitySet')}" >装修
            </button>
            <div class="group-content">0 ~ 1 中设置，透明度值设置越小，越透明。</div>
          </div>
          </div>
					<div class="form-group">
						<div class="group-title">导航栏导航行数
							<select id="change_slider_row" class="right_change">
								<option value="2" <if condition="!$config['mobile_index_slider_row'] || $config['mobile_index_slider_row'] eq '2'">selected="selected"</if>>2行</option>
								<option value="3" <if condition="$config['mobile_index_slider_row'] eq '3'">selected="selected"</if>>3行</option>
							</select>
						</div>
						<div class="group-content">每行展示5个，不设置不展示。可以设置首页显示2行或3行。</div>
					</div>
					<if condition="!$system_session['area_id']">
						<div class="form-group">
							<div class="group-title">
								<span class="name">平台快报</span>
								<span class="switch-<if condition="$config['mobile_index_plat_news'] eq '1'">on<else/>off</if>" id="plat_news"></span>
							</div>
							<div class="group-content">平台快报部分在首页是否展示，用户点击该栏会进入平台快报页面。</div>
						</div>
					</if>
					<div class="form-group">
						<div class="group-title">
							<span class="name">横幅领券入口</span>
							<button type="button" class="btn fitment" data-url="{pigcms{:U('banner_ticket_entrance')}">装修</button>
						</div>
						<div class="group-content">不设置不展示</div>
					</div>
					<div class="form-group">
						<div class="group-title">

							<if condition="$config['app_index_center_four_show_type'] == '0'">
								<span class="name">4张小图广告列表</span>
								<button type="button" class="btn fitment" data-refresh="1" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'app_index_center_four'))}">装修</button>
                            <elseif condition="$config['app_index_center_four_show_type'] == '2'" />
                                <span class="name">9宫格广告列表</span>
                                <button type="button" class="btn fitment" data-refresh="1" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'app_index_center_nine_grid'))}">装修</button>
                            <else/>
								<span class="name">1大3小广告列表</span>
								<button type="button" class="btn fitment" data-refresh="1" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'app_index_center_four_big_and_small'))}">装修</button>
							</if>
						</div>

						<if condition="$config['app_index_center_four_show_type'] == '0'">
							<div class="group-content"> 当前为4张平铺式布局，不设置不展示。</div>
                        <elseif condition="$config['app_index_center_four_show_type'] == '2'" />
                            <div class="group-content"> 当前为9宫格式布局，不设置不展示。</div>
                        <else/>
							<div class="group-content">当前为1大3小式布局，不设置不展示。</div>
						</if>
					</div>
					<div class="form-group">
						<div class="group-title">
							<span class="name">中间栏轮播广告图</span>
							<button type="button" class="btn fitment" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'app_index_center_adver'))}">装修</button>
						</div>
						<div class="group-content">尺寸为 690*150，不设置不展示。</div>
					</div>
					<if condition="isset($config['wap_home_show_classify'])">
            <div class="form-group">
            <div class="group-title">
              <span class="name">{pigcms{$config.classify_name}</span>
              <button type="button" class="btn fitment" data-opacity="1" data-url="{pigcms{:U('Adver/classifySet')}" >装修
              </button>
              <div class="group-content">{pigcms{$config.classify_name}部分在首页是否展示，用户点击该栏下的分类会进入对应分类页面。不同版本的用户端可能会还没上线此功能，请持续关注。</div>
            </div>
            </div>
					</if>
					<div class="form-group">
						<div class="group-title">
							<span class="name">超值抢购</span>
							<button type="button" class="btn fitment" data-url="{pigcms{:U('Plat_active/index')}" data-target="_blank" data-page_id="plat_active">装修</button>
						</div>
						<div class="group-content">可以添加一场或多场活动，然后从不同业务中选取商品展示，多场活动下仅展示符合现在时间的一场活动。不设置不展示。</div>
					</div>
					<div class="form-group">
						<div class="group-title">
							<span class="name">五图广告位</span>
							<button type="button" class="btn fitment" data-url="{pigcms{:U('Plat_five_adver/index')}" data-target="_blank" data-page_id="plat_five_adver">装修</button>
						</div>
						<div class="group-content">可以添加多个五图广告，会在列表中循环展示所有。可以设置五图广告，也可以设置从指定业务展示商品。不设置不展示。</div>
					</div>
		            <div class="form-group">
		                <div class="group-title">
		                    <span class="name">页面分享文案配置</span>
		                    <button type="button" class="btn fitment" data-edit="1" data-url="{pigcms{$config['site_url']}/v20/public/platform/#/dialog/platform.viewpage/ShareSet/gid=index_share" >装修
		                    </button>
		                </div>
		                <div class="group-content">标题：某某平台首页；副标题：点击进入了解更多优惠信息</div>
		            </div>
		            <div class="form-group">
		                <div class="group-title">
		                    <span class="name">小程序流量广告</span>
		                    <button type="button" class="btn fitment" data-edit="1" data-url="{pigcms{$config['site_url']}/v20/public/platform/#/dialog/platform.viewpage/ShareSet/gid=101" >装修
		                    </button>
		                </div>
		            </div>
				</if>
				<if condition="!$system_session['area_id']">
					<div class="form-group">
						<div class="group-title">
							<span class="name">猜你喜欢</span>
							<button type="button" class="btn fitment" data-url="{pigcms{:U('guess_like')}" data-edit="true">装修</button>
						</div>
						<div class="group-content">修改猜你喜欢的标题，以及副标题描述，猜你喜欢展示的业务类别。</div>
					</div>
				</if>

                <if condition="$customization['life_tools'] == '1'">
                    <div class="form-group">
                        <div class="group-title">
                            <span class="name">热门推荐</span>
                            <button type="button" class="btn fitment" data-noedit="1" data-url="{pigcms{$config['site_url']}/v20/public/platform/#/dialog/platform.viewpage/PlatformRecommendHot" >装修
                            </button>
                        </div>
                        <div class="group-content">修改热门推荐的标题,添加热门推荐的商品</div>
                    </div>
                </if>
			</div>

			<div class="page-style-wrapper">
				<div class="page-style">
					<iframe src="<php>echo $this->config['site_url'] . D('House_village')->base_url;</php>" id="viewpageIframe" style="width:100%;height:100%;border:0;"></iframe>
				</div>

				<div class="btn-box"><button data-url="{pigcms{:U('Viewpage/index')}">刷新</button>(如果“装修”后底部导航样式未改变，请点击此刷新按钮)</div>
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

		.page-style-wrapper{position: absolute;left: 650px;top: 82px;}
		.page-style-wrapper .btn-box{margin: 20px 0;color: #999;font-size: 14px;}
		.page-style-wrapper .btn-box button {background-color: #438eb9;border-radius: 3px;border: none;padding: 6px 12px;color: white; cursor: pointer; outline: none;display: inline-block;margin-right: 10px;}
		.page-style-wrapper .btn-box button:active{background: #05679f;}
		.page-style{width: 375px;height: 627px;background: #fff;overflow:hidden;box-shadow: 0 0 10px 2px #ddd;}


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

			var swiper1 = new Swiper('.swiper-container.default-header', {
		        pagination: '.default-header .swiper-pagination',
		        paginationClickable: true,
		        observer:true,
		        observeParents:true
		    });
		    var swiper2 = new Swiper('.swiper-container.slider-small', {
		        pagination: '.slider-small .swiper-pagination',
		        paginationClickable: true,
		        observer:true,
		        observeParents:true
		    });

			$('.page-style-wrapper .btn-box button').on('click', function(){
				window.location.href = $(this).data('url');
			});

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

			switchEvent("#mobile_index_scan",function(){
				change_mobile_index_scan(1);
			},function(){
				change_mobile_index_scan(0);
			});
			function change_mobile_index_scan(value){
				$.post("{pigcms{:U('change_mobile_index_scan')}",{value:value},function(result){
					if(result.status != 1){
						layer.alert('切换失败',{
							offset:'250px'
						});
					}
				});
			}
			
			
			switchEvent("#show_classify",function(){
				console.log('开了');
				change_show_classify(1);
			},function(){
				console.log('关了');
				change_show_classify(0);
			});
			function change_show_classify(value){
				$.post("{pigcms{:U('change_classify_show')}",{value:value},function(result){
					if(result.status != 1){
						layer.alert('切换失败',{
							offset: ($('body').height() - 150) / 2
						});
					}
				});
			}
			
			$('#change_tpl').change(function(){
				var tpl = $(this).val();
				$.post("{pigcms{:U('change_index_tpl')}",{tpl:tpl},function(result){
					if(result.status != 1){
						layer.alert('切换失败',{
							offset: ($('body').height() - 150) / 2
						});
					}else{
						if(tpl == 'default'){
							$('.default_tpl, .default-header').show();
							$('.pure_tpl, .pure-header').hide();
							
						}else{
							$('.default_tpl, .default-header').hide();
							$('.pure_tpl, .pure-header').show();
						}
						$('#viewpageIframe').attr('src', $('#viewpageIframe').attr('src'));
						history.go(0);
					}
				});
			});
			
			$('#change_slider_row').change(function(){
				var row = $(this).val();
				$.post("{pigcms{:U('change_slider_row')}",{row:row},function(result){
					if(result.status != 1){
						layer.alert('切换失败',{
							offset: ($('body').height() - 150) / 2
						});
					}else{
						$('#viewpageIframe').attr('src', $('#viewpageIframe').attr('src'));
					}
				});
			});
			
			$('.fitment').click(function(){
				var _this = $(this);
				if($(this).data('target')){
					top.addIframe(_this.siblings('.name').text(),_this.data('page_id'),_this.data('url'));
				}else if($(this).data('edit')){
					window.o2oTop.artiframe(_this.data('url'),_this.siblings('.name').text(),1000,580,true,false,false,editbtn,'store_add',true);
				}else if($(this).data('simple')){
          window.o2oTop.artiframe(_this.data('url'),_this.siblings('.name').text(),800,500,true,false,false,editbtn,'simple',true);
        }else if($(this).data('opacity')){
          window.o2oTop.artiframe(_this.data('url'),_this.siblings('.name').text(),500,200,true,false,false,editbtn,'opacity',true);
        }else if($(this).data('noedit')){
                    window.o2oTop.artiframe(_this.data('url'),_this.siblings('.name').text(),1000,680,true,false,false,false,'store_add',true);
                }else{
					window.o2oTop.artiframe(_this.data('url')+'&frame_show=true&from_page=viewpage',_this.siblings('.name').text(),1000,600,true,false,false,false,'viewpage',true,
						function(){
							if (_this.data('refresh')=="1") {
								window.location.reload();
							}
						});
				}
			});
		});
		
	</script>
<include file="Public:footer"/>
