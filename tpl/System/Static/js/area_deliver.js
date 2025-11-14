//显示省份
function show_provincess(){
	$.post(choose_provincess,function(result){
		result = $.parseJSON(result);
		console.log(result);
		if(result.error == 0){
			var area_dom = '<select id="choose_provincess" name="province_idss" class="col-sm-1" style="margin-right:10px;">';
			area_dom+= '<option value="0" '+(0==$('#choose_city_for_deliver').attr('province_idss') ? 'selected="selected"' : '')+'>请选择</option>';
			$.each(result.list,function(i,item){
				area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_city_for_deliver').attr('province_idss') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
			});
			area_dom+= '</select>';
			$('#choose_city_for_deliver').prepend(area_dom);
			show_cityss($('#choose_provincess').find('option:selected').attr('value'),$('#choose_provincess').find('option:selected').html(),1);
			$('#choose_provincess').change(function(){
				show_cityss($(this).find('option:selected').attr('value'),$(this).find('option:selected').html(),1);
			});
		}else if(result.error == 2){
			var area_dom = '<select id="choose_provincess" name="province_idss" class="col-sm-1" style="margin-right:10px;">';
			area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
			area_dom += '</select>';
			$('#choose_city_for_deliver').prepend(area_dom);
			show_cityss(result.id,result.name,0);
		}else{
			window.o2oTop.msg(0,result.info,true);
			window.o2oTop.closeiframe();
		}
	});
}
//显示城市
function show_cityss(id,name,type){
	$.post(choose_cityss,{id:id,name:name,type:type},function(result){
		result = $.parseJSON(result);
		var selected_area = [];
		if(result.error == 0){
			var area_dom = '<select id="choose_cityss" name="city_idss" class="col-sm-1" style="margin-right:10px;">';
			area_dom+= '<option value="0" '+(0==$('#choose_city_for_deliver').attr('city_idss') ? 'selected="selected"' : '')+'>请选择</option>';
			$.each(result.list,function(i,item){
				if (item.id==$('#choose_city_for_deliver').attr('city_idss')) {
					area_dom+= '<option value="'+item.id+'" selected="selected" lng="'+item.lng+'" lat="'+item.lat+'"'+'>'+item.name+'</option>';
					selected_area = item;
				}else{
					area_dom+= '<option value="'+item.id+'" lng="'+item.lng+'" lat="'+item.lat+'">'+item.name+'</option>';
				}
			});
			area_dom+= '</select>';
			if(document.getElementById('choose_cityss')){
				$('#choose_cityss').replaceWith(area_dom);
			}else if(document.getElementById('choose_provincess')){
				$('#choose_provincess').after(area_dom);
			}else{
				$('#choose_city_for_deliver').prepend(area_dom);
			}
			show_areass($('#choose_cityss').find('option:selected').attr('value'),$('#choose_cityss').find('option:selected').html(),1);
			$('#choose_cityss').change(function(){
				show_areass($(this).find('option:selected').attr('value'),$(this).find('option:selected').html(),1);
			});
		}else if(result.error == 2){
			var area_dom = '<select id="choose_cityss" name="city_idss" class="col-sm-1" style="margin-right:10px;">';
			area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
			area_dom += '</select>';
			if(document.getElementById('choose_cityss')){
				$('#choose_cityss').replaceWith(area_dom);
			}else if(document.getElementById('choose_provincess')){
				$('#choose_provincess').after(area_dom);
			}else{
				$('#choose_city_for_deliver').prepend(area_dom);
			}
			selected_area = {
				'id':result.id,
				'name':result.name,
				'shop_main_page_show_ad':result.shop_main_page_show_ad,
				'shop_main_page_center_type':result.shop_main_page_center_type,
			}
		}else{
			window.o2oTop.msg(0,result.info,true,5);
			window.o2oTop.closeiframe();
		}
	});
}

