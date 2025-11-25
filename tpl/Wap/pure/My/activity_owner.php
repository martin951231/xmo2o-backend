<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta charset="utf-8">
<title>活动分类管理员认证</title>
<link href="{pigcms{$static_path}car_owner/css/css_whir.css" rel="stylesheet"/>
<script src="{pigcms{$static_path}car_owner/js/jquery-1.8.3.min.js"></script>
<script src="{pigcms{$static_path}js/simplite.js"></script>
<!--[if lte IE 9]>
<script src="scripts/html5shiv.min.js"></script>
<![endif]-->
</head>
<body>
<div class="Guide_end">
	<div id="content">

	</div>

</div>


</body>
<script type="text/html" id="init">
	 <& if(_this.status==0){&>
	 <div class="Guide_top Guide_shz">
		 <h5><span>审核中</span></h5>
	 </div>
	  <&}else if(_this.stauts==2){&>
	 <div class="Guide_top">
		 <h5><span>未通过</span></h5>
	 </div>
	 <&}else{&>
	 <div class="Guide_top Guide_ytg">
		 <h5><span>已通过</span></h5>
	 </div>
	 <&}&>

	<div class="Guide_n">
		<p><span>姓名：</span><&=_this.name&></p>
		<p><span>身份证号：</span><&=_this.id_card&></p>
	</div>
	<div class="Correctid">
		<img src="<&=_this.face_photo&>" width=100% >
		<p class="jszt">身份证正面照片</p>
	</div>
	<div class="Correctid">
		<img src="<&=_this.back_photo&>" width=100% >
		<p class="jszt">身份证反面照片</p>
	</div>
	<div class="Correctid">
		<img src="<&=_this.hand_photo&>" width=100% >
		<p class="jszt">手持身份证照片</p>
	</div>
	 <& if(_this.status==2){&>
	 <div class="Incon">
		 <p><&=_this.message&></p>
	 </div>
	 <div id="car_apply" class="dysb_d">
		 <a href="#" class="dysub">重新提交审核</a>
	 </div>
	 <&}&>

</script>
	<script type="text/javascript">
		var View={
		    init:function(){
		        this.initView()
			},
            initView:function(){
                var id="{pigcms{$Think.get.id}";
                $.ajax({
                    type : "post",
                    url : "{pigcms{:U('Platform/managerDetail')}",
                    dataType : "json",
                    data:{
                       id:id
                    },
                    success : function(data){
                        var tpl = Simplite.getTemplate('init');
                        var compiledTpl = Simplite.compile(tpl)(data.result);
                            $('#content').html(compiledTpl)
                        $(".Correctid img").each(function(){
                            $(this).height($(this).width()*0.6)
							var img=new Image();
                            img.src=data.result.hand_photo;
                            console.log(img.width,img.height)
							var d=img.width/img.height
							var w=$(window).width()/d
                            $('#car_apply').on('click',function(){
                                location.href =	"{pigcms{:U('My/activity_apply')}";
                            });
                        });
                    },
                    error:function(){
                        alert('接口出错');
                    }
                })

			}
		}
		window.View=View;
		 View.init()

	</script>
</html>