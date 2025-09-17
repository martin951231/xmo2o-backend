<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Write an awesome description for your new site here. You can edit this line in _config.yml. It will appear in your document head meta (for Google search results) and in your feed.xml site description.">
    <title>发布活动</title>
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.min.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/css/cropper.min.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/css/index.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/css/icon.css">
    <style>
        .del-question,.del,.addQuestion,.weui-gallery,#address,#profile,.addMore,#new_address,label{
          cursor:pointer
        }
        .fixed{position:fixed;left:0px;right:0px;}
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
        #searchMore .weui-cells_radio{
           overflow-y: scroll;
           margin-top:0px !important;
           background:#fff
        }
        .weui-search-bar__cancel-btn {
          display: block !important;

        }
       .weui-search-bar__label {

           text-align: left;

        }
          #searchAdd{     background:#fff}
          #map_baidu{overflow:hiddden}
        #clipArea{
           min-height: 200px;
           max-height: 466px;
           overflow: hidden;
           width: 100%;
           text-align: center;
       }
       #clipArea > img {
           max-width: 100%;
       }
       .cropper-hidden {
           display: none !important;
       }

       .weui-uploader__img{
           width:70px;
           height:70px;
           margin:10px; 
           box-sizing: border-box;
           border:1px dashed #ddd;
           float:left
       }
       .clear{
           clear:both
       }
       .ImgPicBox li{
           float:left; 
           position: relative;
           width:25%;
           list-style:none
        }
        .ImgPicBox li .closeImg{
          position: absolute;
          right:15px;
          top:5px;
          font-style: normal;
          z-index:9;
          padding:2px; 
          width:12px;
          height:12px; 
          line-height:12px; 
          color:#fff;
          background:#000;
          border-radius: 100%; 
       }  
    </style>
</head>
<body ontouchstart="">
<div class=" tabbar js_show" id="body_content">
    <div class="page__bd "  style="height: 100%;">
        <div id="page__bd" class="weui-tab none">
        </div>
        <div id="addMore" class="weui-tab none" >
        </div>
        <div id="chooseMore" class=" "></div>
    </div>
    <div id="file_pic" class=" none">
    </div>
    <div id="fileImg"></div>
    <div id="agree"></div>
    <div id="map_baidu" style="display:none;">
        <div class="map_header">
            <div class="weui-search-bar" id="searchBar">
                <form class="weui-search-bar__form" action="" onsubmit="View.loadData()" target="frameFile" >
                    <div class="weui-search-bar__box">
                        <i class="weui-icon-search"></i>
                        <input type="search" class="weui-search-bar__input" id="searchInput" placeholder="搜索" required="">
                        <iframe name="frameFile" style="display:none"></iframe>
                        <a href="javascript:" class="weui-icon-clear" id="searchClear"></a>
                    </div>
                    <label class="weui-search-bar__label" id="searchText">
                        <i class="weui-icon-search"></i>
                        <span>搜索</span>
                    </label>
                </form>
                <a href="javascript:" class="weui-search-bar__cancel-btn" id="searchCancel">确定</a>
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
<script>
	  var is_sign ="{pigcms{$_SESSION['user']['uid']}"
