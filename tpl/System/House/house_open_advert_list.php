<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('House/house_adver_list')}">轮播图管理</a>
                    <a href="{pigcms{:U('House/house_nav_list')}">功能导航管理</a>
                    <a href="{pigcms{:U('House/house_middle_advert_list')}">中间栏轮播广告</a>
                    <a href="{pigcms{:U('House/house_open_advert_list')}"  class="on">一键开门广告管理</a>
				</ul>
			</div>
			<div class="page_tips">广告建议尺寸：750*1200，每种手机屏幕尺寸不同显示效果也不同，若广告高度太低或太高会自动等比例放大缩小，空白位置显示设置的背景色  </div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<input type="button" value="添加广告" class="button" onclick="window.top.artiframe('{pigcms{:U('house_open_advert_add')}','添加一键开门广告   ',1010,595,true,false,false,addbtn,'add',true);"/>
						
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					.content{width:80px;overflow: hidden;text-overflow:ellipsis;white-space: nowrap;}
					</style>
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>

							<col/>
							<col/>
							<col width="180" align="center"/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
						<tr>
							<th>编号</th>
							<th>名称</th>


							<th>链接地址</th>
							<th>图片(以下为强制小图，点击图片查看大图)</th>
							<th class="textcenter">最后操作时间</th>
							<th>状态</th>
                            <th>点击量</th>
                            <th>浏览量</th>
							<th class="textcenter">操作</th>
						</tr>
						</thead>
						<tbody>
						<if condition="is_array($adver_list)">
							<volist name="adver_list" id="vo">
								<tr>
									<td>{pigcms{$vo.id}</td>
									<td>{pigcms{$vo.name}</td>


									<td><a href="{pigcms{$vo.url}" target="_blank">访问链接</a></td>
									<td>
										<img src="{pigcms{$vo.pic}" style="width:300px;height:80px;" class="view_msg"/>
									</td>
									<td class="textcenter">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>
									<td>
										<if condition="$vo['status'] eq 1">
											<font color="green">正常</font>
											<elseif condition="$vo['status'] eq 0"/>
											<font color="red">关闭</font>
										</if>
                                    </td>
                                    <td>
                                        {pigcms{$vo['click_num']|default=0}
                                    </td>
                                    <td>
                                        {pigcms{$vo['look_num']|default=0}
                                    </td>
									<td class="textcenter">
                                        <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('House/house_open_adver_edit',array('id'=>$vo['id'],'from_page'=>$_GET['from_page']))}','编辑广告信息',1010,595,true,false,false,editbtn,'add',true);">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('House/house_adver_del')}">删除</a> |
                                        <a href="javascript:void(0);"  onclick="window.top.artiframe('{pigcms{:U('House/house_nav_count',array('id'=>$vo['id']))}','统计分析',1010,595,true,false,false,false,'add',true);">统计分析</a>

                                    </td>
								</tr>
							</volist>
							<else/>
							<tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
						</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>