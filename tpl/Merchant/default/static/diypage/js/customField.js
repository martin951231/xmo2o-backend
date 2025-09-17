var content_editor = null, mapGetters = Vuex.mapGetters, mapMutations = Vuex.mapMutations, mapState = Vuex.mapState,assign = Object.assign,vueStores = [];
$(function(){
	var domList = new Stack();

    Vue.component('right-htmls',{
        name:"right-htmls",
        template:$('#right-htmls').html(),
        data:function(){ return  {} },
        methods:assign(mapMutations(['setActivities','delActivity']),{
            addActivities:function(){
                var _this = this;
            }
        }),
        created: function(){
        },
        computed: assign(mapGetters(["currentTheme"]),mapState(["config", "themes","activities","list"]), {})
    });
    Vue.component("time-count-down", {
        name: "time-count-down",
        data: function () {
            return {
                timeString: "",
                remainMS: 0,
                timer: null,
                status: 2//1:未开始 2：进行中 3：已结束
            }
        },
        props: {
            endTime: {
                required: true,
                type: [Number, String],
                default: 0
            },
            startTime: {
                required: true,
                type: [Number, String],
                default: 0
            },
            activityId: {
                type: [Number, String],
                default: 0,
                required: false
            },
            _onActivityStatusChange: {
                type: Function,
                required: false
            }
        },
        created: function () {
            this.getTimeString()
            this.checkInterval()
        },
        methods: {
            getTimeString: function () {
                var endTime = this.endTime;
                var startTime = this.startTime
                var activityId = this.activityId
                var status
                var currentTime = Date.now()
                endTime = endTime * 1000//php返回的时间戳是秒为单位
                startTime = startTime * 1000
                var timeString = "", remainMS = 0
                if (startTime > currentTime) {
                    remainMS = Math.floor((startTime - currentTime) / 1000) * 1000
                    timeString = '距开始：' + formatDuring(remainMS)
                    status = 1
                } else if (endTime > currentTime && startTime < currentTime) {
                    remainMS = Math.floor((endTime - currentTime) / 1000) * 1000
                    timeString = '距结束：' + formatDuring(remainMS)
                    status = 2
                } else {
                    timeString = '已结束'
                    status = 3
                    clearInterval(this.timer)
                }
                if (status !== this.status) {
                    this._onActivityStatusChange && this._onActivityStatusChange({status:status, activityId:activityId})
                }
                this.remainMS = remainMS
                this.timeString = timeString
                this.status = status
            },
            checkInterval: function () {
                var that = this;
                that.timer = setInterval(function () {
                    that.getTimeString()
                    if (that.timeString === "已结束") {
                        clearInterval(that.timer)
                    }
                }, 1000)
            }
        },
        template: $("#time-count-down").html()
    });
    Vue.component('al-activities', {
        name: "al-activities",
        data:function (){return {}},
        created: function () {
            console.log(this.currentTheme);
        },
        computed: assign(mapGetters(["currentTheme"]),mapState(["config", "themes","activities","list"]), {}),
        template: $("#al-activities").html()
    });


	$('.js-config-region .app-field').live('click',function(){
		var that = $(this);
		console.log(that.data());
		laytpl($('#pageTitleTpl').html()).render(that.data(), function(html){
			var rightHtml = $(html);
			rightHtml.find('input[name="title"]').blur(function(){
                var val = $(this).val();
                if(val.length == 0 || val.length > 50){
                    layer_tips(1,'页面名称不能少于一个字或者多于50个字');
                }
                that.data('page_name',val);
                that.find('h1 span').html(val);
            });
			rightHtml.find('input[name="color"]').change(function(){
                that.data('bgcolor',$(this).val());
            });
			rightHtml.find('input[name="menu_bgcolor"]').change(function(){
                that.data('menu_bgcolor',$(this).val());
            });
			rightHtml.find('.js-reset-bg').click(function(){
                $(this).siblings('input[name="color"]').val('#ffffff');
                that.data('bgcolor','#ffffff');
            });
			rightHtml.find('.js-reset-menubg').click(function(){
                $(this).siblings('input[name="menu_bgcolor"]').val('#ffffff');
                that.data('menu_bgcolor','#ffffff');
            });
			rightHtml.find('input[name="description"]').blur(function(){
                that.data('page_desc',$(this).val());
            });
			$('.js-sidebar-region').html(rightHtml);
			$('.app-sidebar').css('margin-top',that.offset().top - $('.app-preview').offset().top);
			
			$('input:radio[name="page_goods_source"]').click(function(){
				page_goods_source = $('input:radio[name="page_goods_source"]:checked').val();
				console.log('page_goods_source_click',page_goods_source);
				if (page_goods_source == 2) {
                    $('.bargain_module_btn').show();
                    $('.tuan_module_btn').show();
                    $('.tpdh_xfdb_btn').hide();
				} else {
                    $('.bargain_module_btn').hide();
                    $('.tuan_module_btn').hide();
                    $('.tpdh_xfdb_btn').show();
				}
			});
		});
	});
	
	$('.js-fields-region .app-field').live('click',function(){
		var fieldType = $(this).data('field-type');
		console.log(fieldType);
		$('.app-entry .app-field').removeClass('editing');
		$(this).addClass('editing');
		clickEvent($(this));
	});
	//添加内容
	$('.js-add-region .js-new-field').click(function(){
        //判断，限定某些模块数量
        if($(this).attr("data-field-type") == 'tpl_shop' || $(this).attr("data-field-type") == 'tpl_shop1')   {
            if($('.tpl-shop').length>0 || $('.tpl-shop1').length>0 ) {
                layer_tips(1,'一个模板只可拥有一个模板头部！');
                return false;
            }
        }
        if ($(this).attr("data-field-type") == 'map') {
            if($('.map').length > 0) {
                layer_tips(1,'一个模板只可拥有一个店铺地图！');
                return false;
            }
        }
        if ($(this).attr("data-field-type") == 'store_map') {
            if($('.map').length > 0) {
                layer_tips(1,'一个模板只可拥有一个联系店铺！');
                return false;
            }
        }
        if ($(this).attr("data-field-type") == 'store_cate') {
            if($('.addCategoryBtn').length > 0) {
                layer_tips(1,'一个模板只可拥有一个店铺分类！');
                return false;
            }
        }
			
		var app_field = $('<div class="app-field clearfix"><div class="control-group"><div class="component-border"></div></div><div class="actions"><div class="actions-wrap"><span class="action edit">编辑</span><span class="action add">加内容</span><span class="action delete">删除</span></div></div><div class="sort"><i class="sort-handler"></i></div></div>');


		app_field.data('field-type',$(this).data('field-type'));
		$('.js-fields-region .app-fields').append(app_field);
		app_field.trigger('click');


		$('.app-entry .app-fields .app-field').each(function(i, val){
			$('.app-entry .app-fields .app-field:last').attr('data-dom',domList.top);
			$('.app-entry .app-fields .app-field:last').addClass('dom_move'+domList.top);
			domList.push(domList.top)
		})
	});
	$('.js-fields-region .action.add').live('click',function(event){
		var dom = $(this).closest('.app-field');
		dom.attr("is_add_dom", "true");
		
		var rightContent = $('.js-add-region').html();
		var rightHtml = $(rightContent);
		rightHtml.find('.js-new-field').click(function(){
			if ($(this).attr("data-field-type") == 'map') {
				if($('.map').length > 0) {
					layer_tips(1,'一个模板只可拥有一个店铺地图！');
					return false;
				}
			}
			
			var app_field = $('<div class="app-field clearfix"><div class="control-group"><div class="component-border"></div></div><div class="actions"><div class="actions-wrap"><span class="action edit">编辑</span><span class="action add">加内容</span><span class="action delete">删除</span></div></div><div class="sort"><i class="sort-handler"></i></div></div>');
			app_field.data('field-type',$(this).data('field-type'));
			dom.after(app_field);
			app_field.trigger('click');
			
			domList.clear();
			$('.app-entry .app-fields .app-field').each(function(i, val){
				$('.app-entry .app-fields .app-field:last').attr('data-dom',domList.top);
				$('.app-entry .app-fields .app-field:last').addClass('dom_move'+domList.top);
				domList.push(domList.top)
			});
		});
		$('.js-sidebar-region').empty().html(rightHtml);
		$('.app-sidebar').css('margin-top',dom.offset().top - $('.app-preview').offset().top);
		event.stopPropagation();
		return false;
	});
	$('.js-fields-region .action.delete').live('click',function(event){
		var nowDom = $(this);
		button_box($(this),event,'left','delete','确定删除？',function(){
			domList.pop(domList.key(nowDom.data('dom')))
			nowDom.closest('.app-field').remove();
			if(nowDom.closest('.app-field').hasClass('editing')){
				$('.js-config-region .app-field').eq(0).trigger('click');
			}
			close_button_box();
		});
		event.stopPropagation();
		return false;
	});
	
	
	$('.form-actions .btn-save').live('click',function(){
		var defaultFieldObj = $('.js-config-region .app-field');
		var post_data = {};
		post_data.page_id 	  = defaultFieldObj.data('page_id');
		post_data.page_name   = defaultFieldObj.data('page_name');
		post_data.page_goods_source   = page_goods_source;
		if(post_data.page_name.length == 0 || post_data.page_name.length > 50){
			layer_tips(1,'页面名称不能少于一个字或者多于50个字');
			defaultFieldObj.trigger('click');
			return false;
		}

		post_data.show_head  = defaultFieldObj.data('show_head');
		post_data.type  = defaultFieldObj.data('type');
		post_data.show_footer  = defaultFieldObj.data('show_footer');
		post_data.page_desc  = defaultFieldObj.data('page_desc');
		post_data.bgcolor 	 = defaultFieldObj.data('bgcolor');
		post_data.menu_bgcolor 	 = defaultFieldObj.data('menu_bgcolor');
		post_data.cat_ids    = defaultFieldObj.data('cat_ids');
		//封面路径
		post_data.cover_img = defaultFieldObj.data('cover_img');
		post_data.custom     = checkEvent();
		post_data.stock     = domList.dataStore;

		var _flag = true;
		for(var i in post_data.custom) {
			if (post_data.custom[i] == false) {
				_flag = false;
				return;
			}
			if (post_data.custom[i].type == "coupons" && obj2String(post_data.custom[i].coupon_arr) == "{}") {
				layer_tips(1,'请填加优惠券');
				return;
			}
			
			if (post_data.custom[i].type == "store_cate" && !post_data.custom[i].store_cate_arr.title) {
				for(var j in post_data.custom[i].store_cate_arr) {
					var tmp_store = post_data.custom[i].store_cate_arr[j];
					if (!tmp_store.title) {
						layer_tips(1,'店铺分类不能为空');
						return;
					}

					if (tmp_store.store_list.length<=0) {
						layer_tips(1,'请填加店铺');
						return;
					}
				}
			}
		}

		if(post_data.custom.length == 0){
			layer_tips(1,'请给页面先添加一些内容再保存嘛');
			return false;
		}
		if (!_flag) {
			return false;
		}
		var cat_post_url     = post_data.page_id == '0' ? add_url : edit_url;
		console.log(post_data);
		$.post(cat_post_url,post_data,function(result){
			if(result.status == 1){
				layer_tips(0,result.info);
				if(post_data.type != 2){
					setTimeout(function(){
						location.href = '?c=Diypage&a=index&store_id='+store_id;
					},1000);
				}else{
					setTimeout(function(){
						window.location.reload();
					},1000);
				}
			}else{
				layer_tips(1,result.info);
			}
		});
	});
	
	var editDataDom = $('#edit_data');
	if(editDataDom.size() > 0){
		$('.js-config-region .app-field').data({'type':'2','page_id':editDataDom.attr('page-id'),'page_name':editDataDom.attr('page-name'),'page_desc':editDataDom.attr('page-desc'),'bgcolor':editDataDom.attr('bgcolor'),'menu_bgcolor':editDataDom.attr('menu_bgcolor')});
		$('.js-config-region h1 span').html(editDataDom.attr('page-name'));
		setHtml($('#edit_custom').attr('custom-field'));

		$('#edit_data,#edit_custom').remove();
	}
	
	//初始化
	if(!$('.js-config-region .app-field').data('page_id')){
		$('.js-config-region .app-field').data({'page_id':'0','page_name':'页面标题','page_desc':'','bgcolor':'#ffffff','menu_bgcolor':'#ffffff'});
	}
	$('.js-config-region .app-field').trigger('click');

//拖动排序
	// var $nowDom ;
	// 	var $nextDom ;
	// 	var $prevDom ;
		
		//遍历节点获得节点排序
		$('.app-entry .app-fields .app-field').each(function(i, val){
			$(this).attr('data-dom',i)
			$(this).addClass('dom_move'+i)
			domList.push(i)
		});
	
		
	// 	var _move=false;//移动标记  
	// 	var change=false;//移动标记  
	// 	var _x,_y,_now_y,_next_y,nowY,nextY,frist_y,domKey,nowHeight,nextTop;//鼠标离控件左上角的相对位置  
		
	// 	$('.app-entry .app-fields .app-field').click(function(){  
	// 		//alert("click");//点击（松开后触发）  
	// 		}).mousedown(function(e){  
	// 	//	console.log(domList);
	// 		domKey  = $(this).data('dom');
			
	// 		frist_y = e.pageY;
	// 		// $(this).css('z-index',999);
	// 		$nowDom  = $('.dom_move'+domKey);
	// 		var now_key = domList.key(domKey)
	// 		nowHeight = $nowDom.height();
	// 		nowY = $nowDom.offset().top;
	// 		//console.log('nowHeight:'+nowHeight)
	// 		//console.log('now Top:'+nowY)
		
	// 		_move=true; 
	// 		$(this).fadeTo(20, 0.5);//点击后开始拖动并透明显示  
	// 		if(typeof(domList.next(now_key))=='undefined'){
			
	// 			//alert('不能往下移动了')
	// 			_move=false;  
	// 			$nowDom.fadeTo("fast", 1);
	// 		}
	// 		$nextDom  = $('.dom_move'+domList.next(now_key)); 
	// 		$prevDom  = $('.dom_move'+domList.prev(now_key)); 
	// 		nextY = $nextDom.offset().top;
	// 		console.log('next Top:'+nextY)
		
	// 		_y=e.pageY-parseInt($(this).css("top"));  
	// 		_next_y = -$nowDom.height();
	// 		_change = true;
	// 	});  
		
	// 	$(document).mousemove(function(e){  
	// 		if(_move){  
	// 			//var x=e.pageX-_x;//移动时根据鼠标位置计算控件左上角的绝对位置  
	// 			var y=e.pageY-_y;  
	// 			var Y=_next_y;  
	// 			var tmp = $nowDom;
	// 			$nowDom.css({top:y});//控件新位置  
	// 			if($nowDom.offset().top>$nextDom.offset().top){		
	// 				console.log($nextDom.offset().top-nowHeight)
	// 				$nextDom.offset({top:$nextDom.offset().top-nowHeight});
				
	// 				if(_change){
	// 					domList.change(domList.key($nowDom.data('dom')),domList.key($nextDom.data('dom')));
	// 					$nextDom = $('.dom_move'+domList.next(domList.key($nowDom.data('dom'))));
	// 					$prevDom = $('.dom_move'+domList.prev(domList.key($nowDom.data('dom'))));
	// 					console.log($prevDom)
	// 				}
	// 				_change=false;
	// 				if($nowDom.offset().top>$nextDom.offset().top){
	// 					_change=true;
	// 				}
	// 			}
	// 			// if(e.pageY<$prevDom.offset().top+$prevDom.height()&&change){
	// 				// $prevDom.css({top:$nowDom.height()});
	// 				// domList.change(domList.key($nowDom.data('dom')),domList.key($prevDom.data('dom')));
	// 				// change=false;
	// 				// console.log(domList)
	// 			// }
	// 		}  
	// 	}).mouseup(function(){  
	// 		var y=_y;  
			
	// 		console.log('location:'+(nowY+$prevDom.height()))
	// 		console.log('nowdom:'+($nowDom.data('dom')))
	// 		//console.log('noxtdom:'+($prevDom.data('dom')))
	// 		if($nowDom.offset().top>nextY){				
	// 			if($nowDom.data('dom')>$prevDom.data('dom')){
	// 				$nowDom.css({top:0});
	// 			}else{
	// 				$nowDom.css({top:$prevDom.height()});
	// 			}
	// 			//console.log('now top:'+($nowDom.offset().top))
	// 		}
	// 		$nowDom.css('z-index',0);
	// 		if(_move){
	// 			$nowDom.fadeTo("fast", 1);//松开鼠标后停止移动并恢复成不透明  
	// 		}
	// 		_move=false;  
	// 	}); 
	// 	
	
	// jq 拖动
	$( ".app-fields.ui-sortable").disableSelection();
	$( ".app-fields.ui-sortable").sortable({
	    accept:'.app-field',
	    opacity:0.8,
	    revent:true,
	    connectWidth:'.app-fields.ui-sortable',
	    start:function(e,ui){
	    	console.log(ui);
	    	//console.log(this);
	    	//var a=$(this).data('dom');
	    	//	console.log(a);
	    },
	    stop: function(event, ui) { 
		    var domList=[];
		 	$('.app-entry .app-fields .app-field').each(function(i, val){
				domList.push($(this).attr('data-dom'));
			})
		    console.log(domList);
    	}
    });


	
});

function Stack() {  
　this.dataStore = [];//保存栈内元素  
　this.top = 0;  
　//this.end = 0;  
}  

Stack.prototype={  
    push:function push(element) {  
          this.dataStore[this.top++] = element;//添加一个元素并将top+1  
        },  
    peek:function peek() {  
          return this.dataStore[this.top-1];//返回栈顶元素  
        },  
    pop:function pop() {  
          return this.dataStore[--this.top];//返回栈顶元素并将top-1  
       },
	next:function next(i) {  
		  flag = Number(i)+1;
		  if(flag<this.top)
			return this.dataStore[flag];
       }, 	
	prev:function prev(i) {  
        return this.dataStore[Number(i)-1];
	}, 		   
    clear:function clear() {  
		this.top = 0;//将top归0  
	},
	key:function key(i) {  //遍历获取当前元素key
			for (k in this.dataStore) {
				if(this.dataStore[k]==i){
					return k;
				}
			}
         },
	change:function change(i,k) {  //交换位置
			console.log(i+'  '+k)
			var tmp_s = this.dataStore[i];
			this.dataStore[i] = this.dataStore[k];
			this.dataStore[k] = tmp_s;
			
         }, 		 
	length:function length() {  
		return this.top;//返回栈内的元素个数  
	}  ,
	
}  

var checkEvent = function(){
	var returnArr = [];
	$.each($('.js-fields-region .app-field'),function(i,item){
		returnArr[i] = getContent($(item));
		if (returnArr[i] == false) {
			return false;
		}
	});
	return returnArr; 
};
/**
 *
 * @param dom
 * @returns {{}}
 */
var getContent = function(dom){
	var returnArr = [],returnObj = {},domHtml={};
	returnArr['rich_text'] = function(){
		returnObj.type = 'rich_text';
		domHtml = dom.find('.custom-richtext');
		returnObj.bgcolor = domHtml.data('bgcolor');
		returnObj.screen  = domHtml.data('fullscreen');
		returnObj.content = domHtml.data('has_amend')=='1' ? domHtml.html() : '';
	};

	returnArr['notice'] = function(){
		returnObj.type = 'notice';
		domHtml = dom.find('.custom-notice');
		returnObj.content = domHtml.data('content');
	};

	returnArr['title'] = function(){
		returnObj.type = 'title';
		domHtml = dom.find('.custom-title');

		//赋值
		returnObj.title = domHtml.data('title');
		returnObj.sub_title = domHtml.data('sub_title');
		returnObj.show_method = domHtml.data('show_method');
		returnObj.txtcolor = domHtml.find('.title').data('txtcolor');
		returnObj.txtcolor2 = domHtml.find('.sub_title').data('txtcolor2');
		returnObj.bgcolor = domHtml.data('bgcolor');
	};

	returnArr['subject_display'] = function(){
		returnObj.type = 'subject_display';
		domHtml = dom.find('.subject_display');
		returnObj.px_style = domHtml.data('px_style');
		returnObj.hour = domHtml.data('hour');
		returnObj.update_hour = domHtml.data('update_hour');
		returnObj.day = domHtml.data('day');
		returnObj.day_type = domHtml.data('day_type');
		returnObj.number = domHtml.data('number');
	};

	returnArr['tpl_shop'] = function(){
		returnObj.type = 'tpl_shop';
		domHtml = dom.find('.tpl-shop');
		returnObj.shop_head_bg_img = domHtml.data('shop_head_bg_img');
		returnObj.shop_head_logo_img = domHtml.data('shop_head_logo_img');
		returnObj.bgcolor = domHtml.data('bgcolor');
		returnObj.title = domHtml.data('title');
		//return;
	};

	returnArr['tpl_shop1'] = function(){
		returnObj.type = 'tpl_shop1';
		domHtml = dom.find('.tpl-shop1');
		returnObj.shop_head_bg_img = domHtml.data('shop_head_bg_img');
		returnObj.shop_head_logo_img = domHtml.data('shop_head_logo_img');
		returnObj.bgcolor = domHtml.data('bgcolor');
		returnObj.title = domHtml.data('title');
	};

	returnArr['line'] = function(){
		returnObj.type = 'line';
	};

	returnArr['white'] = function(){
		returnObj.type = 'white';
		domHtml = dom.find('.custom-white');
		returnObj.left = domHtml.data('left');
		returnObj.height = domHtml.data('height');
	};

	returnArr['search'] = function(){
		returnObj.type = 'search';
	};

	returnArr['store'] = function(){
		returnObj.type = 'store';
	};

	returnArr['attention_collect'] = function(){
		returnObj.type = 'attention_collect';
	};

	returnArr['my_guanzhu'] = function(){
		returnObj.type = 'my_guanzhu';
	};

	returnArr['text_nav'] = function(){
		returnObj.type = 'text_nav';
		var navList = dom.find('.custom-nav').data('navList');
		var num = 10;
		for(var i in navList){
			returnObj[num] = {title:navList[i].title,name:navList[i].name,prefix:navList[i].prefix,url:navList[i].url};
			num++;
		}
	};

	returnArr['image_nav'] = function(){
		returnObj.type = 'image_nav';
		var navList = dom.find('.custom-nav-4').data('navList');
		var num = 10;
		//returnObj.suspend = dom.find('.custom-nav-4').data('suspend');
		for(var i in navList){
			returnObj[num] = {title:navList[i].title,name:navList[i].name,prefix:navList[i].prefix,url:navList[i].url,image:navList[i].image.replace('./upload/','')};
			num++;
		}

	};

	returnArr['image_nav_bottom'] = function(){
		returnObj.type = 'image_nav_bottom';
		var navList = dom.find('.custom-nav-4').data('navList');
		var num = 10;
		//returnObj.suspend = dom.find('.custom-nav-4').data('suspend');
		for(var i in navList){
			returnObj[num] = {title:navList[i].title,name:navList[i].name,prefix:navList[i].prefix,url:navList[i].url,image:navList[i].image.replace('./upload/','')};
			num++;
		}

	};

	returnArr['component'] = function(){
		domHtml = dom.find('.custom-richtext');
		if(domHtml.data('name')!=''){
			returnObj.type = 'component';
			returnObj.name = domHtml.data('name');
			returnObj.id = domHtml.data('id');
			returnObj.url = domHtml.data('url');
		}
	};

	returnArr['link'] = function(){
		returnObj.type = 'link';
		var navList = dom.find('.custom-nav').data('navList');
		var num = 10;
		for(var i in navList){
			if(navList[i].type == 'link'){
				returnObj[num] = {name:navList[i].name,url:navList[i].url,prefix:navList[i].prefix,type:navList[i].type};
			}else{
				returnObj[num] = {id:navList[i].id,name:navList[i].name,number:navList[i].number,url:navList[i].url,prefix:navList[i].prefix,type:navList[i].type,'widget':navList[i].widget};
			}
			num++;
		}
	};

	returnArr['image_ad'] = function(){
		var domHtml = dom.find('.control-group');
		returnObj.type = 'image_ad';
		returnObj.image_type = domHtml.data('type');
		returnObj.image_size = domHtml.data('size');
		returnObj.max_height = domHtml.data('max_height');
		returnObj.max_width = domHtml.data('max_width');
		returnObj.nav_list = {};
		var navList = domHtml.data('navList');
		var num = 10;
		for(var i in navList){
			returnObj.nav_list[num] = {title:navList[i].title,name:navList[i].name,prefix:navList[i].prefix,url:navList[i].url,image:navList[i].image.replace('./upload/','')};
			num++;
		}
	};

	returnArr['subject_menu'] = function(){
		var domHtml = dom.find('.control-group');

		returnObj.type = 'subject_menu';
		returnObj.subtype_list = {};
		var subtypeList = domHtml.data('subtype_list');

		var num = 0;
		for(var i in subtypeList) {
			returnObj.subtype_list[num] = {id : i, title : subtypeList[i]};
			num++;
		}
	};

	returnArr['goods_group1'] = function(){
		var domHtml = dom.find('.goods_group1');
		returnObj.type = 'goods_group1';

		returnObj.goods_group1={};
		var goods_group_arr = domHtml.data('goods_group1_arr');
		var num=0;
		for(var i in goods_group_arr){
			returnObj.goods_group1[num] = {id:goods_group_arr[i].id,title:goods_group_arr[i].title,show_num:goods_group_arr[i].show_num};
			num++;
		}
	};

	returnArr['goods_group2'] = function(){
		var domHtml = dom.find('.control-group');
		returnObj.type = 'goods_group2';
		returnObj.size = domHtml.data('size');
		returnObj.size_type = domHtml.data('size_type');
		returnObj.buy_btn = domHtml.data('buy_btn');
		returnObj.buy_btn_type = domHtml.data('buy_btn_type');
		returnObj.show_title = domHtml.data('show_title');
		returnObj.price = domHtml.data('price');
		returnObj.goods = {};
		var goods = domHtml.data('goods');
		var num = 0;

		for(var i in goods){
			returnObj.goods[num] = {id:goods[i].id,title:goods[i].title,url:goods[i].url};
			num++;
		}
	};

	returnArr['goods_group3'] = function(){
		var domHtml = dom.find('.goods_group3');
		returnObj.type = 'goods_group3';
        returnObj.show_type = domHtml.data('show_type');
        returnObj.notice_content = domHtml.data('notice_content');

		returnObj.goods_group3={};
		var goods_group_arr = domHtml.data('goods_group3_arr');
		for(var i in goods_group_arr){
			returnObj.goods_group3[i] = {id:goods_group_arr[i].id,title:goods_group_arr[i].title,show_num:goods_group_arr[i].show_num};
		}
	};
	
	returnArr['coupons'] = function(){
		var domHtml=dom.find('.custom-coupon');
		returnObj.type='coupons';
		returnObj.coupon_arr={};
		var coupon_list = domHtml.data('coupon_data');
		var num=0;

		if(!coupon_list){
			coupon_list={};
		}
		
		if (coupon_list.coupon_arr != undefined) {
			//console.log('2');
			for(var i in coupon_list.coupon_arr){
				returnObj.coupon_arr[num]={id:coupon_list.coupon_arr[i].id,title:coupon_list.coupon_arr[i].title,face_money:coupon_list.coupon_arr[i]['face_money'],condition:coupon_list.coupon_arr[i]['condition']};
				num++;
			}
		} else {
			//第一次
			for(var i in coupon_list){
				returnObj.coupon_arr[num]={id:coupon_list[i].id,title:coupon_list[i].title,face_money:coupon_list[i]['face_money'],condition:coupon_list[i]['condition']};
				num++;
			}
		}
	};

	returnArr['goods'] = function(){
		returnObj.type = 'goods';
		var domHtml = dom.find('.control-group');

		returnObj.size = domHtml.data('size');
		returnObj.size_type = domHtml.data('size_type');
		returnObj.buy_btn = domHtml.data('buy_btn');
		returnObj.buy_btn_type = domHtml.data('buy_btn_type');
		returnObj.show_title = domHtml.data('show_title');
		returnObj.price = domHtml.data('price');
		returnObj.goods = {};

		var goods = domHtml.data('goods');
		var num = 0;

		for(var i in goods){
			returnObj.goods[num] = {id:goods[i].id,title:goods[i].title,price:goods[i].price,url:goods[i].url,image:goods[i].image.replace('./upload/','')};
			num++;
		}
	};
	
	returnArr['article'] = function(){
		var activity_list = dom.find('.activity').data('article_data');
		returnObj.type = 'article';
		returnObj.activity_arr = {};
		returnObj.name = activity_list.name;
		
		var num=0;
		if (!activity_list) {
			activity_list = {};
		}

		if (activity_list.activity_arr != undefined) {
			//第二次
			for (var i in activity_list.activity_arr) {
				returnObj.activity_arr[num] = {
					id: activity_list.activity_arr[i].id,
					title: activity_list.activity_arr[i].title,
					atype:activity_list.activity_arr[i].atype
				};
				num++;
			}
		} else {
			//第一次
			for (var i in activity_list) {
				returnObj.activity_arr[num] = {id: activity_list[i].id, title: activity_list[i].title, atype:activity_list[i].atype};
				num++;
			}
		}
	};

	returnArr['new_activity_module'] = function() {
		var domHtml=dom.find('.activity');

		returnObj.type='new_activity_module';
		returnObj.activity_arr = domHtml.data('data_list');
		returnObj.name = domHtml.data('name');
		returnObj.display = domHtml.data('display');
	};

	//魔方数据返回
	returnArr['cube'] = function (e) {
		var error = false;
		var domHtml = dom.find('.control-group');
		var table = '<table>';
		table += cube_create(domHtml.data('content'), 79, 79, 'left');
		table += '</table>';
		table = $(table);
		$('.custom-cube2-table').children('.control-group:eq(0)').removeClass('error');
		$('.custom-cube2-table').children('.control-group:eq(0)').find('.help-desc').next('.error-message').remove();
		table.find('.not-empty').each(function (i) {
			if ($(this).siblings('td').hasClass('empty')) {
				if (!dom.hasClass('editing')) {
					dom.trigger('click');
				}
				$('.custom-cube2-table').children('.control-group:eq(0)').addClass('error');
				$('.custom-cube2-table').children('.control-group:eq(0)').find('.help-desc').after('<p class="help-block error-message">必须添加满4列。</p>');
				error = true;
				returnObj = false;
				return false;
			} else {
				if ($(this).closest('tr').prev('tr').find('td').hasClass('empty')) {
					if (!dom.hasClass('editing')) {
						dom.trigger('click');
						var t = setTimeout("checkCubeSelected()", 100);
					} else {
						checkCubeSelected();
					}
					error = true;
					returnObj = false;
					return false;
				}
			}
		});
		
		if (error == false) {
			returnObj.type = 'cube';
			returnObj.content = domHtml.data('content');
			var cube_save_data = returnObj.content;
			for (var i in cube_save_data) {
				if (cube_save_data[i].image == '') {
					if (!dom.hasClass('editing')) {
						dom.trigger('click');
						var t = setTimeout("checkCubeContent(" + i + ")", 100);
					} else {
						checkCubeContent(i);
					}
					error = true;
					break;
				}
			}
			if (error) {
				if (!dom.hasClass('editing')) {
					dom.trigger('click');
				}
				
				returnObj = false;
			}
		}
	};

	// 地图数据
	returnArr['map'] = function () {
		returnObj.type = 'map';
		var domHtml = dom.find('.control-group');
		returnObj.province = domHtml.data('province');
		returnObj.city = domHtml.data('city');
		returnObj.area = domHtml.data('area');
		returnObj.address = domHtml.data('address');
		returnObj.lng = domHtml.data('lng');
		returnObj.lat = domHtml.data('lat');
		returnObj.entity_name = domHtml.data('entity_name');
	};


    // 联系店铺数据
    returnArr['store_map'] = function () {

        var province = $("#province option:checked").text();
        var city = $("#city option:checked").text();
        var area = $("#area option:checked").text();
        returnObj.type = 'store_map';
        var domHtml = dom.find('.control-group');
        returnObj.province = domHtml.data('province');
        returnObj.city = domHtml.data('city');
        returnObj.area = domHtml.data('area');
        returnObj.address = domHtml.data('address');
        returnObj.lng = domHtml.data('lng');
        returnObj.lat = domHtml.data('lat');
        returnObj.entity_name = domHtml.data('entity_name');
        returnObj.storename = domHtml.data('storename');
        returnObj.storetel = domHtml.data('storetel');
        returnObj.tel_play = domHtml.data('tel_play');
        returnObj.store_play = domHtml.data('store_play');

    };


    // 秒杀、拼团、砍价组件
    var activityModules = ['seckill_module','bargain_module','tuan_module'];
    for (var i = 0; i < activityModules.length; i++) {
        var moduleName = activityModules[i];
        (function (activityModule) {
            returnArr[activityModule] = function(){
                var storeIndex = dom.data('vue-store-index');
                var store = vueStores[storeIndex];
                returnObj.type = activityModule
                returnObj.config = store.state.config
                returnObj.activities = store.state.activities
            }
        })(moduleName);
    }


    // 店铺分类
    returnArr['store_cate'] = function () {
		// 店铺分类
		var store_cate     = $('.addCategoryBtn').data('shopCatList');
		var store_cate_tmp = [];
		for (var i in store_cate ) {
			store_cate_tmp.push(store_cate[i])
		}

        returnObj.type = 'store_cate';
        returnObj.store_cate_arr = store_cate_tmp;

    };
	
	var fieldType = dom.data('field-type');
	if (returnArr[fieldType]) {
		returnArr[fieldType]();
	}
	return returnObj;
};

var setHtml = function(json){
	//赋值
	console.log(json);
	var arr = $.parseJSON(json);
	console.log(arr);
	for(var i in arr){
		// console.log(arr);
		setEvent(arr[i]);
	}
};
var setEvent = function(obj){
	console.log('obj', obj);
	//赋值
	var clickArr=[];
	var show_deletes="";
	var app_field = $('<div class="app-field clearfix"><div class="control-group"><div class="component-border"></div></div><div class="actions"><div class="actions-wrap"><span class="action edit">编辑</span><span class="action add">加内容</span><span class="action delete">删除</span></div></div><div class="sort"><i class="sort-handler"></i></div></div>');
	app_field.data('field-type', obj.field_type);

	clickArr['rich_text'] = function(){
		var defaultHtml = '<p>点此编辑『富文本』内容 ——&gt;</p><p>你可以对文字进行<strong>加粗</strong>、<em>斜体</em>、<span style="text-decoration:underline;">下划线</span>、<span style="text-decoration:line-through;">删除线</span>、文字<span style="color:rgb(0,176,240);">颜色</span>、<span style="background-color:rgb(255,192,0);color:rgb(255,255,255);">背景色</span>、以及字号<span style="font-size:20px;">大</span><span style="font-size:14px;">小</span>等简单排版操作。</p><p>还可以在这里加入表格了</p><table><tr><td width="93" valign="top" style="word-break:break-all;">中奖客户</td><td width="93" valign="top" style="word-break:break-all;">发放奖品</td><td width="93" valign="top" style="word-break: break-all;">备注</td></tr><tr><td width="93" valign="top" style="word-break:break-all;">猪猪</td><td width="93" valign="top" style="word-break: break-all;">内测码</td><td width="93" valign="top" style="word-break:break-all;"><em><span style="color: rgb(255, 0, 0);">已经发放</span></em></td></tr><tr><td width="93" valign="top" style="word-break:break-all;">大麦</td><td width="93" valign="top" style="word-break:break-all;">积分</td><td width="93" valign="top" style="word-break: break-all;"><a href="javascript: void(0);" target="_blank">领取地址</a></td></tr></table><p style="text-align:left;"><span style="text-align:left;">也可在这里插入图片、并对图片加上超级链接，方便用户点击。</span></p>';

		domHtml = $('<div class="custom-richtext"></div>');

		if(obj.content.content){
			domHtml.html(obj.content.content).data('has_amend','1');
		}else{
			domHtml.html(defaultHtml).data('has_amend','0');
		}
		if(obj.content.bgcolor){
			domHtml.css('background-color',obj.content.bgcolor).data('bgcolor',obj.content.bgcolor);
		}else{
			domHtml.data('bgcolor','');
		}
		if(obj.content.screen == '1'){
			domHtml.addClass('custom-richtext-fullscreen').data('fullscreen','1');
		}else{
			domHtml.data('fullscreen','0');
		}
		app_field.find('.control-group').prepend(domHtml);
	};

	clickArr['notice'] = function(){
		var content = '';
		if(typeof obj.content.content != 'undefined'){
			content = obj.content.content;
		}
		app_field.find('.control-group').prepend('<div class="custom-notice"><div class="custom-notice-inner"><div class="custom-notice-scroll"><span>公告：' + content + '</span></div></div></div>');
		app_field.find('.custom-notice').data('content',content);
	};

	clickArr['title'] = function(){
		//赋值，设置左侧dom
		var position = '';
		switch(obj.content.show_method){
			case "0" : position = 'text-left';break;
			case "1" : position = 'text-center';break;
			case "2" : position = 'text-right';break;
		}
		var content = '<div class="custom-title '+position+'"><h2 class="title">' + (obj.content.title ? obj.content.title : '') + '</h2>';
		content += '<p class="sub_title">' + (obj.content.sub_title ? obj.content.sub_title : '') + '</p>';
		content += '</div>';


		app_field.find('.control-group').prepend(content);
		app_field.find('.custom-title').data({'title':(obj.content.title ? obj.content.title : ''),'sub_title':(obj.content.sub_title ? obj.content.sub_title : ''),'show_method':(obj.content.show_method ? obj.content.show_method : ''),'txtcolor':(obj.content.txtcolor ? obj.content.txtcolor : ''),'txtcolor2':(obj.content.txtcolor2 ? obj.content.txtcolor2 : ''),'bgcolor':(obj.content.bgcolor ? obj.content.bgcolor : '')});

		//判断
		if(obj.content.txtcolor){
			app_field.find('.custom-title .title').css('color',obj.content.txtcolor+' !important;');
		}
		if(obj.content.txtcolor2){
			app_field.find('.custom-title .sub_title').css('color',obj.content.txtcolor2+' !important;');
		}
		if(obj.content.bgcolor){
			app_field.find('.custom-title').css('background-color',obj.content.bgcolor);
		}
	};

	//'hour':'','day':'','number':'','px_style':''
	clickArr['subject_display'] = function(){
		var content="";

		var myDate = new Date();var yue = myDate.getMonth()+1;var ri = myDate.getDate();var days = "星期";days_code = myDate.getDay();days_code = parseInt(days_code);
		if(days_code == '0') days += "日";if(days_code == '1') days += "一";if(days_code == '2') days += "二";if(days_code == '3') days += "三";if(days_code == '4') days += "四";if(days_code == '5') days += "五";if(days_code == '6') days += "六";
		content = '<article class="subject subject_display"><section><ul class="show_list"><li><div class="show_title clearfix"> <span>'+yue+'月'+ri+'日&nbsp;'+days+'</span><i><em></em>下次更新8:00</i> </div><ul class="product_show"><li> <a href="product_info.html"><img src="./upload/images/zhanshi_demo_goods.jpg" class="enlarge"><i class="active"><em></em>9999</i><p>专题名称<b></b></p></a> </li></ul></section></article>';


		app_field.find('.control-group').prepend(content);
		app_field.find('.subject_display').data({'day_type':(obj.content.day_type ? obj.content.day_type : '1'),'px_style':(obj.content.px_style ? obj.content.px_style : 'asc'),'hour':(obj.content.hour ? obj.content.hour : '0'),'update_hour':(obj.content.update_hour ? obj.content.update_hour : '1'),'day':(obj.content.day ? obj.content.day : '3'),'number':(obj.content.number ? obj.content.number : '1')});
		app_field.find('.subject_display').attr({'day_type':(obj.content.day_type ? obj.content.day_type : '1'),'hour':(obj.content.hour ? obj.content.hour : '0'),'update_hour':(obj.content.update_hour ? obj.content.update_hour : '1'),'day':(obj.content.day ? obj.content.day : '3'),'number':(obj.content.number ? obj.content.number : '1')});


	};

	clickArr['tpl_shop'] = function(){
		var bg1 = "";
		if(obj.content.bgcolor){
			bg1 += "background-color:"+obj.content.bgcolor+";";
		}

		if(obj.content.shop_head_bg_img) {
			 bg1 += "background-image:url("+obj.content.shop_head_bg_img+");";
		} else {
			 //bg1 += "background-image: url(/upload/images/head_bg1.png);";
		}
		var imgs="";
		if(obj.content.shop_head_logo_img) {
			//读取店铺logo
			imgs = store_logo ? store_logo : obj.content.shop_head_logo_img;
		} else {
			imgs = staticpath+"images/default_shop.png";
			//读取店铺logo
			imgs = store_logo ? store_logo : staticpath + "images/default_shop.png";
		}
		var title = obj.content.title ? obj.content.title : store_name;

		var content = '<div class="custom-title text-left"><div class="tpl-shop">';
		content += '		<div class="tpl-shop-header" style="'+bg1+'">';
		content += '		<div class="tpl-shop-title">'+title+'</div>';
		content += '		<div class="tpl-shop-avatar"><img width="80" height="80" src="'+imgs+'" alt=""></div></div>';
		content += '	<div class="tpl-shop-content">';
		content += '<ul class="clearfix"><li><a href="javascript:;"><span class="count">0</span> <span class="text">全部商品</span></a></li><li><a href="javascript:;"><span class="count mycard"></span> <span class="text">会员卡</span></a></li><li><a href="javascript:;"><span class="count user"></span> <span class="text">我的订单</span></a></li></ul>';
		content += '</div></div></div><div class="component-border"></div>';
		app_field.find('.control-group').prepend(content);

		app_field.find('.tpl-shop').data({'bgcolor':(obj.content.bgcolor ? obj.content.bgcolor : ''),'title':(obj.content.title ? obj.content.title : ''),'shop_head_bg_img':(obj.content.shop_head_bg_img ? obj.content.shop_head_bg_img : ''),'shop_head_logo_img':(imgs ? imgs : '')});


		if(obj.content.bgcolor){
			if(!obj.content.shop_head_bg_img) {
				app_field.find('.tpl-shop-header').css('background-color',obj.content.bgcolor);
			}
		}
	};

	clickArr['tpl_shop1'] = function(){
		var bg1 = "";
		if(obj.content.bgcolor){
			bg1 += "background-color:"+obj.content.bgcolor+";";
		}

		if(obj.content.shop_head_bg_img) {
			 bg1 += "background-image:url("+obj.content.shop_head_bg_img+");";
		} else {
			 //bg1 += "background-image: url(/upload/images/tpl_wxd_bg.png);";
		}
		var imgs="";
		if(obj.content.shop_head_logo_img) {
			imgs = store_logo?store_logo:obj.content.shop_head_logo_img;
			/////---imgs = obj.content.shop_head_logo_img;
		} else {
		//	imgs = "/upload/images/moren_head.jpg";
			imgs = store_logo?store_logo:"./upload/images/moren_head.png";
		}
		var title = obj.content.title ? obj.content.title : store_name;
		var content  = '<div class="tpl-shop1 tpl-wxd"> ';
		content += '<div class="tpl-wxd-header" style="'+bg1+'">';
		content += '<div class="tpl-wxd-title">'+title+'</div>';
		content += '<div class="tpl-wxd-avatar"><img src="'+imgs+'" alt=""></div> </div>';
		content += '</div>';

		app_field.find('.control-group').prepend(content);

		app_field.find('.tpl-shop1').data({'bgcolor':(obj.content.bgcolor ? obj.content.bgcolor : ''),'title':(obj.content.title ? obj.content.title : ''),'shop_head_bg_img':(obj.content.shop_head_bg_img ? obj.content.shop_head_bg_img : ''),'shop_head_logo_img':(imgs ? imgs : '')});
		if(obj.content.bgcolor){
			if(!obj.content.shop_head_bg_img) {
				app_field.find('.tpl-wxd-header').css('background-color',obj.content.bgcolor);
			}
		}
	};

	clickArr['line'] = function(){
		app_field.find('.control-group').prepend('<div class="custom-line-wrap"><hr class="custom-line"/></div>');
	};

	clickArr['white'] = function(){
		app_field.find('.control-group').prepend('<div class="custom-white text-center" style="height:'+obj.content.height+'px;"></div>');
		app_field.find('.custom-white').data({'left':obj.content.left,'height':obj.content.height});
	};

	clickArr['search'] = function(){
		app_field.find('.control-group').prepend('<div class="custom-search"><form action="/" method="GET"><input type="text" class="custom-search-input" placeholder="商品搜索：请输入商品关键字" disabled=""/><button type="submit" class="custom-search-button">搜索</button></form></div>');
	};

	clickArr['attention_collect'] = function(){
		app_field.find('.control-group').prepend('<div class="ft-links custom-attention_collect"><a href="#" target="_blank">收藏店铺(100)</a><a href="#" target="_blank">浏览店铺</a></div>');
	};

	clickArr['store'] = function(){
		app_field.find('.control-group').prepend('<div class="custom-store"><a class="custom-store-link clearfix" href="javascript:;"><div class="custom-store-img"></div><div class="custom-store-name">店铺标题</div><div class="custom-store-enter">进入店铺</div></a></div>');
	};

	clickArr['text_nav'] = function(){
		console.log(obj.content);
		var html = '<ul class="custom-nav clearfix">';
		for(var i in obj.content){
			html += '<li><a class="clearfix" href="javascript:void(0);"><span class="custom-nav-title">'+obj.content[i].title+'</span><i class="right right-arrow"></i></a></li>';
		}
		html += '</ul>';
		app_field.find('.control-group').prepend(html);
		app_field.find('.custom-nav').data('navList',obj.content);
	};

	clickArr['image_nav'] = function(){
		var html = '<ul class="custom-nav-4 clearfix">';
		for(var i in obj.content){
			obj.content[i].image = obj.content[i].image!='' ? obj.content[i].image : '';
			html += '<li><span class="nav-img-wap">'+ (obj.content[i].image!='' ? '<img src="'+obj.content[i].image+'"/>' : '&nbsp;')+'</span>'+ (obj.content[i].title!='' ? '<span class="title">'+obj.content[i].title+'</span>' : '')+'</li>';
		}
		html += '</ul>';
		app_field.find('.control-group').prepend(html);
		app_field.find('.custom-nav-4').data('navList',obj.content);
		//app_field.find('.custom-nav-4').data('suspend',obj.suspend);
	};
	clickArr['image_nav_bottom'] = function(){
		var html = '<ul class="custom-nav-4 clearfix">';
		for(var i in obj.content){
			obj.content[i].image = obj.content[i].image!='' ? obj.content[i].image : '';
			html += '<li><span class="nav-img-wap">'+ (obj.content[i].image!='' ? '<img src="'+obj.content[i].image+'"/>' : '&nbsp;')+'</span>'+ (obj.content[i].title!='' ? '<span class="title">'+obj.content[i].title+'</span>' : '')+'</li>';
		}
		html += '</ul>';
		app_field.find('.control-group').prepend(html);
		app_field.find('.custom-nav-4').data('navList',obj.content);
		//app_field.find('.custom-nav-4').data('suspend',obj.suspend);
	};

	clickArr['component'] = function(){
		var domHtml = $('<div class="custom-richtext" style="padding-bottom:10px;">'+obj.content.name+'</div>');
		domHtml.data({'name':obj.content.name,'id':obj.content.id,'url':obj.content.url});
		app_field.find('.control-group').prepend(domHtml);
	};

	clickArr['link'] = function(){
		var html = '<ul class="custom-nav clearfix">';
		for(var i in obj.content){
			if (obj.content[i].type == 'widget' && obj.content[i].widget == 'goodcat') {
				html += '<li><a class="clearfix" href="javascript:void(0);"><span class="custom-nav-title">'+obj.content[i].name+' 的『关联链接』</span><i class="right right-arrow"></i></a></li>';
			} else if(obj.content[i].type == 'link'){
				html += '<li><a class="clearfix" href="javascript:void(0);"><span class="custom-nav-title">'+obj.content[i].name+'</span><i class="right right-arrow"></i></a></li>';
			}else{
                html += '<li><a class="clearfix" href="javascript:void(0);"><span class="custom-nav-title">'+obj.content[i].name+' 的『关联链接』</span><i class="right right-arrow"></i></a></li>';
				// for(var j=1;j<=obj.content[i].number;j++){
				// 	html += '<li><a class="clearfix" href="javascript:void(0);"><span class="custom-nav-title">第'+j+'条 '+obj.content[i].name+' 的『关联链接』</span><i class="right right-arrow"></i></a></li>';
				// }
			}
		}
		html += '</ul>';
		app_field.find('.control-group').prepend(html);
		app_field.find('.custom-nav').data('navList',obj.content);
	};

	clickArr['image_ad'] = function(){
		var html = '';
		if(getObjLength(obj.content.nav_list) == 0){
			html += '<div class="custom-image-swiper"><div class="swiper-container" style="height: 80px"><div class="swiper-wrapper"><img style="max-height:80px;display:block;" src="'+upload_url+'/images/image_ad_demo.jpg"/></div></div></div>';
			obj.content.nav_list = {};
		}else{
			if(!obj.content.image_type){
				obj.content.image_type = 0;
			}
			if(!obj.content.image_size){
				obj.content.image_size = 0;
			}
			var html = '';
			if(obj.content.image_type == '0'){
				html+= '<div class="custom-image-swiper"><div class="swiper-container"><div class="swiper-wrapper">';
				var j = 0;
				for(var i in obj.content.nav_list){
					if (j == 0) {
					obj.content.nav_list[i].image = obj.content.nav_list[i].image!='' ? obj.content.nav_list[i].image : '';
					html += '<div class="swiper-slide"><a href="javascript:void(0);">'+(obj.content.nav_list[i].title!='' ? '<h3 class="title">'+obj.content.nav_list[i].title+'</h3>' : '')+'<img src="'+obj.content.nav_list[i].image+'" style="max-height:'+obj.content.max_height+'px;"/></a></div>';
					}
					j++;
				}
				html+= '</div></div></div>';
				if(getObjLength(obj.content.nav_list) > 1){
					html+= '<div class="swiper-pagination">';
					var num=0;
					for(var i in obj.content.nav_list){
						html += '<span class="swiper-pagination-switch'+(num==0 ? ' swiper-active-switch' :'')+'"></span>';
						num++;
					}
					html+= '</div>';
				}
			}else{
				html+= '<ul class="custom-image clearfix">';
				for(var i in obj.content.nav_list){
					//obj.content.nav_list[i].image = obj.content.nav_list[i].image!='' ? './upload/'+obj.content.nav_list[i].image : '';
					obj.content.nav_list[i].image = obj.content.nav_list[i].image!='' ? obj.content.nav_list[i].image : '';
					html+= '<li'+(obj.content.image_size=='1' ? ' class="custom-image-small"' : '')+'>'+(obj.content.nav_list[i].title!='' ? '<h3 class="title">'+obj.content.nav_list[i].title+'</h3>' : '')+'<img src="'+obj.content.nav_list[i].image+'"/></li>';
				}
				html+= '</ul>';
			}
		}
		app_field.find('.control-group').prepend(html).data({'navList':obj.content.nav_list,'type':(obj.content.image_type ? obj.content.image_type : 0),'size':(obj.content.image_size ? obj.content.image_size : 0),'max_height':(obj.content.max_height ? obj.content.max_height : 0),'max_width':(obj.content.max_width ? obj.content.max_width : 0)});
	};
	
	//专题分类导航
	clickArr['subject_menu'] = function(){

		var html = "";
		if(getObjLength(obj.content.subtype_list) == 0)  {
			html += '<div style="width:320px;overflow:hidden" class="custom-subject_menu mui-slider-indicator mui-segmented-control mui-segmented-control-inverted menu_list"><ul class="clearfix" style="width: 738px;"><li class=" "><a class="mui-control-item mui-active" href="#item1mobile">精选</a> </li><li><a class="mui-control-item" href="#item2mobile"> 礼物</a> </li><li><a class="mui-control-item" href="#item3mobile"> 美食</a> </li><li><a class="mui-control-item" href="#item4mobile"> 家居</a> </li><li><a class="mui-control-item" href="#item5mobile"> 运动</a> </li><li><a class="mui-control-item" href="#item6mobile"> 精选</a> </li><li><a class="mui-control-item" href="#item7mobile"> 精选</a> </li><li><a class="mui-control-item" href="#item8mobile"> 礼物</a> </li><li><a class="mui-control-item" href="#item9mobile"> 美食</a> </li><li><a class="mui-control-item" href="#item10mobile">家居</a> </li><li><a class="mui-control-item" href="#item11mobile"> 运动</a> </li><li><a class="mui-control-item" href="#item12mobile"> 精选</a> </li></ul></div ';
			obj.content.subtype_list = {};
		} else {
			//读取数据库数据
			html += '<div style="width:320px;overflow:hidden" class="custom-subject_menu mui-slider-indicator mui-segmented-control mui-segmented-control-inverted menu_list"><ul class="clearfix" style="width: 738px;"><li class=" "><a class="mui-control-item mui-active" href="#item1mobile">精选</a> </li><li><a class="mui-control-item" href="#item2mobile"> 礼物</a> </li><li><a class="mui-control-item" href="#item3mobile"> 美食</a> </li><li><a class="mui-control-item" href="#item4mobile"> 家居</a> </li><li><a class="mui-control-item" href="#item5mobile"> 运动</a> </li><li><a class="mui-control-item" href="#item6mobile"> 精选</a> </li><li><a class="mui-control-item" href="#item7mobile"> 精选</a> </li><li><a class="mui-control-item" href="#item8mobile"> 礼物</a> </li><li><a class="mui-control-item" href="#item9mobile"> 美食</a> </li><li><a class="mui-control-item" href="#item10mobile">家居</a> </li><li><a class="mui-control-item" href="#item11mobile"> 运动</a> </li><li><a class="mui-control-item" href="#item12mobile"> 精选</a> </li></ul></div';
		}

		//此处给已经添加的专题分类赋值给data
		app_field.find('.control-group').prepend(html);

		var data = [];
		if (obj.content.subtype_list) {
			for (var i in obj.content.subtype_list) {
				data[obj.content.subtype_list[i].id] = obj.content.subtype_list[i].title
			}
		}
		app_field.find('.control-group').data('subtype_list', data);
		app_field.find('.control-group').attr('subtype_list', data);
	};
    // 商品分组(左部导航)
	clickArr['goods_group1'] = function(){
		var html='<ul class="goods_group1 clearfix"><div class="custom-tag-list clearfix"><div class="custom-tag-list-menu-block js-collection-region" style="min-height: 323px;"><ul class="custom-tag-list-side-menu">';
		var has_group = false;
		for(var i in obj.content.goods_group1){
			if (i == 0) {
				html+='<li><a href="javascript:;" class="current">' + obj.content.goods_group1[i].title + '</a></li>';
			} else {
				html+='<li><a href="javascript:;">' + obj.content.goods_group1[i].title + '</a></li>';
			}
			
			has_group = true;
		}
		
		if (!has_group) {
			html += '<li><a href="javascript:;" class="current">商品分组一</a></li><li><a href="javascript:;">商品分组二</a></li><li><a href="javascript:;">商品分组三</a></li>';
		}
		
		html += '</ul></div><div class="custom-tag-list-goods"><ul class="custom-tag-list-goods-list"><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="'+staticpath+'/images/kd5.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="'+staticpath+'/images/kd1.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="'+staticpath+'/images/kd3.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="'+staticpath+'/images/kd4.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span></div></li></ul></div></div></ul>';

		app_field.find('.control-group').prepend(html);

		app_field.find('.control-group > .goods_group1').data('goods_group1_arr',obj.content.goods_group1);
	};

	clickArr['goods_group2'] = function(){
		if(obj.content.goods){
			for(var i in obj.content.goods){
				obj.content.goods[i].image = obj.content.goods[i].image;
			}
		}
		//此处给已经添加的商品赋值给data
		app_field.find('.control-group').html('<ul class="sc-goods-list clearfix size-2 card pic"></ul>').data({'goods':obj.content.goods,'size':(obj.content.size ? obj.content.size : '0'),'size_type':(obj.content.size_type ? obj.content.size_type : '0'),'buy_btn':(obj.content.buy_btn ? obj.content.buy_btn : '0'),'buy_btn_type':(obj.content.buy_btn_type ? obj.content.buy_btn_type : '0'),'show_title':(obj.content.show_title ? obj.content.show_title : '0'),'price':(obj.content.price ? obj.content.price : '0')});
		clickEvent(app_field);
		app_field.removeClass('editing');
		$('.js-config-region .app-field').eq(0).trigger('click');
	};

	clickArr['goods_group3'] = function(){
		var html = '';
        if(obj.content.notice_content){
            html+= '<div class="goods_group3_notice"><span>公告：<span class="show_notice">'+obj.content.notice_content+'</span></span></div>';
        }else{
            html+= '<div class="goods_group3_notice"><span>公告：<span class="show_notice"></span></span></div>';
        }

        html+='<div class="goods_group3_title"><ul><li class="active">商品</li><li>商家详情</li></ul></div><ul class="goods_group3 clearfix"><div class="custom-tag-list clearfix"><div class="custom-tag-list-menu-block js-collection-region" style="min-height: 323px;"><ul class="custom-tag-list-side-menu">';

        var has_group = false;
		for(var i in obj.content.goods_group3){
			if (i == 0) {
				html+='<li><a href="javascript:;" class="current">'+obj.content.goods_group3[i].title+'</a></li>';
			} else {
				html+='<li><a href="javascript:;">'+obj.content.goods_group3[i].title+'</a></li>';
			}
			
			has_group = true;
		}
		if (!has_group) {
			html += '<li><a href="javascript:;" class="current">商品分组一</a></li><li><a href="javascript:;">商品分组二</a></li><li><a href="javascript:;">商品分组三</a></li>';
		}
		html += '</ul></div><div class="custom-tag-list-goods"><ul class="custom-tag-list-goods-list"><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="./upload/images/kd5.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span><a class="custom-tag-list-goods-buy" href="javascript:void(0)"><span></span></a></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="./upload/images/kd1.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span><a class="custom-tag-list-goods-buy" href="javascript:void(0)"><span></span></a></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="./upload/images/kd7.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span><a class="custom-tag-list-goods-buy" href="javascript:void(0)"><span></span></a></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="./upload/images/kd4.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span><a class="custom-tag-list-goods-buy" href="javascript:void(0)"><span></span></a></div></li></ul></div><img src="template/user/default/images/goods_group_cart.jpg" style="width: 100%;" /></div></ul>';

		
		app_field.find('.control-group').prepend(html);
		if (obj.content.show_type == 2) {
			app_field.find('.goods_group3_title').hide();
		}
		
		app_field.find('.control-group > .goods_group3').data('goods_group3_arr',obj.content.goods_group3);
        app_field.find('.control-group > .goods_group3').data('show_type', obj.content.show_type);
        app_field.find('.control-group > .goods_group3').data('notice_content', obj.content.notice_content);
	};
	
	clickArr['title3'] = function(){
		var content = '<div class="custom-title text-left"><h2 class="title">' + (obj.content.title ? obj.content.title : '') + '</h2>';
		content += '<p class="sub_title">' + (obj.content.sub_title ? obj.content.sub_title : '') + '</p>';
		content += '</div>';

		app_field.find('.control-group').prepend(content);
		app_field.find('.custom-title').data({'title':(obj.content.title ? obj.content.title : ''),'sub_title':(obj.content.sub_title ? obj.content.sub_title : ''),'show_method':(obj.content.show_method ? obj.content.show_method : ''),'bgcolor':(obj.content.bgcolor ? obj.content.bgcolor : '')});
		if(obj.content.bgcolor){
			app_field.find('.custom-title').css('background-color',obj.content.bgcolor);
		}
	};

	clickArr['coupons']=function(){
		var data;
		if (typeof obj.content.coupon_arr == "undefined") {
			data = obj.content;
		} else {
			data = obj.content.coupon_arr;
		}

		app_field.find('.control-group').empty();
		app_field.find('.control-group').html('<ul class="custom-coupon clearfix"></ul>');
		app_field.find('.control-group > .custom-coupon').data('coupon_data', {'coupon_arr': data});

		clickEvent(app_field);
		//移除
		app_field.removeClass('editing');
		//触发点击
		$('.js-config-region .app-field').eq(0).trigger('click');
	};

	clickArr['goods'] = function(){
		if(obj.content.goods){
			for(var i in obj.content.goods){
				obj.content.goods[i].image = obj.content.goods[i].image;
			}
		}
		//此处给已经添加的商品赋值给data
		app_field.find('.control-group').html('<ul class="sc-goods-list clearfix size-2 card pic"></ul>').data({'goods':obj.content.goods,'size':(obj.content.size ? obj.content.size : '0'),'size_type':(obj.content.size_type ? obj.content.size_type : '0'),'buy_btn':(obj.content.buy_btn ? obj.content.buy_btn : '0'),'buy_btn_type':(obj.content.buy_btn_type ? obj.content.buy_btn_type : '0'),'show_title':(obj.content.show_title ? obj.content.show_title : '0'),'price':(obj.content.price ? obj.content.price : '0')});
		clickEvent(app_field);
		app_field.removeClass('editing');
		$('.js-config-region .app-field').eq(0).trigger('click');
	};

	clickArr['new_activity_module'] = function(){
		var activity_display = 0;
		if (typeof obj.content.display != 'undefined') {
			activity_display = obj.content.display;
		}
		//添加
		app_field.find('.control-group').html('<ul class="activity clearfix"></ul>');
		//存值
		app_field.find('.control-group > .activity').data({'name':obj.content.name,'display':activity_display,'data_list':obj.content.activity_arr});
		
		clickEvent(app_field);
		//移除
		app_field.removeClass('editing');
		//触发点击
		$('.js-config-region .app-field').eq(0).trigger('click');
	};
		
	// 地图
	clickArr['map'] = function () {
		app_field.find('.control-group').prepend('<div style="height: 212px;" class="map"><img style="max-height:212px;display:block;" src="'+staticpath+'images/map.png" /></div>');
		app_field.find('.control-group').data({'province': obj.content.province, 'city':  obj.content.city, 'area':  obj.content.area, 'address':  obj.content.address, 'lng':  obj.content.lng, 'lat':  obj.content.lat, 'entity_name': obj.content.entity_name});
	};


    // 联系店铺
    clickArr['store_map'] = function () {
        var tel_play = obj.content.tel_play == 1  ? 1 : 2;
        var store_play=obj.content.store_play == 1  ? 1 : 2;
        var content= '<div class="contact_store store_map">';
        content+= '<dl><dd class="storename">';
        if(obj.content.storename==null || obj.content.storename==undefined || obj.content.storename==''){
            content+="此处显示店铺名称"
        }else{
            content+=obj.content.storename;
        }
        content+='</dd>';
        content+= '<dt><span class="icon_addess adress_title">此处显示详细地址</span></dt></dl>';
        content+= '<div class="contact_icon">';
        if(tel_play==1){
            content+='<a href="#" class="dianhua"><img src="'+staticpath+'images/dianhua.png" alt=""></a>';
        }else{
            content+='<a href="#" class="dianhua" style="display:none"><img src="'+staticpath+'images/dianhua.png" alt=""></a>';
        }
        if(store_play==1){
            content+='<a href="#" class="adressIcon"><img src="'+staticpath+'images/jingqujiaotong-daozhequ.png" alt=""></a>';
        }else{
            content+='<a href="#" class="adressIcon" style="display:none"><img src="'+staticpath+'images/jingqujiaotong-daozhequ.png" alt=""></a>';
        }
        content+= '</div></div>'

        app_field.find('.control-group').prepend(content);
        app_field.find('.control-group').data({'province': obj.content.province, 'city':  obj.content.city, 'area':  obj.content.area, 'address':  obj.content.address,
            'lng':  obj.content.lng, 'lat':  obj.content.lat, 'entity_name': obj.content.entity_name,'storename':obj.content.storename,
            'storetel':obj.content.storetel,'tel_play':obj.content.tel_play, 'store_play':obj.content.store_play});
    };
    //魔方内容
    clickArr['cube'] = function () {
        var cube_save_data = obj.content.content;
        var cube_selected = [];
        for (var i in cube_save_data) {
            cube_selected[i] = cube_save_data[i].x + ':' + cube_save_data[i].y;
        }
        app_field.find('.control-group').addClass('custom-cube2-table').addClass('cube2-preview').data({'content': cube_save_data, 'selected': cube_selected.toString()});

        clickEvent(app_field);
        //移除
        app_field.removeClass('editing');
        //触发点击
        $('.js-config-region .app-field').eq(0).trigger('click');
    };

    // 秒杀、砍价、拼团组件
    var activityModules = ['seckill_module','bargain_module','tuan_module'];
    for (var i = 0; i < activityModules.length; i++) {
        var moduleName = activityModules[i];
        (function (activityModule) {
            clickArr[activityModule] = function(){
                var initialState = getVuexInitialState(activityModule);
                initialState.config = convertString2Bool(obj.content.config);
                if(typeof initialState.config.currentThemeIndex === 'undefined'){//hack之前没保存的主题色
                    initialState.config.currentThemeIndex = 0
                }
                initialState.activities = obj.content.activities || [];
                var vueStore = new Vuex.Store(assign({state: initialState},getVuexStoreMixin()))
                app_field.data('vue-store-index',vueStores.length);
                vueStores.push(vueStore);
                clickEvent(app_field);
                app_field.removeClass('editing');
                $('.js-config-region .app-field').eq(0).trigger('click');
            }
        })(moduleName);
    }

	clickArr['store_cate']=function(){
		var data;
		if (typeof obj.content.store_cate_arr == "undefined") {
			data = obj.content;
		} else {
			data = obj.content.store_cate_arr;
		}

		app_field.find('.control-group').empty();
		app_field.find('.control-group').html('<div class="app-field addCategoryBtn flex"></div>');

		app_field.find('.control-group .addCategoryBtn').data('shopCatList',  data);

		clickEvent(app_field);
		//移除
		app_field.removeClass('editing');
		//触发点击
		$('.js-config-region .app-field').eq(0).trigger('click');
	};

	if (clickArr[obj.field_type]) {
		clickArr[obj.field_type]();
	}
	$('.js-fields-region .app-fields').append(app_field);
};
var clickEvent = function(dom){
	//移除
	$('.app-entry .app-field').removeClass('editing');
	//添加
	dom.addClass('editing');

	//赋值
	var clickArr=[],domHtml='',rightHtml='',defaultHtml='';

	/**
	 * 富文本
	 */
	clickArr['rich_text'] = function(){
		defaultHtml = '<p>点此编辑『富文本』内容 ——&gt;</p><p>你可以对文字进行<strong>加粗</strong>、<em>斜体</em>、<span style="text-decoration:underline;">下划线</span>、<span style="text-decoration: line-through;">删除线</span>、文字<span style="color: rgb(0, 176, 240);">颜色</span>、<span style="background-color:rgb(255, 192, 0);color:rgb(255, 255, 255);">背景色</span>、以及字号<span style="font-size:20px;">大</span><span style="font-size: 14px;">小</span>等简单排版操作。</p><p>还可以在这里加入表格了</p><table><tr><td width="93" valign="top" style="word-break: break-all;">中奖客户</td><td width="93" valign="top" style="word-break: break-all;">发放奖品</td><td width="93" valign="top" style="word-break: break-all;">备注</td></tr><tr><td width="93" valign="top" style="word-break: break-all;">猪猪</td><td width="93" valign="top" style="word-break: break-all;">内测码</td><td width="93" valign="top" style="word-break: break-all;"><em><span style="color: rgb(255, 0, 0);">已经发放</span></em></td></tr><tr><td width="93" valign="top" style="word-break: break-all;">大麦</td><td width="93" valign="top" style="word-break: break-all;">积分</td><td width="93" valign="top" style="word-break: break-all;"><a href="javascript: void(0);" target="_blank">领取地址</a></td></tr></table><p style="text-align: left;"><span style="text-align: left;">也可在这里插入图片、并对图片加上超级链接，方便用户点击。</span></p>';
		if(dom.find('.control-group .custom-richtext').size() == 0){
			domHtml = $('<div class="custom-richtext"></div>');
			domHtml.html(defaultHtml);
			domHtml.data({'bgcolor':'','fullscreen':'0','has_amend':'0'});
			dom.find('.control-group').prepend(domHtml);
		}else{
			domHtml = dom.find('.custom-richtext');
		}

		rightHtml = $('<div class="edit-rich-text"><form class="form-horizontal"><div class="control-group"><div class="left"><label class="control-label">背景颜色：</label><div class="input-append"><input type="color" value="'+ (domHtml.data('bgcolor')!='' ? domHtml.data('bgcolor') : '#ffffff')+'" name="color" class="span1"/><button class="btn js-reset-bg" type="button">重置</button></div></div><div class="left"><label class="control-label">是否全屏：</label><label class="checkbox inline" style="padding-top:0px;"><input type="checkbox" name="fullscreen" ' + (domHtml.data('fullscreen')=='1' ? 'checked="checked"' : '') + '/> 全屏显示</label></div></div><div class="control-group"><script class="js-editor" type="text/plain"></script></div></form></div>');
		rightHtml.find('input[name="color"]').change(function(){
			domHtml.css('background-color',$(this).val()).data('bgcolor',$(this).val());
		});
		rightHtml.find('.js-reset-bg').click(function(){
			$(this).siblings('input[name="color"]').val('#ffffff');
			domHtml.css('background-color','').data('bgcolor','');
		});
		rightHtml.find(':checkbox[name="fullscreen"]').click(function(){
			if($(this).prop('checked')){
				domHtml.data('fullscreen','1');
				dom.find('.control-group .custom-richtext').addClass('custom-richtext-fullscreen');
			}else{
				domHtml.data('fullscreen','0');
				dom.find('.control-group .custom-richtext').removeClass('custom-richtext-fullscreen');
			}
		});
		$('.js-sidebar-region form').remove();
		$('.js-sidebar-region').empty().html(rightHtml);

		if(content_editor != null){
			KindEditor.remove('.js-editor');
		}
	
		content_editor = KindEditor.create(".js-editor",{
			minWidth:'448px',
			width:'448px',
			height:'300px',
			minHeight:'300px',
			resizeType: 1,
			allowPreviewEmoticons:false,
			allowImageUpload: true,
			filterMode: true,
			autoHeightMode : true,
			afterCreate:function(){
				this.loadPlugin('autoheight');
			},
			items:[
				'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
				'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
				'insertunorderedlist', '|', 'emoticons', 'image', 'link', 'table','diyVideo'
			],
			emoticonsPath:'./static/emoticons/',
			uploadJson:uploadJson,
			afterChange:function(){
				var ue_con = content_editor ? content_editor.html() : '';
				if(ue_con != ''){
					domHtml.data('has_amend','1').html(ue_con);
				}else{
					domHtml.data('has_amend','0').html(defaultHtml);
				}
			}
		});
		if(domHtml.data('has_amend') == '1'){
			content_editor.html(domHtml.html());
		}
	};

	/**
	 * 公告
	 */
	clickArr['notice'] = function(){
		defaultHtml = '<div class="custom-notice-inner"><div class="custom-notice-scroll"><span>公告：</span></div></div>';

		if(dom.find('.control-group .custom-notice').size() == 0){
			domHtml = $('<div class="custom-notice"></div>');
			domHtml.html(defaultHtml).data('content','');
			dom.find('.control-group').prepend(domHtml);
		}else{
			domHtml = dom.find('.custom-notice');
		}

		rightHtml = $('<div><form class="form-horizontal edit-tpl-11-11" onsubmit="return false"><div class="control-group"><label class="control-label">公告：</label><div class="controls"><input type="text" name="content" value="' + (domHtml.data('content')) + '" class="input-xxlarge" placeholder="请填写内容，如果过长，将会在手机上滚动显示"/></div></div></form></div>');
		rightHtml.find("input[name='content']").val(domHtml.data('content'));
		rightHtml.find('.input-xxlarge').blur(function(){
			domHtml.data('content',$(this).val()).find('.custom-notice-scroll').html('<span>公告：' + $(this).val() + '</span>');
		});

		$('.js-sidebar-region').empty().html(rightHtml);
	};

	/**
	 * 标题
	 */
	clickArr['title'] = function(){
		//赋值，左侧默认html
		defaultHtml = '<h2 class="title">点击编辑『标题』</h2>';

		//判断，如果没有则添加dom custom-title
		if(dom.find('.control-group .custom-title').size() == 0){
			//第一次
			domHtml = $('<div class="custom-title text-left"></div>');
			domHtml.data({'title':'','sub_title':'','show_method':'0','txtcolor':'','bgcolor':''}).html(defaultHtml);
			dom.find('.control-group').prepend(domHtml);
		}else{
			//第二次
			domHtml = dom.find('.custom-title');
		}

		//赋值，右侧默认html
		var htmlStr='<div><form class="form-horizontal"><div class="control-group"><label class="control-label"><em class="required">*</em>标题名：</label><div class="controls"><input type="text" name="title" value="' + (domHtml.data('title')) + '" maxlength="100"/></div></div><div class="control-group"><label class="control-label">副标题：</label><div class="controls"><input type="text" class="js-time-holder" value="' + (domHtml.data('sub_title')) + '" style="position:absolute;z-index:-1;"/><input type="text" name="sub_title" value="' + (domHtml.data('sub_title')) + '" maxlength="100"/>&nbsp;&nbsp;<a href="javascript:void(0);" class="js-time">日期</a></div></div><div class="control-group"><label class="control-label">显示：</label><div class="controls"><label class="radio inline"><input type="radio" name="show_method" value="0" ' + (domHtml.data('show_method') == '0' ? 'checked="checked"' : '') + '/>居左显示</label><label class="radio inline"><input type="radio" name="show_method" value="1" ' + (domHtml.data('show_method') == '1' ? 'checked="checked"' : '') + '/>居中显示</label><label class="radio inline"><input type="radio" name="show_method" value="2" ' + (domHtml.data('show_method') == '2' ? 'checked="checked"' : '') + '/>居右显示</label></div></div>';
		htmlStr+='<div class="control-group"><label class="control-label">标题文字颜色：</label><div class="controls"><input type="color" name="txtcolor" value="' + (domHtml.data('txtcolor') == '' ? '#000000' : domHtml.data('txtcolor')) + '"/> <button class="btn js-reset-color" type="button">重置</button></div></div>';
		
		htmlStr+='<div class="control-group"><label class="control-label">副标题文字颜色：</label><div class="controls"><input type="color" name="txtcolor2" value="' + (domHtml.data('txtcolor2') == '' ? '#8C8C8C' : domHtml.data('txtcolor2')) + '"/> <button class="btn js-reset-color2" type="button">重置</button></div></div>';

		htmlStr+='<div class="control-group"><label class="control-label">背景颜色：</label><div class="controls"><input type="color" name="color" value="' + (domHtml.data('bgcolor') == '' ? '#ffffff' : domHtml.data('bgcolor')) + '"/> <button class="btn js-reset-bg" type="button">重置</button></div></div></form></div>';

		rightHtml = $(htmlStr);

		//设置值
		rightHtml.find("input[name='title']").val(domHtml.data('title'));
		rightHtml.find(".js-time-holder").val(domHtml.data('sub_title'));
		rightHtml.find("input[name='sub_title']").val(domHtml.data('sub_title'));

		//失去焦点
		rightHtml.find('input[name="title"]').blur(function(){
			//保存title的值到data
			domHtml.data('title',$(this).val()).find('h2.title').html(($(this).val().length != 0 ? $(this).val() : biaotis));
		});

		//失去焦点
		rightHtml.find('input[name="sub_title"]').blur(function(){
			if($(this).val().length == 0){
				//移除
				domHtml.data('sub_title','').find('.sub_title').remove();
			}else{
				//取值
				domHtml.data('sub_title',$(this).val());
				if(domHtml.find('.sub_title').size() > 0){
					//取值
					domHtml.find('.sub_title').html($(this).val());
				}else{
					//添加
					domHtml.find('.title').after('<p class="sub_title">'+$(this).val()+'</p>');
				}
			}
		});

		// 时间
		var timepicker = rightHtml.find('.js-time-holder');
		timepicker.datetimepicker({
			dateFormat: "yy-mm-dd",
			timeFormat: "hh:mm",
			minDate: new Date,
			changeMonth:true,
			changeYear:true,
			onSelect: function(e){
				//找到同辈sub_title，
				timepicker.siblings('input[name="sub_title"]').val(e).trigger('blur');
				//alert(e.type);
			}
		});
		rightHtml.find('a.js-time').click(function(){
			timepicker.datepicker('show');
		});

		// 按钮
		rightHtml.find('input[name="show_method"]').change(function(){
			//取值
			domHtml.data('show_method',$(this).val());
			switch($(this).val()){
				case '0':
					domHtml.removeClass('text-center text-right').addClass('text-left');
					break;
				case '1':
					domHtml.removeClass('text-left text-right').addClass('text-center');
					break;
				default:
					domHtml.removeClass('text-left text-center').addClass('text-right');
			}
		});

		// 设置颜色
		//当input[name="color"]内容改变时，触发change事件
		rightHtml.find('input[name="color"]').change(function(){
			//先获取值，然后保存值到bgcolor的data
			domHtml.css('background-color',$(this).val()).data('bgcolor',$(this).val());
		});

		rightHtml.find('.js-reset-bg').click(function(){
			$(this).siblings('input[name="color"]').val('#ffffff');
			domHtml.css('background-color','').data('bgcolor','');
		});
		/*设置文字颜色*/
		rightHtml.find('input[name="txtcolor"]').change(function(){
			//先获取值，然后保存值到bgcolor的data
			domHtml.find('.title').css('color',$(this).val()+' !important;').data('txtcolor',$(this).val());
		});
		rightHtml.find('input[name="txtcolor2"]').change(function(){
			//先获取值，然后保存值到bgcolor的data
			domHtml.find('.sub_title').css('color',$(this).val()+' !important;').data('txtcolor2',$(this).val());
		});

		rightHtml.find('.js-reset-color').click(function(){
			$(this).siblings('input[name="txtcolor"]').val('#000000');
			domHtml.find('.title').css('color','#000000').data('txtcolor','');
		});
		rightHtml.find('.js-reset-color2').click(function(){
			$(this).siblings('input[name="txtcolor2"]').val('#000000');
			domHtml.find('.sub_title').css('color','#8C8C8C').data('txtcolor2','');
		});

		//移除
		$('.js-sidebar-region form').remove();
		//添加dom
		$('.js-sidebar-region').empty().html(rightHtml);
	};

	/**
	 * 辅助线
	 */
	clickArr['line'] = function(){
		if(dom.find('.control-group .custom-line-wrap').size() == 0){
			dom.find('.control-group').prepend('<div class="custom-line-wrap"><hr class="custom-line"/></div>');
		}
		$('.js-sidebar-region').empty().html('<div><div class="app-component-desc"><p>辅助线</p></div></div>');
	};

	//辅助空白
	clickArr['white'] = function(){
		if(dom.find('.control-group .custom-white').size() == 0){
			domHtml = $('<div class="custom-white text-center" style="height:30px;"></div>');
			domHtml.data({'left':0,'height':30});
			dom.find('.control-group').prepend(domHtml);
		}else{
			domHtml = dom.find('.control-group .custom-white');
		}
		rightHtml = $('<div><form class="form-horizontal"><div class="control-group white-space-group"><label class="control-label">空白高度：</label><div class="controls controls-slider"><div class="js-slider white-space-slider ui-slider ui-slider-horizontal ui-widget ui-widget-content ui-corner-all" aria-disabled="false"><a class="ui-slider-handle ui-state-default ui-corner-all" href="#" style="left:'+domHtml.data('left')+'%;"></a></div><div class="slider-height"><span class="js-height">'+domHtml.data('height')+'</span> 像素</div></div></div></form></div>');
		var heightDom = rightHtml.find('.js-height');
		rightHtml.find('.ui-slider-handle').hover(function(){
			$(this).addClass('ui-state-hover');
		},function(){
			$(this).removeClass('ui-state-hover ui-state-active');
		}).mousedown(function(){
			$(this).addClass('ui-state-active');
		}).mouseup(function(){
			$(this).removeClass('ui-state-active');
			return false;
		}).mousemove(function(e){
			if($(this).hasClass('ui-state-active')){
				var newLeft = e.pageX - rightHtml.find('.js-slider').offset().left;
				if(newLeft < 0 || newLeft > 250){
					return false;
				}else{
					var left = newLeft/250*100;
					if(left < 1) left = 0;
					if(left > 99) left = 100;
					var height = parseInt(30+left/100*70);
					$(this).css('left',left+'%');
					heightDom.html(height);
					domHtml.data({'left':left,'height':height}).css('height',height);
				}
			}
		});
		rightHtml.find('.js-slider').click(function(e){
			var newLeft = e.pageX - $(this).offset().left;
			if(newLeft < 0 || newLeft > 250){
				return false;
			}else{
				var left = newLeft/250*100;
				if(left < 1) left = 0;
				if(left > 99) left = 100;
				var height = parseInt(30+left/100*70);
				rightHtml.find('.ui-slider-handle').css('left',left+'%');
				heightDom.html(height);
				domHtml.data({'left':left,'height':height}).css('height',height);
			}
			return false;
		});
		$('.js-sidebar-region').empty().html(rightHtml);
	};

	/**
	 * 搜索
	 */
	clickArr['search'] = function(){
		if(dom.find('.control-group .custom-search').size() == 0){
			dom.find('.control-group').prepend('<div class="custom-search"><form action="/" method="GET"><input type="text" class="custom-search-input" placeholder="商品搜索：请输入商品关键字" disabled=""/><button type="submit" class="custom-search-button">搜索</button></form></div>');
		}
		$('.js-sidebar-region').empty().html('<div><div class="app-component-desc"><p>可随意插入任何页面和位置，方便粉丝快速搜索商品.</p><p>注意：搜索的商品是根据商品标题匹配的。</p></div></div>');
	};

	/**
	 * 店铺关注和收藏
	 */
	clickArr['attention_collect'] = function(){
		if(dom.find('.control-group .custom-attention_collect').size() == 0){
			dom.find('.control-group').prepend('<div class="ft-links custom-attention_collect"><a href="#" target="_blank">收藏店铺(100)</a><a href="#" target="_blank">浏览店铺</a></div>');
		}
		$('.js-sidebar-region').empty().html('<div><div class="app-component-desc"><p>店铺收藏和浏览</p></div></div>');
	};

	/**
	 * 进入<br/>店铺
	 */
	clickArr['store'] = function(){
		if(dom.find('.control-group .custom-store').size() == 0){
			dom.find('.control-group').prepend('<div class="custom-store"><a class="custom-store-link clearfix" href="javascript:;"><div class="custom-store-img"></div><div class="custom-store-name">店铺标题</div><div class="custom-store-enter">进入店铺</div></a></div>');
		}
		$('.js-sidebar-region').empty().html('<div><div class="app-component-desc"><p>进入店铺</p></div></div>');
	};

	/**
	 * 文本<br/>导航
	 */
	clickArr['text_nav'] = function(){
		rightHtml = $('<div><form class="form-horizontal"><div class="control-group js-collection-region"><ul class="choices ui-sortable"></ul></div><div class="control-group options"><a class="add-option js-add-option" href="javascript:void(0);"><i class="icon-add"></i> 添加一个文本导航</a></div></form></div>');
		if(dom.find('.control-group .custom-nav').size() == 0){
			domHtml = $('<ul class="custom-nav clearfix"></ul>');
			domHtml.data({'navList':[]});
			dom.find('.control-group').prepend(domHtml);
		}else{
			domHtml = dom.find('.control-group .custom-nav');
		}
		var addContent = function(num,dom){
			var navList = domHtml.data('navList');
			console.log('navList', navList)
			if(num >= 0){
				console.log('randNumber1',num)
				randNumber = num;
				var liContent = '<li class="choice" data-id="'+randNumber+'"><div class="control-group"><label class="control-label"><em class="required">*</em>导航名称：</label><div class="controls"><input type="text" name="title" value="'+navList[num].title+'"/></div></div><div class="control-group"><label class="control-label"><em class="required">*</em>链接到：</label><div class="controls"><div class="control-action clearfix">';

				if(navList[num].name == ''){
					liContent += '<div class="dropdown hover"><a class="js-dropdown-toggle dropdown-toggle" href="javascript:void(0);">设置链接到的页面地址 <i class="caret"></i></a></div>';
				}else{
					liContent += '<div class="left js-link-to link-to"><a href="'+navList[num].url+'" target="_blank" class="new-window link-to-title"><span class="label label-success">'+navList[num].prefix+' <em class="link-to-title-text">'+navList[num].name+'</em></span></a><a href="javascript:;" class="js-delete-link link-to-title close-modal" title="删除">×</a></div><div class="dropdown hover right"><a class="dropdown-toggle" href="javascript:void(0);">修改 <i class="caret"></i></a></div>';
				}
				liContent += '</div></div></div><div class="actions"><span class="action add close-modal" title="添加">+</span><span class="action delete close-modal" title="删除">×</span></div></li>';
				var liHtml  = $(liContent);
				liHtml.find("input[name='title']").val(navList[num].title);
				if(navList[num].name != ''){
					liHtml.find('.js-delete-link').click(function(){
						var fDom = $(this).closest('.control-action');
						fDom.find('.js-link-to').remove();
						fDom.find('.dropdown').removeClass('right').children('a').attr('class','js-dropdown-toggle dropdown-toggle').html('设置链接到的页面地址 <i class="caret">');
						var navList = domHtml.data('navList');
						navList[liHtml.data('id')] = {'title':titleDom.val(),'prefix':'','url':'','name':''};
						domHtml.data('navList',navList);
					});
				}
			}else{
				console.log('randNumber2',num)
				var randNumber = getRandNumber();
				navList[randNumber] = {'title':'店铺分类'};
				domHtml.data('navList',navList);
				var liHtml  = $('<li class="choice" data-id="'+randNumber+'"><div class="control-group"><label class="control-label"><em class="required">*</em>导航名称：</label><div class="controls"><input type="text" name="title" value=""/></div></div><div class="control-group"><label class="control-label"><em class="required">*</em>链接到：</label><div class="controls"><div class="control-action clearfix"><div class="dropdown hover"><a class="js-dropdown-toggle dropdown-toggle" href="javascript:void(0);">设置链接到的页面地址 <i class="caret"></i></a></div></div></div></div><div class="actions"><span class="action add close-modal" title="添加">+</span><span class="action delete close-modal" title="删除">×</span></div></li>');
			}
			var titleDom = liHtml.find('input[name="title"]');
			var nowDom = liHtml.find('.dropdown');
			titleDom.blur(function(){ //标题文框失去焦点
				var navList = domHtml.data('navList');
				navList[liHtml.data('id')].title = titleDom.val();
				domHtml.data('navList',navList);
				buildContent();
			});
			link_box(nowDom,[],function(type,prefix,title,href){
				nowDom.siblings('.js-link-to').remove();
				var beforeDom = $('<div class="left js-link-to link-to"><a href="'+href+'" target="_blank" class="new-window link-to-title"><span class="label label-success">'+prefix+' <em class="link-to-title-text">'+title+'</em></span></a><a href="javascript:;" class="js-delete-link link-to-title close-modal" title="删除">×</a></div>');
				if(titleDom.val().length == 0){
					titleDom.val(title);
				}
				var navList = domHtml.data('navList');
				navList[liHtml.data('id')] = {'title':titleDom.val(),'prefix':prefix,'url':href,'name':title};
				domHtml.data('navList',navList);
				buildContent();

				beforeDom.find('.js-delete-link').click(function(){
					var fDom = $(this).closest('.control-action');
					fDom.find('.js-link-to').remove();
					fDom.find('.dropdown').removeClass('right').children('a').attr('class','js-dropdown-toggle dropdown-toggle').html('设置链接到的页面地址 <i class="caret">');
					var navList = domHtml.data('navList');
					navList[liHtml.data('id')] = {'title':titleDom.val(),'prefix':'','url':'','name':''};
					domHtml.data('navList',navList);
				});
				nowDom.before(beforeDom);
				nowDom.children('a').attr('class','dropdown-toggle').html('修改 <i class="caret"></i>');
			});
			liHtml.find('span.add').click(function(){
				addContent(-1,liHtml);
			});
			liHtml.find('span.delete').click(function(){
				var navList = domHtml.data('navList');
				delete navList[liHtml.data('id')];
				domHtml.data('navList',navList);
				$(this).closest('li.choice').remove();
				buildContent();
			});
			if(dom){
				dom.after(liHtml);
				var navList = domHtml.data('navList');
				var newNavList = [];
				$.each(rightHtml.find('.js-collection-region .ui-sortable > li'),function(i,item){
					newNavList[i] = navList[$(item).data('id')];
					$(item).data('id',i);
				});
				domHtml.data('navList',newNavList);
			}else{
				rightHtml.find('.js-collection-region .ui-sortable').append(liHtml);
			}
			buildContent();
		};
		var buildContent = function(){
			var navList = domHtml.data('navList');
			var html = '';
			for(var i in navList){
				html += '<li><a class="clearfix" href="javascript:void(0);"><span class="custom-nav-title">'+navList[i].title+'</span><i class="right right-arrow"></i></a></li>';
			}
			domHtml.html(html);
		};
		var navList = domHtml.data('navList');
		for(var num in navList){
			addContent(num);
		}
		rightHtml.find('.js-add-option').click(function(){
			addContent(-1);
		});

		$('.js-sidebar-region').empty().html(rightHtml);
	};

	/**
	 * 图片<br/>导航
	 */
	clickArr['image_nav'] = function(){
		rightHtml = $('<div><form class="form-horizontal"><div class="js-collection-region"><div class="alert alert-danger" role="alert">请上传50&times50像素的圆形或方形图片</div><ul class="choices ui-sortable"></ul></div></form></div>');
		if(dom.find('.control-group .custom-nav-4').size() == 0){
			domHtml = $('<ul class="custom-nav-4 clearfix"></ul>');
			domHtml.data({'navList':[{'title':'','prefix':'','url':'','name':'','image':''},{'title':'','prefix':'','url':'','name':'','image':''},{'title':'','prefix':'','url':'','name':'','image':''},{'title':'','prefix':'','url':'','name':'','image':''}]});
			dom.find('.control-group').prepend(domHtml);
		}else{
			domHtml = dom.find('.control-group .custom-nav-4');
		}
		var rightUl = rightHtml.find('.js-collection-region .ui-sortable');
		var navList = domHtml.data('navList');
		/**
		rightHtml.find('input[name="suspend"]').change(function(){
			domHtml.data('suspend',$(this).val());
			buildContent();
		});**/
		for(var i in navList){
			(function(){
				var liContent = '<li class="choice" data-id="'+i+'">';
				liContent += '<div class="choice-image">';
				if(navList[i].image){
					liContent += '<img src="'+navList[i].image+'" width="118" height="118" class="thumb-image"/><a class="modify-image js-trigger-image" href="javascript: void(0);">重新上传</a>';
				}else{
					liContent += '<a class="add-image js-trigger-image" href="javascript: void(0);"><i class="icon-add"></i>  添加图片</a>';
				}
				liContent += '</div>';
				liContent += '<div class="choice-content"><div class="control-group"><label class="control-label">文字：</label><div class="controls"><input class="" type="text" name="title" value="'+(navList[i].title!='' ? navList[i].title : '')+'" maxlength="15"/></div></div><div class="control-group"><label class="control-label">链接：</label><div class="control-action clearfix">';
				if(navList[i].name != ''){
					liContent += '<div class="left js-link-to link-to"><a href="'+navList[i].url+'" target="_blank" class="new-window link-to-title"><span class="label label-success">'+navList[i].prefix+' <em class="link-to-title-text">'+navList[i].name+'</em></span></a><a href="javascript:;" class="js-delete-link link-to-title close-modal" title="删除">×</a></div><div class="dropdown hover right"><a class="dropdown-toggle" href="javascript:void(0);">修改 <i class="caret"></i></a></div>';
				}else{
					liContent += '<div class="dropdown hover"><a class="js-dropdown-toggle dropdown-toggle" href="javascript:void(0);">设置链接到的页面地址 <i class="caret"></i></a></div>';
				}
				liContent += '</div></div></div></li>';
				var liHtml = $(liContent);
				liHtml.find("input[name='title']").val(navList[i].title);
				var liHtmlId = liHtml.data('id');
				if(navList[i].name != ''){
					liHtml.find('.js-delete-link').click(function(){
						var fDom = $(this).closest('.control-action');
						fDom.find('.js-link-to').remove();
						fDom.find('.dropdown').removeClass('right').children('a').attr('class','js-dropdown-toggle dropdown-toggle').html('设置链接到的页面地址 <i class="caret">');
						var navList = domHtml.data('navList');
						navList[liHtmlId].prefix = '';
						navList[liHtmlId].url = '';
						navList[liHtmlId].name = '';
						domHtml.data('navList',navList);
					});
				}
				var titleDom = liHtml.find('input[name="title"]');
				var nowDom = liHtml.find('.dropdown');
				titleDom.blur(function(){
					var navList = domHtml.data('navList');
					navList[liHtml.data('id')].title = titleDom.val();
					domHtml.data('navList',navList);
					buildContent();
				});
				liHtml.find('.js-trigger-image').click(function(){
					var imageDom = $(this);
					upload_pic_box(1,true,function(pic_list){
						if(pic_list.length > 0){
							for(var i in pic_list){
								imageDom.siblings('.thumb-image').remove();
								imageDom.removeClass('add-image').addClass('modify-image').html('重新上传').before('<img src="'+pic_list[i]+'" width="118" height="118" class="thumb-image"/>');
								var navList = domHtml.data('navList');
								navList[liHtml.data('id')].image = pic_list[i];
								domHtml.data('navList',navList);
								buildContent();
							}
						}
					},1);
				});
				link_box(nowDom,[],function(type,prefix,title,href){
					nowDom.siblings('.js-link-to').remove();
					var beforeDom = $('<div class="left js-link-to link-to"><a href="'+href+'" target="_blank" class="new-window link-to-title"><span class="label label-success">'+prefix+' <em class="link-to-title-text">'+title+'</em></span></a><a href="javascript:;" class="js-delete-link link-to-title close-modal" title="删除">×</a></div>');

					var navList = domHtml.data('navList');
					var liHtmlId = liHtml.data('id');
					navList[liHtmlId].prefix = prefix;
					navList[liHtmlId].url = href;
					navList[liHtmlId].name = title;

					beforeDom.find('.js-delete-link').click(function(){
						var fDom = $(this).closest('.control-action');
						fDom.find('.js-link-to').remove();
						fDom.find('.dropdown').removeClass('right').children('a').attr('class','js-dropdown-toggle dropdown-toggle').html('设置链接到的页面地址 <i class="caret">');
						var navList = domHtml.data('navList');
						navList[liHtmlId].prefix = '';
						navList[liHtmlId].url = '';
						navList[liHtmlId].name = '';
						domHtml.data('navList',navList);
					});

					domHtml.data('navList',navList);
					buildContent();
					nowDom.before(beforeDom);
					nowDom.addClass('right').children('a').attr('class','dropdown-toggle').html('修改 <i class="caret"></i>');
				});
				rightUl.append(liHtml);
			})();
		}
		var buildContent = function(){
			var navList = domHtml.data('navList');
			var html = '';
			for(var i in navList){
				html += '<li><span class="nav-img-wap">'+ (navList[i].image!='' ? '<img src="'+navList[i].image+'"/>' : '&nbsp;')+'</span>'+ (navList[i].title!='' ? '<span class="title">'+navList[i].title+'</span>' : '')+'</li>';
			}
			domHtml.html(html);
		};
		var navList = domHtml.data('navList');
		//var suspend = domHtml.data('suspend');
		$('.js-sidebar-region').empty().html(rightHtml);
	};


	/**
	 * 图片<br/>导航=>固定底部悬浮
	 */
	clickArr['image_nav_bottom'] = function(){
		rightHtml = $('<div><form class="form-horizontal"><div class="js-collection-region"><div class="alert alert-danger" role="alert">请上传50&times50像素的圆形或方形图片</div><ul class="choices ui-sortable"></ul></div></form></div>');
		if(dom.find('.control-group .custom-nav-4').size() == 0){
			domHtml = $('<ul class="custom-nav-4 clearfix"></ul>');
			domHtml.data({'navList':[{'title':'','prefix':'','url':'','name':'','image':''},{'title':'','prefix':'','url':'','name':'','image':''},{'title':'','prefix':'','url':'','name':'','image':''},{'title':'','prefix':'','url':'','name':'','image':''}]});
			dom.find('.control-group').prepend(domHtml);
		}else{
			domHtml = dom.find('.control-group .custom-nav-4');
		}
		var rightUl = rightHtml.find('.js-collection-region .ui-sortable');
		var navList = domHtml.data('navList');
		/**
		rightHtml.find('input[name="suspend"]').change(function(){
			domHtml.data('suspend',$(this).val());
			buildContent();
		});**/
		for(var i in navList){
			(function(){
				var liContent = '<li class="choice" data-id="'+i+'">';
				liContent += '<div class="choice-image">';
				if(navList[i].image){
					liContent += '<img src="'+navList[i].image+'" width="118" height="118" class="thumb-image"/><a class="modify-image js-trigger-image" href="javascript: void(0);">重新上传</a>';
				}else{
					liContent += '<a class="add-image js-trigger-image" href="javascript: void(0);"><i class="icon-add"></i>  添加图片</a>';
				}
				liContent += '</div>';
				liContent += '<div class="choice-content"><div class="control-group"><label class="control-label">文字：</label><div class="controls"><input class="" type="text" name="title" value="'+(navList[i].title!='' ? navList[i].title : '')+'" maxlength="15"/></div></div><div class="control-group"><label class="control-label">链接：</label><div class="control-action clearfix">';
				if(navList[i].name != ''){
					liContent += '<div class="left js-link-to link-to"><a href="'+navList[i].url+'" target="_blank" class="new-window link-to-title"><span class="label label-success">'+navList[i].prefix+' <em class="link-to-title-text">'+navList[i].name+'</em></span></a><a href="javascript:;" class="js-delete-link link-to-title close-modal" title="删除">×</a></div><div class="dropdown hover right"><a class="dropdown-toggle" href="javascript:void(0);">修改 <i class="caret"></i></a></div>';
				}else{
					liContent += '<div class="dropdown hover"><a class="js-dropdown-toggle dropdown-toggle" href="javascript:void(0);">设置链接到的页面地址 <i class="caret"></i></a></div>';
				}
				liContent += '</div></div></div></li>';
				var liHtml = $(liContent);
				liHtml.find("input[name='title']").val(navList[i].title);
				var liHtmlId = liHtml.data('id');
				if(navList[i].name != ''){
					liHtml.find('.js-delete-link').click(function(){
						var fDom = $(this).closest('.control-action');
						fDom.find('.js-link-to').remove();
						fDom.find('.dropdown').removeClass('right').children('a').attr('class','js-dropdown-toggle dropdown-toggle').html('设置链接到的页面地址 <i class="caret">');
						var navList = domHtml.data('navList');
						navList[liHtmlId].prefix = '';
						navList[liHtmlId].url = '';
						navList[liHtmlId].name = '';
						domHtml.data('navList',navList);
					});
				}
				var titleDom = liHtml.find('input[name="title"]');
				var nowDom = liHtml.find('.dropdown');
				titleDom.blur(function(){
					var navList = domHtml.data('navList');
					navList[liHtml.data('id')].title = titleDom.val();
					domHtml.data('navList',navList);
					buildContent();
				});
				liHtml.find('.js-trigger-image').click(function(){
					var imageDom = $(this);
					upload_pic_box(1,true,function(pic_list){
						if(pic_list.length > 0){
							for(var i in pic_list){
								imageDom.siblings('.thumb-image').remove();
								imageDom.removeClass('add-image').addClass('modify-image').html('重新上传').before('<img src="'+pic_list[i]+'" width="118" height="118" class="thumb-image"/>');
								var navList = domHtml.data('navList');
								navList[liHtml.data('id')].image = pic_list[i];
								domHtml.data('navList',navList);
								buildContent();
							}
						}
					},1);
				});
				link_box(nowDom,[],function(type,prefix,title,href){
					nowDom.siblings('.js-link-to').remove();
					var beforeDom = $('<div class="left js-link-to link-to"><a href="'+href+'" target="_blank" class="new-window link-to-title"><span class="label label-success">'+prefix+' <em class="link-to-title-text">'+title+'</em></span></a><a href="javascript:;" class="js-delete-link link-to-title close-modal" title="删除">×</a></div>');

					var navList = domHtml.data('navList');
					var liHtmlId = liHtml.data('id');
					navList[liHtmlId].prefix = prefix;
					navList[liHtmlId].url = href;
					navList[liHtmlId].name = title;

					beforeDom.find('.js-delete-link').click(function(){
						var fDom = $(this).closest('.control-action');
						fDom.find('.js-link-to').remove();
						fDom.find('.dropdown').removeClass('right').children('a').attr('class','js-dropdown-toggle dropdown-toggle').html('设置链接到的页面地址 <i class="caret">');
						var navList = domHtml.data('navList');
						navList[liHtmlId].prefix = '';
						navList[liHtmlId].url = '';
						navList[liHtmlId].name = '';
						domHtml.data('navList',navList);
					});

					domHtml.data('navList',navList);
					buildContent();
					nowDom.before(beforeDom);
					nowDom.addClass('right').children('a').attr('class','dropdown-toggle').html('修改 <i class="caret"></i>');
				});
				rightUl.append(liHtml);
			})();
		}
		var buildContent = function(){
			var navList = domHtml.data('navList');
			var html = '';
			for(var i in navList){
				html += '<li><span class="nav-img-wap">'+ (navList[i].image!='' ? '<img src="'+navList[i].image+'"/>' : '&nbsp;')+'</span>'+ (navList[i].title!='' ? '<span class="title">'+navList[i].title+'</span>' : '')+'</li>';
			}
			domHtml.html(html);
		};
		var navList = domHtml.data('navList');
		//var suspend = domHtml.data('suspend');
		$('.js-sidebar-region').empty().html(rightHtml);
	};

	/**
	 * 自定义<br/>模块
	 */
	clickArr['component'] = function(){
		if(dom.find('.control-group .custom-richtext').size() == 0){
			domHtml = $('<div class="custom-richtext" style="padding-bottom:10px;">点击编辑『自定义页面模块』</div>');
			domHtml.data({'name':'','id':'','url':''});
			dom.find('.control-group').prepend(domHtml);
		}else{
			domHtml = dom.find('.control-group .custom-richtext');
		}
		var rightContent = '<div><form class="form-horizontal"><div class="control-group control-group-large"><label class="control-label">自定义页面模块：</label><div class="controls"><div class="control-action">';
		if(domHtml.data('name')!=''){
			rightContent += '<div class="left link-to"><a href="'+domHtml.data('url')+'" target="_blank" class="new-window link-to-title"><span class="label label-success">自定义页面模块 <em class="link-to-title-text">'+domHtml.data('name')+'</em></span></a></div><a href="javascript:void(0);" class="js-add-component add-component">修改</a>';
		}else{
			rightContent += '<a href="javascript:void(0);" class="js-add-component add-component">+添加</a>';
		}
		rightContent += '</div></div></div></form></div>';
		rightHtml = $(rightContent);
		rightHtml.find('.js-add-component').click(function(){
			var nowDom = $(this);
			$('.modal-backdrop,.modal').remove();
			$('body').append('<div class="modal-backdrop fade in widget_link_back"></div>');
			var randNum = getRandNumber();
			var load_url = 'user.php?c=widget&a=component&number='+randNum;
			link_save_box[randNum] = function(type,prefix,title,href){
				nowDom.html('修改').siblings('.link-to').remove();
				nowDom.html('<div class="left link-to"><a href="'+href+'" target="_blank" class="new-window link-to-title"><span class="label label-success">自定义页面模块 <em class="link-to-title-text">'+title+'</em></span></a></div><a href="javascript:void(0);" class="js-add-component add-component">修改</a>');
				domHtml.html(title).data({'name':title,'id':type,'url':href});
			};
			modalDom = $('<div class="modal fade hide js-modal in widget_link_box" aria-hidden="false" style="margin-top:0px;display:block;"><iframe src="'+load_url+'" style="width:100%;height:200px;border:0;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px;"></iframe></div>');
			$('body').append(modalDom);
			modalDom.animate({'margin-top': ($(window).scrollTop() + $(window).height() * 0.05) + 'px'}, "slow", function(){
				modalDom.css({'position':'fixed','top':(($(window).height() - 540)/2),'margin-top':'0px'});
			});
		});
		$('.js-sidebar-region').empty().html(rightHtml);
	};

	/**
	 * 关联<br/>链接
	 */
	clickArr['link'] = function(){
		defaultHtml = '<li><a class="clearfix" href="javascript: void(0);" target="_blank"><span class="custom-nav-title">点此编辑第1条『关联链接』</span><i class="pull-right right-arrow"></i></a></li><li><a class="clearfix" href="javascript: void(0);" target="_blank"><span class="custom-nav-title">点此编辑第2条『关联链接』</span><i class="pull-right right-arrow"></i></a></li><li><a class="clearfix" href="javascript: void(0);" target="_blank"><span class="custom-nav-title">点此编辑第n条『关联链接』</span><i class="pull-right right-arrow"></i></a></li>';
		rightHtml = $('<div><form class="form-horizontal"><div class="control-group js-collection-region"><ul class="choices ui-sortable"></ul></div><div class="control-group options"><a class="add-option js-add-option" href="javascript:void(0);"><i class="icon-add"></i> 添加一个关联链接</a></div></form></div>');
		if(dom.find('.control-group .custom-nav').size() == 0){
			domHtml = $('<ul class="custom-nav clearfix">'+defaultHtml+'</ul>');
			domHtml.data({'navList':[]});
			dom.find('.control-group').prepend(domHtml);
		}else{
			domHtml = dom.find('.control-group .custom-nav');
		}
		var addContent = function(num,dom){
			var navList = domHtml.data('navList');
			if(num >= 0){
				randNumber = num;
				if(navList[num].name == ''){
					var liContent = '<li class="choice" data-id="'+randNumber+'"><div class="control-group"><label class="control-label"><em class="required">*</em>内容来源：</label><div class="controls"><div class="control-action clearfix"><div class="dropdown hover"><a class="js-dropdown-toggle dropdown-toggle" href="javascript:void(0);">设置链接到的页面地址 <i class="caret"></i></a></div></div></div></div><div class="actions"><span class="action add close-modal" title="添加">+</span><span class="action delete close-modal" title="删除">×</span></div></li>';
				}else{
					var liContent = '<li class="choice" data-id="'+randNumber+'">';
					if(navList[num].type == 'link'){
						liContent += '<div class="control-group"><label class="control-label"><em class="required">*</em>内容来源：</label><div class="controls"><div class="control-action clearfix"><div class="left js-link-to link-to"><span class="label label-success">自定义外链</span></div></div></div></div><div class="control-group"><label class="control-label"><em class="required">*</em>链接名称：</label><div class="controls"><input type="text" name="name" value="'+navList[num].name+'"/></div></div><div class="control-group"><label class="control-label"><em class="required">*</em>链接地址：</label><div class="controls"><input type="text" name="url" value="'+navList[num].url+'"/></div></div>';
					}else{
						if (navList[num].widget == 'goodcat') {
							liContent += '<div class="control-group"><label class="control-label"><em class="required">*</em>内容来源：</label><div class="controls"><div class="control-action clearfix"><div class="left js-link-to link-to"><a href="'+navList[num].url+'" target="_blank" class="new-window link-to-title"><span class="label label-success">'+navList[num].prefix+' <em class="link-to-title-text">'+navList[num].name+'</em></span></a></div><div class="dropdown hover right"><a class="dropdown-toggle" href="javascript:void(0);">修改 <i class="caret"></i></a></div></div></div></div>';
						} else {
						    console.log('navList_1831', navList);
                            liContent += '<div class="control-group"><label class="control-label"><em class="required">*</em>内容来源：</label><div class="controls"><div class="control-action clearfix"><div class="left js-link-to link-to"><a href="'+navList[num].url+'" target="_blank" class="new-window link-to-title"><span class="label label-success">'+navList[num].prefix+' <em class="link-to-title-text">'+navList[num].name+'</em></span></a></div><div class="dropdown hover right"><a class="dropdown-toggle" href="javascript:void(0);">修改 <i class="caret"></i></a></div></div></div></div>';
                            // liContent += '<div class="control-group"><label class="control-label"><em class="required">*</em>内容来源：</label><div class="controls"><div class="control-action clearfix"><div class="left js-link-to link-to"><a href="'+navList[num].url+'" target="_blank" class="new-window link-to-title"><span class="label label-success">'+navList[num].prefix+' <em class="link-to-title-text">'+navList[num].name+'</em></span></a></div><div class="dropdown hover right"><a class="dropdown-toggle" href="javascript:void(0);">修改 <i class="caret"></i></a></div></div></div></div> <div class="control-group"><label class="control-label">显示条数：</label><div class="controls"><select name="number"><option value="1" '+(navList[num].number=='1' ? 'selected="selected"' : '')+'>1条</option><option value="2" '+(navList[num].number=='2' ? 'selected="selected"' : '')+'>2条</option><option value="3" '+(navList[num].number=='3' ? 'selected="selected"' : '')+'>3条</option><option value="4" '+(navList[num].number=='4' ? 'selected="selected"' : '')+'>4条</option><option value="5" '+(navList[num].number=='5' ? 'selected="selected"' : '')+'>5条</option></select></div></div>';
						}
					}
					liContent += '<div class="actions"><span class="action add close-modal" title="添加">+</span><span class="action delete close-modal" title="删除">×</span></div></li>';
				}
				var liHtml  = $(liContent);
				liHtml.find("input[name='name']").val(navList[num].name);
				liHtml.find("input[name='url']").val(navList[num].url);

				if(navList[num].name != ''){
					liHtml.find('input[name="name"]').blur(function(){
						var navList = domHtml.data('navList');
						navList[liHtml.data('id')].name = $(this).val();
						domHtml.data('navList',navList);
						buildContent();
					});
					liHtml.find('input[name="url"]').blur(function(){
						$(this).val($.trim($(this).val()));
						var navList = domHtml.data('navList');
						navList[liHtml.data('id')].url = $(this).val();
						buildContent();
					});
					liHtml.find('select[name="number"]').change(function(){
						var navList = domHtml.data('navList');
						navList[liHtml.data('id')].number = parseInt($(this).val());
						domHtml.data('navList',navList);
						buildContent();
					});
				}
			}else{
				var randNumber = getRandNumber();
				navList[randNumber] = {'title':'','prefix':'','url':'','name':''};
				domHtml.data('navList',navList);
				var liHtml = $('<li class="choice" data-id="'+randNumber+'"><div class="control-group"><label class="control-label"><em class="required">*</em>内容来源：</label><div class="controls"><div class="control-action clearfix"><div class="dropdown hover"><a class="js-dropdown-toggle dropdown-toggle" href="javascript:void(0);">设置链接到的页面地址 <i class="caret"></i></a></div></div></div></div><div class="actions"><span class="action add close-modal" title="添加">+</span><span class="action delete close-modal" title="删除">×</span></div></li>');
			}
			var nowDom = liHtml.find('.dropdown');
			link_box(nowDom,[],function(type,prefix,title,href){
				nowDom.siblings('.js-link-to').remove();
				if(type =='link'){
					var beforeDom = $('<div class="left js-link-to link-to"><span class="label label-success">自定义外链</span></div>');
				}else{
					var beforeDom = $('<div class="left js-link-to link-to"><a href="'+href+'" target="_blank" class="new-window link-to-title"><span class="label label-success">'+prefix+' <em class="link-to-title-text">'+(typeof title=='object' ? title[1] : title)+'</em></span></a></div>');
				}
				var groupDom = nowDom.closest('.control-group');
				groupDom.siblings('.control-group').remove();

				nowDom.before(beforeDom);
				if(type =='link'){
					var nextDom1 = $('<div class="control-group"><label class="control-label"><em class="required">*</em>链接名称：</label><div class="controls"><input type="text" name="name" value="'+href+'"/></div></div>');
					var nextDom2 = $('<div class="control-group"><label class="control-label"><em class="required">*</em>链接地址：</label><div class="controls"><input type="text" name="url" value="'+href+'"/></div></div>');
					nextDom1.find('input').blur(function(){
						var navList = domHtml.data('navList');
						navList[liHtml.data('id')].name = $(this).val();
						domHtml.data('navList',navList);
						buildContent();
					});
					nextDom2.find('input').blur(function(){
						$(this).val($.trim($(this).val()));
						var navList = domHtml.data('navList');
						navList[liHtml.data('id')].url = $(this).val();
						buildContent();
					});
					groupDom.after(nextDom2);
					groupDom.after(nextDom1);

					nowDom.remove();
					var navList = domHtml.data('navList');
					navList[liHtml.data('id')] = {'type':'link','prefix':prefix,'url':href,'name':href};
					console.log('navList_1902', navList);
					domHtml.data('navList',navList);
				}else{
					var nextDom;
					if (type == 'goodcat') {
						nextDom = $('<div class="control-group"></div>');
					} else {
                        nextDom = $('<div class="control-group"></div>');
						// nextDom = $('<div class="control-group"><label class="control-label">显示条数：</label><div class="controls"><select name="number"><option value="1">1条</option><option value="2">2条</option><option value="3" selected="selected">3条</option><option value="4">4条</option><option value="5">5条</option></select></div></div>');
					}
					//var nextDom = $('<div class="control-group"><label class="control-label">显示条数：</label><div class="controls"><select name="number"><option value="1">1条</option><option value="2">2条</option><option value="3" selected="selected">3条</option><option value="4">4条</option><option value="5">5条</option></select></div></div>');
					nextDom.find('select').change(function(){
						var navList = domHtml.data('navList');
						navList[liHtml.data('id')].number = parseInt($(this).val());
						domHtml.data('navList',navList);
						buildContent();
					});
					groupDom.after(nextDom);
					nowDom.children('a').attr('class','dropdown-toggle').html('修改 <i class="caret"></i>');
					var navList = domHtml.data('navList');
					navList[liHtml.data('id')] = {'type':'widget','widget':type,'number':1,'prefix':prefix,'url':href,'id':title[0],'name':(typeof title=='object' ? title[1] : title)};
					domHtml.data('navList',navList);
				}
				buildContent();
			});
			liHtml.find('span.add').click(function(){
				addContent(-1,liHtml);
			});
			liHtml.find('span.delete').click(function(){
				var navList = domHtml.data('navList');
				delete navList[liHtml.data('id')];
				domHtml.data('navList',navList);
				$(this).closest('li.choice').remove();
				buildContent();
			});
			if(dom){
				dom.after(liHtml);
				var navList = domHtml.data('navList');
				var newNavList = [];
				$.each(rightHtml.find('.js-collection-region .ui-sortable > li'),function(i,item){
					newNavList[i] = navList[$(item).data('id')];
					$(item).data('id',i);
				});
				domHtml.data('navList',newNavList);
			}else{
				rightHtml.find('.js-collection-region .ui-sortable').append(liHtml);
			}
			buildContent();
		};
		var buildContent = function(){
			var navList = domHtml.data('navList');
			var html = '';
			for(var i in navList){
				if (navList[i].type == 'widget' && navList[i].widget == 'goodcat') {
					html += '<li><a class="clearfix" href="javascript:void(0);"><span class="custom-nav-title">' + navList[i].name + ' 的『关联链接』</span><i class="right right-arrow"></i></a></li>';
				} else if(navList[i].type == 'link'){
					html += '<li><a class="clearfix" href="javascript:void(0);"><span class="custom-nav-title">'+navList[i].name+'</span><i class="right right-arrow"></i></a></li>';
				} else {
                    html += '<li><a class="clearfix" href="javascript:void(0);"><span class="custom-nav-title">' + navList[i].name + ' 的『关联链接』</span><i class="right right-arrow"></i></a></li>';
                    // for(var j=1;j<=navList[i].number;j++){
					// 	html += '<li><a class="clearfix" href="javascript:void(0);"><span class="custom-nav-title">第'+j+'条 '+navList[i].name+' 的『关联链接』</span><i class="right right-arrow"></i></a></li>';
					// }
				}
			}
			domHtml.html(html);
		};
		var navList = domHtml.data('navList');
		for(var num in navList){
			addContent(num);
		}
		rightHtml.find('.js-add-option').click(function(){
			addContent(-1);
		});

		$('.js-sidebar-region').empty().html(rightHtml);
	};

	/**
	 * 图片<br/>广告
	 */
	clickArr['image_ad'] = function(){
		defaultHtml = '<div class="custom-image-swiper"><div class="swiper-container" style="height:80px"><div class="swiper-wrapper"><img style="max-height:80px;display:block;" src="'+staticpath+'images/image_ad_demo.jpg"/></div></div></div>';
		domHtml = dom.find('.control-group');
		if(domHtml.html() == '<div class="component-border"></div>'){
			domHtml.prepend(defaultHtml);
			domHtml.data({'navList':[],'type':'0','size':'0','max_height':0,'max_width':0});
		}
		rightHtml = $('<div><form class="form-horizontal"><div class="control-group"><label class="control-label">显示方式：</label><div class="controls"><label class="radio inline"><input type="radio" name="type" value="0"'+(domHtml.data('type')=='0' ? ' checked="checked"' : '')+'/>折叠轮播</label><label class="radio inline"><input type="radio" name="type" value="1"'+(domHtml.data('type')=='1' ? ' checked="checked"' : '')+'/>分开显示</label></div></div><div class="control-group"><label class="control-label">显示大小：</label><div class="controls"><label class="radio inline"><input type="radio" name="size" value="0" '+(domHtml.data('size')=='0' ? ' checked="checked"' : '')+'/>大图</label><label class="radio inline size_1_label" '+(domHtml.data('type')=='0' ? 'style="display:none;"' : '')+'><input type="radio" name="size" value="1"  '+(domHtml.data('size')=='1' ? ' checked="checked"' : '')+'/>小图</label></div></div><div class="alert alert-danger" role="alert">请上传大图（640&times320）小图（320&times320）像素图片</div><div class="control-group js-choices-region"><ul class="choices ui-sortable"></ul></div><div class="control-group options"><a href="javascript:void(0);" class="add-option js-add-option"><i class="icon-add"></i> 添加一个广告</a></div></form></div>');
		rightHtml.find('input[name="type"]').change(function(){
			domHtml.data('type',$(this).val());
			if($(this).val() == '1'){
				rightHtml.find('.size_1_label').show();
			}else{
				domHtml.data('size','0');
				rightHtml.find('input[name="size"][value="0"]').prop('checked',true);
				rightHtml.find('.size_1_label').hide();
			}
			buildContent();
		});
		rightHtml.find('input[name="size"]').change(function(){
			domHtml.data('size',$(this).val());
			buildContent();
		});
		var rightUl = rightHtml.find('.js-choices-region .ui-sortable');
		var addContent = function(num,dom){
			if(num >= 0){
				var navList = domHtml.data('navList');
				var liContent = '<li class="choice" data-id="'+num+'">';
				liContent += '<div class="choice-image">';
				if(navList[num].image){
					liContent += '<img src="'+navList[num].image+'" width="118" height="118" class="thumb-image"/><a class="modify-image js-trigger-image" href="javascript: void(0);">重新上传</a>';
				}else{
					liContent += '<a class="add-image js-trigger-image" href="javascript:void(0);"><i class="icon-add"></i>  添加图片</a>';
				}
				liContent += '</div>';
				liContent += '<div class="choice-content"><div class="control-group"><label class="control-label">文字：</label><div class="controls"><input class="" type="text" name="title" value="'+(navList[num].title!='' ? navList[num].title : '')+'" maxlength="20"/></div></div><div class="control-group"><label class="control-label">链接：</label><div class="control-action clearfix">';
				if(navList[num].name != ''){
					liContent += '<div class="left js-link-to link-to"><a href="'+navList[num].url+'" target="_blank" class="new-window link-to-title"><span class="label label-success">'+navList[num].prefix+' <em class="link-to-title-text">'+navList[num].name+'</em></span></a><a href="javascript:;" class="js-delete-link link-to-title close-modal" title="删除">×</a></div><div class="dropdown hover right"><a class="dropdown-toggle" href="javascript:void(0);">修改 <i class="caret"></i></a></div>';
				}else{
					liContent += '<div class="dropdown hover"><a class="js-dropdown-toggle dropdown-toggle" href="javascript:void(0);">设置链接到的页面地址 <i class="caret"></i></a></div>';
				}
				liContent += '</div></div></div><div class="actions"><span class="action add close-modal" title="添加">+</span><span class="action delete close-modal" title="删除">×</span></div></li>';
				var liHtml = $(liContent);
				liHtml.find("input[name='title']").val(navList[num].title);
				if(navList[num].name != ''){
					liHtml.find('.js-delete-link').click(function(){
						var fDom = $(this).closest('.control-action');
						fDom.find('.js-link-to').remove();
						fDom.find('.dropdown').removeClass('right').children('a').attr('class','js-dropdown-toggle dropdown-toggle').html('设置链接到的页面地址 <i class="caret">');
						var navList = domHtml.data('navList');

						navList[liHtml.data('id')] = {'title':titleDom.val(),'prefix':'','url':'','name':'','image':navList[liHtml.data('id')].image};
						domHtml.data('navList',navList);
					});
				}
			}else{
				var randNumber = getRandNumber();
				var navList = domHtml.data('navList');
				navList[randNumber] = {'title':'','prefix':'','url':'','name':'','image':''};
				domHtml.data('navList',navList);
				var liHtml = $('<li class="choice" data-id="'+randNumber+'"><div class="choice-image"><a class="add-image js-trigger-image" href="javascript: void(0);"><i class="icon-add"></i>  添加图片</a></div><div class="choice-content"><div class="control-group"><label class="control-label">文字：</label><div class="controls"><input type="text" name="title" value="" maxlength="20"/></div></div><div class="control-group"><label class="control-label">链接：</label><div class="control-action clearfix"><div class="dropdown hover"><a class="js-dropdown-toggle dropdown-toggle" href="javascript:void(0);">设置链接到的页面地址 <i class="caret"></i></a></div></div></div></div><div class="actions"><span class="action add close-modal" title="添加">+</span><span class="action delete close-modal" title="删除">×</span></div></li>');
			}
			var titleDom = liHtml.find('input[name="title"]');
			var nowDom = liHtml.find('.dropdown');
			titleDom.blur(function(){
				var navList = domHtml.data('navList');
				navList[liHtml.data('id')].title = titleDom.val();
				domHtml.data('navList',navList);
				buildContent();
			});
			liHtml.find('.js-trigger-image').click(function(){
				var imageDom = $(this);
				upload_pic_box(1,true,function(pic_list){
					if(pic_list.length > 0){
						for(var i in pic_list){
							var image = new Image();
							image.src = pic_list[i];
							image.onload=function(){
								imageDom.siblings('.thumb-image').remove();
								imageDom.removeClass('add-image').addClass('modify-image').html('重新上传').before('<img src="'+pic_list[i]+'" width="118" height="118" class="thumb-image"/>');
								var navList = domHtml.data('navList');
								if(image.height > domHtml.data('max_height')){
									domHtml.data('max_height',image.height);
								}
								if(image.width > domHtml.data('max_width')){
									domHtml.data('max_width',image.width);
								}
								navList[liHtml.data('id')].image = pic_list[i];
								domHtml.data('navList',navList);
								buildContent();
							}
						}
					}
				},1);
			});
			link_box(nowDom,[],function(type,prefix,title,href){
				nowDom.siblings('.js-link-to').remove();
				var beforeDom = $('<div class="left js-link-to link-to"><a href="'+href+'" target="_blank" class="new-window link-to-title"><span class="label label-success">'+prefix+' <em class="link-to-title-text">'+title+'</em></span></a><a href="javascript:;" class="js-delete-link link-to-title close-modal" title="删除">×</a></div>');

				var navList = domHtml.data('navList');
				var liHtmlId = liHtml.data('id');
				navList[liHtmlId].prefix = prefix;
				navList[liHtmlId].url = href;
				navList[liHtmlId].name = title;

				beforeDom.find('.js-delete-link').click(function(){
					var fDom = $(this).closest('.control-action');
					fDom.find('.js-link-to').remove();
					fDom.find('.dropdown').removeClass('right').children('a').attr('class','js-dropdown-toggle dropdown-toggle').html('设置链接到的页面地址 <i class="caret">');
					var navList = domHtml.data('navList');
					navList[liHtmlId].prefix = '';
					navList[liHtmlId].url = '';
					navList[liHtmlId].name = '';
					domHtml.data('navList',navList);
				});

				domHtml.data('navList',navList);
				buildContent();
				nowDom.before(beforeDom);
				nowDom.addClass('right').children('a').attr('class','dropdown-toggle').html('修改 <i class="caret"></i>');
			});
			liHtml.find('span.add').click(function(){
				addContent(-1,liHtml);
			});
			liHtml.find('span.delete').click(function(){
				var navList = domHtml.data('navList');
				delete navList[liHtml.data('id')];
				domHtml.data('navList',navList);
				$(this).closest('li.choice').remove();
				buildContent();
				
				var image1 = new Image();
				domHtml.data('max_height', 0);
				domHtml.data('max_width', 0);
				for(var i in navList) {
					image1.src = navList[i].image;
					image1.onload = function() {
						if(image1.height > domHtml.data('max_height')){
							domHtml.data('max_height', image1.height);
						}
						if(image1.width > domHtml.data('max_width')){
							domHtml.data('max_width', image1.width);
						}
					}
				}
			});
			if(dom){
				dom.after(liHtml);
				var navList = domHtml.data('navList');
				var newNavList = [];
				$.each(rightHtml.find('.js-choices-region .ui-sortable > li'),function(i,item){
					newNavList[i] = navList[$(item).data('id')];
					$(item).data('id',i);
				});
				domHtml.data('navList',newNavList);
			}else{
				rightUl.append(liHtml);
			}
		};
		var buildContent = function(){
			var navList = domHtml.data('navList');
			if(getObjLength(navList) == 0){
				domHtml.find('.component-border').siblings('div').remove();
				domHtml.html(defaultHtml);
			}else{
				var html = '';
				if(domHtml.data('type') == '0'){
					html+= '<div class="custom-image-swiper"><div class="swiper-container"><div class="swiper-wrapper">';
					var j = 0;
					for(var i in navList){
						if (j == 0) {
						html += '<div class="swiper-slide"><a href="javascript:void(0);">'+(navList[i].title!='' ? '<h3 class="title">'+navList[i].title+'</h3>' : '')+'<img src="'+navList[i].image+'"></a></div>';
						}
						j++;
					}
					html+= '</div></div></div>';
					if(getObjLength(navList) > 1){
						html+= '<div class="swiper-pagination">';
						var num=0;
						for(var i in navList){
							html += '<span class="swiper-pagination-switch'+(num==0 ? ' swiper-active-switch' :'')+'"></span>';
							num++;
						}
						html+= '</div>';
					}
				}else{
					html+= '<ul class="custom-image clearfix">';
					for(var i in navList){
						html+= '<li'+(domHtml.data('size')=='1' ? ' class="custom-image-small"' : '')+'>'+(navList[i].title!='' ? '<h3 class="title">'+navList[i].title+'</h3>' : '')+'<img src="'+navList[i].image+'"/></li>';
					}
					html+= '</ul>';
				}
				domHtml.html(html);
			}
		};
		var navList = domHtml.data('navList');
		for(var num in navList){
			addContent(num);
		}
		rightHtml.find('.js-add-option').click(function(){
			addContent(-1);
		});
		$('.js-sidebar-region').empty().html(rightHtml);
	};

	clickArr['article'] = function(){
		var obj = dom.find('.activity').data('article_data');
		var defaultHtml = '<div class="customShop">\
								<div class="title">\
								<span class="js-article_name">店铺动态</span><span><a href="javascript:;">查看更多</a><i></i></span>\
						</div>\
						<ul>\
								<li>\
								<div class="shopInfo clearfix">\
									<div class="shopImg">\
										<a href="#"><img src="'+staticpath+'images/default_shop.png" width="84" height="84"></a>\
									</div>\
									<div class="shopTxt">\
										<h2>店铺名称xx</h2>\
										<p>5天前</p>\
									</div>\
									<button>\
									<i class="active"></i><span>2<span></span></span></button>\
								</div>\
								<ul class="shopList">\
									<li class="">\
										<p>动态内容xx</p>\
										<ul class="clearfix ">\
											<li><img src="'+staticpath+'images/default_shop.png" width="110" height="110"></li>\
											<li><img src="'+staticpath+'images/default_shop.png" width="110" height="110"></li>\
											<li><img src="'+staticpath+'images/default_shop.png" width="110" height="110"></li>\
										</ul>\
									</li>\
								</ul>\
							</li>\
							</ul>\
						<ul class="shopSpot"><span class="swiper-pagination-bullet"></span><span class="swiper-pagination-bullet swiper-pagination-bullet-active"></span><span class="swiper-pagination-bullet e"></span></ul>\
					</div>';
		var domHtml;
		if(typeof obj == "undefined") {
			domHtml = $('<div class="activity "></div>');
			domHtml.html(defaultHtml).data('article_data', {'name': '店铺动态', 'activity_arr': []});
			dom.find('.control-group').prepend(domHtml);
			
			obj = {'name': '店铺动态', 'activity_arr': []};
		} else {
			//找到activity赋值给domHtml
			domHtml = dom.find('.activity');
			obj = domHtml.data('article_data');
			
			//添加defaultHtml
			domHtml.html(defaultHtml);
		}
		
		var activity_data = domHtml.data("article_data");
		var name = (obj != undefined && obj.name != undefined) ? obj.name : '';
		
		if (name.length > 0) {
			domHtml.find(".js-article_name").html(name);
		}
		
		//赋值，右侧默认html
		rightHtmls  = '<div>';
		rightHtmls += '		<div class="form-horizontal">';
		rightHtmls += '			<div class="js-meta-region" style="margin-bottom:20px;">';
		rightHtmls += '				<div class="control-group">';
		rightHtmls += '					<label class="control-label">动态别称：</label>';
		rightHtmls += '					<div class="controls"><input type="text" name="name" value="' + name + '" maxlength="80"/></div>';
		rightHtmls += '				</div>';
		rightHtmls += '				<div class="control-group">';
		rightHtmls += '					<label class="control-label">选择动态：</label>';
		rightHtmls += '					<div class="controls">';
		rightHtmls += '						<ul class="module-goods-list clearfix ui-sortable coupon-list js-coupon-list"name="goods">';
		
		//右侧加载 已保存的产品
		if (activity_data.activity_arr != undefined) {
			//第二次
			var j = 0;
			for(var i in activity_data.activity_arr) {
				rightHtmls += '<li class="sort"><a href="javascript:"><div class="coupon-money">' + activity_data.activity_arr[i]['title'].toString().substr(0,4)+ '</div></a><a class="close-modal js-delete-article small hide" data-id="0" title="删除">×</a></li>';
			}
		}
		rightHtmls += '							<li><a href="javascript:void(0);"class="js-add-articles add-goods"><i class="icon-add"></i></a></li>';
		rightHtmls += '						</ul>';
		rightHtmls += '					</div>';
		rightHtmls += '				</div>';
		rightHtmls += '			</div>';
		rightHtmls += '		</div>';
		rightHtmls += '</div>';

		rightHtml = $(rightHtmls);
		//添加右侧代码
		$('.js-sidebar-region').empty().html(rightHtml);
		
		
		//操作右侧 添加/改变数据
		rightHtml.find('input[name="name"]').blur(function(){
			var article_data = dom.find('.activity').data('article_data');
			var article_name = $(this).val();
			article_data.name = article_name;
			
			//存值
			domHtml.data('article_data', article_data);
			if (article_name.length > 0) {
				domHtml.find(".js-article_name").html(article_name.toString().substr(0, 20));
			} else {
				domHtml.find(".js-article_name").html("店铺动态");
			}
		});

		rightHtml.find(".js-delete-article").click(function (event) {
			var index = $(this).closest("ul").find("li").index($(this).closest("li"));
			var activity_data = domHtml.data('article_data');
			
			try {
				//删除coupon_data.coupon_arr数组的coupon_id
				var activity_arr_tmp = [];
				var j = 0;
				for(var i in activity_data.activity_arr) {
					if (i != index) {
						activity_arr_tmp[j] = activity_data.activity_arr[i];
						j++;
					}
				}
				
				//重新存进去
				activity_data.activity_arr = activity_arr_tmp;
				domHtml.data('article_data', activity_data);
				// 最近的<li>移除
				$(this).closest("li").remove();
			} catch(e) {
				console.log("删除失败");
			}
		});
		
		//右侧动态添加活动
		widget_link_hd(rightHtml.find('.js-add-articles'), 'article_module', function(result) {
			//domHtml.data值取出来
			var article_data = domHtml.data('article_data');
			//合并或赋值
			if(article_data){
				if (article_data.activity_arr != undefined) {
					$.merge(article_data.activity_arr, result);
				} else {
					$.merge(article_data, result);
				}
			} else {
				article_data = result;
			}
			
			var article_count = 0;
			if('activity_arr' in article_data){
				$.each(article_data.activity_arr,function(ii,vv){
					if(vv!=undefined){
						article_count = article_count+1;
					}
				});
			}else{
				article_count = article_data.length;
			}
			if(article_count>6){
				layer_tips(1,'最多只能添加6条动态');
				return;
			}
			//重新存回去domHtml.data
			domHtml.data('article_data', article_data);
			/*选取确认后，左侧和右侧的html*/
			var activity_list_html = '';
			if (article_data.activity_arr != undefined) {
				for(var i in article_data.activity_arr) {
					activity_list_html += '<li class="sort" data-activity-id="' + i + '"><a href="javascript:"><div class="coupon-money">' + article_data.activity_arr[i]['title'].toString().substr(0,4) + '</div></a><a class="close-modal js-delete-article small hide" data-id="0" title="删除">×</a></li>';
				}
			}else {
				for(var i in article_data){
					activity_list_html += '<li class="sort" data-activity-id="' + i + '"><a href="javascript:"><div class="coupon-money">' + article_data[i]['title'].toString().substr(0,4)+ '</div></a><a class="close-modal js-delete-article small hide" data-id="0" title="删除">×</a></li>';
				}
			}
			
			//移除
			rightHtml.find('.sort').remove();
			//倒数第一个<li>前加上coupon_list_html代码
			rightHtml.find("li").eq(-1).before(activity_list_html);

			/*删除对应的左侧和右侧html，和对应存在的activity_data*/

			//右侧点击x删除，选取确认后
			rightHtml.find(".js-delete-article").click(function (event) {
				var index = $(this).closest("ul").find("li").index($(this).closest("li"));
				var activity_data = domHtml.data('article_data');
				
				try {
					var activity_arr_tmp = [];
					var j = 0;
					for(var i in activity_data.activity_arr) {
						if (i != index) {
							activity_arr_tmp[j] = activity_data.activity_arr[i];
							j++;
						}
					}
					
					//重新存进去
					activity_data.activity_arr = activity_arr_tmp;
					domHtml.data('article_data', activity_data);
					// 最近的<li>移除
					$(this).closest("li").remove();
				} catch(e) {
					console.log("删除失败");
				}
			});
		},domHtml.data('article_data'));
	};
	
	/**
	 * 魔方
	 */
	clickArr['cube'] = function() {
		defaultHtml = '<table>';
		defaultHtml += '	<tbody>';
		defaultHtml += '		<tr>';
		defaultHtml += '			<td class="empty" data-x="0" data-y="0"></td>';
		defaultHtml += '			<td class="empty" data-x="1" data-y="0"></td>';
		defaultHtml += '			<td class="empty" data-x="2" data-y="0"></td>';
		defaultHtml += '			<td class="empty" data-x="3" data-y="0"></td>';
		defaultHtml += '		</tr>';
		defaultHtml += '		<tr>';
		defaultHtml += '			<td class="empty" data-x="0" data-y="1"></td>';
		defaultHtml += '			<td class="empty" data-x="1" data-y="1"></td>';
		defaultHtml += '			<td class="empty" data-x="2" data-y="1"></td>';
		defaultHtml += '			<td class="empty" data-x="3" data-y="1"></td>';
		defaultHtml += '		</tr>';
		defaultHtml += '		<tr>';
		defaultHtml += '			<td class="empty" data-x="0" data-y="2"></td>';
		defaultHtml += '			<td class="empty" data-x="1" data-y="2"></td>';
		defaultHtml += '			<td class="empty" data-x="2" data-y="2"></td>';
		defaultHtml += '			<td class="empty" data-x="3" data-y="2"></td>';
		defaultHtml += '		</tr>';
		defaultHtml += '		<tr>';
		defaultHtml += '			<td class="empty" data-x="0" data-y="3"></td>';
		defaultHtml += '			<td class="empty" data-x="1" data-y="3"></td>';
		defaultHtml += '			<td class="empty" data-x="2" data-y="3"></td>';
		defaultHtml += '			<td class="empty" data-x="3" data-y="3"></td>';
		defaultHtml += '		</tr>';
		defaultHtml += '	</tbody>';
		defaultHtml += '</table>';

		var dom_cube_data = [];
		var dom_cube_selected = [];
		//魔方模块索引
		domHtml = dom.find('.control-group');
		if (dom.find('.custom-cube2-table').size() == 0) {
			domHtml.addClass('custom-cube2-table').prepend(defaultHtml);
		} else {
			//保存的数据
			dom_cube_data = domHtml.data('content') || [];
			dom_cube_selected = domHtml.data('selected') || [];
			if (!$.isArray(dom_cube_selected)) {
				dom_cube_selected = dom_cube_selected.split(',');
			}

			var table = '<table>';
			table += cube_create(dom_cube_data, 79, 79, 'left');
			table += '</table>';
			domHtml.html(table);
		}
		
		if (dom_cube_data == undefined || dom_cube_data == '') {
			rightHtml = '<div><form class="form-horizontal custom-cube2-table cube2-edit" novalidate="">';
			rightHtml += '	<div class="control-group layout-map">';
			rightHtml += '		<label class="control-label">布局：</label>';
			rightHtml += '		<div class="controls" name="layout_map">';
			rightHtml += '			<table>';
			rightHtml += '				<tbody>';
			rightHtml += '					<tr>';
			rightHtml += '						<td class="empty" data-x="0" data-y="0"></td>';
			rightHtml += '						<td class="empty" data-x="1" data-y="0"></td>';
			rightHtml += '						<td class="empty" data-x="2" data-y="0"></td>';
			rightHtml += '						<td class="empty" data-x="3" data-y="0"></td>';
			rightHtml += '					</tr>';
			rightHtml += '					<tr>';
			rightHtml += '						<td class="empty" data-x="0" data-y="1"></td>';
			rightHtml += '						<td class="empty" data-x="1" data-y="1"></td>';
			rightHtml += '						<td class="empty" data-x="2" data-y="1"></td>';
			rightHtml += '						<td class="empty" data-x="3" data-y="1"></td>';
			rightHtml += '					</tr>';
			rightHtml += '					<tr>';
			rightHtml += '						<td class="empty" data-x="0" data-y="2"></td>';
			rightHtml += '						<td class="empty" data-x="1" data-y="2"></td>';
			rightHtml += '						<td class="empty" data-x="2" data-y="2"></td>';
			rightHtml += '						<td class="empty" data-x="3" data-y="2"></td>';
			rightHtml += '					</tr>';
			rightHtml += '					<tr>';
			rightHtml += '						<td class="empty" data-x="0" data-y="3"></td>';
			rightHtml += '						<td class="empty" data-x="1" data-y="3"></td>';
			rightHtml += '						<td class="empty" data-x="2" data-y="3"></td>';
			rightHtml += '						<td class="empty" data-x="3" data-y="3"></td>';
			rightHtml += '					</tr>';
			rightHtml += '				</tbody>';
			rightHtml += '			</table>';
			rightHtml += '			<p class="help-desc">点击 + 号添加内容</p>';
			rightHtml += '		</div>';
			rightHtml += '	</div>';
			rightHtml += '	<div class="control-group js-item-region">';
			rightHtml += '		<ul class="choices"></ul>';
			rightHtml += '	</div>';
			rightHtml += '</form></div>';
		} else {
			rightHtml = '<div><form class="form-horizontal custom-cube2-table cube2-edit" novalidate="">';
			rightHtml += '	<div class="control-group layout-map">';
			rightHtml += '		<label class="control-label">布局：</label>';
			rightHtml += '		<div class="controls" name="layout_map">';
			rightHtml += '			<table>';
			var _table = cube_create(dom_cube_data);
			rightHtml += _table;
			rightHtml += '			</table>';
			var empty_num = $(rightHtml).find('.empty').length;
			if (empty_num > 0) {
				rightHtml += '		<p class="help-desc">点击 + 号添加内容</p>';
			} else {
				rightHtml += '		<p class="help-desc hide">点击 + 号添加内容</p>';
			}
			rightHtml += '		</div>';
			rightHtml += '	</div>';
			rightHtml += '	<div class="control-group js-item-region">';
			rightHtml += '		<ul class="choices">';
			for (var i in dom_cube_data) {
				var _colspan = parseInt(dom_cube_data[i].colspan);
				var _rowspan = parseInt(dom_cube_data[i].rowspan);
				var _empty_cols = 0;
				$(_table).eq(dom_cube_data[i].y).find('td').eq(dom_cube_data[i].x).nextAll('.empty').each(function(i) {
					if ($(this).hasClass('empty')) {
						_empty_cols++;
					} else {
						return false;
					}
				});
				_colspan += parseInt(_empty_cols);

				var _empty_rows = 0;
				/*$(_table).eq(dom_cube_data[i].y).nextAll('tr').each(function(i) {
					var flag = true;
					$(this).children('td').each(function(j) {
						if ($(this).hasClass('empty') && $(this).data('x') == x) {
							_empty_rows++;
						} else {
							flag = false;
						}
					});
					if (flag) {
						return false;
					}
				});*/
				_empty_rows = 4 - dom_cube_data[i].rowspan;
				_rowspan += parseInt(_empty_rows);

				rightHtml += '		<li class="choice" style="display: none;">';
				rightHtml += '			<div class="control-group">';
				rightHtml += '				<label class="control-label"><em class="required">*</em>选择图片：</label>';
				rightHtml += '				<div class="controls" name="image_url">';
				var label_text = '选择图片';
				if (dom_cube_data[i].image != '' && dom_cube_data[i].image != undefined) {
					rightHtml += '<img src="' + dom_cube_data[i].image + '" width="100" height="100" class="thumb-image" />';
					label_text = '修改';			
				}
				rightHtml += '					<a class="control-action js-trigger-image" href="javascript: void(0);">' + label_text + '</a>';
				rightHtml += '					<p class="help-desc">建议尺寸：' + dom_cube_data[i].width + ' x ' + dom_cube_data[i].height + ' 像素</p>';
				rightHtml += '				</div>';
				rightHtml += '			</div>';
				rightHtml += '			<div class="control-group">';
				rightHtml += '				<label class="control-label">链接到：</label>';
				rightHtml += '				<div class="controls">';
				var label_text = '设置链接到的页面地址';
				if (dom_cube_data[i].title != '' && dom_cube_data[i].title != undefined) {
					rightHtml += '				<div class="left js-link-to link-to">';
					rightHtml += '					<a href="' + dom_cube_data[i].url + '" target="_blank" class="new-window link-to-title">';
					rightHtml += '						<span class="label label-success">';
					rightHtml += '							' + dom_cube_data[i].prefix + ' <em class="link-to-title-text">' + dom_cube_data[i].title + '</em>';
					rightHtml += '						</span>';
					rightHtml += '					</a>';
					rightHtml += '					<a href="javascript:;" class="js-delete-link link-to-title close-modal" title="删除">×</a>';
					rightHtml += '				</div>';
					label_text = '修改';	
				}
				rightHtml += '					<div class="dropdown hover">';
				rightHtml += '						<a class="js-dropdown-toggle dropdown-toggle control-action" href="javascript:void(0);">';
				rightHtml += '							' + label_text + ' <i class="caret"></i>';
				rightHtml += '						</a>';
				rightHtml += '					</div>';
				rightHtml += '				</div>';
				rightHtml += '			</div>';
				rightHtml += '			<div class="control-group">';
				rightHtml += '				<label class="control-label">图片占：</label>';
				rightHtml += '				<div class="controls">';
				rightHtml += '					<div class="btn-group">';
				rightHtml += '						<a class="btn dropdown-toggle" data-toggle="dropdown" href="javascript:;" style="width: 86px;">';
				rightHtml += 							dom_cube_data[i].rowspan + '行 ' + dom_cube_data[i].colspan + '列<span class="caret"></span>';
				rightHtml += '						</a>';
				rightHtml += '						<ul class="dropdown-menu" style="top: 89%;left:107px;">';
				for (var _r = 0; _r < _rowspan; _r++) {
					for (var _c = 0; _c < _colspan; _c++) {
						rightHtml += '					<li><a class="js-image-layout" href="javascript:;" data-width="' + (_c + 1) + '" data-height="' + (_r + 1) + '">' + (_r + 1) + '行 ' + (_c + 1) + '列</a></li>';
					}
				}
				rightHtml += '						</ul>';
				rightHtml += '					</div>';
				rightHtml += '				</div>';
				rightHtml += '			</div>';
				rightHtml += '			<div class="actions">';
				rightHtml += '				<span class="action delete close-modal" title="删除">×</span>';
				rightHtml += '			</div>';
				rightHtml += '		</li>';
			}
			rightHtml += '		</ul>';
			rightHtml += '	</div>';
			rightHtml += '</form></div>';
		}
		rightHtml = $(rightHtml);

		var num = dom_cube_data.length || 0;
		var cube_unit_width = 160; //单个方格宽度
		var cube_unit_height = 160; //单个方格高度
		var selected_unit_text = cube_unit_width + 'x' + cube_unit_height; //选中单元文本
		var cube_selected = dom_cube_selected; //已选择的布局
		//var cube_real_selected = dom_cube_real_selected;
		var selected_index = 0; //选中项索引
		var cube_save_data = dom_cube_data; //魔方数据
		rightHtml.on('click', 'td', function() {
			var selected = false; //是否已选择布局
			var cols = 3; //最大列索引
			var rows = 3; //最大行索引
			var x = $(this).data('x'); //所在列
			var y = $(this).data('y'); //所在行
			if ($(this).data('index') != undefined) {
				var _data = get_cube_data($(this).data('index'));
				x = _data.x;
				y = _data.y;
			}
			var content = true;
			if ($(this).hasClass('current')) {
				return false;
			} if ($(this).hasClass('not-empty')) {
				$('.cube2-edit').find('td').removeClass('current');
				$(this).addClass('current');
				selected = true;
			} else if (x == cols && y == rows) {
				$('.cube2-edit').find('td').removeClass('current');
				$(this).addClass('current');
				if (!$(this).hasClass('not-empty')) {
					content = false;
					selected_unit_text = cube_unit_width + 'x' + cube_unit_height;
					$(this).html('<img src=""><span>' + selected_unit_text + '</span>').addClass('not-empty cols-1 rows-1 index-' + num).removeClass('empty').attr({'colspan': 1, 'rowspan': 1, 'data-index': num}).removeAttr('data-x data-y');

					//保存数据	
					cube_data({'x': x, 'y': y, 'colspan': 1, 'rowspan': 1});

					//同步展示区域内容
					sync_default_html();
				}
				selected = true;
			} else {
				if (($(this).next('td').hasClass('not-empty') && $(this).closest('tr').next('tr').find('td').eq(x).hasClass('not-empty')) || (y == 3 && $(this).next('td').hasClass('not-empty')) || (x == 3 && $(this).closest('tr').next('tr').find('td').eq(x).hasClass('not-empty'))) {
					$('.cube2-edit').find('td').removeClass('current');
					$(this).addClass('current');
					if (!$(this).hasClass('not-empty')) {
						content = false;
						selected_unit_text = cube_unit_width + 'x' + cube_unit_height;
						$(this).html('<img src=""><span>' + selected_unit_text + '</span>').addClass('not-empty cols-1 rows-1 index-' + num + '').removeClass('empty').attr({'colspan': 1, 'rowspan': 1, 'data-index': num}).removeAttr('data-x data-y');

						//保存数据
						cube_data({'x': x, 'y': y, 'colspan': 1, 'rowspan': 1});

						//同步展示区域内容
						sync_default_html();
					}
					selected = true;
				}
			}

			//当前选中项索引
			selected_index = cube_selected.indexOf(x + ':' + y);

			//布局选择
			if (!selected) {
				cube_layer(this, x, y, cube_selected);
			} else {
				//魔方内容
				cube_content(this, x, y, content, cube_unit_width, cube_unit_height);
			}
		});

		//弹出层
		var cube_layer = function (obj, x, y, cube_selected) {
			var cube_real_selected = [];
			var _cube_save_data = get_cube_data();
			if (_cube_save_data != '' && _cube_save_data != undefined) {
				for (var i in _cube_save_data) {
					for (var _r = 0; _r < _cube_save_data[i].rowspan; _r++) {
						for (var _c = 0; _c < _cube_save_data[i].colspan; _c++) {
							cube_real_selected.push((parseInt(_cube_save_data[i].x) + _c) + ':' + (parseInt(_cube_save_data[i].y) + _r));
						}
					}
				}
			}
			var cols_next_empty = $(obj).nextAll('.empty').length + 1 || 0;
			var rows_next_empty = 1;
			var rows_next = $(obj).closest('tr').nextAll('tr').length || 0;
			if (rows_next > 0) {
				$(obj).closest('tr').nextAll('tr').each(function(i) {
					$(this).children('td').each(function(j) {
						if ($(this).data('x') == x && $(this).hasClass('empty')) {
							rows_next_empty++;
						}
					});
				});
			}

			var top = ($(window).scrollTop() + $(window).height() * 0.3);
			var html = '<div class="modal fade in" style="display: block; top:' + top + 'px">';
				html += '	<div class="modal-header">';
				html += '		<a class="close" data-dismiss="modal">×</a>';
				html += '		<ul class="module-nav modal-tab">';
				html += '			<li class="active">';
				html += '				<h4>选择布局 - <span style="font-size: 12px;font-weight: normal;color:orange;">选择后请点击选中区域确认。</span></h4>';
				html += '			</li>';
				html += '		</ul>';
				html += '	</div>';
				html += '	<div class="modal-body clearfix layout-table">';
				var tmp_cube_real_selected = [];
				for (var c = x; c < 4; c++) {
					html += '	<ul class="layout-cols layout-cols-' + (c + 1) + '">';
					for (var r = y; r < 4; r++) {
						var key = c + ':' + r;
						if ($.inArray(key, $.merge(tmp_cube_real_selected, cube_real_selected)) == -1) {
							html += '	<li data-cols="' + (c + 1) + '" data-rows="' + (r + 1) + '"></li>';
							tmp_cube_real_selected.push();
						} else {
							var _index = cube_real_selected.indexOf(key);
							var data = get_cube_data(_index - 1);
							var _colspan = data.colspan;
							var _rowspan = data.rowspan;
							_rowspan = 4;
							for (var r2 = r; r2 < 4; r2++) {
								for (var c2 = c; c2 < 4; c2++) {
									tmp_cube_real_selected.push(c2 + ':' + r2);
								}
							}
						}
					}
					html += '	</ul>';
				}
				
				html += '	</div>';
				html += '</div>';
			html = $(html);

			if ($('body').find('.modal-backdrop').length == 0) {
				$('body').append('<div class="modal-backdrop false in"></div>');
			}
			$('body').append(html);

			//选择布局
			html.children('.modal-body').find('li').hover(function(e){ //鼠标经过
				var _index = $(this).closest('ul').children('li').index($(this));
				var selected_rows = $(this).data('rows');
				selected_rows = parseInt(selected_rows);
				if (y > 0) {
					selected_rows--
				}
				$(this).addClass('selected');
				$(this).prevAll('li').addClass('selected');
				$(this).closest('ul').prevAll('ul').find('li:lt(' + (_index + 1) + ')').addClass('selected');
			}, function(e) { //鼠标离开
				var selected_rows = $(this).data('rows');
				selected_rows = parseInt(selected_rows);
				if (y > 0) {
					selected_rows--
				}
				$(this).removeClass('selected');
				$(this).prevAll('li').removeClass('selected');
				$(this).closest('ul').prevAll('ul').find('li:lt(' + selected_rows + ')').removeClass('selected');
			});

			/**
			 * 数组去重
			 * @param  {Boolean} isStrict [description]
			 * @return {[type]}           [description]
			 */
			/*Array.prototype.toUnique = function (isStrict) {
				if (this.length < 2)
					return [this[0]] || [];
				var tempObj = {}, newArr = [];
				for (var i = 0; i < this.length; i++) {
					var v = this[i];
					var condition = isStrict ? (typeof tempObj[v] != typeof v) : false;
					if ((typeof tempObj[v] != "undefined") || condition) {
						tempObj[v] = v;
						newArr.push(v);
					}
				}
				return newArr;
			}*/
			var array2Unique = function (arr, isStrict) {
				if (arr.length < 2)
					return [arr[0]] || [];
				var tempObj = {}, newArr = [];
				for (var i = 0; i < arr.length; i++) {
					var v = arr[i];
					var condition = isStrict ? (typeof tempObj[v] != typeof v) : false;
					if ((typeof tempObj[v] != "undefined") || condition) {
						tempObj[v] = v;
						newArr.push(v);
					}
				}
				return newArr;
			}

			//确认选择
			html.children('.modal-body').find('li').click(function(e){
				var cols = 1; //选中列数
				var rows = 1; //选中行数
				var width = cols * cube_unit_width;
				var height = rows * cube_unit_height;

				if (html.children('.modal-body').find('.selected').length == 1) { //单选
					selected_unit_text = cube_unit_width + 'x' + cube_unit_height
					$('.cube2-edit').find('td').removeClass('current');
					$(obj).addClass('current');
					$(obj).html('<img src=""><span>' + selected_unit_text + '</span>').addClass('not-empty cols-1 rows-1 index-' + num).removeClass('empty').attr({'colspan': 1, 'rowspan': 1, 'data-index': num}).removeAttr('data-x data-y');
			

				}  else { //多选合并

					for (var r = 0; r < 4; r++) {
						for (var c = 0; c < 4; c++) {
							html.children('.modal-body').find('.selected').each(function(i){
								var col = $(this).data('cols');
								col -= 1;
								var row = $(this).data('rows');
								row -= 1;
								var td = rightHtml.find('tr').eq(r).children('td').eq(c)
								if (i > 0 && col == td.data('x') && row == td.data('y')) {
									td.addClass('delete-td');
								}
							});
						}
					}
					
					//删除合并的单元格
					$('.delete-td').remove();

					var cols = []; //选中列数
					var rows = []; //选中行数
					html.children('.modal-body').find('.selected').each(function(i){
						var col = $(this).data('cols');
						cols[col] = col;
						var row = $(this).data('rows');
						rows[row] = row;
					});
					cols = array2Unique(cols, true); // 去重(undefined)
					cols = cols.length > 0 ? cols.length : 0;
					rows = array2Unique(rows, true); // 去重(undefined)
					rows = rows.length > 0 ? rows.length : 0;

					width *= cols;
					height *= rows;
					selected_unit_text = width + 'x' + height;

					$('.cube2-edit').find('td').removeClass('current');
					$(obj).html('<img src=""><span>' + selected_unit_text + '</span>').addClass('not-empty cols-' + cols + ' rows-' + rows + ' index-' + num + ' current').removeClass('empty').attr({'colspan': cols, 'rowspan': rows, 'data-index': num}).removeAttr('data-x data-y');
				
				}

				//保存数据
				cube_data({'x': x, 'y': y, 'colspan': cols, 'rowspan': rows, 'width': width, 'height': height});

				//当前选中项索引
				selected_index = cube_selected.indexOf(x + ':' + y);
				//魔方内容
				cube_content(obj , x, y, false, width, height);

				//同步展示区域内容
				sync_default_html();

				//关闭选择布局窗口
				html.find('.close').trigger('click');
			})

			//关闭弹层
			html.find('.close').click(function(e){
				$('.modal-backdrop').remove();
				html.remove();
			});
		}

		/**
		 * 设置魔方单元格内容
		 * @param obj 单元格对象
		 * @param x 单元格所在列
		 * @param y 单元格所在行
		 * @param content 是否有内容
		 * @param img_width 建议宽度
		 * @param img_height 建议高度
		 */
		var cube_content = function (obj, x, y, content, img_width, img_height) {

			$('.js-item-region > .choices > .choice').hide();
			if (!content) {
				//
				var _data = get_cube_data(selected_index);
				var _colspan = _data.colspan;
				var _rowspan = _data.rowspan;
				var _empty_cols = 0;
				rightHtml.find('tr').eq(y).children('td').eq(x).nextAll('td').each(function(i) {
					if ($(this).hasClass('empty')) {
						_empty_cols++;
					} else {
						return false;
					}
				});
				_colspan += _empty_cols;

				var _empty_rows = 0;
				rightHtml.find('tr').eq(y).nextAll('tr').each(function(i) {
					var flag = true;
					$(this).children('td').each(function(j) {
						if ($(this).hasClass('empty') && $(this).data('x') == x) {
							_empty_rows++;
						} else {
							flag = false;
						}
					});
					if ($(this).children('td').hasClass('not-empty')) {
						return false;
					}
					if (flag) {
						return false;
					}
				});
				_rowspan += _empty_rows;

				var html = '<li class="choice">';
					html += '	<div class="control-group">';
					html += '		<label class="control-label"><em class="required">*</em>选择图片：</label>';
					html += '		<div class="controls" name="image_url">';
					html += '			<a class="control-action js-trigger-image" href="javascript: void(0);">选择图片</a>';
					html += '			<p class="help-desc">建议尺寸：' + img_width + ' x ' + img_height + ' 像素</p>';
					html += '		</div>';
					html += '	</div>';
					html += '	<div class="control-group">';
					html += '		<label class="control-label">链接到：</label>';
					html += '		<div class="controls">';
					html += '			<div class="dropdown hover">';
					html += '				<a class="js-dropdown-toggle dropdown-toggle control-action" href="javascript:void(0);">设置链接到的页面地址 <i class="caret"></i></a>';
					html += '			</div>';
					html += '		</div>';
					html += '	</div>';
					html += '	<div class="control-group">';
					html += '		<label class="control-label">图片占：</label>';
					html += '		<div class="controls">';
					html += '			<div class="btn-group">';
					html += '				<a class="btn dropdown-toggle" data-toggle="dropdown" href="javascript:;" style="width: 86px;">' + _data.rowspan + '行 ' + _data.colspan + '列<span class="caret"></span></a>';
					html += '				<ul class="dropdown-menu" style="top: 84%;left:0;position: relative;">';
					for (var _r = 0; _r < _rowspan; _r++) {
						for (var _c = 0; _c < _colspan; _c++) {
							html += '			<li><a class="js-image-layout" href="javascript:;" data-width="' + (_c + 1) + '" data-height="' + (_r + 1) + '">' + (_r + 1) + '行 ' + (_c + 1) + '列</a></li>';
						}
					}
					html += '				</ul>';
					html += '			</div>';
					html += '		</div>';
					html += '	</div>';
					html += '	<div class="actions">';
					html += '		<span class="action delete close-modal" title="删除">×</span>';
					html += '	</div>';
					html += '</li>';

				$('.js-item-region > .choices').append(html).slideDown(300);
			} else {
				//收起下拉列表项
				$('.dropdown-menu').hide();

				var _data = get_cube_data(selected_index);
				var _colspan = parseInt(_data.colspan);
				var _rowspan = parseInt(_data.rowspan);
				var _empty_cols = 0;
				rightHtml.find('tr').eq(y).children('td').eq(x).nextAll('td').each(function(i) {
					if ($(this).hasClass('not-empty')) {
						return false;
					}
					if ($(this).hasClass('empty')) {
						_empty_cols++;
					} else {
						return false;
					}
				});
				_colspan += parseInt(_empty_cols);
				
				var _empty_rows = 0;
				rightHtml.find('tr').eq(y).nextAll('tr').each(function(i) {
					var flag = true;
					if ($(this).children('.not-empty') != undefined && $(this).children('.not-empty').length > 0) {
						return false;
					}
					$(this).children('td').each(function(j) {
						if ($(this).hasClass('empty') && $(this).data('x') == x) {
							_empty_rows++;
						} else {
							flag = false;
						}
					});
					if (flag) {
						return false;
					}
				});
				_rowspan += parseInt(_empty_rows);

				var _options = '';
				for (var _r = 0; _r < _rowspan; _r++) {
					for (var _c = 0; _c < _colspan; _c++) {
						_options += '			<li><a class="js-image-layout" href="javascript:;" data-width="' + (_c + 1) + '" data-height="' + (_r + 1) + '">' + (_r + 1) + '行 ' + (_c + 1) + '列</a></li>';
					}
				}

				$('.js-item-region > .choices > .choice:eq(' + selected_index + ')').find('.dropdown-menu:eq(1)').html(_options);
				$('.js-item-region > .choices > .choice:eq(' + selected_index + ')').slideDown(300);
			}

			//选择链接
			var dropdown = rightHtml.find('.dropdown:visible');
			link_box(dropdown, [], function(type, prefix, title, href){
				cube_data({'url': href, 'title': title, 'type': type, 'prefix': prefix}, selected_index);
				dropdown.closest('.control-group').removeClass('error');
				dropdown.find('.error-message').remove();
				dropdown.siblings('.js-link-to').remove();
				var dropdown_selected = $('<div class="left js-link-to link-to"><a href="'+href+'" target="_blank" class="new-window link-to-title"><span class="label label-success">' + prefix + ' <em class="link-to-title-text">' + title + '</em></span></a><a href="javascript:;" class="js-delete-link link-to-title close-modal" title="删除">×</a></div>');
				dropdown.before(dropdown_selected);
				dropdown.addClass('right').children('a').attr('class','dropdown-toggle').html('修改 <i class="caret"></i>');

				//删除链接	
				rightHtml.find('.js-delete-link:visible').click(function(e) {
					cube_data({'url': ''}, selected_index);

					$(this).closest('.js-link-to').next('.dropdown').removeClass('right').children('a').attr('class','js-dropdown-toggle dropdown-toggle').html('设置链接到的页面地址 <i class="caret">');
					$(this).closest('.js-link-to').remove();
				});
			}, 'cube');

			//删除链接	
			rightHtml.find('.js-delete-link:visible').click(function(e) {
				cube_data({'url': ''}, selected_index);

				$(this).closest('.js-link-to').next('.dropdown').removeClass('right').children('a').attr('class','js-dropdown-toggle dropdown-toggle').html('设置链接到的页面地址 <i class="caret">');
				$(this).closest('.js-link-to').remove();
			});
		}

		/**
		 * 保存的魔方数据
		 * @param  {[type]} params [description]
		 * @param  {[type]} index  [description]
		 * @return {[type]}        [description]
		 */
		var cube_data = function (params, index) {
		
			if (index != undefined && cube_save_data.length > 0 && cube_save_data[index]) {
				var data_obj = {
					'x': (params.x != undefined) ? params.x : cube_save_data[index].x,
					'y': (params.y != undefined) ? params.y : cube_save_data[index].y,
					'colspan': (params.colspan != undefined) ? params.colspan : cube_save_data[index].colspan,
					'rowspan': (params.rowspan != undefined) ? params.rowspan : cube_save_data[index].rowspan,
					'width': (params.width != undefined) ? params.width : cube_save_data[index].width,
					'height': (params.height != undefined) ? params.height : cube_save_data[index].height,
					'image': (params.image != undefined) ? params.image : cube_save_data[index].image,
					'type': (params.type != undefined) ? params.type : cube_save_data[index].type,
					'prefix': (params.prefix != undefined) ? params.prefix : cube_save_data[index].prefix,
					'title': (params.title != undefined) ? params.title : cube_save_data[index].title,
					'url': (params.url != undefined) ? params.url : cube_save_data[index].url,
				}
				cube_save_data[index] = data_obj;
			} else {
				cube_selected.push(params.x + ':' + params.y); //已选择的布局

				var data_obj = {
					'x': params.x,
					'y': params.y,
					'colspan': params.colspan || 0,
					'rowspan': params.rowspan || 0,
					'width': params.width || cube_unit_width,
					'height': params.height || cube_unit_height,
					'image': params.image || '',
					'type': params.type || '',
					'prefix': params.prefix || '',
					'title': params.title || '',
					'url': params.url || '',
				}
				cube_save_data.push(data_obj);
				num++;
			}

			//更新绑定在元素上的数据
			domHtml.data('content', cube_save_data);
			domHtml.data('selected', cube_selected.toString());
			if (params.x != undefined && params.y != undefined) {
				domHtml.data('empty', rightHtml.find('tr').eq(params.y).find('td').eq(params.x).siblings('.empty').length); //未选择数量
			}

			//是否显示“+”提示语
			if (rightHtml.find('.empty').length == 0) {
				rightHtml.find('.help-desc:eq(0)').addClass('hide');
			} else {
				rightHtml.find('.help-desc:eq(0)').removeClass('hide');
			}

			return cube_save_data || [];
		}

		/**
		 * 获取魔方数据
		 * @param  {[type]} index [description]
		 * @return {[type]}       [description]
		 */
		var get_cube_data = function (index) {
			if (index == undefined) {
				return cube_save_data;
			} else {
				return cube_save_data[index] || [];
			}				
		}

		/**
		 * 删除魔方数据
		 * @param  {[type]} index [description]
		 * @return {[type]}       [description]
		 */
		var del_cube_data = function (index) {
			try {
				cube_selected.splice(index, 1);
				cube_save_data.splice(index, 1);
				num--;
			} catch (e) {

			}
		}

		/**
		 * 同步左侧展示区域内容
		 * @return {[type]} [description]
		 */
		var sync_default_html = function() {
			var table =cube_create(cube_save_data, 79, 79, 'left');
			domHtml.find('table').html(table);
		}

		//选择图片
		rightHtml.on('click', '.js-trigger-image:visible', function(e){
			var obj = this;
			upload_pic_box(1, true, function(pic_list){
				if(pic_list.length > 0){
					for(var i in pic_list){
						if (typeof(pic_list[i]) == 'string') {
							$(obj).prev('img').remove();
							$(obj).html('修改').before('<img src="' + pic_list[i] + '" width="100" height="100" class="thumb-image" /> ');
							cube_data({'image': pic_list[i]}, selected_index);
							
							$(obj).closest('.control-group').removeClass('error');
							$(obj).nextAll('.error-message').remove();

							_cube_data = get_cube_data();
							var table = cube_create(_cube_data);
							rightHtml.find('table').html(table);
							
							sync_default_html();
						}
					}
				}
			}, 1);
		});

		//删除布局及内容
		rightHtml.on('click', '.delete:visible', function(e) {
			$(this).closest('.choice').remove();
			del_cube_data(selected_index);

			var table = cube_create(cube_save_data);
			rightHtml.find('table').html(table);

			rightHtml.find('.help-desc:eq(0)').removeClass('hide');

			//同步展示区域内容
			sync_default_html();
			rightHtml.find('td').removeClass('current');
		});

		//下拉列表操作魔方单元格（拆分、合并）
		rightHtml.on('click', '.dropdown-toggle:visible', function(e) {
			if ($(this).next('.dropdown-menu:visible').length > 0) {
				$(this).next('.dropdown-menu:visible:visible').slideUp(300);
			} else {
				$(this).next('.dropdown-menu:hidden').slideDown(300);
			}

			//选择下拉项
			rightHtml.on('click', '.js-image-layout:visible', function(e) {
				var _option = $(this).html();
				$(this).closest('ul').prev('.dropdown-toggle').html(_option + '<span class="caret"></span>');

				var _data = get_cube_data(selected_index);
				var _colspan = $(this).data('width');
				var _rowspan = $(this).data('height');
				var _selected_td = _data.colspan + ':' + _data.rowspan; //已选中的项
				if (_selected_td == (_colspan + ':' + _rowspan)) { //重复选择
					$(this).closest('.dropdown-menu').slideUp(300);
					return false;
				} else {
					_selected_td =  _colspan + ':' + _rowspan;
				}

				cube_data({'colspan': _colspan, 'rowspan': _rowspan}, selected_index);
				$(this).closest('.control-group').prevAll('.control-group').find('.help-desc').html('建议尺寸：' + (_colspan * cube_unit_width) + ' x ' + (_rowspan * cube_unit_width) + ' 像素');

				var table = cube_create(cube_save_data);
				rightHtml.find('table').html(table);

				//隐藏下拉列表项
				$(this).closest('.dropdown-menu').slideUp(300);
			})
		});

		//点击下拉列表之外区域隐藏下拉列表
		$('body').click(function(e){
			var _con = $('.dropdown-menu');   // 设置目标区域
			var _con2 = $('.dropdown-toggle');
			if(!_con.is(e.target) && _con.has(e.target).length === 0 && !_con2.is(e.target) && _con2.has(e.target).length === 0){ // Mark 1
				$('.dropdown-menu').slideUp(300);
			}
		})
		
		$('.js-sidebar-region').empty().html(rightHtml);
	}

	//老 勿删
	/**
	 * 专题导航
	 */
	clickArr['subject_menu'] = function(){
		var data = [];
		defaultHtml = '<ul class="clearfix" style="width: 738px;"><li class=" "><a class="mui-control-item mui-active" href="#item1mobile">精选</a> </li><li><a class="mui-control-item" href="#item2mobile"> 礼物</a> </li><li><a class="mui-control-item" href="#item3mobile"> 美食</a> </li><li><a class="mui-control-item" href="#item4mobile"> 家居</a> </li><li><a class="mui-control-item" href="#item5mobile"> 运动</a> </li><li><a class="mui-control-item" href="#item6mobile"> 精选</a> </li><li><a class="mui-control-item" href="#item7mobile"> 精选</a> </li><li><a class="mui-control-item" href="#item8mobile"> 礼物</a> </li><li><a class="mui-control-item" href="#item9mobile"> 美食</a> </li><li><a class="mui-control-item" href="#item10mobile">家居</a> </li><li><a class="mui-control-item" href="#item11mobile"> 运动</a> </li><li><a class="mui-control-item" href="#item12mobile"> 精选</a> </li></ul>';

		if(dom.find('.control-group .custom-subject_menu').size() == 0) {
			domHtml = $('<div style="width:320px;overflow:hidden" class="custom-subject_menu mui-slider-indicator mui-segmented-control mui-segmented-control-inverted menu_list"></div');
			domHtml.html(defaultHtml).data({'subtypeList':[]});
			dom.find(".control-group").data("subtype_list", data);

			dom.find('.control-group').prepend(domHtml);
		} else {
			domHtml = dom.find('.custom-subject_menu');
			data = dom.find(".control-group").data("subtype_list");
		}
		var subtypeList = domHtml.data("subtypeList");
		var sys_subject_menu = $("#edit_custom_subject_menu").attr('subject-menu-field');
		var arr_subject_menu = $.parseJSON(sys_subject_menu);
		var shuoming = '<div class="ui-block-head-help soldout-help js-soldout-help hide" style="display: inline-block;"><a href="javascript:void(0);" class="js-help-notes" data-class="right"></a><div class="js-notes-cont hide"><p>该列表只会列取二级专题分类哦！去 <font style="font-weight:700;"><a href="./user.php?c=goods&a=subject">添加</a></font></p></div></div>';
		left_subject_menu  = '<div class="area-editor-column js-area-editor-notused" style="float:none"><div class="area-editor"><h4 class="area-editor-head">可选专题(攻略)分类 '+shuoming+'</h4>';
		left_subject_menu += '	<ul class="area-editor-list"><li>';
		left_subject_menu += '		<ul class="area-editor-list area-editor-depth js-area-editor-notused-ul">';
		for(var is in arr_subject_menu){
			if(data[arr_subject_menu[is].id]) {

			} else {



				left_subject_menu += '		<li subtype_id="'+arr_subject_menu[is].id+'"><div class="area-editor-list-title"><div class="area-editor-list-title-content js-ladder-select"><div class="js-ladder-toggle area-editor-ladder-toggle extend">+</div><span class="show_typename">'+arr_subject_menu[is].typename+'</span></div></div></li>';
				var subject_son_menu = arr_subject_menu[is].children;
				for(j in subject_son_menu) {
					left_subject_menu += '		<li subtype_id="'+subject_son_menu[j].id+'"><div class="area-editor-list-title"><div class="area-editor-list-title-content js-ladder-select"><div class="js-ladder-toggle area-editor-ladder-toggle extend">+</div><span class="show_typename">'+subject_son_menu[j].typename+'</span></div></div></li>';
				}
			}

		}
		left_subject_menu += '		</ul>';
		left_subject_menu += '	</li></ul>'
		left_subject_menu += '</div></div>';

		right_select = '<div class="area-editor-column area-editor-column-used js-area-editor-used"><div class="area-editor"><h4 class="area-editor-head">已选分类</h4><ul class="area-editor-list"><li><ul class="area-editor-list area-editor-depth js-area-editor-used-ul"></ul></li></ul></div></div>';
		buttons = '<div style="display:inline-block;line-height:340px;height:340px;vertical-align:top;width:70px;text-align:center"><button class="btn btn-default btn-wide area-editor-add-btn js-area-editor-translate">添加</button></div>';
		rightHtml = $(left_subject_menu+buttons+right_select);
		if (data) {
			for (var i in data) {
				//var rightLiObj = $('<li>11' + data[i] + '<span class="area-editor-remove-btn js-ladder-remove">×</span></li>');

					var show_length = 7; var after_pix='';
					data_title = data[i];
					if(data_title.length > show_length) {
							data_title = data_title.substr(0,show_length);
							after_pix = "..";
					}
					data_title = data_title + after_pix;


				var rightLiObj = $('<li><div class="area-editor-list-title"><div class="area-editor-list-title-content js-ladder-select"><div class="js-ladder-toggle area-editor-ladder-toggle extend">+</div>' + data_title + '<div class="area-editor-remove-btn js-ladder-remove">×</div></div></div></li>');

				rightLiObj.data("subtype_id", i);
				rightHtml.find(".js-area-editor-used-ul").append(rightLiObj);
			}
		}


		rightHtml.find("button").click(function () {
			rightHtml.find(".area-editor-list-select").each(function () {
			var subtype_id = $(this).closest("li").attr("subtype_id");
			var title = $(this).find(".show_typename").text();

			if (typeof data[subtype_id] != "undefined") {
				return;
			}
			data[subtype_id] = title;

			//var rightLiObj = $('<li>111' + title + '<span class="area-editor-remove-btn js-ladder-remove">×</span></li>');
			//rightLiObj.data("subtype_id", subtype_id);

			var show_length = 7; var after_pix='';
			if(title.length > show_length) {
					title = title.substr(0,show_length);
					after_pix = "..";
			}
			title = title+after_pix;
			var rightLiObj = $('<li><div class="area-editor-list-title"><div class="area-editor-list-title-content js-ladder-select"><div class="js-ladder-toggle area-editor-ladder-toggle extend">+</div>'+title+'<div class="area-editor-remove-btn js-ladder-remove">×</div></div></div></li>');
			//rightHtml.find(".js-area-editor-used-ul").append(rightsobjs);
			rightLiObj.data("subtype_id", subtype_id);

			rightHtml.find(".js-area-editor-used-ul").append(rightLiObj);
			$(this).remove();
			});

			rightHtml.find(".area-editor-list-select").removeClass("area-editor-list-select");
			rightHtml.find(".js-ladder-remove").click(function () {
				var subtype_id = $(this).closest("li").data("subtype_id");
					var data_typename = data[subtype_id];
				try {
					delete data[subtype_id];
				} catch (e) {

				}
				if(data_typename) {
					var show_length = 7; var after_pix='';
					if(data_typename.length > show_length) {
						data_typename = data_typename.substr(0,show_length);
						after_pix = "..";
					}
					data_typename = data_typename+after_pix;

					//恢复左侧
					var rightLi2Obj = '<li subtype_id="'+subtype_id+'"><div class="area-editor-list-title"><div class="area-editor-list-title-content js-ladder-select"><div class="js-ladder-toggle area-editor-ladder-toggle extend">+</div><span class="show_typename">'+data_typename+'</span></div></div></li>';
					rightHtml.find(".js-area-editor-notused-ul").append(rightLi2Obj);
				}
				$(this).closest("li").remove();
				dom.find(".control-group").data("subtype_list", data);
			});
			dom.find(".control-group").data("subtype_list", data);
			dom.find(".control-group").attr("subtype_list", obj2String(data));
		});
		$('.js-sidebar-region').empty().html(rightHtml);

		rightHtml.find(".js-ladder-remove").click(function () {
			var subtype_id = $(this).closest("li").data("subtype_id");
			var data_typename = data[subtype_id];
			try {
				delete data[subtype_id];
			} catch (e) {

			}

			var show_length = 7; var after_pix='';
			if(data_typename.length > show_length) {
					data_typename = data_typename.substr(0,show_length);
					after_pix = "..";
			}
			data_typename = data_typename+after_pix;
			//恢复左侧
			var rightLi2Obj = '<li subtype_id="'+subtype_id+'"><div class="area-editor-list-title"><div class="area-editor-list-title-content js-ladder-select"><div class="js-ladder-toggle area-editor-ladder-toggle extend">+</div><span class="show_typename">'+data_typename+'</span></div></div></li>';
			rightHtml.find(".js-area-editor-notused-ul").append(rightLi2Obj);

			$(this).closest("li").remove();
			dom.find(".control-group").data("subtype_list", data);
		});
	};

	/**
	 * 专题展示<br/>列表
	 */
	clickArr['subject_display'] = function(){

		var myDate = new Date();var yue = myDate.getMonth()+1;var ri = myDate.getDate();var days = "星期";days_code = myDate.getDay();days_code = parseInt(days_code);
		if(days_code == '0') days += "日";if(days_code == '1') days += "一";if(days_code == '2') days += "二";if(days_code == '3') days += "三";if(days_code == '4') days += "四";if(days_code == '5') days += "五";if(days_code == '6') days += "六";
		defaultHtml = '<article class="subject"><section><ul class="show_list"><li><div class="show_title clearfix"> <span>'+yue+'月'+ri+'日&nbsp;'+days+'</span><i><em></em>下次更新8:00</i> </div><ul class="product_show"><li> <a href="product_info.html"><img src="./upload/images/zhanshi_demo_goods.jpg" class="enlarge"><i class="active"><em></em>9999</i><p>专题名称<b>1</b></p></a> </li></ul></section></article>';

		domHtml = dom.find('.control-group');
		if(dom.find('.control-group .subject_display').size() == 0){
			domHtml = $('<div class="subject_display text-left"></div>');
			domHtml.data({'hour':'0','update_hour':'1','day':'3','number':'1','day_type':'1'}).html(defaultHtml);
			dom.find('.control-group').prepend(domHtml);
		}else{
			domHtml = dom.find('.subject_display');
		}

		rightHtmls  = '<div class="choicei subject_display_right"><form class="form-horizontal"><label style="display:none;width:100px;float:left;text-align:right;padding-top:5px;font-size:14px;line-height:18px">专题更新规则：</label><div class="controls" style="display:none"><label class="radio inline"><input type="radio" name="px_style" value="asc"'+(domHtml.data('px_style')=='asc' ? ' checked="checked"' : '')+'/>升序</label><label class="radio inline"><input type="radio" name="px_style" value="desc"'+(domHtml.data('px_style')=='desc' ? ' checked="checked"' : '')+'/>降序</label></div>';

		rightHtmls += '<div class="control-group"><label  style="width:100px;text-align:right;padding-top:5px;font-size:14px;line-height:18px">专题更新周期：</label>';
		rightHtmls += '<div class="controls"><label class="radio inline">每日 ';
		rightHtmls += '<select name="select_hour" class="select_hour" style="width:55px;">';
		for(i=0;i<24;i++) {
			if(domHtml.data('hour') == i ) {
				rightHtmls += 	'<option selected="selected" value="'+i+'">'+i+'</option>';
			} else{
				rightHtmls += 	'<option value="'+i+'">'+i+'</option>';
			}
		}
		rightHtmls += '</select> 点开始更新</label></div>';


		rightHtmls += '<div class="control-group">';
		rightHtmls += '<div class="controls"><label class="radio inline">每隔 ';
		rightHtmls += '<select class="select_update_hour" name="select_update_hour" style="width:55px;">';
		if(domHtml.data('update_hour') == 1 ) {	rightHtmls += 	'<option selected="selected" value="1">1</option>';} else {	rightHtmls += 	'<option value="1">1</option>';}
		if(domHtml.data('update_hour') == 2 ) {	rightHtmls += 	'<option selected="selected" value="2">2</option>';} else {	rightHtmls += 	'<option value="2">2</option>';}
		if(domHtml.data('update_hour') == 3 ) {	rightHtmls += 	'<option selected="selected" value="3">3</option>';} else {	rightHtmls += 	'<option value="3">3</option>';}
		if(domHtml.data('update_hour') == 4 ) {	rightHtmls += 	'<option selected="selected" value="4">4</option>';} else {	rightHtmls += 	'<option value="4">4</option>';}
		if(domHtml.data('update_hour') == 5 ) {	rightHtmls += 	'<option selected="selected" value="5">5</option>';} else {	rightHtmls += 	'<option value="5">5</option>';}
		if(domHtml.data('update_hour') == 6 ) {	rightHtmls += 	'<option selected="selected" value="6">6</option>';} else {	rightHtmls += 	'<option value="6">6</option>';}
		rightHtmls += '</select> 小时再次更新数据</label></div>';

		rightHtmls += '<div class="control-group">';
		rightHtmls += '<div class="controls"><label class="radio inline">显示 ';
		rightHtmls += '<select class="select_day" name="select_day" style="width:55px;">';
		if(domHtml.data('day') == 3 ) {	rightHtmls += 	'<option selected="selected" value="3">3</option>';} else {		rightHtmls += 	'<option value="3">3</option>';}
		if(domHtml.data('day') == 5 ) {	rightHtmls += 	'<option selected="selected" value="5">5</option>';} else {		rightHtmls += 	'<option value="5">5</option>';}
		if(domHtml.data('day') == 7 ) {	rightHtmls += 	'<option selected="selected" value="7">7</option>';} else {		rightHtmls += 	'<option value="7">7</option>';}
		if(domHtml.data('day') == 15) {	rightHtmls += 	'<option selected="selected" value="15">15</option>';}else {	rightHtmls += 	'<option value="15">15</option>';}
		rightHtmls += '</select> 天数据</label>';
		rightHtmls += '<span style="display:inline-block;margin-left:5px;">';
		
		//var shuoming = '<div class="ui-block-head-help soldout-help js-soldout-help hide" style="display: inline-block;"><a href="javascript:void(0);" class="js-help-notes" data-class="right"></a><div class="js-notes-cont hide"><p>自然天数据：  <font>截止今天到之前的的N天数据</font></p><p>数据天数据：  <font>截止今天到之前的有数据的N天数据</font></p><p style="color:#f00">* <font>选择自然天：当前推前的N天，若无数据，可能造成无数据显示！</font></p></div></div>';
		var shuoming = "";
		
		if(domHtml.data('day_type') != 2 ) {rightHtmls += '		<input type="radio" name="day_type" value="1" checked="checked" style="margin:0px 0px 0px 4px;"> 自然天数据';} else {rightHtmls += '		<input type="radio" name="day_type" value="1" style="margin:0px 0px 0px 4px;"> 自然天数据';}
		if(domHtml.data('day_type') == 2 ) {rightHtmls += '		<input type="radio" name="day_type" value="2" checked="checked" style="margin:0px 0px 0px 4px;"> 数据天数据';} else {rightHtmls += '		<input type="radio" name="day_type" value="2" style="margin:0px 0px 0px 4px;"> 数据天数据';}
		rightHtmls += '</span>';
		rightHtmls += '</div>';

		rightHtmls += '<div class="controls"><label class="radio inline">每日 ';
		rightHtmls += '<select class="select_number" name="select_number" style="width:55px;">';
		if(domHtml.data('number') == 1 ) {rightHtmls += 	'<option selected="selected" value="1">1</option>';	} else {rightHtmls += 	'<option value="1">1</option>';	}
		if(domHtml.data('number') == 2 ) {rightHtmls += 	'<option selected="selected" value="2">2</option>';	} else {rightHtmls += 	'<option value="2">2</option>';	}
		if(domHtml.data('number') == 3 ) {rightHtmls += 	'<option selected="selected" value="3">3</option>';	} else {rightHtmls += 	'<option value="3">3</option>';	}
		if(domHtml.data('number') == 5 ) {rightHtmls += 	'<option selected="selected" value="5">5</option>';	} else {rightHtmls += 	'<option value="5">5</option>';	}
		if(domHtml.data('number') == 10) {rightHtmls += 	'<option selected="selected" value="10">10</option>';	} else {rightHtmls += 	'<option value="10">10</option>';	}


		rightHtmls += '</select> 条数据显示</label></div>';


	//	rightHtmls += '<label class="radio inline size_1_label" '+(domHtml.data('type')=='0' ? 'style="display:none;"' : '')+'><input type="radio" name="size" value="1"  '+(domHtml.data('size')=='1' ? ' checked="checked"' : '')+'/>小图</label></div></div></form></div>';


		rightHtml = $(rightHtmls);
		//右侧排序规则

		rightHtml.find('input[name="px_style"]').change(function(){
			domHtml.data('px_style',$(this).val());
			domHtml.attr('px_style',$(this).val());
		});
		//右侧每日时间
		rightHtml.find('select[name="select_hour"]').change(function(){
			domHtml.data('hour',$(this).val());
			domHtml.attr('hour',$(this).val());
		});
		//右侧多少天数据
		rightHtml.find('select[name="select_day"]').change(function(){
			domHtml.data('day',$(this).val());
			domHtml.attr('day',$(this).val());
		});
		//右侧选择显示天数类型
		rightHtml.find('input[name="day_type"]').change(function(){
			var day_type_val= rightHtml.find("input[name='day_type']:checked").val();
			domHtml.data('day_type',day_type_val);
			domHtml.attr('day_type',day_type_val);
		});

		//右侧每隔多少小时再次更新
		rightHtml.find('select[name="select_update_hour"]').change(function(){
			domHtml.data('update_hour',$(this).val());
			domHtml.attr('update_hour',$(this).val());
		});
		//右侧多少天数据
		rightHtml.find('select[name="select_number"]').change(function(){
			domHtml.data('number',$(this).val());
			domHtml.attr('number',$(this).val());
		});

		$('.js-sidebar-region').empty().html(rightHtml);

	};

	/**
	 * 网店logo抬头
	 */
	clickArr['tpl_shop'] = function(){
		//判定左侧是否已经有了
		var defaultTitle = '店铺标题xx';
		var biaotis = store_name?store_name:defaultTitle;
		var logos = "";
        var default_shop = staticpath+"images/default_shop.png";
		logos = store_logo?store_logo:default_shop;

		defaultHtml = '<div class="tpl-shop"><div class="tpl-shop-header" style="background-color:#6DABEB"><div class="tpl-shop-title">'+biaotis+'</div><div class="tpl-shop-avatar"><img src="'+logos+'" alt=""></div></div><div class="tpl-shop-content"><ul class="clearfix"><li><a href="javascript:;"><span class="count">0</span> <span class="text">全部商品</span></a></li><li><a href="javascript:;"><span class="count mycard"></span> <span class="text">会员卡</span></a></li><li><a href="javascript:;"><span class="count user"></span> <span class="text">我的订单</span></a></li></ul></div></div>';

		if(dom.find('.control-group .tpl-shop').size() == 0){
			domHtml = $('<div class="tpl-shop text-left"></div>');
			domHtml.data({'shop_head_bg_img':'','shop_head_logo_img':'','bgcolor':'','title':''}).html(defaultHtml);
			dom.find('.control-group').prepend(domHtml);
			//取默认数据
			domHtml.data('title','');
			//domHtml.data('shop_head_logo_img',logos);
			//domHtml.data('shop_head_bg_img',"/upload/images/head_bg1.png");
			domHtml.data('bgcolor',"#6DABEB");
		}else{
			domHtml = dom.find('.tpl-shop');
		}
		domHtml.data('shop_head_logo_img',logos);
		/*
		<div class="controls tpl_shop-bg">
			<div class="tpl-shop-header" style="width:320px;height:90px;background-image:url(/upload/images/head_bg1.png) ;background-repeat:no-repeat;background-size:320px 90px">
				<a class="close-modal small hide js-delete-image" data-index="0">×</a>
			</div>
			<a class="control-action js-trigger-image" href="javascript: void(0);">修改</a>
			<p class="help-desc">最佳尺寸：640 x 200 像素。</p><p class="help-desc">尺寸不匹配时，图片将被压缩或拉伸以铺满画面。</p>
			<p class="help-desc">当设置背景图片后，设置的颜色将会失效。</p>
		</div>
		*/

		var right_bjtp = '<div class="controls tpl_shop-bg"><div class="tpl-shop-header" style=""><a class="close-modal small hide js-delete-image" data-index="0">×</a></div><a class="control-action js-trigger-image js-add-bgpic" href="javascript: void(0);">添加</a><p class="help-desc">最佳尺寸：640 x 200 像素。</p><p class="help-desc">尺寸不匹配时，图片将被压缩或拉伸以铺满画面。</p><p class="help-desc">当设置背景图片后，设置的颜色将会失效。</p></div>';
		var rightHtml = $('<div><form class="form-horizontal" novalidate><div class="control-group"><label class="control-label">背景图片：</label>'+right_bjtp+'</div><div class="control-group"><label class="control-label">背景颜色：</label><div class="controls"><input type="color" value="#ffffff" name="backgroundColor"> <button class="btn js-reset-bg" type="button">重置</button></div></div></form></div>');
		//右侧 背景图片上传
		rightHtml.find('.js-trigger-image').click(function() {
			var imageDom = $(this);
			upload_pic_box(1,true,function(pic_list) {
				if(pic_list.length > 0){
					for(var i in pic_list) {
						//替换左侧 背景图和 背景色
						domHtml.find(".tpl-shop-header").css({ "background-color": "#ff0011", "background-image": "url("+pic_list[i]+")" });
						domHtml.data("shop_head_bg_img",pic_list[i]);
						//上传 返回右侧图片
						rightHtml.find(".tpl-shop-header").css({ "height":"90px","width":"320px","background-color": "#ff0011","background-size":"320px 90px", "background-repeat":"no-repeat","background-image": "url("+pic_list[i]+")" });
						rightHtml.find(".js-add-bgpic").html("修改");
					}
				}
			},1);
		});


		rightHtml.find('input[name="title"]').blur(function(){
			domHtml.data('title',$(this).val()).find('.tpl-shop-title').html(($(this).val().length != 0 ? $(this).val() : biaotis));
		});

		//背景色
		rightHtml.find('input[name="backgroundColor"]').change(function(){
			domHtml.find(".tpl-shop-header").css('background-color',$(this).val());
			domHtml.data("bgcolor",$(this).val());
		});

		//右侧背景图
		var shop_head_bg_img_data = domHtml.data('shop_head_bg_img');
		var html = '';
		if(shop_head_bg_img_data) {
			rightHtml.find('.tpl-shop-header').css({height:"0px"});
			rightHtml.find('.tpl-shop-header').css({"width":"320px","height": "90px","background-repeat":"no-repeat","background-size":"320px 90px", "background-image": "url("+shop_head_bg_img_data+")" });
			rightHtml.find(".js-add-bgpic").html("修改");
		}
		//标题
		var title_data = domHtml.data('title');
		var html = '';
		if(title_data) {
			rightHtml.find("input[name='title']").val(title_data);
		}
		//背景色
		var bgcolor_data = domHtml.data('bgcolor');
		var html = '';
		if(bgcolor_data) {
			rightHtml.find("input[name='backgroundColor']").val(bgcolor_data);
		}
		////////////////////////////////////////////
		var timepicker = rightHtml.find('.js-time-holder');
		timepicker.datetimepicker({
			dateFormat: "yy-mm-dd",
			timeFormat: "HH:mm",
			minDate: new Date,
			changeMonth:true,
			changeYear:true,
			onSelect: function(e){
				timepicker.siblings('input[name="sub_title"]').val(e).trigger('blur');
			}
		});
		rightHtml.find('a.js-time').click(function(){
			timepicker.datepicker('show');
		});


		rightHtml.find('.js-reset-bg').click(function(){
			$(this).siblings('input[name="backgroundColor"]').val('#6DABEB');
			domHtml.data('bgcolor','');
			domHtml.find(".tpl-shop-header").css('background-color','#6DABEB');
		});

		$('.js-sidebar-region form').remove();
		$('.js-sidebar-region').empty().html(rightHtml);
	};
	
	/**
	 * 网店logo抬头1
	 */
	clickArr['tpl_shop1'] = function(){
		var defaultTitle = '店铺标题xx';
		var logos = "";
        var default_shop = staticpath+"images/default_shop.png";
		var logos = store_logo?store_logo:default_shop;

		var biaotis = store_name?store_name:defaultTitle;

	//defaultHtml = '<div class="tpl-shop1 tpl-wxd"> <div class="tpl-wxd-header" style="background-image: url(/upload/images/tpl_wxd_bg.png)"><div class="tpl-wxd-title">"</div><div class="tpl-wxd-avatar"><img src="/upload/images/moren_head.jpg" alt=""></div> </div></div>';

		var defaultHtml = '<div class="tpl-shop1 tpl-wxd"> <div class="tpl-wxd-header" style="background-color:#FF6600" ><div class="tpl-wxd-title">'+biaotis+'</div><div class="tpl-wxd-avatar"><img src="'+logos+'" alt=""></div> </div></div>';
		var domHtml;
		if(dom.find('.control-group .tpl-shop1').size() == 0){
			domHtml = $('<div class="tpl-shop1 text-left"></div>');
			domHtml.data({'shop_head_bg_img':'','shop_head_logo_img':'','bgcolor':'','title':''}).html(defaultHtml);
			dom.find('.control-group').prepend(domHtml);
			//标题(店铺名字)
			domHtml.data('title','');
			//domHtml.data('shop_head_logo_img',logos);
			//domHtml.data('shop_head_bg_img',"/upload/images/tpl_wxd_bg.png");
			domHtml.data('bgcolor',"#FF6600");
		}else{
			domHtml = dom.find('.tpl-shop1');
		}
		domHtml.data('shop_head_logo_img',logos);
		//beijing
		var right_bjtp = '<div class="controls tpl_shop-bg"><div class="tpl-shop-header" style=""><a class="close-modal small hide js-delete-image" data-index="0">×</a></div><a class="control-action js-trigger-image js-add-bgpic" href="javascript: void(0);">添加</a><p class="help-desc">最佳尺寸：640 x 200 像素。</p><p class="help-desc">尺寸不匹配时，图片将被压缩或拉伸以铺满画面。</p><p class="help-desc">当设置背景图片后，设置的颜色将会失效。</p></div>';

		// rightHtml = $('<div><form class="form-horizontal" novalidate><div class="control-group"><label class="control-label">背景图片：</label>'+right_bjtp+'</div><div class="control-group"><label class="control-label">背景颜色：</label><div class="controls"><input type="color" value="#ffffff" name="backgroundColor"> <button class="btn js-reset-bg" type="button">重置</button></div></div><div class="control-group"><label class="control-label">店铺标题：</label><div class="controls"><input type="text" name="title" value="" placeholder="默认为店铺名称" maxlength="100"></div></div><div class="control-group"><label class="control-label">店铺Logo：</label><div class="controls"><img src="'+staticpath+'images/default_shop.png" width="80" height="80" class="thumb-image" style="width:80px;height:80px"> <a class="control-action js-trigger-avatar" href="javascript: void(0);">修改店铺Logo</a></div></div></form></div>');
        rightHtml = $('<div><form class="form-horizontal" novalidate><div class="control-group"><label class="control-label">背景图片：</label>'+right_bjtp+'</div><div class="control-group"><label class="control-label">背景颜色：</label><div class="controls"><input type="color" value="#ffffff" name="backgroundColor"> <button class="btn js-reset-bg" type="button">重置</button></div></div></form></div>');
        //右侧 背景图片上传
		rightHtml.find('.js-trigger-image').click(function(){
			var imageDom = $(this);
			upload_pic_box(1,true,function(pic_list){
				if(pic_list.length > 0){
					for(var i in pic_list){
						//imageDom.siblings('.thumb-image').remove();
						//imageDom.removeClass('add-image').addClass('modify-image').html('重新上传').before('<img src="'+pic_list[i]+'" width="118" height="118" class="thumb-image"/>');
						//替换左侧 背景图和 背景色
						domHtml.find(".tpl-wxd-header").css({ "background-color": "#ff0011", "background-image": "url("+pic_list[i]+")" });
						domHtml.data("shop_head_bg_img",pic_list[i]);

						//替换右侧 背景图
						rightHtml.find('.tpl-shop-header').css({height:"0px"});
						rightHtml.find('.tpl-shop-header').css({height: "90px","background-repeat":"no-repeat","background-size":"320px 90px" ,"background-image": "url("+pic_list[i]+")" });
					}
				}
			},1);
		});
		//背景色
		rightHtml.find('input[name="backgroundColor"]').change(function(){
			domHtml.find(".tpl-wxd-header").css('background-color',$(this).val());
			domHtml.data("bgcolor",$(this).val());
		});


		//标题 店铺名字
		//domHtml.data('title',biaotis);
		rightHtml.find('input[name="title"]').blur(function(){
			domHtml.data('title',$(this).val()).find('.tpl-wxd-title').html(($(this).val().length != 0 ? $(this).val() : biaotis));
		});


		rightHtml.find('.js-trigger-avatar').click(function(){
			var imageDom2 = $(this);
			upload_pic_box(1,true,function(pic_list){
				if(pic_list.length > 0){
					for(var i in pic_list){
						imageDom2.siblings('.thumb-image').remove();
						imageDom2.removeClass('add-image').addClass('modify-image').html('重新上传').before('<img src="'+pic_list[i]+'" width="80" height="80" class="thumb-image"/>');
						//替换左侧 小logo
						domHtml.find(".tpl-wxd-avatar img").attr("src",pic_list[i]);
						domHtml.data("shop_head_logo_img",pic_list[i]);

						logos = pic_list[i];

					}
				}
			},1);
		});

		////////////////////////////////////////////
		//右侧 小图
		//var shop_head_logo_img_data = domHtml.data('shop_head_logo_img');
		var shop_head_logo_img_data = logos;
		var html = '';
		if(shop_head_logo_img_data) {
			html= '<img src="'+shop_head_logo_img_data+'" width="80" height="80" class="thumb-image"/>';
			rightHtml.find('.js-trigger-avatar').siblings('.thumb-image').remove();
			rightHtml.find('.js-trigger-avatar').removeClass('add-image').addClass('modify-image').html('重新上传').before(html);
		}
		//右侧背景图
		var shop_head_bg_img_data = domHtml.data('shop_head_bg_img');
		if(shop_head_bg_img_data) {
			rightHtml.find('.tpl-shop-header').css({height:"0px"});
			rightHtml.find('.tpl-shop-header').css({height: "90px","width":"320px","background-size":"320px 90px","background-repeat":"no-repeat", "background-image": "url("+shop_head_bg_img_data+")" });
		}
		//标题
		var title_data = domHtml.data('title');
		if(title_data) {
			rightHtml.find("input[name='title']").val(title_data);
		}
		//背景色
		var bgcolor_data = domHtml.data('bgcolor');
		if(bgcolor_data) {
			rightHtml.find("input[name='backgroundColor']").val(bgcolor_data);
		}
		////////////////////////////////////////////

		var timepicker = rightHtml.find('.js-time-holder');
		timepicker.datetimepicker({
			dateFormat: "yy-mm-dd",
			timeFormat: "HH:mm",
			minDate: new Date,
			changeMonth:true,
			changeYear:true,
			onSelect: function(e){
				timepicker.siblings('input[name="sub_title"]').val(e).trigger('blur');
			}
		});
		rightHtml.find('a.js-time').click(function(){
			timepicker.datepicker('show');
		});

		rightHtml.find('input[name="show_method"]').change(function(){
			domHtml.data('show_method',$(this).val());
			switch($(this).val()){
				case '0':
					domHtml.removeClass('text-center text-right').addClass('text-left');
					break;
				case '1':
					domHtml.removeClass('text-left text-right').addClass('text-center');
					break;
				default:
					domHtml.removeClass('text-left text-center').addClass('text-right');
			}
		});


		rightHtml.find('.js-reset-bg').click(function(){
			$(this).siblings('input[name="color"]').val('#ffffff');
			domHtml.css('background-color','').data('bgcolor','');
		});

		$('.js-sidebar-region form').remove();
		$('.js-sidebar-region').empty().html(rightHtml);
	};
	
	/**
	 * 优惠券
	 */
	clickArr['coupons'] = function(){
		/*右侧默认样式*/
		var rightHtml=$('<div><form class="form-horizontal edit-custom-coupon" novalidate="" onsubmit="return false"><div class="control-group"><label class="control-label">优惠券：</label><div class="controls"><ul class="module-goods-list clearfix ui-sortable coupon-list js-coupon-list" name="goods"><li><a href="javascript:void(0);" class="js-add-goods add-goods"><i class="icon-add"></i></a></li></ul></div></div></form></div>');
		//添加右侧代码
		$('.js-sidebar-region').empty().html(rightHtml);
		//调用widget_link_yhq()方法，传入dom、type、回调 
		widget_link_yhq(rightHtml.find('.js-add-goods'),'coupon',function(result){
			/*判断是否是第一次还是第二次数据，第一次就直接赋值，第二次就先合并后赋值*/

			//domHtml.data值取出来赋值
			var coupon_data = domHtml.data('coupon_data');
			if(coupon_data){
				//console.log('ok');
				if (coupon_data.coupon_arr != undefined) {
					//第二次数据合并
					$.merge(coupon_data.coupon_arr,result);
				} else {
					$.merge(coupon_data,result);
				}
			}else{
				//第一次
				coupon_data = result;
			}
			//重新存回去domHtml.data
			domHtml.data('coupon_data',coupon_data);
			//选取确定后左侧html和右侧list_html
			var html='';
			var coupon_list_html = '';
			if (typeof coupon_data.coupon_arr != "undefined") {
				//第二次
				for(var i in coupon_data.coupon_arr){
					//alert(coupon_data.coupon_arr[i].face_money);
					html+='<li>  <a href="javascript:;"><div class="custom-coupon-price"><span>￥</span>'+coupon_data.coupon_arr[i].face_money+'</div><div class="custom-coupon-desc">'+coupon_data.coupon_arr[i]['title']+'</div>  </a> </li>';
					coupon_list_html += '<li class="sort" data-coupon-id="' + i + '"><a href="javascript:"><div class="coupon-money">￥' + coupon_data.coupon_arr[i].face_money + '</div></a><a class="close-modal js-delete-coupon small hide" data-id="0" title="删除">×</a></li>';
				}
			} else {
				//第一次
				for(var i in coupon_data){
					//alert(coupon_data.coupon_arr[i].face_money);
					html+='<li>  <a href="javascript:;"><div class="custom-coupon-price"><span>￥</span>'+coupon_data[i].face_money+'</div><div class="custom-coupon-desc">'+coupon_data[i]['title']+'</div>  </a> </li>';
					coupon_list_html += '<li class="sort" data-coupon-id="' + i + '"><a href="javascript:"><div class="coupon-money">￥' + coupon_data[i].face_money + '</div></a><a class="close-modal js-delete-coupon small hide" data-id="0" title="删除">×</a></li>';
				}
			}
			//添加html
			domHtml.html(html);
			//移除
			rightHtml.find('.sort').remove();
			//倒数第一个<li>前加上coupon_list_html代码
			rightHtml.find("li").eq(-1).before(coupon_list_html);

			/*删除对应的左侧和右侧html，和对应存在的coupon_data*/

			//右侧点击x删除，选取确认后
			rightHtml.find(".js-delete-coupon").click(function (event) {
				//取最近的<li>的coupon-id赋值给变量
				var coupon_id = $(this).closest("li").data("coupon-id");
				var index = $(this).closest("ul").find("li").index($(this).closest("li"));

				//移除
				domHtml.find("li").eq(index).remove();
				//最近的<li>移除
				$(this).closest("li").remove();

				//删除对应coupon_id的值
				//domHtml.data值取出来赋值
				var coupon_data = domHtml.data('coupon_data');
				//删除对应的coupon_data
				try {
					//第二次
					//删除coupon_data.coupon_arr数组的coupon_id
					delete coupon_data.coupon_arr[coupon_id];
				} catch(e) {
					//第一次
					//alert('ok');
					delete coupon_data[coupon_id];
				}
				//重新存回去domHtml.data
				domHtml.data('coupon_data', coupon_data);
			});

		});

		/*定义默认左侧html代码(第一次和第二次)*/
		var defaultHtml='<li><a href="javascript:;"><div class="custom-coupon-price"><span>￥</span>100</div><div class="custom-coupon-desc">满500元可用</div></a></li><li><a href="javascript:;"><div class="custom-coupon-price"><span>￥</span>100</div><div class="custom-coupon-desc">满500元可用</div></a></li><li><a href="javascript:;"><div class="custom-coupon-price"><span>￥</span>100</div><div class="custom-coupon-desc">满500元可用</div></a></li>';
		if(dom.find('.control-group .custom-coupon').size() == 0){
			//console.log('1');
			//第一次
			//如果没有custom-coupon则添加
			//domHtml = $('<div class="component-border"></div>');
			domHtml = $('<ul class="custom-coupon clearfix"></ul>');
			//添加html,存值
			domHtml.html(defaultHtml).data('coupon_data', {'coupon_arr': []});
			//在control-group前面添加domHtml
			dom.find('.control-group').prepend(domHtml);
		}else{
			//第二次
			//找到custom-coupon赋值给domHtml
			domHtml = dom.find('.custom-coupon');
			//取出coupon_data赋值给变量obj
			var obj = domHtml.data('coupon_data');
			var defaultHtml = '';
			var coupon_list_html = '';
			
			for(var i in obj.coupon_arr){
				defaultHtml += '<li><a href="javascript:;"><div class="custom-coupon-price"><span>￥</span>'+obj.coupon_arr[i]['face_money']+'</div><div class="custom-coupon-desc"> '+obj.coupon_arr[i]['title']+'</div>  </a> </li>';
				coupon_list_html += '<li class="sort" data-coupon-id="' + i + '"><a href="javascript:"><div class="coupon-money">￥' + obj.coupon_arr[i]['face_money'] + '</div></a><a class="close-modal js-delete-coupon small hide" data-id="0" title="删除">×</a></li>';
			}
			
			if (defaultHtml == "") {
				defaultHtml = '<li><a href="javascript:;"><div class="custom-coupon-price"><span>￥</span>100</div><div class="custom-coupon-desc">满500元可用</div></a></li><li><a href="javascript:;"><div class="custom-coupon-price"><span>￥</span>100</div><div class="custom-coupon-desc">满500元可用</div></a></li><li><a href="javascript:;"><div class="custom-coupon-price"><span>￥</span>100</div><div class="custom-coupon-desc">满500元可用</div></a></li>';
			}
			
			//添加defaultHtml
			domHtml.html(defaultHtml);
			//倒数第一个<li>前加上coupon_list_html代码
			rightHtml.find("li").eq(-1).before(coupon_list_html);
		}

		//右侧点击x删除
		rightHtml.find(".js-delete-coupon").click(function (event) {
			//取最近的<li>的coupon-id赋值给变量
			var coupon_id = $(this).closest("li").data("coupon-id");
			var index = $(this).closest("ul").find("li").index($(this).closest("li"));

			//移除
			domHtml.find("li").eq(index).remove();
			//最近的<li>移除
			$(this).closest("li").remove();

			//取最近的<li>的coupon_data赋值给变量
			var coupon_data = domHtml.data('coupon_data');
			//删除coupon_data.coupon_arr数组的coupon_id
			delete coupon_data.coupon_arr[coupon_id];
			//在dom上存值
			domHtml.data('coupon_data', coupon_data);
		});
	};

	/**
	 * 商品分组(左部导航)
	 */
	clickArr['goods_group1']=function(){
		var rightHtml=$('<div><form class="form-horizontal" novalidate=""><div class="control-group options js-add-subentry" style="display: block;"><a class="add-option js-add-option" href="javascript:void(0);"><i class="icon-add"></i> 添加商品分组</a></div><div class="control-group"><p class="app-component-desc help-desc">选择商品来源后，左侧实时预览暂不支持显示其包含的商品数据</p></div></form></div>');
		if(dom.find('.control-group .goods_group1').size()==0){
			domHtml = $('<ul class="goods_group1 clearfix"></ul>');
			domHtml.data({'goods_group1_arr':[]});
			dom.find('.control-group').prepend(domHtml);
			var defaultHtml='<div class="custom-tag-list clearfix"><div class="custom-tag-list-menu-block js-collection-region" style="min-height: 323px;"><ul class="custom-tag-list-side-menu"><li><a href="javascript:;" class="current">商品组一</a><a href="javascript:;">商品组二</a><a href="javascript:;">商品组三</a></li></ul></div><div class="custom-tag-list-goods"><ul class="custom-tag-list-goods-list"><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="'+staticpath+'/images/kd5.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="'+staticpath+'/images/kd1.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="'+staticpath+'/images/kd3.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="'+staticpath+'/images/kd4.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span></div></li></ul></div></div>';
		}else{
			domHtml = dom.find('.control-group .goods_group1');
			
			if (typeof domHtml.data('goods_group1_arr') == 'undefined') {
				domHtml.data({'goods_group1_arr': []});
			}
		}

		var goods_group1_arr = domHtml.data('goods_group1_arr');
		var html='';
		for(var i in goods_group1_arr){
			html+='<li class="choice"  data-id="'+goods_group1_arr[i].id+'"><div class="edit-tag-list"><div class="tag-source"><div class="control-group"><label class="control-label pull-left">商品来源：</label><div class="controls pull-left"><a href="#" target="_blank" class="tag-title new-window">'+goods_group1_arr[i].title+'</a><input type="hidden" name="title"></div></div></div><div class="split-line"></div><div class="goods-number"><span>显示商品数量</span><div class="dropdown hover pull-right"><a class="dropdown-toggle" data-toggle="dropdown" href="javascript:void(0);">'+(goods_group1_arr[i].show_num?goods_group1_arr[i].show_num:10)+' <i class="caret"></i></a><ul class="dropdown-menu" role="menu"><li><a class="js-goods-number" data-value="5" href="javascript:void(0);">5</a></li><li><a class="js-goods-number" data-value="10" href="javascript:void(0);">10</a></li><li><a class="js-goods-number" data-value="15" href="javascript:void(0);">15</a></li><li><a class="js-goods-number" data-value="30" href="javascript:void(0);">30</a></li></ul></div></div></div><div class="actions"><span class="action delete close-modal" title="删除">×</span></div></li>';
		}


		rightHtml.find('.form-horizontal .js-add-subentry').prepend(html);


		//上传商品
		//widget_link_box(rightHtml.find('.js-add-option'),'goodcat',function(result){
		widget_link_box(rightHtml.find('.js-add-option'),'good_group&only=1',function(result){
			var goods_group1_arr = domHtml.data('goods_group1_arr');
			if(goods_group1_arr){
				//alert(1);
				$.merge(goods_group1_arr,result);
			}else{
				//alert(2);
				goods_group1_arr = result;
			}

            console.log('goods_group1_arr1', goods_group1_arr);

			domHtml.data('goods_group1_arr',goods_group1_arr);
			rightHtml.find('.module-goods-list .sort').remove();


			var html = '';
			var shtml='';

			for(var i in goods_group1_arr){
				if (goods_group1_arr[i]) {
					if (goods_group1_arr[i]['title'] && !goods_group1_arr[i]["name"]) {
                        goods_group1_arr[i]['name'] = goods_group1_arr[i]["title"];
					} else if(goods_group1_arr[i]['name'] && !goods_group1_arr[i]["title"]) {
                        goods_group1_arr[i]['title'] = goods_group1_arr[i]["name"];
                    }
                    html+='<li class="choice"  data-id="'+goods_group1_arr[i].id+'"><div class="edit-tag-list"><div class="tag-source"><div class="control-group"><label class="control-label pull-left">商品来源：</label><div class="controls pull-left"><a href="#" target="_blank" class="tag-title new-window">'+goods_group1_arr[i].name+'</a><input type="hidden" name="title"></div></div></div><div class="split-line"></div><div class="goods-number"><span>显示商品数量</span><div class="dropdown hover pull-right"><a class="dropdown-toggle" data-toggle="dropdown" href="javascript:void(0);">'+(goods_group1_arr[i].show_num?goods_group1_arr[i].show_num:10)+' <i class="caret"></i></a><ul class="dropdown-menu" role="menu"><li><a class="js-goods-number" data-value="5" href="javascript:void(0);">5</a></li><li><a class="js-goods-number" data-value="10" href="javascript:void(0);">10</a></li><li><a class="js-goods-number" data-value="15" href="javascript:void(0);">15</a></li><li><a class="js-goods-number" data-value="30" href="javascript:void(0);">30</a></li></ul></div></div></div><div class="actions"><span class="action delete close-modal" title="删除">×</span></div></li>';
                    shtml+='<li data-title="'+goods_group1_arr[i]["name"]+'" id="'+goods_group1_arr[i]["id"]+'"><a href="javascript:;" ' + (i == 0 ? 'class="current"' : '') + '><span>'+goods_group1_arr[i]["name"]+'</span></a></li>';
				}
			}

			$('.custom-tag-list-side-menu').empty().html(shtml);
			//rightHtml.find('.form-horizontal .js-add-subentry').empty().prepend(html);
			rightHtml.find('.form-horizontal .js-add-subentry li').remove();
			rightHtml.find('.form-horizontal .js-add-subentry').prepend(html);
			rightHtml.find('.delete').click(function(){
				var dataId = $(this).parents('.choice').attr('data-id');
				var current = true;
				var shtml = '';
				for(var i in goods_group1_arr){
                    if (goods_group1_arr[i]) {
						if(goods_group1_arr[i].id==dataId){
							delete goods_group1_arr[i];
						} else {
							var current_class = '';
							if (current) {
								current_class = 'class="current"';
								current = false;
							}
							shtml += '<li><a href="javascript:;" ' + current_class + '>' + goods_group1_arr[i].name + '</a></li>';
						}
                    }
				}
                console.log('goods_group1_arr2', goods_group1_arr);
				domHtml.data('goods_group1_arr',goods_group1_arr);
				$(this).parents('.choice').remove();
				
				if (shtml.length == 0) {
					shtml = '<li><a href="javascript:;" class="current">商品组一</a></li><li><a href="javascript:;">商品组二</a></li><li><a href="javascript:;">商品组三</a></li>';
				}
				$('.custom-tag-list-side-menu').empty().html(shtml);
			});

			rightHtml.find('.dropdown').toggle(function(){
				$(this).find('.dropdown-menu').show();
			},function(){
				$(this).find('.dropdown-menu').hide();
			});

			rightHtml.find('.dropdown-menu li').each(function(){
				$(this).click(function(){
					var selVal=$(this).find('a').attr('data-value');
					$(this).parents('.dropdown').find('.dropdown-toggle').html(selVal+'<i class="caret"></i>');
					var dataId = $(this).parents('.choice').attr('data-id');
					for(var i in goods_group1_arr){
					if(goods_group1_arr[i].id==dataId){
						goods_group1_arr[i]['show_num']=selVal;
					}
				}
				});
			});

		}, domHtml.data('goods_group1_arr'));
		rightHtml.find('.delete').click(function(){
			var dataId = $(this).parents('.choice').attr('data-id');
			var current = true;
			var shtml = '';
			for(var i in goods_group1_arr){
				if(goods_group1_arr[i].id==dataId){
					delete goods_group1_arr[i];
				} else {
					var current_class = '';
					if (current) {
						current_class = 'class="current"';
						current = false;
					}
					shtml += '<li><a href="javascript:;" ' + current_class + '>' + goods_group1_arr[i].title + '</a></li>';
				}
			}
			domHtml.data('goods_group1_arr',goods_group1_arr);
			$(this).parents('.choice').remove();
			
			if (shtml.length == 0) {
				shtml = '<li><a href="javascript:;" class="current">商品组一</a></li><li><a href="javascript:;">商品组二</a></li><li><a href="javascript:;">商品组三</a></li>';
			}
			$('.custom-tag-list-side-menu').empty().html(shtml);
		});

		rightHtml.find('.dropdown').toggle(function(){
			$(this).find('.dropdown-menu').show();
		},function(){
			$(this).find('.dropdown-menu').hide();
		});

		rightHtml.find('.dropdown-menu li').each(function(){
			$(this).click(function(){
				var selVal=$(this).find('a').attr('data-value');
				$(this).parents('.dropdown').find('.dropdown-toggle').html(selVal+'<i class="caret"></i>');
				var dataId = $(this).parents('.choice').attr('data-id');
				for(var i in goods_group1_arr){
				if(goods_group1_arr[i].id==dataId){
					goods_group1_arr[i]['show_num']=selVal;
				}
			}
			});
		});

		$('.js-sidebar-region').empty().html(rightHtml);
		domHtml.html(defaultHtml);
	};

    /**
     * 商品分组(头部导航)
     */
    clickArr['goods_group2']=function(){
        if(dom.find('.control-group .sc-goods-list').size() == 0){
            domHtml = dom.find('.control-group');
            domHtml.html('<ul class="sc-goods-list clearfix size-2 card pic"></ul>').data({'goods':[],'size':'2','size_type':'0','buy_btn':'1','buy_btn_type':'1','show_title':'0','price':'1'});
        }else{
            domHtml = dom.find('.control-group');
        }

        rightHtml = $('<div><div class="form-horizontal"><div class="js-meta-region" style="margin-bottom:20px;"><div><div class="control-group"><label class="control-label">选择商品分组：</label><div class="controls"><ul class="module-goods-list clearfix ui-sortable" name="goods"><li><a href="javascript:void(0);" class="js-add-goods add-goods"><i class="icon-add"></i></a></li></ul></div></div><div class="control-group"><label class="control-label">列表样式：</label><div class="controls"><label class="radio inline"><input type="radio" name="size" value="0"/>大图</label><label class="radio inline"><input type="radio" name="size" value="1"/>小图</label><label class="radio inline"><input type="radio" name="size" value="2"/>一大两小</label><label class="radio inline"><input type="radio" name="size" value="3"/>详细列表</label></div></div><div class="control-group"></div></div></div></div></div>');

        var good_data = domHtml.data('goods');

        var html = '';
        for(var i in good_data){
            var item = good_data[i];
            html+= '<li class="sort">'+item.title+'<a class="close-modal js-delete-goods small hide" data-id="'+i+'" title="删除">×</a></li>';
        }
        rightHtml.find('.module-goods-list').prepend(html);
        rightHtml.find('.module-goods-list .sort .js-delete-goods').click(function(){
            $(this).closest('.sort').remove();
            var good_data = domHtml.data('goods');
            delete good_data[$(this).data('id')];
            domHtml.data('goods',good_data);
        });

        //上传商品

        widget_link_box(rightHtml.find('.js-add-goods'),'good_group&only=1',function(result){
            var good_data = domHtml.data('goods');
            if(good_data){
                $.merge(good_data,result);
            }else{
                good_data = result;
            }
            domHtml.data('goods',good_data);
            rightHtml.find('.module-goods-list .sort').remove();
            var html = '';
            for(var i in good_data){
                if (good_data[i]) {
                    if (good_data[i]['title'] && !good_data[i]["name"]) {
                        good_data[i]['name'] = good_data[i]["title"];
                    } else if(good_data[i]['name'] && !good_data[i]["title"]) {
                        good_data[i]['title'] = good_data[i]["name"];
                    }
                    var item = good_data[i];
                    html+= '<li class="sort">'+item.title+'<a class="close-modal js-delete-goods small hide" data-id="'+i+'" title="删除">×</a></li>';
                }
            }
            rightHtml.find('.module-goods-list').prepend(html);
            rightHtml.find('.module-goods-list .sort .js-delete-goods').click(function(){
                $(this).closest('.sort').remove();
                var good_data = domHtml.data('goods');
                delete good_data[$(this).data('id')];
                domHtml.data('goods',good_data);
            });
        },domHtml.data('goods'));

        var change_size_type = true;
        //列表样式
        rightHtml.find('input[name="size"]').change(function(){
            domHtml.data('size',$(this).val());
            switch($(this).val()){
                case '0':
                    $(this).closest('.control-group').next().replaceWith('<div class="control-group"><div class="controls"><div class="controls-card"><div class="controls-card-tab"><label class="radio inline"><input type="radio" name="size_type" value="0"/>卡片样式</label><label class="radio inline"><input type="radio" name="size_type" value="2"/>极简样式</label></div><div class="controls-card-item"><div><label class="checkbox inline"><input type="checkbox" name="buy_btn" value="1" />显示购买按钮</label></div><div style="margin:10px 0 0 20px;"><label class="radio inline"><input type="radio" name="buy_btn_type" value="1" />样式1</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="2"/>样式2</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="3"/>样式3</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="4"/>样式4</label></div></div><div class="controls-card-item"><label class="checkbox inline"><input type="checkbox" name="show_title" value="1"/>显示商品名</label></div><div class="controls-card-item"><label class="checkbox inline"><input type="checkbox" name="price" value="1"/>显示价格</label></div></div></div></div>');

                    if (!change_size_type) {
                        domHtml.data('size_type','0');
                    }

                    break;
                case '1':
                    $(this).closest('.control-group').next().replaceWith('<div class="control-group"><div class="controls"><div class="controls-card"><div class="controls-card-tab"><label class="radio inline"><input type="radio" name="size_type" value="0"/>卡片样式</label><label class="radio inline"><input type="radio" name="size_type" value="1"/>瀑布流</label><label class="radio inline"><input type="radio" name="size_type" value="2"/>极简样式</label></div><div class="controls-card-item"><div><label class="checkbox inline"><input type="checkbox" name="buy_btn" value="1" />显示购买按钮</label></div><div style="margin:10px 0 0 20px;"><label class="radio inline"><input type="radio" name="buy_btn_type" value="1" />样式1</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="2"/>样式2</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="3"/>样式3</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="4"/>样式4</label></div></div><div class="controls-card-item"><label class="checkbox inline"><input type="checkbox" name="show_title" value="1"/>显示商品名</label></div><div class="controls-card-item"><label class="checkbox inline"><input type="checkbox" name="price" value="1"/>显示价格</label></div></div></div></div>');

                    if (!change_size_type) {
                        domHtml.data('size_type','0');
                    }

                    break;
                case '2':
                    $(this).closest('.control-group').next().replaceWith('<div class="control-group"><div class="controls"><div class="controls-card"><div class="controls-card-tab"><label class="radio inline"><input type="radio" name="size_type" value="0"/>卡片样式</label><label class="radio inline"><input type="radio" name="size_type" value="2"/>极简样式</label></div><div class="controls-card-item"><div><label class="checkbox inline"><input type="checkbox" name="buy_btn" value="1" />显示购买按钮</label></div><div style="margin:10px 0 0 20px;"><label class="radio inline"><input type="radio" name="buy_btn_type" value="1" />样式1</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="2"/>样式2</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="3"/>样式3</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="4"/>样式4</label></div></div><div class="controls-card-item"><label class="checkbox inline"><input type="checkbox" name="show_title" value="1"/>显示商品名 (小图不显示名称)</label></div><div class="controls-card-item"><label class="checkbox inline"><input type="checkbox" name="price" value="1"/>显示价格</label></div></div></div></div>');

                    if (!change_size_type) {
                        domHtml.data('size_type','0');
                    }

                    break;
                case '3':
                    $(this).closest('.control-group').next().replaceWith('<div class="control-group"><div class="controls"><div class="controls-card"><div class="controls-card-tab"><label class="radio inline"><input type="radio" name="size_type" value="0"/>卡片样式</label><label class="radio inline"><input type="radio" name="size_type" value="2"/>极简样式</label></div><div class="controls-card-item"><div><label class="checkbox inline"><input type="checkbox" name="buy_btn" value="1" />显示购买按钮</label></div><div style="margin:10px 0 0 20px;"><label class="radio inline"><input type="radio" name="buy_btn_type" value="1" />样式1</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="2"/>样式2</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="3"/>样式3</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="4"/>样式4</label></div></div></div></div></div>');

                    if (!change_size_type) {
                        domHtml.data('size_type','0');
                    }

                    break;
            }

            rightHtml.find('input[name="size_type"][value="'+domHtml.data('size_type')+'"]').prop('checked',true);

            if(domHtml.data('buy_btn') != '1'){
                rightHtml.find('input[name="buy_btn"]').closest('div').next().remove();
            }else{
                rightHtml.find('input[name="buy_btn"]').prop('checked',true);
                rightHtml.find('input[name="buy_btn_type"][value="'+domHtml.data('buy_btn_type')+'"]').prop('checked',true);
            }

            rightHtml.find('input[name="show_title"]').prop('checked',(domHtml.data('show_title') == '1' ? true : false));
            rightHtml.find('input[name="price"]').prop('checked',(domHtml.data('price') == '1' ? true : false));

            //列表样式属性
            rightHtml.find('input[name="size_type"]').change(function(){
                domHtml.data('size_type',$(this).val());
                if(domHtml.data('size') != '3'){
                    if($(this).val() == '2'){
                        $(this).closest('.controls-card-tab').next().hide();
                    }else{
                        $(this).closest('.controls-card-tab').next().show();
                    }
                }else{
                    /*
                    if($(this).val() == '2'){
                        if(domHtml.data('buy_btn_type') == '3'){
                            domHtml.data('buy_btn_type','1');
                            rightHtml.find('input[name="buy_btn_type"][value="1"]').prop('checked',true);
                        }
                        rightHtml.find('input[name="buy_btn_type"][value="3"]').closest('label').show();
                    }else{
                        rightHtml.find('input[name="buy_btn_type"][value="3"]').closest('label').show();
                    }
                    */
                }
                changeStyleContent();
            }).each(function(i,item){
                if($(item).val() == domHtml.data('size_type')){
                    $(item).prop('checked',true).change();
                }
            });
            rightHtml.find('input[name="buy_btn"]').change(function(){
                if($(this).prop('checked')){
                    $(this).closest('div').next().show();
                    domHtml.data('buy_btn','1');
                }else{
                    $(this).closest('div').next().hide();
                    domHtml.data('buy_btn','0');
                }
                changeStyleContent();
            });
            rightHtml.find('input[name="buy_btn_type"]').change(function(){
                domHtml.data('buy_btn_type',$(this).val());
                changeStyleContent();
            });

            rightHtml.find('input[name="show_title"]').change(function(){
                domHtml.data('show_title',$(this).prop('checked') ? '1' : '0');
                changeStyleContent();
            });
            rightHtml.find('input[name="price"]').change(function(){
                domHtml.data('price',$(this).prop('checked') ? '1' : '0');
                changeStyleContent();
            });

            changeStyleContent();
        }).each(function(i,item){
            if($(item).val() == domHtml.data('size')){
                $(item).prop('checked',true).change();
            }
        });


        function changeStyleContent(){
            var html = '';
            switch(domHtml.data('size')){
                case '0':
                    switch(domHtml.data('size_type')){
                        case '0':
                            html  = '<ul class="sc-goods-list clearfix size-2 card pic"><li class="goods-card big-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li><li class="goods-card big-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li><li class="goods-card big-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li></ul>';
                            break;
                        case '2':
                            html = '<ul class="sc-goods-list clearfix size-2 normal pic"><li class="goods-card big-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            html += '</a></li><li class="goods-card big-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            html += '</a></li><li class="goods-card big-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            html += '</a></li></ul>';
                            break;
                    }
                    break;
                case '1':
                    switch(domHtml.data('size_type')){
                        case '0':
                            html  = '<ul class="sc-goods-list clearfix size-1 card pic"><li class="goods-card small-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li><li class="goods-card small-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li><li class="goods-card small-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/third_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥32.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li><li class="goods-card small-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li></ul>';
                            rightHtml.find("[name='show_title']").parent().show();
                            break;
                        case '1':
                            html  = '<ul class="sc-goods-list clearfix size-1 waterfall pic">';
                            html += '<li class="sc-waterfall-half clearfix">';
                            html += '<ul class="clearfix">';
                            html += '<li class="goods-card goods-list small-pic waterfall"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg" style="height:145px;"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li>';
                            html += '<li class="goods-card goods-list small-pic waterfall"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/third_demo_goods.jpg" style="height:205px;"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥32.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li>';
                            html += '</ul>';
                            html += '</li>';
                            html += '<li class="sc-waterfall-half clearfix">';
                            html += '<ul class="clearfix">';
                            html += '<li class="goods-card goods-list small-pic waterfall"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg" style="height:155px;"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li>';
                            html += '<li class="goods-card goods-list small-pic waterfall"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg" style="height:175px;"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li>';
                            html += '</ul>';
                            html += '</li>';
                            html += '</ul>';
                            break;
                        case '2':
                            html  = '<ul class="sc-goods-list clearfix size-1 normal pic"><li class="goods-card small-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div>';
                            if(domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            html += '</a></li><li class="goods-card small-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div>';
                            if(domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            html += '</a></li><li class="goods-card small-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/third_demo_goods.jpg"/></div>';
                            if(domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥32.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            html += '</a></li><li class="goods-card small-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div>';
                            if(domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            html += '</a></li></ul>';
                            rightHtml.find("[name='show_title']").parent().hide();
                            break;
                    }
                    break;
                case '2':
                    switch(domHtml.data('size_type')){
                        case '0':
                            html  = '<ul class="sc-goods-list clearfix size-2 card pic"><li class="goods-card big-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li><li class="goods-card small-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){

                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li><li class="goods-card small-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</a></li></ul>';
                            break;
                        case '2':
                            html  = '<ul class="sc-goods-list clearfix size-2 normal pic"><li class="goods-card big-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div>';
                            if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            html += '</a></li><li class="goods-card small-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div>';
                            if(domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            html += '</a></li><li class="goods-card small-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div>';
                            if(domHtml.data('price') == '1'){
                                html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
                            }
                            html += '</a></li></ul>';
                            break;
                    }
                    break;
                case '3':
                    switch(domHtml.data('size_type')){
                        case '0':
                            html  = '<ul class="sc-goods-list clearfix size-3 card list"><li class="goods-card card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div><div class="info"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p>';
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</div>';
                            html += '</a></li><li class="goods-card card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div><div class="info"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p>';
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</div>';
                            html += '</a></li><li class="goods-card card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div><div class="info"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p>';
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</div>';
                            html += '</a></li></ul>';
                            break;
                        case '2':
                            html  = '<ul class="sc-goods-list clearfix size-3 normal list"><li class="goods-card normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div><div class="info"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p>';
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</div>';
                            html += '</a></li><li class="goods-card normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div><div class="info"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p>';
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</div>';
                            html += '</a></li><li class="goods-card normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div><div class="info"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p>';
                            if(domHtml.data('buy_btn') == '1'){
                                html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
                            }
                            html += '</div>';
                            html += '</a></li></ul>';
                            break;
                    }
                    break;
            }
            domHtml.find('.sc-goods-list').replaceWith(html);
        }

        $('.js-sidebar-region').empty().html(rightHtml);
    };


    /**
     * 外卖模块
     */
    clickArr['goods_group3']=function(){
        var rightHtml=$('<div><form class="form-horizontal" novalidate=""><div class="control-group"><label class="control-label">展示样式：</label><div class="controls"><label class="radio inline"><input type="radio" name="show_type" value="1" checked="checked">显示商家详情</label><label class="radio inline"><input type="radio" name="show_type" value="2">不显示商家详情</label></div></div><div class="control-group"><label class="control-label">公告：</label><div class="controls"><input type="text" name="notice_content" maxlength="100"></div></div><div class="control-group options js-add-subentry" style="display: block;"><a class="add-option js-add-option" href="javascript:void(0);"><i class="icon-add"></i> 添加商品分组</a></div><div class="control-group"><p class="app-component-desc help-desc">1. 单页面中外卖模块具有唯一性和永久置底性</p><p class="app-component-desc help-desc" style="line-height:18px;">2. 选择商品来源后，左侧实时预览暂不支持显示其包含的商品数据</p></div></form></div>');
        if (dom.find('.control-group .goods_group3').size() == 0) {
            domHtml = $('<ul class="goods_group3 clearfix"></ul>');
            domHtml.data({'goods_group3_arr':[]});
            dom.find('.control-group').prepend(domHtml);
            var defaultHtml='<div class="custom-tag-list clearfix goods_group3"><div class="goods_group3_notice"><span>公告：<span class="show_notice"></span></span></div><div class="goods_group3_title"><ul><li class="active">商品</li><li>详情</li></ul></div><div class="goods_group3_notice"><span>公告：<span class="show_notice"></span></span></div><div class="custom-tag-list-menu-block js-collection-region" style="min-height: 323px;"><ul class="custom-tag-list-side-menu"><li><a href="javascript:;" class="current">商品组一</a><a href="javascript:;">商品组二</a><a href="javascript:;">商品组三</a></li></ul></div><div class="custom-tag-list-goods"><ul class="custom-tag-list-goods-list"><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="./upload/images/kd5.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span><a class="custom-tag-list-goods-buy" href="javascript:void(0)"><span></span></a></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="upload/images/kd1.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span><a class="custom-tag-list-goods-buy" href="javascript:void(0)"><span></span></a></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="./upload/images/kd7.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span><a class="custom-tag-list-goods-buy" href="javascript:void(0)"><span></span></a></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="./upload/images/kd4.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">￥100.00</span><a class="custom-tag-list-goods-buy" href="javascript:void(0)"><span></span></a></div></li></ul></div><img src="template/user/default/images/goods_group_cart.jpg" style="width: 100%;" /></div>';
        } else {
            domHtml = dom.find('.control-group .goods_group3');
            if (typeof domHtml.data('goods_group3_arr') == 'undefined') {
                domHtml.data({'goods_group3_arr': []});
            }
        }

        var goods_group3_arr = domHtml.data('goods_group3_arr');
        var show_type = typeof domHtml.data('show_type') == 'undefined' ? 1 : domHtml.data('show_type');

        if (show_type == 2) {
            rightHtml.find('input').eq(1).prop('checked', true);
        }

        if(domHtml.data('notice_content') != 'undefined'){
            rightHtml.find('input').eq(2).val(domHtml.data('notice_content'));
        }

        var html='';
        for(var i in goods_group3_arr){
            goods_group3_arr[i]['show_num'] = goods_group3_arr[i].show_num?goods_group3_arr[i].show_num:10;
            goods_group3_arr[i]['show_num'] = goods_group3_arr[i]['show_num'] == '0'?'不限制':goods_group3_arr[i]['show_num'];
            html+='<li class="choice"  data-id="'+goods_group3_arr[i].id+'"><div class="edit-tag-list"><div class="tag-source"><div class="control-group"><label class="control-label pull-left">商品来源：</label><div class="controls pull-left"><a href="./user.php?c=goods&a=category#edit/'+ goods_group3_arr[i].id+'" target="_blank" class="tag-title new-window">'+goods_group3_arr[i].title+'</a><input type="hidden" name="title"></div></div></div><div class="split-line"></div><div class="goods-number"><span>显示商品数量</span><div class="dropdown hover pull-right"><a class="dropdown-toggle" data-toggle="dropdown" href="javascript:void(0);">'+(goods_group3_arr[i].show_num?goods_group3_arr[i].show_num:10)+' <i class="caret"></i></a><ul class="dropdown-menu" role="menu"><li><a class="js-goods-number" data-value="0" href="javascript:void(0);">不限制</a></li><li><a class="js-goods-number" data-value="5" href="javascript:void(0);">5</a></li><li><a class="js-goods-number" data-value="10" href="javascript:void(0);">10</a></li><li><a class="js-goods-number" data-value="15" href="javascript:void(0);">15</a></li><li><a class="js-goods-number" data-value="30" href="javascript:void(0);">30</a></li></ul></div></div></div><div class="actions"><span class="action delete close-modal" title="删除">×</span></div></li>';
        }


        rightHtml.find('.form-horizontal .js-add-subentry').prepend(html);


        //上传商品
        //widget_link_box(rightHtml.find('.js-add-option'),'goodcat',function(result){
        widget_link_box(rightHtml.find('.js-add-option'),'good_group&only=1',function(result){
            var goods_group3_arr = domHtml.data('goods_group3_arr');
            if(goods_group3_arr){
                //alert(1);
                $.merge(goods_group3_arr,result);
            }else{
                //alert(2);
                goods_group3_arr = result;
            }

            domHtml.data('goods_group3_arr',goods_group3_arr);
            rightHtml.find('.module-goods-list .sort').remove();


            var html = '';
            var shtml='';

            for(var i in goods_group3_arr){

                if (goods_group3_arr[i]['title'] && !goods_group3_arr[i]["name"]) {
                    goods_group3_arr[i]['name'] = goods_group3_arr[i]["title"];
                } else if(goods_group3_arr[i]['name'] && !goods_group3_arr[i]["title"]) {
                    goods_group3_arr[i]['title'] = goods_group3_arr[i]["name"];
                }
                var item = goods_group3_arr[i];
                item['show_num'] = item['show_num']?item['show_num']:10;
                item['show_num'] = item['show_num'] == '0'?'不限制':item['show_num'];

                html+='<li class="choice"  data-id="'+item.id+'"><div class="edit-tag-list"><div class="tag-source"><div class="control-group"><label class="control-label pull-left">商品来源：</label><div class="controls pull-left"><a href="" target="_blank" class="tag-title new-window">'+item.title+'</a><input type="hidden" name="title"></div></div></div><div class="split-line"></div><div class="goods-number"><span>显示商品数量</span><div class="dropdown hover pull-right"><a class="dropdown-toggle" data-toggle="dropdown" href="javascript:void(0);">'+(item.show_num?item.show_num:10)+' <i class="caret"></i></a><ul class="dropdown-menu" role="menu"><li><a class="js-goods-number" data-value="0" href="javascript:void(0);">不限制</a></li><li><a class="js-goods-number" data-value="5" href="javascript:void(0);">5</a></li><li><a class="js-goods-number" data-value="10" href="javascript:void(0);">10</a></li><li><a class="js-goods-number" data-value="15" href="javascript:void(0);">15</a></li><li><a class="js-goods-number" data-value="30" href="javascript:void(0);">30</a></li></ul></div></div></div><div class="actions"><span class="action delete close-modal" title="删除">×</span></div></li>';
                shtml+='<li data-title="'+item["title"]+'" id="'+item["id"]+'"><a href="javascript:;" ' + (i == 0 ? 'class="current"' : '') + '><span>'+item["title"]+'</span></a></li>';
            }

            if (shtml.length == 0) {
                shtml = '<li><a href="javascript:;" class="current">商品组一</a></li><li><a href="javascript:;">商品组二</a></li><li><a href="javascript:;">商品组三</a></li>';
            }

            $('.custom-tag-list-side-menu').empty().html(shtml);
            //rightHtml.find('.form-horizontal .js-add-subentry').empty().prepend(html);
            rightHtml.find('.form-horizontal .js-add-subentry li').remove();
            rightHtml.find('.form-horizontal .js-add-subentry').prepend(html);
            rightHtml.find('.delete').click(function(){
                var dataId = $(this).parents('.choice').attr('data-id');
                var current = true;
                var shtml = '';
                for(var i in goods_group3_arr){
                    if(goods_group3_arr[i].id==dataId){
                        delete goods_group3_arr[i];
                    } else {
                        var current_class = '';
                        if (current) {
                            current_class = 'class="current"';
                            current = false;
                        }
                        shtml += '<li><a href="javascript:;" ' + current_class + '>' + goods_group3_arr[i].title + '</a></li>';
                    }
                }
                domHtml.data('goods_group3_arr',goods_group3_arr);
                $(this).parents('.choice').remove();

                if (shtml.length == 0) {
                    shtml = '<li><a href="javascript:;" class="current">商品组一</a></li><li><a href="javascript:;">商品组二</a></li><li><a href="javascript:;">商品组三</a></li>';
                }
                $('.custom-tag-list-side-menu').empty().html(shtml);
            });

            rightHtml.find('.dropdown').toggle(function(){
                $(this).find('.dropdown-menu').show();
            },function(){
                $(this).find('.dropdown-menu').hide();
            });

            rightHtml.find('.dropdown-menu li').each(function(){
                $(this).click(function(){
                    var selVal=$(this).find('a').attr('data-value');
                    if(selVal == 0){
                        $(this).parents('.dropdown').find('.dropdown-toggle').html('不限制'+'<i class="caret"></i>');
                    }else{
                        $(this).parents('.dropdown').find('.dropdown-toggle').html(selVal+'<i class="caret"></i>');
                    }
                    var dataId = $(this).parents('.choice').attr('data-id');
                    for(var i in goods_group3_arr){
                        if(goods_group3_arr[i].id==dataId){
                            goods_group3_arr[i]['show_num']=selVal;
                        }
                    }
                });
            });

        }, domHtml.data('goods_group3_arr'));
        rightHtml.find('.delete').click(function(){
            var dataId = $(this).parents('.choice').attr('data-id');

            var shtml = '';
            var current = true;
            for(var i in goods_group3_arr){
                if(goods_group3_arr[i].id==dataId){
                    delete goods_group3_arr[i];
                } else {
                    var current_class = '';
                    if (current) {
                        current_class = 'class="current"';
                        current = false;
                    }
                    shtml += '<li><a href="javascript:;" ' + current_class + '>' + goods_group3_arr[i].title + '</a></li>';
                }
            }
            domHtml.data('goods_group3_arr',goods_group3_arr);
            $(this).parents('.choice').remove();

            if (shtml.length == 0) {
                shtml = '<li><a href="javascript:;" class="current">商品组一</a></li><li><a href="javascript:;">商品组二</a></li><li><a href="javascript:;">商品组三</a></li>';
            }

            $('.custom-tag-list-side-menu').empty().html(shtml);
        });

        rightHtml.find('.dropdown').toggle(function(){
            $(this).find('.dropdown-menu').show();
        },function(){
            $(this).find('.dropdown-menu').hide();
        });

        rightHtml.find('.dropdown-menu li').each(function(){
            $(this).click(function(){
                var selVal=$(this).find('a').attr('data-value');
                if(selVal == 0){
                    $(this).parents('.dropdown').find('.dropdown-toggle').html('不限制'+'<i class="caret"></i>');
                }else{
                    $(this).parents('.dropdown').find('.dropdown-toggle').html(selVal+'<i class="caret"></i>');
                }
                var dataId = $(this).parents('.choice').attr('data-id');
                for(var i in goods_group3_arr){
                    if(goods_group3_arr[i].id==dataId){
                        goods_group3_arr[i]['show_num']=selVal;
                    }
                }
            });
        });
        // 展示样式
        rightHtml.find('input[name="show_type"]').change(function () {
            var t = $(this).val();
            domHtml.data('show_type', t);
            if (t == 1) {
                $('.goods_group3_title').show();
            } else {
                $('.goods_group3_title').hide();
            }
        });

        //失去焦点
        rightHtml.find('input[name="notice_content"]').blur(function(){
        	console.log('notice_content', $(this).val());
            domHtml.data('notice_content',$(this).val());
            $('.goods_group3_notice span.show_notice').html(($(this).val().length != 0 ? $(this).val() : biaotis));
        });

        $('.js-sidebar-region').empty().html(rightHtml);
        domHtml.html(defaultHtml);
    };

	/**
	 * 商品
	 */
	clickArr['goods'] = function(){
        if(dom.find('.control-group .sc-goods-list').size() == 0){
            domHtml = dom.find('.control-group');
            domHtml.html('<ul class="sc-goods-list clearfix size-2 card pic"></ul>').data({'goods':[],'size':'2','size_type':'0','buy_btn':'1','buy_btn_type':'1','show_title':'0','price':'1'});
        }else{
            domHtml = dom.find('.control-group');
        }

		rightHtml = $('<div><div class="form-horizontal"><div class="js-meta-region" style="margin-bottom:20px;"><div><div class="control-group"><label class="control-label">选择商品：</label><div class="controls"><ul id="SortContaint" class="module-goods-list clearfix ui-sortable" name="goods"></ul><ul class="module-goods-lists clearfix ui-sortable" ><li class="no-selected" style="width: 90px;"><a href="javascript:void(0);" class="js-add-goods add-goods"><i class="icon-add"></i>选择商品</a></li><li class="no-selected" style="width: 90px;"><a href="javascript:void(0);" class="js-add-goods-group add-goods-group"><i class="icon-add"></i>选择分组</a></li></ul><span id="msg" style="font-size:12px;opacity: 0.6;">温馨提示：拖动商品图片，可以更换位置</span></div></div><div class="control-group"><label class="control-label">列表样式：</label><div class="controls"><label class="radio inline"><input type="radio" name="size" value="0"/>大图</label><label class="radio inline"><input type="radio" name="size" value="1"/>小图</label><label class="radio inline"><input type="radio" name="size" value="2"/>一大两小</label><label class="radio inline"><input type="radio" name="size" value="3"/>详细列表</label></div></div><div class="control-group"></div></div></div></div></div>');

		var good_data = domHtml.data('goods');
		var html = '';
		var goods_ids = [];
		var goods_ids_delete = [];
		for(var i in good_data){
			var item = good_data[i];
				goods_ids.push(item.id);
				html+= '<li class="sort SortItem"><a href="'+item.url+'" target="_blank"><img price="'+item.price+'" src="'+item.image+'" alt="'+item.title+'" title="'+item.title+'" width="50" height="50"></a><a class="close-modal js-delete-goods small hide" data-id="'+i+'" data-product_id="'+item.id+'" title="删除">×</a></li>';
		}


		rightHtml.find('.module-goods-list').prepend(html);
		rightHtml.find('.module-goods-list .sort .js-delete-goods').click(function(){
			$(this).closest('.sort').remove();
			var good_data = domHtml.data('goods');
			delete good_data[$(this).data('id')];
			domHtml.data('goods',good_data);
		});
		///////////////////////////////////////////////////////
		
		if(rightHtml.find("#SortContaint li").length>0) {	
			rightHtml.find('#SortContaint').sortable().bind('sortupdate', function(j) {
			rightHtml.find("#msg").html('温馨提示：<font style="color:#f00">商品位置更新成功,保存后生效</font>').delay(1000).fadeIn(0,function(){
				rightHtml.find("#msg").html('温馨提示：拖动商品图片，可以更换位置');
			});
			var good_data = domHtml.data('goods');

				var good_data2 = [];
			
				rightHtml.find("#SortContaint li").each(function(i,item) {
					//if(i == rightHtml.find("#SortContaint li").length - 1) return false;
					var ids = $(item).find(".js-delete-goods").data("id");
					var tmp = {};
					tmp.id = $(item).find('.close-modal').data('product_id');
					tmp.image = $(item).find("img").attr("src");
					tmp.price =  $(item).find("img").attr("price");
					tmp.title = $(item).find("img").attr("title");
					tmp.url = $(item).find("a").attr("href");
					good_data2[i] = tmp;
				})
				domHtml.data('goods',good_data2);
			});				
		}

		
		

		//////////////////////////////////////////////////////
		//上传商品

		widget_link_box(rightHtml.find('.js-add-goods'),'good',function(result){
			var good_data = domHtml.data('goods');
			if(good_data){
				$.merge(good_data,result);
			}else{
				good_data = result;
			}
			domHtml.data('goods',good_data);
			rightHtml.find('.module-goods-list .sort').remove();
			var html = '';
			for(var i in good_data){
				var item = good_data[i];
				html+= '<li class="sort"><a href="'+item.url+'" target="_blank"><img  price="'+item.price+'" src="'+item.image+'" alt="'+item.title+'" title="'+item.title+'" width="50" height="50"></a><a class="close-modal js-delete-goods small hide" data-id="'+i+'"  data-product_id="'+item.id+'"  title="删除">×</a></li>';
			}
			rightHtml.find('.module-goods-list').prepend(html);
			rightHtml.find('.module-goods-list .sort .js-delete-goods').click(function(){
				$(this).closest('.sort').remove();
				var good_data = domHtml.data('goods');
				delete good_data[$(this).data('id')];
				domHtml.data('goods',good_data);
			});
			
			
			
			if(rightHtml.find("#SortContaint li").length>0) {	
			  rightHtml.find('#SortContaint').sortable().bind('sortupdate', function(j) {
				rightHtml.find("#msg").html('<font style="font-size:11px;font-weight:700;color:#f00">商品位置更新成功,保存后生效！</font>').fadeIn(200).delay(1000).fadeOut(200);
				var good_data = domHtml.data('goods');

				var good_data2 = [];
			
				rightHtml.find("#SortContaint li").each(function(i,item) {
					//if(i == rightHtml.find("#SortContaint li").length - 1) return false;
						var ids = $(item).find(".js-delete-goods").data("id");

						var tmp = {};
						tmp.id = $(item).find('.close-modal').data('product_id');
						tmp.image = $(item).find("img").attr("src");
						tmp.price =  $(item).find("img").attr("price");
						tmp.title = $(item).find("img").attr("title");
						tmp.url = $(item).find("a").attr("href");
						good_data2[i] = tmp;
					});
					domHtml.data('goods',good_data2);
				});				
			}	
		},domHtml.data('goods'));

		widget_link_box(rightHtml.find('.js-add-goods-group'),'good_group',function(result){
			var good_data = domHtml.data('goods');
			if(good_data){
				$.merge(good_data,result);
			}else{
				good_data = result;
			}
			domHtml.data('goods',good_data);
			rightHtml.find('.module-goods-list .sort').remove();
			var html = '';
			for(var i in good_data){
				var item = good_data[i];
				html+= '<li class="sort"><a href="'+item.url+'" target="_blank"><img  price="'+item.price+'" src="'+item.image+'" alt="'+item.title+'" title="'+item.title+'" width="50" height="50"></a><a class="close-modal js-delete-goods small hide" data-id="'+i+'"  data-product_id="'+item.id+'"  title="删除">×</a></li>';
			}
			rightHtml.find('.module-goods-list').prepend(html);
			rightHtml.find('.module-goods-list .sort .js-delete-goods').click(function(){
				$(this).closest('.sort').remove();
				var good_data = domHtml.data('goods');
				delete good_data[$(this).data('id')];
				domHtml.data('goods',good_data);
			});
			
			
			
			if(rightHtml.find("#SortContaint li").length>0) {	
			  rightHtml.find('#SortContaint').sortable().bind('sortupdate', function(j) {
				rightHtml.find("#msg").html('<font style="font-size:11px;font-weight:700;color:#f00">商品位置更新成功,保存后生效！</font>').fadeIn(200).delay(1000).fadeOut(200);
				var good_data = domHtml.data('goods');

				var good_data2 = [];
			
				rightHtml.find("#SortContaint li").each(function(i,item) {
					//if(i == rightHtml.find("#SortContaint li").length - 1) return false;
						var ids = $(item).find(".js-delete-goods").data("id");

						var tmp = {};
						tmp.id = $(item).find('.close-modal').data('product_id');
						tmp.image = $(item).find("img").attr("src");
						tmp.price =  $(item).find("img").attr("price");
						tmp.title = $(item).find("img").attr("title");
						tmp.url = $(item).find("a").attr("href");
						good_data2[i] = tmp;
					});
					domHtml.data('goods',good_data2);
				});				
			}	
		},domHtml.data('goods'));
		
		var change_size_type = true;
		//列表样式
		rightHtml.find('input[name="size"]').change(function(){
			domHtml.data('size',$(this).val());
			switch($(this).val()){
				case '0':
					$(this).closest('.control-group').next().replaceWith('<div class="control-group"><div class="controls"><div class="controls-card"><div class="controls-card-tab"><label class="radio inline"><input type="radio" name="size_type" value="0"/>卡片样式</label><label class="radio inline"><input type="radio" name="size_type" value="2"/>极简样式</label></div><div class="controls-card-item"><div><label class="checkbox inline"><input type="checkbox" name="buy_btn" value="1" />显示购买按钮</label></div><div style="margin:10px 0 0 20px;"><label class="radio inline"><input type="radio" name="buy_btn_type" value="1" />样式1</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="2"/>样式2</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="3"/>样式3</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="4"/>样式4</label></div></div><div class="controls-card-item"><label class="checkbox inline"><input type="checkbox" name="show_title" value="1"/>显示商品名</label></div><div class="controls-card-item"><label class="checkbox inline"><input type="checkbox" name="price" value="1"/>显示价格</label></div></div></div></div>');
					
					if (!change_size_type) {
						domHtml.data('size_type','0');
					}

					break;
				case '1':
					$(this).closest('.control-group').next().replaceWith('<div class="control-group"><div class="controls"><div class="controls-card"><div class="controls-card-tab"><label class="radio inline"><input type="radio" name="size_type" value="0"/>卡片样式</label><label class="radio inline"><input type="radio" name="size_type" value="1"/>瀑布流</label><label class="radio inline"><input type="radio" name="size_type" value="2"/>极简样式</label></div><div class="controls-card-item"><div><label class="checkbox inline"><input type="checkbox" name="buy_btn" value="1" />显示购买按钮</label></div><div style="margin:10px 0 0 20px;"><label class="radio inline"><input type="radio" name="buy_btn_type" value="1" />样式1</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="2"/>样式2</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="3"/>样式3</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="4"/>样式4</label></div></div><div class="controls-card-item"><label class="checkbox inline"><input type="checkbox" name="show_title" value="1"/>显示商品名</label></div><div class="controls-card-item"><label class="checkbox inline"><input type="checkbox" name="price" value="1"/>显示价格</label></div></div></div></div>');
					
					if (!change_size_type) {
						domHtml.data('size_type','0');
					}
					
					break;
				case '2':
					$(this).closest('.control-group').next().replaceWith('<div class="control-group"><div class="controls"><div class="controls-card"><div class="controls-card-tab"><label class="radio inline"><input type="radio" name="size_type" value="0"/>卡片样式</label><label class="radio inline"><input type="radio" name="size_type" value="2"/>极简样式</label></div><div class="controls-card-item"><div><label class="checkbox inline"><input type="checkbox" name="buy_btn" value="1" />显示购买按钮</label></div><div style="margin:10px 0 0 20px;"><label class="radio inline"><input type="radio" name="buy_btn_type" value="1" />样式1</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="2"/>样式2</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="3"/>样式3</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="4"/>样式4</label></div></div><div class="controls-card-item"><label class="checkbox inline"><input type="checkbox" name="show_title" value="1"/>显示商品名 (小图不显示名称)</label></div><div class="controls-card-item"><label class="checkbox inline"><input type="checkbox" name="price" value="1"/>显示价格</label></div></div></div></div>');
					
					if (!change_size_type) {
						domHtml.data('size_type','0');
					}
					
					break;
				case '3':
					$(this).closest('.control-group').next().replaceWith('<div class="control-group"><div class="controls"><div class="controls-card"><div class="controls-card-tab"><label class="radio inline"><input type="radio" name="size_type" value="0"/>卡片样式</label><label class="radio inline"><input type="radio" name="size_type" value="2"/>极简样式</label></div><div class="controls-card-item"><div><label class="checkbox inline"><input type="checkbox" name="buy_btn" value="1" />显示购买按钮</label></div><div style="margin:10px 0 0 20px;"><label class="radio inline"><input type="radio" name="buy_btn_type" value="1" />样式1</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="2"/>样式2</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="3"/>样式3</label><label class="radio inline"><input type="radio" name="buy_btn_type" value="4"/>样式4</label></div></div></div></div></div>');
					
					if (!change_size_type) {
						domHtml.data('size_type','0');
					}
					
					break;
			}
			rightHtml.find('input[name="size_type"][value="'+domHtml.data('size_type')+'"]').prop('checked',true);
			change_size_type = false;

			if(domHtml.data('buy_btn') != '1'){
				rightHtml.find('input[name="buy_btn"]').closest('div').next().remove();
			}else{
				rightHtml.find('input[name="buy_btn"]').prop('checked',true);
				rightHtml.find('input[name="buy_btn_type"][value="'+domHtml.data('buy_btn_type')+'"]').prop('checked',true);
			}

			rightHtml.find('input[name="show_title"]').prop('checked',(domHtml.data('show_title') == '1' ? true : false));
			rightHtml.find('input[name="price"]').prop('checked',(domHtml.data('price') == '1' ? true : false));

			//列表样式属性
			rightHtml.find('input[name="size_type"]').change(function(){
				domHtml.data('size_type',$(this).val());
				if(domHtml.data('size') != '3'){
					if($(this).val() == '2'){
						$(this).closest('.controls-card-tab').next().hide();
						if(domHtml.data('size') == '1'){
							$(this).closest('.controls-card-tab').next().next().hide();
						}
					}else{
						$(this).closest('.controls-card-tab').next().show();
					}
				}else{
					if($(this).val() == '2'){
						if(domHtml.data('buy_btn_type') == '3'){
							domHtml.data('buy_btn_type','1');
							rightHtml.find('input[name="buy_btn_type"][value="1"]').prop('checked',true);
						}
						rightHtml.find('input[name="buy_btn_type"][value="3"]').closest('label').hide();
					}else{
						rightHtml.find('input[name="buy_btn_type"][value="3"]').closest('label').show();
					}
				}
				changeStyleContent();
			}).each(function(i,item){
				if($(item).val() == domHtml.data('size_type')){
					$(item).prop('checked',true).change();
				}
			});
			rightHtml.find('input[name="buy_btn"]').change(function(){
				if($(this).prop('checked')){
					$(this).closest('div').next().show();
					domHtml.data('buy_btn','1');
				}else{
					$(this).closest('div').next().hide();
					domHtml.data('buy_btn','0');
				}
				changeStyleContent();
			});
			rightHtml.find('input[name="buy_btn_type"]').change(function(){
				domHtml.data('buy_btn_type',$(this).val());
				changeStyleContent();
			});

			rightHtml.find('input[name="show_title"]').change(function(){
				domHtml.data('show_title',$(this).prop('checked') ? '1' : '0');
				changeStyleContent();
			});
			rightHtml.find('input[name="price"]').change(function(){
				domHtml.data('price',$(this).prop('checked') ? '1' : '0');
				changeStyleContent();
			});

			changeStyleContent();
		}).each(function(i,item){
			if($(item).val() == domHtml.data('size')){
				$(item).prop('checked',true).change();
			}
		});

		/**
		 *
		 */
		function changeStyleContent(){
			var html = '';
			switch(domHtml.data('size')){
				case '0':
					switch(domHtml.data('size_type')){
						case '0':
							html  = '<ul class="sc-goods-list clearfix size-2 card pic"><li class="goods-card big-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</a></li><li class="goods-card big-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</a></li><li class="goods-card big-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</a></li></ul>';
							break;
						case '2':
							html = '<ul class="sc-goods-list clearfix size-2 normal pic"><li class="goods-card big-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							html += '</a></li><li class="goods-card big-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							html += '</a></li><li class="goods-card big-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							html += '</a></li></ul>';
							break;
					}
					break;
				case '1':
					switch(domHtml.data('size_type')){
						case '0':
							html  = '<ul class="sc-goods-list clearfix size-1 card pic"><li class="goods-card small-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</a></li><li class="goods-card small-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</a></li><li class="goods-card small-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/third_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥32.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</a></li><li class="goods-card small-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</a></li></ul>';
							break;
						case '1':
							html  = '<ul class="sc-goods-list clearfix size-1 waterfall pic">';
								html += '<li class="sc-waterfall-half clearfix">';
									html += '<ul class="clearfix">';
										html += '<li class="goods-card goods-list small-pic waterfall"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg" style="height:145px;"/></div>';
										if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
											html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
										}
										if(domHtml.data('buy_btn') == '1'){
											html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
										}
										html += '</a></li>';
										html += '<li class="goods-card goods-list small-pic waterfall"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/third_demo_goods.jpg" style="height:205px;"/></div>';
										if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
											html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥32.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
										}
										if(domHtml.data('buy_btn') == '1'){
											html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
										}
										html += '</a></li>';
									html += '</ul>';
								html += '</li>';
								html += '<li class="sc-waterfall-half clearfix">';
									html += '<ul class="clearfix">';
										html += '<li class="goods-card goods-list small-pic waterfall"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg" style="height:155px;"/></div>';
										if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
											html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
										}
										if(domHtml.data('buy_btn') == '1'){
											html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
										}
										html += '</a></li>';
										html += '<li class="goods-card goods-list small-pic waterfall"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg" style="height:175px;"/></div>';
										if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
											html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
										}
										if(domHtml.data('buy_btn') == '1'){
											html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
										}
										html += '</a></li>';
									html += '</ul>';
								html += '</li>';
							html += '</ul>';
							break;
						case '2':
							html  = '<ul class="sc-goods-list clearfix size-1 normal pic"><li class="goods-card small-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div>';
							if(domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							html += '</a></li><li class="goods-card small-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div>';
							if(domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							html += '</a></li><li class="goods-card small-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/third_demo_goods.jpg"/></div>';
							if(domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥32.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							html += '</a></li><li class="goods-card small-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div>';
							if(domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							html += '</a></li></ul>';
							break;
					}
					break;
				case '2':
					switch(domHtml.data('size_type')){
						case '0':
							html  = '<ul class="sc-goods-list clearfix size-2 card pic"><li class="goods-card big-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</a></li><li class="goods-card small-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){

								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</a></li><li class="goods-card small-pic card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</a></li></ul>';
							break;
						case '2':
							html  = '<ul class="sc-goods-list clearfix size-2 normal pic"><li class="goods-card big-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div>';
							if(domHtml.data('show_title') == '1' || domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+' '+(domHtml.data('price')=='1' ? 'info-price' : 'info-no-price')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>379.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							html += '</a></li><li class="goods-card small-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div>';
							if(domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>5.50</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							html += '</a></li><li class="goods-card small-pic normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div>';
							if(domHtml.data('price') == '1'){
								html += '<div class="info clearfix '+(domHtml.data('show_title') == '0' ? 'info-no-title' : '')+'"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>60.00</em>' : '')+'</p><p class="goods-price-taobao"></p></div>';
							}
							html += '</a></li></ul>';
							break;
					}
					break;
				case '3':
					switch(domHtml.data('size_type')){
						case '0':
							html  = '<ul class="sc-goods-list clearfix size-3 card list"><li class="goods-card card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div><div class="info"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p>';
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</div>';
							html += '</a></li><li class="goods-card card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div><div class="info"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p>';
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</div>';
							html += '</a></li><li class="goods-card card"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div><div class="info"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p>';
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</div>';
							html += '</a></li></ul>';
							break;
						case '2':
							html  = '<ul class="sc-goods-list clearfix size-3 normal list"><li class="goods-card normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/first_demo_goods.jpg"/></div><div class="info"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥379.00</em>' : '')+'</p><p class="goods-price-taobao"></p>';
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</div>';
							html += '</a></li><li class="goods-card normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/two_demo_goods.jpg"/></div><div class="info"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥5.50</em>' : '')+'</p><p class="goods-price-taobao"></p>';
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</div>';
							html += '</a></li><li class="goods-card normal"><a href="javascript:void(0);" class="link js-goods clearfix"><div class="photo-block"><img class="goods-photo js-goods-lazy" src="'+staticpath+'images/n_demo_goods.jpg"/></div><div class="info"><p class="goods-title">此处显示商品名称</p><p class="goods-price goods-price-icon">'+(domHtml.data('price')=='1' ? '<em>￥60.00</em>' : '')+'</p><p class="goods-price-taobao"></p>';
							if(domHtml.data('buy_btn') == '1'){
								html += '<div class="goods-buy btn'+domHtml.data('buy_btn_type')+'"></div>';
							}
							html += '</div>';
							html += '</a></li></ul>';
							break;
					}
					break;
			}
			domHtml.find('.sc-goods-list').replaceWith(html);
		}

		$('.js-sidebar-region').empty().html(rightHtml);
	};
	

	/**
	 *新活动模块
	 */		
	clickArr['new_activity_module'] = function() {
		var defaultHtml = '<div class="custom-nav"><a href="#" class="arrow-right"><span class="custom-nav-title js-activity_name">活动模块</span></a></div><ul class="swiper-wrapper clearfix activeList"><li class="swiper-slide swiper-slide-active" style=""><a href="#"><img src="'+staticpath+'images/default_shop.png"><h3>活动标题</h3></a><i class="tipOn bargain">活动</i></li><li class="swiper-slide swiper-slide-next" ><a href="#"><img src="'+staticpath+'images/default_shop.png"><h3>活动标题</h3></a><i class="tipOn presale">活动</i></li></ul>';
		
		// data_list 是存放的活动列表数据
		var data_list = dom.find('.control-group .activity').data('data_list');
		var data = {};
		
		var domHtml;
		if (typeof data_list == "undefined") {
			domHtml = $('<div class="activity "></div>');
			domHtml.html(defaultHtml).data({'name': '活动模块', 'display': '0', 'data_list': []});
			dom.find('.control-group').prepend(domHtml);
		} else {
			domHtml = dom.find(".activity");
			if (domHtml.html() == '') {
				domHtml.html(defaultHtml);
			}
		}
		
		data.name = domHtml.data("name");
		data.display = domHtml.data("display");
		data.data_list = domHtml.data("data_list");
		
		if (typeof data.name == "undefined") {
			data.name = "";
		}

		var auto = "";
        var normal = "";
        var list = "";

		if (data.display == 0) {
			auto = ' checked="true"';
		} else if (data.display == 1) {
            normal = ' checked="true"';
        } else {
            list = ' checked="true"';
        }
		
		//赋值，右侧默认html
		rightHtmls  = '<div>';
		rightHtmls += '		<div class="form-horizontal">';
		rightHtmls += '			<div class="js-meta-region" style="margin-bottom:20px;">';
		rightHtmls += '				<div class="control-group">';
		rightHtmls += '					<label class="control-label">模块名称：</label>';
		rightHtmls += '					<div class="controls"><input type="text" name="name" value="' + data.name + '" maxlength="80"/></div>';
		rightHtmls += '				</div>';
		rightHtmls += '				<div class="control-group">';
		rightHtmls += '					<label class="control-label">显示：</label>';
		rightHtmls += '					<div class="controls">';
		rightHtmls += '						<label class="radio inline"><input type="radio" name="display_mode" value="0" ' + auto + ' />自动轮播</label>';
        rightHtmls += '						<label class="radio inline"><input type="radio" name="display_mode" value="1" ' + normal + ' />手动滑动</label>';
        rightHtmls += '						<label class="radio inline"><input type="radio" name="display_mode" value="2" ' + list + ' />多行一行两个</label>';
		rightHtmls += '					</div>';
		rightHtmls += '				</div>';
		rightHtmls += '				<div class="control-group">';
		rightHtmls += '					<label class="control-label">选择活动：</label>';
		rightHtmls += '					<div class="controls new_activity_module_msg">';
		rightHtmls += '						<ul class="module-goods-list clearfix ui-sortable coupon-list js-activity-list" name="activit_list">';
		
		//右侧加载 已保存的产品
		var j = 0;
		for(var i in data.data_list) {
			rightHtmls += '						<li class="sort"><a href="javascript:"><div class="coupon-money">' + data.data_list[i]['title'].toString().substr(0,4)+ '</div></a><a class="close-modal js-delete-activity small hide" title="删除">×</a></li>';
		}
	
		rightHtmls += '							<li><a href="javascript:void(0);"class="add-goods js-add-activity"><i class="icon-add"></i></a></li>';
		rightHtmls += '						</ul>';
		rightHtmls += '						<a href="javascript:;"class="control-action js-add-activity" style="display:none;">添加活动</a>';
		rightHtmls += '						<input type="hidden"name="coupon">';
		rightHtmls += '					</div>';
        rightHtmls += '				</div>';
        rightHtmls += '				<p style="color: #CC0000;padding-left: 20px;">温馨提示：活动过期自动隐藏</p>';
		rightHtmls += '			</div>';
		rightHtmls += '		</div>';
		rightHtmls += '</div>';

		rightHtml = $(rightHtmls);
		// 添加右侧代码
		$('.js-sidebar-region').empty().html(rightHtml);
		
		
		// 操作右侧 添加/改变数据
		// 更改活动名称
		rightHtml.find('input[name="name"]').blur(function(){
			var activity_name = $(this).val();
			domHtml.data('name', activity_name);
			domHtml.find(".js-activity_name").html(activity_name);
		});
		
		// 更改活动显示方式
		rightHtml.find('input[name="display_mode"]').click(function(){
			domHtml.data('display', $(this).val());
		});	
		
		// 增加活动
		widget_link_hd(rightHtml.find('.js-add-activity'), 'new_activity_module', function(result){
			var activity_list_html = "";
			for(var i in result) {
				activity_list_html += '<li class="sort"><a href="javascript:"><div class="coupon-money">' + result[i]['title'].toString().substr(0, 4)+ '</div></a><a class="close-modal js-delete-activity small hide" title="删除">×</a></li>';
			}
			
			rightHtml.find("li").eq(-1).before(activity_list_html);
			
			// 添加内容后，需要与之前老数据进行合并
			var data_list = domHtml.data("data_list");
			data_list = $.merge(data_list, result);
			domHtml.data("data_list", data_list);
			
			// 删除活动
			rightHtml.find(".js-delete-activity").click(function (event) {
				var index = $(this).closest("ul").find("li").index($(this).closest("li"));
				var data_list = domHtml.data("data_list");
				
				try {
					delete data_list[index];
					
					// 重新生成变量
					var data_list_tmp = [];
					var j = 0;
					for (var i in data_list) {
						if (typeof data_list[i] == "undefined" || data_list[i] == '') {
							continue;
						}
						data_list_tmp[j] = data_list[i];
						j++;
					}
					
					domHtml.data("data_list", data_list_tmp);
					$(this).closest("li").remove();
				} catch(e) {
					
				}
			});
		},domHtml.data("data_list"));
		
		// 删除活动
		rightHtml.find(".js-delete-activity").click(function (event) {
			var index = $(this).closest("ul").find("li").index($(this).closest("li"));
			var data_list = domHtml.data("data_list");
			
			try {
				delete data_list[index];
				
				var data_list_tmp = [];
				var j = 0;
				for (var i in data_list) {
					if (typeof data_list[i] == "undefined" || data_list[i] == '') {
						continue;
					}
					data_list_tmp[j] = data_list[i];
					j++;
				}
				
				domHtml.data("data_list", data_list_tmp);
				$(this).closest("li").remove();
				
				// 当删除0，1活动，需要重新生成
				if (index < 2) {
					
				}
			} catch(e) {
				console.log(e);
			}
		});
	};
	
	
	/**
	 * 地图
	 */
	clickArr['map'] = function () {
		var local = null,marker = null;
		defaultHtml = '<div style="height: 212px;" class="map"><img style="max-height:212px;display:block;" src="'+staticpath+'images/map.png" /></div>';
		
		domHtml = dom.find('.control-group');
		if (domHtml.html() == '<div class="component-border"></div>') {
			domHtml.prepend(defaultHtml);
		}
		
		var rightHtml = $('<div><div class="app-component-desc"><p>地图模块的位置会根据您配置店铺时的坐标展示</p></div></div>');
		
		
		$('.js-sidebar-region').empty().html(rightHtml);
	};

    /**
     * 联系店铺
     */
    clickArr['store_map'] = function () {
        var local = null,marker = null;
        defaultHtml = '<div class="contact_store store_map">' +
            '<dl>' +
            '<dd class="storename">此处显示店铺名称</dd>'+
            '<dt><span class="icon_addess adress_title">此处显示详细地址 </span></dt>' +
            '</dl>' +
            '<div class="contact_icon">' +
            '<a href="#" class="dianhua"><img src="'+staticpath+'images/dianhua.png" alt=""></a>' +
            '<a href="#" class="adressIcon"><img src="'+staticpath+'images/jingqujiaotong-daozhequ.png" alt=""></a>' +
            '</div>' +
            '</div>';

        domHtml = dom.find('.control-group');
        if (domHtml.html() == '<div class="component-border"></div>') {
            domHtml.prepend(defaultHtml);
            // if (typeof get_contact_url == 'undefined') {
            //     get_contact_url = 'user.php?c=store&a=get_contact';
            // }
            var province = $("#province option:checked").text();
            var city = $("#city option:checked").text();
            var county = $("#area option:checked").text();
            domHtml.data({'province': '', 'city': '', 'area': '', 'address': '', 'lng': '', 'lat': '','storename':'','storetel':'','tel_play':'1','store_play':'1'});
            // $.ajax({
            //     type: "get",
            //     url: get_contact_url,
            //     async: false,
            //     success: function(result){
            //         var store_contact = result.err_msg;
            //         domHtml.data({'province': store_contact.province, 'city': store_contact.city, 'area': store_contact.county, 'address': store_contact.address, 'lng': store_contact.long, 'lat': store_contact.lat,'storename':store_contact.storename,'storetel':store_contact.storetel,'tel_play':store_contact.tel_play,'store_play':store_contact.store_play});
            //     }
            // });
        }

        var rightHtmlData = domHtml.data();
        var storetel = rightHtmlData.storetel || "";
        var province = rightHtmlData.province || '选择省';
        var city = rightHtmlData.city || '选择市';
        var area = rightHtmlData.area || '选择区';
        var adress=rightHtmlData.address || '';
        var rightHtml = $('<div>\
							<form class="form-horizontal" novalidate="" onsubmit="return false">\
								<div class="control-group">\
									<label class="control-label">当前地址：</label>\
								</div>\
								<div class="control-group">\
								  <label class="control-label"> 拨号按钮：</label>\
								  <div class="controls">\
								    <input type="checkbox"  checked="checked"  name="tel_play" id="tel_play"   value="' + (domHtml.data('tel_play')) +  '"/>\
								     </div>\
								  </div>\
									<div class="control-group">\
									      <label class="control-label">店铺地址：</label>\
								          <div class="controls">\
									       <input type="checkbox" checked="checked"  name="store_play" id="storePlay"  value="' + (domHtml.data('store_play')) + '"   />\
									      </div>\
				                    </div>\
								<div class="control-group">\
									<div class="controls" style="margin-left: 10px; margin-right: 10px; width: 100%;">\
										<input type="hidden" class="btn js-map-long" value="" />\
										<input type="hidden" class="btn js-map-lat" value="" />\
									</div>\
				<input type="button" name="search" class="btn js-save-btn" value="保存位置" />\
								</div>\
							</form>\
						</div>');

        // 省份切换
        rightHtml.find('#province').change(function(){
            if ($(this).val() != '') {
                getCitys('city', 'province', '');
            } else {
                $('#city').html('<option value="">选择市</option>');
            }
            $('#area').html('<option value="">选择区</option>');
        });

        // 城市切换
        rightHtml.find('#city').change(function(){
            if ($(this).val() != '') {
                getAreas('area', 'city', '');
            } else {
                $('#city').html('<option value="">选择区</option>');
            }
        });

        rightHtml.find('#area').change(function(){
            if ($(this).val() != '') {
            } else {
                $('#area').html('<option value="">选择区</option>');
            }
        });

        rightHtml.find('#tel_play').click(function(){
            if ($(this).is(':checked')) {
                domHtml.data('tel_play', 1);
                $('.dianhua').show();
                $('.is_show').show();
                rightHtml.find("input[name='tel_play']").attr('checked', true);
            } else {
                $('.dianhua').hide();
                $('.is_show').hide();
                domHtml.data('tel_play', 2);
                rightHtml.find("input[name='tel_play']").removeAttr("checked");
            }
        });

        rightHtml.find('#store_play').click(function(){
            if ($(this).is(':checked')) {
                domHtml.data('store_play', 1);
                rightHtml.find("input[name='tel_play']").attr('checked', true);
                $('.adressIcon').show();
            } else {
                domHtml.data('store_play', 2);
                $('.adressIcon').hide();
                rightHtml.find("input[name='store_play']").removeAttr("checked");
            }
        });

        //设置值
        rightHtml.find("input[name='storename']").val(domHtml.data('storename'));
        rightHtml.find("input[name='address']").val(domHtml.data('address'));
        rightHtml.find("input[name='tel_play']").val(domHtml.data('tel_play'));
        rightHtml.find("input[name='store_play']").val(domHtml.data('store_play'));

        //电话显示
        rightHtml.find('input[name="tel_play"]').change(function(){
            domHtml.data('tel_play',$(this).prop('checked') ? '1' : '2');
            if(domHtml.data('tel_play') == '1'){
                $('.dianhua').show();
                domHtml.data('tel_play',1);
                $('input[name="tel_play"]').attr('value','1');
            }else{
                $('.dianhua').hide();
                domHtml.data('tel_play',2);
                $('input[name="tel_play"]').attr('value','2')
                rightHtml.find("input[name='tel_play']").removeAttr("checked");
            }

        })
        //地址显示
        rightHtml.find('input[name="store_play"]').change(function(){
            domHtml.data('store_play',$(this).prop('checked') ? '1' : '2');
            if(domHtml.data('store_play') == '1'){
                domHtml.data('store_play',1);
                $('.adressIcon').show();
                $('.is_show').show();
                $('input[name="store_play"]').attr('value','1');
            }else{
                $('.adressIcon').hide();
                domHtml.data('store_play',2);
                $('.is_show').hide();
                $('input[name="store_play"]').attr('value','2');
                rightHtml.find("input[name='store_play']").removeAttr("checked");
            }
        })


        // 店铺电话
        rightHtml.find('input[name="storetel"]').blur(function(){
            var _this = $(this)
            var valueTel=$('input[name="storetel"]').val();
            if (/^1[0-9]{10}$/.test(valueTel) || (/^[0]?[0-9]{2,3}[\-]{1}[0-9]{5,8}$/.test(valueTel)) || (/^[0-9]{1,4}[\-]{1}[0-9]{2,4}[\-]{1}[0-9]{2,4}$/.test(valueTel))) {
                domHtml.data({
                    storetel:_this.val()
                })
            } else {
                if(domHtml.data('tel_play') == 1){
                    layer_tips(1, "请正确填写手机/电话号码！");
                    return false;
                }else{
                    domHtml.data({
                        storetel:''
                    });
                }

            }

        });

        if (domHtml.data('tel_play') == 1) {
            $('.dianhua').show();
            rightHtml.find("input[name='tel_play']").attr('checked', true);
            $('.is_show').show();
        }else{
            $('.dianhua').hide();
            rightHtml.find("input[name='tel_play']").attr('checked', false);
            $('.is_show').hide();
        }

        if (domHtml.data('store_play') == 1) {
            $('.adressIcon').show();
            rightHtml.find("input[name='store_play']").attr('checked', true);
        }else{
            $('.adressIcon').hide();
            rightHtml.find("input[name='store_play']").attr('checked', false);
        }

        //失去焦点
        rightHtml.find('input[name="storename"]').blur(function(){
            //保存storename的值到data
            domHtml.data('storename',$(this).val()).find('.storename').html(($(this).val().length != 0 ? $(this).val() : '' ));
        });
        rightHtml.find('input[name="address"]').blur(function(){
            //保存adress的值到data
        });
        // 保存
        rightHtml.find('.js-save-btn').click(function () {
            // if (typeof store_contact_url == 'undefined') {
            //     store_contact_url = './user.php?c=setting&a=store_contact';
            // }
            var province = $("#province option:checked").val();
            var city = $("#city option:checked").val();
            var county = $("#area option:checked").val();
            var address = $("#address").val();
            var storename=$('#storename').val();
            var storetel=$('#storetel').val();
            var lng = $('.js-map-long').val();
            var lat = $('.js-map-lat').val();
            var provinceTxt=$('#province option:selected').text();
            var cityTxt=$('#city option:selected').text();
            var areaTxt=$('#area option:selected').text();
            var store_play=$('input[name="store_play"]').val();
            var tel_play=$('input[name="tel_play"]').val();
            domHtml.data('address',$(this).text()).find('.adress_title').html(provinceTxt+cityTxt+areaTxt+address+($(this).text().length != 0 ? $(this).text() :'' ));


            if (province.length == 0) {
                layer_tips(1, '请选择省');
                return;
            }

            if (city.length == 0) {
                layer_tips(1, '请选择市');
                return;
            }

            if (county.length == 0) {
                layer_tips(1, '请选择区');
                return;
            }

            if (address.length == 0) {
                layer_tips(1, '请填写详细地址');
                return;
            }

            if (lng.length == 0 || lat.length == 0) {
                layer_tips(1, '请在地图上确定位置');
                return;
            }
            console.log('province', province);
            console.log('city', city);
            console.log('area', county);
            console.log('address', address);
            console.log('lng', lng);
            console.log('lat', lat);
            console.log('storename', storename);
            console.log('storetel', storetel);
            console.log('store_play', store_play);
            console.log('tel_play', tel_play);

            layer_tips(0, '设置成功');
            domHtml.data({'province': province, 'city': city, 'area': county, 'address': address, 'lng': lng, 'lat': lat,'storename':storename,'storetel':storetel,store_play:store_play,tel_play:tel_play});

            // $.post(store_contact_url, {province: province, city: city, county: county, address: address, map_long: lng, map_lat: lat,storename:storename,storetel:storetel,store_play:store_play,tel_play:tel_play}, function (result) {
            //     if (result.err_code == 0) {
            //         domHtml.data({'province': province, 'city': city, 'area': county, 'address': address, 'lng': lng, 'lat': lat,'storename':storename,'storetel':storetel,store_play:store_play,tel_play:tel_play});
            //         layer_tips(0, '设置成功');
            //     } else {
            //         layer_tips(1, result.err_msg);
            //     }
            // });
        });

        $('.js-sidebar-region').empty().html(rightHtml);

        if (typeof domHtml.data('province') == "undefined" || domHtml.data('province') == '') {
            getProvinces('province','');
        } else {
            getProvinces('province', domHtml.data('province'));
            getCitys('city','province', domHtml.data('city'));
            getAreas('area','city', domHtml.data('area'));
        }

        var map = null;
        var oPoint = new BMap.Point(116.331398, 39.897445);
        var setPoint = function(mk,b){
            var pt = mk.getPosition();
            (new BMap.Geocoder()).getLocation(pt,function(rs){
                addComp = rs.addressComponents;
                infoWindow = new BMap.InfoWindow('<div class="info-window"><p class="address">'+ addComp.city + addComp.district + addComp.street +'</p><div style="text-align:center;"><a class="ui-btn ui-btn-confirm js-confirm" onclick="setSelect(\''+pt.lat+'\',\''+pt.lng+'\')">确认位置</a></div></div>',{width:220,height:60,title:"",enableMessage:false,message:""});
                marker.openInfoWindow(infoWindow);
            });
        };

        map = new BMap.Map("cmmap",{"enableMapClick":false});
        map.enableScrollWheelZoom();

        map.centerAndZoom(oPoint, 15);

        if(domHtml.data('lng') && domHtml.data('lat')){
            marker = new BMap.Marker(new BMap.Point(domHtml.data('lng'), domHtml.data('lat')),{icon:new BMap.Icon("http://map.baidu.com/image/markers_new.png", new BMap.Size(25, 37), {anchor: new BMap.Size(12,15), imageOffset: new BMap.Size(0,-156)}),enableMassClear:false});
            map.addOverlay(marker);
            map.centerAndZoom(new BMap.Point(domHtml.data('lng'), domHtml.data('lat')), 19);
        } else {
            function myFun(result){
                oPoint = new BMap.Point(result.center['lng'],result.center['lat']);
                map.centerAndZoom(oPoint,15);
            }
            var myCity = new BMap.LocalCity();
            myCity.get(myFun);
        }

        map.addControl(new BMap.NavigationControl());
        map.enableScrollWheelZoom();

        map.addEventListener("click",function(e){
            if(!e.overlay){
                if(marker == null){
                    marker = new BMap.Marker(new BMap.Point(e.point.lng,e.point.lat),{icon:new BMap.Icon("http://map.baidu.com/image/markers_new.png", new BMap.Size(25, 37), {anchor: new BMap.Size(12,15), imageOffset: new BMap.Size(0,-156)}),enableMassClear:false});
                    map.addOverlay(marker);
                }else{
                    marker.setPosition(new BMap.Point(e.point.lng,e.point.lat));
                }
                setPoint(marker);
            }
        });
        local = new BMap.LocalSearch(map,{
            pageCapacity:10,
            onSearchComplete:function(results){
                search_point = [];
                var search_count = results.getCurrentNumPois();
                if(search_count > 0){
                    rightHtml.find('#map_quick_search').css('display', 'block');
                    var result_panel = '<ul class="place-list js-place-list">';
                    for(var i=0;i<search_count;i++){
                        var now_poi = results.getPoi(i);
                        result_panel += '<li data-lng="'+now_poi.point.lng+'" data-lat="'+now_poi.point.lat+'" data-title="'+now_poi.title+'" data-content="'+now_poi.address+'"><i class="place-order">'+getWord(i)+'</i><h3>'+now_poi.title+'</h3><p class="place-adress">地址：'+now_poi.address+'</p>'+(now_poi.phoneNumber ?'<p class="place-phone">电话：'+now_poi.phoneNumber+'</p>' : '')+'</li>';
                        if(i == 0){
                            map.centerAndZoom(new BMap.Point(now_poi.point.lng,now_poi.point.lat),17);
                        }

                        var search_marker = new BMap.Marker(new BMap.Point(now_poi.point.lng,now_poi.point.lat),{icon:new BMap.Icon("http://map.baidu.com/image/markers_new.png", new BMap.Size(19, 27), {anchor: new BMap.Size(10,9), imageOffset: new BMap.Size(i*-24,-199)})});
                        search_point[i] = search_marker;
                        map.addOverlay(search_marker);
                    }
                    result_panel += '</ul>';
                    rightHtml.find('#map_quick_search').html(result_panel);

                    rightHtml.find('li').click(function(){
                        var nowDom = $(this);

                        rightHtml.find('li').removeClass('active');
                        nowDom.addClass('active').mouseover();
                        var a = nowDom.index();
                        var pt = search_point[a].getPosition();
                        (new BMap.Geocoder()).getLocation(pt,function(rs){
                            addComp = rs.addressComponents;
                            infoWindow = new BMap.InfoWindow('<div class="info-window"><h3 style="font-size:14px;font-weight:bold;">'+nowDom.data('title')+'</h3><p class="address">'+ nowDom.data('content')+'</p><div style="text-align:center;"><a class="ui-btn ui-btn-confirm js-confirm" onclick="setSelect(\''+nowDom.data('lat')+'\',\''+nowDom.data('lng')+'\')">确认位置</a></div></div>',{width:220,height:(nowDom.data('title').length>15 ? 100 : 80),title : "",enableMessage:false,message:""});
                            search_point[a].openInfoWindow(infoWindow);
                        });
                        map.panTo(search_point[a].getPosition());
                        search_point[a].setZIndex(2);
                    });


                    $.each(search_point,function(i,item){
                        (function(){
                            var index = i;
                            search_point[i].addEventListener('click', function(){
                                rightHtml.find('li').eq(i).click();
                            });
                        })();
                    });
                }else{
                    layer_tips(1, '搜索 " '+ rightHtml.find('input[name="address"]').val() + ' " 无结果');
                }
            }
        });

        $('.js-search').click(function(){
            map.clearOverlays();
            if(rightHtml.find('input[name="address"]').val() != ''){
                var search_val = '';
                if(rightHtml.find('#province').val()){
                    search_val = search_val + $('#province option:checked').text();
                }
                if(rightHtml.find('#city').val()){
                    search_val = search_val + $('#city option:checked').text();
                }
                if(rightHtml.find('#area').val()){
                    search_val = search_val + $('#area option:checked').text();
                }
                search_val = search_val + rightHtml.find('input[name="address"]').val();
                local.search(search_val);
            }else{
                layer_tips(1,'请填写详细地址');
            }
            return false;
        });

        function getWord(num){
            var word = ['A','B','C','D','E','F','G','H','I','J'];
            return word[num];
        }
    };

    /**
	 * 秒杀、砍价、拼团组件
     * @type {string[]}
     */
    var activityModules = ['seckill_module','bargain_module','tuan_module'];
    for (var i = 0; i < activityModules.length; i++) {
        var moduleName = activityModules[i];
        (function (activityModule) {
            clickArr[activityModule] = function(){
                console.log('activityModule', activityModule);
                $('.js-sidebar-region').empty().html('<div id="'+activityModule+'_app"><right-htmls></right-htmls></div>');
                var vueStore;
                var vueStoreIndex = dom.data('vue-store-index');
                console.log('vueStoreIndex', vueStoreIndex);
                if(vueStoreIndex !== undefined){
                    vueStore = vueStores[vueStoreIndex]
                }else{
                    vueStore = new Vuex.Store(assign({state: getVuexInitialState(activityModule)},getVuexStoreMixin()));
                    console.log('vueStore', vueStore);
                    dom.data('vue-store-index',vueStores.length);
                    vueStores.push(vueStore);
                }
                new Vue({
                    store: vueStore,
                    el: '#'+activityModule+'_app'
                });
                var $preview = $('<div class="'+activityModule+'"><al-activities></al-activities></div>');
                console.log('$preview', $preview);
                dom.find('.'+activityModule+'').remove();
                dom.find('.control-group').prepend($preview);
                new Vue({
                    store:vueStore,
                    el:$preview[0]
                });

                var huodong_type = activityModule.replace('_module','');
                var load_url = 'activity_info&hide_others=1&huodong_type='+huodong_type;
                //添加
                var type_msg = $('#' + huodong_type + '_module_app');
                console.log('type_msg', type_msg);


                widget_link_box(type_msg.find('.add-goods'),load_url,function(result){
					vueStore.state.activities = vueStore.state.activities.concat(result);
                }, vueStore);

            }
        })(moduleName);
    }


	/**
	 * 店铺分类
	 */
	clickArr['store_cate'] = function(){
		rightHtml = $('<div class="store_right"><form class="form-horizontal"><div>若只有一个分类，前台默认不显示。为了保证网页打开速度，最多添加5个分类。</div><div class="control-group js-collection-region"><ul class="choices ui-sortable"></ul></div><div class="control-group options"><a class="add-option js-add-option" href="javascript:void(0);"><i class="icon-add"></i> 添加一个文本导航</a></div></form></div>');
		// 默认店铺列表
		bottomHtml = '<div class="productRow store_example" data-id="343" data-name="演示店铺" data-catindex="0" data-image="http://o2o-static.pigcms.com/upload/store/000/000/759/58acec48a3621859.jpg">     <div class="app-field clearfix">      <div class="control-group product_dealcard">       <div class="dealcard-img imgbox">        <img src="http://o2o-static.pigcms.com/upload/store/000/000/759/58acec48a3621859.jpg" alt="演示店铺">       </div>       <div class="dealcard-block-right">        <div class="brand">演示店铺<em class="location-right">xxx km</em></div>        <div class="title">         <span class="star"><i class="full"></i><i class="full"></i><i class="full"></i><i class="half"></i><i></i></span>         <span>月售xx单</span>         <em class="location-right">xx分钟</em>        </div>        <div class="price">         <span>起送价 $xx</span>         <span class="delivery">配送费 $xx</span>         <em class="location-right">配送类型</em>        </div>        <div class="coupon">         <ul>          <li><em class="couponem"></em>优惠信息在手机版显示</li>                     </ul>        </div>       </div>      </div>     </div>    </div><div class="productRow store_example" data-id="343" data-name="演示店铺" data-catindex="0" data-image="http://o2o-static.pigcms.com/upload/store/000/000/759/58acec48a3621859.jpg">     <div class="app-field clearfix">      <div class="control-group product_dealcard">       <div class="dealcard-img imgbox">        <img src="http://o2o-static.pigcms.com/upload/store/000/000/759/58acec48a3621859.jpg" alt="演示店铺">       </div>       <div class="dealcard-block-right">        <div class="brand">演示店铺<em class="location-right">xxx km</em></div>        <div class="title">         <span class="star"><i class="full"></i><i class="full"></i><i class="full"></i><i class="half"></i><i></i></span>         <span>月售xx单</span>         <em class="location-right">xx分钟</em>        </div>        <div class="price">         <span>起送价 $xx</span>         <span class="delivery">配送费 $xx</span>         <em class="location-right">配送类型</em>        </div>        <div class="coupon">         <ul>          <li><em class="couponem"></em>优惠信息在手机版显示</li>                     </ul>        </div>       </div>      </div> </div>    </div><div class="productRow store_example" data-id="343" data-name="演示店铺" data-catindex="0" data-image="http://o2o-static.pigcms.com/upload/store/000/000/759/58acec48a3621859.jpg">     <div class="app-field clearfix">      <div class="control-group product_dealcard">       <div class="dealcard-img imgbox">        <img src="http://o2o-static.pigcms.com/upload/store/000/000/759/58acec48a3621859.jpg" alt="演示店铺">       </div>       <div class="dealcard-block-right">        <div class="brand">演示店铺<em class="location-right">xxx km</em></div>        <div class="title">         <span class="star"><i class="full"></i><i class="full"></i><i class="full"></i><i class="half"></i><i></i></span>         <span>月售xx单</span>         <em class="location-right">xx分钟</em>        </div>        <div class="price">         <span>起送价 $xx</span>         <span class="delivery">配送费 $xx</span>         <em class="location-right">配送类型</em>        </div>        <div class="coupon">         <ul>          <li><em class="couponem"></em>优惠信息在手机版显示</li>                     </ul>        </div>       </div>      </div>     </div>    </div>';

		var defaultHtml='<span class="curr flex_one">店铺分类</span>';
		var shopCatList = [];
		var selected = 0;
		if(dom.find('.control-group .addCategoryBtn').size() == 0){
			//第一次
			domHtml = $('<div class="app-field addCategoryBtn flex"></div>');
			//添加html,存值
			// domHtml.html(defaultHtml).data('shop_cat_data', {'cat_arr': []});
			//在control-group前面添加domHtml
			dom.find('.control-group').prepend(domHtml);

			// dom.find('.control-group').after('<div class="control-group addProductBox"><div id="addProductBtn" title="点击添加店铺"></div></div>');

			dom.append(bottomHtml);

			var randNumber = getRandNumber();
			shopCatList[randNumber] = {'title':'店铺分类','store_list':[],selected:1};
			selected = 1;
	
			domHtml.data('shopCatList',shopCatList);
			var liHtml  = $('<li class="choice" data-id="'+randNumber+'"><div class="control-group"><label class="control-label"><em class="required">*</em>分类名称：</label><div class="controls"><input type="text" name="title" value="店铺分类"/></div></div><div class="control-group"><label class="control-label ">选择店铺：</label><div class="controls"><div class="store_list"></div><a href="javascript:void(0);" class="js-add-goods addProductBtn" data-cate_num="'+randNumber+'" onclick="widget_link_store(this,\'select_store\',\'store_after\')"><i class="icon-add"></i></a></div></div><div class="actions"><span class="action add close-modal" title="添加">+</span><span class="action delete close-modal" title="删除">×</span></div></li>');

			//添加html,存值
			domHtml.html(defaultHtml);

		}else{
			domHtml = dom.find('.control-group .addCategoryBtn');

			if($('.store_example').size() == 0){
				dom.append(bottomHtml);
			}
		}
		console.log('num',num)
		var addContent = function(num,dom){
			shopCatList = domHtml.data('shopCatList');
			if (typeof(shopCatList)=='undefined') {
				shopCatList = [];
			}
			if(num >= 0){
				randNumber = num;
				var liContent = '<li class="choice" data-id="'+randNumber+'"><div class="control-group"><label class="control-label"><em class="required">*</em>分类名称：</label><div class="controls"><input type="text" name="title" value="'+shopCatList[num].title+'"/></div></div><div class="control-group"><label class="control-label ">选择店铺：</label><div class="controls"><div class="store_list">';
				if (shopCatList[num].store_list) {
					for (var i = 0; i < shopCatList[num].store_list.length; i++) {
						var now_store = shopCatList[num].store_list[i];
						if (now_store) {
            				liContent+='<div class="store-item" data-title="'+now_store["title"]+'" id="'+now_store["id"]+'" style="position:relative;padding: 4px;"><a href="javascript:;" ' + (i == 0 ? 'class="current"' : '') + '><span>'+now_store["title"]+'</span></a><a class="close-modal js-delete-store small" data-store_id="'+now_store["id"]+'" title="删除">×</a></div>';
            			}
					}
				}

				liContent += '</div><a href="javascript:void(0);" class="js-add-goods addProductBtn" data-cate_num="'+randNumber+'" onclick="widget_link_store(this,\'select_store\',\'store_after\')"><i class="icon-add"></i></a></div></div><div class="actions"><span class="action add close-modal" title="添加">+</span><span class="action delete close-modal" title="删除">×</span></div></li>';
				var liHtml  = $(liContent);
				liHtml.find("input[name='title']").val(shopCatList[num].title);
				liHtml.find(".addProductBtn").data('store_arr',shopCatList[num].store_list);
				if (selected==0) {
					shopCatList[num].selected = 1;
					selected = 1;
				}
				var store_arr = shopCatList[num].store_list;
				if (store_arr) {
					liHtml.find('.js-delete-store').click(function(){
						var dataId = $(this).parents('.choice').attr('data-id');
						var storeId = $(this).attr('data-store_id');
						for(var i in store_arr){
				            if (store_arr[i]) {
								if(store_arr[i].id==storeId){
									delete store_arr[i]; 
									$(this).parents('div.store-item').remove();
								} 
				            }
						}
						shopCatList[num].store_list = store_arr;
						$('.control-group .addCategoryBtn').data('shopCatList',shopCatList);
						$('.js-sidebar-region').find("a[data-cate_num="+num+"]").data('store_arr',store_arr);
					});
				}
			}else{
				var randNumber = getRandNumber();
				shopCatList[randNumber] = {'title':'店铺分类','store_list':[]};
				domHtml.data('shopCatList',shopCatList);
				var liHtml  = $('<li class="choice" data-id="'+randNumber+'"><div class="control-group"><label class="control-label"><em class="required">*</em>分类名称：</label><div class="controls"><input type="text" name="title" value="店铺分类"/></div></div><div class="control-group"><label class="control-label ">选择店铺：</label><div class="controls"><div class="store_list"></div><a href="javascript:void(0);" class="js-add-goods addProductBtn" data-cate_num="'+randNumber+'" onclick="widget_link_store(this,\'select_store\',\'store_after\')"><i class="icon-add"></i></a></div></div><div class="actions"><span class="action add close-modal" title="添加">+</span><span class="action delete close-modal" title="删除">×</span></div></li>');

				//添加html,存值
				domHtml.html(defaultHtml);
			}
			var titleDom = liHtml.find('input[name="title"]');
			var nowDom = liHtml.find('.dropdown');
			titleDom.blur(function(){ //标题文框失去焦点
				var shopCatList = domHtml.data('shopCatList');
				shopCatList[liHtml.data('id')].title = titleDom.val();
				domHtml.data('shopCatList',shopCatList);
				buildContent();
			});
			link_box(nowDom,[],function(type,prefix,title,href){
				nowDom.siblings('.js-link-to').remove();
				var beforeDom = $('<div class="left js-link-to link-to"><a href="'+href+'" target="_blank" class="new-window link-to-title"><span class="label label-success">'+prefix+' <em class="link-to-title-text">'+title+'</em></span></a><a href="javascript:;" class="js-delete-link link-to-title close-modal" title="删除">×</a></div>');
				if(titleDom.val().length == 0){
					titleDom.val(title);
				}
				var shopCatList = domHtml.data('shopCatList');
				shopCatList[liHtml.data('id')] = {'title':titleDom.val()};
				domHtml.data('shopCatList',shopCatList);
				buildContent();

				beforeDom.find('.js-delete-link').click(function(){
					var fDom = $(this).closest('.control-action');
					fDom.find('.js-link-to').remove();
					var shopCatList = domHtml.data('shopCatList');
					shopCatList[liHtml.data('id')] = {'title':titleDom.val()};
					domHtml.data('shopCatList',shopCatList);
				});
				nowDom.before(beforeDom);
				nowDom.children('a').attr('class','dropdown-toggle').html('修改 <i class="caret"></i>');
			});

			liHtml.find('span.add').click(function(){
				addContent(-1,liHtml);
			});
			liHtml.find('span.delete').click(function(){
				var shopCatList = domHtml.data('shopCatList');
				delete shopCatList[liHtml.data('id')];
				domHtml.data('shopCatList',shopCatList);
				$(this).closest('li.choice').remove();
				buildContent();
			});
			if(dom){
				dom.after(liHtml);
				var shopCatList = domHtml.data('shopCatList');
				var newNavList = [];
				$.each(rightHtml.find('.js-collection-region .ui-sortable > li'),function(i,item){
					newNavList[i] = shopCatList[$(item).data('id')];
					$(item).attr('data-id',i);
					$(item).find('.addProductBtn').attr('data-cate_num',i);
				});
				domHtml.data('shopCatList',newNavList);
			}else{
				rightHtml.find('.js-collection-region .ui-sortable').append(liHtml);
			}
			buildContent();
		};
		var buildContent = function(){
			var shopCatList = domHtml.data('shopCatList');
			var html = '';
			for(var i in shopCatList){
				html += '<span class="flex_one'+(shopCatList[i].selected ? ' curr' : '')+'">'+shopCatList[i].title+'</span>';
			}
			domHtml.html(html);
		};
		var shopCatList = domHtml.data('shopCatList');
		for(var num in shopCatList){
			addContent(num);
		}
		rightHtml.find('.js-add-option').click(function(){
			addContent(-1);
		});

		$('.js-sidebar-region').empty().html(rightHtml);
	};

	$('.app-sidebar').css('margin-top',dom.offset().top - $('.app-preview').offset().top);
	var fieldType = dom.data('field-type');
	clickArr[fieldType]();
};
function formatDuring (mss) {
    var hours = parseInt((mss % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var minutes = parseInt((mss % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = (mss % (1000 * 60)) / 1000;
    if (mss <= 24 * 60 * 60 * 1000) {
        return hours + "小时" + minutes + "分钟" + seconds + "秒";
    } else {
        var days = parseInt(mss / (1000 * 60 * 60 * 24));
        return days + "天" + hours + "小时" + minutes + "分钟" + seconds + "秒";
    }
}

function getVuexInitialState(fieldType){
    var nameShowObj = {
        "seckill_module":{
            buy_btn_name:"马上抢",
            title:"限时秒杀",
            field_type_name:"秒杀",
            price_name:"秒杀价"
        },
        "presale_module":{
            buy_btn_name:"去预定",
            title:"预售有礼",
            field_type_name:"预售",
            price_name:"到手价"
        },
        "tuan_module":{
            buy_btn_name:"去开团",
            title:"超值拼团",
            field_type_name:"拼团",
            price_name:"x人团"
        },
        "bargain_module":{
            buy_btn_name:"立即开砍",
            title:"人气砍价",
            field_type_name:"砍价",
            price_name:"底价"
        }
    };
    return {
        config: {
            list_display_style: 1,//1大图平铺2大图左右滑动3大图轮播4小图平铺5详细列表
            buy_btn_name: nameShowObj[fieldType].buy_btn_name,
            buy_btn_style: 1,
            time_count_show: true,
            name_show: true,
            remain_sku_show: true,
            original_price_show: true,
            presale_price_show: true,
            main_image_size_style: 1,//1 3:2,2 1:1
            main_image_fill_style: 1,//1 填充 2留白
            title: nameShowObj[fieldType].title,
            price_name: nameShowObj[fieldType].price_name,
            field_type_name:nameShowObj[fieldType].field_type_name,
            field_type:fieldType,
            auto_increate:true,
            outsale_hide:true,
            finish_hide:true,
            current_indicate_index:0,//轮播的时候，当前的指示器位置
            currentThemeIndex: 0//选中的主题色（仅仅后台保存，小程序不受这个控制）
        },
        list: [
            {
                img: staticpath+'images/first_demo_goods.jpg',
                title: '第一个商品名称',
                endTime: (Date.now()+24*60*60*1000).toString().slice(0,10),
                startTime: '1541644340',
                remain: 9999,
                curPrice: 32.00,
                price: 110.00,
                pigcms_id: 213
            },
            {
                img: staticpath+'images/two_demo_goods.jpg',
                title: '第二个商品名称',
                endTime: '1541694340',
                startTime: '1541644340',
                remain: 120,
                curPrice: 64.00,
                price: 120.00,
                pigcms_id: 214
            }
        ],
        activities:[],
        themes: ["orangeTheme", "redTheme", "blueTheme", "pinkDarkTheme", "purpleTheme", "brownTheme", "blackTheme"],
    }
}
function getVuexStoreMixin(){
    return {
        mutations: {
            setStyle: function (state, payload) {
                state.content[payload.name] = payload.value
            },
            setActivities:function(state, activities){
                state.activities = state.activities.concat(activities)
            },
            delActivity: function(state,id){
                state.activities = state.activities.filter(function(item){
                    return item.id !== id
                });
            }
        },
        getters: {
            currentTheme: function (state) {
                return state.themes[state.config.currentThemeIndex || 0]
            }
        }
    }
}

function convertString2Bool(obj){
    var returnObj = {}
    for(var key in obj){
        if(obj.hasOwnProperty(key)){
            if(obj[key] === 'true'){
                returnObj[key] = true
            }else if(obj[key] === 'false'){
                returnObj[key] = false
            }else{
                returnObj[key] = obj[key]
            }
        }
    }
    return returnObj
}


function setSelect(lat, lng){
    layer_tips(-1, '地理位置已确认哦');
    $('.js-confirm').html('位置已确认');
    $('.js-map-long').val(lng);
    $('.js-map-lat').val(lat);
}

/**
 * 魔方方法区块
 */

//创建魔方
var cube_create = function (_cube_data, width, height, position) {
    width = width || 60;
    height = height || 60;
    position = position || 'right';
    var megre_td = [];
    var _cub_html = '<tbody>';
    for (var r = 0; r < 4; r++) {
        _cub_html += '<tr>';
        for (var c = 0; c < 4; c++) {
            var _class = 'empty';
            var _selected = false;
            var _colspan = 1;
            var _rowspan = 1;
            var _index = 0;
            var _width = 160;
            var _height = 160;
            var _image = '';
            for (var i in _cube_data) {
                if (_cube_data[i].x == c && _cube_data[i].y == r) {
                    _selected = true;
                    _colspan = _cube_data[i].colspan;
                    _rowspan = _cube_data[i].rowspan;
                    _index = i;
                    _width = _cube_data[i].width;
                    _height = _cube_data[i].height;
                    _image = _cube_data[i].image || '';
                }
            }
            if (_rowspan > 1) {
                for (var r2 = 1; r2 < _rowspan; r2++) {
                    for (var c2 = 0; c2 < _colspan; c2++) {
                        megre_td.push((c + c2) + ':' + (r + r2));
                    }
                }
            }
            if (_colspan > 1) {
                c += (_colspan - 1);
            }
            if (megre_td.indexOf(c + ':' + r) == -1) {
                if (_selected) {
                    var _current = '';
                    if (typeof (selected_index) != 'undefined') {
                        if (_index == selected_index) { //当前操作项
                            _current = ' current';
                        }
                    }
                    _cub_html += '<td class="not-empty cols-' + _colspan + ' rows-' + _rowspan + ' index-' + _index + _current + '" colspan="' + _colspan + '" rowspan="' + _rowspan + '" data-index="' + _index + '">';
                    if (_image != '') {
                        _cub_html += '	<img src="' + _image + '">';
                    } else if (position == 'left') {
                        _cub_html += '	<img src="">';
                    } else {
                        _cub_html += '	<img src=""><span>' + _width + 'x' + _height + '</span>';
                    }
                    _cub_html += '</td>';
                } else {
                    _cub_html += '<td class="empty" data-x="' + c + '" data-y="' + r + '"></td>';
                }
            }
        }
        _cub_html += '</tr>';
    }
    _cub_html += '</body>';

    return _cub_html;
};

//检测魔方选择
var checkCubeSelected = function () {
    var cubeHtml = $('.js-sidebar-region').find('.cube2-edit:visible');
    cubeHtml.children('.control-group:eq(0)').removeClass('error');
    cubeHtml.find('.error-message').remove();

    cubeHtml.children('.control-group:eq(0)').addClass('error');
    cubeHtml.children('.control-group:eq(0)').find('.help-desc').after('<p class="help-block error-message">必须添加满4列。</p>');
};

//检测魔方内容
var checkCubeContent = function (i) {
    var cubeHtml = $('.js-sidebar-region').find('.cube2-edit:visible');
    cubeHtml.children('.control-group:eq(0)').removeClass('error');
    cubeHtml.find('.error-message').remove();

    var item = cubeHtml.find('.index-' + i).closest('.control-group').next('.js-item-region');
    if (item.find('.choice').eq(i).find('.thumb-image').length == 0) {
        item.find('.choice').eq(i).find('.control-group').eq(0).removeClass('error');
        item.find('.choice').eq(i).find('.control-group').eq(0).remove('.error-message');
        item.find('.choice').eq(i).find('.control-group').eq(0).addClass('error');
        item.find('.choice').eq(i).find('.control-group').eq(0).find('.help-desc').after('<p class="help-block error-message">图片不能为空。</p>');
        cubeHtml.find('.index-' + i).trigger('click');
    }
};

/**
 * 链接弹出层
 */
var link_save_box = {};
function link_box(dom,typeArr,after_obj, msg){
	var domHtml;
	dom.hover(function(){
		if(dom.find('.dropdown-menu').size() == 0){
			if(typeArr.length == 0){
				//  page_goods_source
                if (msg && msg=='cube') {
                    var html_info = '<ul class="dropdown-menu" style="display:contents; z-index: 99999999;">';
                    html_info += '<li><a data-type="page" href="javascript:;">自定义页面</a></li>';
                    if (!is_kefu_mer) {
                    	// 自营商家 只保留 自定义页面 填写外链
	                    html_info += '<li><a data-type="good" href="javascript:;">选取商品</a></li>';
	                    html_info += '<li><a data-type="group" href="javascript:;">选取团购</a></li>';
	                    html_info += '<li><a data-type="product_list" href="javascript:;">商品列表页</a></li>';
	                    html_info += '<li><a data-type="home" href="javascript:;">店铺主页</a></li>';
	                    if (page_goods_source && page_goods_source==1) {
	                        html_info += '<li><a data-type="merchant_store" href="javascript:;">店铺列表页</a></li>';
	                        html_info += '<li><a data-type="merchant_shop_list" href="javascript:;">'+shop_alias_name+'店铺列表</a></li>';
						}
	                    html_info += '<li><a data-type="mycard" href="javascript:;">会员卡</a> </li>';
	                    html_info += '<li style="display:none;"><a data-type="ucenter" href="javascript:;">会员主页</a></li>';
	                    html_info += ( is_show_activity == 1 ? '<li><a data-type="activity_module" href="javascript:;">营销活动</a></li>' : '');
	                    html_info += '<li><a data-type="user_index" href="javascript:;">用户平台个人中心</a></li>';
	                }
                    html_info += '<li> <a data-type="scan" href="javascript:;">扫一扫</a></li>';
                    html_info += '<li> <a data-type="dining_self_take_store" href="javascript:;">餐饮-自取店铺列表</a></li>';
                    html_info += '<li> <a data-type="dining_store" href="javascript:;">餐饮-店铺列表</a></li>';
                    html_info += '<li> <a data-type="dining_book_store" href="javascript:;">餐饮-在线预订店铺列表</a></li>';
                    html_info += '<li> <a data-type="dining_eating_store" href="javascript:;">餐饮-通用码店铺列表</a></li>';
                    html_info += '<li> <a data-type="link" href="javascript:;">填写外链</a></li>';
                    html_info += '</ul>';
                    domHtml = $(html_info);
                } else {
                    var html_info = '<ul class="dropdown-menu" style="display:block; z-index: 99999999;">';
                    html_info += '<li><a data-type="page" href="javascript:;">自定义页面</a></li>';
                    if (!is_kefu_mer) {
                    	// 自营商家 只保留 自定义页面 填写外链
	                    html_info += '<li><a data-type="good" href="javascript:;">选取商品</a></li>';
	                    html_info += '<li><a data-type="group" href="javascript:;">选取团购</a></li>';
	                    html_info += '<li><a data-type="product_list" href="javascript:;">商品列表页</a></li>';
	                    html_info += '<li><a data-type="home" href="javascript:;">店铺主页</a></li>';
	                    if (page_goods_source && page_goods_source==1) {
	                        html_info += '<li><a data-type="merchant_store" href="javascript:;">店铺列表页</a></li>';
	                        html_info += '<li><a data-type="merchant_shop_list" href="javascript:;">'+shop_alias_name+'店铺列表</a></li>';
	                    }
	                    html_info += '<li><a data-type="mycard" href="javascript:;">会员卡</a> </li>';
	                    html_info += '<li style="display:none;"><a data-type="ucenter" href="javascript:;">会员主页</a></li>';
	                    html_info += ( is_show_activity == 1 ? '<li><a data-type="activity_module" href="javascript:;">营销活动</a></li>' : '');
	                    html_info += '<li><a data-type="user_index" href="javascript:;">用户平台个人中心</a></li>';
	                }
					if (is_hark != undefined && is_hark == 1 && page_goods_source && page_goods_source==1) {
	                	html_info += '<li><a data-type="shop" href="javascript:;">选取外卖店铺</a></li>';
	            	}
                    html_info += '<li> <a data-type="scan" href="javascript:;">扫一扫</a></li>';
                    html_info += '<li> <a data-type="dining_self_take_store" href="javascript:;">餐饮-自取店铺列表</a></li>';
                    html_info += '<li> <a data-type="dining_store" href="javascript:;">餐饮-店铺列表</a></li>';
                    html_info += '<li> <a data-type="dining_book_store" href="javascript:;">餐饮-在线预订店铺列表</a></li>';
                    html_info += '<li> <a data-type="dining_eating_store" href="javascript:;">餐饮-通用码店铺列表</a></li>';
                    html_info += '<li> <a data-type="link" href="javascript:;">填写外链</a></li>';
                    html_info += '</ul>';
                    domHtml = $(html_info);
                }
			}else{
				if (msg && msg=='cube') {
                    var domContent = '<ul class="dropdown-menu" style="display:contents; z-index: 99999999;">';
				} else {
                    var domContent = '<ul class="dropdown-menu" style="display:block; z-index: 99999999;">';
				}
				for(var i in typeArr){
					domContent += '<li><a data-type="'+typeArr[i]+'" href="javascript:;">';
					switch(typeArr[i]){
						case 'page':
						case 'pagecat':
							domContent += '微页面及分类';
							break;
						case 'page_only':
							domContent += '微页面';
							break;
						case 'pagecat_only':
							domContent += '微页面分类';
							break;
						case 'good':
						case 'goodcat':
							domContent += '商品及分组';
							break;
						case 'good_only':
							domContent += '商品';
							break;
						case 'good_only_pic':
							domContent += '商品及图片';
							break;
						case 'goodcat_only':
							domContent += '商品分组';
							break;
						case 'home':
							domContent += '店铺主页';
							break;
						case 'subject_type':
							domContent += '专题分类展示';
							break;
						case 'tuan':
							//domContent += '团购主页';
							break;
						case 'yydb':
							//domContent += '一元夺宝主页 ';
							break;
						case 'ucenter':
							domContent += '会员主页';
							break;
						case 'link':
							domContent += '自定义外链';
							break;
						case 'checkin':
							domContent += '我要签到';
						break;
					}
					domContent += '</a></li>';
				}
				domContent += '</ul>';
				domHtml = $(domContent);
			}
			dom.append(domHtml);
		}else{
			domHtml = dom.find('.dropdown-menu');
			domHtml.show();
		}
		var modalDom = {};
		domHtml.find('a').bind('click',function(){
			var type = $(this).data('type');
			if(type == 'home'){
				after_obj('home','店铺主页','店铺主页',wap_home_url);
				domHtml.trigger('mouseleave');
			}else if(type == 'subject_type'){
				after_obj('subject_type','专题分类展示页','专题分类展示页',wap_subject_type_url);
				domHtml.trigger('mouseleave');
			} else if (type == 'product_list') {
				after_obj('product_list','商品列表页','商品列表页',wap_product_list_url);
				domHtml.trigger('mouseleave');
			} else if (type == 'ucenter') {
				after_obj('home','会员主页','会员主页',wap_ucenter_url);
				domHtml.trigger('mouseleave');
			} else if(type == 'tuan'){
				after_obj('tuan','团购主页','团购主页',wap_tuan_url);
				domHtml.trigger('mouseleave');
			} else if(type == 'yydb'){
				after_obj('yydb','一元夺宝主页','一元夺宝主页',wap_yydb_url);
				domHtml.trigger('mouseleave');
			}else if(type == 'mycard'){
				after_obj('home','会员卡','会员卡',mycard_url);
				domHtml.trigger('mouseleave');
			}else if(type == 'store_coupon'){
				after_obj('home','优惠券列表','优惠券列表',coupon_url);
				domHtml.trigger('mouseleave');
			}else if(type == 'merchant_store'){
				after_obj('home','门店列表','门店列表页',merchant_store_url);
				domHtml.trigger('mouseleave');
			}else if(type == 'merchant_shop_list'){
                after_obj('home',shop_alias_name+'店铺列表',shop_alias_name+'店铺列表',merchant_shop_list_url);
                domHtml.trigger('mouseleave');
            }else if(type == 'user_index'){
                after_obj('home','用户平台个人中心','用户平台个人中心',user_index );
                domHtml.trigger('mouseleave');
            }else if(type == 'scan'){
                after_obj('home','扫一扫','扫一扫','scan' );
                domHtml.trigger('mouseleave');
            }else if(type == 'dining_self_take_store'){
                after_obj('home','餐饮-自取店铺列表','餐饮-自取店铺列表',dining_self_take_store );
                domHtml.trigger('mouseleave');
            }else if(type == 'dining_store'){
                after_obj('home','餐饮-店铺列表','餐饮-店铺列表',dining_store );
                domHtml.trigger('mouseleave');
            }else if(type == 'dining_book_store'){
                after_obj('home','餐饮-在线预订店铺列表','餐饮-在线预订店铺列表',dining_book_store );
                domHtml.trigger('mouseleave');
            }else if(type == 'dining_eating_store'){
                after_obj('home','餐饮-通用码店铺列表','餐饮-通用码店铺列表',dining_eating_store );
                domHtml.trigger('mouseleave');
            }else if(type == 'link'){
				button_box(dom,event,'bottom','url','链接地址：http://example.com',function(){
					var url = $('.js-link-placeholder').val();
					console.log('url', url);
					if(url != '') {
						if (!check_url(url)){
							url = 'http://' + url;
						}
						after_obj('link','外链',url,url);
						close_button_box();
					} else {
						return false;
					}
				});
				domHtml.trigger('mouseleave');
			}else{
				$('.modal-backdrop,.modal').remove();
				$('body').append('<div class="modal-backdrop fade in widget_link_back"></div>');
				var randNum = getRandNumber();
				if(type.substr(-4,4) == 'only'){
					var load_url = '?c=Diypage&a='+type.replace('_only','')+'&only=1&store_id='+store_id+'&number='+randNum+'&shop_type='+page_goods_source;
				}else{
					var load_url = '?c=Diypage&a='+type+'&store_id='+store_id+'&number='+randNum+'&shop_type='+page_goods_source;
				}
				link_save_box[randNum] = after_obj;
				modalDom = $('<div class="modal fade js-modal in widget_link_box" aria-hidden="false" style="margin-top:0px;display:block;"><iframe src="'+load_url+'" style="width:100%;height:200px;border:0;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px;"></iframe></div>');
				$('body').append(modalDom);
				modalDom.animate({'margin-top': ($(window).scrollTop() + $(window).height() * 0.05) + 'px'}, "slow", function(){
					modalDom.css({'position':'fixed','top':(($(window).height() - 540)/2),'margin-top':'0px'});
				});
				$('.modal-backdrop').click(function(){
					login_box_close();
				});
			}
		});
	},function(e){
		domHtml.hide().find('a').unbind('click');
	});
}

/*
 * 小的弹出层
 *
 * param dom	  弹出层的ID 				使用 $(this);
 * param e	      弹出层的ID点击返回事件 	使用 event;
 * param position 方向  					left,top,right,bottom
 * param type     弹出层的类别  			copy,edit_txt,edit_txt_2delete,confirm,multi_txt,radio,input,url,module, te
 * param content  内容
 * param ok_obj   点击确认键的回调方法
 * param placeholder 点位符
 */
function button_box(dom,event,position,type,content,ok_obj,placeholder){
	var cancel_obj = arguments[7];
	event.stopPropagation();
	var left=0,top=0,width=0,height=0;
	var dom_offset = dom.offset();
	$('.popover').remove();
	if(type=='copy'){
		$.getScript('./static/js/plugin/jquery.zclip.min.js',function(){
			$('body').append('<div class="diypage popover '+position+'" style="left:-'+($(window).width()*5)+'px;top:'+$(window).scrollTop()+($(window).height()/2)+'px;"><div class="arrow"></div><div class="popover-inner"><div class="popover-content"><div class="form-inline"><div class="input-append"><input type="text" class="txt js-url-placeholder url-placeholder" readonly="" value="'+content+'"/><button type="button" class="btn js-btn-copy">复制</button></div></div></div></div></div>');
			$('.popover .js-btn-copy').zclip({
				path:'./static/js/plugin/ZeroClipboard.swf',
				copy:function(){
					return content;
				},
				afterCopy:function(){
					$('.popover').remove();
					layer_tips(0,'复制成功');
				}
			});
			button_box_after();
		});
	}else if(type=='edit_txt'){
		$('body').append('<div class="diypage popover '+position+'" style="left:-'+($(window).width()*5)+'px;top:'+$(window).scrollTop()+'px;"><div class="arrow"></div><div class="popover-inner popover-rename"><div class="popover-content"><div class="form-horizontal"><div class="control-group"><div class="controls"><input type="text" class="js-rename-placeholder" maxlength="256"/> <button type="button" class="btn btn-primary js-btn-confirm">确定</button> <button type="reset" class="btn js-btn-cancel">取消</button></div></div></div></div></div></div>');
		$('.js-rename-placeholder').attr('placeholder', content).focus();
		button_box_after();
	} else if (type=='edit_txt_2') {
		$('body').append('<div class="diypage popover '+position+'" style="left:-'+($(window).width()*5)+'px;top:'+$(window).scrollTop()+'px;"><div class="arrow"></div><div class="popover-inner popover-rename"><div class="popover-content"><div class="form-horizontal"><div class="control-group"><div class="controls">' + content.title_1 + ':<input type="text" class="js-rename-placeholder" maxlength="256"/> <br /><br />' + content.title_2 + ':<input type="text" class="js-keyword-placeholder" maxlength="100" style="width: 100px;" /> <button type="button" class="btn btn-primary js-btn-confirm">确定</button> <button type="reset" class="btn js-btn-cancel">取消</button></div></div></div></div></div></div>');
		$('.js-rename-placeholder').attr('placeholder', content.input_1).focus();
		$('.js-keyword-placeholder').attr('placeholder', content.input_2).focus();
		button_box_after();
	} else if (type=='input') {
        $('body').append('<div class="diypage popover '+position+'" style="left:-'+($(window).width()*5)+'px;top:'+$(window).scrollTop()+'px;"><div class="arrow"></div><div class="popover-inner popover-rename"><div class="popover-content"><div class="form-horizontal"><div class="control-group"><div class="controls"><input type="text" class="js-rename-placeholder" maxlength="256"/> <button type="button" class="btn btn-primary js-btn-confirm">确定</button> <button type="reset" class="btn js-btn-cancel">取消</button></div></div></div></div></div></div>');
        if (placeholder) {
            $('.js-rename-placeholder').attr('placeholder', placeholder);
        }
        $('.js-rename-placeholder').val(content).focus();
        button_box_after();
    } else if(type=='multi_txt') {
        $('body').append('<div class="diypage popover ' + position + '" style="left:-' + ($(window).width() * 5) + 'px;top:' + $(window).scrollTop() + 'px;"><div class="arrow"></div><div class="popover-inner popover-chosen"><div class="popover-content"><div class="select2-container select2-container-multi js-select2 select2-dropdown-open" style="width:242px;display:inline-block;"><ul class="select2-choices"><li class="select2-search-field">    <input type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" class="select2-input" id="s2id_autogen26" tabindex="-1" style="width:192px;"></li></ul></div> <button type="button" class="btn btn-primary js-btn-confirm" data-loading-text="确定">确定</button> <button type="reset" class="btn js-btn-cancel">取消</button></div></div></div>');
        $('.popover-chosen .select2-input').attr('placeholder', content).focus();
        multi_choose_obj();
        button_box_after();
    }else if(type=='multi_txt2') {
        var cccat_id = content.cats_id;
        $('body').append('<div class="diypage popover ' + position + '" style="left:-' + ($(window).width() * 5) + 'px;top:' + $(window).scrollTop() + 'px;"><div class="arrow"></div><div class="popover-inner popover-chosen"><div class="popover-content"><div class="select2-container select2-container-multi js-select2 select2-dropdown-open" style="width:242px;display:inline-block;"><ul class="select2-choices"><li class="select2-search-field">    <input type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" class="select2-input" id="s2id_autogen26" tabindex="-1" style="width:192px;"></li></ul></div> <button type="button" data-button-cat-id="'+cccat_id+'"  class="btn btn-primary js-btn-confirm" data-loading-text="确定">确定</button> <button type="reset" class="btn js-btn-cancel">取消</button></div></div></div>');
        $('.popover-chosen .select2-input').attr('placeholder', content.contents).focus();
       // multi_choose_obj();
        multi_choose_obj2(content.arr,content.has_atom_id);
        button_box_after();
    } else if (type == 'radio') {
        $('body').append('<div class="diypage popover ' + position + '" style="top: ' + $(window).scrollTop() + 'px; left: -' + ($(window).width()*5) + 'px;"><div class="arrow"></div><div class="popover-inner popover-change"><div class="popover-content text-center"><form class="form-inline"><label class="radio"><input type="radio" name="discount" value="1" checked="">参与</label><label class="radio"><input type="radio" name="discount" value="0">不参与</label><button type="button" class="btn btn-primary js-btn-confirm" data-loading-text="确定">确定</button><button type="reset" class="btn js-btn-cancel">取消</button></form></div></div></div>');
        button_box_after();
    } else if (type == 'url') {
    	var yinxiao_btn = '';
	
		var button_h = $('<div class="diypage popover '+position+'" style="left:-'+($(window).width()*5)+'px;top:'+$(window).scrollTop()+'px;"><div class="arrow"></div><div class="popover-inner popover-rename"><div class="popover-content"><div class="form-horizontal"><div class="control-group"><div class="controls"><input type="text" class="link-placeholder js-link-placeholder" placeholder="' + content + '" /> ' + yinxiao_btn + '  <button type="button" class="btn btn-primary js-btn-confirm">确定</button> <button type="reset" class="btn js-btn-cancel">取消</button></div></div></div></div></div></div>');
        button_h.find('.js-btn-link').click(function(){
			$.layer({
        		type : 2,
        		title: '插入功能库链接',
        		shadeClose: true,
       			maxmin: true,
        		fix : false,  
        		area: ['600px','450px'],
       			iframe: {
        		    src : '?c=link&a=index'
       			}
    		});
		});
		$('body').append(button_h);
        $('.js-link-placeholder').focus();
        button_box_after();
    } else if (type == 'module') {
        $('body').append('<div class="popover '+ position + '" style="left:'+(dom_offset.left - 178)+'px;top:' + (dom_offset.top - 500) + 'px;"><div class="arrow"></div><div class="popover-inner popover-text"><div class="popover-content"><form class="form-horizontal"><div class="control-group"><label class="control-label">请设置模块名称：</label><div class="controls"><input type="text" class="text-placeholder js-text-placeholder"></div></div><div class="form-actions"><button type="button" class="btn btn-primary js-btn-confirm" data-loading-text="确定"> 确定</button><button type="reset" class="btn js-btn-cancel">取消</button></div></form></div></div></div>');
        $('.js-text-placeholder').focus();
        $('.js-text-placeholder').val(content);
        button_box_after();
        $('.popover').css({top:(dom_offset.top - dom.height() - 115), left: dom_offset.left - ($('.popover').width() / 2) + 20});
    } else if(type == 'tips') {
        $('body').append('<div class="popover '+position+'" style="display:block;left:-'+($(window).width()*5)+'px;top:'+$(window).scrollTop()+'px;"><div class="arrow"></div><div class="popover-inner popover-'+type+'"><div class="popover-content text-center"><div class="form-inline"><span class="help-inline item-delete">'+content+'</span><button type="button" class="btn btn-primary js-btn-confirm">确定</button> </div></div></div></div>');
        button_box_after();
    }
    else{
		$('body').append('<div class="popover '+position+'" style="display:block;left:-'+($(window).width()*5)+'px;top:'+$(window).scrollTop()+'px;"><div class="arrow"></div><div class="popover-inner popover-'+type+'"><div class="popover-content text-center"><div class="form-inline"><span class="help-inline item-delete">'+content+'</span><button type="button" class="btn btn-primary js-btn-confirm">确定</button> <button type="reset" class="btn js-btn-cancel">取消</button></div></div></div></div>');
		button_box_after();
	}

	function button_box_after(){
		$('.popover .js-btn-cancel').one('click',function(){
			if (cancel_obj != undefined) {
				cancel_obj();
			} else {
				close_button_box();
			}
		});
		$('.popover .js-btn-confirm').one('click',function(){
			if(ok_obj){
				ok_obj();
			} else {
				close_button_box();
			}
		});
		$('.popover').click(function(e){
			e.stopPropagation();
		});
		if (cancel_obj == undefined) {
			$('body').bind('click',function(){
				close_button_box();
			});
		}

		var popover_height = $('.popover').height();
		var popover_width = $('.popover').width();
		switch(position){
			case 'left':
				$('.popover').css({top:dom_offset.top-(popover_height+10-dom.height())/2,left:dom_offset.left-popover_width-14});
				break;
            case 'right':
                $('.popover').css({top:dom_offset.top-(popover_height+10-dom.height())/2,left:dom_offset.left+dom.width() + 27});
                $('.popover-confirm').css('margin-left', '0');
                break;
            case 'top':
                $('.popover').css({top:(dom_offset.top - dom.height() - 40),left:dom_offset.left - (popover_width/2) + (dom.width()/2)});
                break;
			case 'bottom':
				$('.popover').css({top:dom_offset.top+dom.height()-3,left:dom_offset.left - (popover_width/2) + (dom.width()/2)});
				break;
		}
	}
	//添加商品添加规格专用方法
	function multi_choose_obj(){
		$('.popover-chosen .select2-input').keyup(function(event){
			var input_select2 = $.trim($(this).val());
			if(event.keyCode == 13 && input_select2.length != 0){
				var html = $('<li class="select2-search-choice"><div>'+input_select2+'</div><a href="#" class="select2-search-choice-close" tabindex="-1" onclick="$(this).closest(\'li\').remove();$(\'.popover-chosen .select2-input\').focus();"></a></li>');
				if($('.popover-chosen .select2-choices .select2-search-choice').size() > 0){
					var has_li = false;
					$.each($('.popover-chosen .select2-choices .select2-search-choice'),function(i,item){
						if($(item).find('div').html() == input_select2){
							has_li = true;
							return false;
						}
					});
					if(has_li === false){
						$('.popover-chosen .select2-choices .select2-search-choice:last').after(html);
					}else{
						layer_tips(1,'已经存在相同的规格');
						$(this).val('').focus();
						return;
					}
				}else{
					$('.popover-chosen .select2-choices').prepend(html);
				}
				
				var r = getRandNumber();
				html.attr('data-vid', r);
				html.attr('check-data-vid', r);
				
				$.post(get_property_value_url,{pid:dom.closest('.sku-sub-group').find('.js-sku-name').attr('data-id'),txt:input_select2},function(result){
					if(result.err_code == 0){
						html.attr('data-vid',result.err_msg);
						
						if ($("#r_" + r).size() > 0) {
							$("#r_" + r).attr("atom-id", result.err_msg);
						}
					}else{
						layer_tips(result.err_msg);
						html.remove();
					}
				});
				$(this).removeAttr('placeholder').val('').focus();
			}
		});
	}
    //查询商品属性规格专用方法  array(1,2,3)
    function multi_choose_obj2(strss,arr_has_atom_id){

        var html;
         $('.popover-chosen .select2-choices .select2-search-choice').detach('');
        for(var i in strss) {
           // html +=  '<li class="select2-search-choice"  onclick="$(this).addClass(\'choice\');"  data-vid='+strss[i].pid+'"><div>'+strss[i].value+'</div><a href="#" class="select2-search-choice-select" tabindex="-1"  onclick="$(\'.popover-chosen .select2-input\').focus();"></a></li>';
           if(jQuery.inArray(strss[i].vid,arr_has_atom_id)=='-1') {
             html +=  '<li class="select2-search-choice cursor"  onclick="javascript:if($(this).attr(\'idd\')==\'choice\'){ $(this).removeClass(\'choice\').attr(\'idd\',\'\'); } else{$(this).addClass(\'choice\').attr(\'idd\',\'choice\');}"  data-vid='+strss[i].vid+'"><div>'+strss[i].value+'</div><a href="javascript:" class="select2-search-choice-select" tabindex="-1"  onclick="$(\'.popover-chosen .select2-input\').focus();"></a></li>';
           }
        }
        var htmls = $(html);

        $('.popover-chosen .select2-choices').prepend(htmls);
        //包所有属性值 放入 容器中
        $('.popover-chosen .select2-input').keyup(function(event){


        })
    }
}
function close_button_box(){
	$('.popover').remove();
}




/////////////
/*
 * 上传图片弹出层
 *
 * param maxsize    最大上传尺寸            int 单位M
 * param showLocal  是否展示已上传图片列表  bool
 * param obj 	    回调函数                object
 * param maxnum     最多使用的图片数量      int
 */
var upload_local_result = [];
function upload_pic_box(maxsize,showLocal,obj,maxnum){
	var upload_pic = [],oknum = 0,nowImagePage=1;
	if(!showLocal) showLocal = false;
	if(!maxnum) maxnum = 0;
	if ($('.modal-backdrop').length > 0) {
		html = '';
	} else {
		var html = '<div class="modal-backdrop fade in"></div>';
	}
	var widgetDom = $('<div class="widget-image modal fade in" style="top:-350px;"><div class="modal-header"><a class="close" data-dismiss="modal">×</a><ul class="module-nav modal-tab js-modal-tab"><li class="js-modal-tab-item js-modal-tab-image'+(showLocal ? '' : ' hide')+'"><a href="javascript:;" data-pane="image">用过的图片</a><span>|</span></li><li class="js-modal-tab-item js-modal-tab-upload active"><a href="javascript:;" data-pane="upload">新图片</a></li></ul></div>'+(showLocal ? '<div class="tab-pane js-tab-pane js-tab-pane-image js-image-region hide"><div class="widget-list"><div class="modal-body"><div class="js-list-filter-region clearfix ui-box" style="position:relative;min-height:28px;"><div class="widget-list-filter"><div class="widget-image-refresh"><span>点击图片即可选中</span> <a href="javascript:;" class="js-refresh">刷新</a></div><div class="js-list-search ui-search-box"><input class="txt" type="text" placeholder="搜索" value=""/></div></div></div><div class="ui-box"><ul class="js-list-body-region widget-image-list"></ul><div class="js-list-empty-region"><div><div class="no-result widget-list-empty">还没有相关数据。</div></div></div></div></div><div class="modal-footer js-list-footer-region"><div class="widget-list-footer"><div class="left"><a href="javascript:;" class="ui-btn ui-btn-primary js-choose-image hide">确定使用</a></div><div class="pagenavi"></div></div></div></div></div>' : '')+'<div class="tab-pane js-tab-pane js-tab-pane-upload js-upload-region"><div>' + '<div class="js-upload-local-region"><div><div class="modal-body"><div class="upload-local-img"><form class="form-horizontal"><div class="control-group"><label class="control-label">本地图片：</label><div class="controls"><div class="control-action"><ul class="js-upload-image-list upload-image-list clearfix ui-sortable"><li class="fileinput-button js-add-image" data-type="loading"><a class="fileinput-button-icon" href="javascript:;">+</a></li></ul><!--<p class="help-desc">推荐960*960宽高等比的图片会有更好的展示效果</p>--><p class="help-desc">最大支持 1 MB 的图片( jpg / gif / png )，不能选中大于 1 MB 的图片</p></div></div></div></form></div></div><div class="modal-footer"><div class="modal-action right"><input type="button" class="btn btn-primary js-upload-image-btn" value="上传完成"/></div></div></div></div></div></div></div>');
	
	$(".js-add-image").live("click", function () {
		if ($(this).data("type") == "loading") {
			layer_tips(1, "网速慢，加载中，请稍等");
			return;
		}
	});
	
	widgetDom.find('.close,.js-upload-image-btn').click(function(){
		if(!$(this).hasClass('close')){
			//if(obj) obj(upload_pic);
			if (obj) {
				var pic_arr = [];
				$(".js-upload-image-list").find("img").each(function () {
					pic_arr.push($(this).attr("src"));
				});
				obj(pic_arr);
			}
		}
		if ($('body > .modal:visible').length > 1) {
			$('.widget-image').animate({'margin-top': '-' + ($(window).scrollTop() + $(window).height()) + 'px'}, "slow", function(){
				$('.widget-image').remove();
			});
		} else {
			$('.widget-image,.modal-backdrop').animate({'margin-top': '-' + ($(window).scrollTop() + $(window).height()) + 'px'}, "slow", function(){
				$('.widget-image,.modal-backdrop').remove();
			});
		}
	});
	$('.js-upload-image-list .js-remove-image').live('click',function(){
		$.post('./user.php?c=attachment&a=attachment_del',{pigcms_id:$(this).attr('file-id')});
		$(this).closest('li').remove();
		
	});
	
	//回车提交搜索
	$(window).keydown(function(event){
		if (event.keyCode == 13 && widgetDom.find(".js-list-search input").is(':focus')) {
			var keyword = widgetDom.find(".js-list-search input").val();
			var old_keyword = widgetDom.find(".js-list-search input").data("old_keyword");

			if (typeof old_keyword == "undefined") {
				widgetDom.find(".js-list-search input").data("old_keyword", "");
				old_keyword = "";
			}

			if (old_keyword == keyword) {
				return;
			}
			widgetDom.find(".js-list-search input").data("old_keyword", keyword);
			getLocalFun(-1);
		}
	});
	
	var getLocalFun = function(page){
		if(page == -1){
			upload_local_result = [];
			nowImagePage = 1;
			page = 1;
		}
		var keyword = widgetDom.find(".js-list-search input").val();
		$.post(imageList,{page:page,keyword:keyword},function(result){
			if(!upload_local_result[page]){
				upload_local_result[page] = {};
			}
			upload_local_result[page] = result.info;
			
			showLocalFun();
		});
	};
	
	var showLocalFun = function(){
		if(upload_local_result[nowImagePage].count){
			widgetDom.find('.js-list-empty-region').empty();
			var html = '';
			for(var i in upload_local_result[nowImagePage].image_list){
				var nowImage = upload_local_result[nowImagePage].image_list[i];
				var selected = "";
				if (typeof upload_pic[nowImage.pigcms_id] != "undefined") {
					selected = "selected";
				}
				if (nowImage.pigcms_id == undefined) {
					continue;
				}
				html += '<li class="widget-image-item ' + selected + '" data-id="'+nowImage.pigcms_id+'" data-image="'+nowImage.pic+'"><div class="js-choose" title="'+(nowImage.img_remark ? nowImage.img_remark : '')+'"><div class="widget-image-item-content" style="background-image:url('+nowImage.pic+')"></div><div class="widget-image-meta">'+(nowImage.img_width > 0 ? (nowImage.img_width+'x'+nowImage.img_height) : '')+'</div><div class="selected-style"><i class="icon-ok icon-white"></i></div></div></li>';
			}
			widgetDom.find('.js-list-body-region').html(html);
			widgetDom.find('.pagenavi').html(upload_local_result[nowImagePage].page_bar);

			widgetDom.find('.pagenavi a').click(function(){
				nowImagePage = $(this).data('page-num');
				if(upload_local_result[nowImagePage]){
					showLocalFun();
				}else{
					getLocalFun(nowImagePage);
				}
			});

			if(maxnum == 1){
				widgetDom.find('.widget-image-item').click(function(){
					upload_pic[$(this).data('id')] = $(this).data('image');
					if(obj) obj(upload_pic);
					$('.widget-image,.modal-backdrop').remove();
				});
			}else{
				widgetDom.find('.widget-image-item').click(function(){
					if($(this).hasClass('selected')){
						$(this).removeClass('selected');
						delete upload_pic[$(this).data('id')];
						if(widgetDom.find('.widget-image-item.selected').size() == 0){
							widgetDom.find('.js-choose-image').addClass('hide');
						}
					}else{
						if(maxnum > 0 && widgetDom.find('.widget-image-item.selected').size() >= maxnum){
							layer_tips(1,'最多只能选取 '+maxnum+' 张');
						}else{
							widgetDom.find('.js-choose-image').removeClass('hide');
							$(this).addClass('selected');
							upload_pic[$(this).data('id')] = $(this).data('image');
						}
					}
				});
			}
		}else{
			widgetDom.find('.js-list-body-region').empty();
			widgetDom.find('.pagenavi').empty();
			widgetDom.find('.js-list-empty-region').html('<div><div class="no-result widget-list-empty">还没有相关数据。</div></div>');
		}
	};
	
	widgetDom.find('.js-choose-image').click(function(){
		if (obj) {
			var pic_arr = upload_pic.reverse();
			obj(pic_arr);
		}
		if ($('body > .modal:visible').length > 1) {
			$('.widget-image').remove();
		} else {
			$('.widget-image,.modal-backdrop').remove();
		}
	});
	
	if(showLocal){
		if(upload_local_result.length == 0){
			getLocalFun(nowImagePage);
		}else{
			showLocalFun();
		}
		widgetDom.find('.js-modal-tab a').click(function(){
			if(!$(this).closest('li').hasClass('active')){
				$(this).closest('li').addClass('active').siblings('li').removeClass('active');
				$('.js-tab-pane-'+$(this).data('pane')).removeClass('hide').siblings('.js-tab-pane').addClass('hide');
			}
		});
		widgetDom.find('.js-image-region .js-refresh').click(function(){
			getLocalFun(-1);
		});
	}
	var imageDom = widgetDom.find('.js-web-img-input');
	var imageUrlError = function(tips){
		layer_tips(1,tips);
		imageDom.focus();
		imageBtnDom.val('提取').prop('disabled',false);
	}

	$('body').append(html);
	$('body').append(widgetDom);
	widgetDom.animate({
		'top': ($(window).scrollTop() + $(window).height() * 0.2) + 'px'
	},100);
	$.getScript('./static/js/webuploader.min.js',function(){
		$(".js-add-image").data("type", "load");
		if(!WebUploader.Uploader.support()){
			alert( '您的浏览器不支持上传功能！如果你使用的是IE浏览器，请尝试升级 flash 播放器');
			$('.widget-image,.modal-backdrop').remove();
		}
		var uploader = WebUploader.create({
				auto: true,
				swf: './static/js/Uploader.swf',
				server: uploadJson,
				pick: {
					id: '.js-add-image',
					innerHTML: '<a class="fileinput-button-icon" href="javascript:;">+</a>'
				},
				accept: {
					title: 'Images',
					extensions: 'gif,jpg,jpeg,png',
					mimeTypes: 'image/gif,image/jpeg,image/jpg,image/png'
				},
				fileSingleSizeLimit: maxsize * 1024 * 1024,
				duplicate:true
			});
			uploader.on('fileQueued',function(file){
				var pic_loading_dom = $('<li class="upload-preview-img sort loading uploadpic-'+file.id+'">');
				$('.js-add-image').before(pic_loading_dom);
			});
			uploader.on('uploadProgress',function(file,percentage){

			});
			uploader.on('uploadBeforeSend',function(block,data){
				data.maxsize = maxsize;
			});
			uploader.on('uploadSuccess',function(file,response){
				if(response.error == '0'){
					upload_pic[response.pigcms_id] = response.url;
					$('.uploadpic-'+response['id']).removeClass('loading').html('<img src="'+response.url+'"/><a href="javascript:;" class="close-modal small js-remove-image" file-id="'+response.pigcms_id+'">×</a>');
					if(maxnum == 1 && oknum == 0 && obj){
						obj(upload_pic);
						$('.widget-image,.modal-backdrop').remove();
					}
					oknum++;
				}else{
					$('.uploadpic-'+response['id']).remove();
					layer_tips(1,response.err_msg);
				}
			});

			uploader.on('uploadError', function(file,reason){
				$('.uploadpic-'+response['id']).remove();
				layer_tips(1,'上传失败！请重试。');
			});

	});

}
/**
 * 挂件选择弹出层
 */
var widget_link_save_box = {};

function widget_link_box(dom,type,after_obj,items){
	// alert(1)
	var radio = arguments[4] || 0; //是否单选
	//点击事件
    console.log('dom',dom);
	if(type=='select_store'){
		dom = $(dom)
    	var cate_num = dom.data('cate_num');
    	items = dom.data('store_arr')
    }
    console.log('dom_items',items);
    
    console.log('cate_num',cate_num);
    console.log('items',items);
	// $(document).on('click',dom,function(){
	dom.click(function(){
		//移除
		$('.modal-backdrop,.modal').remove();

		//增加
		$('body').append('<div class="modal-backdrop fade in widget_link_back"></div>');

		//赋值
		var randNum = getRandNumber();
		var load_url = '?c=Diypage&a='+type+'&store_id='+store_id+'&type=more&number='+randNum+'&radio=' + radio+'&cate_num=' + cate_num;
		if(typeof shop_type!='undefined' && type=='good' && shop_type==2){
			if(typeof page_goods_source!='undefined'){
				load_url+='&shop_type='+page_goods_source;
			}else{
				load_url+='&shop_type='+shop_type;
			}
		}
		if((type=='select_store'||type=='good'||type=='good_group'|type=='good_group&only=1'|| type=='activity_module'||type=='goodcat&only=1')&&items!=undefined){		// 已选取的商品置灰
			var itemArr = [];
			$.each(items,function(i,v){
				if(v!=undefined){
					itemArr[i] = v.id;
				}
			});
			load_url += '&selecteditems='+itemArr;
		} else if (type.indexOf("activity_info") != -1 ) {
            var itemArr = [];
            items.state.activities.forEach(function(item,index){
                console.log('item_'+index, item);
                if(item!=undefined){
                    itemArr[index] = item.id;
                }
            });
            if(typeof page_goods_source!='undefined'){
                load_url+='&shop_type='+page_goods_source;
            }else{
                load_url+='&shop_type='+shop_type;
            }
            console.log('itemArr', itemArr);
            load_url += '&selecteditems='+itemArr;
		}
		console.log('load_url',load_url);
		widget_link_save_box[randNum] = after_obj;

		modalDom = $('<div class="modal fade js-modal in widget_link_box" aria-hidden="false" style="margin-top:0px;display:block;"><iframe src="'+load_url+'" style="width:100%;height:200px;border:0;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px;"></iframe></div>');

		//增加
		$('body').append(modalDom);

		//动画
		modalDom.animate({'margin-top': ($(window).scrollTop() + $(window).height() * 0.05) + 'px'}, "slow", function(){
			modalDom.css({'position':'fixed','top':(($(window).height() - 540)/4),'margin-top':'0px'});
		});

		//点击关闭
		$('.modal-backdrop').click(function(){
			login_box_close();
		});
	});
}

// 弹出选择店铺页面
function widget_link_store(dom,type,after_obj,items){
	// alert(1)
	var radio = arguments[4] || 0; //是否单选
	//点击事件
    console.log('dom',dom);
	if(type=='select_store'){
		dom = $(dom)
    	var cate_num = dom.data('cate_num');
    	items = dom.data('store_arr')
    }

	//移除
	$('.modal-backdrop,.modal').remove();

	//增加
	$('body').append('<div class="modal-backdrop fade in widget_link_back"></div>');

	//赋值
	var randNum = getRandNumber();
	var load_url = '?c=Diypage&a='+type+'&store_id='+store_id+'&type=more&number='+randNum+'&radio=' + radio+'&cate_num=' + cate_num;
	if(typeof shop_type!='undefined' && type=='good' && shop_type==2){
		if(typeof page_goods_source!='undefined'){
			load_url+='&shop_type='+page_goods_source;
		}else{
			load_url+='&shop_type='+shop_type;
		}
	}
	if(type=='select_store'&&items!=undefined){		// 已选取的商品置灰
		var itemArr = [];
		$.each(items,function(i,v){
			if(v!=undefined){
				itemArr[i] = v.id;
			}
		});
		load_url += '&selecteditems='+itemArr;
	}

	widget_link_save_box[randNum] = after_obj;

	modalDom = $('<div class="modal fade js-modal in widget_link_box" aria-hidden="false" style="margin-top:0px;display:block;"><iframe src="'+load_url+'" style="width:100%;height:200px;border:0;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px;"></iframe></div>');

	//增加
	$('body').append(modalDom);

	//动画
	modalDom.animate({'margin-top': ($(window).scrollTop() + $(window).height() * 0.05) + 'px'}, "slow", function(){
		modalDom.css({'position':'fixed','top':(($(window).height() - 540)/4),'margin-top':'0px'});
	});

	//点击关闭
	$('.modal-backdrop').click(function(){
		login_box_close();
	});
}

/**
 * 挂件选择弹出层优惠券 非传递 仅仅显示优惠券
 */
function widget_link_yhq(dom,type,after_obj){
	//点击
	dom.click(function(){
		try{
			var customs     = checkEvent();
			var items = [];
			var k = 0;
			$.each(customs,function(ii,vv){
				if(vv.type=='coupons'){
					$.each(vv.coupon_arr,function(iii,vvv){
						items[k] = vvv;
						k++;	
					});
				}
			});
		}catch(e) {
			
		}
		//移除
		$('.modal-backdrop,.modal').remove();

		//添加
		$('body').append('<div class="modal-backdrop fade in widget_link_back"></div>');

		//赋值
		var randNum = getRandNumber();
		var load_url = '?c=Diypage&a='+type+'&store_id='+store_id+'&number='+randNum;
		if (is_kefu_mer) {
			load_url += '&type=system'
		}
		if((type=='coupon')&&items!=undefined){		// 已选取的商品置灰
			var itemArr = [];
			$.each(items,function(i,v){
				if(v!=undefined){
					itemArr[i] = v.id;
				}
			});
			load_url += '&selecteditems='+itemArr;
		}
		widget_link_save_box[randNum] = after_obj;


		//添加
		modalDom = $('<div class="modal fade js-modal in widget_link_box" aria-hidden="false" style="margin-top:0px;display:block;"><iframe src="'+load_url+'" style="width:100%;height:200px;border:0;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px;"></iframe></div>');
		$('body').append(modalDom);
		
		//动画
		modalDom.animate({'margin-top': ($(window).scrollTop() + $(window).height() * 0.05) + 'px'}, "slow", function(){
			modalDom.css({'position':'fixed','top':(($(window).height() - 540)/2),'margin-top':'0px'});
		});

		//关闭
		$('.modal-backdrop').click(function(){
			login_box_close();
		});
	});
}


function store_after(result,index ){
	var shopCatList = $('.control-group .addCategoryBtn').data('shopCatList');
	if (typeof(shopCatList)=='undefined') {
		shopCatList = [];
	}
	if (shopCatList[index]) {
		if (shopCatList[index].store_list != undefined) {
			$.merge(shopCatList[index].store_list,result);
		}else{
			shopCatList[index].store_list = result;
		}
	}else{
		shopCatList[index].store_list = result;
	}

	var store_arr = shopCatList[index].store_list;

	$('.control-group .addCategoryBtn').data('shopCatList',shopCatList);

	var html = '';
	var shtml='';
	for(var i in store_arr){
		if (store_arr[i]) {
            shtml+='<div class="store-item" data-title="'+store_arr[i]["title"]+'" id="'+store_arr[i]["id"]+'" style="position:relative;padding: 4px;"><a href="javascript:;" ' + (i == 0 ? 'class="current"' : '') + '><span>'+store_arr[i]["title"]+'</span></a><a class="close-modal js-delete-store small" data-store_id="'+store_arr[i]["id"]+'" title="删除">×</a></div>';
		}
	}

	$('.js-sidebar-region').find("li[data-id="+index+"]").find('.store_list').empty().html(shtml);
	$('.js-sidebar-region').find("a[data-cate_num="+index+"]").data('store_arr',store_arr);

	$('.js-delete-store').click(function(){
		var dataId = $(this).parents('.choice').attr('data-id');
		var storeId = $(this).attr('data-store_id');
		for(var i in store_arr){
            if (store_arr[i]) {
				if(store_arr[i].id==storeId){
					delete store_arr[i]; 
					$(this).parents('div.store-item').remove();
				} 
            }
		}
		shopCatList[index].store_list = store_arr;
		$('.control-group .addCategoryBtn').data('shopCatList',shopCatList);
		$('.js-sidebar-region').find("a[data-cate_num="+index+"]").data('store_arr',store_arr);
		$('.js-sidebar-region').find("li[data-id="+index+"]").find('.store_list').empty().html(shtml);
	});
}

function widget_box_after(number,data,index){
	if (widget_link_save_box[number] == 'store_after') {
		store_after(data,index);
	}else{
		widget_link_save_box[number](data,index);
	}
	login_box_close();
}

/**
 * 生成一个唯一数
 */
function getRandNumber(){
	var myDate=new Date();
	return myDate.getTime() + '' + Math.floor(Math.random()*10000);
}
function login_box_close(){
	$('.widget_link_box').animate({'margin-top': '-' + ($(window).scrollTop() + $(window).height()) + 'px'}, "slow",function(){
		$('.widget_link_back,.widget_link_box').remove();
	});
}
/**
 *
 * @param url
 * @returns {boolean}
 */
function check_url(url){
    var reg = new RegExp();
    reg.compile("^(http|https)://.*?$");
    if(!reg.test(url)){
        return false;
    }
    return true;
}
var obj2String = function(_obj) {
    var t = typeof(_obj);
    if (t != 'object' || _obj === null) {
        // simple data type
        if (t == 'string') {
            _obj = '"' + _obj + '"';
        }
        return String(_obj);
    } else {
        if (_obj instanceof Date) {
            return _obj.toLocaleString();
        }
        // recurse array or object
        var n, v, json = [],
        arr = (_obj && _obj.constructor == Array);
        for (n in _obj) {
            v = _obj[n];
            t = typeof(v);
            if (t == 'string') {
                v = '"' + v + '"';
            } else if (t == "object" && v !== null) {
                v = this.obj2String(v);
            }
            json.push((arr ? '': '"' + n + '":') + String(v));
        }
        return (arr ? '[': '{') + String(json) + (arr ? ']': '}');
    }
};
/**
 *
 * @param msg_type
 * @param msg_content
 */
function layer_tips(msg_type,msg_content){
	layer.closeAll();
	var time = msg_type==0 ? 3000 : 4000;
	var type = msg_type==0 ? 1 : (msg_type != -1 ? 0 : -1);
	if(type == 0){
		msg_content = '<font color="red">'+msg_content+'</font>';
	}
	// layer.msg(msg_content,{offset:['80px',''],icon:1,shift:6,time:time});
	layer.msg(msg_content,{offset:['80px',''],icon:type,shift:6,time:time});
}
/**
 *
 * @param number
 * @param type
 * @param title
 * @param url
 */
/**
 *
 * @param number
 * @param type
 * @param title
 * @param url
 */
function login_box_after(number,type,title,url){
	var prefix = '';
	switch(type){
		case 'page':
			prefix = '微页面';
			break;
		case 'pagecat':
			prefix = '微页面分类';
			break;
		case 'goodcat':
			prefix = '商品分组';
			break;
		case 'good':
			prefix = '商品';
			break;
		case 'activity_module':
			prefix = '营销活动';
			break;
		case 'group':
			prefix = group_alias_name;
			break;
		case 'good_group':
			prefix = '商品分组';
			break;
	}
	link_save_box[number](type,prefix,title,url);
	login_box_close();
}

/**
 * 得到对象的长度
 */
function getObjLength(obj){
	var number = 0;
	for(var i in obj){
		number++;
	}
	return number;
}

/**
 *
 * @param dom
 * @param type
 * @param after_obj
 */
var widget_link_save_box = {};
function widget_link_hd(dom,type,after_obj,items){
	//点击
	dom.click(function(){
		//移除
		$('.modal-backdrop,.modal').remove();

		//添加
		$('body').append('<div class="modal-backdrop fade in widget_link_back"></div>');

		//赋值
		var randNum = getRandNumber();
		var load_url = '?c=Diypage&a='+type+'&type=more&store_id='+store_id+'&number='+randNum;
		if((type=='activity_module'||type=='article_module'||type=='new_activity_module')&&items!=undefined){		// 已选取的商品置灰
			var itemArr = [];
			if(type=='article_module'){
				$.each(items.activity_arr,function(i,v){
					if(v!=undefined){
						itemArr[i] = v.id;
					}
				});
			}else{
				$.each(items,function(i,v){
					if(v!=undefined){
						itemArr[i] = v.id;
					}
				});
			}
			if (type=='new_activity_module') {
                load_url += '&shop_type='+page_goods_source;
			}
			load_url += '&selecteditems='+itemArr;
		}

		widget_link_save_box[randNum] = after_obj;

		//添加
		modalDom = $('<div class="modal fade js-modal in widget_link_box" aria-hidden="false" style="margin-top:0px;display:block;"><iframe src="'+load_url+'" style="width:100%;height:200px;border:0;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px;"></iframe></div>');
		$('body').append(modalDom);
		//动画
		modalDom.animate({'margin-top': ($(window).scrollTop() + $(window).height() * 0.05) + 'px'}, "slow", function(){
			modalDom.css({'position':'fixed','top':(($(window).height() - 540)/2),'margin-top':'0px'});
		});

		//关闭
		$('.modal-backdrop').click(function(){
			login_box_close();
		});
	});
}