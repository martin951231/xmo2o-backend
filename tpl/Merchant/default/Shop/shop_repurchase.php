<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Shop/index')}">{pigcms{$config.shop_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li>{pigcms{$now_store['name']}</li>
			<li class="active">{pigcms{:L_("超值换购列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content"> 
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				.cur_place{
					padding: 10px 0 20px 0;
					color:#999
				}
				.cur_place span,.cur_place a{
					padding: 0 5px;
					 
				}
				.application-list {
					margin-bottom: 24px;
					-webkit-animation: enterIn .2s ease-out;
					animation: enterIn .2s ease-out;
					-webkit-animation-fill-mode: forwards;
					animation-fill-mode: forwards;
				}
				.application-title {
					margin-bottom: 20px;
					color: #323233;
				}
				.application-title .title-main {
					font-size: 16px;
					font-weight: 700;
				}
				.application-title .title-desc {
					color: #999;
					margin-left: 10px;
					font-size:13px;
				}
			</style>
			<div class="cur_place">
				<span style="padding-left: 0;">平台业务</span>/
				<span>外卖管理</span>/ 
			    <a href="{pigcms{:U('Shop/marketingList', array('store_id' => $_GET['store_id']))}">营销活动</a>
			</div>
			<div class="application-title">
				<span class="title-main">超值换购</span>
				<span class="title-desc">花式提高客单价</span>
			</div>
			<div class="row">
				<div class="col-xs-12">
					<div class="form-group">
						<a href="{pigcms{:U('Shop/shop_repurchase_add',array('store_id'=>$_GET['store_id']))}" class="btn btn-success fl" style="">{pigcms{:L_("新建优惠")}</a>
					</div>
					<div class="alert alert-info" style="margin:10px 0;">
						<button data-dismiss="alert" class="close" type="button"><i class="ace-icon fa fa-times"></i></button>
						<p>1.  {pigcms{:L_("活动状态分为进行中、已过期（过了活动时间）、未开始（未到开始时间）、未使用（符合活动时间但是没有设置为使用状态）、已失效（手动设置为失效）。")}</p>
						<p>2.  {pigcms{:L_("一个店铺只允许有一个活动进行中")}</p>
						<p>3 . {pigcms{:L_("一个活动最多只允许添加3个超值抢购商品")}</p>
						<p>4. {pigcms{:L_("一个活动的一个商品最多只允许购买一份")}</p>
						<p>5. {pigcms{:L_("多规格属性与限时优惠的商品不支持参与超值抢购活动")}</p>
						<p>6. {pigcms{:L_("超值抢购商品的打包费参与计算")}</p>
						<p>7. {pigcms{:L_("活动库存来自于原商品库存，活动库存不可设置大于原商品当前库存")}</p>
						<p>8. {pigcms{:L_("门槛条件是（限时优惠、分类折扣、店铺折扣）优惠之后的价格")}</p>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th id="shopList_c1" width="50">{pigcms{:L_("编号")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("活动名称")}</th>
									<th id="shopList_c3" width="80">{pigcms{:L_("活动时间")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("活动周期")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("活动条件")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("活动商品")}</th>
									<th id="shopList_c5" width="20" >{pigcms{:L_("状态")}</th>
									<th id="shopList_c5" width="120" >{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$list">
									<volist name="list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td><div class="tagDiv">{pigcms{$vo.rep_id}</div></td>
											<td><div class="tagDiv">{pigcms{$vo.name}</div></td>
											<td>{pigcms{$vo.start_time|date="Y-m-d H:i:s",###} {pigcms{:L_('至')} {pigcms{$vo.end_time|date="Y-m-d H:i:s",###}</td>
											<td>{pigcms{$vo.week_str} {pigcms{:L_('的')} {pigcms{$vo.show_time_start} {pigcms{:L_('至')} {pigcms{$vo.show_time_end}</td>
											<td>
												<if condition="$vo['is_limit'] eq 0">
													{pigcms{:L_('无门槛')}
												<else/>
													{pigcms{:L_('订单满')}{pigcms{$vo.limit_money}{pigcms{$config['Currency_txt']}{pigcms{:L_('可换购')}
												</if>
											</td>
											<td>{pigcms{$vo.goodsName}</td>
											<td>
												
													<if condition="$vo['status'] eq 0 ">
														<if condition="$vo['start_time'] gt time() ">
																{pigcms{:L_('未开始')}
														<elseif condition="$vo['end_time'] lt time() "/>
															<span style="color: red">{pigcms{:L_('已过期')}</span>
														<else/>
															{pigcms{:L_('未使用')}
														</if>
													<elseif condition="$vo['status'] eq 1"/>
														<if condition="$vo['end_time'] lt time() ">
															<span style="color: red">{pigcms{:L_('已过期')}</span>
														<else/>
															<span style="color: #428bca">{pigcms{:L_('进行中')}</span>
														</if>

														
													<elseif condition="$vo['status'] eq 2"/>
														<span style="color: grey">{pigcms{:L_('已失效')}</span>
													</if>
											</td>
											
											<td>
												<if condition="$vo['status'] neq 1 && $vo['end_time'] gt time()">
													<a title="{pigcms{:L_("设为使用")}" data-rep_id="{pigcms{$vo.rep_id}" class="set_use handle_btn"  style="cursor: pointer;">设为使用</a>&nbsp;|
												</if>
												<if condition="$vo['status'] neq 2">
													<a title="{pigcms{:L_("设为失效")}" data-rep_id="{pigcms{$vo.rep_id}" class="set_lose green handle_btn" style="cursor: pointer;">设为失效</a>&nbsp;|
													<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Shop/shop_repurchase_edit',array('store_id'=>$vo['store_id'], 'rep_id' => $vo['rep_id']))}">编辑
													</a>
												<else/>
													<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Shop/shop_repurchase_edit',array('store_id'=>$vo['store_id'], 'rep_id' => $vo['rep_id']))}">编辑&nbsp;|<a title="{pigcms{:L_("删除")}" class="item-delete tpl-table-black-operation-del check_goods" style="color: grey" href="{pigcms{:U('Shop/shop_repurchase_del',array('store_id'=>$vo['store_id'], 'rep_id' => $vo['rep_id']))}">{pigcms{:L_("删除")}</a>
													</a>
												</if>　
											</td>
											
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="30" >{pigcms{:L_("暂无超值换购活动")}</td></tr>
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
<script>
$('.set_use').click(function(){
	var rep_id = $(this).data('rep_id');
	layer.confirm('{pigcms{:L_("一个店铺只可设置一个超值抢购活动为进行中，设为使用后，其他正在使用的活动将会关闭，确定要设为使用吗？")}', {
	  btn: ['确认', '取消']
	}, function(index, layero){
		//按钮【按钮一】的回调
		$.get('{pigcms{:U("Shop/shop_repurchase_set_use")}',{rep_id:rep_id}, function(data){
			if (data.error) {
				layer.msg(data.msg);
			}else{
				layer.msg(data.msg);
				setTimeout(function(){
					window.location.reload();
				},3000);
			}
		},'json');
	});
})

$('.set_lose').click(function(){
	var rep_id = $(this).data('rep_id');
	layer.confirm('{pigcms{:L_("设为失效后，该活动则不在用户前台显示，确定设为失效吗？")}', {
	  btn: ['确认', '取消']
	}, function(index, layero){
		//按钮【按钮一】的回调
		$.get('{pigcms{:U("Shop/shop_repurchase_set_lose")}',{rep_id:rep_id}, function(data){
			if (data.error) {
				layer.msg(data.msg);
			}else{
				layer.msg(data.msg);
				setTimeout(function(){
					window.location.reload();
				},3000);
			}
		},'json');
	});
})
</script>
<include file="Public:footer"/>
