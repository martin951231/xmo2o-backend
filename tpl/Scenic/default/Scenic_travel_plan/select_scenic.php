<include file="Public/header"/>
<include file="Public/header_city"/>
	<!--导航-->
	<section class="navigation">
		<div class="w1200">
			<ul class="clr nav_top">
				<li>
					<a href="{pigcms{:U('Index/index')}">网站首页</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_list/index')}">景点门票</a>
				</li>
				<li class="pull on">
					<a href="{pigcms{:U('Scenic_travel_plan/index')}">行程规划</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_mate/index')}">寻找游伴</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_aguide/index')}">当地向导</a>
				</li>
			</ul>
		</div>
	</section>
	<!--小导航-->
	<section class="ticklist">
		<div class="w1200">
			<div class="tklist_top">
				<span>您当前位置：</span><a href="{pigcms{:U('index')}">行程规划</a> &gt; <span class="now">按景点规划行程</span>
			</div>
		</div>
	</section>
        <section class="trip_list">
            <div class="w1200 clr">
                <div class="fl w952">
                    <div class="trip_search" id="search_guide">
                        <input type="text" id="search_v" placeholder="请输入景点名称或关键词">
                        <a href="javascript:void(0)" id="search">搜索</a>
                    </div>
                    <div class="tripa">
                        <ul id="newList">
	                        <!--<li>
	                            <a href="#" class="clr">
	                                <div class="img fl">
	                                    <img src="{pigcms{$vo['scenic_pics']}"  width="210" height="117">
	                                </div>
	                                <div class="p235">
	                                    <h2>{pigcms{$vo['scenic_title']}</h2>
	                                    <p>{pigcms{$vo['scenic_title']}</p>
	                                    <div class="address">{pigcms{$now_city}-{pigcms{$vo['area_name']}</div>
	                                </div>
	                            </a>
	                            <div class="click"></div>
	                        </li>-->
                        </ul>
                    </div>
                    <div id="list"></div>
                    <div class="pages">
                    	<span class="num"></span>
                    </div>
                </div>
                <div class="fr w226">
                    <h5>所选景点 ( <i id="scenic_number">0</i> )</h5>
                    <div class="choose_list">
                        <div class="ul_n">
                            <ul>
                            	<!--<li>
                                    <i>1</i>
                                    <h2>合肥欢乐谷</h2>
                                    <a href="javascript:void(0)"></a>
                                </li>
                                <li>
                                    <i>2</i>
                                    <h2>合肥欢乐谷</h2>
                                    <a href="javascript:void(0)"></a>
                                </li>
                                <li>
                                    <i>3</i>
                                    <h2>合肥欢乐谷</h2>
                                    <a href="javascript:void(0)"></a>
                                </li>
                                <li>
                                    <i>4</i>
                                    <h2>合肥欢乐谷</h2>
                                    <a href="javascript:void(0)"></a>
                                </li>-->
                            </ul>
                            <div class="hx"></div>
                        </div>
                    </div>
                    <a href="javascript:void(0)" id="submit"><div class="start">开始规划行程</div></a>
                </div>
            </div>
        </section>
<include file="Public/footer"/>
<script type="text/javascript">
	//右侧定位
	$(window).scroll(function(){
		if ($(window).scrollTop() > $(".trip_list").offset().top){
			$(".trip_list .w226").addClass("w226_on");
		}else{
			$(".trip_list .w226").removeClass("w226_on");
		}
	})
	var user_info	=	"{pigcms{$_SESSION['user']}";
	//所选景点
	var number = 0;
	var page = 1;
	var generate_status	=	true;
	var now_city	=	"{pigcms{$now_city}";

	scenic_list();
	$("#search").click(function(){
		scenic_list(1);
	})
	$("#search_guide").keydown(function(event){
		if (event.keyCode == "13") {//keyCode=13是回车键
			scenic_list(1);
		}
	});
	$("#submit").click(function(){
		if(!user_info){
			$(".board").show();
			$(".mark").show();
			return false;
		}
		var li_lists='';
		if($(".ul_n ul li").length > 0){
			$(".ul_n ul li").each(function(){
				li_lists	+=	+this.id+',';
			})
//			li_lists.substring(0,-1);
	    }else{
			alert('您未选择景区');
			return false;
	    }
	    if(generate_status == true){
			generate_status = false;
			$.ajax({
				type : "post",
				url : "{pigcms{:U('Scenic_travel_plan/arrange_travel')}",
				dataType : "json",
				data:{
					scenic_id_list	:	li_lists,
				},
				success : function(result){
					if(result.error_code == 0){
						alert(result.msg);
						window.location.href = "{pigcms{:U('Scenic_travel_plan/travel_plan_detial')}&sor=2&id="+result.id;
					}else{
						alert(result.msg);
						generate_status	=	true;
					}
				},
				error:function(){
					alert('规划行程失败');
					generate_status	=	true;
				}
			})
	    }
	})
	function scenic_list(search_number){
		if(search_number == 1){
			var search_v	=	$("#search_v").val();
			page = 1;
		}
		$.ajax({
			type : "post",
			url : "{pigcms{:U('select_scenic_json')}",
			dataType : "json",
			data:{
				page	:	page,
				search_v	:	search_v,
			},
			async:false,
			success : function(result){
				var list	=	'';
				if(result.errorCode == 0){
					var data	=	result.result;
					var list_length	=	data.length;
					var li_list	=	'';
					var li_lists	=	'';
					for(var i=0;i<list_length;i++){
						list	+=	'<li>';
	                    list	+=	'    <a target=_blank href="'+data[i].scenic_url+'" class="clr">';
	                    list	+=	'        <div class="img fl">';
	                    list	+=	'            <img src="'+data[i].scenic_pics+'"  width="210" height="117" />';
	                    list	+=	'        </div>';
	                    list	+=	'        <div class="p235">';
	                    list	+=	'            <h2>'+data[i].scenic_title+'</h2>';
	                    list	+=	'            <p>'+data[i].scenic_brief+'</p>';
	                    list	+=	'            <div class="address">'+now_city+'-'+data[i].area_name+'</div>';
	                    list	+=	'        </div>';
	                    list	+=	'    </a>';
	                    if($(".ul_n ul li").length > 0){
							$(".ul_n ul li").each(function(){
								if(data[i].scenic_id == this.id){
									li_lists	=	'<div id="'+data[i].scenic_id+'_s" onclick="show('+data[i].scenic_id + ',\' '+data[i].scenic_title+' \' )" class="click on"></div>';
									return false;
								}
							})
	                    }
						if(li_lists){
							list	+=	li_lists;
							li_lists	=	'';
						}else{
							list	+=	'<div id="'+data[i].scenic_id+'_s" onclick="show('+data[i].scenic_id + ',\' '+data[i].scenic_title+' \' )" class="click"></div>';
						}
	                    list	+=	'</li>';
					}
					if(list_length < 10){
						// 删除加载更多
						$('#list').remove();
					}
					if(page == 1){
						$("#newList").html(list);
					}else{
						$("#newList").append(list);
					}
					page++;
				}else{
					alert(result.errorMsg);
				}
			},
			error:function(){
				alert('网络错误，请稍后重试！');
			}
		})
	}
	function rech(){
        $(".hx").each(function(){
            $(this).height($(".choose_list ul").height()-$(".choose_list ul li:last-child").height());
        })
    }
	function show(scenic_id,title){
		if(!$('#'+scenic_id+'_s').hasClass("on")){
			$('#'+scenic_id+'_s').addClass("on");
			var ul_leng	=	$(".ul_n ul li").length;
			if(ul_leng != 0){
				ul_leng = ul_leng+1;
				$(".ul_n ul").append("<li id='"+scenic_id+"'><i>"+ul_leng+"</i><h2>"+title+"</h2><a href='javascript:void(0)' onclick='remove("+scenic_id+")'></a></li>");
				$("#scenic_number").text(ul_leng);
				rech();
			}else{
				ul_leng = 1;
				$(".ul_n ul").html("<li id='"+scenic_id+"'><i>"+ul_leng+"</i><h2>"+title+"</h2><a href='javascript:void(0)' onclick='remove("+scenic_id+")'></a></li>");
				$("#scenic_number").text(ul_leng);
				rech();
			}
		}else{
			remove(scenic_id);
			$('#'+scenic_id+'_s').removeClass("on");
			rech();
		}
	}
	function remove(number){
		var li_number	=	0;
		var scenic_list	=	'';
		$(".ul_n ul li").each(function(){
			if(number != this.id){
				li_number	=	li_number+1;
				scenic_list	+=	"<li id='"+this.id+"'><i>"+li_number+"</i><h2>"+$(this).children("h2").text()+"</h2><a href='javascript:void(0)' onclick='remove("+this.id+")'></a></li>";
			}else{
				$('#'+this.id+'_s').removeClass("on");
			}
		})
		$(".ul_n ul").html(scenic_list);
		$("#scenic_number").text(li_number);
		rech();
	}
</script>