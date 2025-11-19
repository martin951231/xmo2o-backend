//显示省份
function show_provincess(){

	$.post(choose_provincess,function(result){
		result = $.parseJSON(result);

		if(result.error == 0){
			var area_dom = '<select id="choose_provincess" name="province_idss" class="col-sm-1" style="margin-right:10px;min-width: 10rem;">';
			area_dom+= '<option value="0" '+(0==$('#choose_street').attr('province_idss') ? 'selected="selected"' : '')+'>全部省</option>';
			$.each(result.list,function(i,item){
				area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_street').attr('province_idss') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
			});
			area_dom+= '</select>';
			$('#choose_street').prepend(area_dom);
			show_cityss($('#choose_provincess').find('option:selected').attr('value'),$('#choose_provincess').find('option:selected').html(),1);
			$('#choose_provincess').change(function(){
				show_cityss($(this).find('option:selected').attr('value'),$(this).find('option:selected').html(),1);
			});

		}else if(result.error == 2){
			var area_dom = '<select id="choose_province_hide" name="province_idss" style="display:none;min-width: 10rem;">';
			area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
			area_dom += '</select>';
			$('#choose_street').prepend(area_dom);
			show_cityss(result.id,result.name,0);
		}else{
			// window.top.msg(0,result.info,true);
			alert(result.info);
			window.top.closeiframe();
		}
	});
}
//显示城市
function show_cityss(id,name,type){
	$.post(choose_cityss,{id:id,name:name,type:type},function(result){
		result = $.parseJSON(result);
		if(result.error == 0){
			var area_dom = '<select id="choose_cityss" name="city_idss" class="col-sm-1" style="margin-right:10px;min-width: 10rem;">';
			area_dom+= '<option value="0" '+(0==$('#choose_street').attr('city_idss') ? 'selected="selected"' : '')+'>全部市</option>';
			$.each(result.list,function(i,item){
				area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_street').attr('city_idss') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
			});
			area_dom+= '</select>';
			if(document.getElementById('choose_cityss')){
				$('#choose_cityss').replaceWith(area_dom);
			}else if(document.getElementById('choose_provincess')){
				$('#choose_provincess').after(area_dom);
			}else{
				$('#choose_street').prepend(area_dom);
			}
			show_areass($('#choose_cityss').find('option:selected').attr('value'),$('#choose_cityss').find('option:selected').html(),1);
			$('#choose_cityss').change(function(){
				show_areass($(this).find('option:selected').attr('value'),$(this).find('option:selected').html(),1);
			});

		}else if(result.error == 2){
			var area_dom = '<select id="choose_city_hide" name="city_idss" style="display:none;min-width: 10rem;">';
			area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
			area_dom += '</select>';
			$('#choose_street').prepend(area_dom);
			show_areass(result.id,result.name,0);
		}else{
			// window.top.msg(0,result.info,true,5);
			alert(result.info);
			window.top.closeiframe();
		}
	});
}

//显示区域
function show_areass(id,name,type){
	if(typeof($('#choose_street').attr('area_id'))!='undefined'){
		$.post(choose_area,{id:id,name:name,type:type},function(result){
			result = $.parseJSON(result);
			if(result.error == 0){
				var area_dom = '<select id="choose_areass" name="area_id" class="col-sm-1" style="margin-right:10px;min-width: 10rem;">';
				area_dom+= '<option value="0" '+(0==$('#choose_street').attr('area_id') ? 'selected="selected"' : '')+'>全部区</option>';
				$.each(result.list,function(i,item){
					area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_street').attr('area_id') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
				});
				area_dom+= '</select>';
				if(document.getElementById('choose_areass')){
					$('#choose_areass').replaceWith(area_dom);
				}else if(document.getElementById('choose_cityss')){
					$('#choose_cityss').after(area_dom);
				}else{
					$('#choose_street').prepend(area_dom);
				}
				show_street($('#choose_areass').find('option:selected').attr('value'),$('#choose_areass').find('option:selected').html(),1);
				$('#choose_areass').change(function(){
					show_street($(this).find('option:selected').attr('value'),$(this).find('option:selected').html(),1);
				});

			}else if(result.error == 2){
				var area_dom = '<select id="choose_areass_hide" name="area_id" style="display:none;min-width: 10rem;" >';
				area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
				area_dom += '</select>';
				$('#choose_street').after(area_dom);
				show_street(result.id,result.name,0);
			}else{
				// window.top.msg(0,result.info,true,5);
				alert(result.info);
				window.top.closeiframe();
			}
		});
	}
}


