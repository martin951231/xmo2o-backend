<div class="mask2"></div>
<!-- 店铺 -->
<!-- <div class="business">
    <if condition="$kf_url">
    <div class="manufactor">
        <a href="{pigcms{$kf_url}">联系客服</a>
    </div>
    </if>
    <div class="factor">
        <ul>
            <li><h2>拨打电话</h2></li>
            <volist name="store['phone']" id="phone">
            <li><a href="tel:{pigcms{$phone}">{pigcms{$phone}</a></li>
            </volist>
        </ul>
    </div>
</div> -->
<div class="bg_d pt30 pl30 pr30 pb10 br5 phone_content" style="color: #51c332;z-index:2223;">
    <ul>
        <volist name="store['phone']" id="phone">
            <a href="tel:{pigcms{$phone}">
                <li class="bg_f pt5 pb5 pl10 mb_20 flex br5">
                    <span><i class="icon iconfont font_size28 mg_r5" >&#xe610;</i></span>
                    <span>{pigcms{$phone}</span>                
                </li>
            </a>
        </volist>
         <if condition="$kf_url">
            <a href="{pigcms{$kf_url}" target="_blank">
                <li class="bg_f pt5 pb5 pl10 mb_20 flex br5" style="color: #51c332;">
                    <span><i class="icon iconfont font_size28 mg_r5" >&#xe60e;</i></span>
                    <span>在线客服</span>
                </li>
            </a>
        <else/> 
            <!-- <li class="bg_f pt5 pb5 pl10 mb_20 flex br5" style="color: #000;">
                <span><i class="icon iconfont font_size28 mg_r5" >&#xe60e;</i></span>
                <span>暂未开通在线客服</span>
            </li> -->
        </if>
    </ul>
    <div class="font_size28 color_9 phone_cancel cursor">×</div>
</div>
<section class="Florist">
    <div class="Florist_top clr">
        <a href="{pigcms{:U('Mall/store', array('store_id' => $store['store_id']))}">
            <div class="flex flex_baseline">
                    <img src="{pigcms{$store['image']}" width="48" height="48">
                   <div class="flex_one flex flex_baseline">
                       <div class="p53 flex_one" style="padding-left:10px;height:auto">
                           <h2 style="padding: 6px 0 7px;">{pigcms{$store['name']}</h2>
                           <span class="star">
                                店铺星级：
                                {pigcms{$store['score_mean_html']}  
                           </span>
                       </div>
                       <div class="all" style="position:relative" onclick="location.href='{pigcms{:U('Mall/store', array('store_id' => $store['store_id'],'show_own'=>1))}';return false;">
                           <h3>{pigcms{$goods_count}</h3>
                           <p>全部商品</p>
                       </div>
                   </div>
            </div>


        </a>    
    </div>
    <div class="Florist_end clr flex flex_justify">
        <a href="javascript:void(0);" class="contactlx"><img src="{pigcms{$static_path}mall/images/phone.png">联系商家</a>
        <a href="{pigcms{:U('Mall/store', array('store_id' => $store['store_id']))}" target="_blank"><img src="{pigcms{$static_path}mall/images/shop.png">进店逛逛</a>
    </div>
</section>
<style>
h2{
	padding: 6px 0 7px;
}
.star{
	font-size: 12px;
}
.star i.full {
    background-image: url(./tpl/Wap/pure/static/shop/images/full_star.png);

}
.star i.half {

    background-image: url(./tpl/Wap/pure/static/shop/images/mid_star.png);

}
.star i {

    width: 12px;
    height: 12px;
    display: inline-block;
    background: url(./tpl/Wap/pure/static/shop/images/none_star.png) no-repeat;
        background-image: url("./tpl/Wap/pure/static/shop/images/none_star.png");
        background-size: auto;
    background-size: 100%;
    margin-right: 2px;

}
</style>

<!-- 联系商家 -->
<script type="text/javascript">
    $(function(){
        $(".mask2").height($(window).height());
        $(".contactlx").click(function(){
            var tc=$(".manufactor").height()+$(".factor").height()
            $(".phone_content").show();
            $(".mask2").show();
        })
        $(".mask2,.phone_cancel").click(function(){
            $('.mask2').hide();
            $(".phone_content").hide();
        });
    });    
</script>