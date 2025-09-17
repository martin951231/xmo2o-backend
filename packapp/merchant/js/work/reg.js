if(common.checkWeixin()){
	$('.mui-bar-nav').remove();
}


var config = {}
var phone_country_type_arr=[];
var phone_country_type_txt='';
var myCaptcha,CaptchaToken;
common.http('Merchantapp&a=config', {}, function(data){
	common.setCache('config',data,true);
	config=common.getCache('config',true); 
	config_loaded(data);
});
var merchant_type_switch = 0;



function config_loaded(config){
	if(config.open_score_fenrun && config.open_score_fenrun == '1'){
		$('.spread_code_box').show();
	}
	if(config.merchant_verify && config.merchant_verify == '1'){
		$('.merchant_verify').show();
		$('#site_phone').html(config.site_phone).attr('href','tel:'+config.site_phone);
	}
	if(config.store_register_agreement && config.store_register_agreement != ''){
		$('.register_agreement_box').show();
	}
	
	if(config.open_score_fenrun == '1'){
		$('.spread_code_box').show();
	}
    //  商家推荐商家批发佣金功能】开启了推广码显示
    if(config.merchant_recommend_wholesale_switch == '1'){
        $('.spread_code_box').show();
    }

	if(config.open_admin_code == '1' || config.open_bd_spread == '1'){
		$('.invit_code').show();
	}

	if(config.open_merchant_reg_sms == '1'){
		$('.smscode').show();
	}


	if(config.open_score_fenrun == '1'){
		$('.spread_code_box').show();

	}
	
	if(config.open_distributor == '1'){
		$('.spread_code_box').show();

	}

	if(config.international_phone == '1'){
		$('.phone_country').show();
		
		$.each(config.national_phone,function(i,val){
			phone_country_type_arr.push({'value':val.code,'text':'+'+val.code+' '+val.national})
		})
	}
	
	if(config.open_distributor == '1' && typeof(urlParam.spread_code)!='undefined' && urlParam.spread_code!=''){
		$('#spread_code').val(urlParam.spread_code)
	}

    if (config.merchant_type_switch == '1') {
		console.log('merchant_type_switch',config.merchant_type_switch);
        merchant_type_switch = 1;
        $('#company_name_content').show();
	}
	if(config.open_change_zhu == '1'){
		console.log(444)
        $('title').text(L_('商家入住'));
        $('.mui-title').text(L_('商家入住'));
	}else{
        $('title').text(L_('商家入驻'));
        $('.mui-title').text(L_('商家入驻'));
	}
	
	if(config.dingxiang_captcha_appid && config.dingxiang_captcha_appid != ''){
		myCaptcha = _dx.Captcha(document.getElementById('captchaSendCode'), {
			appId: config.dingxiang_captcha_appid,
			style: 'popup',
			success: function (token) {
				CaptchaToken = token;
				console.log('Captcha token:', token)
				myCaptcha.hide();
				sendVcode();
			}
		});
	}
}







mui.init();
(function($, doc) {
	$.init();
	$.ready(function(){
		common.http('Merchantapp&a=cityList',{}, function(data){
			var cityPicker3 = new $.PopPicker({
				layer: 4
			});
			cityPicker3.setData(data);
			console.log(data);
			var showCityPickerButton = doc.getElementById('showCityPicker');
			showCityPickerButton.addEventListener('tap', function(event) {
				cityPicker3.show(function(items){
					document.getElementById('province_id').value = items[0].value;
					document.getElementById('city_id').value = items[1].value;
					document.getElementById('area_id').value = items[2].value;
					document.getElementById('street_id').value = items[3].value;
					document.getElementById('adress_text').innerText =  items[0].text + " " + items[1].text + " " + items[2].text+ " " + (items[3].text?items[3].text:'');
					document.getElementById('adress_text').style.color='#333';
				});
			}, false);
		});
		
		mui('.mui-content').on('tap','#phone_country_type',function(e){
			var userPicker = new $.PopPicker();
			userPicker.setData(phone_country_type_arr);	
			userPicker.pickers[0].setSelectedValue(phone_country_type_txt);
			userPicker.show(function(items) {
				document.getElementById('phone_country_type_click').innerHTML =items[0].text;
				document.getElementById('phone_country_type_click').style.color='#535353';
				phone_country_type_txt=items[0].value;
				//返回 false 可以阻止选择框的关闭
				//return false;
			});
		});
		
	});
})(mui, document);


