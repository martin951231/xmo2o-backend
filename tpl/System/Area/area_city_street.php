<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
                      <if condition="$login_area_type eq 2 AND $_GET['type'] eq 0">
					    <a href="{pigcms{:U('Area/cityRegion',array('type'=>'2','pid'=>$now_area['area_pid']))}">上一级</a>
                        <elseif condition="$_GET['type'] gt 0"/>
                        <a href="{pigcms{:U('Area/area_city_street',array('pid'=>$now_area['area_pid']))}">上一级</a>
                        </if>
					<a href="{pigcms{$nowUrl}" class="on">{pigcms{$now_type_str}列表</a>
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
							<col/>
                            <col/>
                            <col/>
                            <if condition="$_GET['type'] eq 0">
                                <col/>
                            </if>
							<col width="240" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th  width="8%">排序</th>
								<th  width="8%">编号</th>
								<th  width="15%">{pigcms{$now_type_str}名称</th>
                                <th width="20%">{pigcms{$now_type_str}办公地址</th>
                                <th width="10%">联系电话</th>
								<th width="8%">状态</th>
                                <th width="8%">访问</th>
                                <if condition="$_GET['type'] eq 0">
                                    <th>进入下级分类</th>
                                </if>
								<th width="10%">操作</th>
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
                                        <td>{pigcms{$vo.detail_address}</td>
                                        <td>{pigcms{$vo.phone}</td>
										<td><if condition="$vo['is_open']"><font color="green">显示</font><else/><font color="red">隐藏</font></if></td>
                                        <td>
                                            <if condition="$vo['is_open']">
                                                <a href="{pigcms{:U('House/v20AreaStreetLogin',array('area_id'=>$vo['area_id']))}" target="_blank">访问</a>
                                                <else/>
                                                禁止状态
                                            </if>
                                        </td>

                                        <if condition="$_GET['type'] eq 0">
                                            <td><a href="{pigcms{:U('Area/area_city_street',array('type'=>1,'pid'=>$vo['area_id'],'from' => $_GET['from']))}">进入下级</a></td>
                                        </if>
										<td>
											<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Area/add_area_street',array('area_type'=>$vo['area_type'],'area_id'=>$vo['area_id']))}','查看',850,650,true,false,false,false,'city_region_edit',true);">查看</a> 
						
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