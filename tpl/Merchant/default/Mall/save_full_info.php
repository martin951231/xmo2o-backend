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
				<a href="{pigcms{:U('Mall/full_list',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("满减列表")}</a>
			</li>
			<li>{pigcms{:L_("编辑")}/{pigcms{:L_("新增满减活动")}</li>
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
								<p style="font-size:23px;font-weight:bold;">{pigcms{:L_("满减送基本信息")}</p>


								<p style="font-size:14px;font-weight:bold;">{pigcms{:L_("活动信息:")}</p>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("满减送活动名称")}</label></label>
									<input class="col-sm-2" size="20" name="name"  type="text" value=""/>

								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("满减送活动时间")}</label></label>
									<input class="col-sm-2" size="15" name="sdate" type="text" value="{pigcms{$info.stime|default=''|date='Y-m-d H:i:s',###}" <eq name='info.is_edit' value='0'>readonly<else/>onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"</eq> />
									<span style='float:left;line-height:34px;padding:0 10px;background:#eee;'>{pigcms{:L_("至")}</span>
									<input class="col-sm-2" size="15" name="fdate" type="text" value="{pigcms{$info.ftime|default=''|date='Y-m-d H:i:s',###}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm',<eq name='info.is_edit' value='0'>minDate:'{pigcms{$info.ftime|date='Y-m-d H:i:s',###}'</eq>})"/>


								</div>
								<p style="font-size:14px;font-weight:bold;">{pigcms{:L_("优惠设置:")}</p>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("优惠方式:")}</label></label>
									<div style="width:80px;float:left;font-size:16px;">
										<label><input type="radio" value="1" name="type" checked="checked"/>{pigcms{:L_("普通优惠")}</label>&nbsp;&nbsp;
									</div>
									<div style="width:80px;float:left;font-size:16px;">
										<label><input type="radio" value="2" name="type"/>{pigcms{:L_("多级优惠")}</label>
									</div>
									<span class="form_tips">{pigcms{:L_("(每级优惠不叠加累计)")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("优惠条件:")}</label></label>
									<div style="float:left;font-size:14px;color:red;">
										1、{pigcms{:L_("优惠劵和赠品的有效期时间需大于活动的截止时间。")}<br/>
										2、{pigcms{:L_("优惠券必须为赠送劵,且不能设置限领数量。")}<br/>
									</div>
								</div>

								<table class="table table-striped table-bordered table-hover">
									<thead>
									<tr>

										<th>{pigcms{:L_("优惠门槛")}</th>
										<th>{pigcms{:L_("优惠方式")}</th>
										<th>{pigcms{:L_("操作")}</th>

									</tr>
									</thead>
									<tbody>
										<tr id="layer">

										<td class="rt-border td-class">满&nbsp;<input class="specifications-input" maxlength="20" name="goods_price[]" type="text" value="">&nbsp;元</td>
										<td class="rt-border td-class">
											送&nbsp;<select name="coupon_id[]">
												<option value="" selected="selected">{pigcms{:L_("请选择赠送优惠券")}</option>
												<volist name="receive_coupon_list" id="vo">
													<option value="{pigcms{$vo.coupon_id}" >{pigcms{$vo.name}</option>
												</volist>
											</select>
											<br><br>
											送&nbsp;<select name="give_goods_id[]">
												<option value="" selected="selected">{pigcms{:L_("请选择赠送商品")}</option>
												<volist name="list" id="vo">
													<option value="{pigcms{$vo.goods_id}" >{pigcms{$vo.name} &nbsp;价格:{pigcms{$vo.price}</option>
												</volist>
											</select>
										</td>
										<td></td>
										</tr>

                                    <tr id='hierarchy'>
										<td>
											<a  class="btn btn-success" >{pigcms{:L_("+新增一级优惠")}</a>
										</td>
                                        <td></td>
										<td></td>
									</tr>
									</tbody>

								</table>

								<p style="font-size:14px;font-weight:bold;">{pigcms{:L_("选择活动商品:")}<div style="color:gray;">(只支持赠送自营商品,不支持虚拟商品)</div></p>
								<div class="form-group" id="form-groups">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("活动商品:")}</label></label><br>
									<div style="float:left;font-size:16px;">
										<label><input type="radio" value="1" name="activity_goods_type" checked="checked"/>{pigcms{:L_("全部商品参与")}</label>&nbsp;&nbsp;
									</div>
									<div style="float:left;font-size:16px;">
										<label><input type="radio" value="2" name="activity_goods_type"/>{pigcms{:L_("部分商品参与")}</label>
									</div>
								</div>
								<p id="goods">
									<span style="font-size:23px;font-weight:bold;">{pigcms{:L_("关联商品")}</span>
									<span id='tips' style='color:red;'></span>
								</p>
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
											<th><button type="button" class="cancel">移除</button></th>
										</tr>
									</volist>
								</table>

								<a id='choose_good_btns' class="btn btn-success" >{pigcms{:L_("选择商品")}</a>
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
    console.log("1111");
	if($("input[name='activity_goods_type']").val() == 1){
	  $("#goods").hide()
	  $("#goods_spec").hide()
	  $("#choose_good_btns").hide()
	}

	if($("input[name='type']").val() == 1){
		$("#hierarchy").hide()

	}




	$('form.form-horizontal').submit(function(){
		$(this).find('button[type="submit"]').html('{pigcms{:L_("保存中")}...').prop('disabled',true);
	});
});

$("#hierarchy").click(function(){
	var htmls = '';
	htmls = '<tr><td class="rt-border td-class">满&nbsp;<input class="specifications-input" maxlength="20" name="goods_price[]" type="text" value="">&nbsp;元</td><td class="rt-border td-class">送&nbsp;<select name="coupon_id[]"><option value="" selected="selected">{pigcms{:L_("请选择赠送优惠券")}</option><volist name="receive_coupon_list" id="vo"><option value="{pigcms{$vo.coupon_id}" >{pigcms{$vo.name}</option></volist></select><br><br>送&nbsp;<select name="give_goods_id[]"><option value="" selected="selected">{pigcms{:L_("请选择赠送商品")}</option><volist name="list" id="vo"><option value="{pigcms{$vo.goods_id}" >{pigcms{$vo.name} &nbsp;价格:{pigcms{$vo.price}</option></volist></select></td><td><span style="cursor: pointer;" class="test btn btn-success">-删除</span></td></tr>'
    $("#layer").after(htmls);
});



$(document).on('click', '.test', function() {
	console.log("==")
	$(this).parent().parent().remove();
});

$("input[name='activity_goods_type']").click(function(e) {

	console.log($("input[name='activity_goods_type']:checked").val())
	if($("input[name='activity_goods_type']:checked").val() == 1){
		$("#goods").hide()
		$("#goods_spec").hide()
		$("#choose_good_btns").hide()
	}else if($("input[name='activity_goods_type']:checked").val() == 2){
		$("#goods").show()
		$("#goods_spec").show()
		$("#choose_good_btns").show()
	}

});

$("input[name='type']").click(function(e) {

	console.log($("input[name='type']:checked").val())
	if($("input[name='type']:checked").val() == 1){
		$("#hierarchy").hide()

	}else if($("input[name='type']:checked").val() == 2){

		$("#hierarchy").show()
	}

});
var chk_value =[];
$('#choose_good_btns').click(function(){
    $.each($("input[name='goods_str[]'"),function (e) {
        chk_value.push($(this).val());
    })
     old_goods_id = chk_value.join(",");
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



function login_box_after(a,b,c,d,goods_id,index){
	if(goods_id==old_goods_id){
		layer.close(index);
		return false;
	}

    var is_repeat=false;
    $.each($("input[name='goods_str[]'"),function (e) {
        if ($(this).val()==goods_id) {
            is_repeat = true;
        }
    })

    if (is_repeat) {
        layer.alert('{pigcms{:L_("不能添加重复商品")}',{offset: getScrollTop()});
        return false;
    }

	old_goods_id=goods_id;
	$.post('{pigcms{:U("Mall/get_good_info")}',{goods_id:goods_id},function(res){

		if(res.status==1){
			var data=res.info;
			var html="";

			html+="<tr>";
			html+='<input type="hidden" name="goods_str[]" value="'+data.goods_id+'">';
			html+='<td>'+data.goods_id+'</td>';
			html+='<td>'+data.name+'</td>';
			html+='<td>'+data.price+'</td>';

			html+='<th><button type="button" class="cancel" data-id="8">移除</button></th>';
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
});




//"提交表单")}
var clock=0;
$('form').submit(function(){
	var sdate=$('input[name=sdate]').val();
	var fdate=$('input[name=fdate]').val();
	if(!sdate || !fdate){
		tip('{pigcms{:L_("请选择满减送活动时间")}');
		return false;
	}
	stime=new Date(sdate).getTime();
	ftime=new Date(fdate).getTime();
	if(stime>ftime){
		tip('{pigcms{:L_("活动开始时间不能大于结束时间")}');
		return false;
	}

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
