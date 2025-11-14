<include file="Public:header"/>
<div class="mainbox">
    <include file="header"/>
    <div class="table-list" style="height: 760px;">
        <div class="fitment_div">
            <div class="form-group">
                <div class="group-title">
                    <span class="name">轮播图</span>
                    <button type="button" class="btn fitment" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'wap_foodshop_index_top'))}">装修</button>
                </div>
                <div class="group-content">尺寸为 640*240</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">导航栏导航列表</span>
                    <button type="button" class="btn fitment" data-url="{pigcms{:U('Slider/slider_list',array('cat_key'=>'wap_foodshop_slider'))}" data-page_id="plat_multi_adver">装修</button>
                </div>
                <div class="group-content">每行展示5个，不设置不展示。</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                        <span class="name">4张图片广告列表</span>
                        <button type="button" class="btn fitment" data-refresh="1" data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'wap_foodshop_middle_four'))}">装修</button>

                </div>
                <div class="group-content"> 当前为4张平铺式布局，不设置不展示。</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">店铺列表</span>
                    <button type="button" class="btn fitment" data-url="/foodshop/platform.store/list" data-list="foodshop_list" data-page_id="plat_nearby">装修</button>
                </div>
                <div class="group-content">餐饮的店铺列表页</div>
            </div>
        </div>


        <div class="page-style-wrapper">
            <div class="page-style">
                <iframe src="<php>echo $this->config['site_url'] . D('House_village')->base_url.'pages/foodshop/index/index';</php>" id="viewpageIframe"
                        style="width:100%;height:100%;border:0;"></iframe>
            </div>

            <div class="btn-box">
                <button data-url="{pigcms{:U('Viewpage/foodshop_index')}">刷新</button>
                (如果“装修”后样式未改变，请点击此刷新按钮)
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
        height: 667px;
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
                window.o2oTop.artiframe(_this.data('url'),_this.siblings('.name').text(),680,480,true,false,false,editbtn,'store_add',true);
            }else if($(this).data('list')){ //  url： /foodshop/platform.store/list
                parent.addIframe(_this.siblings('.name').text(),_this.data('page_id'),_this.data('url'));
            }else{
                window.o2oTop.artiframe(_this.data('url')+'&frame_show=true&from_page=viewpage',_this.siblings('.name').text(),1000,600,true,false,false,false,'viewpage',true);
            }
        });
    });

</script>
<include file="Public:footer"/>