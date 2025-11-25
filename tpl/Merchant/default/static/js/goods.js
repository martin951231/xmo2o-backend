var sortData = [];
var weight_unit='g';
KindEditor.ready(function(K) {
	var editor_H = $(window).height() - 280 ;
	var content_h = $("#content").data('h') || 0;
	if (content_h > 0) {
		editor_H = content_h;
	}
	var content_editor = K.create("#content",{
		width: '702px',
		height: editor_H + 'px',
		resizeType: 1,
		pasteType: 1,
		allowPreviewEmoticons: false,
		allowImageUpload: true,
		filterMode: false,
		autoHeightMode: false,
		afterCreate: function() {
			this.loadPlugin('autoheight');
		},
		items: [
			'fullscreen', '|', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
			'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
			'insertunorderedlist', '|', 'emoticons', 'image', 'multiimage', 'link', 'table','diyVideo'
		],
		emoticonsPath: './static/emoticons/',
		uploadJson: uploadJson,
		cssPath: cssPath
	});

    $('#goods_seckill_open_datetime').click(function(){WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'})});
    $('#goods_seckill_close_datetime').click(function(){WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'})});
    $('#goods_seckill_open_time').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'00','minute':'00'}));
    $('#goods_seckill_close_time').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'23','minute':'59'}));

	$('input[name=seckill_type]').change(function(){
	    if ($(this).val() == 0) {
	        $('.datetime').show();
	        $('.time').hide();
	    } else {
            $('.datetime').hide();
            $('.time').show();
	    }
	});

    $('#show_start_time').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'00','minute':'00'}));
    $('#show_end_time').timepicker($.extend($.datepicker.regional['zh-cn'], {'timeFormat':'hh:mm','hour':'23','minute':'59'}));

	/*调整保存按钮的位置*/
	$(".nav-tabs li a").click(function(){
		if($(this).attr("href")=="#imgcontent"){		//店铺图片
			$(".form-submit-btn").css('position','absolute');
			$(".form-submit-btn").css('top','670px');
		}else{
			$(".form-submit-btn").css('position','static');
		}
	});

	$('form.form-horizontal').submit(function(){
		$(this).find('button[type="submit"]').html('保存中...').prop('disabled',true);
	});

	/*分享图片*/
	$('#image-file').ace_file_input({
		no_file:'gif|png|jpg|jpeg格式',
		btn_choose:L_('选择'),
		btn_change:L_('重新选择'),
		no_icon:'fa fa-upload',
		icon_remove:'',
		droppable:false,
		onchange:null,
		remove:false,
		thumbnail:false
	});

	var editor = K.editor({
		allowFileManager : true
	});
	/*K('#J_selectImage').click(function(){
		if($('.upload_pic_li').size() >= 5){
			alert(L_('最多上传5个图片！'));
			return false;
		}
		editor.uploadJson = upload_image;
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				imageUrl : K('#course_pic').val(),
				clickFn : function(url, title, width, height, border, align) {
					$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="pic[]" value="'+title+'"/><br/><a href="#" onclick="deleteImage(\''+title+'\',this);return false;">[ 删除 ]</a></li>');
					editor.hideDialog();
				}
			});
		});
	});*/
});

function previewimage(input){
	if (input.files && input.files[0]){
		var reader = new FileReader();
		reader.onload = function (e) {$('#image_preview_box').html('<img style="width:120px;height:120px" src="'+e.target.result+'" alt="'+L_("图片预览")+'" title="'+L_("图片预览")+'"/>');}
		reader.readAsDataURL(input.files[0]);
	}
}

