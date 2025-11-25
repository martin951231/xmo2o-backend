<style>
    #Recommend li {
        float: left;
        position: relative;
        width: 32%;
        margin-top:2%;
        margin-right:2%;
    }
    #Recommend li:nth-child(3n){
        margin-right: 0;
    }
    #Recommend li img{
        width:100%;
        border-radius: 5px;
    }
    #Recommend {
        height: auto;
        overflow: hidden;
    }
    #Recommend  h4{
        padding-top:0px;
        margin-top: 5px;
    }
    .lookMore>li:after {
        background: url({pigcms{$static_path}mall/images/lookMore.png) no-repeat !important;

        right: 13px;

        margin-top: -8px;
        width: 14px;
        height: 14px;
     
    }
    .tip_yu{
        position: absolute;
        bottom: 0;
        left: 0;
        height: 30px;
        line-height: 30px;
        color: #fff;
        width: 100%;
        text-align: center;
        z-index: 999;
        background-color: #06c1ae;
        opacity:0.85
    }
    #pageLoadTip{display:none !important;}
</style>
<!-- 店铺推荐 -->
<notempty name='recommend_list'>
<div class="demo-block" id="">
    <div class="have_replay" >
        <ul class="ui-list ui-list-single ui-list-link ui-border-t mb_0 lookMore">
            <li class="ui-border-t after_color_red">
                <div class="ui-list-info">
                    <h4 class="ui-nowrap font_size14">
                        店铺推荐
                        <a href="{pigcms{:U('Mall/store',array('store_id'=>$store['store_id']))}" target="_blank">
                            <span class="color_red pl10"  style="  float: right;">查看全部</span>
                        </a>
                    </h4>
                </div>
            </li>
        </ul>
    </div>
</div>
<div style="padding:0px 10px;background: #fff;">
    <ul id="Recommend" class="clr pb10" style="clear:both">
        <volist name='recommend_list' id='re'>
            <li>
                <a href="{pigcms{:U('Mall/detail',array('goods_id'=>$re['goods_id']))}" target="_blank">
                    <div class="pt0 pb0 bg_f">
                        <div class="">
                            <div class="ui-img-vertical" style="padding-top:100%">
                                <span class="pd0">
                                    <img src="{pigcms{$re.image}">
                                <if condition="$re.book_record eq 1">
                                    <div class="tip_yu">预售</div>
                                </if>
                                </span>
                            </div>
                            <div class="ui-grid-info  m_0  ">
                                <h4 class="ui-nowrap-multi pl5">{pigcms{$re.name}</h4>
                                <p class="pl5 pb0">
									<if condition="$re.price_type eq 3">
										<span class="font_size18 color_red font-weight">				
										{pigcms{$re.account_score}{pigcms{$config.score_name}
										</span>
									<elseif condition="$re.price_type eq 2"/>
										<span class="font_size18 color_red font-weight">
                                            {pigcms{$config['Currency_symbol']}{pigcms{$re.account_money}+{pigcms{$re.account_score}{pigcms{$config.score_name}
										</span>
									<elseif condition="$re.price_type eq 1"/>
										<span class="font_size18 color_red font-weight">
                                            {pigcms{$config['Currency_symbol']}{pigcms{$re.account_money}
										</span>
									<else/>
										<span class="font_size18 color_red font-weight">{pigcms{$config['Currency_symbol']}{pigcms{$re.price}</span>
									</if>
                                </p>
                            </div>
                        </div>
                    </div>
                </a>
            </li>
        </volist>
    </ul>
</div>
</notempty>
<script type="text/javascript">
    $(function(){
         for( var i  in $("#Recommend li") ){
             $("#Recommend li").eq(i).find("img").attr("height",$("#Recommend li").eq(i).find("img").width())
              if((i+1)%3==0){
                    var $hh=0;
                   var $h1= $("#Recommend li").eq((i-2)).find("h4").height();
                   var $h2= $("#Recommend li").eq((i-1)).find("h4").height();
                   $hh=$h1>$h2?$h1:$h2;
                   var $h3= $("#Recommend li").eq(i).find("h4").height();
                   $hh=$hh>$h3?$hh:$h3;
                  $("#Recommend li").eq((i-2)).find("h4").css({
                      'height':$hh+'px'
                  })
                  $("#Recommend li").eq((i-1)).find("h4").css({
                      'height':$hh+'px'
                  });
                  $("#Recommend li").eq(i).find("h4").css({
                      'height':$hh+'px'
                  });
              }else{
                   if((i+1)==$("#Recommend li").length && (i+1)%2!=0){
                       var $hh=0;
                       var $h1= $("#Recommend li").eq((i-1)).find("h4").height();
                       var $h= $("#Recommend li").eq(i).find("h4").height();
                        $hh=$h1>$h?$h1:$h;
                       $("#Recommend li").eq(i).find("h4").css({
                           'height':$hh+'px'
                       });
                       $("#Recommend li").eq((i-1)).find("h4").css({
                           'height':$hh+'px'
                       });
                   }
              }
         }

    });
</script>
