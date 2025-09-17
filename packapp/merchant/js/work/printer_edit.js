mui.init();
var name1='',address1='',ptype=1,language='';
var forms=common.getCache('forms');
if(forms){
	$('.change').hide();
	var store_idAll=common.getCache('store_idAll');
	$('#store_id').val(store_idAll);
}else{
	$('.change').show();
}
var store_list = [];
//筛选
(function($,doc){
	$.init();
	$.ready(function(){
		document.activeElement.blur(); 
		common.http('Merchantapp&a=merchant_store',{}, function(data){
			
			if(data.length > 0){
				for(var i in data){
					store_list.push({value:data[i].store_id,text:data[i].name});
				}
			}
			var userPicker = new $.PopPicker();
			userPicker.setData(store_list);
			var showUserPickerButton = doc.getElementById('showUserPicker');
			showUserPickerButton.addEventListener('tap', function(event) {
				userPicker.show(function(items){
					doc.getElementById('shop_text').innerHTML = items[0].text;
					doc.getElementById('store_id').value = items[0].value;
				});
			}, false);
			
			load_printer();
		});
	});
})(mui, document);
var printerList=[];

if(common.checkApp()){
	if(common.checkAndroidApp()|| common.checkIos()){
		printerList=[{"value":"1","text":L_("易联云打印机")},{"value":"2","text":L_("有线打印机")},{"value":"3","text":L_("蓝牙打印机")},{"value":"4","text":L_("飞鹅打印机")},{"value":"5","text":L_("365云打印机")},{"value":"6","text":L_("手持POS终端")}];
	}
}else{
	printerList=[{"value":"1","text":L_("易联云打印机")},{"value":"2","text":L_("有线打印机")},{"value":"4","text":L_("飞鹅打印机")},{"value":"5","text":L_("365云打印机")},{"value":"6","text":L_("手持POS终端")}];
}
///筛选打印机类型
(function($,doc){
	$.init();
	mui('.mui-content').on('tap','#printerClass',function(e){
		document.activeElement.blur(); 
		var userPicker = new $.PopPicker();
		userPicker.setData(printerList);
		userPicker.show(function(items){
			doc.getElementById('printerClass_text').innerHTML = items[0].text;
			ptype=items[0].value;
			if(items[0].value==1){
				document.getElementById('alls').style.display="block";
				document.getElementById('bettly').style.display="none";
				document.getElementById('items1').style.display="block";
				document.getElementById('items4').style.display="none";
				document.getElementById('items6').style.display="none";
				document.getElementById('mkey').value='';
			}else if(items[0].value==2){
				document.getElementById('items1').style.display="none";
				document.getElementById('bettly').style.display="none";
				document.getElementById('items4').style.display="none";
				document.getElementById('items6').style.display="none";
				document.getElementById('mkey').value='';
			}else if(items[0].value==3){
				document.getElementById('alls').style.display="none";
				document.getElementById('bettly').style.display="block";
				document.getElementById('items4').style.display="none";
				document.getElementById('items6').style.display="none";
				document.getElementById('mcode').value='600006';
				getCharacter();
			}else if(items[0].value==4){
				document.getElementById('alls').style.display="block";
				document.getElementById('bettly').style.display="none";
				document.getElementById('items1').style.display="none";
				document.getElementById('items4').style.display="block";
				document.getElementById('items6').style.display="none";
				getCharacter();
			}else if(items[0].value==5){
				document.getElementById('alls').style.display="block";
				document.getElementById('bettly').style.display="none";
				document.getElementById('items1').style.display="none";
				document.getElementById('items4').style.display="none";
				document.getElementById('items6').style.display="none";
				getCharacter();
			}else if(items[0].value==6){
				document.getElementById('alls').style.display="block";
				document.getElementById('bettly').style.display="none";
				document.getElementById('items1').style.display="none";
				document.getElementById('items4').style.display="none";
				document.getElementById('items6').style.display="block";
				getCharacter();
			}
		});
			
	});
})(mui, document);

