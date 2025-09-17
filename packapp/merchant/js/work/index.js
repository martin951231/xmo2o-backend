var ticket = common.getCache('ticket');
var client = common.checkAndroidApp()  ?  2 : (common.checkIosApp() ? 1 : 0);
var power=common.getCache('power');
console.log(power);
var be_date='';
var enddate='';
var stor_list=[];
var type_list=[];
var type='';//全局
var store_id=''; //全局
var store_index='';
var type_index='';
var mySwiper;
var tickets=urlParam.ticket;
var forms=urlParam.from;
var store_device=urlParam.store_device_id ? urlParam.store_device_id : urlParam.store_device;
var indexData = ''
// 店员端打开app携带的店铺id
var store_idAll = urlParam.store_id?urlParam.store_id:'';

var packageData = [];
var dataIndex = 0;
if (forms == 'storestaff') {
    if(store_idAll) {
        common.setCache('store_idAll',store_idAll);
    }
	common.setCache('ticket', tickets, true);
	common.setCache('ticket', tickets);
	common.setCache('device_id', store_device, true);
} else {
	showPackageSelect();
}

function showPackageSelect() {
	common.http('Merchantapp&a=index',{'ticket':ticket,'client':client},function(data){
		console.log(data);
		if(data.list && data.list.is_card) {
			packageData = data.list.group_list;
			if(packageData && packageData.length) {
				$('.select-package-background').show();
				$('body').css('overflow-y', 'hidden');
				showPackageData()
			}
		} else {
			console.log(1111)
			$('.select-package-background').hide();
			$('body').css('overflow-y', 'scroll');
		}
	});
}

// 显示套餐数据
function showPackageData() {
		var data = packageData[dataIndex];
		data.index = dataIndex;
		if(dataIndex == packageData.length -1 ) { // 最后一页
			data.index = -1;
		}
		laytpl(document.getElementById('packageCard').innerHTML).render(data, function(html){
			$('.select-package-background').html(html);
		});

}

//  点击上一页 
mui('.select-package-background').on('tap','.select-package-last img',function(){
	dataIndex--;
	showPackageData()
});
// 点击下一页
mui('.select-package-background').on('tap','.select-package-next img',function(){
	dataIndex++;
	showPackageData()
});
// 点击立即使用
mui('.select-package-background').on('tap','.use-btn',function(){
	//业务处理
    //权限套餐购买
	common.http('Merchantapp&a=auth_package', {'client':client}, function(data){
			openWindow({
				url:'auth_package_buy.html',
				id:'auth_package_buy'
			});
	});

	// 关闭弹层
	$('.select-package-background').hide();
	$('body').css('overflow-y', 'scroll');
});

if(urlParam.device_id && urlParam.ticket && !common.checkWeixinApp()){
	common.setCache('device_id', urlParam.device_id, true);
	common.setCache('ticket', urlParam.ticket, true);
}

if(urlParam.now_lang){
	common.setCache('now_lang', urlParam.now_lang, true);
	now_lang = urlParam.now_lang;
}

var config = common.getCache('config',true);
var admin_arr = [];
if(!config || common.checkApp()){
	common.http('Merchantapp&a=config', {}, function(data){
        console.log('data', data);
		config=data;
		common.setCache('config',config,true);
		config_loaded();
	});
}else{
	config_loaded();
}
function config_loaded(){  
	//language
	if(config.lang_config && config.lang_config.lang_list && config.lang_config.lang_list.length > 0){
		$('.mui-bar-nav .mui-pull-right').hide();
	} 
    
}

