<include file="Public:header"/>
<div class="main-content">
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="clearfix">
						<button onclick="select_goods()" class="btn btn-success" style="float:left;">{pigcms{:L_("添加商品")}</button>
						<form action="{pigcms{:U('Liveshow/shop_goods_list')}" method="get" style="float:left;margin-left:10px">
							<input type="hidden" name="c" value="Liveshow"/>
							<input type="hidden" name="a" value="shop_goods_list"/>
							<!-- {pigcms{:L_("搜索类型")} -->
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder='{pigcms{:L_("请输入商品名称")}'/>
							<button class="btn btn-success" >{pigcms{:L_("查询")}</button>
						</form>
					</div>
					<div class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("门店")}</th>
									<th>{pigcms{:L_("商品名称")}</th>
									<th>{pigcms{:L_("价格")}</th>
									<th>{pigcms{:L_("返佣比例")}</th>
									<th>{pigcms{:L_("添加时间")}</th>
									<th class="am-text-middle">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$goods_list">
									<volist name="goods_list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        	<td>{pigcms{$vo.store_name}</td>
                                            <td>{pigcms{$vo.name}</td>
											<td>{pigcms{$vo.price|floatval}</td>
											<td>{pigcms{$vo.percent_txt}</td>
											<td>{pigcms{$vo.create_time}</td>
											<td class="am-text-middle">
												<div class="tpl-table-black-operation">
													<a class="tpl-table-black-operation-green" onclick="return change_fanyong({pigcms{$vo.id},{pigcms{$vo.percent});" style="cursor: pointer;">{pigcms{:L_("更改返佣")}</a>
													<a title="{pigcms{:L_("移除")}" class="item-delete tpl-table-black-operation-del check_goods" href="{pigcms{:U('Liveshow/goods_del',array('id'=>$vo['id']))}">{pigcms{:L_("移除")}</a>
												</div>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="15" >{pigcms{:L_("无内容")}</td></tr>
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
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
	//选择商品
	function select_goods(){
		layer.open({
			type: 2,
			area: ['700px', '730px'],
			fixed: false,
			maxmin: true,
			content: '{pigcms{:U("select_goods")}&type=shop',
			btn: ['确定', '取消'],
			yes: function(index, layero){
				var frameId = $(layero).find("iframe").attr('id');
				var selected = $.trim($(window.frames[frameId].document).find('[name="selected"]').val());
				var type = $.trim($(window.frames[frameId].document).find('[name="type"]').val());
				$("#goods_spec tbody").empty();
				$.post("{pigcms{:U('ajax_add_goods')}",{type:type,selected:selected},function(data){
					if(data.errcode == 1){
						layer.msg(data.msg);
					}
					else{
						
						layer.msg("添加成功",{time:1000},function(){
							window.location.href = data.redirect_url;
						})
					}
				},'json');
			    layer.close(index);
			},
			btn2: function(index){
				layer.close(index);
			}
		});
	}

	function change_fanyong(id,percent){
		layer.open({
		  title:"修改返佣比例",
		  type: 1,
		  skin: 'layui-layer-rim', //加上边框
          area: ['300px', '200px'], //宽高
          content: '<div style="margin-left:20px;margin-top:10px;">返佣比例：<input type="text" class="input-text" style="width:80px; text-align:center;" name="new_percent" value="'+percent+'" placeholder="请输入一个一百以内的数字" /> %</div>' +
                '<div style="margin-left:20px;margin-top:10px;"><span style="color: red">*设置-1则读取平台配置返佣比例</span></div>',
          btn: ['确定', '取消'],
		  yes:function(index, layero){
		  		var new_percent = $.trim($('[name="new_percent"]').val());
		  		$.post("{pigcms{:U('ajax_change_percent')}",{id:id,percent:new_percent},function(data){
					if(data.errcode == 1){
						layer.msg(data.msg);
					}
					else{
						layer.msg("修改成功",{time:1000},function(){
							window.location.href = window.location.href;	
						})
						
					}
				},'json');
				layer.close(index);
		  },
		  btn2: function(index, layero){

		  }
		});
	}
</script>
<include file="Public:footer"/>
