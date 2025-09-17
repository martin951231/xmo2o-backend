<include file="Public:header"/>
<style type="text/css">
.btn{margin-right:10px;}
.btn.hover{background:#0099cc;color: #fff;}
</style>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('User/recharge_card')}"  <eq name="ACTION_NAME" value='ecard_list'>class="on"</eq>  >返回</a>
					<a href="{pigcms{:U('User/recharge_card_coupon_list',array('card_id'=>$card_id,'make_status'=>0))}" <eq name="make_status" value='0'>class="on"</eq>  >未制卡列表</a>
					<a href="{pigcms{:U('User/recharge_card_coupon_list',array('card_id'=>$card_id,'make_status'=>1))}"  <eq name="make_status" value='1'>class="on"</eq> >已制卡列表</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<eq name='make_status' value='0'>
							<a href="{pigcms{:U('User/recharge_card_coupon_export',array('card_id'=>$card_id))}" class="button export">导出未制作卡片</a>
							<a href="javascript:;" class="button more_set" >批量标记已制作卡片</a>
							<a href="javascript:;" class="button more_del" >批量删除</a>
						<else/>
							<a href="{pigcms{:U('User/recharge_card_coupon_list',array('card_id'=>$card_id,'make_status'=>$make_status))}" class="btn <eq name='_GET.use_status' value=''>hover</eq>">全部卡片</a>	
							<a href="{pigcms{:U('User/recharge_card_coupon_list',array('card_id'=>$card_id,'make_status'=>$make_status,'use_status'=>0))}" class="btn <eq name='_GET.use_status' value='0'>hover</eq>">未使用卡片</a>	
							<a href="{pigcms{:U('User/recharge_card_coupon_list',array('card_id'=>$card_id,'make_status'=>$make_status,'use_status'=>1))}" class="btn <eq name='_GET.use_status' value='1'>hover</eq>">已使用卡片</a>	
							<a href="javascript:;" class="button more_on" >批量激活</a>
							<a href="javascript:;" class="button more_off" >批量关闭</a>
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
								<th><input type='checkbox' id='checkall'></th>
								<th>ID</th>
								<th>卡号</th>
								<th>卡密</th>
								<eq name='make_status' value='0'>
									<th >操作</th>
								<else/>
									<th>是否使用</th>
									<th>使用时间</th>
									<th>使用用户</th>
									<th>面值</th>
									<th>状态</th>
								</eq>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>
										<td>
											<eq name='vo.use_status' value='0'><input type="checkbox" name='ids[]' value="{pigcms{$vo.id}"><else/><input type="checkbox" name='ids[]' value="{pigcms{$vo.id}" disabled="true"></eq>
										</td>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.card_code}</td>
										<td>{pigcms{$vo.card_password}</td>
										<eq name='make_status' value='0'>
											<td>
												<a href="javascript:;" class="button make_done" data-id="{pigcms{$vo.id}">标记为制卡完成</a>
												<a href="{pigcms{:U('User/recharge_card_coupon_del',array('id'=>$vo['id']))}" class="button del">删除</a>
											</td>
										<else/>
											<td><eq name='vo.use_status' value='1'>已使用<else/>未使用</eq></td>
											<td><gt name='vo.use_time' value='0'>{pigcms{$vo.use_time|date='Y-m-d H:i:s',###}<else/>未使用</gt></td>		
											<td>{pigcms{$vo.nickname}</td>	
											<td>{pigcms{$vo.money}</td>	
											<td data-id="{pigcms{$vo.id}">
												<eq name='vo.use_status' value='0'>
												<span class="cb-enable">
													<label class="cb-enable <eq name='vo.status' value='1'>selected</eq>">
														<span>激活</span>
														<input type="radio" name="status" value="1" <eq name="vo.status" value='1'>checked="checked"</eq> >
													</label>
												</span>
												<span class="cb-disable">
													<label class="cb-disable <eq name='vo.status' value='0'>selected</eq>">
														<span>关闭</span>
														<input type="radio" name="status" value="0" <neq name="vo.status" value='1'>checked="checked"</neq> >
													</label>
												</span>
												</eq>
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
var card_id={pigcms{$card_id|default=0};
$(function(){
	$('#checkall').click(function(){
		var flag=this.checked;
		$('input[name="ids[]"]').prop('checked',flag);
	});
	/*批量标记*/
	var src="{pigcms{:U('save_recharge_card_coupon')}";
	var del_src="{pigcms{:U('recharge_card_coupon_del')}";
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
			$.post(src,{ids:ids,card_id:card_id},function(res){
				tip(res.info,function(){
					if(res.status){
						window.location.reload();
					}	
				});
			});	
		});
			
	});
	/*批量删除*/
	$('.more_del').click(function(){
		layer_confirm('确认删除选中的卡券？',function(){
			var ids=[];
			$('input[name="ids[]"]:checked').each(function(){
				ids.push($(this).val());
			});
			if(ids.length==0){
				tip('至少一项！');
				return false;
			}
			$.get(del_src,{id:ids,card_id:card_id},function(res){
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
		if(!confirm('确定把当前的充值卡设置为已制卡！')){
			return false;
		}
		var id=$(this).data('id');
		var ids=[id];
		$.post(src,{ids:ids,card_id:card_id},function(res){
			tip(res.info,function(){
				if(res.status){
					window.location.reload();
				}
			});
		});
	});
	/*更新卡券的状态*/
	var src2='{pigcms{:U("save_recharge_card_coupon_status")}';
	$('input[name="status"]').click(function(){
		var val=$(this).val();
		var id=$(this).parents('td').data('id');
		$.post(src2,{id:id,status:val},function(res){
			console.log(res);
		});
	});
	/*批量激活*/
	$('.more_on').click(function(){
		var ids=[];
		$('input[name="ids[]"]:checked').each(function(){
			ids.push($(this).val());
		});
		if(ids.length==0){
			tip('至少一项！');
			return false;
		}
		var id=$(this).parents('td').data('id');
		$.post(src2,{id:ids,status:1},function(res){
			window.location.reload();
		});
	});
	/*批量关闭*/
	$('.more_off').click(function(){
		var ids=[];
		$('input[name="ids[]"]:checked').each(function(){
			ids.push($(this).val());
		});
		if(ids.length==0){
			tip('至少一项！');
			return false;
		}
		var id=$(this).parents('td').data('id');
		$.post(src2,{id:ids,status:0},function(res){
			if(res.status){
				window.location.reload();
			}
		});
	});

	//删除e卡
	$('.del').click(function(){
		if(!confirm('确认删除当前充值卡！')){
			return false;
		}
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