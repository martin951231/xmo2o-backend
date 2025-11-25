<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <meta http-equiv="Expires" content="-1"/>
    <meta http-equiv="Cache-Control" content="no-cache"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta charset="utf-8" />
    <title>砍价列表</title>
    <link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet"/>
    <link href="{pigcms{$static_path}mall/css/style.css"  rel="stylesheet"/>
    <link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet"/>
    <link href="{pigcms{$static_path}mall/css/iconfont/iconfont.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/pullToRefresh.css" rel="stylesheet"/>
    <link href="{pigcms{$static_path}mall/css/swiper.min.css" rel="stylesheet"/>
    <style>
        body{background:#fff}
        #ui_list li>a{
            position: absolute;
            width: 100%;
            height: 100%;
            top:0;
            left:0;
            z-index:1;
        }
        #pageLoadTip{display:none !important;}
        .scroll-text{width: 100%;height: 30px;}
        .scroll-text .swiper-slide{background:#F87C7E;width: 100%;height: 100%;padding:0 10px;}
        .scroll-text .swiper-slide .avatar{width: 25px;height: 25px;border-radius: 50%;background:green;}
        .scroll-text .swiper-slide .icon{height: 15px;}
        .scroll-text .swiper-slide .goods-name{width: 45%;overflow: hidden;white-space: nowrap;text-overflow: ellipsis;}
        .swiper-container-horizontal>.swiper-pagination{bottom: 0;}
        #refresh{top:0px}

    </style>
</head>
<body>
    <div class="content">
        <div class="demo-block ping_tuan" id="refresh">
            <div  class="  h100" id="ui_list" >
                <div class="swiper-container img-box" >
                    <div class="swiper-wrapper">
                        <volist name='banner_list' id='vo'>
                            <div class="swiper-slide" >
                                <a href="{pigcms{$vo.url}">
                                    <img style="height: 1.4rem;display: block;" src="{pigcms{$vo.pic}" width="100%" class="h100" />
                                </a>
                            </div>
                        </volist>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
                <!-- 存在参与砍价的用户 -->
                <notempty name='list'>
                    <div class="swiper-container scroll-text">
                        <div class="swiper-wrapper">
                            <volist name='list' id='vo2'>
                                <div class="swiper-slide flex font_size12 color_f">
                                    <img class="avatar" src="{pigcms{$vo2.avatar|default=$static_path.'images/new_my/pic-default.png'}" />
                                    <!-- <img class="icon ml5" src="{pigcms{$static_path}mall/images/cart.png"> -->
                                    <span class="ml5">{pigcms{$vo2.nickname}</span>
                                    <!-- <span class="font_size16 font_b" style="color: #ffcc00;">2</span> -->
                                    <span>正在参加砍价活动</span>
                                    <span class="ml10 goods-name">【{pigcms{$vo2.goods_name}】</span>
                                </div>
                            </volist>
                        </div>
                    </div>
                </notempty>
                <ul class="ui-list" id="ui-list-ul">

                </ul>
            </div>
        </div>
    </div>

    <!-- 底部导航 -->
    <div>
        <div class="footerMenu wap footer">
            <ul class="flex ">
                <li class="flex_one">
                    <a class="can active" href="javascript:;" data-url="{pigcms{:U('Mall/cut_goods_list')}">
                        <div class="flex w100 h100"  style="justify-content: center;">
                            <em class="cut_good" style="margin: unset;width:18px;height:18px"></em>
                            <p style="padding:3px 0px 0px 3px">砍价商品</p>
                        </div>
                    </a>
                </li>
                <li class="flex_one">
                    <a class="juan" href="{pigcms{:U('Mall/my_bargain_act')}">
                        <div class="flex w100 h100" style="justify-content: center;">
                            <em class="my_act" style="margin: unset;width:18px;height:18px"></em>
                            <p style="padding:3px 0px 0px 3px">我的砍价</p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <script type="text/html" id="detail">
        <& for(var i=0;i< _this.length;i++) {&>
        <& var item=_this[i] &>
            <li class="block ui-border-t">
                <a href="{pigcms{:U('Mall/detail',array('store_id'=>$store_id))}&goods_id=<&= item.goods_id &>&bar_id=<&= item.bar_id &>"></a>
                <div class="flex flex_baseline">
                    <div class="ui-list-img-square">
                        <span >
                            <img src="<&= item.image &>" class="w100 h100"/>
                        </span>
                    </div>
                    <div class="ui-list-info  flex_one">
                        <h4 class=" font_size16 overflow_ellipsis1 ">
                            <&= item.name &>
                            <& if(item.spec_str){ &>
                                <span class='font_size13 color_7'>(<&= item.spec_str &>)</span>
                            <& } &>
                        </h4>
                        <& if(item.describe && item.describe!='' && item.describe!=null) {&>
                        <div class="join_person flex">
                           <div class="font_size12 color_7 overflow_ellipsis2" style="line-height: normal;">
                               <&= item.describe &>
                           </div>
                       </div>
                        <& } &>
                       <div class="demo-block" style="margin-top:<& if(item.describe && item.describe!=''&& item.describe!=null) {&>5px<&}else{&> 28px<&}&>  "  >
                           <ul class="ui-list ui-list-single ui-list-link mb_0 ">
                               <li class="after ml_0">
                                   <div class="ui-list-info pd0">
                                       <h4 class="ui-nowrap money font_size12">
                                          <div class="color_red"><&= item.bar_total_num &>人正在参加</div>
                                          <div class="color_3">价值<&= item.price &>{pigcms{$config['Currency_txt']}</div>
                                       </h4>
                                       <div class="ui-txt-info font_size14 color_7 mg_t10">
                                            <p>
                                                <& if(item.act_status==0){ &>
                                                    <button class="ui-btn ui-btn-primary" style="border-radius: 20px;background: #ccc;padding: 0px 10px 2px 10px;">未开始</button>
                                                <& }else if(item.act_status==2){ &>
                                                    <button class="ui-btn ui-btn-primary" style="border-radius: 20px;background: #ccc;padding: 0px 10px 2px 10px;">已结束</button>
                                                <& }else if(item.act_status==1){ &>
                                                    <& if(item.has_stock==0){ &>
                                                        <button class="ui-btn ui-btn-primary" style="border-radius: 20px;background: #ccc;padding: 0px 10px 2px 10px;">已砍完</button>
                                                    <& }else{ &>
                                                        <button class="ui-btn ui-btn-primary" style="border-radius: 20px;background: #f23030;padding: 0px 2px 2px 8px;"><&= item.floor_price &>{pigcms{$config['Currency_txt']}砍价<i class="icon iconfont color_f">&#xe616;</i></button>
                                                    <& } &>
                                                <& } &>        
                                            </p>
                                       </div>
                                   </div>
                               </li>
                           </ul>
                       </div>
                    </div>
                </div>
            </li>
        <&}&>
        <& if(View.isEmpty(_this)){&>
        <li class="w100 align_center ml_0" >
            <div class="weui-loadmore  w100" >
                <div><img src="{pigcms{$static_path}mall/images/noresult.png"/></div>
                <span class="weui-loadmore__tips" style="color: #999">暂无砍价商品</span>
            </div>
        </li>
        <&}&>
    </script>
    <script>var $home=false</script>
    <script src="{pigcms{$static_path}mall/js/jquery.min.js"></script>
    <script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
    <script src="{pigcms{$static_path}mall/js/simplite.js"></script>
    <script src="{pigcms{$static_path}mall/js/droprefresh.js"></script>
    <script src="{pigcms{$static_path}mall/js/pullToRefresh.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
    <script type="text/javascript">var noAnimate = true;</script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js"></script>

    <script>
        var src="{pigcms{:U('Mall/cut_goods_list',array('store_id'=>$store_id))}";
        var View={
            inits:function(){
                this.initDetail();
                this.initClick();
            },
            setting:{
                page_size:10,
                total_pages:0,
                pageNo: 1
            },
            isEmpty: function(list){
                var b = View.setting.pageNo == 1 && !list.length;
                return b;
            },
            initDetail:function(){
                var _self=this;
                refresher.init({
                    id:"refresh",
                    pullDownAction:Refresh,
                    pullUpAction:Load
                });
                function Refresh(){
                    _self.cut_goods_list(false,true);
                }
                function Load(){
                    _self.cut_goods_list(true,true);
                }
                _self.cut_goods_list(false,false);
                // 大图轮播
                var wh =$(window).width() * 140 / 375;
                $(".img-box img").css("height",wh+'px');
                if($('.img-box .swiper-slide').length>1){
                    var myswiper = new Swiper('.img-box', {
                        pagination: '.img-box .swiper-pagination',
                        direction : 'horizontal',
                        paginationClickable :true,
                        autoplay :'5000',
                        autoplayDisableOnInteraction : false,
                        loop: true
                    });    
                }
                if($('.scroll-text').length){
                    //文字滚动
                    var myswiper2 = new Swiper('.scroll-text', {
                        direction : 'vertical',
                        slidesPerView: 1,
                        autoplay :'2000',
                        autoplayDisableOnInteraction : false,
                        loop: true
                    });    
                }
            },
            initClick:function(){
                $(document).on('click', '.footerMenu a', function(event){
                    APP.fnUrlReplace($(this).data('url'))
                })

            },
            cut_goods_list:function(more,fresh){
                var _this=this;
                if(more){
                    _this.setting.pageNo++
                }else{
                    _this.setting.pageNo=1
                }
                $.post(src,{page:_this.setting.pageNo,limit: _this.setting.page_size},function(data){
                        var res=data;
                        if(typeof(res)=='string'){
                            res=$.parseJSON(data)
                        }
                        if(res &&  res!=null && res.num*1>0){
                            res=res.list;
                        }else{
                            res=[]
                        }
                        var compiledTpl = Simplite.getTemplate('detail');
                        var compile_item = Simplite.compile(compiledTpl)(res);
                        if(!more){
                            $('#ui-list-ul').html(compile_item);
                        }else{
                            $('#ui-list-ul').append(compile_item);
                        }
                        refresher.isEmpty(res,_this.setting.pageNo)
                        refresher.isNomore(res,_this.setting.pageNo,'','',res.length==0?true:false)
                        setTimeout(function(){
                            myScroll.refresh();
                        },500)

                });
            }
        }
        window.View=View;
        View.inits();
    </script>
<!-- 微信分享 -->
<script type="text/javascript">
    window.shareData = {
        "moduleName":"Mall",
        "moduleID":"0",
        "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/cut_goods_list')}",
        "tTitle": "推荐您多款砍价商品",
        "tContent": "点击查看更多进行中砍价商品"
    };
</script>
{pigcms{$shareScript}
</body>
</html>