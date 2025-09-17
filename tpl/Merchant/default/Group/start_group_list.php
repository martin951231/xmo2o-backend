<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('start_group_list')}">{pigcms{:L_("团购小组列表")}</a>
			</li>
			
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
									<th width="120"><input type="checkbox" id="all" />{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("团购名称")}</th>
									<th>{pigcms{:L_("团购类型")}</th>
									<th>{pigcms{:L_("发起人")}</th>
									<th>{pigcms{:L_("成团人数")}</th>
									<th>{pigcms{:L_("当前参加人数")}</th>
									<th>{pigcms{:L_("是否成团")}</th>
									<th>{pigcms{:L_("发起时间")}</th>
									<th>{pigcms{:L_("最后更新时间")}</th>
									<th>{pigcms{:L_("操作")} <a href="javascript:void(0)"  onclick="all_group_right_now(this)" style="color:red">{pigcms{:L_("批量成团")}</a></th>
								</tr>
							</thead>
							<tbody>
								<if condition="$start_group_list">
									<volist name="start_group_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>" data-id="{pigcms{$vo.id}">
											<td class="start_id">  <if condition="$vo.status eq 0"><input type="checkbox"  name="start[]" value="{pigcms{$vo.id}"/></if> {pigcms{$vo.id}</td>
											<td>{pigcms{$vo['group_name']}</td>
											<td><if condition="$vo.tuan_type eq 0">{pigcms{:L_("团购券")}<elseif condition="$vo.tuan_type eq 1" />{pigcms{:L_("代金券")}<elseif condition="$vo.tuan_type eq 2" />{pigcms{:L_("实物")}</if></td>
											<td>{pigcms{$vo['nickname']}</td>
											<td>{pigcms{$vo['complete_num']}</td>
											<td>{pigcms{$vo.num}</td>
											<td><if condition="$vo.status eq 1"><font color="green">{pigcms{:L_("已经成团")}</font><elseif condition="$vo.status eq 2" /><font color="orange">{pigcms{:L_("拼团超时")}</font><elseif condition="$vo.status eq 3" /><font color="blue">{pigcms{:L_("商家设置成团")}</font><else /><font color="red">{pigcms{:L_("还未成团")}</font></if></td>
											<td>{pigcms{$vo.start_time|date="Y-m-d H:i:s",###}</td>
											<if condition="$vo['status'] eq '2'">
											<td>{pigcms{$vo['start_time']+$vo['pin_effective_time']*3600|date="Y-m-d H:i:s",###}</td>
											<else />
											<td>{pigcms{$vo.last_time|date="Y-m-d H:i:s",###}</td>
											</if>
											<td>
												<div class="tpl-table-black-operation">
													<if condition="$vo.num lt $vo.complete_num AND $vo.status eq 0">
														<a class="tpl-table-black-operation-del" href="javascript:void(0)"  onclick="group_right_now(this)">{pigcms{:L_("立即成团")}</a>
													</if>
													<a class="tpl-table-black-operation-green" href="{pigcms{:U('Group/start_group_info',array('id'=>$vo['id']))}">{pigcms{:L_("查看详情")}</a>
												</div>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("没有团购小组")}</td></tr>
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
	function CreateShop(){
		window.location.href = "{pigcms{:U('Group/pick_address_add')}";
	}
	
	function group_right_now(obj){
		layer.open({
			title:L_('确认提醒')
			,content:L_('该团购还未成团，您确定要立即成团吗？')
			,btn: [L_('确定'),L_('取消')]
			,icon: 3
			,yes: function(index){
				$.post("{pigcms{:U('Group/update_start_status')}", {status: 3,id:$(obj).parents('tr').data('id')}, function(data, textStatus, xhr) {
					if(data){
						window.location.reload();
					}else{
						layer.open({
							title:L_('操作提示')
							,content: data.msg
						});
					}
				});
			}
		});
		return false;
	}
	
	function all_group_right_now(obj){
		var list = $('input[name="start[]"]:checked');
		
		if(list.length<1){
			layer.open({
				title:L_('确认提醒')
				,content:L_('没有勾选待成团的拼团小组')
				,icon: 2
			});
			return false;
		}else{
			var ids='';
			$.each(list,function(i,k){
				ids+=','+$(k).val();
			})
			
			layer.open({
				title:L_('确认提醒')
				,content:L_('这些团购还未成团，您确定要立即成团吗？')
				,btn: [L_('确定'),L_('取消')]
				,icon: 3
				,yes: function(index){
					$.post("{pigcms{:U('Group/update_start_status')}", {status: 3,id:ids.substring(1)}, function(data, textStatus, xhr) {
						if(data){
							window.location.reload();
						}else{
							layer.open({
								title:L_('操作提示')
								,content: data.msg
							});
						}
					});
				}
			});
			return false;
		}
	}
	
	$(function(){
		$('.group_name').hover(function(){
			var top = $(this).offset().top;
			var left = $(this).offset().left+$(this).width()+10;
			$('body').append('<div id="group_name_div" style="position:absolute;z-index:5555;background:white;top:'+top+'px;left:'+left+'px;border:1px solid #ccc;padding:10px;"><div style="margin-bottom:10px;"><b>{pigcms{:L_("商品标题：")}</b>'+$(this).data('title')+'</div><div><b>{pigcms{:L_("商品图片：")}</b><img src="'+$(this).data('pic')+'" style="width:180px;"/></div></div>');
		},function(){
			$('#group_name_div').remove();
		});	
		$('.see_qrcode').click(function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("查看渠道二维码")}',
				padding: 0,
				width: 430,
				height: 433,
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
		var all = false;
		$('#all').click(function(){
			if(!all){
				$('input[name="start[]"]').attr('checked',true);
				all = true;
			}else{
				$('input[name="start[]"]').attr('checked',false);
				all = false;
			}
		})
	});
</script>
<include file="Public:footer"/>
