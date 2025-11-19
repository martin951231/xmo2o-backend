<include file="Public/header"/>
<include file="Public/header_user"/>
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
                                <li class="clr fl">
                                    <div class="fl line"><i>2</i></div>
                                    <div class="fl text">查看实名验证</div>
                                </li>
                                <li class="clr fl">
                                    <div class="fl line"><i>3</i></div>
                                    <div class="fl text">发布向导</div>
                                </li>
                                <li class="clr fl">
                                    <div class="fl line"><i>4</i></div>
                                    <div class="fl text">向导查看</div>
                                </li>
                            </ul>
                        </div>
                        <div class="forms_list">
                            <div class="date">
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>真实姓名：</em></span>
                                    <div class="p105 clr">
                                        <input type="text" placeholder="请输入姓名" id="name">
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>身份证号：</em></span>
                                    <div class="p105 clr">
                                        <input type="text" id="uid" placeholder="请输入身份证号">
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>上传证件：</em></span>
                                    <div class="p105 pt15 clr">
                                        <ul class="fl ulp15">
                                            <li class="upload_list1" id="upload_list1">
                                                <img src="{pigcms{$static_path}images/idpho_10.png" width="150" height="96">
                                                <input type="hidden" name="image1" id="image1">
                                            </li>
                                            <p class="c6">身份证正面</p>
                                        </ul>
                                        <ul class="fl ulp15">
                                            <li class="upload_list2" id="upload_list2">
                                                <img src="{pigcms{$static_path}images/idpho_12.png" width="150" height="96">
                                                <input type="hidden" name="image2" id="image2">
                                            </li>
                                            <p class="c6">身份证背面</p>
                                        </ul>
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>手持证件：</em></span>
                                    <div class="p105 pt15 clr">
                                        <ul class="fl ulp15">
                                            <li class="upload_list1" id="upload_list3">
                                                <img src="{pigcms{$static_path}images/xbq_06.png" width="150" height="96">
                                                <input type="hidden" name="image1" id="image3">
                                            </li>
                                            <p class="c6">手持身份证</p>
                                        </ul>
                                    </div>
                                </div>
                                <div id="submit" class="refer">提交</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
	<link href="{pigcms{$static_path}css/wap_uploadimg.css" rel="stylesheet"/>
	<script type="text/javascript" src="{pigcms{$static_path}js/webuploader.min.js"></script>
	<style>
		.webuploader-element-invisible{
			position: absolute !important;
			clip: rect(1px 1px 1px 1px);
			clip: rect(1px,1px,1px,1px);
		}
		.webuploader-pick,ul.ulp15 img,ul.ulp15 li{height:96px;}
	</style>
	<script type="text/javascript">
		var uploader = WebUploader.create({
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
		uploader.on('uploadSuccess',function(file,response){
			if(response.error == 0){
				$('#upload_list1 div img').attr('src',response.url);
				$('#image1').val(response.title);
			}else{
				alert(response.info);
			}
		});
			uploader.on('uploadError', function(file,reason){
			alert('上传失败！请重试。');
		});
		var uploader1 = WebUploader.create({
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
		uploader1.on('uploadSuccess',function(file,response){
			if(response.error == 0){
				$('#upload_list2 div img').attr('src',response.url);
				$('#image2').val(response.title);
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
				id:'#upload_list3',
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
				$('#upload_list3 div img').attr('src',response.url);
				$('#image3').val(response.title);
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
		var request = true;
		$("#submit").click(function(){
			var name	=	$('#name').val();
			var uid		=	$('#uid').val();
			var image1	=	$('#image1').val();
			var image2	=	$('#image2').val();
			var image3	=	$('#image3').val();
			if(name==''){
				alert('姓名不能为空');
				return false;
			}
			var check = checkCardId(uid);
			if(check == false){
				return false;
			}
			if(image1 == ''){
				alert('身份证正面不能为空');
				return false;
			}
			if(image2 == ''){
				alert('身份证反面不能为空');
				return false;
			}
			if(image3 == ''){
				alert('手持身份证不能为空');
				return false;
			}
			if(request == true){
				request = false;
				$.ajax({
					type : "post",
					url : "{pigcms{:U('add_guide_json')}",
					dataType : "json",
					data:{
						user_truename	:	name,
						user_id_number	:	uid,
						authentication_img	:	image1,
						authentication_back_img	:	image2,
						hand_authentication	:	image3,
					},
					success : function(result){
						var rideList	=	result;
						if(result.errorCode == 0){
							window.location.href	=	rideList.result;
						}else{
							request = true;
							alert(result.errorMsg);
						}
					},
					error:function(){
						request = true;
						alert('接口出错');
					}
				})
			}
		})
	</script>
<include file="Public/footer"/>