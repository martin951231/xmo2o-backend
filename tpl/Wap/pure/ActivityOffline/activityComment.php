
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Write an awesome description for your new site here. You can edit this line in _config.yml. It will appear in your document head meta (for Google search results) and in your feed.xml site description.">
    <title>活动</title>
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.min.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
    <!--[if lte IE 8]>
    <script type="text/javascript" src="static/activity/assets/js/components/respond/1.4.2/respond.min.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/html5shiv/3.7.2/html5shiv.min.js"></script>
    <![endif]-->
    <style>
        body{    background-color: #f0efed;}
        .rel{position:relative}
        .no:after, .no:before{
            content: none !important;
        }
        .ui-b:after {
            content: " ";
            position: absolute;
            left: 0;
            right: 0;
            height: 1px;
            color: #e5e5e5;
            z-index: 0;
            bottom: 0;
            border-bottom: 1px solid #e5e5e5;
            -webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            -webkit-transform: scaleY(.5);
            transform: scaleY(.5);
        }
        .ui-t:before {
            content: " ";
            position: absolute;
            left: 0;
            right: 0;
            height: 1px;
            color: #e5e5e5;
            z-index: 2;
            top: 0;
            border-bottom: 1px solid #e5e5e5;
            -webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            -webkit-transform: scaleY(.5);
            transform: scaleY(.5);
        }
        .list {
            margin-top: 10px;
            margin-bottom: 0;
            background-color: #fff;
        }
        h6 {
            font-size: .3rem;
            font-weight: normal;
            margin-bottom: .2rem;
        }
        .btn-wrapper {
            margin: .28rem .2rem;
        }
        .score {
            position: relative;
        }
        .score:before, .score :after {
            content: '★★★★★';
            position: absolute;
            font-family: 'base_icon';
            font-size: .5rem;
            color: #e9e9e9;
            letter-spacing: .4rem;
            line-height: 1em;
            left: 0;
            z-index:0
        }
        .label input {
            opacity: .0;
            width: 100%;
            height: 100%;
            -webkit-appearance: initial;
            outline: none;
            z-index: 3;
        }
         .label {
            display: inline-block;
            width: .84rem;
            height: .5rem;
        }
        .score span {
            position: absolute;
            visibility: hidden;
            color: #e9e9e9;
            top: 0;
            left: 0;
            width: 100%;
            line-height: 1.9em;
            font-size: 14px;
            pointer-events: none;
            text-align: right;
        }

        .fen{
            color: #f49231;
            float: right;
            margin: 5px 10px 0px 0px;
        }
        .score input:checked + span {
            visibility: visible;
        }
       .score_1:after {
            content: '★' !important;
           color: #f49231;
           position: absolute;
           font-family: 'base_icon';
           font-size: .5rem;
           letter-spacing: .4rem;
           line-height: 1em;
           left: 0;
           z-index:0
        }
        .score_2:after {
            content: '★★' !important;
            color: #f49231;
            position: absolute;
            font-family: 'base_icon';
            font-size: .5rem;
            letter-spacing: .4rem;
            line-height: 1em;
            left: 0;
            z-index:0
        }
        .score_3:after {
            content: '★★★' !important;
            color: #f49231;
            position: absolute;
            font-family: 'base_icon';
            font-size: .5rem;
            letter-spacing: .4rem;
            line-height: 1em;
            left: 0;
            z-index:0
        }
        .score_4:after {
            content: '★★★★' !important;
            color: #f49231;
            position: absolute;
            font-family: 'base_icon';
            font-size: .5rem;
            letter-spacing: .4rem;
            line-height: 1em;
            left: 0;
            z-index:0
        }
        .score_5:after {
            content: '★★★★★' !important;
            color: #f49231;
            position: absolute;
            font-family: 'base_icon';
            font-size: .5rem;
            letter-spacing: .4rem;
            line-height: 1em;
            left: 0;
            z-index:0
        }
        textarea {
            width: 100%;
        }
        .react .kv-line {
            margin: 0;
        }
        textarea {
            width: 100%;
        }

        #uploadNum{
            text-align: center;
            line-height: 30px;
            color: #737373;
        }
        #upload_list .upload_action, #upload_list .upload_item {
            width: 25%;
            float: left;
            position: relative;
            display: -webkit-box;
            -webkit-box-pack: center;
            -webkit-box-align: center;
            border: solid 5px #fff;
            -webkit-box-sizing: border-box;
            overflow: hidden;
        }
        .clearfix:before, .clearfix:after {
            content: " ";
            display: table;
        }
        .clearfix:after {
            content: '.';
            font-size: 0;
            height: 0;
            visibility: hidden;
            display: block;
            clear: both;
        }
        #upload_list .upload_action img {
            display: block;
            width: 100%;
            border: 0;
        }
        .weui-uploader__input-box  input{
            opacity: 0;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
        }
        .weui-uploader__file a{
            position: absolute;
            top: 0;
            right: 0;
            width: 20px;
            height: 20px;
            line-height: 20px;
            background: url(static/activity/assets/image/upimg_x.png) no-repeat;
            background-size: cover;
        }
        .upload_box .upload_item img {
            max-width: 100%;
            max-height: 100%;
            display: none;
        }
        .weui-toast p{text-align:center !important;}
        .weui-cells:after, .weui-cells:before {
            z-index: 0 !important;
        }
    </style>
