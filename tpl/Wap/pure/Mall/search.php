<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1" />
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Pragma" content="no-cache" />
<meta charset="utf-8" />
<title>搜索</title>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/mall.css"/>
<link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}mall/css/index.css"/>
 <script src="{pigcms{$static_path}mall/js/jquery.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/common.js?2gfds106" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js" charset="utf-8"></script>
<script src="{pigcms{$static_path}mall/js/simplite.js"></script>
<script>var ajax_url = '{pigcms{:U("Mall/ajax_list")}';var open_extra_price =Number("{pigcms{$config.open_extra_price}");
var extra_price_name ="{pigcms{$config.extra_price_alias_name}";
var cat_fid = '{pigcms{$cat_fid}', cat_id = '{pigcms{$cat_id}', names = '{pigcms{$names}', ajax_url = '{pigcms{:U("Mall/ajax_list")}';
var open_extra_price =Number("{pigcms{$config.open_extra_price}"); 
var ajax_property_url="{pigcms{:U('Mall/ajax_property')}";
var score_name = "{pigcms{$config['score_name']}";
</script>

<script src="{pigcms{$static_path}mall/js/mallsearch.js?v==5" type="text/javascript" ></script>
<style>
    #pageLoadTip{display:none !important;}
    .tjt_border_t:before{  top: -1px !important;  }
    .tjt_border_b:before{  bottom: -1px !important;  }
    .tjt_border_r:before{  right: -1px !important;  }
    .tjt_border_l:before{  left: -1px !important;  }
    .bd_a a {

        border: none !important;

    }
    .search{position:relative}
    .search:before {
        border-bottom: 1px solid #e9e9e9;
        content: '';
        display: block;
        width: 100%;
        position: absolute;
        left: 0;
        bottom: 0;
        -webkit-transform-origin: left bottom;
        -webkit-transform: scaleY(0.5);
    }
    body{background:#fff}
    .searcht .se_input {
         background: #f4f4f4;

     }
    .searchBox>ul {

        background: #fff !important;

    }
    .searcht {
        background: #fff;

    }
    .searchBox>ul>li.dp {
       position:relative
    }
    .searchBox>ul>li.dp:before {
        -webkit-transform: scaleX(0.5);
        border-left: 1px solid #e9e9e9;
        content: '';
        display: block;
        bottom: 0;
        position: absolute;
        left: 0;
        top: 0;
        -webkit-transform-origin: left top;
    }

    .keyword{
        float: left;
        margin: 5px 10px;
        background: #f4f4f4;
        border-radius:3px;
        font-size:14px;
        padding: 3px 15px;
    }
    .searchBox{
        height:36px;
        margin-left:0px;
        width: 100%;
        background-color:white;
    }
    .searchBox>ul {
        display: -webkit-box;
        display: -moz-box;
        display: -ms-flexbox;
        background: #f4f4f4;
        text-align: center;
        border-bottom: 1px solid #edebeb;
        padding:0px;
    }
    .searchBox>ul>li{
        -webkit-box-flex: 1;
        -moz-box-flex: 1;
        -ms-flex: 1;
        width: 100%;
        text-align: left;
        text-align: center;
        color: #7b7b7b;
        position: relative;
        background-position: 3px right;
        background-size: 1px 38px;
        height: 40px;
        line-height: 40px;
    }
    .searchBox .active {
        color: #06c1ae;
    }
    .hot{
        margin-top: 5px;
    }
    .searchBox span{position:relative}
    .searchBox .active span:after{
        border-top: 2px solid #06c1ae;
        top: 30px;
        content: '';
        position: absolute;
        width: 100%;
        height: 1px;
        left: 0%;
    }
    .bd_a a {

        margin: auto !important

    }
    .searcht a {

        padding: 0 15px;

    }
    .hot {
       border-top: none

    }
    .searcht .se_input {

        padding-left: 15px !important;

    }
    .psnone img {
        width: 145px;
        display: block;
        margin: auto;
        height: 128px;
    }
    .psnone{margin-top:30%}
    .mgt_25{}
	.tip_yu{
		position: absolute;
		bottom: 0;
		left: 0;
		height: 30px;
		line-height: 30px;
		background-color: rgba(0,0,0,0.6);
		color: #fff;
		width: 100%;
		text-align: center;
		z-index: 999;
	}
</style>
<!--[if lte IE 9]>
<script src="scripts/html5shiv.min.js"></script>
<![endif]-->
</head>
<body>
<div>

</div>
<section class="search searcht">
	<div class="searcht_n flex">
		<if condition="$store_id eq 0">
			<input type="text" placeholder="请输入商品或店铺名称" class="se_input flex_one" value="{pigcms{$keyword}"  />
		<else/>
			<input type="text" placeholder="请输入商品名称" class="se_input flex_one" value="{pigcms{$keyword}"  />
		</if>
		<input type="hidden" id="store_id" value="{pigcms{$store_id}" />
		<a href="javascript:void(0)" id="search" style="margin-left:15px">搜索</a>
	</div>
</section>
<if condition="$store_id eq 0">
    <div class="searchBox" style="display:none">
        <ul>
            <li class="sp active"><span class="nav-head-name rel">商品</span></li>
            <li class="dp" ><span class="nav-head-name">店铺</span></li>
        </ul>
    </div>
</if>
<section class="hot" <if condition="$store_id eq 0"><else/>style='padding-top:45px'</if>    >
	<!-- 收索没有结果图 -->
	<div class="psnone" style="display: none;">
		<img src="{pigcms{$static_path}mall/images/s_08.png" />
        <div style=" margin-top: 10px;color: #999;  font-size: 16px;  text-align: center;">很抱歉，暂无搜索内容</div>
	</div>
	
	<!--  收索结果列表 -->
	<div class="bd_a clr search_list ui-grid" >
	</div>
	<div class="navBox_list search_list">
	</div>
    <if condition="$store_id eq 0">
        <div class="mgt_25">
            <div id="rec_search" style="overflow: auto;margin-bottom: 5px;padding-bottom: 5px;" >
                <span style="margin-left: 10px;font-size:12px" id="rec_head">历史记录<span style="margin-right: 10px;float:right; color: gray;padding:3px 5px;font-size: 10px;" id="clearall"><img src="{pigcms{$static_path}mall/images/search_del.png" style="width:20px"/></span></span>
                <div id="rec_content" style="margin-left: 10px;margin-top:10px"></div>
            </div>

            <div id="hot_search">
                <span style="margin-left: 10px;font-size:12px">热门搜索</span>
                <div style="margin-left: 10px;margin-top:10px">
                    <volist name="search_hot_list" id="vo">
                        <div class="hot_word keyword" style="<?php if($vo['hottest']==1){ echo 'color:#f23030;background:#FFEDEB';}else{echo 'color:#000;';} ?> "> {pigcms{$vo.name}</div>
                    </volist>
                </div>
            </div>
        </div>

    </if>
</section>
</body>
<script id="storeListBoxTpl" type="text/html">
{{# for(var i = 0, len = d.store_list.length; i < len; i++){ }}
	<a href="{{ d.store_list[i].url }}" target="_blank">
		<div class="Menulink clr">
			<div class="Menulink_img fl">
				<img class="on" src="{{ d.store_list[i].image }}">
				<span class="MenuGroup"></span>
			</div>
			<div class="Menulink_right">
				<h2>{{ d.store_list[i].name }}</h2>
				<div class="MenuPrice">
					<span class="PriceF">综合评分<i>{{ d.store_list[i].score_mean }}</i></span>
					<span class="PriceS">共<i>{{ d.store_list[i].goods_count }}</i>件商品</span>
				</div>
			</div>
		</div> 
	</a>
{{# } }}
</script>
<script type="text/html" id="goodsListBoxTpl">
    <& for(var i=0;i< _this.goods_list.length;i++) {&>
    <& var _item=_this.goods_list[i] &>
    <li class="w47 ui-border-t tjt_border_t  <&= i%2==0?'clear':'' &>" style="margin:<&= i%2==0?'2% 1% 0% 2%;':' 2% 2% 0% 1%;' &>">
        <a href="<&= _item.url&>" style="width:100%;" target="_blank">
            <div class="pt0 pb0  bg_f  ui-border-l tjt_border_l  " >
                <div class="ui-border-r tjt_border_r">
                    <div class="ui-img-vertical" style="padding-top:100%">
                        <span class="pd0">
                            <img src="<&= _item.image&>"  style="width:<&= View.setting.pic_w&>px;height:<&= View.setting.pic_w&>px"/>
                            <& if(_item.jd_sku_id != 0){ &>
                                <div style="position: absolute;top: 0px;line-height: 20px;font-size: 12px;background: #fe5842;text-align: center;border-radius: 0px 0px 5px 5px;padding: 0 5px;color: #fff;">京东配送</div>
                             <& } &> 
							<& if(_item.is_booking== 1){ &>
								<div class="tip_yu" style="background-color: #06c1ae; opacity:0.85">预售</div>
							<& } &> 
                        </span>
                    </div>

                    <div class="ui-grid-info  m_0  ui-border-b">
                        <h4 class="ui-nowrap-multi pl5 "  style="height: 50px">
                            <& if(_item.is_self==1){ &>    
                                <i class="self_tag">自营</i>
                            <& } &>
                            <&= _item.name&>
                            <& if(_item.act_info && _item.act_info.spec_str){ &>
                            <span class='font_size13 color_7 m_0'>(<&= _item.act_info.spec_str &>)</span>
                            <& } &>
                        </h4>

                        <& if((_item.price&& _item.price!='') ||(_item.sell_count && _item.sell_count!='')){ &>
                        <p class="pl5 pb0">
                            <!-- <span class="font_size18 color_red font-weight" >{pigcms{$config['Currency_symbol']}<&= _item.price&></span> -->  
							<& if(_item.is_booking == 1){ &>
								 预售价 
							 <& } &> 
							<span class="font_size16 color_red font-weight ">
								 <label style="font-size: 12px;">{pigcms{$config['Currency_symbol']}</label>
                            <& if(_item.price_type == 0 ){ &>
                                 <&= _item.price&>
                            <& } &>

							<& if (_item.price_type == 1){ &>
							 <&= _item.price&>
							<& } else if(_item.price_type == 2){ &>
							 <i class=" color_red font-weight "><&=  _item.account_money &> </i>+<&=  _item.account_score &>  <label style="font-size: 12px;"><&= score_name&></label>
							<& } else if(_item.price_type == 3){ &>
							 <&=  _item.account_score &> <label style="font-size: 12px;"><&= score_name&></label>
							<& } &>
							</span>

                            <& if(_item.is_seckill_price ){ &>
                            <span class="font_size2  color_9 border_center rel" style="font-size:12px">原价{pigcms{$config['Currency_symbol']}<&= _item.old_price &></span>
                            <& } &>
							 </p>
							<!-- <p>
							   <& if(_item.sell_count>0){ &>
								<span class="font_size12 color_7 ">已售<&= _item.sell_count&></span>
								<& } &>
							</p> -->
                        <& } &>

                        <& if(_item.act_info && _item.act_info.price){ &>
                        <p class="pb5 pl5">
                            <span class="font_size16 color_red ">{pigcms{$config['Currency_symbol']}<&= _item.act_info.price &></span>
                            <& if(_item.act_info.is_group==1){ &>
                            <span class="font_size12 color_f bg_r pd1_3 fr" ><&= _item.act_info.num &>人拼团价</span>
                            <& }else if(_item.act_info.is_cut==1){ &>
                            <span class="font_size12 color_f bg_r pd1_3 fr">砍价拿</span>
                            <& } &>
                        </p>
                        <& }else if((_item.sell_count==0 && _item.is_new==1) ||(_item.is_seckill_price && _item.is_seckill_price!='')){ &>
                        <p class=" pb0 pl5" style="height:30px">
                            <& if(_item.sell_count==0 && _item.is_new==1){ &>
                            <span class="font_size12 color_f bg_795 pd1_3" style="margin-left: 1px;">新品</span>
                            <& } &>
                            <& if(_item.is_seckill_price){ &>
                            <span class="font_size12 color_f bg_fa pd1_3 left">{pigcms{$config.seckill_alias_name|default='限时优惠'}</span>
                            <& } &>
                        </p>
                        <& } &>
							
						<div class="goodlogoName">
							<img src="<&= _item.store_logo&>" class="store_logo" style="height: 40px;"/> 
							<div class="text" style="line-height: 1;">
								<span class="span1"><&= _item.store_name&></span>
								<& if(_item.sell_count>0){ &>
								<span class="font_size12" style="color:#777; float:right ;">已售<&= _item.sell_count&></span>
								<& } &> 
								 <div style="clear:both"></div>
							</div>  
							
							<label style="color:#999; font-size: 12px;"><&= _item.store_score_mean&>分</label>   
							<& if(_item.show_deliver == true){ &>
							   <label style="color:#999; font-size: 12px;">起送<&= _item.mall_s_basic_price&></label>
							<& } &>  
						</div>
							
							
						<!-- <div class="goodlogoName">
							<img src="<&= _item.store_logo&>" class="store_logo" style="height: 40px;"/>
							<p><&= _item.store_name&></p> 
							<label style="color:#999; font-size: 12px;"><&= _item.store_score_mean&>分</label>   
							<& if(_item.show_deliver == true){ &>
							   <label style="color:#999; font-size: 12px;">起送<&= _item.mall_s_basic_price&></label>
							<& } &>  
						</div> -->
							
                    </div>
                </div>
            </div>
        </a>
    </li>
    <& }&>
</script>
<script id="goodsListBoxTpl" type="text/html">
{{# for(var i = 0, len = d.goods_list.length; i < len; i++){ }}
	<a href="{{ d.goods_list[i].url }}" target="_blank">
		<div class="bd_img">
			<img src="{{ d.goods_list[i].image }}" width="100%"/>
		</div>
		<div class="bd_text">
			<h2>{{ d.goods_list[i].name }}</h2>
            <p class='label_box'>
                {{# if (d.goods_list[i].is_seckill_price){}}
                    <label class='seckill'>{pigcms{$config.seckill_alias_name|default='限时优惠'}</label>
                {{# } }}
            </p>
			<div class="Price clr">
				<div class="fl">
					<span>{pigcms{$config['Currency_symbol']}<i>{{ d.goods_list[i].price }}{{# if(open_extra_price&&d.goods_list[i].extra_pay_price>0){ }}+{{ d.goods_list[i].extra_pay_price}} {{ extra_price_name }} {{# } }}</i></span>
					{{# if (d.goods_list[i].is_seckill_price){}}
					<del>原价{{ d.goods_list[i].old_price }}</del>
					{{# } }}
				</div>
				{{# if (d.goods_list[i].sell_count > 0){}}
				<div class="fr">已售{{ d.goods_list[i].sell_count }}单</div>
				{{# } else if(d.goods_list[i].is_new == 1) { }}
				<div class="fr">新品上架</div>
				{{# } }}
			</div>
            {{# if (d.goods_list[i].act_info && d.goods_list[i].act_info.price){ }}
                <div class='act_box'>
                    <span class='price'>{pigcms{$config['Currency_symbol']}<i>{{ d.goods_list[i].act_info.price }}</i></span>
                    {{# if (d.goods_list[i].act_info.is_group==1){ }}
                        <span class='btn'>{{ d.goods_list[i].act_info.num }}人拼团</span>
                    {{# }else if (d.goods_list[i].act_info.is_cut==1){ }}
                        <span class='btn'>砍价拿</span>
                    {{# } }}
                </div>
            {{# } }}            
		</div>        
		{{# if (false && d.goods_list[i].is_seckill_price){}}
            <div class="discount">限时优惠</div>
        {{# } }}
	</a>
{{# } }}
</script>
<script>
    $(document).ready(function () {
        showHistory();
        //显示历史记录
        function showHistory() {
            var data = new Array();
            var cookie = $.cookie("search_history");
            if(cookie!=null){
                data = JSON.parse(cookie);
            }else{
                $('#rec_search').hide();
                return false;
            }

            var str = $(".se_inputs").val();
            if (data != null) {
                var len = 8;//最大条数
                var limit = data.length-len-1;

                for (var i = data.length-1; i >limit ; i--) {
                    if(data.indexOf(data[i])>-1){
                        var divhtml = '<div class="keyword hot_word">'+ data[i]+ '</div>';
                        $("#rec_content").append(divhtml);
                    }
                }
            }
        }

        //清空
        $("#clearall").on("click",function(){
            $.cookie('search_history', null, {expires : -1});
            showHistory();
        });

        $('.hot_word').click(function () {
            $('.se_input').val($(this).text());
            hasMorePage = true;
            now_page = 0;
            getList(0);
        });
    })
</script>
<script type="text/javascript">
    window.shareData = {
        "moduleName":"Mall",
        "moduleID":"0",
        "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/index')}",
        "tTitle": "{pigcms{$config.mall_alias_name|default='商城'}",
        "tContent": "{pigcms{$config.site_name}"
    };
</script>
{pigcms{$shareScript}
</html>