var lng = 0,lat = 0,now_tab = 'pick',now_tab_index=0,max_tab_index=0;
var startX=0,startY=0,moveDirection='';
var load=true;
$(document).ready(function(){
	var indexData = common.getCache('indexData',true);

	if(!indexData){
		location.href = 'index.html';
		return false;
	}
	if(common.checkWeixin()){
		document.title = L_('处理中订单');
		common.fillPageBg(1,'#f4f4f4');
	}
	
	$(".delivery p em").each(function(){
		// $(this).width($(window).width() - $(this).siblings("i").width() - 55); 
	});
	
	if(common.checkAndroidApp()){
		$('.Navigation').css('top','44px');
	}
	$(".Dgrab").css({"margin-top":"40px"});
	
	$(".nav_end .Dgrab").width($(window).width());
	$("body").css('min-height',$(window).height());
	
	var mark = 0;
    $(document).on('click', '.Fetch,.Pick,.Dis,.service', function(e){
		if (mark) {
			return false;
		}
		var that = $(this);
		mark = 1;
		e.stopPropagation();
		var supply_id = that.attr("data-spid");
		
		if(that.hasClass('Fetch')){
			var postUrl = 'Deliver&a=fetch';
		}else if(now_tab == 'pick'){
			var postUrl = 'Deliver&a=pick';
		}else if(now_tab == 'send'){
			var postUrl = 'Deliver&a=send';
		}else if(now_tab == 'my'){
			var postUrl = 'Deliver&a=complete';
		}
		
		common.http(postUrl,{'supply_id':supply_id}, function(data){
			mark = 0;
			layer.open({
				title:[
					L_('提示'),
					'background-color:#289FFD;color:#fff;'
				]
				,content:L_('更新配送状态成功！')
				,btn: [L_('确定')]
				,end:function(){
					$('#layerTmpStyle').remove();
				}
				,success:function(){
					$('.layermbtn span').width('100%');
					$('body').append('<style id="layerTmpStyle">.layermbtn:before{content:"";position:static;}</style>');
				}
			});
			if(!that.hasClass('Fetch')){
				$(".supply_"+supply_id).remove();
			}else{
				location.reload();
			}
			
			if($('#pick_list .robbed').size() == 0){
				$('#pick_list').html('<div class="psnone"><img src="images/qdz_02.jpg"><div style="text-align:center;color:#999;">' + L_('暂无信息') + '</div></div>');
			}
			
			common.http('Deliver&a=pick_count',{noTip:true}, function(data){
				common.setData(data);
			});
		},function(){
			mark = 0;
			layer.open({
				title:[
					L_('提示')
					,'background-color:#289FFD;color:#fff;'
				]
				,content:data.errorMsg
				,btn: [L_('确定')]
				,end:function(){
					$('#layerTmpStyle').remove();
				}
				,success:function(){
					$('.layermbtn span').width('100%');
					$('body').append('<style id="layerTmpStyle">.layermbtn:before{content:"";position:static;}</style>');
				}
			});
			$(".supply_"+supply_id).remove();
			if($('#pick_list .robbed').size() == 0){
				$('#pick_list').html('<div class="psnone"><img src="images/qdz_02.jpg"><div style="text-align:center;color:#999;">' + L_('暂无信息') + '</div></div>');
			}
		});
    });
	
	max_tab_index = $('.Navigation li').size() - 1;
	$('.Navigation li').click(function(){
		if (!load) {
			return;
		}
		now_tab = $(this).data('type');
		now_tab_index = $(this).index();
		$(this).addClass('on').siblings().removeClass('on');
        load = false;
		get_list();
	});
	$('.Navigation li:eq(0)').trigger('click');
	
	$(document).on('click', '.go_detail', function(e){
		var href = 'detail.html?supply_id='+$(this).data('spid');
		if(common.checkApp()){
			common.setCache('supply_id',$(this).data('spid'),true);
			common.setCache('supply_status',$(this).data('status'),true);
		}
		openWebviewUrl(href);
    });
	
	common.http('Deliver&a=pick_count',{noTip:true}, function(data){
		common.setData(data);
	});
	
	
	//获取接触屏幕时的X和Y
	$('body').bind('touchstart',function(e){
		// console.log(e.originalEvent.changedTouches);
		startX = e.originalEvent.changedTouches[0].clientX,
		startY = e.originalEvent.changedTouches[0].clientY;
	});
	$('body').bind('touchmove',function(e){
		//获取滑动屏幕时的X,Y
		endX = e.originalEvent.changedTouches[0].clientX,
		endY = e.originalEvent.changedTouches[0].clientY;
		//获取滑动距离
		distanceX = endX-startX;
		distanceY = endY-startY;
		
		console.log(distanceX);
		console.log(distanceY);
		//判断滑动方向
		$('.directionBg').remove();
		moveDirection = '';
		if(Math.abs(distanceX)>Math.abs(distanceY) && Math.abs(distanceY) < 50 && Math.abs(distanceX) > 20){
			if(distanceX>0){
				console.log('往右滑动');
				if(now_tab_index > 0){
					moveDirection = 'right';
					$('body').append('<div class="directionBg" style="position:fixed;top:50px;background:rgba(0,0,0,0.6);color:white;padding:5px 10px;font-size:12px;border-radius:5px;left:50%;margin-left:-80px;"><div class="txt">' + L_('松手进入') + ' " ' + $('.Navigation li').eq(now_tab_index-1).find('.txt').html() + ' " ' + L_('订单列表') + ' </div></div>');
				}
			}else{
				console.log('往左滑动');
				if(now_tab_index < max_tab_index){
					moveDirection = 'left';
					$('body').append('<div class="directionBg" style="position:fixed;top:50px;background:rgba(0,0,0,0.6);color:white;padding:5px 10px;font-size:12px;border-radius:5px;left:50%;margin-left:-80px;"><div class="txt">' + L_('松手进入') + ' " ' + $('.Navigation li').eq(now_tab_index+1).find('.txt').html() + ' " ' + L_('订单列表') + ' </div></div>');
				}
			}
		}
		console.log(moveDirection);
	});
	$('body').bind('touchend',function(e){
		$('.directionBg').remove();
		if(moveDirection == 'left'){
			$('.Navigation li').eq(now_tab_index+1).trigger('click');
		}else if(moveDirection == 'right'){
			$('.Navigation li').eq(now_tab_index-1).trigger('click');
		}
		moveDirection = '';
	});
});

