<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Write an awesome description for your new site here. You can edit this line in _config.yml. It will appear in your document head meta (for Google search results) and in your feed.xml site description.">
    <title>活动列表</title>
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.min.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/iscroll-zoom/css/iscroll.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/css/swiper.min.css">
    <!--[if lte IE 8]>
    <script type="text/javascript" src="assets/js/components/respond/1.4.2/respond.min.js"></script>
    <script type="text/javascript" src="assets/js/components/html5shiv/3.7.2/html5shiv.min.js"></script>
    <![endif]-->
    <style>
        #scroller li {
            padding:0px 5px
        }
        #weui-loadmore{
            margin:auto;
            height:45px;
            line-height:45px
        }
        .flex{
            display: -webkit-box;  /* 老版本语法: Safari, iOS, Android browser, older WebKit browsers. */
            display: -moz-box;     /* 老版本语法: Firefox (buggy) */
            display: -ms-flexbox;  /* 混合版本语法: IE 10 */
            display: -webkit-flex; /* 新版本语法: Chrome 21+ */
            display: flex;
            align-items: center;
        }
        .flex1{
            flex:1;
            text-align:center
        }
        .weui-media-box{cursor:pointer}
        .tjt-cell:after{content:none !important;}
        .tjt_cell_before:before{content:none !important;}
        .city_search:after{
            transform: rotate(135deg) !important;
            right:-2px !important;
            border-color: #000 !important;
            border-width: 1px 1px 0 0 !important;
        }
        .tjt_cell_after:after{
            content: " ";
            position: absolute;
            right: 0;
            top: 0;
            left:auto;
            width: 1px;
            bottom: 0;
            height:auto;
            border-right: 1px solid #ccc;
            color: #ccc;
            -webkit-transform-origin: 100% 0;
            transform-origin: 100% 0;
            -webkit-transform: scaleX(.5);
            transform: scaleX(.5);
        }
        .a,.a:hover,.a:active,.a:visited,.a:link,.a:focus{
            -webkit-tap-highlight-color:rgba(0,0,0,0);
            -webkit-tap-highlight-color: transparent;
            outline:none;
            background: none;
            text-decoration: none;
            color:#000
        }
        /* .weui-navbar .weui-cell_access:active{
             background-color:#16B990 !important;
             opacity: 0.3 !important;
         }*/
        #scroller li{position:relative;cursor:pointer}
        #scroller li.active{
            color:#16B990
        }
        #scroller li.active:after{
            content:' ';
            position:absolute;
            left:5px;
            right:5px;
            bottom:0px;
            height:2px;
            border-bottom:2px solid #16B990
        }
        .app-hide{display:none}
        .tjt_b_t{position:relative}
        .tjt_b_t:before{
            content: " ";
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            height: 1px;
            border-top: 1px solid #E5E5E5;
            color: #E5E5E5;
            -webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            -webkit-transform: scaleY(0.5);
            transform: scaleY(0.5);
            left: 15px;
        }
        .weui-media-box_appmsg .weui-media-box__thumb {
            height:100%;
        }
        .span{
            background: #16B990;
            color: #fff;
            z-index: 10;
            padding: 0px 5px;
            border-radius: 3px;
            position: absolute;
            top: 0;
            left: 0;
            line-height: normal;
        }
        .swiper-container,.swiper-wrapper,.swiper-slide{
            width: 100%;
            height: 100%;
        }
        .swiper-container {
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            -webkit-backface-visibility: hidden;
            -moz-backface-visibility: hidden;
            -ms-backface-visibility: hidden;
            -o-backface-visibility: hidden;
            backface-visibility: hidden;
            z-index: 1;
        }
        .swiper-slide{
            float: left;
        }
        .swiper-pagination {
            position: absolute;
            z-index: 20;
            left: 0px;
            width: 100%;
            text-align: center;
            bottom:4px;
        }
        .swiper-pagination-switch {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 8px;
            background: black;
            margin-right:5px;
            opacity: 0.14;
            cursor: pointer;
        }
        .swiper-active-switch {
            background: #06c1ae;
            opacity: 1;
        }

        .banner{
            height: 150px;;
        }
        .banner img{
            height:100%;
            width:100%;
        }
        .w100{width:100%;}
        .h100{height:100%;}
        .near{
            position:relative;
            background: rgb(248, 248, 248);
            padding:0px 5px;
            line-height:35px;
            z-index: 1;
            cursor:pointer
        }
        .near.active:after {
            content: ' ';
            position: absolute;
            left: 5px;
            right: 5px;
            bottom: 0px;
            height: 2px;
            border-bottom: 2px solid #16B990;
        }
        .add_more {
            position: relative;
            width: 35px;
            height: 35px;
            background: rgb(248, 248, 248);
            cursor: pointer;
            z-index:1;
        }
        .add_more:before {
            transform: rotate(-135deg);
            position: absolute;
            width: 9px;
            height: 9px;
            top: 7px;
            right: 14px;
            content: '';
            border: 1px solid #353434;
            border-right: 1px solid transparent;
            border-bottom: 1px solid transparent;
        }
        .add_more_all:before {
            transform: rotate(45deg) !important;
            top: 15px !important;
        }
        .border-l:after {
            border-left: 1px solid #ddd;
            content: '';
            display: block;
            top: 7px;
            bottom: 7px;
            position: absolute;
            left: 0;
            -webkit-transform-origin: left top;
            -webkit-transform: scaleX(0.5);
        }
        #ride_all {
            z-index: 39;
            width: 100%;
            position: absolute;
        }
        .bg_f {
            background: #fff !important;
        }
        #ride_all>div {
            background: rgba(0, 0, 0, 0.6);
            top: 80px;
            position: fixed;
            left: 0px;
            right: 0px;
            bottom: 0px;
            cursor: pointer !important;
            z-index: -7
        }
        .hotKey {
            overflow: hidden;
            margin: 0;
            list-style-type: none;
            padding: 0px 0px 2% 2%;
           padding-top: 3.6%;
        }
        .hotKey li {
            width: 32%;
            float: left;
            margin-right: 1%;
            background-color: white;
            margin-bottom: 1.6%;
        }
        .hotKey li a {
            border: 0.5px solid #e4e1e1;
            padding: 8px 3px;
            display: block;
            text-align: center;
            font-size: 12px;
            color: #7E7E7E;
            border-radius: 3px;
        }
        .bg_active {
            background-color: rgba(245,245,245,0.9);
        }
    </style>
