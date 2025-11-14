<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Area/index')}" <if condition="$_GET['type'] eq 1">class="on"</if>>根列表</a>|
					<if condition="($config['now_province'] && $_GET['type'] gt 2) || ($config['many_city'] && !$config['now_province'] && $_GET['type'] gt 1)">
						<a href="{pigcms{:U('Area/index',array('pid'=>$now_area['area_pid'],'type'=>$_GET['type']-1))}" <if condition="$_GET['type'] eq 1">class="on"</if>>上一级</a>|
					</if>
					<if condition="!$config['many_city'] && $_GET['type'] gt 3">
						<a href="{pigcms{:U('Area/index',array('pid'=>$now_area['area_pid'],'type'=>$_GET['type']-1))}" <if condition="$_GET['type'] eq 1">class="on"</if>>上一级</a>|
					</if>
					<if condition="$_GET['type'] gt 1">
						<a href="{pigcms{:U('Area/index',array('pid'=>$_GET['pid'],'type'=>$_GET['type']))}" class="on">{pigcms{$now_type_str}列表</a>
					</if>
					<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Area/add',array('pid'=>$_GET['pid'],'type'=>$_GET['type']))}','添加{pigcms{$now_type_str}',850,650,true,false,false,addbtn,'store_add',true);">添加{pigcms{$now_type_str}</a>
					<if condition="$_GET['type'] eq 4">
						<a href="{pigcms{:U('Area/circle_category_list',array('pid'=>$_GET['pid'],'type'=>$_GET['type']))}">{pigcms{$now_type_str}分类列表</a>
					</if>
				</ul>
			</div>
			<if condition="$_GET['type'] eq 4">
				<div class="page_tips">若区域下只有一个商圈，则商家后台等前端页面会默认选中且隐藏，无法看到该商圈选项。</div>
			</if>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th>排序</th>
								<th>编号</th>
								<th>名称</th>
								<if condition="$_GET['type'] eq 4">
									<th>所属分类</th>
								</if>
								<if condition="$_GET['type'] eq 2 || $_GET['type'] eq 4">
									<th>首字母</th>
								</if>
								<th>状态</th>
								<if condition="$_GET['type'] gt 1 && 0">
									<th>网址标识</th>
								</if>
								<if condition="$area_list[0]['zip_code']">
									<th>邮编</th>
								</if>
								<if condition="$_GET['type'] lt 3">
									<th>进入下级分类</th>
								</if>
                                <if condition="$_GET['type'] eq 3">
                                    <th>进入下级列表</th>
                                    <th>进入下级商圈列表</th>
                                </if>
                                <if condition="$_GET['type'] eq 4">
                                    <th>进入下级分类</th>
                                </if>
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($area_list)">
								<volist name="area_list" id="vo">
									<tr>
										<td>{pigcms{$vo.area_sort}</td>
										<td>{pigcms{$vo.area_id}</td>
										<td><if condition="$vo['is_hot']"><font color="red">{pigcms{$vo.area_name}</font><else/>{pigcms{$vo.area_name}</if></td>
										<if condition="$_GET['type'] eq 4">
											<td>{pigcms{$circle_category[$vo['circle_category_id']]}</td>
										</if>
										
										<if condition="$_GET['type'] eq 2 || $_GET['type'] eq 4">
											<td>{pigcms{$vo.first_pinyin}</td>
										</if>
										<td><if condition="$vo['is_open']"><font color="green">显示</font><else/><font color="red">隐藏</font></if></td>
										<if condition="$_GET['type'] gt 1 && 0">
											<td>{pigcms{$vo.area_url}</td>
										</if>
										<if condition="$area_list[0]['zip_code']">
											<td>{pigcms{$vo.zip_code}</td>
										</if>
                                        <if condition="$_GET['type'] lt 3">
                                            <td><a href="{pigcms{:U('Area/index',array('type'=>$_GET['type']+1,'pid'=>$vo['area_id']))}">进入下级</a></td>
                                        </if>
                                        <if condition="$_GET['type'] eq 3">
                                            <td><a href="{pigcms{:U('Area/area_street',array('type'=>0,'pid'=>$vo['area_id']))}">进入下级</a></td>

                                            <td><a href="{pigcms{:U('Area/index',array('type'=>$_GET['type']+1,'pid'=>$vo['area_id']))}">进入商圈列表</a></td>
                                        </if>
										<if condition="$_GET['type'] eq 4">
											<td><a href="{pigcms{:U('Area/area_market',array('pid'=>$vo['area_id']))}">进入下级</a></td>
										</if>
										<td>
											<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Area/edit',array('area_id'=>$vo['area_id'],'type'=>$_GET['type']))}','编辑{pigcms{$now_type_str}',850,650,true,false,false,editbtn,'store_add',true);">编辑</a> |
											<a href="javascript:void(0);" class="delete_row" parameter="area_id={pigcms{$vo.area_id}" url="{pigcms{:U('Area/del')}" tip="删除只是进了回收站，所以区域标识未来将无法继续使用，<br/>但是标识无特殊意义，可以填写其他字母数字。<br/>（回收站恢复十分复杂，请勿随意删除区域。）">删除</a>

											<if condition="$config['single_system_type'] neq 'village_group'">
												<if condition="$_GET['type'] eq 1" >
                                                    <!--| <a href="{pigcms{:U('Area/admin', array('area_id' => $vo['area_id']))}">城市管理员</a>-->
                                                    <if condition="$system_session['area_id'] == $vo['area_id'] || $is_system">
                                                        | <a href="{pigcms{:U('Bill/merchant_percentage', array('province_id' => $vo['area_id']))}">数据统计</a>
                                                        <!--| <a href="{pigcms{:U('Bill/express_statistics', array('province_id' => $vo['area_id']))}">配送费统计</a>-->
                                                    </if>
												<elseif condition="$_GET['type'] eq 2" />
                                                    <!--| <a href="{pigcms{:U('Area/admin', array('area_id' => $vo['area_id']))}">城市管理员</a>-->
                                                    <if condition="$system_session['area_id'] == $vo['area_id'] || $is_system">
                                                        | <a href="{pigcms{:U('Bill/merchant_percentage', array('city_id' => $vo['area_id']))}">数据统计</a>
                                                        <!--| <a href="{pigcms{:U('Bill/express_statistics', array('city_id' => $vo['area_id']))}">配送费统计</a-->
                                                        | <a href="{pigcms{:U('Ownpay/index', array('city_id' => $vo['area_id']))}">自有支付</a>
                                                    </if>
												<elseif condition="$_GET['type'] eq 3" />
                                                    <!--| <a href="{pigcms{:U('Area/admin', array('area_id' => $vo['area_id']))}">区域管理员</a>-->
                                                    <if condition="$system_session['area_id'] == $vo['area_id'] || $is_system">
                                                        | <a href="{pigcms{:U('Bill/merchant_percentage', array('area_id' => $vo['area_id']))}">数据统计</a>
                                                        <!--| <a href="{pigcms{:U('Bill/express_statistics', array('area_id' => $vo['area_id']))}">配送费统计</a-->
                                                    </if>
												</if>
												<if condition="$_GET['type'] lt 4">
                                                    | <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Area/deliver',array('area_id'=>$vo['area_id']))}','配送设置',680,480,true,false,false,editbtn,'store_add',true);">配送设置<php>if ($vo['is_deliver'] == 1) { </php><span style="color: red">(已设置)<span><php> } else {</php><span style="color: gray">(未设置)<span><php>}</php></a>
                                                    <!--| <a href="{pigcms{:U('Shop/merchant_reduce', array('area_id' => $vo['area_id'],'type'=>$_GET['type']))}">{pigcms{$config['shop_alias_name']}优惠补贴统计</a-->
                                                    | <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Area/deliver_service',array('area_id'=>$vo['area_id']))}','跑腿设置',680,480,true,false,false,editbtn,'store_add',true);">跑腿设置<php>if ($vo['is_deliver_server'] == 3) { </php><span style="color: red">(全设置)<span><php> } elseif($vo['is_deliver_server'] == 1){</php><span style="color: red">(帮我买设置)<span><php> }  elseif($vo['is_deliver_server'] == 2){</php><span style="color: red">(帮我送设置)<span><php> } else {</php><span style="color: gray">(未设置)<span><php>}</php></a>
												</if>
                                            </if>
                                        </td>
									</tr>
								</volist>
							<else/>
								<tr><td class="textcenter red" colspan="8">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>