function get_list(){
	if(now_tab == 'pick'){
		var postUrl = 'Deliver&a=new_pick_list';
		$('#scan_btn').html(L_('扫码取餐'));
		$('#scan_a').css('background', '#ffa82c');
	}else if(now_tab == 'send'){
		var postUrl = 'Deliver&a=new_send_list';
		$('#scan_btn').html(L_('扫码配送'));
		$('#scan_a').css('background', '#20862e');
	}else if(now_tab == 'my'){
		var postUrl = 'Deliver&a=new_my_list';
		$('#scan_btn').html(L_('扫码送达'));
		$('#scan_a').css('background', '#ff2c4c');
	}
	$('#pick_list').empty();
	common.http(postUrl,{'lat':lat, 'lng':lng}, function(data){
		if(data.length > 0){
			data[0].now_tab = now_tab;
			laytpl($('#replyListBoxTpl').html()).render(data, function(html){
				if($('#scroller').width() == 0 || $('#pick_list').width() == 0){
					$('#scroller,#pick_list').width($(window).width());
					$("body").css('min-height',$(window).height());
				}
				$('#pick_list').html(html);
				$(".delivery p em").each(function(){
					// $(this).width($(window).width() - $(this).siblings("i").width() -55) 
				});
				replace_html_lang();
			});
		}else{
			$('#pick_list').html('<div class="psnone"><img src="images/qdz_02.jpg"><div style="text-align:center;color:#999;">' + L_('暂无信息') + '</div></div>');
		}

		common.http('Deliver&a=pick_count',{noTip:true}, function(data){
			common.setData(data);
		});
        load = true;
	},function(data){
        load = true;
		$('#pick_list').html('<div class="psnone"><img src="images/qdz_02.jpg"><div style="text-align:center;color:#999;">' + L_('暂无信息') + '</div></div>');
	});
}

function pageShowFunc(){
	var supply_id = common.getCache('supply_id',true);
	var supply_status = common.getCache('supply_status',true);
	if(supply_id && supply_status){
		common.http('Deliver&a=new_detail',{supply_id:supply_id}, function(data){
			if(data.supply.status != supply_status || data.supply.is_hide == '1'){
				$('.supply_'+supply_id).remove();
				if($('#pick_list .robbed').size() == 0){
					location.reload();
				}else{
					common.http('Deliver&a=pick_count',{noTip:true}, function(data){
						common.setData(data);
					});
				}
			}
		});
	}
}