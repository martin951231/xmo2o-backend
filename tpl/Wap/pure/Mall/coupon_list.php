<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Expires" content="-1">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Pragma" content="no-cache">
    <meta name="format-detection" content="telephone=no">
    <meta charset="utf-8">
    <title>我的优惠劵</title>
    <link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/cate_common.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/my_cate.css" rel="stylesheet">
    <script src="{pigcms{$static_path}mall/js/jquery.min.js"></script>
</head>
<body>
<div class="">
    <div id="header" class="ui-header  my_ping bg_f">
        <div class="flex">
            <div class="flex_one rel cursor <if condition="$_GET['type'] neq 'mer'">ping</if> link"  data-url="{pigcms{:U('Mall/coupon_list')}&type=sys">平台优惠券</div>
            <div class="flex_one rel cursor <if condition="$_GET['type'] eq 'mer'">ping</if> link" data-url="{pigcms{:U('Mall/coupon_list')}&type=mer">商家优惠券</div>
        </div>
    </div>
    <div class=" content pd-t45" style="overflow:hidden">
        <div id="my_ping" class="my_ping ">
            <div class="flex align_center header-one ui-border-tb ">
                <div class="flex_one rel color_f cursor"  data-use="0">未使用({pigcms{$card_info['0']|intval})</div>
                <div class="flex_one rel cursor" data-use="1">已过期({pigcms{$card_info['2']|intval})</div>
                <div class="flex_one rel cursor" data-use="2">已使用({pigcms{$card_info['1']|intval})</div>
            </div>
            <div class="Coupon ">
                <ul style="width:300%" class=" ui-tab-content">
                    <li>
                        <div class="ui_01">
                            <ul class="end_ul bg_f">
                                <volist name="coupon.0" id="vo">
                                    <li class="li1">
                                        <div class="Muse">
                                            <div class="dd">
                                                <div class="Coupon_top clr use1">
                                                    <div class="fl">
                                                        <div class="fltop">
                                                            <if condition="$vo.is_discount eq 1">{pigcms{$vo.discount_value|floatval}折<else /><i>{pigcms{$config['Currency_symbol']}</i><em>{pigcms{$vo.discount}</em></if>
                                                        </div>
                                                        <div class="flend">
                                                            <if condition="$vo.is_discount eq 1">
                                                                <php>if($vo['order_money']>0){</php>满{pigcms{:floatval($vo['order_money'])}打<php>}</php>{pigcms{$vo.discount-value|floatval}折
                                                                <else />
                                                                满{pigcms{:floatval($vo['order_money'])}减{pigcms{$vo.discount}
                                                            </if>
                                                        </div>
                                                    </div>
                                                    <div class="fr">
                                                        <h2>{pigcms{$vo.name} <php>if($_GET['type']=='mer'){</php>({pigcms{$vo.merchant})<php>}</php></h2>
                                                        <p>使用平台：{pigcms{$vo.platform}</p>
                                                        <p>使用类别：<php>if($vo['cate_name']=='all'){echo "所有";}else{</php>{pigcms{$vo.cate_name}<php>}</php></p>
                                                    </div>
                                                </div>
                                                <div class="Coupon_end">
                                                    <div class="Coupon_x">
                                                        <i>{pigcms{$vo.start_time|date='Y年m月d日',###}至{pigcms{$vo.end_time|date='Y年m月d日',###}</i>

                                                        <a href="{pigcms{$vo.url}"><em>立即使用</em></a>

                                                    </div>
                                                    <div class="Coupon_sm">
                                                        <span class="on">使用说明</span>
                                                        <div class="Coupon_text overflow">测试</div>
                                                    </div>
                                                </div>
                                                <span class="several">{pigcms{$vo.get_num}张</span>
                                                <i class="bj"></i>
                                            </div>
                                        </div>
                                    </li>
                                </volist>
                            </ul>
                            <div class="more  more_juan">
                                <if condition="$_GET['type'] neq 'mer'">
                                    <a href="/wap.php?g=Wap&c=Systemcoupon&a=index">更多好券，去领券中心看看<span></span></a>
                                    <else />
                                    <a href="/wap.php?g=Wap&c=My_card&a=merchant_coupon_list&all_mer=1">更多好券，去领券中心看看<span></span></a>
                                </if>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="ui_02" >
                            <ul class="end_ul bg_f">
                                <volist name="coupon.2" id="vv">
                                    <li class="li2">
                                        <div class="Muse">
                                            <div class="dd">
                                                <div class="Coupon_top clr use2">
                                                    <div class="fl">
                                                        <div class="fltop">
                                                            <if condition="$vv.is_discount eq 1">{pigcms{$vv.discount_value|floatval}折<else /><i>{pigcms{$config['Currency_symbol']}</i><em>{pigcms{$vv.discount}</em></if>
                                                        </div>
                                                        <div class="flend">
                                                            <if condition="$vv.is_discount eq 1">
                                                                <php>if($vv['order_money']>0){</php>满{pigcms{:floatval($vv['order_money'])}打<php>}</php>{pigcms{$vv.discount-value|floatval}折
                                                                <else />
                                                                满{pigcms{:floatval($vv['order_money'])}减{pigcms{$vv.discount}
                                                            </if>
                                                        </div>
                                                    </div>
                                                    <div class="fr">
                                                        <h2>{pigcms{$vv.name} <php>if($_GET['type']=='mer'){</php>({pigcms{$vv.merchant})<php>}</php> </h2>
                                                        <p>使用平台：{pigcms{$vv.platform}</p>
                                                        <p>使用类别：<php>if($vv['cate_name']=='all'){echo "所有";}else{</php>{pigcms{$vv.cate_name}<php>}</php></p>
                                                    </div>
                                                </div>
                                                <div class="Coupon_end">
                                                    <div class="Coupon_x">
                                                        <i>{pigcms{$vv.start_time|date='Y年m月d日',###}至{pigcms{$vv.end_time|date='Y年m月d日',###}</i>

                                                      

                                                    </div>
                                                    <div class="Coupon_sm">
                                                        <span class="on">使用说明</span>
                                                        <div class="Coupon_text overflow">测试</div>
                                                    </div>
                                                </div>
                                                <span class="several">{pigcms{$vv.get_num}张</span>
                                                <i class="bj"></i>
                                            </div>
                                        </div>
                                    </li>
                                </volist>
                            </ul>
                            <div class="more  more_juan">

                                <p class="rel history align_center">
                                    <span class=" color_9">以上是近期使用的优惠劵</span>
                                </p>

                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="ui_03" >
                            <ul class="end_ul bg_f">
                                <volist name="coupon.1" id="voo">
                                    <li class="li3">
                                        <div class="Muse">
                                            <div class="dd">
                                                <div class="Coupon_top clr use3">
                                                    <div class="fl">
                                                        <div class="fltop">
                                                            <if condition="$voo.is_discount eq 1">{pigcms{$voo.discount_value|floatval}折<else /><i>{pigcms{$config['Currency_symbol']}</i><em>{pigcms{$voo.discount}</em></if>
                                                        </div>
                                                        <div class="flend">
                                                            <if condition="$voo.is_discount eq 1">
                                                                <php>if($voo['order_money']>0){</php>满{pigcms{:floatval($voo['order_money'])}打<php>}</php>{pigcms{$voo.discount-value|floatval}折
                                                                <else />
                                                                满{pigcms{:floatval($voo['order_money'])}减{pigcms{$voo.discount}
                                                            </if>
                                                        </div>
                                                    </div>
                                                    <div class="fr">
                                                        <h2>{pigcms{$voo.name} <php>if($_GET['type']=='mer'){</php>({pigcms{$voo.merchant})<php>}</php></h2>
                                                        <p>使用平台：{pigcms{$voo.platform}</p>
                                                        <p>使用类别：<php>if($voo['cate_name']=='all'){echo "所有";}else{</php>{pigcms{$voo.cate_name}<php>}</php></p>
                                                    </div>
                                                </div>
                                                <div class="Coupon_end">
                                                    <div class="Coupon_x">
                                                        <i>{pigcms{$voo.start_time|date='Y年m月d日',###}至{pigcms{$voo.end_time|date='Y年m月d日',###}</i>
                                                    </div>
                                                    <div class="Coupon_sm">
                                                        <span class="on">使用说明</span>
                                                        <div class="Coupon_text overflow">测试</div>
                                                    </div>
                                                </div>
                                                <span class="several">{pigcms{$voo.get_num}张</span>
                                                <i class="bj"></i>
                                            </div>
                                        </div>
                                    </li>
                                </volist>
                            </ul>
                            <div class="more  more_juan">

                                <p class="rel history align_center">
                                    <span class=" color_9">以上是近期使用的优惠劵</span>
                                </p>

                            </div>
                        </div>
                    </li>
                </ul>



            </div>
        </div>
    </div>
</div>
<script>var $home=false</script>
<script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
<script>
    var  View={
        init:function(){
            this.checkClick()
        },
        setting:{
            is_use:1
        },

        checkClick:function(){
            var _self=this;
            $(document).on("click",'#my_ping .flex_one',function(){
                $('#my_ping .flex_one').removeClass('color_f');
                $(this).addClass("color_f")
                var is_use=parseInt($(this).attr("data-use"));
                $('.ui-tab-content').css({
                    'transform':'translate3d(-'+(is_use*$('.ui-tab-content li').width())+'px,0,0)',
                    'transition':'transform 0.5s linear'
                })

            });
            $(document).on("click",'#header .flex_one',function(){
                $('#header .flex_one').removeClass('ping');
                $(this).addClass("ping");
            });
            
            $(document).on('click','.link',function(){
                APP.fnUrlReplace($(this).data('url'))

            });
        }
    }
    window.View=View;
    View.init();
</script>
{pigcms{$hideScript}
</body>
</html>