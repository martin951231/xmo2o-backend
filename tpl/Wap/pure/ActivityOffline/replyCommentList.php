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
    <link rel="stylesheet" type="text/css" href="static/activity/assets/css/icon.css">
    <!--[if lte IE 8]>
    <script type="text/javascript" src="static/activity/assets/js/components/respond/1.4.2/respond.min.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/html5shiv/3.7.2/html5shiv.min.js"></script>
    <![endif]-->
</head>
<style>


    .introList {
        padding: 10px 10px 100px 10px;
        background-color: white;
    }
    .introList .titleDiv {
        padding-bottom: 10px;
    }
    .introList .titleDiv .title {
        border-left: 4px solid #06c1ae;
        padding-left: 8px;
        font-size: 16px;
        line-height: 16px;
        color: #06c1ae;
    }
    .introList .title_info {
        margin-bottom: 10px;
        font-size: 16px;
        line-height: 16px;
        border-left: 4px solid #06c1ae;
    }

    .introList.comment .rateInfo {
        display: inline-block;
        margin-left: 12px;
    }
    .rateInfo .starIconBg {
        width: 97px;
        height: 16px;
        background: url(static/activity/assets/image/starIconBg.png) no-repeat;
        background-size: 100%;
        display: inline-block;
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
    }
    .introList.comment .right {
        float: right;
        color: #999;
    }
    .introList.comment .comment_detail {
        padding: 12px 0px 0px 0px;
    }

    .introList.comment .titleBar {
        margin-bottom: 4px;
    }
    .introList.comment .titleBar div {
        display: inline-block;
    }
    .introList.comment .titleBar .nickname {
        margin-right: 12px;
    }
    .introList.comment .titleBar .dateline {
        color: #888;
    }
    .introList.comment .rateInfo {
        display: inline-block;
        margin-left: 12px;
    }
    .introList.comment .titleBar .rateInfo {
        float: right;
    }
    .introList.comment .titleBar .rateInfo .starIconBg {
        width: 85px;
        height: 14px;
        background-size: 85px 14px;
    }
    .introList.comment .titleBar .rateInfo .starIcon {
        height: 14px;
        background-size: 85px 14px;
        vertical-align: top;
    }
    .introList.comment .replyCon {
        color: #666;
    }
    .introList.comment .replyCon .textDiv {
        padding-bottom: 15px;
        position: relative;
    }
    .introList.comment .replyCon .text {
        color: #666;
    }
    .introList.comment .replyCon ul {
        overflow: hidden;
        margin-top: 10px;
    }
    .introList.comment .replyCon ul li {
        float: left;
        margin-right: 6px;
    }
    .introList.comment .replyCon ul img {
        width: 68px;
        height: 68px;
    }
   .border_bn:after{
       content:none !important;
   }
    .border-t:before{
        content: " ";
        position: absolute;
        left: 0;
        right: 0;
        height: 1px;
        color: #e5e5e5;
        top: 0;
        border-top: 1px solid #e5e5e5;
        -webkit-transform-origin: 0 0;
        transform-origin: 0 0;
        -webkit-transform: scaleY(.5);
        transform: scaleY(.5);
    }
    .rel{position:relative}
    #textArea{
        position:fixed;
        bottom:0px;
        left:0px;
        right:0px;
    }
    .giveContent {
    background:#f6f6f6;
        margin-top:10px;
        position:relative;
        font-size:14px;

    }

    .giveContent:after{
        content: "";width: 0;height: 0;display: inline-block;
        border-top: 8px solid #f6f6f6;border-left: 6px solid transparent;
        border-right: 6px solid transparent;border-bottom: 0;margin-left: 0px;
        position: absolute;top: -8px;left: 11px;-webkit-transform: rotate(-180deg);
    }
    .images{cursor:pointer}
    ul{list-style-type: none}