function add_file_upload(){
	$('table .reg_file').each(function(n,v){
		add_upload_obj($(this));
	});
}
function add_upload_obj(obj){
	setTimeout(function(){
		var  uploader = WebUploader.create({
			auto: true,
			swf: swf,
			server: server,
			pick: {
				id:obj,
				multiple:false
			},
			accept: {
				title: 'Images',
				extensions: 'gif,jpg,jpeg,png',
				mimeTypes: 'image/gif,image/jpeg,image/jpg,image/png'
			}
		});
		uploader.on('fileQueued',function(file){
		});
		uploader.on('uploadSuccess',function(file,response){
			if(response.error == 0){
				var src=response.url;
				var title=response.title;
				var html='<p><img src='+src+' class="reg_img"><a href="#" onclick="deleteImage(\''+title+'\',this,2);return false;">[ 删除 ]</a></p>';
				obj.hide().prev().val(title).before(html);
				set_reg_info(obj.prev(),response);
				//$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'++'"/><input type="hidden" name="pic[]" value="'+response.title+'"/><br/><a href="#" onclick="deleteImage(\''+response.title+'\',this);return false;">[ 删除 ]</a></li>');
			}else{
				alert(response.info);
			}
		});
		uploader.on('uploadError', function(file,reason){

		});
	},20);
}
//设置规格缓存信息
function set_reg_info(obj,response){
	var values = [];
	if (window.sessionStorage.getItem(session_index) != '') {
		values = JSON.parse(window.sessionStorage.getItem(session_index));
	}
	var new_values = {};
	for (var i1 in values) {
		for (var i2 in values[i1]) {
			if (new_values[i1] == undefined) {
				new_values[i1] = {};
			}
			new_values[i1][i2] = values[i1][i2];
		}
	}
	console.log(obj);
	if (new_values[obj.parents('tr').attr('id')] == undefined) {
		new_values[obj.parents('tr').attr('id')] = {};
	}
	var name=obj.attr('name');
	new_values[obj.parents('tr').attr('id')][name] = response;
	window.sessionStorage.setItem(session_index, JSON.stringify(new_values));
}
/*添加选项*/
$(document).ready(function(){
	$(".question_box ul li .list_del").live('click',function(){
		if ($(this).parents("ul").find('li').size() > 1) {
			$(this).parents("li").remove();
		} else {
			$(this).parents(".question_box").remove();
			if ($('.spec').size() < 1) {
				$('.add_table, .add_table_foodshop').css('display', 'none');
			} else if ($('.spec').size() < 3) {
				$(".add_spec, .add_table_foodshop").css('display', 'block');
			}
		}
	});

	$(".properties .bot_add a.btn").live('click',function(){
		var i = $(this).parents('.properties').index();
		var ii = $(this).parents('.properties').find('li').size();
		var t = '<li><u>'+L_('属性的属性值：')+'</u><input type="text" class="txt properties_val" name="properties_val['+i+'][]"/> <a class="list_del" href="javascript:;" title="'+L_("删除这个选项")+'">×</a><label class="statusSwitch" style="display:inline-block;"><input name="properties_val_status_'+i+'_'+ii+'" class="ace ace-switch ace-switch-6" type="checkbox" value="1" checked/><span class="lbl"></span></label>';
		if($(this).parents(".question_box").find("ul li").size() <= 25){
			$(this).parents(".question_box").find("ul").append(t);
		}else{
			alert(L_("数据已达至上限"));
		}
	});

	$(".add_properties").click(function(){
		var i = $('.properties').size();
		var t = '<div class="question_box properties"><p class="question_info"><span>'+L_('属性名称：')+'</span><input type="text" class="txt properties_name" value="" name="properties[]"/><span>'+L_('可选个数：')+'</span><input type="text" class="txt properties_num" value="1" name="properties_num[]" style="width:50px"/>'
				+'<input type="hidden" name="properties_id[]" ><a href="javascript:;" class="box_del">'+L_('删除')+'</a></p>'
				+'<ul id="1" class="optionul"><li><u>'+L_('属性的属性值：')+'</u><input type="text" class="txt properties_val" name="properties_val['+i+'][]"/> <a class="list_del" href="javascript:;" title="'+L_("删除这个选项")+'">×</a><label class="statusSwitch" style="display:inline-block;"><input name="properties_val_status_'+i+'_0" class="ace ace-switch ace-switch-6" type="checkbox" value="1" checked/><span class="lbl"></span></label></li>'
				+'</ul><p class="bot_add"><a href="javascript:;" class="btn btn-sm btn-success">  添加属性的属性值</a></p></div>';
		$(".add_properties").before(t);
	});

	$(".spec .bot_add a.btn").live('click',function(){
		var i = $(this).parents('.spec').index();
		var t = '<li><u>'+L_('规格属性值：')+'</u><input type="hidden" class="hide_txt spec_val_id" name="spec_val_id['+i+'][]"> <input type="text" class="txt spec_val" name="spec_val['+i+'][]"/> <a class="list_del" href="javascript:;" title="'+L_("删除这个选项")+'">×</a></li>';
		if ($(this).parents(".question_box").find("ul li").size() <= 25) {
			$(this).parents(".question_box").find("ul").append(t);
		} else {
			alert(L_("数据已达至上限"));
		}
	});

	$(".add_spec").click(function(){
		var i = $('.spec').size();
		var t = '<div class="question_box spec"><p class="question_info"><span>'+L_('规格名称：')+'</span><input type="text" class="txt spec_name" value="" name="specs[]"/>'
				+'<input type="hidden" name="spec_id[]" /><a href="javascript:;" class="box_del">'+L_('删除')+'</a></p>'
				+'<ul id="1" class="optionul"><li><u>'+L_('规格属性值：')+'</u><input type="hidden" class="hide_txt spec_val_id" name="spec_val_id['+i+'][]" value=""> <input type="text" class="txt spec_val" name="spec_val['+i+'][]"/> <a class="list_del" href="javascript:;" title="'+L_("删除这个选项")+'">×</a></li>'
				+'</ul><p class="bot_add"><a href="javascript:;" class="btn btn-sm btn-success">  添加规格的属性值</a></p></div>';
		$(".add_spec").before(t);
		if ($('.spec').size() > 0) {
			$('.add_table, .add_table_foodshop').css('display', 'block');
		}
		if ($('.spec').size() >= 3) {
			$(".add_spec").css('display', 'none');
		}
	});

	$('.add_table').click(function(){
		var header_html = '<tr><th>'+L_('商品条形码')+'</th>';
		header_html+= '<th>'+L_('规格图')+'</th>';
		formathtml = new Array(), format_value = new Array();
		var flag = false;
		$('.spec_name').each(function(){
			if ($(this).val().length < 1) {
				alert(L_('请输入规格名称'));
				flag = true;
				return false;
			}
			header_html += '<th>' + $(this).val() + '</th>';
		});

		var zl = '';
		if($('#is_open_shop_weigh').val() == 1){
            var zl = '<th>重量('+weight_unit+')</th>';
            var zlinput = '<td><input type="text" class="txt" name="weight[]" value="" style="width:80px;"></td>';
		}

		if ($('#limit_type').val() == 0) {
		    header_html += zl;
		    header_html += '<th>'+L_('原价')+'</th>';
		    header_html += '<th>'+L_('进价')+'</th>';
		    header_html += '<th>'+L_('现价')+'</th>';
		    if(open_price_type=='1'){
		    	header_html += '<th>'+L_('价格类型')+'</th>';
		    	header_html += '<th>'+L_('所需金钱')+'</th>';
		    	header_html += '<th>'+L_('所需积分')+'</th>';
		    }
		    
		    /*header_html += '<th>'+L_('限时价')+'</th>';*/
		    header_html += '<th>'+L_('每单限购')+'</th>';
		    header_html += '<th>'+L_('库存')+'</th>';
		} else {
		    header_html += zl
		    header_html += '<th>'+L_('原价')+'</th>';
		    header_html += '<th>'+L_('进价')+'</th>';
		    header_html += '<th>'+L_('现价')+'</th>';
		    if(open_price_type=='1'){
		    	header_html += '<th>'+L_('价格类型')+'</th>';
		    	header_html += '<th>'+L_('所需金钱')+'</th>';
		   		header_html += '<th>'+L_('所需积分')+'</th>';
			}
		    /*header_html += '<th>'+L_('限时价')+'</th>';*/
		    header_html += '<th>每个ID限购</th>';
		    header_html += '<th>'+L_('库存')+'</th>';
		}

		$('.properties_name').each(function(){
			if ($(this).val().length < 1) {
				alert(L_('请输入属性名称'));
				flag = true;
				return false;
			}
			header_html += '<th>' + $(this).val() + '(可选个数)</th>';
		});
		header_html += '</tr>';

		var spec_val_arr = new Array();
		var spec_id_arr = new Array();
		$('.spec').each(function(i){
			spec_val_arr[i] = new Array();
			$(this).children('ul').children('li').children('.spec_val').each(function(){
				if ($(this).val().length < 1) {
					alert(L_('请输入规格值的名称'));
					flag = true;
					return false;
				}
				spec_val_arr[i].push($(this).val());
			});
			spec_id_arr[i] = new Array();
			$(this).children('ul').children('li').children('.spec_val_id').each(function(l){
				if ($(this).val().length < 1) {
					spec_id_arr[i].push('index_' + l);
				} else {
					spec_id_arr[i].push('id_' + $(this).val());
				}
			});
		});
		if (flag) return false;
		var price_type = $('#price_type').val();
		if(price_type==1){
			var price_type_name = '全现金';
		}else if (price_type==2){
			var price_type_name = score_name+'+现金';
		}else if (price_type==3){
			var price_type_name = '全'+score_name;
		}else{
			price_type_name = '商品现价';
		}
		format_id(spec_id_arr, 0, '');
		format_html(spec_val_arr, 0, '');
		for (var e = 0; e < formathtml.length; e++) {
			header_html += '<tr id="'+format_value[e]+'">';
			header_html += '<td><input type="text" class="txt" name="numbers[]" value="" style="width:100%;"></td>';
			header_html += '<td><input type="hidden" class="txt" name="reg_imgs[]" value="" style="width:100%;"><div class="reg_file">'+L_('上传图片')+'</div></td>'; 
			header_html += formathtml[e] + zlinput ;
			header_html += '<td><input type="text" class="txt" name="old_prices[]" value="" style="width:80px;"></td>';
			header_html += '<td><input type="text" class="txt" name="cost_prices[]" value="" style="width:80px;"></td>';
			header_html += '<td><input type="text" class="txt" name="prices[]" value="" style="width:80px;"></td>';
			if(open_price_type=='1'){
				header_html += '<td><span class="price_type_name">'+price_type_name+'</span></td>';
				header_html += '<td><input type="text" class="txt" name="account_moneys[]" value="" style="width:80px;"></td>';
				header_html += '<td><input type="text" class="txt" name="account_scores[]" value="" style="width:80px;"></td>';
			}
			/*header_html += '<td><input type="text" class="txt" name="seckill_prices[]" value="" style="width:80px;"></td>';*/
			header_html += '<td><input type="text" class="txt" name="max_nums[]" value="0" style="width:80px;"></td>';
			header_html += '<td><input type="text" class="txt" name="stock_nums[]" value="-1" style="width:80px;"></td>';
			$('.properties_name').each(function(t){
				var properties_num = $(this).parent().find('.properties_num').val();
				header_html += '<td><input type="text" class="txt" name="num' + t + '[]" value="'+ properties_num +'" style="width:80px;kkkk"></td>';
			});
			header_html += '</tr>';
		}
		$('#table_list').html(header_html);
		var values = [];
		if (window.sessionStorage.getItem(session_index) != '') {
			values = JSON.parse(window.sessionStorage.getItem(session_index));
			for (var i1 in values) {
				for (var i2 in values[i1]) {
					if(i2=='reg_imgs[]'){
						var arr=values[i1][i2];
						if(arr && arr.title){
							var obj=$('#' + i1 + ' input[name="'+i2+'"]');
							obj.next().hide();
							var html='<p><img src='+arr['url']+' class="reg_img"><a href="#" onclick="deleteImage(\''+arr.title+'\',this,2);return false;">[ 删除 ]</a></p>';
								obj.before(html);
							$('#' + i1 + ' input[name="'+i2+'"]').val(arr['title']);
						}
					}else{
						$('#' + i1 + ' input[name="'+i2+'"]').val(values[i1][i2]);
					}
                    $('.properties_name').each(function(t){
                        var properties_num = $(this).parent().find('.properties_num').val();
                        var header_h = 'num' + t + '[]';
                        $('input[name="'+header_h+'"]').val(properties_num);
                    });
				}
			}
		}
		add_file_upload();
		//JSON.parse(window.sessionStorage.getItem(session_index));
		// console.log(values);
	});

	$('.add_table_foodshop').click(function(){
		var header_html = '<tr><th>'+L_('商品条形码')+'</th>';
		formathtml = new Array(), format_value = new Array();
		var flag = false;
		$('.spec_name').each(function(){
			if ($(this).val().length < 1) {
				alert(L_('请输入规格名称'));
				flag = true;
				return false;
			}
			header_html += '<th>' + $(this).val() + '</th>';
		});
		header_html += '<th>'+L_('原价')+'</th><th>'+L_('现价')+'</th><th style="display:none">'+L_('限时价')+'</th><th>'+L_('当前库存')+'</th><th>'+L_('原始库存')+'</th>';
		$('.properties_name').each(function(){
			if ($(this).val().length < 1) {
				alert(L_('请输入属性名称'));
				flag = true;
				return false;
			}
			header_html += '<th>' + $(this).val() + '(可选个数)</th>';
		});
		header_html += '</tr>';

		var spec_val_arr = new Array();
		var spec_id_arr = new Array();
		$('.spec').each(function(i){
			spec_val_arr[i] = new Array();
			$(this).children('ul').children('li').children('.spec_val').each(function(){
				if ($(this).val().length < 1) {
					alert(L_('请输入规格值的名称'));
					flag = true;
					return false;
				}
				spec_val_arr[i].push($(this).val());
			});
			spec_id_arr[i] = new Array();
			$(this).children('ul').children('li').children('.spec_val_id').each(function(l){
				if ($(this).val().length < 1) {
					spec_id_arr[i].push('index_' + l);
				} else {
					spec_id_arr[i].push('id_' + $(this).val());
				}
			});
		});
		if (flag) return false;
		format_id(spec_id_arr, 0, '');
		format_html(spec_val_arr, 0, '');
		for (var e = 0; e < formathtml.length; e++) {
			header_html += '<tr id="'+format_value[e]+'"><td><input type="text" class="txt" name="numbers[]" value="" style="width:100%;"></td>' + formathtml[e] + '<td><input type="text" class="txt" name="old_prices[]" value="" style="width:80px;"></td><td><input type="text" class="txt" name="prices[]" value="" style="width:80px;"></td><td style="display:none"><input type="text" class="txt" name="seckill_prices[]" value="" style="width:80px;display:none;"></td><td><input type="text" class="txt" name="stock_nums[]" value="-1" style="width:80px;"></td><td><input type="text" class="txt" name="original_stocks[]" value="-1" style="width:80px;"></td>';
			$('.properties_name').each(function(t){
				header_html += '<td><input type="text" class="txt" name="num' + t + '[]" value="1" style="width:80px;"></td>';
			});
			header_html += '</tr>';
		}
		$('#table_list').html(header_html);
		var values = [];
		if (window.sessionStorage.getItem(session_index) != '') {
			values = JSON.parse(window.sessionStorage.getItem(session_index));
			for (var i1 in values) {
				for (var i2 in values[i1]) {
					$('#' + i1 + ' input[name="'+i2+'"]').val(values[i1][i2]);
				}
			}
		}
		//JSON.parse(window.sessionStorage.getItem(session_index));
		//console.log(values);
	});
	$(".box_del").live('click',function(){
		$(this).parents(".question_box").remove();
		if ($('.spec').size() < 1) {
			$('.add_table, .add_table_foodshop').css('display', 'none');
		} else if ($('.spec').size() < 3) {
			$(".add_spec, .add_table_foodshop").css('display', 'block');
		}
	});

	$("#table_list input").live('input', function(e){
		var values = [];
		if (window.sessionStorage.getItem(session_index) != '') {
			values = JSON.parse(window.sessionStorage.getItem(session_index));
		}
		var new_values = {};
		for (var i1 in values) {
			for (var i2 in values[i1]) {
				if (new_values[i1] == undefined) {
					new_values[i1] = {};
				}
				new_values[i1][i2] = values[i1][i2];
			}
		}
		if (new_values[$(this).parents('tr').attr('id')] == undefined) {
			new_values[$(this).parents('tr').attr('id')] = {};
		}
		var name=$(this).attr('name');
		if(name!='file'){
			new_values[$(this).parents('tr').attr('id')][name] = $(this).val();
		}
		window.sessionStorage.setItem(session_index, JSON.stringify(new_values));
	});
	if (typeof (category_list) != 'undefined') {
		var father_category_list = $.parseJSON(category_list);
		var son_category_list = null, cat_fid = parseInt($('#choose_category').attr('cat_fid')), cat_id = parseInt($('#choose_category').attr('cat_id'));
		var area_dom = '<select id="cat_fid" name="cat_fid" class="" style="margin-right:10px;">';
		if (cat_fid == 0) {
			area_dom += '<option value="0" selected="selected" >'+L_('选择分类')+'</option>';
		} else {
			area_dom += '<option value="0">'+L_('选择分类')+'</option>';
		}
		$.each(father_category_list, function(i, item){
			if (item.id == cat_fid) {
				if (item.son_list != undefined) {
					son_category_list = item.son_list;
					show_son_category(item.son_list, cat_id)
				}
				area_dom += '<option value="'+item.id+'" selected="selected" >'+item.name+'</option>';
			} else {
				area_dom += '<option value="'+item.id+'">'+item.name+'</option>';
			}
		});
		if(cat_fid>0){
			$('#mall_box').show();
		}else{
			$('#mall_box').hide();
		}
		area_dom+= '</select>';
		$('#choose_category').prepend(area_dom);
//		if (son_category_list != null) show_son_category(son_category_list, cat_id);
	}
	$('#cat_fid').change(function(){
		var cat_fid = $(this).val(), father_category_list = $.parseJSON(category_list);
		var this_son_list = null;
		$.each(father_category_list, function(i, item){
			if (item.id == cat_fid) {
				if (item.son_list != undefined) {
					this_son_list = item.son_list;
					show_son_category(item.son_list, 0);
				}
			}
		});
		if(cat_fid>0){
			$('#mall_box').show();
		}else{
			$('#mall_box').hide();
		}
		if (this_son_list == null) {
			$('.sortproperties').remove();
			if (document.getElementById('cat_id')) {
				$('#cat_id').replaceWith('');
			} else if(document.getElementById('cat_fid')) {
				$('#cat_fid').after('');
			} else {
				$('#choose_category').prepend('');
			}
		}
	});

	$(document).on('change', '#cat_id', function(){
		var cat_id = $(this).val();
		show_property_html($(this).val());
	});

	if (typeof (sortList) != 'undefined') {
		selectIds = $.parseJSON(selectIds);
		sortList = $.parseJSON(sortList);
		showSortSelectHtml(sortList, 1);
	}

	$(document).on('change', '.sort', function(){
		var index = parseInt($(this).data('index')), sort_id = $(this).val(), thisData = null;
		$.each(sortData[index], function(i, item){
			if (item.sort_id == sort_id) {
				if (item.son_list != undefined) {
					thisData = item.son_list;
				}
			}
		});
		var len = $('.sort').size();
		for (var i = index + 1; i <=  len; i++) {
			$('#sort_id_' + i).remove();
		}
		if (thisData != undefined) {
			showSortSelectHtml(thisData, index + 1);
			$('.sort:last').trigger('change');
		}
	});
});

