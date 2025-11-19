<include file="Public:header"/>
<style type="text/css">
.btn{margin-right:10px;}
.btn.hover{background:#0099cc;color: #fff;}
</style>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Ecard/ecard_list')}"  <eq name="ACTION_NAME" value='ecard_list'>class="on"</eq>  >返回</a>
					<a href="{pigcms{:U('Ecard/ecard_coupon_list',array('card_id'=>$card_id,'make_status'=>0))}" <eq name="make_status" value='0'>class="on"</eq>  >未制卡列表</a>
					<a href="{pigcms{:U('Ecard/ecard_coupon_list',array('card_id'=>$card_id,'make_status'=>1))}"  <eq name="make_status" value='1'>class="on"</eq> >已制卡列表</a>
					<a href="{pigcms{:U('Ecard/ecrad_sales_list',array('card_id'=>$card_id,'from_type'=>'ecard'))}" >E卡售出明细</a>
					<a href="{pigcms{:U('Ecard/ecard_use_list',array('card_id'=>$card_id,'from_type'=>'ecard'))}" >E卡消费明细</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<eq name='make_status' value='0'>
							<a href="{pigcms{:U('Ecard/ecard_coupon_export',array('card_id'=>$card_id))}" class="button export">导出未制作卡片</a>
							<a href="javascript:;" class="button more_set" >批量标记已制作卡片</a>
						<else/>
							<a href="{pigcms{:U('Ecard/ecard_coupon_list',array('card_id'=>$card_id,'make_status'=>$make_status))}" class="btn <eq name='_GET.sales_status' value=''>hover</eq>">全部卡片</a>	
							<a href="{pigcms{:U('Ecard/ecard_coupon_list',array('card_id'=>$card_id,'make_status'=>$make_status,'sales_status'=>0))}" class="btn <eq name='_GET.sales_status' value='0'>hover</eq>">未售出卡片</a>	
							<a href="{pigcms{:U('Ecard/ecard_coupon_list',array('card_id'=>$card_id,'make_status'=>$make_status,'sales_status'=>1))}" class="btn <eq name='_GET.sales_status' value='1'>hover</eq>">已售出卡片</a>	
						</eq>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<eq name='make_status' value='0'>
								<th><input type='checkbox' id='checkall'></th>
								</eq>
								<th>ID</th>
								<th>卡号</th>
								<eq name='make_status' value='0'>
									<th>卡密</th>
									<th >操作</th>
								<else/>
									<th>是否购买</th>
									<th>购买时间</th>
									<th>销售渠道</th>
									<th>面值</th>
									<th class="textcenter">操作</th>
									<th>状态</th>
								</eq>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>
										<eq name='make_status' value='0'>
											<td>
												<input type="checkbox" name='ids[]' value="{pigcms{$vo.id}">
											</td>
										</eq>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.card_code}</td>
										<eq name='make_status' value='0'>
											<td>{pigcms{$vo.card_password}</td>
											<td>
												<a href="javascript:;" class="button make_done" data-id="{pigcms{$vo.id}">标记为制卡完成</a>
												<a href="{pigcms{:U('Ecard/ecard_coupon_del',array('id'=>$vo['id']))}" class="button del">删除</a>
											</td>
										<else/>
											<td>
												<eq name='vo.sales_type' value='0'>
													否
												<else/>
													是
												</eq>
											</td>
											<td>
												<gt name='vo.sales_type' value='0'>{pigcms{$vo.sales_time|date="Y-m-d H:i:s",###}<else/>-</gt>
											</td>
											<td>
												<switch name='vo.sales_type'>
													<case value='1'>在线购买</case>
													<case value='2'>线下购买</case>
													<default/>-
												</switch>
											</td>
											<td>{pigcms{$vo.now_price}</td>
											<td class="textcenter">
												<eq name='vo.sales_type' value='0'>
													<span data-src="{pigcms{:U('Ecard/make_ecard_coupon',array('id'=>$vo['id'],'page'=>$_GET['page']))}" class="button make_sales">标记为线下售出</span>
													<span class='button make_online_sales'  data-src="{pigcms{:U('Ecard/make_ecard_coupon',array('id'=>$vo['id'],'page'=>$_GET['page']))}">标记为线上售出</span>
												<else/>
													—
												</eq>
											</td>											
											<td data-id="{pigcms{$vo.id}">
												<gt name='vo.sales_type' value='0'>
												<span class="cb-enable">
													<label class="cb-enable <eq name='vo.status' value='1'>selected</eq>">
														<span>开启</span>
														<input type="radio" name="status" value="1" <eq name="vo.status" value='1'>checked="checked"</eq> >
													</label>
												</span>
												<span class="cb-disable">
													<label class="cb-disable <eq name='vo.status' value='0'>selected</eq>">
														<span>关闭</span>
														<input type="radio" name="status" value="0" <neq name="vo.status" value='1'>checked="checked"</neq> >
													</label>
												</span>
												</gt>
											</td>
										</eq>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="20">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="20">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script type="text/javascript">
function tip(msg,e){
	layer.alert(msg,{offset:'250px'},function(index){
		layer.close(index);
		typeof e=='function' && e();
	});
}
function layer_confirm(msg,e,e2){
	layer.open({
		content:msg,
		offset:'250px',
		btn:['确认','取消'],
		yes:function(index){
			layer.close(index);
			typeof e=='function' && e();
		},
		no:function(index){
			typeof e2=='function' && e2();
		}
	});
}
var ecard_id={pigcms{$card_id|default=0};
$(function(){
	$('#checkall').click(function(){
		var flag=this.checked;
		$('input[name="ids[]"]').prop('checked',flag);
	});
	/*批量标记*/
	var src="{pigcms{:U('Ecard/save_ecard_coupon')}";
	$('.more_set').click(function(){
		layer_confirm('确认把这些选中的设置为已制卡？',function(){
			var ids=[];
			$('input[name="ids[]"]:checked').each(function(){
				ids.push($(this).val());
			});
			if(ids.length==0){
				tip('至少选择一项标记为已制卡！');
				return false;
			}
			$.post(src,{ids:ids,ecard_id:ecard_id},function(res){
				tip(res.info,function(){
					if(res.status){
						window.location.reload();
					}	
				});
			});	
		});
			
	});
	//标记为已完成
	$('.make_done').click(function(){
		if(!confirm('确定把当前的e卡设置为已制卡！')){
			return false;
		}
		var id=$(this).data('id');
		var ids=[id];
		$.post(src,{ids:ids,ecard_id:ecard_id},function(res){
			tip(res.info,function(){
				if(res.status){
					window.location.reload();
				}
			});
		});
	});
	/*更新卡券的状态*/
	var src2='{pigcms{:U("Ecard/save_ecard_coupon_status")}';
	$('input[name="status"]').click(function(){
		var val=$(this).val();
		var id=$(this).parents('td').data('id');
		$.post(src2,{id:id,status:val},function(res){
			console.log(res);
		});
	});

	//删除e卡
	$('.del').click(function(){
		if(!confirm('确认删除当前e卡！')){
			return false;
		}
	});

	/*标记为已售出*/
	$('.make_sales').click(function(){
		var src=$(this).data('src');
		layer_confirm('确认把该e卡标记为已售出',function(){
			$.post(src,function(res){
				tip(res.info,function(){
					if(res.status){
						window.location.reload();
					}
				});
			});
		});
	});
	$('.make_online_sales').click(function(){
		var orderid=prompt('请输入售出的订单编号！');
		if(orderid==null){
			return false;
		}
		if(!orderid){
			tip('订单号不能为空！');
			return false;
		}
		var src=$(this).data('src');
		layer_confirm('确认把该e卡标记为已售出',function(){
			$.post(src,{sales_type:1,orderid:orderid},function(res){
				tip(res.info,function(){
					if(res.status){
						window.location.reload();
					}
				});
			});
		});
	});
	/*导出*/
	$('.export').click(function(){
		var src=$(this).attr('href');
		var ids=[];
		$('input[name="ids[]"]:checked').each(function(){
			ids.push($(this).val());
		});
		if(ids.length==0){
			tip('至少选择一项未制卡导出！');
			return false;
		}
		src+='&ids='+ids;
		window.location.href=src;
		return false;
	});

});
</script>
<include file="Public:footer"/>