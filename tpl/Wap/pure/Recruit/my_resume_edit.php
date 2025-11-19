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
    <title>我的简历</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/weui.min.css?t={pigcms{:time()}"/>
    <script src="{pigcms{$static_path}villagegroup/js/weui.min.js"></script>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <script src="{pigcms{$static_public}activity/assets/js/common.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/simplite/simplite.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/jquery-form/jquery-plugin-form.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/validator.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
    <style>
        .main_color {
            color: #ff4d00;
        }
        .weui-media-box_appmsg .weui-media-box__hd {
            width: 20px;
            height: 20px;
            line-height: 20px;
        }
        body{background:#f4f4f4}
        .bg_f{background:#fff}
        .color_999{color:#999}
        .align_center{text-align:center}
        .pd30-15{padding:30px 15px}
        .bres{    border: 1px solid #ff4d00;border-radius: 100px;padding: 3px 5px;}
        .right{float:right}
        .pd0{padding:0px}
        .weui-uploader__file a {
            position: absolute;
            top: 0;
            right: 0;
            width: 20px;
            height: 20px;
        }
        .rel{position:relative}
        .demos-content-padded {
            position: fixed;
            bottom: 0px;
            padding: 15px;
            left: 0px;
            right: 0px;
            background: #fff;    
            z-index: 2;
        }
        .weui-btn_primary {
            background-color: #ff4d00;
        }
        .weui-btn_primary:not(.weui-btn_disabled):active {color: #FFFFFF !important;background-color: rgba(255, 77, 0, 0.4)}
        #full input{caret-color: #ff4d00;}
        .flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; align-items: center;  }
        .flex_one{  flex:1;  -webkit-box-flex: 1;  -webkit-flex: 1;  }
        .font13{font-size:13px}
        .mgt0{margin-top:0px !important;}
        .before:before{content:none !important;}
        .after:after{content:none !important;}
        .pdt0{padding-top:0px}

        .coupon_mask {
            position: fixed;
            top: 0px;
            left: 0px;
            background: rgba(0,0,0,0.7);
            z-index: 888;
            width: 100%;
            height: 100%;
            display: none;
        }
	.weui-cells_radio .weui-check + .weui-icon-checked:before {font-family: Muiicons;display: block;content:url('http://www.group.com/tpl/Wap/pure/static/recruit/images/checkBtn.png');font-size: 16px;}
        .weui-cells_radio .weui-check:checked + .weui-icon-checked:before { font-family: Muiicons;display: block;content:url('http://www.group.com/tpl/Wap/pure/static/recruit/images/checkBtnHover.png');color: #09BB07;font-size: 16px;}
        .weui-cells_radio .weui-check + .weui-icon-checked:before{ color:#999}
    </style>
</head>
<body>
<form id="submitForm" action="" method="post" style="padding-bottom:80px">
    <input type="hidden" name="resume_id" value="{pigcms{$_GET['resume_id']}">
    <div class="weui-panel__bd bg_f">
        <div  class="weui-media-box weui-media-box_appmsg">
            <div class="weui-media-box__hd">
                <i class="weui-icon-warns iconfont main_color">&#xe634;</i>

            </div>
            <div class="weui-media-box__bd link" data-url="{pigcms{:U('my_resume_base',array('resume_id'=>$_GET['resume_id']))}">
                <h4 class="weui-media-box__title">基本简历完善成功</h4>
                <p class="weui-media-box__desc">完善以下信息,可提升求职效率</p>
            </div>
        </div>

    </div>
    <div class=" ">
        <div class="weui-cells">
            <div class="weui-panel__hd after before">工作经历</div>
            
            <volist name="now_resume.work_experience" id="vo">
                <div class="weui-cell weui-cell_access">
                    <div class="weui-cell__bd font13 color_999">
                        <p>{pigcms{$vo.start_time|date="Y-m-d",###}-{pigcms{$vo.end_time|date="Y-m-d",###}</p>
                    </div>
                    <div class="weui-cell__ft font13 color_999 after" style="padding-right:0px">
                        <!-- <a href="myVitae.html" class="color_999"> <i class="iconfont font13" style="padding-left:3px"></i></a> -->
                    </div>
                </div>
                <div class="weui-panel__bd ">
                    <div  class="weui-media-box weui-media-box_appmsg pdt0">
                        <div class="weui-media-box__bd">
                            <h4 class="weui-media-box__title">{pigcms{$vo.name}</h4>
                            <p class="weui-media-box__desc">{pigcms{$vo.desc}</p>
                        </div>
                    </div>
                </div>
            </volist>
            <div class="weui-cell pd30-15 before">
                <div class="weui-cell__bd main_color align_center">
                    <span class="bres work">+&nbsp;添加工作经历</span>
                </div>
            </div>
        </div>
        <div class="weui-cells">
            <div class="weui-panel__hd after before">教育经历</div>
            <volist name="now_resume.education_experience" id="vo">
                <div class="weui-cell weui-cell_access">
                    <div class="weui-cell__bd font13 color_999">
                        <p>{pigcms{$vo.start_time|date="Y-m-d",###}-{pigcms{$vo.end_time|date="Y-m-d",###}</p>
                    </div>
                    <div class="weui-cell__ft font13 color_999 after" style="padding-right:0px">
                        <!-- <a href="myVitae.html" class="color_999"> <i class="iconfont font13" style="padding-left:3px"></i></a> -->
                    </div>
                </div>
                <div class="weui-panel__bd ">
                    <div  class="weui-media-box weui-media-box_appmsg pdt0">
                        <div class="weui-media-box__bd">
                            <h4 class="weui-media-box__title">{pigcms{$vo.name}</h4>
                            <p class="weui-media-box__desc">{pigcms{$vo.desc}</p>
                        </div>
                    </div>
                </div>
            </volist>
            <div class="weui-cell pd30-15 before">
                <div class="weui-cell__bd main_color align_center">
                    <span class="bres education">+&nbsp;添加教育经历</span>
                </div>
            </div>
        </div>
       <!--  <div class="weui-cells">
            <div class="weui-panel__hd after before">添加照片<span class="right">最多添加8张照片</span></div>
            <div class="weui-cell  photoModel" >
                <div class="weui-cell__bd">
                    <div class="weui-uploader">
                        <div class="weui-uploader__bd">
                            <ul class="weui-uploader__files" id="uploaderFiles">
                            </ul>
                            <div class="weui-uploader__input-box">
                                <input id="uploaderInput" class="weui-uploader__input" type="file" name="file[]" accept="image/*" multiple=""  capture=“camera”>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
        <div class="weui-cells">
            <div class="weui-panel__hd after before">简单介绍一下自己吧</div>
            <div class=" weui-cells_form">
                <div class="weui-cell">
                    <div class="weui-cell__bd">
                        <textarea class="weui-textarea" placeholder="请输入文本" rows="3" name="self_introduction">{pigcms{$now_resume.self_introduction}</textarea>
                    </div>
                </div>
            </div>
        </div>


    </div>
</form>
<div class="demos-content-padded">
    <a href="javascript:;" class="weui-btn weui-btn_primary submit">完成</a>
</div>
<div id="full" class="weui-popup__container ">
    <div class="weui-popup__modal rel">
        <div style="padding-bottom:80px">
            <div class="mgt10 qiuzhi">
                <div class="weui-cells">
                    <div class="weui-cell full">
                            <div class="weui-cell__bd color_999 align_center">
                                <p class="color_999 name">公司名称</p>
                                <input type="text" class="weui-input align_center" id="name" />
                            </div>
                    </div>
                    <div class="weui-cell full">
                        <div class="weui-cell__bd color_999 align_center">
                            <p class="color_999 desc">职位名称</p>
                            <input type="text" class="weui-input align_center" id="desc" placeholder="请输入职位"/>
                            <input type="text" class="weui-input align_center" id="qualifications" placeholder="请选择学历" readonly/>
                            <input class="weui-input" type="hidden" name="qualifications" value="">
                           
                        </div>
                    </div>
                    <div class="weui-cell full">
                        <div class="weui-cell__bd color_999 align_center flex">
                             <div class="flex_one">
                                 <div class="start-time" id='start-time'>
                                     <p class="font13">开始时间</p>
                                     <span>请选择</span>
                                 </div>
                                 <div >
                                     <input type="hidden"/>
                                 </div>
                             </div>
                            <div class="flex_one">
                                <div class="end-time" id='end-time'>
                                    <p class="font13">结束时间</p>
                                    <span>请选择</span>
                                    </div>
                                <div >
                                    <input type="hidden"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="demos-content-padded close flex">
            <a href="javascript:;" class="weui-btn weui-btn_primary closePopup mgt0" style="width:45%">返回</a>
            <a href="javascript:;" class="weui-btn weui-btn_primary openPopup mgt0 save" style="width:45%">完成</a>
        </div>
    </div>
</div>
<div class="coupon_mask cursor" ></div>
<!-- 上传预览模板 -->
<script type="text/html" id="uploadImageTemplate">
    <& for(var i = 0; i < _this.length; i++){ &>
    <& var obj = _this[i]; &>
    <li class="weui-uploader__file  rel" style="background-image:url(<&= obj.src &>)"><a href="javascript:void(0);" class="upload_delete item" title="删除"><i class="weui-icon-warns iconfont">&#xe612;</i></a></li>
    <& } &>
</script>
<script type="text/html" id="model">
    <p class="font13"><&= _this.content&></p>
    <p style="color:#000"><&= _this.value&></p>
</script>
<script>
    // 学历
    var qualifications_arr = JSON.parse('{pigcms{$qualifications_arr}');
     var View={
          init:function(){
              this.initClick();
              this.uploaderFile();
              this.refresh();
              this.submit()
          },
         settings:{
             img:[],
             start_time:null,
             end_time:null,
             qualifications:0,
             qualifications_txt:'',
         }, 
         refresh:function(){
                var self=this;
                $("#qualifications").select({
                   title: "请选择学历",
                   items: qualifications_arr,
                   onChange: function(d) {
                    console.log(d)
                       $("#desc").val(d.titles)
                       $("input[name=qualifications]").val(d.values)
                   },
                   onClose: function() {
                       $(".coupon_mask").hide()
                   },
                   onOpen: function() {
                       $(".coupon_mask").show()
                   },
               });
           },
         uploaderFile:function(){
             var self=this;
             //  上传图片
             var $removeContainer = $('#uploaderFiles');
             APP.Uploader({
                 element: '#uploaderInput',
                 removeContainer: $removeContainer,
                 onValidate: function() {
                     return {
                         invalid: false,
                         message: ''
                     }
                 },
                 onSelectValid: function(item, list) {
                     APP.render({
                         element: $removeContainer,
                         templateId: 'uploadImageTemplate',
                         replaceAll: false,
                         data: [{
                             src: item.src,
                             info: item.value
                         }]
                     });
                     //    $(".loadedNum").html(list.length)
                     //  $(".leftNum").html((8-(list.length)));
                     $removeContainer.find('.weui-uploader__file').last().append(item.file);
                     self.settings.img=list
                 },
                 onDetail:function(list){
                     $(".loadedNum").html(list.length);
                     $(".leftNum").html(8-(list.length));
                     self.settings.img=list
                 }
             });

         },
         initClick:function(){
             var self=this;
            $(".addPic").on("click",function(){
                 $(".addPhoto").hide();
                 $(".photoModel").show()
            })
             $(".work").on("click",function(){
                $('.name').html('公司名称');
                $('.desc').html('职位');
                $('#desc').show();
                $('#qualifications').hide();
                $('.save').data('type',1)
                $("#full").popup();

             })
             $(".education").on("click",function(){
                $('.name').html('学校名称');
                $('.desc').html('学历');

                $('#desc').hide();
                $('#qualifications').show();

                $('.save').data('type',2)
                $("#full").popup();

             })
             $(".closePopup").on("click",function(){
                 $.closePopup();
             })
             $(".full").on("click",function(e){
                  e.stopPropagation();
                  e.preventDefault();
                  $(this).find("input").focus()
             })

             $(".save").on("click",function(e){
                var data = {};
                data.resume_id ='{pigcms{$_GET["resume_id"]}';
                data.start_time = self.settings.start_time;
                data.end_time = self.settings.end_time;
                data.name = $('#name').val();
                data.type = $(this).data('type');
                if (data.type==1) {
                    data.desc = $('#desc').val();
                }else{
                    data.desc = $('#qualifications').val();
                }
                $.post("{pigcms{:U('ajax_add_resume_detail')}",data,function(result){
                    console.log(result)
                    if(result.errorCode>0){
                        $.alert(result.errorMsg)
                        return false
                    }else{
                        window.location.reload();
                    }
                },'json')
             })

             $('.start-time, .end-time').on('click', function(){
                $(".coupon_mask").show()
                var id = $(this).attr('id')
                var today = new Date()
                var defaultValue = [today.getFullYear(), today.getMonth() + 1, today.getDate()]
                weui.datePicker({
                     start: 1900, // 从今天开始
                     end: today.getFullYear(),
                     defaultValue: defaultValue,
                     onChange: function(result){
                         console.log(result);
                     },
                     onConfirm: function(result){
                         console.log(result);
                         $('#' + id + ' span').html(result[0] + '-' + result[1] + '-' + result[2])
                         if (id=='start-time') {
                            self.settings.start_time = result[0] + '-' + result[1] + '-' + result[2];
                         }else if(id=='end-time'){
                            self.settings.end_time = result[0] + '-' + result[1] + '-' + result[2];
                         }
                       $(".coupon_mask").hide()
                     },
                     id: id
                 });

             })
         },
        submit:function(){
            var _this=this;
            var validate=function(res,is_global){
                var result=res.replace(/(^\s+)|(\s+$)/g,"");
                if(is_global.toLowerCase()=="g") {
                    if(result==''){
                        return false
                    }else{
                        return true
                    }
                }
            }
            $(".submit").on('click',function(){
                $("#submitForm").ajaxSubmit({
                    success: function(res) {
                        console.log(res)
                        res = JSON.parse(res);
                        if (res.code) {
                            layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:res.msg,btn:['好的'],shadeClose:false,yes:function(){
                                if(res.url!=''){
                                    window.location.href= res.url;
                                }
                                layer.closeAll();
                            }});
                        }else{
                            layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:res.msg,btn:['好的'],shadeClose:false,yes:function(){
                                if(res.url!=''){
                                    window.location.href= res.url;
                                }
                                layer.closeAll();
                            }});
                        }
                        console.log(res)

                    },
                    error: function(e) {
                        alert(e)
                    }
                });

            })
        },
     }
     window.View=View;
     View.init();
     window.addEventListener("pageshow",function(){
         if(/iPhone|iPod/i.test(navigator.userAgent)) {
             $("input[name=file]").removeAttr("capture")
         } else {

         }
     })
</script>
</body>
</html>