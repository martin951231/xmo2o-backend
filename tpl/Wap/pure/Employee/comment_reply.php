<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Write an awesome description for your new site here. You can edit this line in _config.yml. It will appear in your document head meta (for Google search results) and in your feed.xml site description.">
    <title>发布评论</title>
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.min.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}employee/css/common/photo.css">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/weui.css"/>
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
    <style>
        #startone.clearfix::after{
            display:block;
            content:'';
            height:0;
            overflow:hidden;
            clear:both;
        }


        #startone.block{
            width:100%;
            background-color: white;
            padding: 10px;
            margin-top: 10px;
            line-height:21px;
        }
        #startone.block .star_score{
            float:left;
        }
        #startone .star_list{
            height:21px;
            margin:50px;
            line-height:21px;
        }
        #startone.block p,.block .attitude{

            line-height:21px;
            display:inline-block;
        }
        #startone.block p span{
            color:#C00;
            font-size:16px;
            font-family:Georgia, "Times New Roman", Times, serif;
        }
        #startone .star_score {
            background:url({pigcms{$static_path}employee/images/stark2.png);
            width:160px;
            height:21px;
            position:relative;
        }

       #startone .star_score a{
            height:21px;
            display:block;
            text-indent:-999em;
            position:absolute;
            left:0;
        }




       #startone .star_score a:hover{
            background:url({pigcms{$static_path}employee/images/stars2.png);
            left:0;
        }

       #startone .star_score a.clibg{
            background:url({pigcms{$static_path}employee/images/stars2.png);
            left:0;
        }
  .bottoms{
      width: 100%;
      position: fixed;
      bottom: 0px;
  }
        .bottoms div{
            background: #fff;
            padding: 5px 10px;
        }
        .font14{font-size:14px}
        .weui-textarea {

            line-height: normal;

        }
        .ping_fen li{
            color: #333;
            padding: 5px 10px;
            font-size: 14px;
            background: #FFEBE0;
            border-radius: 30px;
            margin-right:10px;
            margin-top: 10px;
        }
        .ping_fen li.active{
            color: #fff;
            background: #FF5B0F;
        }
        .memory{
            text-align: center;
            line-height: 30px;
            color: #737373;
        }
        .weui-textarea {
            height: 100px;
        }
    </style>
</head>

