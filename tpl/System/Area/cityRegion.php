<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
{pigcms{$_GET['type']}
				<ul>
					
					<if condition="($login_area_type eq 2 AND $_GET['type'] egt 3) OR ($login_area_type eq 3 AND $_GET['type'] gt 3)">
						<a href="{pigcms{:U('Area/cityRegion',array('pid'=>$now_area['area_pid'],'type'=>$_GET['type']-1))}" <if condition="$_GET['type'] eq 1">class="on"</if>>上一级</a>|
					</if>
					<if condition="$_GET['type'] gt 1">
						<a href="{pigcms{:U('Area/cityRegion',array('pid'=>$_GET['pid'],'type'=>$_GET['type']))}" class="on">{pigcms{$now_type_str}列表</a>
					</if>
				</ul>
			</div>

			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th width="10%">排序</th>
								<th width="10%">编号</th>
								<th width="15%">{pigcms{$now_type_str}名称</th>
                                <th width="25%">{pigcms{$now_type_str}办公地址</th>
                                <th width="10%">联系电话</th>
								<th width="10%">状态</th>
								<th width="10%">进入下级分类</th>
								<th width="10%">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($area_list)">
								<volist name="area_list" id="vo">
									<tr>
										<td>{pigcms{$vo.area_sort}</td>
										<td>{pigcms{$vo.area_id}</td>
										<td><if condition="$vo['is_hot']"><font color="red">{pigcms{$vo.area_name}</font><else/>{pigcms{$vo.area_name}</if></td>
                                        <td>{pigcms{$vo.detail_address}</td>
                                        <td>{pigcms{$vo.lx_phone}</td>
										<td><if condition="$vo['is_open']"><font color="green">显示</font><else/><font color="red">隐藏</font></if></td>

                                        <if condition="$vo['area_type'] lt 3">
                                            <td><a href="{pigcms{:U('Area/cityRegion',array('type'=>$vo['area_type']+1,'pid'=>$vo['area_id']))}">进入下级</a></td>
                                        </if>
                                        <if condition="$vo['area_type'] eq 3">
                                            <td><a href="{pigcms{:U('Area/area_city_street',array('type'=>0,'pid'=>$vo['area_id']))}">进入下级</a></td>

                                        </if>
										<td>
											<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Area/edit',array('area_id'=>$vo['area_id'],'type'=>$vo['area_type']))}','查看{pigcms{$now_type_str}',850,650,true,false,false,false,'city_region_edit',true);">查看</a>
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