$(function(){
    var store_id = 0;
    var url = window.location.href;
    var pattern = new RegExp("[?&]store_id\=([^&]+)", "g");
    var matcher = pattern.exec(url);
    // 取地址栏参数 store_id
    if(null != matcher){
        try{
            store_id = decodeURIComponent(decodeURIComponent(matcher[1]));
        }catch(e){
            try{
                store_id = decodeURIComponent(matcher[1]);
            }catch(e){
                store_id = matcher[1];
            }
        }
    }
    console.log('store_id', store_id);
    $('.register').click(function(){
    	var diy_form = [];
    	var can_submit = true;
		$('#app input,textarea').each(function () {
			var is_require = $(this).attr('is_require');
			if (is_require != undefined) {
				var name = $(this).attr('name');
				var value = $(this).val();
				var type = $(this).attr('diy_type');
				if (is_require == 1 && value == '') {
					can_submit = false;
					mui.alert($(this).attr('placeholder'));
					return false;
				}else{
					diy_form.push({name: name, value: value, type: type});
				}
			}
		})

		if(!can_submit){
			return false;
		}

		var postData = {};
		postData.account = $('#reg_account').val();
		postData.pwd = $('#reg_pwd').val();
		postData.mername = $('#reg_name').val();
		postData.phone = $('#reg_phone').val();
		postData.province_id = $('#province_id').val();
		postData.city_id = $('#city_id').val();
		postData.area_id = $('#area_id').val();
		postData.street_id = $('#street_id').val();
		postData.invit_code = $('#invit_code').val();
		postData.sms_code = $('#smsCode').val();
		postData.diy_form = diy_form;
		postData.address_info = $('#address_info').val();
		// postData.phone_country_type = $('#phone_country_type').val();
		postData.phone_country_type =phone_country_type_txt;
		if(typeof($('#spread_code').val())!='undefined' && $('#spread_code').val()!=''){			
			postData.spread_code = $('#spread_code').val();
		}
		if (merchant_type_switch==1 && typeof($('#company_name').val())!='undefined' && $('#company_name').val()!=''){
            var company_name = $('#company_name').val();
            if (company_name=='') {
                mui.alert(L_('请填写公司名称（营业执照上的名称）'));
			} else {
                postData.company_name = $('#company_name').val();
			}
		}
		// 处理下店铺id
		if (store_id && store_id>0) {
            postData.store_id = store_id;
		}

		// 营业执照
	    $.each($('.showImg0 .delate_photo'),function(i,val){
	        postData.trading_certificate_image = $(this).find('img').data('url');
	    });
		// 身份证正面照
	    $.each($('.showImg1 .delate_photo'),function(i,val){
	        postData.id_card_front = $(this).find('img').data('url');
	    });
		// 身份证反面照
	    $.each($('.showImg2 .delate_photo'),function(i,val){
	        postData.id_card_reverse = $(this).find('img').data('url');
	    });


		if(postData.account.length<6){
			mui.alert(L_('请输入至少 6 个字符的帐号'));
		}else if(!/^\w+$/.test(postData.account)){
			mui.alert(L_('帐号只能输入英文和数字和下划线~'));
		}else if(postData.pwd.length < 6){
			mui.alert(L_('请输入至少 6 个字符的密码'));
		}else if(postData.mername == ''){
			mui.alert(L_('商户名称必填'));
		}else if(postData.phone == '' || (!phone_country_type_txt && !/^[0-9]{11}$/.test(postData.phone))){
			mui.alert(L_('请输入正确的手机号码'));
		}else if(postData.area_id == ''){
			mui.alert(L_('请选择城市区域'));
		}else if(postData.address_info == ''){
			mui.alert(L_('详细地址必填'));
		}else if(!postData.sms_code && config.open_merchant_reg_sms == '1'){
			mui.alert(L_('请填写短信验证码'));
		}else if(config.store_register_agreement && config.store_register_agreement != '' && !$('#register_agreement').prop('checked')){
			mui.alert(L_('请同意商家注册协议，方可继续注册商家'));
		}else{
			common.http('Merchantapp&a=mer_reg',postData,function(data){
                console.log('data', data);
				mui.alert(L_('注册成功！')+(data.type == 2 ? L_('请耐心等待审核或联系工作人员审核') : ''),L_('提醒'),L_('好的'),function(){
				});
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
						location.href = 'index.html';
						document.execCommand('Refresh');
					},2000); 
				}
			});
		}	
	});
	
	

	$('#register_agreement_btn').click(function(){
		layer.open({
		  type: 1
		  ,title: '商家注册协议' //不显示标题栏
		  ,closeBtn: false
		  ,area: ['70%','90%']
		  ,shade: 0.8
		  ,content: '<div style="padding:20px;height:'+($(window).height()*0.9 - 60)+'px;overflow-y:auto;white-space:pre-wrap!important;word-wrap:break-word!important;*white-space:normal!important;">'+config.store_register_agreement+'</div>'
		  ,style: 'position:fixed; top:5%; left:5%; width: 90%; height: 90%; border:none;'
		});
	});
	
	mui('.mui-content').on('tap','.sendCode',function(e){
		if(phone_country_type_arr.length > 0 && phone_country_type_txt == ''){
			mui.alert(L_('请选择国家/区号'));
			return false;
		}
		if($('#reg_phone').val() == ''){
			mui.alert(L_('请输入手机号'));
			return false;
		}
		
		if(config.dingxiang_captcha_appid && config.dingxiang_captcha_appid != ''){
			if(!CaptchaToken || CaptchaToken == ''){
				myCaptcha.show();
			}else{
				myCaptcha.reload();
				myCaptcha.show();
			}
		}else{
			sendVcode();
		}
	});
});

