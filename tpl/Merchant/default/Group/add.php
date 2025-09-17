<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Group/index')}">{pigcms{$config.group_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("添加商品")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				#levelcoupon select {width:150px;margin-right: 20px;}
                .table-list table{
                    border: #DFDFDF solid 1px;
                    margin-left: 10px;
                }
                tr {
                    display: table-row;
                    vertical-align: inherit;
                    border-color: inherit;
                }
                th {
                    text-align: center;
                    min-width: 50px;
                    height: 3rem;
                    background: #eef3f7;
                }
                .rt-border {
                    border: #DFDFDF solid 1px;
                }
                .td-class {
                    text-align: center;
                    min-width: 50px;
                    height: 6rem;
                }
                .table-list .tips_img {
                    margin-left: 5px;
                    vertical-align: top;
                    margin-top: -3px;
                    margin-right: 0;
                }
                .specifications-input {
                    min-width: 50px;
                    max-width: 100px;
                }
                .plus_recharge {
                    background: url("{pigcms{$static_path}css/img/plus_nc.png") no-repeat;
                    width: 32px;
                    height: 32px;
                    border: none;
                    position: absolute;
                }
                .reduce_recharge {
                    background: url("{pigcms{$static_path}css/img/reduce_nc.png") no-repeat;
                    width: 32px;
                    height: 32px;
                    border: none;
                    position: absolute;
                    margin-left: -16px;
                    margin-top: -16px;
                }
                .add_specifications {
                    line-height: 32px;
                    margin-left: 33px;
                    cursor: pointer;
                }
                .add_content {
                    line-height: 32px;
                    margin-left: 33px;
                    cursor: pointer;
                }
                .add_specifications_div{
                    text-align: center;
                    margin-top: 50px;
                }
                .reduce_specifications_div{
                    text-align: center;
                }
                .reduce_recharge_content {
                    background: url(./tpl/Merchant/default/static/css/img/reduce_nc.png) no-repeat;
                    width: 32px;
                    height: 32px;
                    border: none;
                    position: absolute;
                    margin-left: -16px;
                    margin-top: -146px;
                    left:  calc(8.333% + 750px);
                }
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
							<li>
								<a data-toggle="tab" href="#txtintro">{pigcms{$config.group_alias_name}{pigcms{:L_("详情")}</a>
							</li>
							 <php>if(1==$config['group_store_discount_more']){</php>
							<li>
								<a data-toggle="tab" href="#promotion">{pigcms{:L_("商品折扣")}</a>
							</li>
							 <php>}</php>
                            <if condition="!empty($levelarr)">
							<li>
								<a data-toggle="tab" href="#levelcoupon">{pigcms{:L_("会员优惠")}</a>
							</li>
							</if>

                            <li>
                                <a data-toggle="tab" href="#specifications">{pigcms{:L_("规格")}</a>
                            </li>
						
							<li>
								<a data-toggle="tab" href="#relpackages">{pigcms{:L_("套餐设置")}</a>
							</li>
							
							<li>
								<a data-toggle="tab" href="#express_template">{pigcms{:L_("运费模板")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtnum">{pigcms{:L_("数量设置")}</a>
							</li>
							<li style="display:none;">
								<a data-toggle="tab" href="#spread">{pigcms{:L_("推广佣金")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#pickinstore">{pigcms{:L_("到店自提设置")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#opengroupnum">{pigcms{:L_("拼团人数设置")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#trade">{pigcms{:L_("插件绑定")}</a>
							</li>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="add_form">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("商品标题：")}</label>
									<input class="col-sm-3" maxlength="100" name="name" type="text" value="" /><span class="form_tips">{pigcms{:L_("商品的介绍标题，100字段以内,首页和列表页将显示。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("商品名称：")}</label>
									<input class="col-sm-3" maxlength="30" name="s_name" type="text" value="" /><span class="form_tips">{pigcms{:L_("必填。在订单页显示此名称！")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("商品简介：")}</label>
									<textarea class="col-sm-3" rows="5" name="intro"></textarea><span class="form_tips">{pigcms{:L_("商品的简短介绍，建议为100字以下。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("上传图片")}</label>
									<a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectImage">{pigcms{:L_("上传图片")}</a>
									<span class="form_tips">{pigcms{:L_("第一张将作为列表页图片展示！最多上传5张图片！")}<php>if(!empty($config['group_pic_width'])){$group_pic_width=explode(',',$config['group_pic_width']);echo '{pigcms{:L_("图片宽度建议为：")}'.$group_pic_width[0].'px，';}</php><php>if(!empty($config['group_pic_height'])){$group_pic_height=explode(',',$config['group_pic_height']);echo '{pigcms{:L_("高度建议为：")}'.$group_pic_height[0].'px';}</php></span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("选择图片")}</label>
									<a href="#modal-table" class="btn btn-sm btn-success" onclick="selectImg('upload_pic_ul','group')">{pigcms{:L_("选择图片")}</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul"></ul>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("关键词：")}</label>
									<input class="col-sm-3" maxlength="30" name="keywords" type="text" value="" id="keywords"/><span class="form_tips">{pigcms{:L_("选填。")}<font color="red">{pigcms{:L_("（用空格分隔不同的关键词，最多5个）")}</font>{pigcms{:L_("，用户在微信将按此值搜索！")}</span> <a href="javascript:;" id="get_key_btn">{pigcms{:L_("按商品名称获取")}</a>
								</div>
                                <if condition="$config['group_address_self'] eq 1">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="long_lat">{pigcms{:L_("团购所处经纬度")}</label></label>
                                        <input class="col-sm-2" size="10" name="long_lat" id="long_lat" type="text" readonly="readonly"/>
                                        &nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">{pigcms{:L_("点击选取经纬度")}</a>
                                        <span class="form_tips">{pigcms{:L_("团购所处经纬度。")}</span>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="long_lat">{pigcms{:L_("团购所处地址")}</label></label>
                                        <input class="col-sm-5" maxlength="60" size="10" name="address_detail" id="group_address" type="text" readonly="readonly"/>
                                        <span class="form_tips">{pigcms{:L_("上述团购所处经纬度选择后自动显示地址。")}</span>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label>{pigcms{:L_("团购所处地选择")}</label></label>
                                        <fieldset id="choose_cityarea"></fieldset>
                                    </div>
                                </if>



                                <div class="form-group">
									<label  class="col-sm-1">{pigcms{:L_("支持退款：")}</label>
									<label><input type="radio" name="no_refund" value="0" class="ace" checked="checked"><span class="lbl" style="z-index: 1">{pigcms{:L_("支持")}</span></label>
									<label style=" margin-left:10px;"><input type="radio" name="no_refund" value="1" class="ace" ><span class="lbl" style="z-index: 1">{pigcms{:L_("不支持")}</span></label>
									<span class="form_tips" style=" color:red"> {pigcms{:L_("选择不支持，则用户付款该团购后不能进行退款操作")}</span>
								</div>
								
                                <div class="form-group before_effective_time_info">
                                    <label class="col-sm-1">{pigcms{:L_("提前取消时长：")}</label>
                                    <input class="col-sm-2 before_effective_time_input" min="0" maxlength="15" name="before_effective_time" type="number" value="0" />
                                    <select name="effective_time_type" class="col-sm-1" style="height: 34px;margin-left: 5px;">
                                        <option value="1">天</option>
                                        <option value="2">{pigcms{:L_("小时")}</option>
                                    </select>
                                    <span class="form_tips">{pigcms{:L_("在团购券有效期前多少时间用户可取消订单，反之不允许用户取消。0 为不开启该功能退款，按照上面")}<strong>{pigcms{:L_("支持退款")}</strong>{pigcms{:L_("的选择功能实现。")}</span>
                                </div>
							
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("原价：")}</label>
									<input class="col-sm-1" maxlength="100" name="old_price" type="text" value="" />
									<if condition="$config.open_extra_price eq 1 ">
										{pigcms{$config['Currency_txt']} + <input class="col-sm-1" maxlength="30" name="extra_pay_old_price" type="text" value="" style="float:none"/>{pigcms{$config.extra_price_alias_name}
										<span class="form_tips">{pigcms{:L_("如果填写")}{pigcms{$config.extra_price_alias_name}{pigcms{:L_("字段，商品价格将变为：金额")}+{pigcms{$config.extra_price_alias_name}数</span>
									</if>
									<span class="form_tips">{pigcms{:L_("必填。最多支持2位小数")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{$config.group_alias_name}{pigcms{:L_("价：")}</label>
									<input class="col-sm-1" maxlength="30" name="price" type="text" value="" />
									<if condition="$config.open_extra_price eq 1">
										{pigcms{$config['Currency_txt']} + <input class="col-sm-1" maxlength="30" name="extra_pay_price" type="text" value="" style="float:none"/>{pigcms{$config.extra_price_alias_name}
										<span class="form_tips">{pigcms{:L_("如果填写")}{pigcms{$config.extra_price_alias_name}{pigcms{:L_("字段，商品价格将变为：金额")}+{pigcms{$config.extra_price_alias_name}数</span>
									</if>
									<span class="form_tips">{pigcms{:L_("必填。最多支持2位小数")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("微信")}<if condition="isset($config['pay_weixinapp_appid'])">/app</if>{pigcms{:L_("优惠：")}</label>
									<input class="col-sm-1" maxlength="30" name="wx_cheap" type="text" value="" /><span class="form_tips">{pigcms{:L_("单位")}{pigcms{$config['Currency_txt']}{pigcms{:L_("，最多支持2位小数，不填则不显示微信优惠！实际购买价=（")}{pigcms{$config.group_alias_name}价-{pigcms{:L_("微信优惠）")}</span>
								</div>
								<div class="form-group"></div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{$config.group_alias_name}{pigcms{:L_("开始时间：")}</label>
									<input class="col-sm-2 Wdate" type="text" readonly="readonly" style="height:30px;" onfocus="var top = $(this).offset().top+30;WdatePicker({position:{top:top},isShowClear:false,readOnly:true,dateFmt:'yyyy{pigcms{:L_("年MM月dd日 HH时mm分ss秒")}',startDate:'{pigcms{:date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME'])}',vel:'begin_time'})" value="{pigcms{:date('Y年m月d日 H时i分s秒',$_SERVER['REQUEST_TIME'])}"/>
									<input name="begin_time" id="begin_time" type="hidden" value="{pigcms{:date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME'])}"/>
									<span class="form_tips">{pigcms{:L_("到了")}{pigcms{$config.group_alias_name}{pigcms{:L_("开始时间，商品才会显示！")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{$config.group_alias_name}{pigcms{:L_("结束时间：")}</label>
									<input class="col-sm-2 Wdate" type="text" readonly="readonly" style="height:30px;" onfocus="var top = $(this).offset().top+30;WdatePicker({position:{top:top},isShowClear:false,readOnly:true,dateFmt:'yyyy年MM月dd日 HH时mm分ss秒',startDate:'{pigcms{:date('Y-m-d H:i:s',strtotime('+1 day'))}',vel:'end_time'})" value="{pigcms{:date('Y年m月d日 H时i分s秒',strtotime('+1 day'))}"/>
									<input name="end_time" id="end_time" type="hidden" value="{pigcms{:date('Y-m-d H:i:s',strtotime('+1 day'))}"/>
									<span class="form_tips">{pigcms{:L_("超过")}{pigcms{$config.group_alias_name}{pigcms{:L_("结束时间，会结束")}{pigcms{$config.group_alias_name}！</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{$config.group_alias_name}{pigcms{:L_("券有效期：")}</label>
									<input class="col-sm-2 Wdate" type="text" readonly="readonly" style="height:30px;" onfocus="var top = $(this).offset().top+30;WdatePicker({position:{top:top},isShowClear:false,readOnly:true,dateFmt:'yyyy年MM月dd日 HH时mm分ss秒',startDate:'{pigcms{:date('Y-m-d H:i:s',strtotime('+7 day'))}',vel:'deadline_time'})" value="{pigcms{:date('Y年m月d日 H时i分s秒',strtotime('+7 day'))}"/>
									<input name="deadline_time" id="deadline_time" type="hidden" value="{pigcms{:date('Y-m-d H:i:s',strtotime('+7 day'))}"/><span class="required">*</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("使用时间限制：")}</label>
									<select name="is_general" class="col-sm-2">
										<option value="0">{pigcms{:L_("周末、法定节假日通用")}</option>
										<option value="1">{pigcms{:L_("周末不能使用")}</option>
										<option value="2">{pigcms{:L_("法定节假日不能使用")}</option>
										<option value="3">{pigcms{:L_("周末、法定节假日不能通用")}</option>
									</select>
								</div>
							</div>
							<div id="txtstore" class="tab-pane">
								<div class="form-group">
									<volist name="store_list" id="vo">
										<div class="radio">
											<label>
												<input class="paycheck ace" type="checkbox" name="store[]" value="{pigcms{$vo.store_id}" id="store_{pigcms{$vo.store_id}" checked="checked"/>
												<span class="lbl"><label for="store_{pigcms{$vo.store_id}">{pigcms{$vo.name} - {pigcms{$vo.area_name}-{pigcms{$vo.adress}</label></span>
                                                <br/>
                                                <volist name="package_list" id="voo">
                                                    <if condition="$voo['store_id'] eq $vo['store_id']">
                                                        <label>
                                                            <input class="paycheck ace" type="radio" name="package_id[{pigcms{$voo.store_id}]" value="{pigcms{$voo.id}" id="package_{pigcms{$voo.id}"/>
                                                            <span class="lbl"><label for="store_{pigcms{$voo.id}">{pigcms{$voo.name}</label></span>
                                                        </label>
                                                    </if>
                                                </volist>
											</label>
										</div>
									</volist>
								</div>
							</div>
							<div id="txtintro" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{$config.group_alias_name}{pigcms{:L_("类型：")}</label>
									<select name="tuan_type" class="col-sm-1" id="tuan_type">
										<option value="0">{pigcms{$config.group_alias_name}券</option>
										<option value="1">{pigcms{:L_("代金券")}</option>
										<option value="2">{pigcms{:L_("实物")}</option>
									</select>
									<span class="form_tips">{pigcms{:L_("如果是")}{pigcms{$config.group_alias_name}{pigcms{:L_("券或代金券，则会生成券密码； 如果是实物，才支持到店自提与快递配送，且需要填写快递单号")}(<font color="red">{pigcms{:L_("为防止对订单产生影响团购添加后,团购类型不能编辑")}</font>)</span>
								</div>
								<div class="form-group">
									<label  class="col-sm-1">{pigcms{:L_("是否支持自动核销：")}</label>
									<label><input type="radio" name="auto_check" value="0" class="ace" checked="checked"><span class="lbl" style="z-index: 1">{pigcms{:L_("不支持")}</span></label>
									<label style=" margin-left:10px;"><input type="radio" name="auto_check" value="1" class="ace" ><span class="lbl" style="z-index: 1">{pigcms{:L_("支持")}</span></label>
									<span class="form_tips" style=" color:red"> {pigcms{:L_("选择支持，则用户付款该团购商品后该订单自动核销，无需店员操作核销动作；自动核销只支持团购商品归属为一个店铺，如果归属为多个店铺，则默认核销第一个店铺。")}</span>
								</div>
								<div class="form-group open_express " style="display:none">
									<label class="col-sm-1">{pigcms{:L_("开启快递：")}</label>
									<select name="open_express" class="col-sm-1">
										<option value="0">{pigcms{:L_("否")}</option>
										<option value="1" selected>{pigcms{:L_("是")}</option>
									</select>
									<span class="form_tips">{pigcms{:L_("选否用户前台不出现快递的选项")}</span>
								</div>
								<if condition="$config['baodi_ticket_url']">
									<div class="form-group" id="custom_product_id">
										<label class="col-sm-1">{pigcms{:L_("票型ID")}：</label>
										<input class="col-sm-1 inputNumberDot" maxlength="20" name="custom_product_id" type="text" value=""/><span class="form_tips">{pigcms{:L_("对应票类型的seqid")}</span>
									</div>
								</if>
                                <div class="form-group" id="tuan_pass_num">
                                    <label class="col-sm-1">{pigcms{:L_("一份团购生成券密码数量：")}</label>
                                    <input class="col-sm-1 inputNumberDot" maxlength="20" name="pass_num" type="number" value=""/><span class="form_tips">{pigcms{:L_("一份团购生成券密码数量，0和不填表示一份对应生成一张，暂时最大支持500")}</span>
                                </div>

								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("选择分类：")}</label>
									<select id="choose_catfid" name="cat_fid" class="col-sm-1" style="margin-right:10px;">
										<volist name="f_category_list" id="vo">
											<option value="{pigcms{$vo.cat_id}">{pigcms{$vo.cat_name}</option>
										</volist>
									</select>
                                    <if condition="$config.group_category_switch neq 1">
                                        <select id="choose_catid" name="cat_id" class="col-sm-1" style="margin-right:10px;">
                                            <volist name="s_category_list" id="vo">
                                                <option value="{pigcms{$vo.cat_id}">{pigcms{$vo.cat_name}</option>
                                            </volist>
                                        </select>
                                    </if>
								</div>

                                <if condition="$config.group_category_switch eq 1">
                                    <div class="form-group" id="choose_child_info" style="padding-left: 8.333%;">
                                        <volist name="s_category_list" id="child">
                                            <label style="margin-right:10px;min-width: 120px;">
                                                <input class="ace" type="checkbox" name="group_category[]" value="{pigcms{$f_category_list[0].cat_id}-{pigcms{$child.cat_id}" id="group_category_{pigcms{$child.cat_id}"/>
                                                <span class="lbl"><label for="group_category_{pigcms{$child.cat_id}">{pigcms{$child.cat_name}</label></span>
                                            </label>
                                        </volist>
                                    </div>
                                </if>

								<div style="border:1px solid #c5d0dc;padding-left:22px;margin-bottom:10px;" id="custom_html_tips">
									<div class="form-group" style="margin-top:10px;color:red;">{pigcms{:L_("以下为主分类设定的特殊字段，不同分类字段不同，请选择。")}</div>
									<div id="custom_html">{pigcms{$custom_html}</div>
								</div>
								<div style="border:1px solid #c5d0dc;padding-left:22px;margin-bottom:10px;" id="cue_html_tips">
									<div class="form-group" style="margin-top:30px;color:red;">{pigcms{:L_("以下为主分类设定的 购买须知填写项，请填写。")}</div>
									<div id="cue_html">{pigcms{$cue_html}</div>
								</div>
                                <if condition="$config['group_content_switch'] eq 1">
                                    <div class="form-group" >
                                        <label class="col-sm-3">{pigcms{:L_("商品详情")}<span style="font-size:12px;color:#999;">({pigcms{:L_("必填")})</span>：</label>
                                    </div>
<!--                                    <div id="form-group-detail-info">-->
<!--                                        <div class="form-group" style="margin-bottom:0px;margin-top:20px;">-->
<!--                                            <label class="col-sm-1">&nbsp;</label>-->
<!--                                            <input class="col-sm-1 group_content_detail" minlength="1" maxlength="6" name="group_content[0][name]" type="text" value="" /><span class="form_tips">{pigcms{:L_("多个详情必填。单个详情默认为商品详情，最多6个字！")}</span>-->
<!--                                        </div>-->
<!--                                        <div class="form-group" style="margin-bottom:0px;margin-top:20px;">-->
<!--                                            <label class="col-sm-1">&nbsp;</label>-->
<!--                                            <a href="javascript:;" id="editor_plan_btn_0">{pigcms{:L_("插入套餐表格")}</a>-->
<!--                                        </div>-->
<!--                                        <div class="form-group" >-->
<!--                                            <label class="col-sm-1"></label>-->
<!--                                            <textarea name="group_content[0][content]" id="content_0" style="width:702px;"></textarea>-->
<!--                                        </div>-->
<!--                                    </div>-->

                                    <div id="form-group-detail-info">
                                        <volist name="editor_title" id="info" key="index">
                                            <div class="group_content_info">
                                                <div class="form-group" style="margin-bottom:0px;margin-top:20px;">
                                                    <label class="col-sm-1">&nbsp;</label>
                                                    <input class="col-sm-1 group_content_detail" name="group_content[{pigcms{$index}][name]" type="text" value="{pigcms{$info}" disabled/>
                                                    <input class="col-sm-1" name="group_content[{pigcms{$index}][name]" type="hidden" value="{pigcms{$info}"/>
                                                </div>
                                                <div class="form-group" style="margin-bottom:0px;margin-top:20px;">
                                                    <label class="col-sm-1">&nbsp;</label>
                                                    <a href="javascript:;" id="editor_plan_btn_{pigcms{$index}">{pigcms{:L_("插入套餐表格")}</a>
                                                </div>
                                                <div class="form-group" >
                                                    <label class="col-sm-1"></label>
                                                    <textarea name="group_content[{pigcms{$index}][content]" id="content_{pigcms{$index}" style="width:702px;"></textarea>
                                                </div>
                                            </div>
                                        </volist>
                                    </div>
                                    <else/>

                                    <div class="form-group" style="margin-bottom:0px;margin-top:20px;"><label class="col-sm-1">&nbsp;</label><a href="javascript:;" id="editor_plan_btn">{pigcms{:L_("插入套餐表格")}</a></div>
                                    <div class="form-group" >
                                        <label class="col-sm-1">{pigcms{:L_("本单详情：")}<br/><span style="font-size:12px;color:#999;">({pigcms{:L_("必填")})</span></label>
                                        <textarea name="content" id="content" style="width:702px;"></textarea>
                                    </div>
                                </if>
							</div>
							<div id="promotion" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("商品折扣")}</label>
									<input class="col-sm-1" size="10" maxlength="10" name="discount" id="Config_mean_full_money" type="text" value="" /><strong style="color:red">0~10{pigcms{:L_("之间的数字，支持一位小数！8代表8折，8.5代表85折，0与10代表无折扣")}</strong>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("优惠方式")}</label></label>
									<span><label><input id='discount_type0' name="vip_discount_type"  value="2" type="radio"></label>&nbsp;<span>{pigcms{:L_("折上折")}</span>&nbsp;</span>
									<span><label><input id='discount_type1' name="vip_discount_type" value="1" type="radio" ></label>&nbsp;<span>{pigcms{:L_("折扣最优")}</span></span>
									<strong style="color:red">{pigcms{:L_("折上折的意思是如果这个用户是有平台VIP等级，平台VIP等级有折扣优惠。那么这个用户的优惠计算方式是先用店铺的优惠进行打折后，再用VIP折扣进去打折；")}<br/>
									{pigcms{:L_("折扣最优是指：购买产品的总价用店铺优惠打折后的价格与总价跟VIP优惠打折后的价格进行比较，取最小值的优惠方式。")}
									</strong>
								</div>
								<div style="clear:both;"></div>
							</div>
							<if condition="!empty($levelarr)">
							<div id="levelcoupon" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1" style="color:red;width:95%;">{pigcms{:L_("说明：必须设置一个会员等级优惠类型和优惠类型对应的数值，我们将结合优惠类型和所填的数值来计算该商品会员等级的优惠的幅度！")}</label>
								</div>
							    <volist name="levelarr" id="vv">
								  <div class="form-group">
								    <input  name="leveloff[{pigcms{$vv['level']}][lid]" type="hidden" value="{pigcms{$vv['id']}"/>
								    <input  name="leveloff[{pigcms{$vv['level']}][lname]" type="hidden" value="{pigcms{$vv['lname']}"/>
									<label class="col-sm-1">{pigcms{$vv['lname']}：</label>
									{pigcms{:L_("优惠类型：")}&nbsp;
									<select name="leveloff[{pigcms{$vv['level']}][type]" <if condition="empty($_SESSION['system']) AND $config['discount_sync'] eq 1">disabled</if>>
										<option value="0" <if condition="$config.discount_sync eq 1 AND $vv['type'] eq 0">selected</if>>{pigcms{:L_("无优惠")}</option>
										<option value="1" <if condition="$config.discount_sync eq 1 AND $vv['type'] eq 1">selected</if>>{pigcms{:L_("百分比（%）")}</option>
										<option value="2" <if condition="$config.discount_sync eq 1 AND $vv['type'] eq 2">selected</if>>{pigcms{:L_("立减")}</option>
									</select>
									<input name="leveloff[{pigcms{$vv['level']}][vv]" value="<if condition="$config.discount_sync eq 1 ">{pigcms{$vv.vv}</if>" type="text" <if condition="empty($_SESSION['system']) AND $config['discount_sync'] eq 1">disabled</if> placeholder="{pigcms{:L_("请填写一个优惠值数字")}" onkeyup="value=value.replace(/[^1234567890]+/g,'')"/>
								</div>
								</volist>
							</div>
							</if>

                            <div id="specifications" class="tab-pane">
                                <div class="form-group">
                                    <label class="col-sm-1" style="color:red;width:95%;">{pigcms{:L_("说明：可为团购商品的规格设置一个价格、库存、同一ID最多购买数量、同一ID每天最多购买数量、同一ID每次最少购买数量。团购规格适用于普通团购，但是当团购类型为其他的时候、规格失效！")}</label>
                                </div>
                                <div class="form-group">
                                    <div class="table-list">
                                        <table style="width: calc(100% - 20px)" cellspacing="0">
                                            <thead>
                                            <tr>
                                                <th class="rt-border">{pigcms{:L_("规格")}-{pigcms{:L_("名称")}<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_("仅限6位长度")}"></th>
                                                <th class="rt-border">{pigcms{:L_("价格")}<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_("如果填写规格则必填。最多支持2位小数！")}"></th>
                                                <th class="rt-border">{pigcms{:L_("原价")}<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_("选填。最多支持2位小数")}"></th>
                                                <th class="rt-border">{pigcms{:L_("库存")}<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="0{pigcms{:L_("表示不限制，否则产品会出现“已卖光”状态！")}"></th>
                                                <th class="rt-border">{pigcms{:L_("同一ID最多购买数量")}<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_("同一个ID最多购买数量，0表示不限制！请填写大于等于零的值！")}"></th>
                                                <th class="rt-border">{pigcms{:L_("同一ID每天最多购买数量")}<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_("同一ID每天最多购买数量，0表示不限制！请填写大于等于零的值！")}"></th>
                                                <th class="rt-border">{pigcms{:L_("同一ID每次最少购买数量")}<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_("同一ID每次最少购买数量，购买数量低于此设定的不允许参团！请填写大于等于1的值！")}"></th>
                                                <th class="rt-border"></th>
                                            </tr>
                                            </thead>
                                            <tbody id="specifications_msg">
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="add_specifications_div"  id="add_specifications">
                                        <button type="button" class="plus_recharge"></button>
                                        <a class="add_specifications" style="margin-top: 5px">
                                            {pigcms{:L_("添加规格最多6个")}
                                        </a>
                                    </div>
                                </div>
                            </div>

							<div id="relpackages" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1" style="color:red;width:95%;">{pigcms{:L_("说明：一个团购商品只能参与一个套餐！")}</label>
								</div>
							  <div class="form-group">
									<label class="col-sm-1">本{pigcms{$config.group_alias_name}{pigcms{:L_("套餐标签：")}</label>
									<input class="col-sm-2" maxlength="30" name="tagname" type="text" value="" />
									<if condition="!empty($mpackagelist)">
									<label class="col-sm-1" style="margin-left:20px;">{pigcms{:L_("选择加入套餐：")}</label>
									<select name="packageid" style="width:300px;">
									<option value="0">{pigcms{:L_("不加入任何套餐")}</option>
									<volist name="mpackagelist" id="vo">
									  <option value="{pigcms{$vo['id']}">{pigcms{$vo['title']}</option>
									</volist>
									</select>
									<else />
									<label class="col-sm-2" style="color:red;">{pigcms{:L_("您还没有套餐可选，")}<a href="{pigcms{:U('Group/mpackageadd')}">{pigcms{:L_("请点击这里去新建吧")}</a></label>
									</if>
									<span class="form_tips"></span>
							   </div>
							</div>
							<div id="express_template" class="tab-pane">
							
                                <if condition="!empty($express_list)">
                                   <div class="form-group">
									
									<label class="col-sm-1" >{pigcms{:L_("选择运费模板：")}</label>
									<select name="express_template_id" style="width:300px;">
									<option value="0">{pigcms{:L_("不选择运费模板")}</option>
									<volist name="express_list" id="vo">
									  <option value="{pigcms{$vo['id']}" >{pigcms{$vo['name']}</option>
									</volist>
									</select>
                                  </div>
								  <div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("其他区域运费：")}</label>
									<input class="col-sm-2" maxlength="30" name="express_fee" type="text" value="" />
									</div>
									<else />
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("其他区域运费：")}</label>
										<input class="col-sm-2" maxlength="30" name="express_fee" type="text" value="{pigcms{$now_group['express_fee']}" />
										<input name="express_template_id" type="hidden" value="0" />
									</div>
									</if>
									<span class="form_tips"></span>
							 
							</div>
							<div id="txtnum" class="tab-pane">
								<div class="form-group" style="display:none;">
									<label class="col-sm-1">{pigcms{:L_("成功")}{pigcms{$config.group_alias_name}{pigcms{:L_("人数要求：")}</label>
									<input class="col-sm-1" maxlength="20" name="success_num" type="text" value="1" /><span class="form_tips">{pigcms{:L_("最少需要多少人购买才算")}{pigcms{$config.group_alias_name}{pigcms{:L_("成功。")}</span>
								</div>
								<div class="form-group" style="display:none;">
									<label class="col-sm-1">{pigcms{:L_("虚拟已购买人数：")}</label>
									<input class="col-sm-1" maxlength="20" name="virtual_num" type="text" value="0" /><span class="form_tips">{pigcms{:L_("前台购买人数会显示[ 虚拟购买人数")}+{pigcms{:L_("真实购买人数")} ]</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("商品总数量：")}</label>
									<input class="col-sm-1" maxlength="20" name="count_num" type="number" value="0" /><span class="form_tips">0{pigcms{:L_("表示不限制，否则产品会出现“已卖光”状态")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">ID{pigcms{:L_("最多购买数量：")}</label>
									<input class="col-sm-1" maxlength="20" name="once_max" type="number" value="" /><span class="form_tips">{pigcms{:L_("一个ID最多购买数量，0表示不限制")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">ID{pigcms{:L_("每天最多购买数量：")}</label>
									<input class="col-sm-1" maxlength="20" name="once_max_day" type="number" value="" /><span class="form_tips">{pigcms{:L_("一个ID每天最多购买数量，0表示不限制。也会受“ID最多购买数量”配置项限制")}</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("转赠是否受限：")}</label>
                                    <label><input type="radio" name="is_give_limit" value="1" checked="checked">&nbsp;&nbsp;{pigcms{:L_("是")}</label>&nbsp;&nbsp;&nbsp;
                                    <label><input type="radio" name="is_give_limit" value="0">&nbsp;&nbsp;{pigcms{:L_("否")}</label>&nbsp;&nbsp;&nbsp;
                                    <span class="form_tips">{pigcms{:L_("商品券转赠好友是受id购买数量限制，即 若转赠好友id已达购买数量 则无法转赠该好友")}</span>
                                </div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("一次最少购买数量：")}</label>
									<input class="col-sm-1" maxlength="20" name="once_min" type="number" value="1" /><span class="form_tips">{pigcms{:L_("购买数量低于此设定的不允许参团")}</span>
								</div>

                                <div class="alert alert-block alert-success">
                                    <button type="button" class="close" data-dismiss="alert">
                                        <i class="ace-icon fa fa-times"></i>
                                    </button>
                                    <p>
                                        {pigcms{:L_("如果设置商品规格后，以上")}<strong> {pigcms{:L_("商品总数量 、ID最多购买数量、ID每天最多购买数量、一次最少购买数量")}</strong> {pigcms{:L_("设置项将无效")}<br/>
                                    </p>
                                </div>

								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("库存减少的方式：")}</label>
									<label><input type="radio" name="stock_reduce_method" value="0" checked="checked" >&nbsp;&nbsp;{pigcms{:L_("支付成功后减库存")}</label>&nbsp;&nbsp;&nbsp;
									<label><input type="radio" name="stock_reduce_method" value="1">&nbsp;&nbsp;{pigcms{:L_("下单成功后减库存")}</label>&nbsp;&nbsp;&nbsp;
								</div>
								<div class="alert alert-block alert-success">
									<button type="button" class="close" data-dismiss="alert">
										<i class="ace-icon fa fa-times"></i>
									</button>
									<p>
										1.{pigcms{:L_("支付成功后减库存：")}<strong>{pigcms{:L_("可能会出现售出的数量大于商品总数")}</strong><br/>
										2.{pigcms{:L_("下单成功后减库存：")}<strong>{pigcms{:L_("可能会出现大量下单但是没有支付时库存已经没有了，但是如果20分钟后还是没有买单的话系统自动回滚库存")}</strong>
									</p>
								</div>
							</div>
							<div id="pickinstore" class="tab-pane">
								<div class="form-group">
									<label  class="col-sm-1">{pigcms{:L_("开启到店自提：")}</label>
									<label><input type="radio" name="pick_in_store" value="1" class="ace" ><span class="lbl" style="z-index: 1">{pigcms{:L_("开启")}</span></label>
									<label style=" margin-left:10px;"><input type="radio" name="pick_in_store" value="0" class="ace" checked="checked"><span class="lbl" style="z-index: 1">{pigcms{:L_("关闭")}</span></label>
									<span class="form_tips" style=" color:red">{pigcms{:L_("没有设置其他到店自提地点，默认自提点为团购的店铺")}</span>
								</div>
							</div>
							<div id="opengroupnum" class="tab-pane">
								<input type="hidden" name="open_pin"  value="1"  >
								<div id="open_pin" >
									<div class="form-group">
										<label  class="col-sm-1">{pigcms{:L_("拼团人数设置")}</label>
										<input class="col-sm-1" maxlength="20" name="pin_num" type="num" value="" /><span class="form_tips">{pigcms{:L_("拼团人数设置，0表示不设置拼团，最少两人")}</span>
									</div>
                                    <div class="form-group">
                                        <label  class="col-sm-1">{pigcms{:L_("团长优惠比例")}</label>
                                        <input class="col-sm-1" maxlength="20" name="start_discount" type="num" value="" /><span class="form_tips">{pigcms{:L_("团长优惠（百分比")}0-100{pigcms{:L_("） 0 为免费，100 为原价")}</span>
                                    </div>
                                    <div class="form-group">
                                        <label  class="col-sm-1">{pigcms{:L_("团长每次最多可购买数量")}</label>
                                        <input class="col-sm-1" maxlength="20" name="start_max_num" type="num" value="" /><span class="form_tips">{pigcms{:L_("团购每次最多可购买数量，默认是1份")}</span>
                                    </div>
									<div class="form-group">
										<label  class="col-sm-1">{pigcms{:L_("成团后退款手续费比例：")}</label>
										<input class="col-sm-1" maxlength="20" name="group_refund_fee" type="num" value="" /><span class="form_tips">{pigcms{:L_("手续费将按单价百分比（")}0-100{pigcms{:L_("）收取,设置为100（成团前所有参团人也不可退）则不允许退款")}</span>
									</div>
									<div class="form-group">
										<label  class="col-sm-1">{pigcms{:L_("成团有效期")}</label>
										<input class="col-sm-1" maxlength="20" name="pin_effective_time" type="num" value="" /><span class="form_tips">{pigcms{:L_("有效期内未成团团长不能取消订单，单位")}/时</span>
									</div>
									<div class="form-group">
										<span class="form_tips">({pigcms{:L_("拼团人数设置后，团购按拼团的逻辑走，如果当前团购有组团购在进行则不能设置")})</span>
									</div>

                                    <div class="alert alert-block alert-success">
                                        <button type="button" class="close" data-dismiss="alert">
                                            <i class="ace-icon fa fa-times"></i>
                                        </button>
                                        <p>
                                            {pigcms{:L_("拼团开启后，")}<strong> {pigcms{:L_("规格属性设置无效")}</strong><br/>
                                        </p>
                                    </div>
								</div>
							</div>
							<div id="trade" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("绑定插件：")}</label>
									<select name="bind_trade" id="bind_trade" class="col-sm-2">
										<option value="0">{pigcms{:L_("不绑定")}</option>
										<if condition="$appoint_list">
											<option value="1">{pigcms{:L_("预约模块")}</option>
										</if>
										<option value="2">{pigcms{:L_("酒店模块")}</option>
									</select>
								</div>
								<if condition="$appoint_list">
									<div class="trade_box" id="trade_box_1" style="display:none;">
										<div class="form-group">
											<label class="col-sm-1">{pigcms{:L_("选择预约：")}</label>
											<select class="col-sm-1" name="appoint_id">
												<option value="0">{pigcms{:L_("请选择")}</option>
												<volist name="appoint_list" id="vo">
												<option value="{pigcms{$vo.appoint_id}">{pigcms{$vo.appoint_name}</option>
												</volist>
											</select>
										</div>
									</div>
								</if>
								<div class="trade_box" id="trade_box_2" style="display:none;">
									<div class="form-group">
										<label class="col-sm-8">{pigcms{:L_("目前酒店行业插件只适用于单门店，用户下单时会自动在团购绑定的第一个店铺中下单，建议只绑定一个店铺。")}</label>
									</div>
									<div class="form-group">
										<div style="padding-left:12px;margin-bottom:10px;">{pigcms{:L_("绑定分类：")}</div>
										<volist name="trade_hotel_category_list" id="vo">
											<div class="radio">
												<label>
													<input class="paycheck ace" type="checkbox" name="trade_hotel_cat_id[]" value="{pigcms{$vo.cat_id}" id="trade_hotel_category_{pigcms{$vo.cat_id}"/>
													<span class="lbl"><label for="trade_hotel_category_{pigcms{$vo.cat_id}">{pigcms{$vo.cat_name}</label></span>
												</label>
											</div>
										</volist>
									</div>
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

<div id="modal-table" class="modal fade" tabindex="-1" style="display:none;">
    <div class="modal-dialog" style="width:80%;">
        <div class="modal-content" style="width:100%;">
            <div class="modal-header no-padding" style="width:100%;">
                <div class="table-header">
                    <button id="close_button" type="button" class="close" data-dismiss="modal" aria-hidden="true">
                        <span class="white">&times;</span>
                    </button>
                    {pigcms{:L_("选择经纬度")}
                </div>
            </div>
            <div class="modal-body no-padding" style="width:100%;">
                <form id="map-search" style="margin:10px;">
                    <input id="map-keyword" type="textbox" style="width:500px;" placeholder="{pigcms{:L_("尽量填写城市、区域、街道名")}"/>
                    <input type="submit" value="{pigcms{:L_("搜索")}"/>
                </form>
                <div style="margin-left:10px;margin-bottom:10px;" id="map_tips">({pigcms{:L_("用鼠标滚轮可以缩放地图)    拖动红色图标，经纬度框内将自动填充经纬度。")}</div>
                <div style="width:100%;height:560px;min-height:560px;" id="cmmap"></div>
            </div>
            <div class="modal-footer no-margin-top">
                <button class="btn btn-sm btn-success pull-right" data-dismiss="modal">
                    <i class="ace-icon fa fa-times"></i>
                    {pigcms{:L_("关闭")}
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var cityMatchingUrl = "{pigcms{:U('Group/cityMatching')}";
    var choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}",choose_market='';
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area.js?t=01"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/merchant_category.js"></script>
<if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
    <script  src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&key={pigcms{$config.google_map_ak}&libraries=places"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/group_google_map.js"></script>
    <else />
    <script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/group_map.js"></script>
</if>

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
    .ke-dialog-default.ke-dialog {
        top: 38% !important;
    }
</style>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>

<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script type="text/javascript">
// "添加详情"
var item_content_obj = {};
var group_content_switch = "{pigcms{$config['group_content_switch']}";
if (group_content_switch == 1 || group_content_switch == '1') {
    var group_content_count = "{pigcms{$editor_title_count}";
    console.log('group_content_count', group_content_count);
    for (var i = 1; i <= parseInt(group_content_count); i ++) {
        item_content_obj[i] = true;
        editor_initialization('content_' + i, 'editor_plan_btn_' + i);
    }
}
$('.inputNumberDot').change(function () {
    var limitNum = $(this).val().replace(/[^0-9.]+/g, "");
    if(limitNum>=0&&limitNum<=500){
        $(this).val(limitNum);
    }else{
        $(this).val("");
    }
})


$('input[name="no_refund"]').click(function () {
    if ($(this).val() == 1) {
        $('.before_effective_time_info').css('display', 'none');
        $('input[name="before_effective_time"]').val('');
    } else {
        $('.before_effective_time_info').css('display', 'block');
    }
});


$('td .tips_img').mouseover(function(e){
    var now    = $(this);
    var offset = $(this).offset();
    var parent = $(this).closest('td');
    now.data('tips',now.attr('title')).attr('title','');
    parent.append('<div class="tooltipdi" style="left:'+(offset.left+30)+'px;top:'+(offset.top-6)+'px"><span><b></b><em></em>'+now.data('tips')+'</span></div>');
    var tips_div = parent.find('.tooltipdi');
    parent.one('mouseout',function(){
        now.attr('title',now.data('tips'));
        tips_div.remove();
    });
});


// "添加详情")}
var item_content_obj = {};
item_content_obj[0] = true;
$('#add_content').click(function(){
    console.log('{pigcms{:L_("键值")}--item_content_obj-----', item_content_obj);
    var html_div = '';
    var item_arr = Object.keys(item_content_obj);
    if (item_arr.length >= 5) {
        alert('{pigcms{:L_("详情最多同时添加5个！")}');
        return false;
    }
    var item = item_arr.length;
    console.log('{pigcms{:L_("键值")}-------', item);
    for (;item_content_obj[item];item = parseInt(4*Math.random())) {
    }
    item_content_obj[item] = true;

    html_div = '<div class="group_content_info">';
    html_div += '<div class="form-group" style="margin-bottom:0px;margin-top:20px;">';
    html_div += '<label class="col-sm-1">&nbsp;</label>';
    html_div += '<input class="col-sm-1" minlength="1" maxlength="6" name="group_content['+item+'][' + "name" + ']" type="text" value="" /><span class="form_tips">{pigcms{:L_("多个详情必填。单个详情默认为商品详情，最多6个字！")}</span>';
    html_div += '</div>';
    html_div += '<div class="form-group" style="margin-bottom:0px;margin-top:20px;">';
    html_div += '<label class="col-sm-1">&nbsp;</label>';
    html_div += '<a href="javascript:;" id="editor_plan_btn_'+item+'">{pigcms{:L_("插入套餐表格")}</a>';
    html_div += '</div>';
    html_div += '<div class="form-group">';
    html_div += '<label class="col-sm-1"></label>';
    html_div += '<textarea name="group_content['+item+'][' + "content" + ']" id="content_'+item+'" style="width:702px;"></textarea>';
    html_div += '<div data-item="'+item+'" class="reduce_specifications_div"  id="reduce_specifications"><button data-item="'+item+'" type="button" class="reduce_recharge_content"></button></div>';
    html_div += '</div>';
    html_div += '</div>';
    $('#form-group-detail-info').append(html_div);
    // "删除")}
    $(".reduce_recharge_content").click(function() {
        var item = $(this).data('item');
        console.log('{pigcms{:L_("删除的字段")}---------', item);
        $(this).parents().parent('.group_content_info').remove();
        delete(item_content_obj[item]);
    });

    editor_initialization('content_' + item, 'editor_plan_btn_' + item);

});

function category_change_content(content_name) {
    var html_div = '';
    var item_arr = Object.keys(item_content_obj);
    if (item_arr.length >= 5) {
        alert('{pigcms{:L_("详情最多同时添加5个！")}');
        return false;
    }
    var item = item_arr.length;
    console.log('{pigcms{:L_("键值")}-------', item);
    for (;item_content_obj[item];item = parseInt(4*Math.random())) {
    }
    item_content_obj[item] = true;

    html_div = '<div class="group_content_info">';
    html_div += '<div class="form-group" style="margin-bottom:0px;margin-top:20px;">';
    html_div += '<label class="col-sm-1">&nbsp;</label>';
    html_div += '<input class="col-sm-1" name="group_content['+item+'][' + "name" + ']" type="text" value="'+content_name+'" disabled/>';
    html_div += '<input class="col-sm-1" name="group_content['+item+'][' + "name" + ']" type="hidden" value="'+content_name+'"/>';
    html_div += '</div>';
    html_div += '<div class="form-group" style="margin-bottom:0px;margin-top:20px;">';
    html_div += '<label class="col-sm-1">&nbsp;</label>';
    html_div += '<a href="javascript:;" id="editor_plan_btn_'+item+'">{pigcms{:L_("插入套餐表格")}</a>';
    html_div += '</div>';
    html_div += '<div class="form-group">';
    html_div += '<label class="col-sm-1"></label>';
    html_div += '<textarea name="group_content['+item+'][' + "content" + ']" id="content_'+item+'" style="width:702px;"></textarea>';
    // html_div += '<div data-item="'+item+'" class="reduce_specifications_div"  id="reduce_specifications"><button data-item="'+item+'" type="button" class="reduce_recharge_content"></button></div>';
    html_div += '</div>';
    html_div += '</div>';
    $('#form-group-detail-info').append(html_div);
    // "删除")}
    $(".reduce_recharge_content").click(function() {
        var item = $(this).data('item');
        console.log('{pigcms{:L_("删除的字段")}---------', item);
        $(this).parents().parent('.group_content_info').remove();
        delete(item_content_obj[item]);
    });

    editor_initialization('content_' + item, 'editor_plan_btn_' + item);
}


// "添加规格")}
var item_obj = {};
$('#add_specifications').click(function(){
    console.log('{pigcms{:L_("键值")}--item_obj-----', item_obj);
    var html_div = '';
    var item_arr = Object.keys(item_obj);
    if (item_arr.length >= 6) {
        alert('{pigcms{:L_("规格最多同时添加6个！")}');
        return false;
    }
    var item = $('#specifications_msg').children().length;
    console.log('{pigcms{:L_("键值")}-------', item);
    for (;item_obj[item];item = parseInt(6*Math.random())) {
    }
    item_obj[item] = true;


    html_div = '<tr class="specifications_info">';
    html_div += '<td class="rt-border td-class"><input class="specifications-input" maxlength="6" name="specifications['+item+'][' + "specifications_name" + ']" type="text" value="" /></td>';
    html_div += '<td class="rt-border td-class"><input class="specifications-input" maxlength="100" name="specifications['+item+'][' + "price" + ']" type="text" value="" /></td>';
    html_div += '<td class="rt-border td-class"><input class="specifications-input" maxlength="100" name="specifications['+item+'][' + "old_price" + ']" type="text" value="" /></td>';
    html_div += '<td class="rt-border td-class"><input class="specifications-input specifications_count_num" maxlength="20" min="0" name="specifications['+item+'][' + "count_num" + ']" type="number" value="0" /></td>';
    html_div += '<td class="rt-border td-class"><input class="specifications-input specifications_once_max" maxlength="20" min="0"  name="specifications['+item+'][' + "once_max" + ']" type="number" value="" /></td>';
    html_div += '<td class="rt-border td-class"><input class="specifications-input specifications_once_max_day" maxlength="20" min="0" name="specifications['+item+'][' + "once_max_day" + ']" type="number" value="" /></td>';
    html_div += '<td class="rt-border td-class"><input class="specifications-input once_min_input"  min="1" maxlength="20" name="specifications['+item+'][' + "once_min" + ']" type="number" value="1" /></td>';
    html_div += '<td class="rt-border td-class"><div data-item="'+item+'" class="reduce_specifications_div"  id="reduce_specifications"><button data-item="'+item+'" type="button" class="reduce_recharge"></button></div></td>';
    html_div += '</tr>';

    $('#specifications_msg').append(html_div);
    // "删除")}
    $(".reduce_recharge").click(function() {
        var item = $(this).data('item');
        console.log('{pigcms{:L_("删除的字段")}---------', item);
        $(this).parents().parent('.specifications_info').remove();
        delete(item_obj[item]);
    });
    $(".once_min_input").blur(function () {
        console.log('值---------', $(this).val());
        var once_min_input = $(this).val();
        if (once_min_input <= 0 || once_min_input%1 != 0) {
            alert('{pigcms{:L_("当前所填写的值必须大于等于1的整数！")}');
            $(this).val(1);
        }
    });
    // "规格过滤提醒")}
    $(".specifications_count_num").blur(function () {
        var once_min_input = $(this).val();
        console.log('值---------', once_min_input);
        console.log('值----%1-----', once_min_input%1);
        if (once_min_input%1 != 0 || once_min_input < 0) {
            alert('{pigcms{:L_("当前所填写的值必须大于等于0的整数！")}');
            $(this).val(0);
        }
    });
    $(".specifications_once_max").blur(function () {
        var once_min_input = $(this).val();
        console.log('值---------', once_min_input);
        console.log('值----%1-----', once_min_input%1);
        if (once_min_input%1 != 0 || once_min_input < 0) {
            alert('{pigcms{:L_("当前所填写的值必须大于等于0的整数！")}');
            $(this).val(0);
        }
    });
    $(".specifications_once_max_day").blur(function () {
        var once_min_input = $(this).val();
        console.log('值---------', once_min_input);
        console.log('值----%1-----', once_min_input%1);
        if (once_min_input%1 != 0 || once_min_input < 0) {
            alert('{pigcms{:L_("当前所填写的值必须大于等于0的整数！")}');
            $(this).val(0);
        }
    });

});

// "过滤提醒")}
$(".before_effective_time_input").blur(function () {
    var once_min_input = $(this).val();
    console.log('值---------', once_min_input);
    console.log('值----%1-----', once_min_input%1);
    if (once_min_input%1 != 0 || once_min_input < 0) {
        alert('{pigcms{:L_("当前所填写的值必须大于等于0的整数！")}');
        $(this).val(0);
    }
});

$('#bind_trade').change(function(){
    $('.trade_box').hide();
    $('#trade_box_'+$(this).val()).show();
    if($(this).val()==2){
        $(":radio[name='stock_reduce_method']").attr('checked',false);
        $(":radio[name='stock_reduce_method'][value='0']").attr('checked',true);
        $("input[name='count_num']").val(0);
    }
});

$('#tuan_type').change(function(){
    if($(this).val()==2){
        $('.open_express').show();
        $('#tuan_pass_num').hide()
    }else{
        $('.open_express').hide();
        if ($(this).val()<2) {
            $('#tuan_pass_num').show()
        } else {
            $('#tuan_pass_num').hide()
        }
    }
});

$('#choose_catfid').change(function(){
    var cat_fid = $(this).val();
    var group_category_switch = "{pigcms{$config.group_category_switch}";
    console.log('group_category_switch', group_category_switch);
    console.log('cat_fid', cat_fid);
    $.getJSON("{pigcms{:U('Group/ajax_get_category')}",{cat_fid: cat_fid},function(result){
        if(result.error == 0){
            var catid_html = '';

            if (group_category_switch && group_category_switch == 1) {
                $.each(result.cat_list,function(i,item){
                    catid_html += '<label style="margin-right:10px;min-width: 120px;">';
                    catid_html += '<input class="ace" type="checkbox" name="group_category[]" value="' +cat_fid+'-'+item.cat_id+'" id="group_category_' +item.cat_id+'"/>';
                    catid_html += '<span class="lbl"><label for="group_category_'+item.cat_id+'">&nbsp;'+item.cat_name+'</label></span>';
                    catid_html += '</label>';
                });
                $('#choose_child_info').html(catid_html);
            } else {
                $.each(result.cat_list,function(i,item){
                    catid_html += '<option value="'+item.cat_id+'">'+item.cat_name+'</option>';
                });
                $('#choose_catid').html(catid_html);
            }

            if(result.custom_html == ''){
                $('#custom_html_tips').hide();
            }else{
                $('#custom_html_tips').show();
            }
            if(result.cue_html == ''){
                $('#cue_html_tips').hide();
            }else{
                $('#cue_html_tips').show();
            }
            $('#custom_html').html(result.custom_html);
            $('#cue_html').html(result.cue_html);

            // 商品详情切换
            $('#form-group-detail-info').html('');
            item_content_obj = {};
            var editor_title = result['editor_title'];
            // var editor_title_count = result['editor_title_count'];
            for (var i in editor_title) {
                if (editor_title[i]) {
                    category_change_content(editor_title[i]);
                }
            }
        }else{
            // $('#choose_catid').html('<option value="0">{pigcms{:L_("请选择其他分类")}</option>');

            alert(result.msg);
            $('#choose_catfid option').eq(0).prop('selected',true);
            $('#choose_catfid').trigger('change');
        }
    });
});

$('#get_key_btn').click(function(){
    var s_name = $('input[name="s_name"]');
    s_name.val($.trim(s_name.val()));
    $('#keywords').val($.trim($('#keywords').val()));
    if(s_name.val().length == 0){
        alert('{pigcms{:L_("请先填写商品名称！")}');
        s_name.focus();
    }else if($('#keywords').val().length != 0){
        alert('{pigcms{:L_("请先删除您填写的关键词！")}');
        $('#keywords').focus();
    }else{
        $.get("{pigcms{:U('Index/Scws/ajax_getKeywords')}",{title:s_name.val()},function(result){
            result = $.parseJSON(result);
            if(result.num == 0){
                alert('{pigcms{:L_("您的商品名称没有提取到关键词，请手动填写关键词！")}');
                $('#keywords').focus();
            }else{
                $('#keywords').val(result.list.join(' ')).focus();
            }
        });
    }
});
$('#radio_open_pin').click(function(){
    $(this).attr('checked','checked');
    $('#radio_not_pin').removeAttr('checked');
    $('#open_pin').css('display','block');
    $('#not_pin').css('display','none');
});
$('#radio_not_pin').click(function(){
    $(this).attr('checked','checked');
    $('#radio_open_pin').removeAttr('checked');
    $('#open_pin').css('display','none');
    $('#not_pin').css('display','block');
});


$('#add_form').submit(function(){
    if (content_editor_object) {
        for (var i in content_editor_object) {
            if (content_editor_object[i]) {
                content_editor_object[i].sync();
            }
        }
    }
    $('#save_btn').prop('disabled',true);
    $.post("{pigcms{:U('Group/add')}",$('#add_form').serialize(),function(result){
        if(result.status == 1){
            alert(result.info);
            window.location.href = "{pigcms{:U('Group/index')}";
        }else{
            alert(result.info);
        }
        $('#save_btn').prop('disabled',false);
    })
    return false;
});

// "编辑器初始化")}]
var content_editor_object = {};
var group_content_switch = "{pigcms{$config['group_content_switch']}";
if (group_content_switch == 1 || group_content_switch == '1') {
    editor_initialization('content_0', 'editor_plan_btn_0');
} else {
    editor_initialization('content', 'editor_plan_btn');
}
function editor_initialization(index_id, editor_plan_index) {
    console.log('index_id', index_id);
    console.log('editor_plan_index', editor_plan_index);
    KindEditor.ready(function(K) {
        var content_editor = K.create("#" + index_id,{
            width:'702px',
            height:'260px',
            resizeType : 1,
            allowPreviewEmoticons:false,
            allowImageUpload : true,
            filterMode: true,
            autoHeightMode : true,
            afterCreate : function() {
                this.loadPlugin('autoheight');
            },
            items : [
                'fullscreen', '|', 'fontname', 'fontsize', '|','plainpaste', 'wordpaste', 'clearhtml', '|','forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
                'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
                'insertunorderedlist', '|', 'emoticons', 'image', 'multiimage', 'link', 'table'
            ],
            emoticonsPath : './static/emoticons/',
            uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=group/content&session_id={pigcms{:session_id()}",
            cssPath : "{pigcms{$static_path}css/group_editor.css",
        });
        content_editor_object[index_id] = content_editor;

        var editor = K.editor({
            allowFileManager : true
        });
        first_reload(K,editor);

        $('#' + editor_plan_index).click(function(){
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
}
var first = true;
function first_reload(K,editor) {
    if (!first) {
        return;
    }
    console.log('first',first);
    first = false;
    K('#J_selectImage').click(function(){
        if($('.upload_pic_li').size() >= 5){
            alert('{pigcms{:L_("最多上传5个图片！")}');
            return false;
        }
        editor.uploadJson = "{pigcms{:U('Group/ajax_upload_pic')}";
        editor.loadPlugin('image', function(){
            editor.plugin.imageDialog({
                showRemote : false,
                imageUrl : K('#course_pic').val(),
                clickFn : function(url, title, width, height, border, align) {
                    $('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="pic[]" value="'+title+'"/><br/><a href="#" onclick="deleteImage(\''+title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
                    editor.hideDialog();
                }
            });
        });
    });
}

function deleteImage(path,obj){
	$.post("{pigcms{:U('Group/ajax_del_pic')}",{path:path});
	$(obj).closest('.upload_pic_li').remove();
}
</script>
<include file="Public:footer"/>