<include file="NewPublic:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-home home-icon"></i>
			<li class="active">首页</li>
		</ul>
	</div>
	<!-- 内容头部 -->
    <if condition="!$enterprise_wx_corpid">
        <div class="tips">
            <span>我们系统陪伴您工作了{pigcms{$days|number_format=###}天，您一共收入了{pigcms{$sum_money}元，您还有{pigcms{$sum_count|number_format=###}待处理数据</span><br/>
            <span>企业微信，作为“物业管理行业的数字基底”，为物业提供一个简单高效的移动办公平台，且通过各类实用功能连接业主并且能将用户数据转为实际流量，为物业增值增收。还可通过监控业主、员工、群聊的舆情管理，全方位保障物业服务质量，提高业主满意度。</span>
            <br>
            <button class="btn1" onclick="window.location.href='{pigcms{:U('EnterpriseWeixin/index',array(\'iframe\'=> $_GET[\'iframe\']))}'">前往授权登录</button>
        </div>
    </if>
    <div class="page-content page-home">
		<div class="home-main">
			<div class="row">
				<div class="am-margin-bottom">
					<div class="widget am-cf">
						<div class="widget-head">
							<div class="widget-title">数据统计</div>
						</div>
						<div class="widget-body clearfix">
							<div class="am-u-sm-12 am-u-md-6 am-u-lg-3">
								<div class="widget-card card__blue am-cf" style="cursor:pointer;" onclick="into_url('{pigcms{$propertyGroupUrl}','{pigcms{$is_v20}')">
									<div class="card-header">工作人员数量</div>
									<div class="card-body">
										<div class="card-value">{pigcms{$worker_count|number_format=###}</div>
										<div class="card-description">物业当前工作人员总数</div>
										<span class="card-icon iconfont icon-fangjian"></span>
									</div>
								</div>
							</div>
							<div class="am-u-sm-12 am-u-md-6 am-u-lg-3" style="cursor:pointer;" onclick="window.location.href='{pigcms{:U('NewProperty/index',array(\'iframe\'=> $_GET[\'iframe\']))}'">
								<div class="widget-card card__red am-cf">
									<div class="card-header"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>数量</div>
									<div class="card-body">
										<div class="card-value">{pigcms{$village_count|number_format=###}</div>
										<div class="card-description">当前物业下<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>总数量</div>
										<span class="card-icon iconfont icon-huiyuan"></span>
									</div>
								</div>
							</div>
							<div class="am-u-sm-12 am-u-md-6 am-u-lg-3" style="cursor:pointer;" onclick="addRoomList()">
								<div class="widget-card card__violet am-cf">
									<div class="card-header"><?php echo (D('House_village')->getHouseAlias('room_name')?:'房间')?>数量</div>
									<div class="card-body">
										<div class="card-value">{pigcms{$room_count|number_format=###}</div>
										<div class="card-description">物业<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间')?>总数量</div>
										<span class="card-icon iconfont icon-cheliang"></span>
									</div>
								</div>
							</div>
							<div class="am-u-sm-12 am-u-md-6 am-u-lg-3" style="cursor:pointer;" onclick="addUserList()">
								<div class="widget-card card__primary am-cf">
									<div class="card-header">业主数量</div>
									<div class="card-body">
										<div class="card-value">{pigcms{$user_count|number_format=###}</div>
										<div class="card-description">业主总数量</div>
										<span class="card-icon iconfont icon-chewei"></span>
									</div>
								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="am-margin-bottom">
					<div class="widget clearfix">
						<div class="widget-head">
							<div class="widget-title">实时概况</div>
						</div>
						<div class="widget-body clearfix">
							<div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5">
								<div class="widget-outline dis-flex flex-y-center">
									<div class="outline-left">
										<img src="{pigcms{$static_path}images/new/audit.png" alt=""/>
									</div>
                                    <div class="outline-right dis-flex flex-dir-column flex-x-between" onclick="audit_user_list()" style="cursor: pointer;" title="待审核业主列表">
                                        <div style="color: rgb(102, 102, 102); font-size: 1.3rem;">待审核业主数</div>
                                        <div style="color: #38f; font-size: 2.4rem;">{pigcms{$audit_user_count|number_format=###}</div>
                                        <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                    </div>
								</div>
							</div>
                            <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="audit_child_list()" style="cursor: pointer;" title="待审核<if condition="$config['open_house_user_name']">亲友<else/>家属</if>列表">
                                <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                    <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待审核<if condition="$config['open_house_user_name']">亲友<else/>家属</if>数</div>
                                    <div style="color:#38f; font-size: 2.4rem;">{pigcms{$audit_child_count|number_format=###}</div>
                                    <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                </div>
                            </div>
                            <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="audit_tenant_list()" style="cursor: pointer;" title="待审核租客数">
                                <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                    <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待审核租客数</div>
                                    <div style="color:#38f; font-size: 2.4rem;">{pigcms{$audit_tenant_counts|number_format=###}</div>
                                    <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                </div>
                            </div>
                            <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="baoxiu_list()" style="cursor: pointer;" title="待处理报修列表">
                                <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                    <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待处理报修</div>
                                    <div style="color:#38f; font-size: 2.4rem;">{pigcms{$baoxiu_count|number_format=###}</div>
                                    <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                </div>
                            </div>
                            <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="bbs_list()" style="cursor:pointer;" title="待审核文章">
                                <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                    <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待审核文章</div>
                                    <div style="color:#38f; font-size: 2.4rem;">{pigcms{$bbs_count|number_format=###}</div>
                                    <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                </div>
                            </div>
						</div>
						<div class="widget-body clearfix">
							<div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5">
								<div class="widget-outline dis-flex flex-y-center">
									<div class="outline-left">
										<img src="{pigcms{$static_path}images/new/todo.png" alt=""/>
									</div>
                                    <div class="outline-right dis-flex flex-dir-column flex-x-between" onclick="water_list()" style="cursor: pointer;" title="待处理水电煤气上报">
                                        <div style="color: rgb(102, 102, 102); font-size: 1.3rem;">待处理水电煤气上报</div>
                                        <div style="color: #38f; font-size: 2.4rem;">{pigcms{$water_count|number_format=###}</div>
                                        <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                    </div>
								</div>
							</div>
                            <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="express_list()" style="cursor:pointer;" title="待审核<if condition="$config['open_house_user_name']">亲友<else/>家属</if>列表">
                                <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                    <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待处理快递</div>
                                    <div style="color:#38f; font-size: 2.4rem;">{pigcms{$express_count|number_format=###}</div>
                                    <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                </div>
                            </div>
                            <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="unbind_list()" style="cursor:pointer;" title="待申请解绑列表">
                                <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                    <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待申请解绑</div>
                                    <div style="color:#38f; font-size: 2.4rem;">{pigcms{$unbind_count|number_format=###}</div>
                                    <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                </div>
                            </div>
                            <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="suggest_list()" style="cursor:pointer;" title="待处理投诉建议列表">
                                <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                    <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待处理投诉建议</div>
                                    <div style="color:#38f; font-size: 2.4rem;">{pigcms{$suggest_count|number_format=###}</div>
                                    <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                </div>
                            </div>
						</div>
					</div>
				</div>
			</div>
			<!-- 近七日交易走势 -->
			<div class="row">
				<div class="am-margin-bottom">
					<div class="widget clearfix">
						<div class="widget-head">
							<div class="widget-title">近七日收入走势</div>
							<div class="" style="float: right;" onclick="bill_list()"><a href="javascript:;">点击详情</a></div>
						</div>

						<div class="widget-body clearfix">
							<div id="echarts-trade" class="widget-echarts"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="home-aside">
            <if condition="$config.site_qq && $config.site_qq neq ''">
                <a href="http://wpa.qq.com/msgrd?v=3&uin={pigcms{$config.site_qq}&site=qq&menu=yes" target="_blank">
                    <else/>
                    <a href="javascript:void(0);">
            </if>
				<div class="style__style--3Izek">
					<div class="style__style--3zzla">
						<div class="icon icon-headset" style="background-image:url({pigcms{$static_path}images/new/headset.png);"></div>
						<h4 class="title">在线客服</h4>
						<p class="desc">电话：{pigcms{$config.site_phone}</p>
					</div>
				</div>
			</a>
			<div class="style__style--3Izek style__style--j3wmV">
				<div class="zent-popover-wrapper zent-pop-wrapper" id="showMerchantAppPop" style="display:inline-block;">
					<div class="style__style--3zzla">
						<div class="icon icon-app" style="background-image:url({pigcms{$static_path}images/new/mobile.png);"></div>
						<h4 class="title">移动客户端</h4>
						<p class="desc">使用手机、Pad管理社区</p>
					</div>
					<div class="zent-portal zent-pop zent-popover zent-popover-internal-id-4 zent-popover-position-left-top hide" id="downMerchantAppPop">
						<div class="zent-popover-content">
							<div class="zent-pop-inner">
								<div class="app__style--2j6ge">
									<div class="header-box">扫码下载</div>
									<div class="applications">
										<div class="app-item">
											<img src="/index.php?c=Recognition&a=get_own_qrcode&qrCon={pigcms{$config['site_url']}/topic/app_wap_house_manager.html" alt="手机客户端"/>
											<p>社区管理客户端</p>
										</div>
									</div>
								</div>
							</div>
							<i class="zent-pop-arrow"></i>
						</div>
					</div>
				</div>
			</div>
			<div class="style__style--2KBWx">
                <if condition="$config['property_self_logo'] eq 1">
                    <if condition="$property_weixin_qrcode_url">
                        <div class="style__style--2KBWx">
                            <div class="header-box">
                                <h4 class="header-title">客户服务</h4>
                            </div>
                            <div class="img-box list">
                                <img src="{pigcms{$property_weixin_qrcode_url}" width="100%">
                            </div>
                        </div>
                    </if>
                <elseif condition="$config['platform_property_qr_code']"/>
                    <div class="style__style--2KBWx">
                        <div class="header-box">
                            <h4 class="header-title">客户服务</h4>
                        </div>
                        <div class="img-box list">
                            <img src="{pigcms{$config['platform_property_qr_code']}" width="100%">
                        </div>
                    </div>
                </if>
                <div class="style__style--2KBWx">
                    <div class="header-box">
                        <h4 class="header-title">{pigcms{:L_("物业公告")}</h4>
                    </div>
                    <ul class="list">
                        <volist name="notice_list" id="vo">
                            <li>
                                <a class="<if condition="$vo['is_top'] eq 1">red</if> layer-iframe" href="{pigcms{:U('NewProperty/notice',array('id'=>$vo['id'],'iframe_window'=>'1'))}" data-title="{pigcms{:L_("物业公告")}">{pigcms{$vo.title}</a>
                            </li>
                        </volist>
                    </ul>
                </div>
                <if condition="$plat_news_count && $plat_news_count gt 0">
                    <div class="header-box">
                        <h4 class="header-title">平台快报</h4>
                        <a class="header-link" href="{pigcms{$config.site_url}/news/" target="_blank">更多</a>
                    </div>
                </if>
				<ul class="list">
					<volist name="plat_news_list" id="vo">
						<li>
							<a class="<if condition="$i eq 1">red</if>" href="/news/{pigcms{$vo.id}.html" target="_blank" title="【<if condition="$now_cat">{pigcms{$now_cat.name}<else />{pigcms{$vo.name}</if>】{pigcms{$vo.title}">【<if condition="$now_cat">{pigcms{$now_cat.name}<else />{pigcms{$vo.name}</if>】{pigcms{$vo.title}</a>
						</li>
					</volist>
				</ul>
			</div>
		</div>
	</div>
</div>

<style>
	.page-home .am-u-lg-3.txt_5{
		width: 20% !important;
	}
    .tips {
        position: relative;
        padding: 10px 10px 10px 15px;
        color: rgba(0, 0, 0, 0.65);
        line-height: 1.5;
        border-radius: 2px;
        background-color: #e6f7ff;
        border: 1px solid #91d5ff;
    }
</style>
<script src="{pigcms{$static_public}echarts/echarts.min.js"></script>
<script src="{pigcms{$static_public}echarts/echarts-walden.js"></script>
<script>
    function into_url(url,is_v20=false) {
        if (is_v20) {
            window.top.goV20Url(url);
        } else {
            window.location.href=url;
        }
    }
	if("{pigcms{$_GET['type']}" =='merchant'){
		alert('该商家状态不对,请进行处理')

	}
	var echartsTrade = null;
	$(function(){
        $('.ant-layout-content',window.parent.document).css('z-index',1);
		/**
		 * 近七日交易走势
		 */
		$.post("{pigcms{:U('get_week_order_list')}",function(echart_data){
			echartsTrade = echarts.init(document.getElementById('echarts-trade'),'walden');
			echartsTrade.setOption({
				tooltip: {
					trigger: 'axis'
				},
				legend: {
					data: ['成交量', '成交额']
				},
				toolbox: {
					show: true,
					showTitle: false,
					feature: {
						mark: {show: true},
						magicType: {show: true, type: ['line', 'bar']}
					}
				},
				calculable: true,
				xAxis: {
					type: 'category',
					boundaryGap: false,
					data: echart_data.info.xAxis_txt
				},
				yAxis: {
					type: 'value'
				},
				series: [
					{
						name: '成交额',
						type: 'line',
						data: echart_data.info.order_money_list
					},
					{
						name: '成交量',
						type: 'line',
						data: echart_data.info.order_count_list
					}
				]
			}, true);
		});
		$(window).resize(function(){
			if(echartsTrade){
				echartsTrade.resize();
			}
		});
	});

	function addRoomList(){

		var url = "{pigcms{:U('NewProperty/room_list')}";
		var href = url;
		layer.open({
			type: 2,
			title:"<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间')?>"+'数量',
			content: href,
			area: ['750px', '550px'],
			maxmin: false,

		});
	}

	function addUserList(){
		var url = "{pigcms{:U('NewProperty/user_list')}";
		var href = url;
		layer.open({
			type: 2,
			title:'业主数量',
			content: href,
			area: ['750px', '550px'],
			maxmin: false,

		});
	}

	function audit_user_list(){
		var url = "{pigcms{:U('NewProperty/audit_user_list')}";
		var href = url;
		layer.open({
			type: 2,
			title:'待审核业主数量',
			content: href,
			area: ['750px', '550px'],
			maxmin: false,

		});
	}

	function audit_child_list(){
		var url = "{pigcms{:U('NewProperty/audit_child_list')}";
		var href = url;
		layer.open({
			type: 2,
			title:'待审核家属数量',
			content: href,
			area: ['750px', '550px'],
			maxmin: false,

		});
	}

	function audit_tenant_list() {
		var url = "{pigcms{:U('NewProperty/audit_tenant_list')}";
		var href = url;
		layer.open({
			type: 2,
			title:'待审核租客数量',
			content: href,
			area: ['750px', '550px'],
			maxmin: false,

		});
	}

	function baoxiu_list(){
		var url = "{pigcms{:U('NewProperty/baoxiu_list')}";
		var href = url;
		layer.open({
			type: 2,
			title:'待处理报修数量',
			content: href,
			area: ['750px', '550px'],
			maxmin: false,

		});
	}

	function suggest_list(){
		var url = "{pigcms{:U('NewProperty/suggest_list')}";
		var href = url;
		layer.open({
			type: 2,
			title:'待投诉建议数量',
			content: href,
			area: ['750px', '550px'],
			maxmin: false,

		});
	}

	function water_list(){
		var url = "{pigcms{:U('NewProperty/water_list')}";
		var href = url;
		layer.open({
			type: 2,
			title:'待处理水电煤数量',
			content: href,
			area: ['750px', '550px'],
			maxmin: false,

		});
	}

	function express_list(){
		var url = "{pigcms{:U('NewProperty/express_list')}";
		var href = url;
		layer.open({
			type: 2,
			title:'待处理快递数量',
			content: href,
			area: ['750px', '550px'],
			maxmin: false,

		});
	}

	function unbind_list(){
		var url = "{pigcms{:U('NewProperty/unbind_list')}";
		var href = url;
		layer.open({
			type: 2,
			title:'待解绑数量',
			content: href,
			area: ['750px', '550px'],
			maxmin: false,

		});
	}

	function bbs_list(){
		var url = "{pigcms{:U('NewProperty/bbs_list')}";
		var href = url;
		layer.open({
			type: 2,
			title:'待审核文章数量',
			content: href,
			area: ['750px', '550px'],
			maxmin: false,

		});
	}
	//bill_list
	function bill_list(){
		var url = "{pigcms{:U('NewProperty/bill_list')}";
		var href = url;
		layer.open({
			type: 2,
			title:'各'+"<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>"+'收入详情',
			content: href,
			area: ['750px', '550px'],
			maxmin: false,

		});
	}

</script>
<include file="NewPublic:footer"/>
