<include file="Public:header"/>
	<style>
		.line_m{/*text-decoration:line-through*/text-align:center} 
			.table_form{border:1px solid #ddd;}
			.tab_ul{margin-top:10px;border-color:#C5D0DC;margin-bottom:0!important;margin-left:0;position:relative;top:1px;border-bottom:1px solid #ddd;padding-left:0;list-style:none;}
			.tab_ul>li{position:relative;display:block;float:left;margin-bottom:-1px;}
			.tab_ul>li>a {
				position: relative;
				display: block;
				padding: 10px 15px;
				margin-right: 2px;
				line-height: 1.42857143;
				border: 1px solid transparent;
				border-radius: 4px 4px 0 0;
				padding: 7px 12px 8px;
				min-width: 50px;
				text-align: center;
			}
			.tab_ul>li>a, .tab_ul>li>a:focus {
				border-radius: 0!important;
				border-color: #c5d0dc;
				background-color: #F9F9F9;
				color: #999;
				margin-right: -1px;
				line-height: 18px;
				position: relative;
			}
			.tab_ul>li>a:focus, .tab_ul>li>a:hover {
				text-decoration: none;
				background-color: #eee;
			}
			.tab_ul>li>a:hover {
				border-color: #eee #eee #ddd;
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #555;
				background-color: #fff;
				border: 1px solid #ddd;
				border-bottom-color: transparent;
				cursor: default;
			}
			.tab_ul>li>a:hover {
				background-color: #FFF;
				color: #4c8fbd;
				border-color: #c5d0dc;
			}
			.tab_ul>li:first-child>a {
				margin-left: 0;
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #576373;
				border-color: #c5d0dc #c5d0dc transparent;
				border-top: 2px solid #4c8fbd;
				background-color: #FFF;
				z-index: 1;
				line-height: 18px;
				margin-top: -1px;
				box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #555;
				background-color: #fff;
				border: 1px solid #ddd;
				border-bottom-color: transparent;
				cursor: default;
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #576373;
				border-color: #c5d0dc #c5d0dc transparent;
				border-top: 2px solid #4c8fbd;
				background-color: #FFF;
				z-index: 1;
				line-height: 18px;
				margin-top: -1px;
				box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
			}
			.tab_ul:before,.tab_ul:after{
				content: " ";
				display: table;
			}
			.tab_ul:after{
				clear: both;
			}
		</style>
	<form id="myform" method="post" action="{pigcms{:U('guess_like_post')}" frame="true" refresh="true" class="frame_form ">
	  	<ul class="tab_ul">
			<li class="active"><a data-toggle="tab" href="#tab1" >猜你喜欢配置</a></li>
			<li><a data-toggle="tab" href="#tab2" >店铺综合页面</a></li>
			<li><a data-toggle="tab" href="#tab3" >{pigcms{$config.shop_alias_name}配置</a></li>
			<li><a data-toggle="tab" href="#tab4" >{pigcms{$config.group_alias_name}配置</a></li>
			<li><a data-toggle="tab" href="#tab5" >{pigcms{$config.meal_alias_name}配置</a></li>
			<li><a data-toggle="tab" href="#tab6" >酒店配置</a></li>
			<li><a data-toggle="tab" href="#tab7" >团购拼团配置</a></li>
			<li><a data-toggle="tab" href="#tab8" >发现好菜</a></li>
			<li><a data-toggle="tab" href="#tab9" >{pigcms{$config_data.grass_alias_name}配置</a></li>
	  	</ul>
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab1" style="display: table;">
		   <tr>
				<th width="80">是否展示</th>
				<td class="radio_box">
					<span class="cb-enable">
						<label class="cb-enable <if condition="$config_data['index_guess_like_hide'] eq 0">selected</if>">
							<span>展示</span>
							<input id="yes" type="radio" name="index_guess_like_hide" value="0" <if condition="$config_data['index_guess_like_hide'] eq 0">checked="checked"</if>/>
						</label>
					</span>
					<span class="cb-disable">
						<label class="cb-disable <if condition="$config_data['index_guess_like_hide'] eq 1">selected</if>">
							<span>不展示</span>
							<input id="no" type="radio" name="index_guess_like_hide" value="1" <if condition="$config_data['index_guess_like_hide'] eq 1">checked="checked"</if> />
						</label>
					</span>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="这里的提示语改成：平台首页前台是否展示猜您喜欢功能，里面展示的各个业务的店铺/商品得先将后面其他业务tab选项卡开启后才有效果。" style="margin-top:1px;">
				</td>
			</tr>
			<tr>
				<th width="80">主标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_title" value="{pigcms{$config_data.index_guess_like_title}" size="30" validate="maxlength:20" tips="猜你喜欢别名，不填默认猜你喜欢"/></td>
			</tr>
			<tr>
				<th width="80">副标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_desc" value="{pigcms{$config_data.index_guess_like_desc}" size="50" validate="maxlength:50" tips="猜你喜欢副标题"/></td>
			</tr>
			<tr>
				<th width="80">添加活动</th>
				<td>
					<input type="hidden" name="index_guess_like_activity" value="{pigcms{$config_data.index_guess_like_desc}"/>
					<input type="button" value="添加活动" class="button" onclick="add_activity()" style="margin-left:0px;margin-right:10px;margin-bottom:10px;">
					<a></a>
					<table style="width:850px;" class="table_form" id='activity_box'>
						<thead>
						<tr >
							<th width="10%" style="text-align: left;">活动标题</th>
							<th width="10%" style="text-align: left;">活动价格</th>
							<th width="10%" style="text-align: left;">活动描述</th>
							<th width="15%" style="text-align: left;">所属店铺/商家</th>
							<th width="20%" style="text-align: left;">活动图片</th>
							<th width="10%" style="text-align: left;">图片布局形式</th>
							<th width="15%" style="text-align: left;">操作</th>
						</tr>
						</thead>
						<tbody>
						<volist name="activity_list" id="vo">
							<tr>
								<td>{pigcms{$vo.title}</td>
								<td>{pigcms{$vo.price}</td>
								<td>{pigcms{$vo.description}</td>
								<td>{pigcms{$vo.store_name}/{pigcms{$vo.mer_name}</td>
								<td>
									<volist name="vo['image']" id="img">
										<img src="{pigcms{$img}" style="width: 50px;height: 50px;margin-left: 4px;">
									</volist>
								</td>
								<td><php>if($vo['show_type']==0){</php>平铺3张 <php>}else{</php>1大2小<php>}</php></td>
								<td>
									<button type="button" onClick="add_activity({pigcms{$vo.id})">编辑</button>
									<button type="button" onClick="delThis(this,{pigcms{$vo.id})">移除</button>
								</td>
							</tr>
						</volist>
						</tbody>
					</table>
				</td>
			</tr>
		</table>
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab2" style="display: none;">
		   <!-- 店铺综合页面 -->
			<tr>
				<th width="80">刷新猜你喜欢</th>
				<td class="radio_box">
					<input type="button" value="点击刷新" class="button" onclick="refresh_guess_like()" style="margin-left:0px;margin-right:10px;margin-bottom:10px;"/>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="猜你喜欢的店铺会每隔30分钟通过系统定时任务自动刷新一次，点击按钮可重新生成一次定时任务。执行时长大概几分钟，根据平台的店铺数量来决定具体时间。会占用系统资源，尽量不要随便点。" style="margin-top:1px;"/>
				</td>
			</tr>
			<tr>
				<th width="80">是否展示</th>
				<td class="radio_box">
					<span class="cb-enable">
						<label class="cb-enable <if condition="$config_data['index_guess_like_hide_store'] eq 0">selected</if>">
							<span>展示</span>
							<input id="yes" type="radio" name="index_guess_like_hide_store" value="0" <if condition="$config_data['index_guess_like_hide_store'] eq 0">checked="checked"</if>/>
						</label>
					</span>
					<span class="cb-disable">
						<label class="cb-disable <if condition="$config_data['index_guess_like_hide_store'] eq 1">selected</if>">
							<span>不展示</span>
							<input id="no" type="radio" name="index_guess_like_hide_store" value="1" <if condition="$config_data['index_guess_like_hide_store'] eq 1">checked="checked"</if>/>
						</label>
					</span>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="平台首页--猜您喜欢模块中是否展示【店铺综合页面】这个分类" style="margin-top:1px;">
				</td>
			</tr>
			<tr>
				<th width="80">主标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_title_store" value="{pigcms{$config_data.index_guess_like_title_store}" size="30" validate="maxlength:20" tips="平台首页--猜您喜欢模块中的【店铺综合页面】这个分类的主标题，不填默认显示店铺2个字，建议填写4字以内。"/></td>
			</tr>
			<tr>
				<th width="80">副标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_desc_store" value="{pigcms{$config_data.index_guess_like_desc_store}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【店铺综合页面】这个分类的副标题，不填默认显示潮玩享乐4个字，建议填写4字以内。"/></td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="index_guess_like_sort_store" value="{pigcms{$config_data.index_guess_like_sort_store}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【店铺综合页面】这个分类的排序值，数字越大则该分类在平台首页的猜您喜欢模块中显示越靠前。"/></td>
			</tr>
		</table>
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab3" style="display: none;">
		   	<!-- 外卖 -->
			<tr>
				<th width="80">是否展示</th>
				<td class="radio_box">
					<span class="cb-enable">
						<label class="cb-enable <if condition="$config_data['index_guess_like_hide_shop'] eq 0">selected</if>">
							<span>展示</span>
							<input id="yes" type="radio" name="index_guess_like_hide_shop" value="0" <if condition="$config_data['index_guess_like_hide_shop'] eq 0">checked="checked"</if>/>
						</label>
					</span>
					<span class="cb-disable">
						<label class="cb-disable <if condition="$config_data['index_guess_like_hide_shop'] eq 1">selected</if>">
							<span>不展示</span>
							<input id="no" type="radio" name="index_guess_like_hide_shop" value="1" <if condition="$config_data['index_guess_like_hide_shop'] eq 1">checked="checked"</if>/>
						</label>
					</span>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="平台首页--猜您喜欢模块中是否展示【{pigcms{$config.shop_alias_name}】这个分类" style="margin-top:1px;">
				</td>
			</tr>
			<tr>
				<th width="80">主标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_title_shop" value="{pigcms{$config_data.index_guess_like_title_shop}" size="30" validate="maxlength:20" tips="平台首页--猜您喜欢模块中的【{pigcms{$config.shop_alias_name}】这个分类的主标题，不填默认显示店铺2个字，建议填写4字以内。"/></td>
			</tr>
			<tr>
				<th width="80">副标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_desc_shop" value="{pigcms{$config_data.index_guess_like_desc_shop}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【{pigcms{$config.shop_alias_name}】这个分类的副标题，不填默认显示本周特惠4个字，建议填写4字以内。"/></td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="index_guess_like_sort_shop" value="{pigcms{$config_data.index_guess_like_sort_shop}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【{pigcms{$config.shop_alias_name}】这个分类的排序值，数字越大则该分类在平台首页的猜您喜欢模块中显示越靠前。"/></td>
			</tr>
		</table>
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab4" style="display: none;">
		   	<!-- 团购 -->
			<tr>
				<th width="80">是否展示</th>
				<td class="radio_box">
					<span class="cb-enable">
						<label class="cb-enable <if condition="$config_data['index_guess_like_hide_group'] eq 0">selected</if>">
							<span>展示</span>
							<input id="yes" type="radio" name="index_guess_like_hide_group" value="0" <if condition="$config_data['index_guess_like_hide_group'] eq 0">checked="checked"</if>/>
						</label>
					</span>
					<span class="cb-disable">
						<label class="cb-disable <if condition="$config_data['index_guess_like_hide_group'] eq 1">selected</if>">
							<span>不展示</span>
							<input id="no" type="radio" name="index_guess_like_hide_group" value="1" <if condition="$config_data['index_guess_like_hide_group'] eq 1">checked="checked"</if>/>
						</label>
					</span>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="平台首页--猜您喜欢模块中是否展示【{pigcms{$config.group_alias_name}】这个分类" style="margin-top:1px;">
				</td>
			</tr>
			<tr>
				<th width="80">主标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_title_group" value="{pigcms{$config_data.index_guess_like_title_group}" size="30" validate="maxlength:20" tips="平台首页--猜您喜欢模块中的【{pigcms{$config.group_alias_name}】这个分类的主标题，不填默认显示店铺2个字，建议填写4字以内。"/></td>
			</tr>
			<tr>
				<th width="80">副标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_desc_group" value="{pigcms{$config_data.index_guess_like_desc_group}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【{pigcms{$config.group_alias_name}】这个分类的副标题，不填默认显示大牌甄选4个字，建议填写4字以内。"/></td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="index_guess_like_sort_group" value="{pigcms{$config_data.index_guess_like_sort_group}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【{pigcms{$config.group_alias_name}】这个分类的排序值，数字越大则该分类在平台首页的猜您喜欢模块中显示越靠前。"/></td>
			</tr>
		</table>
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab5" style="display: none;">
		   	<!-- 餐饮 -->
			<tr>
				<th width="80">是否展示</th>
				<td class="radio_box">
					<span class="cb-enable">
						<label class="cb-enable <if condition="$config_data['index_guess_like_hide_meal'] eq 0">selected</if>">
							<span>展示</span>
							<input id="yes" type="radio" name="index_guess_like_hide_meal" value="0" <if condition="$config_data['index_guess_like_hide_meal'] eq 0">checked="checked"</if>/>
						</label>
					</span>
					<span class="cb-disable">
						<label class="cb-disable <if condition="$config_data['index_guess_like_hide_meal'] eq 1">selected</if>">
							<span>不展示</span>
							<input id="no" type="radio" name="index_guess_like_hide_meal" value="1" <if condition="$config_data['index_guess_like_hide_meal'] eq 1">checked="checked"</if>/>
						</label>
					</span>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="平台首页--猜您喜欢模块中是否展示【{pigcms{$config.meal_alias_name}】这个分类" style="margin-top:1px;">
				</td>
			</tr>
			<tr>
				<th width="80">主标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_title_meal" value="{pigcms{$config_data.index_guess_like_title_meal}" size="30" validate="maxlength:20" tips="平台首页--猜您喜欢模块中的【{pigcms{$config.meal_alias_name}】这个分类的主标题，不填默认显示店铺2个字，建议填写4字以内。"/></td>
			</tr>
			<tr>
				<th width="80">副标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_desc_meal" value="{pigcms{$config_data.index_guess_like_desc_meal}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【{pigcms{$config.meal_alias_name}】这个分类的副标题，不填默认显示吃遍全城4个字，建议填写4字以内。"/></td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="index_guess_like_sort_meal" value="{pigcms{$config_data.index_guess_like_sort_meal}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【{pigcms{$config.meal_alias_name}】这个分类的排序值，数字越大则该分类在平台首页的猜您喜欢模块中显示越靠前。"/></td>
			</tr>
		</table>
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab6" style="display: none;">
		   	<!-- 酒店 -->
			<tr>
				<th width="80">是否展示</th>
				<td class="radio_box">
					<span class="cb-enable">
						<label class="cb-enable <if condition="$config_data['index_guess_like_hide_hotel'] eq 0">selected</if>">
							<span>展示</span>
							<input id="yes" type="radio" name="index_guess_like_hide_hotel" value="0" <if condition="$config_data['index_guess_like_hide_hotel'] eq 0">checked="checked"</if>/>
						</label>
					</span>
					<span class="cb-disable">
						<label class="cb-disable <if condition="$config_data['index_guess_like_hide_hotel'] eq 1">selected</if>">
							<span>不展示</span>
							<input id="no" type="radio" name="index_guess_like_hide_hotel" value="1" <if condition="$config_data['index_guess_like_hide_hotel'] eq 1">checked="checked"</if>/>
						</label>
					</span>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="平台首页--猜您喜欢模块中是否展示【酒店】这个分类" style="margin-top:1px;">
				</td>
			</tr>
			<tr>
				<th width="80">主标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_title_hotel" value="{pigcms{$config_data.index_guess_like_title_hotel}" size="30" validate="maxlength:20" tips="平台首页--猜您喜欢模块中的【酒店】这个分类的主标题，不填默认显示店铺2个字，建议填写4字以内。"/></td>
			</tr>
			<tr>
				<th width="80">副标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_desc_hotel" value="{pigcms{$config_data.index_guess_like_desc_hotel}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【酒店】这个分类的副标题，不填默认显示特价精选4个字，建议填写4字以内。"/></td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="index_guess_like_sort_hotel" value="{pigcms{$config_data.index_guess_like_sort_hotel}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【酒店】这个分类的排序值，数字越大则该分类在平台首页的猜您喜欢模块中显示越靠前。"/></td>
			</tr>
		</table>
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab7" style="display: none;">
		   	<!-- 拼团 -->
			<tr>
				<th width="80">是否展示</th>
				<td class="radio_box">
					<span class="cb-enable">
						<label class="cb-enable <if condition="$config_data['index_guess_like_hide_pin_group'] eq 0">selected</if>">
							<span>展示</span>
							<input id="yes" type="radio" name="index_guess_like_hide_pin_group" value="0" <if condition="$config_data['index_guess_like_hide_pin_group'] eq 0">checked="checked"</if>/>
						</label>
					</span>
					<span class="cb-disable">
						<label class="cb-disable <if condition="$config_data['index_guess_like_hide_pin_group'] eq 1">selected</if>">
							<span>不展示</span>
							<input id="no" type="radio" name="index_guess_like_hide_pin_group" value="1" <if condition="$config_data['index_guess_like_hide_pin_group'] eq 1">checked="checked"</if>/>
						</label>
					</span>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="平台首页--猜您喜欢模块中是否展示【拼团】这个分类" style="margin-top:1px;">
				</td>
			</tr>
			<tr>
				<th width="80">主标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_title_pin_group" value="{pigcms{$config_data.index_guess_like_title_pin_group}" size="30" validate="maxlength:20" tips="平台首页--猜您喜欢模块中的【拼团】这个分类的主标题，不填默认显示店铺2个字，建议填写4字以内。"/></td>
			</tr>
			<tr>
				<th width="80">副标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_desc_pin_group" value="{pigcms{$config_data.index_guess_like_desc_pin_group}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【拼团】这个分类的副标题，不填默认显示甄选优惠4个字，建议填写4字以内。"/></td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="index_guess_like_sort_pin_group" value="{pigcms{$config_data.index_guess_like_sort_pin_group}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【拼团】这个分类的排序值，数字越大则该分类在平台首页的猜您喜欢模块中显示越靠前。"/></td>
			</tr>
		</table>
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab8" style="display: none;">
		   	<!-- 拼团 -->
			<tr>
				<th width="80">是否展示</th>
				<td class="radio_box">
					<span class="cb-enable">
						<label class="cb-enable <if condition="$config_data['index_guess_like_hide_shop_goods'] eq 0">selected</if>">
							<span>展示</span>
							<input id="yes" type="radio" name="index_guess_like_hide_shop_goods" value="0" <if condition="$config_data['index_guess_like_hide_shop_goods'] eq 0">checked="checked"</if>/>
						</label>
					</span>
					<span class="cb-disable">
						<label class="cb-disable <if condition="$config_data['index_guess_like_hide_shop_goods'] eq 1">selected</if>">
							<span>不展示</span>
							<input id="no" type="radio" name="index_guess_like_hide_shop_goods" value="1" <if condition="$config_data['index_guess_like_hide_shop_goods'] eq 1">checked="checked"</if>/>
						</label>
					</span>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="平台首页前台是否展示外卖单个商品推荐列表。" style="margin-top:1px;">
				</td>
			</tr>
			<tr>
				<th width="80">主标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_title_shop_goods" value="{pigcms{$config_data.index_guess_like_title_shop_goods}" size="30" validate="maxlength:20" tips="外卖单个商品列表的主标题"/></td>
			</tr>
			<tr>
				<th width="80">副标题</th>
				<td><input type="text" class="input fl" name="index_guess_like_desc_shop_goods" value="{pigcms{$config_data.index_guess_like_desc_shop_goods}" size="50" validate="maxlength:50" tips="外卖单个商品列表的副标题"/></td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="index_guess_like_sort_shop_goods" value="{pigcms{$config_data.index_guess_like_sort_shop_goods}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【发现好菜】这个分类的排序值，数字越大则该分类在平台首页的猜您喜欢模块中显示越靠前。"/></td>
			</tr>
			<tr>
				<th width="80">管理分类/商品</th>
				<td>
					<input type="hidden" name="index_guess_like_goods_shop_goods" value="{pigcms{$config_data.index_guess_like_goods_shop_goods}"/>
					<!--<a class="button" href="{pigcms{:U('Diy_category/index',['type'=>1])}" >管理分类/商品</a>-->
					<a class="button" class="iframe_close" href="javascript:;" onclick="test();">管理分类/商品</a>
					<!--<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Diy_category/index',['type'=>1])}','商品管理',480,300,true,false,false,addbtn,'add',true);">创建分类</a>
						<input type="button" value="添加商品" class="button" onclick="select_goods()" style="margin-left:0px;margin-right:10px;margin-bottom:10px;">-->
					<a></a>
					<!--
					<table style="width:800px;" class="table_form" id='goods_spec'>
						<thead>
						<tr >
							<th style="text-align: left;">ID</th>
							<th style="text-align: left;">商品名称</th>
							<th style="text-align: left;">商品价格</th>
							<th style="text-align: left;">操作</th>
						</tr>
						</thead>
						<tbody>
						
						</tbody>
					</table>-->
				</td>
			</tr>
		</table>
        <table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab9" style="display: none;">
            <!-- 种草配置 -->
            <tr>
                <th width="80">是否展示</th>
                <td class="radio_box">
					<span class="cb-enable">
						<label class="cb-enable <if condition="$config_data['index_guess_like_hide_grass'] eq 0">selected</if>">
							<span>展示</span>
							<input id="yes" type="radio" name="index_guess_like_hide_grass" value="0" <if condition="$config_data['index_guess_like_hide_grass'] eq 0">checked="checked"</if>/>
                        </label>
					</span>
                    <span class="cb-disable">
						<label class="cb-disable <if condition="$config_data['index_guess_like_hide_grass'] eq 1">selected</if>">
							<span>不展示</span>
							<input id="no" type="radio" name="index_guess_like_hide_grass" value="1" <if condition="$config_data['index_guess_like_hide_grass'] eq 1">checked="checked"</if>/>
                        </label>
					</span>
                    <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="平台首页--猜您喜欢模块中是否展示【{pigcms{$config_data.grass_alias_name}】这个分类" style="margin-top:1px;">
                </td>
            </tr>
            <tr>
                <th width="80">主标题</th>
                <td><input type="text" class="input fl" name="index_guess_like_title_grass" value="{pigcms{$config_data.index_guess_like_title_grass}" size="30" validate="maxlength:20" tips="平台首页--猜您喜欢模块中的【{pigcms{$config_data.grass_alias_name}】这个分类的主标题，不填默认显示店铺2个字，建议填写4字以内。"/></td>
            </tr>
            <tr>
                <th width="80">副标题</th>
                <td><input type="text" class="input fl" name="index_guess_like_desc_grass" value="{pigcms{$config_data.index_guess_like_desc_grass}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【{pigcms{$config_data.grass_alias_name}】这个分类的副标题，不填默认显示大牌甄选4个字，建议填写4字以内。"/></td>
            </tr>
            <tr>
                <th width="80">附近</th>
                <td><input type="text" class="input fl" name="index_guess_like_nearby" value="{pigcms{$config_data.index_guess_like_nearby}" size="50" validate="maxlength:50" tips="【{pigcms{$config_data.grass_alias_name}】首页附近{pigcms{$config_data.index_guess_like_nearby}公里范围内的发布，默认为0，范围不限。"/>单位：公里</td>
            </tr>
            <tr>
                <th width="80">排序</th>
                <td><input type="text" class="input fl" name="index_guess_like_sort_grass" value="{pigcms{$config_data.index_guess_like_sort_grass}" size="50" validate="maxlength:50" tips="平台首页--猜您喜欢模块中的【{pigcms{$config_data.grass_alias_name}】这个分类的排序值，数字越大则该分类在平台首页的猜您喜欢模块中显示越靠前。"/></td>
            </tr>
        </table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>

	<script>
		function test(){
            parent.addIframe('平台发现好菜-自定义分类','diy_category_1','{pigcms{:U('Diy_category/index',['type'=>1])}');
            window.top.closeiframe();
    	}
		$(function(){
			
			// $('a[data-toggle="tab"]').click(function(){
			// 	layer.load(1, {
			// 		shade: [0.1,'#fff']
			// 	});
			// 	var href = $(this).attr('href');
			// 	setTimeout(function(){
			// 		location.href = href;
			// 	},100);
			// 	return false;
			// });

			$('.table_form:eq(0)').show();
			$('.tab_ul li a').click(function(){
				$(this).closest('li').addClass('active').siblings('li').removeClass('active');
				$($(this).attr('href')).show().siblings('.table_form').hide();
				return false;
			});
			
			var pay_rate = $('.pay_rate');
			var open_pay_percent=$('.pay_percent').find('input').val();
			
	
			$('.pay_percent').find('input').blur(function(){
				open_pay_percent = $(this).val();
			});
			
			$('input[name="open_pay_rate"]').click(function(){
				if($(this).val()==1){
					pay_rate.show();
					if(open_pay_percent<0){
						$('.pay_percent').find('input').val('');
					}else{
						$('.pay_percent').find('input').val(open_scan_percent);
					}
				}else{
					pay_rate.hide();
					$('.pay_percent').find('input').attr('value',-1);
				}
			});
		});

		function add_activity(id=''){
			var url = '';
			url = '{pigcms{:U("activity_edit",array("business_type"=>"plat"))}' + '&id='+id;
			window.top.artiframe(url,'添加活动',720,600,true,false,false,editbtn,'activity_edit',true,
				function(){
					$("#activity_box tbody").empty();
					$.post("{pigcms{:U('activity_list',array('business_type'=>'plat'))}",{},function(data){
						if(data.list.length > 0){
							var html = '';
							data = data.list;
							for(var i = 0; i< data.length; i++){
								console.log(i)
								html += '<tr>';
								html += '	<td>'+data[i].title+'</td>';
								html += '	<td>'+data[i].price+'</td>';
								html += '	<td>'+data[i].description+'</td>';
								html += '	<td>'+data[i].store_name+'/'+data[i].mer_name+'</td>';
								html += '	<td>';

								for(var j = 0; j< data[i].image.length; j++){
									html += '	<img src="'+data[i].image[j]+'" style="width: 50px;height: 50px">';
								}

								html += '	</td>';
								html += '	<td>'+(data[i].show_type==0 ? '平铺3张' : '1大2小')+'</td>';
								html += '	<td>';
								html += '		<button type="button" onClick="add_activity('+data[i].id+')">编辑</button>';
								html += '		<button type="button" onClick="delThis(this,'+data[i].id+')">移除</button>';
								html += '	</td>';
								html += '</tr>';
							}
							console.log(html)
							$("#activity_box tbody").append(html);
						}
					},'json');
			});
			return false;
		}

		function delThis(t,id){
			if(confirm('{pigcms{:L_("你确定要移除此活动吗？")}')){
				$.post("{pigcms{:U('activity_del')}",{id:id},function(data){
					if(data.error > 0){
						layer.alert(data.msg)
					}else{
						layer.alert(data.msg)
						$(t).parents("tr").eq(0).remove();
					}
				},'json');
			}
		}

		var _selected_goods = '{pigcms{$goods_ids}';
		function select_goods(){
			layer.open({
				type: 2,
				area: ['90%', '90%'],
				fixed: false,
				offset:'10px',
				maxmin: true,
				content: '{pigcms{:U("select_goods")}'+'&selected='+_selected_goods,
				btn: ['确定', '取消'],
				yes: function(index, layero){
					var frameId = $(layero).find("iframe").attr('id');
					var selected = $.trim($(window.frames[frameId].document).find('[name="selected"]').val());
					_selected_goods = selected;
					$("#goods_spec tbody").empty();

					// 渲染商品
					ajaxGetGoods(selected);

				    layer.close(index);
				},
				btn2: function(index){
					layer.close(index);
				}
			});
		}

		// 渲染商品
		function ajaxGetGoods(selected){
			$.post("{pigcms{:U('ajaxGetGoods')}",{selected:selected},function(data){
				if(data.length > 0){
					for(var i = 0; i< data.length; i++){
						$("#goods_spec tbody").append('<tr>'+
							'<input type="hidden" name="relation_goods_id[]" value="'+data[i].goods_id+'" />'+
							'<td>'+data[i].goods_id+'</td>'+
							// '<td>'+data[i].model+'</td>'+
							'<td>'+data[i].name+'</td>'+
							'<td>'+data[i].price+'</td>'+
							'<td>'+
									'<button type="button" onClick="delGoods(this)">{pigcms{:L_("移除")}</button>'+
							'</td>'+
						'</tr>');
					}
				}
			},'json');
		}
		
		function refresh_guess_like(){
			$.post("{pigcms{:U('refresh_guess_like')}", {}, function(data){
				alert('刷新成功');
			},'json');
		}

		// 初始化加载商品列表
		//ajaxGetGoods(_selected_goods);

		// 移除商品
		function delGoods(t){
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
				$(t).parents("tr").eq(0).remove();
			}
		}
	</script>
<include file="Public:footer"/>