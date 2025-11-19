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
								<input type="hidden" name="store_id" value="{pigcms{$_GET['store_id']}"/>
								<input type="hidden" name="id" value="{pigcms{$info.id}"/>
								
								<p style="font-size:23px;font-weight:bold;">{pigcms{:L_("基本信息:")}</p>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("活动名称:")}</label></label>
									<input class="col-sm-2" size="20" name="pack_name"  type="text" value="{pigcms{$info.pack_name}" <eq name='info.is_edit' value='0'>readonly="true"</eq>/>
									<!--<span class="form_tips">{pigcms{:L_("成团人数最少填写2人")}</span>-->
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("活动时间:")}</label></label>
									<input class="col-sm-2" size="15" name="begin_time" type="text" value="{pigcms{$info.begin_time|default=''|date='Y-m-d H:i:s',###}" <eq name='info.is_edit' value='0'>readonly<else/>onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"</eq> />
									<span style='float:left;line-height:34px;padding:0 10px;background:#eee;'>{pigcms{:L_("至")}</span>
									<input class="col-sm-2" size="15" name="end_time" type="text" value="{pigcms{$info.end_time|default=''|date='Y-m-d H:i:s',###}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm',<eq name='info.is_edit' value='0'>minDate:'{pigcms{$info.end_time|date='Y-m-d H:i:s',###}'</eq>})"/>
									
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("每人最多参与:")}</label></label>
									<input class="col-sm-1" size="20" name="pack_limit"   placeholder="0为不设上限" type="text" value="{pigcms{$info.pack_limit}" onkeyup="this.value=this.value.replace(/\D/g,'')" onafterpaste="this.value=this.value.replace(/\D/g,'')" />
									<!--<span class="form_tips">{pigcms{:L_("成团人数最少填写2人")}</span>-->
								</div>

								<p style="font-size:23px;font-weight:bold;">{pigcms{:L_("活动规则")}</p>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("活动详情:")}</label></label>
									<input class="col-sm-1" style="width:70px;" size="10" name="pack_price"  type="text" value="{pigcms{$info.pack_price}" onkeyup="if(isNaN(value))execCommand('undo')" onafterpaste="if(isNaN(value))execCommand('undo')"/>
									<span style="float:left;line-height: 34px;"> 元，任选 </span>
									<input class="col-sm-1" style="width:30px;" size="10" name="pack_num"  type="text" value="{pigcms{$info.pack_num}" onkeyup="this.value=this.value.replace(/\D/g,'')" onafterpaste="this.value=this.value.replace(/\D/g,'')"/>
									<span> 件</span>
									
									<span class="form_tips">{pigcms{:L_("结算时，若订单内商品超出活动件数，则按照商品金额高低，默认价高商品参与活动，价低商品原价支付；如果同价商品超出活动件数，则默认先加购的参与活动；注意：打包价高于商品原件时，将按照商品原价购买")}</span>
								</div>
								
								
								<table style="width:800px;" id='goods_spec'>
									<tr>
										<th>商品编号</th>
										<th>商品名称</th>
										<th>现价</th>
										<th>操作</th>

									</tr>
									<volist name="info.goods_list" id="go">
									<tr>
										<input type="hidden" name="goods_str[]" value="{pigcms{$go.goods_id}">
										<td>{pigcms{$go.goods_id}</td>
										<td>{pigcms{$go.name} </td>
										<td>{pigcms{$go.price}</td>
										<th><button type="button" class="cancel" data-id="{pigcms{$go.goods_id}">移除</button></th>
									</tr>
									</volist>
								</table>

								<a id='choose_good_btn' class="btn btn-success" >{pigcms{:L_("选择商品")}</a>
						
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
var old_goods_id="{pigcms{$info.goods_str}";
// alert(old_goods_id)
// old_goods_id=old_goods_id;
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
	old_goods_id_arr = old_goods_id.split(',');
	var new_goods_id_arr = [];
	var is_add = 1;
	$.each(old_goods_id_arr,function(i){
		if (old_goods_id_arr[i] == goods_id) {
			is_add = 0;
		}
	})
	if (is_add==1) {
		old_goods_id += ','+goods_id;
	}else{
		layer.close(index);
		return false;
	}

	$.post('{pigcms{:U("Mall/get_good_info")}',{goods_id:goods_id},function(res){

		if(res.status==1){
			var data=res.info;
			var html="";

			html+="<tr>";
			html+='<input type="hidden" name="goods_str[]" value="'+data.goods_id+'">';
			html+='<td>'+data.goods_id+'</td>';
			html+='<td>'+data.name+'</td>';
			html+='<td>'+data.price+'</td>';
			
			html+='<th><button type="button" class="cancel" data-id="'+data.goods_id+'">移除</button></th>';
			html+='</tr>';
			
			$('#goods_spec').append(html);
			//$('input[name=goods_id]').val(goods_id);
			layer.close(index);
		}else{
			layer.msg(res.info);
		}
	}).error(function(){
		alert('{pigcms{:L_("网络出错，请重试")}');
	});	
	
}
$(document).on('click', '.cancel', function() {
	$(this).parent().parent().remove();
	var gid = $(this).data('id');
	old_goods_id_arr = old_goods_id.split(',');
	var new_goods_id_arr = [];
	$.each(old_goods_id_arr,function(i){
		if (old_goods_id_arr[i] != gid) {
			new_goods_id_arr.push(old_goods_id_arr[i]);
		}
	})
	old_goods_id = new_goods_id_arr.join(',');
});

//"提交表单")}
var clock=0;
$('form').submit(function(){
	var pack_name=$('input[name=pack_name]').val();
	var begin_time=$('input[name=begin_time]').val();
	var end_time=$('input[name=end_time]').val();
	var pack_limit=$('input[name=pack_limit]').val();
	var pack_price=$('input[name=pack_price]').val();
	var pack_num=$('input[name=pack_num]').val();
	var d = {};
  	$("input[name^='goods_str']").each(function(i, el) {
    	d[el.name] =$(this).val();
  	});

	if(!pack_name){
		tip('{pigcms{:L_("请填写活动名称")}');
		return false;
	}
	if(!begin_time || !end_time){
		tip('{pigcms{:L_("请选择活动时间")}');
		return false;
	}
	stime=new Date(begin_time).getTime();
	ftime=new Date(end_time).getTime();
	if(stime>ftime){
		tip('{pigcms{:L_("活动开始时间不能大于结束时间")}');
		return false;
	}
	if(!pack_limit){
		tip('{pigcms{:L_("请设置活动参与次数")}');
		return false;
	}
	if(!parseFloat(pack_price)){
		console.log(parseFloat(pack_price));
		tip('{pigcms{:L_("请设置活动打包价格")}');
		return false;
	}
	if(!d){
		tip('{pigcms{:L_("请选择活动商品")}');
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
