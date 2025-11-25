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
    <link rel="stylesheet" type="text/css" href="https://hf.pigcms.com/tpl/Wap/pure/static/classifynew/css/weui.css">
    <link rel="stylesheet" type="text/css" href="workIcon.css">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="https://hf.pigcms.com/tpl/Wap/pure/static/classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="https://hf.pigcms.com/static/activity/assets/js/components/jquery-form/jquery-plugin-form.js"></script>
    <script src="https://hf.pigcms.com/static/activity/assets/js/common.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="https://hf.pigcms.com/static/activity/assets/js/components/simplite/simplite.js"></script>
    <script type="text/javascript" src="validator.js"></script>
    <style>
        body{    background: #f4f4f4;}

        .pd0{padding:0px}
        .pd-r10{padding-right:10px}
        .mgt10{margin-top:10px}
        .font13{font-size:13px}
        .color_999{color:#999}
        .mgt0{margin-top:0px}
        .after:after,.before:before{content:none !important;}
        .pd0-5{padding:0px 5px}
        .align_center{text-align:center}
        .qiuzhi .weui-cell{    height: 80px;padding: 20px 15px;box-sizing: border-box;}
        .qiuzhi .weui-cell input{position:absolute;width:100%;height:100%;z-index:10;opacity: 0}
        .demos-content-padded{position: fixed;
            bottom: 0px;
            padding:15px;
            left: 0px;
            right: 0px;
            background:#fff;
        }
        .weui-btn_primary {
            background-color: #ff4d00;
        }
        .coupon_mask {position: fixed;top: 0px;left: 0px;background: rgba(0,0,0,0.7);z-index: 888;width: 100%;height: 100%;}
        .rel{position:relative}
        .weui-btn_primary:not(.weui-btn_disabled):active {color: #FFFFFF !important;background-color: rgba(255, 77, 0, 0.4)}
        .my_star{height:20%}
        .my_photo{background: url(https://hf.pigcms.com/upload/adver/000/000/030/590c4b6ad74da520.png) no-repeat center;height: 185px;
            background-size: 100% 100%; position:relative }
        .imgBox{
            width: 75px;
            -webkit-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
            -webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            position: absolute;
            left: 50%;
            top: 90%;
            height: 75px;
            border-radius: 50%;
            border: 1px solid #3C7BC2;
            background:#fff
        }
        .imgBox input {width: 100%;
            height: 75px;
            opacity: 0;}
        .iconfont{
            -webkit-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
            -webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            position: absolute;
            left: 50%;
            top: 50%;
            border-radius: 50%;
            font-size: 45px;
            color:#3C7BC2;
            z-index: -1;
        }
        .img_content{
            color: #3C7BC2;
            text-align: center;
            padding-top: 30px;
        }
        .flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; align-items: center;  }
        .flex_one{  flex:1;  -webkit-box-flex: 1;  -webkit-flex: 1;  }
         .weui-cells_radio .sex .weui-check + .weui-icon-checked:before {
            content:  '\EA01';
        }
        .weui-cells_radio .sex .weui-check:checked + .weui-icon-checked:before {
            content: '\EA06';
        }
        .pd_15 {
           padding: 10px 15px;
        }
        .cursor {
            cursor: pointer !important;
        }

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
        .bgImg{height:100%;    overflow: hidden;}
        .bgImg img{
            height: 100%;
            width: 100%;
            border-radius: 50%;
            display:none
        }
        .demos-content-padded {
            position: fixed;
            bottom: 0px;
            padding: 15px;
            left: 0px;
            right: 0px;
            background: #fff;
            z-index: 99;
        }
        .weui-btn_primary {
            background-color: #ff4d00;
        }
        

    </style>
</head>
<body>
   <div>
       <form id="submitForm" action="/wap.php?g=Wap&c=Employee&a=employee_reply" method="post" >
           <div style="padding-bottom:80px">
               <div class="my_star">
                   <div class="my_photo">
                       <div class="imgBox">
                           <div class="bgImg">
                               <img src="" />
                               <input id="uploaderInput" class="weui-uploader__input" type="file" name="file[]" accept="image/*" capture=“camera” data-rules='{"required":true}' data-messages='{"required":"请上传头像"}'>
                               <i class="iconfont " style="    padding-top: 2px;padding-right: 3px;">&#xe683;</i>
                           </div>

                       </div>

                   </div>
                   <p class="img_content">头像,一份好工作的开始</p>
               </div>
               <div class="weui-cells weui-cells_form weui-cells_radio">
                   <div class="weui-cell">
                       <div class="weui-cell__hd"><label class="weui-label">姓名</label></div>
                       <div class="weui-cell__bd">
                           <input class="weui-input" type="text"  placeholder="请输入姓名"  data-rules='{"required":true}' data-messages='{"required":"请输入姓名"}'>
                       </div>
                   </div>
                   <div class="weui-cell weui-check__label sex" >
                       <div class=" weui-cell__hd">
                           <label class="weui-label">性别</label>

                       </div>
                       <div class="weui-cell__bd flex">
                           <label class=" " >
                               <div style="margin-right:20px">
                                   男
                                   <input type="radio" class="weui-check" name="checkbox1"  checked="checked" data-rules='{"required":true}' data-messages='{"required":"请选择性别"}'>
                                   <i class="weui-icon-checked"></i>
                               </div>
                           </label>
                           <label class=" " >
                               <div>
                                   女
                                   <input type="radio" class="weui-check" name="checkbox1"  data-rules='{"required":true}' data-messages='{"required":"请选择性别"}'>
                                   <i class="weui-icon-checked"></i>
                               </div>
                           </label>


                       </div>
                   </div>
                   <div class="weui-cell weui-cell_vcode">
                       <div class="weui-cell__hd">
                           <label class="weui-label">手机号</label>
                       </div>
                       <div class="weui-cell__bd">
                           <input class="weui-input" type="tel" placeholder="请输入手机号"  data-rules='{"required":true,"isMobile":true}' data-messages='{"required":"请输入手机号码","isMobile":"请输入有效的手机号"}'>
                       </div>
                       <div class="weui-cell__ft">
                           <button class="weui-vcode-btn">获取验证码</button>
                       </div>
                   </div>
                   <div class="weui-cell ">
                       <div class="weui-cell__hd"><label class="weui-label">验证码</label></div>
                       <div class="weui-cell__bd">
                           <input class="weui-input" type="number" placeholder="请输入验证码"   data-rules='{"required":true}' data-messages='{"required":"请输入短信验证码"}'>
                       </div>

                   </div>
                   <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                       <div class="weui-cell__hd">
                           <label for="" class="weui-label">出生年龄</label>
                       </div>
                       <div class="weui-cell__bd">
                           <input class="weui-input" type="text" name="age" id="age" readonly  placeholder="请选择出生年龄"  data-rules='{"required":true}' data-messages='{"required":"请选择出生年龄"}'>
                           <input class="weui-input" type="hidden" name="age" >
                       </div>
                   </div>
                   <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                       <div class="weui-cell__hd">
                           <label for="" class="weui-label">最高学历</label>
                       </div>
                       <div class="weui-cell__bd">
                           <input class="weui-input" type="text" name="select3" id="education" readonly  placeholder="请选择学历要求" data-rules='{"required":true}' data-messages='{"required":"请选择学历要求"}'>
                           <input class="weui-input" type="hidden" name="education" >
                       </div>
                   </div>
                   <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                       <div class="weui-cell__hd">
                           <label for="" class="weui-label">工作时间</label>
                       </div>
                       <div class="weui-cell__bd">
                           <input class="weui-input" type="text" name="select4" id="workHouse" readonly  placeholder="请选择工作时间"  data-rules='{"required":true}' data-messages='{"required":"请选择工作时间"}'>
                           <input class="weui-input" type="hidden" name="workHouse" >
                       </div>
                   </div>
               </div>

            <div class="mgt10 qiuzhi">
                <div class="weui-cells">
                    <div class="weui-panel__hd after before">求职意向</div>
                    <div class="weui-cell before zhiwei">
                        <a class="rel" href="{pigcms{:U('position_select')}" style="display:block;width:100%">
                            <input type="hidden" name="zhiwei" data-rules='{"required": true}' data-messages='{"required": "请选择期望职位"}'/>
                            <div class="weui-cell__bd color_999 align_center">
                                <p>期望职位</p>
                            </div>
                        </a>

                    </div>
                    <div class="weui-cell money">
                        <input type="text" name="money" data-rules='{"required": true}' data-messages='{"required": "请选择期望薪资"}'/>
                        <div class="weui-cell__bd color_999 align_center">
                            <p>期望薪资</p>
                        </div>
                    </div>
                    <div class="weui-cell area">
                        <a class="rel" href="selectArea.html" style="display:block;width:100%">
                            <input type="text" name="area" data-rules='{"required": true}' data-messages='{"required": "请选择求职区域"}'/>
                            <div class="weui-cell__bd color_999 align_center">
                                <p>求职区域</p>
                            </div>
                        </a>

                    </div>
                </div>
            </div>
           </div>
       </form>

       <div class="demos-content-padded">
           <a href="javascript:;" class="weui-btn weui-btn_primary submit">保存</a>
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
  <script>
       var View={
           init:function(){
               this.refresh();
               this.initClick();
               this.uploaderFile();
               this.submit()
           },
           setting:{
               age:[]
           },
           refresh:function(){
               var self=this;
               var year=new Date().getFullYear();
               console.log(year)
               for(var i=(year-60);i<(year-16);i++){
                   self.setting.age.push(i)
               }
               $("#education").select({
                   title: "请选择学历要求",
                   items: [
                       {
                           title: "博士",
                           value: "001",
                       },
                       {
                           title: "硕士",
                           value: "002",
                       },
                       {
                           title: "本科",
                           value: "003",
                       },
                       {
                           title: "大专",
                           value: "004",
                       },
                       {
                           title: "不限",
                           value: "005",
                       },
                       {
                           title: "iPhone 6P",
                           value: "006",
                       },
                       {
                           title: "iPhone 6SP",
                           value: "007",
                       },
                       {
                           title: "iPhone SE",
                           value: "008",
                       },
                       {
                           title: "iPhone 7",
                           value: "009"
                       }
                   ],
                   onChange: function(d) {
                       $("input[name=education]").val(d.values)

                   },
                   onClose: function() {
                       $(".coupon_mask").hide()
                   },
                   onOpen: function() {
                       $(".coupon_mask").show()
                   },
               });
               $("#workHouse").select({
                   title: "请选择工作年限",
                   items: [
                       {
                           title: "5年以上",
                           value: "001",
                       },
                       {
                           title: "3-5年",
                           value: "002",
                       },
                       {
                           title: "1-3年",
                           value: "003",
                       },
                       {
                           title: "不限",
                           value: "0",
                       }
                   ],
                   onChange: function(d) {
                       $("input[name=workHouse]").val(d.values)

                   },
                   onClose: function() {
                       $(".coupon_mask").hide()
                   },
                   onOpen: function() {
                       $(".coupon_mask").show()
                   },
               });
               $("#age").picker({
                   title: "请选择出生年龄",
                   model:true,
                   cols: [
                       {
                           textAlign: 'center',
                           values:self.setting.age
                       }
                   ],
                   onChange: function(d) {


                   },
                   onClose: function() {
                       $(".coupon_mask").hide()
                   },
                   onOpen: function() {
                       $(".coupon_mask").show()
                   },
               });$("input[name=money]").select({
                     title: "请选择您期望的薪资范围",
                     items: [
                         {
                             title: "面议",
                             value: "001",
                         },
                         {
                             title: "1000元以下",
                             value: "002",
                         },
                         {
                             title: "1000-2000元",
                             value: "003",
                         },
                         {
                             title: "2000-3000元",
                             value: "003",
                         },
                         {
                             title: "3000-5000元",
                             value: "003",
                         },
                         {
                             title: "5000-8000元",
                             value: "003",
                         },
                         {
                             title: "8000-12000元",
                             value: "003",
                         },
                         {
                             title: "12000-20000元",
                             value: "003",
                         },
                         {
                             title: "20000-22000元",
                             value: "003",
                         },
                         {
                             title: "22000元以上",
                             value: "0",
                         }
                     ],
                     onChange: function(d) {

                         if(d.values){
                             var cat_data = Simplite.getTemplate('model');
                             var cat_all = Simplite.compile(cat_data)({'content':'薪资期望',value:d.titles} );
                             $('.money .weui-cell__bd').html(cat_all);
                         }


                     },
                     onClose: function() {
                         $(".coupon_mask").hide()
                     },
                     onOpen: function() {
                         $(".coupon_mask").show()
                     },
                 });
           },
           initClick:function(){
            
           },
           uploaderFile:function(){
               var self=this;
               //  上传图片
             $("#uploaderInput").on("change",function(event, dataUrl){
                 var $this = $(this);
                 var getObjectURL = function (file) {
                     if(file){
                         if (window.createObjectURL != undefined) {
                             dataUrl = window.createObjectURL(file)
                         } else if (window.URL != undefined) {
                             dataUrl = window.URL.createObjectURL(file)
                         } else if (window.webkitURL != undefined) {
                             dataUrl = window.webkitURL.createObjectURL(file)
                         }


                     }

                 };
                 getObjectURL($(this)[0].files[0])
               $(".bgImg img").attr("src",dataUrl).show()
                 console.log(dataUrl)

             })


           },
           submit:function(){
               $(".submit").on('click',function(){
                   $("#submitForm").ajaxSubmit({
                       beforeSubmit: function(data, elem, options){
                           if(!Validator.getData(false,'#submitForm')) return false
                           window.location.href='myBasicsResume.html'
                       },
                       success: function(res) {


                       },
                       error: function() {

                       }
                   });

               })
           }
       }
       window.View=View;
       window.addEventListener("pageshow", function(){
           View.init();
           if(/iPhone|iPod/i.test(navigator.userAgent)) {
               //手机端
              $("input[name=file]").removeAttr("capture")
           } else {
               //浏览器

           }

       });

  </script>
</body>
</html>