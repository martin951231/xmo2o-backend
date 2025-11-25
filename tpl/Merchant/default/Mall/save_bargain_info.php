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
				<a href="{pigcms{:U('Mall/bargain_list',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("砍价列表")}</a>
			</li>
			<li>{pigcms{:L_("编辑")}/{pigcms{:L_("新增砍价活动")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<style type="text/css">
	#goods_spec{width:600px;border-color: #ccc;border-style: solid;border-width: 1px 0 0 1px;margin-bottom:10px;}
	#goods_spec tr:first-child{background:#eee;}
	#goods_spec th{height:32px;}
	#goods_spec th,#goods_spec td{border-color: #ccc;border-style: solid;border-width: 0 1px 1px 0;text-align: center;height:32px;}
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
					<div class="tab-content" style="padding-top:10px;">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="form-horizontal" method="post">
								<input type="hidden" name="store_id" value="{pigcms{$now_store.store_id}"/>
								<input type="hidden" name="id" value="{pigcms{$info.id}"/>
								<input type="hidden" name="goods_id" value="{pigcms{$info.goods_id}"/>
								<p style="font-size:23px;font-weight:bold;">{pigcms{:L_("砍价基本信息")}</p>
								<div class="alert alert-info" >
									<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>1、{pigcms{:L_("砍价商品不参与平台与商家的任何优惠，含平台")}/{pigcms{:L_("商家优惠券、平台")}/{pigcms{:L_("商家满减、平台")}/{pigcms{:L_("商家会员卡优惠、店铺折扣。")}<br/>
									2、{pigcms{:L_("砍价商品库存与原商品共用库存，当用户成功支付砍价商品后才扣除相应库存（发起砍价时不会减少库存），用户无法发起库存为0的砍价商品，添加砍价商品时请先保证原商品库存充足。")}<br/>
									3、{pigcms{:L_("原商品被添加为砍价商品后，则商家不可再编辑原商品，想要编辑原商品需先将砍价活动删除。")}<br/>
									4、{pigcms{:L_("添加砍价商品仅支持选择设置为商城的商品（X1商品归属商城分类后即为商城商品）",['X1'=>$config['shop_alias_name']])}
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("砍价活动时间")}</label></label>
									<input class="col-sm-2" size="15" name="sdate" type="text" value="{pigcms{$info.stime|default=''|date='Y-m-d H:i:s',###}" <eq name='info.is_edit' value='0'>readonly<else/>onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"</eq> />
									<span style='float:left;line-height:34px;padding:0 10px;background:#eee;'>{pigcms{:L_("至")}</span>
									<input class="col-sm-2" size="15" name="fdate" type="text" value="{pigcms{$info.ftime|default=''|date='Y-m-d H:i:s',###}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm',<eq name='info.is_edit' value='0'>minDate:'{pigcms{$info.ftime|date='Y-m-d H:i:s',###}'</eq>})"/>

									<span class="form_tips">{pigcms{:L_("可砍价商品的砍价活动时长，用户只可在设置的时间段内对砍价商品发起砍价或帮人砍价")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("砍价有效时间")}</label></label>
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
									<span class="form_tips">{pigcms{:L_("从用户发起砍价到砍价截止的时间")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("砍价底价")}</label></label>
									<input class="col-sm-2" size="20" name="floor_price"  type="text" value="{pigcms{$info.floor_price}" <eq name='info.is_edit' value='0'>readonly="true"</eq>/>
									<span class="form_tips">{pigcms{:L_("不设置则砍价底价为")}0{pigcms{$config['Currency_txt']}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("每次砍价范围")}</label></label>
									<input class="col-sm-1" size="20" name="low_price" type="text" value="{pigcms{$info.low_price}" <eq name='info.is_edit' value='0'>readonly="true"</eq>/>
									<span style='float:left;line-height:34px;padding:0 10px;'>到</span>
									<input class="col-sm-1" size="20" name="high_price" type="text" value="{pigcms{$info.high_price}" <eq name='info.is_edit' value='0'>readonly="true"</eq>/>
									<span class="form_tips">{pigcms{:L_("自己或朋友砍下一刀的范围，砍价的范围里的最大值不能大于商品原价减去底价，若砍下后小于底价则砍至底价，精确到小数点后两位")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("最低帮砍人数")}</label></label>
									<input class="col-sm-2" size="20" name="min_num" type="text" value="{pigcms{$info.min_num}" <eq name='info.is_edit' value='0'>readonly="true"</eq>/>
									<span class="form_tips">{pigcms{:L_("每个砍价订单的帮砍人数最少达到该值时才能砍至底价，不设置或设置为0则不限制砍价人数")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("砍价限购数")}</label></label>
									<input class="col-sm-2" size="20" name="max_bar_num" type="text" value="{pigcms{$info.max_bar_num}" <eq name='info.is_edit' value='0'>readonly="true"</eq> />
									<span class="form_tips">{pigcms{:L_("每个用户发起砍价活动后，以当前的砍价金额购买商品的最多次数，不设置或设置为0则不限制最大砍价购买数")}</span>
								</div>
								<p >
									<span style="font-size:23px;font-weight:bold;">{pigcms{:L_("关联商品")}</span>
									<span id='tips' style='color:red;'></span>
								</p>
								<table style="width:800px;" id='goods_spec'>
									<tr>
										<th>{pigcms{:L_("商品编号")}</th>
										<th>{pigcms{:L_("商品名称")}</th>
										<th>{pigcms{:L_("规格")}</th>
										<th>{pigcms{:L_("现价")}</th>
										<th>{pigcms{:L_("剩余库存")}</th>
										<th>{pigcms{:L_("操作")}</th>
									</tr>
									<notempty name='info'>
										<tr>
											<input type='hidden' name="goods_id[]" value="{pigcms{$info.goods_id}" />
											<input type='hidden' name="spec_id[]" value="{pigcms{$info.spec_id}" />
											<input type='hidden' name="spec_price[]" value="{pigcms{$info.price}" />
											<td>{pigcms{$info.goods_id}</td>
											<td>{pigcms{$info.name}</td>
											<td>{pigcms{$info.reg_name}</td>
											<td>{pigcms{$info.price}</td>
											<td>{pigcms{$info.stock_num}</td>
											<td>
												<eq name='info.is_edit' value='1'>
													<button type="button" onClick="save_good(1)">{pigcms{:L_("更换")}</button>
												<else/>
													{pigcms{:L_("不可操作")}
												</eq>
											</td>
										</tr>	
									</notempty>						
								</table>
								<if condition="empty($info)">
									<a class="btn btn-success choose_good_btn" onclick="save_good()">{pigcms{:L_("选择商品")}</a>
								</if>
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
var goods_id={pigcms{$info.goods_id|default=0};
var goods_price={pigcms{$info.price|default=0};
var default_spec_id="{pigcms{$info.spec_id|default=''}";
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
});
function save_good(is_edit){
	is_edit=is_edit || 0;
	parent.layer.open({
		title:false,
		closeBtn: 0,
		type:2,
		content:'{pigcms{$this->config['site_url']}/merchant.php?c=Diypage&a=mall_good&store_id={pigcms{$_GET.store_id}&type=mall_bargain&get_reg=1&is_edit='+is_edit+'&goods_id='+goods_id+'&spec_id='+default_spec_id,
		area:['650px','576px'],
		shade: 0.2,
		cancel:function(){
			
		},
		move:false
	});
}
//"选择规格后的展示")}
function login_box_after(goods_id,spec_id,spec_name,name,price,is_edit,index){
	var key=goods_id+':'+spec_id;
	var flag=false;
	$('#goods_spec tr:gt(0)').each(function(){
		if($(this).data('key')==key){
			if(spec_id==''){
				layer.msg('{pigcms{:L_("该商品的已添加，请勿重复添加！")}');
			}else{
				layer.msg('{pigcms{:L_("商品的该规格已添加，请勿重复添加！")}');
			}
			flag=true;
			return false;
		}
	});
	if(flag){
		return false;
	}
	var stock_num='';
	$.ajax({
		type:'post',
		url:"{pigcms{:U('Mall/get_spec_stock')}",
		data:{goods_id:goods_id,spec_id:spec_id},
		async:false,
		success:function(res){
			if(res.status){
				stock_num=res.stock_num;
			}
		}
	});
	default_spec_id=spec_id;
	var html='';
	html+='<input type="hidden" name="goods_id[]" value="'+goods_id+'" />';
	html+='<input type="hidden" name="spec_id[]" value="'+spec_id+'" />';
	html+='<input type="hidden" name="spec_price[]" value="'+price+'" />';
	html+='<td>'+goods_id+'</td>';
	html+='<td>'+name+'</td>';
	html+='<td>'+spec_name+'</td>';
	html+='<td>'+price+'</td>';
	html+='<td>'+stock_num+'</td>';
	var floor_price=$('input[name=floor_price]').val();
	if(floor_price>0 && floor_price>=price){
		var tips="({pigcms{:L_("提示：砍价底价应该小于商品原价！")})";
		$('#tips').text(tips);
	}else{
		$('#tips').hide();
	}
	//"更新商品价格")}
	goods_price=price;
	if(is_edit>0){
		html+='<td><button type="button" onClick="save_good(1)">{pigcms{:L_("更换")}</button></td>';
		var obj=$('<tr>',{'data-key':key}).html(html);
		$('#goods_spec').find('tr:gt(0)').remove();
		$('#goods_spec').append(obj);
	}else{
		html+='<td><button type="button" onClick="save_good(1)">{pigcms{:L_("更换")}</button></td>';
		//html+='<td><button type="button" onClick="del_good(this)">{pigcms{:L_("删除")}</button></td>';
		var obj=$('<tr>',{'data-key':key}).html(html);
		$('#goods_spec').append(obj);
		$('.choose_good_btn').hide();	
	}
	layer.close(index);
}
//"删除添加的规格商品")}
function del_good(e){
	$(e).parents('tr').remove();
}

//"提交表单")}
var clock=0;
$('form').submit(function(){
	var sdate=$('input[name=sdate]').val();
	var fdate=$('input[name=fdate]').val();
	if(!sdate || !fdate){
		tip('{pigcms{:L_("请选择砍价活动时间")}');
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
		tip('{pigcms{:L_("请设置砍价有效时间")}');
		return false;
	}
	var floor_price=$('input[name=floor_price]').val();
	if(isNaN(floor_price) || floor_price<0){
		tip('{pigcms{:L_("请设置有效的砍价底价！")}');
		return false;
	}

	var low_price=$('input[name=low_price]').val();
	low_price=parseFloat(low_price);
	var high_price=$('input[name=high_price]').val();
	high_price=parseFloat(high_price);
	if((isNaN(low_price) || low_price<=0) || (isNaN(high_price) || high_price<=0) || low_price>high_price){
		tip('{pigcms{:L_("请设置正确的砍价范围")}');
		return false;
	}	
	var num=$('#goods_spec').find('tr').length;
	if(num<2){
		tip('{pigcms{:L_("请设置砍价活动关联商品")}');
		return false;
	}
	var d_price=goods_price-floor_price;
	if(d_price<high_price){
		tip('{pigcms{:L_("砍价的范围里的最大值不能大于商品原价减去底价")}');
		return false;
	}
	var min_num=$('input[name=min_num]').val();
	min_num=parseInt(min_num);
	if(min_num>0){
		var max_num=Math.ceil((goods_price-floor_price)/low_price);
		if(max_num<min_num+1){
			tip('{pigcms{:L_("抱歉，按照目前设置的砍价最低价和商品价格，当前设置的帮砍人数最多设置为")}'+(max_num-1)+'人');
			return false;
		}
	}
	$('select').removeAttr('disabled');
	$('input[type=radio]').removeAttr('disabled');
});
$('input[name="low_price"]').bind('input propertychange',function(){
	var price=$(this).val();
	console.log(parseFloat(price).toFixed(2));
});
function tip(msg){
	if(!msg){
		return false;
	}
	layer.alert(msg,{icon:2,closeBtn:0},function(index){
		$('.btn.btn-info').text('{pigcms{:L_("保存")}').removeAttr('disabled');
		layer.close(index);
	});
}
function login_box_close(index){
	layer.close(index);
}
</script>

<include file="Public:footer"/>
