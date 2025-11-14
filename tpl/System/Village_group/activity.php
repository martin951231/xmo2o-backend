<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Village_group/activity')}" class="on">活动列表</a>
				</ul>
			</div>
			<table class="search_table" width="80%">
				<tr>
					<td>
						<input type="button" value="添加活动" class="button" onclick="window.o2oTop.artiframe('{pigcms{:U('add_activity')}','添加活动',720,480,true,false,false,addbtn,'add',true);"/>
					</td>
			
					<td>
						<form action="{pigcms{:U('Village_group/activity')}" method="get">
							<input type="hidden" name="c" value="Village_group"/>
							<input type="hidden" name="a" value="activity"/>
                            筛选:
                            <if condition="$admin_area neq 3">
                                <div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                            </if>

							活动类型：<select name="type">
								<option value="0" >全部活动</option>
								<option value="1" <if condition="$type eq '1'">selected="selected"</if>>每日必买</option>
								<option value="2" <if condition="$type eq '2'">selected="selected"</if>>限时抢购</option>
							</select>
							筛选：<input type="text" name="keyword" class="input-text" placeholder="输入活动名称" value="{pigcms{$keyword}"/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<select name="time_type">
								<option value="1" <if condition="$time_type eq '1'">selected="selected"</if>>开始时间</option>
								<option value="2" <if condition="$time_type eq '2'">selected="selected"</if>>结束时间</option>
							</select>
							<input type="text" class="input-text" name="start_time" style="width:120px;"  value="{pigcms{$start_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" value="{pigcms{$end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>
							<input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					</style>
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="250" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>活动编号</th>
								<th>活动名称</th>
                                <th>活动区域</th>
								<th>活动类型</th>
								<th>活动开始时间</th>
								<th>活动结束时间</th>
								<th>状态</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($data)">
								<volist name="data" id="vo">
									<tr>
										<td>{pigcms{$vo.activity_id}</td>
										<td>{pigcms{$vo.activity_name}</td>
                                        <td>{pigcms{$vo.area_name}</td>
										<td>{pigcms{$vo.type_txt}</td>
										<td>{pigcms{$vo.start_datetime}</td>
										<td>{pigcms{$vo.end_datetime}</td>
										<td><span style="color:{pigcms{$vo.font_color}">{pigcms{$vo.status_txt}</span></td>
										<td class="textcenter">
										<a href="javascript:void(0)" onclick="window.o2oTop.artiframe('{pigcms{:U('Village_group/add_activity',array('activity_id'=>$vo['activity_id']))}','编辑',720,480,true,false,false,editbtn,'add',true);">编辑</a>  | <a href="{pigcms{:U('activity_goods',['activity_id'=>$vo['activity_id']])}">商品管理</a>
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
<include file="Public:footer"/>