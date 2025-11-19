<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-group"></i>
			<li class="active">{pigcms{:L_("粉丝管理")}</li>
			<li><a href="{pigcms{:U('card_log')}">{pigcms{:L_("实体卡管理")}</a></li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12" style="padding-left:0px;padding-right:0px;">
					
					<div class="grid-view" style="padding-top:5px;">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>ID</th>
									<th>{pigcms{:L_("实体卡")}ID</th>
									<th>{pigcms{:L_("商家")}</th>
									<th>{pigcms{:L_("店员")}</th>
									<th>{pigcms{:L_("描述")}</th>
									<th >{pigcms{:L_("添加时间")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="is_array($log_list)">
									<volist name="log_list" id="vo">
										<tr>
											<td>{pigcms{$vo.id}</td>
											<td>{pigcms{$vo.card_id}</td>
											<td>{pigcms{$merchant_session.name}</td>
											<td>{pigcms{$vo.staff_name}</td>
											<td>{pigcms{$vo.des}</td>
											<td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
										
										</tr>
									</volist>
								<else/>
									<tr><td class="textcenter red" colspan="7">{pigcms{:L_("列表为空！")}</td></tr>
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
</script>
<include file="Public:footer"/>