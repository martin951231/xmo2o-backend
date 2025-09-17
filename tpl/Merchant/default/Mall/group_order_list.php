<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li>
				<a href="{pigcms{:U('Mall/group_list',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("拼团列表")}</a>
			</li>
			<li class="active">{pigcms{:L_("拼团小组列表")}</li>
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
					<div class="form-group">
						<label class="col-sm-1" style="display:none">{pigcms{:L_("订单状态筛选：")}</label>
						<select id="status" name="status" style="display:none">
							<volist name="status_list" id="vo">
								<option value="{pigcms{$key}" <if condition="$key eq $status">selected="selected"</if>>{pigcms{$vo}</option>
							</volist>
						</select>
						<form action="{pigcms{:U('Mall/group_order_list')}" method="get">
							<input type="hidden" name="c" value="Mall"/>
							<input type="hidden" name="a" value="group_order_list"/>
							<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>
							
							{pigcms{:L_("搜索")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="goods_id" <if condition="$_GET['searchtype'] eq 'goods_id'">selected="selected"</if>>{pigcms{:L_("商品")}ID</option>
								<option value="goods_name" <if condition="$_GET['searchtype'] eq 'goods_name'">selected="selected"</if>>{pigcms{:L_("商品名称")}</option>
							</select>
							<font color="#000">{pigcms{:L_("发起拼团时间：")}</font>
							<input type="text" class="input-text" name="sdate1" style="width:120px;" id="d4311"  value="{pigcms{$_GET.sdate1}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="sdate2" style="width:120px;" id="d4311" value="{pigcms{$_GET.sdate2}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							<font color="#000">{pigcms{:L_("拼团结束时间：")}</font>
							<input type="text" class="input-text" name="fdate1" style="width:120px;" id="d4311"  value="{pigcms{$_GET.fdate1}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="fdate2" style="width:120px;" id="d4311" value="{pigcms{$_GET.fdate2}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							<input type="submit" value="{pigcms{:L_("查询")}" class="button"/>
						</form>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="120"><input type="checkbox" id="all" />{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("团购名称")}</th>
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
								<if condition="$list">
									<volist name="list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>" data-id="{pigcms{$vo.id}">
											<td class="start_id">  <if condition="$vo.status eq 0"><input type="checkbox"  name="start[]" value="{pigcms{$vo.id}"/></if> {pigcms{$vo.id}</td>
											<td>{pigcms{$vo.name}</td>
											<td>{pigcms{$vo.nickname}</td>
											<td>{pigcms{$vo.complete_num}</td>
											<td>{pigcms{$vo.num}</td>
											<td>
												<if condition="$vo.status eq 1">
													<font color="green">{pigcms{:L_("已经成团")}</font>
												<elseif condition="$vo.status eq 2" />
													<font color="orange">{pigcms{:L_("拼团超时")}</font>
												<elseif condition="$vo.status eq 3" />
													<font color="blue">{pigcms{:L_("商家设置成团")}</font>
												<else />
													<font color="red">{pigcms{:L_("还未成团")}</font>
												</if>
											</td>
											<td>{pigcms{$vo.start_time|date="Y-m-d H:i:s",###}</td>
											<if condition="$vo['status'] eq '2'">
												<td>{pigcms{$vo.end_time|date="Y-m-d H:i:s",###}</td>
											<else />
												<td>{pigcms{$vo.update_time|date="Y-m-d H:i:s",###}</td>
											</if>
											<td>
												<if condition="$vo.num lt $vo.complete_num AND $vo.status eq 0">
													<a href="javascript:void(0)"  onclick="group_right_now(this)" style="color:red">{pigcms{:L_("立即成团")}</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
												</if>
												<a href="{pigcms{:U('Mall/group_team_user_list',array('tid'=>$vo['id'],'store_id'=>$now_store['store_id']))}" >{pigcms{:L_("查看详情")}</a>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("没有拼团小组")}</td></tr>
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
	function group_right_now(obj){			
			var r=confirm("{pigcms{:L_("该团购还未成团，您确定要立即成团吗？")}");
			if (r==true){
				$.post("{pigcms{:U('Mall/save_group_status')}", {status: 3,id:$(obj).parents('tr').data('id')}, function(data, textStatus, xhr) {
					if(data){
						window.location.href = window.location.href;
					}else{
						alert(data.msg);
					}
				});
			}
		

	}
	
	function all_group_right_now(obj){
		var list = $('input[name="start[]"]:checked');
		
		if(list.length<1){
			alert('{pigcms{:L_("没有勾选待成团的拼团小组")}');
		}else{
			var ids='';
			$.each(list,function(i,k){
				ids+=','+$(k).val();
			})
		
			var r=confirm("{pigcms{:L_("这些团购还未成团，您确定要立即成团吗？")}");
			if (r==true){
				$.post("{pigcms{:U('Mall/save_group_status')}", {status: 3,id:ids.substring(1)}, function(data, textStatus, xhr) {
					if(data){
						window.location.href = window.location.href;
					}else{
						alert(data.msg);
					}
				});
			}
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
