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
        
        .pay{
            font-size:14px;
        }
        .rateInfo .starIconBg {
            width: 97px;
            height: 16px;
            background: url(static/activity/assets/image/starIconBg.png) no-repeat;
            background-size: 100%;
            display: inline-block;
            float: left;
        }
        .rateInfo .starIcon {
            background: url(static/activity/assets/image/starIcon.png) no-repeat;
            height: 16px;
            background-size: 97px 16px;
        }
        .rateInfo .starText {
            display: inline-block;
            vertical-align: top;
            color: #ff9c00;
            font-weight: bold;
            margin-left: 5px;
            line-height: 16px;
            float: left;
        }
    </style>
</head>
<body ontouchstart="">
<div class="weui-tab" id="content" >
    <div class="weui-navbar " style="background:#fff;color:#000 !important;display:block;position:fixed">
        <div style="width:100%;height:45px;line-height:45px" class="flex">
            <!-- <div class= "flex1" style="margin-top:0px;background-color:transparent">
               <a class="a list_title" href="javascript:;" data-ower="0" data-id="0" data-url="{pigcms{:U('ActivityOffline/activity_all')}">
                     <div class=" pro">全部活动</div>
              </a>
           </div> -->
           <div class="flex1  " style="padding:0px;margin-top:0px;">
               <a class="a list_title" href="javascript:;" data-ower="1" data-id="0" data-url="{pigcms{:U('ActivityOffline/my_activity_list')}">
                <div class=" pro">创建的活动</div>
               </a>
           </div>
            <div class=" flex1" style="margin-top:0px;font-size: 15px;">
                <a class=" a list_title" href="javascript:;" data-ower="2" data-id="1" data-url="{pigcms{:U('ActivityOffline/join_activity_list')}">
                    <div class=" pro">参与的活动</div>
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
    <div class="weui-tabbar" style="z-index:20;position:fixed;    padding: 10px 0px;background-color: #fff">
        <div style="width:90%;margin:auto">
            <a href="{pigcms{:U('ActivityOffline/index')}">
                <button class="weui-btn weui-btn_primary" id="submit" type="button" style="background:#16B990">创建活动</button>
            </a>

        </div>
    </div>
