$(function(){
	// 城市列表
	$('.city_set li').hover(function(){
		$(this).addClass('hover');
	},function(){
		$(this).removeClass('hover');
	});
	
	
	$('#site_sel_pro').change(function(){
		var $pid=$(this).val();
		if($pid == 0){
			$('#site_sel_city').html('<option value="">请选择</option>');
			return false;
		}
		$.getJSON(city_action,{'pid':$pid},function($data){
			if($data||$data!=null){
				var $html='';
				$.each($data,function($i,$v){
					$html+='<option value="'+$v.area_id+'">'+$v.area_name+'</option>';
				});
				$('#site_sel_city').html($html);
			}else{
				$('#site_sel_city').html('<option value="">请选择</option>');
			}
		}).error(function(){
			$('#site_sel_city').html('<option value="">获取城市失败</option>');
		});
	});
	
	$('.hd_left form').submit(function(){
		var $city=$('#site_sel_city').val();
		if($city==''){
			alert('请选择城市！');
		}else{
			choose_city($city);
		}
		return false;
	});
});

function choose_city(city_id){
	$.cookie("now_city",city_id,{expires:365,path:"/"}); 
	// location.href = './';
	location.href = './index.php';
}