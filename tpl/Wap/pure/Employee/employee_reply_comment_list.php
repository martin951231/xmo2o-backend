<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>回复评论</title>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/weui.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/custom.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/icon.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}css/common.css"/>
    <link href="{pigcms{$static_path}employee/css/index.css?001" rel="stylesheet"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/cropper.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/layer.css"/>
    <script src="{pigcms{$static_path}employee/js/common/jquery-2.1.4.js" type="text/javascript"></script>
    <script src="{pigcms{$static_path}employee/js/common/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <style>
        .no_weui-media-box__desc {
            color: #999999;
            font-size: 13px;
            line-height: 1.2;

        }
        .cursor{cursor: pointer !important}
        .x_header {
            background-color: #04CB86!important;
        }
        .weui-pull-to-refresh__layer {
            padding: 6px;
        }
        .ping_fen li{
            color: #333;
            padding: 5px 10px;
            font-size: 14px;
            background: #FFEBE0;
            border-radius: 30px;
            margin-right:10px;
            margin-top: 10px;
        }
        .ping_fen li.active{
            color: #fff;
            background: #FF5B0F;
        }
        .time{
            font-size: 12px;
            font-weight: normal;
            text-align: right;
        }
        .memory_fen li{
            color: #333;
            padding: 5px 10px;
            font-size: 14px;
            background: #FFEBE0;
            border-radius: 30px;
            margin-right:10px;
        }
        #hotel{
           position:fixed;
            top:0px;
            left:0px;
            bottom:0px;
            right:0px;
            z-index:100
        }
        #weui-loadmore {
            margin-bottom: 0px;
            line-height: 40px;
            height: 40px;
            margin-top: 0px;
        }
        .border_radius{
            border-radius: 50%;
        }
        .giveContent:after{
            content: "";
            width: 0;
            height: 0;
            display: inline-block;
            border-top: 8px solid #f6f6f6;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-bottom: 0;
            margin-left: 0px;
            position: absolute;
            top: -8px;
            left: 11px;
            -webkit-transform: rotate(-180deg);
        }
        .my_reply{  display:none}
        .fixed{
            position:fixed;
            bottom:0px;
            right:0px;
            left:0px;
            padding:10px;
            background:#fff
        }
        .weui-btn {
            border-radius:0px !important;
            line-height: 40px !important;
            width: 75% !important;
        }
        #reply .weui-media-box_appmsg .weui-media-box__hd {
            width: 35px;
            height: 35px;

        }
        #reply  ul li{
            padding: 0px 10px;
            background: #f6f6f6;
        }
    </style>
