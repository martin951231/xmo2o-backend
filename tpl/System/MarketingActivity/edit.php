<include file="Public:header"/>
<style type="text/css">
	#choose_city{
		font-size: 18px;
	}
	.city_view{
		margin-bottom: 20px;   
		border-bottom: 1px solid #ccc;
    	padding: 10px;
    	position: relative;
	}
	.city_view .shade{
		position: absolute;
		top:0;
		right: 0;
		bottom: 0;
		left: 0;
		background: #ccc;
		opacity: 0.3;
	}
	.city_view div{
		margin-bottom: 15px;
	}
	.del_btn{
		cursor: pointer;
		background: #f85e5e;
	    color: #fff;
	    display: inline-block;
	    padding: 0 10px;
	    height: 20px;
	    line-height: 20px;
	    -webkit-border-radius: 2px;
	    border-radius: 2px;
	    font-size: 12px;
	    min-height: 20px;
	    margin-left: 15px;
	}
	.choose_add_btn{
		color: #fff;
		cursor: pointer;
	    border: 1px solid #3582fb;
	    background-color: #3582fb;
	}
	.choose_goods_btn{
		cursor: pointer;
	}	

	.del_btn:link,.del_btn:visited,.del_btn:hover,.del_btn:active{
		color: #fff;
	}
	.choose_add_btn:link,.choose_add_btn:visited,.choose_add_btn:hover,.choose_add_btn:active{
		color: #fff;
	}
	.shade{
		background: #ccc;
	}
	.table_form{    margin-top: 20px;}
