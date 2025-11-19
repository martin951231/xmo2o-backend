<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Expires" content="-1" />
    <meta http-equiv="Cache-Control" content="no-cache" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta name="format-detection" content="telephone=no" />
    <meta charset="utf-8" />
    <title>评论列表</title>
    <link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/style.css"  rel="stylesheet" />
    <link href="./tpl/Wap/default/static/bbs/css/iscroll.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/swiper.min.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/shop_manger_penci.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/photo.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/collage_list_detail.css" rel="stylesheet" />
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
    <script>var $home=false</script>
    <script src="{pigcms{$static_path}mall/js/cate_common.js" type="text/javascript" ></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/swiper.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
    <script src="{pigcms{$static_path}mall/js/cate_complite.js" type="text/javascript" ></script>
    <script src="{pigcms{$static_path}mall/js/mall_reply.js" type="text/javascript" ></script>
    <script>
        //获取商品评论信息
        var shopReplyUrl="{pigcms{$config.site_url}/index.php?g=Index&c=Reply&a=ajax_get_list&order_type=3&parent_id={pigcms{$store['store_id']}&goods_id={pigcms{$_GET['goods_id']}";
    </script>
</head>
<body style="overflow-x: hidden;padding-top: 55px;">

    <div class="ks-clear ui-border-b comment_header">
        <div class="left" id='all_count'>
            <div>全部</div>
            <div class="font_size12 num">0</div>
        </div>
        <div class="left" id='good_count'>
            <div>好评</div>
            <div class="font_size12 num">0</div>
        </div>
        <div class="left" id='common_count'>
            <div>中评</div>
            <div class="font_size12 num">0</div>
        </div>
        <div class="left" id='bad_count'>
            <div>差评</div>
            <div class="font_size12 num">0</div>
        </div>
    </div>

    <div class="content2 ping bg_f commentDetail">
        <div class="demo-block">
            
        </div>
    </div>
</body>
<script id="shopReplyTpl" type="text/html">
    {{# for(var i = 0, len = d.length; i < len; i++){ }}
        <div class="pd10 font_size14 color_3 ui-border-b comment_item" >
            <div class="flex pb10">
                 <div class="flex_one">
                     <span>{{ d[i]['nickname'] }}</span>
                     <div class="star">
                        <p tip="{{ d[i]['score'] }}"></p>
                     </div>
                     
                 </div>
                 <div>
                    <span>{{ d[i]['add_time'] }}</span>
                 </div>
            </div>
            <p class="pb10">{{ d[i]['comment'] }}</p>
            {{# if(d[i]['pics']){ }}
                <ul class="ks-clear pb5 pics" >
                    {{# for(var j=0;j<d[i]['pics'].length;j++){ }}
                        <li class="left">
                            <img class="pr5 pb5" data-index="{{j}}"  style="width: 100px;height: 100px;display: block;" src="{{ d[i]['pics'][j]['s_image'] }}" data-pics="{{ d[i]['pics'][j]['image'] }}" />
                        </li>
                    {{# } }}
                </ul>
            {{# } }}
            <div class="color_9 font_size12">
                <span>{{ d[i]['add_time'] }}</span>
                {{# if(d[i]['spec']){ }}
                    <span>|</span>
                    <span>{{ d[i]['spec'] }}</span>
                {{# } }}
            </div>
        </div>
    {{# } }}
</script>
<script>
    var View={
        init:function(){
            this.initClick();
        },
        
        initClick:function(){
            var _self=this;            
            //全部
            $('#all_count').on('click',function(){
                if($(this).hasClass('color_red')){
                    return false;
                }
                $(this).addClass('color_red').siblings().removeClass('color_red');
                now_page=0;
                count=0;
                param={};
                getList();
            });
            $('#good_count').on('click',function(){
                if($(this).hasClass('color_red')){
                    return false;
                }
                $(this).addClass('color_red').siblings().removeClass('color_red');
                param={};
                now_page=0;
                count=0;                
                param['tab']='high';
                getList();
            });
            $('#common_count').on('click',function(){
                if($(this).hasClass('color_red')){
                    return false;
                }
                $(this).addClass('color_red').siblings().removeClass('color_red');
                param={};
                now_page=0;
                count=0;

                param['tab']='mid';
                getList();
            });
            $('#bad_count').on('click',function(){
                if($(this).hasClass('color_red')){
                    return false;
                }
                $(this).addClass('color_red').siblings().removeClass('color_red');
                param={};
                now_page=0;
                count=0;

                param['tab']='low';
                getList();
            });
            var type="{pigcms{$_GET.type}";
            if(type=='good'){
                $('#good_count').click();
            }else if(type=='common'){
                $('#common_count').click();
            }else if(type=='bad'){
                $('#bad_count').click();
            }else{
                $('#all_count').click();
            }

            /*预览图片*/
            $("body").on("click",".pics img",function(){
                var album_array = [];
                var  index=parseInt($(this).attr('data-index'));
                $(this).closest(".pics").find('img').each(function(n,v){
                    var pics=$(this).data('pics');
                    if(pics){
                        album_array[n]=pics;
                    }
                });
                var pb1 = APP.Photos({
                    items:album_array,
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
                pb1.open(index);
            });
        },
    }
    window.View=View;
    View.init()

    // 关闭图片大图浏览
    function close_swiper(){
        $('.albumContainer').remove();
    }
</script>
{pigcms{$hideScript}
</html>