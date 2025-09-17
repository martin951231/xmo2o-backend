<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Appoint/index')}">{pigcms{:L_("预约管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("添加预约")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				#levelcoupon select {width:150px;margin-right: 20px;}
				#add_form *, *::after, *::before{ box-sizing:content-box}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#basicinfo">{pigcms{:L_("基本信息")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtstore">{pigcms{:L_("选择店铺")}</a>
							</li>
							<li id="dialog">
								<a data-toggle="tab" href="#txtimage">{pigcms{:L_("介绍图片")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtattr">{pigcms{:L_("规格属性")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtorder">{pigcms{:L_("状态设置")}</a>
							</li>
                            <li>
                                <a data-toggle="tab" href="#txtmarketing">{pigcms{:L_("营销设置")}</a>
                            </li>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="add_form">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("服务名称：")}</label>
									<input class="col-sm-3" maxlength="30" name="appoint_name" type="text" value="" /><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("必填。在预约页显示此名称！")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("服务简介：")}</label>
									<textarea class="col-sm-3" rows="5" name="appoint_content"></textarea><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("预约的简短介绍，建议为100字以下。")}</span>
								</div>
								<div class="form-group"></div>
                                <div class="form-group" <if condition="$config.appoint_rule neq 1">style="display:none;"</if>>
									<label class="col-sm-1">{pigcms{:L_("收取定金")}</label>
									<label style="padding-left:0px;padding-right:20px;"><input onclick="paymentShow();" type="radio" class="ace" value="1" name="payment_status"><span style="z-index: 1" class="lbl">{pigcms{:L_("开启")}</span></label>
									<label style="padding-left:0px;"><input onclick="paymentHide();" type="radio" class="ace" value="0" checked="checked" name="payment_status"><span style="z-index: 1" class="lbl">{pigcms{:L_("关闭")}</span></label>
								</div>

								<div class="form-group" id="payment_money" style="display:none;">
									<label class="col-sm-1">{pigcms{:L_("定金：")}</label>
									<input class="col-sm-1" maxlength="100" name="payment_money" type="text" value="" /><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("最多支持2位小数（超过后，系统自动截取）")}</span>
								</div>

                                <div class="form-group" id="is_refund" style="display:none;">
                                <label class="col-sm-1">{pigcms{:L_("定金退款")}</label>
                                <label style="padding-left:0px;padding-right:20px;"><input type="radio" checked="checked" class="ace" value="1" name="is_refund"><span style="z-index: 1" class="lbl">{pigcms{:L_("不可退款")}</span></label>
<!--                                <label style="padding-left:0px;"><input type="radio" class="ace" value="1" name="is_refund"><span style="z-index: 1" class="lbl">{pigcms{:L_("直接退款")}</span></label>-->
                                <label style="padding-left:0px;"><input type="radio" class="ace" value="2" name="is_refund"><span style="z-index: 1" class="lbl">{pigcms{:L_("需要申请退款")}</span></label>
                            </div>

								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("全价：")}</label>
									<select style=" width:120px" class="col-sm-1" name="is_appoint_price">
										<option value="0">{pigcms{:L_("面议")}</option>
										<option value="1">{pigcms{:L_("自定义")}</option>
									</select>

									<div style="display:none;" id="appoint_price">
										<input class="col-sm-1" maxlength="30" name="appoint_price" type="text" value="" style="margin-left:20px" />
										<if condition="$config.open_extra_price eq 1">
											{pigcms{$config['Currency_txt']} + <input class="col-sm-1" maxlength="30" name="extra_pay_price" type="text" value="" style="float:none"/>{pigcms{$config.extra_price_alias_name}
											<span class="form_tips">{pigcms{:L_("如果填写")}{pigcms{$config.extra_price_alias_name}{pigcms{:L_("字段，商品价格将变为：金额")}+{pigcms{$config.extra_price_alias_name}数</span>
										</if>
										<span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("必填。最多支持2位小数（超过后，系统自动截取）")}</span>
									</div>
								</div>

                            <div class="form-group">
                                <label class="col-sm-1">{pigcms{:L_("是否使用二维码核销")}</label>
                                <label style="padding-left:0px;padding-right:20px;"><input type="radio" checked="checked" class="ace" value="0" name="is_code_verify"><span style="z-index: 1" class="lbl">{pigcms{:L_("不使用")}</span></label>
                                <label style="padding-left:0px;"><input type="radio" class="ace" value="1" name="is_code_verify"><span style="z-index: 1" class="lbl">{pigcms{:L_("使用")}</span></label>
                            </div>

								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("耗时：")}</label>
									<input class="col-sm-1" maxlength="30" name="expend_time" type="text" value="" /><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("必填。单位：分钟")}</span>
								</div>


								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("排序：")}</label>
									<input class="col-sm-1" maxlength="30" name="sort" type="text" value="0" /><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("值越大，越往前显示。")}</span>
								</div>

								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("虚拟预约量：")}</label>
									<input class="col-sm-1" maxlength="30" name="virtual_sales" type="text" value="0" />
								</div>


								<div class="form-group"></div>
								<div class="form-group">
									<label class="col-sm-1">开始时间：</label>
									<input class="col-sm-2 Wdate" type="text" readonly style="height:30px;" onfocus="WdatePicker({minDate:'{pigcms{:date('Y年m月d日',$_SERVER['REQUEST_TIME'])}',isShowClear:false,readOnly:true,dateFmt:'yyyy年MM月dd日',startDate:'{pigcms{:date('Y-m-d',$_SERVER['REQUEST_TIME'])}',vel:'start_time'})" value="{pigcms{:date('Y年m月d日',$_SERVER['REQUEST_TIME'])}"/>
									<input name="start_time" id="start_time" type="hidden" value="{pigcms{:date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME'])}"/>
									<span class="form_tips red">&nbsp;*&nbsp;到了开始时间，会自动显示！</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">结束时间：</label>
									<input class="col-sm-2 Wdate" type="text" readonly style="height:30px;" onfocus="WdatePicker({minDate:'{pigcms{:date('Y年m月d日',$_SERVER['REQUEST_TIME'])}',isShowClear:false,readOnly:true,dateFmt:'yyyy年MM月dd日',startDate:'{pigcms{:date('Y-m-d',strtotime('+30 day'))}',vel:'end_time'})" value="{pigcms{:date('Y年m月d日 ',strtotime('+30 day'))}"/>
									<input name="end_time" id="end_time" type="hidden" value="{pigcms{:date('Y-m-d H:i:s',strtotime('+30 day'))}"/>
									<span class="form_tips red">&nbsp;*&nbsp;超过结束时间，会自动结束！</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("服务类别：")}</label>
									<select name="appoint_type" class="col-sm-2">
										<option value="0">{pigcms{:L_("到店")}</option>
										<option value="1">{pigcms{:L_("上门")}</option>
									</select>
								</div>

								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("选择分类：")}</label>
									<select id="choose_catfid" name="cat_fid" class="col-sm-1" style="margin-right:10px;">
										<option value="">{pigcms{:L_("请选择")}</option>
										<volist name="f_category_list" id="vo">
											<option value="{pigcms{$vo.cat_id}">{pigcms{$vo.cat_name}</option>
										</volist>
									</select>
									<select id="choose_catid" name="cat_id" class="col-sm-1" style="margin-right:10px;">
										<option value="">{pigcms{:L_("请选择")}</option>
										<volist name="s_category_list" id="vo">
											<option value="{pigcms{$vo.cat_id}">{pigcms{$vo.cat_name}</option>
										</volist>
									</select>
									<input type="hidden" name="cat_id" id="cat_id" value=""/>
									<span class="form_tips red" style="display:none" id="autotrophic_warn">*{pigcms{:L_("此分类为平台自营，须等待平台派单，用户无法直接浏览。到店类别请勿选择自营，无法正常派单。")}</span>
								</div>
								<div class="form-group" >
									<label class="col-sm-1">{pigcms{:L_("服务详情：")}<br/></label>
									<textarea name="appoint_pic_content" id="content" style="width:702px;"></textarea>
								</div>
								<div class="tabbable">
									<ul class="nav nav-tabs" id="myTab">
										<li class="active">
											<a data-toggle="tab" href="#shop_time_1">
												{pigcms{:L_("营业时间段")}
											</a>
										</li>
									</ul>
									<div class="tab-content" id="office_time">
										<div id="shop_time_1" class="tab-pane in active">
											<div>
												<input class="Config_shop_start_time" type="text" value="08:00" name="office_start_time[]" />&nbsp;&nbsp;至&nbsp;&nbsp;
												<input class="Config_shop_stop_time" type="text" value="20:00" name="office_stop_time[]" />
											<!-- 	<div class="errorMessage" id="Config_shop_start_time_em_" style="display:none"></div>
												<div class="errorMessage" id="Config_shop_stop_time_em_" style="display:none"></div> -->
												<span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("如果营业时间段设置为")}00:00-00:00{pigcms{:L_("，则表示24小时营业")}</span>
											</div>
										</div>
									</div>
									<a class="btn btn-success" id="add_shop_time" style="margin-top: 5px">{pigcms{:L_("添加时间段")}</a>
								</div>
									
								<div class="form-group"></div>
										<!--div class="form-group">
											<label class="col-sm-1">{pigcms{:L_("限定人数：")}</label>
											<input class="col-sm-1" maxlength="100" name="appoint_people" type="text" value="0" /><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("限制每个时间点的预约人数，0为不限制")}</span>
										</div-->
								<div class="form-group" style="margin-top: 5px;;">
									<label class="col-sm-1">{pigcms{:L_("时间间隔：")}</label>
									<input class="col-sm-1" maxlength="100" name="time_gap" type="text" value="30" /><span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("预约时间间隔，单位分钟，必须是大于0且为5的倍数")}</span>
								</div>
							</div>


							<div id="txtstore" class="tab-pane">
                            <div class="form-group">
									<label style="margin-left:20px"><label for="is_store">{pigcms{:L_("是否开启选择店铺：")}</label></label>
										<label style="margin-left:10px"><input type="radio" checked="checked" class="ace" value="1" name="is_store"><span style="z-index: 1" class="lbl">{pigcms{:L_("开启")}</span></label>
										<label style="margin-left:10px"><input type="radio" class="ace" value="0" name="is_store"><span style="z-index: 1" class="lbl">{pigcms{:L_("关闭")}</span></label>
										<span class="form_tips red">{pigcms{:L_("关闭后，订单将由商家指定门店及")}<?php echo $config['appoint_worker_name'] ? $config['appoint_worker_name'] : L_('技师'); ?>{pigcms{:L_("进行服务。")}</span>
								</div>

								<div class="form-group store_list">
									<volist name="store_list" id="vo">
										<div class="radio">
											<label>
												<input class="paycheck ace store-list" type="checkbox" name="store_id[]" value="{pigcms{$vo.store_id}" id="store{pigcms{$vo.store_id}" />
												<span class="lbl"><label for="store{pigcms{$vo.store_id}">{pigcms{$vo.name} - {pigcms{$vo.area_name}-{pigcms{$vo.adress}</label></span>
											</label>
											<label><input class="paycheck ace " name="store_sort[]" value="" type="text" style="width:350px" placeholder="{pigcms{:L_("排序值(此排序控制店铺内预约商品的前后顺序。")})" /></label>
										</div>

                                        <if condition="$vo['worker_list']">
                                        <div style=" margin:5px 0 0 60px;display:none" class="worker-list" data-store-id="{pigcms{$vo.store_id}">
                                        	<volist name="vo['worker_list']" id="val">
                                                <label style="margin-right:10px">
                                                    <input class="paycheck ace" type="checkbox" name="worker_memus[]" value="{pigcms{$vo.store_id},{pigcms{$val.merchant_worker_id}" id="worker{pigcms{$val.merchant_worker_id}"/>
                                                    <span class="lbl"><label for="worker{pigcms{$val.merchant_worker_id}">&nbsp;&nbsp;{pigcms{$val.name}</label></span>
                                                </label>
                                            </volist>
										</div>
                                        </if>
									</volist>
								</div>
							</div>

							<div id="txtimage" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("上传图片")}</label>
									<a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectImage">{pigcms{:L_("上传图片")}</a>
									<span class="form_tips red">&nbsp;*&nbsp;{pigcms{:L_("第一张将作为列表页图片展示！最多上传5个图片！")}{pigcms{:L_("图片建议为：")}400*400</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul"></ul>
									</div>
								</div>
							</div>

							<div id="txtattr" class="tab-pane">
								<label style="margin-left:20px"><label for="is_store">{pigcms{:L_("规格类型：")}</label></label>
<!--								<select name="product_type" style="margin-left:10px">-->
<!--									<option value="0">{pigcms{:L_("开启时间段")}</option>-->
<!--									<option value="1">{pigcms{:L_("关闭时间段")}</option>-->
<!--								</select>-->
<!--								<span class="form_tips red">{pigcms{:L_("开启，表示预约商品与店员的时间段照常显示在前台，反之，则用户前台隐藏选择时间段功能")}	</span>-->
								<div style="border:1px solid #c5d0dc;padding-left:22px;margin-bottom:10px;" id="cue_html_tips">
									<table class="table table-striped">
										<tbody>
											<tr>
												<th scope="col">{pigcms{:L_("规格序号")}</th>
												<th scope="col">{pigcms{:L_("规格名称")}</th>
												<th scope="col">{pigcms{:L_("规格定金")}</th>
												<th scope="col">{pigcms{:L_("规格全价")}</th>
												<th scope="col">{pigcms{:L_("规格描述")}</th>
                                                <th scope="col">{pigcms{:L_("平均用时（分钟）")}</th>
											</tr>
											<for start="1" end="41">
												<tr class="parent" data-index="{pigcms{$i}">
													<td><i class="ace-icon fa"></i>{pigcms{:L_("序号")}{pigcms{$i}</td>
													<td><input name="custom_name[]" type="text" class="span2 title" value=""></td>
													<td>{pigcms{$config['Currency_symbol']}<input name="custom_payment_price[]" type="text" class="span2 keyword" value=""></td>
													<td>{pigcms{$config['Currency_symbol']}<input name="custom_price[]" type="text" class="span2 keyword" value=""></td>
													<td><input name="custom_content[]" type="text" class="span3 url" value=""></td>
                                                    <td><input name="use_time[]" type="text" class="span3 url" value=""></td>
												</tr>
											</for>
										</tbody>
									</table>
								</div>
							</div>



							<div id="txtorder" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("预约状态：")}</label>
									<select name="appoint_status" class="col-sm-1">
										<option value="0">{pigcms{:L_("开启")}</option>
										<option value="1">{pigcms{:L_("关闭")}</option>
									</select>
									<span class="form_tips red">{pigcms{:L_("为了方便用户能查找到以前的订单，预约无法删除！")}</span>
								</div>
							</div>

                        <div id="txtmarketing" class="tab-pane">
                            <div class="form-group">
                                <label class="col-sm-1">{pigcms{:L_("关联预约商品推荐：")}</label>
                                <label>
                                    <a class="btn btn-success choose_good_btn" onclick="select_goods()">{pigcms{:L_("选择商品")}</a>
                                    <span class="form_tips red">{pigcms{:L_("设置后，该商品详情页【店铺推荐】会展示选中的商品")}</span>
                                    <table style="width:800px;margin-top: 10px;" class="table table-bordered"
                                           id='goods_spec'>
                                        <thead>
                                        <tr>
                                            <th>{pigcms{:L_("服务ID")}</th>
                                            <th>{pigcms{:L_("服务名称")}</th>
                                            <th>{pigcms{:L_("价格")}</th>
                                            <th>{pigcms{:L_("预约状态")}</th>
                                            <th>{pigcms{:L_("操作")}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <volist name="recommend_goods" id="goods">
                                            <tr>
                                                <input type='hidden' name="relation_goods_id[]"
                                                       value="{pigcms{$goods.appoint_id}"/>
                                                <td>{pigcms{$goods.appoint_id}</td>
                                                <td>{pigcms{$goods.appoint_name}</td>
                                                <td>
                                                    {pigcms{:L_("定金")}：
                                                    <if condition="$goods.payment_status eq 0">--
                                                        <else/>
                                                        {pigcms{$config['Currency_symbol']}{pigcms{$goods.payment_money}
                                                    </if>
                                                    <br/>
                                                    {pigcms{:L_("全价")}：
                                                    <if condition="$goods.is_appoint_price eq 0">面议
                                                        <else/>
                                                        {pigcms{$config['Currency_symbol']}{pigcms{$goods.appoint_price}
                                                    </if>
                                                </td>
                                                <td>{pigcms{$buy.price}</td>
                                                <td>
                                                    <if condition="$goods['appoint_status'] eq 0"><span
                                                                style="color:green">{pigcms{:L_("开启")}</span>
                                                        <else/>
                                                        <span style="color:red">{pigcms{:L_("关闭")}</span>
                                                    </if>
                                                </td>
                                                <td>
                                                    <a type="button" onClick="delThis(this)">{pigcms{:L_("移除")}</a>
                                                </td>
                                            </tr>
                                        </volist>
                                </label>
                                </tbody>
                                </table>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1">{pigcms{:L_("购买赠券：")}</label>
                                <label>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-success"
                                       onclick="select_mer_coupon()">{pigcms{:L_("选择优惠券")}</a>
                                    <span class="form_tips red">{pigcms{:L_("设置购买该商品可赠送的优惠券")}</span>
                                    <input type='hidden' id="buy_give_coupon_id" name="buy_give_coupon_id"
                                           value="{pigcms{$appoint_info.buy_give_coupon_id}"/>
                                    <table style="width:800px;margin-top: 10px;" class="table table-bordered"
                                           id='coupon_spec'>
                                        <thead>
                                        <tr>
                                            <th>{pigcms{:L_("优惠券ID")}</th>
                                            <th>{pigcms{:L_("名称")}</th>
                                            <th>{pigcms{:L_("状态")}</th>
                                            <th>{pigcms{:L_("操作")}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <volist name="give_coupon" id="coupon">
                                            <tr>
                                                <td>{pigcms{$coupon.coupon_id}</td>
                                                <td>{pigcms{$coupon.name}</td>
                                                <td>
                                                    <if condition="$buy['status'] eq 1"><span
                                                                style="color:green">{pigcms{:L_("开启")}</span>
                                                        <else/>
                                                        <span style="color:red">{pigcms{:L_("不可用")}</span>
                                                    </if>
                                                </td>
                                                <td>
                                                    <a type="button"
                                                       onClick="delCouponThis(this)">{pigcms{:L_("移除")}</a>
                                                </td>
                                            </tr>
                                        </volist>
                                        </tbody>
                                    </table>
                                </label>
                            </div>
                        </div>

							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit" id="save_btn">
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
<link rel="stylesheet" href="{pigcms{$static_path}css/appoint_add.css">
<style>
    .reduce_recharge {
        background: url("{pigcms{$static_path}css/img/reduce_nc.png") no-repeat;
        width: 32px;
        height: 32px;
        border: none;
        position: absolute;
        margin-left: 5px;
    }
    .add_tips_info {
        margin-left: 40px;
    }
</style>
<script type="text/javascript">
var ajax_worker_list_url = "{pigcms{:U('Appoint/ajax_worker_list')}";
</script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script src="{pigcms{$static_path}js/appoint_add.js?t=1"></script>
<script>
$(".tab-content").on("focus",".Config_shop_start_time",function(){
　　　$(this).timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
});
$(".tab-content").on("focus",".Config_shop_stop_time",function(){
　　　$(this).timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'10','minute':'52','second':'25'}));
});

// "添加时间段")}
var index = 1;
$('#add_shop_time').click(function(){
	index = index + 1;
	var html_div = '';
	var end_time = $("input[name='office_stop_time[]']:last").val();
	var arr = end_time.split(':');
	console.log(arr);

	if (parseInt(arr[0]) >= 23 && parseInt(arr[1]) >= 59 ) {
		alert('{pigcms{:L_("不能添加时间段")}');
		return false;
	}

	html_div = '<div id="shop_time_1" style="margin-top: 5px;" class="tab-pane in active"><div><input class="Config_shop_start_time" type="text" value="'+end_time+'" name="office_start_time[]"/>&nbsp;&nbsp;至&nbsp;&nbsp;	<input class="Config_shop_stop_time" type="text" value="" name="office_stop_time[]" /><button type="button" class="reduce_recharge"></button><span class="form_tips red add_tips_info">&nbsp;*&nbsp;{pigcms{:L_("如果营业时间段设置为")}00:00-00:00{pigcms{:L_("，则表示24小时营业")}</span></div></div>';
	$('#office_time').append(html_div);
    // "删除")}
    $(".reduce_recharge").click(function() {
        if($('#office_time').children().length<=1){
            $(this).val('');
        }else{
            $(this).css('visibility','hidden');
            $(this).parents('#shop_time_1').remove();
        }
    });

});


var diyVideo = "{pigcms{:U('Article/diyVideo')}";
KindEditor.ready(function(K) {
	var content_editor = K.create("#content",{
		width:'702px',
		height:'260px',
		langType : "{pigcms{$config.system_lang_alias}",
		resizeType : 1,
		allowPreviewEmoticons:false,
		allowImageUpload : true,
		filterMode: true,
		autoHeightMode : true,
		afterCreate : function() {
			this.loadPlugin('autoheight');
		},
		items : [
			'fullscreen', '|', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
			'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
			'insertunorderedlist', '|', 'emoticons', 'image', 'link', 'table','diyVideo'
		],
		emoticonsPath : './static/emoticons/',
		uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=appoint/content",
		cssPath : "{pigcms{$static_path}css/group_editor.css"
	});

	$('input[name="is_store"]').each(function(i){
		$(this).click(function(){
			if(i == 0){
				$('.store_list').show();
			}else{
				$('.store_list').hide();
			}
		});
	});

	var editor = K.editor({
		allowFileManager : true
	});
	K('#J_selectImage').click(function(){
		if($('.upload_pic_li').size() >= 5){
			alert('{pigcms{:L_("最多上传5个图片！")}');
			return false;
		}
		editor.uploadJson = "{pigcms{:U('Appoint/ajax_upload_pic')}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				imageUrl : K('#course_pic').val(),
				clickFn : function(url, title, width, height, border, align) {
					$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="pic[]" value="'+title+'"/><br/><a href="#" onclick="deleteImg(\''+title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
					editor.hideDialog();
				}
			});
			if(liIndex == 'active'){
				$('.ke-dialog-default.ke-dialog').css('top',((document.body.offsetHeight - $('.ke-dialog-default.ke-dialog').height())+'px'))
			}
		});
	});

	$('#choose_catfid').change(function(){
		$.getJSON("{pigcms{:U('Appoint/ajax_get_category')}",{cat_fid:$(this).val()},function(result){
			var html = '';
			html += '<option value="">{pigcms{:L_("请选择")}</option>';
			if(result.error == 0){
				for ( var i=0; i<result.cat_list.length; i++){
                    html += '<option value="'+ result.cat_list[i].cat_id +'" data-is-autotrophic="'+result.cat_list[i].is_autotrophic+'">' + result.cat_list[i].cat_name + '</option>';
                }
                $('#choose_catid').html(html);
            } else {
                $("#choose_catid").html(html);
            }
		});
	});

	$('#choose_catid').change(function(){
		var cat_id = $(this).val();
		var is_autotrophic = $(this).find(':selected').data('is-autotrophic');
		$('#cat_id').attr('value', cat_id);
		if(is_autotrophic==1){
			$('#autotrophic_warn').show();
		}else{
			$('#autotrophic_warn').hide();
		}

	});

	$('#add_form').submit(function(){
		content_editor.sync();
		$('#save_btn').prop('disabled',true);
		$.post("{pigcms{:U('Appoint/add')}",$('#add_form').serialize(),function(result){
			if(result.status == 1){
				alert(result.info);
				window.location.href = "{pigcms{:U('Appoint/index')}";
			}else{
				alert(result.info);
			}
			$('#save_btn').prop('disabled',false);
		})
		return false;
	});

	$('#editor_plan_btn').click(function(){
		var dialog = K.dialog({
				width : 200,
				title : '{pigcms{:L_("输入欲插入表格行数")}',
				body : '<div style="margin:10px;"><input id="edit_plan_input" style="width:100%;"/></div>',
				closeBtn : {
						name : '{pigcms{:L_("关闭")}',
						click : function(e) {
							dialog.remove();
						}
				},
				yesBtn : {
						name : '{pigcms{:L_("确定")}',
						click : function(e){
							var value = $('#edit_plan_input').val();
							if(!/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d+)?$/.test(value)){
								alert('{pigcms{:L_("请输入数字！")}');
								return false;
							}
							value = parseInt(value);
							var html = '<table class="deal-menu">';
							html += '<tr><th class="name" colspan="2">{pigcms{:L_("套餐内容")}</th><th class="price">{pigcms{:L_("单价")}</th><th class="amount">{pigcms{:L_("数量")}/{pigcms{:L_("规格")}</th><th class="subtotal">{pigcms{:L_("小计")}</th></tr>';
							for(var i=0;i<value;i++){
								html += '<tr><td class="name" colspan="2">{pigcms{:L_("内容")}'+(i+1)+'</td><td class="price">¥</td><td class="amount">1份</td><td class="subtotal">¥</td></tr>';
							}
							html += '</table>';
							html += '<p class="deal-menu-summary">{pigcms{:L_("价值")}: <span class="inline-block worth">¥</span>{pigcms{$config.group_alias_name}{pigcms{:L_("价：")} <span class="inline-block worth price">¥</span></p><br/><br/>{pigcms{:L_("介绍")}...';
							content_editor.appendHtml(html);

							dialog.remove();
						}
				},
				noBtn : {
						name : '{pigcms{:L_("取消")}',
						click : function(e) {
							dialog.remove();
						}
				}
		});
	});
});

var _selected_goods = '{pigcms{$appoint_list["product_recommendation"]}'; //已选择的商品
var _selected_coupon = '{pigcms{$appoint_list["buy_give_coupon_id"]}'; //已选择的优惠券
var offset = parseInt(getScrollTop()) - 150;

//选择商品
function select_goods() {
    layer.open({
        title: '{pigcms{:L_("选择商品")}',
        type: 2,
        area: ['700px', '730px'],
        fixed: false,
        offset: offset + 'px',
        maxmin: true,
        content: '{pigcms{:U("select_goods")}' + '&selected=' + _selected_goods,
        btn: ['{pigcms{:L_("确定")}', '{pigcms{:L_("取消")}'],
        yes: function (index, layero) {
            var frameId = $(layero).find("iframe").attr('id');
            var selected = $.trim($(window.frames[frameId].document).find('[name="selected"]').val());
            _selected_goods = selected;
            $("#goods_spec tbody").empty();
            $.post("{pigcms{:U('ajaxGetGoods')}", {selected: selected}, function (data) {
                if (data.length > 0) {
                    for (var i = 0; i < data.length; i++) {
                        var paymentStatus = data[i].payment_status == 0 ? '--' : '{pigcms{$config["Currency_symbol"]}' + data[i].payment_money;
                        var appointPrice = data[i].is_appoint_price ? '面议' : '{pigcms{$config["Currency_symbol"]}' + data[i].appoint_price;
                        var appointStatus = data[i].appoint_status == 0 ? '<span style="color:green">{pigcms{:L_("开启")}</span>' : '<span style="color:red">{pigcms{:L_("关闭")}</span>';

                        $("#goods_spec tbody").append('<tr>' +
                            '<input type="hidden" name="relation_goods_id[]" value="' + data[i].appoint_id + '" />' +
                            '<td>' + data[i].appoint_id + '</td>' +
                            '<td>' + data[i].appoint_name + '</td>' +
                            '<td>{pigcms{:L_("定金")}：' + paymentStatus + '<br/>{pigcms{:L_("全价")}：' + appointPrice + '</td>' +
                            '<td>' + appointStatus + '</td>' +
                            '<td>' +
                            '<a type="button" onClick="delThis(this)">{pigcms{:L_("移除")}</a>' +
                            '</td>' +
                            '</tr>');
                    }
                }

            }, 'json');
            layer.close(index);
        },
        btn2: function (index) {
            layer.close(index);
        }
    });
}

function delThis(t) {
    layer.open({
        fixed: false,
        offset: offset + 'px',
        title: '{pigcms{:L_("提示")}',
        content: '{pigcms{:L_("你确定要移除此商品吗？")}',
        icon: 3,
        btn: ['{pigcms{:L_("确定")}', '{pigcms{:L_("取消")}'],
        yes: function (index, layero) {
            layer.close(index);
            var goods_id = $(t).parents("tr").find('[name="relation_goods_id[]"]').val();
            var _selected_goods_arr = _selected_goods.split(',');
            var _selected_goods_arr_new = [];
            for (var i = 0; i < _selected_goods_arr.length; i++) {
                if (_selected_goods_arr[i] != goods_id) {
                    _selected_goods_arr_new.push(_selected_goods_arr[i]);
                }
            }
            _selected_goods = _selected_goods_arr_new.join(",");
            $(t).parents("tr").remove();
        }
    });
}

//选择优惠券
function select_mer_coupon() {
    var offset = parseInt(getScrollTop()) - 150;
    layer.open({
        title: '{pigcms{:L_("选择优惠券")}',
        type: 2,
        area: ['700px', '730px'],
        fixed: false,
        offset: offset + 'px',
        maxmin: true,
        content: '{pigcms{:U("select_mer_coupon")}' + '&selected=' + _selected_coupon,
        btn: ['{pigcms{:L_("确定")}', '{pigcms{:L_("取消")}'],
        yes: function (index, layero) {
            var frameId = $(layero).find("iframe").attr('id');
            var selected = $.trim($(window.frames[frameId].document).find('[name="coupon_id"]').val());
            _selected_coupon = selected;
            $("#buy_give_coupon_id").val(_selected_coupon);
            $("#coupon_spec tbody").empty();
            $.post("{pigcms{:U('ajaxGetCoupon')}", {selected: selected}, function (data) {
                if (data.length > 0) {
                    for (var i = 0; i < data.length; i++) {
                        var appointStatus = data[i].status == 1 ? '<span style="color:green">{pigcms{:L_("开启")}</span>' : '<span style="color:red">{pigcms{:L_("不可使用")}</span>';

                        $("#coupon_spec tbody").append('<tr>' +
                            '<td>' + data[i].coupon_id + '</td>' +
                            '<td>' + data[i].name + '</td>' +
                            '<td>' + appointStatus + '</td>' +
                            '<td>' +
                            '<a type="button" onClick="delCouponThis(this)">{pigcms{:L_("移除")}</a>' +
                            '</td>' +
                            '</tr>');
                    }
                }

            }, 'json');
            layer.close(index);
        },
        btn2: function (index) {
            layer.close(index);
        }
    });
}

//移除优惠券
function delCouponThis(t) {
    layer.open({
        fixed: false,
        offset: offset + 'px',
        title: '{pigcms{:L_("提示")}',
        content: '{pigcms{:L_("你确定要移除此优惠券吗？")}',
        icon: 3,
        btn: ['{pigcms{:L_("确定")}', '{pigcms{:L_("取消")}'],
        offset: offset + 'px',
        yes: function (index, layero) {
            layer.close(index);
            $('#buy_give_coupon_id').val('');
            _selected_coupon = '';
            $(t).parents("tr").remove();
        }
    });
}
</script>
<include file="Public:footer"/>