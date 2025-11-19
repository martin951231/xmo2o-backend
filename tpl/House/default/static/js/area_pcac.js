//显示省份
function show_provincess(){
	
	$.post(choose_provincess,function(result){
		result = $.parseJSON(result);
			
		if(result.error == 0){
			var area_dom = '<select id="choose_provincess" name="province_idss" class="col-sm-1" style="margin-right:10px;">';
			area_dom+= '<option value="0" '+(0==$('#choose_pca').attr('province_idss') ? 'selected="selected"' : '')+'>全部省</option>';
			$.each(result.list,function(i,item){
				area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_pca').attr('province_idss') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
			});
			area_dom+= '</select>';
			$('#choose_pca').prepend(area_dom);
			show_cityss($('#choose_provincess').find('option:selected').attr('value'),$('#choose_provincess').find('option:selected').html(),1);
			$('#choose_provincess').change(function(){
				show_cityss($(this).find('option:selected').attr('value'),$(this).find('option:selected').html(),1);
			});
		
		}else if(result.error == 2){
			var area_dom = '<select id="choose_province_hide" name="province_idss" style="display:none;">';
			area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
			area_dom += '</select>';
			$('#choose_pca').prepend(area_dom);
			show_cityss(result.id,result.name,0);
		}else{
			window.top.msg(0,result.info,true);
			window.top.closeiframe();
		}
	});
}
//显示城市

function show_cityss(id,name,type){
	$.post(choose_cityss,{id:id,name:name,type:type},function(result){
		result = $.parseJSON(result);
		if(result.error == 0){
			var area_dom = '<select id="choose_cityss" name="city_idss" class="col-sm-1" style="margin-right:10px;">';
			area_dom+= '<option value="0" '+(0==$('#choose_pca').attr('city_idss') ? 'selected="selected"' : '')+'>全部市</option>';
			$.each(result.list,function(i,item){
				area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_pca').attr('city_idss') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
			});
			area_dom+= '</select>';
			if(document.getElementById('choose_cityss')){
				$('#choose_cityss').replaceWith(area_dom);
			}else if(document.getElementById('choose_provincess')){
				$('#choose_provincess').after(area_dom);
			}else{
				$('#choose_pca').prepend(area_dom);
			}
			show_areass($('#choose_cityss').find('option:selected').attr('value'),$('#choose_cityss').find('option:selected').html(),1);
			$('#choose_cityss').change(function(){
				show_areass($(this).find('option:selected').attr('value'),$(this).find('option:selected').html(),1);
			});
			
		}else if(result.error == 2){
			var area_dom = '<select id="choose_city_hide" name="city_idss" style="display:none;">';
			area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
			area_dom += '</select>';
			$('#choose_pca').prepend(area_dom);
			show_areass(result.id,result.name,0);
		}else{
			window.top.msg(0,result.info,true,5);
			window.top.closeiframe();
		}
	});
}

//显示区域
function show_areass(id,name,type){
	if(typeof($('#choose_pca').attr('area_id'))!='undefined'){
		$.post(choose_area,{id:id,name:name,type:type},function(result){
			result = $.parseJSON(result);
			if(result.error == 0){
				var area_dom = '<select id="choose_areass" name="area_id" class="col-sm-1" style="margin-right:10px;">';
				area_dom+= '<option value="0" '+(0==$('#choose_pca').attr('area_id') ? 'selected="selected"' : '')+'>全部区</option>';
				$.each(result.list,function(i,item){
					area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_pca').attr('area_id') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
				});
				area_dom+= '</select>';
				if(document.getElementById('choose_areass')){
					$('#choose_areass').replaceWith(area_dom);
				}else if(document.getElementById('choose_cityss')){
					$('#choose_cityss').after(area_dom);
				}else{
					$('#choose_pca').prepend(area_dom);
				}
                show_circle($('#choose_areass').find('option:selected').attr('value'),$('#choose_areass').find('option:selected').html(),1);
                $('#choose_areass').change(function(){
                    show_circle($(this).find('option:selected').attr('value'),$(this).find('option:selected').html(),1);
                });
			}else if(result.error == 2){
				var area_dom = '<select id="choose_areass_hide" name="area_id" style="display:none;">';
				area_dom += '<option value="'+result.id+'">'+result.name+'</option>';
				area_dom += '</select>';
				$('#choose_cityss').after(area_dom);
			}else{
				window.top.msg(0,result.info,true,5);
				window.top.closeiframe();
			}
		});
	}
}
function show_circle(id,name,type){
    $.post(choose_circle,{id:id,name:name,type:type},function(result){
        result = $.parseJSON(result);
        if(result.error == 0){
            var area_dom = '<select id="choose_circle" name="circle_id" class="col-sm-1" style="margin-right:10px;">';
            area_dom+= '<option value="0" '+(0==$('#choose_pca').attr('circle_id') ? 'selected="selected"' : '')+'>全部商圈</option>';
            $.each(result.list,function(i,item){
                area_dom+= '<option value="'+item.id+'" '+(item.id==$('#choose_pca').attr('circle_id') ? 'selected="selected"' : '')+'>'+item.name+'</option>';
            });
            area_dom+= '</select>';
            if(document.getElementById('choose_circle')){
                $('#choose_circle').replaceWith(area_dom);
            }else if(document.getElementById('choose_city')){
                $('#choose_areass').after(area_dom);
            }else{
                $('#choose_pca').after(area_dom);
            }
            // if (typeof(show_circle) == 'undefined') {

            // show_market($('#choose_circle').find('option:selected').attr('value'),$('#choose_circle').find('option:selected').html(),1);
            // $('#choose_circle').change(function(){
            //     show_market($(this).find('option:selected').attr('value'),$(this).find('option:selected').html(),1);
            // });

            // }
        }else{
            $('#choose_circle').replaceWith('');
			window.top.msg(0,result.info,true,5);
			window.top.closeiframe();
//             alert(result.info);
        }
    });
}
$(function(){
	//检测是否需要显示城市
	if(document.getElementById('choose_pca')){
		show_provincess();
	}
});