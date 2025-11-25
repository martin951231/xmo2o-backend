<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("店铺列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="50">{pigcms{:L_("编号")}</th>
									<th width="50">{pigcms{:L_("排序")}</th>
									<th width="100">{pigcms{:L_("店铺名称")}</th>
									<th width="150">{pigcms{:L_("店铺电话")}</th>
									<th class="button-column" width="140">{pigcms{:L_("查看二维码")}</th>
									<th class="button-column" width="140">{pigcms{:L_("完善店铺信息")}</th>
									<th class="button-column" width="140">{pigcms{:L_("订单查看")}</th>
									<th class="button-column" width="140">{pigcms{:L_("商品管理")}</th>
									<th class="button-column" width="140">{pigcms{:L_("推荐商品管理")}</th>
									<th class="button-column" width="140">{pigcms{:L_("营销活动")}</th>
									 
									<!-- <th class="button-column" width="140">{pigcms{:L_("店铺优惠")}</th>
									<th class="button-column" width="140">{pigcms{:L_("拼团")}</th>
									<th class="button-column" width="140">{pigcms{:L_("周期购")}</th>
									<th class="button-column" width="140">{pigcms{:L_("一口价")}</th>
									<th class="button-column" width="140">{pigcms{:L_("砍价")}</th>
									<th class="button-column" width="140">{pigcms{:L_("满减送")}</th> -->
									<!-- <th class="button-column" width="140">{pigcms{:L_("开通商城")}</th> -->
								</tr>
							</thead>
							<tbody>
								<php>if ($store_list) {</php>
									<volist name="store_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td><div class="tagDiv">{pigcms{$vo.store_id}</div></td>
											<td><div class="tagDiv">{pigcms{$vo.sort}</div></td>
											<td><div class="shopNameDiv">{pigcms{$vo.name}</div></td>
											<td>{pigcms{$vo.phone}</td>
											
											<php>if(empty($vo['sid'])){</php>
												<td></td>
												<td class="button-column">
													<a style="width:80px;" class="label label-sm label-pink" title="{pigcms{:L_("完善店铺信息")}" href="{pigcms{:U('Mall/shop_edit',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("完善信息")}</a>
												</td>
												<td></td>
												<td></td>
												<td></td>
												<td></td>
												
											<php>}else{</php>
												<td class="button-column">
													<a href="{pigcms{$config.site_url}/merchant.php?c=Mall&a=see_qrcode&type=mall&id={pigcms{$vo['store_id']}" class="see_qrcode">{pigcms{:L_("二维码")}</a>
												</td>
												<td class="button-column">
													<a style="width:80px;" class="label label-sm label-success" title="{pigcms{:L_("修改")}" href="{pigcms{:U('Mall/shop_edit',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("修改信息")}</a>
												</td>
												<td class="button-column">
													<a style="width:80px;" class="label label-sm label-warning" title="{pigcms{:L_("查看店铺订单")}" href="{pigcms{:U('Mall/order',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("查看订单")}</a>
												</td>
												<td class="button-column">
													<a style="width: 60px;" class="label label-sm label-purple" title="{pigcms{:L_("商品管理")}" href="{pigcms{:U('Mall/goods_list',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("商品管理")}</a>
												</td>
												<td class="button-column">
													<a style="width: 80px;" class="label label-sm label-info" title="{pigcms{:L_("推荐商品管理")}" href="{pigcms{:U('Mall/recommend_goods_list',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("推荐商品管理")}</a>
												</td>
												
												<td class="button-column">
													<a  class="label label-sm label-success" title="{pigcms{:L_("营销活动")}" href="{pigcms{:U('Mall/marketingList',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("营销活动")}</a>
												</td> 
											<php>}</php>
										</tr>
									</volist>
								<php>}else{</php>
									<tr class="odd"><td class="button-column" colspan="10" >{pigcms{:L_("您没有添加店铺，或店铺没开启")}{pigcms{$config.mall_alias_name}{pigcms{:L_("功能，或店铺正在审核中。")}</td></tr>
								<php>}</php>
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
		$('.see_qrcode').click(function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("查看二维码")}',
				padding: 0,
				width: 900,
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
		$('.handle_btn').live('click',function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("克隆店铺商品至其他店铺")}',
				padding: 0,
				width: 720,
				height: 520,
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
        $('.zbw_btn').live('click',function(){
            art.dialog.open($(this).attr('href'),{
                init: function(){
                    var iframe = this.iframe.contentWindow;
                    window.top.art.dialog.data('iframe_handle',iframe);
                },
                id: 'handle',
                title:'{pigcms{:L_("完善智百威ERP对接信息")}',
                padding: 0,
                width: 320,
                height: 200,
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
		$('.eleme').click(function(){
		    art.dialog({
		        title: "{pigcms{:L_("提示")}",
		        content: "<b>{pigcms{:L_("目前饿了么外卖平台仅支持已有门店的绑定开通")}</b> <br/> {pigcms{:L_("如您在饿了么上已建有门店，请联系客服人员提交绑定申请")}",
		        lock: true,
		        fixed: true
		    });
		});

        $('.synczbw').click(function(){
            var index = layer.load(1, {shade: [0.7,'#000']}), store_id = $(this).data('store_id');
            $.post("{pigcms{:U('Mall/syncZbw')}", {'store_id':$(this).data('store_id')}, function(res){
                if (res.sCode == 0) {
                    syncGoods(store_id, 1, index);
                } else {
                    layer.msg(res.sError);
                    layer.close(index)
                }
            },'json');
        });
	});

    function syncGoods(store_id, page, index)
    {
        $.post("{pigcms{:U('Mall/syncZbwGoods')}", {'store_id':store_id, 'page':page}, function(res){
            if (res.sCode == 0) {
                if (res.page == 0) {
                    layer.msg('{pigcms{:L_("同步完成")}');
                    layer.close(index);
                } else {
                    syncGoods(store_id, res.page, index);
                }
            } else {
                layer.msg(res.sError);
                layer.close(index);
            }
        },'json');
    }
    
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