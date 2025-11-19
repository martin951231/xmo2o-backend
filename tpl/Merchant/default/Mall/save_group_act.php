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
			<li>{pigcms{:L_("编辑")}/{pigcms{:L_("新增拼团活动")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<style type="text/css">
	#goods_spec{width:800px;border-color: #ccc;border-style: solid;border-width: 1px 0 0 1px;margin-bottom:10px;}
	#goods_spec tr:first-child{background:#eee;}
	#goods_spec th{height:32px;}
	#goods_spec th,#goods_spec td{border-color: #ccc;border-style: solid;border-width: 0 1px 1px 0;text-align: center;}
	#goods_spec input{text-indent:3px;margin:5px 0;height:26px;}
	select:disabled{background: #F5F5F5;}
	</style>
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<!-- <div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">				
							<li class="active">
								<a href="{pigcms{:U('Mall/sort_edit',array('sort_id'=>$now_sort['sort_id']))}">{pigcms{:L_("编辑分类")}</a>
							</li>
						</ul>
					</div> -->
					<div class="tab-content" style="padding-top:10px;">
						<div class="grid-view">							
							<form enctype="multipart/form-data" class="form-horizontal" method="post">
								<input type="hidden" name="store_id" value="{pigcms{$now_store.store_id}"/>
								<input type="hidden" name="id" value="{pigcms{$info.id}"/>
								<input type="hidden" name="goods_id" value="{pigcms{$info.goods_id}"/>
								<p style="font-size:23px;font-weight:bold;">{pigcms{:L_("拼团基本信息")}</p>
								<div class="alert alert-info" >
									<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>1{pigcms{:L_("、拼团商品不参与平台与商家的任何优惠，含平台")}/{pigcms{:L_("商家优惠券、平台")}/{pigcms{:L_("商家满减、平台")}/{pigcms{:L_("商家会员卡优惠、店铺折扣。")}<br/>
									2{pigcms{:L_("、拼团商品库存与原商品共用库存，当开团者或参团者成功支付拼团商品后才扣除相应库存，用户无法开团或参团库存为0的拼团商品，添加拼团商品时请先保证原商品库存充足。")}<br/>
									3{pigcms{:L_("、原商品被添加为拼团活动商品后，则商家不可再编辑原商品，想要编辑原商品需先将拼团活动删除。")}<br/>
									4{pigcms{:L_("、添加拼团商品仅支持选择设置为商城的商品（X1商品归属商城分类后即为商城商品）",['X1'=>$config['shop_alias_name']])}
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("拼团活动时间")}</label></label>
									<input class="col-sm-2" size="15" name="sdate" type="text" value="{pigcms{$info.stime|default=''|date='Y-m-d H:i:s',###}" <eq name='info.is_edit' value='0'>readonly<else/>onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"</eq> />
									<span style='float:left;line-height:34px;padding:0 10px;background:#eee;'>{pigcms{:L_("至")}</span>
									<input class="col-sm-2" size="15" name="fdate" type="text" value="{pigcms{$info.ftime|default=''|date='Y-m-d H:i:s',###}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm',<eq name='info.is_edit' value='0'>minDate:'{pigcms{$info.ftime|date='Y-m-d H:i:s',###}'</eq>})"/>

									<span class="form_tips">{pigcms{:L_("可开团商品的拼团活动时长，用户只可在设置的时间段内对拼团商品发起拼团或参团")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("成团有效时间")}</label></label>
									<select name='day' <eq name='info.is_edit' value='0'>disabled="true"</eq> >
										<for start='0' end='16'>
											<option value="{pigcms{$i}" <eq name='info.day' value="$i">selected='selected'</eq> >{pigcms{$i}</option>
										</for>
									</select>
									<span>天</span>
									<select name='hour' <eq name='info.is_edit' value='0'>disabled="true"</eq> >
										<for start='0' end='24'>
											<option value="{pigcms{$i}" <eq name='info.hour' value="$i">selected='selected'</eq> >{pigcms{$i}</option>
										</for>
									</select>
									<span>时</span>
									<select name='minute' <eq name='info.is_edit' value='0'>disabled="true"</eq> >
										<for start='0' end='60'>
											<option value="{pigcms{$i}" <eq name='info.minute' value="$i">selected='selected'</eq> >{pigcms{$i}</option>
										</for>
									</select>
									<span>分</span>
									<span class="form_tips">{pigcms{:L_("用户开团后与其他人组团的时间，该有效时间内没有拼团成功，将自动退款给用户；有效时间内用户不可取消拼团订单")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("成团人数")}</label></label>
									<input class="col-sm-2" size="20" name="complete_num"  type="text" value="{pigcms{$info.complete_num}" <eq name='info.is_edit' value='0'>readonly="true"</eq>/>
									<span class="form_tips">{pigcms{:L_("成团人数最少填写2人")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("每人限购")}</label></label>
									<input class="col-sm-2" size="20" name="limit_num" type="text" value="{pigcms{$info.limit_num}" <eq name='info.is_edit' value='0'>readonly="true"</eq>/>
									<span class="form_tips">{pigcms{:L_("每人最多可购买多少件，填写0或不填写均为不限制；设置为拼团的商品，则该商品的原每单")}/{pigcms{:L_("每ID限购，最少起购数无效")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("拼团类型")}</label></label>
									<div style="width:80px;float:left;font-size:16px;">
										<label><input type="radio" value="1" name="join_type" <if condition="empty($info) || $info['join_type'] eq 1">checked="checked"</if> <eq name='info.is_edit' value='0'>disabled="true"</eq>/>{pigcms{:L_("普通拼团")}</label>&nbsp;&nbsp;
									</div>
									<div style="width:80px;float:left;font-size:16px;">
										<label><input type="radio" value="2" name="join_type" <if condition="$info['join_type'] eq 2">checked="checked"</if> <eq name='info.is_edit' value='0'>disabled="true"</eq>/>{pigcms{:L_("邀新团")}</label>&nbsp;&nbsp;
									</div>
									<span class="form_tips">{pigcms{:L_("邀新团即所有用户都能开团，但必须要店铺新用户才能参团")}</span>
								</div>
								<p>
									<span style="font-size:23px;font-weight:bold;">{pigcms{:L_("关联商品")}</span>
									<span style='color:red;font-size:12px;'>{pigcms{:L_("（建议：团长价一般比参团价优惠）")}</span>
								</p>
								<table style="width:800px;" id='goods_spec'>
									<notempty name='info'>
										<tr>
											<th>{pigcms{:L_("商品编号")}</th>
											<th style="width:120px;">{pigcms{:L_("商品名称")}</th>
											<volist name='info.spec_list' id='vo'>
												<th>{pigcms{$vo.name}</th>
											</volist>
											<th>{pigcms{:L_("现价")}({pigcms{$config['Currency_txt']})</th>
											<th>{pigcms{:L_("剩余库存")}</th>
											<th>{pigcms{:L_("参团价")}({pigcms{$config['Currency_txt']})</th>
											<th>{pigcms{:L_("团长价")}({pigcms{$config['Currency_txt']})</th>
										</tr>										
										<notempty name='info.spec_list'>
											<volist name='info.list' id='vo2' key='k'>
												<tr>
													<eq name="k" value='1'>
														<td rowspan="{pigcms{$info.list|count}">{pigcms{$info.goods_id}</td>
														<td rowspan="{pigcms{$info.list|count}" width="100px">{pigcms{$info.name}</td>
													</eq>
													<volist name='vo2.spec' id='vo3'>
														<td>{pigcms{$vo3.spec_val_name}</td>
													</volist>
													<td>{pigcms{$vo2.price}</td>
													<td>{pigcms{$vo2.t_stock_num}</td>
													<td><input type='text' name='join_price[]' value='{pigcms{$info["join_price"][$k-1]}' <eq name='info.is_edit' value='0'>readonly="true"</eq>></td>
													<td><input type='text' name='team_price[]' value='{pigcms{$info["team_price"][$k-1]}' <eq name='info.is_edit' value='0'>readonly="true"</eq>></td>
												</tr>
											</volist>
										<else/>
											<tr>
												<td>{pigcms{$info.goods_id}</td>
												<td>{pigcms{$info.name}</td>
												<td>{pigcms{$info.price}</td>
												<td>{pigcms{$info.t_stock_num}</td>
												<td><input type='text' name='join_price[]' value="{pigcms{$info.join_price.0}"  <eq name='info.is_edit' value='0'>readonly="true"</eq>></td>
												<td><input type='text' name='team_price[]' value="{pigcms{$info.team_price.0}"  <eq name='info.is_edit' value='0'>readonly="true"</eq>></td>
											</tr>
										</notempty>
											
									</notempty>									
								</table>
								<neq name='info.is_edit' value='0'>
									<a id='choose_good_btn' class="btn btn-success" >{pigcms{:L_("选择商品")}</a>
								</neq>
								<div class="clearfix form-actions">
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="submit">
											<i class="ace-icon fa fa-check bigger-110"></i>
											{pigcms{:L_("保存")}
										</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script>
var old_goods_id="{pigcms{$info.goods_id}";
old_goods_id=parseInt(old_goods_id);
var goods_html_url='{pigcms{$this->config['site_url']}/merchant.php?c=Diypage&a=mall_good&store_id={pigcms{$_GET.store_id}&type=mall_group';
$(function(){
	/*{pigcms{:L_("调整保存按钮的位置")}*/
	$(".nav-tabs li a").click(function(){
		if($(this).attr("href")=="#imgcontent"){		//"店铺图片")}
			$(".form-submit-btn").css('position','absolute');
			$(".form-submit-btn").css('top','670px');	
		}else{
			$(".form-submit-btn").css('position','static');
		}
	});

	$('form.form-horizontal').submit(function(){
		$(this).find('button[type="submit"]').html('{pigcms{:L_("保存中")}...').prop('disabled',true);
	});
	$('#choose_good_btn').click(function(){

		parent.layer.open({
			title:false,
			closeBtn: 0,
			type:2,
			content:goods_html_url+'&goods_id='+old_goods_id,
			area:['650px','576px'],
			shade: 0.2,
			cancel:function(){ 
			},
			move:false
		});
	});
});
function login_box_after(a,b,c,d,goods_id,index){

	if(goods_id==old_goods_id){
		layer.close(index);
		return false;
	}
	old_goods_id=goods_id;
	$.post('{pigcms{:U("Mall/get_good_info")}',{goods_id:goods_id},function(res){
		if(res.status==1){
			var data=res.info;
			var html="";
			if(data.spec_list.length>0){
				html+='<tr>';
				html+='<th>{pigcms{:L_("商品编号")}</th>';
				html+='<th style="width:120px;">{pigcms{:L_("商品名称")}</th>';
				$.each(data.spec_list,function(n,v){
					html+='<th>'+v.name+'</th>';
				});
				html+="<th>{pigcms{:L_("现价")}({pigcms{$config['Currency_txt']})</th>";
				html+="<th>{pigcms{:L_("剩余库存")}</th>";
				html+='<th>{pigcms{:L_("参团价")}({pigcms{$config['Currency_txt']})</th>';
				html+='<th>{pigcms{:L_("团长价")}({pigcms{$config['Currency_txt']})</th>';
				html+='</tr>';
				var i=0;
				var arr=Object.keys(data.list);
				var row=arr.length;
				$.each(data.list,function(n2,v2){
					html+='<tr>';
					if(i==0){
						html+='<th rowspan="'+row+'">'+data.goods_id+'</th>';
						html+='<th rowspan="'+row+'">'+data.name+'</th>';		
					}
					i++;								
					$.each(v2.spec,function(n3,v3){
						html+='<td>'+v3.spec_val_name+'</td>';
					});
					html+='<th>'+v2.price+'</th>';
					html+='<th>'+v2.t_stock_num+'</th>';
					html+='<th><input type="text" name="join_price[]"></th>';
					html+='<th><input type="text" name="team_price[]"></th>';					
					html+='</tr>';
				});
			}else{
				html+="<tr>";
				html+='<th>{pigcms{:L_("商品编号")}</th>';
				html+='<th style="width:120px;">{pigcms{:L_("商品名称")}</th>';
				html+='<th>{pigcms{:L_("现价")}({pigcms{$config['Currency_txt']})</th>';
				html+="<th>{pigcms{:L_("剩余库存")}</th>";
				html+='<th>{pigcms{:L_("参团价")}({pigcms{$config['Currency_txt']})</th>';
				html+='<th>{pigcms{:L_("团长价")}({pigcms{$config['Currency_txt']})</th>';
				html+='</tr>';
				html+="<tr>";
				html+='<td>'+data.goods_id+'</td>';
				html+='<td>'+data.name+'</td>';
				html+='<td>'+data.price+'</td>';
				html+='<td>'+data.t_stock_num+'</td>';
				html+='<th><input type="text" name="join_price[]"></th>';
				html+='<th><input type="text" name="team_price[]"></th>';
				html+='</tr>';
			}
			$('#goods_spec').html(html);
			$('input[name=goods_id]').val(goods_id);
			layer.close(index);
		}else{
			layer.msg(res.info);
		}
	}).error(function(){
		alert('{pigcms{:L_("网络出错，请重试")}');
	});	
}
//"提交表单")}
var clock=0;
$('form').submit(function(){
	var sdate=$('input[name=sdate]').val();
	var fdate=$('input[name=fdate]').val();
	if(!sdate || !fdate){
		tip('{pigcms{:L_("请选择拼团活动时间")}');
		return false;
	}
	stime=new Date(sdate).getTime();
	ftime=new Date(fdate).getTime();
	if(stime>ftime){
		tip('{pigcms{:L_("活动开始时间不能大于结束时间")}');
		return false;
	}
	var day=$('select[name=day]').val();
	var hour=$('select[name=hour]').val();
	var minute=$('select[name=minute]').val();
	console.log(day,hour,minute);
	if((!day || day<=0) && (!hour || hour<=0) && (!minute || minute<=0)){
		tip('{pigcms{:L_("请设置成团有效时间")}');
		return false;
	}
	//"成团人数")}
	var complete_num=$('input[name=complete_num]').val();
	if(isNaN(complete_num) || complete_num<=1){
		tip('{pigcms{:L_("成团人数最少填写2人")}');
		return false;
	}
	var goods_id=$('input[name=goods_id]').val();
	if(goods_id<=0){
		tip('{pigcms{:L_("请选择拼团商品")}');
		return false;
	}
	var flag=true;
	var pobj=$('#goods_spec');
	pobj.find('input[name="join_price[]"]').each(function(n,v){
		var val=$(this).val();
		var val2=pobj.find('input[name="team_price[]"]').eq(n).val();
		if(val<=0 || val2<=0){
			tip('{pigcms{:L_("请填写正确的拼团价格")}');
			flag=false;
			return false;
		}
	});
	if(!flag){
		return false;
	}
	$('select').removeAttr('disabled');
	$('input[type=radio]').removeAttr('disabled');
});
function login_box_close(index){
	layer.close(index);
}
function tip(msg){
	if(!msg){
		return false;
	}
	layer.alert(msg,{icon:2,closeBtn:0},function(index){
		$('.btn.btn-info').text('{pigcms{:L_("保存")}').removeAttr('disabled');
		layer.close(index);
	});
}
</script>

<include file="Public:footer"/>
