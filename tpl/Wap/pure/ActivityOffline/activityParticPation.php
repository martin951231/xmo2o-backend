<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Write an awesome description for your new site here. You can edit this line in _config.yml. It will appear in your document head meta (for Google search results) and in your feed.xml site description.">
    <title>活动</title>
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.min.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/iscroll-zoom/css/iscroll.css">
    <!--[if lte IE 8]>
    <script type="text/javascript" src="static/activity/assets/js/components/respond/1.4.2/respond.min.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/html5shiv/3.7.2/html5shiv.min.js"></script>
    <![endif]-->
    <style>
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

        .weui-navbar  .pro{position:relative;cursor:pointer;}
        .weui-navbar  .pro.active{
            color:#16B990
        }
        .weui-navbar  .pro.active:after{
            content:' ';
            position:absolute;
            left:0px;
            right:0px;
            bottom:0px;
            height:2px;
            border-bottom:2px solid #16B990
        }
        .weui-navbar .flex1{padding:10px 15px}
        .app-hide{display:none}
        .tjt_b_t{position:relative}
        .tjt_b_t_n:after{
            content: none !important;
        }
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
        .weui-media-box_appmsg .weui-media-box__thumb {

            height: 100%;


        }
         .change-animated{
            -webkit-animation-duration: 1s;
            animation-duration: 1s;
            -webkit-animation-iteration-count: infinite;
            animation-iteration-count: infinite;
            -webkit-animation-name: slideTurn;
            animation-name: slideTurn;
        }

        @-webkit-keyframes slideTurn {
            0% {
                transform: rotateY(0);
            }
            50%{
                transform: rotateY(90deg);
            }
            100% {
                transform: rotateY(0);
                top: 20%;}
        }
        .weui-btn{
            font-size: 14px;
        }
        .weui-pull-to-refresh__layer{
            padding: 0px;
        }
        #scroller li>div{text-align:center;}
    </style>
