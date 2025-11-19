var area_used_id=[],area_selected_id=[],tmp_area_selected_id=[],now_edit_area = null;
$(function(){
	$('.js-assign-cost').live('click',function(){
		now_edit_area = null;
		type = $(this).data('type');
		show_area_layer(type);
	});
	
	$('.freight-add-form .js-save-btn').live('click',function(){
		var save_btn = $(this);
		var name_dom = $('.freight-add-form input[name="name"]');
        var is_type = $('input[name=is_type]:checked').val();
		name_dom.val($.trim(name_dom.val()));
		if(name_dom.val().length == 0){
			layer.alert(L_('模板名称不能为空'), 1);
		}else if(is_type ==0 && $('.is_type_box1 .freight-template-table tbody tr').length == 0){
            layer.alert(L_('快递配送至少要有一个配送区域'));
        }else if(is_type ==0 && $('.is_type_box2 .freight-template-table tbody tr').length == 0){
            layer.alert(L_('物流配送至少要有一个配送区域'));
        }else if(is_type ==1 && $('.is_type_box1 .freight-template-table tbody tr').length == 0){
            layer.alert(L_('快递配送至少要有一个配送区域'));
        }else if(is_type ==2 && $('.is_type_box2 .freight-template-table tbody tr').length == 0){
            layer.alert(L_('物流配送至少要有一个配送区域'));
        }else{
            var post_txt = [];
            var freight_type = [];
			var vid = 0, freight = 0, full_money = 0;
			var first_weight=0;
			var first_freight=0;
			var add_weight=0;
			var add_freight=0;

			$.each($('.freight-template-table tbody tr'),function(i,item){
				var vid = $(this).data('vid'), aids = [];
				var type = $(this).data('type');
                var default_type = $("input[name='freight_type["+type+"]']:checked").val();
                type = type -1;
                if (!aids[type]) {
                    aids[type] = [];
                }
                if (!post_txt[type]) {
                    post_txt[type] = [];
                }
                freight_type[type] = default_type;
				$.each($(item).find('.text-depth'),function(j,jtem){
                    aids[type].push($(jtem).attr('area-id'));
				});

				var class_name = '.type_box'+default_type;

                console.log('default_type'+ type, default_type);
				if(default_type==1 || default_type==2){
					first_weight = $(item).find(class_name).find("input[name='first_weight']").val();
					first_freight = $(item).find(class_name).find("input[name='first_freight']").val();
					add_weight = $(item).find(class_name).find("input[name='add_weight']").val();
					add_freight = $(item).find(class_name).find("input[name='add_freight']").val();
				}
				freight = $(item).find('.cost-input[name="freight"]').val();
				full_money = $(item).find('.cost-input[name="full_money"]').val();
				//alert($("input[name='add_freight']").eq(1).val());
				var info = {'vid':vid,
						'aids':aids[type],
						'freight':freight,
						'first_weight':first_weight,
						'first_freight':first_freight,
						'add_weight':add_weight,
						'add_freight':add_freight,
						'full_money':full_money
				};
                console.log('info_'+ type, info);
                post_txt[type].push(info);
			});
			console.log('post_txt', post_txt);
			save_btn.html(L_('提交中...')).prop('disabled',true);
			var edit_tip_id = $('.freight-add-form').attr('tpl-id');
			if (edit_tip_id) {
				$.post(add_url,{name:name_dom.val(),area:post_txt,tpl_id:edit_tip_id,is_type:is_type,freight_type:freight_type},function(result){
					if (result.err_code == 0) {
						location.href = load_url;
					} else {
						layer.alert(result.err_msg,0);
					}
					save_btn.html(L_('保存')).prop('disabled',false);
				}, 'json');
			} else {
				$.post(add_url,{name:name_dom.val(),area:post_txt,is_type:is_type,freight_type:freight_type},function(result){
					if (result.err_code==0) {
						location.href = load_url;
					} else {
						layer.alert(result.err_msg, 0);
					}
				}, 'json');
			}
		}
	});
					
	$('.freight-template-list-wrap .freight-template-title').live('click',function(e){
		e.stopPropagation();
		var siblings_table = $(this).siblings('table');
		if(siblings_table.hasClass('hide')){
			$(this).find('.js-freight-extend-toggle').removeClass('freight-extend-toggle-extend');
			siblings_table.removeClass('hide');
		}else{
			$(this).find('.js-freight-extend-toggle').addClass('freight-extend-toggle-extend');
			siblings_table.addClass('hide');
		}
	});
					
	$('.freight-template-list-wrap .js-freight-edit').live('click',function(e){
		e.stopPropagation();
		location.href = $(this).data('href');
		return false;
	}); 

	
  $('.area-editor-depth li').live('click',function(){ 
	if($(this).find('.area-editor-list-title').hasClass('area-editor-list-select')){
		$(this).find('.area-editor-list-title').removeClass('area-editor-list-select');
	}else{
		$(this).find('.area-editor-list-title').addClass('area-editor-list-select');
	} 
}); 


	$('.js-area-editor-translate').live('click',function(){
		var areas = $('.area-editor-list .area-editor-depth').children('li');
		var selected_num = 0;
		$.each(areas,function(i,v){
			var selected_provence_li = $(v).children('div').hasClass('area-editor-list-select');
			if(selected_provence_li){
				var area_id = $(v).attr('area-id');
				// 检查该省下面的市有没有添加过，如果有，则删除该省下已添加过的市
				var used_li = $('.js-area-editor-used .area-editor-depth li');
				$.each(used_li,function(aa,bb){
					if(parseInt(area_id) == parseInt($(bb).attr('area-id'))){
						area_used_id[$(bb).attr('area-id')] = 0;
					}
				});
				area_used_id[area_id] = 1;
				selected_num++;
				$(v).remove();
				return true;	// 等同于php中的continue
			}
		});
		
		if(selected_num > 0){
			var area_used_html = '';
			for (var key in __alldiv){
				if(area_used_id[key] == 1){
					area_used_html += '<li area-id="'+key+'"><div class="area-editor-list-title"><div class="area-editor-list-title-content js-ladder-select"><div class="area-editor-ladder-toggle extend">&nbsp;</div>'+__alldiv[key][0]+'<div class="area-editor-remove-btn js-ladder-remove">×</div></div></div></li>';
				}
			}
			$('.js-area-editor-used .area-editor-depth').html(area_used_html);
			
		}
	});


	$('.js-ladder-remove').live('click',function(){
		var tmp_li = $(this).closest('li');
		var area_id = tmp_li.attr('area-id');
		area_used_id[area_id] = 0;
		tmp_area_selected_id[area_id] = 0;
		var area_notused_html = '';
		for (var key in __alldiv){
			if(area_used_id[key] != 1){
				area_notused_html += '<li area-id="'+key+'"><div class="area-editor-list-title"><div class="area-editor-list-title-content js-ladder-select"><div class="js-ladder-toggle area-editor-ladder-toggle extend">+</div><span>'+__alldiv[key][0]+'</span></div></div></li>';
			}
		}
		$('.js-area-editor-notused .area-editor-depth').html(area_notused_html);
		tmp_li.remove();
	});
	$('.js-modal-close').live('click',function(){
		$('.area-modal-wrap').remove();
		area_used_id = [];
	});
	
	$('.js-modal-save').live('click',function(){
		var save_type = $(this).data('type');
        var class_info = '.is_type_box1';
        if (save_type==2) {
            class_info = '.is_type_box2';
        }
		var used_li = $('.js-area-editor-used .area-editor-depth li');
		if(used_li.length == 0){
			alert(L_('请先选择省份！'));
			return;
		}
		var used_area = [];
		$.each(used_li,function(i,item){
			used_area[i] = $(item).attr('area-id');
		});

		var just_selectes = [];
		$.each($('.freight-template-table tbody tr'),function(ji,item){
			$.each($(item).find('.text-depth'),function(j,jtem){
				var area_id = $(jtem).attr('area-id');
				just_selectes[j] = area_id;
			});
		});

		var selected_area = [];
		selected_area = selected_area.concat(just_selectes);
		var is_exits = false;

		if(is_exits){
			layer_tips(1,L_('区域重复选择！'));
		}else{
			var html = '<tr data-vid="0" data-type="'+save_type+'"><td>';
			var area_html = '';
			$.each(used_area,function(i,item){
				area_html += '、<span area-id="'+item+'" class="text-depth">'+__alldiv[item][0]+'</span>';
			});
			html += area_html.substr(1);
			var default_type = $("input[name='freight_type["+save_type+"]']:checked").val();
			var freight = 0.00;
            var first_weight = 1;
			if(default_type==1){
				first_weight = 1;
			}else{
				first_weight = 0.1;
			}
			var first_freight = 0.00;
			var add_weight = 0.00;
			var add_freight = 0.00;
			var full_money = 0.00;
			if(now_edit_area){
				freight = now_edit_area.find("input[name='freight']").val();
				full_money = now_edit_area.find("input[name='full_money']").val();
				first_weight = now_edit_area.find("input[name='first_weight']").val();
				first_freight = now_edit_area.find("input[name='first_freight']").val();
				add_weight = now_edit_area.find("input[name='add_weight']").val();
				add_freight = now_edit_area.find("input[name='add_freight']").val();
			}
			
			html += '<div class="right"><a href="javascript:;" class="js-edit-cost-item" data-type="'+save_type+'">'+L_('编辑')+'</a> <a href="javascript:;" class="js-delete-cost-item" data-type="'+save_type+'">'+L_('删除')+'</a></div></td>';
			/*新增计重模式的运费计算*/
			html +='<td class="type_box1"><input type="text" value="' + first_weight + '" class="cost-input js-input-currency" name="first_weight" data-default="0" maxlength="5"></td>';
			html +='<td class="type_box1"><input type="text" value="' + first_freight + '" class="cost-input js-input-currency" name="first_freight" data-default="0" maxlength="5"></td>';
			html +='<td class="type_box1"><input type="text" value="' + add_weight + '" class="cost-input js-input-currency" name="add_weight" data-default="0" maxlength="5"></td>';
			html +='<td class="type_box1"><input type="text" value="' + add_freight + '" class="cost-input js-input-currency" name="add_freight" data-default="0" maxlength="5"></td>';
			html +='<td class="type_box2"><input type="text" value="' + first_weight + '" class="cost-input js-input-currency" name="first_weight" data-default="0" maxlength="5" data-min_num="0.1"></td>';
			html +='<td class="type_box2"><input type="text" value="' + first_freight + '" class="cost-input js-input-currency" name="first_freight" data-default="0" maxlength="5"></td>';
			html +='<td class="type_box2"><input type="text" value="' + add_weight + '" class="cost-input js-input-currency" name="add_weight" data-default="0" maxlength="5"></td>';
			html +='<td class="type_box2"><input type="text" value="' + add_freight + '" class="cost-input js-input-currency" name="add_freight" data-default="0" maxlength="5"></td>';
			html +='<td class="type_box3"><input type="text" value="' + freight + '" class="cost-input js-input-currency" name="freight" data-default="0" maxlength="5"></td>';

			html +='<td><input type="text" value="' + full_money + '" class="cost-input js-input-currency" name="full_money" maxlength="8"></td></tr>';
			if(now_edit_area){
				now_edit_area.replaceWith(html);
			}else{
				$(class_info + ' .freight-template-table tbody').append(html);
			}
			/*隐藏当前不显示的元素*/
            if(default_type==1){
                $(document).find(class_info+' .type_box1').show();
                $(document).find(class_info+' .type_box2').hide();
                $(document).find(class_info+' .type_box3').hide();
            }else if(default_type==2){
                $(document).find(class_info+' .type_box2').show();
                $(document).find(class_info+' .type_box1').hide();
                $(document).find(class_info+' .type_box3').hide();
            }else{
                $(document).find(class_info+' .type_box3').show();
                $(document).find(class_info+' .type_box1').hide();
                $(document).find(class_info+' .type_box2').hide();
            }

			$.each($(class_info + ' .freight-template-table .text-depth'),function(i,item){
				var area_id = $(item).attr('area-id');
				area_selected_id[area_id] = 1;
			});
			$('.area-modal-wrap').remove();
			area_used_id = [];
		}
	});

	/*限制首重数字不能小于0.1kg*/
	$(document).on('blur',"table input",function(){
		var val=$(this).val();
		val=parseFloat(val).toFixed(2);
		num=parseFloat(val);
		var min_num=parseFloat($(this).data('min_num'));
		if(min_num>0){
			if(num<min_num){
				layer.msg(L_('最小为')+min_num);
			}
			num=Math.max(num,min_num);
		}
		$(this).val(num);
	});
	
	$('.js-edit-cost-item').live('click',function(){
		var type = $(this).data('type');
		now_edit_area = $(this).closest('tr');
		var area_span = $(this).closest('td').find('span');
		$.each(area_span,function(i,item){
			var area_id = $(item).attr('area-id');
			area_used_id[area_id] = 1;
		});
		show_edit_area_layer(type);
	});
	var cost_item_obj = null;
	$('.js-delete-cost-item').live('click',function(){
		cost_item_obj = $(this).closest('tr');
		layer.tips('<div class="form-inline" style="padding:5px;"><span class="help-inline item-delete" style="display:inline-block;padding-right:20px;font-size:14px;letter-spacing:1px;">确定删除?</span><button type="button" class="tbtn btn-primary js-btn-confirm" id="js-btn-confirm"  style="margin-right:5px;">'+L_('确定')+'</button><button type="reset" class="tbtn js-btn-cancel">'+L_('取消')+'</button></div>',$(this),{
			tips: 3,
			time: 0
		});
		$('body').bind('click',function(e){
			e=e||window.event;
			var src=e.target||e.srcElement;
			if(src.id == 'js-btn-confirm'){
				var area_span = cost_item_obj.find('span');
				$.each(area_span,function(i,item){
					var area_id = $(item).attr('area-id');
					area_selected_id[area_id] = 0;
				});
				cost_item_obj.remove();
			}
			layer.closeAll();
			$('body').unbind('click');
		});
	});
	$('.js-btn-cancel').live('click',function(){
		layer.closeAll();
	});
	
	$('.freight-template-table tbody .js-input-number').live('blur',function(){
		$(this).val($.trim($(this).val()));
		if(!/^\d+$/.test($(this).val())){
			$(this).val('0');
		}
	});
	$('.freight-template-table tbody .js-input-currency').live('blur',function(){
		$(this).val($.trim($(this).val()));
		var float_val = parseFloat($(this).val());
		if(float_val > 9999.99){
			$(this).val('9999.00');
		}else if(!/^\d+(\.\d+)?$/.test($(this).val())){
			$(this).val('0.00');
		}else{
            var save_type = $(this).parent().parent('tr').data('type');
            var freight_type = $("input[name='freight_type["+save_type+"]']:checked").val();
			var name = $(this).attr("name");
			if(freight_type==1&&(name =='first_weight' || name =='add_weight')){
				$(this).val(float_val.toFixed(0));
			}else{
				$(this).val(float_val.toFixed(2));
			}

		}
		console.log($(this));
	});
	
	var delete_dom = null;
	$('.freight-template-list-wrap .js-freight-delete').live('click',function(e){
		delete_dom = $(this);
		layer.tips('<div class="form-inline" style="padding:5px;"><span class="help-inline item-delete" style="display:inline-block;padding-right:20px;font-size:14px;letter-spacing:1px;">确定删除?</span><button type="button" class="tbtn btn-primary js-btn-delete" id="js-btn-confirm"  style="margin-right:5px;">'+L_('确定')+'</button><button type="reset" class="tbtn js-btn-cancel">'+L_('取消')+'</button></div>',$(this),{
			tips: 3,
			time: 0,
			style: ['background-color:black; color:#fff', 'black']
		});
		e.stopPropagation();
		$('body').bind('click',function(e){
			e=e||window.event;
			var src=e.target||e.srcElement;
			if(src.id == 'js-btn-confirm'){
				$.post(delete_url,{tpl_id:delete_dom.attr('tpl-id')},function(result){
					if(result.err_code == 0){
						layer.alert(result.err_msg,0);
						delete_dom.parents('li').remove();
					}else{
						layer.alert(result.err_msg,0);
					}
				}, 'json');
			}
			layer.closeAll();
			$('body').unbind('click');
		});
	});
});

