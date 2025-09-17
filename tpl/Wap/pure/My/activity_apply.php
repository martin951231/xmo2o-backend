<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<meta http-equiv="Expires" content="-1">
	<meta http-equiv="Cache-Control" content="no-cache">
	<meta http-equiv="Pragma" content="no-cache">
	<meta charset="utf-8">
	<title>申请活动分类管理员</title>
	<link href="{pigcms{$static_path}car_owner/css/css_whir.css" rel="stylesheet"/>
	<link href="{pigcms{$static_path}car_owner/css/wap_uploadimg.css" rel="stylesheet"/>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}layer/need/layer.css"/>
	<script src="{pigcms{$static_path}car_owner/js/jquery-1.8.3.min.js"></script>
	<script type="text/javascript" src="{pigcms{$static_path}car_owner/js/webuploader.min.js"></script>
	<script src="{pigcms{$static_path}js/iscroll.js"></script>
	<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
	<script src="{pigcms{$static_path}js/simplite.js"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
	<style>
		.webuploader-element-invisible {
			position: absolute !important;
			clip: rect(1px 1px 1px 1px);
			clip: rect(1px,1px,1px,1px);
		}
		.webuploader-pick-hover .btn{
			background-color: #629b58!important;
			border-color: #87b87f;
		}
		.webuploader-pick,dl.scwj dd input,.upload_list1,.upload_list1 .upload_action, .upload_list1 .upload_item,dl.scwj dd img{height:85px;}
		.increaseid_top,.increaseid_top img,.increaseid_top input,.increaseid_top .upload_action, .increaseid_top .upload_item,.increaseid_top .webuploader-pick{ height: 125px; }
		.mask {
			position: fixed;
			left: 0;
		    top: 0;
		    width: 100%;
		    height: 110%;
		    background-color: rgba(0, 0, 0, .2);
		    /*z-index: 100;*/
		}
		.car_show{
			width: 80%;
			height: 400px;
			margin:0 10%;
			position: fixed;
			top:50%;
			margin-top: -200px;
			background: #fff;
			border-radius: 5px;
			/*z-index: 10000;*/
			/*overflow: hidden;*/
			overflow:auto; 
			 -webkit-overflow-scrolling : touch; 
		}
		.car_show p{
			padding: 10px 0;
			font-size: 16px;
		}
		.car_show div{
			width: 90%;
			margin:0 5%;
			padding-bottom: 15px;

		}
		.add{
			position: fixed;
		}
		.hidden{
			display: none;
		}
		.bg{
		    width:100%;
		    height:100%;
		    background-color: rgba(0,0,0,0.5);
		    position:absolute;
		    top:0;
		    left:0;
		}
	</style>
	<!--[if lte IE 9]>
	<script src="scripts/html5shiv.min.js"></script>
	<![endif]-->