//显示街道
function show_street(id,name,type){
	if(typeof($('#choose_street').attr('street_idss'))!='undefined' && typeof(choose_street)!='undefined'){
		$.post(choose_street,{id:id,name:name,type:type},function(result){
			result = $.parseJSON(result);
			if(result.error == 0){
				var area_dom = '<select id="choose_streetss" name="street_idss" class="col-sm-1" style="margin-right:10px;min-width: 15rem;">';
				area_dom+= '<option value="0" '+(0==$('#choose_street').attr('street_idss') ? 'selected="selected"' : '')+'>全部街道/乡镇</option>';
				$.each(result.list,function(i,item){
					area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_street').attr('street_idss') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
				});
				area_dom+= '</select>';
				if(document.getElementById('choose_streetss')){
					$('#choose_streetss').replaceWith(area_dom);
				}else if(document.getElementById('choose_areass')){
					$('#choose_areass').after(area_dom);
				}else{
					$('#choose_street').prepend(area_dom);
				}
				show_community($('#choose_streetss').find('option:selected').attr('value'),$('#choose_streetss').find('option:selected').html(),1);
				$('#choose_streetss').change(function(){
					show_community($(this).find('option:selected').attr('value'),$(this).find('option:selected').html(),1);
				});

			}else if(result.error == 2){
				var area_dom = '<select id="choose_streetss_hide" name="street_idss" style="display:none;min-width: 15rem;">';
				area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
				area_dom += '</select>';
				$('#choose_street').after(area_dom);
				show_community(result.id,result.name,0);
			}else{
				// window.top.msg(0,result.info,true,5);
				alert(result.info);
				//window.top.closeiframe();
			}
		});
	}
}
//显示社区
function show_community(id,name,type){
	if(typeof($('#choose_street').attr('community_idss'))!='undefined' && typeof(choose_community)!='undefined'){
		$.post(choose_community,{id:id,name:name,type:type},function(result){
			result = $.parseJSON(result);
			if(result.error == 0){
				var area_dom = '<select id="choose_communityss" name="community_idss" class="col-sm-1" style="margin-right:10px;min-width: 15rem;">';
				area_dom+= '<option value="0" '+(0==$('#choose_street').attr('community_idss') ? 'selected="selected"' : '')+'>全部社区/村</option>';
				$.each(result.list,function(i,item){
					area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_street').attr('community_idss') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
				});
				area_dom+= '</select>';
				if(document.getElementById('choose_communityss')){
					$('#choose_communityss').replaceWith(area_dom);
				}else if(document.getElementById('choose_streetss')){
					$('#choose_streetss').after(area_dom);
				}else{
					$('#choose_street').prepend(area_dom);
				}

			}else if(result.error == 2){
				var area_dom = '<select id="choose_communityss_hide" name="community_idss" style="display:none;min-width: 15rem;">';
				area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
				area_dom += '</select>';
				$('#choose_communityss').after(area_dom);
			}else{
				// window.top.msg(0,result.info,true,5);
				alert(result.info);
				//window.top.closeiframe();
			}
		});
	}
}
$(function(){
	//检测是否需要显示城市
	if(document.getElementById('choose_street')){
		show_provincess();
	}
});