$(document).ready(function(){
	function refresh_n(){
		//隔5s刷新页面
		// setInterval(function(){
		// 	if ($('.Mask').css('display') == 'none') {
		// 		load_data(1);
		// 	}
		// },5000)
	}
	refresh_n();

	common.onlyScroll($('.tab_list'));
	
	$(".Mask").height($(window).height());
	function load_data(noTip){
		var post_data = {};
		if (noTip) {
			post_data = {noTip:true};
		}
		common.http('Storestaff&a=foodshop_table_list',post_data, function(data){
			laytpl($('#tableTypeTpl').html()).render(data.list, function(html){
				$('#tableTypeBox').html(html);
			});
			laytpl($('#tableListTpl').html()).render(data.list, function(html){
				$('#tableBox').html(html);
			});
			
			//顶部切换
			$(".tab li").click(function(){
				$(this).addClass("on").siblings().removeClass("on");
				$(".lock").eq($(this).index()).show().siblings(".lock").hide();
				$('body,html').animate({scrollTop: 0},300);
				
				$('#searchTxt').val('');
				$('#tableSearchBox').hide();
				$('#tableBox').show();
			}).eq(0).trigger('click');
			
			//切换导航
			var swiper = new Swiper('.reserve_tab .swiper-container', {
				direction : 'horizontal',
				freeMode:true,
				slidesPerView: 'auto'
			});

				//锁定解锁
			$(document).on('click','.dj',function(){
				if (data.store_open_table_type!=1) {
					var that = $(this);
					var lockThat = $('.lock_table_'+that.data('table_id'));
					
						if(that.hasClass('uolock')){
							common.http('Storestaff&a=tmp_table_lock',{id:that.data('table_id'),lock:that.hasClass('uolock') ? 0 : 1},function(saveResult){
								lockThat.addClass('unlock').removeClass('uolock').html('点击锁定');
								lockThat.closest('.lock_n').find('.current').addClass('unlock').removeClass('uolock').html('当前状态：解锁');
							});
						}else{

							var order_html = create_order();
							var table_id = that.data('table_id');
							var table_type = that.data('table_type');
							common.http('Storestaff&a=foodshop_order_before', {'noTip':true,table_id:table_id,table_type:table_type}, function(data){
								if (data.store_open_table_bind_time) {
									//开启桌台绑定时间段
									// 创建订单					
									laytpl(order_html).render(data, function(html){
										$('.setup').remove();
										$('body').append(html);
										$(".Mask,.setup").show();
									});
									$(document).on('click', '.Mask, .setup .del', function() {
										$('.setup').remove();
										$(".Mask,.setup").hide();
									});

									$(document).on('click', '.setup .sub input', function(){
										var note = $('.setup').find('textarea').val(), book_num = $('.setup').find('.book_num').val();
										var book_time = $('#book_time').val();
										var post_data = {'note':note, 'book_num':book_num, 'table_id':table_id, 'table_type':table_type, 'noTip':true};
										if (typeof(book_time)!='undefined') {
											post_data.book_time = book_time;
										}
										common.http('Storestaff&a=foodshop_add_order', post_data, function(data){
											motify.log('订单创建成功');
											setTimeout(function(){
												location.reload();
											},2000)
										});
									});
								}else{
									common.http('Storestaff&a=tmp_table_lock',{id:that.data('table_id'),lock:that.hasClass('uolock') ? 0 : 1},function(saveResult){
										lockThat.addClass('uolock').removeClass('unlock').html('点击解锁');
										lockThat.closest('.lock_n').find('.current').addClass('uolock').removeClass('unlock').html('当前状态：锁定');
									});
								}
							});
						}
				}else{
					var that = $(this);
					var table_id = that.data('table_id');
					var table_type = that.data('table_type');
					var status = that.data('status');
					var order_id = parseFloat(that.data('order_id'));
					var order_html = create_order();

					common.http('Storestaff&a=foodshop_table_order', {'noTip':true,table_id:table_id}, function(data){
						if (data.table_order_id) {	
							// 订单详情						
							common.setCache('table_order_id',data.table_order_id,true);
							window.location='foodshop_order_list.html?status='+data.status+'&order_id='+data.table_order_id;
						}else{		 
							common.http('Storestaff&a=foodshop_order_before', {'noTip':true,table_id:table_id,table_type:table_type}, function(data){
								// 创建订单					
								laytpl(order_html).render(data, function(html){
									$('.setup').remove();
									$('body').append(html);
									$(".Mask,.setup").show();
								});
								$(document).on('click', '.Mask, .setup .del', function() {
									$('.setup').remove();
									$(".Mask,.setup").hide();
								});

								$(document).on('click', '.setup .sub input', function(){
									var note = $('.setup').find('textarea').val(), book_num = $('.setup').find('.book_num').val();
									var book_time = $('#book_time').val();
									var post_data = {'note':note, 'book_num':book_num, 'table_id':table_id, 'table_type':table_type, 'noTip':true};
									if (typeof(book_time)!='undefined') {
										post_data.book_time = book_time;
									}
									common.http('Storestaff&a=foodshop_add_order', post_data, function(data){
										motify.log('订单创建成功');
										setTimeout(function(){
											location.reload();
										},2000)
									});
								});
								
							});
						}
					});
				}
			})
		});
	}
	load_data();
	
	
	$(".table_details .del,.Mask").click(function(){
		$(".table_details,.Mask").hide();
	})
		
	//查看预订
	$(document).on('click','.ck',function(){
		common.http('Storestaff&a=book_list',{table_id:$(this).data('table_id')},function(result){
			if(result.length > 0){
				laytpl($('#bookListTpl').html()).render(result, function(html){
					$('.tab_list ul').html(html);
				});
			}else{
				$('.tab_list ul').html('<div class="jroll-infinite-tip">暂无预订</div>');
			}
			$(".table_details,.Mask").show();
			$('.tab_list').scrollTop(0);
		});
	})
		
		
	$('#searchTxt').keyup(function(){
		var txt = $('#searchTxt').val();
		var tableArr = [];
		if(txt == ''){
			$('#tableSearchBox').hide();
			$('#tableBox, #tableTypeBox').show();
		}else{
			var html = '';
			$.each($('#tableBox .lock li'),function(i,item){
				if($(item).find('h2').html().indexOf(txt) >= 0){
					html += $(item).prop("outerHTML");
				}
			});
			$('#tableSearchBox .clr').html(html);
			$('#tableSearchBox').show();
			$('#tableBox, #tableTypeBox').hide();
		}
	});

	function create_order(table_id,table_type,status,order_id,is_lock){
		var order_html = '';
		order_html += '<section class="setup">';
		order_html += '<div class="setup_n">';
		order_html += '{{# if (d.order_id == 0) { }}';
		order_html += '<h2>创建订单</h2>';
		order_html += '{{# } else { }}';
		order_html += '<h2>编辑订单</h2>';
		order_html += '{{# } }}';

		order_html += '<li class="clr classify">';
		order_html += '<div class="fl">桌台分类</div>';
		order_html += '<div class="fr select">';
		order_html += '<select {{# if (d.now_table_type > 0) { }}disabled{{# } }}>';
		order_html += '{{# var this_table_list = null;}}';
		order_html += '{{# for(var i = 0, len = d.list.length; i < len; i++){ }}';
		order_html += '{{# if (d.list[i].id == d.now_table_type) { }}';
		order_html += '<option value="{{ d.list[i].id }}" selected>{{ d.list[i].name }}</option>';
		order_html += '{{# this_table_list = d.list[i].list;}}';
		order_html += '{{# } else { }}';
		order_html += '<option value="{{ d.list[i].id }}">{{ d.list[i].name }}</option>';
		order_html += '{{# } }}';
		order_html += '{{# } }}';
		order_html += '</select>';
		order_html += '</div>';
		order_html += '</li>';
		order_html += '<li class="clr number">';
		order_html += '<div class="fl">桌台编号</div>';
		order_html += '<div class="fr select">';
		order_html += '<select {{# if (d.now_table_id > 0) { }}disabled{{# } }}>';
		order_html += '{{# if (this_table_list == null) { }}';
		order_html += '{{# this_table_list = d.list[0].list; }}';
		order_html += '{{# } }}';

		order_html += '{{# for(var i = 0, len = this_table_list.length; i < len; i++){ }}';
		order_html += '{{# if (this_table_list[i].id == d.now_table_id) { }}';
		order_html += '<option value="{{ this_table_list[i].id }}" selected>{{ this_table_list[i].name }}</option>';
		order_html += '{{# } else { }}';
		order_html += '<option value="{{ this_table_list[i].id }}">{{ this_table_list[i].name }}</option>';
		order_html += '{{# } }}';

		order_html += '{{# } }}';

		order_html += '</select>';
		order_html += '</div>';
		order_html += '</li>';
		order_html += '<ul>';
		order_html += '{{# if (d.store_open_table_bind_time == 1) { }}';
		order_html += '<li class="clr time">';
		order_html += '<div class="fl">预约时间</div>';
		order_html += '<div class="fr">';
		order_html += '<div id="Choice" style="border-top: none;">\
							<span class="Reser_right">\
								{{# if (d.book_time) { }}\
								<i class="m">{{ d.m }}</i>月\
								<i class="d">{{ d.d }}</i>日 \
								<i class="e">{{ d.w }}</i> \
								<i class="o">{{ d.o }}</i>\
								{{# }else{ }}\
								<i class="m">-</i>月\
								<i class="d">-</i>日 \
								<i class="e">-</i> \
								<i class="o">-</i>\
								{{# } }}\
							</span>\
							<img style="vertical-align: middle;width: 9px;height: 9px;" src="/images/sanjiao.png">\
							<input type="hidden" name="book_time" id="book_time" value="{{ d.book_time }}">\
						</div>';
		order_html += '</div>';
		order_html += '</li>';
		order_html += '{{# } }}';
		order_html += '<li class="clr people">';
		order_html += '<div class="clr">';
		order_html += '<div class="fl">用餐人数</div>';
		order_html += '<div class="fr">';
		order_html += '<input type="tel" placeholder="请输入人数" class="book_num"/>';
		order_html += '</div>';
		order_html += '</div>';
		order_html += '<p>不收取客户必点菜费用，可设置为0</p>';
		order_html += '</li>';
		order_html += '{{# if (d.order_id == 0) { }}';
		order_html += '<li class="clr remark"><textarea placeholder="添加备注..."></textarea></li>';
		order_html += '{{# } }}';

		order_html += '</ul>';
		order_html += '<div class="sub">';
		order_html += '{{# if (d.order_id == 0) { }}';
		order_html += '<input type="submit" value="生成订单" data-order_id="{{d.order_id}}">';
		order_html += '{{# } else { }}';
		order_html += '<input type="submit" value="保存订单" data-order_id="{{d.order_id}}">';
		order_html += '{{# } }}';

		order_html += '</div>';
		order_html += '<div class="del"></div>';
		order_html += '</div>';
		order_html += '</section>';
		return order_html;
	}
});