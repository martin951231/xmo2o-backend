<include file="Publics:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-rtl.min.css">
<script src="./tpl/House/default/static/layer/layer.js"></script>
<style>
    .visualize_assign:hover{
        background-color: #438eb9!important;
    }
</style>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
								.slider {
                    width: 20px !important;
                    height: 20px !important;
								}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div class="tabbable">
                        <ul id="myTab" class="nav nav-tabs">
                            <li class="active">
                                <a href="#basicinfo" onclick="viewpage_list()" data-toggle="tab">首页</a>
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
                        <div class="tab-pane active" id="basicinfo" style="height: 88% !important;">
                            <div class="table-list">
                                <div class="fitment_div">

                                   <if condition="$jsdhwy_new_wap_index gt 0">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">小区快送</span>
                                                <div class="div_right">
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;"  data-block_type="village_express_delivery" data-url="{pigcms{:U('House/village_express_delivery',array('is_community_platform'=>1,'block_type_id' => 'business_express_delivery','source'=>1))}">装修</button>
                                                 <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="30">分配权限</button>
                                                </div>
                                            </div>
                                            <div class="group-content">小区快送展示：快店、新版商城、社区团购、预约服务的产品信息</div>
                                        </div>
                                    <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">小区生活服务</span>
                                                <div class="div_right">
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('House/house_nav_2list',array('is_community_platform'=>1,'block_type_id' => 'village_life_service'))}">装修</button>
                                                  <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="31">分配权限</button>
                                                </div>
                                            </div>
                                            <div class="group-content">小区快送展示：快店、新版商城、社区团购、预约服务的产品信息</div>
                                        </div>

                                    <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">家庭助理</span>
                                                 <div class="div_right">
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('House/house_nav_2list',array('is_community_platform'=>1,'block_type_id' => 'village_life_assistant'))}">装修</button>
                                                 <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="32">分配权限</button>
                                                </div>
                                            </div>
                                            <div class="group-content">小区快送展示：快店、新版商城、社区团购、预约服务的产品信息</div>
                                        </div>
                                    </if>
                                    <if condition="!$jsdhwy_new_wap_index">
                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">轮播图</span>
                                            <div class="div_right">
                                                <button type="button" class="btn fitment" style="height: 28px;" data-url="{pigcms{:U('House/house_adver_list',array('source'=>'1'))}">装修</button>
                                                <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="1">分配权限</button>
                                            </div>
                                        </div>
                                        <div class="group-content">尺寸:715*278。</div>
                                    </div>
                                    </if>
                                    
                                  <if condition="$yuanJino2oCustomized">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">首页弹窗</span>
                                               <div class="div_right">
                                                <button type="button" class="btn fitment" style="height: 28px;" data-url="{pigcms{:U('House/index_nav_pop')}">装修</button>
                                                <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="14">分配权限</button>
                                                </div>
                                            </div>
                                            <div class="group-content">尺寸:586*734。</div>
                                        </div>

                                     </if>

                                    <if condition="$yingshan">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">单行功能导航</span>
                                                <div class="div_right">
                                                    <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('House/house_nav_list',array('row_type' => 'single','source'=>'1'))}">装修</button>
                                                    <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="11">分配权限</button>
                                                </div>

                                            </div>
                                            <div class="group-content">尺寸:150*150。</div>
                                        </div>
                                    </if>

                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">中间栏轮播广告图</span>
                                            <div class="div_right">
                                                <button type="button" class="btn fitment" style="height: 28px;" data-url="{pigcms{:U('House/house_middle_advert_list',array('source'=>'1'))}">装修</button>
                                                <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="3">分配权限</button>
                                            </div>
                                        </div>
                                        <div class="group-content">尺寸:715*206,不设置,不展示。</div>
                                    </div>

                                  <if condition="$jsdhwy_new_wap_index gt 0">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">直播</span>
                                                <div class="div_right">
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('House/house_nav_live',array('is_community_platform'=>1,'block_type_id' => 'business_live_broadcast'))}">装修</button>
                                               <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="33">分配权限</button>
                                            </div>
                                            </div>
                                            <div class="group-content">小区可设置是否显示直播内容。</div>
                                        </div>

                                    </if>

                                  <if condition="$yuanJino2oCustomized">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">中间栏功能导航</span>
                                               <div class="div_right">
                                                <button type="button" class="btn fitment" style="height: 28px;" data-url="{pigcms{:U('House/house_nav_list',array('source'=>'1','row_type' => 'middle'))}">装修</button>
                                                <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="13">分配权限</button>
                                                </div>
                                            </div>
                                            <div class="group-content">尺寸:200*200。</div>
                                        </div>
                                     </if>

                                    <if condition="!$jsdhwy_new_wap_index">
                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">功能导航</span>
                                            <div class="div_right">
                                                <button type="button" class="btn fitment" style="height: 28px;" data-url="{pigcms{:U('House/house_nav_list',array('row_type' => 'normal','source'=>'1'))}">装修</button>
                                                <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="2">分配权限</button>
                                            </div>
                                        </div>
                                        <div class="group-content">尺寸:150*150。</div>
                                    </div>
                                   </if>
                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">四图广告位</span>
                                           <div class="div_right">
                                               <button type="button" class="btn fitment" style="height: 28px;" data-url="{pigcms{:U('Visualize/adver_list',array('cat_key'=>'house_four_adver'))}">装修</button>
                                               <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="4">分配权限</button>
                                           </div>
                                        </div>
                                        <div class="group-content">可以设置四图广告位,不设置不展示。</div>
                                    </div>

                                    <if condition="$yingshan">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">四宫格推荐广告位</span>
                                                <div class="div_right">
                                                    <button type="button" class="btn fitment" style="height: 28px;" data-url="/shequ.php?g=House&c=Adver&a=grid_num_list&grid_num=4&source=sys">装修</button>
                                                    <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="8">分配权限</button>
                                                </div>
                                            </div>
                                            <div class="group-content">可以设置四图广告位,不设置不展示。</div>
                                        </div>
                                    </if>
                                <if condition="$jsdhwy_new_wap_index gt 0">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">邻里猜你喜欢</span>
                                                 <div class="div_right">
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('House/house_nav_guess_love',array('is_community_platform'=>1,'block_type_id' => 'business_classify_info'))}">装修</button>
                                                <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="34">分配权限</button>
                                            </div>
                                            </div>
                                            <div class="group-content">修改猜你喜欢的标题，以及副标题描述，猜你喜欢展示的业务类别。</div>
                                        </div>

                                    </if>

                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">中部单图广告位</span>
                                            <div class="div_right">
                                                <button type="button" class="btn fitment" style="height: 28px;" data-url="{pigcms{:U('Visualize/adver_list',array('cat_key'=>'house_center_adver'))}">装修</button>
                                                <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="5">分配权限</button>
                                            </div>
                                        </div>
                                        <div class="group-content">可以设置单张广告,不设置不显示。</div>
                                    </div>

                                <if condition="$yingshan">
                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">三宫格推荐广告位</span>
                                            <div class="div_right">
                                                <button type="button" class="btn fitment" style="height: 28px;" data-url="/shequ.php?g=House&c=Adver&a=grid_num_list&grid_num=3&source=sys">装修</button>
                                                <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="9">分配权限</button>
                                            </div>
                                        </div>
                                        <div class="group-content">可以设置四图广告位,不设置不展示。</div>
                                    </div>
                                </if>
                                    <if condition="$jsdhwy_new_wap_index gt 0">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">猜你喜欢商家</span>
                                                 <div class="div_right">
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('House/house_nav_guess_love',array('is_community_platform'=>1,'block_type_id' => 'business_store_info'))}">装修</button>
                                                <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="35">分配权限</button>
                                            </div>
                                            </div>
                                            <div class="group-content">按钮开关不控制系统的商品推荐，将控制自定义装修内容，关闭则装修的不展示。</div>
                                        </div>

                                    </if>
                                   <if condition="$yuanJino2oCustomized">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">秒杀专区</span>
                                               <div class="div_right">
                                                <button type="button" class="btn fitment" style="height: 28px;" data-url="{pigcms{:U('House/shop_goods_seckill_set')}" data-block_type="shop_goods_seckill_block" >装修</button>
                                                <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="15" >分配权限</button>
                                                </div>
                                            </div>
                                            <div class="group-content">可以选择商城、外卖、社区团购秒杀商品进行展示</div>
                                        </div>

                                     </if>
                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">底部单图广告位</span>
                                            <div class="div_right">
                                                <button type="button" class="btn fitment" style="height: 28px;" data-url="{pigcms{:U('Visualize/adver_list',array('cat_key'=>'house_footer_adver'))}">装修</button>
                                                <button type="button" class="btn  visualize_assign" style="height: 28px;" data-id="6">分配权限</button>
                                            </div>
                                        </div>
                                        <div class="group-content">可以设置单张广告,不设置不显示。</div>
                                    </div>
                                    <if condition="$jsdhwy_new_wap_index gt 0">
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">底部左侧二宫格</span>
                                                <div class="div_right">
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('House/house_nav_2list',array('is_community_platform'=>1,'block_type_id' => 'left_bottom_rowgrid'))}">装修</button>
                                                 <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="36">分配权限</button>
                                            </div>
                                            </div>
                                            <div class="group-content">可以设置单张广告，不设置不展示。</div>
                                        </div>
                                        <div class="form-group">
                                            <div class="group-title">
                                                <span class="name">底部右侧二宫格</span>
                                                <div class="div_right">
                                                <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('House/house_nav_2list',array('is_community_platform'=>1,'block_type_id' => 'right_bottom_rowgrid'))}" >装修</button>
                                                <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="37">分配权限</button>
                                            </div>
                                            </div>
                                            <div class="group-content">可以设置单张广告，不设置不展示。</div>
                                        </div>

                                    </if>
                                    <if condition="$yingshan"><div class="form-group">
                                            <div class="form-group">
                                                <div class="group-title">
                                                    <span class="name">猜你喜欢</span>
                                                    <div class="div_right">
                                                        <button type="button" class="btn fitment" style="height: 28px;" data-url="/shequ.php?g=House&c=Index&a=index_guess_like&source=sys">装修</button>
                                                        <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="10">分配权限</button>
                                                    </div>
                                                </div>
                                            </div>
                                    </if>

                                  <if condition="$guess_you_like_goods gt 0"><div class="form-group">
                                    <div class="form-group">
                                        <div class="group-title">
                                            <span class="name">猜你喜欢商品</span>
                                             <div class="div_right">
                                            <button type="button" class="btn fitment" style="width: 55px;height: 28px;" data-url="{pigcms{:U('House/guess_you_like_goods',array('village_id'=>$village_id,'block_type_id' => 'guess_you_like_goods'))}" data-edit="true">装修</button>
                                            <button type="button" class="btn visualize_assign" style="height: 28px;" data-id="40">分配权限</button>
                                        </div>
                                        </div>
                                        <div class="group-content">猜你喜欢商品开启后，用户端首页展示外卖、团购、预约服务三种业务的商品</div>
                                    </div>
                                </if>

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
    .page-style-wrapper .btn-box button {background-color: #438eb9;border-radius: 3px;border: none;padding: 6px 12px;color: white; cursor: pointer; outline: none;display: inline-block;margin-right: 10px;}
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
        background-color: #438eb9;
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
    .group-title .btn.fitment,.visualize_assign {
        background-color: #438eb9!important;
        border-radius: 3px;
        border: none;
        padding:0 12px;
        color:white;
        cursor:pointer;
        outline:0;
        -webkit-user-select: none;
        -webkit-touch-callout: none;
        margin-left:10px;
    }
    .div_right{
        float: right;
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
            // console.log(_this.data('url'));
            if(_this.data('url') == '/shequ.php?g=House&c=Library&a=index_nav&type=nav'){
                window.open(_this.data('url'));
            }else{
              var block_type= _this.data('block_type');
               if(block_type=='village_express_delivery'){
                  window.top.artiframe(_this.data('url')+'&frame_show=true&from_page=viewpage',_this.parent().siblings()[0].innerText,1300,600,true,false,false,false,'viewpage',true);
               }else{
                var popwidth=1200;
                var popheight=600;
                if(block_type=='shop_goods_seckill_block'){
                    popwidth=1400;
                    popheight=650;
                }
                window.top.artiframe(_this.data('url')+'&frame_show=true&from_page=viewpage',_this.parent().siblings()[0].innerText,popwidth,popheight,true,false,false,false,'viewpage',true);
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
            title:'选择小区',
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
                layer.msg('是否将该小区用户端样式复制到本小区？', {
                    time: 0 //不自动关闭
                    ,btn: ['确定', '取消']
                    ,icon: 3
                    ,offset:['550px','750px']
                    ,skin:'demo'
                    ,yes: function(index){
                        if (BDDM==''){
                            layer.alert('请选择一个小区', {icon: 2,offset:['350px','750px']});
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




    $('.visualize_assign').click(function () {
        var that=$(this),type=that.attr('data-id');
            layer.open({
                type: 2,
                title: '分配权限',
                shadeClose: false,
                maxmin: true, //开启最大化最小化按钮
                area: ['80%', '80%'],
                content: "{pigcms{:U('Visualize/visualize_assign')}&source_type="+type
            });

    })




</script>

<include file="Publics:footer"/>