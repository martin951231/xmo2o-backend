<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta name="format-detection" content="telephone=no"/>
<meta charset="utf-8">
<title>{pigcms{$title}</title>
<link href="{pigcms{$static_path}mall/css/pullToRefresh.css" rel="stylesheet" />
<link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/mall.css"/>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>

<script>
var $home=false;
var cat_fid = '{pigcms{$cat_fid}', cat_id = '{pigcms{$cat_id}', names = '{pigcms{$names}', ajax_url = '{pigcms{:U("Mall/ajax_list")}';
var open_extra_price =Number("{pigcms{$config.open_extra_price}");
var extra_price_name ="{pigcms{$config.extra_price_alias_name}";
var ajax_property_url="{pigcms{:U('Mall/ajax_property')}";
var score_name = "{pigcms{$config['score_name']}";
</script>
<!--[if lte IE 9]>
<script src="{pigcms{$static_path}js/html5shiv.min.js"></script>
<![endif]-->
	<style>
		#pageLoadTip{display:none !important;}
		body{background-color: #fff;}
		.ui-border-t:before {
			border-top: 1px solid #e9e9e9;
			content: '';
			display: block;
			width: 100%;
			position: absolute;
			left: 0;
			top: 0;
			-webkit-transform-origin: left top;
			-webkit-transform: scaleY(0.5);
		}
		.ui-border-r:before {
			border-right: 1px solid #e9e9e9;
			content: '';
			display: block;
			bottom: 0;
			position: absolute;
			right: 0;
			top: 0;
			-webkit-transform-origin: right top;
			-webkit-transform: scaleX(0.5);
		}
		.ui-border-l:before {
			border-left: 1px solid #e9e9e9;
			content: '';
			display: block;
			bottom: 0;
			position: absolute;
			left: 0;
			top: 0;
			-webkit-transform-origin: left top;
			-webkit-transform: scaleX(0.5);
		}
		.ui-border-b:before {
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
		.w47{position:relative}
		.tjt_border_t:before{  top: -1px !important;  }
		.tjt_border_b:before{  bottom: -1px !important;  }
		.tjt_border_r:before{  right: -1px !important;  }
		.tjt_border_l:before{  left: -1px !important;  }
		.dity_top li span:before {

			content: "";
			width: 4px;
			height: 4px;
			position: absolute;
			border-right: #797b7d 1px solid;
			border-bottom: #797b7d 1px solid;
			transform: rotate(225deg);
			display: inline-block;
			border-top: 1px solid transparent;
			border-left: 1px solid transparent;
			left: 36px;
			top: 17px;
		}
		.dity_top li.on span:before {
			border-top:1px solid transparent;
			border-right: #f23030 1px solid;
			border-bottom: #f23030 1px solid;
		}
		.dity_top li span:after {
			transform: rotate(45deg);
			border-right: #797b7d 1px solid ;
			border-bottom: #797b7d 1px solid;
			content: "";
			width: 4px;
			height: 4px;
			display: inline-block;
			border-top: 1px solid transparent;
			border-left: 1px solid transparent;
			left: 36px;
			position: absolute;
			top: 23px;
		}
		.dity_top li.ou span:after {
			border-right: #f23030 1px solid ;
			border-bottom: #f23030 1px solid;
			border-top: 1px solid transparent;

		}
		#refresh{
			top: 85px;
			bottom: auto;
		}
		#scrollers {
			position: absolute;
			z-index: 1;
			-webkit-touch-callout: none;
			-webkit-tap-highlight-color: rgba(0,0,0,0);
			width: 100%;
			padding: 0;
		}
		#ride_all {
			z-index: 1000;

		}
		.dity_end>ul{padding:8px 0px;height: 28px;}
		.dity_end{padding:0px}
		.act_box span.btn {
			float: right;
		  display:block;
			padding: 1px 3px;

		}
		
		#refresh{
			overflow-y: scroll;
		}
		/* #wrapper{
            overflow-x: auto;
        }
		#cat-scroller{
            white-space: nowrap;
        }
        #cat-scroller li {
            padding: 0 5px;
            font-size: 14px;
            color: #333;
            display: inline-block;
            text-align: center;
            height: 40px;
            line-height: 40px;
        }
        #cat-scroller  li a {
            color: #333;
            display: block;
            padding: 0 6px;
            line-height: 42px;
        }
        #cat-scroller li.is_scroll a {
            color: #f23030;
            height: .9rem;
        }
        #cat-scroller li.is_scroll a:after {
            border-top: 3px solid #f23030;
            top: 37px;
            content: '';
            position: absolute;
            width: 80%;
            height: 8px;
            left: 10%;
            z-index: 10;
        } */
		#wrapper{
			height: 82px; 
		}
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
</head>
<body>
<!-- 返回顶部按钮 -->
<div id='toTop' class="cursor"><img src="{pigcms{$static_path}mall/images/go_top.png" class="w100 h100" /></div>
<div class="slideBox slideBox2">
	<a class="search" href="{pigcms{:U('search')}">
		<input type="text" placeholder="搜索商品名称" class="se_input1" disabled="disabled" />
		<!-- <a href="javascript:void(0)" id="search">搜索</a> -->
	</a> 
		<div class="hd nav_topfied" style="z-index:1000">
			 <div  class="bg_f ">
				<div class="news-scroller bg-w  wrapper03 wrapTop"  id="wrapper">
					<ul class="clearfix clearfix_list font_size14" id="cat-scroller">
						<li data-id=""  data-index="0">
							<a href="javascript:void(0)" class="rel">全部</a>
						</li>
						<volist name="category_list" id='vo' key="k">
							<li data-fid='{pigcms{$vo.fid}' data-id="{pigcms{$vo.id}"  data-index="{pigcms{$k}" <if condition="$vo['id'] eq $_GET['cat_id']">class="is_scroll"</if>>
								<a href="javascript:void(0)" class="rel">{pigcms{$vo.name}</a>
							</li>

						</volist>
						<div class="clear"></div>
					</ul> 
					<!-- <div style='width:100%;height:100%;z-index:60;position:absolute;top:0px;left:0px; margin: 0px 5px;display:none' class='look_all'>全部分类 </div> -->
				</div>
				<div class='add_more add_select'>
				</div>
			</div>
			<div style='display:none;padding-top: 0px;width:100%' id='ride_all' class="bg_f">
				<div  class="cursor "></div>
				<ul class="hotKey bg_f" data-down="1" style="display: block;">
					<li class="bg_active" data-id="" data-index="0">
						<a href="javascript:void(0)">全部</a>
					</li>
					<volist name="category_list" id='vo' key="k">
						<li data-fid='{pigcms{$vo.fid}' data-id="{pigcms{$vo.id}"  data-index="{pigcms{$k}">
							<a href="javascript:void(0)">{pigcms{$vo.name}</a>
						</li>
					</volist>
				</ul>
			</div>
			<div class="dity_top ui-border-b rel" >
				<ul class="clr">
					<li class="ou updown" data-sort="1">
						<span>综合</span>
					</li>
					<li data-sort="2">
						<span>销量</span>
					</li>
					<li data-sort="3">
						<span>价格</span>
					</li>
				</ul>
			</div>
			<div class="dity_end">
				
			</div>
	</div>
</div>

	
<div >
	<div class="dityhome" style="background:#fff;box-sizing:border-box; z-index: -1!important;" id="refresh">
		<div id="ui_list">
			<div style="padding-top: 7px;">
				<div class="psnone" style=" margin-top:30%;display:none;">
					<img src="https://hf.pigcms.com/tpl/Wap/pure/static/mall/images/no_content.png" style="width: 145px;  display: block;   margin: auto;  height: 128px;">
					<div style=" margin-top: 10px;color: #999;  font-size: 16px;  text-align: center;">很抱歉，暂无内容</div>
				</div>
				<div class="bd_a clr" style="padding-bottom:0px;"></div>
			</div>
		</div>
	</div>
</div>
<adiv class="mask" style="height:100%"></adiv>
<div class="pageSliderHide"></div>
</body>
<script id='property_box' type="text/html">
	<ul class="clr">
		{{# for(var i=0;i<d.length;i++){  }}
			<li>
				<span data-name="{{ d[i]['name'] }}">{{ d[i]['name'] }}</span>
			</li>
		{{# } }}
	</ul>

	<!-- 下拉菜单 -->
	{{# for(var i=0;i<d.length;i++){  }}
		<div class="drop">
			<div class="drop_top drop_top{pigcms{$key}">
				<dl class="clr">
					{{# var item=d[i]['value_list'];if(item && item.length>0){ }}
						{{# for(var j=0;j<item.length;j++){  }}
							<dd data-pid="{{ item[j]['id'] }}">
								<span>{{ item[j]['name'] }}</span>
							</dd>
						{{# } }}
					{{# } }}
				</dl>
			</div>
			<div class="confirm clr">
				<a href="javascript:void(0)" class="fl">重置</a>
				<a href="javascript:void(0)" class="fr">确定</a>
			</div>
		</div>
	{{# } }}
</script>
<script id="goodsListBoxTpl" type="text/html">
	{{# for(var i = 0, len = d.length; i < len; i++){ }}
	<div class="w47 ui-border-t tjt_border_t left  {{ i%2==0?'clear':'' }}" style="margin:{{ i%2==0?'2% 1% 0% 2%;':' 2% 2% 0% 1%;' }}">
		<a href="{{ d[i].url }}"  target="_blank" app_target_blank="_blank" class="  ui-border-l tjt_border_l " style=" float: none;margin:unset;width:100%;border:none">
			<div class="ui-border-r tjt_border_r">
				<div class="bd_img" style="position: relative;">
					<img src="{{ d[i].image }}" width="100%" class="bd_imgImg"/> 
					{{# if(d[i].act_info && d[i].act_info.is_booking && d[i].act_info.is_booking==1){ }}
						<div class="tip_yu" style="background-color: #06c1ae; opacity:0.85">预售</div>
					{{# } }}
                    {{# if(d[i].vip_show){ }}
                    <i class="self_tag" style="position: absolute;top: 0px;left: 0px">{{ d[i].vip_tag }}</i>
                    {{# } }}
				</div>
				<div class="bd_text pb10 ui-border-b" style='padding-left:0px;padding-right:0px'>
					<div class="ui-grid-info">
						<h4 class="overflow_ellipsis2 font_size14" style="padding: 6px 10px 0 5px;">
							{{# if(d[i].is_self==1){ }}
								<i class="self_tag">自营</i>
							{{# } }}
							{{ d[i].name }}
						</h4>
					</div>
					<p class="pl5 pb0">
						{{# if(d[i].act_info && d[i].act_info.is_booking && d[i].act_info.is_booking==1){ }}
							预售价
						{{# } }} 
						<span class="font_size18 color_red font-weight ">
							<label style="font-size:12px">{pigcms{$config['Currency_symbol']}</label>
						{{# if (d[i].price_type == 0){}}
						<i class=" color_red font-weight ">{{ d[i].price }}{{# if(open_extra_price==1&&d[i].extra_pay_price>0){ }}+{{ d[i].extra_pay_price }}{{ extra_price_name }}{{# } }}</i> 
						{{# } else if(d[i].price_type == 1){}} 
					     <i class=" color_red font-weight ">{{ d[i].price }} </i> 
						{{# } else if(d[i].price_type == 2){}}
						 <i class=" color_red font-weight ">{{ d[i].account_money }} </i>+ {{ d[i].account_score }}<label style="font-size:12px"> {pigcms{$config.score_name}</label>
						{{# } else if(d[i].price_type == 3){}}
						 {{ d[i].account_score }}<label style="font-size:12px"> {pigcms{$config.score_name}</label>
						{{# } }}
						</span>
						 
						{{# if (d[i].is_seckill_price){}}
						<span class="font_size2  color_9 border_center rel" style="font-size:12px">原价{pigcms{$config['Currency_symbol']}{{ d[i].old_price }}</span>
						{{# } }}  
					</p>
					

					{{# if (d[i].act_info && d[i].act_info.price){ }}
					<div class='act_box' style="padding-left:5px">
						<span class='price'>{pigcms{$config['Currency_symbol']}<i>{{ d[i].act_info.price }}</i></span>
						{{# if (d[i].act_info.is_group==1){ }}
						<span class='btn'>{{ d[i].act_info.num }}人拼团价</span>
						{{# }else if (d[i].act_info.is_cut==1){ }}
						<span class='btn'>砍价拿</span>
						{{# } }} 
					</div>
					{{# }else if (d[i].is_seckill_price  ||  (d[i].is_new == 1 && d[i].sell_count == 0)  ){}}
					<p class=" pb0 pl5 " style="overflow:hidden">
						{{# if (d[i].is_new == 1 &&  d[i].sell_count == 0 ){}}
						<span class="font_size12 color_f bg_795 pd1_3 left"  style="margin-right:10px">新品</span>
						{{# } }}
						{{# if ( d[i].is_seckill_price){}}
						<span class="font_size12 color_f bg_fa pd1_3 left">{pigcms{$config.seckill_alias_name|default='限时优惠'}</span>
						{{# } }}
						<div class="clear"></div>
					</p>

					{{# } }} 
					
					<div class="goodlogoName"> 
						{{# if ( d[i].store_logo){}}
							<img src="{{ d[i].store_logo }}" class="store_logo" style="height: 40px;"/> 
						{{# } }}
						<div class="text">
							<span class="span1">{{ d[i].store_name }}</span>
							{{# if (d[i].sell_count > 0){}} 
							<span class="font_size12" style="color:#777; float:right ;">已售{{ d[i].sell_count }}</span>
							{{# } }} 
							 <div style="clear:both"></div>
						</div>  
						
						<label style="color:#999; font-size: 12px;">{{ d[i].store_score_mean }}分</label>   
						{{# if ( d[i].show_deliver == true){}}
						   <label style="color:#999; font-size: 12px;">起送{{ d[i].mall_s_basic_price }}</label>
						{{# } }} 
					</div>

				</div>
			</div>


		</a>
	</div>

	{{# } }}
</script>
<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>

<!-- minirefresh -->
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/minirefresh.min.css"/>
<script type="text/javascript" src="{pigcms{$static_public}minirefresh/minirefresh.min.js" charset="utf-8"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.css"/>
<script type="text/javascript" src="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.js" charset="utf-8"></script>

<script type="text/javascript" src="{pigcms{$static_path}js/common.js" charset="utf-8"></script>

<script type="text/javascript">
    $(function (){
        if ($('#cat-scroller li').length <= 6 ) {
            $('#wrapper').height(40)
            $('.add_select').css('bottom','20px');
            $('.dity_top').css('border-top', 'none');
        }
    })
</script>

<!-- 小程序分享 -->
<script type="text/javascript">
	var ua = window.navigator.userAgent.toLowerCase();
	var is_weixin=false;
	var is_mini=false;
	var res_url='';
	var  webview=function(){
		wx.miniProgram.getEnv(function(res) {
			if (res.miniprogram) {
				//小程序中
				is_mini=true;
			}else{
				//微信端
				is_weixin=true;
			}
		});
	}
	if(ua.indexOf('micromessenger') == -1){
		//不在微信或者小程序中
	}else{
		webview()
	}
</script>
<script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
<script src="{pigcms{$static_path}mall/js/simplite.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}mall/js/goodslist.js?461"></script>
<script type="text/javascript">
    window.shareData = {
        "moduleName":"Mall",
        "moduleID":"0",
        'alias_name':"{pigcms{$config.mall_alias_name|default='商城'}",
        'rootUrl':"{pigcms{$config.site_url}{pigcms{:U('Mall/goods_list',array('openid'=>$_SESSION['user']['openid'],'cat_fid'=>$cat_fid))}",
        "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/goods_list',array('cat_fid'=>$cat_fid,'cat_id'=>$cat_id))}",
        "tTitle": "{pigcms{$title}【{pigcms{$config.mall_alias_name|default='商城'}】",
        "tContent": "{pigcms{$config.site_name}"
    };
</script>
{pigcms{$shareScript}
</html>