</head>

<body>
    <div class="demos-content-padded">
        <form id="submitForm" action="{pigcms{:U('ActivityOffline/add_activity_reply')}" method="post">
            <dl class="list">
                <dd class="dd-padding">
                    <h6>评分</h6>
                    <div >
                        <div class="score score_5"></div>
                        <div>
                            <label class="label rel">
                                <input type="radio"  value="1" name="reply_star">
                            </label>
                            <label class="label rel">
                                <input type="radio"  value="2" name="reply_star">
                            </label>
                            <label class="label rel">
                                <input type="radio"  value="3" name="reply_star">
                            </label>
                            <label class="label rel">
                                <input type="radio"  value="4" name="reply_star">
                            </label>
                            <label class="label rel">
                                <input type="radio"  value="5" name="reply_star" checked="checked">
                            </label>
                            <span class="fen"><b class="fen1">5</b>分</span>
                        </div>
                        <input type="hidden"  value="" name="activity_id">
                    </div>
                </dd>

            </dl>
            <div class="list ui-b rel">
                <div>
                    <div>
                        <div class="weui-cell ui-b">
                            <div class="weui-cell__bd">
                                <textarea name="content" class="weui-textarea " placeholder="最少评论10字，您的评价将是其他用户的重要参考" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="item uploadNum" id="uploadNum">还可上传<span class="leftNum orange">8</span>张图片，已上传<span class="loadedNum orange">0</span>张(非必填)</div>
                        <div class="weui-cells weui-cells_form" style="margin-top:0px">
                            <div class="weui-cell">
                                <div class="weui-cell__bd">
                                    <div class="weui-uploader">
                                        <div class="weui-uploader__bd">
                                            <ul class="weui-uploader__files" id="uploadImageBox">

                                            </ul>
                                            <div class="weui-uploader__input-box no">
                                                <img src="https://hf.pigcms.com/tpl/Wap/default/static/classify/upimg.png" style="width:100%;height:100%">
                                                <input type="file" accept="image/jpg,image/jpeg,image/png,image/gif" data-extension="jpg|jpeg|png|gif" id="fileInput" name="file[]">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="weui-cells weui-cells_checkbox" style="margin-top:0px">
                            <label class="weui-cell weui-check__label" for="s11">
                                <div class="weui-cell__bd">
                                    <p>匿名评价</p>
                                    <input type="hidden" name="is_anonymous" value="1"/>
                                </div>
                                <div class="weui-cell__hd">
                                    <input type="checkbox" class="weui-check" name="niming" id="s11" checked="checked">
                                    <i class="weui-icon-checked"></i>
                                </div>
                            </label>

                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div style="padding:45px">
            <a href="javascript:;" class="weui-btn weui-btn_primary submit">保存</a>
        </div>
    </div>
    <!-- 上传预览模板 -->
    <script type="text/html" id="uploadImageTemplate">
        <& for(var i = 0; i < _this.length; i++){ &>
        <& var obj = _this[i]; &>
        <li class="weui-uploader__file  rel" style="background-image:url(<&= obj.src &>)"><a href="javascript:void(0);" class="upload_delete item" title="删除"></a></li>
        <& } &>
    </script>
    <script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/common.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/fastClick/fastClick.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/jquery-form/jquery-plugin-form.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/swiper/swiper.js"></script>
    <script>
        View={
            init:function(){
                this.initFresh();
                this.uploaderFile();
                this.submit();
                this.image()
            },
            settings:{
                img:[]
            },
            initFresh:function(){
                var id="{pigcms{$Think.get.activity_id}"
               $('input[name=activity_id]').val(id)
               $('input[name=reply_star]').on('change',function(){
                   console.log($(this).val())
                   var i=$(this).val()
                    $(".score").removeClass('score_1 score_2 score_3 score_4 score_5').addClass('score_'+i)
                   $(".fen1").html(i)
                  })
                $('input[name=niming]').on('change',function(){
                      if($(this).prop('checked')){
                          $('input[name=is_anonymous]').val(1)
                      }else{
                          $('input[name=is_anonymous]').val(2)
                      }
                })
            },
            uploaderFile:function(){
                var self=this;
                //  上传图片
                var $removeContainer = $('#uploadImageBox');
                APP.Uploader({
                    element: '#fileInput',
                    removeContainer: $removeContainer,
                    onValidate: function() {
                        return {
                            invalid: $removeContainer.find('.weui-uploader__file').size() >= 8,
                            message: '最多只能选择 8 张图片'
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
                        $(".loadedNum").html(list.length);
                        var leftNum = 8-parseInt(list.length);
                        $(".leftNum").html(leftNum);
                        $removeContainer.find('.weui-uploader__file').last().append(item.file);
                        self.settings.img=list
                    },
                    onDetail:function(list){
                         $(".loadedNum").html(list.length);
                        var leftNum = 8-parseInt(list.length);
                        $(".leftNum").html(leftNum);
                        self.settings.img=list
                    }
                });

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
                        var length = $('.weui-textarea ').val().length;
                        if(length<10){
                            $.toptip("最少评论10字", 'error')
                          return false
                        }
                      if(!validate($('.weui-textarea ').val(),'g')){
                          $.toptip("请输入评论", 'error')
                          return false
                      }
                      
                      $.showLoading('保存中');
                      $("#submitForm").ajaxSubmit({
                          beforeSubmit: function(data, elem, options){
                              console.log(data)
                              for(var i=0;i<data.length;i++){
                                  if(data[i].name=='file[]'&& data[i].value==''){
                                      data.splice(i,1)
                                  }
                              }
                              console.log(data)
                          },
                          success: function(res) {
                              $.hideLoading()
                              var s=eval('(' + res + ')');
                              console.log(s)
                              if(s.errorMsg!='success'){
                                  $.alert(s.errorMsg)
                                  return false
                              }else{
                                  $.toast("保存成功",function(){
                                      window.history.back(-1)
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
            image:function(){
                var self=this;
                $(document).on('click','.weui-uploader__file ',function(event){
                    event.stopPropagation()
                     var index=$(this).index();
                    var img=[]
                    for(var i=0;i < self.settings.img.length;i++){
                        img.push(self.settings.img[i].src)
                    }
                    var pb1 = $.photoBrowser({
                        items:img,
                        onSlideChange: function(index) {
                            console.log(this, index);
                        },
                        onOpen: function() {
                            console.log("onOpen", this);
                        },
                        onClose: function() {
                            console.log("onClose", this);
                        }
                    });
                    pb1.open(index);
                })
            }
        }
        window.View=View;
         View.init()
    </script>
</body>
</html>
