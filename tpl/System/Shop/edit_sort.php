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

        .preference_effect_date_tr, .preference_effect_time_tr, .recommend_effect_date_tr, .recommend_effect_time_tr {
            display: none;
        }
	</style>
	<form id="myform" method="post" action="{pigcms{:U('Shop/edit_amend')}" frame="true" refresh="true">
		<input type="hidden" name="id" value="{pigcms{$now_shop.store_id}"/>
		<ul class="tab_ul">
			<li class="active"><a data-toggle="tab" href="#tab1" >基础设置</a></li>
			<li><a data-toggle="tab" href="#tab2" >优选设置</a></li>
			<li><a data-toggle="tab" href="#tab3" >爆款配置</a></li>
	  	</ul>
		<table cellpadding="0" cellspacing="0" class="frame_form table_form" id="tab1" width="100%">
			<tr>
				<th width="90">店铺名称</th>
				<td>{pigcms{$now_shop.name}</td>
			</tr>
			<tr class="delivery_range_type0">
				<th width="90">手动排序</th>
				<td><input type="text" class="input fl" name="sort" value="{pigcms{$now_shop.sort}" id="sort" size="10" tips="数值越大，在店铺列表页排序越前"/></td>
			</tr>
			</tr>
			<tr class="delivery_range_type0">
				<th width="90">虚拟销量</th>
				<td><input type="text" class="input fl" name="virtual_sale_count" value="{pigcms{$now_shop.virtual_sale_count}" id="sort" size="10" tips="显示给用户看的销量=虚拟销量+实际销量"/></td>
			</tr>
            <if condition="!empty($config['eleme_app_key'])">
				<tr class="">
					<th width="90">饿了么店铺ID</th>
					<td><input type="text" class="input fl" name="eleme_shopId" value="{pigcms{$now_shop.eleme_shopId}" id="eleme_shopId" size="10" tips="填写已授权给平台饿了么应用的店铺ID"/></td>
				</tr>
            </if>
			<tr>
				<th width="90">店铺关键词</th>
				<td><input type="text" class="input fl" name="search_keywords" value="{pigcms{$now_shop.search_keywords}" id="search_keywords" size="30" tips="{pigcms{$config.shop_alias_name}搜索页面的热门关键词，会随机推荐符合显示条件的店铺设置的关键词，请注意搜索词只用于推荐，搜索结果和搜索词无关，建议填写店铺名称或商品名称。以空格分割，最多100个字"/></td>
			</tr>
			<tr class="">
				<th width="90">主推描述</th>
				<td><input type="text" class="input fl" name="preference_desc" value="{pigcms{$now_shop.preference_desc}" id="sort" size="30" tips="设置了主推描述，则用户前台店铺列表页会优先展示当前主推描述，未设置则前台显示当前店铺所属的前三个子分类名称。尽量控制在16字以内。"/></td>
			</tr>
		</table>
		<table cellpadding="0" cellspacing="0" class="frame_form table_form" id="tab2" width="100%" style="display: none;">
			<tr>
				<th width="90">是否设置为优选</th>
				<td>
					<span class="cb-enable preference_btn"><label class="cb-enable <if condition="$now_shop['preference_status'] eq 1">selected</if>"><span>是</span><input type="radio" name="preference_status" value="1"  <if condition="$now_shop['preference_status'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable preference_btn"><label class="cb-disable <if condition="$now_shop['preference_status'] eq 0">selected</if>"><span>否</span><input type="radio" name="preference_status" value="0" <if condition="$now_shop['preference_status'] eq 0">checked="checked"</if>/></label></span>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="设置为优选店铺，请先在商家后台上传正方形LOGO" style="margin-top:1px;"/>
				</td>
			</tr>
			<tr class="preference_setting">
				<th width="90">优选排序</th>
				<td><input type="text" class="input fl" name="preference_sort" value="{pigcms{$now_shop.preference_sort}" id="sort" size="10" tips="值越高，在优选店铺中排序越前。优选中排序仅依靠此值"/></td>
			</tr>
			<tr class="preference_setting">
				<th width="90">优选描述</th>
				<td><input type="text" class="input fl" name="preference_reason" value="{pigcms{$now_shop.preference_reason}" id="sort" size="30" tips="一句话描述店铺优势，尽量控制在十六字以内，能展现多少字以页面效果为准"/></td>
			</tr>

            <tr class="preference_setting">
                <th width="80"><span style="color: #FF0000">*</span>生效日期</th>
                <td class="radio_box">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_shop['preference_effect_date_type'] neq 2">selected</if>"><span>全时段</span><input type="radio" name="preference_effect_date_type" value="1" <if condition="$now_shop['preference_effect_date_type'] neq 2">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_shop['preference_effect_date_type'] eq 2">selected</if>"><span>自定义日期</span><input type="radio" name="preference_effect_date_type" value="2" <if condition="$now_shop['preference_effect_date_type'] eq 2">checked="checked"</if> /></label></span>
                </td>
            </tr>
            <tr class="preference_setting preference_effect_date_tr">
                <th width="80">选择日期</th>
                <td colspan="2">
                    <input type="text" class="input-text" name="preference_start_date" style="width:120px;" id="d4311"  value="{pigcms{$now_shop.preference_start_date}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>-
                    <input type="text" class="input-text" name="preference_end_date" style="width:120px;" id="d4311" value="{pigcms{$now_shop.preference_end_date}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" validate="required:true"/>
                </td>
            </tr>
            <tr class="preference_setting preference_effect_date_tr">
                <th width="80">选择星期</th>
                <td colspan="2">
                    <input type="checkbox" name="preference_week[]" value="0" <if condition="($now_shop['preference_effect_date_type'] eq 1) or (strpos($now_shop['preference_week'],'0') !== false)">checked</if>>&nbsp;星期日&nbsp;
                    <input type="checkbox" name="preference_week[]" value="1" <if condition="($now_shop['preference_effect_date_type'] eq 1) or (strpos($now_shop['preference_week'],'1') !== false)">checked</if>>&nbsp;星期一&nbsp;
                    <input type="checkbox" name="preference_week[]" value="2" <if condition="($now_shop['preference_effect_date_type'] eq 1) or (strpos($now_shop['preference_week'],'2') !== false)">checked</if>>&nbsp;星期二&nbsp;
                    <input type="checkbox" name="preference_week[]" value="3" <if condition="($now_shop['preference_effect_date_type'] eq 1) or (strpos($now_shop['preference_week'],'3') !== false)">checked</if>>&nbsp;星期三&nbsp;
                    <input type="checkbox" name="preference_week[]" value="4" <if condition="($now_shop['preference_effect_date_type'] eq 1) or (strpos($now_shop['preference_week'],'4') !== false)">checked</if>>&nbsp;星期四&nbsp;
                    <input type="checkbox" name="preference_week[]" value="5" <if condition="($now_shop['preference_effect_date_type'] eq 1) or (strpos($now_shop['preference_week'],'5') !== false)">checked</if>>&nbsp;星期五&nbsp;
                    <input type="checkbox" name="preference_week[]" value="6" <if condition="($now_shop['preference_effect_date_type'] eq 1) or (strpos($now_shop['preference_week'],'6') !== false)">checked</if>>&nbsp;星期六
                </td>
            </tr>
            <tr class="preference_setting">
                <th width="80"><span style="color: #FF0000">*</span>生效时间段</th>
                <td class="radio_box">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_shop['preference_effect_time_type'] neq 2">selected</if>"><span>全时段</span><input type="radio" name="preference_effect_time_type" value="1" <if condition="$now_shop['preference_effect_time_type'] neq 2">checked="checked"</if>/></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_shop['preference_effect_time_type'] eq 2">selected</if>" ><span>自定义时间段</span><input type="radio" name="preference_effect_time_type" value="2" <if condition="$now_shop['preference_effect_time_type'] eq 2">checked="checked"</if>/></label></span>
                </td>
            </tr>
            <tr class="preference_setting preference_effect_time_tr">
                <th width="80">选择时间</th>
                <td colspan="2">
                    <input type="text" class="input-text" name="preference_start_time" style="width:120px;" id="d4312"  value="{pigcms{$now_shop.preference_start_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})"/>-
                    <input type="text" class="input-text" name="preference_end_time" style="width:120px;" id="d4312" value="{pigcms{$now_shop.preference_end_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})" validate="required:true"/>
                </td>
            </tr>

		</table>
		<table cellpadding="0" cellspacing="0" class="frame_form table_form" id="tab3" width="100%" style="display: none;">
			<tr>
				<th width="90">是否设置爆款商品推荐</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_shop['recommend_status'] eq 1">selected</if>"><span>是</span><input type="radio" name="recommend_status" value="1"  <if condition="$now_shop['recommend_status'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_shop['recommend_status'] eq 0">selected</if>"><span>否</span><input type="radio" name="recommend_status" value="0" <if condition="$now_shop['recommend_status'] eq 0">checked="checked"</if>/></label></span>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="设置为爆款商品推荐，请先在商家后台上传正方形LOGO" style="margin-top:1px;"/>
				</td>
			</tr>
			<tr class="">
				<th width="90">排序</th>
				<td><input type="text" class="input fl" name="recommend_sort" value="{pigcms{$now_shop.recommend_sort}" id="sort" size="10" tips="值越高，在爆款商品推荐中排序越前。"/></td>
			</tr>
			<tr class="">
				<th width="90">爆款商品推荐描述</th>
				<td><input type="text" class="input fl" name="recommend_desc" value="{pigcms{$now_shop.recommend_desc}" id="sort" size="30" tips="一句话描述店铺优势，尽量控制在十六字以内，能展现多少字以页面效果为准"/></td>
			</tr>
			<tr class="">
				<th width="90">店铺图片</th>
				<td>
					<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" style="background: #87b87f!important;border-color: #87b87f;color:#fff;" >上传图片</a>&nbsp;&nbsp;建议尺寸：400*750
	                <input type="hidden" class="input-image" name="recommend_pic" id="pic_list" value="{pigcms{$now_shop['recommend_pic']}">
	                <ul class="image_product_list" id="avatar_detail">

	                </ul>
	                <ul class="avatar_list" id="avatar_show" >
		                <if condition="$now_shop['recommend_pic'] neq ''">
		                    <li  class="upload_pic_li1" style="float:left;list-style:none;"><img src="{pigcms{:file_domain().$now_shop['recommend_pic']}" style="width:400px;height:auto;margin-right:5px;"></li>
		                   
		                </if>
	                </ul>
	            </td>
			</tr>
			<tr class="">
				<th width="90">关联商品</th>
				<td>
					<a class="button fl"  onclick="select_goods()"/>添加商品</a><br/>
					<table style="width:100%;margin-top: 10px;" class="table table-bordered" id='goods_spec'>
						<thead>
							<tr>
								<th width="10%">{pigcms{:L_("商品编号")}</th>
								<th width="40%">{pigcms{:L_("商品名称")}</th>
								<th width="10%">{pigcms{:L_("现价")}</th>
								<th width="20%">{pigcms{:L_("操作")}</th>
							</tr>
						</thead>
						<tbody>
							<volist name="now_shop['recommend_good_list']" id="vo">
							<tr>
								<input type='hidden' name="recommend_good_ids[]" value="{pigcms{$vo.goods_id}" />
								<td>{pigcms{$vo.goods_id}</td>
								<td>{pigcms{$vo.name}</td>
								<td>{pigcms{$vo.price}</td>
								<td>
									<button type="button" onClick="delThis(this)">移除</button>
								</td>
							</tr>							
							</volist>
						</tbody>
					</table>
				</td>
			</tr>



            <tr>
                <th width="80"><span style="color: #FF0000">*</span>生效日期</th>
                <td class="radio_box">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_shop['recommend_effect_date_type'] neq 2">selected</if>"><span>全时段</span><input type="radio" name="recommend_effect_date_type" value="1" <if condition="$now_shop['recommend_effect_date_type'] neq 2">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_shop['recommend_effect_date_type'] eq 2">selected</if>"><span>自定义日期</span><input type="radio" name="recommend_effect_date_type" value="2" <if condition="$now_shop['recommend_effect_date_type'] eq 2">checked="checked"</if> /></label></span>
                </td>
            </tr>
            <tr class="recommend_effect_date_tr">
                <th width="80">选择日期</th>
                <td colspan="2">
                    <input type="text" class="input-text" name="recommend_start_date" style="width:120px;" id="d4313"  value="{pigcms{$now_shop.recommend_start_date}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>-
                    <input type="text" class="input-text" name="recommend_end_date" style="width:120px;" id="d4313" value="{pigcms{$now_shop.recommend_end_date}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" validate="required:true"/>
                </td>
            </tr>
            <tr class="recommend_effect_date_tr">
                <th width="80">选择星期</th>
                <td colspan="2">
                    <input type="checkbox" name="recommend_week[]" value="0" <if condition="($now_shop['recommend_effect_date_type'] eq 1) or (strpos($now_shop['recommend_week'],'0') !== false)">checked</if>>&nbsp;星期日&nbsp;
                    <input type="checkbox" name="recommend_week[]" value="1" <if condition="($now_shop['recommend_effect_date_type'] eq 1) or (strpos($now_shop['recommend_week'],'1') !== false)">checked</if>>&nbsp;星期一&nbsp;
                    <input type="checkbox" name="recommend_week[]" value="2" <if condition="($now_shop['recommend_effect_date_type'] eq 1) or (strpos($now_shop['recommend_week'],'2') !== false)">checked</if>>&nbsp;星期二&nbsp;
                    <input type="checkbox" name="recommend_week[]" value="3" <if condition="($now_shop['recommend_effect_date_type'] eq 1) or (strpos($now_shop['recommend_week'],'3') !== false)">checked</if>>&nbsp;星期三&nbsp;
                    <input type="checkbox" name="recommend_week[]" value="4" <if condition="($now_shop['recommend_effect_date_type'] eq 1) or (strpos($now_shop['recommend_week'],'4') !== false)">checked</if>>&nbsp;星期四&nbsp;
                    <input type="checkbox" name="recommend_week[]" value="5" <if condition="($now_shop['recommend_effect_date_type'] eq 1) or (strpos($now_shop['recommend_week'],'5') !== false)">checked</if>>&nbsp;星期五&nbsp;
                    <input type="checkbox" name="recommend_week[]" value="6" <if condition="($now_shop['recommend_effect_date_type'] eq 1) or (strpos($now_shop['recommend_week'],'6') !== false)">checked</if>>&nbsp;星期六
                </td>
            </tr>
            <tr>
                <th width="80"><span style="color: #FF0000">*</span>生效时间段</th>
                <td class="radio_box">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_shop['recommend_effect_time_type'] neq 2">selected</if>"><span>全时段</span><input type="radio" name="recommend_effect_time_type" value="1" <if condition="$now_shop['recommend_effect_time_type'] neq 2">checked="checked"</if>/></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_shop['recommend_effect_time_type'] eq 2">selected</if>" ><span>自定义时间段</span><input type="radio" name="recommend_effect_time_type" value="2" <if condition="$now_shop['recommend_effect_time_type'] eq 2">checked="checked"</if>/></label></span>
                </td>
            </tr>
            <tr class="recommend_effect_time_tr">
                <th width="80">选择时间</th>
                <td colspan="2">
                    <input type="text" class="input-text" name="recommend_start_time" style="width:120px;" id="d4314"  value="{pigcms{$now_shop.recommend_start_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})"/>-
                    <input type="text" class="input-text" name="recommend_end_time" style="width:120px;" id="d4314" value="{pigcms{$now_shop.recommend_end_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})" validate="required:true"/>
                </td>
            </tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	<script>
        var ignoreValidate = ":hidden";

        $('.preference_btn').click(function(){
			var preference_status = $(this).find('input').val();
			if(preference_status == 1){
				$('.preference_setting').show();
			}else{
				$('.preference_setting').hide();
			}
            $('input[name=preference_effect_date_type]:checked').trigger('click');
            $('input[name=preference_effect_time_type]:checked').trigger('click');
		});
		$('.preference_btn .selected').closest('.preference_btn').trigger('click');

		$('.table_form:eq(0)').show();
		$('.tab_ul li a').click(function(){
			$(this).closest('li').addClass('active').siblings('li').removeClass('active');
			$($(this).attr('href')).show().siblings('.table_form').hide();
			return false;
		});

		var _selected_goods = '{pigcms{$now_shop["recommend_good_ids"]}';
		function select_goods(){
			layer.open({
				type: 2,
				area: ['90%', '90%'],
				fixed: false,
				maxmin: true,
				content: '{pigcms{:U("select_goods",array("store_id"=>$_GET["store_id"]))}'+'&selected='+_selected_goods,
				btn: ['确定', '取消'],
				yes: function(index, layero){
					var frameId = $(layero).find("iframe").attr('id');
					var selected = $.trim($(window.frames[frameId].document).find('[name="selected"]').val());
					console.log(selected)
					var selected_arr = selected.split(',')
					console.log(selected_arr)
					if (selected_arr.length>3) {
						layer.alert("{pigcms{:L_('最多只能添加3个商品')}");
						return false;
					}
					_selected_goods = selected;
					$("#goods_spec tbody").empty();
					$.post("{pigcms{:U('ajaxGetGoods')}",{selected:selected},function(data){
						if(data.length > 0){
							for(var i = 0; i< data.length; i++){
								$("#goods_spec tbody").append('<tr>'+
									'<input type="hidden" name="recommend_good_ids[]" value="'+data[i].goods_id+'" />'+
									'<td>'+data[i].goods_id+'</td>'+
									'<td>'+data[i].name+'</td>'+
									'<td>'+data[i].price+'</td>'+
									'<td width="70px">'+
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
				var goods_id = $(t).parents("tr").find('[name="recommend_good_ids[]"]').val();
				var _selected_goods_arr = _selected_goods.split(',');
				var _selected_goods_arr_new = [];
				for(var i = 0;i< _selected_goods_arr.length;i++){
					if(_selected_goods_arr[i] != goods_id){
						_selected_goods_arr_new.push(_selected_goods_arr[i]);
					}
				}
				_selected_goods = _selected_goods_arr_new.join(",");
				$(t).parents("tr").eq(0).remove();
			}
		}

		KindEditor.ready(function(K){
			var editor = K.editor({
				allowFileManager : true
			});
            $('.J_selectImage').click(function(){
                var upload_file_btn = $(this);
                editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}&type=shop";
                editor.loadPlugin('image', function(){
                    editor.plugin.imageDialog({
                        showRemote : false,
                        clickFn : function(url, title, width, height, border, align) {
                            var avatar_detail_length = $('#avatar_detail').find('li').length;
                            var li_length = upload_file_btn.siblings('.avatar_list').find('li').length+avatar_detail_length;
                                $('#avatar_show').show();
                              
                                upload_file_btn.siblings('.input-image').val(url);
                                
                                var html='<li class="upload_pic_li" style="float:left;list-style:none;"><img src="'+url+'" style="width:90px;height:50px;margin-right:5px;"><br></li>';
                                upload_file_btn.siblings('.avatar_list').html(html)
                                editor.hideDialog();
                                

                        }
                    });
                });
            });
        })

        $('input[name=preference_effect_date_type]').on('click', function () {
            if ($(this).val() == 2) {
                $('.preference_effect_date_tr').show();
            } else {
                $('.preference_effect_date_tr').hide();
            }
        })

        $('input[name=preference_effect_time_type]').on('click', function () {
            if ($(this).val() == 2) {
                $('.preference_effect_time_tr').show();
            } else {
                $('.preference_effect_time_tr').hide();
            }
        })
        $('input[name=recommend_effect_date_type]').on('click', function () {
            if ($(this).val() == 2) {
                $('.recommend_effect_date_tr').show();
            } else {
                $('.recommend_effect_date_tr').hide();
            }
        })

        $('input[name=recommend_effect_time_type]').on('click', function () {
            if ($(this).val() == 2) {
                $('.recommend_effect_time_tr').show();
            } else {
                $('.recommend_effect_time_tr').hide();
            }
        })
        $('input[name=preference_effect_date_type]:checked').trigger('click');
        $('input[name=preference_effect_time_type]:checked').trigger('click');
        $('input[name=recommend_effect_date_type]:checked').trigger('click');
        $('input[name=recommend_effect_time_type]:checked').trigger('click');
	</script>
<include file="Public:footer"/>