(function($,doc){
	$.init();
	$.ready(function(){
		var userPicker = new $.PopPicker();
		var LanguageList = [{"value":"","text":L_("通用")},{"value":"Uyghur","text":L_("维文")}];
		userPicker.setData(LanguageList);
		var showUserPickerButton = doc.getElementById('showLanguagePicker');
		showUserPickerButton.addEventListener('tap', function(event) {
			userPicker.show(function(items){
				doc.getElementById('showLanguagePicker').innerHTML = items[0].text;
				language = items[0].value;
			});
		}, false);
	});
})(mui, document);

function getCharacter(){ 
	character='';
	for(var i=0;i<16;i++){
		character+= String.fromCharCode(Math.floor(Math.random()*26)+"A".charCodeAt(0)); 
	}
	$('#mkey').val(character);
 	console.log(character); 
}

mui('.mui-content').on('tap','.checkbox_change',function(e){
	document.activeElement.blur(); 
	$(this).is('.active') ? $(this).removeClass('active') : $(this).addClass('active');
});

mui('.mui-content').on('tap','.booking_order_print_change',function(e){
	document.activeElement.blur();
	$(this).is('.active') ? $(this).removeClass('active') : $(this).addClass('active');
});

mui('.mui-content').on('tap','.mall_print_change',function(e){
	document.activeElement.blur();
	$(this).is('.active') ? $(this).removeClass('active') : $(this).addClass('active');
});

mui('.mui-content').on('tap','.store_print_change',function(e){
	document.activeElement.blur();
	$(this).is('.active') ? $(this).removeClass('active') : $(this).addClass('active');
});

function load_printer(){
	common.http('Merchantapp&a=hardware_add',{status:2,pigcms_id:urlParam.print_id}, function(data){
		console.log(data);
		
		for(var i in store_list){
			if(store_list[i].value == data.store_id){
				$('#shop_text').html(store_list[i].text);
				break;
			}
		}
		$('#store_id').val(data.store_id);
		$('#name').val(data.name);
		$('#mp').val(data.mp);
		$('#username').val(data.username);
		$('#mcode').val(data.mcode);
		$('#mkey').val(data.mkey);
		$('#count').val(data.count);
		$('#feie_user').val(data.feie_user);
		$('#feie_ukey').val(data.feie_ukey);
		$('#version').val(data.version);
		// if(data.mcode='600006'){
		// 	$('#printerClass_text').text('蓝牙打印机').css('color','#333');
		// 	document.getElementById('alls').style.display="none";
		// 	document.getElementById('bettly').style.display="block";
		// 	$('#bettly input').val(data.print_bluetooth_name);
		// }
		$('input[name="paper"][value="'+data.paper+'"]').prop('checked',true);
		$('input[name="font_size"][value="'+data.is_big+'"]').prop('checked',true);
		$('input[name="pic"][value="'+data.image+'"]').prop('checked',true);
		
		var paidArr = data.paid.split(',');
		for(var i in paidArr){
			$('.checkbox_change[data-paid="'+paidArr[i]+'"]').addClass('active');
		}
		var bookingOrderArr = data.booking_order_print.split(',');
		for(var i in bookingOrderArr){
			$('.booking_order_print_change[data-paid="'+bookingOrderArr[i]+'"]').addClass('active');
		}
		var mallPrintArr = data.mall_print.split(',');
		for(var i in mallPrintArr){
			$('.mall_print_change[data-paid="'+mallPrintArr[i]+'"]').addClass('active');
		}

		var storePrintArr = data.store_print.split(',');
		for(var i in storePrintArr){
			$('.store_print_change[data-paid="'+storePrintArr[i]+'"]').addClass('active');
		}
		if(data.is_main == '1'){
			$('.is_main').addClass('mui-active');
		}
		ptype=data.print_type;
		if(data.print_type==2){
			document.getElementById('items1').style.display="none";
			document.getElementById('bettly').style.display="none";
			document.getElementById('items4').style.display="none";
			document.getElementById('items6').style.display="none";
			$('#printerClass_text').text(L_('有线打印机'));
		}else if(data.print_type==3){
			document.getElementById('alls').style.display="none";
			document.getElementById('bettly').style.display="block";
			$('#bettly input').val(data.print_bluetooth_name);
			name1=data.print_bluetooth_name;
			address1=data.print_bluetooth_code;
			$('#printerClass_text').html(L_('蓝牙打印机'));
		}else if(data.print_type==1){
			document.getElementById('alls').style.display="block";
			document.getElementById('bettly').style.display="none";
			document.getElementById('items4').style.display="none";
			document.getElementById('items6').style.display="none";
			$('#printerClass_text').text(L_('易联云打印机'));
		}else if(data.print_type==4){
			document.getElementById('alls').style.display="block";
			document.getElementById('bettly').style.display="none";
			document.getElementById('items1').style.display="none";
			document.getElementById('items4').style.display="block";
			document.getElementById('items6').style.display="none";
			$('#printerClass_text').text(L_('飞鹅打印机'));
		}else if(data.print_type==5){
			document.getElementById('alls').style.display="block";
			document.getElementById('bettly').style.display="none";
			document.getElementById('items1').style.display="none";
			document.getElementById('items4').style.display="none";
			document.getElementById('items6').style.display="none";
			$('#printerClass_text').text(L_('365云打印机'));
		}else if(data.print_type==6){
			document.getElementById('alls').style.display="block";
			document.getElementById('bettly').style.display="none";
			document.getElementById('items1').style.display="none";
			document.getElementById('items4').style.display="none";
			document.getElementById('items6').style.display="block";
			$('#printerClass_text').text(L_('手持POS终端'));
		}
		language = data.language;
		if(language=="Uyghur"){
			$('#language_text').text(L_('维文'));
		}

	});
}

