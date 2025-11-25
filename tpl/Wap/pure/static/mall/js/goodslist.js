var myScroll4 = null, myScroll1 = null, myScroll2 = null, myScroll3 = null, now_page = 0, hasMorePage = true, isLoading = true;
var pids1 = '', pids2 = '', pids3 = '', pids4 = '';
var sort_type = 1, sort = 1;
var pageIndexMiniRefresh;
window.onload=function(){
	var View={
		init:function(){
			this.initFresh();
			this.top_list();
			this.initDeatal();
			this.click_dom()
		},
		setting:{
			pic_w:130,
			page_size:10,
			total_pages:0,
			pageNo: 1,
			menu_info:false,
			isNomore:false,
			isEmpty:false,
			top:false
		},
		isEmpty: function(list){
			var b = View.setting.pageNo == 1 && !list.length;
			this.setting.isEmpty=b;
			return b;
		},
		isNomore:function(list){
			console.log("setting.total_pages:"+this.setting.total_pages);
			console.log("setting.pageNo:"+this.setting.pageNo);
			//console.log(list.length);
			//console.log(this.setting.page_size);
			//if((list.length < this.setting.page_size)){
			//return true;
			//}
			// var b =this.setting.total_pages>0 && ((this.setting.pageNo >= this.setting.total_pages) || (list.length < this.setting.page_size)) ;
			var b =this.setting.total_pages>=0 && this.setting.pageNo >= this.setting.total_pages ;
			this.setting.isNomore=b;
			if(!b){
				isLoading = false;
			}
			return b
		},
		height:function(list){
			var self=this;
			//偶数
			for(var index=0;index<list.goods_list.length;index++){
				if(index*1%2==0){
					if(index*1+1!=list.goods_list.length){
						if(list.goods_list[index*1+1].act_info && list.goods_list[index*1+1].act_info.price){
							if(list.goods_list[index*1+1].is_seckill_price || (list.goods_list[index*1+1].sell_count==0 && list.goods_list[index*1+1].is_new==1) ){
								if(((list.goods_list[index*1+1].name.length*14+16)+(list.goods_list[index*1+1].is_self==1?2:0)>self.setting.pic_w) || ((list.goods_list[index*1].name.length*14+16)+(list.goods_list[index*1].is_self==1?2:0)>self.setting.pic_w)){
									list.goods_list[index].height=is_mini?'125px':'115px'
									list.goods_list[index].height1='41px'
								}else{
									list.goods_list[index].height=is_mini?'105px':'95px'
									list.goods_list[index].height1='25px'
								}

							}else{
								if(((list.goods_list[index*1+1].name.length*14+16)+(list.goods_list[index*1+1].is_self==1?2:0)>self.setting.pic_w) || ((list.goods_list[index*1].name.length*14+16)+(list.goods_list[index*1].is_self==1?2:0)>self.setting.pic_w)){
									list.goods_list[index].height=is_mini?'101px':'91px'
									list.goods_list[index].height1='41px'
								}else{
									list.goods_list[index].height=is_mini?'80px':'72px'
									list.goods_list[index].height1='25px'
								}

							}
						}else{
							if(list.goods_list[index].act_info && list.goods_list[index].act_info.price){
								if(list.goods_list[index*1].is_seckill_price || (list.goods_list[index*1].sell_count==0 && list.goods_list[index*1].is_new==1)){
									if(((list.goods_list[index*1+1].name.length*14+16)+(list.goods_list[index*1+1].is_self==1?2:0)>self.setting.pic_w) || ((list.goods_list[index*1].name.length*14+16)+(list.goods_list[index*1].is_self==1?2:0)>self.setting.pic_w)){
										list.goods_list[index].height=is_mini?'125px':'115px'
										list.goods_list[index].height1='41px'

									}else{
										console.log(list.goods_list[index*1].name,list.goods_list[index*1].name.length*14,self.setting.pic_w)
										list.goods_list[index].height=is_mini?'105px':'95px'
										list.goods_list[index].height1='25px'
									}
								}else{
									if(((list.goods_list[index*1+1].name.length*14+16)+(list.goods_list[index*1+1].is_self==1?2:0)>self.setting.pic_w) || ((list.goods_list[index*1].name.length*14+16)+(list.goods_list[index*1].is_self==1?2:0)>self.setting.pic_w)){
										list.goods_list[index].height=is_mini?'115px':'91px'
										list.goods_list[index].height1='41px'
									}else{
										list.goods_list[index].height=is_mini?'80px':'72px'
										list.goods_list[index].height1='25px'
									}

								}
							}else{
								if(((list.goods_list[index*1+1].name.length*14+16)+(list.goods_list[index*1+1].is_self==1?2:0)>self.setting.pic_w) || ((list.goods_list[index*1].name.length*14+16)+(list.goods_list[index*1].is_self==1?2:0)>self.setting.pic_w)){
									list.goods_list[index].height=is_mini?'96px':'95px'
									//list.goods_list[index].height1='45px'
									list.goods_list[index].height1='39px'
								}else{
									list.goods_list[index].height1='25px'
									if((list.goods_list[index*1].is_seckill_price || (list.goods_list[index*1].sell_count==0 && list.goods_list[index*1].is_new==1)) || (list.goods_list[index*1+1].is_seckill_price || (list.goods_list[index*1+1].sell_count==0 && list.goods_list[index*1+1].is_new==1))){
										list.goods_list[index].height=is_mini?'77px':'72px'
									}else{
										list.goods_list[index].height='auto'
									}


								}

							}
						}

					}else{
						list.goods_list[index].height= 'auto'
					}
				}else{
					//奇数
					if(list.goods_list[index*1-1].act_info && list.goods_list[index-1].act_info.price){
						if(list.goods_list[index*1-1].is_seckill_price || (list.goods_list[index*1-1].sell_count==0 && list.goods_list[index*1-1].is_new==1)){
							if(((list.goods_list[index*1-1].name.length*14+16)+(list.goods_list[index*1-1].is_self==1?2:0)>self.setting.pic_w )|| ((list.goods_list[index*1].name.length*14+16)+(list.goods_list[index*1].is_self==1?2:0)>self.setting.pic_w)){
								list.goods_list[index].height=is_mini?'125px':'115px'
								list.goods_list[index].height1='41px'
							}else{
								list.goods_list[index].height1='25px'
								console.log(list.goods_list[index*1].name,list.goods_list[index*1].name.length*14,self.setting.pic_w)
								list.goods_list[index].height=is_mini?'105px':'95px'
							}

						}else{
							if(((list.goods_list[index*1-1].name.length*14+16)+(list.goods_list[index*1-1].is_self==1?2:0)>self.setting.pic_w )|| ((list.goods_list[index*1].name.length*14+16)+(list.goods_list[index*1].is_self==1?2:0)>self.setting.pic_w)){
								list.goods_list[index].height=is_mini?'101px':'91px'
								list.goods_list[index].height1='41px'
							}else{
								list.goods_list[index].height=is_mini?'80px':'72px'
								list.goods_list[index].height1='25px'
							}

						}
					}else{
						if(list.goods_list[index].act_info && list.goods_list[index].act_info.price){
							if(list.goods_list[index].is_seckill_price || (list.goods_list[index*1].sell_count==0 && list.goods_list[index*1].is_new==1)){
								if(((list.goods_list[index*1-1].name.length*14+16)+(list.goods_list[index*1-1].is_self==1?2:0)>self.setting.pic_w)|| ((list.goods_list[index*1].name.length*14+16)+(list.goods_list[index*1].is_self==1?2:0)>self.setting.pic_w)){
									list.goods_list[index].height=is_mini?'125px':'115px'
									list.goods_list[index].height1='41px'
								}else{
									list.goods_list[index].height=is_mini?'105px':'95px'
									list.goods_list[index].height1='25px'
								}
							}else{
								if(((list.goods_list[index*1-1].name.length*14+16)+(list.goods_list[index*1-1].is_self==1?2:0)>self.setting.pic_w )|| ((list.goods_list[index*1].name.length*14+16)+(list.goods_list[index*1].is_self==1?2:0)>self.setting.pic_w)){
									list.goods_list[index].height=is_mini?'115px':'105px'
									list.goods_list[index].height1='41px'
								}else{
									list.goods_list[index].height=is_mini?'80px':'72px'
									list.goods_list[index].height1='25px'
								}

							}
						}else{
							if(((list.goods_list[index*1-1].name.length*14+16)+(list.goods_list[index*1-1].is_self==1?2:0)>self.setting.pic_w)|| ((list.goods_list[index*1].name.length*14+16)+(list.goods_list[index*1].is_self==1?2:0)>self.setting.pic_w)){
								list.goods_list[index].height=is_mini?'96px':'95px'
								//list.goods_list[index].height1='45px'
								list.goods_list[index].height1='39px'
							}else{
								list.goods_list[index].height1='25px'
								if((list.goods_list[index*1].is_seckill_price || (list.goods_list[index*1].sell_count==0 && list.goods_list[index*1].is_new==1)) || (list.goods_list[index*1-1].is_seckill_price || (list.goods_list[index*1-1].sell_count==0 && list.goods_list[index*1-1].is_new==1))){
									list.goods_list[index].height=is_mini?'77px':'72px'
								}else{
									list.goods_list[index].height='auto'
								}

							}

						}
					}




				}
			}
			return list
		},
		top_list:function(){
			var _self=this;
			this.setting.pic_w=$(window).width()*0.47;
		},
		initDeatal:function(){
			names = names.split(',');
			if ($('.drop_top0').size()) myScroll4 = new IScroll('.drop_top0', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:true,scrollbars:false,useTransform:false,useTransition:false});
			if ($('.drop_top1').size()) myScroll1 = new IScroll('.drop_top1', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:true,scrollbars:false,useTransform:false,useTransition:false});
			if ($('.drop_top2').size()) myScroll2 = new IScroll('.drop_top2', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:true,scrollbars:false,useTransform:false,useTransition:false});
			if ($('.drop_top3').size()) myScroll3 = new IScroll('.drop_top3', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:true,scrollbars:false,useTransform:false,useTransition:false});


			//背景单窗高度
			$(".se_input").width($(window).width() - 100);

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
					return false;
				}
				$("#layermbox3").show()
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
					_self.getList(false,true,false);
				}

				$(".drop").height(0);
				$(".dity_end li").removeClass("on");
				$(".maskt").hide();
			});

			//下拉
			$(document).on('click','.dity_end li',function(){
				if (myScroll4 != null) myScroll4.refresh();
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
					$(".drop").eq(index).animate({height:he + 45}, 300,'linear',function(){

					}).siblings(".drop").animate({height:"0px"}, 300)
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
				$("#layermbox3").show()
				var index = $(this).parents(".drop").index();
				if ($(this).parents(".drop").find("dd").hasClass("on")) {
					var text = $(this).parents(".drop").find("dd.on").text();
					$(this).parents(".drop").animate({height:"0px"},300);
					$(".dity_end li").eq(index-1).removeClass("on").find("span").text(text).css({"color":"#f23030"}).addClass("on");
					$(".maskt").hide();
				} else {
					var obj=$(".dity_end li").eq(index-1).removeClass("on").find("span");
					var text=obj.data('name');
					obj.text(text).css({"color":"#232326"}).removeClass("on");
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
				_self.getList(false,true,false);
			})

			$(document).on("click",'.maskt',function(){
				$(".dity_end li").removeClass("on");
				$(this).hide();
				$(".drop").height(0);
			})
			$(document).on("click",'#search',function(){
				now_page = 0;
				_self.getList(false,true,false);
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
					// $(".clearfix_list").hide();
					// $("#scroller").addClass("no_tranns");
					$(this).addClass("add_more_all")
				}

			})
			$(document).on("click",'#ride_all>div',function(){
				$(".add_more").trigger("click")
			});

			$(document).on("click",'#ride_all li',function(){
				var index=parseInt($(this).attr("data-index"));
				$(".add_more").trigger("click");

				console.log($("#cat-scroller li").eq(index));

				$("#cat-scroller li").eq(index).trigger("click");
			});

			/*点击二级分类*/
			var flag=0;
			$("#cat-scroller li").click(function(){
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
				changeWechatShare('mall',{title:document.title+"【"+window.shareData.alias_name+"】",desc:window.shareData.tContent,imgUrl:window.shareData.imgUrl,link:window.shareData.rootUrl+'&cat_id='+cat_id});

				$(this).addClass('is_scroll').siblings().removeClass('is_scroll');

				var nav_obj=$("#ride_all li[data-id=" + $(this).data('id') + "]").find('a');
				$("#ride_all li a").removeClass("bg_active");
				nav_obj.addClass("bg_active");


				//优化滚动加载
				$(document).scrollTop(0);
				now_page=0;
				_self.get_property();
				_self.getList(false,true,false);
				pageIndexMiniRefresh.endDownLoading(true);
				isWrapCssTranslate: true
				//_self.initFresh();
				$('.minirefresh-upwrap .upwrap-tips').html('');
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
		initFresh:function(){
			var _self=this;
			var w=$(window).height();
			var $h=0
			var innerHeight=$(".dity_end").innerHeight();
			/*if($(".dity_end ul>li").size()==0){
                $(".dity_end").hide()
            }else{
                $h=$(".dity_end").height()
            }*/
			$("#refresh").css({
				'height':(w-85-$h)+'px',
				'top':($h+$(".dity_top").height()+$("#wrapper").height())+'px'
			});

			$(".maskt").css('top', 86+innerHeight);
			//$(".maskt").height($(window).height());
			$('.mask').bind("touchmove",function(e){
				e.preventDefault();
			});

			//下拉刷新效果
			pageIndexMiniRefresh = new MiniRefresh({
				container: '#refresh',
				down: {
					callback: function(){
						Refresh();
					},
					// 这个也生效
					isWrapCssTranslate: true
				},
				up: {
					callback: function(){
						Load();
					},
					isAuto:false,
					// 这个也生效
					isWrapCssTranslate: true
				}
			});
			_self.getList(false,true,true)
			_self.get_property();

			function Refresh(){
				_self.getList(false,true,true)

				pageIndexMiniRefresh.endDownLoading(true);
			}
			function Load(){
				//motify.log('加载中...')
				_self.getList(true,true,true)
			}
			function pullTop(type){
				if(type){
					if(_self.setting.top){
						$("#toTop").fadeOut()
						_self.setting.top=false
					}else{
						$("#toTop").fadeIn()
					}

				}else{
					$("#toTop").fadeOut()
				}

			}


		},
		getList:function(type,scroll,load,is_init){
			var self=this;
			if(type){
				self.setting.pageNo++
			}else{
				self.setting.pageNo=1;
			}
			if(self.setting.pageNo == 1){
				$('.bd_a').empty();
			}
			isLoading = true;
			$.post(ajax_url, {'pids1':pids1, 'pids2':pids2, 'pids3':pids3, 'pids4':pids4, 'page':self.setting.pageNo, 'cat_fid':cat_fid, 'cat_id':cat_id, 'sort':sort, 'sort_type':sort_type, 'key':$('.se_input').val()}, function(result){
				var lists=[];
				var de=$(window).width();
				var ss=de/2;
				var fr=de*0.03;
				if(result.total > 0 || result.goods_list.length>0){
					$('.psnone').hide();
					hasMorePage = now_page < result.total_page ? true : false;
					self.setting.total_pages=result.total_page;
					//var list= result=self.height(result)
					lists=result.goods_list;
					laytpl($('#goodsListBoxTpl').html()).render(lists, function(html){
						if (type) {
							$('.bd_a').append(html);
						} else {
							$('.bd_a').html(html);
						}
						var $index=0;
						if(self.setting.pageNo>1){
							$index=$('.bd_a>div').length-lists.length+1;
						}
						for($index;$index<$('.bd_a>div').length;$index++){
							$('.bd_a .bd_imgImg').css("height",(ss-fr)+"px");
							if($index*1%2==0){
								//偶数
								if($index*1+1!=lists.length){

								}else{

								}
							}else{
								//奇数
								var $h2=$('.bd_a>div').eq($index).height();
								var $h1=$('.bd_a>div').eq($index-1).height();
								if($h2>=$h1){
									$('.bd_a>div').eq($index-1).css("height",$h2+"px")
								}else{
									$('.bd_a>div').eq($index).css("height",$h1+"px")
								}



							}
						}

						setTimeout(() => {
							var num = parseInt($('.bd_a>div').length / 2);
							for (var i=0; i<num; i++) {
								var height_left = $('.bd_a>div:eq('+(i*2)+')').height();
								var height_right = $('.bd_a>div:eq('+(i*2+1)+')').height();
								var result = height_left - height_right;

								if (result > 0) {
									if ($('.bd_a>div:eq('+(i*2+1)+') .ui-border-b').attr('style').indexOf('padding-bottom') == -1) {
										$('.bd_a>div:eq('+(i*2+1)+') .ui-border-b').css("cssText", "padding:0;padding-bottom:"+ (10+result ) +"px !important;");
									}
								} else {
									if($('.bd_a>div:eq('+(i*2)+') .ui-border-b').attr('style').indexOf('padding-bottom') == -1) {
										$('.bd_a>div:eq('+(i*2)+') .ui-border-b').css("cssText", "padding:0;padding-bottom:"+ (10-result ) +"px !important;");
									}
								}

							}
						}, 0)

					});
					// 图片等比例
					/*$(".bd_a img").each(function(){
						$(this).height($(this).width());
					});*/
					$(".bd_a a:nth-child(2n)").css("margin-right", 0);
				} else {
					lists=[]
					self.setting.total_pages=0;
					$('.bd_a').html('');
					$('.psnone').show();
				}

				pids1 =  '';

				pids2 =  '';

				pids3 = '';

				pids4 = '';

				console.log('isNomore', View.isNomore(lists));
				pageIndexMiniRefresh.endUpLoading(View.isNomore(lists));


				//isLoading = false;
				/*if(now_page <= 2){
					pageLoadTipHide();
				}else{
					motify.clearLog();
				}*/
				setTimeout(function () {
					if (!load && scroll) {
						self.setting.top=true;
						$("#toTop").trigger("click");
					}
					APP.hideLoad()
				}, 200)
			}, 'json');
		},
		get_property:function(){
			$.post(ajax_property_url,{cat_fid:cat_fid,cat_id:cat_id},function(res){
				var list=eval("("+res+")");
				laytpl($('#property_box').html()).render(list, function(html){
					$('.dity_end').html(html);
					var h=$(".dity_end").height();
					var topH = $(".dity_top").height()+$("#wrapper").height()
					$('#refresh').css('top',$(".dity_top").height()+$("#wrapper").height()+70);
					$('#refresh').height($(window).height() - topH - 70)
					if(list.length>0){
						$('.dity_end').show();
						$('#refresh').css('top',h+topH+70);
						$('#refresh').height($('#refresh').height() - h)
					}else{
						$('.dity_end').hide();

					}
				});
			});

		}
	}
	window.View=View;
	View.init()
}
function changeWechatShare(type,param){
	if(typeof(wxSdkLoad) == "undefined"){
		return false;
	}

	if(type == 'plat'){
		param = {
			title: window.shareData.tTitle,
			desc:  window.shareData.tContent,
			link:  window.shareData.sendFriendLink + '&openid=' + userOpenid,
			imgUrl: window.shareData.imgUrl,
		};
	}
	wx.ready(function () {
		wx.updateAppMessageShareData({
			title: param.title,
			desc: param.desc,
			link: param.link,
			imgUrl: param.imgUrl,
			success: function () {
				// 设置成功
			}
		});
		wx.updateTimelineShareData({
			title:param.title,
			link:  param.link,
			imgUrl:  param.imgUrl,
			success: function () {
				// 设置成功
			}
		});
		wx.onMenuShareQQ({
			title: param.title,
			desc: param.desc,
			link: param.link,
			imgUrl: param.imgUrl,
			success: function () {
				// 用户确认分享后执行的回调函数
			},
			cancel: function () {
				// 用户取消分享后执行的回调函数
			}
		});
		wx.onMenuShareAppMessage({
			title: param.title,
			desc: param.desc,
			link: param.link,
			imgUrl: param.imgUrl,
			success: function () {
				// 用户确认分享后执行的回调函数
			},
			cancel: function () {
				// 用户取消分享后执行的回调函数
			}
		});
		wx.onMenuShareTimeline({
			title: param.title,
			desc: param.desc,
			link: param.link,
			imgUrl: param.imgUrl,
			success: function () {
				// 用户确认分享后执行的回调函数
			},
			cancel: function () {
				// 用户取消分享后执行的回调函数
			}
		});
	});
}