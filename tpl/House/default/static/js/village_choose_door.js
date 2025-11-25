//显示: 楼栋
function show_single(){

	if (is_door=='undefined' && !is_door) {
		is_door = 0;
	}
	$.post(choose_single,{is_door: is_door},function(result){
		result = $.parseJSON(result);

		if(result.status == 1){
			var area_dom = '<select id="choose_single" name="single_id" class="col-sm-1" style="margin-right:10px;min-width: 15rem;">';
			area_dom+= '<option value="0" style="display: none;" '+(0==$('#choose_village').attr('single_id') ? 'selected="selected"' : '')+'>请选择楼栋</option>';
			$.each(result.cat_list,function(i,item){
				if (item.is_door) {
					var val = 'door_'+item.public_area_id;
					var single_id = $('#choose_village').attr('single_id');
					area_dom+= '<option value="'+val+'" '+(val==single_id ? 'selected="selected"' : '')+'>'+item.public_area_name+'</option>';
				} else {
					area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_village').attr('single_id') ? 'selected="selected"' : '')+'>'+item.single_name+'</option>';
				}
			});
			area_dom+= '</select>';
			$('#choose_village').prepend(area_dom);
			show_floor($('#choose_single').find('option:selected').attr('value'),$('#choose_single').find('option:selected').html());
			$('#choose_single').change(function(){
				show_floor($(this).find('option:selected').attr('value'),$(this).find('option:selected').html());
			});

		}else{
			layer.msg('请联系管理员添加楼栋信息');
		}
	});
}
//显示单元
function show_floor(id,name){
	$.post(choose_floor,{cat_id:id,name:name},function(result,is_door){
		result = $.parseJSON(result);
		if(result.status == 1 || id==0){
			var area_dom = '<select id="choose_floor" name="floor_id" class="col-sm-1" style="margin-right:10px;min-width: 15rem;">';
			area_dom+= '<option value="0" style="display: none;" '+(0==$('#choose_village').attr('floor_id') ? 'selected="selected"' : '')+'>请选择单元</option>';

			var device_type = $('#device_type').val();
			if ( device_type && (device_type==3 || device_type==5)) {
				area_dom += '<option value="-2" ' + ('-2' == $('#choose_village').attr('floor_id') ? 'selected="selected"' : '') + '>全部单元</option>';
			}
			$.each(result.cat_list,function(i,item){
				area_dom+= '<option value="'+item.floor_id+'" '+(item.floor_id==$('#choose_village').attr('floor_id') ? 'selected="selected"' : '')+'>'+item.floor_name+'</option>';
			});
			area_dom+= '</select>';
			if(document.getElementById('choose_floor')){
				$('#choose_floor').replaceWith(area_dom);
			}else if(document.getElementById('choose_single')){
				$('#choose_single').after(area_dom);
			}else{
				$('#choose_village').prepend(area_dom);
			}
			if ($('#choose_floor').find('option:selected').attr('value')!='0') {
				show_layer($('#choose_village').attr('floor_id'));
			} else if(document.getElementById('choose_layer')){
				var area_dom = '<select id="choose_layer" name="layer_id" class="col-sm-1" style="margin-right:10px;min-width: 15rem;">';
				area_dom+= '<option value="0" style="display: none;">请先选择单元</option>';
				area_dom+= '</select>';
				$('#choose_layer').replaceWith(area_dom);
				$('#choose_device_layer').val('');
			}

			show_vacancy($('#choose_floor').find('option:selected').attr('value'),$('#choose_floor').find('option:selected').html());
			$('#choose_floor').change(function(){
				var device_type = $('#device_type').val();
				if ( device_type && (device_type==3 || device_type==5)){
					if ($(this).val()>0) {
						var valf = $(this).find('option:selected').attr('value');
						// alert(valf);
						var html = '<div class="floor_info" style="margin-right: 5px;width:auto; display:inline-block !important; display:inline; ">'+$('#choose_single').find('option:selected').html()+'#'+$(this).find('option:selected').html()+'<span class="del'+valf+'" style="color: red;font-size:18px;cursor:pointer">×</span><input class="input_val" type="hidden" name="floor_arr[]" value="'+valf+'"></div>';

						var arrInputValues = new Array();
						$(".input_val").each(function(){
							arrInputValues.push($(this).val());
						});
						if(arrInputValues.indexOf(valf)<0) {
							$('.multiple_floor_info_box').append(html);
						}

						$('.del'+valf).click(function () {
							$(this).closest('.floor_info').remove();
						})
					}
					if($(this).val() == '-2'){
						var arrInputValues = new Array();
						$(".input_val").each(function(){
							arrInputValues.push($(this).val());
						});
						$.each(result.cat_list,function(i,item) {
							// var valf = $(this).find('option:selected').attr('value');
							// alert(valf);
							var html = '<div class="floor_info" style="margin-right: 5px;width:auto; display:inline-block !important; display:inline; ">' + $('#choose_single').find('option:selected').html() + '#' + item.floor_name + '<span class="del' + valf + '" style="color: red;font-size:18px;cursor:pointer">×</span><input class="input_val" type="hidden" name="floor_arr[]" value="' + item.floor_id + '"></div>';
							if(arrInputValues.indexOf(item.floor_id)<0)
							{
								$('.multiple_floor_info_box').append(html);
							}
							$('.del' + valf).click(function () {
								$(this).closest('.floor_info').remove();
							})
						})
					}
				}
				show_vacancy($(this).find('option:selected').attr('value'),$(this).find('option:selected').html());
				show_layer($(this).find('option:selected').attr('value'));
			});

		}else{
			var strs = id.split("_");
			if (strs[0]=='door' && document.getElementById('choose_floor')) {
				var device_type = $('#device_type').val();
				if (device_type && (device_type==3 || device_type==5)) {
					$('.multiple_floor_info_box').html('<label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"></label>');
				}
				document.getElementById('choose_floor').remove();
				var area_dom = '<select id="choose_layer" name="layer_id" class="col-sm-1" style="margin-right:10px;min-width: 15rem;">';
				area_dom+= '<option value="0" style="display: none;">请先选择单元</option>';
				area_dom+= '</select>';
				if(document.getElementById('choose_layer')){
					$('#choose_layer').replaceWith(area_dom);
				}else{
					$('#choose_layer_field').prepend(area_dom);
				}
				$('#choose_device_layer').val('');
			} else if(strs[0]!='door') {
				var area_dom = '<select id="choose_floor" name="floor_id" class="col-sm-1" style="margin-right:10px;min-width: 15rem;">';
				area_dom+= '<option value="0" style="display: none;" '+(0==$('#choose_village').attr('floor_id') ? 'selected="selected"' : '')+'>请选择单元</option>';
				area_dom+= '</select>';
				if(document.getElementById('choose_floor')){
					$('#choose_floor').replaceWith(area_dom);
				}else if(document.getElementById('choose_single')){
					$('#choose_single').after(area_dom);
				}else{
					$('#choose_village').prepend(area_dom);
				}

				layer.msg('请联系管理员添加单元信息');
			} else if (strs[0]=='door') {
				var device_type = $('#device_type').val();
				if (device_type && (device_type==3 || device_type==5)) {
					$('.multiple_floor_info_box').html('');
				}
			}
		}
	});
}