</style>
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('index')}">营销活动列表</a>
			<a href="{pigcms{:U('edit')}" class="on">编辑营销活动</a>
		</ul>
	</div>
	<form id="myform" method="post" action="{pigcms{:U('edit')}" frame="true" refresh="true">
		<input type="hidden" name="activity_id" value="{pigcms{$_GET['activity_id']}">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">	
			<tr>
				<td width="100"><span style="color: red;margin-right: 2px;">*</span>活动名称：</td>
				<td>
					<input type="text"  style="width:300px;" class="input fl" name="title" value="{pigcms{$info.title}"  validate="required:true" autocomplete="off" />
				</td>
			</tr>
			<tr>
				<td width="100">状态：</td>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$info['status'] eq 1">selected</if>"><span>开启</span><input type="radio" name="status" value="1"  <if condition="$info['status'] eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$info['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0"  <if condition="$info['status'] eq 0">checked="checked"</if> /></label></span>
				</td>
			</tr>
			<tr>
				<td width="100">参与城市：</td>
				<td class="city_list">
					<?php if ($detail): ?>
						<volist name="detail" id="det" key="k">
							<div class="city_view city_{pigcms{$k}" id="{pigcms{$k}" >
								 <if condition='$det["is_power"] neq 1'><div class="shade"></div> </if>
								<div id="city_{pigcms{$k}">
									<input type="hidden" name="city[]" value="{pigcms{$det.city_id}">
									<input type="hidden" name="province[]" value="{pigcms{$det.province_id}">
									<label><span style="color: red;margin-right: 2px;">*</span>城&nbsp;&nbsp;&nbsp;市：</label>
									<span id="choose_city">{pigcms{$det.area_name}</span>
									<if condition="$det['is_power'] eq 1">
										<a class="btn btn-success del_btn" onclick="del_city('city_{pigcms{$k}',this)">删除</a>
									</if>
								</div>
								<div>
									<label><span style="color: red;margin-right: 2px;">*</span>优惠券：</label>
									<input type="hidden" name="coupon[]"  value="{pigcms{$det.coupon_ids}">
									<span id="choose_city"></span>
									<if condition="$det['is_power'] eq 1">
										<a class="button" onclick="select_coupon(this)">添加优惠券</a>
									</if>
									<table style="width:800px;" class="table_form" id='goods_spec'>
										<thead>
											<tr >
												<th style="text-align: left;">ID</th>
												<th style="text-align: left;">满减条件</th>
												<th style="text-align: left;">有效时间</th>
												<th style="text-align: left;">使用平台</th>
												<th style="text-align: left;">使用类别</th>
												<th style="text-align: left;">使用分类</th>
												<th style="text-align: left;">操作</th>
											</tr>
										</thead>
										<tbody>
											<volist name="det.coupon_list" id="vo">
											<tr>
												<td>{pigcms{$vo.coupon_id}</td>
												<td><php>if($vo['is_discount']==1){</php> 满 {pigcms{$vo.order_money|getFormatNumber=###} 打 {pigcms{$vo.discount_value|floatval}折  <php>}else{</php>满 {pigcms{$vo.order_money|getFormatNumber=###} 减 {pigcms{$vo.discount|getFormatNumber=###}<php>}</php></td>
												<td>{pigcms{$vo.start_time|date='Y-m-d',###} 到 {pigcms{$vo.end_time|date='Y-m-d',###}</td>
												<td><volist name="vo.platform" id="vv">{pigcms{$platform[$vv]}&nbsp;&nbsp;</volist></td>
												<td><if condition="$vo.cate_name eq 'all'">全部类别<else />{pigcms{$category[$vo['cate_name']]}</if></td>
												<td><if condition="$vo.cate_id eq '0'">全部分类<else />{pigcms{$vo['cate_id']}</if></td>
												<td>
												<if condition="$det['is_power'] eq 1">
													<button type="button" onClick="delThis(this)"  data-coupon_id="{pigcms{$vo.coupon_id}">移除</button>
												</if>
												</td>
											</tr>							
											</volist>
										</tbody>
									</table>
								</div>
								<div>
									<label><span style="color: red;margin-right: 2px;">*</span>活动时间：</label>
									<input type="text" class="input-text" name="start_time[]" style="width:140px;" id="d4311"  value="{pigcms{$det['start_time']|date='Y-m-d H:i:s',###}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm:ss'})" placeholder="开始时间"/>-
									<input type="text" class="input-text" name="end_time[]" style="width:140px;" id="d4311" value="{pigcms{$det['end_time']|date='Y-m-d H:i:s',###}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm:ss'})"  placeholder="结束时间" validate="required:true"/>
								</div>
							</div>
						</volist>
					<?php else: ?>
						<div class="city_view city_0" id="0">
							<div id="city_0">
								<input type="hidden" name="city[]">
								<input type="hidden" name="province[]">
								<label><span style="color: red;margin-right: 2px;">*</span>城&nbsp;&nbsp;&nbsp;市：</label>
								<span id="choose_city"></span>
								<a class="button" onclick="select_city('city_0',this)">选择城市</a>
								<a class="btn btn-success del_btn" onclick="del_city('city_0',this)">删除</a>
							</div>
							<div>
								<label><span style="color: red;margin-right: 2px;">*</span>优惠券：</label>
								<input type="hidden" name="coupon[]">
								<span id="choose_city"></span>
								<a class="button" onclick="select_coupon(this)">添加优惠券</a>
								<table style="width:800px;" class="table_form" id='goods_spec'>
									<thead>
										<tr >
											<th style="text-align: left;">ID</th>
											<th style="text-align: left;">满减条件</th>
											<th style="text-align: left;">有效时间</th>
											<th style="text-align: left;">使用平台</th>
											<th style="text-align: left;">使用类别</th>
											<th style="text-align: left;">使用分类</th>
											<th style="text-align: left;">操作</th>
										</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
							</div>
							<div>
								<label>活动时间：</label>
								<input type="text" class="input-text" name="start_time[]" style="width:140px;" id="d4311"  value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm:ss'})" placeholder="开始时间"/>-
								<input type="text" class="input-text" name="end_time[]" style="width:140px;" id="d4311" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm:ss'})"  placeholder="结束时间" validate="required:true"/>
							</div>
						</div>
					<?php endif ?>
					<a class="btn btn-success choose_add_btn" onclick="add_city(this)">添加城市</a>
				</td>			
			</tr>
		</table>
		<input type="hidden" id="_selected_city"  value="{pigcms{$city_ids}"/>
		<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
	</form>
</div>
	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
	<script type="text/javascript">

		function select_city(contentId,obj){
			var _selected_city = '';
			$.each($('input[name="city[]"]'),function(){
				_selected_city += $(this).val()+','
			})
			art.dialog.data('contentId', contentId);
			var city_id = $(obj).siblings('input[name="city[]"]').val();
			art.dialog.data('city_id', city_id);
			art.dialog.data('_selected_city', _selected_city);
			art.dialog.open('?g=System&c=MarketingActivity&a=select_city&selected='+_selected_city,{lock:true,title:'选择商品',width:700,height:730,yesText:'关闭',background: '#000',opacity: 0.45});
		}

				
		var _selected_coupon_ids = '{pigcms{$first["coupon_ids"]}';
		function select_coupon(obj){
			var that = $(obj);
			var url = '';
			url = '{pigcms{:U("Home/select_coupon",array("is_hide"=>1))}';
			// 已选优惠券id
			var _selected_coupon_ids = $(obj).siblings('input[name="coupon[]"]').val();
			url += '&selected='+_selected_coupon_ids;

			// 所选城市
			var city_id = $(obj).parents('div').prev('div').find('input[name="city[]"]').val();
			console.log($(obj).parents('div').prev('div'))
			console.log($(obj).parents('div').prev('div').find('input[name="city[]"]'))
			url += '&city_id='+city_id;
			if (!city_id) {
				art.dialog.tips('请先选择城市');return false;
			}

			layer.open({
				type: 2,
				area: ['75%', '700px'],
				fixed: false,
				offset:'100px',
				maxmin: true,
				content: url,
				btn: ['确定', '取消'],
				yes: function(index, layero){
					var frameId = $(layero).find("iframe").attr('id');
					var selected = $.trim($(window.frames[frameId].document).find('[name="selected"]').val());
					
					_selected_coupon_ids = selected;
					that.siblings('input[name="coupon[]"]').val(_selected_coupon_ids);
					that.siblings("table").find('tbody').empty();
					$.post("{pigcms{:U('Home/ajaxGetCoupon')}",{selected:selected},function(data){
						if(data.length > 0){
							var html = '';
							for(var i = 0; i< data.length; i++){
								html += '<tr>'+'<td>'+data[i].coupon_id+'</td>';
								if (data[i].is_discount=='1') {
									html += '<td>满'+data[i].order_money+'打'+data[i].discount_value+'折</td>'; 
								}else{
									html += '<td>满'+data[i].order_money+'减'+data[i].discount+'</td>';
								}
								html += 	'<td>'+data[i].start_time+'至'+data[i].end_time+'</td>'+
											'<td>'+data[i].platform_str+'</td>'+
											'<td>'+data[i].cate_name+'</td>'+
											'<td>'+data[i].cate_id+'</td>'+
											'<td>'+
													'<button type="button" onClick="delThis(this)" data-coupon_id="'+data[i].coupon_id+'">移除</button>'+
											'</td>'+
										'</tr>'
							}
							that.siblings("table").find('tbody').html(html);
						}
					},'json');
				    layer.close(index);
				},
				btn2: function(index){
					layer.close(index);
				}
			});
		}

		function delThis(t,type=''){
			if(confirm('{pigcms{:L_("你确定要移除此优惠券吗？")}')){
				var _selected_coupon_ids = $(t).parents("table").siblings('input[name="coupon[]"]').val();
				var coupon_id = $(t).data('coupon_id');
				var _selected_coupon_ids_arr = (_selected_coupon_ids).split(',');
				var _selected_coupon_ids_arr_new = [];
				for(var i = 0;i< _selected_coupon_ids_arr.length;i++){
					if(_selected_coupon_ids_arr[i] != coupon_id){
						_selected_coupon_ids_arr_new.push(_selected_coupon_ids_arr[i]);
					}
				}
				_selected_coupon_ids = _selected_coupon_ids_arr_new.join(",");
				console.log(coupon_id)
				$(t).parents("table").siblings('input[name="coupon[]"]').val(_selected_coupon_ids);
				$(t).parents("tr").eq(0).remove();
			}
		}

		function add_city(obj){
			var index = parseInt($('.city_view:last').attr('id'));
			var html = '<div class="city_view city_'+(index+1)+'" id="'+(index+1)+'">\
						<div id="city_'+(index+1)+'">\
							<input type="hidden" name="city[]">\
							<input type="hidden" name="province[]" >\
							<label><span style="color: red;margin-right: 2px;">*</span>城&nbsp;&nbsp;&nbsp;市：</label>\
							<span id="choose_city"></span>\
							<a class="button" onclick="select_city(\'city_'+(index+1)+'\',this)">选择城市</a>\
							<a class="btn btn-success del_btn" onclick="del_city(\'city_'+(index+1)+'\',this)">删除</a>\
						</div>\
						<div>\
							<label><span style="color: red;margin-right: 2px;">*</span>优惠券：</label>\
							<input type="hidden" name="coupon[]">\
							<span id="choose_city"></span>\
							<a class="button" onclick="select_coupon(this)">添加优惠券</a>\
							<table style="width:800px;" class="table_form" id=\'goods_spec\'>\
								<thead>\
									<tr >\
										<th style="text-align: left;">ID</th>\
										<th style="text-align: left;">满减条件</th>\
										<th style="text-align: left;">有效时间</th>\
										<th style="text-align: left;">使用平台</th>\
										<th style="text-align: left;">使用类别</th>\
										<th style="text-align: left;">使用分类</th>\
										<th style="text-align: left;">操作</th>\
									</tr>\
								</thead>\
								<tbody></tbody>\
							</table>\
						</div>\
						<div>\
							<label><span style="color: red;margin-right: 2px;">*</span>活动时间：</label>\
							<input type="text" class="input-text" name="start_time[]" style="width:140px;" id="d4311"  value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:\'yyyy-MM-dd HH:mm:ss\'})" placeholder="开始时间"/>-\
							<input type="text" class="input-text" name="end_time[]" style="width:140px;" id="d4311" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:\'yyyy-MM-dd HH:mm:ss\'})"  placeholder="结束时间" validate="required:true"/>\
						</div>\
					</div>';
			$(obj).before(html)
		}

		function del_city(city_id,obj){
			if(confirm('{pigcms{:L_("删除该城市后，已经设置的信息将会被清空，不可恢复，是否确认删除")}')){
				
				$('.'+city_id).remove();
			}
		}
	</script>
<include file="Public:footer"/>

