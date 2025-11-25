<include file="Public/header"/>
<include file="Public/header_user"/>
<link href="{pigcms{$static_path}css/wap_uploadimg.css" rel="stylesheet"/>
<link href="{pigcms{$static_path}css/mobiscroll.2.13.2.css" rel="stylesheet"/>
<script type="text/javascript" src="{pigcms{$static_path}js/webuploader.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/mobiscroll.2.13.2.js"></script>
	<section class="personal">
		<div class="w1200 clr">
			<div class="fl nav">
				<ul>
					<li class="gr">
						<a href="{pigcms{:U('index')}">个人信息</a>
					</li>
					<li class="mp">
						<a href="{pigcms{:U('ticket_order_list')}">门票订单</a>
					</li>
					<li class="dy">
						<a href="{pigcms{:U('aguide_order_list')}">导游订单</a>
					</li>
					<li class="xd on">
						<a href="{pigcms{:U('add_guide')}">申请向导</a>
					</li>
					<li class="xd">
						<a href="{pigcms{:U('guide_order')}">我是向导订单</a>
					</li>
					<li class="yb">
						<a href="{pigcms{:U('mate')}">我的游伴</a>
					</li>
					<li class="cx">
						<a href="{pigcms{:U('travel_person')}">出行人信息</a>
					</li>
					<li class="xc">
						<a href="{pigcms{:U('manager_travel')}">我的行程</a>
					</li>
					<li class="sc">
						<a href="{pigcms{:U('collection')}">我的收藏</a>
					</li>
				</ul>
			</div>
            <div class="fr navright">
                <div class="askfor">
                    <div class="ask_top">
                        <h2>申请向导</h2>
                    </div>
                    <div class="ask_end">
                        <div class="ask_list">
                            <ul class="clr">
                                <li class="clr fl on">
                                    <div class="fl line"><i>1</i></div>
                                    <div class="fl text">实名认证</div>
                                </li>
                                <li class="clr fl on">
                                    <div class="fl line"><i>2</i></div>
                                    <div class="fl text">查看实名验证</div>
                                </li>
                                <li class="clr fl on">
                                    <div class="fl line"><i>3</i></div>
                                    <div class="fl text">发布向导</div>
                                </li>
                                <li class="clr fl">
                                    <div class="fl line"><i>4</i></div>
                                    <div class="fl text">向导查看</div>
                                </li>
                            </ul>
                        </div>
                        <div class="forms_list let600">
                            <div class="date">
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>喜欢昵称：</em></span>
                                    <div class="p105 clr">
                                        <input id="guide_nickname" value="{pigcms{$scenic_aguide['guide_nickname']}" type="text" placeholder="请输入昵称">
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>从业年限：</em></span>
                                    <div class="p105 clr">
                                        <input id="guide_life" value="{pigcms{$scenic_aguide['guide_life']}" type="number" class="w224" placeholder="请输入年限，请填写数字"><span class="ml15">年</span>
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>服务地区：</em></span>
                                    <div class="p105 clr">
	                                    <form action="" method="get">
	                                        <select name="province" id="province">
	                                        	<volist name="scenic_area" id="vo">
	                                            	<option value="{pigcms{$vo['area_id']}" <if condition="$vo['area_id'] eq $scenic_aguide['province_id']">selected="selected"</if>>{pigcms{$vo['area_name']}</option>
	                                            </volist>
	                                        </select>
	                                        <select name="city" id="city">
	                                        	<volist name="scenic_area[$number]['son']" id="vo">
	                                            	<option value="{pigcms{$vo['area_id']}" <if condition="$vo['area_id'] eq $scenic_aguide['city_id']">selected="selected"</if>>{pigcms{$vo['area_name']}</option>
	                                            </volist>
	                                        </select>
										</form>
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>个性签名：</em></span>
                                    <div class="p105 clr">
                                        <input id="guide_autograph" value="{pigcms{$scenic_aguide['guide_autograph']}" type="text" placeholder="请输入个性签名">
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>简单描述：</em></span>
                                    <div class="p105 clr">
                                        <textarea id="guide_intr" placeholder="请输入简单描述">{pigcms{$scenic_aguide['guide_intr']|htmlspecialchars_decode=ENT_QUOTES}</textarea>
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>手机号码：</em></span>
                                    <div class="p105 clr">
                                        <input id="guide_phone" type="text" placeholder="请输入手机号码" value="{pigcms{$scenic_aguide['guide_phone']}">
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>预定价格：</em></span>
                                    <div class="p105 clr">
                                        <input id="guide_price" type="text" placeholder="请输入预定价格（天）" value="{pigcms{$scenic_aguide['guide_price']}">
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>选择性别：</em></span>
                                    <div class="p105 clr">
                                        <input type="text" id="xz_dummy"  readonly="readonly" class="fr" placeholder="选择性别" >
                                        <select id="xz"  class="demo-test-select" data-role="none" style="display: none;" >
                                            <option value="1" <if condition="$scenic_aguide['guide_sex'] eq 1">selected="selected"</if>>男</option>
                                            <option value="2" <if condition="$scenic_aguide['guide_sex'] eq 2">selected="selected"</if>>女</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>出生年月：</em></span>
                                    <div class="p105 clr">
                                        <input type="text" value="{pigcms{$scenic_aguide['date']}" placeholder="请选择日期" id="demo">
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>游记图片：</em></span>
                                    <div class="p105 clr">
                                        <h3 class="mible-tit">请上传1-8张照片，上传自己去过的景点图片，可以提高成功率哟~</h3>
                                            <div class="item">
                                                <div class="upload_box">
                                                    <ul class="upload_list3 clr" id="upload_list3">
                                                        <li class="upload_action" id="uploadPicBtn">
                                                            <img src="{pigcms{$static_path}images/sztup_11.png">
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        <div class="item uploadNum" id="uploadNum">
                                            最多上传<span class="leftNum orange">8</span>张图片每张不超过5M，支持JPG、BMP、PNG，已上传<span class="loadedNum orange">0</span>张
                                        </div>
                                    </div>
                                </div>
                                <if condition="$type eq 2">
	                                <div class="surface clr">
	                                    <span class="on fl"><i></i><em>真实姓名：</em></span>
	                                    <div class="p105 clr">
	                                        <input id="guide_name" type="text" placeholder="请输入姓名">
	                                    </div>
	                                </div>
	                                <div class="surface clr">
	                                    <span class="on fl"><i></i><em>导游证号：</em></span>
	                                    <div class="p105 clr">
	                                        <input id="guide_card" type="text" placeholder="请输入导游证号">
	                                    </div>
	                                </div>
	                                <div class="surface clr">
	                                    <span class="on fl"><i></i><em>上传证件：</em></span>
	                                    <div class="p105 pt15 h142 clr">
	                                        <ul class="fl ulp15">
	                                            <li class="upload_list1" id="upload_list1">
	                                                <img src="{pigcms{$static_path}images/tux_03.png" width="150" height="96">
	                                                <input type="hidden" name="image1" id="image1">
	                                            </li>
	                                            <p class="c6">导游证正面</p>
	                                        </ul>
	                                        <ul class="fl ulp15">
	                                            <li class="upload_list1" id="upload_list2">
	                                                <img src="{pigcms{$static_path}images/tux_05.png" width="150" height="96">
	                                                <input type="hidden" name="image2" id="image2">
	                                            </li>
	                                            <p class="c6">导游证背面</p>
	                                        </ul>
	                                    </div>
	                                </div>
                                </if>
                                <div class="refer" id="submit">提交</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
	<style type="text/css">
		#xz_dummy,#demo{ background: url({pigcms{$static_path}images/ldtx_03.png) 298px center no-repeat; }
		.mbsc-android-holo .dwwol,.mbsc-mobiscroll .dwwol{ border-top:#1bccaf 2px solid; border-bottom:#1bccaf 2px solid; }
		.mbsc-android-holo .dwv{border-bottom:#1bccaf 2px solid; color: #1bccaf;  }
		.mbsc-mobiscroll .dwb{ color: #000; }
		.webuploader-element-invisible {
			position: absolute !important;
			clip: rect(1px 1px 1px 1px);
			clip: rect(1px,1px,1px,1px);
		}
		.h142 .webuploader-pick,.h142 ul.ulp15 img,.h142 ul.ulp15 li{height:142px;}
		.upload_box .webuploader-pick,.upload_box .upload_action{  height: 76px;  }
	</style>
    <script type="text/javascript">
	    $("select[name='province']").change(function(){
			show_city($(this).find('option:selected').attr('value'));
		});
		function show_city(id){
			$.post("{pigcms{:U('Scenic_user/select_area')}",{pid:id},function(result){
				result = $.parseJSON(result);
				if(result.error == 0){
					var area_dom = '';
					$.each(result.list,function(i,item){
						area_dom+= '<option value="'+item.area_id+'">'+item.area_name+'</option>';
					});
					$("select[name='city']").html(area_dom);
				}else{
					alert('未开通此城市');
				}
			});
		}
        //插件日历
        $(function () {
            var currYear = new Date().getFullYear();
            var opt = {
                'dateYMD': {
                    preset: 'date',
                    dateFormat: 'yyyy-mm-dd',
                    theme: 'android-ics light', //皮肤样式
                    display: 'modal',           //显示方式
                    mode: 'scroller',           //日期选择模式
                    showNow: true,
                    nowText: "今天",
                    startYear: currYear-50,    //开始年份
                    endYear: currYear-10, 		//结束年份
                    // minDate: new Date(),    //只能选择
                },'select': {
                    preset: 'select'
                }
            }
            $('#demo').scroller($.extend(opt['dateYMD'],opt['default']));
            $('.demo-test-select').scroller($.extend(opt['select'],opt['default']));
        });

		var uploader1 = WebUploader.create({
			auto: true,
			swf: '{pigcms{$static_public}js/Uploader.swf',
			server: "{pigcms{:U('Scenic_aguide/ajaxWebUpload')}",
			pick: {
				id:'#upload_list1',
				multiple:false
			},
			accept: {
				title: 'Images',
				extensions: 'gif,jpg,jpeg,png',
				mimeTypes: 'image/*'
			}
		});
		uploader1.on('uploadSuccess',function(file,response){
			if(response.error == 0){
				$('#upload_list1 li img').attr('src',response.url);
				$('#image1').val(response.title);
			}else{
				alert(response.info);
			}
		});
		uploader1.on('uploadError', function(file,reason){
			alert('上传失败！请重试。');
		});
		var uploader2 = WebUploader.create({
			auto: true,
			swf: '{pigcms{$static_public}js/Uploader.swf',
			server: "{pigcms{:U('Scenic_aguide/ajaxWebUpload')}",
			pick: {
				id:'#upload_list2',
				multiple:false
			},
			accept: {
				title: 'Images',
				extensions: 'gif,jpg,jpeg,png',
				mimeTypes: 'image/*'
			}
		});
		uploader2.on('uploadSuccess',function(file,response){
			if(response.error == 0){
				$('#upload_list2 li img').attr('src',response.url);
				$('#image2').val(response.title);
			}else{
				alert(response.info);
			}
		});
		uploader2.on('uploadError', function(file,reason){
			alert('上传失败！请重试。');
		});
		var uploader3 = WebUploader.create({
			auto: true,
			swf: '{pigcms{$static_public}js/Uploader.swf',
			server: "{pigcms{:U('Scenic_aguide/ajaxWebUpload')}",
			pick: {
				id:'#uploadPicBtn',
				multiple:false
			},
			accept: {
				title: 'Images',
				extensions: 'gif,jpg,jpeg,png',
				mimeTypes: 'image/*'
			}
		});
		uploader3.on('uploadSuccess',function(file,response){
			if(response.error == 0){
				if($('.picListLi').size() > 0){
					$('.picListLi:last').after('<li class="upload_action picListLi"><img src="'+response.url+'"/><input type="hidden" name="picList[]" value="'+response.title+'"/></li>');
				}else{
					$('#upload_list3').append('<li class="upload_action picListLi"><img src="'+response.url+'"/><input type="hidden" name="picList[]" value="'+response.title+'"/></li>');
				}
				$('.loadedNum').html($('.picListLi').size());
				if($('.picListLi').size() >= 8){
					$('#uploadPicBtn').hide();
				}
			}else{
				alert(response.info);
			}
		});
		uploader3.on('uploadError', function(file,reason){
			alert('上传失败！请重试。');
		});
		uploader3.on('fileQueued',function(file){
			if($('.picListLi').size() >= 8){
				uploader3.cancelFile(file);
				alert('最多上传8张图片');
				return false;
			}
		});
		var submit_release	=	true;
		var type="{pigcms{$type}";
		$('#submit').on('click',function(){
			var guide_nickname	=	$('#guide_nickname').val();	//昵称
			var guide_life	=	$('#guide_life').val();			//从业年限
			var province	=	$('#province').val();			//省
			var city		=	$('#city').val();				//市
			var guide_autograph	=	$('#guide_autograph').val();//个性签名
			var guide_intr	=	$('#guide_intr').val();		//简单描述
			var guide_phone	=	$('#guide_phone').val();		//手机号码
			var guide_price	=	$('#guide_price').val();		//预定价格
			var guide_sex	=	$('#xz').val();					//性别
			var date		=	$('#demo').val();				//出生年月
			var guide_name	=	$('#guide_name').val();			//真实姓名
			var guide_card	=	$('#guide_card').val();			//向导证号
			var image1		=	$('#image1').val();				//向导证正面
			var image2		=	$('#image2').val();				//向导证背面
			var image4		=	'';
			$("input[name='picList[]']").each(function(index,item){
				image4	+=	$(this).val()+';';
			});
			if(guide_nickname == ''){
				alert('昵称不能为空');
				return false;
			}
			if(guide_life == ''){
				alert('从业年限不能为空');
				return false;
			}
			if(guide_autograph == ''){
				alert('个性签名不能为空');
				return false;
			}
			if(guide_intr == ''){
				alert('简单描述不能为空');
				return false;
			}
			if(guide_phone == ''){
				alert('手机号码不能为空');
				return false;
			}
			if(guide_price == ''){
				alert('预定价格不能为空');
				return false;
			}
			if(date == ''){
				alert('出生年月不能为空');
				return false;
			}
			if(image4 == ''){
				alert('游记图片不能为空');
				return false;
			}
			if(type == 2){
				if(guide_name == ''){
					alert('真实姓名不能为空');
					return false;
				}
				if(guide_card == ''){
					alert('导游证号不能为空');
					return false;
				}
				if(image1 == ''){
					alert('导游证正面不能为空');
					return false;
				}
				if(image2 == ''){
					alert('导游证背面不能为空');
					return false;
				}
			}
			if(submit_release == true){
				submit_release = false;
				$.ajax({
					type : "post",
					url : "{pigcms{:U('guide_release_json')}",
					dataType : "json",
					data:{
						guide_nickname	:	guide_nickname,
						guide_life	:	guide_life,
						province	:	province,
						city		:	city,
						guide_autograph	:	guide_autograph,
						guide_intr	:	guide_intr,
						guide_phone	:	guide_phone,
						guide_price	:	guide_price,
						guide_sex	:	guide_sex,
						date		:	date,
						guide_name	:	guide_name,
						guide_card	:	guide_card,
						image1		:	image1,
						image2		:	image2,
						image4		:	image4,
						type		:	type,
						guide_id	:	"{pigcms{$scenic_aguide['guide_id']}",
					},
					success : function(result){
						if(result.errorCode == 0){
							window.location.href	=	result.result;
						}else{
							submit_release = true;
							alert(result.errorMsg);
						}
					},
					error:function(){
						submit_release = true;
					}
				})
			}
		});
    </script>
<include file="Public/footer"/>