function showSortSelectHtml(data, index)
{
	var sort_id = 0, thisData = null, isFirst = 0;
	if (selectIds!=null&&selectIds[index - 1] != undefined) {
		sort_id = selectIds[index - 1];
	}
	sortData[index] = data;

	var area_dom = '<select id="sort_id_' + index + '" name="sort_id_' + index + '" class=" sort" data-index="' + index + '" style="margin-right:10px;">';
//	if (sort_id == 0) {
//		area_dom += '<option value="0" selected="selected" >'+L_('选择分类')+'</option>';
//	} else {
//		area_dom += '<option value="0">'+L_('选择分类')+'</option>';
//	}
	$.each(data, function(i, item){
		if (sort_id == 0) {
			if (isFirst == 0) {
				if (item.son_list != undefined) {
					thisData = item.son_list;
				}
				area_dom += '<option value="'+item.sort_id+'" selected="selected" >'+item.sort_name+'</option>';
			} else {
				area_dom += '<option value="'+item.sort_id+'">'+item.sort_name+'</option>';
			}
			isFirst ++;
		} else {
			if (item.sort_id == sort_id) {
				if (item.son_list != undefined) {
					thisData = item.son_list;
				}
				area_dom += '<option value="'+item.sort_id+'" selected="selected" >'+item.sort_name+'</option>';
			} else {
				area_dom += '<option value="'+item.sort_id+'">'+item.sort_name+'</option>';
			}
		}
	});
	area_dom+= '</select>';
	if (index == 1) {
		$('#choose_sort').prepend(area_dom);
	} else {
		var i = index - 1;
		$('#sort_id_' + i).after(area_dom);
	}
	if (thisData != null) showSortSelectHtml(thisData, ++index);

}
function show_property_html(cat_id)
{
	$.post(ajax_goods_properties, {'cat_id':cat_id, 'goods_id':$('#goods_id').val()}, function(response){
		if (response.error_code == false) {
			var data = response.data, html = '';
			$.each(data, function(i, item){
				html += '<div class=" ">';
				html += '<div class="radio">';
				html += '<span class="lbl" style="color: red"> ' + item.name + '</span>';
				if (item.value_list != null) {
					$.each(item.value_list, function(ii, vitem){
						html += '<label>';
						if (vitem.checked == 1) {
							html += '<input class="cat_class" type="checkbox" name="goodsproperties[]" value="' + vitem.id + '" id="properties_' + vitem.id + '" checked="checked"/>';
						} else {
							html += '<input class="cat_class" type="checkbox" name="goodsproperties[]" value="' + vitem.id + '" id="properties_' + vitem.id + '"/>';
						}

						html += '<span class="lbl"><label for="properties_' + vitem.id + '">' + vitem.name + '</label></span>';
						html += '</label>';
					});
				}
				html += '</div>';
				html += '</div>';
			});
			html='<div class="form-group sortproperties"><label class="col-sm-1">'+L_('商品属性')+'</label><div style="float:left;">'+html+'</div></div>';
			$('.sortproperties').remove();
			$('#category').append(html);
		} else {
			$('.sortproperties').remove();
		}
	}, 'json');
}
function show_son_category(son_category_list, cat_id)
{
	var area_dom = '<select id="cat_id" name="cat_id" class="" style="margin-right:10px;">', now_cat_id = 0, isFirst = 0;
//	console.log(son_category_list)
	$.each(son_category_list, function(i, item){
		if (cat_id == 0) {
			if (isFirst == 0) {
				now_cat_id = item.id;
				area_dom += '<option value="'+item.id+'" selected="selected" >'+item.name+'</option>';
			} else {
				area_dom += '<option value="'+item.id+'">'+item.name+'</option>';
			}
			isFirst ++;
		} else {
			if (item.id == cat_id) {
				now_cat_id = item.id;
				area_dom += '<option value="'+item.id+'" selected="selected" >'+item.name+'</option>';
			} else {
				area_dom += '<option value="'+item.id+'">'+item.name+'</option>';
			}
		}
	});
	area_dom += '</select>';
	if (document.getElementById('cat_id')) {
		$('#cat_id').replaceWith(area_dom);
	} else if(document.getElementById('cat_fid')) {
		$('#cat_fid').after(area_dom);
	} else {
		$('#choose_category').prepend(area_dom);
	}
	show_property_html(now_cat_id)
}
function format_id(a, i, str)
{
	if (i == 0) {
		var ii = i + 1;
		for (var index = 0; index < a[i].length; index++) {
			if (ii == a.length) {
				format_value.push(str + a[i][index]);
			} else {
				format_id(a, ii, a[i][index]);
			}
		}
	} else if (i == a.length - 1) {
		for (var index = 0; index < a[i].length; index++) {
			//console.log(str + '_' + a[i][index]);
			format_value.push(str + '_' + a[i][index]);
		}
		return false;
	} else {
		var ii = i + 1;
		for (var index = 0; index < a[i].length; index++) {
			format_id(a, ii, str + '_' + a[i][index]);
		}
	}
}


function format_html(a, i, str)
{
	if (i == 0) {
		var ii = i + 1;
		for (var index = 0; index < a[i].length; index++) {
			if (ii == a.length) {
				formathtml.push(str + '<td>' + a[i][index] + '</td>');
			} else {
				format_html(a, ii, '<td>' + a[i][index] + '</td>');
			}
		}
	} else if (i == a.length - 1) {
		for (var index = 0; index < a[i].length; index++) {
			formathtml.push(str + '<td>' + a[i][index] + '</td>');
		}
		return false;
	} else {
		var ii = i + 1;
		for (var index = 0; index < a[i].length; index++) {
			format_html(a, ii, str + '<td>' + a[i][index] + '</td>');
		}
	}
}
/*添加题目和删除题目结束*/