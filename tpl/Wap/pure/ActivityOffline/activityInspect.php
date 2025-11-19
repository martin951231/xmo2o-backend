<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description"
          content="Write an awesome description for your new site here. You can edit this line in _config.yml. It will appear in your document head meta (for Google search results) and in your feed.xml site description.">
    <title>活动审核/置顶</title>
    <link rel="stylesheet" type="text/css"
          href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.min.css">
    <link rel="stylesheet" type="text/css"
          href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/iscroll-zoom/css/iscroll.css">
    <!--[if lte IE 8]>
    <script type="text/javascript" src="static/activity/assets/js/components/respond/1.4.2/respond.min.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/html5shiv/3.7.2/html5shiv.min.js"></script>
    <![endif]-->
    <style>
        #weui-loadmore {
            margin: auto;
            height: 45px;
            line-height: 45px
        }

        .flex {
            display: -webkit-box; /* 老版本语法: Safari, iOS, Android browser, older WebKit browsers. */
            display: -moz-box; /* 老版本语法: Firefox (buggy) */
            display: -ms-flexbox; /* 混合版本语法: IE 10 */
            display: -webkit-flex; /* 新版本语法: Chrome 21+ */
            display: flex;
            align-items: center;
        }

        .flex1 {
            flex: 1;
            text-align: center
        }

        .weui-media-box {
            cursor: pointer
        }

        .tjt-cell:after {
            content: none !important;
        }

        .tjt_cell_before:before {
            content: none !important;
        }

        .city_search:after {
            transform: rotate(135deg) !important;
            right: -2px !important;
            border-color: #000 !important;
            border-width: 1px 1px 0 0 !important;
        }

        .tjt_cell_after:after {
            content: " ";
            position: absolute;
            right: 0;
            top: 0;
            left: auto;
            width: 1px;
            bottom: 0;
            height: auto;
            border-right: 1px solid #ccc;
            color: #ccc;
            -webkit-transform-origin: 100% 0;
            transform-origin: 100% 0;
            -webkit-transform: scaleX(.5);
            transform: scaleX(.5);
        }

        .a, .a:hover, .a:active, .a:visited, .a:link, .a:focus {
            -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
            -webkit-tap-highlight-color: transparent;
            outline: none;
            background: none;
            text-decoration: none;
            color: #000
        }

        .weui-navbar .pro {
            position: relative;
            cursor: pointer;
        }

        .weui-navbar .pro.active {
            color: #16B990
        }

        .weui-navbar .pro.active:after {
            content: ' ';
            position: absolute;
            left: 0px;
            right: 0px;
            bottom: 0px;
            height: 2px;
            border-bottom: 2px solid #16B990
        }

        .weui-navbar .flex1 {
            padding: 10px 10px
        }

        .app-hide {
            display: none
        }

        .tjt_b_t {
            position: relative
        }

        .tjt_b_t_n:after {
            content: none !important;
        }

        .tjt_b_t:before {
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

        .span {
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

        button {
            line-height: 30px !important;
            font-size: 14px !important;
        }

        .pointer {
            cursor: pointer;
        }

        button.weui-btn {
            width: 30%;
        }

        .weui-media-box_appmsg .weui-media-box__thumb {

            height: 100%;

        }

        #scroller li > div {
            text-align: center;
        }

    </style>
</head>
<body ontouchstart="">
<div class="weui-tab" id="content">
    <div class="weui-navbar " style="background:#fff;color:#000 !important;display:block;position:fixed;z-index:10">
        <div style="width:100%;height:45px;line-height:45px" class="flex" id='wriper'>
            <div id="scroller">
                <ul class="clearfix ">
                    <li>
                        <div class="" style="margin-top:0px;background-color:transparent;font-size: 15px;">
                            <a class="a list_title" href="javascript:;" data-ower="0" data-examine_status="1"
                               data-url="{pigcms{:U('ActivityOffline/activity_list_api')}">
                                <div class=" pro">待审核</div>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="  " style="padding:0px;margin-top:0px;font-size: 15px;">
                            <a class="a list_title" href="javascript:;" data-ower="1" data-examine_status="2"
                               data-url="{pigcms{:U('ActivityOffline/activity_list_api')}">
                                <div class=" pro">已通过</div>
                            </a>
                        </div>
                    </li>
                    <li>

                        <div class=" " style="margin-top:0px;font-size: 15px;">
                            <a class=" a list_title" href="javascript:;" data-ower="2" data-examine_status="4"
                               data-url="{pigcms{:U('ActivityOffline/activity_list_api')}">
                                <div class=" pro">已置顶</div>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class=" " style="margin-top:0px;font-size: 15px;">
                            <a class=" a list_title" href="javascript:;" data-ower="2" data-examine_status="3"
                               data-url="{pigcms{:U('ActivityOffline/activity_list_api')}">
                                <div class=" pro">已拒绝</div>
                            </a>
                        </div>
                    </li>
                    <li id="">
                        <div class=" " style="margin-top:0px;font-size: 15px;">
                            <a class=" a list_title" href="javascript:;" data-ower="2" data-examine_status="5"
                               data-url="{pigcms{:U('ActivityOffline/manager_refund_list')}">
                                <div class=" pro">申请退款</div>
                            </a>
                        </div>
                    </li>
                </ul>
                <div style="clear:both"></div>
            </div>


        </div>
    </div>
    <div class="weui-tab__bd" style="padding-top:0px">
        <div id="tab1" class="weui-tab__bd-item weui-pull-to-refresh weui-tab__bd-item--active"
             style="margin-top:0px;top:0px;position:relative">
            <div class="weui-pull-to-refresh__layer">
                <div class="weui-pull-to-refresh__arrow"></div>
                <div class="weui-pull-to-refresh__preloader"></div>
                <div class="down">下拉刷新</div>
                <div class="up">释放刷新</div>
                <div class="refresh">正在刷新</div>
            </div>
            <div class="content-padded" style="padding-bottom:2px">
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
<div id='pay_money'>

</div>
<script id="input_money" type="text/html">
    <div class="weui-mask weui-mask--visible" style='z-index:20'></div>
    <div class="weui-dialog weui-dialog--visible">
        <div class="weui-dialog__hd">
            <strong class="weui-dialog__title">退款金额</strong>
        </div>
        <div class="weui-dialog__bd">
            <p class="weui-prompt-text"></p>
            <input type="money" class="weui-input weui-prompt-input" id="weui-prompt-input" value=""
                   placeholder="请输入退款金额">
        </div>
        <div class="weui-dialog__ft">
            <a href="javascript:;" class="weui-dialog__btn default" id='tjt_calsel'>取消</a>
            <a href="javascript:;" class="weui-dialog__btn primary" id='tjt_confirm'>确定</a>
        </div>
    </div>
</script>
<script id="index" type="text/html">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var _item=_this[i] &>
    <& if(View.isjoin()){&>
    <div class="weui-panel__hd tjt_b_t_n" style="color:#16B990;padding:5px 15px;text-align:right">报名成功</div>
    <&}&>
    <& if(View.settings.examine_status!=5) {&>
    <a data-ac="<&=_item.is_owner==1?'true':'false'&>"
       href="{pigcms{:U('ActivityOffline/indexDetail')}&activity_id=<&=_item.activity_id&>&examine_status=<&=View.examine_status()&>"
       class="weui-media-box weui-media-box_appmsg weui-media-boxs">
        <&}else{&>
        <a data-ac="<&=_item.is_owner==1?'true':'false'&>"
           href="{pigcms{:U('ActivityOffline/activityOrder')}&activity_id=<&=_item.activity_id&>&join_id=<&=_item.join_id&>"
           class="weui-media-box weui-media-box_appmsg weui-media-boxs">
            <&}&>

            <div class="weui-media-box__hd" style="width:135px;height:90px;position:relative">
                <& if(_item.is_owner == 1){&>
                <span class="span">我创建</span>
                <&}&>
                <& if(_item.join_id!=0 && _item.join_status==1){&>
                <span class="span">已报名</span>
                <&}&>
                <& if(_item.join_id!=0 && _item.join_status==2){&>
                <span class="span">已参加</span>
                <&}&>
                <& if(_item.join_id!=0 && _item.join_status==4){&>
                <span class="span">已退款</span>
                <&}&>
                <img class="weui-media-box__thumb" src="<&= _item.image&>" alt="">
            </div>
            <div class="weui-media-box__bd">
                <h4 class="weui-media-box__title"><&= _item.title&></h4>
                <p class="" style="color:#999;font-size:13px"><&= _item.start_time &>开始</p>
                <& if(_item.address && _item.address!='') {&>
                <p class="weui-media-box__desc"><&= _item.address&></p>
                <&}&>
                <& if(_item.number && _item.number*1>0) {&>
                <p class="weui-media-box__desc">已报名<&= _item.number&>人</p>
                <&}&>
                <& if(!View.isjoin()) {&>
                <p style="padding-top:5px;display:flex;display:-moz-flex;display:-ms-flex;;display:-o-flex">
                    <& if(_item.fees && _item.fees!='' && _item.fees*1>0) {&>
                    <span style="color:#16B990;flex:1; padding: 3px 7px;">{pigcms{$config['Currency_symbol']}<&= _item.fees&></span>
                    <&} else {&>
                    <span style="color:#16B990;flex:1;padding: 3px 7px;">免费</span>
                    <&}&>


                    <& if(_item.is_end) {&>
                    <& if(_item.status==2) {&>
                    <span style="padding: 3px 7px;background: #ddd;  color: #fff;float:right">已取消</span>
                    <&} else{&>
                    <span style="padding: 3px 7px;background: #ddd;  color: #fff;float:right">已结束</span>
                    <&}&>
                    <&} else{&>
                    <& if(_item.is_new==1) {&>
                    <& if(_item.is_going==1) {&>
                    <span style="padding: 3px 7px;background: #E1F5ED;  color: #16B990;float:right">进行中</span>
                    <&} else{&>
                    <span style="padding: 3px 7px;background: #E1F5ED;  color: #16B990;float:right">最新发布</span>
                    <&}&>
                    <&} else{&>
                    <& if(_item.is_sign_end) {&>
                    <span style="padding: 3px 7px;background: #E1F5ED;  color: #16B990;float:right">进行中</span>
                    <&}&>
                    <&}&>
                    <&}&>
                </p>

                <&}&>
                <& if(View.settings.examine_status==4) {&>
                <p class="">
                    <button class="weui-btn mini-btn del_zhiding" type="button" data-id='<&= _item.activity_id&>'
                            style="background-color:#16B990;line-height:2;width:auto;margin-left:0px">取消置顶
                    </button>
                </p>
                <&}&>
                <& if(_item.refund_status && _item.reject_times==1){&>
                <div style='text-align: right;'>

                    <button style=' margin-right: 0px;padding:0px;background:#16B990;' class="weui-btn examine"
                            value="<&= _item.id&>" type="button" order_data="<&= _item.order_id&>"
                            join_data="<&= _item.join_id&>">审核
                    </button>
                </div>
                <&}&>
            </div>
            <& if(View.settings.examine_status!=5) {&>
        </a>
        <&}else{&>
    </a>
    <&}&>


    <& if(View.isjoin()){&>
    <div class="weui-panel__hd" style="color:#16B990;padding:5px 15px;text-align:right">
        <& if(_item.fees && _item.fees!='' && _item.fees*1>0) {&>
        <span>{pigcms{$config['Currency_symbol']}<&= _item.fees&>   x1</span>
        <div style="padding-top:5px ;color:#999">合计： {pigcms{$config['Currency_symbol']}<&= _item.fees&></div>
        <&} else {&>
        <span>免费</span>
        <&}&>

    </div>
    <&}&>
    <&}&>

    <& if(View.isEmpty(_this)) {&>
    <div class="weui-loadmore ">
        <div><img src="static/activity/assets/image/noresult.png"/></div>
        <span class="weui-loadmore__tips" style="color: #999">暂无数据</span>
    </div>
    <& }&>
    <& if(View.isNomore(_this)) {&>
    <div class="weui-loadmore tjt_b_t" style="height:45px;line-height:45px;margin:auto">
        <span class="weui-loadmore__tips" style="color: #999">暂无更多数据</span>
    </div>
    <& }&>

</script>
<script>
    // var uesr="{pigcms{:U('ActivityOffline/activity_list_api')}"
    //var other='{pigcms{:U(\'ActivityOffline/indexDetail\')}'
</script>
<script>
    var is_sign = "{pigcms{$_SESSION['user']['uid']}"
</script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/iscroll-zoom/js/iscroll.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/fastClick/fastClick.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
<script>
    var View = {
        init: function () {
            this.initRefresh();
            this.clickList()
        },
        settings: {
            pageNo: 1,
            pageSize: 10,
            totalPages: 0,
            url: "{pigcms{:U('ActivityOffline/activity_list_api')}",
            loading: false,
            examine_status: 1
        },
        isOwer: function (type, activity_id) {
            var ower = $('.pro.active').closest('a').attr('data-ower')
            if (ower == 0) {
                if (type == 1) {
                    return uesr + '&activity_id=' + activity_id
                } else {
                    return other + '&activity_id=' + activity_id
                }

            } else if (ower == 1) {
                return uesr + '&activity_id=' + activity_id
            } else {
                return other + '&activity_id=' + activity_id
            }


        },
        isEmpty: function (list) {
            var b = View.settings.pageNo == 1 && !list.length;

            if (b) {
                this.resetInfinite(false);
            }

            return b;
        },
        isNomore: function (list) {
            var b = this.settings.totalPages > 0 && ((this.settings.pageNo >= this.settings.totalPages) || (list.length < this.settings.pageSize));

            if (b) {
                this.resetInfinite(false);
            }
            return b;
        },
        isjoin: function () {
            return this.settings.is_join
        },
        examine_status: function () {
            return this.settings.examine_status
        },
        initRefresh: function () {
            var self = this;
            var $body = $('body');
            var w = $(window).width() / 5;
            $("#scroller li").css("width", w + 'px');
            $("#scroller ").width(w * 5 + 60);
            new IScroll('#wriper', {eventPassthrough: true, scrollX: true, scrollY: false, preventDefault: false});
            var resetInfinite = self.resetInfinite = function (init, reFresh) {
                $body[init ? 'infinite' : 'destroyInfinite']();
                $body.find('#weui-loadmore')[init ? 'removeClass' : 'addClass']('app-hide');

            };
            var requestData = self.requestData = function (replaceAll) {
                if (is_sign == '') {
                    $.alert({
                        title: '提示',
                        text: '请先登录',
                        onOK: function () {
                            window.location.href = '/wap.php?g=Wap&c=Login&a=index'
                        }
                    });
                } else {
                    if (View.settings.loading) return;
                    $.showLoading('加载中');

                    View.settings.loading = true;
                    if (replaceAll) {
                        self.settings.pageNo = 1;
                    } else {
                        self.settings.pageNo++;
                    }
                    var datas = {
                        page: self.settings.pageNo,
                        examine_status: self.settings.examine_status
                    }
                    if (self.settings.examine_status == 4) {
                        datas.is_top = 1;
                        datas.examine_status = 2
                    }
                    $.ajax({
                        url: self.settings.url,
                        async: false,//这一步是非常重要的，作用是设置为同步执行
                        dataType: "json",
                        data: datas,
                        type: "post",
                        success: function (data) {
                            console.log(data)
                            if (data.errorMsg != 'success') {
                                $.hideLoading()
                            } else {
                                View.settings.totalPages = data.result.pageTotal;
                                var index = Simplite.getTemplate('index');
                                if (data.result.list == null) {
                                    data.result.list = []
                                }
                                var indexTpl = Simplite.compile(index)(data.result.list);
                                var h1 = $(".weui-navbar").height();
                                var h2 = $(".news-scroller").height();
                                var wh = $(window).height();
                                if (replaceAll) {
                                    $("#showIndex").html(indexTpl);

                                } else {
                                    $("#showIndex").append(indexTpl);
                                }
                                /*if(data.result.list && data.result.list.length>0){
                                    console.log(44)
                                    if(self.settings.examine_status==1 ){
                                        $('.pro').eq(0).html('待审核('+data.result.total+')')
                                    }else if(self.settings.examine_status==2){
                                        $('.pro').eq(1).html('已通过('+data.result.total+')')
                                    }else if(self.settings.examine_status==3){
                                        $('.pro').eq(3).html('已拒绝('+data.result.total+')')
                                    }
                                }*/

                                var h3 = $("#showIndex").height();
                                if (wh - h3 >= 0) {
                                    if (data.result.list && data.result.list.length >= 10) {
                                        View.settings.loading = false;
                                        requestData(false);
                                    } else {
                                        View.settings.loading = false;
                                        $('#weui-loadmore').addClass('app-hide')
                                    }
                                } else {
                                    if (self.isNomore(data.result.list)) {
                                        $('#weui-loadmore').addClass('app-hide')
                                    }
                                    View.settings.loading = false;
                                }
                                $.hideLoading()
                            }


                        },
                        error: function (data) {
                            $.hideLoading()
                            console.log(data)
                        }
                    });
                }

            }
            $body.on("infinite", function () {
                requestData(false);

            });
            var $item = $('.list_title');
            $item.on('click', function (event) {
                var url = $(this).attr('data-url');
                $(this).find('div').addClass('active');
                $(this).closest('li').siblings().find('.pro').removeClass('active');
                self.settings.url = url;
                self.settings.pageNo = 1;
                self.settings.examine_status = $(this).attr('data-examine_status');
                View.settings.loading = false
                $("#showIndex").html('');
                resetInfinite(true);
                requestData(true);
            });
            if ("{pigcms{$Think.get.refund_examine}") {
                $item.last().trigger('click');
            } else {
                $item.first().trigger('click');
            }

        },
        clickList: function () {
            var self = this;
            var $_id = '';
            var $_join_id = '';
            var $_order_id = ''
            $(function () {
                FastClick.attach(document.body);
            });
            $(document).on("click", '.weui-media-boxs', function () {
                var url = $(this).attr('data-url');
                var ac = $(this).attr('data-ac');
                if (ac == 'true') {
                    url = $(this).attr('data-activity');
                }
                location.href = url

            })
            $('.weui-tab__bd-item').pullToRefresh({
                onRefresh: function () {
                    self.resetInfinite(true)
                    //$.showLoading('加载中')
                    self.settings.pageNo = 1;
                    self.settings.pageSize = 10;
                    self.settings.totalPages = 0;
                    View.settings.loading = false
                    self.requestData(true)
                    setTimeout(function () {
                        $('.weui-tab__bd-item').pullToRefreshDone();
                    }, 2000)
                },
                onPull: function (percent) {

                },
                distance: 50
            });
            $(document).on("click", '.examine', function (e) {
                e.stopPropagation();
                e.preventDefault();
                $_id = $('.examine').attr('value');
                $_order_id = $('.examine').attr('order_data');
                $_join_id = $('.examine').attr('join_data');
                $.modal({
                    title: "提示",
                    text: "是否审核通过",
                    buttons: [
                        {
                            text: "通过", onClick: function () {

                                var indexs = Simplite.getTemplate('input_money');
                                $("#pay_money").html(indexs);

                            }
                        },
                        {
                            text: "驳回", onClick: function () {
                                $.post("{pigcms{:U('ActivityOffline/verifyRefund')}", {
                                    'id': $_id,
                                    'status': 2
                                }, function (data) {
                                    $.alert(data.errorMsg, function () {
                                        if (data.errorCode == 0) {
                                            View.settings.loading = false
                                            $("#showIndex").html('');
                                            self.resetInfinite(true);
                                            self.requestData(true);
                                        }
                                        window.location.href = "{pigcms{$config['site_url']}/wap.php?g=Wap&c=ActivityOffline&a=activityInspect&refund_examine=1";
                                    });
                                }, 'JSON');

                            }
                        },
                        {
                            text: "取消", className: "default", onClick: function () {
                                console.log(3)
                                window.location.href = "{pigcms{$config['site_url']}/wap.php?g=Wap&c=ActivityOffline&a=activityInspect&refund_examine=1";
                            }
                        },
                    ]
                });
                /*layer.confirm('是否审核通过?', {               
                    btn: ['通过', '驳回']
                    ,offset: '300px'
                   , btn1:function(){
                        layer.open({
                          title: [
                            '退款金额',
                          ]
                          ,anim: 'up'
                          ,content: '<div style="text-align:center;"><input style="height:20px;line-height:20px;" name="money" id="money" type="text"></div>'
                          ,btn: ['确认', '取消']
                           ,yes: function(index){
                                var refund_money = $('#money').val();
                                console.log(order_id)
                                $.post("{pigcms{:U('ActivityOffline/mobile_refund')}",{'order_id':order_id,'id':id,'join_id':join_id,'refund_money':refund_money},function(data){
                                    layer.msg(data.errorMsg, {offset: '400px',icon: 1,time: 3000},function(){location.reload()});
                                },'JSON');
                                layer.close(index);
                                
                            }
                        });
                    }
                    , btn2: function () {
                        $.post("{pigcms{:U('ActivityOffline/verifyRefund')}",{'id':id,'status':2},function(data){
                          data=parseInt(data);
                          if(!data){
                                layer.msg('审核未通过!', {offset: '400px',icon: 1,time: 2000},function(){location.reload()});
                           }else{
                                layer.msg('操作失败,请重试!!', {offset: '400px',icon: 2,time: 2000},function(){location.reload()});
                           }
                        },'JSON');
                      layer.close(index);
                    }
                });*/

            })
            $(document).on("click", '#tjt_calsel', function () {
                $("#pay_money").html("")
            })
            $(document).on("click", '#tjt_confirm', function () {
                var input = $("#pay_money input").val();
                var reg = /(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/;
                if (reg.test(input)) {
                    $.post("{pigcms{:U('ActivityOffline/mobile_refund')}", {
                        'order_id': $_order_id,
                        'id': $_id,
                        'join_id': $_join_id,
                        'refund_money': input
                    }, function (data) {

                        $.alert(data.errorMsg, function () {
                            if (data.errorCode == 0) {
                                View.settings.loading = false
                                $("#showIndex").html('');
                                self.resetInfinite(true);
                                self.requestData(true);
                            }
                            window.location.href = "{pigcms{$config['site_url']}/wap.php?g=Wap&c=ActivityOffline&a=activityInspect&refund_examine=1";
                        })

                    }, 'JSON');

                } else {
                    $.toptip("请输入有效的金额", 'error')
                }
            })
            $(document).on("click", '.del_zhiding', function (e) {
                var id = $(this).attr("data-id")
                e.stopPropagation();
                e.preventDefault();
                $.ajax({
                    url: "{pigcms{:U('ActivityOffline/set_top')}",
                    async: false,//这一步是非常重要的，作用是设置为同步执行
                    dataType: "json",
                    data: {'activity_id': id, is_top: 0},
                    type: "post",
                    success: function (data) {
                        if (data.errorCode == 0) {
                            View.settings.loading = false
                            $("#showIndex").html('');
                            self.resetInfinite(true);
                            self.requestData(true);
                        } else {
                            $.alert("取消失败")
                        }

                    },
                    error: function () {

                    },
                })
            })

        }


    }
    window.View = View;
    View.init()
</script>
</body>
</html>