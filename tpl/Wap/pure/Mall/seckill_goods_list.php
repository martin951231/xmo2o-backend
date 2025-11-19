<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta http-equiv="Expires" content="-1" />
    <meta http-equiv="Cache-Control" content="no-cache" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta name="format-detection" content="telephone=no" />
    <meta charset="utf-8" />
    <title>{pigcms{$config.seckill_alias_name|default='限时优惠'}</title>
    <link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/cate_common.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/discount.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/pullToRefresh.css" rel="stylesheet" />

    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}shop/css/shopBase.css?t={pigcms{$_SERVER.REQUEST_TIME}"/>
    <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/idangerous.swiper.min.js" charset="utf-8"></script>

    <style type="text/css">
        .ui-border-b{position: relative;}
        .jump_box{position:absolute;left:0;top:0;width:100%;height:100%;z-index: 1;}
        .jump_box.bespoke{width:74%;}
        #pageLoadTip{display:none !important;}
        .ui-progress{background-color:#ededed;height: 8px;width: 55%;max-width: 200px;display: inline-block;}
        .ui-progress span{background:#ec2e53;border-radius: 5px;}
        .ui-list-img-square{width: 85px;height: 85px;}
        .ui-list-info{padding:12px 12px 12px 0;}
        .pd1{padding:1px 2px;}

    </style>
    <!-- 关注提示 -->
    <style type="text/css">
        .black_bg{
            position: fixed;
            top:0;
            left:0;
            width:100%;
            height: 100%;
            background: rgba(0,0,0,.6);
            display:none;
        }
        .tip_box{
            position: absolute;
            width:80%;
            left:10%;
            top:-100%;
            margin-top: -50%;
            text-align: center;
        }
        .tip_box .box{
            background:#fff;
            padding:10px;
            box-sizing:border-box;
        }
        .tip_box .box img{
            display:block;
            width:100%;
        }
        .tip_box .close{
            position: relative;
            width:30px;
            height: 30px;
            border:1px solid #fff;
            border-radius: 15px;
            box-sizing:border-box;
            display:inline-block;
            margin-top: 10%;
            transform: rotate(45deg);
        }
        .tip_box .close:before,.tip_box .close:after{
            position:absolute;
            background: #fff;
            content: '';
        }
        .tip_box .close:before{
            width:1px;
            height: 16px;
            top:6px;
            left:13.5px;
        }
        .tip_box .close:after{
            width:16px;
            height: 1px;
            top:13.5px;
            left:6px;
        }
    </style>
</head>
<body>
<section class="homepage" id="listBanner">
    <div class="swiper-container swiper-container-horizontal">
        <div class="swiper-wrapper">
            <volist name="wap_seckill_top" id="banner">
                <div class="swiper-slide">
                    <a href="{pigcms{$banner['url']}">
                        <img src="{pigcms{$banner['pic']}" width="100%">
                    </a>
                </div>
            </volist>
        </div>
        <div class="swiper-pagination"></div>
    </div>
    <script type="text/javascript">
        var myswiper = new Swiper('.swiper-container', {
            pagination: '.swiper-container .swiper-pagination',
            direction : 'horizontal',
            paginationClickable :true,
            autoplay :'1000',
            autoplayDisableOnInteraction : false,
            loop: true
        });

        $(".swiper-wrapper .swiper-slide").each(function(){
            $(".swiper-container").height($(this).width()*0.375);
            $(".swiper-wrapper").height($(this).width()*0.375);
            $(".swiper-slide").height($(this).width()*0.375);
        })
    </script>
</section>
<div class="content pageSliderHide">
    <div class="demo-block">
        <div class="demo-block ping_tuan" id="refresh" style="">
            <ul class="ui-list" id="ui_list">

            </ul>
        </div>
    </div>
</div>
<notempty name="wap_seckill_top">
    <style>
        #refresh{
            top:145px;
            bottom:0px;
        }
    </style>
    <else/>
    <style>
        #refresh{
            top:0px;
            bottom:0px;
        }
    </style>
</notempty>
<script type="text/html" id="temp_list">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var item=_this[i] &>
    <li class="ui-border-b ">
        <& if(item.seckill_status==0){ &>
        <& if(item.is_bespoke==0 || !item.is_bespoke){ &>
        <div class='jump_box bespoke link-url' data-url="{pigcms{:U('Mall/detail')}&goods_id=<&= item.goods_id &>" target="_blank"></div>
        <& }else{ &>
        <div class='jump_box link-url' data-url="{pigcms{:U('Mall/detail')}&goods_id=<&= item.goods_id &>" target="_blank"></div>
        <& } &>
        <& }else{ &>
        <div class='jump_box link-url' data-url="{pigcms{:U('Mall/detail')}&goods_id=<&= item.goods_id &>" target="_blank"></div>
        <& } &>
        <div class="ui-list-img-square">
                    <span class="rel">
                        <img src="<&= item.image &>" class="w100 h100 img">
                    </span>
        </div>
        <div class="ui-list-info ">
            <h4 class="ui-nowrap font_size14 overflow_auto"><&= item.name &></h4>
            <div class="demo-block pb10">
                <ul class="ui-list ui-list-single ui-list-link mb_0 ">
                    <li class="after ml_0">
                        <div class="w100 pd0">
                            <div class="ui-txt-info font_size14 color_orgin font-weight ">
                                <h4 class="ui-nowrap money flex_two ">
                                    <& if(item.seckill_status==0){ &>
                                    <p class="align_left color_7 tjt_list1 clock time<&= item.goods_id &>" data-clock="<&= item.time_clock &>" <&= View.times(item.seckill_open_time,item.goods_id ) &>>
                                    <span style="padding-right:7px" class="font_size14">距开始</span>
                                    <span class="day-box">
                                                    <span class="time joind d">00</span>
                                                    <span class="pd0_3 font-weight color_0">天</span>
                                                </span>

                                    <span class="hour-box">
                                                    <span class="time joinh h">00</span>
                                                    <span class="pd0_3 font-weight color_0">:</span>
                                                </span>
                                    <span class="time joinm m">00</span>
                                    <span class="pd0_3 font-weight color_0">:</span>
                                    <span class="time joins s">00</span>
                                    </p>
                                    <& }else{ &>
                                    <div class="ui-progress">
                                        <span style="width:<&= item.percent &>%"  data-scroll-reveal="enter from the left over 1.5s"></span>
                                    </div>
                                    <span class="font_size12" style="color: #ec2e53;">&nbsp;&nbsp;已售<&= item.percent &>%</span>
                                    <& } &>
                                </h4>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="demo-block pd10_0">
                <ul class="ui-list ui-list-single ui-list-link mb_0 ">
                    <li class="after ml_0 ">
                        <div class="flex w100 flex_justify">
                            <& if(item.seckill_status==1){ &>
                            <div class="go_discount_time flex time<&= item.goods_id &>">
                                <img src="./tpl/Wap/pure/static/mall/images/seckill.png" class="" alt="" style="width:20px;margin-right:5px;display:block;height:20px;margin-top:-2px">
                                <span class="font_size13 flex_one clock" style="color:#ec2e53;" data-clock="<&= item.time_clock &>" <&= View.times(item.seckill_close_time,item.goods_id ) &>>
                                <span class="day-box">
                                                <i class='d color_f br2 pd1 joind' style="background-color: #ec2e53;">00</i>
                                                <i class="font_b"> 天 </i>
                                            </span>
                                <span class="hour-box">
                                                <i class='h color_f br2 pd1 joinh' style="background-color: #ec2e53;">00</i>
                                                <i class="font_b"> : </i>
                                            </span>
                                <i class='m color_f br2 pd1 joinm' style="background-color: #ec2e53;">00</i>
                                <i class="font_b"> : </i>
                                <i class='s color_f br2 pd1 joins' style="background-color: #ec2e53;">00</i>
                                </span>
                            </div>
                            <& } &>
                            <h4 class="ui-nowrap font_size12 color_7 align_right ">
                                <& if(item.limit_type==1){ &>
                                限购<span class="color_yellow">&nbsp;<&= item.limit_num &>&nbsp;</span><&= item.unit &>
                                <& } &>

                                <& if(item.limit_type==0){ &>
                                不限购
                                <& } &>
                            </h4>
                        </div>

                    </li>
                </ul>
            </div>
            <div class="demo-block">
                <ul class="ui-list ui-list-single ui-list-link mb_0 ">
                    <li class="after ml_0">
                        <div class="ui-list-info pd0 flex">
                            <div class="ui-nowrap money flex_two" style="color:#ec2e53;">
                                {pigcms{$config['Currency_symbol']}<span class="font_size20"><&= item.seckill_price &></span>
                                <span class="color_9 border_center rel font_size12">{pigcms{$config['Currency_symbol']}<&= item.price &></span>
                            </div>
                            <div class="ui-txt-info  color_fb  pl10 flex_one">
                                <& if(item.seckill_status==0){ &>
                                <& if(is_h5==1){ &>
                                <a href="{pigcms{:U('Mall/detail')}&store_id=<&= item.store_id &>&goods_id=<&= item.goods_id &>" onClick="jump(this);">
                                    <button class="ui-btn ui-btn-primary w100" style="border-radius:100px">抢先试试</button>
                                    <& }else if(item.is_bespoke==0 || !item.is_bespoke){ &>
                                    <button class="ui-btn ui-btn-primary w100 bespoke" style="border-radius:100px" data-goods_id="<&= item.goods_id &>" data-start_time="<&= item.seckill_open_time &>">立即预约</button>
                                    <& }else{ &>
                                    <button class="ui-btn ui-btn-primary w100 bg_pink" style="border-radius:100px">已预约</button>
                                    <& } &>
                                    <& }else if(item.seckill_status==1){ &>
                                    <& if(item.percent==100){ &>
                                    <button class="ui-btn ui-btn-primary w100 bg_999" style="border-radius:100px">已抢完</button>
                                    <& }else{ &>
                                    <a href="{pigcms{:U('Mall/detail')}&store_id=<&= item.store_id &>&goods_id=<&= item.goods_id &>" onClick="jump(this);">
                                        <button class="ui-btn ui-btn-primary w100" style="border-radius:100px">马上抢</button>
                                    </a>
                                    <& } &>
                                    <& }else if(item.seckill_status==2){ &>
                                    <button class="ui-btn ui-btn-primary w100 bg_999" style="border-radius:100px">已结束</button>
                                    <& } &>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </li>
    <&}&>
    <& if(View.isEmpty(_this)){&>
    <li class="w100 align_center ml_0" style="padding-top:15px">
        <div class="weui-loadmore  w100" >
            <div><img src="{pigcms{$static_path}mall/images/noresult.png"/></div>
            <span class="weui-loadmore__tips" style="color: #999">暂无商品</span>
            <p style="    padding-top: 15px;"><a style='border-radius: 100px;    height: auto;  line-height: normal;  padding: 9px 25px;' class="ui-btn ui-btn-primary inline color_f" href="{pigcms{:U('Mall/index')}" target="_blank">去首页逛一逛</a></p>
        </div>
    </li>
    <&}&>
</script>
<div class="black_bg"></div>
<div class="tip_box">
    <div class="box">
        <i></i>
        <p>
            您还没有关注公众号<br/>
            关注后才可以收到微信提醒奥~
        </p>
        <img src="{pigcms{$config.wechat_qrcode}" />
        <eq name="is_weixin_browser" value='1'>
            <p>长按二维码关注</p>
            <else/>
            <p class="font_size12">请长按二维码保存图片后通过扫一扫关注，或直接在微信中搜索“{pigcms{$config.wechat_name}”</p>
        </eq>
    </div>
    <span class='close'></span>
</div>
<script src="{pigcms{$static_path}mall/js/jquery.min.js"></script>
<script>var $home=false</script>
<script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
<script src="{pigcms{$static_path}mall/js/simplite.js"></script>
<script src="{pigcms{$static_path}mall/js/droprefresh.js"></script>
<script src="{pigcms{$static_path}mall/js/pullToRefresh.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
<script>
    var src="{pigcms{:U('Mall/seckill_goods_list',array('store_id'=>$store_id))}";
    var bespoke_src="{pigcms{:U('Mall/add_bespoke_msg',array('store_id'=>$store_id))}";
    var View={
        init:function(){
            this.initList();
            this.initClick();
        },
        setting:{
            page_size:6,
            total_pages:0,
            pageNo: 1,
            times:[],
            scrollReveal:true,
            windowTime:0
        },
        isEmpty: function(list){
            var b = View.setting.pageNo == 1 && !list.length;
            if(b){
                $('body').addClass("bg_f")
            }else{
                $('body').removeClass("bg_f")
            }
            return b;
        },
        times:function(time,id){
            var _this=this;
            var s='time'+id;
            s=APP.timer({
                time: time*1*1000-_this.setting.windowTime,
                onBackTime:function(d){
                    if (d[0] > 0) {
                        $(".time"+id).find(".joind").html(d[0]);
                    } else {
                        $(".time"+id).find(".day-box").css('display', 'none');
                    }

                    if(d[1] > 0) {
                        $(".time"+id).find(".joinh").html(d[1]);
                    } else {
                        $(".time"+id).find(".hour-box").css('display', 'none');
                    }

                    $(".time"+id).find(".joinm").html(d[2]);
                    $(".time"+id).find(".joins").html(d[3]);
                }
            })
            this.setting.times.push(s)
        },
        initList:function(){
            var _self=this;
            refresher.init({
                id:"refresh",
                pullDownAction:Refresh,
                pullUpAction:Load
            });
            function Refresh(){
                APP.destruction(_self.setting.times)
                _self.setting.times=[]
                _self.seckill_goods_list(false,true);
            }
            function Load(){
                _self.seckill_goods_list(true,true);
            }
            _self.seckill_goods_list(false,true);
        },
        initClick:function(){
            $('body').on('click','.bespoke',function(){
                var goods_id=$(this).data('goods_id');
                var start_time=$(this).data('start_time');
                var obj=$(this);
                $.post(bespoke_src,{goods_id:goods_id,start_time:start_time},function(res){
                    if(res.status){
                        obj.removeClass('bespoke').unbind('click');
                        obj.addClass('bg_pink').text('已预约');
                        obj.parents('li').find('.jump_box').removeClass('bespoke');
                    }else{
                        /*弹出关注信息*/
                        if(res.is_follow==0){
                            $('.black_bg').show();
                            $('.tip_box').animate({'top':'50%'});
                        }else{
                            layer.open({
                                content: res.info,
                                skin: 'msg',
                                time: 2, //2秒后自动关闭
                                end:function(){
                                    if(res.url){
                                        window.location.href=res.url;
                                    }
                                }
                            });
                        }
                    }
                });
            });
            $(document).on('click','.tip_box .close',function(){
                $('.tip_box').animate({'top':'-100%'},function(){
                    $('.black_bg').hide();
                });
            });
        },
        seckill_goods_list:function(type,refresh){
            var _this=this;
            if(type){
                _this.setting.pageNo++
            }else{
                _this.setting.pageNo=1
            }
            $.post(src,{page:_this.setting.pageNo},function(data){
                var res=data;
                _this.setting.windowTime=new Date().getTime()
                if(typeof(res)=='string'){
                    res=$.parseJSON(data)
                }
                if(!res.list || res.list.length==0){
                    res.list=[]
                }
                var compiledTpl = Simplite.getTemplate('temp_list');
                var compile_item = Simplite.compile(compiledTpl)(res.list);
                if(!type){
                    $('#ui_list').html(compile_item);
                }else{
                    $('#ui_list').append(compile_item);
                }
                refresher.isEmpty(res.list,_this.setting.pageNo)
                refresher.isNomore(res.list,_this.setting.pageNo,'','',res.list.length==0?true:false)
                setTimeout(function(){
                    myScroll.refresh();
                    if(_this.setting.scrollReveal){
                        _this.setting.scrollReveal=false;

                    }

                },500)
            });
        }
    }
    var time_arr=[];
    window.View=View;
    View.init()
</script>
<script type="text/javascript">
    function jump(e){

        var url=$(e).attr('href');
        window.location.href=url;
    }
    var is_weixin_browser={pigcms{$is_weixin_browser|default=0};
    var is_wxapp_browser={pigcms{$is_wxapp_browser|default=0};
    var is_app_browser={pigcms{$is_app_browser|default=0};
    var is_h5=0;
    if(is_weixin_browser==0 && is_wxapp_browser==0 && is_app_browser==0){
        is_h5=1;
    }
</script>
<script type="text/javascript">
    window.shareData = {
        "moduleName":"Mall",
        "moduleID":"0",
        "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/seckill_goods_list')}",
        "tTitle": "推荐您多款{pigcms{$config.seckill_alias_name|default='限时优惠'}商品",
        "tContent": "点击查看更多进行中{pigcms{$config.seckill_alias_name|default='限时优惠'}商品"
    };
</script>
{pigcms{$shareScript}
</body>
</html>