</div>
<script id="index" type="text/html">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var _item=_this[i] &>
    <& if(View.isjoin()){&>
    <& if(_item.join_status==3){&>
        <div class="weui-panel__hd tjt_b_t_n" style="color:#16B990;padding:5px 15px;text-align:right">待付款</div>
    <&}else if(_item.join_status==1){&>
        <div class="weui-panel__hd tjt_b_t_n" style="color:#16B990;padding:5px 15px;text-align:right">报名成功</div>
    <&}else if(_item.join_status==2){&>
        <div class="weui-panel__hd tjt_b_t_n" style="color:#16B990;padding:5px 15px;text-align:right">已完成</div>
    <&}else if(_item.join_status==4){&>
        <div class="weui-panel__hd tjt_b_t_n" style="color:#B9403D;padding:5px 15px;text-align:right">已退款</div>
    <&}else if(_item.join_status==5){&>
        <div class="weui-panel__hd tjt_b_t_n" style="color:#16B990;padding:5px 15px;text-align:right">已参加</div>
    <&}else if(_item.join_status==6){&>
        <div class="weui-panel__hd tjt_b_t_n" style="color:#B9AAA8;padding:5px 15px;text-align:right">已取消</div>
    <&}&>
    <&}&>
    <a data-ac="<&=_item.is_owner==1?'true':'false'&>"  style=" <& if(View.isjoin()){&> padding-bottom:0px <&}&>"  href="<&= View.isOwer(_item.is_owner,_item.activity_id,_item.join_id)&>" class="weui-media-box weui-media-box_appmsg">
        <div class="weui-media-box__hd" style="width:135px;height:90px;position:relative">
            <& if(_item.is_owner == 1){&>
             <span class="span">我创建</span>
            <&}&>
            <& if(_item.join_id!=0 && _item.join_status==1){&>
            <span class="span">已报名</span>
            <&}&>
            <& if(_item.join_id!=0 && _item.join_status==5){&>
            <span class="span">已参加</span>
            <&}&>
            <& if(_item.join_id!=0 && _item.join_status==2){&>
            <span class="span">已完成</span>
            <&}&>
            <& if(_item.join_id!=0 && _item.join_status==4){&>
            <span class="span">已退款</span>
            <&}&>
            <img class="weui-media-box__thumb" src="<&= _item.image&>" alt="">
        </div>
        <div class="weui-media-box__bd" >
            <& if(_item.join_status == 3 && _item.fees !=0) {&>
            <p class="weui-media-box__info__meta go_pay_join" style="float:right;padding-right:0px">
                <button class="weui-btn mini-btn pay" type="button" data-uid="<&= _item.uid&>" data-id="<&= _item.activity_id&>"  style="background-color:#16B990;line-height:2">去付款</button>
            </p>
            <&}&>
            <h4 class="weui-media-box__title"><&= _item.title&></h4>
            <& if(_item.average_star > 0) {&>
                <div class="rateInfo">
                    <div class="starIconBg">
                        <div class="starIcon" style="width:<&=(_item.average_star*20+1)&>%;"></div>
                    </div>
                    <div class="starText"><&=(_item.average_star && _item.average_star!=null)?_item.average_star:'0'&></div>
                </div>
                <div style="clear: both;"></div>
            <&}&>

            <p class="" style="color:#999;font-size:13px"><&= _item.start_time_format &>开始</p>
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
                   
                        <& if(_item.status==1) {&>
                            <& if(_item.examine_status==2) {&>
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
                            <&}else if(_item.examine_status==1){&>
                                <span style="padding: 3px 7px;background: #E1F5ED;  color: #16B990;float:right">审核中</span>
                            <&}else{&>
                                <span style="padding: 3px 7px;background: #E1F5ED;  color: #16B990;float:right">审核未通过</span>
                            <&}&>
                        <&}&>
                        <& if(_item.status==2) {&>
                            <span style="padding: 3px 7px;background: #ddd;  color: #fff;float:right">已取消</span>
                        <&}&>
                        <& if(_item.status==3) {&>
                            <span style="padding: 3px 7px;background: #ddd;  color: #fff;float:right">已结束</span>
                        <&}&>
                        <& if(_item.status==4) {&>
                            <span style="padding: 3px 7px;background: #ddd;  color: #fff;float:right">已退款</span>
                        <&}&>
                   
                <&}&>
            </p>
            <&}&>

        </div>
    </a>
    <& if(View.isjoin()){&>
        <div class="weui-panel__hd" style="color:#16B990;padding:5px 15px;text-align:right">
            <& if(_item.status==3 && !_item.is_already_reply) {&>
                <div class="weui-media-box__info__meta comment" style="margin-left:145px;padding-right:0px">
                    <button class="weui-btn mini-btn" type="button" data-id="{pigcms{:U('ActivityOffline/activityComment')}&activity_id=<&=_item.activity_id&>" style="background-color:#16B990;line-height:2;font-size: 14px;">去评论</button>
                </div>
            <&}&>
            <& if(_item.fees && _item.fees!='' && _item.fees*1>0) {&>
            <span >{pigcms{$config['Currency_symbol']}<&= _item.fees&>   x1</span>
            <div style="padding-top:5px ;color:#999">合计： {pigcms{$config['Currency_symbol']}<&= _item.fees&> </div>
            <&} else {&>
            <span >免费</span>
            <&}&>

        </div>
    <&}&>
    <&}&>
    <& if(View.isEmpty(_this)) {&>
    <div class="weui-loadmore " >
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
    var uesr="{pigcms{:U('ActivityOffline/activityDetail')}"
    var other="{pigcms{:U('ActivityOffline/indexDetail')}"
    var join="{pigcms{:U('ActivityOffline/activityOrder')}"
     var is_sign ="{pigcms{$_SESSION['user']['uid']}"
</script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
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
            url:'activity_all',
            loading:false,
            is_join:false
        },
        isOwer:function(type,activity_id,join_id){

             var  ower=$('.pro.active').closest('a').attr('data-ower')
            if(ower==0){
                if(type==1){
                    return  uesr+'&activity_id='+activity_id
                }else{
                    return other+'&activity_id='+activity_id
                }

            }else if(ower==1){
                return  uesr+'&activity_id='+activity_id
            }else if(ower==2){
                return join+'&activity_id='+activity_id+'&join_id='+join_id
            }


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
        isjoin:function(){
             return this.settings.is_join
        },
        initRefresh: function() {
            var self = this;
            var $body = $('body');
            var resetInfinite = self.resetInfinite = function(init,reFresh){
                $body[ init ? 'infinite' : 'destroyInfinite' ]();
                    $body.find('#weui-loadmore')[ init ? 'removeClass' : 'addClass' ]('app-hide');

            };
            var requestData = self.requestData = function(replaceAll){
            	if(is_sign==''){
                          $.alert({
                        title: '提示',
                         text: '请先登录',
                        onOK: function () {
                         window.location.href='/wap.php?g=Wap&c=Login&a=index'
                         }
            });
            	}else{
                       if (View.settings.loading) return;
                $.showLoading('加载中');
                View.settings.loading = true;
                if(replaceAll){
                    self.settings.pageNo = 1;
                }else{
                    self.settings.pageNo++;
                }
                var datas={
                    page:self.settings.pageNo,
                    cate_id:'',
                    replace:self.settings.is_join
                }
                $.ajax({
                    url: self.settings.url,
                    async: false,//这一步是非常重要的，作用是设置为同步执行
                    dataType : "json",
                    data:datas,
                    type : "post",
                    success: function (data) {
                        if(data.errorMsg!='success'){
                            $.hideLoading()
                            resetInfinite(false)
                            $.alert(data.errorMsg)
                        }else{
                            View.settings.totalPages=data.result.pageTotal;
                            var index = Simplite.getTemplate('index');
                            var indexTpl = Simplite.compile(index)(data.result.list);
                            var h1=$(".weui-navbar").height();
                            var h2=$(".news-scroller").height();
                            var wh=$(window).height();
                            if(replaceAll){
                                $("#showIndex").html(indexTpl);

                            }else{
                                $("#showIndex").append(indexTpl);
                            }
                            var h3=$("#showIndex").height();
                            if(wh-h3>=0){
                                if(data.result.list &&  data.result.list.length>=10){
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
                        $.hideLoading();
                        console.log(data)
                    }
                });
            	}
              
            }
            $body.on("infinite", function() {
                requestData(false);

            });
            var $item = $('.list_title');
            $item.on('click', function(event) {
                var url = $(this).attr('data-url');
                $(this).find('div').addClass('active')
                $(this).closest('.flex1').siblings().find('.pro').removeClass('active')
                self.settings.url = url;
                self.settings.pageNo=1;
                self.settings.is_join=$(this).attr('data-id')==1?true:false;
                View.settings.loading=false
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

            });
            $(document).on('click','.comment button',function(e){
                var url=$(this).attr('data-id');
                e.stopPropagation();e.preventDefault();
                location.href=url;
            });
            $('.weui-tab__bd-item').pullToRefresh({
                onRefresh: function () {
                    self.resetInfinite(true);
                    //$.showLoading('加载中')
                    self.settings.pageNo=1;
                    self.settings. pageSize=10;
                    self.settings.totalPages=0;
                    View.settings.loading=false;
                    self.requestData(true);
                    setTimeout(function() {
                        $('.weui-tab__bd-item').pullToRefreshDone();
                    }, 2000)
                },
                onPull: function (percent) {

                },
                distance: 50
            });

            $(document).on("click",'.go_pay_join button',function(e){
                var id=$(this).attr('data-id');
                var  uid=$(this).attr("data-uid");
                e.stopPropagation();e.preventDefault();
                $.ajax({
                    url: "{pigcms{:U('ActivityOffline/go_pay_join')}",
                    dataType : "json",
                    data:{activity_id:id,uid:uid},
                    type : "post",
                    success: function (data) {
                        if (data.errorCode) {
                            $.toast(data.errorMsg, "cancel");
                        } else if(data.result && data.result.url) {
                            location.href=data.result.url
                        }
                    },
                    error:function(data){
                        console.log(data)
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