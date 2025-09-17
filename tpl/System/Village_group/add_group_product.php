<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('add_group_product')}" frame="true" refresh="true" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<!--tr>
				<th width="160">是否开启预售</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$group['open_time'] eq 1">selected</if>"><span>开启</span><input type="radio" name="open_time" value="1" <if condition="$group['open_time'] eq 1">checked="checked"</if>"/></label></span>
					<span class="cb-disable"><label class="cb-disable  <if condition="$group['open_time'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="open_time" value="0"  <if condition="$group['open_time'] eq 0">checked="checked"</if>" /></label></span>
					<p style="display:block;clear:both;padding-top:10px;">默认商品购买方式为次日达，<br/>即当日下单，次日发货到自提点。<br/>开启预售即可设置该商品什么时候发货  什么时候到达</p>
				</td>
			</tr-->
            <input type="hidden" name="city_id" value="{pigcms{$city_id}">
			<tr>
				<th width="80">商品名称</th>
				<td><input type="text" class="input fl" name="name" id="name" size="50" value="{pigcms{$group.name}" placeholder=""  tips="" validate="required:true"/></td>
			</tr>
			
			<tr>
				<th width="80">所属分类</th>
				<td>
					<select name="cat_fid" id="cat_fid">
						<volist name="cates" id="catvo">
						<option value="{pigcms{$catvo.cat_id}" <if condition="$cat_fid && $cat_fid eq $catvo['cat_id']">selected="selected"</if>>{pigcms{$catvo.cat_name}</option>
						</volist>
					</select>
					<select name="cat_id" id="cat_id">
					</select>
				</td>
			</tr>
		
			
			<tr>
				<th width="80">商品描述</th>
				<td>
					<textarea class="fl" name="des" id="des" size="25" placeholder=""  tips="" rows="6" cols="50" validate="required:true">{pigcms{:replace_file_domain($group['des'])}</textarea>
				</td>
			</tr>
			
			<tr >
				<th width="80">图片详情</th>
				<td id="product_detail_td">
                    <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" style="display:inline-block;">上传图片</a>&nbsp;&nbsp;建议尺寸：500*500或其他尺寸正方形,拖拽图片即可调整顺序
                    <input type="hidden" class="input-image" name="pic_list" id="pic_list" value="<if condition="$group['pic_list'] neq ''">{pigcms{$group['pic_list']}<else />{pigcms{$group['image']}</if>"/>
                    <ul class="image_product_list" id="group_product_detail">

                    </ul>
                    <ul class="image_list" id="product_detail" style="display:none;">
						<if condition="$group.pic_list neq ''">
							<volist name="group.all_pic" id="vo">
								<li data-id="{pigcms{$vo.path_info}" class="upload_pic_li" style="float:left;list-style:none;"><img src="{pigcms{$vo.image}" style="width:90px;height:50px;margin-right:5px;"><br><a href="javascript:void(0)" onclick="del(this,{pigcms{$i})">[删除]</a></li>
							</volist>
						<else />
						
						</if>
                    </ul>
				</td>
				
			</tr>

            <if condition="!empty($group['goods_id'])">
                <tr>
                    <th width="80">商品类型</th>
                    <td>
                        <if condition="0 eq $group['village_group_type']">
                            普通商品
                            <else/>
                            周期购商品
                        </if>
                        <input type="hidden" name="village_group_type" value="{pigcms{$group['village_group_type']}" />
                    </td>
                </tr>
                <else/>

                <tr>
                    <th width="80">商品类型</th>
                    <td>
                        <select name="village_group_type" id="village_group_type">
                            <option value="0" <if condition="0 eq $cycle_info['village_group_type']">selected</if>>普通商品</option>
                            <option value="1" <if condition="1 eq $cycle_info['village_group_type']">selected</if>>周期购商品</option>
                        </select>
                    </td>
                </tr>
            </if>


            <tr <if condition="$group['village_group_type'] eq 1">style="line-height: 28px;"<else/>style="display: none;line-height: 28px;" </if> class="cycle_info">
                <th width="80">周期购设置</th>
                <td>
                    <div>
                        <div class="fl" style="width: 100px;"><span style="color: red;">*</span>配送频次</div>
                        <div class="fl">
                            <label style="float:left;width:60px" class="checkbox_status"><input type="radio" class="input_radio" name="cycle_type" <if condition="1 eq $cycle_info['cycle_type']">checked="checked"<elseif condition="0 eq $cycle_info['cycle_type']"/>checked="checked"</if> value="1" validate=" maxlength:1" /> 每日</label>
                            <label style="float:left;width:60px" class="checkbox_status"><input type="radio" class="input_radio" name="cycle_type" <if condition="2 eq $cycle_info['cycle_type']">checked="checked"</if> value="2" /> 每周</label>
                            <label style="float:left;width:60px" class="checkbox_status"><input type="radio" class="input_radio" name="cycle_type" <if condition="3 eq $cycle_info['cycle_type']">checked="checked"</if> value="3" /> 每月</label>
                        </div>
                    </div>
                    <div style="clear: both;">
                        <div class="fl" style="width: 100px;"><span style="color: red;">*</span>配送日期</div>
                        <div class="fl cycle_type_1" <if condition="$cycle_info && $cycle_info['cycle_type'] neq 1">style="display: none;"</if>>
                            <div  class="delivery_day_date" data-value="all" data-type="1">每天</div>
                        </div>
                        <div class="fl cycle_type_2" <if condition="!$cycle_info || $cycle_info['cycle_type'] neq 2">style="display: none;"</if>>
                            <volist name="choose_date['week']" id="item">
                                <div class="delivery_date fl <if condition="in_array($key, $cycle_info['delivery_date_arr'])">active</if>" data-value="{pigcms{$key}" data-type="2">{pigcms{$item}</div>
                            </volist>
                            <div class="delivery_date fl <if condition="$cycle_info['is_all_date'] eq 1">active</if>" data-value="all" data-type="2">全选</div>
                        </div>
                        <div class="fl cycle_type_3" <if condition="$cycle_info && $cycle_info['cycle_type'] eq 3">style="margin-top: 5px;"<else/> style="display: none;margin-top: 5px;" </if>>
                            <volist name="choose_date['date']" id="item">
                                <div class="delivery_date fl <if condition="in_array($key, $cycle_info['delivery_date_arr'])">active</if>" data-value="{pigcms{$key}" data-type="3">{pigcms{$item}</div>
                            </volist>
                            <div class="delivery_date fl <if condition="$cycle_info['is_all_date'] eq 1">active</if>" data-value="all" data-type="3">全选</div>
                        </div>
                    </div>
                    <div style="clear: both;">
                        <div class="fl" style="width: 100px;"><span style="color: red;">*</span>下单时间限制</div>
                        <div class="">
                            <span>买家需提前</span>
                            <input type="number" min="0" class="input" name="limit_days" value="{pigcms{$cycle_info['limit_days']}" size="5" style="width: 5rem"/>
                            <span>天，并在</span>
                            <select name="limit_time" id="limit_time">
                                <volist name="choose_date['limit_time']" id="item">
                                    <option value="{pigcms{$item}" <if condition="$item eq $cycle_info['limit_time']">selected<elseif condition="$cycle_info['limit_time'] eq '' AND $item eq 18"/>selected</if>>{pigcms{$item}</option>
                                </volist>
                            </select>
                            <span>时前下单支付，才能在最近的配送时间送达</span>
                        </div>
                    </div>
                    <div style="clear: both;">
                        <div class="fl" style="width: 100px;">&nbsp;</div>
                        <span style="color: red;">该时间会同时默认为备货时间，系统会结合每次配送时间，提前生成一条待发货订单</span>
                    </div>

                    <div style="clear: both;">
                        <div class="fl" style="width: 100px;"><span style="color: red;">*</span>顺延次数</div>
                        <label style="float:left;width:60px" class="checkbox_status"><input type="radio" class="input_radio" name="is_limit_delay" <if condition="0 eq $cycle_info['is_limit_delay']">checked="checked"<elseif condition="0 eq $cycle_info['cycle_type']"/>checked="checked"</if> value="0"/> 不限制</label>
                        <label style="float:left;width:60px" class="checkbox_status"><input type="radio" class="input_radio" name="is_limit_delay" <if condition="1 eq $cycle_info['is_limit_delay']">checked="checked"</if> value="2" /> 限制</label>

                        <input type="number" min="0" class="input" name="limit_delay_num" value="{pigcms{$cycle_info['limit_delay_num']}" size="5" style="width: 5rem"/>次
                    </div>
                    <div style="clear: both;">
                        <div class="fl" style="width: 100px;">&nbsp;</div>
                        <span style="color: red;">顺延次数记录该笔订单的累计顺延次数，0代表不允许顺延</span>
                    </div>

                    <div style="clear: both;">
                        <div class="fl" style="width: 100px;"><span style="color: red;">*</span>配送期数</div>
                        <input type="number" min="1" class="input" value="{pigcms{$cycle_info['delivery_periods']}" name="delivery_periods" />
                    </div>
                    <input type="hidden" name='delivery_date' id="delivery_date_text" value="{pigcms{$cycle_info['delivery_date']}">
                    <input type="hidden" name='is_all_date' id="is_all_date" value="{pigcms{$cycle_info['is_all_date']}">
                </td>
            </tr>
			<if condition="$_GET['copy'] eq 1">
                <tr>
                    <th width="80">搜索商家</th>
                    <td><input type="text" class="input fl" name="searchMer" id="searchMer" size="25" value="{pigcms{$group.merchant}" placeholder="请输入商家名称" /><em class="notice_tips" tips="编辑商品时，商家信息不可修改"></em><b class="tips"></b></td>
                </tr>
            <else/>
                <tr>
                    <th width="80">搜索商家</th>
                    <td><input type="text" class="input fl" name="searchMer" id="searchMer" size="25" <if condition="$group.goods_id && $group.goods_id gt 0"> value="{pigcms{$group.merchant}" disabled="disabled" <else/>value=""</if> placeholder="请输入商家名称" /><em class="notice_tips" tips="编辑商品时，商家信息不可修改"></em><b class="tips"></b></td>
                </tr>
            </if>
			
			
			<tr>
				<th width="80">商品佣金</th>
				<td><input type="text" class="input fl" name="leader_money" id="leader_money" size="25" value="{pigcms{$group.leader_money|default='0'}" placeholder="" tips="可以不设置，不设置的情况下，团长佣金按团长的佣金配置计算，配置了就按商品独立的佣金配置计算，20  表示单位商品分给团长的佣金比例为百分之20（商品售价的百分比），0代表不设置 " validate="required:true"/>
				</td>
			</tr>

			<tr>
				<th width="80">是否属于新人专享</th>
				<td><span class="cb-enable"><label class="cb-enable <if condition="$group['is_new_user'] eq 1">selected</if>"><span>是</span><input type="radio" name="is_new_user" value="1" <if condition="$group['is_new_user'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$group['is_new_user'] eq 0">selected</if>"><span>否</span><input type="radio" name="is_new_user" value="0" <if condition="$group['is_new_user'] eq 0">checked="checked"</if> /></label></span>
					<p style="display:block;clear:both;padding-top:10px;">若选择是，该商品只能新用户购买。</p>
					<div id="new_user_limit_buy" style="display:<if condition="$group['is_new_user'] eq 0">none;<else/>block;</if>"><div class="fl">每人限购数：</div><input type="text" class="input fl" name="new_user_limit_buy" value="{pigcms{$group.new_user_limit_buy|default='0'}" placeholder="每人限购数" /></div>
				</td>
			</tr>
			
			<tr >
				<th width="160">开始时间</th>
				<td>
				<input type="text" class="input fl" name="start_time" size="25" placeholder="开始时间" value="<if condition="$group.start_time gt 0">{pigcms{$group.start_time|date='Y-m-d H:i',###}</if>" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" validate="required:true" />
				<em class="notice_tips" tips="商品启售时时间,必填"></em>
				</td>
			</tr>
			
			<tr>
				<th width="160">结束时间</th>
				<td>
				<input type="text" class="input fl" name="end_time" size="25" value="<if condition="$group.end_time gt 0">{pigcms{$group.end_time|date='Y-m-d H:i',###}</if>" placeholder="结束时间" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" />
				<em class="notice_tips" tips="商品结束时间，当自动上下架选择为“是”时必填。"></em>
				</td>
			</tr>
			<tr>
				<th width="80">是否自动上下架</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$group['is_auto_offline'] eq 1">selected</if>"><span>是</span><input type="radio" name="is_auto_offline" value="1" <if condition="$group['is_auto_offline'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$group['is_auto_offline'] eq 0">selected</if>"><span>否</span><input type="radio" name="is_auto_offline" value="0" <if condition="$group['is_auto_offline'] eq 0">checked="checked"</if> /></label></span>
					<p style="display:block;clear:both;padding-top:10px;">若选择是，则跟销售时间绑定；若选择否，除非设置结束时间，否则一直售卖。</p>
				</td>
			</tr>
			<tr id="arrive_type" <if condition="$group['village_group_type'] eq 1">style="display:none;"</if>>
				<th width="80">到达类型</th>
				<td>
					<select name="arrive_type">
						<option value="1" <if condition="$group['arrive_type'] eq 1">selected="selected"</if>>当日达</option>
						<option value="2" <if condition="$group['arrive_type'] eq 2">selected="selected"</if>>次日达</option>
						<option value="3" <if condition="$group['arrive_type'] eq 3">selected="selected"</if>>隔日达</option>
						<option value="4" <if condition="$group['arrive_type'] eq 4">selected="selected"</if>>自定义间隔</option>
						<option value="5" <if condition="$group['arrive_type'] eq 5">selected="selected"</if>>固定日期</option>
					</select>
				</td>
			</tr>

			<tr id="arrive_days" <if condition="$group['village_group_type'] eq 1">style="display:none;"</if>>
				<th width="80">时间设置</th>
				<td>
					<div id="normal_box" style="display: <if condition="in_array($group['arrive_type'],[1,2,3]) || empty($group['arrive_type'])">block<else />none</if>;">
						截单时间：<input type="text" class="input" size="15" name="arrive_cutting_time_normal" value="{pigcms{$group['arrive_cutting_time']}" placeholder="截单时间" onfocus="WdatePicker({readOnly:true,dateFmt:'HH:mm'})" />
						到达时间：<input type="text" class="input" size="15" name="arrive_time_normal" value="{pigcms{$group['arrive_time']}" placeholder="到达时间" onfocus="WdatePicker({readOnly:true,dateFmt:'HH:mm'})" />
					<em class="notice_tips" tips="选择截单时间后，用户在截单时间后下单自动到到达时间的第二天送达，截单时间必须小于到达时间"></em>
					</div> 
					<div id="number_box" style="display: <if condition="in_array($group['arrive_type'],[4])">block<else />none</if>;">
						间隔天数：<input type="text" class="input" size="15" name="arrive_days" value="{pigcms{$group['arrive_days']}" placeholder="填一个数字" />
						截单时间：<input type="text" class="input" size="15" name="arrive_cutting_time_normal4" value="{pigcms{$group['arrive_cutting_time']}" placeholder="截单时间" onfocus="WdatePicker({readOnly:true,dateFmt:'HH:mm'})" />
						到达时间：<input type="text" class="input" size="15" name="arrive_time_normal4" value="{pigcms{$group['arrive_time']}" placeholder="到达时间" onfocus="WdatePicker({readOnly:true,dateFmt:'HH:mm'})" />
					<em class="notice_tips" tips="确定截单时间后，用户在截单时间之后下单，则在到达时间的次日送达"></em>
					</div>
					<div id="arrive_box" style="display: <if condition="in_array($group['arrive_type'],[5])">block<else />none</if>;">
						截单时间：<input type="text" class="input" size="25" name="arrive_cutting_datetime" value="<notempty name="group['arrive_cutting_datetime']">{pigcms{$group['arrive_cutting_datetime']}</notempty>" placeholder="截单时间" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" />
						到达时间：<input type="text" class="input" size="25" name="arrive_datetime" value="<notempty name="group['arrive_datetime']">{pigcms{$group['arrive_datetime']}</notempty>" placeholder="到达时间" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" />
						<em class="notice_tips" tips="该商品在截单时间后，只能查看，不能购买"></em>
					</div>
				</td>
			</tr>
			
            <!--<tr id="send_time" <if condition="$group['village_group_type'] eq 1">style="display:none;"</if>>
				<th width="160">发货时间</th>
				<td>
				<input type="text" class="input fl" name="send_time" size="25" value="<if condition="$group.send_time gt 0">{pigcms{$group.send_time|date='Y-m-d',###}</if>" placeholder="发货时间" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})" />
				<em class="notice_tips" tips="发货时间"></em>
				</td>
			</tr>
			
			 <tr id="get_time" <if condition="$group['village_group_type'] eq 1">style="display:none;"</if>>
				<th width="160">到达时间</th>
				<td>
				<input type="text" class="input fl" name="get_time" size="25" value="<if condition="$group.get_time gt 0">{pigcms{$group.get_time|date='Y-m-d',###}</if>" placeholder="到达时间" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})" validate="required:true" />
				<em class="notice_tips" tips="到达时间不同，用户前台看到该商品的到达类型也不同，比如到达时间=用户下单时间当日，到达类型为当日达；如果到达时间=用户下单时间次日，到达类型为次日达；如果到达时间>用户下单时间的次日，到达类型为预售；注意：用户的下单时间是会不断变动的，且商品结束时间只可设置≤到达时间。"></em>
				</td>
			</tr> -->
		
			<tr>
				<th width="80">商品标签</th>
				<td>
					<select name="label">
						<option value="0" <if condition="0 eq $group['label']">selected</if>>不选标签</option>
						<volist name="label" id="vo">
							<option value="{pigcms{$vo.id}" <if condition="$vo['id'] eq $group['label']">selected</if>>{pigcms{$vo.name} <if condition="$vo.type eq 1">【图片】<else />【文字】</if></option>
						</volist>
					</select>
				</td>
			</tr>
			
			<tr>
				<th width="80">是否是自营</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$group['own_sell'] eq 1">selected</if>"><span>是</span><input type="radio" name="own_sell" value="1" <if condition="$group['own_sell'] eq 1">checked="checked"</if>"/></label></span>
					<span class="cb-disable"><label class="cb-disable  <if condition="$group['own_sell'] eq 0">selected</if>"><span>否</span><input type="radio" name="own_sell" value="0"  <if condition="$group['own_sell'] eq 0">checked="checked"</if>" /></label></span>
					<p style="display:block;clear:both;padding-top:10px;">选择是，商品详情会有自营提示，商品提供者将显示平台的供货信息;选择否,商品提供者将显示商家名称</p>
				</td>
			</tr>
			<tr>
				<th width="80">是否商家发货</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$group['is_merchant_send'] eq 1">selected</if>"><span>是</span><input type="radio" name="is_merchant_send" value="1" <if condition="$group['is_merchant_send'] eq 1">checked="checked"</if>"/></label></span>
					<span class="cb-disable"><label class="cb-disable  <if condition="$group['is_merchant_send'] eq 0">selected</if>"><span>否</span><input type="radio" name="is_merchant_send" value="0"  <if condition="$group['is_merchant_send'] eq 0">checked="checked"</if>" /></label></span>
					<p style="display:block;clear:both;padding-top:10px;">商家发货，即该商品的订单由商家端发货给团长，商家可在商家后台查看具体订单信息；设置该商品为商家发货类型前，请先绑定商家ID</p>
				</td>
			</tr>
			
			
				
			<tr>
				<th width="80">原始库存</th>
				<td><input type="text" class="input fl" name="stock_num" id="stock_num" size="25" value="{pigcms{$group.stock_num}" placeholder=""  tips="请设置大于0的数字" validate="required:true"/></td>
			</tr>
			
			<tr>
				<th width="80">虚拟销量</th>
				<td><input type="text" class="input fl" name="virtual_sell_count" id="virtual_sell_count" size="25" value="{pigcms{$group.virtual_sell_count}" placeholder=""  tips="请设置大于0的数字"/></td>
			</tr>
			
			<tr>
				<th width="80">进价</th>
				<td><input type="text" class="input fl" name="purchase_price" id="purchase_price" size="25" value="{pigcms{$group.purchase_price}" placeholder="" tips="商品成本价，计算收益的依据" validate="required:true"/>
				</td>
			</tr>
			
			<tr>
				<th width="80">售价</th>
				<td><input type="text" class="input fl" name="price" id="price" size="25" value="{pigcms{$group.price}" placeholder=""  tips="" validate="required:true"/></td>
			</tr>
			
			<tr>
				<th width="80">原价</th>
				<td><input type="text" class="input fl" name="old_price" id="old_price" size="25" value="{pigcms{$group.old_price}" placeholder="" tips="原价小于等于售价则不显示" validate="required:true"/></td>
			</tr>

            <tr>
                <th width="80">重量</th>
                <td><input type="text" class="input fl" name="goods_weight" id="goods_weight" size="25" value="{pigcms{$group.goods_weight}" placeholder="" tips="商品重量（单位：kg）"/></td>
            </tr>
            
			<tr>
				<th width="80">打包费</th>
				<td><input type="text" class="input fl" name="packing_fee" id="packing_fee" size="25" value="{pigcms{$group.packing_fee}" placeholder="" tips="不设置或设置为0，则不需要打包费" /></td>
			</tr>
			
			<tr>
				<th width="80">排序值</th>
				<td><input type="text" class="input fl" name="sort" id="sort" size="25" value="{pigcms{$group.sort|default=0}" placeholder="" tips="排序值越高，排序越前。排序值大于0，团购分享图片页面中会显示推广单图。" validate="required:true"/></td>
			</tr>
			<!--这个功能目前无法使用先隐藏<tr>
				<th width="80">抽成比例</th>
				<td><input type="text" class="input fl" name="take_a_percentage" id="take_a_percentage" size="25" value="{pigcms{$group.take_a_percentage|default=0}" placeholder="" tips="请设置一个0-100的数字" /></td>
			</tr>-->
			<tr>
				<th width="80">积分设置</th>
				<td>
                    <input type='radio' name='score_set_type' value="1" onclick="score_set()" <if condition="$group['score_percent'] egt 0">checked</if>/>&nbsp;设置&nbsp;&nbsp;
                    <input type='radio' name='score_set_type' value="0" onclick="score_set()" <if condition="$group['score_percent'] eq -1">checked</if>/>&nbsp;跳过
                    <p id='score_set_p1'>
                        <input type="text" class="input fl" name="score_percent" id="score_percent" size="25" value="<php>echo $group['score_percent'] >=0 ?$group['score_percent']:0;</php>" placeholder="" tips="消费1元所获得积分值"/>
                    </p>
                    <p id='score_set_p0'>跳过，则读取平台积分设置</p>
                </td>
			</tr>
            <script type='text/javascript'>
                
            </script>

			
			<tr>
				<th width="80">商品详情</th>
				<td width="240"><textarea class="input-text" id="content" name="content" style="width: 100px; height: 300px; ">{pigcms{:replace_file_domain($group['content'])}</textarea></td>
			</tr>


            <if condition="!empty($levelarr)">

                <tr>
                    <th width="80">会员优惠</th>
                    <td style="color:red;width:240px;">说明：必须设置一个会员等级优惠类型和优惠类型对应的数值，我们将结合优惠类型和所填的数值来计算该商品会员等级的优惠的幅度！</td>
                </tr>
                <volist name="levelarr" id="vv">
                    <tr>
                        <input  name="leveloff[{pigcms{$vv['level']}][lid]" type="hidden" value="{pigcms{$vv['id']}"/>
                        <input  name="leveloff[{pigcms{$vv['level']}][lname]" type="hidden" value="{pigcms{$vv['lname']}"/>
                        <th width="80"></th>
                        <td style="width:240px;">
                            <div class="fl" style="width: 80px;font-weight: bold;">{pigcms{$vv['lname']}：&nbsp;</div>
                            优惠类型：&nbsp;
                            <select name="leveloff[{pigcms{$vv['level']}][type]">
                                <option value="0" <if condition="$level_off && $level_off[$vv['level']] && $level_off[$vv['level']]['type'] eq 0">selected</if>>无优惠</option>
                                <option value="1" <if condition="$level_off && $level_off[$vv['level']] && $level_off[$vv['level']]['type'] eq 1">selected</if>>百分比（%）</option>
                                <option value="2" <if condition="$level_off && $level_off[$vv['level']] && $level_off[$vv['level']]['type'] eq 2">selected</if>>立减</option>
                            </select>
                            <input type="text" class="input" name="leveloff[{pigcms{$vv['level']}][vv]" id="leveloff_value" value="<if condition="$level_off && $level_off[$vv['level']]">{pigcms{$level_off[$vv['level']]['vv']}</if>" size="25" placeholder="请填写一个优惠值数字" onkeyup="value=value.replace(/[^1234567890]+/g,'')" tips="填写数字即可，无需%符号，填写50代表5折，0与100代表没有折扣。"/>
                        </td>
                    </tr>
                </volist>
            </if>

            <tr>
                <th width="80">佣金推广比例设置</th>
                <td style="color:red;width:240px;">分享商品获取的佣金</td>
            </tr>
            <tr>
                <th width="80"></th>
                <td style="width:240px;">
                    直接推广用户分享佣金金额：&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="text" class="input" name="first_share_money" id="first_share_money" value="{pigcms{$group.first_share_money|default=0.00}" tips="直接推广用户分享佣金金额0.00"/>
                </td>
            </tr>
            <tr>
                <th width="80"></th>
                <td style="width:240px;">
                    直接推广用户的上级分享佣金金额：&nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="text" class="input" name="second_share_money" id="second_share_money" value="{pigcms{$group.second_share_money|default=0.00}" tips="直接推广用户的上级分享佣金金额0.00"/>
                </td>
            </tr>
            <tr>
                <th width="80"></th>
                <td style="width:240px;">
                    直接推广用户的上上级分享佣金金额：&nbsp;
                    <input type="text" class="input" name="thild_share_money" id="thild_share_money" value="{pigcms{$group.thild_share_money|default=0.00}" tips="直接推广用户的上上级分享佣金金额0.00"/>
                </td>
            </tr>
      <if condition="C('customization.village_group_user_share_commission')">
            <tr>
                <th width="80"></th>
                <td style="width:240px;">
                    社区团购直接推广用户的分享佣金金额：&nbsp;
                    <input type="text" class="input" name="user_share_money" id="user_share_money" value="{pigcms{$group.user_share_money|default=0.00}" tips="社区团购直接推广用户的分享佣金金额 0.00"/>
                </td>
            </tr>
      </if>

            <!------------------------------- 开启分城市管理后 start------------------------------->
            <if condition="$city_manage eq 1 and $level eq 1">
                 <tr>
                     <th width="160">适用社区</th>
                     <td>
                         <select name="buy_user_type" class="buy_user_type" style="width:150px;">
                             <option value="0"
                             <if condition="$goods_detail['buy_user_type'] eq 0"> selected="selected"</if>
                             >所有社区</option>
                             <option value="1"
                             <if condition="$goods_detail['buy_user_type'] eq 1"> selected="selected"</if>
                             >部分社区</option>
                             <option value="2"
                             <if condition="$goods_detail['buy_user_type'] eq 2"> selected="selected"</if>
                             >部分社区不参与</option>
                         </select>
                     </td>
                 </tr>

                 <tr class="address_list"
                 <if condition="$goods_detail['buy_user_type'] eq 0">style="display:none;"</if>>
                 <th width="120">社区选择：</th>
                 <td>
                     <div style="float: left; border: 1px solid #ccc; padding: 2px 3px; cursor: pointer;" onclick="addFans()"
                          class="a_choose class_1">添加社区
                     </div>&nbsp;&nbsp;<a href="###" onclick="clearUsers()" class="a_clear class_1">清空重选</a>
                     <div class="tags class_1" id="tags" tabindex="1">
                         <if condition="!empty($village_list)">
                             <volist name="village_list" id="vo">
                                 <span class='tag'>{pigcms{$vo.name}<a class='close'
                                                                       data-uid="{pigcms{$vo.id}">X</a></span><br>
                             </volist>
                         </if>
                         <input id="form-field-tags" type="text" placeholder="请输入标签 ..." value="Tag Input Control"
                                name="tags" style="display: none;"/>

                         <!-- <input type="text"  class="tags_enter" autocomplete="off"> -->

                        <input type="hidden" class="px" id="uids" value="{pigcms{$goods_detail.address_list}"
                               name="address_list" style="width:300px">
                    </div>
                </td>
                </tr>
    <!--            <tr>-->
    <!--                <th width="80">售价</th>-->
    <!--                <td><input type="text" class="input fl" name="price" value="{pigcms{$goods_detail.area_price}"-->
    <!--                           validate="required:true" tips=""/></td>-->
    <!--            </tr>-->
                <tr>
                    <th width="80">团长是否可以加价</th>
                    <td>
                        <span class="cb-enable"><label class="cb-enable <if condition=" $goods_detail['is_add_price'] eq 1">selected</if>
                            "><span>是</span><input type="radio" name="is_add_price" value="1" <if
                                    condition="$goods_detail['is_add_price'] eq 1">checked="checked"</if>/></label></span>
                        <span class="cb-disable"><label class="cb-disable  <if condition=" $goods_detail['is_add_price'] eq 0">selected</if>
                            "><span>否</span><input type="radio" name="is_add_price" value="0"  <if
                                    condition="$goods_detail['is_add_price'] eq 0">checked="checked"</if>/></label></span>
                        <p style="display:block;clear:both;padding-top:10px;"></p>
                    </td>
                </tr>
                <tr class="price_interval"
                <if condition="$goods_detail['is_add_price'] eq 0">style="display: none;"</if>>
                <th width="80">加价区间</th>
                <td>
                    <input type="text" class="input" size="10" name="min_price" value="{pigcms{$goods_detail.min_price}"/> -
                    <input type="text" class="input" size="10" name="max_price" value="{pigcms{$goods_detail.max_price}"
                           tips="团长可以在此区间内给商品加价出售"/>
                </td>
                </tr>
            </if>
            <!---------------------------- 开启分城市管理后 end --------------------------------->

		</table>
		<div class="btn hidden">
            <input type="hidden" id="is_copy" name="is_copy" value="{pigcms{$_GET['copy']}" >
			<input type="hidden" id="goods_id" name="goods_id" value="{pigcms{$_GET['goods_id']}" >
			<input type="hidden" id="mer_group_id" name="group_id" value="{pigcms{$group['group_id']}" >
			<input type="hidden" id="mer_id" name="mer_id" value="{pigcms{$group['mer_id']}">
			<input type="hidden" id="image" name="image" value="{pigcms{$group.image}" >
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<style>
    .time {
        display: none;
    }

    .tags {
        background-color: #fff;
        border: 1px solid #d5d5d5;
        color: #777;
        padding: 4px 6px;
        width: 406px;
        /*height: 24px;*/
        margin: 12px 0px 0px 0px;
    }

    .tags:hover {
        border-color: #f59942;
        outline: 0 none;
    }

    .tags[class*="span"] {
        float: none;
        margin-left: 0;
    }

    .tags input[type="text"], .tags input[type="text"]:focus {
        border: 0 none;
        box-shadow: none;
        display: inline;
        line-height: 22px;
        margin: 0;
        outline: 0 none;
        padding: 4px 6px;
    }

    .tags .tag {
        background-color: #91b8d0;
        color: #fff;
        display: inline-block;
        font-size: 12px;
        font-weight: normal;
        margin-bottom: 3px;
        margin-right: 3px;
        padding: 4px 22px 5px 9px;
        position: relative;
        text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.15);
        transition: all 0.2s ease 0s;
        vertical-align: baseline;
        white-space: nowrap;
    }

    .tags .tag .close {
        bottom: 0;
        color: #fff;
        float: none;
        font-size: 12px;
        line-height: 20px;
        opacity: 1;
        position: absolute;
        right: 0;
        text-align: center;
        text-shadow: none;
        top: 0;
        width: 18px;
    }

    .tags .tag .close:hover {
        background-color: rgba(0, 0, 0, 0.2);
    }

    .close {
        color: #000;
        float: right;
        font-size: 21px;
        font-weight: bold;
        line-height: 1;
        opacity: 0.2;
        text-shadow: 0 1px 0 #fff;
    }

    .close:hover, .close:focus {
        color: #000;
        cursor: pointer;
        opacity: 0.5;
        text-decoration: none;
    }

    button.close {
        background: transparent none repeat scroll 0 0;
        border: 0 none;
        cursor: pointer;
        padding: 0;
    }

    .tags .tag-warning {
        background-color: #ffb752;
    }
    .delivery_date {
        background-color: white;
        color: #0a0a0a;
        border: #b6b4a4 1px solid;
        padding: 0 8px;
        margin-bottom: 5px;
        cursor:pointer;
        margin-right: 5px;
    }
    .delivery_date.active{
        color: #067ADE;
        border: #067ADE 1px solid;;
    }
    .delivery_day_date {
        background-color: white;
        color: #067ADE;
        border: #067ADE 1px solid;;
        padding: 0 8px;
        margin-bottom: 5px;
        cursor:pointer;
        margin-right: 5px;
    }
</style>
	<link rel="stylesheet" href="{pigcms{$static_public}js/jquery-ui/jquery-ui.css">
	<script type="text/javascript" src="{pigcms{$static_public}js/jquery-ui/jquery-ui.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css"/>
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	
	<script type="text/javascript" src="./static/js/webuploader.min.js"></script>
    <script type="text/javascript" src="./static/js/sortable.min.js"></script>
	<style>
		.webuploader-container {
			position: relative;
		}
		.webuploader-element-invisible {
			position: absolute !important;
			clip: rect(1px 1px 1px 1px); /* IE6, IE7 */
			clip: rect(1px,1px,1px,1px);
		}
		.webuploader-pick {
			position: relative;
			display: inline-block;
			cursor: pointer;
			background: #87b87f!important;
			padding: 5px 15px;
			color: #fff;
			text-align: center;
			border-radius: 3px;
			overflow: hidden;
		}
		.webuploader-pick-hover {
			background: #00a2d4;
		}

		.webuploader-pick-disable {
			opacity: 0.6;
			pointer-events:none;
		}
	</style>
	
	<script type="text/javascript">
		function show_son_category(son_selected=0){
			var cat_fid = $('#cat_fid').val();
			$.post("{pigcms{:U('ajax_get_son_category')}",{'cat_fid':cat_fid},function(data){
				if(data.status == 0){
	                if (data.info) {
	                    alert(data.info);
	                } else {
	                    alert('数据错误！');
	                }
				}else{
					if(data.info.length != 0) {
	                    $('#cat_id').show();
	                    $('#cat_id').html('');
	                    creat_html(data.info,son_selected);
	                } else {
	                	$('#cat_id').html('');
	                    $('#cat_id').hide();
	                }
				}
			});
		}
		$('#cat_fid').change(function(){
			show_son_category();
		})
		function creat_html(list,son_selected){
			var html = '';
			console.log(list);
			for (var i = 0; i <list.length; i++) {
				console.log(list[i]['cat_id']);
				console.log(son_selected);
				if(son_selected==list[i]['cat_id']){
					html += '<option value="'+list[i]['cat_id']+'" selected="selected">'+list[i]['cat_name']+'</option>';
				}else{
					html += '<option value="'+list[i]['cat_id']+'">'+list[i]['cat_name']+'</option>';
				}
			}
			console.log(html);
			$('#cat_id').append(html);
		}
		var cat_id_default = "{pigcms{$group.cat_id|intval}";
		cat_id_default = parseInt(cat_id_default);
		show_son_category(cat_id_default);
        var sortableEl = document.getElementById('product_detail');
        //常用
        new Sortable(sortableEl, {   
            // handle: ".my-handle", // 拖拽区域，默认为 items 的 子元素
            draggable: '.upload_pic_li',
            onStart: function (/**Event*/evt) { // 拖拽开始
                var itemEl = evt.item;// 当前拖拽的html元素
            },
        
            onEnd: function (/**Event*/evt) { // 拖拽结束
                var itemEl = evt.item;
                let pic_list_data = $('#pic_list').val();
                let pic_list_params = [];
                pic_list_data = pic_list_data.split(';')
                if($('#product_detail').children()){
                    $('#product_detail').children().each(function(i,v){
                        pic_list_params.push($(v).data().id)
                    })
                    $('#pic_list').val(pic_list_params.join(';'));
                }
            }    
        });
	</script>
	<script type="text/javascript">
        var content_editor;
        $('#village_group_type').change(function(){
            if($(this).val()==1){
                $('.cycle_info').show();
                $('#get_time').hide();
                $('#send_time').hide();
                $('#arrive_type').hide();
                $('#arrive_days').hide();
            }else{
                $('.cycle_info').hide();
                $('#get_time').show();
                $('#send_time').show();
                $('#arrive_type').show();
                $('#arrive_days').show();
            }
        });

        $('[name="arrive_type"]').change(function(){
        	var _val = $(this).find(":selected").val();
        	if(_val == 1 || _val == 2 || _val == 3){
        		if(_val == 1){
        			$("#normal_box").find(".tips_img").attr("title", '确定截单时间后，用户在截单时间之后下单，则在到达时间的次日送达，截单时间必须小于到达时间');
        		}
        		else{
        			$("#normal_box").find(".tips_img").attr("title", '确定截单时间后，用户在截单时间之后下单，则在到达时间的次日送达');	
        		}
        		$("#normal_box").show();
        		$("#number_box").hide();
        		$("#arrive_box").hide();
        	}
        	else if(_val == 4){
        		$("#normal_box").hide();
        		$("#number_box").show();
        		$("#arrive_box").hide();	
        	}
        	else if(_val == 5){
        		$("#normal_box").hide();
        		$("#number_box").hide();
        		$("#arrive_box").show();	
        	}
        });

        $("#searchMer").autocomplete({
	      source: "{pigcms{:U('searchMerchant')}",
	      minLength: 1,
	      select: function( event, ui ) {
	        $("#mer_id").val(ui.item.id);
	      }
	    });
	    $("#searchMer").keyup(function(event) {
	    	$("#mer_id").val(0);//必须让上面联想选择
	    });

        $('input[name="cycle_type"]').click(function () {
            if ($(this).val() == 1) {
                $('.cycle_type_1').show();
                $('.cycle_type_2').hide();
                $('.cycle_type_3').hide();
                $('.cycle_type_1').children('div').removeClass('active');
            } else if ($(this).val() == 2) {
                $('.cycle_type_1').hide();
                $('.cycle_type_2').show();
                $('.cycle_type_3').hide();
                $('.cycle_type_2').children('div').removeClass('active');
            }else{
                $('.cycle_type_1').hide();
                $('.cycle_type_2').hide();
                $('.cycle_type_3').show();
                $('.cycle_type_3').children('div').removeClass('active');
            }
            $('#delivery_date_text').val('');
            $('#is_all_date').val(0);
        });
        // 点击选择日期
        $('.delivery_date').click(function ()  {
            var value = $(this).data('value');
            var is_active = $(this).hasClass('active');
            if (is_active) {
                if ('all'==value) {
                    $(this).parent().children('div').removeClass('active');
                } else {
                    $(this).parent().children('div[data-value="all"]').removeClass('active');
                }
                $('#is_all_date').val(0);
                $(this).removeClass('active');
            }else{
                if ('all'==value) {
                    $('#is_all_date').val(1);
                    $(this).parent().children('div').addClass('active');
                }
                $(this).addClass('active');
            }
            var date_arr = [];
            var num=0;// 选中数量
            var node = $(this).parent().children('div');
            node.each(function(i,val){
                var val_active = $(val).hasClass('active');
                if (val_active && $(val).data('value') != 'all') {
                    date_arr.push($(val).data('value'));
                    num +=1;
                }
            });
            var type = $(this).data('type');
            var all_active = $(this).parent().children('div[data-value="all"]').hasClass('active');
            if (2==type && 7==num && !all_active) {
                $(this).parent().children('div[data-value="all"]').addClass('active');
                $('#is_all_date').val(1);
            } else if(3==type && 30==num && !all_active){
                $(this).parent().children('div[data-value="all"]').addClass('active');
                $('#is_all_date').val(1);
            }
            date_arr = date_arr.join(',');
            $('#delivery_date_text').val(date_arr);
        });



        // $('#dosubmit').click(function(){
        //     content_editor.sync();
        //     var slA = $('#myform').serializeArray();
        //     console.log('slA', slA);
        //     $.post($('#myform').attr('action'),slA,function(result){
        //         console.log('result', result);
        //         console.log('status', result.status);
        //         if(result.status == 0){
        //             console.log('info', result.info);
        //             alert(result.info)
        //         }else{
        //             window.top.msg(1,result.info,true,3);
        //             window.top.main_refresh();
        //             window.top.closeiframe();
        //         }
        //     }, 'json');
        //     return false;

        // });


		KindEditor.ready(function(K){
			kind_editor = K.create('#content', {
				filterMode: false,
				resizeType : 1,
				allowPreviewEmoticons : false,
				allowImageUpload : true,
				uploadJson : '/admin.php?g=System&c=Upyun&a=kindedtiropic',
				items : ['source', 'fontname', 'fontsize','subscript','superscript','indent','outdent','|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline','hr',
				 '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
				'insertunorderedlist','link', 'unlink','image','media','diyTool']
			});
            content_editor = kind_editor;
			var site_url = "{pigcms{$config.site_url}";
			var editor = K.editor({
				allowFileManager : true
			});
			
			var uploader = WebUploader.create({
				auto: true,
				duplicate :true,
				pick: {
					id: '.J_selectImage',
					label: '上传图片'
				},
				dnd: '#group_product_detail',
				accept: {
					title: 'Images',
					extensions: 'gif,jpg,jpeg,bmp,png',
					mimeTypes: 'image/*'
				},
				swf: './static/js/Uploader.swf',
				disableGlobalDnd: true,
				chunked: true,
				server: '{pigcms{:U('Config/ajax_upload_pic')}&type=group&session_id={pigcms{:session_id()}',
				fileNumLimit: 20,
				fileVal: 'imgFile',
				fileSizeLimit: 5 * 1024 * 1024,    //200 M
				fileSingleSizeLimit: 1 * 1024 * 1024    //50 M
			});
			
			uploader.on( 'fileQueued', function( file ) {
				// 选中文件时要做的事情，比如在页面中显示选中的文件并添加到文件列表，获取文件的大小，文件类型等
				console.log(file.ext) // 获取文件的后缀
				console.log(file.size) // 获取文件的大小
				console.log(file);
			});

			uploader.on( 'uploadSuccess', function( file,response ) {
				
				var image_url = $('#pic_list').val();
				if(image_url){
					$('#pic_list').val(image_url+';'+response.title);
				}else{
					$('#pic_list').val(response.title);
				}
				
				var html='<li data-id="'+(response.title)+'" class="upload_pic_li" style="float:left;list-style:none;"><img src="'+response.url+'" style="width:90px;height:50px;margin-right:5px;"><br><a href="javascript:void(0)" onclick="del(this,'+($('.upload_pic_li').size()+1)+')">[删除]</a></li>';
				$('#product_detail').append(html).show();
			});
		});

		function addFans(){
			art.dialog.data('titledom', 'titledom');
			art.dialog.data('imgids', 'imgids');
			art.dialog.data('multinews', 'multinews');
			art.dialog.data('singlenews', 'singlenews');
			art.dialog.data('js_appmsg_preview', 'js_appmsg_preview');
			art.dialog.data('multione', 'multione');
			art.dialog.open('?g=System&c=Village_group&a=select_village',{lock:true,title:'选择社区',width:600,height:400,yesText:'关闭',background: '#000',opacity: 0.45});
		}
		
		function addTag(obj) {
			var tag = obj.val();
			if (tag != '') {
				var i = 0;
				$(".tag").each(function() {
					if ($(this).text() == tag + "×") {
						$(this).addClass("tag-warning");
						setTimeout("removeWarning()", 400);
						i++;
					}
				})
				obj.val('');
				if (i > 0) { //说明有重复
					return false;
				}
				$("#form-field-tags").before("<span class='tag'>" + tag + "<button class='close' type='button'>×</button></span><br>"); //添加标签
			}
		}
		function removeWarning() {
			$(".tag-warning").removeClass("tag-warning");
		}
		
				
		 function clearUsers(){
			$('.tag').remove();
		}
		function del(obj,id){
			var image_list = $(obj).parents('.image_list').siblings('.input-image').val();
			image_list =  image_list.split(';')
			
			
			console.log("id:%",id);
			console.log(image_list);
			var tmp_img_list= 
			image_list.splice(id-1,1);
			tmp_img_list = image_list.join(';');
			// for (var i=1;i<=image_list.length;i++){
				// if(i!=id){
					// tmp_img_list += (i==1?'':';')+image_list[i];
				// }
			// }
			
			$(obj).parents('.image_list').siblings('.input-image').val(tmp_img_list)
			
			console.log(tmp_img_list);
			$(obj).closest('.upload_pic_li').remove();
		}
        function del_producnt(obj,id){
            var image_product_list = $(obj).parents('.image_product_list').siblings('.input-image').val();
            image_product_list =  image_product_list.split(';')


            console.log("id:%",id);
            console.log(image_product_list);
            var tmp_img_list=
                image_product_list.splice(id-1,1);
            tmp_img_list = image_product_list.join(';');
            // for (var i=1;i<=image_list.length;i++){
            // if(i!=id){
            // tmp_img_list += (i==1?'':';')+image_list[i];
            // }
            // }

            $(obj).parents('.image_product_list').siblings('.input-image').val(tmp_img_list)

            console.log(tmp_img_list);
            $(obj).closest('.upload_pic_li').remove();
        }
	</script>
	
	<script>
		$('#search_btn').click(function(){
			if($('#search').val()!=''){
					
				$.post("{pigcms{:U('group_search')}",{w:$('#search').val()},function(data){
					if(data.status == 0 || !data.info.group){
                        if (data.info) {
                            alert(data.info);
                        } else {
                            alert('没有搜索到结果！');
                        }
					}else{
						$('.tips').html('')
						console.log(data.info.group)
						var name ='',mer_id ='',mer_group_id ='',image ='',des ='',stock_num ='',list_pic ='',content ='',cat_fid='',cat_id='';
						if(data.info.group && data.info.group.group_name){
							name =data.info.group.group_name;
						    mer_id =data.info.group.mer_id;
							mer_group_id =data.info.group.group_id;
							image =data.info.group.list_pic;
							cat_id =data.info.group.cat_id;
							cat_fid =data.info.group.cat_fid;
							des =data.info.group.intro;
							stock_num =data.info.group.count_num;
							list_pic =data.info.group.list_pic;
							content =data.info.group.content;
							pic_list =data.info.group.pic;
							all_pic =data.info.group.all_pic;
							$('#name').val(name);
							$('#mer_id').val(mer_id);
							$('#mer_group_id').val(mer_group_id);
							$('#image').val(image);
							// $('#cat_fid').val(cat_fid);
							// $('#cat_id').val(cat_id);
							$('#des').val(des);
							if (stock_num>0) {
                                $('#stock_num').val(stock_num);
							}
                            $('#group_product_detail').html('');
                            var group_product_detail = $('#group_product_detail');
                            var product_detail = $('#product_detail');

                            var image_url = product_detail.siblings('.input-image').val();
                            var li_length1 = group_product_detail.find('li').length+product_detail.find('li').length;
                            console.log(image_url);
                            if(li_length1==0){
                                $('#pic_list').val(pic_list);
                            }else{
                                $('#pic_list').val(image_url+';'+pic_list);
                            }
							all_pic.forEach(function(val,index,arr){
                                var li_length = group_product_detail.find('li').length+product_detail.find('li').length;
                                var html='<li class="upload_pic_li" style="float:left;list-style:none;"><img src="'+all_pic[index]['s_image']+'" style="width:90px;height:50px;margin-right:5px;"><br><a href="javascript:void(0)" onclick="del_producnt(this,'+(li_length+1)+')">[删除]</a></li>';
                                $('#group_product_detail').append(html);
							});
														
							$('#product_detail').show();
							KindEditor.html('#content', content);
						}else{
							$('#name').val(name);
							$('#mer_id').val(mer_id);
							$('#mer_group_id').val(mer_group_id);
							$('#image').val(image);
							// $('#cat_fid').val(cat_fid);
							// $('#cat_id').val(cat_id);
							$('#des').val(des);
							$('#stock_num').val(stock_num);
							$('#product_detail_td img').attr('src',list_pic);
							$('#product_detail').hide();
							KindEditor.html('#content', content);
						}
					
					}
				});
			}else{
				$('#product_detail').hide();
			}
		});
		
	</script>
	<script>
		$(function(){
			$('input[name="open_time"]').click(function(){
				var sub = $(this);
				if(sub.val()==1){
					$('.time').show();
				}else{
					$('.time').hide();
				}
			});

			$("[name='is_new_user']").bind("click", function(){
				if($(this).val() == 1){
					$("#new_user_limit_buy").show();
				}
				else{
					$("#new_user_limit_buy").hide();
				}
			})
				
			<if condition="$group.open_time eq 1">
				$('.time').show();
			</if>
			
			<if condition="$group.image neq ''">
				$('#product_detail').show();
			</if>
			
			
			
			
		})
	</script>

    <script type="text/javascript">
        function addFans() {
            art.dialog.data('titledom', 'titledom');
            art.dialog.data('imgids', 'imgids');
            art.dialog.data('multinews', 'multinews');
            art.dialog.data('singlenews', 'singlenews');
            art.dialog.data('js_appmsg_preview', 'js_appmsg_preview');
            art.dialog.data('multione', 'multione');
            art.dialog.open('?g=System&c=Village_group&a=select_village&area_id={pigcms{$area_info['
            area_id
            ']}', {
                lock: true,
                title: '选择社区',
                width: 600,
                height: 400,
                yesText: '关闭',
                background: '#000',
                opacity: 0.45,
                top: '70%'
            }
        );
        }

        function clearUsers() {
            $('.tag').remove();
            $('#uids').val('');
        }

        $(".close").live("click", function () {
            $(this).parent(".tag").remove();
            var delete_name = $(this).data('uid');
            var userlist = $('#uids').val();
            userlistArr = userlist.split(',');
            for (var i = 0; i < userlistArr.length; i++) {
                if (userlistArr[i] == delete_name) {
                    userlistArr.splice(i, 1);
                }
            }
            userlist = userlistArr.join(',')
            $('#uids').val(userlist.replace(delete_name, ''))

        });

        $('input[name=is_add_price]').click(function () {
            console.log($('input[name=is_add_price]:checked').val())
            if ($('input[name=is_add_price]:checked').val() == '1') {
                $('.price_interval').show();
            } else {
                $('.price_interval').hide();
            }
        });

        $('.buy_user_type').click(function () {
            var sub = $(this);

            if (sub.val() == 0) {
                $('.address_list').hide();
            } else {
                $('.address_list').show();
            }
        });

        $('.buy_user_type').trigger('click');

        $(".tags_enter").blur(function () { //焦点失去触发
            var txtvalue = $(this).val().trim();
            if (txtvalue != '') {
                addTag($(this));
                $(this).parents(".tags").css({"border-color": "#d5d5d5"})
            }
        }).keydown(function (event) {
            var key_code = event.keyCode;
            var txtvalue = $(this).val().trim();
            if (key_code == 13 && txtvalue != '') { //enter
                addTag($(this));
            }
            if (key_code == 32 && txtvalue != '') { //space
                addTag($(this));
            }
        });

        $(".goods_close").live("click", function () {
            $(this).parent(".goods_tag").remove();
            $('#goods_id').val('');
        });

        score_set();
        function score_set(){
            var type = $('input[name=score_set_type]:checked').val();
            if(type == 1){
                $('#score_set_p1').show();
                $('#score_set_p0').hide();
            }else{
                $('#score_set_p1').hide();
                $('#score_set_p0').show();
            }
        }
    </script>
	
	
<include file="Public:footer"/>