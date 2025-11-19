<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Write an awesome description for your new site here. You can edit this line in _config.yml. It will appear in your document head meta (for Google search results) and in your feed.xml site description.">
    <title>活动审核列表</title>
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
        .pointer{cursor:pointer;}
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
        button{line-height:30px !important;font-size:14px !important;}
    </style>
</head>
<body ontouchstart="">
<div class="weui-tab" id="content" >
    <div class="weui-navbar " style="background:#fff;color:#000 !important;display:block;position:fixed">
        <div style="width:100%;height:45px;line-height:45px" class="flex">
            <div class= "flex1" style="margin-top:0px;background-color:transparent">
                <a class="a list_title" href="javascript:;" data-status="" data-id="0" data-url="{pigcms{:U('Platform/activity_all')}">
                    <div class=" pro">待审核</div>
                </a>
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
            <div class="content-padded" style="padding-bottom:66px">
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
        <div class="weui-panel__hd tjt_b_t_n" style="color:#16B990;padding:5px 15px;text-align:right">
            <& if(_item.status == 1){&>
            待审核
            <&}&>
            <& if(_item.status==2){&>
            审核不通过
            <&}&>
            <& if(_item.status==3){&>
            审核通过
            <&}&>
        </div>
        <a   style=" "  href="{pigcms{:U('Platform/activityOrder')}&join_id=<&=_item.join_id&>&activity_id=<&=_item.activity_id&>" class="weui-media-box weui-media-box_appmsg">
            <div class="weui-media-box__hd" style="width:135px;height:90px;position:relative">
                <img class="weui-media-box__thumb" src="<&= _this.activity.image&>" alt="">
            </div>
            <div class="weui-media-box__bd" >
                <h4 class="weui-media-box__title"><&= _this.activity.title&></h4>
                <p class="" style="color:#999;font-size:13px"><&= _this.activity.start_time_format &>开始</p>
                <& if(_this.activity.address && _this.activity.address!='') {&>
                <p class="weui-media-box__desc">订单编号:  <&= _item.order_id&></p>
                <&}&>
            </div>
        </a>
        <div style='display:flex'>
             <div class="weui-panel__hd  examine pointer" value="<&= _item.id&>" style="float: left;display:flex" >

            <button class="weui-btn" type="button"  style="background-color:#1AAD19" >审核</button>
        </div>
        <div class="weui-panel__hd" style="color:#16B990;padding:5px 15px;text-align:right;flex:1">
            <& if(_item.money && _item.money!='' && _item.money*1>0) {&>
            <span >{pigcms{$config['Currency_symbol']}<&= _item.money&>   x1</span>
            <div style="padding-top:5px ;color:#999">合计： {pigcms{$config['Currency_symbol']}<&= _item.money&> </div>
            <&} else {&>
            <span >免费</span>
            <&}&>

        </div>
        </div>
        
        
        <&}&>
        <& if(View.isEmpty(_this.lists.list)) {&>
        <div class="weui-loadmore " >
            <div><img src="static/activity/assets/image/noresult.png"/></div>
            <span class="weui-loadmore__tips" style="color: #999">暂无数据</span>
        </div>
        <& }&>
        <& if(View.isNomore(_this.lists.list)) {&>
        <div class="weui-loadmore tjt_b_t" style="height:45px;line-height:45px;margin:auto">
            <span class="weui-loadmore__tips" style="color: #999">暂无更多数据</span>
        </div>
        <& }&>



</script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/fastClick/fastClick.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
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
        isNomore: function(list){
            var b=false;
             if(this.settings.pageNo==1){
                 if(list.length>0){
                     return true
                 }else{
                     return false
                 }
             }else{
                 if(list.length < this.settings.pageSize) {
                     return true
                 }else{
                     return false
                 }
             }

            if(b){
                this.resetInfinite(false);
            }
            return b;
        },
        initRefresh: function() {
            var self = this;
            var $body = $('body');
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
                    // status:self.settings.status,
                    activity_id:self.settings.activity_id
                }
                $.ajax({
                    url: '{pigcms{:U(\'Platform/refundList_manager\')}',
                    async: false,//这一步是非常重要的，作用是设置为同步执行
                    dataType : "json",
                    data:datas,
                    type : "post",
                    success: function (data) {
                        if(data.errorMsg!='success'){
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
                            }else{
                                if(self.isNomore(data.result.list)){
                                    $('#weui-loadmore').addClass('app-hide')
                                }
                                View.settings.loading = false;
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
                $(this).find('div').addClass('active')
                $(this).closest('.flex1').siblings().find('.pro').removeClass('active')
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
                location.href=url

            })
            $(document).on("click",'.examine',function(){
                var id=$('.examine').attr('value');
                layer.confirm('是否审核通过?', {               
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
                                var money = $('#money').val();//获取退款金额
                                layer.close(index);
                                //进行微信退款操作
                            }
                        });
                    }
                    , btn2: function () {
                        $.post("{pigcms{:U('Platform/verifyRefund')}",{'id':id,'status':2},function(data){
                          data=parseInt(data);
                          if(!data){
                                layer.msg('审核未通过!', {offset: '400px',icon: 1,time: 2000},function(){location.reload()});
                           }else{
                                layer.msg('操作失败,请重试!!', {offset: '400px',icon: 2,time: 2000},function(){location.reload()});
                           }
                        },'JSON');
                      layer.close(index);
                    }
                });

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
        }


    }
    window.View=View;
    View.init()
        
</script>
</body>
</html>