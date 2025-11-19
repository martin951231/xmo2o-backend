<style>
 	html,body{
 		background:white;
 	}
 	.layui-main {
 	    margin: 20px;
 		line-height: 22px;
 		width: auto;
 	}
 	
 	
 	/* 首页 */
 	.application-list {
 	    margin-bottom: 24px;
 	    -webkit-animation: enterIn .2s ease-out;
 	    animation: enterIn .2s ease-out;
 	    -webkit-animation-fill-mode: forwards;
 	    animation-fill-mode: forwards;
 	}
 	.application-title {
 	    margin-bottom: 16px;
 	    color: #323233;
 	}
 	.application-title .title-main {
 	    font-size: 14px;
 	    font-weight: 700;
 	}
 	.application-title .title-desc {
 	    color: #999;
 	    margin-left: 10px;
 		font-size:12px;
 	}
 	
 	.application-list .application-list-content {
 	    margin-left: -16px;
 	}
 	.application-item {
 	    background: #f8f8f8;
 	    margin-left: 16px;
 	    margin-bottom: 16px;
 	    min-width: 245px;
 	    float: left;
 	    border-radius: 2px;
 	}
 	.application-item   {
 	    width: 100%;
 	    box-sizing: border-box;
 	    zoom: 1;
 	    display: block;
 	    padding: 16px;
 	}
 	.application-item   .application-logo {
 	    float: left;
 	    width: 40px;
 	    height: 40px;
 	    border-radius: 4px;
 	}
 	.application-item   .application-logo>img {
 	    width: 40px;
 	    height: 40px;
 	    vertical-align: bottom;
 	}
 	.application-item  .application-name {
 	    display: -ms-flexbox;
 	    display: flex;
 	    -ms-flex-align: center;
 	    align-items: center;
 	    font-size: 14px;
 	    height: 20px;
 	    line-height: 20px;
 	    vertical-align: bottom;
 	    color: #323233;
 	    font-weight: 700;
 	    margin-left: 50px;
 	}
 	.application-item   .application-desc {
 	    font-size: 12px;
 	    height: 18px;
 	    line-height: 18px;
 	    vertical-align: top;
 	    color: #969799;
 	    text-overflow: ellipsis;
 	    white-space: nowrap;
 	    overflow: hidden;
 	    margin-left: 50px;
 	    margin-top: 2px;
 	}
 	.clearfix:after {
 	    content: "";
 	    display: table;
 	    clear: both;
 	} 
 	.support-wxapp {
 	    display: inline-block;
 	    width: 30px;
 	    height: 20px;
 	    background-repeat: no-repeat;
 	    background-size: 12px;
 	    background-position: 10px 3px;
 	    vertical-align: middle;
 	    cursor: pointer;
 	    margin-left: -6px;
 	    background-image: url(../images/wxapp.png);
 	} 
 </style>
 <include file="Public:header"/>
 <div class="main-content" style="background: #fff; padding: 20px 40px;min-height: 1000px;">
 	<!-- <iframe src="{pigcms{$config.site_url}/plugin.php?plugin=index&from=merchant" style="width:100%;border:0;" id="accessUrl"></iframe> -->
	<div class="application-list">
		<div class="application-title">
			<span class="title-main">营销活动</span>
			<span class="title-desc">超多营销玩法，助力商家用户扩散，提升营收</span>
		</div>
		<div class="application-list-content clearfix">
            <li class="application-item" style="width: 379.5px;">
                <a href="{pigcms{:U('Shop/time_limited_discount',array('store_id'=>$_GET['store_id']))}">
                    <div class="application-logo">
                        <img src="{pigcms{$this->config['site_url']}/plugin/villagegroup/market/yx_icon1.png">
                    </div>
                    <div class="application-name">
                        <span class="application-name-content">{pigcms{:L_("限时优惠")}</span>
                        <div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;">
                            <span class="support-wxapp"></span>
                        </div>
                    </div>
                    <div class="application-desc">花式提高客单价</div>
                </a>
            </li>
            <li class="application-item" style="width: 379.5px;">
				<a href="{pigcms{:U('Shop/shop_repurchase',array('store_id'=>$_GET['store_id']))}"> 
					<div class="application-logo">
						<img src="{pigcms{$this->config['site_url']}/plugin/villagegroup/market/yx_icon1_origin.png">
					</div>
					<div class="application-name">
						<span class="application-name-content">{pigcms{:L_("超值换购")}</span>
						<div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;">
							<span class="support-wxapp"></span>
						</div>
					</div>
					<div class="application-desc">花式提高客单价</div>
				</a>
			</li> 
			<li class="application-item" style="width: 379.5px;">
				<a href="{pigcms{:U('Config/discount',array('store_id'=>$_GET['store_id']))}">
					<div class="application-logo">
						<img src="{pigcms{$this->config['site_url']}/plugin/villagegroup/market/yx_icon2.png">
					</div>
					<div class="application-name">
						<span class="application-name-content">{pigcms{:L_("店铺优惠")}</span>
						<div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;">
							<span class="support-wxapp"></span>
						</div>
					</div>
					<div class="application-desc">有效活跃整场活动气氛，吸引顾客下单购买</div>
				</a>
			</li>
			 <li class="application-item" style="width: 379.5px;">
			 	<a href="{pigcms{:U('Shop/fullGiftActivity',array('store_id'=>$_GET['store_id']))}">
			 		<div class="application-logo">
			 			<img src="{pigcms{$this->config['site_url']}/plugin/villagegroup/market/yx_icon3.png">
			 		</div>
			 		<div class="application-name">
			 			<span class="application-name-content">{pigcms{:L_("满赠活动")}</span>
			 			<div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;">
			 				<span class="support-wxapp"></span>
			 			</div>
			 		</div>
			 		<div class="application-desc">吸引顾客提高成交几率</div>
			 	</a>
			 </li>
            <li class="application-item" style="width: 379.5px;">
                <a href="#" onclick="goUrl('{pigcms{$this->config.site_url]}v20/public/platform/#/shopNew/merchant/CollectionPointRebate?store_id={pigcms{$store_id}','','1','')">
                    <div class="application-logo">
                        <img src="{pigcms{$this->config['site_url']}/plugin/villagegroup/market/yx_icon3.png">
                    </div>
                    <div class="application-name">
                        <span class="application-name-content">{pigcms{:L_("集点返券")}</span>
                        <div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;">
                            <span class="support-wxapp"></span>
                        </div>
                    </div>
                    <div class="application-desc">通过优惠券吸引客户多下单</div>
                </a>
            </li>
        </div>
	</div>
	
	
 </div>
 
 <script type="text/javascript">
 	var winHeight = $(window).height()-$('#new_navbar').height()-8;
 	var LeftHeight = $('#sidebar .nav-list').height() + $('#sidebar-collapse').height()+30;
 	$('#accessUrl').height(winHeight > LeftHeight ? winHeight : LeftHeight);
	 function goUrl(url,new_blank,new_tab,new_page) {
				if(new_blank>0){
					window.open(url)
				}else if(new_tab){
					top.openNewTab(url)

				}else if(new_page>0){
                    window.top.location.href = url
                }else{
					window.top.contentScrollToTop()
					location.href=url
				}
			}
 </script>
 
 <include file="Public:footer"/>