<link href="{pigcms{$static_path}mall/css/photo.css" rel="stylesheet" />
<div class="content2 ping bg_f commentDetail mg_t10" >
    <div class="demo-block" id="reply_box" >
        <div class="have_replay" style="display:none;">
            <ul class="ui-list ui-list-single ui-list-link ui-border-t mb_0">
                <li class="ui-border-t">
                    <div class="ui-list-info">
                        <h4 class="ui-nowrap font_size14" >评价<span class="color_red pl10">好评<i class='good_percent'>100%</i></span></h4>
                        <a href="{pigcms{:U('Mall/reply',array('goods_id'=>$now_goods['goods_id'],'new_comment'=>1))}" target="_blank">
                            <div class="ui-txt-info  font_size12">共<i class='total_reply'>0</i>条评论</div>
                        </a>
                    </div>
                </li>
            </ul>
            <div class="reply_nav">
                <p class="pd10_0 color_6">
                    <a href="{pigcms{:U('Mall/reply',array('goods_id'=>$now_goods['goods_id'],'new_comment'=>1,'type'=>'good'))}">
                        <span class="bg_fd  pd5_8 ml10 font_size12 br2 good_reply"></span>
                    </a>
                    <a href="{pigcms{:U('Mall/reply',array('goods_id'=>$now_goods['goods_id'],'new_comment'=>1,'type'=>'common'))}">
                        <span class="bg_fd  pd5_8 ml10 font_size12 br2 common_reply"></span>
                    </a>
                    <a href="{pigcms{:U('Mall/reply',array('goods_id'=>$now_goods['goods_id'],'new_comment'=>1,'type'=>'bad'))}">
                        <span class="bg_fd  pd5_8 ml10 font_size12 br2 bad_reply"></span>
                    </a>
                </p>
            </div>
            <div class="pd10_0 font_size14 align_center">
                <a href="{pigcms{:U('Mall/reply',array('goods_id'=>$now_goods['goods_id'],'new_comment'=>1))}">
                    <button class="border_d" style="border-radius: 20px;padding: 5px 14px;">查看全部评价</button>
                </a>
            </div>
        </div>
        <div class="no_replay" style="display:none;">
            <ul class="ui-list ui-list-single ui-border-tb mb_0">
                <li class="ui-border-t">
                    <div class="ui-list-info">
                        <h4 class="ui-nowrap font_size14">评价</h4>
                        <div class="ui-txt-info  font_size12">暂无评论，欢迎您购买之后留下宝贵的评论</div>
                    </div>
                </li>
            </ul>
        </div>
    </div>

</div>

<!-- 店铺评论模板 -->
<script id="shopReplyTpl2" type="text/html">
    {{# for(var i=0;i<d.length;i++){ }}
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
        {{# if(d[i]['pics'] && d[i]['pics'].length>0){ }}
            <ul class="ks-clear pb5 pics">
                {{# for(var j=0;j<d[i]['pics'].length;j++){ }}
                <li class="left ">
                    <img class="pr5 pb5 cursor" style="width: 100px;height: 100px;display: block;" data-index="{{j}}" src="{{ d[i]['pics'][j]['s_image'] }}"  data-pics="{{ d[i]['pics'][j]['m_image'] }}"/>
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
<script src="{pigcms{$static_path}mall/js/cate_complite.js" type="text/javascript" ></script>
<script type="text/javascript">
//获取店铺商品评论
if(typeof shopReplyUrl2!='undefined'){
    $.post(shopReplyUrl2,{showCount:1,'order':'custom'},function(res){
        res=$.parseJSON(res);
        if(res){
            if(res.list && res.list.length<1){
                $('#reply_box .no_replay').show();
                return false;
            }
            //顶部评论
            $('#commentDetail').css({'display':'inline-block'});
            $('#reply_box .have_replay').show();
            $('#reply_box .good_percent').text(res.good_percent + '%');
            $('#reply_box .total_reply').text(res.all_count);
            $('#reply_box .good_reply').text('好评('+res.good_count+')');
            $('#reply_box .common_reply').text('中评('+res.common_count+')');
            $('#reply_box .bad_reply').text('差评('+res.bad_count+')');
            var data=[];
            for(var i=0;i<res.list.length;i++){
                if(i>=3){
                    break;
                }else{
                    data.push(res.list[i]);
                }
            }
            laytpl($('#shopReplyTpl2').html()).render(data, function(html){
                $('.reply_nav').after(html);
            });
            $(".star p").each(function(index, element) {
                $(this).css("width", $(this).attr("tip") * 16);
            });
        }
    });     
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
            $(".weui-photo-browser-modal").remove()
        }
    });
    pb1.open(index);
});
// 关闭图片大图浏览
function close_swiper(){
    $('.albumContainer').remove();
}
</script>