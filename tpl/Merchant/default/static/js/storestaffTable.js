$(function(){
	function refresh_n(){
		//隔5s刷新页面
		setInterval(function(){
			if ($('body').find('.layui-layer').length == 0) {
				$.get(ajax_tmp_table_url,{type_id:type_id},function(result){
					laytpl($('#table_html').html()).render(result.data, function(html){
						$('.table_body').html(html);
					});
				},'json'); 

			}
		},5000)
	}
	refresh_n();
						

	$('.table_body').height($(window).height() - 50 - 160 -63);
	var swiper = new Swiper('.swiper-container', {
        slidesPerView: 'auto',
        spaceBetween: 10,
		prevButton:'.swiper-button-prev',
		nextButton:'.swiper-button-next',
    });  
	// $('.tabele_cat li').eq(0).addClass('cur');
	$('.tabele_cat li').click(function(){
		$(this).addClass('cur').siblings('li').removeClass('cur');
		var index = layer.load(0, {shade: 0.1});
		location.href = $(this).data('href');
	});
	
	$('.table_tab div').click(function(){
		$(this).addClass('cur').siblings('div').removeClass('cur');
		$('.table_body li').hide();
		$('.table_search input').val('');
		if($(this).hasClass('tab_lock')){
			$('.table_body li.locked').show();
		}else if($(this).hasClass('tab_all')){
			$('.table_body li').show();
		}else{
			$('.table_body li.unlocked').show();
		}
	});
	
	$('.table_search input').keyup(function(){
		if($('.table_search input').val() == ''){
			if($('.table_tab .cur').size() > 0){
				if($('.table_tab .cur').hasClass('tab_lock')){
					$('.table_body li.locked').show();
				}else if($('.table_tab .cur').hasClass('tab_all')){
					$('.table_body li').show();
				}else{
					$('.table_body li.unlocked').show();
				}
			}else{
				$('.table_body li').show();
			}
		}else{
			$('.table_body li').hide();
			if($('.table_tab .cur').size() > 0){
				if($('.table_tab .cur').hasClass('tab_lock')){
					$.each($('.table_body li.locked'),function(i,item){
						if($(item).find('.cat_name').html().indexOf($('.table_search input').val()) >= 0){
							$(item).show();
						}
					});
				}else if($('.table_tab .cur').hasClass('tab_all')){
					$.each($('.table_body li'),function(i,item){
						if($(item).find('.cat_name').html().indexOf($('.table_search input').val()) >= 0){
							$(item).show();
						}
					});
				}else{
					$.each($('.table_body li.unlocked'),function(i,item){
						if($(item).find('.cat_name').html().indexOf($('.table_search input').val()) >= 0){
							$(item).show();
						}
					});
				}
			}else{
				$.each($('.table_body li'),function(i,item){
					if($(item).find('.cat_name').html().indexOf($('.table_search input').val()) >= 0){
						$(item).show();
					}
				});
			}
		}
	});
	$('body').on('click','.cat_status_btn',function(){
		var table_id = $(this).data('table_id');
		var table_type = $(this).data('table_type');
		if (store_open_table_type==1) {
			$.post(ajax_get_table_order_url,{table_id:table_id},function(result){
				console.log(result)
				if(result.order_id ){
					layer.open({
						type: 2,
						title: '查看订单详情',
						shadeClose: true,
						shade: 0.6,
						area: ['95%','95%'],
						content: foodshop_order_url+'&order_id='+result.order_id,
						end:function(){
							window.location.reload();
						}
					});
				}else{
					
					if (store_open_table_bind_time==1) {
						layer.open({
							type: 2,
							title: '创建订单',
							shadeClose: true,
							shade: 0.6,
							area: ['620px','450px'],
							content: foodshop_order_before_url+'&table_id='+table_id+'&table_type='+table_type,
							end:function(){
								window.location.reload();
							}
						});
					}else{
						$("#table_id").val(table_id);
						$("#table_type").val(table_type);
						$(".counter").removeClass('slcontainer').addClass('xgcontainer');
						$(".counter").show().find("input")[0].focus();
						$(".shadow_two").show();
					}
					

				}
			},'json'); 
		}else{
			var that = $(this);
			var li = $(this).closest('li');
			if(li.hasClass('locked')){
				var index = layer.load(0, {shade: 0.1});
				$.post(table_lock_url,{id:li.data('id'),lock:li.hasClass('locked') ? 0 : 1},function(result){
					layer.close(index);
					if(result.status == 1){
						li.addClass('unlocked').removeClass('locked');
						li.find('.cat_status').html('当前状态：解锁');
						that.html('点击锁定');
					}else{
						layer.msg(result.info);
					}
				});
			}else{
				if (store_open_table_bind_time==1) {
					layer.open({
						id:'add_order',
						type: 2,
						title: '创建订单',
						shadeClose: true,
						shade: 0.6,
						area: ['620px','450px'],
						content: foodshop_order_before_url+'&table_id='+table_id+'&table_type='+table_type
					});
				}else{
					var index = layer.load(0, {shade: 0.1});
					$.post(table_lock_url,{id:li.data('id'),lock:li.hasClass('locked') ? 0 : 1},function(result){
						layer.close(index);
						if(result.status == 1){
							li.addClass('locked').removeClass('unlocked');
							li.find('.cat_status').html('当前状态：锁定');
							that.html('点击解锁');
						}else{
							layer.msg(result.info);
						}
					});
				}
			}
				
		}
	});

	//计算器输入值的处理
	$('.counter input.text').keyup(function(e){
		if (e.keyCode == 13) {
			$(this).parents('.counter').find('.getResult').trigger('click');
			return false;
		}
		var type = $(this).parents('.counter').data('type')
		if (type == 0) {
			var text = $(this).val(), last_v = $(this).val().substr(-1, 1);
			if (last_v.search(/\d/) == -1) {
				$(this).val(text.substr(0, text.length - 1));
				return false;
			}
		}
			
	});
	//计算器点击确定的处理结果
	$(".getResult").click(function(){
		var num = parseFloat($.trim($(this).parents('.counter').find("input.text").val()));
		var table_id = $('#table_id').val();
		var table_type = $('#table_type').val();
		// 创建订单
	
		if(isNaN(num)){
			alert('请输入正确的就餐人数！');
			// $('#book_num').focus();
			return false;
		}
		if(table_id == '0'){
			alert('请选择就餐桌台');
			return false;
		}
		// $('#submit_btn').html('生成中...').prop('disabled',true);
		$.post(foodshop_add_order_url,{book_num:num,table_type:table_type,table_id:table_id},function(result){
			if(result.status == 1){
				parent.layer.open({
					type: 2,
					title: false,
					shadeClose: true,
					shade: 0.6,
					area: ['95%','95%'],
					content:foodshop_order_url+'&order_id='+result.info,
					end:function(){
						location.reload();
					}
				});
				parent.layer.close(parent.layer_index);
			}else{
				alert(result.info);
			}
		});
	
		$(".counter, .shadow_two").hide();
		
		$(this).parents('.counter').find("input.text").val('');
	});
	
	// 点击计算器中的数字
	$(".show").click(function(){
		var data = $(this).val(), text = $(this).parents('.counter').find('.text').val();
		if (text != '0') {
			$(this).parents('.counter').find('.text').val(text + '' + data);
		} else {
			if (!(data == '0' || data == '00')) {
				$(this).parents('.counter').find('.text').val(data);
			}
		}
		$(this).parents('.counter').find('.text').focus();
	});
	//点击计算器中的点
	$(".point").click(function(){
		if ($(this).parents('.counter').data('type') == 0) return false;
		var text = $(this).parents('.counter').find('.text')[0];
		var p = $(this)[0];
		if (text.value.search(/\./) != -1) return false;
		if (text == '') text = 0;
		text.value += p.value;
		$(this).parents('.counter').find('.text').focus();
	});
	
	//点击计算器的清空
	$(".funclear").click(function(){
		$(this).parents('.counter').find('.text').val('').focus();
	});
	
	//点击计算器的退格键
	$(".funback").click(function(){
		var text = $(this).parents('.counter').find('.text').val(); 
		if (text == "0" || text == "") {
			$(this).parents('.counter').find('.text').val('');
		} else {
			var last_value = text.substr(-2, 1);
			if (last_value == '.') {
				$(this).parents('.counter').find('.text').val(text.substr(0, text.length - 2));
			} else {
				$(this).parents('.counter').find('.text').val(text.substr(0, text.length - 1));
			}
		}
		$(this).parents('.counter').find('.text').focus();
	});
	// 计算器取消
	$(".counter .cancel").click(function(){
		if (!$(".shadow_two").is(":hidden")) {
			$(".counter, .shadow_two").hide();
		} else {
			number_or_name_focus();
			$(".counter, .shadow").hide();
		}
	});
});
