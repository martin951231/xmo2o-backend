
<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Merchant/index')}" class="on">商家列表</a>|
					<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/add')}','添加商家',800,560,true,false,false,addbtn,'add',true);">添加商家</a>
					<if condition="C('config.lakala_pay_switch') neq null">
						<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/downloadLakalaBill')}','【拉卡拉】下载商户对账文件',600,500,true,false,false,false,'',true);">【拉卡拉】下载商户对账文件</a>
					</if>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td style="width:75%;">
						<form action="{pigcms{:U('Merchant/index')}" method="get">
							<input type="hidden" name="c" value="Merchant"/>
							<input type="hidden" name="a" value="index"/>
							筛选: 
							<if condition="$admin_area neq 3">
							<div id="choose_pca" street_id="{pigcms{$_GET.street_id}" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
							</if>
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>商家名称</option>								
								<option value="store" <if condition="$_GET['searchtype'] eq 'store'">selected="selected"</if>>店铺名称</option>								
								<option value="account" <if condition="$_GET['searchtype'] eq 'account'">selected="selected"</if>>商家帐号</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>联系电话</option>
								<option value="mer_id" <if condition="$_GET['searchtype'] eq 'mer_id'">selected="selected"</if>>商家编号</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							商家状态: <select name="searchstatus">
								<option value="0" <if condition="$_GET['searchstatus'] eq 0">selected="selected"</if>>正常</option>
								<option value="1" <if condition="$_GET['searchstatus'] eq '1'">selected="selected"</if>>待审核</option>
								<option value="2" <if condition="$_GET['searchstatus'] eq '2'">selected="selected"</if>>关闭</option>
								<option value="3" <if condition="$_GET['searchstatus'] eq '3'">selected="selected"</if>>全部</option>
							</select>
                            <if condition="C('customization.internal_membership_card')">
                                <span style="padding: 0px 5px 0px 5px;">
                                     一卡通: <select name="ecard_pay">
                                        <option value="0" <if condition="$_GET['ecard_pay'] eq 0">selected="selected"</if>>全部</option>
                                        <option value="1" <if condition="$_GET['ecard_pay'] eq 1">selected="selected"</if>>启用</option>
                                        <option value="2" <if condition="$_GET['ecard_pay'] eq 2">selected="selected"</if>>未启用</option>
                                    </select>
                                </span>
                            </if>
							排序: <select name="searchorder">
								<option value="0" <if condition="$_GET['searchorder'] eq 0">selected="selected"</if>>商家ID</option>
								<option value="1" <if condition="$_GET['searchorder'] eq '1'">selected="selected"</if>>商家余额</option>
						
							</select>
                            <if condition="$config['merchant_type_switch'] eq 1 && $type_list">
                                商家类型：
                                <select name="merchant_type">
                                    <option value="0">全部类型</option>
                                    <volist name="type_list" id="vo">
                                        <option value="{pigcms{$vo['type_id']}" <if condition="$_GET['merchant_type'] eq $vo['type_id']">selected="selected"</if>>{pigcms{$vo['type_name']}</option>
                                    </volist>
                                </select>
                            </if>
							<input type="submit" value="查询" class="button"/>
							
						</form>
					</td>
					<td>
						<b>平台商家总余额：{pigcms{$all_money}</b>
					</td> 
					<notempty name="export">
					<td>
						<a  href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出列表</a>
					</td>
					</notempty>
				</tr>
			</table>
			
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup><col> <col> <col> <col><col><col><col><col><col width="240" align="center"> </colgroup>
						<thead>
							<tr>
								<th>编号</th>
				
								<th>商家名称</th>

								<if condition="C('customization.merchant_settle_in') eq 1">
									<th>商家标签</th>
								</if>

                                <if condition="$config.open_c2b_spread eq 1">
                                    <th>注册来源</th>
                                </if>
								<if condition="!isset($config['not_o2o_demo'])">
								<th>联系电话</th>
								</if>
								
								<th>最后登录时间</th>

                                <if condition="$notShow eq 1">
                                <else/>
                                    <th class="textcenter">访问该商家后台</th>
                                </if>

								<th class="textcenter">微官网点击数</th>
								<th class="textcenter">粉丝数</th>
								<th class="textcenter">账户余额</th>
								<if condition="C('config.open_score_pay_back_mer') eq 1">
									<th class="textcenter">积分</th>
								</if>
								<if condition="$config.open_juhepay eq 1">
								<th class="textcenter">聚合账号</th>
								</if>
								<th>状态</th>
                                <if condition="C('customization.internal_membership_card')">
                                    <th>一卡通</th>
                                </if>
								<if condition="C('config.open_extra_price') eq 1">
									<th>{pigcms{:C('config.extra_price_alias_name')}结算</th>
								</if>
								
								<th class="textcenter">商家账单</th>
								<if condition="C('config.pay_weixin_sp_xiaowei_open') eq 1">
								<th class="textcenter">小微商户</th>
								</if>
								<!--th class="textcenter">微店账单</th-->
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($merchant_list)">
								<volist name="merchant_list" id="vo">
									<tr>
										<td>{pigcms{$vo.mer_id}</td>
								
										<td>{pigcms{$vo.name}</td>

										<if condition="C('customization.merchant_settle_in') eq 1">
											<if condition="$vo.settle_in_money GT 0 ">
												<td><span style="color: #ff6f00">{pigcms{$vo.settle_in_sign}</span></td>
											<else />
												<td><span style="color: #c8c8c8">{pigcms{$vo.settle_in_sign}</span></td>
											</if>
										</if>

                                        <if condition="$config.open_c2b_spread eq 1">
                                            <td>
                                                <if condition="$vo.user_nickname neq '' || $vo.user_phone neq ''">
                                                    邀请人推荐（ <php>if ($vo['user_nickname'] != '') {</php>昵称:{pigcms{$vo.user_nickname}<php>}</php>  <php>if ($vo['user_phone'] != '') {</php>电话:{pigcms{$vo.user_phone}<php>}</php> 编号:{pigcms{$vo.user_uid}）
                                                    <else/>
                                                    -
                                                </if>
                                            </td>
                                        </if>
										<if condition="!isset($config['not_o2o_demo'])">
										<td>{pigcms{$vo.phone}</td>
										</if>
										

										<td><if condition="$vo['last_time']">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}<else/>无</if></td>

                                        <if condition="$notShow eq 1">
                                        
										<else/>
											<td class="textcenter">
												<if condition="$vo['status'] eq 1 OR $vo['status'] eq 3">
													<a class="__full_screen_link" onclick="go_merchant({pigcms{$vo.mer_id})" style="cursor:pointer;">访问</a>
												<else/>
													<a href="javascript:alert('商家状态不正常，无法访问！请先修改商家状态。');" class="__full_screen_link">访问</a>
												</if>
											</td>
                                        </if>
										<td class="textcenter">{pigcms{$vo.hits}</td>
										<td class="textcenter">{pigcms{$vo.fans_count}</td>
										<td class="textcenter">{pigcms{$vo.money}</td>
										<if condition="C('config.open_score_pay_back_mer') eq 1">
											<td class="textcenter">{pigcms{$vo.system_score}</td>
										</if>
										<if condition="$config.open_juhepay eq 1">
										<td class="textcenter"><if condition="$vo.juhe_code eq ''">未配置<else />{pigcms{$vo.juhe_code}</if></td>
										</if>
										<td>
											<if condition="$vo['status'] eq 1">
												<font color="green">启用</font>
											<elseif condition="$vo['status'] eq 2"/>
												<font color="red">待审核</font>
											<elseif condition="$vo['status'] eq 3"/>
												<font color="red">欠款</font>
											<else/>
												<font color="red">关闭<if condition="$vo['merchant_end_time'] && $vo['merchant_end_time'] lt time()">（服务时间到期）</if></font>
											</if>
										</td>
                                        <if condition="C('customization.internal_membership_card')">
                                            <td><if condition="$vo['is_internal_card_pay'] eq 1"><font color="green">开启</font><else/><font color="red">关闭</font></if></td>
                                        </if>
										<if condition="C('config.open_extra_price') eq 1">
											<td> 商家欠平台{pigcms{$vo.extra_price_pay_for_system}个{pigcms{:C('config.extra_price_alias_name')},即{pigcms{:sprintf("%.2f",$vo['extra_price_pay_for_system']*$vo['extra_price_percent']/100)}{pigcms{$config['Currency_txt']}</td>
										</if>
										
										<td class="textcenter"><a href="{pigcms{:U('Bill/merchant_money_list',array('mer_id'=>$vo['mer_id']))}">查看账单</a></td>
										<if condition="C('config.pay_weixin_sp_xiaowei_open') eq 1">
										<td class="textcenter">
											<if condition="$vo.xiaowei eq '1'">
											<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/add_xiaowei',array('mer_id'=>$vo['mer_id']))}','录入进件信息',800,800,true,false,false,editbtn,'edit',true);">立即进件</a>
											<elseif condition="$vo.xiaowei eq '2'"/>
												<span style="color:red;">微信官方正在审核</span>
											<elseif condition="$vo.xiaowei eq '3'"/>
												<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/view_status',array('mer_id'=>$vo['mer_id']))}','查看微信审核结果',800,800,true,false,false,editbtn,'edit',true);">查看审核结果</a>
											<elseif condition="$vo.xiaowei eq '4'"/>
												<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/add_xiaowei',array('mer_id'=>$vo['mer_id']))}','录入进件信息',800,800,true,false,false,editbtn,'edit',true);">审核失败，重新进件</a>
											<elseif condition="$vo.xiaowei eq '5'"/>
												<a href="javascript:void(0);" style="color:green;" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/view_status',array('mer_id'=>$vo['mer_id']))}','查看微信审核结果',800,800,true,false,false,editbtn,'edit',true);">已通过审核(待商户扫码)</a>
											<elseif condition="$vo.xiaowei eq '6'"/>
												<span style="color:green;">进件流程已完成</span>
											</if>
										</td>
										</if>
										<!--td class="textcenter"><a href="{pigcms{:U('Merchant/weidian_order',array('mer_id'=>$vo['mer_id']))}">微店账单</a></td-->
										<td class="textcenter">
                                        <if condition="$open_yibao == 1">
										<a href="javascript:void(0);" onclick="window.o2oTop.iframeDrawer({
											url: '/v20/public/platform/#/merchant/platform/NetInDetail?mer_id={pigcms{$vo[\mer_id\]}',
											id: '',
											title: '入网信息', //标题
											placement: 'right', //弹出方向 top、right、bottom、left 默认右侧right
											closable: true, //是否显示关闭按钮
											visible: true, //是否显示
											width: '800',
											height: '256',
											zIndex: 1000,
											drawerIframeStyle: {
												width: '100%',
												height: '100vh'
											}
											});">查看入网信息</a> |
                                        </if>
										<a href="{pigcms{:U('Merchant/store',array('mer_id'=>$vo['mer_id']))}">店铺列表</a>
										<!--<a href="javascript:void(0);" onclick="window.o2oTop.show_other_frame('Group','product','mer_id={pigcms{$vo.mer_id}')">{pigcms{$config.group_alias_name}列表</a> | -->
										<!--<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/edit',array('mer_id'=>$vo['mer_id'],'frame_show'=>true))}','查看详细信息',520,370,true,false,false,false,'detail',true);">查看</a> | -->
										<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/edit',array('mer_id'=>$vo['mer_id']))}','编辑商家信息',800,650,true,false,false,editbtn,'edit',true);">编辑</a> |
										
										<a href="javascript:void(0);" class="delete_row" parameter="mer_id={pigcms{$vo.mer_id}" url="{pigcms{:U('Merchant/del')}">删除</a>
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="13">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
		
		</div>
		<script>
			var url = "{pigcms{$config.site_url}"
			var export_url = "{pigcms{:U('Merchant/export_info')}"
			function go_merchant(mer_id){
                $.post("{pigcms{:U('Merchant/merchant_login_v20')}&mer_id="+mer_id,function(result){
                    console.log(result)
                    $.cookie('merchant_access_token',result.data.ticket)
                    setTimeout(function(){
                        window.open(result.data.url);
                    },600)
                    // if(result.status == 1){

                    // 	// window.open("{pigcms{:U('Merchant/merchant_login')}&mer_id=" + mer_id);
                    // }else{
                    // 	if(confirm('您当前已经登录了 “' + result.info.merchant_login_name + '” 商家。如果您正在使用商家页面增加或修改数据，继续登录可能会导致数据紊乱，请谨慎确定是否还需要登录。（您可以在商家后台先进行退出再来此页面访问商家）')){
                    // 		window.open("{pigcms{:U('Merchant/merchant_login')}&mer_id=" + mer_id);
                    // 	}
                    // }
                },'json');
			}
		</script>
		<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>