<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Deliver/user')}" class="on">配送员管理</a>|
					<a href="{pigcms{:U('Deliver/lenTime')}">配送员时长统计</a>|
                    <a href="{pigcms{:U('Deliver/applyUser')}">待审核配送员</a>|
                    <if condition="C('config.open_scan_qrcode_receive') eq 1">
                        <a href="{pigcms{:U('Deliver/scan_report')}">配送员扫码收单统计</a>|
                    </if>
					<a href="{pigcms{:U('Deliver/reply')}">配送员评论列表</a>|
					<a href="{pigcms{:U('Deliver/cancel')}">配送员扔回订单列表</a>|
					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('user_add')}','添加配送员',900,560,true,false,false,editbtn,'edit',true);">添加配送员</a>
					<!-- <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('field')}','配送员注册自定义字段',580,360,true,false,false,false,'detail',true);">配送员注册自定义字段</a> -->
                    <if condition="$show_merchant_user eq 1">
                    <a href="{pigcms{:U('Deliver/merchant_user')}">商家配送员管理</a>
                    </if>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Deliver/user')}" method="get">
							<input type="hidden" name="c" value="Deliver"/>
							<input type="hidden" name="a" value="user"/>
							筛选:
                            <div id="choose_pca" street_id="{pigcms{$_GET.street_id}"  province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>

                            <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="uid" <if condition="$_GET['searchtype'] eq 'uid'">selected="selected"</if>>用户ID</option>
								<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>昵称</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>手机号</option>
							</select>&nbsp;&nbsp;
                            入驻方式:
                            <select name="register_type">
                                <option value="-1" <if condition="$_GET['register_type'] eq -1">selected="selected"</if>>全部</option>
                                <option value="0" <if condition="isset($_GET['register_type']) AND ($_GET['register_type'] eq 0)">selected="selected"</if>>后台创建</option>
                                <option value="1" <if condition="isset($_GET['register_type']) AND ($_GET['register_type'] eq 1)">selected="selected"</if>>主动申请</option>
                            </select>&nbsp;&nbsp;
							当前状态:
							<select name="current_status">
                                <option value="-1" <if condition="$_GET['current_status'] eq -1">selected="selected"</if>>全部</option>
                                <option value="0" <if condition="isset($_GET['current_status']) AND ($_GET['current_status'] eq 0)">selected="selected"</if>>账号退出</option>
                                <option value="1" <if condition="isset($_GET['current_status']) AND ($_GET['current_status'] eq 1)">selected="selected"</if>>在线</option>
                            </select>&nbsp;&nbsp;
							状态
							<select name="status">
                                <option value="-1" <if condition="$_GET['status'] eq -1">selected="selected"</if>>全部</option>
                                <option value="1" <if condition="isset($_GET['status']) AND ($_GET['status'] eq 1)">selected="selected"</if>>正常</option>
								<option value="0" <if condition="isset($_GET['status']) AND ($_GET['status'] eq 0)">selected="selected"</if>>禁止</option>
                            </select>&nbsp;&nbsp;
							<input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th>ID</th>
                                <th>入驻方式</th>
								<th>排序</th>
								<th>昵称</th>
								<th>手机号</th>
								<th>常驻地址</th>
								<th>当前状态</th>
								<th>最后修改时间</th>
								<th>配送范围（公里）</th>
								<if condition="$config['open_deliver_level']">
								<th>等级积分</th>
								</if>
								<th class="textcenter">状态</th>
								<if condition="$system_session['level'] eq 2">
									<th class="textcenter">配送总量</th>
									<th class="textcenter">本月单量</th>
								</if>
                                <th class="textcenter">余额</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($user_list)">
								<volist name="user_list" id="vo">
									<tr>
										<td>{pigcms{$vo.uid}</td>
                                        <th><php>echo $vo['register_type'] == 0?'后台创建':'主动申请';</php></th>
										<td>{pigcms{$vo.sort|intval}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.phone|phone_demo_show}</td>
										<td>{pigcms{$vo.site}</td>
										<td>
											<if condition="$vo['last_time'] eq 0"><font color="red">账号退出</font><else/>在线（<if condition="$vo['is_notice'] eq 0">接收新订单<else/><font color="red">不接收新订单</font></if>）</if>
										</td>
										<td><if condition="$vo['last_time']">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}<else/>未登录</if></td>
                                        <if condition="$vo['delivery_range_type'] eq 1">
                                            <td>自定义</td>
                                        <else/>
										    <td>{pigcms{$vo.range}</td>
                                        </if>
										<if condition="$config['open_deliver_level']">
                                        <td>{pigcms{$vo.score}（{pigcms{$vo.level_name}）</td>
                                    	</if>
										<td class="textcenter"><if condition="$vo['status'] eq 1"><font color="green">正常</font><else/><font color="red">禁止</font></if></td>
										<if condition="$system_session['level'] eq 2">
											<td class="textcenter">{pigcms{$vo.num}</td>
											<td class="textcenter">{pigcms{$vo.month_count}</td>
										</if>
                                        <td class="textcenter">{pigcms{$vo.money}</td>
										<td class="textcenter">
											<if condition="$system_session['level']==2 OR in_array('258',$menus)">
											<a style="width: 60px;" class="" href="{pigcms{:U('Deliver/count_log',array('uid'=>$vo['uid']))}">历史记录统计</a> | </if>
											<if condition="$system_session['level']==2 OR in_array('259',$menus)">
											<a style="width: 60px;" class="" href="{pigcms{:U('Deliver/log_list',array('uid'=>$vo['uid']))}">查看配送记录</a> | </if> 
											<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Deliver/user_login_log',array('uid'=>$vo['uid']))}','配送员登录日志',680,560,true,false,false,null,'user_login_log',true);">登录日志</a> | 
											<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Deliver/deliverClockRecords',array('uid'=>$vo['uid']))}','配送员打卡日志',680,560,true,false,false,null,'user_login_log',true);">打卡日志</a> | 
											<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Deliver/user_edit',array('uid'=>$vo['uid']))}','编辑配送员',860,560,true,false,false,editbtn,'edit',true);">编辑</a> | 
											<a href="javascript:void(0);" class="delete_row" parameter="uid={pigcms{$vo.uid}" url="{pigcms{:U('Deliver/del')}">删除</a>
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="15">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="15">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>