function logIn(){
	if(forms&&tickets&&store_device){
		common.http('Merchantapp&a=login', {'from':forms,'store_ticket':tickets,'store_device':store_device}, function(data){
			console.log('Merchantapp&a=login---data',data);
			common.setCache('ticket',data.ticket,true);
			common.setCache('merchant_info',data.user,true);
			common.setCache('ticket',data.ticket);
			common.setCache('mer_id',data.user.mer_id);
            if(data.store_id && data.store_id != 'null' && data.store_id != 'undefined') {
                common.setCache('store_idAll',data.store_id);
            }
			common.setCache('power',data.auth);
			redirect_self((urlParam.back ? urlParam.back : 'index1')+'.html');
		});
	}
}
if(forms && tickets && store_device){
	// alert(forms);
	common.setCache('forms',forms);
	common.setCache('tickets',tickets);
	common.setCache('store_device',store_device);
	logIn();
}else{
	var forms1=common.getCache('forms');
	if(forms1){
		tickets=common.getCache('tickets')
		forms=common.getCache('forms')
		store_device=common.getCache('store_device');
		logIn();
	}else{
		function sleep(numberMillis) {
			var now = new Date();
			var exitTime = now.getTime() + numberMillis;
			while (true) {
				now = new Date();
				if (now.getTime() > exitTime)
					return;
			}
		}

		function selectChange(lists){
			for( var  x in lists){
				var goods_id_list ={'value':'','text':''};
				goods_id_list.text=lists[x].name;
				goods_id_list.value=lists[x].store_id;
				stor_list.push(goods_id_list);
			}
		}

		function replaceAll(str){
			if(str!=null)
				str = str.replace(/-/g,"/")
			return str;
		}

		function setData(data){
			$('.ashop').text(data.shop_name);
			$('.byin').text(data.meal_name);
			$('.ctuan').text(data.group_name);
			$('.dyu').text(data.appoint_name);
			if(data.mall_name){
				$('.amall').text(data.mall_name);
			}else{
				$('.mall_list').remove();
			}
			common.setCache('ashop',data.shop_name);
			common.setCache('amall',data.mall_name);
			common.setCache('byin',data.meal_name);
			common.setCache('ctuan',data.group_name);
			common.setCache('dyu',data.appoint_name);
			common.setCache('jif',data.score_name);
			common.setCache('androidUrl',data.staff_android_url);
			common.setCache('iosUrl',data.storestaff_ios_download_url);
			common.setCache('androidBao',data.storestaff_android_package_name);
			common.setCache('iosBao',data.storestaff_ios_package_name);
			if (data.lang_config) {
				if(data.lang_config.lang_list && data.lang_config.lang_list.length > 0 && (!all_lang || !now_lang)){
					common.setCache('lang_list',data.lang_config.lang_list,true);
					get_lang_list(data.lang_config.now_lang,'lang_loaded');
				}else{
					replace_html_lang();
				}
			}
			$(window).scrollTop(0);
		}

		if(common.getCache('isStarted',true)){
			$('#mainPage').css('opacity','1');
			$('#startBg').show();
			$('#startBg1').show();
			$('#startBg2').show();
		}else{
			common.setCache('isStarted','true',true);
			$('#startBg').hide();
			$('#startBg1').hide();
			$('#startBg2').hide();
			$('#startBg img').css({height:$(window).height(),width:$(window).width()});
			$('#startBg1 img').css({height:$(window).height(),width:$(window).width()});
			$('#startBg2 img').css({height:$(window).height(),width:$(window).width()});
			$('#mainPage').css('opacity','0');
		}
		
		/*先初始化判断有没有缓存，有的话先展示缓存内容*/
		var app_config = common.getCache('config',true);
		if(app_config){
			setData(app_config);
			initialize();
		}else{
			common.http('Merchantapp&a=config', {'client':client}, function(data){
				app_config = data;
				common.setCache('config',data,true);
				setData(data);	
				initialize();
			},function(data){

			});
		}

//选择店铺
		common.http('Merchantapp&a=merchant_money_info', {'client':client,'type':'group'}, function(data){
			//console.log(data);
			stor_list=[];
			selectChange(data.store_list);
			$('.open_income div h3').text(data.today_money);
			$('#todayordercount2').text(data.today_count);
			$('.my_balance div h3').text(data.merchant_money);
			if(data.show_merchant_score == 1){
				$('.my_merchant_score div h3').text(data.merchant_score);
				$('.my_lock_merchant_score div h3').text(data.lock_merchant_score);
				$('.show_merchant_score').show();
			}else{
				$('.show_merchant_score').hide();
			}


		},function(data){

		});

		function set_index_data(data,type){
			//在线签署合同
			if (data.contract_sign_alert) {
				mui.alert('<p class="textP">' + data.contract_sign_tip + '</p>', L_('温馨提示'), L_('去签署合同'),
					function (e) {
						openWebviewUrl('contract.html');
					});
				return false;
			}

			common.setCache('qrcodeinfo',data.qrcodeinfo,true);
			type_list=[];
			data.type_name = data.type_name ? data.type_name : [];
			$.each(data.type_name,function(i,val){
				var types_lists ={'value':'','text':''};
				types_lists.text=val.name+L_("订单");
				types_lists.value=val.type;
				type_list.push(types_lists);
			});
			var length= data.wap_merchantAd ? data.wap_merchantAd.length : [];
			if(length>0){
				var max_width=$(window).width()*120/400;
				$(".swiper-container1").css("height",max_width+"px");
				var str = '';
				$.each(data.wap_merchantAd,function(i,val){
					str+='<div class="swiper-slide swiper-slide-duplicate" ><div style="height:100%;    display: flex; align-items: center;"><img class="img'+i+'" id="img'+i+'" data-url='+data.wap_merchantAd[i].url+' src='+data.wap_merchantAd[i].pic+'></div></div>';
				});
				$('#add_imgpic .swiper-wrapper').html(str);
				$('#add_imgpic').show();
				mySwiper = new Swiper('.swiper-container',{
					direction:"horizontal",/*横向滑动*/
					loop:true,/*形成环路（即：可以从最后一张图跳转到第一张图*/
					pagination:".swiper-pagination",/*分页器*/
					autoplay:3000/*每隔3秒自动播放*/  ,
					pagination : '.swiper-pagination',
					paginationClickable :true
				});
			}else{
				$('#add_imgpic').hide();
			}
		}
		//网址打开广告图
		$('.swiper-wrapper').off('click','.swiper-slide img').on('click','.swiper-slide img',function(e){
			e.stopPropagation();
			e.preventDefault();
			var url=$(this).attr('data-url');
			if(common.checkApp()){
				if(common.checkAndroidApp()){
					window.pigcmspackapp.openBrowser(url);
				}else{
					var iosHref = window.btoa(url);
					iosHref = iosHref.replace('/','&');
					common.iosFunction('openBrowser/'+iosHref);
				}
			}else{
				location.href=url;
			}

		});

		if(common.getCache('ticket',true)){
			//首页主体数据
			/*先初始化判断有没有缓存，有的话先展示缓存内容*/

			var index_data = common.getCache('index_data',true);
			if(index_data){
				// set_index_data(index_data,true);
			}
			common.http('Merchantapp&a=index', {'client':client}, function(data){
				common.setCache('index_data',data,true);
                indexData = data
                if (is_Simple_Password != 1) {
                    set_index_data(data,false);
                }
			});

			//订单报表
			common.http('Merchantapp&a=merchant_money_date',{'client':client,'type':type,'store_id':store_id},function(data){
				echartJs(data.xAxis_arr,data.order_count,data.income);
			});
		}

// 轮播图切换点击
		mui('.mui-content').on('tap','.mui-indicator',function(e){
			if($(this).is('.mui-active')){}else{

				var transform_width=-(index-this_index)*index_width+'px';
				console.log(transform_width);
				$('.mui-slider-group.mui-slider-loop').css({
					'transform': 'translate3d('+transform_width+', 0px, 0px) translateZ(0px)',
					'transition-duration': '0ms',
					'transition-timing-function':'cubic-bezier(0.165, 0.84, 0.44, 1)'
				});
			}
		});



		function initialize(){
			if(common.checkLogin() == false){
				return false;
			}

			if (common.getVersion() >= 1300) {
				common.appFunction({name:"closeStartupPage"});
			}

			if(common.checkIosApp()){
				common.iosFunction('changecolor/#2ECC71');
			}else if(common.checkAndroidApp()){
				window.pigcmspackapp.changecolor('#2ECC71');
			}

			var merchantArr = common.getCache('merchant_info',true);
			if(false && merchantArr){
				indexDataObj(merchantArr);
			}else{
				common.http('Merchantapp&a=login',{noTip:true}, function(data){
					
					console.log(data);
					common.setCache('ticket', data.ticket, true);
					common.setCache('merchant_info', data.user, true);
					common.setCache('ticket', data.ticket);
					common.setCache('mer_id', data.mer_id);
					common.setCache('power', data.auth);
					common.setCache('card_recharge', data.auth.card_recharge);
					
					indexDataObj(data.user);

				},function(data){
					common.removeCache('ticket');
					common.removeCache('ticket',true);
					redirect_self('login.html');
				});
			}
		}

		function indexDataObj(merchantArr){
			if(merchantArr){
				$('#staff_name,#footer_staff_name').html(merchantArr.name);
				$('#footer_store_name').html(merchantArr.store_name);
			}
			var indexData = common.getCache('indexData',true);
			if(indexData){
				editData(indexData);
			}else{
				common.http('Merchantapp&a=index',{noTip:true}, function(data){
					common.setCache('indexData',data,true);
					editData(data);
				});
			}
		}

		function editData(data){
			common.setData(data.count_number);
			//console.log(data);
			$('#mainPage').css('opacity','1');
			$('#startBg').hide();
			$('#startBg1').hide();
			$('#startBg2').hide();
		}

		(function($, doc){
			$.init();
			mui('.mui-content').on('tap','#showUserPicker1',function(){
				var _getParam = function(obj, param) {
					return obj[param] || '';
				};
				var userPicker = new $.PopPicker();
				userPicker.setData(stor_list);
				userPicker.pickers[0].setSelectedValue(store_index);
				var userResult = doc.getElementById('userResult');
				userPicker.show(function(items) {
					if(items[0].text!=undefined){
						$('#change_shop')[0].innerText= items[0].text;
						store_id=items[0].value;
						store_index=items[0].value;
						common.http('Merchantapp&a=merchant_money_date', {'client':client,'type':type,'store_id':store_id,'period':be_date+'-'+enddate}, function(data){
							//console.log(data);
							echartJs(data.xAxis_arr,data.order_count,data.income);
						});
					}
				});
				replace_html_lang();
			});
			//团购、快店类型选择
			mui('.mui-content').on('tap','#showUserPicker',function(){
                 
				var _getParam = function(obj, param) {
					return obj[param] || '';
				};
				//普通示例
				var userPicker = new $.PopPicker();
				userPicker.setData(type_list);
				userPicker.pickers[0].setSelectedValue(type_index);
				var userResult = doc.getElementById('userResult');
				userPicker.show(function(items) {
					if(items[0].text!=undefined){
						$('#tuan_ding')[0].innerText= items[0].text;
						type=items[0].value;
						type_index=items[0].value;
						common.http('Merchantapp&a=merchant_money_info', {'client':client,'type':type}, function(data){
							stor_list=[];
							selectChange(data.store_list);


						});
						common.http('Merchantapp&a=merchant_money_date', {'client':client,'type':type,'store_id':store_id,'period':be_date+'-'+enddate}, function(data){
							echartJs(data.xAxis_arr,data.order_count,data.income);
						});
					}
				});
				replace_html_lang();
			});


			$('.begin_date')[0].addEventListener('tap',function(){
				var timeValue=document.getElementById('begin_date').innerText;
				var optionsJson = '{"type":"date","beginYear":2014,"beginMonth":5,"beginDay":1,"endYear":2025}';
				var options = JSON.parse(optionsJson);
				var id = this.getAttribute('id');
				var picker = new $.DtPicker(options);
				picker.setSelectedValue(timeValue);	
				picker.show(function(rs) {
					$('#begin_date')[0].innerText = rs.text;
					be_date=replaceAll(rs.text);
					picker.dispose();
					if(enddate!=''){
						common.http('Merchantapp&a=merchant_money_date',{'client':client,'period':be_date+'-'+enddate,'type':type,'store_id':store_id},function(data){
							console.log(data);
							echartJs(data.xAxis_arr,data.order_count,data.income);
						});
					}else{
						mui.toast(L_('请选择结束日期'));
					}
				});
				replace_html_lang();
			});
			$('.end_date')[0].addEventListener('tap',function(){
				var timeValue=document.getElementById('end_date').innerText;
				var optionsJson = '{"type":"date","beginYear":2014,"endYear":2025}';
				var options = JSON.parse(optionsJson);
				var id = this.getAttribute('id');
				var picker = new $.DtPicker(options);
				picker.setSelectedValue(timeValue);
				picker.show(function(rs) {
					$('#end_date')[0].innerText = rs.text;
					picker.dispose();
					//var start_date=replaceAll($('#begin_date').text());
					enddate=replaceAll(rs.text);
					if(be_date!=''){
						common.http('Merchantapp&a=merchant_money_date',{'client':client,'period':be_date+'-'+enddate,'type':type,'store_id':store_id},function(data){
							console.log(data);
							echartJs(data.xAxis_arr,data.order_count,data.income);
						});
					}else{
						mui.toast(L_('请选择开始日期'));
					}
				});
				replace_html_lang();
			});
		})(mui, document);




		function echartJs(xAxis_arr,order_count,income){
			//曲线图表
			var getOption = function(chartType) {
				var chartOption = {
					//					option = {
					title : {
						//					        text: '某楼盘销售情况',/
					},
					tooltip : {
						trigger: 'axis'
					},
					grid: {
						left: '3%',
						right: '4%',
						bottom: '3%',
						containLabel: true
					},
					calculable : false,
					xAxis : [
						{
							type : 'category',
							boundaryGap : false,
							data :xAxis_arr,
							axisLine:{
								lineStyle:{
									color:'#D2DDDE',
									width:1,//这里是为了突出显示加上的，可以去掉
								}
							},
							splitLine: {           // 分隔线
								show: true,        // 默认显示，属性show控制显示与否
								// onGap: null,
								lineStyle: {       // 属性lineStyle（详见lineStyle）控制线条样式
									color: ['#D2DDDE'],
									width: 1,
									type: 'solid'
								}
							}
						}
					],
					yAxis : [
						{
							type : 'value',
							axisLine:{
								lineStyle:{
									color:'#D2DDDE',
									width:1,//这里是为了突出显示加上的，可以去掉
								}
							},
							splitLine: {           // 分隔线
								show: true,        // 默认显示，属性show控制显示与否
								// onGap: null,
								lineStyle: {       // 属性lineStyle（详见lineStyle）控制线条样式
									color: ['#D2DDDE'],
									width: 1,
									type: 'solid'
								}
							}
						}
					],
					series : [

						{
							name:L_('订单总数'),
							type:'line',
							smooth:true,
							symbol:'none',
							itemStyle: {
								normal: {
									areaStyle: {
										type: 'default'
									},
								}
							},
							data:order_count
						},
						{
							name:L_('订单收入'),
							type:'line',
							smooth:true,
							symbol:'none',
							itemStyle: {
								normal: {
									areaStyle: {
										type: 'default'
									},
								}
							},
							data:income
						}

					]
				};

				return chartOption;
			};
			var byId = function(id) {
				return document.getElementById(id);
			};

			var lineChart = echarts.init(byId('lineChart'));
			lineChart.setOption(getOption('line'));
		}

		mui.init({
			swipeBack: true //启用右滑关闭功能
		});


//点击二维码进入二维码页面
		mui('.mui-bar-nav').on('tap', 'div.mui-pull-right', function(e) {
			openWindow({
				url:'qr_code.html',
				id:'qr_code'
			});
		});

//点击进入收入记录
		mui('.mui-content').on('tap','.open_income',function(e){
			openWindow({
				url:'record.html',
				id:'record'
			});
		});

//快店管理点击
		mui('.mui-content').on('tap','.shop_list',function(e){
			openWindow({
				url:'shop_list.html',
				id:'shop_list'
			});
		});
//商城管理点击
		mui('.mui-content').on('tap','.mall_list',function(e){
			openWindow({
				url:'mall_list.html',
				id:'mall_list'
			});
		});
//餐饮管理点击
		mui('.mui-content').on('tap','.eat_list',function(e){
			openWindow({
				url:'shop_list_col.html',
				id:'shop_list_col'
			});
		});
//点击店铺管理
		mui('.mui-content').on('tap','.shop_mange',function(e){
			openWindow({
				url:'shop_management.html',
				id:'shop_management'
			});
		});
//团购管理点击
		mui('.mui-content').on('tap','.grounp_buy',function(e){
			openWindow({
				url:'group_buy.html',
				id:'group_buy'
			});
		});
//预约管理点击
		mui('.mui-content').on('tap','.booking',function(e){
			openWindow({
				url:'booking.html',
				id:'booking'
			});
		});

//点击进入我的余额
        mui('.mui-content').on('tap','.my_balance',function(e){
            openWindow({
                url:'my_balance.html',
                id:'my_balance'
            });   
            
        });

		//查看可用积分
		mui('.mui-content').on('tap','.my_merchant_score',function(e){
			openWindow({
				url:'merchant_score.html',
				id:'merchant_score'
			});

		});

		//查看锁定积分
		mui('.mui-content').on('tap','.my_lock_merchant_score',function(e){
			openWindow({
				url:'lock_merchant_score.html',
				id:'lock_merchant_score'
			});

		});
    }
//点击进入批发市场
    mui('.mui-content').on('tap','.wholesale_info',function(e){
        openWindow({
            url:'market_list.html',
            id:'market_list'
        });

    });
//点击进入积分商城
    mui('.mui-content').on('tap','.integral_mall_info',function(e){
        openWindow({
            url:'integral_mall_index.html',
            id:'integral_mall_index'
        });

    });
	
// 营业分析
    mui('.mui-content').on('tap','.businessAnalysis_info',function(e){
        openWindow({
            url:'businessAnalysis_shop.html',
            id:'businessAnalysis'
        });

    });
	
}
 

