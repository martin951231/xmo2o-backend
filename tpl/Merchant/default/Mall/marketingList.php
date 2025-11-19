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
				<a href="{pigcms{:U('Mall/discount',array('store_id'=>$_GET['store_id']))}">
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
                <a href="{pigcms{:U('Mall/time_limited_discount',array('store_id'=>$_GET['store_id'],'from_type'=>'mall'))}">
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
				<a href="{pigcms{:U('Mall/group_list',array('store_id'=>$_GET['store_id']))}">
					<div class="application-logo">
						<img src="{pigcms{$this->config['site_url']}/plugin/villagegroup/market/yx_icon7.png">
					</div>
					<div class="application-name">
						<span class="application-name-content">{pigcms{:L_("拼团管理")}</span>
						<div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;">
							<span class="support-wxapp"></span>
						</div>
					</div>
					<div class="application-desc">商品推广，提升平台用户引流能力</div>
				</a>
			</li> 
			<li class="application-item" style="width: 379.5px;">
				<a href="{pigcms{:U('Mall/periodic_index',array('store_id'=>$_GET['store_id']))}">
					<div class="application-logo">
						<img src="{pigcms{$this->config['site_url']}/plugin/villagegroup/market/yx_icon8.png">
					</div>
					<div class="application-name">
						<span class="application-name-content">{pigcms{:L_("周期购管理")}</span>
						<div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;">
							<span class="support-wxapp"></span>
						</div>
					</div>
					<div class="application-desc">一次订单卖出一年销量，提高用户粘性利器</div>
				</a>
			</li>
			 <li class="application-item" style="width: 379.5px;">
			 	<a href="{pigcms{:U('Mall/pack_index',array('store_id'=>$_GET['store_id']))}">
			 		<div class="application-logo">
			 			<img src="{pigcms{$this->config['site_url']}/plugin/villagegroup/market/yx_icon4.png">
			 		</div>
			 		<div class="application-name">
			 			<span class="application-name-content">{pigcms{:L_("打包一口价")}</span>
			 			<div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;">
			 				<span class="support-wxapp"></span>
			 			</div>
			 		</div>
			 		<div class="application-desc">一口价打包售卖，提升销量，促进购物欲望</div>
			 	</a>
			 </li> 
			 <li class="application-item" style="width: 379.5px;">
			 	<a href="{pigcms{:U('Mall/bargain_list',array('store_id'=>$_GET['store_id']))}">
			 		<div class="application-logo">
			 			<img src="{pigcms{$this->config['site_url']}/plugin/villagegroup/market/yx_icon5.png">
			 		</div>
			 		<div class="application-name">
			 			<span class="application-name-content">{pigcms{:L_("砍价管理")}</span>
			 			<div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;">
			 				<span class="support-wxapp"></span>
			 			</div>
			 		</div>
			 		<div class="application-desc">转发分享，平台高效增长粉丝</div>
			 	</a>
			 </li> 
			 <li class="application-item" style="width: 379.5px;">
			 	<a href="{pigcms{:U('Mall/full_list',array('store_id'=>$_GET['store_id']))}">
			 		<div class="application-logo">
			 			<img src="{pigcms{$this->config['site_url']}/plugin/villagegroup/market/yx_icon6.png">
			 		</div>
			 		<div class="application-name">
			 			<span class="application-name-content">{pigcms{:L_("满减送")}</span>
			 			<div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;">
			 				<span class="support-wxapp"></span>
			 			</div>
			 		</div>
			 		<div class="application-desc">多种促销手段，助力商家提高复购率</div>
			 	</a>
			 </li> 
			 <li class="application-item" style="width: 379.5px;">
			 	<a href="{pigcms{:U('Mall/book_index',array('store_id'=>$_GET['store_id']))}">
			 		<div class="application-logo">
			 			<img src="{pigcms{$this->config['site_url']}/plugin/villagegroup/market/book_icon.png">
			 		</div>
			 		<div class="application-name">
			 			<span class="application-name-content">{pigcms{:L_("预售管理")}</span>
			 			<div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;">
			 				<span class="support-wxapp"></span>
			 			</div>
			 		</div>
			 		<div class="application-desc">提前锁定客户，降低资金压力</div>
			 	</a>
			 </li>
            <li class="application-item" style="width: 379.5px;">
                <a onClick="goUrl({pigcms{$_GET['store_id']})">
                    <div class="application-logo">
                        <img src="{pigcms{$this->config['site_url']}/plugin/villagegroup/market/book_icon.png">
                    </div>
                    <div class="application-name">
                        <span class="application-name-content">{pigcms{:L_("满包邮")}</span>
                        <div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;">
                            <span class="support-wxapp"></span>
                        </div>
                    </div>
                    <div class="application-desc">提前锁定客户，降低资金压力</div>
                </a>
            </li>
		</div>
	</div>
	
	
 </div>
 
 
 <script type="text/javascript">
 	var winHeight = $(window).height()-$('#new_navbar').height()-8;
 	var LeftHeight = $('#sidebar .nav-list').height() + $('#sidebar-collapse').height()+30;
 	$('#accessUrl').height(winHeight > LeftHeight ? winHeight : LeftHeight);
 	function goUrl(id) {
 	    window.top.addIframe('满包邮', '', '/merchant/merchant.mall/shippingList',{store_id: id})
    }
 </script>
 
 <include file="Public:footer"/>