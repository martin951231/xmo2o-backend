<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Write an awesome description for your new site here. You can edit this line in _config.yml. It will appear in your document head meta (for Google search results) and in your feed.xml site description.">
    <title>搜索活动</title>
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.min.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/iscroll-zoom/css/iscroll.css">
    <!--[if lte IE 8]>
    <script type="text/javascript" src="static/activity/assets/js/components/respond/1.4.2/respond.min.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/html5shiv/3.7.2/html5shiv.min.js"></script>
    <![endif]-->
    <style>
        .weui-loadmore{
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
        .tjt-cell:after{content:none !important;}
        .tjt_cell_before:before{content:none !important;}
        .city_search:after{
            transform: rotate(135deg) !important;
            right:-2px !important;
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
        }
        .app-noresult-nothing{
            width: 65%;
            margin: 1.5em auto;
            line-height: 1.6em;
            font-size: 14px;
            text-align: center;
        }
        .weui-media-box_appmsg .weui-media-box__thumb {
   
    height: 100%;
  
}
    </style>
</head>
<body ontouchstart="">
<div class="" id="content" style="display:none">
    <div class="weui-search-bar" id="searchBar" style="position:fixed;left:0px;top:0px;right:0px;z-index:10;height:45px">
        <form class="weui-search-bar__form" action="#" target='frameFile' onsubmit="View.loadSearch()">
            <div class="weui-search-bar__box">
                <i class="weui-icon-search"></i>
                <input type="search" class="weui-search-bar__input" id="searchInput" placeholder="搜索" required="">
                <a href="javascript:" class="weui-icon-clear" id="searchClear"></a>
            </div>
            <label class="weui-search-bar__label" id="searchText" style="transform-origin: 0px 0px 0px; opacity: 1; transform: scale(1, 1);">
                <i class="weui-icon-search"></i>
                <span>搜索</span>
            </label>
            <iframe name="frameFile" style="display:none"></iframe>
        </form>
        <a href="javascript:" class="weui-search-bar__cancel-btn" id="searchCancel">取消</a>
    </div>
    <div id="listBox">
        <div class="weui-panel weui-panel_access" style="padding-top:35px">
            <div class="weui-panel__bd" id="showIndex" >


            </div>
        </div>
    </div>
</div>
<div class="weui-loadmore app-hide" id="weui-loadmore">
    <i class="weui-loading"></i>
    <span class="weui-loadmore__tips">正在加载</span>
</div>
<script type="text/html" id="page">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var _item=_this[i] &>
    <a href="{pigcms{:U('ActivityOffline/indexDetail')}&activity_id=<&=_item.activity_id&>" class="weui-media-box weui-media-box_appmsg">
        <div class="weui-media-box__hd" style="width:135px;height:90px">
            <img class="weui-media-box__thumb" src="<&= _item.image&>" alt="">
        </div>
        <div class="weui-media-box__bd" >
            <h4 class="weui-media-box__title"><&= _item.title&></h4>
            <p class="" style="color:#999;font-size:13px"><&= _item.start_time&>开始</p>
            <p class="weui-media-box__desc"><&= _item.start_time&></p>
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
    <& if(View.isEmpty(_this)){ &>
    <div class="app-noresult-nothing p-md">
        <div><img src="static/activity/assets/image/noresult.png"/></div>
        <span class="info" style="color: #999">暂无数据</span>
    </div>
    <& }else if(View.isNomore(_this)){ &>
    <div class="weui-loadmore  tjt_b_t" >
        <span class="weui-loadmore__tips" style="color: #999">暂无更多数据</span>
    </div>
    <& } &>
</script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/fastClick/fastClick.js"></script>
<script>
    var View={
        init:function(){
            this.initScroll();
            this.initRefresh();
            this.cansel()
        },
        settings: {
            pageNo: 1,
            pageSize: 10,
            totalPages: 0,
            city_id:'',
            content:''
        },
        initScroll: function() {
            var _this=this;
            var href=window.location.href;
            var ss=href.lastIndexOf('=')+1
            this.settings.city_id=href.slice(ss)
            $("#content").fadeIn();
            var bind_name = 'input';
            if (navigator.userAgent.indexOf("MSIE") != -1){ bind_name = 'propertychange' }
            $('#searchInput').bind(bind_name ,function(){
                _this.settings.content=$.trim($('#searchInput').val());
                if(_this.settings.content!=''){
                    $("#showIndex").html("")
                    _this.resetInfinite(true);
                    _this.requestData(true);
                }
            })
        },
        loadSearch:function(){
           _this.settings.content=$.trim($('#searchInput').val());
                if(_this.settings.content!=''){
                    $("#showIndex").html("")
                    _this.resetInfinite(true);
                    _this.requestData(true);
                }
        },
        cansel:function(){
        	$("#searchCancel").on('click',function(){
        		$("#showIndex").html("")
        	})
        },
        isEmpty: function(list){
            var b = View.settings.pageNo == 1 && !list.length;

            if(b){
                this.resetInfinite(false);
            }

            return b;
        },
        isNomore: function(list){
            var b =this.settings.totalPages>0 && ((this.settings.pageNo >= this.settings.totalPages) || (list.length < this.settings.pageSize)) ;

            if(b){
                this.resetInfinite(false);
            }
            return b;
        },
        initRefresh: function() {
            var self = this;
            var $body = $('body');
            var loading = false;
            var resetInfinite = self.resetInfinite = function(init){
                $body[ init ? 'infinite' : 'destroyInfinite' ]();
                $body.find('#weui-loadmore')[ init ? 'removeClass' : 'addClass' ]('app-hide');
            };
            var requestData = self.requestData = function(replaceAll){
                if (loading) return;
                loading = true;

                if(replaceAll){
                    self.settings.pageNo = 1;
                }else{
                    self.settings.pageNo++;
                }
                var datas={
                    page:self.settings.pageNo,
                    city_id:self.settings.city_id,
                    title:self.settings.content
                }
                $.showLoading('加载中')
                $.ajax({
                    url: "{pigcms{:U('ActivityOffline/activity_list')}",
                    async: false,//这一步是非常重要的，作用是设置为同步执行
                    dataType : "json",
                    data:datas,
                    type : "POST",
                    success: function (data) {
                        if(data.errorMsg!='success'){
                            $.hideLoading()
                        }else{
                            View.settings.totalPages=data.result.pageTotal;
                            var index = Simplite.getTemplate('page');
                             if(!data.result.list || data.result.list==null){
                                 data.result.list=[]
                             }
                            var indexTpl = Simplite.compile(index)(data.result.list)
                            if(replaceAll){
                                $("#showIndex").html(indexTpl);
                            }else{
                                $("#showIndex").append(indexTpl);
                            }
                            loading=false
                            $.hideLoading()
                        }

                    },
                    error:function(data){
                        $.hideLoading()
                    }
                });
            }
            $body.on("infinite", function() {
                requestData(false);
            });
        },
    }
    window.View=View;
    View.init()
</script>
</body>
</html>