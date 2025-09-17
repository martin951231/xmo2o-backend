<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Live/live_category')}" class="on">首页分类</a>|
					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Live/add_live_category')}','添加分类','800px','80%',true,false,false,addbtn,'add',true);">添加分类</a>
				</ul>
			</div>
			
			<div class="page_tips" style="margin: 10px">
				<ol>
					<li>1. 您可以设置热门的直播分类或者短视频话题至用户直播首页，用于提高直播/短视频的曝光率。</li>
				</ol>
			</div>
			
			<!--<p>网站首页会显示最前面10条快报。置顶的快报会优先显示，并将悬浮在页面顶部。</p>-->
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
								<th>编号</th>
								<th>分类名称</th>
								<th>添加时间</th>
								<th>排序</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="!empty($data_list)">
								<volist name="data_list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.addtime|date='Y-m-d H:i:s',###}</td>
										<td>{pigcms{$vo.sort}</td>
										<td class="textcenter"><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Live/edit_live_category',array('id'=>$vo['id']))}','编辑分类',800,600,true,false,false,editbtn,'edit',true);">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Live/del_live_category',array('id'=>$vo['id']))}">删除</a></td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="9">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>