//显示房子
function show_vacancy(id,name){
	if(typeof($('#choose_village').attr('vacancy_id'))!='undefined'){
		$.post(choose_vacancy,{cat_id:id,name:name},function(result){
			result = $.parseJSON(result);
			if(result.status == 1 || id==0){
				var area_dom = '<select id="choose_vacancy" name="vacancy_id" class="col-sm-1" style="min-width: 15rem;">';
				area_dom+= '<option value="0" style="display: none;" '+(0==$('#choose_village').attr('vacancy_id') ? 'selected="selected"' : '')+'>请选择房间</option>';
				$.each(result.cat_list,function(i,item){
					area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_village').attr('vacancy_id') ? 'selected="selected"' : '')+'>'+item.room+'</option>';
				});
				area_dom+= '</select>';
				if(document.getElementById('choose_vacancy')){
					$('#choose_vacancy').replaceWith(area_dom);
				}else if(document.getElementById('choose_floor')){
					$('#choose_floor').after(area_dom);
				}else{
					$('#choose_village').prepend(area_dom);
				}
			}else{
				if(typeof($('#choose_village').attr('vacancy_id'))!='undefined'){
					var area_dom = '<select id="choose_vacancy" name="vacancy_id" class="col-sm-1" style="min-width: 15rem;">';
					area_dom+= '<option value="0" style="display: none;" '+(0==$('#choose_village').attr('vacancy_id') ? 'selected="selected"' : '')+'>请选择房间</option>';
					area_dom+= '</select>';
					if(document.getElementById('choose_vacancy')){
						$('#choose_vacancy').replaceWith(area_dom);
					}else if(document.getElementById('choose_floor')){
						$('#choose_floor').after(area_dom);
					}else{
						$('#choose_village').prepend(area_dom);
					}
				}
				layer.msg('请联系管理员添加房间信息');
			}
		});
	}
}

//显示楼层
function show_layer(id) {
	if ($('input[name="device_model"]:checked').val()!=1) {
		return false;
	}
	$.post(show_layer_url,{floor_id: id},function(result){
		result = $.parseJSON(result);
		if(result.status == 1){
			$('.choose_layer_box').show();
			$('.choose_layer_box').css('display','flex');
			var area_dom = '<select id="choose_layer" name="layer_id" class="col-sm-1" style="margin-right:10px;min-width: 15rem;">';
			area_dom+= '<option value="0" style="display: none;">请选择楼层</option>';
			$.each(result.list,function(i,item){
				area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_layer_field').attr('layer_id') ? 'selected="selected"' : '')+'>'+item.layerNum+'</option>';
			});
			area_dom+= '</select>';
			if(document.getElementById('choose_layer')){
				$('#choose_layer').replaceWith(area_dom);
			}else{
				$('#choose_layer_field').prepend(area_dom);
			}

			$('#choose_layer').change(function(){
				$('#choose_device_layer').val($(this).find('option:selected').html());
			});

		}else{
			layer.msg('请联系管理员添加对应单元楼层信息');
		}
	});
}


$(function(){
	//检测是否需要显示楼栋
	if(document.getElementById('choose_village')){
		show_single();
	}
	$('input[name="device_model"]').click(function () {
		var val = $(this).val();
		var choose_floor = $('#choose_floor').val();
		if (1==val && choose_floor) {
			show_layer(choose_floor);
		} else if(val) {
			$('.choose_layer_box').hide();
			$('#choose_layer_field').html('');
		}
	})
});