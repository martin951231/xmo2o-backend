<include file="Public:header"/>
		<link rel="stylesheet" href="./tpl/System/Static/css/select-multiple.css">
		<div class="mainbox">
			<if condition="empty($_GET['galias'])">
				<div id="nav" class="mainnav_title">
					<ul>
						<volist name="group_list" id="vo">
							<a href="{pigcms{:U('Config/index',array('gid'=>$vo['gid']))}" <if condition="$gid eq $vo['gid']">class="on"</if>>{pigcms{$vo.gname}</a>|
						</volist>
					</ul>
				</div>
			<else/>
				<if condition="$header_file">
					<include file="$header_file"/>
				</if>
			</if>
			
			<style>.page_tips_li_hide{display:none;}</style>
			<if condition="$_SESSION['level'] eq 2">
				<div class="page_tips" id="page_tips_div" style="padding-left:30px;">
					<ol style="list-style:decimal;">
						<if condition="$gid eq 7">
							<li class="page_tips_li_hide tab_weixin">微信支付 对接文档&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=5" target="_blank">【点击查看】</a></li>
							<li class="page_tips_li_hide tab_weixinh5">微信支付 对接文档&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=5" target="_blank">【点击查看】</a></li>
							<li class="page_tips_li_hide tab_alipayh5">支付宝H5 对接文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=15" target="_blank">【点击查看】</a></li>
						<elseif condition="$gid eq 43"/>
							<li class="page_tips_li_hide tab_alipayh5">支付宝H5 对接文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=15" target="_blank">【点击查看】</a></li>
						<elseif condition="$gid eq 8"/>
							<li>微信公众号 对接文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=3" target="_blank">【点击查看】</a></li>
						<elseif condition="$gid eq 22"/>
							<li>小区模块 使用文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=32" target="_blank">【点击查看】</a></li>
							<li>车牌识别配置 对接文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=34" target="_blank">【点击查看】</a></li>
							<li>A1人脸识别 使用文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=35" target="_blank">【点击查看】</a></li>
							<li>物业公众号配置 对接文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=17" target="_blank">【点击查看】</a></li>
						<elseif condition="$gid eq 34"/>
							<li class="page_tips_li_hide tab_alipayh5">支付宝H5 对接文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=15" target="_blank">【点击查看】</a></li>
						<elseif condition="$gid eq 20"/>
							<li>多个配送时间段时间不能重叠设置，例如 时间段一为8:00-10:00，时间段二为6:00-22:00 否则取最后一个时间段内的数据</li>
						</if>
						<li class="page_tips_li_hide tab_imageslim">图片无损压缩 使用文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=53" target="_blank">【点击查看】</a></li>
					</ol>
				</div>
			</if>
				
			<form id="myform" method="post" action="{pigcms{:U('Config/amend')}" refresh="true">
				{pigcms{$config_tab_html}
				{pigcms{$config_html}
				<input type='hidden' name='village_group_threshold_warning_member_value' value='' id='village_group_threshold_warning_member_value'>
				<div class="btn dblDom" style="margin-top:20px;">
					<input id="submitClick" type="button" name="dosubmit" value="提交" class="button" />

					<input type="button" value="获取及时聊天的key" class="button dblShow" id="im_key" style="display:<if condition="empty($config['im_appid'])">inline<else/>none</if>;"/>
					<input type="button" value="整站换HTTPS" class="button dblShow" id="change_https" style="display:none;"/>
					<input type="button" value="测试公众号可用" class="button dblShow" id="get_wechat_code" style="display:none;"/>
					<input type="button" value="百度地图对接检测" class="button dblShow" id="check_baidu_map" style="display:none;"/>
					<input type="button" value="修复PC站扫码问题" class="button dblShow" id="repair_pc_qrcode" style="display:none;"/>
					<input type="button" value="修复店铺营业时间" class="button dblShow" id="repair_store_time" style="display:none;"/>
					<if condition="$gid eq 8">
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="微信API接口填写信息" class="button" onclick="window.o2oTop.artiframe('{pigcms{:U('Config/show',array('id'=>$vo['id']))}','API接口信息',560,100,true,false,false,'','add',true);"/>
					</if>
				</div>
			</form>
		</div>
		<script>
			$(function(){
				var jq_v=jQuery.fn.jquery;
                console.log('jq_v=>', jq_v);

				if($('#page_tips_div li').length == $('#page_tips_div li.page_tips_li_hide').length){
					$('#page_tips_div').hide();
				}
				$('.tab_ul li a').click(function(){
					$(this).closest('li').addClass('active').siblings('li').removeClass('active');
					$($(this).attr('href')).show().siblings('.table_form').hide();
					
					$('.page_tips_li_hide').hide();
					if($('.page_tips_li_hide .' + $(this).attr('href').replace('#', '')).length > 0){
						$('#page_tips_div').show();
						$('.page_tips_li_hide .' + $(this).attr('href').replace('#', '')).show();
					}else{
						if($('#page_tips_div li').length == $('#page_tips_div li.page_tips_li_hide').length){
							$('#page_tips_div').hide();
						}
					}
					
					return false;
				});
				if($('.tab_ul li a').length > 0){
					$('.tab_ul li a:eq(0)').click();
				}else{
					$('.table_form:eq(0)').show();
				}
				
				$('#im_key').click(function(){
					window.o2oTop.msg(2,'正在请求中,请稍等...',true,100);
					$.get("{pigcms{:U('Config/im')}",function(data){
						if(data.error_code){
							window.o2oTop.msg(0,data.msg,true,3);
						}else{
							window.o2oTop.msg(1,data.msg,true,3);
						}
					},'json');
				});
				$('#change_https').click(function(){
					window.o2oTop.msg(2,'正在请求中,请稍等...',true,100);
					$.get("{pigcms{:U('Config/change_https')}",function(data){
						if(data.error_code){
							window.o2oTop.msg(0,data.msg,true,3);
						}else{
							window.o2oTop.msg(1,data.msg,true,5);
						}
					},'json');
				});
				$('#check_baidu_map').click(function(){
					window.o2oTop.msg(2,'正在请求中,请稍等...',true,100);
					$.get("{pigcms{:U('Config/check_baidu_map')}",function(data){
						if(data.error_code){
							window.o2oTop.msg(0,data.msg,true,3);
						}else{
							window.o2oTop.msg(1,data.msg,true,3);
						}
					},'json');
				});
				$('#live_service_key').click(function(){
					window.o2oTop.msg(2,'正在请求中,请稍等...',true,100);
					$.get("{pigcms{:U('Config/live_service')}",function(data){
						if(data.error_code){
							window.o2oTop.msg(0,data.msg,true,3);
						}else{
							window.o2oTop.msg(1,data.msg,true,3);
						}
					},'json');
				});
				$('#get_wechat_code').click(function(){
					window.o2oTop.msg(2,'正在请求中,请稍等...',true,100);
					$.get("{pigcms{:U('Config/get_wechat_code')}",function(data){
						if(data.error_code){
							window.o2oTop.msg(0,data.msg,true,10);
						}else{
							window.o2oTop.msg(1,data.msg,true,6);
						}
					},'json');
				});
				$('#repair_pc_qrcode').click(function(){
					window.o2oTop.msg(2,'正在请求中,请稍等...',true,100);
					$.get("{pigcms{:U('Config/repair_pc_qrcode')}",function(data){
						if(data.error_code){
							window.o2oTop.msg(0,data.msg,true,3);
						}else{
							window.o2oTop.msg(1,data.msg,true,3);
						}
					},'json');
				});

				$('#repair_store_time').click(function(){
					window.o2oTop.msg(2,'正在请求中,请稍等...',true,100);
					$.get("{pigcms{:U('Config/repair_store_time')}",function(data){
						if(data.error_code){
							window.o2oTop.msg(0,data.msg,true,3);
						}else{
							window.o2oTop.msg(1,data.msg,true,3);
						}
					},'json');
				});
				
				$('.dblDom').dblclick(function(){
                	$(this).find('.dblShow').show();
                });


				// 处理 添加 多个数据
                var count_text_arr = "{pigcms{$count_text_arr}";
                // info_service_buy_weight_info
                $('.plus_recharge').click(function(){
                    var html_div = '';
                    ++count_text_arr;
                    var name = $(this).data('name');

                    html_div += '<p class="config_text_arr_'+count_text_arr+'"><span>';
                    html_div += '<input type="text" oninput = "value=value.replace(/[^\\d]/g,'+"''"+')" class="input-text" name="'+name+'['+count_text_arr+'][' + "start_weight" + ']" value="" size="5"> 公斤至 ';
                    html_div += '<input type="text" oninput = "value=value.replace(/[^\\d]/g,'+"''"+')" class="input-text" name="'+name+'['+count_text_arr+'][' + "end_weight" + ']" value="" size="5"> 公斤，费用为 ';
                    html_div += '<input type="text" oninput = "value=value.replace(/[^\\d]/g,'+"''"+')" class="input-text" name="'+name+'['+count_text_arr+'][' + "weight_price" + ']" value="" size="5"> {pigcms{$config['Currency_txt']}';
                    html_div += '<button data-item="'+count_text_arr+'" data-name="'+name+'" type="button" class="reduce_recharge"></button>';
                    html_div += '</span></p>';

                    $('.info_'+ name).children().last().append(html_div);
                    // 删除
                    $(".reduce_recharge").click(function() {
                        var item = $(this).data('item');
                        $(this).parents().parent('.config_text_arr_' + item).remove();
                    });

                });
                // 删除
                $(".reduce_recharge").click(function() {
                    var item = $(this).data('item');
                    $(this).parents().parent('.config_text_arr_' + item).remove();
                });
                // 监听 帮我买多档重量开关 值 的 切换
                $("input[name='service_weight_multilevel_switch']").click(function () {
                    console.log('service_weight_multilevel_switch-----', $(this).val());
                    var val = $(this).val();
                    if (val == 1) {
                        // 开启了多档重量， 隐藏帮我送重量信息 开启帮我买帮我送 多档重量
                        $('.title_service_basic_weight').css('display', 'none');
                        $('.title_service_basic_weight_price').css('display', 'none');
                        $('.title_service_bounds_weight').css('display', 'none');
                        $('.info_service_basic_weight').css('display', 'none');
                        $('.info_service_basic_weight_price').css('display', 'none');
                        $('.info_service_bounds_weight').css('display', 'none');
                        $('.title_service_buy_weight_info').css('display', 'table-cell');
                        $('.info_service_buy_weight_info').css('display', 'table-cell');
                        $('.title_service_give_weight_info').css('display', 'table-cell');
                        $('.info_service_give_weight_info').css('display', 'table-cell');
                    } else {
                        // 关闭了多档重量， 显示帮我送重量信息 隐藏帮我买帮我送 多档重量
                        $('.title_service_basic_weight').css('display', 'table-cell');
                        $('.title_service_basic_weight_price').css('display', 'table-cell');
                        $('.title_service_bounds_weight').css('display', 'table-cell');
                        $('.info_service_basic_weight').css('display', 'table-cell');
                        $('.info_service_basic_weight_price').css('display', 'table-cell');
                        $('.info_service_bounds_weight').css('display', 'table-cell');
                        $('.title_service_buy_weight_info').css('display', 'none');
                        $('.info_service_buy_weight_info').css('display', 'none');
                        $('.title_service_give_weight_info').css('display', 'none');
                        $('.info_service_give_weight_info').css('display', 'none');
                    }
                });

                // 监听 帮我买多档时间段配送配置 值 的 切换
                $("input[name='service_buy_deliver_times_switch']").click(function () {
                    console.log('service_buy_deliver_times_switch-----', $(this).val());
                    var val = $(this).val();
                    if (val == 1) {
                        // 开启了多档时间段配送配置， 隐藏帮我买旧配置项
                        $('.title_service_basic_km').css('display', 'none');
                        $('.info_service_basic_km').css('display', 'none');

                        $('.title_service_basic_km_time').css('display', 'none');
                        $('.info_service_basic_km_time').css('display', 'none');

                        $('.title_service_give_basic_distance').css('display', 'none');
                        $('.info_service_give_basic_distance').css('display', 'none');

                        $('.title_service_give_delivery_fee').css('display', 'none');
                        $('.info_service_give_delivery_fee').css('display', 'none');

                        $('.title_service_give_per_km_price').css('display', 'none');
                        $('.info_service_give_per_km_price').css('display', 'none');


                        $('.title_service_buy1').css('display', 'table-cell');
                        $('.info_service_buy1').css('display', 'table-cell');

                        $('.title_service_buy2').css('display', 'table-cell');
                        $('.info_service_buy2').css('display', 'table-cell');

                        $('.title_service_buy_delivery_fee2').css('display', 'table-cell');
                        $('.info_service_buy_delivery_fee2').css('display', 'table-cell');

                        $('.title_service_buy_basic_distance2').css('display', 'table-cell');
                        $('.info_service_buy_basic_distance2').css('display', 'table-cell');

                        $('.title_service_buy_per_km_price2').css('display', 'table-cell');
                        $('.info_service_buy_per_km_price2').css('display', 'table-cell');

                        $('.title_service_buy_delivery_time').css('display', 'table-cell');
                        $('.info_service_buy_delivery_time').css('display', 'table-cell');

                        $('.title_service_buy_delivery_time2').css('display', 'table-cell');
                        $('.info_service_buy_delivery_time2').css('display', 'table-cell');

                        $('.title_service_buy_delivery_fee').css('display', 'table-cell');
                        $('.info_service_buy_delivery_fee').css('display', 'table-cell');

                        $('.title_service_buy_basic_distance').css('display', 'table-cell');
                        $('.info_service_buy_basic_distance').css('display', 'table-cell');

                        $('.title_service_buy_per_km_price').css('display', 'table-cell');
                        $('.info_service_buy_per_km_price').css('display', 'table-cell');
                    } else {
                        // 关闭了多档时间段配送配置， 开启帮我买旧配置项
                        $('.title_service_basic_km').css('display', 'table-cell');
                        $('.info_service_basic_km').css('display', 'table-cell');

                        $('.title_service_basic_km_time').css('display', 'table-cell');
                        $('.info_service_basic_km_time').css('display', 'table-cell');

                        $('.title_service_give_basic_distance').css('display', 'table-cell');
                        $('.info_service_give_basic_distance').css('display', 'table-cell');

                        $('.title_service_give_delivery_fee').css('display', 'table-cell');
                        $('.info_service_give_delivery_fee').css('display', 'table-cell');

                        $('.title_service_give_per_km_price').css('display', 'table-cell');
                        $('.info_service_give_per_km_price').css('display', 'table-cell');


                        $('.title_service_buy1').css('display', 'none');
                        $('.info_service_buy1').css('display', 'none');

                        $('.title_service_buy2').css('display', 'none');
                        $('.info_service_buy2').css('display', 'none');

                        $('.title_service_buy_delivery_fee2').css('display', 'none');
                        $('.info_service_buy_delivery_fee2').css('display', 'none');

                        $('.title_service_buy_basic_distance2').css('display', 'none');
                        $('.info_service_buy_basic_distance2').css('display', 'none');

                        $('.title_service_buy_per_km_price2').css('display', 'none');
                        $('.info_service_buy_per_km_price2').css('display', 'none');

                        $('.title_service_buy_delivery_time').css('display', 'none');
                        $('.info_service_buy_delivery_time').css('display', 'none');

                        $('.title_service_buy_delivery_time2').css('display', 'none');
                        $('.info_service_buy_delivery_time2').css('display', 'none');

                        $('.title_service_buy_delivery_fee').css('display', 'none');
                        $('.info_service_buy_delivery_fee').css('display', 'none');

                        $('.title_service_buy_basic_distance').css('display', 'none');
                        $('.info_service_buy_basic_distance').css('display', 'none');

                        $('.title_service_buy_per_km_price').css('display', 'none');
                        $('.info_service_buy_per_km_price').css('display', 'none');
                    }
                });

                // 监听 定制-从业者推荐店铺获取积分开关 值 的 切换
                $("input[name='employee_recommend_store_get_score']").click(function () {
                    console.log('employee_recommend_store_get_score-----', $(this).val());
                    var val = $(this).val();
                    if (val == 1) {
                        // 开启了获取积分开关， 显示获取积分制配置
                        $('.title_employee_recommend_score').css('display', 'table-cell');
                        $('.info_employee_recommend_score').css('display', 'table-cell');
                    } else {
                        // 关闭了获取积分开关， 隐藏获取积分制配置
                        $('.title_employee_recommend_score').css('display', 'none');
                        $('.info_employee_recommend_score').css('display', 'none');
                    }
                });
                var val = "{pigcms{$config['employee_recommend_store_get_score']}";
                if (val == 1) {
                    // 开启了获取积分开关， 显示获取积分制配置
                    $('.title_employee_recommend_score').css('display', 'table-cell');
                    $('.info_employee_recommend_score').css('display', 'table-cell');
                } else {
                    // 关闭了获取积分开关， 隐藏获取积分制配置
                    $('.title_employee_recommend_score').css('display', 'none');
                    $('.info_employee_recommend_score').css('display', 'none');
                }
                /*增加切换签到模式时的弹窗提示*/
                var open_custom_sign = "{pigcms{$config['open_custom_sign']}";
                $(document).on('click','input[name="open_custom_sign"]',function(){
                    var val=$(this).val();
                    if(val!=open_custom_sign){
                        layer.alert('切换签到模式后，未签到的用户当前的签到记录将从第一天开始获取奖励！',{
                            offset: (window.parent.parent.innerHeight / 3) + 'px',
                            icon:5,
                            title:'提示'
                        });
                    }
                });
			});
			$('#submitClick').click(function(e){
				let village_group_threshold_warning_member = $('#village_group_threshold_warning_member').val()
				let village_group_threshold_warning_member_value = village_group_threshold_warning_member && village_group_threshold_warning_member.length>0?village_group_threshold_warning_member.join(","):''
				$("#village_group_threshold_warning_member_value").attr('value',village_group_threshold_warning_member_value)
				$('#village_group_threshold_warning_member').val('')
				document.getElementById("myform").submit();
			})
		</script>
		<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
		<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
		<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
		<script src="./tpl/System/Static/js/select-multiple.js?t=2"></script>
		<script type="text/javascript">
			$(document).ready(function () {
				let params = $("#village_group_threshold_warning_member").attr("data-data")?$("#village_group_threshold_warning_member").attr("data-data").split(','): []
				if(params && params.length>0){
					$("#village_group_threshold_warning_member").val(params)
				}
				// 多选框配置
				$("#village_group_threshold_warning_member").fSelect({
					placeholder: "请选择通知管理员",//占位符，默认显示什么
					showSearch: false//是否显示搜索框
				});
			})
			KindEditor.ready(function(K){
				var site_url = "{pigcms{$config.site_url}";
				var editor = K.editor({
					allowFileManager : true
				});
				$('.config_upload_image_btn').click(function(){
					var upload_file_btn = $(this);
					editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
					editor.loadPlugin('image', function(){
						editor.plugin.imageDialog({
							showRemote : false,
							clickFn : function(url, title, width, height, border, align) {
								upload_file_btn.siblings('.input-image').val(site_url+url);
								editor.hideDialog();
							}
						});
					});
					setTimeout(function(){
						$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
					},200);
				});
				$('.config_upload_file_btn').click(function(){
					var upload_file_btn = $(this);
					editor.uploadJson = "{pigcms{:U('Config/ajax_upload_file')}&name="+upload_file_btn.siblings('.input-file').attr('name');
					editor.loadPlugin('insertfile', function(){
						editor.plugin.fileDialog({
							showRemote : false,
							clickFn : function(url, title, width, height, border, align) {
								upload_file_btn.siblings('.input-file').val(url);
								editor.hideDialog();
							}
						});
					});
					setTimeout(function(){
						$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
					},200);
				});

				window.editor = K.create('#config_register_agreement',{pasteType : 1,uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant/news"});
				window.editor = K.create('#config_c2b_user_check_text',{pasteType : 1,uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant/news"});
				window.editor = K.create('#config_store_register_agreement',{pasteType : 1,uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant/news"});
				window.editor = K.create('#config_village_group_rule',{pasteType : 1,resizeType : 1,width:"50%",height:"300px",uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant/news"});
                window.editor = K.create('#config_activity_offline_agreement',{pasteType : 1,resizeType : 1,width:"60%",height:"400px",uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=activity_offline"});
                window.editor = K.create('#config_recruit_agreement',{pasteType : 1,resizeType : 1,width:"60%",height:"400px",uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=recruit"});
                window.editor = K.create('#config_merchant_recharge_agreement',{pasteType : 1,resizeType : 1,width:"60%",height:"400px",uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant_recharge"});
			});

            $(document).on('click', '.show-select-value', function(){
                if ($(this).find('.list').attr('class').indexOf('active') != -1) {
                    $(this).find('.list').removeClass('active');
                    return
                }
                $(this).find('.list').addClass('active').css('bottom', -$(this).find('.list').height()-2)
            })

            $(document).on('click', '.show-select-value .list .item', function(){
                var label_id = $(this).data('id');

                var label_ids = $(this).closest('.show-select-value').find('.hidden_multi_select').val();
                if (label_ids != '') {
                    var label_ids_arr = label_ids.split(',');
                    var selected = 0;
                    $.each(label_ids_arr,function (index,j) {
                        if(label_id == j){
                            selected = 1;
                        }
                    })
                    if (selected==0) {
                        label_ids_arr.push(label_id);
                        label_ids = label_ids_arr.join(',');
                        $(this).closest('.show-select-value').find('.hidden_multi_select').val(label_ids);
                    }else{
                        $(this).closest('.show-select-value').find('.list').removeClass('active');
                        return false;
                    }
                }else{
                    $(this).closest('.show-select-value').find('.hidden_multi_select').val(label_id);
                }
                $(this).closest('.show-select-value').find('.select-list').append('<div class="item" data-id="'+label_id+'" >'+ $(this).html() +'<span class="icon">×</span></div>');
                $(this).closest('.show-select-value').find('.list').removeClass('active');
				return false
            })

            $(document).on('click', '.show-select-value .select-list .item .icon', function(){
                var label_id = $(this).parent().attr("data-id");
                var label_ids = $(this).closest('.show-select-value').find('.hidden_multi_select').val();
                var label_ids_arr = label_ids.split(',');

                label_ids_arr.splice($.inArray(label_id,label_ids_arr),1);
                label_ids = label_ids_arr.join(',');
                $(this).closest('.show-select-value').find('.hidden_multi_select').val(label_ids);
                $(this).parents('.item').remove()
                return false
            })
		</script>
		<style>
			.dblDom{
				margin-bottom: 80px !important;
			}
			.ke-container{
				width: 700px !important;
			}
            .ke-dialog-default{
                top:200px !important;
            }
            .plus_recharge {
                background: url("{pigcms{$static_path}css/img/plus_nc.png?t=003") no-repeat;
                width: 16px;
                height: 16px;
                border: none;
                position: absolute;
                margin-top: 2px;
                margin-left: 5px;
                cursor: pointer;
            }
            .reduce_recharge {
                background: url("{pigcms{$static_path}css/img/reduce_nc.png?t=003") no-repeat;
                width: 16px;
                height: 16px;
                border: none;
                position: absolute;
                margin-top: 2px;
                margin-left: 5px;
                cursor: pointer;
            }
			.table_form{border:1px solid #ddd;}
			.tab_ul{margin-top:10px;border-color:#C5D0DC;margin-bottom:0!important;margin-left:0;position:relative;top:1px;border-bottom:1px solid #ddd;padding-left:0;list-style:none;}
			.tab_ul>li{position:relative;display:block;float:left;margin-bottom:-1px;}
			.tab_ul>li>a {
				position: relative;
				display: block;
				padding: 10px 15px;
				margin-right: 2px;
				line-height: 1.42857143;
				border: 1px solid transparent;
				border-radius: 4px 4px 0 0;
				padding: 7px 12px 8px;
				min-width: 100px;
				text-align: center;
			}
			.tab_ul>li>a, .tab_ul>li>a:focus {
				border-radius: 0!important;
				border-color: #c5d0dc;
				background-color: #F9F9F9;
				color: #999;
				margin-right: -1px;
				line-height: 18px;
				position: relative;
			}
			.tab_ul>li>a:focus, .tab_ul>li>a:hover {
				text-decoration: none;
				background-color: #eee;
			}
			.tab_ul>li>a:hover {
				border-color: #eee #eee #ddd;
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #555;
				background-color: #fff;
				border: 1px solid #ddd;
				border-bottom-color: transparent;
				cursor: default;
			}
			.tab_ul>li>a:hover {
				background-color: #FFF;
				color: #4c8fbd;
				border-color: #c5d0dc;
			}
			.tab_ul>li:first-child>a {
				margin-left: 0;
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #576373;
				border-color: #c5d0dc #c5d0dc transparent;
				border-top: 2px solid #4c8fbd;
				background-color: #FFF;
				z-index: 1;
				line-height: 18px;
				margin-top: -1px;
				box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #555;
				background-color: #fff;
				border: 1px solid #ddd;
				border-bottom-color: transparent;
				cursor: default;
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #576373;
				border-color: #c5d0dc #c5d0dc transparent;
				border-top: 2px solid #4c8fbd;
				background-color: #FFF;
				z-index: 1;
				line-height: 18px;
				margin-top: -1px;
				box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
			}
			.tab_ul:before,.tab_ul:after{
				content: " ";
				display: table;
			}
			.tab_ul:after{
				clear: both;
			}
			.deptWrap, .fs-wrap{
				width: 70%;
			}
			.fs-dropdown .fs-options{
				max-height: 150px;
			}
			.info_village_group_threshold_warning_member{
				display: flex;
			}
			.info_village_group_threshold_warning_member img{
				width: 22px;
				height: 22px;
				margin-top: 6px !important;
			}
            .show-select-value{position: relative;float: left;border: 1px solid #d5d5d5;width: 400px;min-height: 30px;border-radius: 2px;background: #fff;}
            .show-select-value .select-list{margin: 10px}
            .show-select-value .select-list .item{float: left;border:1px solid #d5d5d5;background: #f2f2f2;padding:0 5px;margin: 0 10px 10px 0;border-radius: 2px;}
            .show-select-value .select-list .item .icon{padding: 0 5px;font-weight: bold;cursor: pointer;}
            .show-select-value .select-icon{position: absolute;right: 10px;top: 17px;vertical-align: middle;width: 0;height: 0;border: 5px solid transparent;border-top-color:#666;}
            .show-select-value .list{position: absolute;bottom: -100px;width: 400px;border: 1px solid #1686ef;background: #fff;border-radius: 2px;display: none;z-index: 10;}
            .show-select-value .list.active{display: block;}
            .show-select-value .list .item{padding: 2px 10px}
            .show-select-value .list .item:hover{background: #1686ef;color: #fff;}
		</style>
<include file="Public:footer"/>