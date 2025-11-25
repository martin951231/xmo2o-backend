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
                                    <div class="fl text">申请向导</div>
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
	                                        <li class="upload_list1 upload_list2" id="upload_list1">
	                                            <img src="{pigcms{$static_path}images/tux_03.png" width="150" height="96">
	                                            <input type="hidden" name="image1" id="image1">
	                                        </li>
	                                        <p class="c6">导游证正面</p>
	                                    </ul>
	                                    <ul class="fl ulp15">
	                                        <li class="upload_list1 upload_list3" id="upload_list2">
	                                            <img src="{pigcms{$static_path}images/tux_05.png" width="150" height="96">
	                                            <input type="hidden" name="image2" id="image2">
	                                        </li>
	                                        <p class="c6">导游证背面</p>
	                                    </ul>
	                                </div>
	                            </div>
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
				$('#upload_list1 img').attr('src',response.url);
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
				$('#upload_list2 img').attr('src',response.url);
				$('#image2').val(response.title);
			}else{
				alert(response.info);
			}
		});
		uploader2.on('uploadError', function(file,reason){
			alert('上传失败！请重试。');
		});
		var submit_release	=	true;
		$('#submit').on('click',function(){
			var guide_name	=	$('#guide_name').val();			//真实姓名
			var guide_card	=	$('#guide_card').val();			//向导证号
			var image1		=	$('#image1').val();				//向导证正面
			var image2		=	$('#image2').val();				//向导证背面
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
			if(submit_release == true){
				submit_release = false;
				$.ajax({
					type : "post",
					url : "{pigcms{:U('again_guide_release_json')}",
					dataType : "json",
					data:{
						guide_name	:	guide_name,
						guide_card	:	guide_card,
						image1		:	image1,
						image2		:	image2,
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