function sendVcode(){
	common.http('Merchantapp&a=sendCode',{type:1, phone:$('#reg_phone').val(), phone_country_type:phone_country_type_txt, CaptchaToken:CaptchaToken}, function(data){
		motify.log('短信发送成功');
		$('.sendCode').data('second',59).html('59 秒').prop('disabled',true);
		var smsTimer = setInterval(function(){
			var second = $('.sendCode').data('second');
			if(second == 1){
				$('.sendCode').html('获取验证码').prop('disabled',false);
				clearInterval(smsTimer);
			}else{
				second--;
				$('.sendCode').data('second',second).html(second + ' 秒');
			}
		},1000);
	});
}


function getFormFields(){
	common.http('Merchantapp&a=regForm',{}, function(data){
		laytpl(document.getElementById('formTpl').innerHTML).render(data, function (html) {
			$('#app').append(html);
			if(data.length>0){
				all_event();
			}
		});
	});
}

getFormFields();





function all_event(){
	$('.fileImage').off('change').on('change', function (e) {
		var post_url = requestUrl + 'WapMerchant&a=uploadStoreImage';
		var id = $(this).attr('id');
		var obj = $(this);
		$.ajaxFileUpload({
			url: post_url,
			secureuri: false,
			fileElementId: id,
			dataType: 'json',
			type: 'post',
			//data: {'wxcard_img': 1},
			success: function (data, status) {
				console.log(data.result.title)
				$('.hidden' + id).val(data.result.title)
				$('.img' + id).attr('src', data.result.url)
				$('.img' + id).css({width: '100%', height: '100%'}).show();
				$('.icon' + id).remove();
			},
			error: function (data, status, e) {
			}
		});
	});


	$(".date_key").on('tap', function () {
		that = this;
		var _init = $(that).val();
		var optionsJson = '{"type":"date","beginYear":2014,"beginMonth":5,"beginDay":1,"endYear":2025}';
		var options = JSON.parse(optionsJson);
		var dtPicker = new mui.DtPicker(options)
		dtPicker.show(function (rs) {
			$(that).val(rs.text);
		});
	})

	$(".time_key").on('tap', function () {
		that = this;
		var _init = $(that).val();
		var optionsJson = '{"type":"time"}';
		var options = JSON.parse(optionsJson);
		var dtPicker = new mui.DtPicker(options)
		dtPicker.show(function (rs) {
			$(that).val(rs.text);
		});
	})

	$(".date_time_key").on('tap', function () {
		that = this;
		var _init = $(that).val();
		var optionsJson = '{"type":"datetime"}';
		var options = JSON.parse(optionsJson);
		var dtPicker = new mui.DtPicker(options)
		dtPicker.show(function (rs) {
			$(that).val(rs.text);
		});
	})

	$('.select_key').on('tap', function () {
		that = this;
		var values = $.parseJSON($(this).attr('data-val'))
		var picker = new mui.PopPicker();
		picker.setData(values)
		//picker.pickers[0].setSelectedValue('fourth', 2000);
		picker.show(function (SelectedItem) {
			$(that).val(SelectedItem[0].text);
		})
	})
}