</script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-form/jquery-plugin-form.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
<script type="text/javascript"  src="static/activity/assets/js/components/iscroll-zoom/js/iscroll-zoom.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/cropper/cropper.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
<script type="text/javascript" src="static/activity/assets/js/common.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/city-picker/city-picker.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/fastClick/fastClick.js"></script>
<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
<script type="text/html" id="map_adress">
    <& for(var i=0;i< _this.length;i++) {&>
    <& var _item=_this[i] &>
    <label class="weui-cell weui-check__label" for="x<&= _item.point.x&>" data-long="<&= _item.point.x&>" data-lat="<&= _item.point.y&>" data-city_id="<&= _this.city_id&>" data-province_id="<&= _this.province_id&>">
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
<script type="text/html" id="index">
    <form action="{pigcms{:U('ActivityOffline/set_activity')}"  method="post" target="nm_iframe" id='form' >
        <input style='width:0px;height:0px;position:absolute' name='start_time' value="<&= _this.start_time||'' &>"/>
        <input style='width:0px;height:0px;position:absolute' name='activity_id' value=""/>
        <input style='width:0px;height:0px;position:absolute' name='end_time' value="<&= _this.end_time||'' &>"/>
        <input style='width:0px;height:0px;position:absolute' name='file_img' value="<&= _this.avatar||'' &>"/>
        <input style='width:0px;height:0px;position:absolute' name='province_id' value="<&= _this.province_id||'' &>"/>
        <input style='width:0px;height:0px;position:absolute' name='city_id' value="<&= _this.city_id||'' &>"/>
        <input style='width:0px;height:0px;position:absolute' name='cate_id' value="<&= _this.cate_id||'' &>"/>
        <input style='width:0px;height:0px;position:absolute' name='fid' value="<&= _this.fid||'' &>"/>
        <input style='width:0px;height:0px;position:absolute' name='deadline' value="<&= _this.deadline||'' &>"/>
        <input style='width:0px;height:0px;position:absolute' name='provincename' value="<&= _this.provincename||'' &>"/>
        <input style='width:0px;height:0px;position:absolute' name='cityname' value="<&= _this.cityname||'' &>"/>
        <input style='width:0px;height:0px;position:absolute' type="text" name="new_address" />
        <input style='width:0px;height:0px;position:absolute'   type="text" name="lat" value=""/>
        <input style='width:0px;height:0px;position:absolute'   type="text" name="long" value=""/>
    <div class="weui-tab__panel" style="padding-bottom:0px">
        <div class="content_wrapper">
            <div class="weui-cells weui-cells_after-title">
                <div class="mg15 bg-f">
                    <div id="bg_img" >
                    <!-- style="height:<&= _this.h &>px" -->
                    <!-- padding-top:<&= _this.hh &>px -->
                        <div  id="weui-uploader__img" class="<& if(_this.image){&>none<&}&>"> 
                            <ul class='ImgPicBox'>
                                <li>
                                    <div id="class_Img"> 
                                        <input id="uploaderInput" class="weui-uploader__input" type="file" name='img'>
                                        <image class="weui-uploader__img" id="ImgPr" src="static/activity/assets/image/addMobile.png"  /> 
                                        <div class="clear"></div>
                                    </div> 
                                </li>
                            <ul>
                            
                        </div>
                        <div class="clear"></div> 
                        <!-- <div class="<& if(!_this.image){&>none<&}&> wh100" style="position:relative"> -->
                            <!-- <image class="weui-uploader__img " id="ImgPic" src="<&= _this.image &>"  /> -->
                            <!-- <a href="javascript:;"  class="lookImg" style="position:absolute;right:20px;bottom:15px;width:30px;height:30px;z-index:5">
                                <img src="static/activity/assets/image/setMobile.png" style="width:100%;height:100%"/>
                            </a> -->
                        <!-- </div> -->
                        <div style="text-align:left;color:#999; padding:10px;">添加活动海报,最多6张</div>  
                    </div>
                    <div class="weui-cell weui-cell_input relatice">
                        <div class="weui-cell__hd" style="width:50px">
                            <div class="weui-label" >主题</div>
                        </div>
                        <div class="weui-cell__bd">
                            <input class="weui-input" placeholder="请输入活动主题" name="title" value="<&= _this.title||'' &>"/>
                        </div>
                    </div>
                    <div class="weui-cell weui-cell_input relatice">
                        <div class="weui-cell__hd" style="width:50px">
                            <div class="weui-label" style="width:48px" >日期</div>
                        </div>
                        <div class="weui-cell__bd select_date">
                            <div class="date_text time timeStar" style="font-size:14px;flex:1" >
                                <div id="time-star"><&= _this.start_time?_this.start_time:'开始日期' &></div>
                            </div>

                            <span class="date_text">至</span>
                            <div style="font-size:14px;flex:1;text-align:right" class="date_text timeEnd">
                                <div   id="time-end"><&= _this.end_time?_this.end_time:'结束日期' &></div>
                            </div>

                        </div>
                    </div>
                    <div class="weui-cells weui-cells_after-title bg-f md-b-n order_time" >
                        <div class="weui-cell weui-cell_access">
                            <div class="weui-cell__bd">报名截止</div>
                            <div class="weui-cell__ft weui-cell__ft_in-access" id="activiteEnd"><&= _this.deadline?_this.deadline:'活动报名结束前均可报名' &></div>
                        </div>
                    </div>
                    <div class="weui-cell weui-cell_access" id="new_address" >
                        <div class="weui-cell__hd" style="min-width:50px">活动地点</div>
                        <div class="weui-media-box__bd weui-cell__ft " style=" flex: 1;text-align: right;">
                            <h4 class="weui-media-box__title" style="color:#999;white-space: normal;">
                                <&= _this.address_name?_this.address_name:'选择活动地点' &>
                            </h4>
                        </div>
                    </div>
                    <div class="weui-cell weui-cell_access" >
                        <div class="weui-cell__hd" style="width:50px">分类</div>
                        <div class=" weui-cell__ft_in-access weui-cell__bd weui-cell__ft" style="text-align:right" id='profile_name'>
                            <input class="weui-input weui-cell__ft" id="profile" value='<& if(_this.first_cate_name){&><&= _this.first_cate_name&> <&=  _this.second_cate_name&><&}&>'  placeholder="选择分类" type="text" name="profile" readonly/>
                        </div>
                    </div>
                    <div class="weui-cell weui-cell_input relatice">
                        <div class="weui-cell__hd" style="width:60px">
                            <div class="weui-label" >手机号</div>
                        </div>
                        <div class="weui-cell__bd">
                            <input class="weui-input" maxlength="11" max='11' placeholder="请输入联系手机号" name="phone" value="<&= _this.phone||'' &>"/>
                        </div>
                    </div>
                </div>
                <div class="mg15 bg-f">
                   <div class="weui-cell weui-cell_input" >
                        <div class="weui-cell__hd">
                            <div class="weui-label">总费用</div>
                        </div>
                        <div class="weui-cell__bd">
                            <input class="weui-input"  value="<&= _this.fees_all && _this.fees_all!=''?_this.fees_all:''&>" placeholder="默认免费" type="number" name="fees_all"  style="text-align:right"/>
                        </div>
                    </div>
                    <div class="weui-cell weui-cell_input relatice" id="dd">
                        <div class="weui-cell__hd">
                            <div class="weui-label">费用</div>
                        </div>
                        <div class="weui-cell__bd">
                            <input class="weui-input"  value="<&= _this.fees && _this.fees!=''?_this.fees:''&>" placeholder="默认免费" type="number" name="fees"  style="text-align:right"/>
                        </div>
                    </div>
                    <div class="weui-cell weui-cell_input relatice">
                        <div class="weui-cell__hd">
                            <div class="weui-label">成团人数</div>
                        </div>
                        <div class="weui-cell__bd">
                            <input class="weui-input" placeholder="默认无限制"  type="number" name="open_number" style="text-align:right" value="<&= _this.open_number && _this.open_number*1>0?_this.open_number:''&>"/>
                        </div>
                    </div>
                    <div class="weui-cell weui-cell_input relatice">
                        <div class="weui-cell__hd">
                            <div class="weui-label">名额限制</div>
                        </div>
                        <div class="weui-cell__bd">
                            <input class="weui-input" placeholder="默认无限制"  type="number" name="limit" style="text-align:right" value="<&= _this.limit && _this.limit*1>0?_this.limit:''&>"/>
                        </div>
                    </div>
                </div>
                <div class="mg15 bg-f">
                    <div class="weui-cell">
                        <div class="weui-cell__bd">
                            <textarea class="weui-textarea" maxlength="200"  placeholder="请输入详细内容" rows="3" name="desc" id="textarea"><&= _this.desc||''&></textarea>
                            <div class="weui-textarea-counter"><span id="len"><&= _this.desc?_this.desc.length:0&></span>/200</div>
                        </div>
                    </div>
                </div>
                <div class=" bg-f" >
                    <div  class="weui-cell weui-cell_access" id="baoMing" data-url="#sign_up=1">
                        <div class="weui-cell__bd">报名设置</div>
                        <div class="weui-cell__ft weui-cell__ft_in-access">姓名、手机</div>
                    </div>
                </div>
                <div class="mg15 " >
                    <label for="weuiAgree" class="weui-agree" style="display:inline;padding-right:0px">
                        <input id="weuiAgree" type="checkbox" class="weui-agree__checkbox">
                       <span class="weui-agree__text">阅读并同意</span>
                    </label>
                    <span class="weui-agree" style="display:inline;padding:0px">
                        <span class="weui-agree__text"><a href="javascript:void(0);" class="agree_ment">《用户协议》</a></span>
                    </span>

                </div>

            </div>
        </div>
    </div>
    <div class="weui-tabbar" style="z-index:9;position:fixed;padding:10px 0px;">
        <div style="width:90%;margin:auto">
            <button class="weui-btn weui-btn_primary" id="submit" type="button"   >发布</button>
        </div>
    </div>
   </form>
</script>
<script type="text/html" id="editImg">
    <div class="" style="width:100%;height:100%">
        <div id="clipArea"></div>
    </div>
    <div class="weui-tabbar" >
        <div style="width:90%;margin:auto">
            <button id="clipBtn"  class="weui-btn weui-btn_primary" style="display: inline-block;width:48%">点击生成图片</button>
            <button  class="weui-btn weui-btn_primary goBack"  style="display: inline-block;width:48%;margin-top:0px">返回</button>
        </div>

    </div>
</script>
<script type="text/html" id="tplAdd">
    <& var data=_this&>
    <div class="" id="questionType">
        <div>
            <div class="weui-cell bg-f">
                <div class="weui-cell__bd">
                    <input class="weui-input" name="<&= data.text&>" type="text" placeholder="<&= data.text&>">
                </div>
            </div>
            <& if(parseInt(data.id)==1){ &>
            <div class='txt-info' >需要报名者填写文字较多的信息，如个人简介，项目介绍，团队介绍等，请使用本功能</div>
            <& } &>
            <& if(parseInt(data.id)>1){ &>
            <div class="question" >
                <div class="weui-cell bg-f">
                    <div class="weui-cell__hd del-question"><label class="weui-label tjt-label">
                        <image src="static/activity/assets/image/minus.png" style="width:20px;height:20px;margin-right:5px;"/>
                    </label>
                    </div>
                    <div class="">
                        <div class="weui-cell__bd">
                            <input class="weui-input" type="text"  placeholder="请输入问题答案">
                        </div>
                    </div>

                </div>
                <div class="weui-cell bg-f">
                    <div class="weui-cell__hd del-question"><label class="weui-label tjt-label">
                        <image src="static/activity/assets/image/minus.png" style="width:20px;height:20px;margin-right:5px;"/>
                    </label>
                    </div>
                    <div class="">
                        <div class="weui-cell__bd">
                            <input class="weui-input" type="text"  placeholder="请输入问题答案">
                        </div>
                    </div>

                </div>
                <div class="weui-cell bg-f">
                    <div class="weui-cell__hd del-question"><label class="weui-label tjt-label">
                        <image src="static/activity/assets/image/minus.png" style="width:20px;height:20px;margin-right:5px;"/>
                    </label>
                    </div>
                    <div class="">
                        <div class="weui-cell__bd">
                            <input class="weui-input" type="text"  placeholder="请输入问题答案">
                        </div>
                    </div>

                </div>
            </div>

            <label class="weui-cell weui-check__label bg-f addQuestion" style="padding:0px">
                <div class="weui-cell__bd  ">
                    <p style="text-align:center;color:#16B990;font-size:30px">+</p>
                </div>
            </label>
            <& } &>



        </div>
    </div>
    <div class="weui-tabbar" >
        <div style="width:90%;margin:auto">
            <button id="btnMore"  class="weui-btn weui-btn_primary" style="display: inline-block;width:48%;margin-top:0px">保存</button>
            <button  class="weui-btn weui-btn_primary back"  style="display: inline-block;width:48%;margin-top:0px">返回</button>
        </div>
    </div>
</script>
<script type="text/html" id="ming">
    <div class="weui-tab__panel" style="padding-bottom:0px">
        <div class="content_wrapper">
            <div class="weui-cells weui-cells_after-title">
                <div class=''>
                    <div class="weui-cells__title">设置用户报名时填写的信息</div>
                    <div class="weui-cells weui-cells_checkbox bg-f " style="height:100%">
                        <div>
                            <& for(var i=0;i< _this.question.length;i++){ &>
                            <& var item=_this.question[i]&>
                            <& var num=i&>
                            <div class="weui-cell weui-check__label "  >
                                <& if(num>1) {&>
                                <div class="weui-cell__hd del" data-index="<&= num&>">
                                    <image src="static/activity/assets/image/minus.png" style="width:20px;height:20px;margin-right:5px;"/>
                                </div>
                                <& }&>

                                <div class="weui-cell__hd">
                                    <p><&= item.name&></p>
                                </div>

                                <label for="<&= item.name&><&= num&>" class="weui-cell__bd weui-cell__ft is_require "  data-index='<&= num&>'>
                                    <span>必填</span>
                                    <input type="checkbox" <&= num<=1?'disabled':''&> class="weui-check" name="checkbox1" id="<&= item.name&><&= num&>" <&=item.is_required==0? 'checked':''&> >
                                    <i class="weui-icon-checked"></i>
                                </label>
                            </div>
                            <& }&>

                        </div>
                        <label class="weui-cell weui-check__label addMore" >
                            <div class="weui-cell__bd addOption ">
                                <p >+添加自定义填写项</p>
                            </div>
                        </label>

                    </div>


                </div>
            </div>
        </div>
    </div>
    <div class="weui-tabbar"  style="z-index:9;position: fixed">
        <div style="width:90%;margin:auto">
            <button   class="weui-btn weui-btn_primary curentOne" style="display: inline-block;width:48%;margin-top:0px">保存</button>
            <button  class="weui-btn weui-btn_primary backOne"  style="display: inline-block;width:48%;margin-top:0px">返回</button>
        </div>
    </div>
</script>
<script type="text/html" id="question">
    <div class="weui-cell bg-f">
        <div class="weui-cell__hd del-question"><label class="weui-label tjt-label">
            <image src="static/activity/assets/image/minus.png" style="width:20px;height:20px;margin-right:5px;"/>
        </label>
        </div>
        <div class="">
            <div class="weui-cell__bd">
                <input class="weui-input" type="text"  placeholder="请输入问题答案">
            </div>
        </div>

    </div>
</script>
<script type="text/html" id="questionNumber">
    <label class="weui-cell weui-check__label" for="s12">
        <div class="weui-cell__hd">
            <p> <image src="static/activity/assets/image/minus.png" style="width:20px;height:20px;margin-right:5px;"/>手机</p>
        </div>
        <div class="weui-cell__bd weui-cell__ft">
            <span>必填</span>
            <input type="checkbox" class="weui-check" name="checkbox1" id="s12" checked="checked">
            <i class="weui-icon-checked"></i>

        </div>
    </label>
</script>
<script type="text/html" id="showImg">
    <div class="weui-gallery" style="display: block">
        <span class="weui-gallery__img" style="background-image: url(<&= _this.img&>);"></span>
        <div class="weui-gallery__opr">
            <a href="javascript:" class="weui-gallery__del">
                <i class="weui-icon-delete weui-icon_gallery-delete none"></i>
            </a>
        </div>
    </div>
</script>
<script type="text/html" id="aggrement">
    <div class="weui-mask weui-mask--visible " style="z-index:99"></div>
    <div class="weui-dialog weui-dialog--visible yui-auth-form  auth-animated">
        <div class="weui-dialog__hd" style="position: relative">
            <strong class="weui-dialog__title" style="    font-weight: normal;font-size: 16px;">用户协议</strong>
            <a class="del_sing closeHide" href="javascript:;"><span class="iconfont icon-close" style="font-size:20px"></span></a>
        </div>
        <div class="weui-dialog__bd" style="text-align: left;overflow-y: auto; max-height:<&=_this.hh&>px;">
           <div >
               {pigcms{$config.activity_offline_agreement|html_entity_decode=###}
           </div>
        </div>

    </div>
</script>
<script type="text/html" id="adress">
    <input class="weui-input weui-cell__ft" value="<&=_this[0]&> <&=_this[1]&>" id="address" placeholder="选择地址" type="text" name="address" readonly="">
</script>
<script type="text/html" id="order_time">
    <div class="weui-cell__bd">报名截止</div>
    <div class="weui-cell__ft weui-cell__ft_in-access" id="activiteEnd"><&= _this.st&></div>
</script>
<script>
	 var url="{pigcms{:U('ActivityOffline/set_activity')}";
	 if(is_sign=='')
	 {
        $.alert({
            title: '提示',
            text: '请先登录',
            onOK: function () {
               window.location.href='/wap.php?g=Wap&c=Login&a=index';
           }
        });
       }
</script>
<script>
    var h=0;
    var clipArea=null;
    var w=$(window).width();
    var question=[
        {
            'name': '姓名',
            'type': 0,
            'is_required': 0,
            'checked':0,
            'title': '姓名',

        },
        {
            'name': '手机',
            'type': 0,
            'is_required': 0,
            'title': '手机',
             'checked':0,
        }
    ];
    var type=1;
    var View = {
        questionList:[

        ],
        init: function () { 
            this.initIndex();
            this.textarea();
            this.selectTime();
            this.is_require();
            this.img();
            this.showImg();
            this.addMore();
            this.submit();
            this.back()
        },
        setting:{
            title:'',
            activity_id:'',
            sing_up:'',
            window_w:0,
            hashchange:0,
            chackchange:true,
            top:0,
            lats:'',
            lngs:'',
            bindname:navigator.userAgent.indexOf("MSIE") != -1?'propertychange':'input',
            is_check_map:false,
            check_lat:'',
            check_lon:'',
            map:null,
            init_lat:'',
            init_lon:'',
            check_adress:'',
            check_adress1:'',
            href:false,
            money:0,
            city_id:0,
            province_id:0
        },
        initIndex:function(){
            var _this=this;
            $.showLoading('加载中')
            h=$(window).width()*0.44;
            _this.setting.window_w=$(window).width()
            var hh=(h-70)/2;
            var href=window.location.href;
            if(href.indexOf('activity_id')!=-1){
               _this.setting.href=true
                var ss=href.lastIndexOf('=')+1
                View.setting.activity_id=href.slice(ss)
                $.ajax({
                    type : "post",
                    url : "{pigcms{:U('ActivityOffline/activity_detail')}",
                    dataType : "json",
                    data:{'activity_id':View.setting.activity_id},
                    async:false,
                    success : function(data){
                        if(data.errorMsg!='success'){
                            $.hideLoading()
                        }else{

                            data.result.h=h;
                            data.result.hh=hh
                            _this.showDetail(data.result,true)
                        }

                    },
                    error:function(res){
                        $.hideLoading()
                        $.alert({
                            title: '提示',
                            text: '加载失败',
                            onOK: function () {
                                //点击确认
                            }
                        });
                        console.log(res)
                    }
                })
            }else{
              _this.setting.href=false
                var tr={

                            h:h,
                            hh:hh

                }
                _this.showDetail(tr,false)
            }


        },
        showDetail:function(contents_datas,set_activity){
            var ads=false;
            var newCity=false;
            var contents=contents_datas;
            var index = Simplite.getTemplate('index');
            var indexTpl = Simplite.compile(index)(contents_datas);
            View.setting.money=contents_datas.fees;
            $("#page__bd").html(indexTpl).fadeIn();
            if(set_activity){
                $("input[name=activity_id]").val(View.setting.activity_id)
            }
            if(contents.custom_list){
                for(var i in contents.custom_list){
                    contents.custom_list[i].name=contents.custom_list[i].title
                }
                question=contents.custom_list
            }
            $.hideLoading()
            var activityType={
                id:'',
                fid:''

            }
            var parvince=[];var city=[];var area=[];var activity_offline_cate=[];var num=0;
            
            var newCate=function(type){
                activityType.fid=''
                var v=$("#profile").val();
                if(type){
                    v=''
                }
                var html=' <input class="weui-input weui-cell__ft" id="profile" value="'+v+'"   placeholder="选择分类" type="text" name="profile" readonly/>'
                $("#profile_name").html(html);
                activity_profile(3,true)
            }

            var profile=function(){
                if(area.length>0){
                    var datas={
                        province:area,
                        city:activity_offline_cate
                    }
                    $("#profile").cityPicker({
                        showDistrict: false,
                        datas:datas,
                        onBack:function(province_id){
                            activity_offline_cate=[];
                            activityType.id=province_id
                            activityType.fid=province_id
                            $.ajax({
                                url: "{pigcms{:U('ActivityOffline/activity_cate')}",
                                async: false,//这一步是非常重要的，作用是设置为同步执行
                                type: "POST",
                                data: activityType,
                                dataType: "json",
                                success: function (data) {
                                    if(data.errorMsg!='success'){
                                        activity_offline_cate=[]
                                        return activity_offline_cate
                                    }else{
                                        if(data.result.activity_offline_cate!=null ){
                                            activity_offline_cate=data.result.activity_offline_cate;
                                        }else{
                                            activity_offline_cate=[];
                                        }

                                        return activity_offline_cate;
                                    }

                                },
                                error:function(){
                                    activity_offline_cate=[];
                                    return activity_offline_cate;
                                }
                            });
                            return activity_offline_cate;
                        },
                        onChange:function(self, picker, values, displayValues){

                        },
                        onClose:function(self){
                            console.log(self)
                            activityType.id='';
                            activityType.fid='';
                            $("input[name=cate_id]").val(self.value[1]);
                            $("input[name=fid]").val(self.value[0]);
                        }
                    });
                }else{

                }

            }
            $(document).on("click",'#address',function(){
                if(!ads){
                    if(parvince.length==0){
                        $.toptip("暂无可选地点", 'error')
                        return false
                    }else{
                        picter()
                    }
                }
            })

            var activity_profile=function(n,type){
                if(type){
                    $.post('{pigcms{:U(\'ActivityOffline/activity_cate\')}',activityType,function(data){
                        if(n==3){
                            if(data.errorMsg!='success'){
                                activity_profile(5,false)
                                $("input[name=cate_id]").val('');
                                $("#profile").val('')
                            }else{
                                if(data.result.activity_offline_cate && data.result.activity_offline_cate!=null && data.result.activity_offline_cate.length>0){
                                    activityType.id=data.result.activity_offline_cate[0].id;
                                    activityType.fid=data.result.activity_offline_cate[0].id;
                                    area=data.result.activity_offline_cate;
                                    activity_profile(4,type)
                                }else{
                                    activity_profile(5,false)
                                    $("input[name=cate_id]").val('');
                                    $("#profile").val('')
                                }
                            }

                        }
                        if(n==4){
                            if(data.errorMsg!='success'){
                                activity_offline_cate=[]
                            }else{
                                if(data.result.activity_offline_cate && data.result.activity_offline_cate!=null && data.result.activity_offline_cate.length>0){
                                    activity_offline_cate=data.result.activity_offline_cate;
                                }else{
                                    activity_offline_cate=[]
                                }
                            }

                            activity_profile(4,false)
                        }
                    },'json')
                }else{
                    if(n==4){
                        profile()
                    }
                    if(n==5){
                        $("#profile").on("click",function(){

                            $.toptip("暂无分类", 'error')
                            return false
                        })
                    }
                }
            }

            var activity=function(n,type,arr){
                //获取分类的接口
                if(type){
                    $.post('{pigcms{:U(\'ActivityOffline/activity_cate\')}',activityType,function(data){
                        if(n==1){
                            if(data.errorMsg!='success'){
                                activity(3,false,arr)
                            }else{
                                if(data.result.area!=null && data.result.area.length>0){
                                    if(set_activity){
                                        activityType.province_id=contents.province_id;
                                    }else{
                                        activityType.province_id=data.result.area[0].area_id;
                                    }

                                    activityType.area_type=2;
                                    parvince=data.result.area;
                                    activity(2,true,arr)
                                }else{
                                    activity(3,false,arr)
                                }
                            }
                        }
                        if(n==2){
                            if(data.errorMsg!='success'){
                                activity(2,false,arr)
                            }else{
                                if(data.result.area!=null && data.result.area.length>0){
                                    if(set_activity){
                                        activityType.city_id=contents.city_id;
                                    }else{
                                        activityType.city_id=data.result.area[0].area_id;
                                    }
                                    city=data.result.area;
                                    activity(2,false,arr)
                                }else{
                                    activity(2,false,arr)
                                }
                            }
                        }

                    },'json')
                }else{
                    if(n==2){
                        if(set_activity){
                            var d={
                                displayValue:[contents.provincename,contents.cityname],
                                value:[contents.province_id,contents.city_id]
                            }
                            fenlei(d)
                        }
                    }
                }
            }

            newCate(true);
            activity(1,true,true);
        },
        textarea:function(){
            var bind_name = 'input';var dropload2='';
            if (navigator.userAgent.indexOf("MSIE") != -1){ bind_name = 'propertychange' }
            $(document).bind(bind_name,'#textarea' ,function(){
                var length=$('#textarea').val().length;
                var content=$('#textarea').val()
                $("#len").html(length)
                if(length==200){
                    $("#len").html(200)
                }

            })
        },
        selectTime:function(){
            //选择日期
            // var format=nTime.setDate(nTime.getDate()-1)
            // var after=new Date(format)
            var _this=this;
            var nTime = new Date();
            var afterTime=new Date(nTime.getTime()+(60*60*1000*3))
            var formatNumber =View.formatNumber= function (n) {
                return n < 10 ? "0" + n : n;
            }
            var getDate=View.getDate=function(t){
                return t.getFullYear() + "-" +  formatNumber(t.getMonth()+1) + "-" + formatNumber(t.getDate()) + " "+formatNumber(t.getHours()) + ":" +(formatNumber(t.getMinutes()));

            }
            var timers=function(time,star,type){

                var max='2099-12-31 23:59';
                if(type){
                    max=getDate(time)
                }
                $("#time-star").datetimePicker({
                    title: "开始时间",
                    min: getDate(afterTime),
                    value:star,
                    max:max,
                    onChange: function (picker, values, displayValues) {
                        console.log(values)
                        $("#time-star").html(picker.value[0]+'-'+picker.value[1]+'-'+picker.value[2]+' '+picker.value[3]+':'+picker.value[4]);
                        $("input[name=start_time]").val(picker.value[0]+'-'+picker.value[1]+'-'+picker.value[2]+' '+picker.value[3]+':'+picker.value[4])
                       _this.select_deadline()
                    },
                    onClose:function(r){
                        var star=$("#time-star").html();
                        var con=$("#time-end").html()
                       var html='<div   id="time-end">'+con+'</div>';
                       $(".timeEnd").html(html);
                       var tr=star.replace(/-/g,'/')
                       var d=new Date(tr).getTime()+(60*60*1000*3)
                        var t=new Date(d)
                        timer(t)
                                          }
                });
            }
            timers('2030-12-31 23:59',getDate(afterTime),false)

            var timer=function(star){
                $("#time-end").datetimePicker({
                    title: "结束时间",
                    min: getDate(star),
                    max:'2099-12-31 12:20',
                    onChange: function (picker, values, displayValues) {
                        console.log(picker)
                        $("#time-end").html(picker.value[0]+'-'+picker.value[1]+'-'+picker.value[2]+' '+picker.value[3]+':'+picker.value[4]);
                             $("input[name=end_time]").val(picker.value[0]+'-'+picker.value[1]+'-'+picker.value[2]+' '+picker.value[3]+':'+picker.value[4])
                        _this.select_deadline()
                    },
                    onClose:function(r){
                        var star=$("#time-star").html();
                        var con=$("#time-end").html()
                        var html='<div   id="time-star">'+star+'</div>';
                        $(".timeStar").html(html);
                        var d=new Date(con).getTime()-(60*60*1000*1)
                        var t=new Date(d)
                        timers(t,star,true)
                    }
                });
            }
            $("#time-end").on("click",function(){
                 if($("#time-star").html()=='开始日期'){
                     $.toptip("请先选择开始时间", 'error')
                     return false
                 }
            })


        },
        clipImg:function(){
            var _this=this;
          APP.$flie({
              element: '#uploaderInput',
            //   aspectRatio: w/h,
              aspectRatio:1.5, 
              strict: true,
              zoomOnTouch:false,
              modal:true,
              guides:true,
              background:true,
              autoCrop:true,
              scalable:false,
              zoomable:false,
              wheelZoomRatio:false,
              cropBoxMovable:true,
              cropBoxResizable:true,
              minCropBoxWidth:w,
              minCropBoxHeight:h,
              maxCropBoxWidth:w,
              maxCropBoxHeight:h,
              avatarWrapper:'#clipArea',
              com_btn:'#clipBtn',
              btn_back:'.goBack',
              View:_this,
              btn_success_back:function(dataURL){ 
                //   $("#ImgPic").attr("src",dataURL); 
                //   $("#weui-uploader__img").siblings().show();
                if($('.ImgPicBox li').length<7){ 
                    if($('.ImgPicBox li').length==6){
                        $('#class_Img').hide();
                     } 
                    $('.ImgPicBox ul').hide();
                    // $("input[name=file_img]").val(dataURL);
                    $(".ImgPicBox").prepend('<li><image class="weui-uploader__img ImgPic" src="'+dataURL+'"/><i class="closeImg">×</i></li>');   
                    $("#file_pic").hide();
                     $.hideLoading() 
                     window.history.go(-1)
                }else{
                    $.hideLoading() 
                     window.history.go(-1)
                } 
              },
              back:function(){
                  window.history.go(-1)
              },
              element1:'uploaderInput'
          })
        },
        img:function(){
            //图片裁剪  
            var _this=this;
            var edImg = Simplite.getTemplate('editImg');
            var edImgTpl = Simplite.compile(edImg)([]);
            $("#file_pic").html(edImgTpl)
            _this.clipImg()
        },
        select_deadline:function(){
            if($("#time-star").html()!='开始日期' && $("#time-end").html()!='结束日期'){
                var times={
                    st:$("#activiteEnd").html()
                }
                var edImg = Simplite.getTemplate('order_time');
                var edImgTpl = Simplite.compile(edImg)(times);
                $(".order_time .weui-cell ").html(edImgTpl)
                var min=new Date(new Date($("#time-star").html().replace(/-/g,'/')).getTime()+(60*60*1000*1))
                var max=new Date(new Date($("#time-end").html().replace(/-/g,'/')).getTime());
                var show_time=$("#time-end").html();
                $("#activiteEnd").datetimePicker({
                    title: "报名截止时间",
                    max:View.getDate(max),
                    value:show_time,
                    onChange: function (picker, values, displayValues) {
                        $("#activiteEnd").html(picker.value[0]+'-'+picker.value[1]+'-'+picker.value[2]+' '+picker.value[3]+':'+picker.value[4])
                        $("input[name=deadline]").val(picker.value[0]+'-'+picker.value[1]+'-'+picker.value[2]+' '+picker.value[3]+':'+picker.value[4])
                    }
                });

            }



        },
        showImg:function(){
            //展示图片
            var imgSrc=function(){
                var tpl = Simplite.getTemplate('showImg');   
                var img=$('.ImgPic').attr("src");
                var data={
                    img:img
                }
                var compiledTpl = Simplite.compile(tpl)(data);
                $("#fileImg").html(compiledTpl)
            }
            $(document).on("click",'.ImgPic',function(){
                imgSrc()

            })
            $(document).on("click",'#fileImg .weui-gallery',function(){
                var _this=$(this);
                $(this).fadeOut()
                setTimeout(function(){
                    _this.remove()
                },500)
            })
            $(document).on("click",'.lookImg',function(){
                $.actions({
                    actions: [
                        {
                            text: "预览图片",
                            onClick:function(){
                                imgSrc()
                            }
                        },
                        {
                            text: "重新上传",
                            onClick:function(){
                                return $("#uploaderInput").click()
                            }
                        }
                    ]
                });
            }) 


        },
        addMore:function(){
            var _this=this;
            $(window).on('hashchange',function(e){
                 if(  _this.setting.chackchange){
                     if(_this.setting.hashchange==1){
                         $('#addMore').html('').hide();
                         $("#page__bd").show().css('left','0px')
                         _this.setting.hashchange=0
                         /*$("#page__bd").animate({'left':-wh+'px'},500)
                         $('#addMore').animate({'left':'0px'},500,function(){
                             $("#page__bd").hide()
                             $('#addMore').html(compiledTpl).show();
                         })*/
                     }else if(_this.setting.hashchange==2){
                         _this.setting.hashchange=1;
                         $('#addMore').show().css('left','0px');
                         $("#chooseMore").hide()
                     }else if(_this.setting.hashchange==5){
                         $('#file_pic').hide();
                         _this.setting.hashchange=0;
                         $("#page__bd").show().css('left','0px')
                     }else if(_this.setting.hashchange=8){
                         $("#map_baidu").hide()
                         $("#page__bd").show().css('left','0px')
                     }
                 }

            })
            $(document).on("click",'.addMore' ,function() {
                $.actions({
                    actions: [
                        {
                            text: "单行文本",
                            onClick: function() {
                                type=1;
                                var tpl = Simplite.getTemplate('tplAdd');
                                var data={
                                    text:'单行文本' ,
                                    id:0
                                }
                                var compiledTpl = Simplite.compile(tpl)(data);

                                _this.setting.chackchange=false
                                location.hash="#line=1"
                                $("#addMore").animate({'left':-_this.setting.window_w*2+'px'},200)

                                $('#chooseMore').animate({'left':'0px'},200,function(){
                                    $("#addMore").hide()
                                    $('#chooseMore').html(compiledTpl).show();
                                    _this.setting.chackchange=true;
                                    _this.setting.hashchange=2;
                                })
                               // $("#chooseMore").popup();
                            }
                        },
                        {
                            text: "多行文本",
                            onClick: function() {
                                type=2;
                                var tpl = Simplite.getTemplate('tplAdd');
                                var data={
                                    text:'多行文本' ,
                                    id:1
                                }
                                var compiledTpl = Simplite.compile(tpl)(data);
                               // $('#chooseMore').html(compiledTpl);

                                _this.setting.chackchange=false
                                location.hash="#line=1"
                                $("#addMore").animate({'left':-_this.setting.window_w*2+'px'},200)

                                $('#chooseMore').animate({'left':'0px'},200,function(){
                                    $("#addMore").hide()
                                    $('#chooseMore').html(compiledTpl).show();
                                    _this.setting.chackchange=true;
                                    _this.setting.hashchange=2;
                                })
                                //$("#chooseMore").popup();
                            }
                        },
                        {
                            text: "单选问题",
                            onClick: function() {
                                type=3;
                                var tpl = Simplite.getTemplate('tplAdd');
                                var data={
                                    text:'单选问题' ,
                                    id:2
                                }
                                var compiledTpl = Simplite.compile(tpl)(data);
                                _this.setting.chackchange=false
                                location.hash="#line=1"
                                $("#addMore").animate({'left':-_this.setting.window_w*2+'px'},200)
                                $('#chooseMore').animate({'left':'0px'},200,function(){
                                    $("#addMore").hide()
                                    $('#chooseMore').html(compiledTpl).show();
                                    _this.setting.chackchange=true;
                                    _this.setting.hashchange=2;
                                })
                               // $('#chooseMore').html(compiledTpl);
                                //$("#chooseMore").popup();
                            }
                        },
                        {
                            text: "多选问题",
                            onClick: function() {
                                type=4;
                                var tpl = Simplite.getTemplate('tplAdd');
                                var data={
                                    text:'多选问题' ,
                                    id:3
                                }
                                var compiledTpl = Simplite.compile(tpl)(data);
                                _this.setting.chackchange=false
                                location.hash="#line=1"
                                $("#addMore").animate({'left':-_this.setting.window_w*2+'px'},200)

                                $('#chooseMore').animate({'left':'0px'},200,function(){
                                    $("#addMore").hide()
                                    $('#chooseMore').html(compiledTpl).show();
                                    _this.setting.chackchange=true;
                                    _this.setting.hashchange=2;
                                })
                               // $('#chooseMore').html(compiledTpl);
                                //$("#chooseMore").popup();
                            }
                        }
                    ]
                });
            })
            //添加自定义填写项
            $("#baoMing").on("click",function(){
                _this.setting.sing_up=$(this).attr('data-url');
                View.questionList=[];
                View.questionList=View.questionList.concat(question);
                _this.addInformation(false)

            })

            $(document).on("click",'.addQuestion',function(){
                if($(document).find(".question>div").length>=5){
                    $.toptip("最多只能添加5项", 'error')
                }else{
                    var tpl = Simplite.getTemplate('question');
                    var compiledTpl = Simplite.compile(tpl)([]);
                    $(document).find(".question").append(compiledTpl);
                }

            })
            $(document).on("click",'.del-question',function(){
                $(this).parent().hide().remove()
            })
            $(document).on("click",'#addMore .del',function(){
                var index=parseInt($(this).attr('data-index'))
                question.splice(index,1)
                $("#addMore .weui-cells_checkbox>div>div").eq(index).hide().remove()
            })
            $(document).on("click",'#btnMore',function(){
                if(type==1 || type==2){
                    if(!_this.validate($('#chooseMore input').val(),'g')){
                        $.toptip("请输入文本标题", 'error')
                        return false
                    }
                    if(_this.setting.title==question[0].name || _this.setting.title==question[1].name){
                        $.toptip("已设置改标题，不可重复设置", 'error')
                        return false
                    }
                     if(question.length>2){
                         for(var t=2;t<question.length;t++){
                              if(question[t].type==type){
                                  if(_this.setting.title==question[t].name){
                                      $.toptip("已设置改标题，不可重复设置", 'error')
                                      return false
                                  }
                              }

                         }
                     }
                    var datas={
                        'name': $('#chooseMore input').val(),
                        'type': type,
                        'is_required': 0,
                        'title': $('#chooseMore input').val()
                    }
                    question.push(datas)
                    console.log(View.questionList)
                     var end=$("#activiteEnd").html();
                    _this.addInformation(true)
                    window.history.go(-1)
                }else{
                    if(type===3){
                        if(!_this.validate($('#chooseMore input[name=单选问题]' ).val(),'g')){
                            $.toptip("请输入问题标题", 'error')
                            return false
                        }
                    }else{
                        if(!_this.validate( $('#chooseMore input[name=多选问题]' ).val(),'g')){
                            $.toptip("请输入问题标题", 'error')
                            return false
                        }
                    }
                    if($("#chooseMore .question>div").length<2){
                        $.toptip("问题选项最少为2项", 'error')
                        return false
                    }
                    if(question.length>2){
                        for(var d=2;d<question.length;d++){
                             if(question[d].type==type){
                                 if(_this.setting.title==question[d].name){
                                     $.toptip("已设置改标题，不可重复设置", 'error')
                                     return false
                                 }
                             }

                        }
                    }
                    for(var i=0;i<$("#chooseMore .question>div").length;i++){
                        var con=$("#chooseMore .question>div").eq(i).find("input").val();
                        if(!_this.validate( con,'g')){
                            $.toptip("请输入问题选择答案", 'error')
                            return false
                        }
                    }
                    var datas={
                        'name': type==3?$('#chooseMore input[name=单选问题]' ).val():$('#chooseMore input[name=多选问题]' ).val(),
                        'type': type,
                        'is_required': 0,
                        'title': type==3?$('#chooseMore input[name=单选问题]' ).val():$('#chooseMore input[name=多选问题]' ).val(),
                        'content':[]
                    }
                    for(var i=0;i<$("#chooseMore .question>div").length;i++){
                        var con=$("#chooseMore .question>div").eq(i).find("input").val();
                        datas.content.push(con)
                    }
                    question.push(datas)
                    var end=$("#activiteEnd").html();
                    _this.addInformation(true);
                    window.history.go(-1)
                }

                //$.closePopup();
            })
        },
        submit:function(form){
            var _this=this;
           $("#submit").on("click",function(){ 
               
              if(is_sign==''){
                   $.alert({
                        title: '提示',
                        text: '请先登录',
                        onOK: function () {
                             window.location.href='/wap.php?g=Wap&c=Login&a=index'
                        }
                   })
                   return false
              }
               if(!_this.validate($('input[name=title]').val(),'g')){
                   $.toptip("请输入活动主题", 'error')
                   return false
               }
               
            //    if($('.ImgPicBox li').size<2){
            //     $.toptip("请上传活动图片", 'error')
            //         return false
            //    }
               
           	    //  if($("input[name=file_img]").val()==''){
           	  	//    $.toptip("请上传活动图片", 'error')
                //     return false
           	    //  }
            	
                if($("input[name=cate_id]").val()==''){
                    $.toptip("请选择分类", 'error')
                    return false
                }
               var myreg = /^[1][3,4,5,7,8][0-9]{9}$/;
               if(!_this.validate($('input[name=phone]').val(),'g')){
                   $.toptip("请输入手机号", 'error')
                   return false
               }
               var phone = $('input[name=phone]').val();
               if (!myreg.test(phone.trim())) {
                   $.toptip("手机号码格式不正确", 'error');
                   return false
               }
                if($("#time-star").html()=='开始日期'){
                    $.toptip("请选择活动开始日期", 'error')
                    return false
                }
                if(new Date($("#time-star").html()).getTime()-new Date().getTime()<=0){
                    $.toptip("活动开始日期不能小于当前日期", 'error')
                    return false
                }
                 if($("#time-end").html()=='结束日期'){
                    $.toptip("请选择活动结束日期", 'error')
                    return false
                }
               if(new Date($("#time-end").html()).getTime()-new Date().getTime()<=0){
                   $.toptip("活动结束日期不能小于当前日期", 'error')
                   return false
               }

                if(!_this.validate($('textarea[name=desc]').val(),'g')){
                    $.toptip("请输入活动详细内容", 'error')
                    return false
                }
                if($("input[name=deadline]").val()!=''){
                    if(new Date($("input[name=deadline]").val()).getTime()-new Date().getTime()<=0){
                        $.toptip("活动报名日期不能小于活动开始日期", 'error')
                        return false
                    }
                }
               if(new Date($("input[name=deadline]").val()).getTime()-new Date($("#time-end").html().replace(/-/g,'/')).getTime()>0){
                   $.toptip("活动报名日期不能大于活动结束日期", 'error')
                   return false
               }
               if(!$("#weuiAgree").prop("checked")){
                   $.toptip("请同意协议", 'error')
                   return false
               }
               if($("input[name=fees]").val()>0){
                }
             $.showLoading('保存中')
         $("form").ajaxSubmit({ 
            beforeSubmit: function(data, elem, options){ 
                var img=[];
                $(".ImgPicBox li ").each(function(){
                    var imgSrc=$(this).find('img.ImgPic').attr('src');
					if(imgSrc!=null){
                        img.push(imgSrc);
                    }
                });
                img = img.join('||');

                var fileImg={
                    name:'fileImg',
                    'value':img
                }
                data.push(fileImg) 

            	var datas={
            		name:'custom',
            		'value':JSON.stringify(question)
            	}

            	 data.push(datas)
               console.log(data)
            },
            success: function(res) {
                
                console.log(res);
                $.hideLoading()
                var s=eval('(' + res + ')');
              if(s.errorMsg!='success'){
                  $.alert(s.errorMsg)
                  return false
              }else{
                  $.toast("保存成功",function(){
                      if(View.setting.activity_id!=''){
                          //编辑后的费用
                          var fee=$("input[name=fees]").val();
                           if(fee>0){
                              window.location.href=s.result.url
                           }else{
                              window.history.go(-1)
                           }
                      }else{
                          //添加
                         window.location.href=s.result.url
                      }


                  });
              }



            },
            error: function() {
            	$.hideLoading()
            	$.toast("保存失败", "cancel");
            }
          });
           })

        },
        validate:function(res,is_global){
            var _this=this;
            var result=res.replace(/(^\s+)|(\s+$)/g,"");
            if(is_global.toLowerCase()=="g") {
                _this.setting.title=result = result.replace(/\s/g,"");
                if(result==''){
                    return false
                }else{
                    return true
                }
            }
        },
        addInformation:function(type){
            var _this=this;
        	var time= $("input[name=deadline]").val()
        	var qw={
        		question:question,
        		time:'活动报名结束前均可报名'
        	}
        	 if(time!=''){

                	 	qw.time=time

                }
             var tpl = Simplite.getTemplate('ming');
            var compiledTpl = Simplite.compile(tpl)(qw);
            var wh=$(window).width();
            if(!type){
                $("#page__bd").animate({'left':-wh+'px'},200);
                _this.setting.chackchange=false
                location.hash=_this.setting.sing_up
                $('#addMore').animate({'left':'0px'},200,function(){
                    $("#page__bd").hide()
                    $('#addMore').html(compiledTpl).show();
                    _this.setting.hashchange=1
                    _this.setting.chackchange=true

                })
            }else{
                $('#addMore').html(compiledTpl);

            }


        },
        is_require:function(){
          $(document).on("click",'.is_require',function(e){
          	e.stopPropagation();
              var index=parseInt($(this).attr("data-index"))
          	  var input=$(this).find("input").prop('checked');
          	   if(input){
                  question[index].is_required=0
          	   }else{
                     question[index].is_required=1
          	   }
          	  console.log(input)
          })
        },
        back:function(){
            var _this=this;
            $(document).on("click",'.back',function(){
                //$.closePopup();
                window.history.go(-1)
            })
            $(document).on("click",'.backOne',function(){
                question=[];
                question=question.concat(View.questionList)
                //$('#addMore').html('').hide();
                //$("#page__bd").show().css('left','0px')
                window.history.go(-1)

            })
            $(document).on("click",'.curentOne',function(){
            	 var t=$("#activiteEnd").html();
            	  if(t!='活动报名结束前均可报名'){
                     $("input[name=deadline]").val(t)
            	  }else{
            	  	  $("input[name=deadline]").val('')
            	  }

                View.questionList=[];
                View.questionList=View.questionList.concat(question)
               // $('#addMore').html('').hide();
                //$("#page__bd").show()
                window.history.go(-1)

            })
            $(document).on("click",'.agree_ment',function(){
                var tpl = Simplite.getTemplate('aggrement');
                var datas={
                    hh:$(window).height()*0.6
                }
                var compiledTpl = Simplite.compile(tpl)(datas);
                $('#agree').html(compiledTpl);
                setTimeout(function () {
                    $(" #agree .yui-auth-form").removeClass("auth-animated")
                    //fixed()
                    _this.setting.top=$(window).scrollTop()
                    $("#body_content").addClass("fixed").css('top',_this.setting.top*(-1))
                },1000);
            })
            $(document).on("click",'.closeHide',function(){
                $(".yui-auth-form").addClass("close-animated");
                setTimeout(function () {
                    $("#agree").html("")
                    $('#body_content').removeClass('fixed');//去掉给body的类
                    $(window).scrollTop(_this.setting.top);//设置页面滚动的高度，如果不设置，关闭弹出层时页面会回到顶部。
                },1000);
            })
            $(document).on("click",'#new_address',function(){
                var wh=$(window).width();
                $("#page__bd").animate({'left':-wh+'px'},200);
                _this.setting.chackchange=false
                location.hash='#mar_adress=1'
                $('#map_baidu').animate({'left':'0px'},200,function(){
                    $("#page__bd").hide()
                    $('#map_baidu').show()
                    _this.setting.hashchange=8
                    _this.setting.chackchange=true;
                    if(_this.setting.is_check_map){

                    }else{
                        _this.open_map();
                    }

                })
            })


            $('#searchInput').bind(_this.setting.bindname ,function(){
                var val=$(this).val();
                if(val.trim()!=''){
                    $(".close_map").hide();
                    $("#searchAdd").html("");
                    $("#searchCancel").text("取消");
                    setTimeout(function(){
                        _this.search_adress(val);
                    },500)
                }
            })
            $("#searchClear").on("click",function(){
                $("#searchAdd").html("")
            })
            $("#searchCancel").on("click",function(){
                if($(this).html()=='取消'){
                    $("#searchAdd").hide().html("");
                    $(this).html('确定');
                    $(".close_map").show();
                }else{
                    _this.setting.lngs=_this.setting.check_lon;
                    _this.setting.lats=_this.setting.check_lat;
                    $('input[name=lat]').val(_this.setting.lats)
                    $('input[name=long]').val(_this.setting.lngs)
                    $('input[name=new_address]').val(_this.setting.check_adress1+'-'+_this.setting.check_adress);
                    $('#new_address h4').html(_this.setting.check_adress1+'-'+_this.setting.check_adress)

                    $('input[name=city_id]').val(_this.setting.city_id)
                    $('input[name=province_id]').val(_this.setting.province_id)
                    window.history.go(-1)
                }
            })
            $(document).on("click",'#searchMore label',function(){
                _this.setting.check_lat=$(this).attr("data-lat");
                _this.setting.check_lon=$(this).attr("data-long");
                _this.setting.city_id=$(this).attr("data-city_id");
                _this.setting.province_id=$(this).attr("data-province_id");
                _this.setting.check_adress1=$(this).find(".weui-media-box__title").html();
                _this.setting.check_adress=$(this).find(".weui-media-box__desc").html();
                var oPoint = new BMap.Point(_this.setting.check_lon,_this.setting.check_lat);
                _this.setting.map.centerAndZoom(oPoint, 16);
                $("#searchCancel").html('确定');
            })
            $(document).on("click",'#searchAdd label',function(){
                var check_lat=$(this).attr("data-lat");
                var check_lon=$(this).attr("data-long");
                $("#searchAdd").hide().html('');
                $(".close_map").show();
                $('#searchInput').val('');
                $("#searchCancel").html('确定');
                var oPoint = new BMap.Point(check_lon,check_lat);
                _this.setting.map.centerAndZoom(oPoint, 16);
                _this.getPositionInfo(check_lat,check_lon);
            })
            $(document).on("click",'#activiteEnd',function(){
                if($("#time-star").html()=='开始日期' ){
                        $.toptip("请选择活动开始时间", 'error')
                        return false

                }else if($("#time-end").html()=='结束日期'){
                        $.toptip("请选择活动结束时间", 'error')
                }
            })
            $(document).bind(_this.setting.bindname ,'input[name=fees]',function(){
                var money=$('input[name=fees]').val()
                if(/^\d+\.?\d{0,2}$/.test(money)){
                    $('input[name=fees]').val(money)
                }else{
                    $('input[name=fees]').val(money.substring(0,money.length-1))

                }
            })


            var d=/\d/
            $(document).bind(_this.setting.bindname ,'input[name=limit]',function(){
                if(!d.test($('input[name=limit]').val())){
                    $('input[name=limit]').val(0)
                }else{
                    $('input[name=limit]').val(parseInt($('input[name=limit]').val()))
                }

            })

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
            var _this=this;
            _this.setting.is_check_map=true;
            var window_w=$(window).width();
            $("#map_baidu").show();
            $("#shap_manger").hide();
            _this.setting.map = new BMap.Map("map_show",{"enableMapClick":false});
            _this.setting.map.enableScrollWheelZoom();
            _this.setting.map.enableContinuousZoom();
            _this.setting.map.centerAndZoom(new BMap.Point(116.331398,39.897445),6);
            _this.setting.map.addEventListener("dragend", function(e){
                var centerMap = _this.setting.map.getCenter();
                _this.getPositionInfo(centerMap.lat,centerMap.lng);
            });
            _this.setting.map.addEventListener('zoomend', function (e) {
                console.log(e)
                var centerMap = _this.setting.map.getCenter();
                console.log(centerMap);
                _this.getPositionInfo(centerMap.lat,centerMap.lng);
            });
            if(_this.setting.lngs == '' || _this.setting.lats == ''){
                var geolocation = new BMap.Geolocation();
                geolocation.getCurrentPosition(function(r){
                    if(this.getStatus() == BMAP_STATUS_SUCCESS){
                        var oPoint = new BMap.Point(r.point.lng, r.point.lat);
                        _this.setting.map.centerAndZoom(oPoint,16);
                        _this.setting.init_lat=r.point.lat;
                        _this.setting.init_lon=r.point.lng;
                        _this.getPositionInfo(r.point.lat,r.point.lng);

                    } else {
                        $.toast('failed'+this.getStatus(), "cancel");
                    }
                },{enableHighAccuracy: true})

            }else{
                var oPoint = new BMap.Point(_this.setting.lngs, _this.setting.lats);
                _this.setting.map.centerAndZoom(oPoint, 16);
                _this.getPositionInfo(_this.setting.lats,_this.setting.lngs);
            }
        },
        getPositionInfo:function(lat,lng){
            $.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&location='+lat+','+lng+'&output=json&pois=1&callback=getPositionAdress&json=?');
        }
    };
    window.View=View;
    View.init();
    function getPositionAdress(result){
        var window_h=$(window).height()
        if(result.status == 0){
            var res = result.result;
            // $("#map_baidu").css('height',window_h+'px')
            // $("#searchMore .weui-cells_radio").css('height',(window_h-250-48)+'px')
            // $("#searchAdd").css('height',(window_h-50)+'px')
            // $("#searchAdd").hide().html('')
            // $("#searchMore .weui-cells_radio").html(indexTpl)


           $.post("{pigcms{:U('Home/cityMatching')}",{'city_name':res.addressComponent.city,'area_name':res.addressComponent.district,'get_province':'1','all_city':'1'},function(data) {
               if(data.status == 1){
                    res.pois.city_id = data.info.city_id;
                    res.pois.province_id = data.info.province_id;
                    console.log('22222')
                    console.log(res.pois)
                    var index = Simplite.getTemplate('map_adress');
                    var indexTpl = Simplite.compile(index)(res.pois);
                    $("#map_baidu").css('height',window_h+'px')
                    $("#searchMore .weui-cells_radio").css('height',(window_h-250-48)+'px')
                    $("#searchAdd").css('height',(window_h-50)+'px')
                    $("#searchAdd").hide().html('')
                    $("#searchMore .weui-cells_radio").html(indexTpl)

               }else{
                   alert('当前城市不可用');
               }
           });
        }else{
            alert('获取位置失败！');
        }
    } 

    $(document).on("click",'.closeImg',function(){ 
        $(this).parents('li').remove(); 
        $('#class_Img').show();   
     })  
</script>
</body>
</html>