function lang_loaded(){
	// replace_html_lang();
}

// 判断密码是123456 弹出提示 
var is_Simple_Password=common.getCache('is_Simple_Password',true);
var user=common.getCache('merchant_info',true); 
if(is_Simple_Password == 1){ 
    $('.alap_box').addClass('show');
    $('.alap_con').addClass('show');  
    $('html, body').addClass('lock-back');  
    function commonAlap(){
        $('.alap_box').hide();
        $('.alap_con').hide(); 
        common.setCache('is_Simple_Password','0',true);
        $('html, body').removeClass('lock-back');  
        indexData && set_index_data(indexData)
    }
     mui('.mui-content').on('tap','.btn_pass',function(){
         commonAlap();
     })
     mui('.mui-content').on('tap','.btn_ok',function(){   
         window.location.href="modify_password.html?back=2"
         commonAlap();
    })  
}  else {
    indexData && set_index_data(indexData)
}

// 隐私协议    
 var lastAgreeTime='';
 var privacy_policy_md5='';  
 
  
if(common.checkApp() && false){ 
    common.http('Merchantapp&a=get_privacy_policy',{noTip:true}, function(data){
       privacy_policy_md5=data.privacy_policy_md5; 
        if(data.privacy_policy){ 
         if (common.checkIosApp()) {  
             common.iosFunction('getAgreement/getAgreementOk', {name: 'getAgreement',callback:'getAgreementOk'});     
          }else{  
             window.pigcmspackapp.getAgreement('getAgreementOk');
         } 
       } 
    })    
}

