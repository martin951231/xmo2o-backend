<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('index')}">{pigcms{$config.shop_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('goods_sort_new',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("分类列表")}</a></li>
			<li class="active"><a href="{pigcms{:U('goods_list_new',array('sort_id'=>$now_sort['sort_id']))}">{pigcms{$now_sort.sort_name}</a></li>
			<li class="active">{pigcms{:L_("修改商品")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				#levelcoupon select {width:150px;margin-right: 20px;}
				table .reg_file{display:inline-block;;background: #0099cc;color: #fff;position: relative;}
				.reg_img{max-height:60px;vertical-align: bottom;;margin-right: 10px;}
			</style>
			<div class="row">
				<div class="col-xs-12">	
					<div class="tabbable" <if condition="!$_GET['goods_id']">style="display:none;"</if>>
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#basicinfo">{pigcms{:L_("基本信息")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtattr">{pigcms{:L_("商品规格")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#seckill">{pigcms{:L_("限时优惠")}</a>
							</li>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post">
						<input type="hidden" value="{pigcms{$now_goods.goods_id}" name="goods_id" />
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane  active">
								<if condition="$error_tips">
									<div class="alert alert-danger">
										<p>{pigcms{:L_("请更正下列输入错误")}:</p>
										<p>{pigcms{$error_tips}</p>
									</div>
								</if>
								<if condition="$ok_tips">
									<div class="alert alert-info">
										<p>{pigcms{$ok_tips}</p>
									</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品名称")}</label></label>
									<input type="text" id="goods_name" readonly value="{pigcms{$now_goods.name}">

									<if condition="!$_GET['goods_id']">
									<a class="btn btn-sm btn-success" onclick="addGoods()" style="line-height: 1.2;margin-left: 10px">从商品库选择</a>				
									</if>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品现价")}</label></label>
									<input class="col-sm-1" size="20" name="price" id="price" type="text" value="{pigcms{$now_goods.price|floatval}" />
									<span class="form_tips">{pigcms{:L_("必填。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="old_price">{pigcms{:L_("商品原价")}</label></label>
									<input class="col-sm-1" size="20" name="old_price" id="old_price" type="text" value="{pigcms{$now_goods.old_price|floatval}" />
									<span class="form_tips">{pigcms{:L_("必填，原价小于等于现价则前端不展示原价。")}</span>
								</div>
								<div class="form-group num_stock">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品库存")}</label></label>
									<div>{pigcms{:L_("当前库存")}</div>
									<div>
										<input name="spec_stock" style="width:70px;" id="spec_stock" type="text" value="<if condition='$now_goods'>{pigcms{$now_goods.spec_stock}<else/>-1</if>"/>
                                       
										<span class="form_tips">-1{pigcms{:L_("表示继承")}{pigcms{$config.shop_alias_name}{pigcms{:L_("商品库存。当前库存小于10时，商品详细页面会显示库存。")}</span>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品显示时间类型")}</label></label>
									<span><label><input id='seckill_type0' name="spec_type" <if condition="$now_goods['spec_type'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("固定时间段")}</span>&nbsp;</label></span>
									<span><label><input id='seckill_type1' name="spec_type" <if condition="$now_goods['spec_type'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;<span>{pigcms{:L_("每天的时间段")}</span></label></span>
									<span class="form_tips">{pigcms{:L_("用于控制该商品显示在用户前台的时间段")}</span>

								</div>

								<div class="form-group datetime" <if condition="$now_goods['spec_type'] eq 1 ">style="display:none"</if>>
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品显示时间段")}</label></label>
									<div>
										<input id="spec_open_datetime" type="text" value="<if condition="$now_goods['spec_open_datetime']">{pigcms{$now_goods['spec_open_datetime']|date='Y-m-d H:i',###}</if>" name="spec_open_datetime" readonly/>	{pigcms{:L_("至")}
										<input id="spec_close_datetime" type="text" value="<if condition="$now_goods['spec_close_datetime']">{pigcms{$now_goods['spec_close_datetime']|date='Y-m-d H:i',###}</if>" name="spec_close_datetime" readonly/>
									</div>
								</div>

                                <div class="form-group time" <if condition="$now_goods['spec_type'] eq 0 ">style="display:none"</if>>
                                    <label class="col-sm-1"><label for="price">{pigcms{:L_("商品显示时间段")}</label></label>
                                    <div>
                                        <input id="spec_open_time" type="text" value="<if condition="$now_goods['spec_open_time']">{pigcms{:date('H:i' , $now_goods['spec_open_time'])}</if>" name="spec_open_time" readonly style="width:70px"/>   至
                                        <input id="spec_close_time" type="text" value="<if condition="$now_goods['spec_open_time']">{pigcms{:date('H:i' , $now_goods['spec_close_time'])}</if>" name="spec_close_time" readonly style="width:70px"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1" for="Food_status">{pigcms{:L_("商品状态")}</label>
                                    <select name="status" id="Food_status">
                                        <option value="1" <if condition="!isset($now_goods['status']) || $now_goods['status'] neq 0"> selected</if>>{pigcms{:L_("正常")}</option>
                                        <option value="0" <if condition="isset($now_goods['status']) && $now_goods['status'] eq 0"> selected</if>>{pigcms{:L_("停售")}</option>
                                    </select>
									<span class="form_tips" style="color: red;">{pigcms{:L_("由于部分商品存在有货但线上不售卖，所以停售只会关闭用户下单场景，店员端线下零售商品库中会展示。若永远停售可以考虑删除。")}</span>
                                </div>
							</div>
							<div id="txtattr" class="tab-pane">
                                <div class='topic_til'>
                                   <h4>{pigcms{:L_("设置规格关系")}</h4>
								</div>
								<div class="topic_box">
									<table class="table table-striped table-bordered table-hover" id="table_list">
									<if condition="$now_goods['spec_list']">
									<tbody>
										<tr>
											<th>{pigcms{:L_("规格图")}</th>
											<volist name="now_goods['spec_list']" id="gs">
											<th>{pigcms{$gs['name']}</th>
											</volist>
                                           	<th>{pigcms{:L_("进价")}</th>
                                           	<th>{pigcms{:L_("现价")}</th>
                                           	<th>{pigcms{:L_("限时价")}</th>
                                           	<th>{pigcms{:L_("库存")}</th>
											<volist name="now_goods['properties_list']" id="gp">
											<th>{pigcms{$gp['name']}({pigcms{:L_("可选个数")})</th>
											</volist>
										</tr>

										<volist name="now_goods['list']" id="gl" key="num">
											<input type="hidden" name="indexs[]" value="{pigcms{$gl['key']}">
											<tr id="{pigcms{$gl['index']}">
												<td>
													<notempty name="gl.reg_img">
														<p>
															<img src="{pigcms{$gl['reg_img']['image']}" class="reg_img" />
														</p>
													</notempty>
												</td>

												<volist name="gl['spec']" id="g">
												    <td>{pigcms{$g['spec_val_name']}</td>
												</volist>
												<td>{pigcms{$gl['cost_price']}</td>
												<td><input type="text" class="txt" name="prices[]" value="{pigcms{$gl['price']}" style="width:80px;" <eq name="now_goods.is_act" value='1'>readonly='readonly' onclick="tip();" onclick="tip(this);"</eq>  ></td>
												<td><input type="text" class="txt" name="seckill_prices[]" value="{pigcms{$gl['seckill_price']}" style="width:80px;"></td>
												<td><input type="text" class="txt" name="stock_nums[]" value="{pigcms{$gl['stock_num']}" style="width:80px;"></td>
												<volist name="gl['properties']" id="gpp" key="num">
												<td>{pigcms{$gpp['num']}</td>
												</volist>
											</tr>
										</volist>
									</tbody>
									</if>
									</table>
                                </div>

                                <div class='topic_til'>
                                    <h4>{pigcms{:L_("附属商品区块")}</h4>
                                </div>
                                <div class="alert alert-info">
                                    <button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
                                </div>
								<div class="topic_box">
                                    <div class='fushuWrap'>
                                    	<if condition="$now_goods['piece_list']">
                                    	<div class="fushuBox">
                                    		<volist name="now_goods['piece_list']" id="vo">
											<div class="fushu">
												<span>{pigcms{:L_("区块名称")}：{pigcms{$vo.name}</span>
												<span>
													{pigcms{:L_("设置区块总的最少购买数和最大购买数")}：{pigcms{$vo.mininum} ~ {pigcms{$vo.maxnum}
												</span>
											</div>
											<table class="table table-striped table-bordered FuShuTbody"  style="width:800px; margin-top:20px;">
												<tbody class="tbody_til">
													<tr>
														<th>{pigcms{:L_("附属商品")}</th>
														<th>{pigcms{:L_("售价")}</th>
														<th>{pigcms{:L_("最少可选数")}</th>
														<th>{pigcms{:L_("最多可选数")}</th>
														<th>{pigcms{:L_("排序")}</th>
													</tr>
												</tbody>
												<tbody class="tbodyFushu"> 
													<volist name="vo.goods" id="vv">
														<tr>
															<td>{pigcms{$vv.name}</td>
															<td><if condition="$vv['spec_value']">多规格<else/>{pigcms{$vv.price}</if></td>
															<td>{pigcms{$vv.mininum}</td>
															<td>{pigcms{$vv.maxnum}</td>
															<td>{pigcms{$vv.sort}</td>
														</tr>
													</volist>
												</tbody>
											</table>

                                    		</volist>
										</div> 
										</if>
                                    </div>
								</div>
							</div>
							<div id="seckill" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("商品限时价")}</label></label>
									<input class="col-sm-1" size="20" name="seckill_price" id="seckill_price" type="text" value="{pigcms{$now_goods.seckill_price|default=0}"/>
									<span class="form_tips">0{pigcms{:L_("表示无限时价。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("限时价库存")}</label></label>
									<input class="col-sm-1" size="20" name="seckill_stock" id="seckill_stock" type="text" value="{pigcms{$now_goods.seckill_stock}"/>
									<span class="form_tips">-1{pigcms{:L_("表示无限量。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="price">{pigcms{:L_("限时价类型")}</label></label>
									<span><label><input id='seckill_type0' name="seckill_type" <if condition="$now_goods['seckill_type'] eq 0 ">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("固定时间段")}</span>&nbsp;</label></span>
									<span><label><input id='seckill_type1' name="seckill_type" <if condition="$now_goods['seckill_type'] eq 1 ">checked="checked"</if> value="1" type="radio" >&nbsp;<span>{pigcms{:L_("每天的时间段")}</span></label></span>
								</div>

								<div class="form-group datetime1" <if condition="$now_goods['seckill_type'] eq 1 ">style="display:none"</if>>
									<label class="col-sm-1"><label for="price">{pigcms{:L_("限时段")}</label></label>
									<div>
                                        <input id="goods_seckill_open_datetime" type="text" <if condition="$now_goods['seckill_open_time']">value="{pigcms{$now_goods['seckill_open_time']|date='Y-m-d H:i',###}"<else/>value=''</if> name="seckill_open_datetime" readonly/>	{pigcms{:L_("至")}
                                        <input id="goods_seckill_close_datetime" type="text" <if condition="$now_goods['seckill_close_time']">value="{pigcms{$now_goods['seckill_close_time']|date='Y-m-d H:i',###}"<else/>value=''</if> name="seckill_close_datetime" readonly/>
									</div>
								</div>

                                <div class="form-group time1" <if condition="$now_goods['seckill_type'] eq 0 ">style="display:none"</if>>
                                    <label class="col-sm-1"><label for="price">{pigcms{:L_("限时段")}</label></label>
                                    <div>
                                        <input id="goods_seckill_open_time" type="text" <if condition="$now_goods['seckill_open_time']">value="{pigcms{:date('H:i' , $now_goods['seckill_open_time'])}"<else/>value=''</if> name="seckill_open_time" readonly style="width:70px"/>   至
                                        <input id="goods_seckill_close_time" type="text" <if condition="$now_goods['seckill_close_time']">value="{pigcms{:date('H:i' , $now_goods['seckill_close_time'])}"<else/>value=''</if> name="seckill_close_time" readonly style="width:70px"/>
                                    </div>
								</div>
							</div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										{pigcms{:L_("保存")}
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<style>
	input.ke-input-text {
		background-color: #FFFFFF;
		background-color: #FFFFFF!important;
		font-family: "sans serif",tahoma,verdana,helvetica;
		font-size: 12px;
		line-height: 24px;
		height: 24px;
		padding: 2px 4px;
		border-color: #848484 #E0E0E0 #E0E0E0 #848484;
		border-style: solid;
		border-width: 1px;
		display: -moz-inline-stack;
		display: inline-block;
		vertical-align: middle;
		zoom: 1;
	}
	.form-group>label{font-size:12px;line-height:24px;}
	#upload_pic_box{margin-top:20px;height:150px;}
	#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
	#upload_pic_box img{width:100px;height:70px;}
	.webuploader-element-invisible {
	    position: absolute !important;
	    clip: rect(1px 1px 1px 1px);
	    clip: rect(1px,1px,1px,1px);
	}
	.webuploader-pick-hover .btn{
		background-color: #629b58!important;
	    border-color: #87b87f;
	}
	.show-select-value{position: relative;float: left;border: 1px solid #d5d5d5;width: 400px;min-height: 30px;border-radius: 2px;}
	.show-select-value .select-list{margin: 10px}
	.show-select-value .select-list .item{float: left;border:1px solid #d5d5d5;background: #f2f2f2;padding:0 5px;margin: 0 10px 10px 0;border-radius: 2px;}
	.show-select-value .select-list .item .icon{padding: 0 5px;font-weight: bold;cursor: pointer;}
	.show-select-value .select-icon{position: absolute;right: 10px;top: 17px;vertical-align: middle;width: 0;height: 0;border: 5px solid transparent;border-top-color:#666;}
	.show-select-value .list{position: absolute;bottom: -100px;width: 400px;border: 1px solid #1686ef;background: #fff;border-radius: 2px;display: none;}
	.show-select-value .list.active{display: block;}
	.show-select-value .list .item{padding: 2px 10px}
	.show-select-value .list .item:hover{background: #1686ef;color: #fff;}
	table .reg_img {
	    max-height: 60px;
	    vertical-align: bottom;
	    margin-right: 10px;
	}
</style>
<script type="text/javascript">
	$(document).on('click', '.show-select-value', function(){
		if ($(this).find('.list').attr('class').indexOf('active') != -1) {
			$(this).find('.list').removeClass('active');
			return 
		}
		$(this).find('.list').addClass('active').css('bottom', -$(this).find('.list').height()-2)
	})

	$(document).on('click', '.show-select-value .list .item', function(){
		var group_id = $(this).data('id');
		var group_ids = $('input[name=group_ids]').val();
		if (group_ids != '') {
			var group_ids_arr = group_ids.split(',');
			var selected = 0;
			$.each(group_ids_arr,function (index,j) {
				if(group_id == j){
					selected = 1;
				}
			})
			if (selected==0) {
				group_ids_arr.push(group_id);
				group_ids = group_ids_arr.join(',');
				$('input[name=group_ids]').val(group_ids);
			}else{
				$('.show-select-value .list').removeClass('active');
				return false;
			}
		}else{
			$('input[name=group_ids]').val(group_id);
		}
		$('.show-select-value .select-list').append('<div class="item" >'+ $(this).html() +'<span class="icon">×</span></div>');
		$('.show-select-value .list').removeClass('active');
		return false
	})
	$(document).on('click', '.show-select-value .select-list .item .icon', function(){
		var group_id = $(this).data('id');
		var group_ids = $('input[name=group_ids]').val();
		var group_ids_arr = group_ids.split(',');
		group_ids_arr.splice($.inArray(group_id,group_ids_arr),1);
		group_ids = group_ids_arr.join(',');
		$('input[name=group_ids]').val(group_ids);
		$(this).parents('.item').remove()
		return false
	})
</script>
<link rel="stylesheet" href="{pigcms{$static_path}css/activity.css">
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
<script>
    var uploaderHas = false;
    var uploaderHas2 = false;
    var swf = '{pigcms{$static_public}js/Uploader.swf';
    var server = "{pigcms{:U('Shop/ajax_upload_pic', array('store_id' => $now_store['store_id']))}";
    var diyVideo = "{pigcms{:U('Article/diyVideo')}";
    var no_spec = false;
    var formathtml = new Array();
    var format_value = new Array();
    var json = '{pigcms{$now_goods['json']}', sortList = '{pigcms{$sort_list}', selectIds = '{pigcms{$select_ids}';
    var category_list = '{pigcms{$category_list}', ajax_goods_properties = "{pigcms{:U('Shop/ajax_goods_properties')}";
    var session_index = 'goods_{pigcms{$now_sort["sort_id"]}';

    window.sessionStorage.setItem(session_index, json);
    function tip(e) {
        if (act_name) {
            var tip_msg = '{pigcms{:L_("抱歉！该商品已经被设置为")}' + act_name + '{pigcms{:L_("活动，该项暂不可修改与删除。请先到商城管理")}--{pigcms{:L_("商城店铺列表")}--' + act_name + '{pigcms{:L_("管理中解禁该商品。")}';
            layer.alert(tip_msg, {title: '{pigcms{:L_("提示")}'});
            e.preventDefault();
            return false;
        }
    }
    $('.tr_del').live('click', function () {
        $(this).parents('tr').remove();
    });

    $(".box_del").live('click',function(){
        $(this).parents(".fushuBox").remove();
    });



$('#spec_open_datetime').click(function(){WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'})});
$('#spec_close_datetime').click(function(){WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'})});
$('#spec_open_time').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'00','minute':'00'}));
$('#spec_close_time').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'23','minute':'59'}));
$('#goods_seckill_open_datetime').click(function(){WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'})});
$('#goods_seckill_close_datetime').click(function(){WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'})});
$('#goods_seckill_open_time').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'00','minute':'00'}));
$('#goods_seckill_close_time').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'23','minute':'59'}));

$('input[name=spec_type]').change(function(){
    if ($(this).val() == 0) {
        $('.datetime').show();
        $('.time').hide();
    } else {
        $('.datetime').hide();
        $('.time').show();
    }
});

$('input[name=seckill_type]').change(function(){
    if ($(this).val() == 0) {
        $('.datetime1').show();
        $('.time1').hide();
    } else {
        $('.datetime1').hide();
        $('.time1').show();
    }
});
</script>
<!-- <script type="text/javascript" src="{pigcms{$static_path}js/goods.js?t=122"></script> -->
<script type="text/javascript">
	var goods_url = "{pigcms{:U('Diypage/select_from_shop',array('sort_id' => $now_sort['sort_id'],'store_id' => $now_sort['store_id'],))}";
	function addGoods(){
		parent.layer.open({
			title:false,
			closeBtn: 0,
			type:2,
			content:goods_url+'&goods_id=',
			area:['650px','576px'],
			shade: 0.2,
			cancel:function(){ 
			},
			move:false
		});
	}
	var old_goods_id = 0;
	function login_box_after(a,b,c,d,goods_id,index){
		if(goods_id==old_goods_id){
			layer.close(index);
			return false;
		}

		old_goods_id=goods_id;
		$.post('{pigcms{:U("Shop/get_goods_info")}',{goods_id:goods_id},function(res){
			if(res.status==1){
				var goods_info=res.data;
				
				$('#goods_name').val(goods_info.name);
				$('input[name=goods_id]').val(goods_info.goods_id);
				$('input[name=price]').val(goods_info.price);
				$('input[name=spec_stock]').val(-1);

				// 限时优惠
				$('input[name=seckill_price]').val(goods_info.seckill_price);
				$('input[name=seckill_stock]').val(goods_info.seckill_stock);
				$('input[name=seckill_type]').val(goods_info.seckill_type);
			    if (goods_info.seckill_type== 0) {
			        $('.datetime1').show();
			        $('.time1').hide();
			        $('#goods_seckill_open_datetime').val(goods_info.open_time_formart);
			        $('#goods_seckill_close_datetime').val(goods_info.close_time_formart);
			    } else {
			        $('.datetime1').hide();
			        $('.time1').show();
			        $('#goods_seckill_open_time').val(goods_info.open_time_formart);
			        $('#goods_seckill_close_time').val(goods_info.close_time_formart);
			    }

				if (goods_info.spec_list) {
					var html = '<tbody>\
										<tr>\
											<th>{pigcms{:L_("商品条形码")}</th>\
											<th>{pigcms{:L_("规格图")}</th>';
					for (var i = 0; i < goods_info.spec_list.length; i++) {
						html += '<th>'+goods_info.spec_list[i].name+'</th>';
					}

					html +='<th>{pigcms{:L_("现价")}</th><th>{pigcms{:L_("限时价")}</th><th>{pigcms{:L_("库存")}</th>';
					$.each( goods_info.properties_list,function(e,item){
						html += '<th>'+item.name+'({pigcms{:L_("可选个数")})</th>';
					})				
					html += '					</tr>';

					$.each(goods_info.list,function(e,item){
						html += '<tr id="'+item.index+'">';
						html += '<input type="hidden" name="indexs[]" value="'+e+'">';
						html += '	<td>'+item.number+'</td>';
						html += '<td>';
						if (item.reg_img) {
							html += '	<p><img src="'+item.reg_img.image+'" class="reg_img" /></p></td>'
						}
						for (var j = 0; j < item.spec.length; j++) {
							html += '<td><p>'+item.spec[j].spec_val_name+'</td>';
						}					
						
						html += '<td><input type="text" class="txt" name="prices[]" value="'+item.price+'" style="width:80px;"></td>' ;
						html += '<td><input type="text" class="txt" name="seckill_prices[]" value="'+item.seckill_price+'" style="width:80px;"></td>' ;
						html += '<td><input type="text" class="txt" name="stock_nums[]" value="'+item.stock_num+'" style="width:80px;"></td>' ;
						for (var j = 0; j < item.properties.length; j++) {
							html += '<td>'+item.properties[j].num+'</td>';
						}					
										
						html += '/tr>';
					})
					html += '</tbody>';
				}
				$('#table_list').html(html);
				$('.tabbable').show();
				$('form').show();

				// 附属菜
				var html='';
				var arraySubsidiary=goods_info.piece_list;
	            if(arraySubsidiary!=null && arraySubsidiary.length>0){
	                arraySubsidiary.forEach(function(value,i,array){
		                html+='<div class="fushuBox">'
		                html+=' <div class="fushu">'
		                html += '		<span>{pigcms{:L_("区块名称")}：'+value.name
		                html += '		</span>'
		                html+=' <span>'
		                html+='{pigcms{:L_("设置区块总的最少购买数和最大购买数")}：'+value.mininum+' ~ '+value.maxnum
		                html+=' </span>  '
		                html+='</div> '
		                html+=' <table class="table table-striped table-bordered FuShuTbody"  style="width:800px; margin-top:20px;">'
		                html+=' <tbody class="tbody_til">'
		                html+='     <tr>'
		                html += '					<th>{pigcms{:L_("附属商品")}</th>'
		                html += '					<th>{pigcms{:L_("售价")}</th>'
		                html += '					<th>{pigcms{:L_("最少可选数")}</th>'
		                html += '					<th>{pigcms{:L_("最多可选数")}</th>'
		                html += '					<th>{pigcms{:L_("排序")}</th>'
		                html+=' </tr>'
		                html+=' </tbody>'
		                html+=' <tbody class="tbodyFushu"> '
		                if(value.goods.length>0){
		                   var arrayGoods=value.goods;
		                   arrayGoods.forEach(function(valueJ,j,array2){
		                       if(valueJ.spec_value){
		                           var inputstr = '多规格';
		                       }else{
		                           var inputstr = valueJ.price;
		                       }
		                       html+='<tr><td>'+valueJ.name+'</td><td>'+inputstr+'</td><td>'+valueJ.mininum+'</td><td>'+valueJ.maxnum+'</td><td>'+valueJ.sort+'</td></tr>'
		                    })
		                }
		                html+='  </tbody>'
		                html+=' </table>'
		                html+='</div> '
		            })
	            }

	            $('.fushuWrap').append(html);

				layer.close(index);
			}else{
				layer.msg(res.info);
			}
		}).error(function(){
			alert('{pigcms{:L_("网络出错，请重试")}');
		});	
	}
</script>
<include file="Public:footer"/>
<style>
	.add_table{
		width:90px;
	}
	.add_properties{
		width:90px;
	}
	
	.add_spec{
		width:90px;
	}
    .alert-info {
        background-color: #f3f8f2;
        border-color: #f3f8f2;
        color: #999;
    }

    .topic_til {
        background: #f0f0f0;
        padding: 10px;
        position: relative;
    }

    .topic_til:before {
        content: '';
        width: 8px;
        height: 8px;
        background: #87b87f;
        border-radius: 50%;
        display: block;
        position: absolute;
        left: 15px;
        top: 25px;
    }

    .topic_til h4 {
        padding: 0 20px;
    }

    .question_box ul a.list_del {
        background: #f00;
        color: #fff !important;
        border-radius: 100%;
        width: 18px;
        height: 18px;
        text-align: center;
        line-height: 18px;
    }

    .box_del, .tr_del {
        padding: 5px 10px;
        background: #f00;
        color: #fff;
        border-radius: 15px;
    }

    a.box_del, .tr_del:hover {
        color: #fff
    }

    .btn {
        border-radius: 15px;
    }

    .add_spec, .add_table {
        
    }

    .fushu span {
        padding-right: 20px;
    }

    .fushu span input {
        width: 80px;
    }

    .btn-success.btn_guige, .btn-success.btn_guige:focus {
        background-color: #0099cc !important;
        border-color: #0099cc;
    }

    .tbody_til {
        /* display: none */
    }

    .fushuBox {
        margin: 20px 0;
    }
    .topic_box{
        padding-top:0
    }
</style>