var myScroll = null, myScroll1 = null, myScroll2 = null, myScroll3 = null, now_page = 0, hasMorePage = true, isLoading = true;
var pids1 = '', pids2 = '', pids3 = '', pids4 = '';
var sort_type = 1, sort = 1;
window.onload=function(){
	var View={
		init:function(){
			this.top_list();
			this.initDeatal();
			this.click_dom();
			this.scroll()
		},
		height:function(list){
			//偶数
			for(var index=0;index<list.goods_list.length;index++){
				if(index*1%2==0){
					if(index*1+1!=list.goods_list.length){
						if(list.goods_list[index*1+1].act_info && list.goods_list[index*1+1].act_info.price){
							if(list[index*1+1].is_seckill_price){
								list.goods_list[index].height='115px'
							}else{
								list.goods_list[index].height= '90px'
							}
						}else{
							if(list.goods_list[index].act_info && list.goods_list[index].act_info.price){
								if(list.goods_list[index].is_seckill_price){
									list.goods_list[index].height= '115px'
								}else{
									list.goods_list[index].height= '90px'
								}
							}else{
								list.goods_list[index].height= 'auto'
							}
						}

					}else{
						list.goods_list[index].height= 'auto'
					}
				}else{
					//奇数
					if(list.goods_list[index*1-1].act_info && list.goods_list[index-1].act_info.price){
						if(list.goods_list[index*1-1].is_seckill_price){
							list.goods_list[index].height= '115px'
						}else{
							list.goods_list[index].height= '90px'
						}
					}else{
						if(list.goods_list[index].act_info && list.goods_list[index].act_info.price){
							if(list.goods_list[index].is_seckill_price){
								list.goods_list[index].height= '115px'
							}else{
								list.goods_list[index].height= '90px'
							}
						}else{
							list.goods_list[index].height= 'auto'
						}
					}




				}
			}
			 return list
		},
		top_list:function(){
			$('#wrapper').navbarscroll({
				defaultSelect:0,
				scrollerWidth:6,
				fingerClick:1,
				endClickScroll:function(obj){
					var nav_obj=$("#ride_all li").eq(parseInt(obj.attr('data-index'))).find('a');
					if(nav_obj.hasClass('bg_active')){
						return false;
					}
					$("#ride_all li a").removeClass("bg_active");
					nav_obj.addClass("bg_active");




				}
			});
		},
		initDeatal:function(){
			names = names.split(',');
			if ($('.drop_top0').size()) myScroll = new IScroll('.drop_top0', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick(),scrollbars:false,useTransform:false,useTransition:false});
			if ($('.drop_top1').size()) myScroll1 = new IScroll('.drop_top1', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick(),scrollbars:false,useTransform:false,useTransition:false});
			if ($('.drop_top2').size()) myScroll2 = new IScroll('.drop_top2', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick(),scrollbars:false,useTransform:false,useTransition:false});
			if ($('.drop_top3').size()) myScroll3 = new IScroll('.drop_top3', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick(),scrollbars:false,useTransform:false,useTransition:false});
			//背景单窗高度
			$(".se_input").width($(window).width() - 100);
			$(".maskt").height($(window).height() - 141);
			$(".drop_top").each(function(){
				$(this).css("max-height", $(window).height() - 240);
			});

		},
		click_dom:function (){
			var _self=this;
			$(".bd_a a:nth-child(2n)").css("margin-right", 0);

			// 分类箭头切换
			$(document).on('click','.dity_top li',function(){
				sort = $(this).data('sort');
				var is_load = true;
				if (sort == 1 && ($(this).hasClass("on") || $(this).hasClass("ou"))) {
					is_load = false;
				}
				if (sort == 3) {
					if ($(this).hasClass("on")) {
						sort_type = 1;
						$(this).removeClass("on").addClass("ou");
					} else {
						sort_type = 2;
						$(this).addClass("on").removeClass("ou");
					}
				} else {
					if ($(this).hasClass("ou")) {
						sort_type = 2;
						$(this).removeClass("ou").addClass("on");
					} else {
						sort_type = 1;
						$(this).addClass("ou").removeClass("on");
					}
				}
				$(this).siblings().removeClass("on").removeClass("ou");
				if (is_load) {
					now_page = 0;
					_self.getList(0);
				}

				$(".drop").height(0);
				$(".dity_end li").removeClass("on");
				$(".maskt").hide();
			});

			//下拉
			$(document).on('click','.dity_end li',function(){
				if (myScroll != null) myScroll.refresh();
				if (myScroll1 != null) myScroll1.refresh();
				if (myScroll2 != null) myScroll2.refresh();
				if (myScroll3 != null) myScroll3.refresh();
				var index = $(this).index();
				var he = $(".drop").eq(index).find(".drop_top").height();
				if($(this).hasClass("on")){
					$(".maskt").hide();
					$(this).removeClass("on")
					$(".drop").eq(index).animate({height:"0px"}, 300)
				}else{
					$(".maskt").show();
					$(this).addClass("on").siblings().removeClass("on");
					$(".drop").eq(index).animate({height:he + 45}, 300).siblings(".drop").animate({height:"0px"}, 300)
				}
			})


			// 选择下拉按钮
			$(document).on('click','.drop dd',function(){
				if ($(this).hasClass("on")) {
					$(this).removeClass("on");
				} else {
					$(this).addClass("on");
				}
			})



			$(document).on('click','.confirm .fl',function(){
				$(this).parents('.drop').find('dd').removeClass("on");
			})
			$(document).on('click','.confirm .fr',function(){
				var index = $(this).parents(".drop").index();
				if ($(this).parents(".drop").find("dd").hasClass("on")) {
					var text = $(this).parents(".drop").find("dd.on").text();
					$(this).parents(".drop").animate({height:"0px"},300);
					$(".dity_end li").eq(index-1).removeClass("on").find("span").text(text).css({"color":"#f23030"}).addClass("on");
					$(".maskt").hide();
				} else {
					$(".dity_end li").eq(index-1).removeClass("on").find("span").text(names[index-1]).css({"color":"#232326"}).removeClass("on");
					$(this).parents(".drop").height(0);
					$(".maskt").hide();
				}
				var t_pids = [];
				$(this).parents(".drop").find("dd.on").each(function(){
					t_pids.push($(this).data('pid'));
				});
				if (index == 1) {
					pids1 = t_pids.length > 0 ? t_pids.join() : '';
				} else if (index == 2) {
					pids2 = t_pids.length > 0 ? t_pids.join() : '';
				} else if (index == 3) {
					pids3 = t_pids.length > 0 ? t_pids.join() : '';
				} else if (index == 4) {
					pids4 = t_pids.length > 0 ? t_pids.join() : '';
				}
				now_page = 0;
				_self.getList(0);
			})

			$(document).on("click",'.maskt',function(){
				$(".dity_end li").removeClass("on");
				$(this).hide();
				$(".drop").height(0);
			})
			$(document).on("click",'#search',function(){
				now_page = 0;
				_self.getList(0);
			})
			$(document).on("click",'.add_more',function(){
				if($(this).hasClass('add_more_all')){
					$(this).removeClass("add_more_all")
					$("#ride_all").slideUp('fast') ;
					$(".look_all").hide();
					$(".clearfix_list").show();
					$("#scroller").removeClass("no_tranns")
				}else{
					$("#ride_all").slideDown('fast') ;
					$(".look_all").show();
					$(".clearfix_list").hide();
					$("#scroller").addClass("no_tranns");
					$(this).addClass("add_more_all")
				}

			})
			$(document).on("click",'#ride_all>div',function(){
				$(".add_more").trigger("click")
			});

			$(document).on("click",'#ride_all li',function(){
				var index=parseInt($(this).attr("data-index"));
				$(".add_more").trigger("click");
				$("#scroller li").eq(index).trigger("click");
			});
			/*点击二级分类*/
			var flag=0;
			$("#scroller li").click(function(){
				var id=$(this).data('id');
				if(flag==1 && cat_id>0 && cat_id==id){
					return false;
				}
				flag=1;
				cat_id=id;
				var title=document.title;
				var arr=title.split('-');
				if(cat_id>0){
					document.title=arr['0']+'-'+$(this).text();
				}else{
					document.title=arr['0'];
				}
				//优化滚动加载
				$(document).scrollTop(0);
				now_page=0;
				_self.getList(0);
			});
			if(cat_id>0){
				$('#ride_all li').each(function(){
					if(cat_id==$(this).data('id')){
						var index=$(this).data('index');
						$("#scroller li").eq(index).trigger("click");
					}
				});
			}else if(cat_fid>0){
				$("#scroller li").eq(0).trigger("click");
			}
		},
		scroll:function(){
			//分类定位
			var _self=this;
			$(window).scroll(function() {
				if ($(window).scrollTop() > $(".bd_a").offset().top) {
					if(isLoading == false && hasMorePage == true && $(document).scrollTop() >= $(document).height() - $(window).height() - 50){
						_self.getList(true);
					}
				}
			});

		},
		getList:function(more){
			var self=this;
			if(now_page <= 1){
				pageLoadTip(92);
			}else{
				motify.log('加载中...');
			}
			isLoading = true;
			now_page += 1;
			$.post(ajax_url, {'pids1':pids1, 'pids2':pids2, 'pids3':pids3, 'pids4':pids4, 'page':now_page, 'cat_fid':cat_fid, 'cat_id':cat_id, 'sort':sort, 'sort_type':sort_type, 'key':$('.se_input').val()}, function(result){
				if(result.total > 0){
					$('.psnone').hide();
					hasMorePage = now_page < result.total_page ? true : false;
					var list= result=self.height(result)
					laytpl($('#goodsListBoxTpl').html()).render(list, function(html){
						if (more) {
							$('.bd_a').append(html);
						} else {
							$('.bd_a').html(html);
						}
					});
					// 图片等比例
					$(".bd_a img").each(function(){
						$(this).height($(this).width());
					});
					$(".bd_a a:nth-child(2n)").css("margin-right", 0);
				} else {
					$('.bd_a').html('');
					$('.psnone').show();
				}
				isLoading = false;
				if(now_page <= 2){
					pageLoadTipHide();
				}else{
					motify.clearLog();
				}
			}, 'json');
		}

	}
	window.View=View;
	View.init()
}
