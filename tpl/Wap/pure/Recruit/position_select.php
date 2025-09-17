<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name='apple-touch-fullscreen' content='yes'/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="format-detection" content="address=no"/>
    <title>选择职位</title> 
     <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}/recruit/css/workIcon.css"> 
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
	<script>
	var currency_symbol = "{pigcms{$config.Currency_symbol}";
	</script>
    <script src="{pigcms{$static_path}recruit/js/base.js?133" ></script>
    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/simplite/simplite.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}/recruit/js/validator.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script> 

    <style>
        body {
            font-size: 14px;
        }
        .rel{position:relative}
        a ,a:visited,a:hover{color:#999}
        .weui-cell:active,.weui-cell:visited{    background: #fff;}
        .weui-cells {font-size: 16px;}
        .flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; align-items: center;  }
        .flex_one{  flex:1;  -webkit-box-flex: 1;  -webkit-flex: 1;  }
        .cursor{cursor:pointer}
        .allPosition .weui-cells{margin-top:0px}
        .allPosition .right .weui-cell{    background: rgba(238,238,238,0.4);}
        .allPosition .left .weui-cell i{margin-right:-10px;color:#ddd}
        .allPosition .flex_one{overflow-y: scroll;height:100%}
        .weui-cell.activited{
            background: rgba(238,238,238,0.4);
        }
        .allPosition .left .weui-cell i{display:none}
        .allPosition .left .weui-cell.activited i{display:block}
        .allPosition .right .weui-cell:active {background-color: #ECECEC;}
    </style>
</head>
<body>
<!---选择职位--->
   <div class="position" > 
        <div>
            <div class="positionList">
                <div class="flex allPosition">
                    <div class="flex_one left">
                        <div class="weui-cells">
                            <volist name="category" id="vo">
                                 <div class="weui-cell <if condition='$key eq 0'>activited </if>" data-id="{pigcms{$vo.id}">
                                    <div class="weui-cell__bd">
                                        <p>{pigcms{$vo.name}</p>
                                    </div>
                                    <i class="iconfont">&#xe646;</i>
                                </div>
                            </volist>
                        </div>
                    </div>
                    <div class="flex_one right">
                        <div class="weui-cells">
                            <if condition="category[0]['sub']">
                            <volist name="category[0]['sub']" id="vo">
                                 <div class="weui-cell " data-id="{pigcms{$vo.id}" > 
                                    <div class="weui-cell__bd">
                                        <p data-id="{pigcms{$vo.id}" class="openUrl newWin" data-url="{pigcms{:U('position_edit')}">{pigcms{$vo.name}</p>
                                    </div>
                                </div>
                            </volist>
                            <else/>
                            暂无数据
                            </if>
                        </div>
                    </div>
                </div>
            </div>
            <div class="searchs" style="display:none">
                <div class="flex allPosition ">
                    <div class="flex_one">

                    </div>
                </div>
            </div>

        </div>
   </div>

    <script src="{pigcms{$static_path}js/jquery.cookie.js" type="text/javascript" charset="UTF-8"></script>
   <script type="text/html" id="rightPositions">
        <& for (var i = 0; i < obj.data.length; i++) { &>
        <div class="weui-cell" data-id="<&= obj.data[i].id &>">
            <div class="weui-cell__bd">
                <p data-id="<&= obj.data[i].id &>"><&= obj.data[i].name &></p>
            </div>
        </div>
        <& } &>
    </script>

   <script type="text/html" id="searchPositions">
        <div class="weui-cell">
            <div class="weui-cell__bd">
                <p><&= obj.content &></p>
            </div>
            <div class="weui-cell__ft">销售</div>
        </div>
    </script>
   <script>
    var type = '{pigcms{$_GET['type']}';
    var position_edit_url = "{pigcms{:U('position_edit')}";
    var View={
        init:function(){
            this.initFresh();
            this.initInput();
            this.initClick()
        },
        settings:{
            img:[],
            memory_info:[],
            window_w:375
        },
        initFresh:function(){
            this.settings.window_w=$(window).width();
            $(".allPosition").css("height",$(window).height()-50+"px");
            $(".weui-search-bar__form").on("click",function(){
                $(".positionList").hide();
                $(".searchs").show()
            })
            $(".weui-search-bar__cancel-btn").on("click",function(){
                $(".positionList").show();
                $(".searchs").hide()
            })
        },
        initInput:function(){
            var bind_name = 'input';
            if (navigator.userAgent.indexOf("MSIE") != -1){ bind_name = 'propertychange' }
            $('#searchBars').bind(bind_name,'#searchPosition' ,function(){
                var content=$.trim($('#searchPosition').val());
                if(content!=''){
                    var cat_data = Simplite.getTemplate('searchPositions');
                    var cat_all = Simplite.compile(cat_data)({'data':data} );
                    $('.searchs .flex_one').append(cat_all);
                }
            })
        },
        initClick:function(){
            // 父分类点击
            $(document).on("click",".allPosition .left .weui-cell",function(){
                $(".allPosition .left .weui-cell").removeClass("activited");
                $(this).addClass("activited");
                //获取子分类
                var fid = $(this).data('id');
                $.post('{pigcms{:U("ajax_get_subcategory")}',{fid:fid},function(data){
                    if(data.length>0){
                        var cat_data = Simplite.getTemplate('rightPositions');
                        var cat_all = Simplite.compile(cat_data)({'data':data} );
                        $('.allPosition .right .weui-cells').html(cat_all);
                    }else{
                        $('.allPosition .right .weui-cells').html('暂无数据');
                    }
                },'json')
            
            })
            // 子分类
            $(document).on("click","  .allPosition .right .weui-cell",function(){
                var position = {
                    id:$(this).find("p").data('id'),
                    name:$(this).find("p").html()
                };
                sessionStorage.setItem("selectPosition",JSON.stringify(position));
                $.cookie("selectPosition", JSON.stringify(position));
               
                key = 'user_address_'+type;
                info = $.cookie(key);
                if (typeof(info)!='undefined') { 
                    info = JSON.parse(info);
                    if(!info){
                        info = {};
                    } 
                    info.position_cat_id = $(this).data('id');
                    info.position_name = $(this).find("p").html();
                    $.cookie(key, JSON.stringify(info));
                }
                if (type == 'position') {
                    // 职位 
                    // $(this).attr('data-url',position_edit_url);  
                    window.location.href = position_edit_url;
                }else{
                    history.back()
                }
            })

        }
    }
    window.View=View;
    View.init();
</script> 
<!-- <div class="share_html">{pigcms{$shareScript}</div> -->
</body>
</html>