function show_edit_area_layer(type){
	tmp_area_selected_id = area_selected_id;
	var html = '<div class="area-modal-wrap"><div class="modal-mask"><div class="area-modal"><div class="area-modal-head">'+L_('选择可配送区域')+'</div><div class="area-modal-content"><div class="area-editor-wrap clearfix"><div class="area-editor-column js-area-editor-notused"><div class="area-editor"><h4 class="area-editor-head">'+L_('可选区域')+'</h4><ul class="area-editor-list"><li><ul class="area-editor-list area-editor-depth">';
	for (var key in __alldiv){
		if (area_used_id[key] == 1) {
			continue;
		}
		html += '<li area-id="'+key+'"><div class="area-editor-list-title"><div class="area-editor-list-title-content js-ladder-select"><div class="js-ladder-toggle area-editor-ladder-toggle extend">+</div><span>'+__alldiv[key][0]+'</span></div></div></li>';
	}
	html += '</ul></li></ul></div></div><button class="tbtn btn-wide area-editor-add-btn js-area-editor-translate">'+L_('添加')+'</button><div class="area-editor-column area-editor-column-used js-area-editor-used"><div class="area-editor"><h4 class="area-editor-head">'+L_('已选区域')+'</h4><ul class="area-editor-list"><li><ul class="area-editor-list area-editor-depth">';
	for (var key in __alldiv){
		if(area_used_id[key] == 1){
			html += '<li area-id="'+key+'"><div class="area-editor-list-title"><div class="area-editor-list-title-content js-ladder-select"><div class="js-ladder-toggle area-editor-ladder-toggle extend">+</div>'+__alldiv[key][0]+'<div class="area-editor-remove-btn js-ladder-remove">×</div></div></div></li>';
		}
	}
	html += '</ul></li></ul></div></div></div></div><div class="area-modal-foot"><button class="tbtn btn-primary btn-wide js-modal-save" data-type="'+type+'">'+L_('确定')+'</button>&nbsp;&nbsp;<button class="tbtn btn-wide js-modal-close" data-type="'+type+'">'+L_('取消')+'</button></div></div></div></div>';
	$('body').append(html);
}
function show_area_layer(type){
	var html = '<div class="area-modal-wrap"><div class="modal-mask"><div class="area-modal"><div class="area-modal-head">'+L_('选择可配送区域')+'</div><div class="area-modal-content"><div class="area-editor-wrap clearfix"><div class="area-editor-column js-area-editor-notused"><div class="area-editor"><h4 class="area-editor-head">'+L_('可选区域')+'</h4><ul class="area-editor-list"><li><ul class="area-editor-list area-editor-depth">';
	for (var key in __alldiv){
		html += '<li area-id="'+key+'"><div class="area-editor-list-title"><div class="area-editor-list-title-content"><div class="js-ladder-toggle area-editor-ladder-toggle extend">+</div><span>'+__alldiv[key][0]+'</span></div></div></li>';
	}
	html += '</ul></li></ul></div></div><button class="tbtn btn-wide area-editor-add-btn js-area-editor-translate">'+L_('添加')+'</button><div class="area-editor-column area-editor-column-used js-area-editor-used"><div class="area-editor"><h4 class="area-editor-head">'+L_('已选区域')+'</h4><ul class="area-editor-list"><li><ul class="area-editor-list area-editor-depth"></ul></li></ul></div></div></div></div><div class="area-modal-foot"><button class="tbtn btn-primary btn-wide js-modal-save" data-type="'+type+'">'+L_('确定')+'</button>&nbsp;&nbsp;<button class="tbtn btn-wide js-modal-close"  data-type="'+type+'">'+L_('取消')+'</button></div></div></div></div>';
	$('body').append(html);
}

