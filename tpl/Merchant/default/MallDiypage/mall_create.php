<include file="Public:header"/>
<div class="main-content">
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
                                        <ul>
                                            <h5 style="font-size: 12px">基础组件</h5>
                                            <li><a class="js-new-field rich-text" data-field-type="rich_text">富文本</a></li>
                                            <li><a class="js-new-field rich-text" data-field-type="goods">商品</a></li>
                                            <li><a class="js-new-field rich-text" data-field-type="goods_group2">商品分组</a></li>
                                            <li><a class="js-new-field rich-text" data-field-type="image_ad">图片广告</a></li>
                                            <li><a class="js-new-field rich-text" data-field-type="image_nav">图片导航</a></li>
                                            <li><a class="js-new-field rich-text" data-field-type="title">标题</a></li>
                                            <li><a class="js-new-field rich-text" data-field-type="text_nav">文本导航</a></li>
                                        </ul>
                                        <ul>
                                            <h5 style="font-size: 12px">营销组件</h5>
                                            <li><a class="js-new-field rich-text" data-field-type="coupons">优惠券</a></li>
                                            <li>
                                                <a class="js-new-field rich-text" data-field-type="group_module">拼团</a>
                                                <i class="icon-fire_group"></i>
                                            </li>
                                            <li>
                                                <a class="js-new-field rich-text" data-field-type="bargain_module">砍价</a>
                                                <i class="icon-fire_bargain"></i>
                                            </li>
                                            <li>
                                                <a class="js-new-field rich-text" data-field-type="seckill_module">秒杀</a>
                                                <i class="icon-fire_seckill"></i>
                                            </li>
                                            <li>
                                                <a class="js-new-field rich-text" data-field-type="periodic_module">周期购</a>
                                                <i class="icon-fire_periodic"></i>
                                            </li>
                                        </ul>
                                        <ul>
                                            <h5 style="font-size: 12px">其他</h5>
                                            <li><a class="js-new-field rich-text" data-field-type="notice">公告</a></li>
                                            <li><a class="js-new-field rich-text" data-field-type="white">辅助空白</a></li>
                                            <li><a class="js-new-field rich-text" data-field-type="map">地图</a></li>
                                        </ul>
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
<div style="display:none;" id="edit_data"></div>
<div style="display:none;" id="edit_custom"></div>
<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
<link rel="stylesheet" href="../../../System/Static/css/mall_custom-ui.css"/>
<link rel="stylesheet" href="{pigcms{$static_path}diypage/css/mallCustomField.css?t=1"/>
<link rel="stylesheet" href="{pigcms{$static_path}diypage/css/pc-1.css?t=1"/>
<link rel="stylesheet" href="{pigcms{$static_path}diypage/css/mallCustomFieldActivities.css?t=1"/>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script type="text/javascript" src="../../../../static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="../../../../static/js/artdialog/iframeTools.js"></script>
<script src="{pigcms{$static_path}diypage/js/vuex/3.0.1/vuex.min.js"></script>
<script src="{pigcms{$static_path}diypage/js/vue/2.5.17/vue.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/laytpl.js"></script>
<script type="text/javascript">
    function LoadJS( fileUrl )
    {
        var oHead = document.getElementsByTagName('HEAD').item(0);
        var oScript= document.createElement("script");
        oScript.type = "text/javascript";
        oScript.src=fileUrl ;
        oHead.appendChild( oScript);

    }
    var store_id = window.parent.location.href.split('store_id=')[1].split('&')[0];
    var path = "{pigcms{$static_path}diypage/js/mallCustomField.js?040";
    var group_alias_name = "{pigcms{$config.group_alias_name}";
    var shop_alias_name = "{pigcms{$config.shop_alias_name}";
    var staticpath = "{pigcms{$static_path}diypage/";
    var upload_url = staticpath;
    var uploadJson = "{pigcms{:U('MallDiypage/upload')}";
    var is_show_activity = <if condition="$config['wxapp_url']">1<else/>0</if>;
    var diyVideo = "{pigcms{:U('Article/diyVideo')}";
    var diyTool = "{pigcms{:U('Article/diytool')}";
    var shop_type=2;
    var user_index = "{pigcms{$config.site_url}/wap.php?c=My&a=index";
    var page_goods_source = "{pigcms{$page_goods_source}";//来源
    var add_url = '';
    var edit_url = '';
    var imageList = '';
    var store_name = '';
    var store_logo = '';
    var mycard_url = '';
    var wap_home_url = '';
    var coupon_url = '';
    var wap_product_list_url = '';
    var merchant_store_url = '';
    var merchant_shop_list_url = '';
    var select_store_url = '';
    var mer_id = '';
    var kefu_mer_id = "{pigcms{$config.kefu_mer_id}";//平台在线客服/自营商家ID：
    var is_kefu_mer = 0;
    $.ajax({
        "url":"{pigcms{$config.site_url}/merchant.php?g=Merchant&c=MallDiypage&a=create_ajax",
        "data": {store_id},
        "type":"post",
        "dataType":"json",
        "success":function(obj) {
             add_url = "/merchant.php?g=Merchant&c=MallDiypage&a=page_add&store_id="+obj.now_store['store_id'];
             edit_url = "/merchant.php?g=Merchant&c=MallDiypage&a=page_add&store_id="+obj.now_store['store_id'];
             imageList = "/merchant.php?g=Merchant&c=MallDiypage&a=imageList&store_id="+obj.now_store['store_id'];
             store_name = obj.now_store['name'];
             store_logo = obj.now_store['pic'];
             mycard_url = "{pigcms{$config.site_url}/wap.php?c=My_card&a=merchant_card&mer_id="+obj.now_store['store_id'];
             wap_home_url = "{pigcms{$config.site_url}/wap.php?c=Mall&a=store&store_id="+obj.now_store['store_id'];
             coupon_url = "{pigcms{$config.site_url}/wap.php?c=My_card&a=merchant_coupon&mer_id="+obj.now_store['mer_id'];
             wap_product_list_url = "{pigcms{$config.site_url}/wap.php?c=Mall&a=store&store_id="+obj.now_store['store_id']+"&show_own=1";
             merchant_store_url = "{pigcms{$config.site_url}/wap.php?c=My_card&a=merchant_store&mer_id="+obj.now_store['mer_id']+"&show_own=1";
             merchant_shop_list_url = "{pigcms{$config.site_url}/wap.php?c=My_card&a=merchant_shop_list&mer_id="+obj.now_store['mer_id']+"&show_own=1";
             select_store_url = "/merchant.php?g=Merchant&c=MallDiypage&a=select_store&store_id="+obj.now_store['store_id'];
             mer_id = obj.now_store['mer_id'];
            if (kefu_mer_id==mer_id) {
                is_kefu_mer = 1;
            }
            LoadJS( path);
            if(obj.now_page){
                $("input[name='title']").val(obj.now_page.page_name)
                $("input[name='description']").val(obj.now_page.page_desc)
                $("input[name='color']").val(obj.now_page.bgcolor)
                $('.js-config-region .app-field').data({'page_id':obj.now_page.page_id,'page_name':obj.now_page.page_name,'page_desc':obj.now_page.page_desc,'bgcolor':obj.now_page.bgcolor});
                $('.js-config-region h1 span').html(obj.now_page.page_name);
                setTimeout(function () {
                    setHtml(obj.now_page_custom)
                },600)
                $('#edit_data,#edit_custom').remove()
            }
        },
        "error":function(obj) {
        }
    })

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

    .icon-fire_group {
        position: absolute;
        left: 188px;
        width: 14px;
        height: 14px;
        background-image: url({pigcms{$static_path}diypage/images/icon-fire.png);
    }
    .icon-fire_bargain {
        position: absolute;
        left: 289px;
        width: 14px;
        height: 14px;
        background-image: url({pigcms{$static_path}diypage/images/icon-fire.png);
    }
    .icon-fire_seckill {
        position: absolute;
        left: 86px;
        width: 14px;
        height: 14px;
        background-image: url({pigcms{$static_path}diypage/images/icon-fire.png);
    }
    .icon-fire_periodic {
        position: absolute;
        left: 194px;
        width: 14px;
        height: 14px;
        background-image: url({pigcms{$static_path}diypage/images/icon-fire.png);
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

</style>

<script id="pageTitleTpl" type="text/html">
    <div>
        <form class="form-horizontal">
            <div class="control-group">
                <label class="control-label"><em class="required">*</em>{pigcms{:L_("页面名称：")}</label>
                <div class="controls"><input class="input-xxlarge" type="text" name="title"/></div>
            </div>
            <div class="control-group">
                <label class="control-label">{pigcms{:L_("页面描述：")}</label>
                <div class="controls"><input class="input-xxlarge" type="text" name="description"" placeholder="{pigcms{:L_("用户通过微信分享给朋友时，会自动显示页面描述")}"/></div>
            </div>
            <div class="control-group">
                <label class="control-label">{pigcms{:L_("背景颜色：")}</label>
                <div class="controls">
                    <input type="color" name="color"/>
                    <button class="btn js-reset-bg" type="button">{pigcms{:L_("重置")}</button>
                    <p class="help-desc">{pigcms{:L_("背景颜色只在手机端显示。")}</p>
                </div>
            </div>
        </form>
    </div>
</script>
<!--<div id="nprogress"><div class="bar" role="bar"><div class="peg"></div></div><div class="spinner" role="spinner"><div class="spinner-icon"></div></div></div>-->
<include file="Public:mall_activities_template"/>
<include file="Public:footer"/>