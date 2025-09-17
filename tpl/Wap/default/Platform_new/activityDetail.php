<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Write an awesome description for your new site here. You can edit this line in _config.yml. It will appear in your document head meta (for Google search results) and in your feed.xml site description.">
    <title>活动</title>
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/weui.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/js/components/jquery-weui/0.8.2/css/jquery-weui.css">
    <link rel="stylesheet" type="text/css" href="static/activity/assets/css/icon.css">
    <!--[if lte IE 8]>
    <script type="text/javascript" src="static/activity/assets/js/components/respond/1.4.2/respond.min.js"></script>
    <script type="text/javascript" src="static/activity/assets/js/components/html5shiv/3.7.2/html5shiv.min.js"></script>
    <![endif]-->
   <style>
       .iconfont {
           color:#7C8185;
           font-size:22px;
       }
    .weui-grid__label{color:#7C8185;}
   </style>
</head>
<body ontouchstart="">
<div class="page " >
    <div class="weui-grids">

    </div>
</div>
<script type="text/html" id="index">
<& if(_this.examine_status!=2) {&>
    <a href="{pigcms{:U('Platform/index')}&activity_id=<&=_this.activity_id&>" class="weui-grid js_grid">
        <div class="weui-grid__icon">
            <span class="iconfont icon-editor "></span>
        </div>
        <p class="weui-grid__label">
            活动编辑
        </p>
    </a>
    <& } &>
    <a href="{pigcms{:U('Platform/activityParticPation')}&activity_id=<&=_this.activity_id&>" class="weui-grid js_grid">
        <div class="weui-grid__icon">
            <span class="iconfont icon-set "></span>
        </div>
        <p class="weui-grid__label">
            报名管理
        </p>
    </a>
    <a href="{pigcms{:U('Platform/activityRefund')}&activity_id=<&=_this.activity_id&>" class="weui-grid js_grid">
        <div class="weui-grid__icon">
            <span class="iconfont icon-see "></span>
        </div>
        <p class="weui-grid__label">
            退款审核
        </p>
    </a>
    <a href="{pigcms{:U('Platform/indexDetail')}&activity_id=<&=_this.activity_id&>" class="weui-grid js_grid">
        <div class="weui-grid__icon">
            <span class="iconfont icon-see "></span>
        </div>
        <p class="weui-grid__label">
            查看详情
        </p>
    </a>
    <a href="javascript:;" class="weui-grid js_grid <&=_this.status==1?'cancle_activity':''&> " data-url="{pigcms{:U('Platform/cancle_activity')}">
        <div class="weui-grid__icon">
            <span class="iconfont icon-quxiao"></span>
        </div>
        <p class="weui-grid__label">
            <& if(_this.status==1) {&>
            取消活动
            <&} else if(_this.status==3){&>
            活动已结束
            <&}else{&>
            活动已取消
            <&}&>

        </p>
    </a>
    <& if(!_this.is_end){&>
    <a href="javascript:;"  class="weui-grid js_grid close_enroll" data-url="<& if(_this.enroll_status==1){&>{pigcms{:U('Platform/close_enroll')}<&}else{&>{pigcms{:U('Platform/open_enroll')}<&}&>">
        <div class="weui-grid__icon">
            <span class="iconfont icon-guanbi"></span>
        </div>
        <p class="weui-grid__label">
            <& if(_this.enroll_status==1){&>
            关闭报名
            <&}else{&>
            开启报名
            <&}&>
        </p>
    </a>
    <&}&>
    <& if(_this.is_end){&>
    <a href="javascript:;" class="weui-grid js_grid finish_one"  data-url="{pigcms{:U('Platform/finish_all')}">
        <div class="weui-grid__icon">
            <span class="iconfont icon-wancheng"></span>
        </div>
        <p class="weui-grid__label">
            完成活动
        </p>
    </a>
    <&}&>

    <!--<a href="" class="weui-grid js_grid">
        <div class="weui-grid__icon">
            <span class="iconfont icon-daochu"></span>
        </div>
        <p class="weui-grid__label">
            导出报名数据
        </p>
    </a>-->
    <& if(_this.owner_info==null){&>
    <a href="javascript:;" class="weui-grid js_grid refund"  data-url="{pigcms{:U('Platform/refund_examine')}">
        <div class="weui-grid__icon">
            <span class="iconfont icon-wancheng"></span>
        </div>
        <p class="weui-grid__label">
            申请退款
        </p>
    </a>
     <& }else{&>
        <& if(_this.owner_info.reject_times == 1 && _this.owner_info.status==2){&>
         <a href="javascript:;" class="weui-grid js_grid refund2"  data-url="{pigcms{:U('Platform/refund_examine')}">
            <div class="weui-grid__icon">
                <span class="iconfont icon-wancheng"></span>
            </div>
            <p class="weui-grid__label">
                退款申诉
            </p>
        </a>
         <& }&>
         <& if( _this.owner_info.status==1){&>
         <a href="javascript:;" class="weui-grid js_grid"  data-url="{pigcms{:U('Platform/refund_examine')}">
            <div class="weui-grid__icon">
                <span class="iconfont icon-wancheng"></span>
            </div>
            <p class="weui-grid__label">
                审核中
            </p>
        </a>
         <& }&>

    <& }&>
</script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery/1.11.1/jquery.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/simplite/simplite.js"></script>
<script type="text/javascript" src="static/activity/assets/js/components/jquery-weui/0.8.2/js/jquery-weui.js"></script>
<script>
    View={
        init:function(){
            this.initRefresh();
            this.click()
        },
        initRefresh:function(){
            var href=window.location.href;
            var ss=href.lastIndexOf('=')+1
            var activity_id=View.activity_id=href.slice(ss)
            console.log(activity_id)
            var datas={
                activity_id:activity_id
            }

            $.ajax({
                type : "post",
                url : "{pigcms{:U('Platform/activity_detail')}",
                dataType : "json",
                data:{'activity_id':activity_id},
                async:false,
                success : function(data){
                    if(data.errorMsg!='success'){
                        $.hideLoading()
                    }else{
                        var tpl = Simplite.getTemplate('index');
                        var compiledTpl = Simplite.compile(tpl)(data.result);
                        $(".weui-grids").html(compiledTpl);
                        $.hideLoading();
                    }


                },
                error:function(res){
                    $.hideLoading()
                    console.log(res)
                }
            })
        },
        click:function(){
            var _this=this
            $(document).on("click",'.cancle_activity',function(){
                //取消活动
                var url=$(this).attr('data-url')
                $.confirm({
                    title: '提示',
                    text: '确定取消活动吗？',
                    onOK: function () {
                        //点击确认
                        $.ajax({
                            type : "post",
                            url : url,
                            dataType : "json",
                            data:{'activity_id':View.activity_id},
                            async:false,
                            success : function(data){
                                if(data.errorMsg!='success'){
                                    $.alert(data.errorMsg)
                                    return false
                                }
                                _this.initRefresh()
                            },
                            error:function(res){

                                console.log(res)
                            }
                        })
                    },
                    onCancel: function () {
                    }
                });
            })
            $(document).on("click",'.finish_one',function(){
                //完成活动
                var con=$(this).find('.weui-grid__label').html().trim();
                var url=$(this).attr('data-url');
                $.confirm({
                    title: '提示',
                    text: '确定完成活动吗？',
                    onOK: function () {
                        //点击确认
                        $.ajax({
                            type : "post",
                            url : url,
                            dataType : "json",
                            data:{'activity_id':View.activity_id},
                            async:false,
                            success : function(data){
                                if(data.errorMsg!='success'){
                                    $.alert(data.errorMsg)
                                }else{
                                    _this.initRefresh()
                                }

                            },
                            error:function(res){
                               $.alert(res.errorMsg)
                                console.log(res)
                            }
                        })
                    },
                    onCancel: function () {
                    }
                });
            })
            $(document).on("click",'.close_enroll',function(){
                //开启、关闭活动
                var con=$(this).find('.weui-grid__label').html().trim();
                var url=$(this).attr('data-url')
                $.confirm({
                    title: '提示',
                    text: '确定'+con+'吗？',
                    onOK: function () {
                        //点击确认
                        $.ajax({
                            type : "post",
                            url : url,
                            dataType : "json",
                            data:{'activity_id':View.activity_id},
                            async:false,
                            success : function(data){
                                if(data.errorMsg!='success'){
                                    $.alert(data.errorMsg)
                                    return false
                                }
                                _this.initRefresh()
                            },
                            error:function(res){

                                console.log(res)
                            }
                        })
                    },
                    onCancel: function () {
                    }
                });
            })
            $(document).on("click",'.refund',function(){
               $.confirm({
                   title: '提示',
                   text: '确定申请退款吗？',
                   onOK: function () {
                       //点击确认
                       $.showLoading('加载中')
                        $.ajax({
                        url: "{pigcms{:U('Platform/refund_examine')}",
                           async: false,//这一步是非常重要的，作用是设置为同步执行
                           dataType : "json",
                           data:{'join_id':'is_owner','activity_id':View.activity_id,'reject_times':1},
                           type : "post",
                           success: function (data) {
                               $.hideLoading()
                               if(data.errorMsg!='success'){
                                   $.alert(data.errorMsg)
                                   return false
                               }
                             _this.initRefresh()
                           },
                           error:function(data){
                               $.hideLoading()
                               console.log(data)
                           }
                       });
                   },
                   onCancel: function () {
                   }
               });

           })

            $(document).on("click",'.refund2',function(){
               $.confirm({
                   title: '提示',
                   text: '确定申诉到平台管理员请求退款吗？',
                   onOK: function () {
                       //点击确认
                       $.showLoading('加载中')
                        $.ajax({
                           url: "{pigcms{:U('Platform/refund_examine')}",
                           async: false,//这一步是非常重要的，作用是设置为同步执行
                           dataType : "json",
                           data:{'join_id':'is_owner','activity_id':View.activity_id,'reject_times':2},
                           type : "post",
                           success: function (data) {
                               $.hideLoading()
                               if(data.errorMsg!='success'){
                                   $.alert(data.errorMsg)
                                   return false
                               }
                             _this.initRefresh()
                           },
                           error:function(data){
                               $.hideLoading()
                               console.log(data)
                           }
                       });
                   },
                   onCancel: function () {
                   }
               });

           })
        }
    }
    window.View=View;
    View.init()
</script>
</body>
</html>