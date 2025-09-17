<include file="Public:header"/>
<div class="main-content">
    <style type="text/css">
.page-content-area .form-group .col-sm-1:first-child{box-sizing:content-box}
    </style>
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-home"></i>
			<li class="active"><a href="{pigcms{:U('index')}">景区管理</a></li>
			<li>评论列表</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12" style="padding-left:0px;padding-right:0px;">
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="10%">顾客ID</th>
									<th width="10%">订单号</th>
									<th>评论内容</th>
									<th width="10%">评论时间</th>
									<th width="10%">评论打分</th>
									<th width="10%">是否回复</th>
                                    <th width="10%">审核状态</th>
									<th class="button-column" width="10%">操作</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$reply_list">
									<volist name="reply_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.user_id}</td>
											<td>{pigcms{$vo.order_id}</td>
											<td>{pigcms{$vo.reply_content|msubstr=###,0,50}</td>
											<td>{pigcms{$vo.reply_time|date='Y-m-d H:i:s',###}</td>
											<td>{pigcms{$vo.reply_score}</td>
											<td><if condition="$vo['replys_time']"><span class="green">已回复</span><else/><span class="red">未回复</span></if></td>
                                            <td><if condition="$vo['status'] == 0"><span class="red">未审核</span><elseif condition="$vo['status'] == 1" /><span class="green">审核通过</span><else/><span class="red">审核不通过</span></if></td>
											<td><a style="width: 60px;" class="label label-sm label-info" title="评论列表" href="{pigcms{:U('detail',array('reply_id'=>$vo['reply_id']))}">评论详情</a></td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="7" >暂无评论！</td></tr>
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
<include file="Public:footer"/>
