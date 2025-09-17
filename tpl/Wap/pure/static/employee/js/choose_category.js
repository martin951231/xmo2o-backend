
var window_width = $(window).width();
var window_height = $(window).height();
//显示一级分类
function show_employee_category_first(){
    $.post(employee_category,function(result){

        if (result.status==0) {
            alert(result.info);
        } else {
            result = $.parseJSON(result);
            if(result.errorCode == 0){
                var employee_category = result.result.employee_category;
                var msg = '';
                if (examine_status_info == 0 && examine_status_info != '') {
                    msg = 'disabled="disabled"';
                } else {
                    msg = '';
                }

                var cat_id_dom = '<select '+msg+' id="choose_cat_id" data-role="cat_id" name="cat_id" class="weui-select_info choose_category" style="text-align:center;margin-right:3%;padding: 0px;">';
                cat_id_dom+= '<option value="0" '+(0==$('#choose_category').attr('cat_id') ? 'selected="selected"' : '')+'>一级分类</option>';
                $.each(employee_category,function(i,item){
                    cat_id_dom+= '<option value="'+item.cat_id+'" '+(item.cat_id==$('#choose_category').attr('cat_id') ? 'selected="selected"' : '')+'>'+item.cat_name+'</option>';
                });
                //   cat_id_dom+= '</select>';
                cat_id_dom+= '</select>   <select id="choose_cat_fid" data-role="cat_fid" name="cat_fid" class="weui-select_info choose_category" style="text-align:center;margin-right:3%;padding: 0px;"><option value="0" selected="selected">二级分类</option></select><select id="choose_cat_pfcid" data-role="cat_pfcid" name="cat_pfcid" class="weui-select_info choose_category" style="text-align:center;padding: 0px;"><option value="0" selected="selected">三级分类</option></select>';

                $('#choose_category').prepend(cat_id_dom);

                if ($('#choose_cat_id').find('option:selected').attr('value') > 0) {
                    first_cid=$('#choose_cat_id').find('option:selected').attr('value');
                    show_employee_category_second($('#choose_cat_id').find('option:selected').attr('value'),$('#choose_cat_id').find('option:selected').html());
                }
                $('#choose_cat_id').change(function(){
                    first_cid=$(this).find('option:selected').attr('value');
                    show_employee_category_second($(this).find('option:selected').attr('value'),$(this).find('option:selected').html());
                });

            }else{
                alert(result.errorMsg);
            }
        }
    });
}
//显示二级分类

function show_employee_category_second(cat_fid,cat_name){
    $.post(employee_category,{cat_fid:cat_fid,cat_name:cat_name},function(result){
        if (result.status==0) {
            alert(result.info);
        } else {
            result = $.parseJSON(result);
            if(result.errorCode == 0){
                var employee_category = result.result.employee_category;
                var msg = '';
                if (examine_status_info == 0 && examine_status_info != '') {
                    msg = 'disabled="disabled"';
                } else {
                    msg = '';
                }
                var cat_fid_dom = '<select '+msg+' id="choose_cat_fid" data-role="cat_fid" name="cat_fid" class="weui-select_info choose_category" style="text-align:center;margin-right:3%;padding: 0px;">';
                cat_fid_dom+= '<option value="0" '+(0==$('#choose_category').attr('cat_fid') ? 'selected="selected"' : '')+'>二级分类</option>';
                $.each(employee_category,function(i,item){
                    cat_fid_dom+= '<option value="'+item.cat_id+'" '+(item.cat_id==$('#choose_category').attr('cat_fid') ? 'selected="selected"' : '')+'>'+item.cat_name+'</option>';
                });
                cat_fid_dom+= '</select>';
                if(document.getElementById('choose_cat_fid')){
                    $('#choose_cat_fid').replaceWith(cat_fid_dom);
                }else if(document.getElementById('choose_cat_id')){
                    $('#choose_cat_id').after(cat_fid_dom);
                }else{
                    $('#choose_cat_fid').prepend(cat_fid_dom);
                }

                if ($('#choose_cat_fid').find('option:selected').attr('value') > 0) {
                    show_employee_category_third($('#choose_cat_fid').find('option:selected').attr('value'),cat_fid,$('#choose_cat_fid').find('option:selected').html());
                }
                $('#choose_cat_fid').change(function(){
                    if ($(this).find('option:selected').attr('value') > 0) {
                        show_employee_category_third($(this).find('option:selected').attr('value'),$('#choose_cat_id').find('option:selected').attr('value'),$(this).find('option:selected').html());
                    }
                });

            }else{
                alert(result.errorMsg);
            }
        }
    });
}

//显示三级分类
function show_employee_category_third(cat_fid,cat_pfcid,cat_name){
    $.post(employee_category,{cat_fid:cat_fid,cat_pfcid:cat_pfcid,cat_name:cat_name},function(result){
        if (result.status==0) {
            alert(result.info);
        } else {
            result = $.parseJSON(result);
            if(result.errorCode == 0){
                var employee_category = result.result.employee_category;
                var msg = '';
                if (examine_status_info == 0 && examine_status_info != '') {
                    msg = 'disabled="disabled"';
                } else {
                    msg = '';
                }
                var cat_pfcid_dom = '<select '+msg+' id="choose_cat_pfcid" data-role="cat_pfcid" name="cat_pfcid" class="weui-select_info choose_category" style="text-align:center;padding: 0px;">';
                cat_pfcid_dom+= '<option value="0" '+(0==$('#choose_category').attr('cat_pfcid') ? 'selected="selected"' : '')+'>三级分类</option>';
                $.each(employee_category,function(i,item){
                    cat_pfcid_dom+= '<option value="'+item.cat_id+'" '+(item.cat_id==$('#choose_category').attr('cat_pfcid') ? 'selected="selected"' : '')+'>'+item.cat_name+'</option>';
                });
                cat_pfcid_dom+= '</select>';
                if(document.getElementById('choose_cat_pfcid')){
                    $('#choose_cat_pfcid').replaceWith(cat_pfcid_dom);
                }else if(document.getElementById('choose_cat_fid')){
                    $('#choose_cat_fid').after(cat_pfcid_dom);
                }else{
                    $('#choose_cat_pfcid').prepend(cat_pfcid_dom);
                }

                //模拟更新网址栏，防止用户刷新时，还是原页面
                var stateObj = { foo: category };
                history.replaceState(stateObj, "", category);


                $('#choose_cat_pfcid').change(function(){
                    if ($(this).find('option:selected').attr('value') > 0 && load) {
                        write_field_info(cat_fid, cat_pfcid);
                    }
                });
                load = true;


            }else{
                alert(result.errorMsg);
            }
        }
    });
}


