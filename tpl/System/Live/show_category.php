<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<notempty name="parent">
					<a href="{pigcms{:U('Live/show_category', ['fid'=>$_GET['fid']])}" class="on">{pigcms{$parent.name}的下级</a>|
					<else/>
					<a href="{pigcms{:U('Live/show_category')}" class="on">直播分类</a>|
					</notempty>
					<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Live/add_liveshow_category', ['fid'=>$_GET['fid']])}','添加分类',800,460,true,false,false,addbtn,'add',true);">添加分类</a>
				</ul>
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
								<empty name="parent">
								<th>下级分类</th>
								</empty>
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
										<empty name="parent">
										<td><a href="{pigcms{:U('Live/show_category',array('fid'=>$vo['id']))}">查看下级</a></td>
										</empty>
										<td>{pigcms{$vo.orderby}</td>
										<td class="textcenter"><a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Live/edit_liveshow_category',array('id'=>$vo['id']))}','编辑分类',800,460,true,false,false,editbtn,'edit',true);">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Live/del_liveshow_category',array('id'=>$vo['id']))}">删除</a></td>
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