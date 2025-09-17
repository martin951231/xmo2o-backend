<include file="Public:header"/>
<div class="mainbox">
    <include file="header"/>
    <div class="table-list">
        <div class="fitment_div">
            <div class="form-group">
                <div class="group-title">
                    <span class="name">轮播图</span>
                    <button type="button" class="btn fitment"
                            data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'wap_shop_index_top'))}">装修
                    </button>
                </div>
                <div class="group-content">尺寸为 640*240</div>
            </div>
          <div class="form-group">
            <div class="group-title">
              <span class="name">首页背景是否展示</span>
              <span class="switch-<if condition="$config['shop_index_page_background_show'] eq '1'">on<else/>off</if>" id="background_show"></span>
            </div>
            <div class="group-content">外卖首页banner背景图是否展示。</div>
          </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">中间橱窗</span>
                    <button type="button" class="btn fitment"
                            data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'wap_shop_index_showcase_adver'))}">装修
                    </button>
                </div>
                <div class="group-content">750*230，仅显示一张广告图。</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">导航栏导航列表</span>
                    <button type="button" class="btn fitment"
                            data-url="{pigcms{:U('Slider/slider_list',array('cat_key'=>'wap_shop_slider'))}" >装修
                    </button>
                </div>
                <div class="group-content">每行展示5个，不设置不展示。</div>
            </div>
            <div class="form-group">
                <div class="group-title">
                    <span class="name">限时秒杀</span>
                    <button type="button" class="btn fitment" data-edit="1"
                            data-url="{pigcms{$config['site_url']}/v20/public/platform/#/dialog/platform.viewpage/ShopSeckill" >装修
                    </button>
                </div>
                <div class="group-content">平台可推荐正在参与限时优惠的商品到首页，增加秒杀商品的曝光率</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">多图广告列表</span>
                    <button type="button" class="btn fitment"
                            data-url="{pigcms{:U('Shop/main_page')}" data-target="_blank" data-page_id="plat_multi_adver">装修
                    </button>
                </div>
                <div class="group-content">支持3/4/5图3种模式切换，不设置不展示。</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">为您优选</span>
                    <button type="button" class="btn fitment"
                            data-url="{pigcms{:U('Shop/shop_list')}" data-target="_blank" data-page_id="plat_recommand">装修
                    </button>
                </div>
                <div class="group-content">推荐热销店铺到首页，增加曝光率。</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">中间轮播宽图</span>
                    <button type="button" class="btn fitment"
                            data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'wap_shop_index_center_slider_adver'))}">装修
                    </button>
                </div>
                <div class="group-content">730*190，最多显示3张</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">爆款推荐</span>
                    <button type="button" class="btn fitment"
                            data-url="{pigcms{:U('Shop/shop_list')}" data-target="_blank" data-page_id="plat_hot">装修
                    </button>
                </div>
                <div class="group-content">推荐店铺下的热门商品到首页；最多展示6个店铺，一个店铺最多展示3个商品</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">附近商家</span>
                    <button type="button" class="btn fitment"
                            data-url="{pigcms{:U('Shop/shop_list')}" data-target="_blank" data-page_id="plat_nearby">装修
                    </button>
                </div>
                <div class="group-content">推荐热门店铺到猜您喜欢店铺列表，提高热门店铺曝光率</div>
            </div>
            <div class="form-group">
                <div class="group-title">
                    <span class="name">活动列表</span>
                    <button type="button" class="btn fitment"
                            data-url="{pigcms{:U('Viewpage/shop_activity')}"  data-page_id="plat_nearby">装修
                    </button>
                </div>
                <div class="group-content">创建热门活动到外卖首页，提高热门店铺曝光率</div>
            </div>
            <div class="form-group">
                <div class="group-title">
                    <span class="name">发现好菜</span>
                    <button type="button" class="btn fitment"
                            data-url="{pigcms{:U('Viewpage/shop_guess_like')}" data-edit="true" data-page_id="plat_nearby">装修
                    </button>
                </div>
                <div class="group-content">推荐热门外卖商品到外卖首页，提高热门商品曝光率</div>
            </div>
            <div class="form-group">
                <div class="group-title">
                    <span class="name">小程序流量广告</span>
                    <button type="button" class="btn fitment" data-edit="1" data-url="{pigcms{$config['site_url']}/v20/public/platform/#/dialog/platform.viewpage/ShareSet/gid=102" >装修
                    </button>
                </div>
            </div>
        </div>


        <div class="page-style-wrapper">
            <div class="page-style">
                <iframe src="{pigcms{$config.site_url}{pigcms{:D('House_village')->base_url}pages/shop_index/index?viewpage=true" id="viewpageIframe"
                        style="width:100%;height:100%;border:0;"></iframe>
            </div>

            <div class="btn-box">
                <button data-url="{pigcms{:U('Viewpage/shop_index')}">刷新</button>
                (如果“装修”后底部导航样式未改变，请点击此刷新按钮)
            </div>
        </div>
    </div>
