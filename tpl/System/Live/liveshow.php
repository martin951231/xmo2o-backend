<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Live/liveshow')}" class="on">直播列表</a>
			</ul>
		</div>
		<table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('Live/liveshow')}" method="get">
						<input type="hidden" name="c" value="Live"/>
						<input type="hidden" name="a" value="liveshow"/>
						
						搜索: <input type="text" name="keyword" id="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						<select name="searchtype" id="searchtype">
							<option value="liveshow_no" <if condition="$_GET['searchtype'] eq 'liveshow_no'">selected="selected"</if>>直播编号</option>
							<option value="uid" <if condition="$_GET['searchtype'] eq 'uid'">selected="selected"</if>>主播UID</option>
							<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>主播昵称</option>
						</select>
						<font color="#000">日期筛选：</font>
						<select name="searchTimeType">
							<option value="create_time" <if condition="$_GET['searchTimeType'] eq 'create_time'">selected="selected"</if>>创建时间</option>
							<option value="estimate_time" <if condition="$_GET['searchTimeType'] eq 'estimate_time'">selected="selected"</if>>预计开播时间</option>
						</select>
						<input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						状态筛选:
						<select id="status" name="status">
							<option value="">全部</option>
							<option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>待开始</option>
							<option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>进行中</option>
							<option value="3" <if condition="$_GET['status'] eq 3">selected="selected"</if>>已结束</option>
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
						<col width="180" align="center"/>
					</colgroup>
					<thead>
						<tr>
							<th>直播房间号</th>
							<th>主播uid</th>
							<th>主播昵称</th>
							<th>标题</th>
							<th>预计开始时间</th>
							<th>实际开播时间</th>
							<th>实际结束时间</th>
							<th>创建时间</th>
							<th>分类</th>
							<th>区域</th>
							<th>排序</th>
							<th class="textcenter">操作</th>
						</tr>
					</thead>
					<tbody>
						<if condition="!empty($data_list)">
							<volist name="data_list" id="vo">
								<tr>
									<td>{pigcms{$vo.live_no}</td>
									<td>{pigcms{$vo.uid}</td>
									<td>{pigcms{$vo.live_nickname}</td>
									<td>{pigcms{$vo.title}</td>
									<td><if condition="strtotime($vo['estimate_time']) gt 0">{pigcms{$vo.estimate_time}<else />-</if></td>
									<td><if condition="strtotime($vo['real_start_time']) gt 0">{pigcms{$vo.real_start_time}<else />-</if></td>
									<td><if condition="strtotime($vo['real_end_time']) gt 0">{pigcms{$vo.real_end_time}<else />-</if></td>
									<td><if condition="strtotime($vo['create_time']) gt 0">{pigcms{$vo.create_time}<else />-</if></td>
									<td>{pigcms{$vo.category}</td>
									<td>{pigcms{$vo.area_name}</td>
									<td>{pigcms{$vo.sort}</td>
									<td class="textcenter">
										<if condition="$vo.record_status eq 1 && !empty($vo['video_url'])">
										<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Live/liveshow_replay',array('live_id'=>$vo['live_id'],'frame_show'=>true))}','查看直播详情',375,667,true,false,false,false,'detail',true);">回放</a>
										</if>
                                        <if condition="$vo['status'] eq '1'">
                                            <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.live_id}" url="{pigcms{:U('Live/forbid_show')}">禁播</a>
                                        </if>
										<if condition="$vo['status'] eq '3' || $vo['status'] eq '2'">
											<a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.live_id}" url="{pigcms{:U('Live/delete_show')}">删除</a>									
										</if>
										<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Live/setup',array('live_id'=>$vo['live_id']))}','设置直播信息',520,250,true,false,false,editbtn,'add',true);">设置</a>
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
<script>
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<include file="Public:footer"/>