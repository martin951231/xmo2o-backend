<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Market/market')}">{pigcms{:L_("进销存")}</a>
			</li>
			<li class="active">{pigcms{:L_("进货")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				#levelcoupon select {width:150px;margin-right: 20px;}
				.group_btn{margin-left: 20px;}
				.time_box{display:inline-block;margin-left: 10px;}
                .flex {
                    display: flex !important;
                    display: -webkit-box;
                    display: -moz-box;
                    display: -ms-flexbox;
                    display: -webkit-flex;
                    align-items: center;
                }
                .flex_grow {
                    flex-grow: 1;
                    text-align: center
                }
			</style>
			<div class="row">
				<div class="col-xs-12">
                    <div class="clearfix">
                        <a class="btn btn-success" href="{pigcms{:U('Market/cart')}" style="float:left;">{pigcms{:L_("购物车")}({pigcms{$count})</a>
                    </div>
                    <br/>
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#basicinfo">{pigcms{:L_("商品信息")}</a>
							</li>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post">
						<input type="hidden" value="{pigcms{$now_goods.goods_id}" name="goods_id" />
						<input type="hidden" value="0" name="type" id="type"/>
                        <if condition="$config['is_switch_wholesale_mobile'] eq 1">
						<input type="hidden" value="{pigcms{$now_goods.is_group|default=0}" name="is_group"/>
                        </if>
						<input type="hidden" value="{pigcms{$team_id}" name="team_id"/>
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品名称")}</label></label>
									<label class="col-sm-1">{pigcms{$now_goods.name}</label>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品条形码")}</label></label>
									<label class="col-sm-1">{pigcms{$now_goods.number}</label>
								</div>
								<if condition="empty($now_goods['list'])">
								<div class="form-group">
									<label class="col-sm-1"><label for="wholesale_price">{pigcms{:L_("批发价")}</label></label>
									<label class="col-sm-1">{pigcms{$now_goods.price|floatval}({pigcms{$config['Currency_txt']})</label>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="stock">{pigcms{:L_("库存")}</label></label>
									<label class="col-sm-1">{pigcms{$now_goods.stock_num}({pigcms{$now_goods.unit})</label>
								</div>

                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("配送计费方式")}</label>
                                    <div  class="col-sm-1">
                                        <php>
                                            if (2==$now_goods['freight_type']) {
                                            echo '按重量';
                                            } else if(3==$now_goods['freight_type']) {
                                            echo '按体积';
                                            } else {
                                            echo '按件数';
                                            }
                                        </php>
                                    </div>
                                </div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="min_num">{pigcms{:L_("最低批发数")}</label></label>
									<label class="col-sm-1">{pigcms{$now_goods.min_num}({pigcms{$now_goods.unit})</label>
								</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul">
											<volist name="now_goods['pic_arr']" id="vo">
												<li class="upload_pic_li"><img src="{pigcms{$vo.url}" class="view_img"/></li>
											</volist>
										</ul>
									</div>
								</div>
								<div class="form-group topic_box">
								    <volist name="now_goods['discount_info']" id="drow">
								    <div class="question_box properties">
    								    <p class="question_info">
        								    <span>{pigcms{:L_("批发满：")}</span>
        								    <input type="text" class="txt properties_name" value="{pigcms{$drow['num']}" style="width:50px" disabled/>
        								    <span>{pigcms{:L_("件，享受：")}</span>
        								    <input type="text" class="txt properties_num" value="{pigcms{$drow['discount']}" style="width:50px" disabled/>{pigcms{:L_("折优惠")}
    								    </p>
								    </div>
								    </volist>
								</div>
                                <div class="form-group" style="color: red;font-weight: bolder;">
                                    <label class="col-sm-1">{pigcms{:L_("配送方式")}</label>
                                    <if condition="$is_pick">
                                        <label>
                                            <input type="radio" name='is_pick_in_store' value="0" <if condition="$has_check eq 'pick'">checked='checked'</if> />&nbsp;&nbsp;{pigcms{:L_("到店自提")}
                                        </label>
                                    </if>
                                    <if condition="$is_type eq 1 || $is_type eq 3">
                                        <label>
                                            <input type="radio" name='is_pick_in_store' value="1" <if condition="$has_check eq 'express'">checked='checked'</if>/>&nbsp;&nbsp;{pigcms{:L_("快递配送")}
                                        </label>
                                    </if>
                                    <if condition="$is_type eq 2 || $is_type eq 3">
                                        <label>
                                            <input type="radio" name='is_pick_in_store' value="2" <if condition="$has_check eq 'logistics'">checked='checked'</if>/>&nbsp;&nbsp;{pigcms{:L_("物流配送")}
                                        </label>
                                    </if>
                                    <if condition="$is_type neq 1 && $is_type neq 2 && $is_type neq 3">
                                        <label>
                                            <input type="radio" name='is_pick_in_store' value="1" <if condition="$has_check eq ''">checked='checked'</if>/>&nbsp;&nbsp;{pigcms{:L_("快递配送")}
                                        </label>
                                    </if>
                                </div>



                                <div class="form-group pick_info" style="font-weight: bolder;<if condition='!$is_pick'>display:none;</if>">
                                    <label class="col-sm-1">{pigcms{:L_("自提点选择")}</label>
                                    <volist name="pick_address" id="item">
                                        <label class="flex" style="float: left;">
                                            <input type="radio" name='pick_id' value="{pigcms{$item['pick_addr_id']}"/>&nbsp;&nbsp;
                                            <div style="padding: 5px;border: 1px dotted #ccc;">
                                                <p>地址： {pigcms{$item['name']}</p>
                                                <p>电话： {pigcms{$item['phone']}</p>
                                                <p>省市区： {pigcms{$item['area_info']['province']} {pigcms{$item['area_info']['city']} {pigcms{$item['area_info']['area']}</p>
                                            </div>
                                        </label>
                                    </volist>
                                </div>
                                <div style="clear: both;"></div>


                                <div class="topic_box">
									<table class="table table-striped table-bordered table-hover" id="table_list">



                                        <if condition="$now_goods['spec_list']">
                                            <div class="form-group">
                                                <label class="col-sm-1">{pigcms{:L_("配送计费方式")}</label>
                                                <div  class="col-sm-1">
                                                    <php>
                                                        if (2==$now_goods['freight_type']) {
                                                        echo '按重量';
                                                        } else if(3==$now_goods['freight_type']) {
                                                        echo '按体积';
                                                        } else {
                                                        echo '按件数';
                                                        }
                                                    </php>
                                                </div>
                                            </div>
                                            <tbody>
                                                <tr>
                                                    <th>{pigcms{:L_("商品条形码")}</th>
                                                    <volist name="now_goods['spec_list']" id="gs">
                                                    <th>{pigcms{$gs['name']}</th>
                                                    </volist>
                                                    <th>{pigcms{:L_("批发价")}</th>
                                                    <if condition="$config['is_switch_wholesale_mobile'] eq 1">
                                                    <eq name='now_goods.is_group' value='1'>
                                                        <th>{pigcms{:L_("拼团价")}</th>
                                                    </eq>
                                                    </if>
                                                    <th>{pigcms{:L_("库存")}</th>
                                                    <th class="goods_weight_spec" <neq name='now_goods.freight_type' value='2'>style="display: none;"</neq>>{pigcms{:L_("重量")}（{pigcms{:L_("千克")}kg）</th>
                                                    <th class="goods_volume_spec" <neq name='now_goods.freight_type' value='3'>style="display: none;"</neq>>{pigcms{:L_("体积")}（{pigcms{:L_("立方")}m³）</th>
                                                    <th>{pigcms{:L_("最低批发数")}</th>
                                                    <th>{pigcms{:L_("本次批发数（正整数）")}</th>
                                                </tr>

                                                <volist name="now_goods['list']" id="gl" key="id_index" >
                                                    <tr id="{pigcms{$gl['index']}">
                                                        <td>{pigcms{$gl['number']}</td>
                                                        <volist name="gl['spec']" id="g">
                                                        <td>{pigcms{$g['spec_val_name']}</td>
                                                        </volist>

                                                        <td>{pigcms{$gl['price']}</td>
                                                        <if condition="$config['is_switch_wholesale_mobile'] eq 1">
                                                            <eq name='now_goods.is_group' value='1'>
                                                                <td>{pigcms{$gl['group_team_price']}</td>
                                                            </eq>
                                                        </if>
                                                        <td>{pigcms{$gl['stock_num']}</td>
                                                        <td class="goods_weight_spec" <neq name='now_goods.freight_type' value='2'>style="display: none;"</neq>>
                                                            {pigcms{$gl['goods_weight']}
                                                        </td>

                                                        <td class="goods_volume_spec" <neq name='now_goods.freight_type' value='3'>style="display: none;"</neq>>
                                                            {pigcms{$gl['goods_volume']}
                                                        </td>
                                                        <td>{pigcms{$gl['min_num']}</td>
                                                        <td style="width: 30%;"><input type="text" class="txt buy_num_tip" data-is_pec="true"  data-min_num="{pigcms{$gl.min_num}" data-min_unit="{pigcms{$now_goods.min_unit}" data-stock_num="{pigcms{$now_goods.stock_num}" name="buy_nums[{pigcms{$gl['index']}]" value="{pigcms{$gl['buy_num']}" style="width:80px;"><span class="form_tips" style="color: red; display: none; height: 20px;">{pigcms{:L_("最多不得超过")}{pigcms{$gl.stock_num}{pigcms{:L_("库存")}</span></td>
                                                    </tr>
                                                </volist>
                                            </tbody>
                                        </if>
									</table>
								</div>
								
								<if condition="empty($now_goods['spec_list'])">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("本次批发数")}</label>
									<input class="col-sm-1 buy_num_tip" data-min_num="{pigcms{$now_goods.min_num}" data-min_unit="{pigcms{$now_goods.min_unit}" data-stock_num="{pigcms{$now_goods.stock_num}"  name="buy_num" type="text" value="{pigcms{$now_goods.buy_num}"/><span class="form_tips" style="color: red; display: none;">{pigcms{:L_("最多不能超过")}{pigcms{$now_goods.stock_num}{pigcms{:L_("库存")}</span>
									<span class="form_tips">{pigcms{:L_("必填。(正整数,非整数的强制转换成整数。")}) 样品采购数量为{pigcms{$now_goods.min_num}，批量批发请填写{pigcms{$now_goods.min_unit}的整数倍</span>
								</div>
								</if>

								<script>
									function buy_num_keyup(val,obj,stock_num, min_unit, min_num){
									    console.log('min_num', min_num);
										if(parseInt(val) > parseInt(stock_num)){
											$(obj).val(stock_num);
                                            $(obj).next("span").html('最多不能超过' + stock_num + '库存');
											$(obj).next("span").css("display", "");
										} else if (parseInt(val) < parseInt(min_num)) {
                                            $(obj).val(min_num);
                                            $(obj).next("span").html('最少购买' + min_num);
                                            $(obj).next("span").css("display", "");
                                        }else{
                                            $(obj).next("span").html('最多不能超过' + stock_num + '库存');
                                            $(obj).next("span").css("display", "none");
										}
									}
								</script>

                                <div class="form-group msg_express_fee" <if condition="$is_pick">style="display:none;"</if>>
                                    <label class="col-sm-1">{pigcms{:L_("配送费")}</label>
                                    <label class="col-sm-1 red" id="total_express_fee">{pigcms{$now_goods['total_express_fee']|floatval} ({pigcms{$config['Currency_txt']})</label>
                                </div>

								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("应收商品总额")}</label>
                                    <label class="col-sm-1 red" id="totalMoney">{pigcms{$now_goods['totalMoney']|floatval}({pigcms{$config['Currency_txt']})</label>
                                    <if condition="$now_goods['wholesale_discount'] gt 0">
                                        <label class="col-sm-6" style="display: -webkit-box;">
                                            <div style="background-color: #F7511D;color: white;border-radius: 10px;padding: 1px 15px;">
                                                {pigcms{:L_("下单后，还可享受")}<span id="wholesale_discount">{pigcms{$now_goods['wholesale_discount']|floatval}</span>{pigcms{:L_("折优惠，以")}<span id="discount_totalMoney">{pigcms{$now_goods['discount_totalMoney']|floatval}</span>{pigcms{$config['Currency_txt']}{pigcms{:L_("购买")}
                                            </div>
                                        </label>
                                    </if>
								</div>
                                <if condition="$config['is_switch_wholesale_mobile'] neq 1">
                                    <eq name="now_goods.is_group" value='1'>
                                        <div class="form-group">
                                            <label class="col-sm-1">{pigcms{:L_("拼团应收总额")}</label>
                                            <label class="col-sm-1 red" id="group_totalMoney">{pigcms{$now_goods['group_totalMoney']|floatval} ({pigcms{$config['Currency_txt']})</label>
                                            <label class="col-sm-6" style="display: -webkit-box;">
                                                <div style="background-color: #F7511D;color: white;border-radius: 10px;padding: 1px 15px;">
                                                    {pigcms{:L_("拼团不享受优惠")}<gt name="distance_num" value='0'>{pigcms{:L_("，还差")}<b>{pigcms{$now_goods.distance_num}</b>{pigcms{:L_("个商家成团")}</gt>
                                                </div>
                                                <div style="background-color: #F7511D;color: white;border-radius: 10px;padding: 1px 15px;" class="time_box">
                                                </div>
                                            </label>
                                        </div>
                                    </eq>
                                </if>

								<div class="form-group no_pick_address" <if condition="$is_pick">style="display:none;"</if>>
									<label class="col-sm-1">{pigcms{:L_("收货人")}</label>
									<input class="col-sm-1" name="username" type="text" value="{pigcms{$now_goods['username']}"/>
									<span class="form_tips">{pigcms{:L_("必填")}</span>
								</div>
								<div class="form-group no_pick_address" <if condition="$is_pick">style="display:none;"</if>>
									<label class="col-sm-1">{pigcms{:L_("联系电话")}</label>
									<input class="col-sm-1" name="userphone" type="text" value="{pigcms{$now_goods.userphone}"/>
									<span class="form_tips">{pigcms{:L_("必填")}</span>
								</div>
                                <div class="form-group no_pick_address" <if condition="$is_pick">style="display:none;"</if>>
                                    <label class="col-sm-1"><label>{pigcms{:L_("收货所在地")}</label></label>
                                    <fieldset id="choose_cityarea" province_id="{pigcms{$now_goods.province_id}" city_id="{pigcms{$now_goods.city_id}" area_id="{pigcms{$now_goods.area_id}"></fieldset>
                                </div>
								<div class="form-group no_pick_address" <if condition="$is_pick">style="display:none;"</if>>
									<label class="col-sm-1">{pigcms{:L_("收货详细地址")}</label>
									<input class="col-sm-3" name="address" type="text" value="{pigcms{$now_goods.address}"/>
									<span class="form_tips">{pigcms{:L_("必填(填写的完整地址")})</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("备注说明")}</label>
									<textarea class="col-sm-3" rows="5" cols="10" name="desc">{pigcms{$now_goods.desc}</textarea>
								</div>
							</div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>{pigcms{:L_("直接购买")}
									</button>
                                    <if condition="$config['is_switch_wholesale_mobile'] eq 1">
                                        <button class="btn btn-info" type="button" id="addCart">
                                            <i class="ace-icon fa fa-check bigger-110"></i>{pigcms{:L_("加入购物车")}
                                        </button>
                                        <else/>
                                        <eq name="now_goods.is_group" value='1'>
                                            <gt name='team_id' value='0'>
                                                <button class="btn btn-info group_btn" type="button">
                                                    <i class="ace-icon fa fa-check bigger-110"></i>{pigcms{:L_("参加拼团")}
                                                </button>
                                                <else/>
                                                <button class="btn btn-info group_btn" type="button">
                                                    <i class="ace-icon fa fa-check bigger-110"></i>{pigcms{:L_("去拼团")}
                                                </button>
                                            </gt>
                                            <else/>　　
                                            <button class="btn btn-info" type="button" id="addCart">
                                                <i class="ace-icon fa fa-check bigger-110"></i>{pigcms{:L_("加入购物车")}
                                            </button>
                                        </eq>
                                    </if>
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
    var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
<script type="text/javascript">
var is_group={pigcms{$now_goods.is_group|default=1};
$(document).ready(function(){
	$('input[class="txt"], input[name="buy_num"]').blur(function(){
        count_money();
	});
    $('#addCart').click(function(){
        $('.form-horizontal').attr('action', '{pigcms{:U("Market/addCart")}');
        $('.form-horizontal').submit();
        return false;
        $.post('{pigcms{:U("Market/addCart")}', $('.form-horizontal').serialize(), function(respone){
            
        }, 'json');
        console.log($('.form-horizontal').serialize());
    });
});

</script>
<script type="text/javascript">
$(function(){

    $('.buy_num_tip').blur(function () {
        var val = $(this).val();
        var min_unit = $(this).data('min_unit');
        var min_num = $(this).data('min_num');
        var stock_num = $(this).data('stock_num');
        var is_pec = $(this).data('is_pec');

        if(parseInt(val) > parseInt(stock_num)){
            $(this).val(stock_num);
            $(this).next("span").html('最多不能超过' + stock_num + '库存');
            $(this).next("span").css("display", "");
            return false;
        } else if (parseInt(val) < parseInt(min_num)) {
            $(this).val(min_num);
            $(this).next("span").html('最少购买' + min_num);
            $(this).next("span").css("display", "");
            return false;
        } else {
            $(this).next("span").html('');
            $(this).next("span").css("display", "none");
        }

        console.log('min_unit', min_unit);
        console.log('min_num', min_num);
        if (!is_pec && min_num<=0) {
            min_num = 1;
        }
        if (!min_unit || min_unit<=0) {
            min_unit = 1;
        }
        if (parseInt(val)%parseInt(min_unit) != 0 && val != min_num) {
            $(this).val(min_num);
            var msg = '批量批发请填写' + min_unit + '的整数倍';
            $(this).next("span").html(msg);
            $(this).next("span").css("display", "");
            count_money();
        } else {
            $(this).next("span").html('');
            count_money();
        }
    });

    $('.view_img').click(function(){
        var obj = $(this);
        layer.open({
            type: 1,
            title: false,
            closeBtn: 0,
            skin: 'layui-layer-nobg', //"没有背景色")}
            shadeClose: true,
            content: '<img src="' + obj.attr('src') + '" style="height:100%;width:100%"/>'
        });
    });

    $('input[name="is_pick_in_store"]').change(function () {
        var val = $(this).val();
        console.log('is_pick_in_store', val);
        if (val == 0) {
            $('.no_pick_address').hide();
            $('.pick_info').show();
            $('.msg_express_fee').hide();
        } else {
            $('.no_pick_address').show();
            $('.pick_info').hide();
            $('.msg_express_fee').show();
        }
        count_money();
    });




	var group_error_msg="{pigcms{$group_error_msg}";
	var group_id="{pigcms{$now_goods.mid}";
	$(document).on('click','.group_btn',function(){
		if(group_error_msg){
			layer.msg(group_error_msg);
			$(this).attr('disabled','disabled');
		}else{
			$('form').append($('<input>',{'type':'text','name':'group_id','value':group_id}));
			$('form').submit();
		}
	});
    setTimeout(function () {
        count_money();
        $('#choose_province').change(function () {
            count_money();
        });
    },500)
});

function count_money() {
    $.post('{pigcms{:U("Market/getTotalPrice", array("goods_id" => $now_goods["goods_id"]))}', $("form").serialize(), function(response){
        console.log('response', response);
        if (!response.error && response.totalMoney > 0) {
            var totalMoney = parseFloat(response.totalMoney);
            totalMoney.toFixed(2);
            $('#totalMoney').html(totalMoney + "({pigcms{$config['Currency_txt']})");
            if(is_group==1){
                var group_totalMoney = parseFloat(response.group_totalMoney);
                group_totalMoney.toFixed(2);
                $('#group_totalMoney').html(group_totalMoney + "({pigcms{$config['Currency_txt']})");
            }
            if (response.wholesale_discount > 0) {
                $('#wholesale_discount').html(response.wholesale_discount);
            }
            if (response.discount_totalMoney > 0) {
                var discount_totalMoney = parseFloat(response.discount_totalMoney);
                discount_totalMoney.toFixed(2);
                $('#discount_totalMoney').html(discount_totalMoney);
            }
            if (response.total_express_fee > 0) {
                var total_express_fee = parseFloat(response.total_express_fee);
                console.log('total_express_fee', total_express_fee);
                total_express_fee.toFixed(2);
                $('#total_express_fee').html(total_express_fee + "({pigcms{$config['Currency_txt']})");
            }
        } else if (response.error){
            if (response.error_msg) {
                layer.msg(response.error_msg);
            }
        }
    }, 'json');
}


var t={pigcms{$now_goods.clock_time|default=0};
if(t>0){
	clock(t);
	var k=setInterval(function(){
		if(t<=0){
			clearInterval(k);
			$('.time_box').hide();
			return false;
		}
		t--;
		clock(t);
	},1000);
}else{
	$('.time_box').hide();
}

function clock(t){
	var d=Math.floor(t/(24*3600));
	var h=Math.floor((t-d*24*3600)/3600);
	var m=Math.floor(t%3600/60);
	var s=Math.floor(t%60);
	var time_str='';
	if(d>0){
		time_str+=d+'天';
	}
	if(time_str || h>0){
		time_str+=h+'{pigcms{:L_("小时")}';
	}
	if(time_str || m>0){
		time_str+=m+'{pigcms{:L_("分钟")}';
	}
	if(time_str || s>0){
		time_str+=s+'秒';
	}
	$('.time_box').text('{pigcms{:L_("倒计时：")}'+time_str);
}
</script>
<include file="Public:footer"/>
