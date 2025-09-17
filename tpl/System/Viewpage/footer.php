<include file="Public:header"/>
	<div class="mainbox">
		<include file="header"/>
		<div class="table-list">
			<div class="fitment_div">
				<div class="form-group default_tpl">
					<div class="group-title">
						<span class="name">导航列表</span>
						<button type="button" class="btn fitment" data-url="{pigcms{:U('Home/foot_menu_list',array('cat_key'=>'app_footer'))}">装修</button>
					</div>
					<div class="group-content">自定义底部导航列表，若不设置则展示系统默认的。</div>
				</div>
				<div class="form-group">
					<div class="group-title">
						<span class="name">文本默认颜色</span>
						<div class="right_div">
							<input type="text" class="input-text" id="config_txtcolor" value="{pigcms{$config.app_footer_txtcolor}" readonly="readonly" style="text-indent:1000px;width:24px;cursor:default;background-color:{pigcms{$config.app_footer_txtcolor}"/>
							<a href="javascript:void(0);" id="choose_color_box_txtcolor" style="line-height:28px;">点击选择颜色</a>
						</div>
					</div>
					<div class="group-content">导航文字的默认颜色，即非选中色。</div>
				</div>
				<div class="form-group">
					<div class="group-title">
						<span class="name">文本选中颜色</span>
						<div class="right_div">
							<input type="text" class="input-text" id="config_txtactivecolor" value="{pigcms{$config.app_footer_txtactivecolor}" readonly="readonly" style="text-indent:1000px;width:24px;cursor:default;background-color:{pigcms{$config.app_footer_txtactivecolor};"/>
							<a href="javascript:void(0);" id="choose_color_box_txtactivecolor" style="line-height:28px;">点击选择颜色</a>
						</div>
					</div>
					<div class="group-content">导航文字的选中颜色，即当前页面指向的部分。</div>
				</div>
				<div class="form-group">
					<div class="group-title">
						<span class="name">背景颜色</span>
						<div class="right_div">
							<input type="text" class="input-text" id="config_bgcolor" value="{pigcms{$config.app_footer_bgcolor}" readonly="readonly" style="text-indent:1000px;width:24px;cursor:default;background-color:{pigcms{$config.app_footer_bgcolor};"/>
							<a href="javascript:void(0);" id="choose_color_box_bgcolor" style="line-height:28px;">点击选择颜色</a>
						</div>
					</div>
					<div class="group-content">导航条整个背景颜色。方便配合页面做一些节假日的样式调整。例如过年就红色等</div>
				</div>
			</div>

			<div class="page-style-wrapper">
				<div class="page-style flex " style="background:{pigcms{$config.app_footer_bgcolor};color:{pigcms{$config.app_footer_txtcolor};">
					<volist name="footer_menu_list" id="vo">
						<if condition="$i eq 1">
							<div class="item selected flex_one" style="color:{pigcms{$config.app_footer_txtactivecolor};">
						<else/>
							<div class="item flex_one">
						</if>
							<div class="img "><img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$vo.pic_path}"/></div>
							<div class="txt">{pigcms{$vo.name}</div>
						</div>
					</volist>
				</div>

				<div class="btn-box"><button data-url="{pigcms{:U('Viewpage/footer')}">刷新</button>(如果修改后底部导航样式未改变，请点击此刷新按钮)</div>
			</div>
			
		</div>
	</div>
	<style>
		.flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; display: box; align-items: center;  }
		.flex_one{flex: 1;-webkit-box-flex: 1;-webkit-flex: 1;}

		.page-style-wrapper{
			float: left;
			margin: -210px auto auto 650px;
		}
		.page-style{
			width: 375px;
			height: 50px;
			background: #FAFAFC;
			color: #777777;
			overflow:hidden;
			box-shadow: 0 0 10px 2px #ddd;
		}

		@media screen and (max-width: 1050px) {
		    .page-style-wrapper{
		    	float: none;
		    	margin: 20px;
		    }
		}

		.page-style .item{
			text-align: center;
			box-sizing: border-box;
			cursor: pointer;
		}

		.page-style .img{
			width: 28px;
			height: 28px;
			background: #fff;
			margin: 4px auto 0;
			box-sizing: border-box;
		}
		.page-style .img img{
			width: 100%;
			height: 100%;
		}

		.page-style-wrapper .btn-box{
			margin: 20px 0;
			color: #999;
			font-size: 14px;
		}

		.page-style-wrapper .btn-box button {
			background-color: #438eb9;
		    border-radius: 3px;
		    border: none;
		    padding: 6px 12px;
		    color: white;
		    cursor: pointer;
		    outline: none;
		    display: inline-block;
		    margin-right: 10px;
		}

		.page-style-wrapper .btn-box button:active{
			background: #05679f;
		}

		.fitment_div {
			width: 520px;
			margin-left: 20px;
		}
		.form-group {
			margin-bottom: 10px;
			border-bottom: 1px solid #EEE;
			padding: 10px 0;
		}
		.form-group:last-child{
			border-bottom:none;
		}
		.group-title {
			position: relative;
			font-size: 16px;
			color: black;
			margin-bottom: 10px;
		}
		.group-content {
			width: 380px;
			line-height: 1.3;
			letter-spacing: 0.5px;
			font-size: 12px;
			color: #999;
		}
		.group-title .btn.fitment {
			background-color: #438eb9!important;
			border-radius: 3px;
			border: none;
			position: absolute;
			right: 0px;
			top: 0px;
			padding:0 12px;
			color:white;
			cursor:pointer;
			outline:0;
			-webkit-user-select: none;
			-webkit-touch-callout: none;
		}
		.group-title .switch-off,.group-title .switch-on{
			position: absolute!important;
			right: 0px;
			top: 0px;
		}
		.group-title .right_change{
			position: absolute!important;
			right: 0px;
			top: 0px;
		}
		.group-title .right_div{
			position: absolute!important;
			right: 0px;
			top: 0px;
			font-size:12px;
		}
	</style>
	<script type="text/javascript" charset="utf-8">

		$(function(){
			$("#choose_color_box_bgcolor, #config_bgcolor").click(function(){
				change_bgcolor();
			});

			$("#choose_color_box_txtactivecolor, #config_txtactivecolor").click(function(){
				change_txtactivecolor();
			});

			$("#choose_color_box_txtcolor, #config_txtcolor").click(function(){
				change_txtcolor();
			});
			
			
			$('.fitment').click(function(){
				var _this = $(this);
				window.top.artiframe(_this.data('url')+'&frame_show=true&from_page=viewpage',_this.siblings('.name').text(),1000,600,true,false,false,false,'viewpage',true);
			});

			$('.page-style .item').click(function(){
				$('.page-style .item').css('color', '').removeClass('selected');
				$(this).css('color', $('#config_txtactivecolor').val()).addClass('selected');
			})

			

			$('.page-style-wrapper .btn-box button').on('click', function(){
				window.location.href = $(this).data('url');
			})
		});
		
		function change_bgcolor(){
			window.showTopColorPanel("leftmenu_Viewpage_index",$("#choose_color_box_txtactivecolor").offset().top,$("#choose_color_box_txtactivecolor").height(),$("#choose_color_box_txtactivecolor").offset().left,"config_bgcolor",function(){
				var bgcolor = $('#config_bgcolor').val();
				$('.page-style').css('background-color', bgcolor);
				save_change_color('app_footer_bgcolor',bgcolor);
			});
		}
		
		function change_txtactivecolor(){
			window.showTopColorPanel("leftmenu_Viewpage_index",$("#choose_color_box_txtactivecolor").offset().top,$("#choose_color_box_txtactivecolor").height(),$("#choose_color_box_txtactivecolor").offset().left,"config_txtactivecolor",function(){
				var txtactivecolor = $('#config_txtactivecolor').val();
				$('.page-style .item.selected').css('color', txtactivecolor);
				save_change_color('app_footer_txtactivecolor',txtactivecolor);
			});
		}
		
		function change_txtcolor(){
			window.showTopColorPanel("leftmenu_Viewpage_index",$("#choose_color_box_txtcolor").offset().top,$("#choose_color_box_txtcolor").height(),$("#choose_color_box_txtcolor").offset().left,"config_txtcolor",function(){
				var txtcolor = $('#config_txtcolor').val();
				$('.page-style').css('color', txtcolor);
				save_change_color('app_footer_txtcolor',txtcolor);
			});
		}
		
		function save_change_color(name,color){
			$.post("{pigcms{:U('change_footer_color')}",{name:name,color:color},function(result){
				if(result.status != 1){
					layer.alert('切换失败',{
						offset: ($('body').height() - 150) / 2
					});
				}
			});
		}
		function showTopColorPanel(frameName,ttop,thei,tleft,txt,callFunc){
        //txtobj = $('#'+txt,window.frames[frameName].document);

        $('#HexColor').val($("#"+txt+"").val());
        $('#DisColor').css('background-color',$("#"+txt+"").val() +' !important;');

        $("#colorpanel").css({

            top:ttop + thei + 5,
            left:tleft,
            'z-index':'19999',
        });
        $("#colorpanel").show();
        $("#CT tr td").unbind("click").unbind("mouseover").mouseover(function(){
            var aaa=$(this).css("background-color");
            $("#DisColor").css("background-color",aaa);
            $("#HexColor").val(aaa);
        }).click(function(){
            var aaa=$(this).css("background-color");
            $("#"+txt+"").val(aaa);
            $("#"+txt+"").css("background-color",aaa);
            $("#colorpanel").hide();
            console.log(typeof callFunc);
            if(typeof callFunc == 'function'){
                callFunc(aaa);
            }
        });
        $("#_clean").unbind("click").click(function(){
            $("#colorpanel").hide();
            $("#"+frameName+"").val('');
            if(typeof callFunc == 'function'){
                callFunc();
            }
        });
        $("#_cclose").unbind("click").click(function(){$("#colorpanel").hide();}).css({"font-size":"12px","padding-left":"10px"});
        $('#HexColor').unbind("keypress").keypress(function(e){
            if(e.keyCode==13){
                $("#colorpanel").hide();
                $("#"+txt+"").val($(this).val());
                $("#"+txt+"").css("background-color",$(this).val()+' !important;');
                if(typeof callFunc == 'function'){
                    callFunc();
                }
            }
        }).css({'width':'90px'});
    }
	</script>
<include file="Public:footer"/>