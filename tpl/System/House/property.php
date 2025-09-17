<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('House/property')}" class="on">物业列表</a>
					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('House/property_add')}','添加物业',650,500,true,false,false,addbtn,'add',true);">添加物业</a>
					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('House/property_import')}','导入物业',450,150,true,false,false,importbtn,'add',true);">导入物业</a>
<!--					<if condition="$config['buy_sms'] eq 1">-->
<!--						<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('House/sms_add_all')}','添加短信',520,350,true,false,false,addbtn,'add',true);">添加短信</a>-->
<!--					</if>-->
						<!-- <a href="{pigcms{:U('House/door_notice_map')}" target="_blank" >{pigcms{$config['house_name']}开门状态监控</a> -->
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('House/property')}" method="get">
							<input type="hidden" name="c" value="House"/>
							<input type="hidden" name="a" value="property"/>
							筛选: 
							<if condition="$admin_area neq 3">
								<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
							</if>
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
<!--								<option value="village_name" <if condition="$_GET['searchtype'] eq 'village_name'">selected="selected"</if>>{pigcms{$config['house_name']}名称</option>-->
								<option value="property_name" <if condition="$_GET['searchtype'] eq 'property_name'">selected="selected"</if>>物业名称</option>
								<option value="property_phone" <if condition="$_GET['searchtype'] eq 'property_phone'">selected="selected"</if>>物业电话</option>
								<option value="property_address" <if condition="$_GET['searchtype'] eq 'property_address'">selected="selected"</if>>物业地址</option>
<!--								<option value="account" <if condition="$_GET['searchtype'] eq 'account'">selected="selected"</if>>社区账号</option>-->
<!--								<option value="village_id" <if condition="$_GET['searchtype'] eq 'village_id'">selected="selected"</if>>社区ID</option>-->
							</select>
							<input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
								<th>物业名称</th>
								<th>物业地址</th>
								<th>物业电话</th>
								<th>访问</th>
<!--								<th>物业管理账号</th>-->
<!--								<th>物业管理账号的密码</th>-->
								<th><?php echo D('House_village')->getHouseAlias('house_name')?:'小区'?>列表</th>
								<th>物业服务详情</th>
								<th><a href="{pigcms{:U('House/property',array('sort'=>'lastTime'))}" id="add_class" style="color:blue;">最后登录时间{pigcms{$style}</a></th>
<!--								<th>访问</th>-->
<!--								<th>账单</th>-->
<!--								<th>{pigcms{$config.house_market_name}账单</th>-->
								<th class="textcenter">状态</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($property_list)">
								<volist name="property_list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>
                                            <if condition="$vo['status'] neq 2">
                                                <a onclick="go_property({pigcms{$vo['id']})" style="cursor:pointer;" target="_blank">{pigcms{$vo.property_name}</a>
                                                <else/>
                                                {pigcms{$vo.property_name}
                                            </if>
                                            <if condition="$vo['facilities_type_text'] && $vo['facilities_type_text'] neq ''">
                                                <span style="color:#2681f3;">[{pigcms{$vo.facilities_type_text}]</span>
                                            </if>
                                            <if condition="$vo['saasText'] && $vo['saasText'] neq ''">
                                                <span style="font-weight: bolder;color: red;">（{pigcms{$vo.saasText}）</span>
                                            </if>
                                        </td>
										<td>{pigcms{$vo.property_address}</td>
										<td>{pigcms{$vo.property_phone}</td>
										<td>
											<if condition="$vo['status'] neq 2">
												<a onclick="go_property({pigcms{$vo['id']})" style="cursor:pointer;" target="_blank">访问</a>
												<else/>
												禁止状态
											</if>
										</td>
<!--										<td>{pigcms{$vo.account}</td>-->
<!--										<td>{pigcms{$vo.password}</td>-->
										<td>
                                            <if condition="$vo['status'] neq 2">
                                                <a href="{pigcms{:U('House/village',array('property_id'=>$vo['id']))}">进入<?php echo D('House_village')->getHouseAlias('house_name')?:'小区'?>列表</a>
                                            <else/>
											禁止状态
											</if>
										</td>
										<td>
                                            <if condition="$vo['package_order'] && $vo['package_order']['order_id']">
                                                <div onclick="packageOrderDetail({pigcms{$vo['package_order']['order_id']})"  style="cursor:pointer;color: {pigcms{$vo['package_order']['font_color']};padding: 5px 0;">
                                                    <div style="padding-bottom: 5px; ">
                                                        套餐名称： {pigcms{$vo['package_order']['package_title']}
                                                    </div>
                                                    <div>
                                                        套餐到期时间： {pigcms{$vo['package_order']['tip']}
                                                    </div>
                                                </div>
                                                <else/>
                                                暂无套餐
                                            </if>
                                        </td>
										<td><if condition="$vo['last_time']">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}<else/>从未登录</if></td>

										<td class="textcenter"><if condition="$vo['status'] eq 1"><font color="green">正常</font><elseif condition="$vo['status'] eq 3"/><font color="red" title="等待管理员审核通过">待审核</font><elseif condition="$vo['status'] eq 0"/><font color="red" title="等待{pigcms{$config['house_name']}管理员登录<?php echo D('House_village')->getHouseAlias('house_name')?:'小区'?>后台完善信息">待完善信息</font><else/><font color="red">禁止</font></if></td>

										<td class="textcenter"><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('House/property_edit',array('id'=>$vo['id']))}','编辑物业信息',800,650,true,false,false,editbtn,'edit',true);">编辑</a>
<!--                                            &nbsp;|&nbsp;<a class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('House/del_property')}">删除</a>-->
                                        </td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="10">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="10">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
		<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
        <script>
            function packageOrderDetail(order_id) {
                var url = "{pigcms{:U('House/package_order')}&order_id="+order_id;
                window.top.artiframe(url,'物业服务详情',500,650,true,false,false,false,'detail',true);
            }


            function go_property(property_id){
                $.post("{pigcms{:U('House/property_login_v20')}&property_id="+property_id,function(result){
                    $.cookie('property_access_token',result.data.ticket)
                    setTimeout(function(){
                        window.open(result.data.url);
                    },600)
                },'json');
            }
        </script>
<include file="Public:footer"/>