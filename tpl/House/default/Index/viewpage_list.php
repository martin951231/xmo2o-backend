<include file="Public:header"/>

<div class="main-content">

    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Index/index')}"><?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区')?>管理</a>
            </li>
            <li class="active">基本信息设置</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
					<if condition="$yingshan">
						<div style="margin-left: 10px;color: #9a9999;margin-bottom: 10px">
							<span> 说明：</span>
							<p>1.可视化页面不设置开启新版页面时，配置单功能导航、四宫格推荐广告位、三宫格推荐广告位、猜你喜欢(关联店铺)不会生效</p>
							<p>2.开启可视化页面新版页面时，建议先配置好单功能导航、四宫格推荐广告位、三宫格推荐广告位、猜你喜欢(关联店铺)功能</p>
							<p>3.轮播图等广告图的圆角半径是统一的</p>
							<if condition="$assign_type['type_0']">
								<p>4.从其他<?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区')?>复制 不复制新版页面的四宫格推荐广告位、三宫格推荐广告位、猜你喜欢(关联店铺)功能</p>
							</if>
						</div>
					</if>
            <style>
                .ace-file-input a {display:none;}
								.slider {
                    width: 20px !important;
                    height: 20px !important;
								}
            </style>
            <if condition="$assign_type['type_0']">
            <button type="button" onclick="chooseFloor()" class="ant-btn ant-btn-primary" style="float: left; margin-top: 15px;margin-bottom: 10px"><span>从其他<?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区')?>复制</span></button>
            </if>
            <div class="row">
                <div class="col-xs-12">
                    <div class="tabbable">
                        <ul id="myTab" class="nav nav-tabs">
                            <li class="active">
                                <a href="#basicinfo" onclick="viewpage_list()" data-toggle="tab"><?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区')?>首页</a>
                            </li>
                            <li>
                                <a href="#txtstore" onclick="footer_list()" data-toggle="tab">底部导航</a>
                            </li>
                            <if condition="$yingshan OR $jsdhwy_new_wap_index OR $yuanJino2oCustomized">
                                <li>
                                    <a href="#topstore" onclick="top_list()" data-toggle="tab">顶部导航</a>
                                </li>
                            </if>
                        </ul>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane active" id="basicinfo" style="min-height:1400px;">
                            <div class="table-list">
                                <div class="fitment_div">
                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">首页样式</span>
                                            <button type="button" class="btn fitment home-color-save" onclick="saveHomeColorConfig()">保存</button>
                                        </div>
                                        <div class="group-content home-color-config">
                                            <label>切换小区按钮：<input type="color" id="switch_village_color" value="{pigcms{$home_color_config.switch_village_color}" /></label>
                                            <label>首页标题：<input type="color" id="index_title_color" value="{pigcms{$home_color_config.index_title_color}" /></label>
                                            <label>头部背景：<input type="color" id="index_header_bg_color" value="{pigcms{$home_color_config.index_header_bg_color}" /></label>
                                            <div class="home-bg-image-config">
                                                <input type="hidden" id="index_header_bg_image" value="{pigcms{$home_color_config.index_header_bg_image}" />
                                                <div class="home-bg-image-preview">
                                                    <span>头部背景图：</span>
                                                    <img id="index_header_bg_image_preview" src="{pigcms{$home_color_config.index_header_bg_image}" alt="头部背景图预览" title="点击查看大图" role="button" tabindex="0" />
                                                    <span class="home-bg-image-tip">建议尺寸：750 × 220，未配置时使用头部背景颜色</span>
                                                </div>
                                                <div class="home-bg-image-actions">
                                                    <button type="button" class="btn btn-sm btn-info" id="index_header_bg_image_upload">选择图片</button>
                                                    <button type="button" class="btn btn-sm btn-default" onclick="clearIndexHeaderBgImage()">清除图片</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="index_header_bg_image_lightbox" class="home-image-lightbox" aria-hidden="true">
                                            <button type="button" class="home-image-lightbox-close" onclick="closeIndexHeaderBgImagePreview()" aria-label="关闭大图预览">&times;</button>
                                            <img id="index_header_bg_image_large" src="" alt="头部背景图大图" />
                                        </div>
                                    </div>
                                <if condition="$jsdhwy_new_wap_index gt 0">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">小区快送</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;"  data-block_type="village_express_delivery" data-url="{pigcms{:U('Library/village_express_delivery',array('village_id'=>$village_id,'block_type_id' => 'business_express_delivery'))}">装修</button>
                                            </div>
                                            <div class="group-content">小区快送展示：快店、新版商城、社区团购、预约服务的产品信息</div>
                                        </div>
                                    <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">小区生活服务</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Library/index_nav_2new',array('village_id'=>$village_id,'block_type_id' => 'village_life_service'))}">装修</button>
                                            </div>
                                            <div class="group-content">小区快送展示：快店、新版商城、社区团购、预约服务的产品信息</div>
                                        </div>

                                    <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">家庭助理</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Library/index_nav_2new',array('village_id'=>$village_id,'block_type_id' => 'village_life_assistant'))}">装修</button>
                                            </div>
                                            <div class="group-content">小区快送展示：快店、新版商城、社区团购、预约服务的产品信息</div>
                                        </div>

                                    </if>

                                    <if condition="!$jsdhwy_new_wap_index AND (!$config['house_advert_targeted_delivery'] || $config['house_advert_targeted_delivery'] neq 1) || ($config['house_visualize_switch'] && $config['house_visualize_switch'] eq 1)">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">轮播图</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'house_app_index_top'))}">装修</button>
                                            </div>
                                            <div class="group-content">尺寸:{pigcms{$index_top_size}。</div>
                                        </div>
                                    </if>
                                  <if condition="$yuanJino2oCustomized">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">首页弹窗</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Library/index_nav_pop')}">装修</button>
                                            </div>
                                            <div class="group-content">尺寸:586*734。</div>
                                        </div>
                                     </if>
                                    <if condition="$yingshan">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">单行功能导航</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Library/index_nav_new',array('type'=>'nav','row_type' => 'single'))}">装修</button>
                                            </div>
                                            <div class="group-content">尺寸:150*150。</div>
                                        </div>
                                    </if>


                                    <if condition="(!$config['house_advert_targeted_delivery'] || $config['house_advert_targeted_delivery'] neq 1) || ($config['house_visualize_switch'] && $config['house_visualize_switch'] eq 1)">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">中间栏轮播广告图</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'house_app_index_center'))}">装修</button>
                                            </div>
                                            <div class="group-content">尺寸:{pigcms{$index_center_size},不设置,不展示。</div>
                                        </div>
                                    </if>
                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">公共收益入口</span>
                                            <button type="button" class="btn fitment public-revenue-decorate" onclick="openPublicRevenueConfig()">装修</button>
                                        </div>
                                        <div id="public_revenue_config_modal" class="public-revenue-modal" aria-hidden="true">
                                            <div class="public-revenue-modal-mask" onclick="closePublicRevenueConfig()"></div>
                                            <div class="public-revenue-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="public_revenue_modal_title">
                                                <div class="public-revenue-modal-header">
                                                    <strong id="public_revenue_modal_title">公共收益装修</strong>
                                                    <button type="button" class="public-revenue-modal-close" onclick="closePublicRevenueConfig()" aria-label="关闭">&times;</button>
                                                </div>
                                                <div class="public-revenue-modal-body public-revenue-home-config">
                                                    <div class="public-revenue-config-row">
                                                        <span class="public-revenue-config-label">是否显示：</span>
                                                        <label><input type="radio" name="public_revenue_home_show" value="1" <if condition="$public_revenue_home_config.public_revenue_home_show eq 1">checked</if> /> 显示</label>
                                                        <label><input type="radio" name="public_revenue_home_show" value="0" <if condition="$public_revenue_home_config.public_revenue_home_show neq 1">checked</if> /> 隐藏</label>
                                                    </div>
                                                    <div class="public-revenue-config-row">
                                                        <label class="public-revenue-config-label" for="public_revenue_home_title">标题内容：</label>
                                                        <input type="text" id="public_revenue_home_title" maxlength="30" value="{pigcms{$public_revenue_home_config.public_revenue_home_title}" placeholder="请输入标题" />
                                                    </div>
                                                    <div class="public-revenue-config-row">
                                                        <label class="public-revenue-config-label" for="public_revenue_home_bg_color">入口背景：</label>
                                                        <input type="color" id="public_revenue_home_bg_color" value="{pigcms{$public_revenue_home_config.public_revenue_home_bg_color}" />
                                                        <span class="public-revenue-config-tip-inline">小程序首页入口模块背景颜色</span>
                                                    </div>
                                                    <div class="public-revenue-config-row">
                                                        <label class="public-revenue-config-label" for="public_revenue_page_bg_color">页面背景：</label>
                                                        <input type="color" id="public_revenue_page_bg_color" value="{pigcms{$public_revenue_home_config.public_revenue_page_bg_color}" />
                                                        <span class="public-revenue-config-tip-inline">详情页和季度详情页共用</span>
                                                    </div>
                                                    <div class="public-revenue-config-row public-revenue-image-row">
                                                        <span class="public-revenue-config-label">背景图片：</span>
                                                        <input type="hidden" id="public_revenue_page_bg_image" value="{pigcms{$public_revenue_home_config.public_revenue_page_bg_image}" />
                                                        <img id="public_revenue_page_bg_image_preview" src="{pigcms{$public_revenue_home_config.public_revenue_page_bg_image}" alt="公共收益背景图预览" />
                                                        <button type="button" class="btn btn-sm btn-info" id="public_revenue_page_bg_image_upload">选择图片</button>
                                                        <button type="button" class="btn btn-sm btn-default" onclick="clearPublicRevenueBgImage()">清除图片</button>
                                                    </div>
                                                    <div class="public-revenue-config-tip">背景图将从手机顶部开始，按页面宽度等比展示，不裁切、不变形，超出内容高度的部分不会显示。</div>
                                                </div>
                                                <div class="public-revenue-modal-footer">
                                                    <button type="button" class="btn btn-default" onclick="closePublicRevenueConfig()">取消</button>
                                                    <button type="button" class="btn btn-primary" onclick="savePublicRevenueHomeConfig()">保存</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                  <if condition="$jsdhwy_new_wap_index gt 0">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">直播</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Library/index_nav_live',array('village_id'=>$village_id,'block_type_id' => 'business_live_broadcast'))}">装修</button>
                                            </div>
                                            <div class="group-content">小区可设置是否显示直播内容。</div>
                                        </div>

                                    </if>
                                    <if condition="$yuanJino2oCustomized">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">中间栏功能导航</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Library/index_nav_new',array('type'=>'nav','row_type' => 'middle'))}">装修</button>
                                            </div>
                                            <div class="group-content">尺寸:200*200。</div>
                                        </div>
                                     </if>
                                    <if condition="!$jsdhwy_new_wap_index">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">功能导航</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Library/index_nav_new',array('type'=>'nav'))}">装修</button>
                                            </div>
                                            <div class="group-content">尺寸:150*150。</div>
                                        </div>
                                     </if>
                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">四图广告位</span>
                                            <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'house_four_adver'))}">装修</button>
                                        </div>
                                        <div class="group-content">可以设置四图广告位,不设置不展示。</div>
                                    </div>

                                <if condition="$yingshan">
                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">四宫格推荐广告位</span>
                                            <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Adver/grid_num_list',array('grid_num'=>4))}">装修</button>
                                        </div>
                                        <div class="group-content">可以设置四图广告位,不设置不展示。</div>
                                    </div>
                                </if>
                             <if condition="$jsdhwy_new_wap_index gt 0">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">邻里猜你喜欢</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Library/index_nav_guess_love',array('village_id'=>$village_id,'block_type_id' => 'business_classify_info'))}">装修</button>
                                            </div>
                                            <div class="group-content">修改猜你喜欢的标题，以及副标题描述，猜你喜欢展示的业务类别。</div>
                                        </div>

                                    </if>

                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">中部单图广告位</span>
                                            <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'house_center_adver'))}">装修</button>
                                        </div>
                                        <div class="group-content">可以设置单张广告,不设置不显示。</div>
                                    </div>

                                    <if condition="$yingshan"><div class="form-group">
                                            <div class="group-title">
                                                <span class="name">三宫格推荐广告位</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Adver/grid_num_list',array('grid_num'=>3))}">装修</button>
                                            </div>
                                            <div class="group-content">可以设置四图广告位,不设置不展示。</div>
                                        </div>
                                    </if>
                                    <if condition="$jsdhwy_new_wap_index gt 0">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">猜你喜欢商家</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Library/index_nav_guess_love',array('village_id'=>$village_id,'block_type_id' => 'business_store_info'))}">装修</button>
                                            </div>
                                            <div class="group-content">按钮开关不控制系统的商品推荐，将控制自定义装修内容，关闭则装修的不展示。</div>
                                        </div>

                                    </if>
                                    <if condition="$yuanJino2oCustomized">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">秒杀专区</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Library/shop_goods_seckill_set')}" data-block_type="shop_goods_seckill_block" >装修</button>
                                            </div>
                                            <div class="group-content">可以选择商城、外卖、社区团购秒杀商品进行展示</div>
                                        </div>
                                     </if>
                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">底部单图广告位</span>
                                            <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'house_footer_adver'))}">装修</button>
                                        </div>
                                        <div class="group-content">可以设置单张广告,不设置不显示。</div>
                                    </div>
                                    <if condition="$jsdhwy_new_wap_index gt 0">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">底部左侧二宫格</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Library/index_nav_2new',array('village_id'=>$village_id,'block_type_id' => 'left_bottom_rowgrid'))}">装修</button>
                                            </div>
                                            <div class="group-content">可以设置单张广告，不设置不展示。</div>
                                        </div>
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">底部右侧二宫格</span>
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Library/index_nav_2new',array('village_id'=>$village_id,'block_type_id' => 'right_bottom_rowgrid'))}" >装修</button>
                                            </div>
                                            <div class="group-content">可以设置单张广告，不设置不展示。</div>
                                        </div>

                                    </if>
                                <if condition="$yingshan"><div class="form-group">
                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">猜你喜欢</span>
                                            <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('index_guess_like')}" data-edit="true">装修</button>
                                        </div>
                                        <div class="group-content">按钮开关不控制系统的商品推荐，将控制自定义装修内容，关闭则装修的不展示。</div>
                                    </div>
                                </if>

                             <if condition="$guess_you_like_goods gt 0"><div class="form-group">
                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">猜你喜欢商品</span>
                                            <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('Library/guess_you_like_goods',array('village_id'=>$village_id,'block_type_id' => 'guess_you_like_goods'))}" data-edit="true">装修</button>
                                        </div>
                                        <div class="group-content">猜你喜欢商品开启后，用户端首页展示外卖、团购、预约服务三种业务的商品</div>
                                    </div>
                                </if>

                                </div>

                                <div class="page-style-wrapper" style="margin: 0 auto;">
                                    <div class="page-style">
                                        <iframe src="{pigcms{$page_url}" id="viewpageIframe" style="width:350px;height:100%;border:0;"></iframe>
										<div style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:1;"></div>
									</div>
                                    <div class="btn-box"><button data-url="{pigcms{:U('Index/viewpage_list')}">刷新</button>(如果“装修”后底部导航样式未改变，请点击此刷新按钮)</div>
                                </div>
                            </div>
                        </div>

                    </div>
                 </div>
              </div>
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

    .page-style-wrapper{position: absolute;left: 650px;top: 2px;}
    .page-style-wrapper .btn-box{margin: 20px 0;color: #999;font-size: 14px;}
    .page-style-wrapper .btn-box button {background-color: #40a9ff;border-radius: 3px;border: none;padding: 6px 12px;color: white; cursor: pointer; outline: none;display: inline-block;margin-right: 10px;}
    .page-style-wrapper .btn-box button:active{background: #05679f;}
    .page-style{width: 350px;height: 1227px;background: #fff;overflow:hidden;box-shadow: 0 0 10px 2px #ddd;position:relative;}
    .page-style .now_view .item { width: 200 !important }


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
    .home-color-config {width:100%;white-space:nowrap;}
    .home-color-config > label {display:inline-flex;align-items:center;margin-right:12px;color:#555;white-space:nowrap;}
    .home-color-config input[type=color] {width:42px;height:30px;padding:1px;border:1px solid #ddd;cursor:pointer;}
    .home-color-config .btn {margin-left:5px;}
    .public-revenue-home-config {width:100%;color:#555;box-sizing:border-box;}
    .public-revenue-config-row {display:flex;align-items:center;margin-top:10px;}
    .public-revenue-config-row:first-child {margin-top:0;}
    .public-revenue-config-row > label {margin:0 18px 0 0;font-weight:normal;}
    .public-revenue-config-label,
    .public-revenue-title-label {width:86px;margin:0;flex-shrink:0;font-weight:normal;}
    .public-revenue-home-config input[type=text] {width:260px;height:30px;padding:4px 8px;border:1px solid #ddd;border-radius:3px;box-sizing:border-box;}
    .public-revenue-home-config input[type=text]:focus {border-color:#40a9ff;outline:none;box-shadow:0 0 0 2px rgba(64,169,255,.12);}
    .public-revenue-config-tip {margin-top:8px;color:#999;}
    .group-title .btn.public-revenue-decorate {width:55px;height:28px;}
    .public-revenue-modal {display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center;}
    .public-revenue-modal.is-open {display:flex;}
    .public-revenue-modal-mask {position:absolute;inset:0;background:rgba(0,0,0,.42);}
    .public-revenue-modal-dialog {position:relative;width:620px;max-width:calc(100vw - 40px);max-height:calc(100vh - 50px);overflow:auto;border-radius:5px;background:#fff;box-shadow:0 10px 35px rgba(0,0,0,.25);}
    .public-revenue-modal-header {display:flex;align-items:center;justify-content:space-between;padding:15px 20px;border-bottom:1px solid #eee;font-size:16px;}
    .public-revenue-modal-close {padding:0;border:0;background:none;color:#999;font-size:26px;line-height:1;cursor:pointer;}
    .public-revenue-modal-body {padding:20px;}
    .public-revenue-modal-footer {padding:12px 20px;border-top:1px solid #eee;text-align:right;}
    .public-revenue-modal-footer .btn {margin-left:8px;}
    .public-revenue-config-label {width:86px;flex-shrink:0;font-weight:normal;}
    .public-revenue-home-config input[type=color] {width:52px;height:32px;padding:1px;border:1px solid #ddd;cursor:pointer;}
    .public-revenue-config-tip-inline {margin-left:10px;color:#999;}
    .public-revenue-image-row img {display:block;width:84px;height:84px;margin-right:10px;border:1px solid #ddd;object-fit:contain;background:#f7f7f7;}
    .public-revenue-image-row img[src=""] {display:none;}
    .public-revenue-image-row .btn {margin-right:8px;}
    .home-bg-image-config {margin:15px 0 0;color:#555;white-space:nowrap;}
    .home-bg-image-actions,
    .home-bg-image-preview {display:flex;align-items:center;flex-wrap:nowrap;}
    .home-bg-image-actions {margin-top:10px;padding-left:84px;}
    .home-bg-image-config .home-bg-image-tip {margin-left:6px;color:#999;}
    .home-bg-image-config img {width:66px;height:48px;margin-left:10px;border:1px solid #ddd;object-fit:cover;flex-shrink:0;cursor:pointer;}
    .home-bg-image-config img:hover,
    .home-bg-image-config img:focus {border-color:#40a9ff;outline:none;box-shadow:0 0 0 2px rgba(64,169,255,.2);}
    .home-bg-image-config img[src=""] {display:none;}
    .home-bg-image-config .btn {margin-left:6px;}
    .home-image-lightbox {display:none;position:fixed;z-index:99999;inset:0;align-items:center;justify-content:center;padding:40px;background:rgba(0,0,0,.72);box-sizing:border-box;}
    .home-image-lightbox > img {display:block;max-width:100%;max-height:100%;object-fit:contain;background:#fff;box-shadow:0 4px 24px rgba(0,0,0,.35);}
    .home-image-lightbox-close {position:absolute;top:18px;right:24px;width:36px;height:36px;padding:0;border:0;border-radius:50%;background:rgba(0,0,0,.55);color:#fff;font-size:28px;line-height:34px;text-align:center;cursor:pointer;}
    .home-image-lightbox-close:hover,
    .home-image-lightbox-close:focus {background:#40a9ff;outline:none;}
    .group-title .btn.home-color-save {width:55px;height:28px;}
    .group-title .btn.fitment {
        background-color: #40a9ff!important;
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
    #colorpanel{
        font-size: 12px;
    }
    #colorpanel input{
        height:20px;
        width: 40px;
        font-size: 12px;
    }
    .ant-btn-primary {
        color: #fff;
        background-color: #1890ff;
        border-color: #1890ff;
        text-shadow: 0 -1px 0 rgb(0 0 0 / 12%);
        -webkit-box-shadow: 0 2px 0 rgb(0 0 0 / 5%);
        box-shadow: 0 2px 0 rgb(0 0 0 / 5%);
    }
    .ant-btn {
        line-height: 1.499;
        position: relative;
        display: inline-block;
        font-weight: 400;
        white-space: nowrap;
        text-align: center;
        background-image: none;
        border: 1px solid transparent;
        -webkit-box-shadow: 0 2px 0 rgb(0 0 0 / 2%);
        box-shadow: 0 2px 0 rgb(0 0 0 / 2%);
        cursor: pointer;
        -webkit-transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        -ms-touch-action: manipulation;
        touch-action: manipulation;
        height: 32px;
        padding: 0 15px;
        font-size: 14px;
        border-radius: 2px;

    }
</style>
<!-- swiper -->

<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css" />
<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>
<!-- honeySwitch -->
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}js/honeySwitch/honeySwitch.css" />
<script type="text/javascript" src="{pigcms{$static_public}js/honeySwitch/honeySwitch.js" charset="utf-8"></script>

<script type="text/javascript" src="{pigcms{$static_public}js/jquery.colorpicker.js"></script>

<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>

<script type="text/javascript" charset="utf-8">

    function viewpage_list(){
        window.location.href = "{pigcms{:U('viewpage_list')}";
    }
    function footer_list(){
        window.location.href = "{pigcms{:U('footer_list')}";
    }
    function top_list(){
      window.location.href = "{pigcms{:U('top_list')}";
    }
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
        })

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
        $('#change_tpl').change(function(){
            var tpl = $(this).val();
            $.post("{pigcms{:U('change_index_tpl')}",{tpl:tpl},function(result){
                if(result.status != 1){
//                        layer.alert('切换失败',{reminder
//                            offset: ($('body').height() - 150) / 2
//                        });
                }else{
                    if(tpl == 'default'){
                        $('.default_tpl, .default-header').show();
                        $('.pure_tpl, .pure-header').hide();

                    }else{
                        $('.default_tpl, .default-header').hide();
                        $('.pure_tpl, .pure-header').show();
                    }
                    $('#viewpageIframe').attr('src', $('#viewpageIframe').attr('src'));
                }
            });
        });

        $("#viewpageIframe").load(function(){
            setTimeout(function(){
                // 以免页面未加载完成 8秒后执行获取页面高度
                var iframe = document.getElementById('viewpageIframe');
                //2.获取iframe的body标签
                var body = iframe.contentWindow.document.body;
                //3.获取iframe中body标签下的某个div的高度
                var ght = $(body).find(".main-body").outerHeight();
                if(ght){
                    $('.page-style').height(ght+50);
                    $('#basicinfo').css('min-height', ght + 50);
                }
            },6000);
        });
    });

</script>
<script src="/static/js/cart/jscolor.js" type="text/javascript"></script>
<style type="text/css">
    .plug-menu {
        width:36px;
        height:36px;
        border-radius:36px;
        -moz-box-shadow:0 0 0 4px #FFFFFF, 0 2px 5px 4px rgba(0, 0, 0, 0.25);
        -webkit-box-shadow:0 0 0 4px #FFFFFF, 0 2px 5px 4px rgba(0, 0, 0, 0.25);
        box-shadow:0 0 0 4px #FFFFFF, 0 2px 5px 4px rgba(0, 0, 0, 0.25);
        background-color: FF0000;
        position:relative
    }
    .plug-menu span {
        display: block;
        width:28px;
        height:28px;
        background: url(/static/images/photo/plugmenu.png) no-repeat;
        background-size: 28px 28px;
        text-indent: -999px;
        position:absolute;
        top:4px;
        left:4px;
        overflow: hidden;
    }
</style>
<style>
    .flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; display: box; align-items: center;  }
    .flex_one{flex: 1;-webkit-box-flex: 1;-webkit-flex: 1;}

    .page-style-wrapper{
        float: left;
        margin: -210px auto auto 650px;
    }

    @media screen and (max-width: 1050px) {
        .page-style-wrapper{
            float: none;
            margin: 20px;
        }
    }

    .page-style .item{
        text-align: center;
        box-sizing: border-box;
        cursor: pointer;
    }

    .page-style .img{
        width: 28px;
        height: 28px;
        background: #fff;
        margin: 4px auto 0;
        box-sizing: border-box;
    }
    .page-style .img img{
        width: 100%;
        height: 100%;
    }

    .page-style-wrapper .btn-box{
        margin: 20px 0;
        color: #999;
        font-size: 14px;
    }

    .page-style-wrapper .btn-box button {
        background-color: #40a9ff;
        border-radius: 3px;
        border: none;
        padding: 6px 12px;
        color: white;
        cursor: pointer;
        outline: none;
        display: inline-block;
        margin-right: 10px;
    }

    .page-style-wrapper .btn-box button:active{
        background: #05679f;
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
        background-color: #40a9ff!important;
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
    .group-title .right_div{
        position: absolute!important;
        right: 0px;
        top: 0px;
        font-size:12px;
    }
</style>
<script type="text/javascript">
    if(typeof parent.showHelpText == 'function'){
        parent.showHelpText(parentShowHelpParam);
    }
    if(typeof parent.showHelpType == 'function'){
        parent.showHelpType(parentShowIndex,'{pigcms{:GROUP_NAME}','{pigcms{:MODULE_NAME}','{pigcms{:ACTION_NAME}');
    }
    $(function(){
        parent.iframeRealHeight = $('body').height() + 40;

        if(typeof parent.setMainHeight == 'function'){
            parent.setMainHeight({iframeHeight:true});
        }
        /* alert($(window.parent).scrollTop()); */
        /* parent.scrollTo(0,0); */
        // alert(parent.iframeRealHeight);
    });
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/jquery.colorpicker.js"></script>
<script type="text/javascript" charset="utf-8">
    $(function(){
        $("#choose_color_box_bgcolor").click(function(){
            change_bgcolor();
        });

        $("#choose_color_box_txtactivecolor").click(function(){
            change_txtactivecolor();
        });

        $("#choose_color_box_txtcolor").click(function(){

            change_txtcolor();
//            window.top.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'config_txtcolor')

        });


        $('.fitment').click(function(){
            var _this = $(this);
            console.log(_this.data('url'));
            if(_this.data('url') === undefined){
              return false
			}
            if(_this.data('url') == '/shequ.php?g=House&c=Library&a=index_nav&type=nav'){
                window.open(_this.data('url'));
            }else{
               var block_type= _this.data('block_type');
               if(block_type=='village_express_delivery'){
                  window.top.artiframe(_this.data('url')+'&frame_show=true&from_page=viewpage',_this.siblings('.name').text(),1300,600,true,false,false,false,'viewpage',true);
               }else{
                var popwidth=1200;
                var popheight=600;
                if(block_type=='shop_goods_seckill_block'){
                    popwidth=1400;
                    popheight=650;
                }
                window.top.artiframe(_this.data('url')+'&frame_show=true&from_page=viewpage',_this.siblings('.name').text(),popwidth,popheight,true,false,false,false,'viewpage',true);
               }
            }

        });

        $('.page-style .item').click(function(){
            $('.page-style .item').css('color', '').removeClass('selected');
            $(this).css('color', $('#config_txtactivecolor').val()).addClass('selected');
        })



        $('.page-style-wrapper .btn-box button').on('click', function(){
            window.location.href = $(this).data('url');
        })
    });

    function change_bgcolor(){
        window.showTopColorPanel("leftmenu_Viewpage_index",$("#choose_color_box_txtactivecolor").offset().top,$("#choose_color_box_txtactivecolor").height(),$("#choose_color_box_txtactivecolor").offset().left,"config_bgcolor",function(){
            var bgcolor = $('#config_bgcolor').val();
            $('.page-style').css('background-color', bgcolor);
            save_change_color('app_footer_bgcolor',bgcolor);
        });
    }

    function change_txtactivecolor(){
        window.showTopColorPanel("leftmenu_Viewpage_index",$("#choose_color_box_txtactivecolor").offset().top,$("#choose_color_box_txtactivecolor").height(),$("#choose_color_box_txtactivecolor").offset().left,"config_txtactivecolor",function(){
            var txtactivecolor = $('#config_txtactivecolor').val();
            $('.page-style .item.selected').css('color', txtactivecolor);
            save_change_color('app_footer_txtactivecolor',txtactivecolor);
        });
    }

    function change_txtcolor(){
        window.showTopColorPanel("leftmenu_Viewpage_index",$("#choose_color_box_txtcolor").offset().top,$("#choose_color_box_txtcolor").height(),$("#choose_color_box_txtcolor").offset().left,"config_txtcolor",function(){
            var txtcolor = $('#config_txtcolor').val();
            $('.page-style').css('color', txtcolor);
            save_change_color('app_footer_txtcolor',txtcolor);
        });
    }

    function save_change_color(name,color){
        $.post("{pigcms{:U('change_footer_color')}",{name:name,color:color},function(result){
            if(result.status != 1){
                layer.alert('切换失败,请重试~ ',{
//                    offset: ($('body').height() - 60) / 2
                });
            }else{
                layer.alert('切换成功',{
//                    offset: ($('body').height() - 60) / 2

                });
                window.location.href="{pigcms{:U('viewpage_list')}"
            }
        },'json');
    }

    function showTopColorPanel(frameName,ttop,thei,tleft,txt,callFunc){
        //txtobj = $('#'+txt,window.frames[frameName].document);

        $('#HexColor').val($("#"+txt+"").val());
        $('#DisColor').css('background-color',$("#"+txt+"").val() +' !important;');

        $("#colorpanel").css({

            top:ttop + thei + 5,
            left:tleft,
            'z-index':'19999',
        });
        $("#colorpanel").show();
        $("#CT tr td").unbind("click").unbind("mouseover").mouseover(function(){
            var aaa=$(this).css("background-color");
            $("#DisColor").css("background-color",aaa);
            $("#HexColor").val(aaa);
        }).click(function(){
            var aaa=$(this).css("background-color");
            $("#"+txt+"").val(aaa);
            $("#"+txt+"").css("background-color",aaa);
            $("#colorpanel").hide();
            console.log(typeof callFunc);
            if(typeof callFunc == 'function'){
                callFunc(aaa);
            }
        });
        $("#_clean").unbind("click").click(function(){
            $("#colorpanel").hide();
            $("#"+frameName+"").val('');
            $("#"+txt+"").val('');
            if(typeof callFunc == 'function'){
                callFunc();
            }
        });
        $("#_cclose").unbind("click").click(function(){$("#colorpanel").hide();}).css({"font-size":"12px","padding-left":"10px"});
        $('#HexColor').unbind("keypress").keypress(function(e){
            if(e.keyCode==13){
                $("#colorpanel").hide();
                $("#"+txt+"").val($(this).val());
                $("#"+txt+"").css("background-color",$(this).val()+' !important;');
                if(typeof callFunc == 'function'){
                    callFunc();
                }
            }
        }).css({'width':'90px'});
    }


</script>
<script type="text/javascript">
    var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}",long="{pigcms{$village_info.long}",lat="{pigcms{$village_info.lat}";
</script>
<!--<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>-->
<!--<script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>-->
<!--<script type="text/javascript" src="{pigcms{$static_path}js/map.js"></script>-->

<style>
    .BMap_cpyCtrl{display:none;}
    input.ke-input-text {
        background-color: #FFFFFF;
        background-color: #FFFFFF!important;
        font-family: "sans serif",tahoma,verdana,helvetica;
        font-size: 12px;
        line-height: 24px;
        height: 24px;
        padding: 2px 4px;
        border-color: #848484 #E0E0E0 #E0E0E0 #848484;
        border-style: solid;
        border-width: 1px;
        display: -moz-inline-stack;
        display: inline-block;
        vertical-align: middle;
        zoom: 1;
    }
    .form-group>label{font-size:12px;line-height:24px;}
    #upload_pic_box{margin-top:20px;height:150px;}
    #upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
    #upload_pic_box img{width:100px;height:70px;border:1px solid #ccc;}
    .demo .layui-layer-content{color: #3b3a3a;}
</style>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

<script type="text/javascript">
    KindEditor.ready(function(K) {
        var indexHeaderBgImageEditor = K.editor({
            allowFileManager: false
        });
        K('#index_header_bg_image_upload').click(function() {
            indexHeaderBgImageEditor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            indexHeaderBgImageEditor.loadPlugin('image', function() {
                indexHeaderBgImageEditor.plugin.imageDialog({
                    showRemote: false,
                    imageUrl: K('#index_header_bg_image').val(),
                    clickFn: function(url) {
                        K('#index_header_bg_image').val(url);
                        K('#index_header_bg_image_preview').attr('src', url).show();
                        indexHeaderBgImageEditor.hideDialog();
                    }
                });
            });
        });
        var publicRevenueBgImageEditor = K.editor({ allowFileManager: false });
        K('#public_revenue_page_bg_image_upload').click(function() {
            publicRevenueBgImageEditor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            publicRevenueBgImageEditor.loadPlugin('image', function() {
                publicRevenueBgImageEditor.plugin.imageDialog({
                    showRemote: false,
                    imageUrl: K('#public_revenue_page_bg_image').val(),
                    clickFn: function(url) {
                        K('#public_revenue_page_bg_image').val(url);
                        K('#public_revenue_page_bg_image_preview').attr('src', url).show();
                        publicRevenueBgImageEditor.hideDialog();
                    }
                });
            });
        });
    });

    function openPublicRevenueConfig() {
        $('#public_revenue_config_modal').addClass('is-open').attr('aria-hidden', 'false');
    }

    function closePublicRevenueConfig() {
        $('#public_revenue_config_modal').removeClass('is-open').attr('aria-hidden', 'true');
    }

    function clearPublicRevenueBgImage() {
        $('#public_revenue_page_bg_image').val('');
        $('#public_revenue_page_bg_image_preview').attr('src', '').hide();
    }

    function clearIndexHeaderBgImage() {
        $('#index_header_bg_image').val('');
        $('#index_header_bg_image_preview').attr('src', '').hide();
    }

    function previewIndexHeaderBgImage() {
        var imageUrl = $('#index_header_bg_image_preview').attr('src');
        if (!imageUrl) {
            return;
        }
        $('#index_header_bg_image_large').attr('src', imageUrl);
        $('#index_header_bg_image_lightbox').css('display', 'flex').attr('aria-hidden', 'false');
    }

    function closeIndexHeaderBgImagePreview() {
        $('#index_header_bg_image_lightbox').hide().attr('aria-hidden', 'true');
        $('#index_header_bg_image_large').attr('src', '');
    }

    $(document).on('click', '#index_header_bg_image_preview', previewIndexHeaderBgImage);
    $(document).on('keydown', '#index_header_bg_image_preview', function(event) {
        if (event.key === 'Enter' || event.key === ' ' || event.keyCode === 13 || event.keyCode === 32) {
            event.preventDefault();
            previewIndexHeaderBgImage();
        }
    });
    $(document).on('click', '#index_header_bg_image_lightbox', function(event) {
        if (event.target === this) {
            closeIndexHeaderBgImagePreview();
        }
    });
    $(document).on('keydown', function(event) {
        if ((event.key === 'Escape' || event.keyCode === 27) && $('#index_header_bg_image_lightbox').is(':visible')) {
            closeIndexHeaderBgImagePreview();
        }
    });

    //art弹框组件
	function artiframe(url, title, width, height, lock, resize, background, button, id, fixeds, closefun, left, top, padding){
		if(url.indexOf("?") != -1){
			url = url+'&frame=1';
		}else{
			url = url+'?frame=1';
		}
		if (!width) width = 'auto';
		if (!height){
			height = 'auto';
		}else if(height > $(window).height() * 0.8){
			height = $(window).height() * 0.8;
		}
		if (!lock) lock = false;
		if (!resize) resize = false;
		if (!background) background = 'black';
		if (!closefun) closefun = null;
		if (!button) button = null;
		if (!left) left = '50%';
		if (!top) top = '38.2%';
		if (!id) id = null;
		if (!fixeds) fixeds = false;
		if (!padding) padding = 0;
		art.dialog.open(url, {
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe' + id, iframe);
			},
			id: id,
			title: title,
			padding: padding,
			width: width,
			height: height,
			lock: lock,
			resize: resize,
			background: background,
			button: button,
			fixed: fixeds,
			close: closefun,
			left: left,
			top: top,
			opacity:'0.4'
		});
	}

    function chooseFloor() {
        var url = "{pigcms{:U('Index/house_village_list')}";
        // var href = url + '&selecteds='+selecteds;
        var href = url+'&type=1';
        var index= layer.open({
            type: 2,
            title:'选择'+"<?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区')?>",
            content: href,
            area: ['1000px', '700px'],
            offset:['0px','150px'],
            btn: ['确定','关闭'],
            maxmin: false,
            yes: function (index,layero) {
                var body = layer.getChildFrame('body', index); //得到iframe页的body内容
                var BDDM = body.find("#village_id").val();
                var index1=index;
                console.log('BDDM',BDDM);
                layer.msg('是否将'+"<?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区')?>"+'用户端样式复制到本'+"<?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区')?>"+'？', {
                    time: 0 //不自动关闭
                    ,btn: ['确定', '取消']
                    ,icon: 3
                    ,offset:['550px','750px']
                    ,skin:'demo'
                    ,yes: function(index){
                        if (BDDM==''){
                            layer.alert('请选择一个'+"<?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区')?>", {icon: 2,offset:['350px','750px']});
                            layer.close(index);
                        }else{
                            console.log(BDDM);
                            layer.close(index1);
                            layer.close(index);
                            copy_village(BDDM);
                        }

                    }
                });

            },
        });
    }
    function copy_village(village_id) {
        $.post("{pigcms{:U('copy_village_info')}",{village_id:village_id},function(result){
            if(result.status != 1){
                layer.alert('复制失败,请重试~ ',{
                });
            }else{
                layer.alert('复制成功',{
                });
                window.location.href="{pigcms{:U('viewpage_list')}"
            }
        },'json');
    }
	//art信息提示
	function saveHomeColorConfig() {
		$.post("{pigcms{:U('save_home_color_config')}", {
			switch_village_color: $('#switch_village_color').val(),
			index_title_color: $('#index_title_color').val(),
			index_header_bg_color: $('#index_header_bg_color').val(),
			index_header_bg_image: $('#index_header_bg_image').val()
		}, function (result) {
			layer.msg(result.msg);
			if (result.status == 1) {
				document.getElementById('viewpageIframe').contentWindow.location.reload(true);
			}
		}, 'json');
	}

	function savePublicRevenueHomeConfig() {
		var title = $.trim($('#public_revenue_home_title').val());
		if (!title) {
			layer.msg('请输入公共收益入口标题');
			return;
		}
		$.post("{pigcms{:U('save_public_revenue_home_config')}", {
			public_revenue_home_show: $('input[name="public_revenue_home_show"]:checked').val(),
			public_revenue_home_title: title,
			public_revenue_home_bg_color: $('#public_revenue_home_bg_color').val(),
			public_revenue_page_bg_color: $('#public_revenue_page_bg_color').val(),
			public_revenue_page_bg_image: $('#public_revenue_page_bg_image').val()
		}, function (result) {
			layer.msg(result.msg);
			if (result.status == 1) {
				closePublicRevenueConfig();
				document.getElementById('viewpageIframe').contentWindow.location.reload(true);
			}
		}, 'json');
	}

	function msg(status,msg,lock,time){
		var list = window.art.dialog.list;
		for (var i in list){
			if(i == 'form_submit_tips'){
				list[i].close();
			}
		};
		if(lock){
			lock = true;
		}else{
			lock = false;
		}
		if (!time){
			time = 2;
		}
		if(status == 2){
			ico = 'face-smile';
		}else if(status){
			ico = 'succeed';
		}else{
			ico = 'error';
		}
		if(status == 2){
			id = 'form_submit_tips';
		}else{
			id = 'msg' + Math.random();
		}
		art.dialog({
			icon: ico,
			time: time,
			/*background: '#FFF',*/
			title: '提示信息',
			id: id,
			opacity:'0.4',
			lock: lock,
			fixed: true,
			resize: false,
			content: msg
		});
	}
</script>

<include file="Public:footer"/>
