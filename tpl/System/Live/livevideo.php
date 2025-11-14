<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Live/livevideo')}" class="on">短视频列表</a>
			</ul>
		</div>
		<table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('Live/livevideo')}" method="get">
						<input type="hidden" name="c" value="Live"/>
						<input type="hidden" name="a" value="livevideo"/>
						
						搜索: <input type="text" name="keyword" id="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						<select name="searchtype" id="searchtype">
							<option value="uid" <if condition="$_GET['searchtype'] eq 'uid'">selected="selected"</if>>作者UID</option>
							<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>作者昵称</option>
						</select>
						<font color="#000">日期筛选：</font>
						<input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
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
							<th>短视频ID</th>
							<th>作者uid</th>
							<th>昵称</th>
							<th>视频描述</th>
							<th>获得赞</th>
							<th>评论数</th>
							<th>播放量</th>
							<th>创建时间</th>
							<th class="textcenter">操作</th>
						</tr>
					</thead>
					<tbody>
						<if condition="!empty($data_list)">
							<volist name="data_list" id="vo">
								<tr>
									<td>{pigcms{$vo.video_id}</td>
									<td>{pigcms{$vo.uid}</td>
									<td>{pigcms{$vo.nickname}</td>
									<td>{pigcms{$vo.title}</td>
									<td>{pigcms{$vo.zan}</td>
									<td>{pigcms{$vo.comments}</td>
									<td>{pigcms{$vo.views}</td>
									<td>{pigcms{$vo.addtime}</td>
									<td class="textcenter">
										<if condition="!empty($vo['play_url'])">
										<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Live/livevideo_replay',array('video_id'=>$vo['video_id'],'frame_show'=>true))}','查看短视频详情',375,667,true,false,false,false,'detail',true);">播放</a>
										</if>
										<a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.video_id}" url="{pigcms{:U('Live/delete_video')}">删除</a>	
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