</head>
<body ontouchstart="">
<div class="" id="content"  style="display:none">
    <div class="weui-navbar " style="background:#fff;color:#000 !important;display:block;position:fixed">
        <div style="width:100%;height:45px;line-height:45px" class="flex  ">
            <if condition="$config['many_city']">
                <div class="weui-cells tjt_cell_before tjt-cell  <if condition="$config['many_city']">hasManyCity</if> " style="margin-top:0px;background-color:transparent">
            <a class="weui-cell weui-cell_access" href="{pigcms{:U('Changecity/index')}&Platform=1">
                <div id='cityBtn' class="weui-cell__ft city_search" style="color:#000;font-size: 15px;" data-top_domain="{pigcms{$config.many_city_top_domain}">{pigcms{:msubstr($config['now_select_city']['area_name'],0,2)}</div>
            </a>
        </div>
        <else />

        <div  id='cityBtn' class="weui-cell__ft city_search no_city weui-cell" style="color:#000;font-size: 15px;" data-top_domain="{pigcms{$config.many_city_top_domain}">{pigcms{:msubstr($config['now_select_city']['sub_city']['area_name'],0,2)}</div>

    </if>
    <div class="flex1 weui-cells  has_city" style="padding:0px;margin-top:0px;">
        <a class="weui-cell weui-cell_access" href="{pigcms{:U('Platform/searchIndex')}&city_id={pigcms{$config['now_city']}" style="    opacity: 0.6;border-radius: 4px; font-size: 14px;  padding: 0px;  border: 1px solid #ddd;  background: #ddd;">
            <i class="weui-icon-search" style="color:#999"></i>
            <span style="color:#999">搜索感兴趣的活动</span>
        </a>
    </div>
    <div class="weui-cells tjt_cell_before tjt-cell" style="margin-top:0px;font-size: 15px;">
        <a class="weui-cell weui-cell_access" href="{pigcms{:U('Platform/activity')}">
            <div class="weui-cell__ft tjt-cell team_in" style="  cursor: pointer;     padding-right: 0px;  color: #16B990;">我的活动</div>
        </a>
    </div>
</div>
<div class="flex" style=position:relative>
    <div class="near active ">
        <ul class='slide_none'>
            <li class=" por " data-id="recent">
                <a class="weui-cell_access" href="javascript:;" >附近活动</a>
            </li>
        </ul>

    </div>
    <div class="news-scroller flex1" id="wrapper" style="line-height:35px;background:#F8F8F8">
        <div id="scroller"  class="scroller">
            <ul class="clearfix  slide_none">

            </ul>
            <div style="clear:both"></div>
        </div>
    </div>
    <div class="add_more border-l"></div>
    <div style="     z-index: 60; background: rgb(248, 248, 248);padding-left:10px;  position: absolute; top: 0px;line-height: 35px; display: none; right: 40px; left: 0px;" class="look_all">全部活动</div>
    <div style="top:35px; display: none;" id="ride_all" class="bg_f w100">
        <div class="cursor  add_one"></div>
        <ul class="hotKey bg_f" data-down="1" style="display: block;">
            <li>
                <a href="javascript:void(0)" class="bg_active" data-id="" data-index="0">附近活动</a>
            </li>

        </ul>
    </div>
</div>

<if condition="$wap_activity_list">
    <div class="swiper-container swiper-container1 w100 " id='listBanner'>
        <div class="swiper-wrapper h100">
            <volist name="wap_activity_list" id="vo">
                <div class="swiper-slide w100 h100">
                    <a href="{pigcms{$vo.url}">
                        <img src="{pigcms{$vo.pic}" class="w100 h100"/>
                    </a>
                </div>
            </volist>
        </div>
        <div class="swiper-pagination"></div>
    </div>
</if>
</div>

<div class="weui-tab__bd"  id='init' style="padding-top:30px">
    <div id="tab1" class="weui-tab__bd-item weui-pull-to-refresh weui-tab__bd-item--active" style="margin-top:0px;top:0px;position:relative">
        <div class="weui-pull-to-refresh__layer">
            <div class="weui-pull-to-refresh__arrow"></div>
            <div class="weui-pull-to-refresh__preloader"></div>
            <div class="down">下拉刷新</div>
            <div class="up">释放刷新</div>
            <div class="refresh">正在刷新</div>
        </div>
        <!--广告位-->
        <div id="container" ></div>
        <div class="content-padded" style="padding-bottom:65px">
            <div class="weui-panel__bd" id="showIndex">

            </div>
            <div class="weui-loadmore" id='weui-loadmore'>
                <i class="weui-loading"></i>
                <span class="weui-loadmore__tips">正在加载</span>
            </div>
        </div>
    </div>
</div>
</div>
<div class="weui-tabbar" style="z-index:9;position:fixed;padding: 10px 0px;    background-color: #fff;">
<div style="width:90%;margin:auto">
    <a href="{pigcms{:U('Platform/index')}"><button class="weui-btn weui-btn_primary" id="submit" type="button" >发布活动</button></a>
</div>
</div>
<script type="text/html" id="cate_id">

<& for(var i=0;i< _this.length;i++) {&>
<&var _item=_this[i]&>
<li class=" por " data-id="<&=_item.id&>" data-index="<&=i+1&>">
    <a class="weui-cell_access" href="javascript:;" ><&=_item.cate_name&></a>
</li>
<&}&>
</script>
<script type="text/html" id="cate_id_on">
<& for(var i=0;i< _this.length;i++) {&>
<&var _item=_this[i]&>
<li>
    <a href="javascript:void(0)" class="" data-id="" data-index="<&=i+1&>"><&=_item.cate_name&></a>
</li>
<&}&>
</script>


<script id="index" type="text/html">
<& for(var i=0;i< _this.length;i++) {&>
<& var _item=_this[i] &>
<a data-url="{pigcms{:U('Platform/indexDetail')}&activity_id=<&=_item.activity_id&>" href="javascript:;" class="weui-media-box weui-media-box_appmsg">
    <div class="weui-media-box__hd" style="width:135px;height:90px;position:relative">
        <& if(_item.is_top==1) {&>
        <span class="span">置顶活动</span>
        <&}&>
        <img class="weui-media-box__thumb" src="<&= _item.image&>" alt="">
    </div>
    <div class="weui-media-box__bd" >
        <h4 class="weui-media-box__title"><&= _item.title&></h4>
        <p class="" style="color:#999;font-size:13px"><&= _item.start_time&>开始</p>
        <p class="weui-media-box__desc"><&= _item.end_time&>结束</p>
        <& if (_item.address_name) {&>
        <p class="weui-media-box__desc"><&= _item.address_name&></p>
        <&} else {&>
        <p class="weui-media-box__desc"><&= _item.address&></p>
        <&}&>
        <p style="padding-top:5px;display:flex;display:-moz-flex;display:-ms-flex;;display:-o-flex">
            <& if(_item.fees && _item.fees!='' && _item.fees*1>0) {&>
            <span style="color:#16B990;flex:1; padding: 3px 7px;">{pigcms{$config['Currency_symbol']}<&= _item.fees&></span>
            <&} else {&>
            <span style="color:#16B990;flex:1;padding: 3px 7px;">免费</span>
            <&}&>
            <& if(_item.status==4) {&>
            <span style="padding: 3px 7px;background: #E1F5ED;  color: #16B990;float:right">最新发布</span>
            <&} else if(_item.status==1) {&>
            <span style="padding: 3px 7px;background: #E1F5ED;  color: #16B990;float:right">进行中</span>
            <&} else if(_item.status==2) {&>
            <span style="padding: 3px 7px;background: #E1F5ED;  color: #16B990;float:right">已取消</span>
            <&} else if(_item.status==3) {&>
            <span style="padding: 3px 7px;background: #E1F5ED;  color: #16B990;float:right">已结束</span>
            <&}&>

        </p>
    </div>
</a>
<&}&>
<& if(View.isEmpty(_this)) {&>
<div class="weui-loadmore " >
    <div><img src="static/activity/assets/image/noresult.png"/></div>
    <span class="weui-loadmore__tips" style="color: #999">暂无数据</span>
</div>
<& }&>
<& if(View.isNomore(_this)) {&>
<div class="weui-loadmore tjt_b_t" style="height:45px;line-height:45px;margin:auto;width:100%">
    <span class="weui-loadmore__tips" style="color: #999">暂无更多数据</span>
</div>
<& }&>

</script>
<script type="text/javascript">
var group_index_sort_url="{pigcms{:U('Home/group_index_sort')}";
<if condition="$user_long_lat">var user_long = "{pigcms{$user_long_lat.long}",user_lat = "{pigcms{$user_long_lat.lat}";<else/>var user_long = '0',user_lat  = '0';</if>var app_version="{pigcms{$_REQUEST['app_version']}"
var city_id="{pigcms{$config['now_city']}"
var is_sign ="{pigcms{$_SESSION['user']['uid']}"
</script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/common/js/common.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/common/js/index.js"></script>
<script type="text/javascript"  src="static/activity/assets/js/components/iscroll-zoom/js/iscroll.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/navbarscroll/navbarscroll.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/swiper/swiper.min.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/fastClick/fastClick.js"></script>
<script type="text/javascript">
var banner_height = $(window).width()/320;
banner_height =  Math.ceil(banner_height*119);
$("#banner_hei").css('height',banner_height);
$('.recommendLeft').height(parseInt(($(window).width()/2) * 135 / 280)*2);
$('.recommendRightTop,.recommendRightBottom').height(parseInt(($(window).width()/2) * 135 / 280));
if($('.swiper-container1').size() > 0){
    var myswiper = new Swiper('.swiper-container1', {
        pagination: '.swiper-container1 .swiper-pagination',
        direction : 'horizontal',
        paginationClickable :true,
        autoplay :'5000',
        autoplayDisableOnInteraction : false,
        loop: true
    });


}
</script>
<script>
var View={
    init:function(){
        this.initRefresh();
        this.clickList()
    },
    settings: {
        pageNo: 1,
        pageSize: 4,
        totalPages: 0,
        cate_id:'',
        loading:false,
        initScroll:true,
        swiper:true,
        initTwo:true,
        initnavascroll:true
    },
    isEmpty: function(list){
        var b = View.settings.pageNo == 1 && !list.length;

        if(b){
            this.resetInfinite(false);
        }

        return b;
    },
    isNomore: function(list){
        var b =this.settings.totalPages>0&&((this.settings.pageNo >= this.settings.totalPages) || (list.length < this.settings.pageSize)) ;

        if(b){
            this.resetInfinite(false);
        }
        return b;
    },
    initRefresh: function() {
        var self = this;
        var $body = $('body');

        if($(".no_city").size()==0){
            $(".no_city").css('padding','0px 10px');
        }
        var resetInfinite = self.resetInfinite = function(init,reFresh){
            $body[ init ? 'infinite' : 'destroyInfinite' ]();
            $body.find('#weui-loadmore')[ init ? 'removeClass' : 'addClass' ]('app-hide');
        };
        var requestData = self.requestData = function(){
            var datas={
                page:self.settings.pageNo,
                city_id:city_id,
                cate_id:'',
                replace:true
            }
            $.showLoading('加载中')
            $.ajax({
                url: "{pigcms{:U('Platform/activity_cate_api')}",
                async: false,//这一步是非常重要的，作用是设置为同步执行
                dataType : "json",
                data:datas,
                type : "get",
                success: function (data) {
                    console.log(data)
                    if(data.errorMsg=='success'){
                        if(data.result && data.result.length>0){
                            for(var i=0;i<data.result.length;i++){
                                if(data.result[i].id=='recent'){
                                    data.result.splice(i,1);
                                    break
                                }
                            }
                            datas.cate_id='recent';
                            self.settings.cate_id='recent';
                            var index = Simplite.getTemplate('cate_id');
                            var indexTpl = Simplite.compile(index)(data.result)
                            var index_on = Simplite.getTemplate('cate_id_on');
                            var indexTpl_on = Simplite.compile(index_on)(data.result)
                            $("#scroller .clearfix").html(indexTpl);
                            $(".hotKey").append(indexTpl_on)
                        }

                        self.list(datas)
                        if(self.settings.swiper){
                            self.swiper()
                        }
                    }else{
                        if(self.settings.swiper){
                            self.swiper()
                        }
                        $.hideLoading();
                        $.alert(data.errorMsg)
                    }



                },
                error:function(data){
                    if(self.settings.swiper){
                        self.swiper()
                    }
                    $.hideLoading()
                }
            });
        }
        $body.on("infinite", function() {
            self.settings.pageNo++;
            var datas={
                page:self.settings.pageNo,
                city_id:city_id,
                cate_id:self.settings.cate_id,
                replace:false
            }

            self.list(datas);
        });
        resetInfinite(true);
        requestData(true);

    },
    swiper:function(){
        var self=this;


        var wh=$(window).width();
        var banner_height=wh/320;
        banner_height=Math.ceil(banner_height*119);
        $("#listBanner").css('height',banner_height);
        if($(".swiper-container1").length>0){
            $("#init").css('padding-top',(banner_height+30)+'px')
            var myswiper = new Swiper('.swiper-container1', {
                pagination: '.swiper-container1 .swiper-pagination',
                direction : 'horizontal',
                paginationClickable :true,
                autoplay :'5000',
                autoplayDisableOnInteraction : false,
                loop: true
            });
        }

        self.settings.swiper=false

    },
    list:function(datas){
        var self=this;
        if (View.settings.loading) return;
        View.settings.loading = true;
        $.ajax({
            url: "{pigcms{:U('Platform/activity_list')}",
            async: false,//这一步是非常重要的，作用是设置为同步执行
            dataType : "json",
            data:datas,
            type : "post",
            success: function (data) {
                if(data.errorMsg=='success'){
                    View.settings.totalPages=data.result.total;
                    var index = Simplite.getTemplate('index');
                    if(!data.result.list || data.result.list==null ){
                        data.result.list=[]
                    }
                    var indexTpl = Simplite.compile(index)(data.result.list);
                    var h1=$(".weui-navbar").height();
                    var h2=$(".news-scroller").height();
                    var wh=$(window).height();
                    var $scroller = $('#scroller');
                    if(datas.replace){
                        $("#showIndex").html(indexTpl);

                    }else{
                        $("#showIndex").append(indexTpl);
                    }
                    console.log($(".no_city").length)
                    if($("#cityBtn").html()==''){
                        $(".has_city").css('padding-left','20px');
                    }
                    if(self.settings.initScroll){
                        $("#content").show();
                        self.settings.initScroll=false
                        var innerWidth = 0;
                        $('#wrapper').navbarscroll({
                            defaultSelect:0,
                            scrollerWidth:6,
                            fingerClick:1,
                            endClickScroll:function(obj){
                                if(!self.settings.initnavascroll){
                                    self.resetInfinite(true);
                                    $(".hotKey a").removeClass("bg_active")
                                    $(".hotKey li").eq(parseInt(obj.attr("data-index"))).find('a').addClass("bg_active")
                                    self.settings.pageNo=1;
                                    self.settings. pageSize=10;
                                    self.settings.totalPages=0;
                                    self.settings.cate_id=obj.attr("data-id")
                                    obj.addClass("active").siblings().removeClass("active");
                                    $(".near").removeClass("active");
                                    var datas={
                                        page:1,
                                        city_id:city_id,
                                        cate_id:obj.attr("data-id"),
                                        replace:true
                                    }
                                    self.list(datas)
                                }
                                self.settings.initnavascroll=false

                            }
                        });
                        /*  $scroller.find('li').each(function() {
                         innerWidth += $(this).outerWidth()+10;
                         });
                         $scroller.width(innerWidth+10);
                         new IScroll('#wrapper', { eventPassthrough: true, scrollX: true, scrollY: false, preventDefault: false });*/
                    }
                    var h3=$("#showIndex").height();
                    var h2=$(".weui-navbar ").height();
                    var h4=$(".weui-tabbar").height()+20
                    if(wh-h2-h4-h3>=0){
                        if(data.result.list.length && data.result.list.length>=4 ){
                            var t={
                                page:self.settings.pageNo,
                                city_id:city_id,
                                cate_id:self.settings.cate_id,
                                replace:false
                            }
                            View.settings.loading = false;
                            self.list(t);
                        }else{
                            $.hideLoading();
                            View.settings.initTwo=false;
                            View.settings.loading = false;
                            $('#weui-loadmore').addClass('app-hide')
                        }
                    }else{
                        if(self.isNomore(data.result.list)){
                            $('#weui-loadmore').addClass('app-hide')
                        }
                        $.hideLoading();
                        View.settings.initTwo=false;
                        View.settings.loading = false;
                    }
                    if(!self.settings.initTwo){
                        // $("html,body").animate({"scrollTop":0},100);
                    }

                }else{
                    $.hideLoading();
                    $.alert(data.errorMsg)
                }


            },
            error:function(data){
                $.hideLoading()
                console.log(data)
            }
        });
    },
    clickList:function(){
        $(function() {
            FastClick.attach(document.body);
        });
        var self=this;

        $('.add_one').bind("touchmove",function(e){
            e.preventDefault();
        });
        $(document).on("click",'.team_in',function(e){
            var href=$(this).closest('a').attr("href")
            e.stopPropagation();
            e.preventDefault();
            if(is_sign==''){
                $.alert({
                    title: '提示',
                    text: '请先登录',
                    onOK: function () {
                        window.location.href='/wap.php?g=Wap&c=Login&a=index'
                    }
                })
                return false
            }else{
                window.location.href=href
            }

        })
        $(document).on("click",'.near',function(){
            $.showLoading('加载中');
            self.resetInfinite(true);
            self.settings.pageNo=1;
            self.settings. pageSize=10;
            self.settings.totalPages=0;
            self.settings.cate_id=$(this).find("li").attr("data-id")
            $(this).addClass("active")
            $(".hotKey a").removeClass("bg_active")
            $(".hotKey li").eq(0).find("a").addClass("bg_active")
            $(".clearfix li").removeClass("active");
            var datas={
                page:1,
                city_id:city_id,
                cate_id:$(this).find("li").attr("data-id"),
                replace:true
            }
            self.list(datas)
        })
        $(document).on("click",'.weui-media-box',function(){
            var url=$(this).attr('data-url')
            location.href=url

        })
        $(document).on("click",'.add_more',function(){
            if($(this).hasClass("add_more_all")){
                $(this).removeClass("add_more_all");
                $(".look_all").hide()
                $(".slide_none").show()
                $("#ride_all").slideUp(100)
            }else{
                $(this).addClass("add_more_all");
                $(".slide_none").hide();
                $(".look_all").show()
                $("#ride_all").slideDown(100)
            }
        })
        $(document).on("click",'.add_one',function(){
            $(".add_more").trigger("click");
        })

        $(document).on("click",'.hotKey li a',function(){
            var index=parseInt($(this).attr("data-index"));
            $(".add_more").trigger("click");
            setTimeout(function(){
                if(index==0){
                    $(".near").trigger("click")
                }else{
                    $(".clearfix li").eq(index-1).trigger("click")
                }
            },100)


        })

        $('.weui-tab__bd-item').pullToRefresh({
            onRefresh: function () {
                self.resetInfinite(true)
                $("#showIndex").html('')
                //$.showLoading('加载中')
                self.settings.pageNo=1;
                self.settings. pageSize=10;
                self.settings.totalPages=0;
                var datas={
                    page:self.settings.pageNo,
                    city_id:city_id,
                    cate_id:self.settings.cate_id,
                    replace:true,
                    refresh:true
                }
                self.list(datas)
                setTimeout(function() {
                    $('.weui-tab__bd-item').pullToRefreshDone();
                }, 2000)
            },
            onPull: function (percent) {
                console.log(percent)
            },
            distance: 50
        });
    }


}
window.View=View;
View.init()
</script>
        </body>
        </html>