//显示三级分类默认的
function show_employee_category_third_default(){
    var msg = '';
    if (examine_status_info == 0) {
        msg = 'disabled="disabled"';
    } else {
        msg = '';
    }
    var cat_pfcid_dom = '<select '+msg+' id="choose_cat_pfcid" data-role="cat_pfcid" name="cat_pfcid" class="weui-select_info choose_category" style="text-align:center;padding: 0px;">';
    cat_pfcid_dom+= '<option value="0" '+(0==$('#choose_category').attr('cat_pfcid') ? 'selected="selected"' : '')+'>请选择分类</option>';
    cat_pfcid_dom+= '</select>';
    if(document.getElementById('choose_cat_pfcid')){
        $('#choose_cat_pfcid').replaceWith(cat_pfcid_dom);
    }else if(document.getElementById('choose_cat_fid')){
        $('#choose_cat_fid').after(cat_pfcid_dom);
    }else{
        $('#choose_cat_pfcid').prepend(cat_pfcid_dom);
    }
}


// 处理显示问题
// 0 单行文本 1 多行文本 2 地图 3 下拉框选择 4 数字 5 邮件 6 日期 7 时间 8 日期时间 9 手机 10 多张图片
function write_field_info(cat_fid, cat_pfcid) {
    $(".mapaddress").css({
        'height':$(window).height()-84-250+'px'
    })
    $(".show_adress").css({
        'height':$(window).height()-42+'px'
    })
    $.post(employee_category,{cat_fid:cat_fid,cat_pfcid:cat_pfcid},function(result){
        if (result.errorCode == 0) {
            var write_group_info = result.result.write_group_info;
            // var write_field = result.result.write_field;
            var write_field = [];
            var write_html = '';
            var upload_key = [];
            if (write_group_info) {
                console.log('write_group_info',write_group_info);
                var num = 0;
                var i = 0;
                for(var j in write_group_info) {
                    if (write_group_info[j]) {
                        if (write_group_info[j]['gname']) {
                            write_html += '<div class="weui-cell" style="color: #F58444;font-weight: bold;">';
                            write_html +=write_group_info[j]['gname']+' : ';
                            if (write_group_info[j]['gid']==4) {
                                write_html +='（能更好的展现自己）';
                            }
                            write_html += '</div>';
                        }
                        write_field = write_group_info[j]['write_info'];
                        for (var n in write_field) {
                            var val = write_field[n];
                            i = parseInt(n) + parseInt(num);
                            var is_write = '';
                            var is_upload = '';
                            var is_required = '';
                            var alias_name = val['alias_name'];
                            if (val['iswrite']==1) {
                                is_required = 'data-required="required"';
                            } else {
                                is_write = '（选填)';
                                is_upload = '（可不上传)';
                            }

                            if (!val['des']) {
                                if (val['type'] == 0 || val['type'] == 1 || val['type'] == 4 || val['type'] == 5 || val['type'] == 6 || val['type'] == 7 || val['type'] == 8 || val['type'] == 9) {
                                    val['des'] = '请填写'+val['name']+'';
                                } else if(val['type'] == 2 || val['type'] == 3) {
                                    val['des'] = '请点击选择'+val['name']+'';
                                } else if(val['type'] == 10) {
                                    val['des'] = '请上传'+val['name']+'';
                                }
                            }

                            if (val && val['type'] == 0) {
                                write_html += '<div class="weui-cell">';
                                write_html += '  <div class="weui-cell__hd">';
                                if (val['iswrite']==1) {
                                    write_html += '<strong style="color:red;">*</strong>';
                                } else {
                                    write_html += ' ';
                                }
                                write_html += '  </div>';
                                write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                                write_html += '  <div class="weui-cell__bd">';
                                write_html += '    <input data-role="text" name="write_field[' + i + '][value]" class="value_'+alias_name+' weui-input" placeholder="' + val['des'] + is_write + '" type="text" value="" ' + is_required + '/>';
                                if (val['alias_name'] == 'work_unit') {
                                    write_html += '    <div id="searchBtn" class="listSearchBtn page-link" data-url="shopSearch"></div>';
                                }
                                write_html += '    <input name="write_field[' + i + '][id]"  value="' + val['id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                                write_html += '  </div>';
                                write_html += '</div>';

                            } else if (val && val['type'] == 1) {
                                write_html += '<div class="weui-cell">';
                                write_html += '  <div class="weui-cell__hd">';
                                if (val['iswrite']==1) {
                                    write_html += '<strong style="color:red;">*</strong>';
                                } else {
                                    write_html += ' ';
                                }
                                write_html += '  </div>';
                                write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                                write_html += '  <div class="weui-cell__bd">';
                                write_html += '    <textarea data-role="textarea" class="value_'+alias_name+' weui-textarea" rows="2" name="write_field[' + i + '][value]" placeholder="' + val['des'] + is_write + '"  value="" ' + is_required + '></textarea>';
                                write_html += '    <input name="write_field[' + i + '][id]"  value="' + val['id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                                write_html += '  </div>';
                                write_html += '</div>';
                            } else if (val && val['type'] == 2) {
                                write_html += '<div class="weui-cell">';
                                write_html += '  <div class="weui-cell__hd">';
                                if (val['iswrite']==1) {
                                    write_html += '<strong style="color:red;">*</strong>';
                                } else {
                                    write_html += ' ';
                                }
                                write_html += '  </div>';
                                write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                                write_html += '  <div class="weui-cell__bd">';
                                write_html += '    <input data-role="position" data-key="' + i + '" class="value_'+alias_name+' weui-input position_info position_key_' + i + '" type="text" name="write_field[' + i + '][value]" placeholder="' + val['des'] + is_write + '" readonly="readonly" ' + is_required + '/>';
                                write_html += '    <input name="write_field[' + i + '][id]"  value="' + val['id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                                write_html += '    <input class="long_'+alias_name+' long_' + i + '" name="write_field[' + i + '][long]"  type="hidden" />';
                                write_html += '    <input class="lat_'+alias_name+' lat_' + i + '" name="write_field[' + i + '][lat]"  type="hidden" />';
                                write_html += '    <input class="address_'+alias_name+' address_' + i + '" name="write_field[' + i + '][address]" type="hidden" />';
                                write_html += '  </div>';
                                write_html += '</div>';

                                write_html += '<div class="weui-cell">';
                                write_html += '  <div class="weui-cell__hd">';
                                if (val['iswrite']==1) {
                                    write_html += '<strong style="color:red;">*</strong>';
                                } else {
                                    write_html += ' ';
                                }
                                write_html += '  </div>';
                                write_html += '  <div class="weui-cell__hd"><label class="weui-label">补充地址</label></div>';
                                write_html += '  <div class="weui-cell__bd">';
                                write_html += '     <input data-role="position-desc" data-key="' + i + '" class="value-desc_'+alias_name+' weui-input position_desc position_desc_key_' + i + '" type="text" name="write_field[' + i + '][value-desc]" placeholder="选择地图后填写详细地址' + is_write + '" ' + is_required + '/>';
                                write_html += '  </div>';
                                write_html += '</div>';

                            } else if (val && val['type'] == 3) {
                                write_html += '<div class="weui-cell">';
                                write_html += '  <div class="weui-cell__hd">';
                                if (val['iswrite']==1) {
                                    write_html += '<strong style="color:red;">*</strong>';
                                } else {
                                    write_html += ' ';
                                }
                                write_html += '  </div>';
                                write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                                write_html += '  <div class="weui-cell__bd">';
                                write_html += '    <select class="weui-select" name="write_field[' + i + '][value]" data-role="select" ' + is_required + '>';
                                write_html += '        <option value="">' + val['des'] + '</option>';

                                if (val['use_field']) {
                                    for (var j in val['use_field']) {
                                        if (val['use_field'][j]) {
                                            write_html += '<option value="' + val['use_field'][j] + '">' + val['use_field'][j] + '</option>';
                                        }
                                    }
                                }

                                write_html += '    </select>';
                                write_html += '    <input name="write_field[' + i + '][id]"  value="' + val['id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                                write_html += '  </div>';
                                write_html += '</div>';
                            } else if (val && val['type'] == 4) {
                                write_html += '<div class="weui-cell">';
                                write_html += '  <div class="weui-cell__hd">';
                                if (val['iswrite']==1) {
                                    write_html += '<strong style="color:red;">*</strong>';
                                } else {
                                    write_html += ' ';
                                }
                                write_html += '  </div>';
                                write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                                write_html += '  <div class="weui-cell__bd">';
                                write_html += '    <input data-role="number" type="number" name="write_field[' + i + '][value]" class="value_'+alias_name+' weui-input" placeholder="' + val['des'] + is_write + '" value="" onkeyup="value=clearNoNum(this.value)" ' + is_required + '/>';
                                write_html += '    <input name="write_field[' + i + '][id]"  value="' + val['id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                                write_html += '  </div>';
                                write_html += '</div>';
                            } else if (val && val['type'] == 5) {
                                write_html += '<div class="weui-cell">';
                                write_html += '  <div class="weui-cell__hd">';
                                if (val['iswrite']==1) {
                                    write_html += '<strong style="color:red;">*</strong>';
                                } else {
                                    write_html += ' ';
                                }
                                write_html += '  </div>';
                                write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                                write_html += '  <div class="weui-cell__bd">';
                                write_html += '    <input data-role="email" type="email" name="write_field[' + i + '][value]" class="value_'+alias_name+' weui-input" placeholder="' + val['des'] + is_write + '" value="" ' + is_required + '/>';
                                write_html += '    <input name="write_field[' + i + '][id]"  value="' + val['id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                                write_html += '  </div>';
                                write_html += '</div>';
                            } else if (val && val['type'] == 6) {
                                write_html += '<div class="weui-cell">';
                                write_html += '  <div class="weui-cell__hd">';
                                if (val['iswrite']==1) {
                                    write_html += '<strong style="color:red;">*</strong>';
                                } else {
                                    write_html += ' ';
                                }
                                write_html += '  </div>';
                                write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                                write_html += '  <div class="weui-cell__bd">';
                                write_html += '    <input data-role="date" type="date" name="write_field[' + i + '][value]" class="value_'+alias_name+' weui-input" placeholder="' + val['des'] + is_write + '" value="" ' + is_required + '/>';
                                write_html += '    <input name="write_field[' + i + '][id]"  value="' + val['id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                                write_html += '  </div>';
                                write_html += '</div>';
                            } else if (val && val['type'] == 7) {
                                write_html += '<div class="weui-cell">';
                                write_html += '  <div class="weui-cell__hd">';
                                if (val['iswrite']==1) {
                                    write_html += '<strong style="color:red;">*</strong>';
                                } else {
                                    write_html += ' ';
                                }
                                write_html += '  </div>';
                                write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                                write_html += '  <div class="weui-cell__bd">';
                                write_html += '    <input data-role="time" type="time" name="write_field[' + i + '][value]" class="value_'+alias_name+' weui-input" placeholder="' + val['des'] + is_write + '" value="" ' + is_required + '/>';
                                write_html += '    <input name="write_field[' + i + '][id]"  value="' + val['id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                                write_html += '  </div>';
                                write_html += '</div>';
                            } else if (val && val['type'] == 8) {
                                console.log('val', val);
                                write_html += '<div class="weui-cell">';
                                write_html += '  <div class="weui-cell__hd">';
                                if (val['iswrite']==1) {
                                    write_html += '<strong style="color:red;">*</strong>';
                                } else {
                                    write_html += ' ';
                                }
                                write_html += '  </div>';
                                write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                                write_html += '  <div class="weui-cell__bd">';
                                write_html += '    <input style="float: left;width: 60%;" data-role="date" type="date" name="write_field[' + i + '][date_value]" class="date_value_'+alias_name+' weui-input" placeholder="' + val['des'] + is_write + '" value="" ' + is_required + '/>';
                                write_html += '    <input style="float: left;width: 40%;" data-role="time" type="time" name="write_field[' + i + '][time_value]" class="time_value_'+alias_name+' weui-input" value="" ' + is_required + '/>';
                                write_html += '    <input name="write_field[' + i + '][id]"  value="' + val['id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                                write_html += '  </div>';
                                write_html += '</div>';
                            } else if (val && val['type'] == 9) {
                                write_html += '<div class="weui-cell">';
                                write_html += '  <div class="weui-cell__hd">';
                                if (val['iswrite']==1) {
                                    write_html += '<strong style="color:red;">*</strong>';
                                } else {
                                    write_html += ' ';
                                }
                                write_html += '  </div>';
                                write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                                write_html += '  <div class="weui-cell__bd">';
                                write_html += '    <input data-role="phone" type="tel" name="write_field[' + i + '][value]" class="value_'+alias_name+' weui-input" placeholder="' + val['des'] + is_write + '" value="" ' + is_required + '/>';
                                write_html += '    <input name="write_field[' + i + '][id]"  value="' + val['id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                                write_html += '  </div>';
                                write_html += '</div>';
                            } else if (val && val['type'] == 10) {
                                write_html += '<div class="weui-cell">';
                                write_html += '<div class="weui-cell__bd">';
                                write_html += '  <div class="weui-uploader">';
                                write_html += '     <div class="weui-uploader__hd">';
                                if (val['iswrite']==1) {
                                    write_html += '<strong style="color:red;">*</strong>';
                                } else {
                                    write_html += ' ';
                                }
                                write_html += '         <p class="weui-uploader__title">' + val['name'] + '</p>';
                                write_html += '         <div class="weui-uploader__info" style="font-size:12px;">' + val['des'] + ' 最多上传8张照片'+is_upload+'</div>';
                                write_html += '     </div>';
                                write_html += '     <div class="weui-uploader__hd">';
                                write_html += '         <ul class="weui-uploader__files" id="uploaderFiles_'+i+'"></ul>';
                                write_html += '         <div class="weui-uploader__input-box">';
                                write_html += '             <input id="uploaderInput_' + i + '" class="weui-uploader__input" type="file"/>';
                                write_html += '             <input name="write_field[' + i + '][id]"  value="' + val['id'] + '"  type="hidden" />';
                                write_html += '             <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                                write_html += '             <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                                write_html += '             <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                                write_html += '             <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                                write_html += '             <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                                write_html += '             <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                                write_html += '         </div>';
                                write_html += '     </div>';
                                write_html += '  </div>';
                                write_html += '</div>';
                                write_html += '</div>';

                                // 图片初始化
                                upload_key.push(i);
                            } else if (val && val['type'] == 11) {
                                console.log('val', val);
                                write_html += '<div class="weui-cell">';
                                write_html += '  <div class="weui-cell__hd">';
                                if (val['iswrite']==1) {
                                    write_html += '<strong style="color:red;">*</strong>';
                                } else {
                                    write_html += ' ';
                                }
                                write_html += '  </div>';
                                write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                                write_html += '  <div class="weui-cell__bd weui-cells_radio" style="display:flex">';
                                write_html += '    <div class="weui-cell__bd" style="    text-align: left;">';
                                write_html += '        <label for="x11_' + i + '"><span>男</span>';
                                write_html += '            <input data-role="checked" type="radio" class="weui-check" name="write_field[' + i + '][value]" id="x11_' + i + '" value="1" checked="checked"><span class="weui-icon-checked" style="border: #ff4d00 1px solid;border-radius: 18%;"></span>';
                                write_html += '        </label>';
                                write_html += '    </div>';
                                write_html += '    <div class="weui-cell__bd" style="    text-align: left;">';
                                write_html += '        <label for="x12_' + i + '"><span>女</span>';
                                write_html += '            <input data-role="checked" type="radio" class="weui-check" name="write_field[' + i + '][value]" id="x12_' + i + '" value="2"><span class="weui-icon-checked" style="border: #ff4d00 1px solid;border-radius: 18%;"></span>';
                                write_html += '        </label>';
                                write_html += '    </div>';
                                write_html += '    <input name="write_field[' + i + '][id]"  value="' + val['id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                                write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                                write_html += '  </div>';
                                write_html += '</div>';
                            }else if (val && val['type'] == 12) {
                                write_html += '<div class="weui-cell">';
                                write_html += '<div class="weui-cell__bd">';
                                write_html += '  <div class="weui-uploader">';
                                write_html += '     <div class="weui-uploader__hd">';
                                write_html += '         <p class="weui-uploader__title">' + val['name'] + '</p>';
                                write_html += '         <div class="weui-uploader__info" style="font-size:12px;">' + val['des'] +'</div>';
                                write_html += '     </div>';
                                write_html += '     <div class="weui-uploader__hd">';
                                write_html += '         <ul class="weui-uploader__files" id="uploaderAvatar_'+i+'"></ul>';
                                write_html += '         <div class="weui-uploader__input-box">';
                                write_html += '             <input id="uploaderAvatarInput_' + i + '" class="weui-uploader__input" type="file"/>';
                                write_html += '             <input name="write_field[' + i + '][id]"  value="' + val['id'] + '"  type="hidden" />';
                                write_html += '             <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                                write_html += '             <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                                write_html += '             <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                                write_html += '             <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                                write_html += '             <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                                write_html += '             <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                                write_html += '         </div>';
                                write_html += '     </div>';
                                write_html += '  </div>';
                                write_html += '</div>';
                                write_html += '</div>';

                                // 图片初始化
                                single_img_info(i);
                            }
                            $('.write_box').html(write_html);

                            for (var m in upload_key) {
                                if (upload_key[m]) {
                                    console.log('upload_key_'+m, upload_key[m]);
                                    img_info(upload_key[m]);
                                }
                            }

                            $('input[data-role="position"]').click(function(){
                                $('input').blur()
                                var height_info = $('.write_box').css('height');
                                height_info = height_info.replace('px', '');
                                key = $(this).data('key');
                                console.log('key', key);
                                var winHeight = $(window).height() + parseInt(height_info)/2; //获取当前页面高度
                                $('#service-position').css({'z-index':'999999999','opacity':1, overflow: 'hidden'}).show();
                                $("body").addClass("overhidden")
                                $('html,body').animate({scrollTop: '0px'}, 500);
                                $('.page__bd').hide();
                                selectPosition($(this));
                            });
                            $('input[data-role="position-desc"]').keyup(function(){
                                key = $(this).data('key');
                                $('.address_'+key).val($('.position_key_' + key).val()+' '+$(this).val());
                            });
                        }
                        var length = write_field.length;
                        num = parseInt(num) + parseInt(length);
                    }
                }
            } else {
                layer.open({
                    content: '该分类没有自定义填写项，请联系管理员添加'
                    ,btn: ['确定']
                    ,yes: function(index){
                        layer.close(index);
                    }
                });
            }
        }
    }, 'json');
}




function img_info(key) {
    var photoct = $('#photo');
    var imgpop = $('#popctrl');
    var uploadinput = $('#uploaderInput_'+key);
    var max_upload_num = 8;
    var boxer = $('#uploaderFiles_'+key);



    var URL = window.URL || window.webkitURL;
    var blobURL;
    var file;
    uploadinput.on("change", function () {
        photoct.cropper('destroy').cropper({
            minContainerHeight: 320,
            autoCropArea:1
        });
        var files = this.files;
        if (!photoct.data('cropper')) {
            return;
        }
        if (files && files.length) {
            file = files[0];
            if (/^image\/\w+$/.test(file.type)) {
                blobURL = URL.createObjectURL(file);
                photoct.one('built.cropper', function () {
                    URL.revokeObjectURL(blobURL);
                }).cropper('reset').cropper('replace', blobURL);
                uploadinput.val('');
                imgpop.popup();
            } else {
                $.toptip('Please choose an image file.', 'error');
            }
        }
    });

    $('#popctrl .pub_funcbar a').each(function () {
        var btn = $(this);
        var mtd = btn.attr('data-method');
        btn.on('click', function () {
            if (mtd == 'destroy') {
            } else if (mtd == 'confirm') {
                result = photoct.cropper('getCroppedCanvas');
                photo = result.toDataURL('image/jpeg');
                var img=photo.split(',')[1];
                img=window.atob(img);
                var ia = new Uint8Array(img.length);
                for (var i = 0; i < img.length; i++) {
                    ia[i] = img.charCodeAt(i);
                }
                var bfile = new Blob([ia], {type:"image/jpeg"});


                compress(bfile, function(cmp_photo){
                    var img = cmp_photo.split(',')[1];
                    img = window.atob(img);
                    var ia = new Uint8Array(img.length);
                    for (var i = 0; i < img.length; i++) {
                        ia[i] = img.charCodeAt(i);
                    }
                    var blob = new Blob([ia], {type:"image/jpeg"});
                    var formdata=new FormData();
                    formdata.append('filename','file');
                    formdata.append('file',blob);

                    $.ajax({
                        type: 'post',
                        url: ajaxImgUpload,
                        data :  formdata,
                        processData : false,
                        contentType : false,
                        success: function (data) {
                            if(null==data){ tip_common('error|'+ERROR_TIP); return false;}
                            if(data.indexOf('success')!==-1){
                                var html = '<li class="uploader__file_del weui-uploader__file weui-uploader__file_status" style="background-image:url(' + cmp_photo + ')"><input type="hidden" name="write_field[' + key + '][value][]" value="' + data.split('|')[1] + '"/><div class="weui-uploader__file-content"><i class="weui-icon-warn iconfont icon-shanchu"></i></div></li>';
                                $('#loadingimg').remove();
                                boxer.append(html);
                            } else {
                                $('#loadingimg').remove();
                                tip_common(data);
                            }
                            // 删除事件
                            $(document).on('click', '.uploader__file_del', function () {
                                var that = $(this);
                                $.confirm(DELCONFIRM, function () {
                                    that.remove();
                                }, function () {
                                });
                                return false;
                            });
                        }
                    });
                });

                boxer.append('<li id="loadingimg" class="uploader__file_del weui-uploader__file weui-uploader__file_status"><div class="weui-uploader__file-content"><img src="'+static_path+'employee/images/loading.gif"/></div></li>');

            } else {
                var opt = btn.attr('data-option');
                photoct.cropper(mtd, opt);
            }
        });
    });
}


function single_img_info(key) {
    var photoct = $('#photo_single');
    var imgpop = $('#popctrl_single');
    var uploadinput = $('#uploaderAvatarInput_'+key);
    var boxer = $('#uploaderAvatar_'+key);

    console.log('uploadinput',uploadinput);
    console.log('boxer',boxer);


    var URL = window.URL || window.webkitURL;
    var blobURL;
    var file;
    $(document).on('change', '#uploaderAvatarInput_'+key, function () {
        console.log('uploaderAvatarInput_');
        photoct.cropper('destroy').cropper({
            minContainerHeight: 320,
            autoCropArea:1
        });
        var files = this.files;
        console.log('files1',files);
        if (!photoct.data('cropper')) {
            return;
        }
        console.log('files',files);
        if (files && files.length) {
            file = files[0];
            if (/^image\/\w+$/.test(file.type)) {
                blobURL = URL.createObjectURL(file);
                photoct.one('built.cropper', function () {
                    URL.revokeObjectURL(blobURL);
                }).cropper('reset').cropper('replace', blobURL);
                uploadinput.val('');
                imgpop.popup();
            } else {
                $.toptip('Please choose an image file.', 'error');
            }
        }
    });

    $('#popctrl_single .pub_funcbar a').each(function () {
        var btn = $(this);
        var mtd = btn.attr('data-method');
        btn.on('click', function () {
            if (mtd == 'destroy') {
            } else if (mtd == 'confirm') {
                result = photoct.cropper('getCroppedCanvas');
                photo = result.toDataURL('image/jpeg');
                var img=photo.split(',')[1];
                img=window.atob(img);
                var ia = new Uint8Array(img.length);
                for (var i = 0; i < img.length; i++) {
                    ia[i] = img.charCodeAt(i);
                }
                var bfile = new Blob([ia], {type:"image/jpeg"});


                compress(bfile, function(cmp_photo){
                    var img = cmp_photo.split(',')[1];
                    img = window.atob(img);
                    var ia = new Uint8Array(img.length);
                    for (var i = 0; i < img.length; i++) {
                        ia[i] = img.charCodeAt(i);
                    }
                    var blob = new Blob([ia], {type:"image/jpeg"});
                    var formdata=new FormData();
                    formdata.append('filename','file');
                    formdata.append('file',blob);

                    $.ajax({
                        type: 'post',
                        url: ajaxImgUpload,
                        data :  formdata,
                        processData : false,
                        contentType : false,
                        success: function (data) {
                            if(null==data){ tip_common('error|'+ERROR_TIP); return false;}
                            console.log('data',data);
                            if(data.indexOf('success')!==-1){
                                var html = '<li class="uploader__file_del weui-uploader__file weui-uploader__file_status" style="background-image:url(' + cmp_photo + ')"><input type="hidden" name="write_field[' + key + '][value][]" value="' + data.split('|')[1] + '"/><div class="weui-uploader__file-content"><i class="weui-icon-warn iconfont icon-shanchu"></i></div></li>';

                                console.log('html',html);
                                $('#loadingimg').remove();
                                $('#uploaderAvatar_'+key).html(html);
                            } else {
                                $('#loadingimg').remove();
                                tip_common(data);
                            }
                            // 删除事件
                            $(document).on('click', '.uploader__file_del', function () {
                                var that = $(this);
                                $.confirm(DELCONFIRM, function () {
                                    that.remove();
                                }, function () {
                                });
                                return false;
                            });
                        }
                    });
                });

                $('#uploaderAvatar_'+key).html('<li id="loadingimg" class="uploader__file_del weui-uploader__file weui-uploader__file_status"><div class="weui-uploader__file-content"><img src="'+static_path+'employee/images/loading.gif"/></div></li>');


            } else {
                var opt = btn.attr('data-option');
                photoct.cropper(mtd, opt);
            }
        });
    });
}


var map = null;
var marker = null;
var mapObjDom = null;
var key = null;
var nowPage = '';
$(function(){
    //检测是否需要显示分类
    if(document.getElementById('choose_category')){
        show_employee_category_first();
    }
    $('#pageShopSearchBackBtn').click(function(){
        goBackPage();
    });
    // 填写内容 操作
    $("#pageShopSearchTxt").bind('input', function(e){
        var address = $.trim($(this).val());
        if(address.length > 0){
            $('#pageShopSearchDel').show();
            $('#pageShopSearchBtn').addClass('so');
        }else{
            $('#pageShopSearchDel').hide();
            $('#pageShopSearchBtn').removeClass('so');
        }
    });
    // 点击输入框中 取消 去除输入值
    $('#pageShopSearchDel').click(function(){
        $('#pageShopSearchTxt').val('').trigger('input');
        isSearchListShow = false;
        showShopSearchList(true);
    });
    // 点击搜索
    $('#pageShopSearchBtn').click(function(){
        var address = $.trim($("#pageShopSearchTxt").val());
        if(address == ''){
            layer.open({
                content: '请您输入店铺名称'
                ,btn: ['确定']
                ,yes: function(index){
                    layer.close(index);
                }
            });
        }else{
            isSearchListShow = false;
            showShopSearchList(true);
        }
    });

    // 删除事件
    $(document).on('click', '.uploader__file_del', function () {
        var that = $(this);
        $.confirm(DELCONFIRM, function () {
            that.remove();
        }, function () {
        });
        return false;
    });


    /*点击后选则店铺*/
    $(document).on('click','.choose_link',function(){
        var that = $(this);
        var name = $(this).attr('data-name'); // 店铺名称
        var province_id = $(this).attr('data-province_id'); // 店铺所在省id
        var city_id = $(this).attr('data-city_id'); // 店铺所在城市id
        var area_id = $(this).attr('data-area_id'); // 店铺所在区域id
        var kefu_phone = $(this).attr('data-kefu_phone'); // 店铺 客服电话
        var adress = $(this).attr('data-adress'); // 店铺 地址
        var long = $(this).attr('data-long'); // 店铺 地址经纬度
        var lat = $(this).attr('data-lat'); // 店铺 地址经纬度
        var store_id = $(this).attr('data-store_id'); // 店铺 id

        layer.open({
            title:['店铺提示','background-color:#8DCE16;color:#fff;'],
            content:'您选择的店铺是：'+name,
            btn: ['确定选择','重新选择'],
            yes:function(index){
                $('.value_work_unit').val(name);
                $('.long_unit_address').val(long);
                $('.lat_unit_address').val(lat);
                $('.value_unit_phone').val(kefu_phone);
                $('.address_unit_address').val(adress);
                $('.employee_store_id').val(store_id);
                $('.value_unit_address').data({'long':long,'lat':lat,'address':adress}).val(adress);
                layer.close(index);
                $('#pageShopSearch').css({'z-index':'-999','opacity':0}).hide();
                $('#pageShopSearch #storeList .dealcard').empty();
                $('.page__bd').css('z-index','999').show();
            }
        });
        //
        return false;
    });

    var locationHash = location.hash.replace("#","");
    var locationHashParam = locationHash.split('-');
    var locationHashItem = locationHashParam[0];
    if (locationHashItem == 'shopSearch') {
        isShowShopSearch = false;
        redirectPage(locationHashItem);
    }


    /*页面点击事件*/
    $(document).on('click','.page-link',function(){
        isShowShopSearch = false;
        redirectPage('shopSearch');
        return false;
    });

    $('#service_time').val();

    $('section').css('min-height','100%');

    $('.arrow-wrapper').click(function(){
        closeWin();
    });

    $('.yxc-time-con dt[data-role="date"]').click(function(){
        $('.yxc-time-con dt[data-role="date"]').removeClass('active');
        $(this).addClass('active');
        $('.date-'+$(this).data('date')).show().siblings('div').hide();
        if(time_gap!=-1){

        }else{
            $('#serviceJobTime').val($('.yxc-time-con dt[data-role="date"].active').data('text'));
            $('#service_date').val($('.yxc-time-con dt[data-role="date"].active').data('date'));
            $('#service_time').val($(this).data('date'));
            closeWin();
        }
    });

    $('.yxc-time-con dd[data-role="item"]').click(function(){

        if(!$(this).hasClass('disable')){
            $('.yxc-time-con dd[data-role="item"]').removeClass('active');
            $(this).addClass('active');
            $('#serviceJobTime').val($('.yxc-time-con dt[data-role="date"].active').data('text') + ' ' +$(this).data('peroid'));
            $('#service_date').val($('.yxc-time-con dt[data-role="date"].active').data('date'));
            $('#service_time').val($(this).data('peroid'));
            closeWin();
        }
    });

    $('textarea.ipt-attr').focus(function(){
        $(this).css('height','60px');
    }).blur(function(){
        if($(this).val() == ''){
            $(this).css('height','24px');
        }
    });

    $('.select select').change(function(){
        if($(this).val() != ''){
            $(this).css('color','black');
        }else{
            $(this).css('color','#999');
        }
    });



    $('input[data-role="position"]').click(function(){
        var height_info = $('.write_box').css('height');
        height_info = height_info.replace('px', '');
        key = $(this).data('key');
        console.log('key', key);
        var winHeight = $(window).height() + parseInt(height_info)/2; //获取当前页面高度
        $('#service-position').css({'z-index':'11111','opacity':1, height: winHeight + 'px'}).show();
        $('html,body').animate({scrollTop: '0px'}, 500);
        $('.page__bd').hide();
        console.log('selectPosition', $(this));
        selectPosition($(this));
    });
    $('input[data-role="position-desc"]').keyup(function(){
        key = $(this).data('key');
        $('.address_'+key).val($('.position_key_' + key).val()+' '+$(this).val());
    });

    $("#se-input-wd").bind('input',function(e){
        conAdress()

    });
    $('#addressShow').delegate("li","click",function(){
        var addressName = $(this).attr("address");
        var addressLongitude = $(this).attr("lng");
        var addressLatitude = $(this).attr("lat");
        var addressSugAddress = $(this).attr("sug_address");
        layer.open({
            title:['位置提示','background-color:#8DCE16;color:#fff;'],
            content:'您选择的位置是：'+addressName,
            btn: ['确定位置','重新选择'],
            yes:function(index){
                console.log('mapObjDom', mapObjDom);
                $('.long_'+key).val(addressLongitude);
                $('.lat_'+key).val(addressLatitude);
                $('.address_'+key).val(addressName);
                mapObjDom.data({'long':addressLongitude,'lat':addressLatitude,'address':addressName}).val(addressSugAddress);
                layer.close(index);
                closeWin();
            }
        });
    });


    $('#dosubmit').click(function(){
        var nowDom = $(this);
        var slA = $('#form').serializeArray();
        console.log('slA',slA);
        for(var i in slA){
            var tmpDom = $("[name='"+slA[i].name+"']");
            if(tmpDom.data('role')){
                if(tmpDom.data('role') == 'cat_id' && (slA[i].value == '' || slA[i].value == 0)){
                    layer.open({content:'请选择一级分类', skin:'msg', time:2});
                    return false;
                }else if(tmpDom.data('role') == 'cat_fid' && (slA[i].value == '' || slA[i].value == 0)){
                    layer.open({content:'请选择二级分类', skin:'msg', time:2});
                    return false;
                }else if(tmpDom.data('role') == 'cat_pfcid' && (slA[i].value == '' || slA[i].value == 0)){
                    layer.open({content:'请选择三级分类', skin:'msg', time:2});
                    return false;
                }
                if(tmpDom.data('required')){
                    if(tmpDom.data('role') == 'phone' && slA[i].value == ''){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'text' && slA[i].value == ''){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'position' && slA[i].value == ''){
                        console.log(1)
                        console.log(tmpDom.data('long'))
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'textarea' && slA[i].value == ''){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'number' && !/^[0-9]*$/.test(slA[i].value)){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'date'  && slA[i].value == '' ){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'time' && slA[i].value == ''){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'select' && slA[i].value == ''){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'email'  && !/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i.test(slA[i].value)){
                        formError(tmpDom);
                        return false;
                    }
                }
            }
        }
        $("#dosubmit").attr("disabled", "disabled");
        $("#dosubmit").val("正在入驻...");
        $.post($('#form').attr('action'),slA,function(result){
            console.log('result', result);
            if(result.status == 1){
                layer.open({
                    title:'提示：'
                    ,content:result.info
                    ,btn:['好的']
                    ,shadeClose:false
                    ,success:function(){
                        window.location.href = ajax_url_root + 'my_detail';
                    }
                });
            }else{
                layer.open({
                    title:'提示：'
                    ,content:result.info
                    ,btn:['好的']
                    ,shadeClose:false
                });
                $("#dosubmit").removeAttr("disabled");
                $("#dosubmit").val("确认入驻");
            }
        }, 'json');
        return false;
    });


});

function formError(tmpDom){
    $('.form_error').removeClass('form_error');
    layer.open({content:tmpDom.attr('placeholder'), skin:'msg', time:2});
}

function selectPosition(objDom){
    $('.se-input-wd').val('');
    $('#addressShow').empty();
    mapObjDom = objDom;
    // 百度地图API功能
    map = new BMap.Map("allmap",{"enableMapClick":false});
    map.enableScrollWheelZoom();
    map.enableContinuousZoom();
    map.centerAndZoom(new BMap.Point(116.331398,39.897445),6);
    map.addEventListener("dragend", function(e){
        var centerMap = map.getCenter();
        getPositionInfo(centerMap.lat,centerMap.lng);
    });
    map.addEventListener('zoomend', function (e) {

        var centerMap = map.getCenter();

        getPositionInfo(centerMap.lat,centerMap.lng);
    });


    if(objDom.data('long')){
        var oPoint = new BMap.Point(objDom.data('long'),objDom.data('lat'));
        map.centerAndZoom(oPoint, 16);
        getPositionInfo(objDom.data('lat'),objDom.data('long'));




    }else{
        if(user_long == 0){
            var geolocation = new BMap.Geolocation();
            geolocation.getCurrentPosition(function(r){
                if(this.getStatus() == BMAP_STATUS_SUCCESS){
                    var oPoint = new BMap.Point(r.point.lng, r.point.lat);
                    map.centerAndZoom(oPoint,16);

                    getPositionInfo(r.point.lat,r.point.lng);

                } else {

                }
            },{enableHighAccuracy: true})
        }else{
            var oPoint = new BMap.Point(user_long,user_lat);
            map.centerAndZoom(oPoint, 16);
            getPositionInfo(user_long,user_lat);
        }

    }


}

function getPositionInfo(lat,lng){
    $.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&callback=renderReverse&location='+lat+','+lng+'&output=json&pois=1&callback=getPositionAdress&json=?');
}

function getPositionAdress(result){
    if(result.status == 0){
        result = result.result;
        var re = [];
        re.push({'name':result.sematic_description,'address':result.formatted_address,'long':result.location.lng,'lat':result.location.lat});
        for(var i in result.pois){
            re.push({'name':result.pois[i].name,'address':result.pois[i].addr,'long':result.pois[i].point.x,'lat':result.pois[i].point.y});
        }
        getAdress(re);
    }else{
        alert('获取位置失败！');
    }
}
function getAdress(re){
    var addressHtml = '';
    for(var i=0;i<re.length;i++){
        addressHtml += '<li lng="'+re[i]['long']+'" lat="'+re[i]['lat']+'" sug_address="'+re[i]['name']+'" address="'+re[i]['address']+'">';
        addressHtml += '<div class="mapaddress-title"><span class="icon-location" data-node="icon"></span><span class="recommend">'+(i == 0 ? '[推荐位置]' : '')+''+re[i]['name']+'</span></div>';
        addressHtml += '<div class="mapaddress-body">'+re[i]['address']+'</div>';
        addressHtml += '</li>';
    }
    $('#addressShow').html(addressHtml);
}
function conAdress_before(){
    $("#se-input-wd").blur()
    conAdress()
}
function conAdress(){
    var address = $.trim($('#se-input-wd').val());
    if(address.length>0 && address !== '直接输入定位您的地址'){
        $('#addressShow').empty();
        $.get('/index.php?g=Index&c=Map&a=suggestion', {query:user_city+address}, function(data){
            if(data.status == 1){
                getAdress(data.result);
            }
        });
    }
}
function closeWin(){
    $('#service-type').hide();
    $('#service-date').hide();
    $('#service-position').css({'z-index':'-999','opacity':0}).hide();
    $("body").removeClass("overhidden")
    $('.page__bd').css('z-index','0').show();
}



function compress(file, callback){
    var reader = new FileReader();

    reader.onload = function (e) {

        var image = $('<img/>');
        image.on('load', function () {
            var square = 640;
            var canvas = document.createElement('canvas');


            if (this.width > this.height) {
                canvas.width = Math.round(square * this.width / this.height);
                canvas.height = square;
            } else {
                canvas.height = Math.round(square * this.height / this.width);
                canvas.width = square;
            }

            var context = canvas.getContext('2d');
            context.clearRect(0, 0, square, square);
            var imageWidth = canvas.width;
            var imageHeight = canvas.height;
            var offsetX = 0;
            var offsetY = 0;
            context.drawImage(this, offsetX, offsetY, imageWidth, imageHeight);
            var data = canvas.toDataURL('image/jpeg', 0.8);
            console.log([imageWidth,imageHeight]);
            callback(data);
        });

        image.attr('src', e.target.result);
    };

    reader.readAsDataURL(file);
}

function tip_common(msg) {
    var msgar = msg.split('|');
    var type = '';
    if(msgar[0]=='error'){
        type = 'cancel';
    }else if(msgar[0]=='loading'){
        $.showLoading(msgar[1]);
        if (msgar[2]) { setTimeout(function () {
            var jpurl = msgar[2]+(msgar[2].indexOf(_APPNAME+'?id')!==-1?_URLEXT:'');
            if(typeof wx !=='undefined' && jpurl.indexOf('javascript')===-1){
                if (window.__wxjs_environment === 'miniprogram') {
                    GSITE = GSITE.replace(/http:\/\//, 'https://');
                    var needfix = (jpurl.indexOf('http://')===-1&&jpurl.indexOf('https://')===-1) ? (GSITE+''+jpurl) : jpurl;
                    wx.miniProgram.navigateTo({url:'/pages/webview/webview?webview_url='+encodeURIComponent(needfix)+'&webview_title='+encodeURIComponent(_APPTITLE)});
                    return false;
                }
            }
            window.location.href = jpurl;
        }, 100); }
        return msgar;
    }
    $.toast(msgar[1], type);
    if (msgar[2]) {
        setTimeout(function () {
            if(msgar[2] == 'reload'){
                window.location.reload();
            }else{
                var jpurl = msgar[2]+(msgar[2].indexOf(_APPNAME+'?id')!==-1?_URLEXT:'');
                if(typeof wx !=='undefined' && jpurl.indexOf('javascript')===-1){
                    if (window.__wxjs_environment === 'miniprogram') {
                        GSITE = GSITE.replace(/http:\/\//, 'https://');
                        var needfix = (jpurl.indexOf('http://')===-1&&jpurl.indexOf('https://')===-1) ? (GSITE+''+jpurl) : jpurl;
                        wx.miniProgram.navigateTo({url:'/pages/webview/webview?webview_url='+encodeURIComponent(needfix)+'&webview_title='+encodeURIComponent(_APPTITLE)});
                        return false;
                    }
                }
                window.location.href = jpurl;
            }
        }, 2000);
    }
    return msgar;
}



var isShowShopSearch = false,loadShopSearchTimer=null,isSearchListShow=true,isShowShade = false;
var flag = false;
var myScroll;
function redirectPage(locationHash){
    console.log('locationHash', locationHash);
    pageLoadTips({showBg:false});
    nowPage = locationHash;
    var winHeight = $(window).height(); //获取当前页面高度
    $('#pageShopSearch').css({'z-index':'99999','opacity':1, height: winHeight + 'px', 'display': 'inline'}).show();
    $('.page__bd').hide();

    if(isShowShopSearch == false){
        $('#pageShopSearchTxt').width(window_width-124-32);
        isSearchListShow = false;
        showShopSearchList(true);


        $('#storeList').css({top:'50px'});
        $('#scroller').css({'min-height':($(window).height()-50+1)+'px'});

        myScroll = new IScroll('#storeList', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick(),scrollbars:false,useTransform:false,useTransition:false});
        var upIcon = $("#pullUp"),
            downIcon = $("#pullDown");
        myScroll.on("scroll",function(){
            var maxY = this.maxScrollY - this.y;
            if(this.y >= 50){
                if(!downIcon.hasClass("reverse_icon")) downIcon.addClass("reverse_icon").find('.pullDownLabel').html('松开可以刷新');
                return "";
            }else if(this.y < 50 && this.y > 0){
                if(downIcon.hasClass("reverse_icon")) downIcon.removeClass("reverse_icon").find('.pullDownLabel').html('下拉可以刷新');
                return "";
            }
            if(maxY >= 50){
                if(!upIcon.hasClass("reverse_icon")) upIcon.addClass("reverse_icon").find('.pullUpLabel').html('松开加载更多');
                return "";
            }else if(maxY < 50 && maxY >=0){
                if(upIcon.hasClass("reverse_icon")) upIcon.removeClass("reverse_icon").find('.pullUpLabel').html('上拉加载更多');
                return "";
            }
        });
        myScroll.on("slideDown",function(){
            if(this.y > 50){
                now_page = 0;
                hasMorePage = true;
                upIcon.removeClass('noMore loading').show();
                showShopSearchList(true);
            }
        });
        myScroll.on("slideUp",function(){
            console.log('shopSearchListHasMorePage', shopSearchListHasMorePage);
            if(shopSearchListHasMorePage){
                $('.listBox dl').append('<dd class="loadMoreList">正在加载...</dd>');
                // upIcon.addClass('loading');
                // setTimeout(function(){
                myScroll.refresh();
                myScroll.scrollTo(0,this.maxScrollY);
                showShopSearchList(false);
                // },200);
            }
            /* if(this.maxScrollY - this.y > 50 && !upIcon.hasClass('noMore')){
                upIcon.addClass('noMore').hide();
            } */
        });



        // 上拉加载和下拉刷新
        // $(window).scroll(function(e) {
        //     e.stopPropagation();
        //     //上拉加载
        //     if(flag){
        //         //数据加载中
        //         return false;
        //     }
        //     if ($(document).scrollTop() >= $(document).height() - $(window).height()) {
        //         $('.pullup').css('display', 'none');
        //         $('.loading').css('display', 'block');
        //         flag = true;
        //         if(nowPage == 'shopSearch'){
        //             if(isSearchListShow == false && shopSearchListHasMorePage == true && $(document).scrollTop() >= $(document).height() - $(window).height() - 50){
        //                 showShopSearchList();
        //             }
        //         }
        //     }
        // });
        isShowShopSearch = true;
    }
    pageLoadHides();
}

function goBackPage() {
    $('#pageShopSearch').css({'z-index':'-999','opacity':0}).hide();
    $("#pageShopSearchTxt").val('')
    $('.page__bd').css('z-index','9999').show();
}

// 搜索店铺
function showShopSearchList(newPage){
    console.log('isSearchListShow', isSearchListShow);
    if (isSearchListShow == false) {
        isSearchListShow = true;
        $('#pageShopSearch #storeListLoadTip').show();

        if(newPage || listShopSearchNowPage == 0){
            listShopSearchNowPage = 1;
            shopSearchListHasMorePage = true;
        }else{
            listShopSearchNowPage++;
        }
        if(shopSearchListHasMorePage){
            $("#pullUp").removeClass('noMore loading').show();
        }
        if(newPage){
            $('.listBox dl').html('');
            $(".noMore").hide()
        }

        $.getJSON(ajax_url_root+'ajaxMerchantStore',{page:listShopSearchNowPage,key:$('#pageShopSearchTxt').val(), cid:first_cid},function(result){
            console.log('newPage', newPage);
            if(result.store_list && result.store_list.length > 0){
                laytpl($('#listShopTpl').html()).render(result.store_list, function(html){
                    if(newPage){
                        $('.listBox dl').html(html).addClass('dealcard').show();
                        $('#pageShopSearch #storeList').show();
                    }else{
                        $('.listBox dl').append(html);
                    }
                });
                if(result.has_more == false){
                    shopSearchListHasMorePage = false;
                    $("#pullUp").addClass('noMore').hide();
                    $(".noMore").html('暂无更多数据!').show()

                }
                $('.loadMoreList').remove();
            }else{
                shopSearchListHasMorePage = false;
                if(listShopSearchNowPage==1){
                    $(".noMore").html('暂无数据!').show()
                }else{
                    $(".noMore").html('暂无更多数据!').show()
                }
                $("#pullUp").addClass('noMore').hide();
                $('.loadMoreList').remove();
            }
            $('.loading').css('display', 'none');
            setTimeout(function(){
                // console.log(more);
                myScroll.refresh();
                if(newPage){
                    myScroll.scrollTo(0,0);
                }
            },200);
            isSearchListShow = false;
        });
    }
}


function pageLoadTips(options){
    this.options = {
        showBg:true,
        top:'center',
        left:'center'
    }
    for (var i in options){
        this.options[i] = options[i];
    }
    options = this.options;
    //显示背景
    if(options.showBg){
        $('#pageLoadTipShade').removeClass('nobg');
    }else{
        $('#pageLoadTipShade').addClass('nobg');
    }
    //显示顶边
    if(options.top == 'center'){
        options.top = (window_height-120)/2;
    }
    //显示顶边
    if(options.left == 'center'){
        options.left = (window_width-120)/2;
    }
    $('#pageLoadTipBox').css({'top':options.top+'px','left':options.left+'px'});
    $('#pageLoadTipShade').css({'height':$(window).height(),'width':$(window).width()}).show();
}
function pageLoadHides(){
    $('#pageLoadTipShade').hide();
}
