//显示: 楼栋
function show_single(){

	if (is_door=='undefined' && !is_door) {
		is_door = 0;
	}
	$.post(choose_single,{is_door: is_door},function(result){
		console.log(3);
		console.log(result);
		result = $.parseJSON(result);
		var title = '全部楼栋';
		if (1==is_door) {
			 title = '请选择楼栋或公共区域';
		}

		if(result.status == 1){
			var area_dom = '<select id="choose_single" name="single_id" class="col-sm-1" style="margin-right:10px;min-width: 18rem;">';
			area_dom+= '<option value="0" '+(0==$('#choose_village').attr('single_id') ? 'selected="selected"' : '')+'>'+title+'</option>';
			$.each(result.cat_list,function(i,item){
				if (item.is_door) {
					var val = 'door_'+item.public_area_id;
					var single_id = $('#choose_village').attr('single_id');
					console.log('val',val);
					console.log('single_id',single_id);
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
	$.post(choose_floor,{cat_id:id,name:name},function(result){
		result = $.parseJSON(result);
		var title = '全部单元';
		if (1==is_door) {
			title = '请选择单元';
		}
		if(result.status == 1 || id==0){
			var area_dom = '<select id="choose_floor" name="floor_id" class="col-sm-1" style="margin-right:10px;min-width: 15rem;">';
			area_dom+= '<option value="0" '+(0==$('#choose_village').attr('floor_id') ? 'selected="selected"' : '')+'>'+title+'</option>';
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
			show_vacancy($('#choose_floor').find('option:selected').attr('value'),$('#choose_floor').find('option:selected').html());
			$('#choose_floor').change(function(){
				show_vacancy($(this).find('option:selected').attr('value'),$(this).find('option:selected').html());
			});

		}else{
			var strs = id.split("_");
			console.log('strs',strs)
			if (strs[0]=='door' && document.getElementById('choose_floor')) {
				document.getElementById('choose_floor').remove();
			} else if(strs[0]!='door') {
				var area_dom = '<select id="choose_floor" name="floor_id" class="col-sm-1" style="margin-right:10px;min-width: 15rem;">';
				area_dom+= '<option value="0" '+(0==$('#choose_village').attr('floor_id') ? 'selected="selected"' : '')+'>全部单元</option>';
				area_dom+= '</select>';
				if(document.getElementById('choose_floor')){
					console.log('choose_floor',1)
					$('#choose_floor').replaceWith(area_dom);
				}else if(document.getElementById('choose_single')){
					console.log('choose_floor',2)
					$('#choose_single').after(area_dom);
				}else{
					console.log('choose_floor',3)
					$('#choose_village').prepend(area_dom);
				}

				layer.msg('请联系管理员添加单元信息');
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
				area_dom+= '<option value="0" '+(0==$('#choose_village').attr('vacancy_id') ? 'selected="selected"' : '')+'>全部房间</option>';
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
					area_dom+= '<option value="0" '+(0==$('#choose_village').attr('vacancy_id') ? 'selected="selected"' : '')+'>全部房间</option>';
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

$(function(){
	//检测是否需要显示楼栋
	console.log(1);
	if(document.getElementById('choose_village')){
		console.log(2);
		show_single();
	}
});