//显示区域
function show_areass(id,name,type){
	$.post(choose_area,{id:id,name:name,type:type},function(result){
		result = $.parseJSON(result);
		var selected_area = [];
		if(result.error == 0){
			var area_dom = '<select id="choose_areass" name="area_idss" class="col-sm-1" style="margin-right:10px;">';
			area_dom+= '<option value="0" '+(0==$('#choose_city_for_deliver').attr('area_idss') ? 'selected="selected"' : '')+'>请选择</option>';
			$.each(result.list,function(i,item){
				if (item.id==$('#choose_city_for_deliver').attr('area_idss')) {
					area_dom+= '<option value="'+item.id+'" selected="selected" lng="'+item.lng+'" lat="'+item.lat+'"'+'>'+item.name+'</option>';
					selected_area = item;
				}else{
					area_dom+= '<option value="'+item.id+'" lng="'+item.lng+'" lat="'+item.lat+'">'+item.name+'</option>';
				}
			});
			area_dom+= '</select>';
			if(document.getElementById('choose_areass')){
				$('#choose_areass').replaceWith(area_dom);
			}else if(document.getElementById('choose_cityss')){
				$('#choose_cityss').after(area_dom);
			}else{
				$('#choose_city_for_deliver').prepend(area_dom);
			}
			show_street($('#choose_areass').find('option:selected').attr('value'),$('#choose_areass').find('option:selected').html(),1);
			$('#choose_areass').change(function(){
				show_street($(this).find('option:selected').attr('value'),$(this).find('option:selected').html(),1);
			});
		}else if(result.error == 2){
			var area_dom = '<select id="choose_areass" name="area_idss" class="col-sm-1" style="margin-right:10px;">';
			area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
			area_dom += '</select>';
			if(document.getElementById('choose_areass')){
				$('#choose_areass').replaceWith(area_dom);
			}else if(document.getElementById('choose_cityss')){
				$('#choose_cityss').after(area_dom);
			}else{
				$('#choose_city_for_deliver').prepend(area_dom);
			}
			selected_area = {
				'id':result.id,
				'name':result.name,
				'shop_main_page_show_ad':result.shop_main_page_show_ad,
				'shop_main_page_center_type':result.shop_main_page_center_type,
			}
		}else{
			window.o2oTop.msg(0,result.info,true,5);
			window.o2oTop.closeiframe();
		}
	});
}

function show_street(id,name,type){
	$.post(choose_street,{id:id,name:name,type:type},function(result){
		result = $.parseJSON(result);
		var selected_area = [];
		if(result.error == 0){
			var area_dom = '<select id="choose_street" name="street_id" class="col-sm-1" style="margin-right:10px;">';
			area_dom+= '<option value="0" '+(0==$('#choose_city_for_deliver').attr('street_id') ? 'selected="selected"' : '')+'>请选择</option>';
			$.each(result.list,function(i,item){
				if (item.id==$('#choose_city_for_deliver').attr('street_id')) {
					area_dom+= '<option value="'+item.id+'" selected="selected" lng="'+item.lng+'" lat="'+item.lat+'"'+'>'+item.name+'</option>';
					selected_area = item;
				}else{
					area_dom+= '<option value="'+item.id+'" lng="'+item.lng+'" lat="'+item.lat+'">'+item.name+'</option>';
				}
			});
			area_dom+= '</select>';
			if(document.getElementById('choose_street')){
				$('#choose_street').replaceWith(area_dom);
			}else if(document.getElementById('choose_areass')){
				$('#choose_areass').after(area_dom);
			}else{
				$('#choose_city_for_deliver').prepend(area_dom);
			}

		}else if(result.error == 2){
			var area_dom = '<select id="choose_street" name="street_id" class="col-sm-1" style="margin-right:10px;">';
			area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
			area_dom += '</select>';
			if(document.getElementById('choose_street')){
				$('#choose_street').replaceWith(area_dom);
			}else if(document.getElementById('choose_areass')){
				$('#choose_areass').after(area_dom);
			}else{
				$('#choose_city_for_deliver').prepend(area_dom);
			}
			selected_area = {
				'id':result.id,
				'name':result.name,
				'shop_main_page_show_ad':result.shop_main_page_show_ad,
				'shop_main_page_center_type':result.shop_main_page_center_type,
			}
		}else{
			window.o2oTop.msg(0,result.info,true,5);
			window.o2oTop.closeiframe();
		}
	});
}

$(function(){
	//检测是否需要显示城市
	if(document.getElementById('choose_city_for_deliver')){
		show_provincess();
	}
});