</div>
<style>
    /**flex**/
    .rel {
        position: relative;
    }

    .flex {
        display: flex !important;
        display: -webkit-box;
        display: -moz-box;
        display: -ms-flexbox;
        display: -webkit-flex;
        display: box;
        align-items: center;
    }

    .flex_justify {
        -webkit-box-pack: justify;
        -webkit-justify-content: space-between;
        -ms-flex-pack: justify;
        justify-content: space-between;
        box-pack: center;
    }

    .flex-align {
        -webkit-box-align: center;
        -moz-align-items: center;
        -webkit-align-items: center;
        align-items: center;
        box-align: center;
    }

    .flex_one {
        flex: 1;
        -webkit-box-flex: 1;
        -webkit-flex: 1;
    }

    .flex-wrap {
        -webkit-flex-wrap: wrap;
        -moz-flex-wrap: wrap;
        -ms-flex-wrap: wrap;
        -o-flex-wrap: wrap;
        flex-wrap: wrap;
    }

    @media screen and (max-width: 1050px) {
        .page-style-wrapper {
            position: static;
            margin: 20px;
        }
    }

    .page-style-wrapper {
        position: absolute;
        left: 650px;
        top: 15px;
    }

    .page-style-wrapper .btn-box {
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

    .page-style-wrapper .btn-box button:active {
        background: #05679f;
    }

    .page-style {
        width: 375px;
        height: 627px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 0 10px 2px #ddd;
    }


    .table-list {
        position: relative;
    }

    .fitment_div {
        width: 520px;
        margin-left: 20px;
        padding-bottom: 150px;
    }

    .form-group {
        margin-bottom: 10px;
        border-bottom: 1px solid #EEE;
        padding: 10px 0;
    }

    .form-group:last-child {
        border-bottom: none;
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
        background-color: #438eb9 !important;
        border-radius: 3px;
        border: none;
        position: absolute;
        right: 0px;
        top: 0px;
        padding: 0 12px;
        color: white;
        cursor: pointer;
        outline: 0;
        -webkit-user-select: none;
        -webkit-touch-callout: none;
    }

    .group-title .switch-off, .group-title .switch-on {
        position: absolute !important;
        right: 0px;
        top: 0px;
    }

    .group-title .right_change {
        position: absolute !important;
        right: 0px;
        top: 0px;
    }
</style>
<!-- swiper -->
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>
<!-- honeySwitch -->
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}js/honeySwitch/honeySwitch.css"/>
<script type="text/javascript" src="{pigcms{$static_public}js/honeySwitch/honeySwitch.js" charset="utf-8"></script>
<script type="text/javascript" charset="utf-8">
    $(function () {

        var swiper1 = new Swiper('.swiper-container.default-header', {
            pagination: '.default-header .swiper-pagination',
            paginationClickable: true,
            observer: true,
            observeParents: true
        });
        var swiper2 = new Swiper('.swiper-container.slider-small', {
            pagination: '.slider-small .swiper-pagination',
            paginationClickable: true,
            observer: true,
            observeParents: true
        });

        $('.page-style-wrapper .btn-box button').on('click', function () {
            window.location.href = $(this).data('url');
        })


        $('.fitment').click(function(){
            var _this = $(this);
            if($(this).data('target')){
                parent.addIframe(_this.siblings('.name').text(),_this.data('page_id'),_this.data('url'));
            }else if($(this).data('edit')){
                window.top.artiframe(_this.data('url'),_this.siblings('.name').text(),1000,480,true,false,false,editbtn,'store_add',true);
            }else{
                window.top.artiframe(_this.data('url')+'&frame_show=true&from_page=viewpage',_this.siblings('.name').text(),1000,600,true,false,false,false,'viewpage',true);
            }
        });

      switchEvent("#background_show",function(){
        console.log('开了');
        change_show_classify(1);
      },function(){
        console.log('关了');
        change_show_classify(0);
      });
      function change_show_classify(value){
        $.post("{pigcms{:U('change_background_show')}",{value:value},function(result){
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