<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-comments-o comments-o-icon"></i>
			<li class="active">{pigcms{:L_("粉丝列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12" style="padding-left:0px;padding-right:0px;">
					<if condition="$_GET['page'] lt 2">
						<div class="widget-box">
							<div class="widget-header">
								<h5>{pigcms{:L_("统计图")}</h5>
							</div>
							<div class="widget-body" id="main" style="text-align:center;">
								<div style="float:left;width:49%;" id="behavior_chart">
								</div>
								<div style="float:left;width:49%;" id="sex_chart">
								</div>
							</div>
							<div style="clear:both;"></div>
							<script type="text/javascript" src="{pigcms{$static_public}fushionCharts/FusionCharts.js"></script>
							<script type="text/javascript">
								<if condition="$chart_list">
									var chart_behavior = new FusionCharts("{pigcms{$static_public}fushionCharts/Pie3D.swf", "ChartId", "600", "400", "0", "1");
									chart_behavior.setDataXML('<chart borderThickness="0" caption="{pigcms{:L_("7天内粉丝行为统计")}" baseFontColor="666666" baseFont="宋体" baseFontSize="14" bgColor="FFFFFF" bgAlpha="0" showBorder="0" bgAngle="360" pieYScale="90"  pieSliceDepth="5" smartLineColor="666666"><volist name="chart_list" id="vo"><set label="{pigcms{$vo.name}" value="{pigcms{$vo.count}"/></volist></chart>');
									chart_behavior.render("behavior_chart");
								<else/>
									$('#behavior_chart').html('<h2 style="margin:150px 0 50px;">{pigcms{:L_("7天内粉丝行为统计")}</h2><p style="margin-bottom:150px;">{pigcms{:L_("暂无数据")}</p>');
								</if>
								
								<if condition="$man_count || $woman_count || $unsexman_count">
									var chart_sex = new FusionCharts("{pigcms{$static_public}fushionCharts/Pie3D.swf", "ChartId", "600", "400", "0", "1");
									chart_sex.setDataXML('<chart borderThickness="0" caption="{pigcms{:L_("粉丝性别比例图")}" baseFontColor="666666" baseFont="宋体" baseFontSize="14" bgColor="FFFFFF" bgAlpha="0" showBorder="0" bgAngle="360" pieYScale="90"  pieSliceDepth="5" smartLineColor="666666"><set label="{pigcms{:L_("男性")}" value="{pigcms{$man_count}"/><set label="{pigcms{:L_("女性")}" value="{pigcms{$woman_count}"/><set label="{pigcms{:L_("未知性别")}" value="{pigcms{$unsexman_count}"/></chart>');
									chart_sex.render("sex_chart");
								<else/>
									$('#sex_chart').html('<h2 style="margin:150px 0 50px;">{pigcms{:L_("粉丝性别比例图")}</h2><p style="margin-bottom:150px;">{pigcms{:L_("暂无数据")}</p>');
								</if>
							</script>
						</div>
						<div class="alert alert-danger" style="margin-top:5px;margin-bottom:0px;">
							<button type="button" class="close" data-dismiss="alert">
								<i class="ace-icon fa fa-times"></i>
							</button>
							{pigcms{:L_("粉丝 即指：扫描了商家二维码、")}{pigcms{$config.group_alias_name}{pigcms{:L_("二维码、")}{pigcms{$config.meal_alias_name}{pigcms{:L_("二维码等产生了与商家直接关系的用户。用户还必须在网站中注册帐号或绑定帐号才能显示在列表中！")}
						</div>
					</if>
					
					<div class="alert alert-info" style="margin-top:5px;margin-bottom:0px;">
					<form action="{pigcms{:U('Customer/fans_list')}" method="get" style="float:left;margin-left:10px">
						<input type="hidden" name="c" value="Customer"/>
						<input type="hidden" name="a" value="fans_list"/>
						<select name="searchtype">
							<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>{pigcms{:L_("昵称")}</option>
							<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("手机")}</option>
							<option value="uid" <if condition="$_GET['searchtype'] eq 'uid'">selected="selected"</if>>{pigcms{:L_("用户")}id</option>
						</select>
						<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						 <button >{pigcms{:L_("搜索")}</button>
						 
						 </form>
						<volist name="count_list" id="cl">
						
						&nbsp;&nbsp;&nbsp;  <a href="{pigcms{:U('Customer/fans_list', array('from_merchant' => $cl['from_merchant']))}">{pigcms{$from_type[$cl['from_merchant']]}({pigcms{$cl['count']})</if></a>
						</volist>
						 <button onclick="exports()">{pigcms{:L_("导出粉丝")}</button>
					</div>
					<div class="grid-view" style="padding-top:5px;">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("会员")}ID</th>
									<th>{pigcms{:L_("会员昵称")}</th>
									<th>{pigcms{:L_("手机号")}</th>
									<th>{pigcms{:L_("性别")}</th>
									<th>{pigcms{:L_("省(直辖市)")}</th>
									<th>{pigcms{:L_("城市")}</th>
									<th>{pigcms{:L_("头像")}</th>
									<th width="150">{pigcms{:L_("关注时间")}</th>
									<th width="150">{pigcms{:L_("最后登录")}</th>
									<th width="150">{pigcms{:L_("获取渠道")}</th>
									<th style="text-align:center;">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$fans_list">
									<volist name="fans_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.uid}</td>
											<td>{pigcms{$vo.nickname}</td>
											<td>{pigcms{$vo.phone}</td>
											<td><switch name="vo['sex']"><case value="1">{pigcms{:L_("男")}</case><case value="2">{pigcms{:L_("女")}</case><default/>{pigcms{:L_("未知")}</switch></td>
											<td>{pigcms{$vo.province}</td>
											<td>{pigcms{$vo.city}</td>
											<td><if condition="$vo['avatar']"><a href="{pigcms{$vo.avatar}" target="_blank" class="view_img_frame"><img src="{pigcms{$vo.avatar|replace_file_domain=###}" style="width:50px;" /></a></if></td>
											<td>{pigcms{$vo.dateline|date='Y-m-d H:i:s',###}</td>
											<td>{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>
											<td><if condition="$vo['from_merchant'] eq 0">{pigcms{:L_("扫描商家产品二维码")}<elseif condition="$vo['from_merchant'] eq 1" />{pigcms{:L_("扫描商家二维码")}<elseif condition="$vo['from_merchant'] eq 2" />{pigcms{:L_("平台赠送")}<else />{pigcms{:L_("扫描产品推广二维码")}</if></td>
											<td>
												<a style="width:80px;" class="label label-sm label-info" title="{pigcms{:L_("粉丝行为分析")}" href="{pigcms{:U('Customer/detail',array('uid'=>$vo['uid']))}">{pigcms{:L_("粉丝行为分析")}</a> <!--a style="width: 60px;" class="label label-sm label-info" title="{pigcms{:L_("粉丝行为分析")}" href="{pigcms{:U('Customer/detail',array('uid'=>$vo['uid']))}">{pigcms{:L_("粉丝行为分析")}</a-->
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("暂无粉丝！")}</td></tr>
								</if>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(function(){
		$('#group_id').change(function(){
			$('#frmselect').submit();
		});
	});
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
	$(function(){
		$('.view_img_frame').click(function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("查看大头像")}',
				padding: 0,
				width: 640,
				height: 643,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '38.2%',
				opacity:'0.4'
			});
			return false;
		});
	});
 var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Customer/export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>
