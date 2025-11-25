<include file="Public:header"/>
<div class="main-content">
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-credit-card"></i>
                <a href="{pigcms{:U('Card_new/index')}">{pigcms{:L_("会员卡")}</a>
            </li>
        </ul>
    </div>
	<div class="page-content form-horizontal ">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
					<form class="form" method="post" action="" target="_top" enctype="multipart/form-data">
						<label for="tab1" class="select_tab select" style="cursor:pointer;">{pigcms{:L_("基本信息")}</label>
						<if condition="$config['coupon_wx_sync']">
							<label for="tab2" class="select_tab" style="margin-left:-4px;cursor:pointer;">{pigcms{:L_("微信会员卡")}</label>
						</if>
						<div class="tab-content card_new" id="tab1">
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("启用会员卡：")}</label></label>
								<label class="radiolabel first"><span><label><input name="status" value="1" type="radio" <if condition="$card.status eq 1">checked="checked"</if> /></label>&nbsp;<span>{pigcms{:L_("是")}</span></span></label>
								<label class="radiolabel"><span><label><input name="status" value="0" type="radio" <if condition="$card.status eq 0">checked="checked"</if>/></label>&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</span></label>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("会员卡背景图：")}</label></label>
								<select name="bg" id="sys_card_bg" class="pt" style="width:210px;">
									<?php
									for ($i = 4; $i <= 20; $i++) {
										$i = $i < 10 ? '0' . $i : $i;
										$str = './static/images/card/card_bg' . $i . '.png';
										if ($card['bg'] == $str) {
											echo $str = '<option value="' . $str . '" selected="selected" >' . $i . '</option>';
										} else {
											echo $str = '<option value="' . $str . '">' . $i . '</option>';

										}
									}
									?>
								</select>
								<span class="tip">({pigcms{:L_("选择系统内置背景图")})</span>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("上传背景图：")}</label></label>
								<input type="text" name="diybg" id="bgs" class="px" value="{pigcms{$card.diybg}" style="width:210px;"/>
								<a class="fileupload-exists btn btn-ccc" style="margin-left:20px;font-size:12px;" onclick="upyunPicUpload('bgs',1000,600,'card',L_('上传图片'),L_('关闭'))">{pigcms{:L_("上传图片")}</a>
								<span class="tip">({pigcms{:L_("同时选择系统背景图和上传背景图，优先使用上传背景图")})</span>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("卡号文字颜色：")}</label></label>
								<input type="text" name="numbercolor" id="numbercolor" value="{pigcms{$card.numbercolor}" class="px color" style="width:80px;background-image:none;background-color:rgb(0,0,0);color:rgb(255,255,255);" onblur="document.getElementById('number').style.color=document.getElementById('numbercolor').value;"/>
								<span class="tip"></span>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("会员卡折扣数：")}</label></label>
								<input type="text" name="discount" id="discount" class="px" value="{pigcms{$card.discount|floatval}" style="width:210px;"/><span class="tip">({pigcms{:L_("请填写0到10的数字,0相当于不打折,比如95折 填写9.5即可")})</span>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("用户自助领卡：")}</label></label>
								<label class="radiolabel first"><span><label><input name="self_get" value="1" type="radio" <if condition="$card.self_get eq 1">checked="checked"</if> /></label>&nbsp;<span>{pigcms{:L_("是")}</span></span></label>
								<label class="radiolabel"><span><label><input name="self_get" value="0" type="radio" <if condition="$card.self_get eq 0">checked="checked"</if>/></label>&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</span></label>
								<span class="tip">({pigcms{:L_("用户扫店铺二维码与访问会员卡页面时都可自动领卡")})</span>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("支持实体卡：")}</label></label>
								<label class="radiolabel first"><span><label><input name="is_physical_card" value="1" type="radio" <if condition="$card.is_physical_card eq 1">checked="checked"</if> /></label>&nbsp;<span>{pigcms{:L_("是")}</span></span></label>
								<label class="radiolabel"><span><label><input name="is_physical_card" value="0" type="radio" <if condition="$card.is_physical_card eq 0">checked="checked"</if>/></label>&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</span></label>
								<span class="tip">({pigcms{:L_("用户会员卡页面出现绑定实体卡的选项")})</span>
							</div>
							<div class="form-group hide">
								<label class="tiplabel"><label>{pigcms{:L_("自动领卡：")}</label></label>
								<label class="radiolabel first"><span><label><input name="auto_get" value="1" type="radio" <if condition="$card.auto_get eq 1">checked="checked"</if> /></label>&nbsp;<span>{pigcms{:L_("是")}</span></span></label>
								<label class="radiolabel"><span><label><input name="auto_get" value="0" type="radio" <if condition="$card.auto_get eq 0">checked="checked"</if>/></label>&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</span></label>
								<span class="tip">({pigcms{:L_("用户访问会员卡页面时自动领卡，且开启该功能后用户可以先领取商家优惠券再领取商家会员卡")})</span>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("消费自动领卡：")}</label></label>
								<label class="radiolabel first"><span><label><input name="auto_get_buy" value="1" type="radio" <if condition="$card.auto_get_buy eq 1">checked="checked"</if> /></label>&nbsp;<span>{pigcms{:L_("是")}</span></span></label>
								<label class="radiolabel"><span><label><input name="auto_get_buy" value="0" type="radio" <if condition="$card.auto_get_buy eq 0">checked="checked"</if>/></label>&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</span></label>
								<span class="tip">({pigcms{:L_("购买商家商品后自动领卡，不指定商品则代表消费任何商家商品后均可自动领卡")})</span>
							</div>
                            <div class="form-group">
                                <label class="tiplabel"><label>{pigcms{:L_("抵扣1元所需积分量：")}</label></label>
                                <input type="text" name="money_need_score" id="discount" class="px" value="{pigcms{$card.money_need_score|floatval}" style="width:210px;"/><span class="tip">({pigcms{:L_("请填写大于0的数字")})</span>
                            </div>
							<p >
								<span style="font-size:23px;font-weight:bold;">{pigcms{:L_("关联商品")}</span>
								<span id='tips' style='color:red;'>设置指定商品后，用户购买指定商品且验证消费后，即可自动获得商家会员卡</span>
							</p>
							<table style="width:800px;" class="table table-bordered" id='goods_spec'>
								<thead>
									<tr>
										<th>{pigcms{:L_("商品编号")}</th>
										<th>{pigcms{:L_("所属业务")}</th>
										<th>{pigcms{:L_("商品名称")}</th>
										<th>{pigcms{:L_("现价")}</th>
										<th>{pigcms{:L_("操作")}</th>
									</tr>
								</thead>
								<tbody>
									<volist name="card['buy_goods_list']" id="buy">
									<tr>
										<input type='hidden' name="relation_goods_id[]" value="{pigcms{$buy.goods_id}" />
										<input type='hidden' name="relation_type[]" value="{pigcms{$buy.type}" />
										<td>{pigcms{$buy.goods_id}</td>
										<td>{pigcms{$buy.model}</td>
										<td>{pigcms{$buy.name}</td>
										<td>{pigcms{$buy.price}</td>
										<td>
											<button type="button" onClick="delThis(this)">{pigcms{:L_("移除")}</button>
										</td>
									</tr>							
									</volist>
								</tbody>
							</table>
							<a class="btn btn-success choose_good_btn" onclick="select_goods()">{pigcms{:L_("选择商品")}</a>
							
							
							
							<div class="headings gengduoxian">{pigcms{:L_("余额")} / {pigcms{:L_("充值")}<span class="note-inf">带<a style="color:red;">*</a>{pigcms{:L_("为必填项")}</span></div>
							<if condition="$config.merchant_card_recharge_offline eq 1">
								<div class="form-group">
									<label class="tiplabel"><label>{pigcms{:L_("会员卡初始金额：")}</label></label>
									<input type="text" name="begin_money" id="begin_money" class="px" value="{pigcms{$card.begin_money|floatval=###}" style="width:210px;"/><span class="tip">({pigcms{:L_("领会员卡时自动向该卡赠送金额")})</span>
								</div>
							</if>
							
							<if condition="is_array($card['recharge_rule'])" >
							<volist name="card.recharge_rule" id="vv">
							
							<div class="form-group plus_re" data-id="{pigcms{$i}">
								<label class="tiplabel"><label>{pigcms{:L_("充值返现规则：")}</label></label>
								{pigcms{:L_("充值")}&nbsp;&nbsp;<input type="text" name="recharge_count[]"  class="px" value="{pigcms{$vv.count}" style="width:60px;"/>&nbsp;&nbsp;{pigcms{$config['Currency_txt']}, {pigcms{:L_("返")}&nbsp;&nbsp;<input type="text" name="recharge_back_money[]"  class="px" value="{pigcms{$vv.back_money}" style="width:60px;"/>&nbsp;&nbsp;{pigcms{$config['Currency_txt']}, {pigcms{:L_("返")}&nbsp;&nbsp;<input type="text" name="recharge_back_score[]"  class="px" value="{pigcms{$vv.back_score}" style="width:60px;"/>&nbsp;&nbsp;{pigcms{$config['score_name']}
								<button type="button" class="plus_recharge" <php>if(count($card['recharge_rule'])<10 && $i==count($card['recharge_rule'])){</php> style="visibility:visible;"<php>}else{</php>style="visibility:hidden;"<php>}</php>></button>
								<button type="button" class="reduce_recharge" <php>if(count($card['recharge_rule'])<10 && $i==count($card['recharge_rule'])){</php> style="visibility:hidden;"<php>}else{</php>style="visibility:visible;"<php>}</php>></button>
								<span class="tip" <php>if($i!=1){</php> style="visibility:hidden;"<php>}else{</php>style="visibility:visible;"<php>}</php>>({pigcms{:L_("用户每在线充值一笔达到金额即赠送的金额与积分，最多可添加10种充值类型")})</span>
							</div>
							</volist>
							<else />
							<div class="form-group plus_re" data-id="1">
								<label class="tiplabel"><label>{pigcms{:L_("充值返现规则：")}</label></label>
								{pigcms{:L_("充值")}&nbsp;&nbsp;<input type="text" name="recharge_count[]"  class="px" value="" style="width:60px;"/>&nbsp;&nbsp;{pigcms{$config['Currency_txt']}, {pigcms{:L_("返")}&nbsp;&nbsp;<input type="text" name="recharge_back_money[]"  class="px" value="" style="width:60px;"/>&nbsp;&nbsp;{pigcms{$config['Currency_txt']}, {pigcms{:L_("返")}&nbsp;&nbsp;<input type="text" name="recharge_back_score[]"  class="px" value="" style="width:60px;"/>&nbsp;&nbsp;{pigcms{$config['score_name']}
								<button type="button" class="plus_recharge"></button>
								<button type="button" class="reduce_recharge"></button>
								<span class="tip">({pigcms{:L_("用户每在线充值一笔达到金额即赠送的金额与积分，最多可添加10种充值类型")})</span>
							</div>
							</if>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("充值金额建议：")}</label></label>
								<input type="text" name="recharge_suggest" id="recharge_suggest" class="px" value="{pigcms{$card.recharge_suggest}" style="width:210px;"/><span class="tip">({pigcms{:L_("用户可以在充值页面快速点击按钮充值该建议金额。英文逗号隔开，如")} 10, 20, 30 )</span>
							</div>
                            <div class="form-group">
                                <label class="tiplabel"><label>{pigcms{:L_("用户是否只按建议金额充值")}：</label></label>
                                <label class="radiolabel first"><span><label><input class="recharge_suggest_select" name="recharge_suggest_select" value="1" type="radio" <if condition="$card.recharge_suggest_select gt 0">checked="checked"</if> /></label>&nbsp;<span>{pigcms{:L_("是")}</span></span></label>
                                <label class="radiolabel"><span><label><input class="recharge_suggest_select" name="recharge_suggest_select" value="0" type="radio" <if condition="$card.recharge_suggest_select eq 0">checked="checked"</if>/></label>&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</span></label>
                            </div>
							
							
							
							<div class="headings gengduoxian">{pigcms{$config['score_name']}<span class="note-inf">{pigcms{:L_("带")}<a style="color:red;">*</a>{pigcms{:L_("为必填项")}</span></div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("会员卡初始")}{pigcms{$config['score_name']}：</label></label>
								<input type="text" name="begin_score" id="begin_score" class="px" value="{pigcms{$card.begin_score}" style="width:210px;"/><span class="tip">({pigcms{:L_("领会员卡时自动向该卡赠送")}{pigcms{$config['score_name']})</span>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("消费获得")}{pigcms{$config['score_name']}：</label></label>
								<label class="radiolabel first"><span><label><input class="support_score_select" name="support_score_select" value="1" type="radio" <if condition="$card.support_score gt 0">checked="checked"</if> /></label>&nbsp;<span>{pigcms{:L_("是")}</span></span></label>
								<label class="radiolabel"><span><label><input class="support_score_select" name="support_score_select" value="0" type="radio" <if condition="$card.support_score eq 0">checked="checked"</if>/></label>&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</span></label>
								<span class="tip">({pigcms{:L_("用户购买商品之后是否能获取一定的")}{pigcms{$config['score_name']})</span>
							</div>
							<div class="form-group support_score">
								<label class="tiplabel"><label>{pigcms{:L_("消费一元获得")}{pigcms{$config['score_name']}：</label></label>
								<input type="text" name="support_score" id="support_score" class="px" value="{pigcms{$card.support_score}" style="width:210px;"/><span class="tip">({pigcms{:L_("用户每消费一元获得的")}{pigcms{$config['score_name']}{pigcms{:L_("数，大于1的整数")})</span>
							</div>
							
							
							
							<div class="headings gengduoxian">{pigcms{:L_("优惠券")}<span class="note-inf">{pigcms{:L_("带")}<a style="color:red;">*</a>{pigcms{:L_("为必填项")}</span></div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("自动领优惠券：")}</label></label>
								<label class="radiolabel first"><span><label><input name="auto_get_coupon" value="1" type="radio" <if condition="$card.auto_get_coupon eq 1">checked="checked"</if> /></label>&nbsp;<span>{pigcms{:L_("是")}</span></span></label>
								<label class="radiolabel"><span><label><input name="auto_get_coupon" value="0" type="radio" <if condition="$card.auto_get_coupon eq 0">checked="checked"</if>/></label>&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</span></label>
								<span class="tip">({pigcms{:L_("仅限第一次领卡时触发")})</span>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("微信购买自动派发优惠券开关：")}</label></label>
								<label class="radiolabel first"><span><label><input name="weixin_send" value="1" type="radio" <if condition="$card.weixin_send eq 1">checked="checked"</if> /></label>&nbsp;<span>{pigcms{:L_("开启")}</span></span></label>
								<label class="radiolabel"><span><label><input name="weixin_send" value="0" type="radio" <if condition="$card.weixin_send eq 0">checked="checked"</if>/></label>&nbsp;<span>{pigcms{:L_("关闭")}</span>&nbsp;</span></label>
								<span class="tip">({pigcms{:L_("仅支持微信支付后自动派发优惠券")})</span>
							</div>
							
							
							
							<div class="headings gengduoxian">{pigcms{:L_("使用说明")}<span class="note-inf">{pigcms{:L_("带")}<a style="color:red;">*</a>{pigcms{:L_("为必填项")}</span></div>
							<div class="form-group">
								<label class="tiplabel" style="vertical-align:top;"><label><font color="red">*</font> {pigcms{:L_("充值说明：")}</label></label>
								<textarea name="recharge_des" id="recharge_des" class="px" style="width:410px;height:120px;">{pigcms{$card.recharge_des}</textarea>
							</div>
							<div class="form-group">
								<label class="tiplabel" style="vertical-align:top;"><label><font color="red">*</font>{pigcms{$config['score_name']}{pigcms{:L_("说明：")}</label></label>
								<textarea name="score_des" id="score_des" class="px" style="width:410px;height:120px;">{pigcms{$card.score_des}</textarea>
							</div>
							<div class="form-group">
								<label class="tiplabel" style="vertical-align:top;"><label><font color="red">*</font>{pigcms{:L_("会员卡说明：")}</label></label>
								<textarea name="info" id="info" class="px" style="width:410px;height:120px;">{pigcms{$card.info}</textarea>
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
						
						<div class="tab-content card_new" id="tab2">
							<div class="headings gengduoxian" style="display:none">{pigcms{:L_("操作")}<span class="note-inf"></div>
							<div class="form-group" style="display:none">
								<label class="tiplabel"><label>{pigcms{:L_("同步微信会员卡：")}</label></label>
								
								<a href="javascript:void(0)" onclick="sysc()" class="fileupload-exists btn btn-ccc">{pigcms{:L_("同步")}</a>
								<span class="tip">({pigcms{:L_("编辑下面的数据并保存后再同步")})</span>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("是否同步会员卡")}(<font color="red">{pigcms{:L_("同步请选择是")}</font>)</label></label>
								<label class="radiolabel first"><span><label><input name="sysc_weixin" value="1" type="radio" <if condition="$card.sysc_weixin eq 1">checked="checked"</if> /></label>&nbsp;<span>{pigcms{:L_("是")}</span></span></label>
								<label class="radiolabel"><span><label><input name="sysc_weixin" value="0" type="radio" <if condition="$card.sysc_weixin eq 0">checked="checked"</if>/></label>&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</span></label>
							</div>
						
							<div class="headings gengduoxian">{pigcms{:L_("基本信息")}<span class="note-inf"></span>
								<a href="javascript:void(0);" id="show_card_dome">{pigcms{:L_("查看示例")}</a>
							</div>
							
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("会员卡名称：")}</label></label>
								
								<input type="text" name="wx_title"  class="px" value="{pigcms{$card.wx_param.title}" style="width:210px;"/><span class="tip">(9{pigcms{:L_("个汉字以内")})</span>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("会员卡提醒：")}</label></label>
								
								<input type="text" name="wx_notice"  class="px" value="{pigcms{$card.wx_param.notice}" style="width:210px;"/><span class="tip">(16{pigcms{:L_("个汉字以内")})</span>
							</div>
							
							<div class="form-group ">
								<label class="tiplabel"><label>{pigcms{:L_("会员卡颜色：")}</label></label>
								<select name="wx_color" id="color"class="px" <if condition="$card['wx_param']['color']">style="background-color:{pigcms{$card['wx_param']['color']}"</if>>
									<volist name="color_list" id="vo">
										<option value="{pigcms{$key}" style="background-color:{pigcms{$vo};margin:5px auto;color:{pigcms{$vo};" <php>if($card['wx_param']['color']==$vo){</php>selected="selected"<php>}</php>>{pigcms{$vo}</option>
									</volist>
								</select>
								<span class="tip">({pigcms{:L_("会员卡字体颜色，不包含卡号")})</span>
							</div>
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("中间按钮标题：")}</label></label>
								
								<input type="text" name="wx_center_title"  class="px" value="{pigcms{$card.wx_param.center_title}" style="width:210px;"/><span class="tip">(6{pigcms{:L_("个汉字以内")})</span>
								<label class="tiplabel"><label>{pigcms{:L_("中间按钮副标题：")}</label></label>
								
								<input type="text" name="wx_center_sub_title"  class="px" value="{pigcms{$card.wx_param.center_sub_title}" style="width:210px;"/><span class="tip">(8{pigcms{:L_("个汉字以内")})</span>
								<label class="tiplabel"><label>{pigcms{:L_("中间按钮链接：")}</label></label>
								
								<input type="text" name="wx_center_url"  id="url_center-title"  class="px" value="{pigcms{$card.wx_param.center_url}" style="width:210px;"/>
								
								<a href="" id="addLink" class="fileupload-exists btn btn-ccc" onclick="addLinks('url_center-title',0)" data-toggle="modal">{pigcms{:L_("从功能库选择")}</a>
								<span class="tip">(128{pigcms{:L_("个字符以内")})</span>
							</div>
							
							
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("自定义类目1(可填)：")}</label></label>
								
								<input type="text" name="wx_custom_url_name"  class="px" value="{pigcms{$card.wx_param.custom_url_name}" style="width:210px;"/><span class="tip">(5{pigcms{:L_("个汉字以内")})</span>
								<label class="tiplabel"><label>{pigcms{:L_("提示语：")}</label></label>
								
								<input type="text" name="wx_custom_url_sub_title"  class="px" value="{pigcms{$card.wx_param.custom_url_sub_title}" style="width:210px;"/>
								
								<span class="tip">(6{pigcms{:L_("个汉字以内")})</span>
								<label class="tiplabel"><label>{pigcms{:L_("自定义跳转链接：")}</label></label>
								
								<input type="text" name="wx_custom_url" id="cuntom_url" class="px" value="{pigcms{$card.wx_param.custom_url}" style="width:210px;"/>
								<a href="" id="addLink" class="fileupload-exists btn btn-ccc" onclick="addLinks('cuntom_url',0)" data-toggle="modal">{pigcms{:L_("从功能库选择")}</a>
								<span class="tip">(128{pigcms{:L_("个字符以内")})</span>
							</div>
							<div class="form-group ">
								<label class="tiplabel"><label>{pigcms{:L_("自定义类目2(可填)：")}</label></label>
								<input type="text" name="wx_custom_cell1_name"  class="px" value="{pigcms{$card.wx_param.custom_cell1_name }" style="width:210px;"/><span class="tip">(5{pigcms{:L_("个汉字以内")})</span>
								<label class="tiplabel"><label>{pigcms{:L_("提示语：")}</label></label>
								
								<input type="text" name="wx_custom_cell1_tips"  class="px" value="{pigcms{$card.wx_param.custom_cell1_tips}" style="width:210px;"/><span class="tip">(6{pigcms{:L_("个汉字以内")})</span>
								<label class="tiplabel"><label>{pigcms{:L_("自定义跳转链接：")}</label></label>
								
								<input type="text" name="wx_custom_cell1_url" id="custom_cell1_url" class="px" value="{pigcms{$card.wx_param.custom_cell1_url}" style="width:210px;"/>
								<a href="" id="addLink" class="fileupload-exists btn btn-ccc" onclick="addLinks('custom_cell1_url',0)" data-toggle="modal">{pigcms{:L_("从功能库选择")}</a>
								<span class="tip">(128{pigcms{:L_("个字符以内")})</span>
							</div>
							
							<div class="form-group ">
								<label class="tiplabel"><label>{pigcms{:L_("自定义类目3(可填)：")}</label></label>
								<input type="text" name="wx_custom_cell2_name"  class="px" value="{pigcms{$card.wx_param.custom_cell2_name }" style="width:210px;"/><span class="tip">(5{pigcms{:L_("个汉字以内")})</span>
								<label class="tiplabel"><label>{pigcms{:L_("提示语：")}</label></label>
								
								<input type="text" name="wx_custom_cell2_tips"  class="px" value="{pigcms{$card.wx_param.custom_cell2_tips}" style="width:210px;"/><span class="tip">(6{pigcms{:L_("个汉字以内")})</span>
								<label class="tiplabel"><label>{pigcms{:L_("自定义跳转链接：")}</label></label>
								
								<input type="text" name="wx_custom_cell2_url"  id="custom_cell2_url" class="px" value="{pigcms{$card.wx_param.custom_cell2_url}" style="width:210px;"/>
								<a href="" id="addLink" class="fileupload-exists btn btn-ccc" onclick="addLinks('custom_cell2_url',0)" data-toggle="modal">{pigcms{:L_("从功能库选择")}</a>
								<span class="tip">(128{pigcms{:L_("个字符以内")})</span>
							</div>
							
							
							
							<div class="form-group">
								<label class="tiplabel"><label>{pigcms{:L_("自定义类目4(可填)：")}</label></label>
								<input type="text" name="wx_promotion_url_name"  class="px" value="{pigcms{$card.wx_param.promotion_url_name }" style="width:210px;"/><span class="tip">(5{pigcms{:L_("个汉字以内")})</span>
								<label class="tiplabel"><label>{pigcms{:L_("提示语：")}</label></label>
								
								<input type="text" name="wx_promotion_url_sub_title"  class="px" value="{pigcms{$card.wx_param.promotion_url_sub_title}" style="width:210px;"/><span class="tip">(6{pigcms{:L_("个汉字以内")})</span>
								<label class="tiplabel"><label>{pigcms{:L_("自定义跳转链接：")}</label></label>
								
								<input type="text" name="wx_promotion_url" id="promotion_url" class="px" value="{pigcms{$card.wx_param.promotion_url}" style="width:210px;"/>
								<a href="" id="addLink" class="fileupload-exists btn btn-ccc" onclick="addLinks('promotion_url',0)" data-toggle="modal">{pigcms{:L_("从功能库选择")}</a>
									<span class="tip">(128{pigcms{:L_("个字符以内")})</span>
							</div>
							
							<div class="form-group">
								<label class="tiplabel" style="vertical-align:top;"><label>{pigcms{:L_("特权说明：")}</label></label>
								<textarea name="wx_prerogative" id="prerogative" class="px" style="width:410px;height:120px;">{pigcms{$card.wx_param.prerogative}</textarea>
							</div>
							
							<div class="form-group wx_coupon">
		
								<label class="tiplabel"><label>{pigcms{:L_("商家服务类型：")}</label></label>
								<input type="checkbox"  name="wx_business_service[]" class="px" value="BIZ_SERVICE_DELIVER" <if condition="in_array('BIZ_SERVICE_DELIVER',$card['wx_param']['business_service'])">checked="checked"</if>>{pigcms{:L_("外卖服务")}
								<input type="checkbox"  name="wx_business_service[]" class="px" value="BIZ_SERVICE_FREE_PARK" <if condition="in_array('BIZ_SERVICE_FREE_PARK',$card['wx_param']['business_service'])">checked="checked"</if>>{pigcms{:L_("停车位")}
								<input type="checkbox"  name="wx_business_service[]" class="px" value="BIZ_SERVICE_WITH_PET" <if condition="in_array('BIZ_SERVICE_WITH_PET',$card['wx_param']['business_service'])">checked="checked"</if>>{pigcms{:L_("可带宠物")}
								<input type="checkbox"  name="wx_business_service[]" class="px" value="BIZ_SERVICE_FREE_WIFI" <if condition="in_array('BIZ_SERVICE_FREE_WIFI',$card['wx_param']['business_service'])">checked="checked"</if>>{pigcms{:L_("免费")}wifi
								<span class="tip">({pigcms{:L_("已经设置就不能取消，可以更改")})</span>
							</div>
							
							<div class="headings gengduoxian">{pigcms{:L_("卡券图文（图片大小1MB）：")}</div>
							
							<div class="form-group other">	
								<if condition="!empty($card['wx_param']['text_image_list'])">
									<table cellpadding="0" cellspacing="0" class="px" width="60%" >
									<volist name="card.wx_param.text_image_list" id="vo">
										<tr class="plus textIamge" >
											<td width="100"><label class="tiplabel">{pigcms{:L_("图文消息")}<label>{pigcms{$i}</label></label></td>
											
											<td>
												
												<table style="width:100%;">
												<tr class="textIamge">
													<td width="66" >{pigcms{:L_("图片：")}</td>
													<td width="380"><input type="text"   name="wx_image_url[]"  class="px input-image" value="{pigcms{$vo.image_url}"   readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="fileupload-exists btn btn-ccc J_selectImage" >{pigcms{:L_("上传图片")}</a></td>
													<td width="66" >{pigcms{:L_("描述：")}</td>
													<td width="180"><textarea  class="px" style="width:410px;height:50px;margin-right:5px;" name="wx_text[]" >{pigcms{$vo.text}</textarea></td>
													<td rowspan="2" class="delete">
														<a href="javascript:void(0)" class="fileupload-exists btn btn-ccc" onclick="del(this)">{pigcms{:L_("删除")}</a>
													</td>
												<tr/>
												
											</table>
											</td>
										</tr>
											</volist>
										<tr class="textIamge">
											<td></td>
											<td><a href="javascript:void(0)" onclick="plus()" class="fileupload-exists btn btn-ccc">{pigcms{:L_("增加一行")}</a></td>
										</tr>
									</table>
									
								<else />
								<table cellpadding="0" cellspacing="0" class="px" width="60%" >
								
									<tr class="plus textIamge" >
										<td width="100"><label class="tiplabel">{pigcms{:L_("图文消息")}<label>1</label></label></td>
										<td>
											<table style="width:100%;">
												<tr class="textIamge">
													<td width="66" >{pigcms{:L_("图片：")}</td>
													<td width="380"><input type="text"   name="wx_image_url[]"  class="px input-image" value=""   readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="fileupload-exists btn btn-ccc J_selectImage" >{pigcms{:L_("上传图片")}</a></td>
													<td width="66" >{pigcms{:L_("描述：")}</td>
													<td width="180"><textarea  class="px" style="width:410px;height:50px;margin-right:5px;" name="wx_text[]" ></textarea></td>
													<td rowspan="2" class="delete">
														<a href="javascript:void(0)" class="fileupload-exists btn btn-ccc" onclick="del(this)">{pigcms{:L_("删除")}</a>
													</td>
												<tr/>
												
											</table>
										</td>
									</tr>
									<tr class="textIamge">
										<td></td>
										<td><a href="javascript:void(0)" onclick="plus()" class="fileupload-exists btn btn-ccc">{pigcms{:L_("增加一行")}</a></td>
									</tr>
									
									
								</table>
								</if>
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
	<div class="vipcard_box">
		<div class="vipcard">
			<img id="cardbg" style="width: 380px;height: 146px" src="<if condition="$card.diybg neq ''">{pigcms{:replace_file_domain($card['diybg'])}<elseif condition="$card.bg neq ''" />{pigcms{:replace_file_domain($card['bg'])}<else/>./static/images/card/new_merchant_card.png</if>"/>
			<strong class="pdo verify" id="number" style="color:{pigcms{$card.numbercolor};right:142px"><span>6537 1998</span></strong>
		</div>
		<span class="red">{pigcms{:L_("背景图宽718px高240px，图片类型png，大小不超过1MB 。")}</span>
	</div>
	<style>
		ul, ol { padding: 0;}
		.banners { position: relative; overflow: auto; text-align: center;}
		.banners li { list-style: none; }
		.banners ul li { float: left; }
		#b04 { width: 320px;    overflow: hidden;    left: 40%;display:none;z-index:1000}
		#b04 .dots { position: absolute; left: 0; right: 0; bottom: 20px;}
		#b04 .dots li 
		{ 
			display: inline-block; 
			width: 10px; 
			height: 10px; 
			margin: 0 4px; 
			text-indent: -999em; 
			border: 2px solid #000; 
			border-radius: 6px; 
			cursor: pointer; 
			opacity: .4; 
			-webkit-transition: background .5s, opacity .5s; 
			-moz-transition: background .5s, opacity .5s; 
			transition: background .5s, opacity .5s;
		}

		#b04 .dots li.active {
			background: #000;
			opacity: 1;
		}
		#b04 .arrow { position: absolute; top: 200px;}
		#b04 #al { left: 15px;}
		#b04 #ar { right: 15px;}
		#pwd_bg{
			background-color: #000;
	position: fixed;
	z-index: 999;
	left: 0;
	top: 0;
	
	width: 100%;
	height: 100%;
	opacity: 0.3;
	filter: alpha(opacity=30);
	-moz-opacity: 0.3;
			
		}

</style>	
	<div id="pwd_bg" style="display:none">

	</div>
	<div class="banners vipcard_box"  id="b04" >
		<ul style="margin:0;">
			<li><img src="{pigcms{$static_path}images/card1.jpg" alt="" style="width:320px;480px;" ></li>
			<li><img src="{pigcms{$static_path}images/card2.jpg" alt="" style="width:320px;480px;" ></li>
			<li><img src="{pigcms{$static_path}images/card3.png" alt="" style="width:320px;480px;" ></li>
			<li><img src="{pigcms{$static_path}images/card4.jpg" alt="" style="width:320px;480px;" ></li>
		</ul>

		<a href="javascript:void(0);" class="unslider-arrow04 prev"><img class="arrow" id="al" src="{pigcms{$static_path}images/arrowl.png" alt="prev"  style="width:20px;35px;"></a>

		<a href="javascript:void(0);" class="unslider-arrow04 next"><img class="arrow" id="ar" src="{pigcms{$static_path}images/arrowr.png" alt="next" style="width:20px;35px;"></a>

	</div>
</div>

<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script src="./static/js/cart/jscolor.js" type="text/javascript"></script>
<link rel="stylesheet" href="./static/kindeditor/themes/default/default.css"/>
<link rel="stylesheet" href="./static/kindeditor/plugins/code/prettify.css"/>
<link rel="stylesheet" href="./static/validate/dist/css/bootstrapValidator.css"/>
<script type="text/javascript" src="./static/validate/vendor/jquery/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="./static/validate/vendor/bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="./static/validate/dist/js/bootstrapValidator.js"></script>
<style>
	.select_tab{
		width:100px;
		height:36px;
		color: #555;
		border: 1px solid #c5d0dc;
		font-size:16px;
		z-index:9;
		line-height: 36px;
    text-align: center;
		position: relative;
	}
	label .select_tab{
		display: inline-block;
		margin: 0 0 -1px;
		padding: 15px 25px;
		font-weight: 600;
		text-align: center;
		color: #bbb;
		border: 1px solid transparent;
	}
	
	.select{
		border-top: 1px solid orange;
		border-bottom: 1px solid #fff;
	}
	.card_new{
		margin-top:-6px;
	}
	.other label,table{
		color:#a0a0a0;
	}
</style>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script src="{pigcms{$static_path}js/unslider.min.js?2222"></script>

<script type="text/javascript" src="./static/js/upyun.js"></script>
<script type="text/javascript">
	function editorImageCallback(){
		setTimeout(function(){
			$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
		},300);
	}
	
	KindEditor.ready(function(K){
			var site_url = "{pigcms{$config.site_url}";
			var editor = K.editor({
				allowFileManager : true
			});
			$('.J_selectImage').click(function(){
				var upload_file_btn = $(this);
				editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic_wx')}";
				editor.loadPlugin('image', function(){
					editor.plugin.imageDialog({
						showRemote : false,
						clickFn : function(url, title, width, height, border, align) {
							upload_file_btn.siblings('.input-image').val(url);
							editor.hideDialog();
						}
					});
				});
			});

		});
</script>
<script type="text/javascript">
	var _selected_goods = '{pigcms{$card["buy_goods_list_str"]}';
	//选择商品
	function select_goods(){
		layer.open({
			type: 2,
			area: ['700px', '730px'],
			fixed: false,
			maxmin: true,
			content: '{pigcms{:U("select_goods")}'+'&selected='+_selected_goods,
			btn: ['确定', '取消'],
			yes: function(index, layero){
				var frameId = $(layero).find("iframe").attr('id');
				var selected = $.trim($(window.frames[frameId].document).find('[name="selected"]').val());
				_selected_goods = selected;
				$("#goods_spec tbody").empty();
				$.post("{pigcms{:U('ajaxGetGoods')}",{selected:selected},function(data){
					if(data.length > 0){
						for(var i = 0; i< data.length; i++){
							$("#goods_spec tbody").append('<tr>'+
										'<input type="hidden" name="relation_goods_id[]" value="'+data[i].goods_id+'" />'+
										'<input type="hidden" name="relation_type[]" value="'+data[i].type+'" />'+
										'<td>'+data[i].goods_id+'</td>'+
										'<td>'+data[i].model+'</td>'+
										'<td>'+data[i].name+'</td>'+
										'<td>'+data[i].price+'</td>'+
										'<td>'+
												'<button type="button" onClick="delThis(this)">{pigcms{:L_("移除")}</button>'+
										'</td>'+
									'</tr>');
						}
					}
				},'json');
			    layer.close(index);
			},
			btn2: function(index){
				layer.close(index);
			}
		});
	}

	function delThis(t){
		if(confirm('{pigcms{:L_("你确定要移除此商品吗？")}')){
			var goods_id = $(t).parents("tr").find('[name="relation_goods_id[]"]').val();
			var type = $(t).parents("tr").find('[name="relation_type[]"]').val();
			var _selected_goods_arr = _selected_goods.split(',');
			var _selected_goods_arr_new = [];
			for(var i = 0;i< _selected_goods_arr.length;i++){
				if(_selected_goods_arr[i] != type+'-'+goods_id){
					_selected_goods_arr_new.push(_selected_goods_arr[i]);
				}
			}
			_selected_goods = _selected_goods_arr_new.join(",");
			$(t).parents("tr").remove();
		}
	}
    $(document).ready(function() {
		
		$('.form').bootstrapValidator({
	//        live: 'disabled',
			message: 'This value is not valid',
		  
			fields: {
				recharge_des: {
					validators: {
						notEmpty: {
							message: '{pigcms{:L_("充值说明不能为空")}'
						}
					}
				},
				score_des: {
					validators: {
						notEmpty: {
							message: '{pigcms{:L_("积分说明不能为空")}'
						}
					}
				},
				info: {
					validators: {
						notEmpty: {
							message: '{pigcms{:L_("会员卡说明不能为空")}'
						}
					}
				},
				 discount: {
					validators: {
						callback: {
							message: '{pigcms{:L_("错误的折扣")}',
							callback: function(value, validator) {
							   return (value>=0 && value<=10)
								
							}
						}
					}
				}
			}
		});
		
		$('#sys_card_bg').change(function(){
			// if($.trim($('#bgs').val()) == ''){
				$('#cardbg').attr('src', $(this).val());
				$('#bgs').val('')
			// }
		});
		
		 var unslider04 = $('#b04').unslider({
			dots: true
		}),
			data04 = unslider04.data('unslider');
			$('.unslider-arrow04').click(function() {

			var fn = this.className.split(' ')[1];

				data04[fn]();

			});
		
		$('#show_card_dome').click(function(){
			$('#b04').show();
			$('#pwd_bg').show();
		})
		
		$('#pwd_bg').click(function(){
			$('#b04').hide();
			$('#pwd_bg').hide();
		})
		
		if($('.support_score_select:checked').val()==0){
            $('.support_score').css('display','none');
		}else{
            $('.support_score').css('display','block');
		}

		$('#support_recharge').change(function(event) {
			if($('#support_recharge').val()==0){
                $('.support_recharge').css('display','none');

			}else{
                $('.support_recharge').css('display','block');
			}
		});

		$('.support_score_select').change(function(event) {
            if($('.support_score_select:checked').val()==0){
                $('.support_score').css('display','none');
			}else{
                $('.support_score').css('display','block');
			}
		});
	   $('#tab2').hide();
		$('.select_tab').click(function(){
			
			$('.select_tab').removeClass('select');
			$(this).addClass('select');
			var id_for = $(this).attr('for');
			if(id_for=='tab1'){
				// $('#sysc_weixin').val(0);
				$('#tab2').hide();
				$('.vipcard_box').show();
				
			}else{
				$('#tab1').hide();
				$('.vipcard_box').hide();
				// $('#sysc_weixin').val(1);
			}
		
			$('#'+id_for).show();
			$('#b04').hide();
			$('#pwd_bg').hide();
			
		});
		
		//$('select[name="wx_color"]').css('background-color','#63b359');	
			$('select[name="wx_color"]').change(function(event) {
				$('#wx_color').css('background-color',$('select[name="wx_color"]').find('option:selected').html());
				$(this).css('background-color',$('select[name="wx_color"]').find('option:selected').html());
			});		
		if($('.plus').length<2){
			$('.delete').children().hide();
		}
		if($('.plus_re').length<2){
			
			$('.plus_re:first').find('.reduce_recharge').css('visibility','hidden');
		}
		$('.plus_recharge').click(function(){
			var item = $('.plus_re:last');
			var newitem = $(item).clone(true);
			var No = parseInt(item.data('id'))+1;
			
			$(this).css('visibility','hidden');
			$(this).siblings('.reduce_recharge').css('visibility','visible');
			// $(this).removeClass('plus_recharge')
			if($('.plus_re').length>9){
				layer.alert('{pigcms{:L_("不能超过10条配置")}');
			}else{
				newitem.find('.tip').css('visibility','hidden');
				
				$(item).after(newitem);
				newitem.find('input').attr('value','');
				newitem.data('id',No)
				console.log(No)
				if(No==10){
					 $('.plus_re:last').find('.plus_recharge').css('visibility','hidden');
					 $('.plus_re:last').find('.reduce_recharge').css('visibility','visible');
				}
			}
			
		})
		$(".reduce_recharge").click(function() {
			if($('.plus_re').length<=1){
				$('.plus_re input').val('');
				$('.plus_re:first').find('.tip').css('visibility','visible');
				
			}else{
				
				$(this).parents('.plus_re').remove();
				$('.plus_re:last').find('.reduce_recharge').css('visibility','hidden');
				$('.plus_re:last').find('.plus_recharge').css('visibility','visible');
				
				$.each($('.plus_re'), function(index, val) {
					var No =index+1;
					if(No==1){
						$(val).find('.tip').css('visibility','visible')
					}
					$(val).data('id',No);
				
				});
			}
		})
    });
	function upload_func(){
		$('#cardbg').attr('src',$('#bgs').val());
	}
	
	function plus(){
			var item = $('.plus:last');
			var newitem = $(item).clone(true);
			var No = parseInt(item.find(".tiplabel label").html())+1;
			$('.delete').children().show();
			if(No>4){
				layer.alert('{pigcms{:L_("不能超过4条信息")}');
			}else{
				$(item).after(newitem);
				newitem.find('input').attr('value','');
				newitem.find('textarea').attr('value','');
				newitem.find("#addLink").attr('onclick',"addLink('url"+No+"',0)");
				newitem.find(".tiplabel label").html(No);
				newitem.find('input[name="url[]"]').attr('id','url'+No);
				newitem.find('.delete').children().show();
			}
		}
		function del(obj){
			if($('.plus').length<=1){
				$('input[name="wx_image_url[]"]').val('');
				$('textarea[name="wx_text[]"]').val('');
				$('.delete').children().hide();
			}else{
				if($('.plus').length==2){
					$('.delete').children().hide();
				}
				$(obj).parents('.plus').remove();
				$.each($('.plus'), function(index, val) {
					var No =index+1;
					$(val).find(".tiplabel label").html(No);
					$(val).find('input[name="url[]"]').attr('id','url'+No);
					$(val).find("#addLink").attr('onclick',"addLink('url"+No+"',0)");
				});
			}
		}
		
		function sysc(){
			$.ajax({
				url: '{pigcms{:U('sysc_wxcard')}',
				type: 'POST',
				dataType: 'json',
				data: {param1: 'value1'},
				beforeSend:function(){
					var index = layer.load(1, {
					  shade: [0.3,'#000'] //0.1"透明度的白色背景")}
					});
				},
				success:function(data){
					layer.closeAll()
					layer.alert(data.info)
				}
			});
		}
		
		function addLinks(domid,iskeyword){
			art.dialog.data('domid', domid);
			art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'{pigcms{:L_("插入链接或关键词',width:800,height:500,yesText:'关闭")}',background: '#000',opacity: 0.45});
		}
</script>
<link rel="stylesheet" href="{pigcms{$static_path}css/card_new.css"/>

<include file="Public:footer"/>