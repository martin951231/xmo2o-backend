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
    <title>职位描述</title>
    <link rel="stylesheet" type="text/css" href="https://hf.pigcms.com/tpl/Wap/pure/static/classifynew/css/weui.css">
    <link rel="stylesheet" type="text/css" href="workIcon.css">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="https://hf.pigcms.com/tpl/Wap/pure/static/classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <style>
        body{font-size:14px;    background: #f4f4f4;}
        .bg-f{background:#fff}
        .font16{font-size:16px}
        .font14{font-size:14px}
        .weui-cells__title{padding:0px 10px}
        .weui-cells__title span{padding:3px 5px;display:inline-block;background:#E9E9E9}
        .color_r{color:#ff4d00}
        .pd13{padding:13px}
        .pd10{padding:10px}
        .pdt0{padding-top:0px !important;}
        .mgt0{margin-top:0px}
        .mgb0{margin-bottom:0px}
        .contents .bg-f>span{font-size:12px;color:#999}
        .contents .link span{border:1px solid #ddd;color:#777;padding:3px 5px}
        ul,li{list-style:none}
        ul{overflow:hidden}
        li{margin-top: 10px;
            float: left;
            padding: 3px 5px;}
        .demos-content-padded{
            padding: 15px;
        }
        .demos-content-padded a{background-color: #ff4d00;}
        .weui-btn_primary:not(.weui-btn_disabled):visited {color: #FFFFFF !important;}
        .weui-btn_primary:not(.weui-btn_disabled):active {color: #FFFFFF !important;background-color: rgba(255, 77, 0, 0.4)}
    </style>
</head>
<body>
    <div>
        <div class="weui-cells__title mgb0">
           <span>不能填写电话、QQ、微信等联系方式及特殊字符、性别歧视等内容</span>
        </div>
         <div class="pd10">
                <div class="weui-cells weui-cells_form font16 mgt0">
                <div class="weui-cell">
                    <div class="weui-cell__bd weui-text">
                        <textarea class="weui-textarea" placeholder="请输入文本" rows="5"></textarea>
                        <div class="weui-textarea-counter font14">已输入<span class="length color_r">0</span><span>个字</span></div>
                    </div>
                </div>
            </div>

        </div>
        <div class="pd13 contents pdt0">
            <div class="bg-f pd10">
                <h3>职位标签</h3>
                <span>根据点选的标签，可自动生成职位描述，招人效果更高哦</span>
                <div class="link">
                    <ul>
                        <li><span>室内办公</span></li>
                        <li><span>外出跑客户</span></li>
                        <li> <span>需出差</span></li>
                        <li> <span>做五休二</span></li>
                        <li><span>做六休一</span></li>
                        <li><span>月休四天</span></li>
                    </ul>
                </div>
            </div>

        </div>
        <div class="demos-content-padded">
            <a href="javascript:;" class="weui-btn weui-btn_primary submit">发布</a>
        </div>
    </div>
   <script>
        var View={
            init:function(){
                this.input()
            },
            input:function(){
                var bind_name = 'input';
                if (navigator.userAgent.indexOf("MSIE") != -1){ bind_name = 'propertychange' }
                $(".weui-text").bind(bind_name,'textarea',function(){
                    $(".length").html($(".weui-textarea").val().trim().length)
                })
            }
        }
        window.View=View;
        View.init()
   </script>
</body>
</html>