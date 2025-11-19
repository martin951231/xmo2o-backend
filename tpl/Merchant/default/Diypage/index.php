<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Config/store')}">{pigcms{:L_("店铺管理")}</a>
			</li>
			<li class="active">【{pigcms{$now_store.name}{pigcms{:L_("】 自定义页面列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="alert alert-info" style="margin:10px 0;">
						<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
						{pigcms{:L_("将页面设置为商城主页，再次由商城进入店铺时直接显示此页为首页，原有系统界面将不再显示。该页面的二维码可进行宣传推广。")}
					</div>
					<div class="tabbable">
						<a href="{pigcms{:U('create',array('store_id'=>$now_store['store_id']))}" class="btn btn-success">{pigcms{:L_("新建自定义页面")}</a>
						<div id="shopList" class="grid-view">
							<table class="table table-striped table-bordered table-hover">
								<thead>
									<tr>
										<th width="100">{pigcms{:L_("标题")}</th>
										<th width="100">{pigcms{:L_("创建时间")}</th>
										<th width="100">{pigcms{:L_("浏览次数")}</th>
										<th width="100">{pigcms{:L_("商品来源")}</th>
										<th width="100">{pigcms{:L_("二维码")}</th>
										<th width="80" class="button-column">{pigcms{:L_("操作")}</th>
									</tr>
								</thead>
								<tbody>
									<if condition="$page_list">
										<volist name="page_list" id="vo">
											<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
												<td><a href="{pigcms{$config.site_url}/wap.php?c=Diypage&a=page&page_id={pigcms{$vo.page_id}&system_own={pigcms{$vo.system_own}" target="_blank">{pigcms{$vo.page_name}</a></td>
												<td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
												<td>{pigcms{$vo.hits}</td>
												<td>
													<if condition="$vo.page_goods_source eq 1 ">{pigcms{:C('config.shop_alias_name')}
													<elseif condition="$vo.page_goods_source eq 2"/>{pigcms{:C('config.mall_alias_name')}
													<else />{pigcms{:L_("underfind")}
													</if>
												</td>
												<td>
													<a href="{pigcms{$config.site_url}/merchant.php?c=Diypage&a=see_qrcode&type=shop&store_id={pigcms{$now_store.store_id}&page_id={pigcms{$vo['page_id']}&system_own={pigcms{$vo.system_own}" class="see_qrcode">{pigcms{:L_("二维码")}</a>
												</td>
												<td class="button-column">
													<if condition="$vo.page_goods_source eq 2 ">
														<if condition="$vo['is_home']">
															<span style="padding-right:0px;color:#999;">{pigcms{:L_("商城主页")}</span>
															<a class="green" style="padding-right:8px;" href="{pigcms{:U('set_home', array('page_id'=>$vo['page_id'],'store_id'=>$now_store['store_id'],'close'=>'1'))}" >[{pigcms{:L_("取消")}]</a>
														<else/>
															<a class="green" style="padding-right:8px;" href="{pigcms{:U('set_home', array('page_id'=>$vo['page_id'],'store_id'=>$now_store['store_id']))}" >{pigcms{:L_("设为商城主页")}</a>
														</if>
													</if>
													<a class="green" style="padding-right:8px;" href="{pigcms{:U('create', array('page_id'=>$vo['page_id'],'store_id'=>$now_store['store_id']))}" >
														<i class="ace-icon fa fa-pencil bigger-130"></i>
													</a>
													<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('delete',array('page_id'=>$vo['page_id'],'store_id'=>$now_store['store_id']))}">
														<i class="ace-icon fa fa-trash-o bigger-130"></i>
													</a>
												</td>
											</tr>
										</volist>
									<else/>
										<tr class="odd"><td class="button-column" colspan="6" >{pigcms{:L_("无内容")}</td></tr>
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
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<if condition="!$_GET['store_id']">
    <script type="text/javascript">
        var href = parent.location.href;
        console.log(href);
        if(href.indexOf('store_id') > 0){
            var paramArr = href.split('?');
            if(paramArr[1]){
                location.href = "{pigcms{:U('index')}&" + paramArr[1];
            }
        }
    </script>
</if>
<script type="text/javascript">
$(function(){

	$('.see_qrcode').live('click',function(){
		art.dialog.open($(this).attr('href'),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'{pigcms{:L_("查看页面二维码")}',
			padding: 0,
			width: 600,
			height: 333,
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
	
	jQuery(document).on('click','#shopList a.red',function(){
		if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
	});
});
</script>
<include file="Public:footer"/>
