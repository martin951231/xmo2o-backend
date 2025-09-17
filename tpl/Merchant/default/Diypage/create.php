<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Config/store')}">{pigcms{:L_("店铺管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Diypage/index',array('store_id'=>$now_store['store_id']))}">【{pigcms{$now_store.name}{pigcms{:L_("】 自定义页面列表")}</a></li>
			<li class="active"><if condition="$_GET['page_id']">{pigcms{:L_("编辑")}<else/>{pigcms{:L_("新建")}</if>{pigcms{:L_("自定义页面")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="app-init-container">
                <div class="nav-wrapper--app"></div>
                <div class="app__content js-app-main">
					<div class="app-design clearfix">
						<div class="app-preview">
							<div class="app-header"></div>
							<div class="app-entry">
								<div class="app-config js-config-region">
									<div class="app-field clearfix editing">
										<h1><span>{pigcms{:L_("页面标题")}</span></h1>
									</div>
								</div>
								<div class="app-fields js-fields-region">
									<div class="app-fields ui-sortable"></div>
								</div>
							</div>
							<div class="js-add-region">
								<div>
									<div class="app-add-field">
										<div style="margin-top: 6px">
											<div class="field-title">基础组件</div>
											<div class="field-text">
												<ul class="field-text-ul">
													<li><a class="js-new-field rich-text" data-field-type="title">{pigcms{:L_("标题")}</a></li>
													<li><a class="js-new-field rich-text" data-field-type="rich_text">{pigcms{:L_("富文本")}</a></li>
													<li><a class="js-new-field rich-text" data-field-type="text_nav">{pigcms{:L_("文本导航")}</a></li>
													<li><a class="js-new-field rich-text" data-field-type="line">{pigcms{:L_("辅助线")}</a></li>
													<li><a class="js-new-field rich-text" data-field-type="white">{pigcms{:L_("辅助空白")}</a></li>

													<li><a class="js-new-field rich-text" data-field-type="image_nav">{pigcms{:L_("图片导航")}</a></li>
													<if condition="$config['kefu_mer_id'] neq $merchant_session['mer_id']">
														<li><a class="js-new-field rich-text" data-field-type="search">{pigcms{:L_("商品搜索")}</a></li>
														<li><a class="js-new-field rich-text" data-field-type="goods">{pigcms{:L_("商品")}</a></li>
			                                            <li><a class="js-new-field rich-text" data-field-type="goods_group1">{pigcms{:L_("商品分组(左部导航)")}</a></li>
			                                            <li><a class="js-new-field rich-text" data-field-type="goods_group2">{pigcms{:L_("商品分组(头部导航)")}</a></li>
													</if>
													<li><a class="js-new-field rich-text" data-field-type="image_ad">{pigcms{:L_("图片广告")}</a></li>
		                                            <li><a class="js-new-field rich-text" data-field-type="link">{pigcms{:L_("关联链接")}</a></li>
													<li class="tpdh_xfdb_btn" <if condition="$page_goods_source eq 2">style="display:none;"</if>><a class="js-new-field rich-text" data-field-type="image_nav_bottom">{pigcms{:L_("底部导航")}</a></li>
												</ul>
											</div>
										</div>

										<div>
											<div class="field-title">营销组件</div>
											<div class="field-text">
												<ul class="field-text-ul">
		                                            <li><a class="js-new-field rich-text" data-field-type="coupons">{pigcms{:L_("优惠券")}</a></li>
		                                            <li><a class="js-new-field rich-text" data-field-type="cube">{pigcms{:L_("魔方")}</a></li>
													<if condition="$config['kefu_mer_id'] neq $merchant_session['mer_id']">
														<li><a class="js-new-field rich-text" data-field-type="store">{pigcms{:L_("进入店铺")}</a></li>
														<if condition="$config['wxapp_url']">
															<li><a class="js-new-field rich-text" data-field-type="new_activity_module">{pigcms{:L_("营销活动")}</a></li>
														</if>
		                                            	<li><a class="js-new-field rich-text" data-field-type="new_activity_module">{pigcms{:L_("活动模块")}</a></li>
		                                            	<li><a class="js-new-field rich-text" data-field-type="seckill_module">{pigcms{:L_("秒杀组件")}</a></li>
		                                            	<li><a class="js-new-field rich-text" data-field-type="goods_group3">{pigcms{:L_("外卖模块")}</a></li>
			                                            <li class="tuan_module_btn" <if condition="$page_goods_source neq 2">style="display:none;"</if>><a class="js-new-field rich-text tuan_module_btn" data-field-type="tuan_module">{pigcms{:L_("拼团组件")}</a></li>
			                                            <li class="bargain_module_btn" <if condition="$page_goods_source neq 2">style="display:none;"</if>><a class="js-new-field rich-text" data-field-type="bargain_module">{pigcms{:L_("砍价组件")}</a></li>
													</if>
												</ul>
											</div>
										</div>

										<div>
											<div class="field-title">其他组件</div>
											<div class="field-text">
												<ul class="field-text-ul">
													<li><a class="js-new-field rich-text" data-field-type="notice">{pigcms{:L_("公告")}</a></li>
													<if condition="$config['kefu_mer_id'] neq $merchant_session['mer_id']">
		                                           	 	<li><a class="js-new-field rich-text" data-field-type="store_map">{pigcms{:L_("联系店铺")}</a></li>
														<li><a class="js-new-field rich-text" data-field-type="map">{pigcms{:L_("店铺地图")}</a></li>
		                                            	<li><a class="js-new-field rich-text" data-field-type="attention_collect">{pigcms{:L_("店铺收藏")}</a></li>
			                                            <li><a class="js-new-field rich-text" data-field-type="tpl_shop1">{pigcms{:L_("不带导航的头部")}</a></li>
			                                            <li><a class="js-new-field rich-text" data-field-type="tpl_shop">{pigcms{:L_("带导航的头部")}</a></li>
			                                        <else/>
			                                            <li><a class="js-new-field rich-text" data-field-type="store_cate">{pigcms{:L_("店铺分类")}</a></li>
													</if>
												</ul>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="app-sidebars">
							<div class="app-sidebar" style="margin-top:72px;">
								<div class="arrow"></div>
								<div class="app-sidebar-inner js-sidebar-region"></div>
							</div>
						</div>
						<div class="app-actions bottom" style="display:block;bottom:0px;">
							<div class="form-actions text-center">
								<input class="btn btn-primary btn-save" type="submit" value="{pigcms{:L_("保存")}" data-loading-text="{pigcms{:L_("保存")}...">
							</div>
						</div>
					</div>
				</div>
            </div>
		</div>
	</div>
</div>
<if condition="$now_page">
	<div style="display:none;" id="edit_data" page-name="{pigcms{$now_page.page_name}" page-id="{pigcms{$now_page.page_id}" page-desc="{pigcms{$now_page.page_desc}" bgcolor="{pigcms{$now_page.bgcolor}" menu_bgcolor="{pigcms{$now_page.menu_bgcolor}"></div>
	<div style="display:none;" id="edit_custom" custom-field='{pigcms{:json_encode($now_page_custom, JSON_UNESCAPED_UNICODE|JSON_HEX_APOS)}'></div>
</if>
<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
<link rel="stylesheet" href="./tpl/System/Static/css/custom-ui.css"/>
<link rel="stylesheet" href="{pigcms{$static_path}diypage/css/customField.css?t=1"/>
<link rel="stylesheet" href="{pigcms{$static_path}diypage/css/pc-1.css?t=1"/>
<link rel="stylesheet" href="{pigcms{$static_path}diypage/css/customFieldActivities.css?t=1"/>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
	var group_alias_name = "{pigcms{$config.group_alias_name}";
	var shop_alias_name = "{pigcms{$config.shop_alias_name}";
	var staticpath = "{pigcms{$static_path}diypage/";
	var upload_url = staticpath;
	var imageList = "{pigcms{:U('Diypage/imageList',array('store_id'=>$now_store['store_id']))}";
	var uploadJson = "{pigcms{:U('Diypage/upload')}";
	var store_id = "{pigcms{$now_store.store_id}";
	var store_name = "{pigcms{$now_store.name}";
	var store_logo = "{pigcms{$now_store.all_pic.0}";
	var add_url = "{pigcms{:U('page_add',array('store_id'=>$now_store['store_id']))}";
	var edit_url = "{pigcms{:U('page_add',array('store_id'=>$now_store['store_id']))}";
	var mycard_url = "{pigcms{$config.site_url}/wap.php?c=My_card&a=merchant_card&mer_id={pigcms{$now_store.mer_id}";
	var wap_home_url = "{pigcms{$config.site_url}/wap.php?c=Mall&a=store&store_id={pigcms{$now_store.store_id}";
	var coupon_url = "{pigcms{$config.site_url}/wap.php?c=My_card&a=merchant_coupon&mer_id={pigcms{$now_store.mer_id}";
	var wap_product_list_url = "{pigcms{$config.site_url}/wap.php?c=Mall&a=store&store_id={pigcms{$now_store.store_id}&show_own=1";
	var merchant_store_url = "{pigcms{$config.site_url}/wap.php?c=My_card&a=merchant_store&mer_id={pigcms{$now_store.mer_id}&show_own=1";
	var merchant_shop_list_url = "{pigcms{$config.site_url}/wap.php?c=My_card&a=merchant_shop_list&mer_id={pigcms{$now_store.mer_id}&show_own=1";
	var is_show_activity = <if condition="$config['wxapp_url']">1<else/>0</if>;
	var diyVideo = "{pigcms{:U('Article/diyVideo')}";
	var diyTool = "{pigcms{:U('Article/diytool')}";
	var shop_type=2;
    var dining_self_take_store = "{pigcms{$config.site_url}/packapp/plat/pages/foodshop/merchant/storeList?dining_type=selftake";
    var dining_store = "{pigcms{$config.site_url}/packapp/plat/pages/foodshop/merchant/storeList";
    var dining_book_store = "{pigcms{$config.site_url}/packapp/plat/pages/foodshop/merchant/storeList?dining_type=book";
    var dining_eating_store = "{pigcms{$config.site_url}/packapp/plat/pages/foodshop/merchant/storeList?dining_type=inhouse";

    var user_index = "{pigcms{$config.site_url}/wap.php?c=My&a=index";
    
	var page_goods_source = "{pigcms{$page_goods_source}";//来源
	var is_hark = "{pigcms{$is_hark|default=0}";
	

	var select_store_url = "{pigcms{:U('Diypage/select_store',array('store_id'=>$now_store['store_id']))}";
	var kefu_mer_id = "{pigcms{$config.kefu_mer_id}";//平台在线客服/自营商家ID：
	var mer_id = "{pigcms{$now_store.mer_id}";
	var is_kefu_mer = 0;
	if (kefu_mer_id==mer_id) {
		is_kefu_mer = 1;
	}
</script>
<style>
    .modal {
        position: fixed;
        top: 10%;
        left: 50%;
        z-index: 1050;
        width: 560px;
        min-height: 400px;
        margin-left: -280px;
        background-color: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.3);
        -webkit-border-radius: 6px;
        -moz-border-radius: 6px;
        border-radius: 6px;
        outline: none;
        -webkit-box-shadow: 0 3px 7px rgba(0, 0, 0, 0.3);
        -moz-box-shadow: 0 3px 7px rgba(0, 0, 0, 0.3);
        box-shadow: 0 3px 7px rgba(0, 0, 0, 0.3);
        -webkit-background-clip: padding-box;
        -moz-background-clip: padding-box;
        background-clip: padding-box;
        bottom: 25%;
    }

    @media screen and (max-width:1600px) {
        .modal-body {
            max-height: 500px !important;
        }
    }
    @media screen and (max-width:1366px) {
        .modal-body {
            max-height: 400px !important;
        }
    }
    @media screen and (min-width:1600px) {
        .modal-body {
            max-height: 600px !important;
        }
    }
    .clearfix:after {
        clear: both;
    }
    .clearfix:before, .clearfix:after {
        display: table;
        line-height: 0;
        content: "";
    }
    em, h1, h2, h3, h4, input, li, ol, p, span, textarea, ul {
        margin: 0;
        padding: 0;
        /* font-weight: 400; */
        list-style: none;
        font-style: normal;
    }
    .modal {
        margin-top: auto;
        width: 650px;
        margin-left: -325px;
    }
    .contact_store {
        display: block;
        padding: 0px 10px 5px 10px;
        border-radius: 4px;
        margin: 10px;
        position: relative;
        background: #fff;
    }
    .contact_store dl dd {
        font-size: 14px;
        font-weight: 700;
        color: #333;
        margin-left: 0;
        line-height: 40px;
    }
    .contact_icon {
        position: absolute;
        right: 20px;
        top: 15px;
    }
    .contact_icon a {
        width: 24px;
        height: 24px;
        margin-left: 10px;
    }
    .contact_icon a img {
        width: 24px;
    }
    .new_activity_module_msg .coupon-money {
        line-height: 15px;
    }
	    
	.app-fields.addProductBox.fixed{
		position:fixed;
		bottom:0px;
	    width: 320px;
		z-index: 3;
		height:91px;
	}
	.app-fields.addProductBox.fixed .app-field{
	    background: rgba(0,0,0,0.6) !important;
	}
	.app-fields.addProductBox.fixed .app-field .addProductBtn{
	    margin-top:0px;
	    margin-bottom:0px;
		padding-top:15px;
	    padding-bottom:15px;
		color:white;
	}

	.addCategoryBtn{
		height:50px;
		background:white;
		cursor:pointer;
	    position: relative;
	}

	.addCategoryBtn .right{
	    position: absolute;
	    top: 0;
	    right: 17px;
	    color: white;
	    font-size: 26px;
	    line-height: 50px;
		width: 24px;
	    right: 0px;
	    background: rgba(0,0,0,0.3);
	    text-align: center;
	}
	.addCategoryBtn  span{
		height:50px;
		line-height:50px;
		width:100%;
		text-align:center;
		overflow:hidden;
		float:left;
	}

	.addCategoryBtn  span.curr{
	    background: #f98100;
	    color: #FFF;
	}

	.flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; display: box; align-items: center;  }
	.flex_justify{-webkit-box-pack: justify;-webkit-justify-content: space-between;-ms-flex-pack: justify;justify-content: space-between;box-pack: center;}
	.flex-align{-webkit-box-align: center;-moz-align-items: center;-webkit-align-items: center; align-items: center;box-align: center;}
	.flex_one{flex: 1;-webkit-box-flex: 1;-webkit-flex: 1;}
	.flex-wrap{-webkit-flex-wrap: wrap;-moz-flex-wrap: wrap;-ms-flex-wrap: wrap;-o-flex-wrap: wrap;flex-wrap:wrap;}

	/* 改版新加样式*/
	.field-title{
		font-size: 14px;
	    font-weight: bold;
	    padding: 10px 4px;
	}	
	.app-design .app-add-field a {
	    padding: 4px
	}
	.app-add-field .js-new-field{    
		width: 94px !important;
    	height: 36px !important;
	    background: inherit;
	    background-color: rgba(242, 242, 246, 1) !important;
	    border: none;
	    border-radius: 0px;
	    -moz-box-shadow: none;
	    -webkit-box-shadow: none;
	    box-shadow: none;
	    font-size: 12px !important;
	    color: #595959 !important;
	}
	.field-text-ul{
		display: flex;
    	flex-wrap: wrap;
    	justify-content: flex-start;
	}
	.app-design .app-add-field li {
	    margin: 0 5px 10px 5px;
	}
	.app-design .app-add-field ul {
	    margin-left: 6px !important;
	}
</style>
<!--<script src="//cdn.staticfile.org/vuex/3.0.1/vuex.min.js"></script>-->
<!--<script src="//cdn.staticfile.org/vue/2.5.17/vue.min.js"></script>-->
<script src="{pigcms{$static_path}diypage/js/vuex/3.0.1/vuex.min.js"></script>
<script src="{pigcms{$static_path}diypage/js/vue/2.5.17/vue.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}diypage/js/customField.js?t=04"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/laytpl.js"></script>
<script id="pageTitleTpl" type="text/html">
	<div>
		<form class="form-horizontal">
			<div class="control-group">
				<label class="control-label"><em class="required">*</em>{pigcms{:L_("商品来源：")}</label>
				<div class="controls">
					<if condition="$page_id">
						<if condition="$page_goods_source eq 1 "><input class="input-xxlarge" type="radio" disabled checked name="page_goods_source" value="1"/>{pigcms{:C('config.shop_alias_name')}</if>
						<if condition="$page_goods_source eq 2 "><input class="input-xxlarge" type="radio" disabled checked name="page_goods_source" value="2"/>{pigcms{:C('config.mall_alias_name')}</if>
					<else/>
						<input class="input-xxlarge" type="radio"  <if condition="$page_goods_source eq 1 ">checked</if> name="page_goods_source" value="1"/>{pigcms{:C('config.shop_alias_name')}&nbsp;&nbsp;
						<input class="input-xxlarge" type="radio"  <if condition="$page_goods_source eq 2 ">checked</if> name="page_goods_source" value="2"/>{pigcms{:C('config.mall_alias_name')}
					</if>
				</div>

			</div>
			<div class="control-group">
				<label class="control-label"><em class="required">*</em>{pigcms{:L_("页面名称：")}</label>
				<div class="controls"><input class="input-xxlarge" type="text" name="title" value="{{ d.page_name }}"/></div>
			</div>
			<div class="control-group">
				<label class="control-label">{pigcms{:L_("页面描述：")}</label>
				<div class="controls"><input class="input-xxlarge" type="text" name="description" value="{{ d.page_desc }}" placeholder="{pigcms{:L_("用户通过微信分享给朋友时，会自动显示页面描述")}"/></div>
			</div>
			<div class="control-group">
				<label class="control-label">{pigcms{:L_("背景颜色：")}</label>
				<div class="controls">
					<input type="color" value="{{ d.bgcolor }}" name="color"/>
					<button class="btn js-reset-bg" type="button">{pigcms{:L_("重置")}</button>
					<p class="help-desc">{pigcms{:L_("背景颜色只在手机端显示。")}</p>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">{pigcms{:L_("导航栏背景颜色：")}</label>
				<div class="controls">
					<input type="color" value="{{ d.menu_bgcolor }}" name="menu_bgcolor"/>
					<button class="btn js-reset-menubg" type="button">{pigcms{:L_("重置")}</button>
					<p class="help-desc">{pigcms{:L_("背景颜色只在手机端显示。")}</p>
				</div>
			</div>
		</form>
	</div>
</script>
<!--<div id="nprogress"><div class="bar" role="bar"><div class="peg"></div></div><div class="spinner" role="spinner"><div class="spinner-icon"></div></div></div>-->
<include file="Public:activities_template"/>
<include file="Public:footer"/>