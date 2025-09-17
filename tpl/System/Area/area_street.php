<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
                    <if condition="$_GET['from'] neq 'house'">
					<a href="{pigcms{:U('Area/index')}">根列表</a>|
                    </if>
                    <if condition="$_GET['type'] eq 0 && $_GET['from'] neq 'house'">
					    <a href="{pigcms{:U('Area/index',array('type'=>'3','pid'=>$now_area['area_pid']))}">上一级</a>
                        <elseif condition="$_GET['from'] neq 'house'"/>
                        <a href="{pigcms{:U('Area/area_street',array('pid'=>$now_area['area_pid']))}">上一级</a>
                        <elseif  condition="$_GET['from'] eq 'house' && $now_area['area_pid']"/>
                        <a href="{pigcms{:U('Area/area_street',array('from' => $_GET['from']))}">上一级</a>
                    </if>
					<a href="{pigcms{$nowUrl}" class="on">{pigcms{$now_type_str}列表</a>
                    <if condition="$_GET['type'] eq 0">
                        <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Area/add_area_street',array('area_pid'=>$area_id,'area_type'=>0,'from' => $_GET['from']))}','添加街道/乡镇',800,650,true,false,false,addbtn,'store_add',true);">添加街道/乡镇</a>
                        <else/>
                        <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Area/add_area_street',array('area_pid'=>$area_id,'area_type'=>1))}','添加社区/村',800,650,true,false,false,addbtn,'store_add',true);">添加社区/村</a>
                    </if>
                </ul>
			</div>
            <div style="background-color: #dff0d8;padding: 20px;">
                可复制该链接进入街道/社区后台：   {pigcms{$area_login_url}
            </div>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
                            <col/>
                            <if condition="$_GET['from'] eq 'house'">
                                <col/>
                            </if>
                            <col/>
                            <if condition="$_GET['type'] eq 0">
                                <col/>
                            </if>
							<col width="240" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>排序</th>
								<th>编号</th>
								<th>名称</th>
								<th>状态</th>
                                <th>访问</th>
                                <if condition="$_GET['from'] eq 'house'">
                                    <th>上级地址</th>
                                </if>
								<th>热门</th>
                                <if condition="$_GET['type'] eq 0">
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
										<td>
                                            <if condition="$vo['is_open']">
                                                <a href="{pigcms{:U('House/v20AreaStreetLogin',array('area_id'=>$vo['area_id']))}" target="_blank">{pigcms{$vo.area_name}</a>
                                                <else/>
                                                {pigcms{$vo.area_name}
                                            </if>
                                        </td>
										<td><if condition="$vo['is_open']"><font color="green">显示</font><else/><font color="red">隐藏</font></if></td>
                                        <td>
                                            <if condition="$vo['is_open']">
                                                <a href="{pigcms{:U('House/v20AreaStreetLogin',array('area_id'=>$vo['area_id']))}" target="_blank">访问</a>
                                                <else/>
                                                禁止状态
                                            </if>
                                        </td>
                                        <if condition="$_GET['from'] eq 'house'">
                                            <td>
                                                {pigcms{$vo.addressTxt|default=''}
                                            </td>
                                        </if>
										<td><if condition="$vo['is_hot']"><font color="green">是</font><else/><font color="red">否</font></if></td>
                                        <if condition="$_GET['type'] eq 0">
                                            <td><a href="{pigcms{:U('Area/area_street',array('type'=>1,'pid'=>$vo['area_id'],'from' => $_GET['from']))}">进入下级</a></td>
                                        </if>
										<td>
                                            <if condition="$_GET['from'] neq 'house'">
                                            <a href="{pigcms{:U('Bill/merchant_percentage', array('street_id' => $vo['area_id']))}">数据统计</a> |
                                            </if>
											<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Area/add_area_street',array('area_type'=>$vo['area_type'],'area_id'=>$vo['area_id']))}','编辑',850,650,true,false,false,editbtn,'store_add',true);">编辑</a> |
											<a href="javascript:void(0);" class="delete_row" parameter="area_id={pigcms{$vo.area_id}" url="{pigcms{:U('Area/del_area_street')}">删除</a>
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