<!DOCTYPE html>
<html>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" /> 
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}coupon/css/reset.css" />
    <script src="{pigcms{:C('JQUERY_FILE')}"></script>
    <title>关注有礼</title>
    <body>
        <div class="bg1">
           <h2 class="title">恭喜您</h2> 
           <!--  -->
           <div class="yhq_conbg">
                <div class="yhq">
                    <img src="{pigcms{$static_path}coupon/images/top_yhq.png" style="width: 100%" class="top_yhq">
                    <div class="yhq_box">
                        <volist name="coupon_list" id="vo">
                            <div class="yhq_block">
                                <div class="yhq_block1 fl">
                                    <php>if($vo['is_discount']==1){</php>
                                        <p>&nbsp;<span class="money_til1">{pigcms{$vo.discount_value|floatval}</span>&nbsp;折</p>
                                    <php>}else{</php>
                                        <p>{pigcms{$config['Currency_symbol']}<span class="money_til1">{pigcms{$vo.discount|getFormatNumber=###}</span></p>
                                    <php>}</php>
                                    <p><php>if($vo['is_discount']==1){</php> 满 {pigcms{$vo.order_money|getFormatNumber=###} 打 {pigcms{$vo.discount_value|floatval}折  <php>}else{</php>满 {pigcms{$vo.order_money|getFormatNumber=###} 减 {pigcms{$vo.discount|getFormatNumber=###}<php>}</php></p> 
                                </div>
                                <div class="yhq_block2 fr">
                                    <h2>超市</h2>
                                    <p>
                                        使用平台：<volist name="vo.platform" id="vv">{pigcms{$platform[$vv]}&nbsp;&nbsp;</volist><br>
                                        使用类别：<if condition="$vo.cate_name eq 'all'">全部类别<else />{pigcms{$category[$vo['cate_name']]}</if><br>
                                        <if condition="$vo.cate_id neq '0'">使用分类：{pigcms{$vo['cate_id']}<br></if>
                                        有效期截止{pigcms{$vo['end_time']|date="Y-m-d",###}
                                    </p> 
                                </div>
                                <div class="clear"></div>   
                            </div>
                        </volist>
                    </div> 
                </div>

                <div class="yhq_block_bb">
                    <h2>获得{pigcms{$count}张平台优惠券</h2>
                    <p>已存入您的账户</p>
                </div>

                <a href="{pigcms{:U('Home/index')}" class="btn">
                    <img src="{pigcms{$static_path}coupon/images/btn.png"/>
                </a>

           </div>
           <!-- <div class="hd_box">
               <img src="images/qiqiu.png" class="icon_qiqiu">
                <div class="hd_til">
                    <img src="images/sm_icon.png" class="icon_sm">
                    <span>活动介绍</span>
                </div>
                <p>点击开奖按钮，将会获得随机金额的优惠券一张，并且直接存入您的账户，只可抢一次！</p>
           </div>   -->

           <div class="bt_bg">
             <img src="{pigcms{$static_path}coupon/images/bg2.png" style="width: 100%">
           </div>

        </div> 
       
    </body>
</html>