<body>
<div class="demos-content-padded">
    <form id="submitForm" action="{pigcms{:U('Employee/employee_reply')}" method="post">
        <input type="hidden"  value="{pigcms{$_GET['employee_id']}" name="employee_id" />
        <input type="hidden"  value="" name="memory_arr" id="memory_arr"/>
        <div class="item memory font14">请选择以下你对{pigcms{$config.employee_alias}印象标签</div>
        <div class="weui-cells mg_t0">
            <div class="weui-cell">
                <ul class="ping_fen">
                    <volist name="employee_memory_list" id="item">
                        <li class="left cursor choose_memory" data-memory_id="{pigcms{$item['memory_id']}">{pigcms{$item.memory_name}</li>
                    </volist>
                </ul>
            </div>
        </div>
        <div id="startone"  class="block clearfix" >
            <div class="star_score"></div>
            <p style="float:right;   margin-right: 10px; padding-right: 10px;">您的评分：<span class="fenshu">0</span> 分</p>
            <div class="attitude"></div>
            <input type="hidden"  value="" name="reply_star" />
        </div>
        <div class="list ui-b rel">
            <div>
                <div>
                    <if condition="$is_comment neq 2">
                        <div class="weui-cell ui-b">
                            <div class="weui-cell__bd">
                                <textarea name="reply_content" class="weui-textarea font14" placeholder="最少评论5字，您的评价将是其他用户的重要参考" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="item uploadNum font14" id="uploadNum">还可上传<span class="leftNum orange">8</span>张图片，已上传<span class="loadedNum orange">0</span>张(非必填)</div>
                        <div class="weui-cells weui-cells_form" style="margin-top:0px">
                            <div class="weui-cell">
                                <div class="weui-cell__bd">
                                    <div class="weui-uploader">
                                        <div class="weui-uploader__bd">
                                            <ul class="weui-uploader__files" id="uploadImageBox">

                                            </ul>
                                            <div class="weui-uploader__input-box no">
                                                <img src="https://hf.pigcms.com/tpl/Wap/default/static/classify/upimg.png" style="width:100%;height:100%">
                                                <input type="file" accept="image/*"   mutiple="mutiple" data-extension="jpg|jpeg|png|gif" id="fileInput" name="file[]">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </if>
                </div>
            </div>
        </div>
    </form>
    <div  class="bottoms">
        <div>
            <a href="javascript:;" class="weui-btn weui-btn_primary submit">保存</a>
        </div>

    </div>
</div>
<!-- 上传预览模板 -->
<script type="text/html" id="uploadImageTemplate">
    <& for(var i = 0; i < _this.length; i++){ &>
    <& var obj = _this[i]; &>
    <li class="weui-uploader__file  rel" style="background-image:url(<&= obj.src &>)"><a href="javascript:void(0);" class="upload_delete item" title="删除"></a></li>
    <& } &>
</script>
<script src="{pigcms{$static_path}employee/js/common/jquery-2.1.4.js" type="text/javascript"></script>
<script src="{pigcms{$static_path}employee/js/common/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
<script src="{pigcms{$static_path}employee/js/common/starScore.js" type="text/javascript" charset="UTF-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}employee/js/common/simplite.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}employee/js/common/employee_common.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/fastClick/fastClick.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-form/jquery-plugin-form.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/swiper/swiper.js"></script>
<script src="{pigcms{$static_path}js/layer.m.js" type="text/javascript"></script>
<script>
    View={
        init:function(){
            this.initFresh();
            this.uploaderFile();
            this.submit();
            this.image();
            this.choose_memory();
        },
        settings:{
            img:[],
            memory_info:[]
        },
        initFresh:function(){
            scoreFun($("#startone"),{
                star:5,
                callback:function(score){
                    console.log(score)
                 $("input[name=reply_star]").val(score)
                }
            });
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
                    $(".loadedNum").html(list.length)
                    $(".leftNum").html((8-(list.length)));
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
                if(length<5){
                    layer.open({
                        content: '最少评论5字'
                        ,skin: 'msg'
                        ,time: 2 //2秒后自动关闭
                    });

                    return false
                }
                if(!validate($('.weui-textarea ').val(),'g')){
                    layer.open({
                        content: '请输入评论'
                        ,skin: 'msg'
                        ,time: 2 //2秒后自动关闭
                    });
                    return false
                }
                layer.open({type: 2,content: '保存中',shadeClose:false});
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
                        layer.closeAll();
                        console.log('res', res);
                        if(res.status!=1){
                            layer.open({
                                content: res.info
                                ,skin: 'msg'
                                ,time: 2 //2秒后自动关闭
                            });

                            return false
                        }else{
                            $.toast("保存成功",function(){
                                window.history.back(-1)
                            });
                        }

                    },
                    error: function() {
                        layer.closeAll();
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
        },
        choose_memory: function () {
            var self=this;
            $(document).on('click','.choose_memory',function(event){
                var memory_info = self.settings.memory_info;
                var memory_id=$(this).attr('data-memory_id');
                // self.settings.memory_info=list
                if($(this).hasClass('active')){
                    $(this).removeClass("active");
                    delete memory_info[memory_id];
                } else {
                    $(this).addClass("active");
                    memory_info[memory_id] = memory_id;
                }
                self.settings.memory_info = memory_info;
                console.log('memory_info', memory_info);
                $("input[name=memory_arr]").val(memory_info);
            })
        }
    };
    window.View=View;
    View.init()
</script>
</body>
</html>