$(function(){
	$('#add_button').click(function(){
		var postData = {};
		postData.status = 1;
		postData.pigcms_id = urlParam.print_id;
		postData.store_id = $('#store_id').val();
		postData.name = $('#name').val();
		postData.mp = $('#mp').val();
		postData.username = $('#username').val();
		postData.mcode = $('#mcode').val();
		postData.mkey = $('#mkey').val();
		postData.count = $('#count').val();
		postData.is_main = $('.is_main').hasClass('mui-active') ? "1" : "0";
		postData.paper = $('input[name="paper"]').filter(':checked').val();
		postData.is_big = $('input[name="font_size"]').filter(':checked').val();
		postData.image = $('input[name="pic"]').filter(':checked').val();
		postData.feie_user = $('#feie_user').val();
		postData.feie_ukey = $('#feie_ukey').val();
		postData.version = $('#version').val();

		postData.print_type = ptype;
		postData.print_bluetooth_name = name1;
		postData.print_bluetooth_code = address1;
		postData.language = language;
		var paidArr = [];
		$.each($('.checkbox_change.active'),function(i,item){
			paidArr.push($(item).data('paid'));
		});
		postData.paid = paidArr.join(',');
		var bookingOrderPrintArr = [];
		$.each($('.booking_order_print_change.active'),function(i,item){
			bookingOrderPrintArr.push($(item).data('paid'));
		});
		postData.booking_order_print = bookingOrderPrintArr.join(',');
		var mallPrintdArr = [];
		$.each($('.mall_print_change.active'),function(i,item){
			mallPrintdArr.push($(item).data('paid'));
		});
		postData.mall_print = mallPrintdArr.join(',');

		var storePrintdArr = [];
		$.each($('.store_print_change.active'),function(i,item){
			storePrintdArr.push($(item).data('paid'));
		});
		postData.store_print = storePrintdArr.join(',');
		
		if(ptype==1||ptype==2||ptype==4||ptype==5||ptype==6){
			if(postData.name == ''){
				mui.alert(L_('请输入打印机名称'));
			}else if(postData.mkey == ''){
				mui.alert(L_('请输入密钥'));
			}else if(postData.count <= 0){
				mui.alert(L_('请输入打印份数'));
			}else if(postData.store_id == '0'){
				mui.alert(L_('请选择店铺'));
			}else{
				console.log(postData);
				common.http('Merchantapp&a=hardware_add',postData,function(data){
					mui.toast(L_('保存成功'));
					if(common.checkApp()){
						setTimeout(function(){
							if(common.checkAndroidApp()){
								window.pigcmspackapp.closewebview(2);
							}else{
								common.iosFunction('closewebview/2', {name:'closewebview', type: '2'});
							}
						},2000);
					}else{
						setTimeout(function(){
							history.go(-1);
							document.execCommand('Refresh');
						},2000); 
					}			
				});
			}
		}else{
			if(postData.name == ''){
				mui.alert(L_('请输入打印机名称'));
			}else if(postData.count <= 0){
				mui.alert(L_('请输入打印份数'));
			}else if(postData.store_id == '0'){
				mui.alert(L_('请选择店铺'));
			}else if(postData.paid == ''){
				mui.alert(L_('请选择打印类型'));
			}else{
				if($('#printerClass_text').text()=="蓝牙打印机"&&name1==""){
					mui.toast(L_('蓝牙打印机模式下必须选中蓝牙设备'));
					return false;
				}else{
					common.http('Merchantapp&a=hardware_add',postData,function(data){
						mui.toast(L_('保存成功'));
						if(common.checkApp()){
							setTimeout(function(){
								if(common.checkAndroidApp()){
									window.pigcmspackapp.closewebview(2);
								}else{
									common.iosFunction('closewebview/2', {name:'closewebview', type: '2'});
								}
							},2000);
						}else{
							setTimeout(function(){
								history.go(-1);
								document.execCommand('Refresh');
							},2000); 
						}	
						
					});
				}
			}
			
		}
			
	});
});