</head>
<body>
	<div class="Secondary">
		<ul>
			<li>
				<span class="on">姓名</span>
				<input id="name" type="text" placeholder="请输入姓名">
			</li>
			<li>
				<span>手机号</span>
				<input id="models" type="text" placeholder="请输入手机号码">
			</li>
			<li>
				<span>身份证号</span>
				<input id="user_id_number" type="text" placeholder="请输入身份证号">
			</li>
			<li>
				<span class="on1">城市</span>
				<select id="province" name="province" class="on option_select" style="width: 45%">
					<option value="">请选择省</option>
				</select>
				<select id="city" name="city" class="on option_select" style="width: 45%">
					<option value="">请选择市</option>
				</select>
			</li>
			<li>
				<span class="on1">分类</span>
				<select id="front_number" name="front_number" class="on option_select" style="width: 45%">
					<option value="">请选择分类</option>
				</select>
				<select id="cate_id" name="cate_id" class="on option_select" style="width: 45%">
					<option value="">请选择分类</option>
				</select>
			</li>
			<li>
				<span class="">微信号</span>
				<input id="driving" type="text" placeholder="请输入微信号">
			</li>

		</ul>
	</div>
	<div id="view"></div>
	<div class="Secondary">
		<ul>
			<li>
				<span>上传文件</span>
				<input style="border-bottom:0px;" type="text" readonly="true" placeholder="">
			</li>
		</ul>
	<!-- 新增 -->
		<div class="increaseid">
				<dl class="clr">
					<dd class="fl">
						<div class="increaseid_end">
							<h2>身份证正面照片上传</h2>
						</div>
					</dd>
					<dd class="p165">
						<ul class="upload_list3 clearfix increaseid_top" id="upload_list1">
							<li class="upload_action">
								<img src="{pigcms{$static_path}images/xbq_06.png" />
								<input type="hidden" name="image1" id="image1" />
							</li>
						</ul>
					</dd>
				</dl>
		</div>
		<div class="increaseid">
			<dl class="clr">
				<dd class="fl">
					<div class="increaseid_end">
						<h2>身份证背面照片上传</h2>
					</div>
				</dd>
				<dd class="p165">
					<ul class="upload_list3 clearfix increaseid_top" id="upload_list2">
						<li class="upload_action">
							<img src="{pigcms{$static_path}images/xbq_06.png" />
							<input type="hidden" name="image2" id="image2" />
						</li>
					</ul>
				</dd>
			</dl>
		</div>
		<div class="increaseid">
				<dl class="clr">
					<dd class="fl">
						<div class="increaseid_end">
							<h2>手持身份证上传</h2>
						</div>
					</dd>
					<dd class="p165">
						<ul class="upload_list3 clearfix increaseid_top" id="upload_list3">
							<li class="upload_action">
								<img src="{pigcms{$static_path}images/xbq_06.png" />
								<input type="hidden" name="image3" id="image3" />
							</li>
						</ul>
					</dd>
				</dl>
		</div>
		<if condition="$config.car_apply_rule neq ''">
		<div class="increaseid">
			<dl class="clr">
				<input type="checkbox" id="car_apply_rule_agreement" name="car_apply_rule_agreement" value="1" style="-webkit-appearance:checkbox;vertical-align: middle;margin-right: 5px;"><span style="vertical-align: middle;">同意《认证协议》</span><a href="javascript:;" id="read_register_agreement" style="color: #ff7a92;vertical-align: bottom;">查看协议</a>
			</dl>
		</div>
		</if>
		<div class="dysb_d">
		  <input id="submit" type="submit" value="提交" class="dysub">
		</div>
	
		<div class="mask hidden">
				<div class="car_show" id="show_car">
					<p class="tips" id="tips_content" style=" text-align:center;">申请管理员协议</p>
					<div>
						{pigcms{$config.car_apply_rule|nl2br}
					</div>
				</div>
		</div>
	</div>
</body>
<script id="demo" type="text/html">
	<& if(_this.fenlei) {&>
	<option value="">请选择分类</option>
	<&}else{&>
	<option value="">请选择<&=_this.city?'市':'省'&></option>
	<&}&>
    <& if(_this.result && _this.result!=null && _this.result.length>0) {&>
        <& for(var i=0;i< _this.result.length;i++) {&>
        <& var item=_this.result[i]&>
        <option value="<&=item.area_id || item.id&>"><&=item.area_name || item.cate_name&></option>
        <&}&>
    <&}&>

</script>
<script id="option" type="text/html">
	<option value="">请选择<&=_this&></option>
</script>
<script>
	var activity_ower="{pigcms{:U('My/activity_owner')}"
</script>
    <script>
        var find = '{pigcms{$find}';
	</script>
	<script type="text/javascript">
		 View={
		     init:function(){
		         this.initView();
		         this.city();
		         this.uploaderFile();
		         this.submit();
		         this.agreement()
			 },
             initView:function(){
                 var slef=this;
                 var province=slef.province=function(datas){
                     $.ajax({
                         type : "post",
                         url : "{pigcms{:U('Platform/area')}",
                         dataType : "json",
                         data:datas,
                         success : function(data){
                             var tpl = Simplite.getTemplate('demo');
                             if(datas.area_id){
                                 data.city=true
                             }else{
                                 data.city=false
                             }
                             var compiledTpl = Simplite.compile(tpl)(data);
                             if(datas.area_id){
                                 $('#city').html(compiledTpl)
							 }else{
                                 $('#province').html(compiledTpl)
							 }


                         },
                         error:function(){

                             alert('接口出错');
                         }
                     })
                 }
                    //省市获取
					 var datas={
                         area_type	:	1,
					 }
					 province(datas)
			 },
             city:function(){
		         //选择省
				 var self=this;
				 $("#province").on('change',function(){
				      console.log($(this).val())
					 if($(this).val()!=''){
					     var datas={
                             area_id:$(this).val(),
                             area_type:2
						 }
                         repeat(1)
                         self.province(datas)
					 }
				 })
				 //选择市
                 $("#city").on('change',function(){
                     if($(this).val()!=''){
                         var datas={
                             province_id:$("#province").val(),
                             city_id:$(this).val()
                         }
                         repeat(2)
                         self.fenlei(datas)
                     }
                 })
                 //分类
                 $("#front_number").on('change',function(){
                     if($(this).val()!=''){
                         var datas={
                             province_id:$("#province").val(),
                             city_id:$('#city').val(),
							 id:$(this).val()
                         }
                         repeat(3)
                         self.fenlei(datas)
                     }
                 })
                 var fenlei=self.fenlei=function(datas){
                     $.ajax({
                         type : "post",
                         url : "{pigcms{:U('Platform/cate_list')}",
                         dataType : "json",
                         data:datas,
                         success : function(data){
                             var tpl = Simplite.getTemplate('demo');
                             data.fenlei=true
                             if(datas.id){
                                 var compiledTpl = Simplite.compile(tpl)(data);
                                 $('#cate_id').html(compiledTpl)
							 }else{
                                 var compiledTpl = Simplite.compile(tpl)(data);
                                 $('#front_number').html(compiledTpl)
							 }

                         },
                         error:function(){

                             alert('接口出错');
                         }
                     })
                 }
                 var repeat=function(n){
                     for(var i=n;i < $(".option_select").length;i++){
                         $(".option_select").eq(i).find('option').remove()
                         var tpl = Simplite.getTemplate('option');
                         var con='市'
                         if(i>=2){
                             con='分类'
                         }

                         var compiledTpl = Simplite.compile(tpl)(con);
                         $(".option_select").eq(i).html(compiledTpl)
                     }
				 }
			 },
             uploaderFile:function(){
                 //  上传图片
                 var uploader = WebUploader.create({
                     auto: true,
                     swf: '{pigcms{$static_public}js/Uploader.swf',
                     server: "{pigcms{:U('Platform/upload_img')}",
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
                     if(response.result.error == 0){
                         $('#upload_list1 li img').attr('src',response.result.url['file']);
                         $('#image1').val(response.result.url['file']);
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
                     server: "{pigcms{:U('Platform/upload_img')}",
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
                     if(response.result.error == 0){
                         $('#upload_list2 li img').attr('src',response.result.url['file']);
                         $('#image2').val(response.result.url['file']);
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
                     server: "{pigcms{:U('Platform/upload_img')}",
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
                     if(response.result.error == 0){
                         $('#upload_list3 li img').attr('src',response.result.url['file']);
                         $('#image3').val(response.result.url['file']);
                     }else{
                         alert(response.info);
                     }
                 });
                 uploader2.on('uploadError', function(file,reason){
                     alert('上传失败！请重试。');
                 });
             },
             submit:function(){
                 var request = true;
                 var self=this;
                 $("#submit").click(function(){
                     var name	=	$('#name').val();
                     var models	=	$('#models').val();
                     var user_id_number=	$('#user_id_number').val();
                     var front_number	=	$('#front_number').val();
                     var driving	=	$('#driving').val();
                     var city=$("#city").val();
                     var cate_id=$("#cate_id").val()
                     var province=$("#province").val()
                     var image1	=	$('#image1').val();
                     var image2	=	$('#image2').val();
                     var image3	=	$('#image3').val();
                     var car_apply_rule_agreement	=	0;
                     if(name==''){
                         alert('姓名不能为空');
                         return false;
                     }
                     var check = self.checkCardId(user_id_number);
                     if(check == false){
                         return false;
                     }
                     if(models==''){
                         alert('手机号不能为空');
                         return false;
                     }
                     var myreg = /^[1][3,4,5,7,8][0-9]{9}$/;
                     if(!myreg.test(models)){
                         alert('手机号码无效');
                         return false;
                     }
                     if(province=='' || city==''){
                         alert('请选择城市');
                         return false;
					 }
					if(front_number=='' || cate_id==''){
                         alert('请选择分类');
                         return false;
					 }
                     if(driving==''){
                         alert('微信号不能为空');
                         return false;
                     }
                     if(image1==''){
                         alert('请上传身份证正面');
                         return false;
					 }
                     if(image2==''){
                         alert('请上传身份证反面');
                         return false;
                     }
                     if(image3==''){
                         alert('请上传手持身份证');
                         return false;
                     }
                     if(request == true){
                         request = false;
                         $.ajax({
                             type : "post",
                             url : "{pigcms{:U('Platform/add_cate_manager')}",
                             dataType : "json",
                             data:{
                                 name	:	name,
                                 id_card	:	user_id_number,
                                 phone	:	models,
                                 wx_card	:	driving,
								 province_id:province,
								 city_id:city,
						         cate_id:cate_id,
                                 hand_photo	:image3,
                                 back_photo	:	image2,
                                 face_photo	:	image1,
                             },
                             success : function(result){
                                 var rideList	=	result;
                                 console.log(result)
                                 if(result.errorCode == 0){
                                   window.location.href	=activity_ower+'&id='+result.result
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
             },
             checkCardId:function(socialNo){
                 if(socialNo == ""){
                     alert("身份证号码不能为空!");
                     return (false);
                 }
                 if (socialNo.length != 15 && socialNo.length != 18){
                     alert("输入身份证号码格式不正确!");
                     return (false);
                 }
                 var area={11:"北京",12:"天津",13:"河北",14:"山西",15:"内蒙古",21:"辽宁",22:"吉林",23:"黑龙江",31:"上海",32:"江苏",33:"浙江",34:"安徽",35:"福建",36:"江西",37:"山东",41:"河南",42:"湖北",43:"湖南",44:"广东",45:"广西",46:"海南",50:"重庆",51:"四川",52:"贵州",53:"云南",54:"西藏",61:"陕西",62:"甘肃",63:"青海",64:"宁夏",65:"新疆",71:"台湾",81:"香港",82:"澳门",91:"国外"};
                 if(area[parseInt(socialNo.substr(0,2))]==null){
                     alert("身份证号码不正确(地区非法)!");
                     return (false);
                 }
                 if (socialNo.length == 15){
                     pattern= /^\d{15}$/;
                     if (pattern.exec(socialNo)==null){
                         alert("15位身份证号码必须为数字！");
                         return (false);
                     }
                     var birth = parseInt("19" + socialNo.substr(6,2));
                     var month = socialNo.substr(8,2);
                     var day = parseInt(socialNo.substr(10,2));
                     switch(month) {
                         case '01':
                         case '03':
                         case '05':
                         case '07':
                         case '08':
                         case '10':
                         case '12':
                             if(day>31) {
                                 alert('输入身份证号码不格式正确!');
                                 return false;
                             }
                             break;
                         case '04':
                         case '06':
                         case '09':
                         case '11':
                             if(day>30) {
                                 alert('输入身份证号码不格式正确!');
                                 return false;
                             }
                             break;
                         case '02':
                             if((birth % 4 == 0 && birth % 100 != 0) || birth % 400 == 0){
                                 if(day>29) {
                                     alert('输入身份证号码不格式正确!');
                                     return false;
                                 }
                             }else{
                                 if(day>28){
                                     alert('输入身份证号码不格式正确!');
                                     return false;
                                 }
                             }
                             break;
                         default:
                             alert('输入身份证号码不格式正确!');
                             return false;
                     }
                     var nowYear = new Date().getYear();
                     if(nowYear - parseInt(birth)<15 || nowYear - parseInt(birth)>100) {
                         alert('输入身份证号码不格式正确!');
                         return false;
                     }
                     return (true);
                 }

                 var Wi = new Array(
                     7,9,10,5,8,4,2,1,6,
                     3,7,9,10,5,8,4,2,1
                 );
                 var   lSum        = 0;
                 var   nNum        = 0;
                 var   nCheckSum   = 0;
                 for (i = 0; i < 17; ++i){
                     if ( socialNo.charAt(i) < '0' || socialNo.charAt(i) > '9' )
                     {
                         alert("输入身份证号码格式不正确!");
                         return (false);
                     }
                     else
                     {
                         nNum = socialNo.charAt(i) - '0';
                     }
                     lSum += nNum * Wi[i];
                 }
                 if( socialNo.charAt(17) == 'X' || socialNo.charAt(17) == 'x'){
                     lSum += 10*Wi[17];
                 }else if( socialNo.charAt(17) < '0' || socialNo.charAt(17) > '9' ){
                     alert("输入身份证号码格式不正确!");
                     return (false);
                 }else{
                     lSum += ( socialNo.charAt(17) - '0' ) * Wi[17];
                 }
                 if((lSum % 11) == 1 ){
                     return true;
                 }else{
                     alert("输入身份证号码格式不正确!");
                     return (false);
                 }
			 },
             agreement:function(){
                 if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase()))){
                     var reg = /versioncode=(\d+),/;
                     var arr = reg.exec(navigator.userAgent.toLowerCase());
                     if(arr == null){

                     }else{
                         var version = parseInt(arr[1]);
                         if(version >= 50){
                             if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
                             	iosCommon.iosFunction('hideWebViewHeader/true', {name:'hideWebViewHeader', show_head: '1'});
                                 $('#li_delivery a').click(function(){
                                     var address_id = $('#address_id').val() == '' ? '0' : $('#address_id').val();
                                     iosCommon.iosFunction('getUserAddress/'+address_id, {name:'getUserAddress', address_id: address_id});
                                     $('body').append('<iframe src="pigcmso2o://getUserAddress/'+address_id+'" style="display:none;"></iframe>');
                                     return false;
                                 });
                             }else{
                                 $('#li_delivery a').click(function(){
                                     var address_id = $('#address_id').val() == '' ? '0' : $('#address_id').val();
                                     window.lifepasslogin.getUserAddress(address_id);
                                     return false;
                                 });
                             }
                         }
                     }
                 }
                 var top = 0;//给top变量一个初始值，以便下方赋值并引用。
                 $('#read_register_agreement').click(function(){//点击事件弹出层显示，
                     $('.mask').removeClass('hidden'); //弹出层出现
                     top = $(window).scrollTop();//获取页面的scrollTop；
                     $('body').css("top",-top+"px");//给body一个负的top值；
                     $('body').addClass('add');//给body增加一个类，position:fixed;
                 });
                 $('.mask').click(function(){//弹出层消失点击事件
                     $('.mask').addClass('hidden'); //弹出层消失
                     $('body').removeClass('add');//去掉给body的类
                     $(window).scrollTop(top);//设置页面滚动的高度，如果不设置，关闭弹出层时页面会回到顶部。
                 });
             }
		 }
		 window.View=View;
		 View.init()
	</script>
	
</html>