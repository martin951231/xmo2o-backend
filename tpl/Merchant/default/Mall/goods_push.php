<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("发布商品到批发市场")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				#levelcoupon select {width:150px;margin-right: 20px;}
				select:disabled{background: #EDEDED;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#basicinfo">{pigcms{:L_("商品信息")}</a>
							</li>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post">
						<input type="hidden" value="{pigcms{$now_goods.goods_id}" id="goods_id" />
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
									<input class="col-sm-1" size="20" name="name" id="name" type="text" value="{pigcms{$now_goods.name}" disabled/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品条形码")}</label></label>
									<input class="col-sm-1" size="20" name="number" id="number" type="text" value="{pigcms{$now_goods.number}" disabled/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="unit">{pigcms{:L_("商品单位")}</label></label>
									<input class="col-sm-1" size="20" name="unit" id="unit" type="text" value="{pigcms{$now_goods.unit}" disabled/>
								</div>
                                <!--div class="form-group">
                                    <label class="col-sm-1" for="Food_status">{pigcms{:L_("商品状态")}</label>
                                    <select name="status" id="Food_status">
                                        <option value="1" <if condition="$now_goods['status'] eq 1">selected="selected"</if>>{pigcms{:L_("正常")}</option>
                                        <option value="0" <if condition="$now_goods['status'] eq 0">selected="selected"</if>>{pigcms{:L_("下架")}</option>
                                    </select>
                                </div-->
                                <div class="form-group">
                                    <label class="col-sm-1" for="Food_status">{pigcms{:L_("商品分类")}</label>
                                    <fieldset id="choose_category" cat_fid="{pigcms{$now_goods.cat_fid}" cat_id="{pigcms{$now_goods.cat_id}"></fieldset>
                                </div>
								<if condition="empty($now_goods['list'])">
								<div class="form-group">
                                    <if condition="$config['merchant_type_switch'] eq 1">
                                        <label class="col-sm-1"><label for="wholesale_price">{pigcms{:L_("普通批发价")}</label></label>
                                        <else/>
                                        <label class="col-sm-1"><label for="wholesale_price">{pigcms{:L_("批发价")}</label></label>
                                    </if>
									<input class="col-sm-1" size="20" name="wholesale_price" id="wholesale_price" type="text" value="{pigcms{$now_goods.wholesale_price}"/>
									<span class="form_tips">{pigcms{:L_("必填。")}</span>
								</div>
                                    <if condition="$config['merchant_type_switch'] eq 1">
                                        <div class="form-group">
                                            <label class="col-sm-1"><label for="vip_wholesale_price">{pigcms{:L_("VIP批发价")}</label></label>
                                            <input class="col-sm-1" size="20" name="vip_wholesale_price" id="vip_wholesale_price" type="text" value="{pigcms{$now_goods.vip_wholesale_price}"/>
                                            <span class="form_tips">{pigcms{:L_("必填。")}</span>
                                        </div>
                                    </if>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="min_num">{pigcms{:L_("最低批发数")}</label></label>
									<input class="col-sm-1" size="20" name="min_num" id="min_num" type="text" value="{pigcms{$now_goods.min_num}"/>
									<span class="form_tips">{pigcms{:L_("必填。")}</span>
								</div>

                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="stock">{pigcms{:L_("库存")}</label></label>
                                        <input class="col-sm-1" size="20" name="stock" id="stock" type="text" value="{pigcms{$now_goods.stock}"/>
                                        <span class="form_tips">{pigcms{:L_("必填。")}</span>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="stock">{pigcms{:L_("重量")}</label></label>
                                        <input class="col-sm-1" size="20" name="goods_weight" id="goods_weight" type="text" value="{pigcms{$now_goods.goods_weight}"/>
                                        <span class="form_tips">{pigcms{:L_("必填。")}</span>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="stock">{pigcms{:L_("体积")}</label></label>
                                        <input class="col-sm-1" size="20" name="goods_volume" id="goods_volume" type="text" value="{pigcms{$now_goods.goods_volume}"/>
                                        <span class="form_tips">{pigcms{:L_("必填。")}</span>
                                    </div>
								</if>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul">
											<volist name="now_goods['pic_arr']" id="vo">
												<li class="upload_pic_li"><img src="{pigcms{$vo.url}"/></li>
											</volist>
										</ul>
									</div>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="is_add_white_list">{pigcms{:L_("是否加入白名单")}</label></label>
                                    <label><input  value="0" name="is_add_white_list" type="radio" <if condition="$now_goods['is_add_white_list'] neq 1"> checked="checked" </if> />&nbsp;&nbsp;{pigcms{:L_("否")}</label>
                                    &nbsp;&nbsp;&nbsp;
                                    <label><input  value="1" name="is_add_white_list" type="radio" <if condition="$now_goods['is_add_white_list'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;{pigcms{:L_("是")}</label>
                                    <span class="form_tips">{pigcms{:L_("必选。选择是后必须是加入白名单的商户才能在批发市场中查看和批发")}</span>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-1"></label>
                                    <label>
                                        <a href="javascript:void(0);" onclick="give_white_list('{pigcms{$this->config['site_url']}/merchant.php?c=Shop&a=capture_merchant_info&goods_id={pigcms{$now_goods['goods_id']}')" >{pigcms{:L_("选择白名单成员")}</a>
                                    </label>
                                </div>
								<div class="form-group topic_box">
								    <volist name="now_goods['discount_info']" id="drow">
								    <div class="question_box properties">
								    <p class="question_info">
								    <span>{pigcms{:L_("批发满：")}</span>
								    <input type="text" class="txt properties_name" value="{pigcms{$drow['num']}" name="nums[]" style="width:50px"/>
								    <span>{pigcms{:L_("件，享受：")}</span>
								    <input type="text" class="txt properties_num" value="{pigcms{$drow['discount']}" name="discounts[]" style="width:50px"/>{pigcms{:L_("折优惠")}　
                                    <a href="javascript:;" class="box_del">{pigcms{:L_("删除")}</a></p></div>
								    </volist>
								    <p class="add_properties" style="margin-left:12px;"><a href="javascript:;" title="{pigcms{:L_("添加")}" class="btn btn-sm btn-success">{pigcms{:L_("新增优惠")}</a></p>
								</div>
                                <if condition="$config['is_switch_wholesale_mobile'] neq 1">
								<div class="topic_box">
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("是否设置为拼团")}</label>
										<select name='is_group' <eq name="now_goods.no_edit" value='1'>disabled='disabled'</eq> >
											<option value="0" <eq name='now_goods.is_group' value='0'>selected='selected'</eq> >{pigcms{:L_("否")}</option>
											<option value='1'  <eq name='now_goods.is_group' value='1'>selected='selected'</eq>>{pigcms{:L_("是")}</option>
										</select>
									</div>
									<div id="group_box" style="display:none;">
										<empty name="now_goods.spec_list">
											<div class="form-group">
												<label class="col-sm-1">{pigcms{:L_("拼团价")}</label>
												<input type='text' name='group_team_price' value="{pigcms{$now_goods.group_team_price}" <eq name="now_goods.no_edit" value='1'>readonly='readonly'</eq> />
											</div>
											<!-- <div class="form-group">
												<label class="col-sm-1">{pigcms{:L_("参团价")}</label>
												<input type='text' name='group_join_price' value="{pigcms{$now_goods.group_join_price}" />
											</div> -->
										</empty>
										<div class="form-group">
											<label class="col-sm-1">{pigcms{:L_("拼团活动时间")}</label>
											<input type='text' name='group_sdate' value="{pigcms{$now_goods.group_stime|default=''|date='Y-m-d H:i',###}" <eq name="now_goods.no_edit" value='1'>readonly='readonly'<else/>onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"</eq> />
											--
                                            <input type='text' name='group_fdate' value="{pigcms{$now_goods.group_ftime|default=''|date='Y-m-d H:i',###}" <eq name='now_goods.no_edit' value='1'>readonly='readonly'<else/>onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"</eq>/>
											<span class="form_tips">{pigcms{:L_("可开团商品的拼团活动时长，用户只可在设置的时间段内对拼团商品发起拼团或参团")}</span>
										</div>
										<div class="form-group">
											<label class="col-sm-1">{pigcms{:L_("成团有效时间")}</label>
											<select name='group_day' <eq name="now_goods.no_edit" value='1'>disabled='disabled'</eq> >
												<for start='0' end='16'>
													<option value="{pigcms{$i}" <eq name='now_goods.day' value="$i">selected='selected'</eq> >{pigcms{$i}</option>
												</for>
											</select>
											<span>天</span>
											<select name='group_hour' <eq name="now_goods.no_edit" value='1'>disabled='disabled'</eq> >
												<for start='0' end='24'>
													<option value="{pigcms{$i}" <eq name='now_goods.hour' value="$i">selected='selected'</eq> >{pigcms{$i}</option>
												</for>
											</select>
											<span>时</span>
											<select name='group_minute' <eq name="now_goods.no_edit" value='1'>disabled='disabled'</eq> >
												<for start='0' end='60'>
													<option value="{pigcms{$i}" <eq name='now_goods.minute' value="$i">selected='selected'</eq> >{pigcms{$i}</option>
												</for>
											</select>
											<span>分</span>
											<span class="form_tips">{pigcms{:L_("用户开团后与其他人组团的时间，该有效时间内没有拼团成功，将自动退款给用户；有效时间内用户不可取消拼团订单")}</span>
										</div>
										<div class="form-group">
											<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("成团人数")}</label></label>
											<input class="col-sm-2" size="20" name="group_complete_num"  type="text" value="{pigcms{$now_goods.group_complete_num}" <eq name="now_goods.no_edit" value='1'>readonly='readonly'</eq> />
											<span class="form_tips">{pigcms{:L_("成团人数最少填写2人")}</span>
										</div>
									</div>
								</div>
                                </if>
								<div class="topic_box">
									<table class="table table-striped table-bordered table-hover" id="table_list">
									<if condition="$now_goods['spec_list']">
									<tbody>
										<tr>
											<th>{pigcms{:L_("商品条形码")}</th>
											<volist name="now_goods['spec_list']" id="gs">
												<th>{pigcms{$gs['name']}</th>
											</volist>
                                            <if condition="$config['merchant_type_switch'] eq 1">
                                                <th>{pigcms{:L_("普通批发价")}</th>
                                                <th>{pigcms{:L_("VIP批发价")}</th>
                                                <else/>
                                                <th>{pigcms{:L_("批发价")}</th>
                                            </if>

                                            <th class='group_line'>{pigcms{:L_("拼团价")}</th>
											<!-- <th class='group_line'>{pigcms{:L_("参团价")}</th> -->
											<th>{pigcms{:L_("库存")}</th>
											<th>{pigcms{:L_("最低批发数")}</th>
										</tr>
										
										<volist name="now_goods['list']" id="gl" key="id_index" >
											<tr id="{pigcms{$gl['index']}">
												<td>{pigcms{$gl['number']}</td>
												<volist name="gl['spec']" id="g">
													<td>{pigcms{$g['spec_val_name']}</td>
												</volist>

                                                <if condition="$config['merchant_type_switch'] eq 1">
                                                    <td><input type="text" class="txt" name="wholesale_prices[{pigcms{$gl['index']}]" value="{pigcms{$gl['wholesale_prices']}" style="width:80px;"></td>
                                                    <td><input type="text" class="txt" name="vip_wholesale_prices[{pigcms{$gl['index']}]" value="{pigcms{$gl['vip_wholesale_prices']}" style="width:80px;"></td>
                                                    <else/>
                                                    <td><input type="text" class="txt" name="wholesale_prices[{pigcms{$gl['index']}]" value="{pigcms{$gl['wholesale_prices']}" style="width:80px;"></td>
                                                </if>
												<td  class='group_line'><input type="text" class="txt" name="group_team_prices_arr[{pigcms{$gl['index']}]" value="{pigcms{$gl['group_team_prices']}" style="width:80px;" <eq name="now_goods.no_edit" value='1'>readonly='readonly'</eq> ></td>
												<!-- <td  class='group_line'><input type="text" class="txt" name="group_join_prices_arr[{pigcms{$gl['index']}]" value="{pigcms{$gl['group_join_prices']}" style="width:80px;"></td> -->

												<td><input type="text" class="txt" name="stocks[{pigcms{$gl['index']}]" value="{pigcms{$gl['stocks']}" style="width:80px;"></td>
												<td><input type="text" class="txt" name="min_nums[{pigcms{$gl['index']}]" value="{pigcms{$gl['min_nums']}" style="width:80px;"></td>
											</tr>
										</volist>
									</tbody>
									</if>
									</table>
								</div>
							</div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										{pigcms{:L_("发布")}
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
</style>
<link rel="stylesheet" href="{pigcms{$static_path}css/activity.css">
<script type="text/javascript">
$(function(){
	$('select[name="is_group"]').change(function(){
		var val=$(this).val();
		if(val==1){
			$('#group_box').show();
			$('.group_line').show();
		}else{
			$('#group_box').hide();
			$('.group_line').hide();
		}
	});
	$('select[name="is_group"]').change();
});
</script>
<script>
var category_list = '{pigcms{$category_list}';
$(document).ready(function(){
	$(".add_properties").click(function(){
		var i = $('.properties').size();
		var t = '<div class="question_box properties"><p class="question_info"><span>{pigcms{:L_("批发满：")}</span><input type="text" class="txt properties_name" value="" name="nums[]" style="width:50px"/><span>{pigcms{:L_("件，享受（请填写0-10的数字）：")}</span><input type="text" class="txt properties_num" value="" name="discounts[]" style="width:50px"/>{pigcms{:L_("折优惠")}　'
				+'<a href="javascript:;" class="box_del">{pigcms{:L_("删除")}</a></p></div>';
		$(".add_properties").before(t);
	});
	$(document).on('click', '.box_del', function(){
	    $(this).parents('.properties').remove();
	});

	if (category_list != undefined) {
		var father_category_list = $.parseJSON(category_list);
		var son_category_list = null, cat_fid = parseInt($('#choose_category').attr('cat_fid')), cat_id = parseInt($('#choose_category').attr('cat_id'));
		var area_dom = '<select id="cat_fid" name="cat_fid" class="col-sm-1" style="margin-right:10px;">';
		if (cat_fid == 0) {
			area_dom += '<option value="0" selected="selected" >{pigcms{:L_("选择分类")}</option>';
		} else {
			area_dom += '<option value="0">{pigcms{:L_("选择分类")}</option>';
		}
		$.each(father_category_list, function(i, item){
			if (item.id == cat_fid) {
				if (item.son_list != undefined) {
					son_category_list = item.son_list;
					show_son_category(item.son_list, cat_id)
				}
				area_dom += '<option value="'+item.id+'" selected="selected" >'+item.name+'</option>';
			} else {
				area_dom += '<option value="'+item.id+'">'+item.name+'</option>';
			}
		});
		area_dom+= '</select>';
		$('#choose_category').prepend(area_dom);
//		if (son_category_list != null) show_son_category(son_category_list, cat_id);
	}
	$('#cat_fid').change(function(){
		var cat_fid = $(this).val(), father_category_list = $.parseJSON(category_list);
		var this_son_list = null;
		$.each(father_category_list, function(i, item){
			if (item.id == cat_fid) {
				if (item.son_list != undefined) {
					this_son_list = item.son_list;
					show_son_category(item.son_list, 0);
				}
			}
		});
		if (this_son_list == null) {
			$('.sortproperties').remove();
			if (document.getElementById('cat_id')) {
				$('#cat_id').replaceWith('');
			} else if(document.getElementById('cat_fid')) {
				$('#cat_fid').after('');
			} else {
				$('#choose_category').prepend('');
			}
		}
	});
});
function show_son_category(son_category_list, cat_id)
{
    var area_dom = '<select id="cat_id" name="cat_id" class="col-sm-1" style="margin-right:10px;">', now_cat_id = 0, isFirst = 0;
    $.each(son_category_list, function(i, item){
        if (cat_id == 0) {
            if (isFirst == 0) {
                now_cat_id = item.id;
                area_dom += '<option value="'+item.id+'" selected="selected" >'+item.name+'</option>';
            } else {
                area_dom += '<option value="'+item.id+'">'+item.name+'</option>';
            }
            isFirst ++;
        } else {
            if (item.id == cat_id) {
                now_cat_id = item.id;
                area_dom += '<option value="'+item.id+'" selected="selected" >'+item.name+'</option>';
            } else {
                area_dom += '<option value="'+item.id+'">'+item.name+'</option>';
            }
        }
    });
    area_dom += '</select>';
    if (document.getElementById('cat_id')) {
        $('#cat_id').replaceWith(area_dom);
    } else if(document.getElementById('cat_fid')) {
        $('#cat_fid').after(area_dom);
    } else {
        $('#choose_category').prepend(area_dom);
    }
}

function give_white_list(url) {
    parent.layer.open({
        title:false,
        closeBtn: 0,
        type:2,
        content:url,
        area:['700px','900px'],
        shade: 0.2,
        cancel:function(){

        },
        move:false
    });
}
$('form').submit(function(){
	$('select').removeAttr('disabled');
});
</script>
<include file="Public:footer"/>