// 上传图片
function imgUpload(id,imageIndex){
    var index = layer.open({type:2,shade:false,shadeClose:false});
    var post_url = requestUrl + 'WapMerchant&a=uploadStoreImage&path=merchant';
    // console.log(post_url);
    $.ajaxFileUpload({
        url:post_url,
        secureuri:false,
        fileElementId:id,
        dataType: 'json',
        type: 'post',
        // data : {'wxcard_img' : 1},
        success: function(data, status){
            //console.log(data);
            var str='<div class="mui-pull-left delate_photo" ><img src='+data.result.url+' data-url='+data.result.title+' data-preview-src="" data-preview-group="1"/><i></i></div>';
            $('.showImg'+imageIndex).append(str);
            layer.close(index);

        },
        error: function(data, status, e){
            //  alert(e);
            //alert(111);
            layer.close(index);
            mui.toast('上传失败');
        }
    });
}

// 上传图片
if(common.checkWeixin()){
	$('.fileImage').off('click').on('click',function(e){
		
		var imageIndex = $(this).data('id')
		var length=$('.showImg'+imageIndex+' .delate_photo').length;

		if(length<1){
			wx.chooseImage({
				count: 1, // 默认9
				success: function (res) {
					var localIds = res.localIds; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
					wx.uploadImage({
						localId: localIds[0], // 需要上传的图片的本地ID，由chooseImage接口获得
						isShowProgressTips: 1, // 默认为1，显示进度提示
						success: function (res) {
							var serverId = res.serverId; // 返回图片的服务器端ID
							common.http('WapMerchant&a=ajaxGetWxImg',{'serverId':serverId, 'picType':'merchant'},function(data){
								var str='<div class="mui-pull-left delate_photo" ><img src='+data.url+' data-url='+data.title+' data-preview-src="" data-preview-group="1"/><i></i></div>';
								$('.showImg'+imageIndex).append(str);
							});
						},
						fail: function(res){
							alert('微信上传组件返回：' + res.errMsg);
						}
					});
				},
				fail: function(res){
					alert('微信选择图片组件返回：' + res.errMsg);
				}
			});
		}else{
			alert('最多只能上传1张图片');
		}
		
		return false;
	});
	
}else{
	$('.up_img').off('change','input').on('change','input',function(e){
		var id=$(this).attr('id');
		var imageIndex = $(this).data('id')
		var length=$('.showImg'+imageIndex+' .delate_photo').length;
		if(length<1){
			imgUpload(id,imageIndex);
			$('.showImg'+imageIndex).width(105*(length+1)+150);
		}else{
			mui.toast('最多只能上传1张图片');
		}
	});
}

//图片删除点击
mui('.mui-content').on('tap','.delate_photo i',function(e){
    $(this).parent('.delate_photo').remove();
    $('#fileImage0').val('')
    $('#fileImage1').val('')
    $('#fileImage2').val('')
});

