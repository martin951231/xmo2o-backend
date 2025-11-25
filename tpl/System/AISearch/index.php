<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('AISearch/index')}" class="on">关键词列表</a>|
					<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('AISearch/add')}','添加关键词',700,250,true,false,false,addbtn,'add',true);">添加热门搜索词</a>
					<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('AISearch/batchAdd')}','批量添加热门搜索词',700,250,true,false,false,addbtn,'add',true);">批量添加热门搜索词</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('AISearch/index')}" method="get">
							<input type="hidden" name="c" value="AISearch"/>
							<input type="hidden" name="a" value="index"/>
							关键词筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<input type="submit" style="margin-right:20px;" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup><col> <col> <col> <col><col> <col width="140" align="center"> </colgroup>
						<thead>
							<tr>
								<th>排序</th>
								<th>关键词</th>
								<th>类型</th>
								<th>自定义链接</th>
								<th>更新时间</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($words)">
								<volist name="words" id="vo">
									<tr>
										<td>{pigcms{$vo.sort}</td>
										<td>{pigcms{$vo.word}</td>
										<td><php>echo $typeNames[$vo['type']]??'--';</php></td>
										<td>
                                            <if condition="$vo['link']">
                                                <a href="{pigcms{$vo.link}" target="_blank">预览链接</a>
                                            <else/>
                                                --
                                            </if>
										</td>
										<td>{pigcms{$vo.update_time}</td>
										<td class="textcenter"><a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('AISearch/add',array('id'=>$vo['id']))}','编辑关键词',700,250,true,false,false,editbtn,'add',true);">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('AISearch/del')}">删除</a></td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="8">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="8">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>