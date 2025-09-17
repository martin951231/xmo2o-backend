<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Live/report')}" class="on">举报列表</a>
			</ul>
		</div>
		<table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('Live/report')}" method="get">
						<input type="hidden" name="c" value="Live"/>
						<input type="hidden" name="a" value="report"/>
						
						举报人: 
						<select name="searchtype" id="searchtype">
							<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>昵称</option>
							<option value="uid" <if condition="$_GET['searchtype'] eq 'uid'">selected="selected"</if>>UID</option>
							<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>手机</option>
						</select>
						<input type="text" name="keyword" id="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						
						<font color="#000">举报时间：</font>
						<input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						业务:
						<select id="business" name="business">
							<option value="">全部</option>
							<volist name="business" id="vo">
							<option value="{pigcms{$key}" <if condition="$_GET['business'] eq $key">selected="selected"</if>>{pigcms{$vo}</option>
							</volist>
						</select>
						举报类别:
						<select id="category" name="category">
							<option value="">全部</option>
							<volist name="category" id="vo">
							<option value="{pigcms{$key}" <if condition="$_GET['category'] eq $key">selected="selected"</if>>{pigcms{$vo}</option>
							</volist>
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
							<th>举报编号</th>
							<th>举报人</th>
							<th>举报时间</th>
							<th>业务</th>
							<th>举报类型</th>
							<th>举报内容</th>
							<th class="textcenter">查看详情</th>
						</tr>
					</thead>
					<tbody>
						<if condition="!empty($data_list)">
							<volist name="data_list" id="vo">
								<tr>
									<td>{pigcms{$vo.id}</td>
									<td>{pigcms{$vo.nickname}</td>
									<td>{pigcms{$vo.create_time}</td>
									<td>{pigcms{$vo.business_txt}</td>
									<td>{pigcms{$vo.category_txt}</td>
									<td>{pigcms{$vo.content}</td>
									<td class="textcenter">
										<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Live/report_detail',array('report_id'=>$vo['id'],'frame_show'=>true))}','查看举报详情',900,'90%',true,false,false,false,'detail',true);">查看</a>
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