</style>
<body>
    <div class="comment introList">

    </div>
    <div id="textArea">

    </div>
    <script type="text/html"  id="list">
        <& if(View.settings.page_info==0 || View.settings.page_info==1){&>
        <div class="titleDiv">
            <div class="comment_detail weui-form-preview border_bn">
                <div class="titleBar">
                    <div class="nickname"><&=_this.now_comment.nickname&></div>
                    <div class="dateline"><&=_this.now_comment.add_time&></div>
                    <div class="rateInfo">
                        <div class="starIconBg">
                            <div class="starIcon" style="width:<&=(_this.now_comment.reply_star*20+1)&>%;"></div>
                        </div>
                    </div>
                </div>
                <div class="replyCon">
                    <div class="textDiv">
                        <div class="text"><&= _this.now_comment.content &></div>
                    </div>
                    <& if(_this.now_comment.img && _this.now_comment.img!=null && _this.now_comment.img.length>0){&>
                    <ul class="imgList">
                        <& for(var n=0;n< _this.now_comment.images.length;n++) {&>
                        <li>
                            <img src="<&= _this.now_comment.images[n]&>" data-index="<&= n&>" data-eq='<&= JSON.stringify(_this.now_comment)&>' class="images">
                        </li>
                        <&}&>
                    </ul>
                    <&}&>
                </div>
                <div style="text-align:right;display: none;">
                    <a class="iconfont  icon-huifu" data-index="<&=_this.now_comment&>" data-id="<&=_this.now_comment.id &>" data-activity_id="<&=_this.now_comment.activity_id &>"></a>
                </div>
            </div>
        </div>
        <div class="title_info">
            以下是评论回复
        </div>
        <&}&>
        <div id="content">
            <& for(var i=0;i< _this.list.length;i++) {&>
            <& var _item=_this.list[i] &>
            <div class="comment_detail weui-form-preview border_bn">
                <div class="titleBar">
                    <div class="nickname"><&=_item.nickname&></div>
                    <div class="dateline"><&=_item.add_time&></div>
                </div>
                <div class="replyCon">
                    <div class="textDiv">
                        <div class="text"><&= _item.content &></div>
                    </div>
                </div>
            </div>
            <&}&>
            <& if(View.isEmpty(_this.list)) {&>
            <div class="weui-loadmore " >
                <div><img src="static/activity/assets/image/noresult.png"/></div>
                <span class="weui-loadmore__tips" style="color: #999">暂无评论</span>
            </div>
            <&}&>
            <& if(View.isNomore(_this.list)) {&>
            <div class="weui-loadmore border-t rel" style="height:45px;line-height:45px;margin:auto;width:100%">
                <span class="weui-loadmore__tips" style="color: #999">暂无更多评论</span>
            </div>
            <&}&>
      <& if(View.settings.pageNo==1){&>
        </div>
     <&}&>
    </script>
    <script type="text/html" id="text">
        <div class="weui-cells weui-cells_form">
            <div class="weui-cell">
                <div class="weui-cell__bd" style="display: -webkit-box;">
                    <textarea class="weui-textarea" placeholder="请输入评论" rows="3" style="width: 80%;"></textarea>
                    <div class="weui-media-box__info__meta comment" style="padding: 0;width: 20%;">
                        <button class="weui-btn mini-btn" type="button" style="background-color:#16B990;line-height:2;font-size: 14px;">评论</button>
                    </div>
                </div>
            </div>
        </div>
    </script>
    <script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/fastClick/fastClick.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/swiper/swiper.js"></script>
    <script>
         View={
             init:function(){
                 this.initRefresh();
                 this.huifu()
             },
             settings:{
                 id:'',
                 page_info:0,
                 pageNo: 1,
                 pageSize: 10,
                 totalPages: 0
             },
             isEmpty: function(list){
                 var b = View.settings.pageNo == 1 && !list.length;

                 if(b){
                     this.resetInfinite(false);
                 }

                 return b;
             },
             isNomore: function(list){
                 var b =this.settings.totalPages>0 &&((this.settings.pageNo > this.settings.totalPages) || (list.length < this.settings.pageSize)) ;

                 if(b){
                     this.resetInfinite(false);
                 }
                 return b;
             },
             initRefresh: function() {
                 var self = this;
                 $(function() {
                     FastClick.attach(document.body);
                 });
                 self.settings.id="{pigcms{$Think.get.id}";
                 var $body = $('body');
                 var resetInfinite = self.resetInfinite = function(init,reFresh){
                     $body[ init ? 'infinite' : 'destroyInfinite' ]();
                     $body.find('#weui-loadmore')[ init ? 'removeClass' : 'addClass' ]('app-hide');
                 };
                 var requestData = self.requestData = function(replaceAll){
                     var datas={
                         page:replaceAll?1:self.settings.pageNo,
                         id:self.settings.id
                     }
                     $.showLoading('加载中')
                     $.ajax({
                         url: "{pigcms{:U('ActivityOffline/comment_reply_list')}",
                         async: false,//这一步是非常重要的，作用是设置为同步执行
                         dataType : "json",
                         data:datas,
                         type : "post",
                         success: function (data) {
                             console.log(data)
                             if(data.errorMsg=='success'){
                                 self.settings.totalPages=data.result.pageTotal;
                                 var index = Simplite.getTemplate('list');
                                 self.settings.page_info++;
                                  if(data.result.list && data.result.list!=null){
                                        if(data.result.list.length>=10){
                                            self.settings.pageNo++
                                        }
                                  }else{
                                      data.result.list=[]
                                  }
                                 var indexTpl = Simplite.compile(index)(data.result);
                                 if(replaceAll){
                                      $(".introList").html(indexTpl)
                                 }else{
                                     $("#content").append(indexTpl)
                                 }
                                 if($(".introList").height()-$(window).height()<=0){
                                     console.log('introList', self.settings.pageNo);
                                     if(data.result.list.length>=10){
                                         self.requestData(false);
                                     }else{
                                         $.hideLoading();
                                     }
                                 }else{
                                     $.hideLoading();
                                 }
                             }else{
                                 $.hideLoading();
                                 $.alert(data.errorMsg)
                             }

                         },
                         error:function(data){
                             $.hideLoading()
                         }
                     });
                 }
                 $body.on("infinite", function() {
                     console.log('infinite', self.settings.pageNo);
                     self.requestData(false);
                 });
                 resetInfinite(true);
                 requestData(true);

             },
             huifu:function(){
                 var self=this;
                 var id=0;
                 var activity_id=0;
                 var index=0;var result='';
                 var validate=function(res,is_global){
                      result=res.replace(/(^\s+)|(\s+$)/g,"");
                     if(is_global.toLowerCase()=="g") {
                         if(result==''){
                             return false
                         }else{
                             return true
                         }
                     }
                 };
                 $(document).on('click','.icon-huifu',function(){
                     id=$(this).attr('data-id');
                     activity_id=$(this).attr('data-activity_id');
                     index=$(this).attr('data-index');
                     var index = Simplite.getTemplate('text');
                     var indexTpl = Simplite.compile(index)({});
                     console.log($("#textArea").html())
                     if($("#textArea .weui-cells").size()==0){
                         $("#textArea").html(indexTpl).focus()
                     }

                 });
                 $('.icon-huifu').click();
                 // $("#textArea").keydown(function(event) {
                 //     if (event.keyCode == 13) {
                 //         if(!validate($('.weui-textarea ').val(),'g')){
                 //             $.toptip("请输入评论", 'error')
                 //             return false
                 //         }else{
                 //             $.ajax({
                 //                 url: "{pigcms{:U('ActivityOffline/add_activity_reply')}",
                 //                 type:'post',
                 //                 data:{fid:id, activity_id: activity_id, content: $('.weui-textarea ').val(), reply_type: 2},
                 //                 async:false,
                 //                 dataType:'json',
                 //                 success:function(data){
                 //                    if(data.errorCode!=0){
                 //                        $.alert(data.errorMsg)
                 //                    }else{
                 //                        window.location.href = window.location.href;
                 //                    }
                 //                 },
                 //                 error:function(){
                 //
                 //                 }
                 //
                 //             })
                 //         }
                 //     }
                 // });
                 var comment_info = true;
                 $(document).on('click','.comment button',function(){
                     if (!comment_info) {
                         return false;
                     }
                     comment_info = false;
                     if(!validate($('.weui-textarea ').val(),'g')){
                         comment_info = true;
                         $.toptip("请输入评论", 'error');
                         return false
                     }else{
                         $.ajax({
                             url: "{pigcms{:U('ActivityOffline/add_activity_reply')}",
                             type:'post',
                             data:{fid:id, activity_id: activity_id, content: $('.weui-textarea ').val(), reply_type: 2},
                             async:false,
                             dataType:'json',
                             success:function(data){
                                 comment_info = true;
                                 if(data.errorCode!=0){
                                     $.alert(data.errorMsg)
                                 }else{
                                     window.location.href = window.location.href;
                                 }
                             },
                             error:function(){

                             }

                         })
                     }
                 });

                 $(document).on('click','.images',function(){
                    var datas=JSON.parse($(this).attr('data-eq'))
                     var i=parseInt($(this).attr('data-index'))
                     var pb1 = $.photoBrowser({
                         items:datas.images,
                         onSlideChange: function(index) {
                             console.log(this, index);
                         },
                         onOpen: function() {
                             console.log("onOpen", this);
                         },
                         onClose: function() {
                             console.log("onClose", this);
                         }
                     });
                     console.log(pb1.items)
                     pb1.open(i);
                 })
                 $(document).click(function(e){
                     var target = $(e.target);
                     if(target.closest(".iconfont").length != 0  || target.closest("#textArea").length != 0 ) {

                     }else{
                         // $("#textArea .weui-cells").hide().remove()
                     }


                 });

             }

         }
        window.View=View
         View.init()
    </script>
</body>
</html>