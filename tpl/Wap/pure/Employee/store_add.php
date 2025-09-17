

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>添加店铺</title>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/weui.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/custom.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/common.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/icon.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/mobileSelect.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/layer.css"/>
    <script src="{pigcms{$static_path}employee/js/common/jquery-2.1.4.js" type="text/javascript"></script>
    <script src="{pigcms{$static_path}employee/js/common/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <script>

    </script>
    <style>
        body{background:#fff}
        .weui-cells:after{content:none !important;}
        .ui-border-b:after {  content: " ";  position: absolute;  left: 0;  bottom: 0;  right: 0;  height: 1px;  border-top: 1px solid #e5e5e5;  color: #e5e5e5;  -webkit-transform-origin: 0 0;  transform-origin: 0 0;  -webkit-transform: scaleY(0.5);  transform: scaleY(0.5); z-index: 2;  }
        .none{display:none}
        .color_main{color:#04CB86}
        .iconfont{font-size:20px}
        .bo_ft:after {  height: 12px !important;  width: 12px !important;  border-width: 0px 2px 2px 0px !important;  margin-top: -10px !important;  }
        .bottoms {  width: 100%;  position: fixed;  bottom: 0px;z-index:5  }
        .bottoms div {  background: #fff;  padding: 5px 10px;  }
        .weui-search-bar{
            background-color: #04CB86!important;
            height: 42px;
            line-height: 42px;
            position: fixed;
            top: 0px;
            width: 100%;
        }
        .one_left{
            padding-left: 5px;
            margin-right: 15px;
            line-height: 28px;
            color: #fff;
            white-space: nowrap;
        }
        .weui-search-bar__box{
            line-height: 22px;
        }
        .close_map .dot {
            display: none;
            position: absolute;
            left: 50%;
            top: 50%;
            width: 19px;
            height: 25px;
            margin-top: -25px;
            margin-left: -10px;
            background: url(static/activity/assets/image/marker_red_sprite_ec1a2a9.png) 0 0 no-repeat;
        }
        #searchMore .weui-cells_radio {
            overflow-y: scroll;
            margin-top: 0px !important;
            background: #fff;
            position:fixed;
            top:292px;
            width:100%
        }
        .weui-cells_radio .weui-icon-checked:before {
            content: '\EA01';
            color: #c9c9c9;
            font-size: 23px;
            display: block;
        }
        .weui-cells_radio .weui-check:checked+.weui-icon-checked:before {
            display: block;
            color: #09bb07;
            font-size: 23px;
            content: '\EA06';
        }
        .pt_45{position:fixed;top:42px;width:100%}
        .weui-btn_primary:not(.weui-btn_disabled):visited , .weui-btn_primary:not(.weui-btn_disabled):hover{
            color: #FFFFFF !important;
        }
        .weui-textarea{
            max-height: 70px;
            padding: 0px 5px 1px 3px;
            color: #000;
            min-height: 35px;
            width: 94%;
            font-size: 15px;
            border: 1px solid rgba(0, 0, 0, .2);
        }
        .ui-border-b:before {
            content: " ";
            position: absolute;
            left: 0;
            bottom: 0;
            right: 0;
            height: 1px;
            border-top: 1px solid #e5e5e5;
            color: #e5e5e5;
            -webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            -webkit-transform: scaleY(0.5);
            transform: scaleY(0.5);
            left: 15px;
            z-index: 2;
        }
        .weui-icon-clear:before {
            content: none;
        }
    </style>
</head>
<body class="pb0">
    <form action="{pigcms{:U('Employee/store_modify')}"  method="post" target="#" id='form' >
        <input   type="text" name="lat" value="" class="hidden"/>
        <input   type="text" name="long" value="" class="hidden"/>
       <div id="ui_left" class="">
        <if condition="!$is_weixin_browser && !$is_app_browser">
            <div class="weui-pull-to-refresh__layer" style="    height: 24px;">
            </div>
            <header class="x_header bgcolor_11 cl f15" >
                <a class="z f14" href="javascript:window.history.go(-1);"><i class="iconfont icon-fanhuijiantou w15"></i>返回</a>
                <div class="navtitle">添加店铺</div>
            </header>
        </if>
        <div class="weui-cells weui-cells_form font_size14" style="margin-top:0px;padding-bottom:70px">
            <div class="weui-cell">
                <div class="weui-cell__hd"><label class="weui-label ">店铺名称</label></div>
                <div class="weui-cell__bd">
                    <input class="weui-input" type="text" name="name"  placeholder="请输入店铺名称" />
                </div>
            </div>
            <div class="weui-cell ">
                <div class="weui-cell__hd">
                    <label class="weui-label">店铺描述</label>
                </div>
                <div class="weui-cell__bd">
                    <textarea class="weui-textarea" rows="5" cols="5"  placeholder="请输入店铺描述" name="txt_info" id="txt_info"></textarea>

                </div>
            </div>
            <div class="weui-cell ">
                <div class="weui-cell__hd">
                    <label class="weui-label">联系电话</label>
                </div>
                <div class="weui-cell__bd">
                    <input class="weui-input" type="tel" name="phone" placeholder="请输入联系电话" />
                </div>
            </div>

            <div class="openTime weui-cells " style="margin-top:0px">
                <div class="weui-cell ">
                    <div class="weui-cell__hd">
                        <label class="weui-label">营业时间</label>
                    </div>
                    <div class="weui-cell__bd align_left" id="work1">
                        <span>开始时间</span>
                        <div class=" font_b pl10"><span class="times">00:00</span></div>
                        <input   type="text" name="open_1" value="" class="hidden open"/>

                    </div>
                    <div class="">
                        至
                    </div>
                    <div class="weui-cell__bd align_right " id="work2">
                        <span>结束时间</span>
                        <div class=" font_b pr10"><span class="times">00:00</span></div>
                        <input   type="text" name="close_1" value="" class="hidden close"/>
                    </div>
                    <div class="weui-cell__bd color_main align_center">
                        <span class="icon iconfont cursor addTime" >&#xe6a9;</span>
                    </div>
                </div>

            </div>

            <div class="weui-cell weui-cell_access">
                <div class="weui-cell__hd">
                    <label class="weui-label">店铺分类</label>
                </div>
                <div class="weui-cell__bd" id="trigger2">
                    <input class="weui-input" type="text" placeholder="请选择店铺分类"  readonly="readonly"/>
                    <input   type="text" name="cat_fid" value="" class="hidden"/>
                    <input   type="text" name="cat_id" value="" class="hidden"/>
                </div>
                <span class="weui-cell__ft bo_ft"></span>
            </div>
            <div class="weui-cell weui-cell_access">
                <div class="weui-cell__hd">
                    <label class="weui-label">店铺所在地</label>
                </div>
                <div class="weui-cell__bd" id="trigger3">
                    <input class="weui-input" type="text" placeholder="请选择店铺所在地" readonly="readonly" />
                    <input  type="text"  name="circle_id" class="hidden"/>
                </div>
                <span class="weui-cell__ft bo_ft"></span>
            </div>
            <div class="weui-cell weui-cell_access none" id="groupTeam">

            </div>
            <div class="weui-cell weui-cell_access" id="new_address">
                <div class="weui-cell__hd" style=""><label class="weui-label">店铺地址</label></div>
                <div class="weui-media-box__bd weui-cell__ft show_map" style=" flex: 1;text-align: right;">
                    <h4 class="flex weui-media-box__title" style="text-align: left;color:#999;white-space: normal;font-size:14px">
                        <span>请选择店铺地址</span>
                        <img src="{pigcms{$static_path}employee/images/7-1_03.png" style="width:15px" class="pl10" />
                    </h4>
                    <input   type="text" name="long_lat" value="" class="hidden"/>
                </div>
            </div>
            <div class="weui-cell ui-border-b">
                <div class="weui-cell__hd">
                    <label class="weui-label">店铺详细地址</label>
                </div>
                <div class="weui-cell__bd">
                    <textarea class="weui-textarea" rows="5" cols="5" placeholder="请输入店铺详细地址" name="adress"  id="textarea"  ></textarea>

                </div>
            </div>
        </div>
        <div  class="bottoms">
            <div>
                <a href="javascript:;" class="weui-btn weui-btn_primary submit">保存</a>
            </div>

        </div>
    </div>
    </form>
    <div id="ui_right" style="display:none;">
        <div id="map_baidu" >
            <div class="map_header">
                <div class="weui-search-bar weui-search-bar_focusing flex" id="searchBar">
                    <a class="flex one_left" href="javascript:window.history.go(-1);" ><i class="iconfont icon-fanhuijiantou w15"></i>返回</a>
                    <form class="weui-search-bar__form flex_one" action="#" onsubmit="View.loadData()" target="frameFile" >
                        <div class="weui-search-bar__box">
                            <i class="weui-icon-search"></i>
                            <input type="search" class="weui-search-bar__input" id="searchInput" placeholder="搜索" required="">
                            <a href="javascript:" class="weui-icon-clear" id="searchClear"></a>
                            <iframe name="frameFile" style="display:none"></iframe>
                        </div>
                        <label class="weui-search-bar__label" id="searchText" style="transform-origin: 0px 0px 0px; opacity: 1; transform: scale(1, 1);">
                            <i class="weui-icon-search"></i>
                            <span>搜索</span>
                        </label>
                    </form>
                    <a href="javascript:" class="" id="searchCancel" style="color:#fff;padding-left: 10px;">确定</a>
                </div>
                <div class='pt_45'>
                    <div class="close_map" >
                        <div  style="height:250px;position:relative" >
                            <div id="map_show" style="height:100%"></div>
                            <div class="dot" style="display:block;"></div>
                        </div>
                        <div id="searchMore" >
                            <div class="weui-cells weui-cells_radio">

                            </div>
                        </div>
                    </div>
                    <div id="searchAdd" style="display:none;overflow-y: auto;"></div>
                </div>

            </div>

        </div>

    </div>
    <div id="layermbox0" class="layermbox layermbox0 layermshow none" index="0">
    <div class="laymshade"></div>
    <div class="layermmain">
        <div class="section">
            <div class="layermchild layermanim">
                <div class="layermcont"></div>
            </div>
        </div>
    </div>
</div>
</body>
<script type="text/html" id="clone">
    <input   type="text" name="<&= _this.open?'open_2':'close_2'&>" value="<&= _this.value&>" class="hidden <&= _this.open?'open':'close'&>"/>
</script>
<script type="text/html" id="group">
    <div class="weui-cell__hd">
          <label class="weui-label">店铺所在商圈</label>
     </div>
     <div class="weui-cell__bd" id="trigger4">
        <input class="weui-input" type="text" placeholder="请选择店铺所在商圈"   readonly="readonly"/>
      </div>
     <span class="weui-cell__ft bo_ft"></span>
</script>
<script type="text/html" id="time">
    <div class="weui-cell ">
        <div class="weui-cell__hd">
            <label class="weui-label"></label>
        </div>
        <div class="weui-cell__bd align_left"  id="<&= View.showId(true)&>">
            <span>开始时间</span>
            <div class=" font_b pl10 "><span class="times">00:00</span></div>
            <input   type="text" name="open_<&= _this.index&>" value="" class="hidden open"/>

        </div>
        <div class="">
            至
        </div>
        <div class="weui-cell__bd align_right" id="<&= View.showId(false)&>">
            <span>结束时间</span>
            <div class=" font_b pr10 "><span class="times">00:00</span></div>
            <input   type="text" name="close_<&= _this.index&>" value="" class="hidden close"/>
        </div>
        <div class="weui-cell__bd color_main align_center">
            <span class="icon iconfont cursor delTime" >&#xe624;</span>
        </div>
    </div>
</script>
<script type="text/html" id="map_adress">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var _item=_this[i] &>
    <label class="weui-cell weui-check__label" for="x<&= _item.point.x&>" data-long="<&= _item.point.x&>" data-lat="<&= _item.point.y&> " data-city="<&= _item.city&> ">
        <div class="weui-media-box__bd" style="flex:1">
            <h4 class="weui-media-box__title"><&= _item.name&></h4>
            <p class="weui-media-box__desc"><&= _item.addr&></p>
        </div>
        <div class="weui-cell__ft">
            <input type="radio" class="weui-check" name="radio1" id="x<&= _item.point.x&>">
            <span class="weui-icon-checked"></span>
        </div>
    </label>
    <&}&>
</script>
<script type="text/html" id="map_adress1">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var _item=_this[i] &>
    <label class="weui-cell weui-check__label"  data-long="<&= _item.long&>" data-lat="<&= _item.lat&> " >
        <div class="weui-media-box__bd" style="flex:1">
            <h4 class="weui-media-box__title"><&= _item.address&></h4>
            <p class="weui-media-box__desc"><&= _item.name&></p>
        </div>
    </label>
    <&}&>
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
<script src="{pigcms{$static_path}js/layer.m.js" type="text/javascript"></script>
<script src="{pigcms{$static_path}employee/js/common/simplite.js" type="text/javascript" charset="UTF-8"></script>
<script src="{pigcms{$static_path}employee/js/common/employee_common.js" type="text/javascript" charset="UTF-8"></script>
<script src="{pigcms{$static_path}employee/js/common/mobileSelect.js" type="text/javascript" charset="UTF-8"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-form/jquery-plugin-form.js"></script>
<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
<script type="text/javascript">
    var View={
        init:function(){
            this.addopenTime();
            this.initRefresh();
            this.checkClick();
            this.submit()
        },
        settings: {
            check_lat:"",
            check_lon:'',
            check_adress1:'',
            check_adress:'',
            bindname:navigator.userAgent.indexOf("MSIE") != -1?'propertychange':'input',
            is_check_map:false,
            lngs:'',
            lats:'',
            map:null,
            chackchange:true,
            hashchange:0,
            store:[],
            times:['00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23'],
            fen:[],
            index:0,
            id:[{
                e1:"work1",
                e2:"work2"
            }]
        },
        showId:function(type){
            var self=this;
            if(type){
                return  self.settings.id[self.settings.id.length-1].e1
            }else{
                return  self.settings.id[self.settings.id.length-1].e2
            }
        },
        addopenTime:function(){
            $.showLoading('加载中')
            var self=this;
            for(var i=0;i<60;i++){
                self.settings.fen.push(i<10?'0'+i:i)
            }
            self.initTime()
        },
        initTime:function(){
            var self=this;
            var list=self.settings.id[self.settings.id.length-1];
            for(var i in list){
                new MobileSelect({
                    trigger: '#'+list[i],
                    title: '营业时间',
                    wheels: [
                        {data:  self.settings.times},
                        {data:  self.settings.fen}
                    ],
                    position:[1, 2],
                    transitionEnd:function(indexArr, data){
                    },
                    callback:function(indexArr, data,$_id){
                        $_id.querySelector('.times').innerHTML=data[0]+':'+data[1]
                        $_id.querySelector('input').value=data[0]+':'+data[1]
                    }
                });
            }

        },
        initRefresh: function() {
            var self=this;
            var list='{pigcms{$info['catList']|json_encode}';
            self.settings.store = eval('(' + list + ')');
            //店铺分类
            var mobileSelect2 = new MobileSelect({
                trigger: '#trigger2',
                title: '店铺分类',
                keyMap:{
                    id:'value',
                    value:'text',
                    childs:'children'
                },
                wheels: [
                    {data:self.settings.store}
                ],
                transitionEnd:function(indexArr, data){
                    console.log(data);
                },
                callback:function(indexArr, data){
                    console.log(data);
                    var content=''
                    for(var i in data){
                        content+=' '+data[i].text
                    }
                    $("#trigger2 .weui-input").val(content);
                    $("#trigger2 input[name=cat_fid]").val(data[0].value);
                    $("#trigger2 input[name=cat_id]").val(data[1].value)
                }
            });
            $.ajax({
                type : "post",
                url : "{pigcms{:U('Employee/cityList')}",
                dataType : "json",
                data:{},
                async:false,
                success : function(data){

                    if(data.result && data.result!=null && data.result.length>0){
                        //店铺所在地
                        var mobileSelect3 = new MobileSelect({
                            trigger: '#trigger3',
                            title: '店铺所在地',
                            keyMap:{
                                id:'value',
                                value:'text',
                                childs:'children'
                            },
                            wheels: [
                                {data : data.result}
                            ],
                            transitionEnd:function(indexArr, data){
                                console.log(data);
                            },
                            callback:function(indexArr, data){
                                console.log(data)
                                var content=''
                                for(var i in data){
                                    content+=' '+data[i].text
                                }
                                $("#trigger3 .weui-input").val(content)
                                var id=data[data.length-1].value
                                self.groupTeam(id)
                            }
                        });
                    }

                    $.hideLoading();
                },
                error:function(res){
                    $.hideLoading()
                    console.log(res)
                }
            })
            // APP.textAuto('#textarea')




        },
        groupTeam:function(id){
            var self=this;
            //店鋪所在商圈
            $.ajax({
                type : "post",
                url : "{pigcms{:U('Employee/ajaxCircle')}",
                dataType : "json",
                data:{id:id},
                async:false,
                success : function(data){

                    if(data.result && data.result!=null && data.result.length>0){
                        var index = Simplite.getTemplate('group');
                        var indexTpl = Simplite.compile(index)({});
                        $("#groupTeam").html(indexTpl).removeClass("none");
                        var mobileSelect4= new MobileSelect({
                            trigger: '#trigger4',
                            title: '店铺所在商圈',
                            keyMap:{
                                id:'area_id',
                                value:'area_name'
                            },
                            wheels: [
                                {data : data.result}
                            ],
                            transitionEnd:function(indexArr, data){
                                console.log(data);
                            },
                            callback:function(indexArr, data){
                                console.log(data);
                                $("#trigger4 .weui-input").val(data[0].area_name)
                                $("#trigger3 input[name=circle_id]").val(data[0].area_id)

                            }
                        });
                    }else{
                        $("#groupTeam").html('').addClass("none");
                        $("#trigger3 input[name=circle_id]").val('')
                    }
                },
                error:function(res){
                    console.log(res)
                }
            })
        },
        checkClick:function(){
            var self=this;
            $('#searchInput').bind(self.settings.bindname ,function(){
                var val=$(this).val();
                if(val.trim()!=''){
                    $(".close_map").hide();
                    $("#searchAdd").html("");
                    $("#searchCancel").text("取消");
                    setTimeout(function(){
                        self.search_adress(val);
                    },500)
                }else{
                    $("#searchAdd").html('')
                }
            })
            $(document).on("click",'#searchMore label',function(){
                self.settings.check_lat=$(this).attr("data-lat");
                self.settings.check_lon=$(this).attr("data-long");
                self.settings.check_adress1=$(this).find(".weui-media-box__title").html();
                self.settings.check_adress=$(this).find(".weui-media-box__desc").html();
                var oPoint = new BMap.Point(self.settings.check_lon,self.settings.check_lat);
                self.settings.map.centerAndZoom(oPoint, 16);
                $("#searchCancel").html('确定');
            })
            $(document).on("click",'#searchCancel',function(){
                if($(this).html()=='确定'){
                    $("#textarea").val(self.settings.check_adress1+self.settings.check_adress);

                    if( self.settings.check_lat!=''){
                        $(".show_map>h4").html(self.settings.check_lat+','+self.settings.check_lon)
                        $("input[name=long_lat]").val(self.settings.check_lon+','+self.settings.check_lat)
                    }
                    window.history.go(-1)
                }else{
                    $("#searchAdd").html("")
                    $("#searchInput").val("")
                    $(this).html('确定')
                }
            })
            $(document).on("click",'#searchAdd label',function(){
                var check_lat=$(this).attr("data-lat");
                var check_lon=$(this).attr("data-long");
                $("#searchAdd").hide().html('');
                $(".close_map").show();
                $('#searchInput').val('');
                $("#searchCancel").html('确定');
                var oPoint = new BMap.Point(check_lon,check_lat);
                self.settings.map.centerAndZoom(oPoint, 16);
                self.getPositionInfo(check_lat,check_lon);
            })
            $(document).on("click",'.addTime',function(){
                if($(".openTime .weui-cell ").size()>=3){
                    self.load('最多只能选择三个时间段')
                    return false
                }
                self.settings.index+=2
                var _id={
                    e1:"work"+(self.settings.index+1),
                    e2:"work"+(self.settings.index+2)
                };
                self.settings.id.push(_id)
                var length=$(".openTime>div").length+1;

                var index = Simplite.getTemplate('time');
                var indexTpl = Simplite.compile(index)({index:length});
                $(".openTime").append(indexTpl);

                self.initTime()


            })
            $(document).on("click",'.delTime',function(){
                var index=$(this).closest(".weui-cell").index();
                  if(index==1 && $(".openTime>div").length==3){
                      var clone = Simplite.getTemplate('clone');
                      var open = Simplite.compile(clone)({value:$(".openTime>div").eq(2).find(".open").val(),open:true});
                      var close = Simplite.compile(clone)({value:$(".openTime>div").eq(2).find(".close").val(),open:false});
                      $(".openTime>div").eq(2).find(".open").replaceWith(open)
                      $(".openTime>div").eq(2).find(".close").replaceWith(close)
                  }
                $(this).closest(".weui-cell").hide().remove()
            })
            $(document).on("click",'.show_map',function(){
                var wh=$(window).width();
                self.settings.hashchange=1;
                self.settings.chackchange=false;
                location.hash='#mar_adress=1';
                $("#ui_left").animate({'left':-wh+'px'},200);
                $('#ui_right').animate({'left':'0px'},200,function(){
                    $("#ui_left").hide();
                    $('#ui_right').show();
                    $(".close_map").show();
                    $("#searchAdd").html('').hide()
                    self.settings.chackchange=true;
                    if(!self.settings.is_check_map){
                        self.settings.is_check_map=true
                        self.open_map();
                    }


                })
            })

            $(window).on('hashchange',function(e){
                if(self.settings.chackchange){
                    if(self.settings.hashchange==1){
                        $("#ui_right").hide();
                        $("#ui_left").show().css('left','0px');
                        $("#searchInput").val("");
                        $(".close_map").show();
                        $("#searchAdd").html('').hide()
                        self.settings.hashchange=0;
                        self.settings.chackchange=false
                    }
                }


            })
        },
        load:function(text,time,success){
            $("#layermbox0 .layermcont").html(text)
            $("#layermbox0").removeClass("none");
            setTimeout(function(){
                $("#layermbox0").addClass("none");
                success && success()
            },time?time:2000)
        },
        loadData:function(){
            var _this=this;
            var val=$("#searchInput").val();
            if(val.trim()!=''){
                $(".close_map").hide();
                $("#searchAdd").html("");
                $("#searchCancel").text("取消");
                setTimeout(function(){
                    _this.search_adress(val);
                },500)
            }
        },
        search_adress:function(val){
            var post_url ='/index.php?g=Index&c=Map&a=suggestion&query='+val+'&city_limit=1';
            $.get(post_url,{}, function(data) {
                        var res=data;
                        var datas=[];
                        if(res.status == 1){
                            if(res.result && res.result.length>0){
                                datas=res.result
                            }
                            var index = Simplite.getTemplate('map_adress1');
                            var indexTpl = Simplite.compile(index)(datas);
                            $("#searchAdd").html(indexTpl).show()

                        }

                    }

            )

        },
        open_map:function(){
            var self=this;
            var window_w=$(window).width();
            self.settings.map = new BMap.Map("map_show",{"enableMapClick":false});
            self.settings.map.enableScrollWheelZoom();
            self.settings.map.enableContinuousZoom();
            self.settings.map.centerAndZoom(new BMap.Point(116.331398,39.897445),6);
            self.settings.map.addEventListener("dragend", function(e){
                var centerMap = self.settings.map.getCenter();
                self.getPositionInfo(centerMap.lat,centerMap.lng);
            });
            self.settings.map.addEventListener('zoomend', function (e) {
                console.log(e)
                var centerMap = self.settings.map.getCenter();
                self.getPositionInfo(centerMap.lat,centerMap.lng);
            });
            if(self.settings.lngs == '' || self.settings.lats == ''){
                var geolocation = new BMap.Geolocation();
                geolocation.getCurrentPosition(function(r){
                    if(this.getStatus() == BMAP_STATUS_SUCCESS){
                        var oPoint = new BMap.Point(r.point.lng, r.point.lat);
                        self.settings.map.centerAndZoom(oPoint,16);
                       // self.settings.init_lat=r.point.lat;
                       // self.settings.init_lon=r.point.lng;
                        self.getPositionInfo(r.point.lat,r.point.lng);

                    } else {

                    }
                },{enableHighAccuracy: true})

            }else{
                var oPoint = new BMap.Point(self.settings.lngs, self.settings.lats);
                self.settings.map.centerAndZoom(oPoint, 16);
                self.getPositionInfo(self.settings.lats,self.settings.lngs);
            }
        },
        getPositionInfo:function(lat,lng){
            $.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&location='+lat+','+lng+'&output=json&pois=1&callback=getPositionAdress&json=?');
        },
        submit:function(){
            var self=this;

              $(".submit").click(function(){
                  if(!self.validate($('input[name=name]').val(),'g')){
                      self.load('请输入店铺名称',1000);
                      return false
                  }
                  if(!self.validate($('#txt_info').val(),'g')){
                      self.load('请输入店铺描述',1000);
                      return false
                  }
                  var myreg = /^[1][3,4,5,7,8][0-9]{9}$/;
                  if(!self.validate($('input[name=phone]').val(),'g',true)){
                      self.load('请输入手机号',1000);
                      return false
                  }else{
                      if (!myreg.test(self.validate($('input[name=phone]').val(),'g',true))) {
                          self.load('手机号码格式不正确',1000);
                          return false
                      }
                  }
                  if( $("#trigger2 input[name=cat_fid]").val()==''){
                      self.load('请选择店铺分类',1000);
                      return false
                  }
                  if( $("input[name=long_lat]").val()==''){
                      self.load('请选择地址',1000);
                      return false
                  }
                  $.showLoading('加载中')
                  $("#form").ajaxSubmit({

                      beforeSubmit: function(data, elem, options){

                          console.log(data)
                      },
                      success: function(res) {
                            var s=res;
                            if(typeof(res)=='string'){
                                   s=eval('(' + res + ')');
                            }
                           if(s.errorCode==0){
                               self.load('添加成功',1000,function(){
                                   console.log('res', s);
                                   if (s.result.url) {
                                       window.location.href=s.result.url;
                                   } else {
                                       var url = "{pigcms{$config['site_url']}" + '/wap.php?g=Wap&c=Employee&a=claim_store&store_id=' + s.result.store_id;
                                       window.location.href=url;
                                   }
                               });
                           }else{
                               self.load(s.errorMsg,1000);
                           }

                          $.hideLoading()
                      },
                      error: function() {
                          $.hideLoading();
                          self.load('添加失败',1000);
                          return false
                      }
                  });


              })
        },
        validate:function(res,is_global,type){
        var _this=this;
        var result=res.replace(/(^\s+)|(\s+$)/g,"");
        if(is_global.toLowerCase()=="g") {
             result = result.replace(/\s/g,"");
            if(result==''){
                return false
            }else{
                 if(type){
                     return result
                 }else{
                     return true
                 }

            }
        }
      }
    }
    window.View=View;
    View.init()
    function getPositionAdress(result){
        var window_h=$(window).height()
        if(result.status == 0){
            var res = result.result;
            var index = Simplite.getTemplate('map_adress');
            var indexTpl = Simplite.compile(index)(res.pois);
            $("#map_baidu").css('height',window_h+'px')
            $("#searchMore .weui-cells_radio").css('height',(window_h-250-48)+'px')
            $("#searchAdd").css('height',(window_h-50)+'px')
            $("#searchAdd").hide().html('')
            $("#searchMore .weui-cells_radio").html(indexTpl)
        }else{
            alert('获取位置失败！');
        }
    }
</script>


<script type="text/javascript">
    window.shareData = {
        "moduleName":"Employee",
        "moduleID":"0",
        "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Employee/store_add')}",
        "tTitle": "{pigcms{$config['employee_alias']}添加店铺",
        "tContent": "{pigcms{$config.site_name}"
    };
</script>
{pigcms{$shareScript}
<if condition="$is_app_browser">
    <script type="text/javascript">
        
        if("{pigcms{$app_browser_type}" == 'android'){
            window.lifepasslogin.shareLifePass("{pigcms{$config['employee_alias']}添加店铺","","<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>","{pigcms{$config.site_url}{pigcms{:U('Employee/store_add')}");
        } else {
            iosCommon.iosFunction('shareLifePass', {name:'shareLifePass', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
        }
    </script>
</if>

</html>