function getAgreementOk(option){   
	if(!option || privacy_policy_md5.trim() != option.trim()){ 
		$('.alapbg').show();
		$('.alapbg > .alap_con').show();
	}
}

mui('.mui-content').on('tap','#Noagree',function(e){
	notice();
	$('.alapbg').hide(); 
});

mui('.mui-content').on('tap','#agree',function(e){
	 if (common.checkIosApp()) {
	     common.iosFunction('saveAgreement/'+privacy_policy_md5, {name: 'saveAgreement',privacyPolicyMd5:privacy_policy_md5});    
	 }else{  
	     window.pigcmspackapp.saveAgreement(privacy_policy_md5);
	 }   
	 $('.alapbg').hide();
});

 
 function notice(){
     setTimeout(function(){
         mui.alert('<p class="textP"><img src="images/notice.png" style="width:50px"  /><br>'+L_('若不同意则不能正常使用本软件')+'！</p>',L_('温馨提示'),'',
         function(e){
         	$('.alapbg').show();
          }); 
         
     },350);
 }

 mui('.mui-bar').on('tap','.mui-pull-msg',function() {
    openWindow({
        url:'msg_list.html',
        id:'msgList'
    });
 })

//  消息未读数量
 var timer = null
 function getUnreadMsg() {
    let success = (res) => {
        if(res.num > 0) {
            $('.mui-pull-msg').addClass('mui-pull-msg-dot')
        } else {
            $('.mui-pull-msg').removeClass('mui-pull-msg-dot')
        }
        timer = null
        clearTimeout(timer)
        timer = setTimeout(() => {
            getUnreadMsg()
        },1000 * 60)
    }
    common.httpV20('/warn/merchant.Notice/unreadNum', {noTip: true}, success);
 }
 getUnreadMsg()

 