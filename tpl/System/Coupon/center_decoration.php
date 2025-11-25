<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Coupon/index')}">SYSTEM_COUPON_CENTER_1</a>
            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Coupon/add')}','SYSTEM_COUPON_CENTER_2','80%','80%',true,false,false,addbtn,'edit',true);">SYSTEM_COUPON_CENTER_2</a>
            <a href="{pigcms{:U('Coupon/had_pull')}" >领取列表</a>
            <a href="{pigcms{:U('Coupon/send_coupon')}" >派发优惠券</a>
            <a href="{pigcms{:U('Coupon/center_decoration')}"  class="on">领券中心装修</a>
            <a target="_blank" href="{pigcms{$config.site_url}/v20/public/platform/#/dialog/platform.coupon/SysGetRecords" >发放记录</a>
            <a target="_blank" href="{pigcms{$config.site_url}/v20/public/platform/#/dialog/platform.coupon/SysUseRecords" >核销记录</a>
        </ul>
    </div>

    <div class="table-list">
        <div class="fitment_div">

            <div class="form-group">
                <div class="group-title">
                    <span class="name">领券中心主题色</span>
                    <div class="right_div">
                        <input type="text" class="input-text" id="config_txtcolor" value="{pigcms{$config.coupon_main_color}" readonly="readonly" style="text-indent:1000px;width:24px;cursor:default;background-color:{pigcms{$config.coupon_main_color}"/>
                        <a href="javascript:void(0);" id="choose_color_box_txtcolor" style="line-height:28px;">点击选择颜色</a>
                    </div>
                </div>
                <div class="group-content">可以设置领券中心整个页面的主题色</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">热搜词</span>
                    <button type="button" class="btn fitment"
                            data-url="{pigcms{$config.site_url}/v20/public/platform/#/dialog/platform.coupon/searchHotWords?t=1">装修
                    </button>
                </div>
                <div class="group-content">设置推荐热搜关键词到搜索页，提高优惠券搜索曝光率</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">轮播图</span>
                    <button type="button" class="btn fitment"
                            data-url="{pigcms{:U('Adver/adver_list',array('cat_key'=>'wap_coupon_index'))}">装修
                    </button>
                </div>
                <div class="group-content">尺寸为 640*338，图片是从手机状态栏顶部开始展示，导航栏会悬浮在图片上方，需要注意图片上部分的颜色不能太艳丽，阻碍用户看到导航。</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">品牌精选</span>
                    <button type="button" class="btn fitment"
                            data-url="{pigcms{$config.site_url}/v20/public/platform/#/dialog/platform.coupon/brandSelection?t=1">装修
                    </button>
                </div>
                <div class="group-content">推荐优惠力度大，热销店铺的优惠券到首屏，增加曝光率</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">SYSTEM_COUPON_CENTER_3</span>
                    <button type="button" class="btn fitment"  data-edit="1"
                            data-url="{pigcms{:U('Coupon/edit_system_coupon_alias',array('frame'=>1))}">装修
                    </button>
                </div>
                <div class="group-content">SYSTEM_COUPON_CENTER_4</div>
            </div>

            <div class="form-group">
                <div class="group-title">
                    <span class="name">分享文案配置</span>
                    <button type="button" data-edit="1" class="btn fitment"
                            data-url="{pigcms{:U('Coupon/set_center_decoration_share',array('frame'=>1))}">装修
                    </button>
                </div>
                <div class="group-content" id="share_desc">标题：{pigcms{$config.coupon_main_title}；副标题：{pigcms{:C('config.coupon_main_sub_title')}</div>
            </div>

        </div>


        <div class="page-style-wrapper">
            <div class="page-style">
                <iframe src="{pigcms{$config.site_url}/packapp/plat/pages/coupon/index" id="viewpageIframe"
                        style="width:100%;height:100%;border:0;"></iframe>
            </div>

            <div >
                <button class="refresh" onclick="reload_iframe()">刷新</button>
                (如果“装修”后底部导航样式未改变，请点击此刷新按钮)
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function reload_iframe(){
        $('#viewpageIframe').attr('src', $('#viewpageIframe').attr('src'));
        return false;
    }
    </script>
<style>
    /**flex**/
    .rel {
        position: relative;
    }
    .refresh {
        background-color: #438eb9;
        border-radius: 3px;
        border: none;
        padding: 6px 12px;
        color: white;
        cursor: pointer;
        outline: none;
        display: inline-block;
        margin-right: 10px;
        margin-top: 10px;
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
        padding-bottom: 250px;
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

    .group-title .right_div{
        position: absolute!important;
        right: 0px;
        top: 0px;
        font-size:12px;
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

        $("#choose_color_box_txtcolor").click(function(){
            change_txtcolor();
        });

        function change_txtcolor(){
            window.showTopColorPanel("leftmenu_Viewpage_index",$("#choose_color_box_txtcolor").offset().top,$("#choose_color_box_txtcolor").height(),$("#choose_color_box_txtcolor").offset().left,"config_txtcolor",function(){
                var txtcolor = $('#config_txtcolor').val();
                $('.page-style').css('color', txtcolor);
                save_change_color('coupon_main_color',txtcolor);
            });
        }

        function save_change_color(name,color){
            $.post("{pigcms{:U('Viewpage/change_footer_color')}",{name:name,color:color},function(result){
                if(result.status != 1){
                    layer.alert('切换失败',{
                        offset: ($('body').height() - 150) / 2
                    });
                }
            });
        }


        $('.fitment').click(function(){
            var _this = $(this);
            if($(this).data('target')){
                parent.addIframe(_this.siblings('.name').text(),_this.data('page_id'),_this.data('url'));
            }else if($(this).data('edit')){
                window.o2oTop.artiframe(_this.data('url'),_this.siblings('.name').text(),1000,480,true,false,false,editbtn,'store_add',true);
            }else{
                window.o2oTop.artiframe(_this.data('url')+'&frame_show=true&from_page=viewpage',_this.siblings('.name').text(),1200,600,true,false,false,false,'viewpage',true);
            }
        });


    });


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
        $("#_clean").hide();
        $("#_clean").unbind("click").click(function(){
            $("#colorpanel").hide();
            $("#"+frameName+"").val('');
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

    function dialogConfirm() {
        $.get("{pigcms{:U('Coupon/center_decoration')}", function (result) {
            $('#share_desc').html('标题：' + result.config.coupon_main_title + '；副标题：' + result.config.coupon_main_sub_title);
        });
    }

    function dialogCancel(){
        $.get("{pigcms{:U('Coupon/center_decoration')}", function (result) {
            $('#share_desc').html('标题：' + result.config.coupon_main_title + '；副标题：' + result.config.coupon_main_sub_title);
        });
    }

</script>
<include file="Public:footer"/>