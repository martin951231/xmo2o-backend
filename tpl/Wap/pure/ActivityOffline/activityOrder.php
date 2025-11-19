<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Write an awesome description for your new site here. You can edit this line in _config.yml. It will appear in your document head meta (for Google search results) and in your feed.xml site description.">
    <title>活动订单详情</title>
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.min.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/iscroll-zoom/css/iscroll.css">
    <style>
        .ticket {
        margin:15px;
          border-left:5px solid #16B990;
          border-right:1px solid #f5f5f5;
          box-shadow:5px 3px 5px #e5e5e5;

        }
        body{    background: #F8F8F8;}
        button{line-height:30px !important;font-size:14px !important;}
          .weui-cell_tjt {
            padding: 10px 15px;
            position: relative;
            display: -webkit-box;
            display: -webkit-flex;
            display: flex;
            -webkit-box-align: center;
            -webkit-align-items: center;
            align-items: center;
        }
        .weui-cell_tjt:before {
            content: " ";
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            height: 1px;
            border-top: 1px solid #e5e5e5;
            color: #e5e5e5;
            -webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            -webkit-transform: scaleY(.5);
            transform: scaleY(.5);
            left: 15px;
            z-index: 2;
        }
        .weui-media-box__thumb {
           
            height: 100%;
            
        }
    </style>
</head>
<body ontouchstart="">
<div class="weui-tab" id="content" >
    <div class="weui-tab__bd" style="padding-top:0px">
        <div id="tab1" class="weui-tab__bd-item weui-pull-to-refresh weui-tab__bd-item--active" style="margin-top:0px;top:0px;position:relative">
            <div class="content-padded" >
                <div class="weui-panel__bd" id="showIndex" style="margin-top:0px">
                </div>
            </div>
        </div>
    </div>
</div>
<script id="index" type="text/html">
    <a style="background: #fff;"  href="{pigcms{:U('ActivityOffline/indexDetail')}&activity_id=<&=_this.activity_id&>" class="weui-media-box weui-media-box_appmsg">
        <div class="weui-media-box__hd" style="width:135px;height:90px;position:relative">
            <img class="weui-media-box__thumb" src="<&= _this.image&>" alt="">
        </div>
        <div class="weui-media-box__bd" >
            <h4 class="weui-media-box__title"><&= _this.title&></h4>
            <p class="" style="color:#999;font-size:13px">开始时间：<&= _this.start_time &></p>
            <p class="" style="color:#999;font-size:13px">结束时间：<&= _this.end_time &></p>
            <& if(_this.address && _this.address!='') {&>
            <p class="weui-media-box__desc">地址：<&= _this.address&></p>
            <&}&>

            <div class="weui-media-box__info__meta comment" style="margin-top:5px;">
                <button class="weui-btn mini-btn" type="button"  style="background-color:#16B990;font-size: 14px;">点击查看详情</button>
            </div>
        </div>
    </a>
    <div class="weui-panel ticket">
        <div class="weui-panel__bd">
            <div class="weui-media-box weui-media-box_text">
                <div class="weui-media-box__info pay_desc" style="color:#000;    display: flex; align-items: center;">
                    <& if(_this.fees && _this.fees!='' && _this.fees*1>0) {&>
                    <div class="weui-media-box__info__meta" style='float:none'>{pigcms{$config['Currency_symbol']}<&=_this.fees&></div>
                    <div class="weui-media-box__info__meta" style='float:none'>X1</div>
                    <&}else{&>
                      <div class="weui-media-box__info__meta" style='float:none'>免费</div>
                    <&}&>
                    <div style='display: flex; align-items: center;flex:1;text-align:right;justify-content: flex-end;'>
                        <& if(_this.is_end) {&>
                        <div class="weui-media-box__info__meta" style="color:#B9AAA8;float:none">已结束</div>
                        <&}else if(_this.join_info.status==1) {&>
                        <div class="weui-media-box__info__meta" style="color:#16B990;float:none">待参加</div>
                        <&}else if(_this.join_info.status==2){&>
                        <div class="weui-media-box__info__meta" style="color:#16B990;float:none">已参加</div>
                        <&}else if(_this.join_info.status==3){&>
                        <div class="weui-media-box__info__meta" style="color:#16B990;float:none">待付款</div>
                        <&}else if(_this.join_info.status==4){&>
                        <div class="weui-media-box__info__meta" style="color:#B9403D;float:none">已退款</div>
                        <&}else if(_this.join_info.status==5){&>
                        <div class="weui-media-box__info__meta" style="color:#16B990;float:none">已参加</div>
                        <&}else if(_this.join_info.status==6){&>
                        <div class="weui-media-box__info__meta" style="color:#B9AAA8;float:none">已取消</div>
                        <&}&>

                        <& if(!_this.is_end && _this.join_info.is_mine && _this.fees==0 && (_this.join_info.status==1 || _this.join_info.status==5)) {&>
                        <div class="weui-media-box__info__meta">
                            <button class="weui-btn mini-btn refund_join" type="button"  style="background-color:#1AAD19">取消</button>
                        </div>
                        <&}&>
                   </div>
                    
                </div>
                <div class="weui-media-box__info pay_desc" style="color:#000">
                    <div class="weui-media-box__info__meta"><&=_this.join_info.name&></div>
                    <div class="weui-media-box__info__meta"><&=_this.join_info.phone&></div>
                </div>
                <& if(_this.is_owner==1) {&>
                <div class="weui-media-box__info" style="float:right;margin-top:5px" >
                    <& if(_this.status=='1' && _this.join_info.status==1 && _this.fees==0 && _this.join_info.is_mine){&>
                    <div class="weui-media-box__info__meta">
                        <button class="weui-btn mini-btn cancle" type="button"  style="background-color:#1AAD19">取消报名</button>
                    </div>
                    <&}&>
                    <& if(_this.status=='3' && _this.join_info.status==2){&>
                    <div class="weui-media-box__info__meta">
                        <button class="weui-btn mini-btn " type="button" style="background-color:#1AAD19">活动已完成</button>
                    </div>
                    <&}&>
                    <& if(_this.status=='2'){&>
                    <div class="weui-media-box__info__meta" >
                        <button class="weui-btn mini-btn" type="button" style="background-color:#1AAD19">活动已取消</button>
                    </div>
                    <&}&>
                </div>
                <&}&>

            </div>
        </div>
    </div>
    <div class="weui-cells weui-cells_after-title">
        <& if(_this.fees!=''){&>
        <div class="weui-cell" >
            <div class="weui-cell__bd">票总价</div>
            <div class="weui-cell__ft">{pigcms{$config['Currency_symbol']}<&=_this.fees&></div>
        </div>
        <div class="weui-cell" >
            <div class="weui-cell__bd">实际支付金额</div>
            <div class="weui-cell__ft" style="color:#16B990">{pigcms{$config['Currency_symbol']}<&=_this.fees&></div>
        </div>
        <&}&>
        <& if(_this.order!=''&& _this.order.orderid != ''){&>
        <div class="weui-cell">
            <div class="weui-cell__bd">订单编号：<&=_this.order.orderid&></div>
        </div>
        <div class="weui-cell">
            <& if(_this.order.pay_type==0){&>
            <div class="weui-cell__bd" >支付方式：余额</div>
            <&}&>
            <& if(_this.order.pay_type==1){&>
            <div class="weui-cell__bd" >支付方式：微信</div>
            <&}&>

        </div>
        <&}&>
        <div class="weui-cell">
            <div class="weui-cell__bd">创建时间：<&=_this.add_time&></div>
        </div>
       <& if(_this.setJoin.length>0 ){&>
        <div class=" "  >
            <div class="weui-cell__bd weui-cell_tjt">自定义信息</div>
            <& for(var i=0;i< _this.setJoin.length;i++) {&>
            <div class="weui-cell"  >
                <div class="weui-cell__bd"><&=_this.setJoin[i].key&></div>
                <div class="weui-cell__ft weui-cell__bd"><&=_this.setJoin[i].value&></div>
            </div>
            <&}&>
        </div>
        <&}&>
    </div>
</script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/fastClick/fastClick.js"></script>
<script>
    $.showLoading('加载中')
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
            loading:false,
            join_id:'',
            activity_id:""
        },
        initRefresh: function() {

            var self = this;
            var href=window.location.href;
            var ss=href.lastIndexOf('=')+1
            var activity_id=$.getUrlParam('activity_id');
            var index=href.indexOf('join_id')+'join_id'.length+1
            var join_id=$.getUrlParam('join_id')
            var datas={
                page:self.settings.pageNo,
                join_id:join_id,
                activity_id:activity_id
            }
            $.ajax({
                url: "{pigcms{:U('ActivityOffline/join_detail')}",
                async: false,//这一步是非常重要的，作用是设置为同步执行
                dataType : "json",
                data:datas,
                type : "post",
                success: function (data) {
                    console.log(data);
                    if(data.errorMsg!='success'){
                        $.hideLoading()
                        $.alert(data.errorMsg)
                    }else{
                        var index = Simplite.getTemplate('index');
                        if(data.result.join_info.content && data.result.join_info.content!=null){
                            data.result.setJoin = Object.keys(data.result.join_info.content).map(function(item, index){
                                return {key: item, value:data.result.join_info.content[item]}
                            })
                            for(var i in data.result.setJoin){
                                if(data.result.setJoin[i].value!=''){


                                }
                            }
                        }else{
                            data.result.setJoin=[]
                        }
                        var indexTpl = Simplite.compile(index)(data.result);
                        $("#showIndex").html(indexTpl);
                        $.hideLoading()
                    }

                },
                error:function(data){
                    $.hideLoading()
                    console.log(data)
                }
            });


        },

        clickList:function(){
            var self=this;
            var join_id=$.getUrlParam('join_id')
           var activity_id=$.getUrlParam('activity_id')
            $(function() {
                FastClick.attach(document.body);
            });
           $(document).on("click",'.cancle',function(){
               $.confirm({
                   title: '提示',
                   text: '确定取消报名吗？',
                   onOK: function () {
                       //点击确认
                       $.showLoading('加载中')
                        $.ajax({
                           url: "{pigcms{:U('ActivityOffline/cancle')}",
                           async: false,//这一步是非常重要的，作用是设置为同步执行
                           dataType : "json",
                           data:{'join_id':join_id,'activity_id':activity_id},
                           type : "post",
                           success: function (data) {
                               $.hideLoading()
                               if(data.errorMsg!='success'){
                                   $.alert(data.errorMsg)
                                   return false
                               }
                             self.initRefresh()
                           },
                           error:function(data){
                               $.hideLoading()
                               console.log(data)
                           }
                       });
                   },
                   onCancel: function () {
                   }
               });

           })
           
           $(document).on("click",'.refund',function(){
               $.confirm({
                   title: '提示',
                   text: '确定申请退款吗？',
                   onOK: function () {
                       //点击确认
                       $.showLoading('加载中')
                        $.ajax({
                           url: "{pigcms{:U('ActivityOffline/refund_examine')}",
                           async: false,//这一步是非常重要的，作用是设置为同步执行
                           dataType : "json",
                           data:{'join_id':join_id,'activity_id':activity_id},
                           type : "post",
                           success: function (data) {
                               $.hideLoading()
                               if(data.errorMsg!='success'){
                                   $.alert(data.errorMsg)
                                   return false
                               }
                             self.initRefresh()
                           },
                           error:function(data){
                               $.hideLoading()
                               console.log(data)
                           }
                       });
                   },
                   onCancel: function () {
                   }
               });

           });
           // 取消参与活动
            $(document).on("click",'.refund_join',function(){
                $.confirm({
                    title: '提示',
                    text: '确定取消参与吗，取消后不可重复参加？',
                    onOK: function () {
                        //点击确认
                        $.showLoading('加载中')
                        $.ajax({
                            url: "{pigcms{:U('ActivityOffline/refund_join')}",
                            async: false,//这一步是非常重要的，作用是设置为同步执行
                            dataType : "json",
                            data:{'join_id':join_id,'activity_id':activity_id},
                            type : "post",
                            success: function (data) {
                                $.hideLoading()
                                if(data.errorMsg!='success'){
                                    $.alert(data.errorMsg)
                                    return false
                                }
                                self.initRefresh()
                            },
                            error:function(data){
                                $.hideLoading()
                                console.log(data)
                            }
                        });
                    },
                    onCancel: function () {
                    }
                });

            });



           $(document).on("click",'.refund2',function(){
               $.confirm({
                   title: '提示',
                   text: '确定申诉到分类管理员请求退款吗？',
                   onOK: function () {
                       //点击确认
                       $.showLoading('加载中')
                        $.ajax({
                           url: "{pigcms{:U('ActivityOffline/refund_examine')}",
                           async: false,//这一步是非常重要的，作用是设置为同步执行
                           dataType : "json",
                           data:{'join_id':join_id,'activity_id':activity_id,'reject_times':1},
                           type : "post",
                           success: function (data) {
                               $.hideLoading()
                               if(data.errorMsg!='success'){
                                   $.alert(data.errorMsg);
                                   return false
                               }
                               self.initRefresh();
                               window.location.href = window.location.href;
                           },
                           error:function(data){
                               $.hideLoading()
                               console.log(data)
                           }
                       });
                   },
                   onCancel: function () {
                   }
               });

           })
           $(document).on("click",'.refund3',function(){
               $.confirm({
                   title: '提示',
                   text: '确定申诉到平台管理员请求退款吗？',
                   onOK: function () {
                       //点击确认
                       $.showLoading('加载中')
                        $.ajax({
                           url: "{pigcms{:U('ActivityOffline/refund_examine')}",
                           async: false,//这一步是非常重要的，作用是设置为同步执行
                           dataType : "json",
                           data:{'join_id':join_id,'activity_id':activity_id,'reject_times':2},
                           type : "post",
                           success: function (data) {
                               $.hideLoading()
                               if(data.errorCode!='73000021'){
                                   $.alert(data.errorMsg)
                                
                               }else{
                               	   $.alert(data.errorMsg,'提示',function() {
                                      self.initRefresh()
                                     });
                               }
                            
                           },
                           error:function(data){
                               $.hideLoading()
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