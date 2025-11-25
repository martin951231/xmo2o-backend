<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("满减列表")}</li>
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
				<span>商城管理</span>/ 
			    <a href="{pigcms{:U('Mall/marketingList', array('store_id' => $_GET['store_id']))}">营销活动</a>
			</div>
			<div class="application-title">
				<span class="title-main">满减送</span>
				<span class="title-desc">多种促销手段，助力商家提高复</span>
			</div>
			<div class="row">
				<div class="col-xs-12">
					<div style="margin-bottom:10px;">
<!--						<a href="{pigcms{:U('Mall/full_list',array('store_id'=>$now_store['store_id']))}" class="btn  btn-primary" style="background-color:#6FB3E0;">{pigcms{:L_("订单")}</a>-->
						<a href="{pigcms{:U('Mall/save_full_info',array('store_id'=>$now_store['store_id']))}" class="btn btn-success" style="margin-left:10px;background-color:#0099cc;">{pigcms{:L_("新建满减送活动")}</a>
					</div>

					<div class="form-group">

						<form action="" method="get">
							<input type="hidden" name="c" value="Mall"/>
							<input type="hidden" name="a" value="full_list"/>
							<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>

							{pigcms{:L_("搜索")}: <input type="text" name="keyword" placeholder="活动名称" class="input-text" value="{pigcms{$_GET['keyword']}"/>

							<font color="#000">{pigcms{:L_("活动开始时间：")}</font>
							<input type="text" class="input-text" name="sdate1" style="width:120px;" id="d4311"  value="{pigcms{$_GET.sdate1}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							<input type="text" class="input-text" name="sdate2" style="width:120px;" id="d4311" value="{pigcms{$_GET.sdate2}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							<font color="#000">{pigcms{:L_("活动结束时间：")}</font>
							<input type="text" class="input-text" name="fdate1" style="width:120px;" id="d4311"  value="{pigcms{$_GET.fdate1}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							<input type="text" class="input-text" name="fdate2" style="width:120px;" id="d4311" value="{pigcms{$_GET.fdate2}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							<input type="submit" value="{pigcms{:L_("查询")}" class="button "/>
						</form>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("活动名称")}</th>
									<th>{pigcms{:L_("类型")}</th>
									<th>{pigcms{:L_("开始时间")}</th>
									<th>{pigcms{:L_("结束时间")}</th>
									<th>{pigcms{:L_("活动详情")}</th>
									<th>{pigcms{:L_("支付订单")}</th>
									<th>{pigcms{:L_("参与客户")}</th>
									<th>{pigcms{:L_("实付金额")}</th>
									<th>{pigcms{:L_("活动状态")}</th>
									<th>{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<volist name='list' id='vo'>
									<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
										<td>{pigcms{$vo['id']}</td>
										<td>{pigcms{$vo['name']}</td>
										<td>
											<if condition="$vo['type'] eq 1">
												{pigcms{:L_("普通优惠")}
											<else/>
												{pigcms{:L_("多级优惠")}
											</if>
										</td>
										<td>{pigcms{$vo.stime|date="Y-m-d H:i:s",###}</td>
										<td>{pigcms{$vo.ftime|date="Y-m-d H:i:s",###}</td>
										<td>
											<volist name="vo.mall_full_layer" id="voo">
                                              <div>{pigcms{$voo.coupon_name}</div><div>{pigcms{$voo.goods_name}</div>
											</volist>
										</td>
										<td></td>
										<td>{pigcms{$vo.affect_time}</td>
										<td></td>
									    <td>
											<if condition="$vo['status'] eq 1">
												<if condition="$vo['ftime'] lt time()">
													<div style="color:red;">已过期</div>
												<else/>
													<div style="color:#82af6f;">进行中</div>
												</if>
											<else/>
												<div style="color:red;">已失效</div>
											</if>
										</td>
										<td>
											<if condition="$vo['status'] eq 1">
											<a style="width: 60px; !important;" class="label label-sm label-success" href="{pigcms{:U('Mall/edit_full',array('store_id'=>$now_store['store_id'],'id'=>$vo['id']))}">{pigcms{:L_("编辑")}</a>
											<a style="width: 60px; !important;" class="label label-sm label-success invalid" href="{pigcms{:U('Mall/full_invalid',array('store_id'=>$now_store['store_id'],'id'=>$vo['id']))}">{pigcms{:L_("设为失效")}</a>
											</if>
											<if condition="$vo['status'] eq 0">
												<a style="width: 60px;background-color:red !important;" class="label label-sm label-success del" href="{pigcms{:U('Mall/full_delete',array('store_id'=>$now_store['store_id'],'id'=>$vo['id']))}">{pigcms{:L_("删除")}</a>
											</if>
										</td>
									</tr>
								</volist>
								<empty name='list'>
									<tr class="odd">
										<td class="button-column" colspan="30" >{pigcms{:L_("暂无满减送活动")}</td>
									</tr>
								</empty>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
	$(function(){

        $('.del').live('click',function(){
			if(!confirm('{pigcms{:L_("确认删除该活动！")}')){
				return false;
			}
		});

		$('.invalid').live('click',function(){
			if(!confirm('{pigcms{:L_("确认操作吗?")}')){
				return false;
			}
		});


	});


    
	$(function(){
		/*{pigcms{:L_("店铺状态")}*/
		updateStatus(".statusSwitch .ace-switch", ".statusSwitch", "OPEN", "CLOSED", "store_theme");
	});
	function updateStatus(dom1, dom2, status1, status2, attribute){
		$(dom1).each(function(){
			if($(this).attr("data-status")==status1){
				$(this).attr("checked",true);
			}else{
				$(this).attr("checked",false);
			}
			$(dom2).show();
		}).click(function(){
			var _this = $(this), type = 'open', id = $(this).attr("data-id");
			_this.attr("disabled",true);
			if(_this.attr("checked")){	//"开启")}
				type = 'open';
			}else{		//"关闭")}
				type = 'close';
			}
			$.ajax({
				url:"{pigcms{:U('Mall/change_mall')}",
				type:"post",
				data:{"type":type,"id":id,"status1":status1,"status2":status2,"attribute":attribute},
				dataType:"text",
				success:function(d){
					if(d != '1'){		//"失败")}
						if(type=='open'){
							_this.attr("checked",false);
						}else{
							_this.attr("checked",true);
						}
						bootbox.alert("{pigcms{:L_("操作失败")}");
					}
					_this.attr("disabled",false);
				}
			});
		});
	}
</script>
<include file="Public:footer"/>