</head>
<body ontouchstart="">
<div class="weui-tab" id="content" >

    <div class="weui-navbar " style="background:#fff;color:#000 !important;display:block;position:fixed;z-index:10">
        <div style="width:100%;height:45px;line-height:45px" class="flex" id='wriper'>
            <div id="scroller"  >
                <ul class="clearfix ">
                    <li>
                        <div class= "" style="margin-top:0px;background-color:transparent;font-size: 14px;">
                            <a class="a list_title" href="javascript:;" data-status="" data-id="0" data-url="{pigcms{:U('ActivityOffline/join_list')}">
                                <div class=" pro">全部({pigcms{$activity_count['all']})</div>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="  " style="padding:0px;margin-top:0px;font-size: 14px;">
                            <a class="a list_title" href="javascript:;" data-status="1" data-id="0" data-url="{pigcms{:U('ActivityOffline/join_list')}">
                                <div class=" pro">待参加({pigcms{$activity_count['1']})</div>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class=" " style="margin-top:0px;font-size: 14px;">
                            <a class=" a list_title" href="javascript:;" data-status="5" data-id="0" data-url="{pigcms{:U('ActivityOffline/join_list')}">
                                <div class=" pro">已参加({pigcms{$activity_count['5']})</div>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class=" " style="margin-top:0px;font-size: 14px;">
                            <a class=" a list_title" href="javascript:;" data-status="2" data-id="1" data-url="{pigcms{:U('ActivityOffline/join_list')}">
                                <div class=" pro">已完成({pigcms{$activity_count['2']})</div>
                            </a>
                        </div>
                    </li>
             <!--        <li>
                        <div class=" " style="margin-top:0px;font-size: 14px;">
                            <a class=" a list_title" href="javascript:;" data-status="4" data-id="1" data-url="{pigcms{:U('ActivityOffline/join_list')}">
                                <div class=" pro">已退款({pigcms{$activity_count['4']})</div>
                            </a>
                        </div>
                    </li> -->
                    <li>
                        <div class=" " style="margin-top:0px;font-size: 14px;">
                            <a class=" a list_title" href="javascript:;" data-status="6" data-id="1" data-url="{pigcms{:U('ActivityOffline/join_list')}">
                                <div class=" pro">已取消({pigcms{$activity_count['6']})</div>
                            </a>
                        </div>
                    </li>
                </ul>
                <div style="clear:both"></div>
            </div>

        </div>
    </div>
    <div class="weui-tab__bd" style="padding-top:0px">
        <div id="tab1" class="weui-tab__bd-item weui-pull-to-refresh weui-tab__bd-item--active" style="margin-top:0px;top:0px;position:relative">
            <div class="weui-pull-to-refresh__layer">
                <div class="weui-pull-to-refresh__arrow"></div>
                <div class="weui-pull-to-refresh__preloader"></div>
                <div class="down">下拉刷新</div>
                <div class="up">释放刷新</div>
                <div class="refresh">正在刷新</div>
            </div>
            <div class="content-padded" style="margin-top:10px">
                <div class="weui-panel__bd" id="showIndex" style="margin-top:0px">
                </div>
                <div class="weui-loadmore" id='weui-loadmore'>
                    <i class="weui-loading"></i>
                    <span class="weui-loadmore__tips">正在加载</span>
                </div>
            </div>
        </div>
    </div>
</div>
<script id="index" type="text/html">

        <& for(var i=0;i< _this.lists.list.length;i++) {&>
        <& var _item=_this.lists.list[i] &>

        <& if(_item.aj_status == 1){&>
            <& if(_this.activity.final_money > 0 && _item.final_pay == 1){&>
                <div class="weui-panel__hd tjt_b_t_n" style="color:#16B990;padding:5px 15px;text-align:right">待参加，尾款已支付</div>
            <&} else if(_this.activity.final_money > 0 && _item.final_pay == 0){&>
                <div class="weui-panel__hd tjt_b_t_n" style="color:#16B990;padding:5px 15px;text-align:right">待参加，尾款待支付</div>
            <&} else {&>
                <div class="weui-panel__hd tjt_b_t_n" style="color:#16B990;padding:5px 15px;text-align:right">待参加</div>
            <&}&>
        <&} else if(_item.aj_status==0){&>
            <div class="weui-panel__hd tjt_b_t_n" style="color:#16B990;padding:5px 15px;text-align:right">报名失败</div>
        <&} else if(_item.aj_status==2){&>
            <div class="weui-panel__hd tjt_b_t_n" style="color:#16B990;padding:5px 15px;text-align:right">已完成</div>
        <&} else if(_item.aj_status==3){&>
            <div class="weui-panel__hd tjt_b_t_n" style="color:#16B990;padding:5px 15px;text-align:right">待付款</div>
        <&} else if(_item.aj_status==4){&>
            <div class="weui-panel__hd tjt_b_t_n" style="color:#B9403D;padding:5px 15px;text-align:right">已退款</div>
        <&} else if(_item.aj_status==5){&>
            <div class="weui-panel__hd tjt_b_t_n" style="color:#999;padding:5px 15px;text-align:right">已参加</div>
        <&} else if(_item.aj_status==6){&>
            <div class="weui-panel__hd tjt_b_t_n" style="color:#999;padding:5px 15px;text-align:right">已取消</div>
        <&}&>
        <a   style=" padding-bottom:0px"   class="weui-media-box weui-media-box_appmsg">
            <div class="weui-media-box__hd" style="width:90px;height:90px;position:relative">
                <img class="weui-media-box__thumb" style="border-radius: 45px;" src="<&= _item.avatar&>" alt="">
            </div>
            <div class="weui-media-box__bd" >
                <h4 class="weui-media-box__title"><&= _this.activity.title&></h4>
                <!--<p class="" style="color:#999;font-size:13px"><&= _this.activity.start_time_format &>开始</p>-->
                <p class="weui-media-box__desc">报名人：<&= _item.name&></p>
                <p class="weui-media-box__desc">联系方式：<&= _item.phone&></p>
                <!--<& if(_this.activity.address && _this.activity.address!='') {&>
                <p class="weui-media-box__desc"><&= _this.activity.address&></p>
                <&}&>
                -->
                <& if(_this.activity.is_owner==1 && _this.activity.can_signup) {&>
                    <& if((_this.activity.status=='1' && _item.aj_status==1) || (_item.ao_status==1 && _item.aj_status!=5)){&>
                        <div class="weui-media-box__info__meta" style="padding-top: 10px;">
                            <&if(_item.aj_status==6){&>
                                <button class="weui-btn mini-btn" type="button" data-join="<&= _item.join_id &>" data-activity="<&= _item.activity_id &>" style="background-color:#999">签到</button>
                            <&} else {&>
                                <button class="weui-btn mini-btn sign_in_one" type="button" data-join="<&= _item.join_id &>" data-activity="<&= _item.activity_id &>" style="background-color:#1AAD19">签到</button>
                            <&}&>
                        </div>
                    <&} else if(!_this.activity.is_start) {&>
                        <div class="weui-media-box__info__meta" style="padding-top: 10px;">
                            <button class="weui-btn mini-btn no_start" type="button" data-join="<&= _item.join_id &>" data-activity="<&= _item.activity_id &>" style="background-color:#999">签到</button>
                        </div>
                    <&} else if(_item.aj_status==5) {&>
                        <div class="weui-media-box__info__meta" style="padding-top: 10px;">
                            <button class="weui-btn mini-btn" type="button" data-join="<&= _item.join_id &>" data-activity="<&= _item.activity_id &>" style="background-color:#999">已签到</button>
                        </div>
                    <&} else if(_item.aj_status==6) {&>
                        <div class="weui-media-box__info__meta" style="padding-top: 10px;">
                            <button class="weui-btn mini-btn" type="button" data-join="<&= _item.join_id &>" data-activity="<&= _item.activity_id &>" style="background-color:#999">签到</button>
                        </div>
                    <&}&>
                <&} else if(_this.activity.is_owner==1 && !_this.activity.can_signup && !_this.activity.is_start) {&>
                    <div class="weui-media-box__info__meta" style="padding-top: 10px;">
                        <button class="weui-btn mini-btn no_start" type="button" data-join="<&= _item.join_id &>" data-activity="<&= _item.activity_id &>" style="background-color:#999">签到</button>
                    </div>
                <&} else if(_this.activity.is_owner==1 && !_this.activity.can_signup && _this.activity.is_start) {&>
                    <div class="weui-media-box__info__meta" style="padding-top: 10px;">
                        <button class="weui-btn mini-btn finish_one" type="button" data-join="<&= _item.join_id &>" data-activity="<&= _item.activity_id &>" style="background-color:#999">签到</button>
                    </div>
                <&} else if(_this.activity.is_owner==1 && _item.aj_status==5) {&>
                    <div class="weui-media-box__info__meta" style="padding-top: 10px;">
                        <button class="weui-btn mini-btn" type="button" data-join="<&= _item.join_id &>" data-activity="<&= _item.activity_id &>" style="background-color:#999">已签到</button>
                    </div>
                <&}&>
            </div>
        </a>

        <div class="weui-panel__hd" style="color:#16B990;padding:0px 15px 5px 15px;text-align:right">
            <& if(_item.money && _item.money!='' && _item.money*1>0) {&>
            <span >报名费：{pigcms{$config['Currency_symbol']}<&= _item.money&></span>
            <div style="padding-top:5px ;color:#999">总价： {pigcms{$config['Currency_symbol']}<&=_this.activity.fees_all&> </div>
            <&} else {&>
            <span >免费</span>
            <&}&>

        </div>

        <&}&>
        <& if(View.isEmpty(_this.lists.list)) {&>
        <div class="weui-loadmore " >
            <div><img src="static/activity/assets/image/noresult.png"/></div>
            <span class="weui-loadmore__tips" style="color: #999">暂无数据</span>
        </div>
        <& }&>
        <& if(View.isNomore(_this.lists)) {&>
        <div class="weui-loadmore tjt_b_t" style="height:75px;line-height:45px;margin:auto">
            <span class="weui-loadmore__tips" style="color: #999">暂无更多数据</span>
        </div>
        <& }&>
</script>
<script id="no_data" type="text/html">
     <div class="weui-loadmore " >
            <div><img src="static/activity/assets/image/noresult.png"/></div>
            <span class="weui-loadmore__tips" style="color: #999"><&= _this&>  </span>
        </div>
</script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
<script type="text/javascript"  src="static/activity/assets/js/components/iscroll-zoom/js/iscroll.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/fastClick/fastClick.js"></script>
<script>
    var View={
        init:function(){
            this.initRefresh();
            this.clickList()
        },
        settings: {
            pageNo: 1,
            pageSize: 10,
            totalPages: 0,
            status:'',
            activity_id:'',
            loading:false,
        },
        isEmpty: function(list){
            var b = View.settings.pageNo == 1 && !list.length;

            if(b){
                this.resetInfinite(false);
            }

            return b;
        },
        isNomore: function(s){
          var b =this.settings.totalPages>0&&((this.settings.pageNo >= this.settings.totalPages) || (list.length < this.settings.pageSize)) ;

            if(b){
                this.resetInfinite(false);
            }
            return b;
        },
        initRefresh: function() {
            var self = this;
            var $body = $('body');
            var w=($(window).width() + 78)/6;
            $("#scroller li").css("width",w+'px');
            $("#scroller ").width(w*6+78);
            new IScroll('#wriper', { eventPassthrough: true, scrollX: true, scrollY: false, preventDefault: false });
            var href=window.location.href;
            var ss=href.lastIndexOf('=')+1
            var activity_id=self.settings.activity_id=href.slice(ss)
            var resetInfinite = self.resetInfinite = function(init,reFresh){
                $body[ init ? 'infinite' : 'destroyInfinite' ]();
                $body.find('#weui-loadmore')[ init ? 'removeClass' : 'addClass' ]('app-hide');

            };
            var requestData = self.requestData = function(replaceAll){
                if (View.settings.loading) return;
                $.showLoading('加载中')
                View.settings.loading = true;
                if(replaceAll){
                    self.settings.pageNo = 1;
                }else{
                    self.settings.pageNo++;
                }
                var datas={
                    page:self.settings.pageNo,
                    status:self.settings.status,
                    activity_id:self.settings.activity_id
                }
                $.ajax({
                    url: "{pigcms{:U('ActivityOffline/join_list')}",
                    async: false,//这一步是非常重要的，作用是设置为同步执行
                    dataType : "json",
                    data:datas,
                    type : "post",
                    success: function (data) {
                        if(data.errorMsg!='success'){
                             if(self.settings.pageNo==1){
                                 var index = Simplite.getTemplate('no_data');
                                 var indexTpl = Simplite.compile(index)(data.errorMsg);
                                $("#showIndex").html(indexTpl);
                                $("#weui-loadmore").hide()
                             }
                            $.hideLoading()
                        }else{
                            View.settings.totalPages=data.result.lists.pageTotal;
                            var index = Simplite.getTemplate('index');
                            var indexTpl = Simplite.compile(index)(data.result);
                            var h1=$(".weui-navbar").height();
                            var wh=$(window).height();
                            if(replaceAll){
                                $("#showIndex").html(indexTpl);

                            }else{
                                $("#showIndex").append(indexTpl);
                            }
                            var h3=$("#showIndex").height();
                            if(wh-h3-h1>=0){
                                if(data.result.lists.list && data.result.lists.list>=10){
                                    View.settings.loading = false;
                                    requestData(false);
                                }else{
                                    View.settings.loading = false;
                                    $('#weui-loadmore').addClass('app-hide')
                                }
                            }
                            $.hideLoading()
                        }


                    },
                    error:function(data){
                        $.hideLoading()
                        console.log(data)
                    }
                });
            }
            $body.on("infinite", function() {
                requestData(false);

            });
            var $item = $('.list_title');
            $item.on('click', function(event) {
                var url = $(this).attr('data-url');
                $(this).find('div').addClass('active');
                $(this).closest('li').siblings().find('.pro').removeClass('active');
                self.settings.status=$(this).attr('data-status');
                View.settings.loading=false;
                self.settings.pageNo=1;
                $("#showIndex").html('');
                resetInfinite(true);
                requestData(true);
            });
            $item.first().trigger('click')

        },
        clickList:function(){
            var self=this;

            $(function() {
                FastClick.attach(document.body);
            });
            $(document).on("click",'.weui-media-box',function(){
                var url=$(this).attr('data-url');
                var ac=$(this).attr('data-ac');
                if(ac=='true'){
                    url=$(this).attr('data-activity');
                }
                //location.href=url

            })
            $('.weui-tab__bd-item').pullToRefresh({
                onRefresh: function () {
                    self.resetInfinite(true)
                    //$.showLoading('加载中')
                    self.settings.pageNo=1;
                    self.settings. pageSize=10;
                    self.settings.totalPages=0;
                    View.settings.loading=false
                    self.requestData(true)
                    setTimeout(function() {
                        $('.weui-tab__bd-item').pullToRefreshDone();
                    }, 2000)
                },
                onPull: function (percent) {

                },
                distance: 50
            });

            // 活动未开始
            $(document).on('click', '.no_start', function () {
                $.confirm({
                    title: '提示',
                    text: '当前活动还未开始，不能签到！',
                    onOK: function () {
                        //点击确认
                    },
                    onCancel: function () {
                    }
                });
            });
            // 活动已经结束
            $(document).on('click', '.finish_one', function () {
                $.confirm({
                    title: '提示',
                    text: '当前活动已结束，不能签到！',
                    onOK: function () {
                        //点击确认
                    },
                    onCancel: function () {
                    }
                });
            });

            $(document).on("click",'.sign_in_one',function(){
                var join_ids=$(this).attr('data-join');
                var activity_ids=$(this).attr('data-activity');
               $.confirm({
                   title: '提示',
                   text: '确定签到活动吗？',
                   onOK: function () {
                       //点击确认
                       $.showLoading('加载中');
                        $.ajax({
                           url: "{pigcms{:U('ActivityOffline/sign_in_one')}",
                           async: false,//这一步是非常重要的，作用是设置为同步执行
                           dataType : "json",
                           data:{'join_id':join_ids,'activity_id':activity_ids},
                           type : "post",
                           success: function (data) {
                               $.hideLoading()
                               if(data.errorMsg!='success'){
                                   $.alert(data.errorMsg)
                                   return false
                               }
                               window.location.href = window.location.href;
                           },
                           error:function(data){
                               $.hideLoading();
                               console.log(data)
                           }
                       });
                   },
                   onCancel: function () {
                   }
               });
           })

        }



    }
    window.View=View;
    View.init()
</script>
</body>
</html>