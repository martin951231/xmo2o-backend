<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <meta http-equiv="Expires" content="-1">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Pragma" content="no-cache">
    <meta charset="utf-8">
    <title>选择景区</title>
    <link href="{pigcms{$static_path}scenic/css/css_whir.css" rel="stylesheet"/>
    <script src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
    <!--[if lte IE 9]>
    <script src="scripts/html5shiv.min.js"></script>
    <![endif]-->
</head>

<body>

<section class="search_top Choice_search">
    <div class="search_n clr">
        <em class="searchA"></em>
        <input type="text" name="keyword" id="keyword" placeholder="">
        <a href="javascript:void(0)" class="searchD">搜索</a>
    </div>
</section>
<div class="strip"></div>

<section class="Choice">
    <div class="Choice_end">
        <ul id="scenic_select">
            <if condition="$scenic_list">
                <volist name="scenic_list" id="vo">
                    <php>$pic = explode(';',$vo['scenic_pic']);$pic = str_replace(',','/',$pic[0]);</php>
                    <li class="clr" data-id="{pigcms{$vo.scenic_id}" id="scenic{pigcms{$vo.scenic_id}">
                        <div class="fl">
                            <div class="Choice_img fl">
                                <img src="{pigcms{:file_domain()}/upload/scenic/config/{pigcms{$pic}">
                            </div>
                            <div class="Choice_wz">
                                <h2>{pigcms{$vo.scenic_title}</h2>

                                <p>{pigcms{$now_city}，{pigcms{$vo.area_name}</p>
                            </div>
                        </div>
                        <em></em>
                    </li>
                </volist>
            </if>

        </ul>
    </div>
</section>

<section class="Plan">
    <a href="javascript:void(0)" id="start_arrange" onclick="start_arrange()">开始规划行程</a>
</section>

<section class="Plan_Up"><span><!-- 逍遥津-包公祠-岱山湖-包公祠... --></span><em></em></section>

<section class="Popup" style="display: none;">
    <div class="Popup_top">
        已选景点<em></em>
    </div>
    <div class="Roll">
        <ul>
        </ul>
    </div>
</section>
<div class="dmbj"></div>
<div id="pwd_bg" class="bg bg_style" style="height: 921px; display: none;">
</div>
<img class="bg" src="{pigcms{$static_path}images/loadh.gif"
     style="display: none;width:150px; height:150px; border-radius:50%; top: 30%;position: absolute;left: 32%;z-index: 9999;">
</body>


<script type="text/javascript">
	var tmp = [];
	var tmp2 = [];
    //选择人
    var scenic_id_list = '';
    $(".dmbj").height($(window).height())
    load();
    var scenic_list = [];
    function load() {

        $(".Choice_end li").each(function () {

            $(this).click(function () {

                if (!$(this).hasClass("on")) {
                    $(this).addClass("on");
                    $(".Plan_Up").show();
                    $(".Plan a").css("background", "#06c1ae")
                    scenic_list.push($(this).attr('data-id'));
                } else {
                    scenic_list.remove($(this).attr('data-id'));
					delete tmp[$(this).attr('data-id')];
					delete tmp2[$(this).attr('data-id')];
                    $(this).removeClass("on");

                }
                if (scenic_list.length==0) {
                    $(".Plan_Up").hide();
                    $(".Plan a").css("background", "#ccc")
                }

                var div = '';
                var text = '';
                $.each(scenic_list, function (index, val) {
					if(tmp[val]==''||typeof(tmp[val])=='undefined'){
						tmp[val]= "<li class='clr'><div class='fl'><h2>" + $('#scenic'+val).find("h2").text() + "</h2><p>" + $('#scenic'+val).find("p").text()+ "</p></div><a href='javascript:void(0)' class='fr' onclick=dell('" + $('#scenic'+val).find("h2").text() + "')></a></li>";
						tmp2[val]= "<i>" + $('#scenic'+val).find("h2").text() + "</i>";
					}
                });
				 $.each(tmp, function (index, val) {
					if(typeof(val)!='undefined'){
						div+=val;
						text+=tmp2[index];
					}
				 });
				 $(".Popup ul").html(div);
				 $(".Plan_Up span").html(text)
				
            })


        })

    }


    Array.prototype.indexOf = function (val) {
        for (var i = 0; i < this.length; i++) {
            if (this[i] == val) return i;
        }
        return -1;
    };

    Array.prototype.remove = function (val) {
        var index = this.indexOf(val);
        while (index > -1) {
            this.splice(index, 1);
            index = this.indexOf(val);
        }
    };


    $(".Plan_Up").click(function () {
        $(this).hide();
        $(".Popup").show();
        $(".dmbj").show();
    })
    $(".Popup_top").click(function () {
        $(".Popup").hide();
        $(".Plan_Up").show();
        $(".dmbj").hide();
        if ($(this).siblings(".Roll").find("li").length < 1) {
            $(".Plan_Up").hide();
            $(".Plan a").css("background", "#ccc")
        }
    })

    function dell(up) {
        $(".Popup li").each(function () {
            var name = $(this).find("h2").text();
            if (up == name) {
                $(this).slideUp(function () {
                    $(this).remove();
                });
            }
        })
        $(".Choice_end li").each(function () {
            var sName = $(this).find("h2").text();
            if (up == sName) {
                $(this).removeClass("on");
            }
        })
        $(".Plan_Up span i").each(function () {
            var sName = $(this).text();
            if (up == sName) {
                $(this).slideUp(function () {
                    $(this).remove();
                });
            }
        })

        scenic_id_list = '';
        $(".Choice_end li.on").each(function () {

            scenic_id_list += $(this).attr('data-id') + ',';

        })

    }
	
	function sortNumber(a, b){
		return a - b
	}


    function start_arrange() {
		scenic_list.sort(sortNumber);
        scenic_id_list=scenic_list.toString();

        $.ajax({
			url: '{pigcms{:U('Scenic_travel_plan/arrange_travel')}',
			type: 'POST',
			dataType: 'json',
			data: {scenic_id_list: scenic_id_list},
			beforeSend:function(){
				$('.bg').show();
			},
			success:function(data){
				if(data.error_code){
                    $('.bg').hide();
					alert(data.msg);
				}else{
					location.href = "{pigcms{:U('Scenic_travel_plan/travel_plan_detial')}&id="+data.id;
				}
			}
        });
    }

    $('.searchD').click(function () {
        var keyword = $('#keyword').val();

        $.ajax({
			url: '{pigcms{:U('Scenic_travel_plan/ajax_search_scenic')}',
            type:'POST',
            dataType:'json',
            data:{keyword: keyword},
			beforeSend:function () {
				$('.bg').show();
			},
			success:function (data) {
            $('.bg').hide();
            if (data.error_code) {
                alert(data.msg);
                window.location.reload();
            } else {

                var str = '';
                $.each(data.scenic_list, function (index, val) {
                    var pic = val.scenic_pic.split(';');
					console.log(scenic_list.indexOf(val.scenic_id))
					if(scenic_list.indexOf(val.scenic_id)!=-1){
						
						str += '<li class="clr on" data-id = "' + val.scenic_id + '" id="scenic'+val.scenic_id+'">';
					}else{						
						str += '<li class="clr" data-id = "' + val.scenic_id + '" id="scenic'+val.scenic_id+'">';
					}
                    str += '<div class="fl"><div class="Choice_img fl">';
                    str += '<img src="{pigcms{:file_domain()}/upload/scenic/config/' + pic[0].replace(',', '/') + '">';
                    str += '</div><div class="Choice_wz">';
                    str += '<h2>' + val.scenic_title + '</h2>';
                    str += '<p>{pigcms{$now_city}，' + val.area_name + '</p>';
                    str += '</div></div><em></em></li>';
                });

                $('#scenic_select').html(str);
                load();
            }
        }
    })
        ;
    });

</script>


</html>







