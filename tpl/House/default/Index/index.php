<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-home home-icon"></i>
			<li class="active">首页</li>
		</ul>
	</div>
	<!-- 内容头部 -->
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
								<div class="widget-card card__blue am-cf" style="cursor:pointer;" onclick="<if condition="in_array(111185,$house_session['menus'])">open_new_page('/village/village_api.Room/index','new_v20')<else/>layer.alert('您没有权限查看，请联系管理员！');</if>">
									<div class="card-header"><?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间')?>数量</div>
									<div class="card-body">
										<div class="card-value">{pigcms{$room_count|number_format=###}</div>
										<div class="card-description"><?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区')?>当前<?php echo (D('House_village')->getHouseAlias('room_name',$village_id)?:'房间')?>总数</div>
										<span class="card-icon iconfont icon-fangjian"></span>
									</div>
								</div>
							</div>
							<div class="am-u-sm-12 am-u-md-6 am-u-lg-3" style="cursor:pointer;" onclick="<if condition="in_array(90,$house_session['menus'])">open_new_page('{pigcms{:U('User/index')}','house_user_index')<else/>layer.alert('您没有权限查看，请联系管理员！');</if>">
								<div class="widget-card card__red am-cf">
									<div class="card-header" title="真实业主数量(不包含虚拟业主数)">业主数量</div>
									<div class="card-body">
										<div class="card-value">{pigcms{$user_count|number_format=###}</div>
										<div class="card-description">当前入住真实业主总数量</div>
										<span class="card-icon iconfont icon-huiyuan"></span>
									</div>
								</div>
							</div>
							<div class="am-u-sm-12 am-u-md-6 am-u-lg-3" style="cursor:pointer;" onclick="<if condition="$park_new_switch!=1&&in_array(55,$house_session['menus'])">open_new_page('{pigcms{:U('Unit/vehicle_management')}','house_unit_vehicle_management') <elseif condition="in_array(112025,$house_session['menus'])" />open_new_page('/village/yardManagement/vehicleManagement','new_v20')<else />layer.alert('您没有权限查看，请联系管理员！');</if>">
								<div class="widget-card card__violet am-cf">
									<div class="card-header">车辆数量</div>
									<div class="card-body">
										<div class="card-value">{pigcms{$car_count|number_format=###}</div>
										<div class="card-description">已登记车辆总数量</div>
										<span class="card-icon iconfont icon-cheliang"></span>
									</div>
								</div>
							</div>
							<div class="am-u-sm-12 am-u-md-6 am-u-lg-3" style="cursor:pointer;" onclick="<if condition="$park_new_switch!=1&&in_array(45,$house_session['menus'])">open_new_page('{pigcms{:U('Unit/parking_management')}','house_unit_parking_management')<else/>open_new_page('/village/yardManagement/parkingSpace','new_v20')</if>">
								<div class="widget-card card__primary am-cf">
									<div class="card-header">车位数量</div>
									<div class="card-body">
										<div class="card-value">{pigcms{$position_count|number_format=###}</div>
										<div class="card-description">已登记车位总数量</div>
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
					<div class="widget clearfix profile-completion-widget">
						<div class="widget-head">
							<div class="widget-title">个人信息完成度</div>
						</div>
						<div class="widget-body clearfix">
							<form class="profile-completion-filter" method="get" action="{pigcms{:U('Index/index')}">
								<input type="hidden" name="iframe" value="{pigcms{$_GET['iframe']}" />
								<input type="hidden" name="profile_filter_submitted" value="1" />
								<div class="profile-completion-filter-title">统计字段</div>
								<div class="profile-completion-fields">
									<?php if (!empty($profile_completion['fields'])) { ?>
										<button type="button" class="profile-completion-select-trigger">
											<span class="profile-completion-select-text">请选择统计字段</span>
											<span class="profile-completion-clear" title="清除已选"><i class="ace-icon fa fa-times"></i></span>
											<i class="ace-icon fa fa-angle-down"></i>
										</button>
										<div class="profile-completion-select-panel">
											<?php foreach ($profile_completion['fields'] as $field) { ?>
												<label class="profile-completion-field">
													<input type="checkbox" name="profile_fields[]" value="<?php echo htmlspecialchars($field['key'], ENT_QUOTES); ?>" <?php if (!empty($field['checked'])) { ?>checked="checked"<?php } ?> />
													<span><?php echo htmlspecialchars($field['title'], ENT_QUOTES); ?></span>
												</label>
											<?php } ?>
										</div>
									<?php } else { ?>
										<span class="profile-completion-empty">暂无可统计字段</span>
									<?php } ?>
								</div>
								<button type="submit" class="btn btn-sm btn-primary profile-completion-submit">查询</button>
								<a class="btn btn-sm btn-default" href="{pigcms{:U('Index/index',array('iframe'=>$_GET['iframe']))}">重置</a>
							</form>
							<div class="profile-completion-cards">
								<?php foreach (array('all','owner','family','tenant') as $completion_type) { $completion_item = $profile_completion['stats'][$completion_type]; ?>
									<div class="profile-completion-card">
										<div class="profile-completion-card-title"><?php echo htmlspecialchars($completion_item['title'], ENT_QUOTES); ?></div>
										<div class="profile-completion-card-value"><?php echo floatval($completion_item['percent']); ?>%</div>
										<div class="profile-completion-progress">
											<div class="profile-completion-progress-bar" style="width: <?php echo min(100, max(0, floatval($completion_item['percent']))); ?>%;"></div>
										</div>
										<div class="profile-completion-card-desc">
											<?php echo intval($completion_item['numerator']); ?> / <?php echo intval($completion_item['denominator']); ?>
										</div>
										<div class="profile-completion-card-meta">
											<span>总户数：<?php echo intval($completion_item['room_count']); ?></span>
											<span>字段数量：<?php echo intval($completion_item['field_count']); ?></span>
											<span><?php echo $completion_type == 'all' ? '业主+家属+租客数量' : htmlspecialchars($completion_item['title'], ENT_QUOTES) . '数量'; ?>：<?php echo intval($completion_item['people_count']); ?></span>
										</div>
									</div>
								<?php } ?>
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
									<if condition="in_array(101,$house_session['menus'])">
										<div class="outline-right dis-flex flex-dir-column flex-x-between" onclick="javascript:open_new_page('{pigcms{:U('User/audit_index')}','house_user_audit_index')" style="cursor: pointer;" title="待审核业主列表">
											<div style="color: rgb(102, 102, 102); font-size: 1.3rem;">待审核业主数</div>
											<div style="color: #38f; font-size: 2.4rem;">{pigcms{$audit_user_count|number_format=###}</div>
											<div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
										</div>
									</if>
								</div>
							</div>
							<if condition="in_array(103,$house_session['menus'])">
								<div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="javascript:open_new_page('{pigcms{:U('User/bind_audit_list')}','house_user_bind_audit_list')" style="cursor: pointer;" title="待审核<if condition="$config['open_house_user_name']">亲友<else/>家属</if>列表">
									<div class="widget-outline dis-flex flex-dir-column flex-x-between">
										<div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待审核<if condition="$config['open_house_user_name']">亲友<else/>家属</if>数</div>
										<div style="color:#38f; font-size: 2.4rem;">{pigcms{$audit_child_count|number_format=###}</div>
										<div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
									</div>
								</div>
							</if>
							<if condition="in_array(410,$house_session['menus'])">
								<div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="javascript:open_new_page('{pigcms{:U('User/bind_audit_list_tenant')}','house_user_bind_audit_list_tenant')" style="cursor: pointer;" title="待审核租客数">
									<div class="widget-outline dis-flex flex-dir-column flex-x-between">
										<div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待审核租客数</div>
										<div style="color:#38f; font-size: 2.4rem;">{pigcms{$audit_tenant_count|number_format=###}</div>
										<div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
									</div>
								</div>
							</if>
							<if condition="in_array(107,$house_session['menus'])">
								<div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="javascript:open_new_page('{pigcms{:U('User/audit_unbind')}','house_user_audit_unbind')" style="cursor:pointer;" title="待申请解绑列表">
									<div class="widget-outline dis-flex flex-dir-column flex-x-between">
										<div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待申请解绑</div>
										<div style="color:#38f; font-size: 2.4rem;">{pigcms{$unbind_count|number_format=###}</div>
										<div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
									</div>
								</div>
							</if>
						</div>
                        <if condition="in_array(127,$house_session['menus']) || in_array(216,$house_session['menus']) || $showCheckCarNum">
                            <div class="widget-body clearfix">
                                <if condition="in_array(127,$house_session['menus'])">
                                    <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" >
                                        <div class="widget-outline dis-flex flex-y-center">
                                            <div class="outline-left">
                                                <img src="{pigcms{$static_path}images/new/audit.png" alt=""/>
                                            </div>
                                            <div class="outline-right dis-flex flex-dir-column flex-x-between" onclick="javascript:open_new_page('{pigcms{:U('Bbs/aricle_audit_list')}','house_bbs_aricle_audit_list')" style="cursor:pointer;" title="待审核文章">
                                                <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待审核文章</div>
                                                <div style="color:#38f; font-size: 2.4rem;">{pigcms{$bbs_count|number_format=###}</div>
                                                <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                            </div>
                                        </div>
                                    </div>
                                </if>
            
                                <if condition="in_array(216,$house_session['menus']) && in_array(127,$house_session['menus'])">
                                    <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="javascript:open_new_page('{pigcms{:U('Library/visitor_list')}','house_library_visitor_list')" style="cursor:pointer;" title="<?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区')?>今日来访客人统计">
                                        <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                            <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">今日<?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区')?>访客</div>
                                            <div style="color:#38f; font-size: 2.4rem;">{pigcms{$visitor_count|number_format=###}</div>
                                            <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                        </div>
                                    </div>
                                    <else/>
                                    <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5">
                                        <div class="widget-outline dis-flex flex-y-center">
                                            <div class="outline-left">
                                                <img src="{pigcms{$static_path}images/new/audit.png" alt=""/>
                                            </div>
                                            <div class="outline-right dis-flex flex-dir-column flex-x-between" onclick="javascript:open_new_page('{pigcms{:U('Library/visitor_list')}','house_library_visitor_list')" style="cursor:pointer;" title="<?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区')?>今日来访客人统计">
                                                <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">今日<?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区')?>访客</div>
                                                <div style="color:#38f; font-size: 2.4rem;">{pigcms{$visitor_count|number_format=###}</div>
                                                <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                            </div>
                                        </div>
                                    </div>
                                </if>


                                <if condition="$showCheckCarNum && $showCheckCarUrlArr['style'] eq 1">
                                    <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="javascript:open_new_page('{pigcms{$showCheckCarUrlArr['path']}','{pigcms{$showCheckCarUrlArr['path_name']}')" style="cursor:pointer;" title="待审核车辆">
                                        <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                            <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待审核车辆</div>
                                            <div style="color:#38f; font-size: 2.4rem;">{pigcms{$checkCarNum|number_format=###}</div>
                                            <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                        </div>
                                    </div>
                                    <else/>
                                    <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5">
                                        <div class="widget-outline dis-flex flex-y-center">
                                            <div class="outline-right dis-flex flex-dir-column flex-x-between" onclick="javascript:open_new_page('{pigcms{$showCheckCarUrlArr['path']}','{pigcms{$showCheckCarUrlArr['path_name']}')" style="cursor:pointer;" title="待审核车辆">
                                                <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待审核车辆</div>
                                                <div style="color:#38f; font-size: 2.4rem;">{pigcms{$checkCarNum|number_format=###}</div>
                                                <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                            </div>
                                        </div>
                                    </div>
                                </if>
                            </div>
                        </if>

                        <if condition="$water_count_auth == 1 || $express_count_auth == 1 || $baoxiu_count_auth == 1">
                            <div class="widget-body clearfix">
                                <if condition="$water_count_auth == 1">
                                    <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5">
                                        <div class="widget-outline dis-flex flex-y-center">
                                            <div class="outline-left">
                                                <img src="{pigcms{$static_path}images/new/todo.png" alt=""/>
                                            </div>
                                                <div class="outline-right dis-flex flex-dir-column flex-x-between" onclick="javascript:open_new_page('{pigcms{:U('Repair/water')}','house_repair_water')" style="cursor: pointer;" title="待处理水电煤气上报">
                                                    <div style="color: rgb(102, 102, 102); font-size: 1.3rem;">待处理水电煤气上报</div>
                                                    <div style="color: #38f; font-size: 2.4rem;">{pigcms{$water_count|number_format=###}</div>
                                                    <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                                </div>
                                        </div>
                                    </div>
                                </if>
                                <if condition="$express_count_auth == 1 && $water_count_auth == 1">
                                        <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="javascript:open_new_page('{pigcms{:U('Library/express_service_list')}','house_library_express_service_list')" style="cursor:pointer;" title="待处理快递">
                                            <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                                <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待处理快递</div>
                                                <div style="color:#38f; font-size: 2.4rem;">{pigcms{$express_count|number_format=###}</div>
                                                <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                            </div>
                                        </div>
                                    <elseif condition="$express_count_auth == 1"/>
                                        <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5">
                                            <div class="widget-outline dis-flex flex-y-center">
                                                <div class="outline-left">
                                                    <img src="{pigcms{$static_path}images/new/todo.png" alt=""/>
                                                </div>
                                                <div class="outline-right dis-flex flex-dir-column flex-x-between" onclick="javascript:open_new_page('{pigcms{:U('Library/express_service_list')}','house_library_express_service_list')" style="cursor: pointer;" title="待处理快递">
                                                    <div style="color: rgb(102, 102, 102); font-size: 1.3rem;">待处理快递</div>
                                                    <div style="color: #38f; font-size: 2.4rem;">{pigcms{$express_count|number_format=###}</div>
                                                    <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                                </div>
                                            </div>
                                        </div>
                                </if>
                                <if condition="$baoxiu_count_auth == 1 && ($express_count_auth == 1 || $water_count_auth == 1)">
                                        <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="javascript:open_new_page('{pigcms{:U('Repair/index')}','house_repair_index')" style="cursor: pointer;" title="待处理报修列表">
                                            <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                                <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待处理报修</div>
                                                <div style="color:#38f; font-size: 2.4rem;">{pigcms{$baoxiu_count|number_format=###}</div>
                                                <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                            </div>
                                        </div>
                                    <elseif condition="$baoxiu_count_auth == 1"/>
                                        <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5">
                                            <div class="widget-outline dis-flex flex-y-center">
                                                <div class="outline-left">
                                                    <img src="{pigcms{$static_path}images/new/todo.png" alt=""/>
                                                </div>
                                                <div class="outline-right dis-flex flex-dir-column flex-x-between" onclick="javascript:open_new_page('{pigcms{:U('Repair/index')}','house_repair_index')" style="cursor: pointer;" title="待处理报修列表">
                                                    <div style="color: rgb(102, 102, 102); font-size: 1.3rem;">待处理报修</div>
                                                    <div style="color: #38f; font-size: 2.4rem;">{pigcms{$baoxiu_count|number_format=###}</div>
                                                    <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                                </div>
                                            </div>
                                        </div>
                                </if>
                                <if condition="$suggest_count_auth == 1 && ($baoxiu_count_auth == 1 || $express_count_auth == 1 || $water_count_auth == 1)">
                                        <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="javascript:open_new_page('{pigcms{:U('Repair/village_suggest')}','house_repair_village_suggest')" style="cursor:pointer;" title="待处理投诉建议列表">
                                            <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                                <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">待处理投诉建议</div>
                                                <div style="color:#38f; font-size: 2.4rem;">{pigcms{$suggest_count|number_format=###}</div>
                                                <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                            </div>
                                        </div>
                                    <elseif condition="$suggest_count_auth == 1"/>
                                        <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5">
                                            <div class="widget-outline dis-flex flex-y-center">
                                                <div class="outline-left">
                                                    <img src="{pigcms{$static_path}images/new/todo.png" alt=""/>
                                                </div>
                                                <div class="outline-right dis-flex flex-dir-column flex-x-between" onclick="javascript:open_new_page('{pigcms{:U('Repair/village_suggest')}','house_repair_village_suggest')" style="cursor:pointer;" title="待处理投诉建议列表">
                                                    <div style="color: rgb(102, 102, 102); font-size: 1.3rem;">待处理投诉建议</div>
                                                    <div style="color: #38f; font-size: 2.4rem;">{pigcms{$suggest_count|number_format=###}</div>
                                                    <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                                </div>
                                            </div>
                                        </div>
                                </if>
                            </div>
                        </if>
						
						<div class="widget-body clearfix">
							<div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5">
								<div class="widget-outline dis-flex flex-y-center">
									<div class="outline-left">
										<img src="{pigcms{$static_path}images/new/audit.png" alt=""/>
									</div>

										<div class="outline-right dis-flex flex-dir-column flex-x-between" onclick="javascript:open_new_page('/village/village.charge.cashier/receivableOrderList','new_v20')" style="cursor: pointer;" title="账单作废数">
											<div style="color: rgb(102, 102, 102); font-size: 1.3rem;">账单作废数</div>
											<div style="color: #38f; font-size: 2.4rem;">{pigcms{$my_need_order_discard|number_format=###}</div>
											<div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
										</div>
								</div>
							</div>

								<div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="javascript:open_new_page('/village/village.charge.cashier/payableOrderList','new_v20')" style="cursor: pointer;" title="账单退款数">
									<div class="widget-outline dis-flex flex-dir-column flex-x-between">
										<div style="color: rgb(102, 102, 102); font-size: 1.2rem;">账单退款数</div>
										<div style="color:#38f; font-size: 2.4rem;">{pigcms{$my_need_order_refund|number_format=###}</div>
										<div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
									</div>
								</div>
                            
                                <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5" onclick="javascript:open_new_page('{pigcms{:U('User/index')}','house_user_index')" style="cursor: pointer;" title="物业服务时间审核">
                                    <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                        <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">物业服务时间审核</div>
                                        <div style="color:#38f; font-size: 2.4rem;">{pigcms{$my_need_property_check|number_format=###}</div>
                                        <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                    </div>
                                </div>

                                <if condition="$my_modify_money_check gt 0">
                                    <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5 online_files_click" onclick="javascript:open_new_page('/village/village.charge.cashier/receivableOrderList','new_v20')" style="cursor: pointer;" title="账单退款数">
                                        <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                            <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">修改费用审核</div>
                                            <div style="color:#38f; font-size: 2.4rem;">{pigcms{$my_modify_money_check|number_format=###}</div>
                                            <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                        </div>
                                    </div>
                                </if>

                                <if condition="in_array(3,$application_bind)">
                                    <div class="am-u-sm-6 am-u-md-6 am-u-lg-3 txt_5 online_files_click" style="cursor: pointer;" title="在线文件审核" data-url="{pigcms{$online_files['url']}">
                                        <div class="widget-outline dis-flex flex-dir-column flex-x-between">
                                            <div style="color: rgb(102, 102, 102); font-size: 1.2rem;">在线文件审核</div>
                                            <div style="color:#38f; font-size: 2.4rem;">{pigcms{$online_files['count']|number_format=###}</div>
                                            <div style="color: rgb(153, 153, 153); font-size: 1.2rem;">&nbsp;</div>
                                        </div>
                                    </div>
                                </if>

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
						</div>
						<div class="widget-body clearfix">
							<div id="echarts-trade" class="widget-echarts" style="height: 400px;"></div>
						</div>
					</div>
				</div>
			</div>
            <!-- 工作量统计图 -->
            <div class="row">
                <div class="am-margin-bottom">
                    <div class="widget clearfix">
                        <div class="widget-head">
                            <div class="widget-title">工作量统计图</div>
                        </div>
                        <div class="widget-body clearfix">
                            <div class="label_div">
                                <span class="label_span active" data-id="3">本日报修数量</span>
                                <span class="label_span " data-id="2">本月报修数量</span>
                                <span class="label_span " data-id="1">本年报修数量</span>
                            </div>
                            <div class="sjqh-box">
                                <span class="sjqh">切换数据统计</span>
                            </div>
                            <div id="echarts-worksorder-stack" class="widget-echarts" style="height: 400px;"></div>
                            <div id="echarts-sjqh"></div>
                        </div>
                    </div>
                </div>
            </div>

		</div>
		<div class="home-aside">
            <if condition="$config.site_qq && $config.site_qq neq '' && $config.hidden_nline_service != 1">
                <a href="http://wpa.qq.com/msgrd?v=3&uin={pigcms{$config.site_qq}&site=qq&menu=yes" target="_blank">
                <else/>
                <a href="javascript:void(0);">
            </if>
				<div class="style__style--3Izek">
					<div class="style__style--3zzla">
						<div class="icon icon-headset" style="background-image:url({pigcms{$static_path}images/new/headset.png);"></div>
                        <if condition="$config.hidden_nline_service == 1">
                            <h4 class="title">联系电话</h4>
                            <p class="desc">{pigcms{$config.site_phone}</p>
                            <else/>
                            <h4 class="title">在线客服</h4>
                            <p class="desc">电话：{pigcms{$config.site_phone}</p>
                        </if>
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
            <if condition="$plat_news_list && isset($plat_news_list[0]) && $plat_news_list[0]">
                <div class="style__style--2KBWx">
                    <div class="header-box">
                        <h4 class="header-title">平台快报</h4>
                        <a class="header-link" href="{pigcms{$config.site_url}/news/" target="_blank">更多</a>
                    </div>
                    <ul class="list">
                        <volist name="plat_news_list" id="vo">
                            <li>
                                <a class="<if condition="$i eq 1">red</if>" href="/news/{pigcms{$vo.id}.html" target="_blank" title="【<if condition="$now_cat">{pigcms{$now_cat.name}<else />{pigcms{$vo.name}</if>】{pigcms{$vo.title}">【<if condition="$now_cat">{pigcms{$now_cat.name}<else />{pigcms{$vo.name}</if>】{pigcms{$vo.title}</a>
                            </li>
                        </volist>
                    </ul>
                </div>
            </if>
		</div>
	</div>
</div>
<style>
	/*.page-home .am-u-lg-3.txt_5{*/
	/*	width: 20% !important;*/
	/*}*/

    .widget-body .label_div{
        display: inline-block;
        margin-left: 35%;
    }
    .widget-body .active{
        background-color: #1890FF;
        color: #FFFFFF;
        border: 1px solid #1890FF !important;
    }
    .widget-body .label_span{
        border: 1px solid #f3e5e5;
        padding: 8px 15px;
        cursor: pointer;
        margin-right: -5px;
        border-radius:1px 2px 3px 4px;
    }
    .widget-body .label_div :first-child{
        border-radius: 15px 0px 0px 15px;
     }
    .widget-body .label_div :nth-child(2){
        /*border-left: none;*/
        /*border-right: none;*/
    }
    .widget-body .label_div :last-child{
        border-radius: 0px 15px 15px 0px;
    }
    .widget-body #echarts-sjqh{
        min-height: 100px width: 100%;padding: 10px;
    }
    .widget-body .sjqh{
        position:absolute;right: 1.5%;top: 0%;cursor: pointer;padding: 3px;
        border: 1px solid #f3e5e5;
    }

    .widget-body .sjqh-d{
       display: inline-block;margin: 10px;cursor: pointer;
        padding: 10px;
        width: 235px;
    }
    .widget-body .sjqh-t{
        font-weight: bold;
    }
    .widget-body .sjqh-s{
        padding: 8px 0px;
    }
    .profile-completion-widget .widget-body{
        padding: 18px 20px;
    }
    .profile-completion-filter{
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: 8px;
        margin-bottom: 18px;
        position: relative;
    }
    .profile-completion-filter-title{
        color: #344054;
        font-size: 13px;
        line-height: 36px;
        margin-right: 2px;
        font-weight: 600;
        white-space: nowrap;
    }
    .profile-completion-fields{
        position: relative;
        min-width: 420px;
        flex: 1 1 auto;
    }
    .profile-completion-select-trigger{
        position: relative;
        width: 100%;
        height: 36px;
        padding: 0 64px 0 14px;
        border: 1px solid #cfd8e3;
        border-radius: 8px;
        background: #fff;
        color: #344054;
        font-size: 13px;
        line-height: 34px;
        text-align: left;
        outline: none;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .profile-completion-select-trigger:hover,
    .profile-completion-fields.open .profile-completion-select-trigger{
        border-color: #409eff;
        box-shadow: 0 0 0 3px rgba(64, 158, 255, 0.12);
    }
    .profile-completion-select-trigger .fa{
        position: absolute;
        top: 50%;
        right: 14px;
        margin-top: -7px;
        color: #667085;
        line-height: 14px;
        transition: transform .15s ease;
    }
    .profile-completion-fields.open .profile-completion-select-trigger .fa{
        transform: rotate(180deg);
    }
    .profile-completion-select-text{
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .profile-completion-clear{
        display: none;
        position: absolute;
        top: 50%;
        right: 36px;
        width: 20px;
        height: 20px;
        margin-top: -10px;
        border-radius: 50%;
        background: #eef4ff;
        color: #1f6fd1;
        font-size: 12px;
        line-height: 20px;
        text-align: center;
    }
    .profile-completion-clear .fa{
        position: static;
        margin: 0;
        line-height: 20px;
        color: inherit;
        transform: none !important;
    }
    .profile-completion-fields.has-checked .profile-completion-clear{
        display: inline-block;
    }
    .profile-completion-clear:hover{
        background: #dbeafe;
    }
    .profile-completion-select-panel{
        display: none;
        position: absolute;
        top: 42px;
        left: 0;
        z-index: 20;
        width: 100%;
        max-width: 100%;
        max-height: 260px;
        overflow-y: auto;
        padding: 12px;
        border: 1px solid #d9e2ec;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
    }
    .profile-completion-fields.open .profile-completion-select-panel{
        display: block;
    }
    .profile-completion-field{
        display: inline-block;
        margin: 0 8px 8px 0;
        padding: 7px 12px;
        border: 1px solid #e4e7ed;
        border-radius: 16px;
        background: #f8fafc;
        font-size: 13px;
        color: #475467;
        font-weight: normal;
        white-space: nowrap;
        cursor: pointer;
        transition: all .15s ease;
    }
    .profile-completion-field:hover{
        border-color: #b9d7ff;
        background: #f0f7ff;
        color: #1f6fd1;
    }
    .profile-completion-field input{
        display: none;
    }
    .profile-completion-field.is-checked{
        border-color: #409eff;
        background: #eaf4ff;
        color: #1f6fd1;
        box-shadow: inset 0 0 0 1px rgba(64, 158, 255, 0.12);
    }
    .profile-completion-empty{
        color: #999;
        line-height: 30px;
    }
    .profile-completion-submit{
        height: 36px;
        min-width: 70px;
        margin-left: 0;
        border-radius: 8px;
    }
    .profile-completion-filter .btn-default{
        height: 36px;
        line-height: 24px;
        border-radius: 8px;
    }
    .profile-completion-cards{
        display: flex;
        flex-wrap: wrap;
        margin: 0 -7px;
    }
    .profile-completion-card{
        width: calc(25% - 14px);
        border: 1px solid #e8e8e8;
        background: #fff;
        padding: 16px;
        border-radius: 4px;
        min-height: 128px;
        margin: 0 7px 14px;
    }
    .profile-completion-card-title{
        color: #666;
        font-size: 13px;
        line-height: 20px;
    }
    .profile-completion-card-value{
        color: #1f6fd1;
        font-size: 30px;
        line-height: 42px;
        font-weight: 600;
        margin: 4px 0 8px;
    }
    .profile-completion-progress{
        height: 8px;
        background: #edf1f7;
        border-radius: 4px;
        overflow: hidden;
    }
    .profile-completion-progress-bar{
        height: 8px;
        background: #3b82f6;
        border-radius: 4px;
    }
    .profile-completion-card-desc{
        margin-top: 8px;
        color: #999;
        font-size: 12px;
    }
    .profile-completion-card-meta{
        margin-top: 8px;
        color: #666;
        font-size: 12px;
        line-height: 22px;
    }
    .profile-completion-card-meta span{
        display: inline-block;
        margin-right: 14px;
        white-space: nowrap;
    }
    @media (max-width: 1200px){
        .profile-completion-card{
            width: calc(50% - 14px);
        }
    }
    @media (max-width: 640px){
        .profile-completion-filter{
            flex-wrap: wrap;
        }
        .profile-completion-fields{
            min-width: 100%;
        }
        .profile-completion-select-panel{
            width: 100%;
            max-width: 100%;
        }
        .profile-completion-card{
            width: calc(100% - 14px);
        }
    }

</style>
<script src="{pigcms{$static_public}echarts/echarts.min.js"></script>
<script src="{pigcms{$static_public}echarts/echarts-walden.js"></script>
<script>
	var echartsTrade = null,echartsWorksOrderStack=null;
    function refreshProfileCompletionSelect(){
        var $fields = $('.profile-completion-fields');
        var $checked = $fields.find('.profile-completion-field input:checked');
        var titles = [];
        $fields.find('.profile-completion-field').removeClass('is-checked');
        $fields.toggleClass('has-checked', $checked.length > 0);
        $checked.each(function(){
            var $field = $(this).closest('.profile-completion-field');
            $field.addClass('is-checked');
            titles.push($.trim($field.find('span').text()));
        });
        if(titles.length < 1){
            $fields.find('.profile-completion-select-text').text('请选择统计字段');
        }else if(titles.length <= 2){
            $fields.find('.profile-completion-select-text').text(titles.join('、'));
        }else{
            $fields.find('.profile-completion-select-text').text('已选择 ' + titles.length + ' 个字段');
        }
    }
    $(function(){
        refreshProfileCompletionSelect();
        $(document).on('click', '.profile-completion-select-trigger', function(e){
            e.stopPropagation();
            $(this).closest('.profile-completion-fields').toggleClass('open');
        });
        $(document).on('click', '.profile-completion-clear', function(e){
            e.preventDefault();
            e.stopPropagation();
            var $fields = $(this).closest('.profile-completion-fields');
            $fields.find('.profile-completion-field input').prop('checked', false);
            refreshProfileCompletionSelect();
        });
        $(document).on('change', '.profile-completion-field input', refreshProfileCompletionSelect);
        $(document).on('click', '.profile-completion-select-panel', function(e){
            e.stopPropagation();
        });
        $(document).on('click', function(){
            $('.profile-completion-fields').removeClass('open');
        });
    });
    $('#showMerchantAppPop').hover(function(){
        console.log('showMerchantAppPop',1)
        $('#downMerchantAppPop').css({position:'fixed',top:$(this).offset().top,left:$(this).offset().left-160}).removeClass('hide');
    },function(){
        $('#downMerchantAppPop').addClass('hide');
    });

	function open_new_page(url,$path_name='') {
	    var iframe = "{pigcms{$_GET['iframe']}";
        console.log('iframe', iframe);
        console.log('$path_name', $path_name);
		if($path_name=='new_v20'){
			window.top.addIframe('','',url);
		}else if (iframe && $path_name) {
            $path = "/village/village.iframe/"+$path_name;
            window.top.addIframe('','',$path);
        }
    }

    $('.online_files_click').click(function (){
        var v20GroupUrl = $(this).data('url');
        window.top.goV20Url(v20GroupUrl);
    })

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

        get_work_order_stack($('.widget-body .active').attr('data-id'));

		//年月日点击切换事件
		$('.widget-body .label_span').click(function () {
            var that=$(this),
                type=that.attr('data-id'),
                data_type=0,
                is_active=that.hasClass("active");
            that.siblings().removeClass("active");
            that.addClass("active");
            if($('.widget-body .sjqh-box .sjqh').hasClass("active")){
                data_type=1;
            }
            if(!is_active){
                get_work_order_stack(type,data_type)
            }
        })

        //切换数据统计
        $('.widget-body .sjqh').click(function () {
            var that=$(this),
                type=$('.widget-body .label_div .active').attr('data-id'),
                data_type=0,
                is_active=that.hasClass("active");
            if(is_active){
                that.removeClass("active");
                $('.widget-body .label_div,#echarts-worksorder-stack').show();
            }else{
                data_type=1;
                that.addClass("active");
                $('.widget-body .label_div,#echarts-worksorder-stack').hide();
            }
            get_work_order_stack(type,data_type)
        })

        //切换数据统计 点击跳转
        function clickFunc(){
            $('.widget-body .sjqh-d').click(function () {
                var that=$(this),
                    group_id=that.attr('data-id'),
                    title=that.attr('data-title'),
                    date_type=$('.widget-body .label_div .active').attr('data-id'),
                    url = "{pigcms{:U('Index/get_work_order_class')}"+'&date_type=1&data_type=1&group_id='+group_id;
                layer.open({
                    type: 2,
                    title: '选择【'+title+'】',
                    shadeClose: false,
                    maxmin: true, //开启最大化最小化按钮
                    area: ['85%', '80%'],
                    content: url
                });
            })
        }

        /**
         * 工作量柱状统计图
         */
        function get_work_order_stack(type,data_type=0){
            $.post("{pigcms{:U('get_work_order_stack')}",{date_type:type,data_type:data_type},function(echart_data){
                if(!echart_data.info.error && echart_data.info.msg){
                     layer.msg(echart_data.info.msg);
                }
                if(data_type > 0){
                    var str='';
                    var dd=echart_data.info.data;
                    for (var i=0;i<dd.length;i++) {
                        str+='<div class="sjqh-d" style="background: '+dd[i].background+'" data-id="'+dd[i].group_id+'" data-title="'+dd[i].title+'"><span class="sjqh-t">'+dd[i].title+'</span><div class="sjqh-s"><div>本年报事数量：'+dd[i].year_data.total+'</div><div>(及时数：'+dd[i].year_data.timely_num+' 及时率：'+dd[i].year_data.timely_rate+')</div></div><div class="sjqh-s"><div>本月报事数量：'+dd[i].month_data.total+'</div><div>(及时数：'+dd[i].month_data.timely_num+' 及时率：'+dd[i].month_data.timely_rate+')</div></div><div class="sjqh-s"><div>本日报事数量：'+dd[i].day_data.total+'</div><div>(及时数：'+dd[i].day_data.timely_num+' 及时率：'+dd[i].day_data.timely_rate+')</div></div></div>';
                    }
                    $('#echarts-sjqh').html(str);
                    clickFunc();
                    return false;
                }else{
                    $('#echarts-sjqh').html('');
                }
                echartsWorksOrderStack = echarts.init(document.getElementById('echarts-worksorder-stack'),'walden');
                echartsWorksOrderStack.setOption({
                    title: {},
                    tooltip: {
                        trigger: 'axis',
						axisPointer: {
						  type: 'shadow',
						  label: {
							show: true
						  }
						},
						formatter(params){
							let result = params[1].value*1/(params[0].value*1)*100, pre='0';
                            if(result > 0){
                                pre=result.toFixed(2)
                            }
							return '及时率' +":"+pre+'%';
						}
                    },
                    legend: {
                        data: ['报修总数', '及时数'],
                        x:'right',
                        padding:[10,150,0,0]
                    },
                    toolbox: {
                        show: true,
                        feature: {
                            dataView: {show: true, readOnly: false},
                            magicType: {show: true, type: ['line', 'bar']},
                            restore: {show: true},
                            saveAsImage: {show: true}
                        }
                    },
                    calculable: true,
                    xAxis: [echart_data.info.data.xAxisData],
                    yAxis: [
                        {
                            type: 'value'
                        }
                    ],
                    series: echart_data.info.data.seriesData
                }, true);
                echartsWorksOrderStack.on('click', function (params) {
                    var groupData=echart_data.info.data.groupData;
                    if(!groupData){
                        return layer.msg('暂无数据,请稍后重试');
                    }
                    chooseBranch(groupData,params);
                });
            });

        }

        //柱状图点击事件
        function chooseBranch(groupData,params) {
            var date_type=$('.widget-body .active').attr('data-id'),
                url = "{pigcms{:U('Index/get_work_order_class')}"+'&date_type='+date_type+'&data_type=0&group_id='+groupData[params.name];
            layer.open({
                type: 2,
                title: '选择【'+params.name+'】',
                shadeClose: false,
                maxmin: true, //开启最大化最小化按钮
                area: ['85%', '80%'],
                content: url
            });
        }

	});
</script>
<include file="Public:footer"/>