</head>
<body class="pb0">
    <div class="list">
        <if condition="!$is_weixin_browser && !$is_app_browser">
            <div class="weui-pull-to-refresh__layer">
            </div>
            <header class="x_header bgcolor_11 cl f15" >
                <a class="z f14" href="javascript:window.history.go(-1);"><i class="iconfont icon-fanhuijiantou w15"></i>返回</a>
                <div class="navtitle">回复评论</div>
            </header>
        </if>
        <div class="page__bd " style="background-color: #F9F9F9;">
            <div class="weui-panel weui-panel_access before">
                <div class="weui-panel__bd" id="showDetail">
                    <div class="weui-media-box weui-media-box_appmsg flex_star">
                        <div class="weui-media-box__hd">
                            <img class="weui-media-box__thumb border_radius" src="{pigcms{$comment_info.avatar}" alt="" />
                        </div>
                        <div class="weui-media-box__bd w_3">
                            <h4 class="weui-media-box__title color_9 font_b font_size15 flex">
                                <span class="flex_one">{pigcms{$comment_info.nickname}</span>
                                <span class="flex_one time">{pigcms{$comment_info.add_time|date="Y-m-d H:i:s",###}</span>
                            </h4>
                            <div class="reply_info weui-media-box__desc">
                                <div class="rateInfo">
                                    <div class="starIconBg">
                                        <div class="starIcon" style="width:{pigcms{$comment_info.start}%;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="weui-media-box__desc">{pigcms{$comment_info.reply_content}</p>
                            <div class="clear pics">
                                <volist name="comment_info['image'] " id="voo">
                                    <img class="weui-media-box__thumb cursor" data-img="{pigcms{$voo}" data-index="{pigcms{$key}" src="{pigcms{$voo}" alt=""  style="margin-right: 2%; width: 31%;margin-top:2%;display:block;float:left;"/>
                                </volist>
                            </div>
                            <div class="clear"></div>
                            <p class="weui-media-box__desc " style="text-align:right;margin-top: 15px;">
                                <a href="javascript:;" class="add_ping " >
                                    <span class="iconfont comments icon-huifu" style=" font-size: 15px;"></span >
                                    <span style="padding-left:3px">加评/回复</span>
                                </a>

                            </p>
                        </div>
                    </div>
                    <if condition="$comment_info['memory_info']">
                        <div class="weui-cell">
                            <ul class="memory_fen">
                                <volist name="comment_info['memory_info']" id="vo">
                                    <li class="left cursor" data-type="memory" data-type_id="{pigcms{$vo['memory_id']}">{pigcms{$vo.memory_name}</li>
                                </volist>
                            </ul>
                        </div>
                    </if>
                    <div class="weui-cell" id="reply" style="padding:0px">
                        <ul class="giveContent rel" style=" width:100%;  margin: 10px 10px;">

                        </ul>
                    </div>
                </div>
                <div class="weui-loadmore none show" id='weui-loadmore'>
                    <i class="weui-loading"></i>
                    <span class="weui-loadmore__tips">正在加载</span>
                </div>
            </div>
        </div>
    </div>
   <div class="my_reply">
       <if condition="!$is_weixin_browser && !$is_app_browser">
           <div class="weui-pull-to-refresh__layer">
           </div>
           <header class="x_header bgcolor_11 cl f15" >
               <a class="z f14" href="javascript:window.history.go(-1);"><i class="iconfont icon-fanhuijiantou w15"></i>返回</a>
               <div class="navtitle">回复评论</div>
           </header>
       </if>
       <div class="weui-cells weui-cells_form ping" id="ping">
           <div class="weui-cell">
               <div class="weui-cell__bd">
                   <textarea class="weui-textarea" placeholder="请回复评论" rows="3"></textarea>
                   <div class="weui-textarea-counter"><span class="num">0</span>/50</div>
               </div>
           </div>
       </div>
       <div class="fixed">
            <div class="flex">
                <div class="flex_one">
                    <button class="weui-btn weui-btn_primary ">确定</button>
                </div>
                <div class="flex_one">
                    <a class="weui-btn weui-btn_default"  href="javascript:window.history.go(-1);">取消</a>
                </div>
            </div>
       </div>
   </div>

</body>
<script type="text/html" id="detail">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var _item=_this[i] &>
    <li class="weui-cell" style='position:relative'>
        <div class="weui-media-box weui-media-box_appmsg  flex" style="padding:10px 0px;width: 100%;">
            <div class="weui-media-box__hd">
                <img class="weui-media-box__thumb border_radius" src="<&= _item.avatar&>" alt="">
            </div>
            <div class="weui-media-box__bd w_3">
                <h4 class="weui-media-box__title color_9 font_b font_size15 flex">
                    <span class="flex_one" style="color: #72ACE3;"><&= _item.nickname&></span>
                    <span class="flex_one time"><&= _item.add_time_info&></span>
                </h4>
                <p class="no_weui-media-box__desc"><&= _item.reply_content&></p>
                <div class="clear pics">
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </li>
    <& }&>
    <& if(View.isEmpty(_this)) {&>
    <div class="weui-loadmore " >
        <div><img src="static/activity/assets/image/noresult.png"/></div>
        <span class="weui-loadmore__tips" style="color: #999">暂无评论数据</span>
    </div>
    <& }&>
    <& if(View.isNomore(_this)) {&>
    <div class="weui-loadmore tjt_b_t" style="height:45px;line-height:45px;margin:auto;width:100%">
        <span class="weui-loadmore__tips" style="color: #999">暂无更多数据</span>
    </div>
    <& }&>

</script>
<script src="{pigcms{$static_path}js/layer.m.js" type="text/javascript"></script>
<script src="{pigcms{$static_path}employee/js/common/simplite.js" type="text/javascript" charset="UTF-8"></script>
<include file="big_img"/>
<script type="text/javascript">
    //点赞
    var View={
        init:function(){
             this.initRefresh();
              this.checkClick();
              this.initImg()
        },
        settings: {
            pageNo: 1,
            pageSize: 10,
            totalPages: 0,
            reply_id:'',
            input:(navigator.userAgent.indexOf("MSIE") != -1)?'propertychange':'input',
            hashchange:0,
            chackchange:true,
            window_w:0,
            initfresh:false,
            w_3:0

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
             self.settings.reply_id = $.getUrlParam('reply_id');
            self.settings.window_w=$(window).width()
            var loading = false;  //状态标记
            var $body = $('body');
            var employee_id = $.getUrlParam('employee_id');
            var resetInfinite = self.resetInfinite = function(init,refresh){
                $body[ init ? 'infinite' : 'destroyInfinite' ]();
                $body.find('.show')[ (init && self.settings.pageNo!=1) ? 'removeClass' : 'addClass' ]('none');
            };
            var requestData = self.requestData = function(type){

                if (loading) return;
                loading = true;
              APP.load()
                var datas={
                    page:self.settings.pageNo,
                    employee_id:employee_id,
                    reply_id:self.settings.reply_id
                }
                $.ajax({
                    url: "{pigcms{:U('Employee/employee_reply_comment_ajax')}",
                    async: false,//这一步是非常重要的，作用是设置为同步执行
                    dataType : "json",
                    data:datas,
                    type : "get",
                    success: function (data) {
                        var index = Simplite.getTemplate('detail');
                        var list=[];
                        self.settings.totalPages=data.info.employee_reply_comment_list.totalPage;
                        if(data.info && data.info.employee_reply_comment_list && data.info.employee_reply_comment_list.list!=null && data.info.employee_reply_comment_list.list.length>0){
                            list=data.info.employee_reply_comment_list.list
                        }
                        if(self.settings.pageNo==1){
                            $("#reply ul").html('')
                        }
                        var indexTpl = Simplite.compile(index)(list);
                        if(type){
                            $("#reply ul").html(indexTpl);
                        }else{
                            $("#reply ul").append(indexTpl);
                        }
                        loading = false;
                        if(self.settings.w_3!=0){
                            $(".pics img").css("height",self.settings.w_3+'px')
                        }else{
                            self.initImg()
                        }
                        APP.hideLoad()
                    },
                    error:function(data){
                       APP.hideLoad()
                        loading = false;
                    }
                });
            }
            $body.on("infinite", function() {
                self.settings.pageNo++;
                requestData(false);
            });
            resetInfinite(true,true);
            requestData(false);
          /*  var reload=function(){
                var h=$(document).height();
                var wh=$(window).height();
                if(h<=wh){
                    requestData(false);
                }
            }
            reload()*/
        },
        validate:function(res,is_global){
            var _this=this;
            var result=res.replace(/(^\s+)|(\s+$)/g,"");
            if(is_global.toLowerCase()=="g") {
                if(result==''){
                    return false
                }else{
                    return true
                }
            }
        },
        checkClick:function(){
            var _self=this;
          $(document).on("click",'.add_ping',function(){
              _self.settings.chackchange=false;
              $(".list").animate({'left':_self.settings.window_w+'px'},200);
              // location.hash="#line=1"
              $('.my_reply').animate({'left':'0px'},200,function(){
                  $(".list").hide()
                  $(".my_reply").show()
                  _self.settings.chackchange=true;
                  _self.settings.hashchange=1;
              })
            })
            $('.weui-textarea').bind(_self.settings.input ,function(){
                if($.trim($('.weui-textarea').val())!=''){
                    console.log($('.weui-textarea').val())
                    if($('.weui-textarea').val().length>50){
                        $('.weui-textarea').val($('.weui-textarea').val().substring(0,49))
                        $(".num").html(50)
                    }else{
                        $(".num").html($('.weui-textarea').val().length)
                    }

                }
            })
            $(document).on("click",'.weui-btn_primary',function(){
                if(!_self.validate($('.weui-textarea').val(),'g')){
                    alert("评论不能为空", 'error')
                    return false
                }
                var loading = _self.settings.loading;
                if (loading) return;
                _self.settings.loading = true;
                var datas={
                    reply_content:$('.weui-textarea').val(),
                    reply_id:_self.settings.reply_id
                }
                layer.open({type: 2,content: '加载中',shadeClose:false});
               // layer.open({type: 2,content: '评论中',shadeClose:false});
                $.ajax({
                    url: "{pigcms{:U('Employee/employee_reply_comment')}",
                    async: false,//这一步是非常重要的，作用是设置为同步执行
                    dataType : "json",
                    data:datas,
                    type : "get",
                    success: function (data) {
                        console.log('data',data);
                        layer.closeAll();
                        if (data.status == 0) {
                            layer.open({
                                content:data.info
                                ,time: 2

                            });
                            setTimeout(function(){
                                _self.settings.initfresh=true
                                window.location.reload();
                            },2000)

                        } else {
                            layer.open({
                                content:data.info
                                ,time: 2
                            });
                        }
                        _self.settings.loading = false;
                    },
                    error:function(data){
                        layer.closeAll();
                        _self.settings.loading = false;
                    }
                });
            })


            $(window).on('hashchange',function(e){
                if( _self.settings.chackchange){
                    if(_self.settings.hashchange==1){
                        $(".my_reply").hide();
                        $('.weui-textarea').val('')
                        $(".list").show().css('left','0px')
                        _self.settings.chackchange=false;
                        if(_self.settings.initfresh){
                            _self.settings.initfresh=false;
                            _self.resetInfinite(true,true);
                            _self.requestData(false);

                        }
                    }
                }


            })


        },
        initImg:function(){
            var _self=this;
            if(this.settings.w_3==0){
                if($(".pics img").size()>0){
                    $(".pics img").each(function(){
                        _self.settings.w_3=$(this).width()
                         $(this).css("height",$(this).width())
                    })
                }
            }
        }
    }
    window.View=View;
    View.init()

</script>
</html>