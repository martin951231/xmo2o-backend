<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>全部评论</title>
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
        }
        .border_radius{
            border-radius: 50%;
        }
    </style>
</head>
<body class="pb0">
    <if condition="!$is_weixin_browser && !$is_app_browser">
        <div class="weui-pull-to-refresh__layer">
        </div>
        <header class="x_header bgcolor_11 cl f15" >
            <a class="z f14" href="javascript:window.history.go(-1);"><i class="iconfont icon-fanhuijiantou w15"></i>返回</a>
            <div class="navtitle">全部评论</div>
        </header>
    </if>
    <div class="page__bd" style="background-color: #F9F9F9;">
        <div class="weui-cells mg_t0">
             <div class="weui-cell">
                 <ul class="ping_fen">
                     <volist name="info" id="item">
                         <li class="left cursor <if condition="$key eq 0">active</if>" data-type="{pigcms{$item['type']}" data-type_id="{pigcms{$item['type_id']}">{pigcms{$item.msg}({pigcms{$item.count})</li>
                     </volist>
                 </ul>
             </div>
        </div>
        <div class="weui-panel weui-panel_access before">
            <div class="weui-panel__bd" id="showDetail">
                <volist name="list['list']" id="item">
                    <div class="weui-media-box weui-media-box_appmsg flex_star">
                        <div class="weui-media-box__hd">
                            <if condition="$vo.avatar neq ''">
                                <img class="weui-media-box__thumb border_radius" src="{pigcms{$vo.avatar}" alt="" />
                                <else/>
                                <img class="weui-media-box__thumb border_radius" src="{pigcms{$config['site_url']}/tpl/Wap/pure/static/images/new_my/pic-default.png" alt="" />
                            </if>
                        </div>
                        <div class="weui-media-box__bd w_3">
                            <h4 class="weui-media-box__title color_9 font_b font_size15 flex">
                                <span class="flex_one">{pigcms{$item.nickname}</span>
                                <span class="flex_one time">{pigcms{$item['add_time']|date="Y-m-d H:i:s",###}</span>
                            </h4>
                            <div class="reply_info weui-media-box__desc">
                                <div class="rateInfo">
                                    <div class="starIconBg">
                                        <div class="starIcon" style="width:{pigcms{$item['start']}%;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="weui-media-box__desc">{pigcms{$item.reply_content}</p>
                            <div class="clear pics">
                                <volist name="item['image']" id="voo">
                                    <img class="weui-media-box__thumb cursor" data-img="{pigcms{$voo}" data-index="{pigcms{$key}" src="{pigcms{$voo}" alt=""  style="margin-right: 2%; width: 31%;margin-top:2%;display:block;float:left;"/>
                                </volist>
                            </div>
                            <div class="clear"></div>
                            <p class="weui-media-box__desc " style="text-align:right">
                                <a href="{pigcms{:U('employee_reply_comment_list',array('reply_id'=>$item['id']))}">
                                       <span class="iconfont comments icon-huifu" style=" font-size: 15px;"></span >\
                                      <span style="padding-left:3px">评论</span>
                                </a>

                            </p>
                        </div>
                    </div>
                    <div class="weui-cell">
                        <ul class="memory_fen">
                            <volist name="item['memory_info']" id="vo">
                                <li class="left cursor" data-type="memory" data-type_id="{pigcms{$vo['memory_id']}">{pigcms{$vo.memory_name}</li>
                            </volist>
                        </ul>
                    </div>
                </volist>
            </div>
            <div class="weui-loadmore none show" id='weui-loadmore'>
                <i class="weui-loading"></i>
                <span class="weui-loadmore__tips">正在加载</span>
            </div>
        </div>
    </div>
</body>
<script type="text/html" id="detail">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var _item=_this[i] &>
    <div class="weui-media-box weui-media-box_appmsg  flex_star">
        <div class="weui-media-box__hd">
             <img class="weui-media-box__thumb border_radius" src="<&= _item.avatar&>" alt="" />
        </div>
        <div class="weui-media-box__bd w_3">
            <h4 class="weui-media-box__title color_9 font_b font_size15 flex">
                <span class="flex_one"><&= _item.nickname&></span>
                <span class="flex_one time"><&= _item.add_time_info&></span>
            </h4>
            <div class="reply_info weui-media-box__desc">
                <div class="rateInfo">
                    <div class="starIconBg">
                        <div class="starIcon" style="width:<&= _item.start&>%;">
                        </div>
                    </div>
                </div>
            </div>
            <p class="weui-media-box__desc"><&= _item.reply_content&></p>
            <& if(_item.image && _item.image!=null  && _item.image!='' && _item.image.length>0 ) {&>
            <div class="pics clear">
                <& for(var g=0;g< _item.image.length;g++) {&>
                <& var _item2=_item.image[g] &>
                  <img class="weui-media-box__thumb cursor " data-img="<&= _item2&>" data-index="<&= g&>" src="<&= _item2&>" alt=""  style="margin-right: 2%; width: 31%;margin-top:2%;display:block;float:left;"/>
                <& }&>
            </div>
            <div class="clear"></div>
            <p class="weui-media-box__desc " style="text-align:right">
                <a href="{pigcms{:U('employee_reply_comment_list')}&reply_id=<&= _item.id&>">
                    <span class="iconfont comments icon-huifu" style=" font-size: 15px;"></span >
                    <span style="padding-left:3px">加评/回复</span>
                </a>

            </p>
            <& }&>
        </div>
    </div>
    <& if(_item.memory_info && _item.memory_info!=null && _item.memory_info.length>0 ) {&>
      <div class="weui-cell">
          <ul class="memory_fen">
          <& for(var n=0;n< _item.memory_info.length;n++) {&>
           <& var _items=_item.memory_info[n] &>
              <li class="left cursor" data-type="memory" data-type_id="<&= _items.memory_id&>"><&= _items.memory_name&></li>
          <& }&>
          </ul>
      </div>
    <& }&>

    <& }&>
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
<script src="{pigcms{$static_path}js/layer.m.js" type="text/javascript"></script>
<script src="{pigcms{$static_path}employee/js/common/simplite.js" type="text/javascript" charset="UTF-8"></script>
<include file="big_img"/>
<script type="text/javascript">
    //点赞
    var View={
        init:function(){
             this.initRefresh();
              this.checkClick()
        },
        settings: {
            pageNo: 1,
            pageSize: 10,
            totalPages: 0,
            type:'',
            type_id:'',
            img_h:0
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
        img_h:function(){
            var self=this;
            if($(".w_3").size()>0 && this.settings.img_h==0){
                console.log($(".w_3").eq(0).width())
                self.settings.img_h=$(".w_3").eq(0).width()*0.31+'px';
                console.log(self.settings.img_h)
                $('.pics img').css('height',self.settings.img_h)
            }else{
                $('.pics img').css('height',self.settings.img_h)
            }
        },
        initRefresh: function() {
            var self = this;
            this.img_h()
            var loading = false;  //状态标记
            var $body = $('body');
            var employee_id = $.getUrlParam('employee_id');
            var resetInfinite = self.resetInfinite = function(init,refresh){
                $body[ init ? 'infinite' : 'destroyInfinite' ]();
                $body.find('.show')[ init ? 'removeClass' : 'addClass' ]('none');
            };
            var requestData = self.requestData = function(type){
                //layer.open({type: 2,content: '加载中',shadeClose:false});
                if (loading) return;
                loading = true;
                var datas={
                    page:self.settings.pageNo,
                    employee_id:employee_id,
                    type:self.settings.type,
                    type_id:self.settings.type_id
                }
                $.ajax({
                    url: "{pigcms{:U('Employee/reply_more_ajax')}",
                    async: false,//这一步是非常重要的，作用是设置为同步执行
                    dataType : "json",
                    data:datas,
                    type : "get",
                    success: function (data) {
                        var index = Simplite.getTemplate('detail');
                        var list=[];
                        self.settings.totalPages=data.info.totalPage;
                        if(data.info && data.info.list && data.info.list!=null && data.info.list.length>0){
                            list=data.info.list
                        }
                        if(self.settings.pageNo==1){
                            $("#showDetail").html('')
                        }
                        var indexTpl = Simplite.compile(index)(list);
                        if(type){
                            $("#showDetail").html(indexTpl);
                        }else{
                            $("#showDetail").append(indexTpl);
                        }
                        loading = false;
                        layer.closeAll();
                        self.img_h()
                    },
                    error:function(data){
                        layer.closeAll();
                        loading = false;
                    }
                });
            }
            $body.on("infinite", function() {
                self.settings.pageNo++;
                requestData(false);
            });
            resetInfinite(true,true);
            var reload=function(){
                var h=$(document).height();
                var wh=$(window).height();
                if(h<=wh){
                    requestData(false);
                }
            }
            reload()



        },
        checkClick:function(){
            var _self=this;
            $(document).on("click",'.ping_fen li',function(){
                $(".ping_fen li").removeClass("active");
                $(this).addClass("active");
                var type_id=$(this).attr('data-type_id');
                var type=$(this).attr('data-type');
                _self.settings.type=type;
                _self.settings.type_id=type_id;
                _self.settings.pageNo=1;
                layer.open({type: 2,content: '加载中',shadeClose:false});
                _self.resetInfinite(true,true);
                _self.requestData(true);
            })


        }
    }
    window.View=View;
    View.init()

</script>
</html>