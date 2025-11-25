<include file="Public:header"/>

<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-tablet"></i>
                <a href="{pigcms{:U('print_template_list')}">打印模板</a>
            </li>
            <li class="active">打印模板设置</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div>
        <div>
					<span class="page_top">
              <span class="notice">
                  注意：<br/>
								1、仅页眉和页脚支持换行<br/>
								2、选择自定义时，需要填写自定义名称且不能和模板字段名称重复<br/>
								3、所有自定义字段均为空白字头，不支持自动填充<br/>
								4、自定义字段名称最长8位<br/>
								5、系统关键词：标题、编号、打印日期、小区、房号、住户姓名、住户手机号、合计、
								收款日期、收款人、收费项目、收费标准、缴费周期、数量、单价、应收金额、优惠、实收金额、
								备注、收款日期、收款方、收款备注、收款方式、说明、起始日期、终止日期、合计(大写)、缴费日期、换行、房屋面积<br/>
								6、模板标题不选或不填，打印时则不展示标题<br/>
                                7、起度和止度只对 水费、电费、燃气费账单打印有效，且只支持新版收费。
              </span>
          </span>
            <style>
                .ace-file-input a {
                    display: none;
                }

                body {
                    background-color: #fff;
                }

                .border-bd {
                    border: 1px solid #ddd;
                    padding: 3px 5px;
                    margin-right: 5px;
                    margin-bottom: 5px;
                }

                .border-bd i {
                    padding-left: 3px;
                }

                .ant-col-18 {
                    display: block;
                    float:left;
                    -webkit-box-sizing: border-box;
                    box-sizing: border-box;
                    width: 75%;
                }

                .ant-col-6 {
                    float:left;
                    display: block;
                    -webkit-box-sizing: border-box;
                    box-sizing: border-box;
                    width: 25%;
                }
                .border-bd {
                    padding: 4px 10px !important;;
                    margin-right: 10px !important;
                    margin-bottom: 10px !important;
                    cursor:pointer !important;
                }
                #body td{height:30px}
                .ant-card-extra a{color:#40a9ff}
                .ant-card {
                    font-family: Monospaced Number,Chinese Quote,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Hiragino Sans GB,Microsoft YaHei,Helvetica Neue,Helvetica,Arial,sans-serif;
                    font-size: 14px;
                    line-height: 1.5;
                    color: rgba(0,0,0,.65);
                    -webkit-box-sizing: border-box;
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                    list-style: none;
                    background: #fff;
                    border-radius: 2px;
                    position: relative;
                    -webkit-transition: all .3s;
                    transition: all .3s;
                }
                .m-b-lg {
                    margin-bottom: 30px!important;
                }
                .ant-card-head {
                    background: #fff;
                    border-bottom: 1px solid #e8e8e8;
                    padding: 0 24px;
                    border-radius: 2px 2px 0 0;
                    zoom: 1;
                    margin-bottom: -1px;
                    min-height: 48px;
                }
                .ant-card-head:after, .ant-card-head:before {
                    content: " ";
                    display: table;
                }
                .ant-card-head-wrapper {
                    display: -ms-flexbox;
                    display: flex
                }
                .ant-card-head-title {
                    font-size: 16px;
                    padding: 16px 0;
                    text-overflow: ellipsis;
                    overflow: hidden;
                    white-space: nowrap;
                    color: rgba(0,0,0,.85);
                    font-weight: 500;
                    display: inline-block;
                    -ms-flex: 1 1 0%;
                    flex: 1 1 0%;
                }
                .ant-card-extra {
                    float: right;
                    padding: 17.5px 0;
                    text-align: right;
                    margin-left: auto;
                }
                .ant-card-head:after {
                    clear: both;
                    visibility: hidden;
                    font-size: 0;
                    height: 0;
                }
                .ant-card-body {
                    padding: 10px;
                    zoom: 1;
                }
                .ant-card-body:after, .ant-card-body:before {
                    content: " ";
                    display: table;
                }
                .anticon {
                    display: inline-block;
                    font-style: normal;
                    vertical-align: baseline;
                    text-align: center;
                    text-transform: none;
                    line-height: 1;
                    text-rendering: optimizeLegibility;
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                }
                .anticon-close:before, .anticon-cross:before {
                    content: "\E633";
                }

                .anticon:before {
                    display: block;
                    font-family: anticon!important;
                }
                .ant-card-wider-padding .ant-card-head {
                    padding: 0 32px;
                }
                .ant-card-padding-transition .ant-card-body,.ant-card-padding-transition .ant-card-head {
                    -webkit-transition: padding .3s;
                    transition: padding .3s
                }

                .text-success {
                	color: #52c41a
                }

                .text-danger {
                	color: #f5222d
                }

                .readed {
                	color: rgba(0,0,0,.45)
                }

                .form-group-title {
                	padding-left: 16px;
                	font-size: 16px;
                	position: relative
                }

                .form-group-title:after {
                	content: "";
                	width: 5px;
                	height: 24px;
                	background: #47c479;
                	position: absolute;
                	top: 0;
                	left: 0
                }

                @font-face {
                	font-family:anticon;src:url("https://at.alicdn.com/t/font_148784_v4ggb6wrjmkotj4i.eot");src:url("https://at.alicdn.com/t/font_148784_v4ggb6wrjmkotj4i.woff") format("woff"),url("https://at.alicdn.com/t/font_148784_v4ggb6wrjmkotj4i.ttf") format("truetype"),url("https://at.alicdn.com/t/font_148784_v4ggb6wrjmkotj4i.svg#iconfont") format("svg")
                }

                .ant-card-body {
                	padding: 10px;
                	zoom: 1
                }

                .ant-card-body:after,.ant-card-body:before {
                	content: " ";
                	display: table
                }

                .ant-card-body:after {
                	clear: both;
                	visibility: hidden;
                	font-size: 0;
                	height: 0
                }

                .ant-card-contain-grid .ant-card-body {
                	margin: -1px 0 0 -1px;
                	padding: 0
                }

                .ant-card-wider-padding .ant-card-body {
                	padding: 24px 32px
                }

                .ant-card-padding-transition .ant-card-body,.ant-card-padding-transition .ant-card-head {
                	-webkit-transition: padding .3s;
                	transition: padding .3s
                }


                .ant-card-type-inner .ant-card-body {
                	padding: 16px 24px
                }
                .page_top{
                    background-color: #e6f7ff;
                    display: inline-block;
                    width: 100%;
                    padding: 20px 20px;
                    color: #666666;
                }
								.custom_field {
                    width: 230px;
                    margin-left: 2px;
                    margin-top: 5px;
								}
            </style>
            <div style="background:#f5f5f5;height:30px"></div>
            <div class="col-xs-12" style="background:#f5f5f5;padding: 0 20px">
                <div class="ant-col-6" style="padding-right:30px">
                    <div>
                        <div class="ant-card m-b-lg">
                            <div class="ant-card-head">
                                <div class="ant-card-head-wrapper">
                                    <div class="ant-card-head-title">页眉区</div>
                                    <div class="ant-card-extra"><a href="javascript:void(0);" onclick="addCustom(1)">添加一列</a></div>
                                </div>
                            </div>
                                <div id="print_1" class="ant-card-body">
                                    <if condition="$print_template.custom">
                                        <volist name="print_template['custom']" id="vo" key="index">
                                            <if condition="$vo.type eq '1'">

                                                <div  class="fl border-bd" id="left_{pigcms{$vo.configure_id}_{pigcms{$index}" cid="{pigcms{$vo.configure_id}">{pigcms{$vo.title}<i class="anticon anticon-cross" onclick="delCustom(1,'{pigcms{$vo.configure_id}_{pigcms{$index}')"></i></div>
                                            </if>
                                        </volist>
                                    </if>
                                </div>
                        </div>
                        <div class="ant-card m-b-lg">
                            <div class="ant-card-head">
                                <div class="ant-card-head-wrapper">
                                    <div class="ant-card-head-title">表格区</div>
                                    <div class="ant-card-extra"><a href="javascript:void(0);" onclick="addCustom(2)">添加一列</a></div>
                                </div>
                            </div>
                            <div id="print_2" class="ant-card-body">
                                <volist name="print_template['custom']" id="vo" key="index">
                                    <if condition="$vo.type eq '2'">
                                        <div class="fl border-bd" id="left_{pigcms{$vo.configure_id}_{pigcms{$index}" cid="{pigcms{$vo.configure_id}">{pigcms{$vo.title}<i class="anticon anticon-cross" onclick="delCustom(2,'{pigcms{$vo.configure_id}_{pigcms{$index}')"></i></div>
                                    </if>
                                </volist>
                            </div>
                        </div>
                        <div class="ant-card m-b-lg">
                            <div class="ant-card-head">
                                <div class="ant-card-head-wrapper">
                                    <div class="ant-card-head-title">页脚区</div>
                                    <div class="ant-card-extra"><a href="javascript:void(0);" onclick="addCustom(3)">添加一列</a></div>
                                </div>
                            </div>
                                <div id="print_3" class="ant-card-body">
                                    <volist name="print_template['custom']" id="vo" key="index">
                                        <if condition="$vo.type eq '3'">
                                            <div class="fl border-bd" id="left_{pigcms{$vo.configure_id}_{pigcms{$index}" cid="{pigcms{$vo.configure_id}">{pigcms{$vo.title}<i class="anticon anticon-cross" onclick="delCustom(3,'{pigcms{$vo.configure_id}_{pigcms{$index}')"></i></div>
                                        </if>
                                    </volist>
                                </div>
                        </div>
                    </div>
                </div>
                <div class="ant-col-18" style=" padding-right: 12px;">
                    <div class="ant-card print_right___1E8QP ant-card-wider-padding ant-card-padding-transition">
                        <div class="ant-card-head">
                            <div class="ant-card-head-wrapper">
                                <div class="ant-card-head-title">【{pigcms{$print_template.title}】 预览区</div>
                                <div class="">
                                    <div>
                                        <div class="" style="padding-top:6px">
                                            <if condition="in_array(87,$house_session['menus']) || in_array(88,$house_session['menus'])">
                                            <button class="btn btn-info" onclick="submit()">
                                                <i class="ace-icon fa fa-check bigger-110"></i>
                                                保存
                                            </button> &nbsp;&nbsp;
                                            <else/>
                                            <button class="btn btn-info" type="submit" disabled="disabled">
                                                <i class="ace-icon fa fa-check bigger-110"></i>
                                                保存
                                            </button> &nbsp;&nbsp;
                                            </if>
                                            <a href="{pigcms{:U('print_template_add',array('template_id'=>$vo['template_id']))}" class="btn">
                                                返回
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ant-card-body">
                            <div class="tpl_paper___1PaZS tpl-item" style="width: 725px;">
                                <div style="margin: 20px;">
                                    <div class="tpl_hd">
                                        <div id="div_1" style="margin-bottom:10px">
                                            <if condition="$print_template.custom">
                                                <volist name="print_template['custom']" id="vo" key="index">
                                                    <if condition="$vo.type eq '1'">
                                                        <if condition="$vo.title eq '标题'">
                                                            <div id="right_{pigcms{$vo.configure_id}_{pigcms{$index}" style="width: 100%;float: left;text-align: center;font-size: 23px;"><strong>{pigcms{$print_template.top_title}</strong></div>
																													<elseif condition="$vo.title eq '换行'" />
																													<div id="right_{pigcms{$vo.configure_id}_{pigcms{$index}" style="width: 100%;float: left;"></div>
                                                            <else/>
                                                            <div id="right_{pigcms{$vo.configure_id}_{pigcms{$index}" style="width: {pigcms{$with};float: left;">
																															<strong>{pigcms{$vo.title}:</strong>
																															<if condition="$vo.id neq -1">
																															【{pigcms{$vo.title}】
																															</if>
																														</div>
                                                        </if>
                                                    </if>
                                                </volist>
                                            </if>
                                            <div style="clear:both"></div>
                                        </div>
                                    </div>
                                    <div class="tpl_bd" style="margin: 8px 0px -10px;">
                                        <div id="div_2">
                                            <table class="table  table-bordered" width="100%">
                                                <tbody id="head">
                                                <tr>
                                                    <volist name="print_template['custom']" id="vo" key="index">
                                                        <if condition="$vo.type eq '2'">
                                                            <th id="right_{pigcms{$vo.configure_id}_{pigcms{$index}" style="text-align: center;">{pigcms{$vo.title}</th>
                                                        </if>
                                                    </volist>
                                                </tr>
                                                </tbody>
                                                <tbody id="body">
                                                <tr>
                                                    <volist name="print_template['custom']" id="vo" key="index">
                                                        <if condition="$vo.type eq '2'">
                                                            <td class="tdright_{pigcms{$vo.configure_id}_{pigcms{$index}"></td>
                                                        </if>
                                                    </volist>
                                                </tr>
                                                <tr>
                                                    <volist name="print_template['custom']" id="vo" key="index">
                                                        <if condition="$vo.type eq '2'">
                                                            <td class="tdright_{pigcms{$vo.configure_id}_{pigcms{$index}"></td>
                                                        </if>
                                                    </volist>
                                                </tr>
                                                <tr>
                                                    <volist name="print_template['custom']" id="vo" key="index">
                                                        <if condition="$vo.type eq '2'">
                                                            <td class="tdright_{pigcms{$vo.configure_id}_{pigcms{$index}"></td>
                                                        </if>
                                                    </volist>
                                                </tr>
                                                <tr>
                                                    <volist name="print_template['custom']" id="vo" key="index">
                                                        <if condition="$vo.type eq '2'">
                                                            <td class="tdright_{pigcms{$vo.configure_id}_{pigcms{$index}"></td>
                                                        </if>
                                                    </volist>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tpl_ft">
                                        <div class="tpl_row" style="display: flex; flex-wrap: wrap; width: 100%; position: relative; height: auto;">
                                            <div id="div_3" style='overflow:hidden;width: 100%'>
                                                <if condition="$print_template.custom">
                                                    <volist name="print_template['custom']" id="vo" key="index">
                                                        <if condition="$vo.type eq '3'">
                                                            <if condition="$vo.title eq '说明'">
                                                                <div id="right_{pigcms{$vo.configure_id}_{pigcms{$index}" style="width: 30%;float: left;"><strong>{pigcms{$vo.title}:</strong>【{pigcms{$print_template.desc}】</div>
                                                            <elseif condition="$vo.title eq '换行'" />
																															<div id="right_{pigcms{$vo.configure_id}_{pigcms{$index}" style="width: 100%;float: left;"></div>
                                                            <else/>
                                                                <div id="right_{pigcms{$vo.configure_id}_{pigcms{$index}" style="width: {pigcms{$with};float: left;">
																																	<strong>{pigcms{$vo.title}:</strong>
																																	<if condition="$vo.id neq -1">
																																		【{pigcms{$vo.title}】
																																	</if>
																																</div>
                                                            </if>
                                                        </if>
                                                    </volist>
                                                </if>
                                            </div>
                                            <div class='clear:both'></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div id="custom_1" style="display: none;">
        <table class="" border="0">
            <tbody>
            <tr>
                <th style="font-size: 15px">显示内容：</th>
                <td>
                    <select name="custom_select_1" id="custom_select_1" style="width: 230px;margin-left:2px; " onchange="customField(1,this.value)">
                        <option value="0" selected>请选择显示内容</option>
                        <volist name="print_custom" id="vo">
                            <if condition="$vo.type eq 1">
                                <option value="{pigcms{$vo.configure_id}">{pigcms{$vo.title}</option>
                            </if>
                        </volist>
											<option value="-1">自定义</option>
                    </select>
                </td>
            </tr>
						<tr class="custom_field_1">
							<th style="font-size: 15px"></th>
							<td>
								<input type="text" name="custom_field_1" class="custom_field" id="custom_field_1" />
							</td>
						</tr>
            </tbody>
        </table>
    </div>
    <div id="custom_2" style="display: none;">
        <table class="">
            <tbody>
            <tr>
                <th style="font-size: 15px">显示内容：</th>
                <td>
                    <select name="custom_select_2" id="custom_select_2" style="width: 230px;margin-left:2px; ">
                        <option value="0">请选择显示内容</option>
                        <volist name="print_custom" id="vo">
                            <if condition="$vo.type eq 2">
                                <option value="{pigcms{$vo.configure_id}">{pigcms{$vo.title}</option>
                            </if>
                        </volist>
                    </select>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div id="custom_3" style="display: none;">
        <table class="">
            <tbody>
            <tr>
                <th style="font-size: 15px">显示内容：</th>
                <td>
                    <select name="custom_select_3" id="custom_select_3" style="width: 230px;margin-left:2px; " onchange="customField(3,this.value)">
                        <option value="0" selected>请选择显示内容</option>
                        <volist name="print_custom" id="vo">
                            <if condition="$vo.type eq 3">
                                <option value="{pigcms{$vo.configure_id}">{pigcms{$vo.title}</option>
                            </if>
                        </volist>
											<option value="-1">自定义</option>
                    </select>
                </td>
            </tr>
						<tr class="custom_field_3">
							<th style="font-size: 15px"></th>
							<td>
								<input type="text" name="custom_field_3" class="custom_field" id="custom_field_3" />
							</td>
						</tr>
            </tbody>
        </table>
    </div>

</div>
    <script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
    <script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
    <script type="text/javascript">
        function submit() {
            var ids = [];
            $('#print_1').find('div').each(function () {
              var param = {
                id: $(this).attr('cid'),
                title: $(this).text(),
                print_type: 1
              }
                ids.push(param);
            });
            $('#print_2').find('div').each(function () {
              var param = {
                id: $(this).attr('cid'),
                title: $(this).text(),
                print_type: 2
              }
              ids.push(param);
            });
            $('#print_3').find('div').each(function () {
              var param = {
                id: $(this).attr('cid'),
                title: $(this).text(),
                print_type: 3
              }
              ids.push(param);
            });
            $.post("{pigcms{:U('save_custom',array('template_id'=>$template_id))}",{ids: ids},function (data) {
                if (data.status == 0) {
                    layer.msg('保存成功');
                    setTimeout(function () {
                        window.location.href = "{pigcms{:U('print_template_list')}";
                    },1000)
                } else {
                  layer.msg(data.msg,{icon: 2});
									return false;
                }
            },'json')

        }
        $("#print_1 div").on({
            mousedown: function(e){
                        var el=$(this);
                        var os = el.offset(); dx = e.pageX-os.left, dy = e.pageY-os.top;
                        $(document).on('mousemove.drag', function(e){ el.offset({top: e.pageY-dy, left: e.pageX-dx}); });
                    },
           mouseup: function(e){ $(document).off('mousemove.drag'); }
       })

        var index = '{pigcms{$lastIndex}';
        var customFieldStr = "{pigcms{$templateKeyWord}";
        var customFieldArr = customFieldStr.split(',');
        var col_with = '{pigcms{$with}';

        function addCustom(type) {
          let dom_id = '.custom_field_'+type
          $(dom_id).hide(); // 隐藏自定义输入框
          $("#custom_field_"+type).val('');
					$("#custom_select_"+type+" option[value='0']").attr('selected',true)
            var title;
            if (type == 1) {
                title = '设置页眉';
            }
            else if (type == 2) {
                title = '设置表格';
            }
            else if (type == 3) {
                title = '设置页脚';
            }
            art.dialog({
                content: document.getElementById('custom_' + type),
                id: 'handle',
                title: title,
                padding: 0,
                width: 380,
                height: 200,
                lock: true,
                resize: false,
                background: 'black',
                fixed: false,
                okVal: '确定',
                cancelVal: '取消',
                left: '50%',
                top: '38.2%',
                opacity: '0.4',
                ok: function (argument) {
                    //按钮【按钮一】的回调
                    var custom_selected = $('#custom_select_' + type).val();
                    // 自定义字段
                    if(custom_selected == -1){
                      let dom_id = '#custom_field_'+type
                      var custom_selected_name = $(dom_id).val();
                      if(custom_selected_name.length < 1){
                        layer.msg('请填写自定义名称',{icon: 2});
                        return false;
											}
                      if(custom_selected_name.length > 8){
                        layer.msg('自定义名称最大长度为8位',{icon: 2});
                        return false;
                      }
                      let key = keyWord(custom_selected_name,type); // 过滤特殊字段
                      let res = selectCustomField(type,custom_selected_name);
                      if(!res || !key){
                        return false
											}
                      customFieldArr.push(custom_selected_name);
                      custom_selected = type+'_'+index
                    }else{
                      var custom_selected_name = $('#custom_select_' + type + ' option:selected').text();
										}
                    let reg = /^[0-9]*$/
                    if (custom_selected != 0) {
                        var leftHtml = '';
                        leftHtml = '<div class="fl border-bd" id="left_' + index + '" cid="' + custom_selected + '">' + custom_selected_name + '<i class="anticon anticon-cross" onclick="delCustom(' + type + ',' + index + ')"></i></div>';
                        if(custom_selected_name === '标题'){
                          $('#print_' + type).prepend(leftHtml);
												}else{
                          $('#print_' + type).append(leftHtml);
												}
                        var rightHtml = '';
                        if (type == 1) {
                            if (custom_selected_name == '标题') {
                                rightHtml = '<div id="right_' + index + '" style="width: 100%;float: left;text-align: center;font-size: 23px;margin-bottom: 4px;"><strong>{pigcms{$print_template.top_title}</strong></div>';
                                $('#div_' + type).prepend(rightHtml);
                            }else if (custom_selected_name == '换行') {
                              rightHtml = '<div id="right_' + index + '" style="width: 100%;float: left;"></div>';
                              $('#div_' + type).append(rightHtml);
														} else {
                                rightHtml = '<div id="right_' + index + '" style="width: '+ col_with +';float: left;margin-bottom: 2px;"><strong>' + custom_selected_name + ':</strong>'+ (reg.test(custom_selected) ? '【' + custom_selected_name + '】' : '') +'</div>';
                                $('#div_' + type).append(rightHtml);
                            }
                        } else if (type == 2) {
                            $('#div_' + type).find('table').find('#head').find('tr').append('<th id="right_' + index + '" style="text-align: center;">' + custom_selected_name + '</th>');
                            $('#div_' + type).find('table').find('#body').find('tr').append('<td class="tdright_' + index + '"></td>');
                        } else if (type == 3) {
                            if (custom_selected_name == '说明') {
                                rightHtml = '<div id="right_' + index + '" style="width: '+ col_with +';float: left;"><strong>' + custom_selected_name + ':</strong>【{pigcms{$print_template.desc}】</div>';
                            }else if (custom_selected_name == '换行') {
                              rightHtml = '<div id="right_' + index + '" style="width: 100%;float: left;"></div>';
                            } else {
                                rightHtml = '<div id="right_' + index + '" style="width: '+ col_with +';float: left;"><strong>' + custom_selected_name + ':</strong>'+ (reg.test(custom_selected) ? '【' + custom_selected_name + '】' : '') +'</div>';
                            }
                            $('#div_' + type).append(rightHtml);
                        }
                        index++;
                    }
                }
            });

        }

        function delCustom(type,i) {
          if(customFieldArr.indexOf($('#left_' + i).text()) > -1){
            customFieldArr.splice(customFieldArr.indexOf($('#left_' + i).text()),1);
          }
            if (type == 1) {
                $('#left_' + i).remove();
                $('#right_' + i).remove();
            } else if (type == 2) {
                $('#left_' + i).remove();
                $('#right_' + i).remove();
                $('.tdright_' + i).remove();
            } else if (type == 3) {
                $('#left_' + i).remove();
                $('#right_' + i).remove();
            }
        }

        function customField(type,value){
					let dom_id = '.custom_field_'+type
					// 自定义字段
          if(value == -1){
            $(dom_id).show();
					}else{
            $(dom_id).hide();
					}
				}

				function selectCustomField(type,value){
          if(customFieldArr.indexOf(value) > -1){
            layer.msg('该名字是系统关键词或与已有的名称重复，请更换',{icon: 2});
            return false;
					}
					return true
				}

				function keyWord(str,type){
          if(str.indexOf('<script') != -1 || str.indexOf('script>') != -1 || str.indexOf('<') != -1  || str.indexOf('>') != -1){
            $("#custom_field_"+type).val('');
            layer.msg('该名字中含有危险字段，请重新填写',{icon: 2});
            return false;
          }
          return true
				}
    </script>

    <include file="Public:footer"/>