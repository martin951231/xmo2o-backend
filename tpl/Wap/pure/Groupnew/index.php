<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config.group_index_name|default='同城优选'}</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width, viewport-fit=cover"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/index.css?t={pigcms{:time()}"/>
		<script type="text/javascript">var nowPage = 'pageIndex';</script>
        <style>
            .goodPrice-new .oldPrice {
                font-size: 12px;
                font-weight: normal;
                color: #999;
                margin-left: 0;
                text-decoration: line-through;
            }
            .common-score-tag {
                margin-left: 5px;
                padding: 0 5px;
                display: inline-block;
                font-size: 10px;
                font-weight: bold;
                color: rgba(250, 63, 2, 1);
                line-height: 18px;
                background-color: rgba(250, 63, 2, .1);
                border-radius: 3px;
                text-align: center;
                vertical-align: text-bottom;
                box-sizing: border-box;
            }
        </style>
	</head>
	<body>
		<div class="pageBg"></div>
		<scrollView id="pageIndex" class="page pageIndex y hide" data-loadurl="{pigcms{:U('index')}" data-pagetitle="{pigcms{$config.group_index_name|default='同城优选'}">
			<div class="container">
                <!--头部搜索-->
				<div class="searchBox link newWin" data-url="{pigcms{:U('search')}">
					<i class="iconfont">&#xe622;</i>
					<span>搜索商品或店铺...</span>
				</div>
                <!--头部分类-->
				<div class="categoryBox swiper-container" id="categoryBox">
					<div class="swiper-wrapper">
						<div class="swiper-slide item active">首页</div>
						<volist name="all_category_list" id="vo">
							<div class="swiper-slide item link newWin" data-url="{pigcms{:U('category',array('cat_id'=>$vo['cat_id'], 'page_num' => 1))}">{pigcms{$vo.cat_name}</div>
						</volist>
					</div>
				</div>
                <!--人气推荐-->
                <if condition="$pop_list['num'] gt 0">
                    <div class="sliderBox">
                        <div class="rightPosition recommend-pagination">
                            <span class="now">1</span><span class="all">/{pigcms{$pop_list.num}</span>
                        </div>
                        <div class="ConTitle">人气推荐</div>
                        <div class="ConDesc">每天一个好美味，每天一个好心情！</div>
                        <div class="sliderInner swiper-container" id="recommend">
                            <div class="swiper-wrapper">
                                <volist name="pop_list['group_list']" id="vv">
                                    <div class="swiper-slide link newWin" data-url="{pigcms{:U('detail',array('group_id'=>$vv['group_id'], 'page_num' => 1))}">
                                        <div class="sliderImg" style="background-image:url({pigcms{$vv['list_pic']})"></div>
                                        <div class="sliderTitle">{pigcms{$vv.merchant_name} | {pigcms{$vv.s_name}</div>
                                        <div class="sliderDesc">{pigcms{$vv.intro}</div>
                                    </div>
                                </volist>
                            </div>
                        </div>
                    </div>
                </if>
                <!--48H人气TOP10-->
                <if condition="COUNT($hot_list['group_list']) gt 0">
                    <div class="goodListBox row">
                        <div class="ConTitle">48H人气TOP10</div>
                        <div class="ConDesc">人气爆品推荐，左划更多惊喜！</div>
                        <scrollView class="goodList">
                            <volist name="hot_list['group_list']" id="vv">
                                <div class="goodItem link newWin" data-url="{pigcms{:U('detail',array('group_id'=>$vv['group_id'], 'page_num' => 1))}">
                                    <div class="goodImage pr">
										<if condition="$vv['pin_num'] gt 0">
											<div class="pin_style"></div>
										</if>
                                        <img src="{pigcms{:thumb_img($vv['list_pic'], 450, 250, 'fill')}"/>
                                    </div>
                                    <div class="goodName">{pigcms{$vv.merchant_name} | {pigcms{$vv.s_name}</div>
                                    <div class="goodPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vv.price}<span class="unit">/份</span><span class="oldPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vv.old_price}</span></div>
                                </div>
                            </volist>
                        </scrollView>
                    </div>
                </if>
                <!--近期新品-->
                <if condition="COUNT($new_list['group_list']) gt 0">
                    <div class="goodListBox list">
                        <if condition="$new_list['more']">
                            <div class="moreGood">查看更多</div>
                            <div class="moreGood link newWin" data-url="{pigcms{:U('category',array('page_num' => 1))}">查看更多</div>
                        </if>
                        <div class="ConTitle">近期新品</div>
                        <div class="ConDesc">做一个喜新不厌旧的人！</div>
                        <scrollView class="goodList">
                            <volist name="new_list['group_list']" id="vv">
                            <div class="goodItem link newWin" data-url="{pigcms{:U('detail',array('group_id'=>$vv['group_id'], 'page_num' => 1))}">
                                <div class="goodImage pr">
									<if condition="$vv['pin_num'] gt 0">
										<div class="pin_style"></div>
									</if>
                                    <img src="{pigcms{:thumb_img($vv['list_pic'], 450, 250, 'fill')}"/>
                                </div>
                                <div class="goodName">{pigcms{$vv.merchant_name} | {pigcms{$vv.s_name}</div>
                                <div class="goodPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vv.price}<span class="unit">/份</span><if condition="$vv['score_text']"><span class="common-score-tag" >{pigcms{$vv.score_text}</span></if></div>
                                <div class="goodPrice-new">
                                    <span class="oldPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vv.old_price}</span>
                                </div>
                            </div></volist>
                        </scrollView>
                    </div>
                </if>
                <!--分类下商品展示-->
                <if condition="COUNT($all_category_group) gt 0">
                    <volist name="all_category_group" id="v">
                        <if condition="$v['slipping'] && $v['group_count'] gt 2">
                            <div class="goodListBox row">
                                <if condition="$v['more']">
                                    <div class="moreGood link newWin" data-url="{pigcms{:U('category',array('cat_id'=>$v['cat_id'], 'page_num' => 1))}">查看更多</div>
                                </if>
                                <div class="ConTitle">{pigcms{$v.cat_name}</div>
                                <div class="ConDesc">{pigcms{$v.cat_des|default='&nbsp;'}</div>
                                <scrollView class="goodList">
                                    <volist name="v['group_list']" id="vv">
                                        <div class="goodItem link newWin" data-url="{pigcms{:U('detail',array('group_id'=>$vv['group_id'], 'page_num' => 1))}">
                                            <div class="goodImage pr">
												<if condition="$vv['pin_num'] gt 0">
													<div class="pin_style"></div>
												</if>
                                                <img src="{pigcms{:thumb_img($vv['list_pic'], 450, 250, 'fill')}"/>
                                            </div>
                                            <div class="goodName">{pigcms{$vv.merchant_name} | {pigcms{$vv.s_name}</div>
                                            <div class="goodPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vv.price}<span class="unit">/份</span><span class="oldPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vv.old_price}</span></div>
                                        </div>
                                    </volist>
                                </scrollView>
                            </div>
                            <else/>
                            <div class="goodListBox list">
                                <if condition="$v['more']">
                                    <div class="moreGood link newWin" data-url="{pigcms{:U('category',array('cat_id'=>$v['cat_id'], 'page_num' => 1))}">查看更多</div>
                                </if>
                                <div class="ConTitle">{pigcms{$v.cat_name}</div>
                                <div class="ConDesc">{pigcms{$v.cat_des|default='&nbsp;'}</div>
                                <scrollView class="goodList">
                                    <volist name="v['group_list']" id="vv">
                                        <div class="goodItem link newWin" data-url="{pigcms{:U('detail',array('group_id'=>$vv['group_id'], 'page_num' => 1))}">
                                            <div class="goodImage pr">
												<if condition="$vv['pin_num'] gt 0">
													<div class="pin_style"></div>
												</if>
                                                <img src="{pigcms{:thumb_img($vv['list_pic'], 450, 250, 'fill')}"/>
                                            </div>
                                            <div class="goodName">{pigcms{$vv.merchant_name} | {pigcms{$vv.s_name}</div>
                                            <div class="goodPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vv.price}<span class="unit">/份</span><span class="oldPrice">{pigcms{$config['Currency_symbol']}{pigcms{$vv.old_price}</span></div>
                                        </div>
                                    </volist>
                                </scrollView>
                            </div>
                        </if>
                    </volist>
                </if>
                <!--底部搜索-->
				<div class="searchBox link newWin bottom" data-url="{pigcms{:U('search')}">
					<i class="iconfont">&#xe622;</i>
					<span>还没挑到喜欢的？请点击搜索</span>
				</div>
			</div>
		</scrollView>
		<scrollView class="page pageFind y hide" data-loadurl="{pigcms{:U('find')}" data-pagetitle="为您挑好店" data-pagebg="{pigcms{$config.mobile_head_color}"></scrollView>
		<scrollView id="pageMy" class="page pageMy y hide" data-loadurl="{pigcms{:U('my')}" data-pagetitle="个人中心"></scrollView>
		<include file="index_footer"/>
	</body>
</html>