$('.mui-content').on('click','.phone_ti',function(e){
	e.stopPropagation();
	e.preventDefault();
	document.activeElement.blur(); 
	mui.alert(L_('绑定手机号和绑定账号只能填写一个（【底部有终端号】需填写手机号，【底部无终端号】需填写绑定账号）'));
});
$('.mui-content').on('click','.bang_ti',function(e){
	e.stopPropagation();
		e.preventDefault();
		document.activeElement.blur(); 
	mui.alert(L_('绑定手机号和绑定账号只能填写一个（【底部有终端号】需填写手机号，【底部无终端号】需填写绑定账号）'));
});
$('.mui-content').on('click','.zhong_ti',function(e){
	e.stopPropagation();
		e.preventDefault();
		document.activeElement.blur(); 
	mui.alert(L_('【底部无终端号】的打印机点击打印机下面的黑色小按钮查看, 【底部有终端号】的打印机在打印机底部查看'));
});
$('.mui-content').on('click','.mi_ti',function(e){
	e.stopPropagation();
		e.preventDefault();
		document.activeElement.blur(); 
	mui.alert(L_('【底部无终端号】的打印机在注册页面查看, 【底部有终端号】的打印机在打印机底部查看'));
});
$('.mui-content').on('click','.nums1',function(e){
	e.stopPropagation();
	e.preventDefault();
	document.activeElement.blur(); 
	mui.alert(L_('每个订单打印几份（最多100）'));
});

if(common.checkApp()){
	if(common.checkAndroidApp()){
		$('.lanShow').show();	
		//蓝牙打印机开关
		$('.checked1').click(function(e){
			window.pigcmspackapp.openBlueScanDialog();
		});
	}else{
		$('.lanShow').hide();
	}

}else{
	$('.lanShow').hide();
}


function  getChooseDeviceInfos(name,macAddress){
	name1=name;
	address1=macAddress;
	$('#bettly input').val(name1);
}











