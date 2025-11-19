<include file="NewPublic:header"/>
    <style>
        .page-content {
            /*background-color: white !important;*/
            padding: 20px;
        }
        .application_detail_box {
            background-color: white !important;
            padding: 20px;
            width: 100%;
            height: 100%;
        }
        .application-detail-top {
            background: #f8f8f8;
            width: 100%;
            padding: 20px;
            display: flex;
        }
        .application-detail-img {
            width: 150px;
            height: 150px;
            background-image: url("{pigcms{$application_detail['application_img']}");
            background-repeat: no-repeat;
            background-size: cover;
        }
        .application-detail-introduce {
            width: calc(100% - 200px);
            position: relative;
            padding: 0 20px;
        }
        .application-name-box {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }
        .application-desc-box {
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }
        .application-btn {
            position: absolute;
            bottom: 0px;
            text-align: -webkit-center;
            /*width: calc(100% - 240px);*/
            cursor:pointer;
            display: flex;
        }
        .application-btn .btn-msg{
            padding: 10px 20px;
            background-color: #2681f3;
            color: white;
            border-radius: 5px;
            max-width: 16rem;
        }
        .application-btn .btn-msg.del {
            background-color: red;
        }
        .application-into-msg{
            padding: 10px 20px;
            background-color: #2681f3;
            color: white;
            border-radius: 5px;
            max-width: 16rem;
        }
        .application-detail-introduce-box {
            width: 100%;
            padding: 20px 0;
        }
        .introduce-box-title {
            /*border-bottom:3px solid #2681f3;*/
            /*max-width: 8rem;*/
            /*text-align: center;*/
            /*padding: 5px;*/
            /*margin-left: 20px;*/
            white-space: nowrap;
            overflow-x: auto;
            display: -ms-flexbox;
            display: flex;
            padding: 0 16px;
            margin-bottom: -1px;
            border-bottom: 1px solid #dcdee0;
            border-bottom-color: var(--theme-stroke-5,#dcdee0);
        }
        .introduce-box-title .box-title {
            border-bottom: 2.4px solid #155bd4;
            border-bottom-color: var(--theme-primary-4,#155bd4);
            padding: 14px 16px 12px;
            margin-bottom: -1px;
        }
        .introduce-box-detail {
            /*border-top:1px solid #e7e7eb;*/
            padding: 0 20px;
        }
        .detail_box {
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #ebedf0;
            text-align: center;
            /*border:1px solid #B4B4B4;*/
        }
        .detail_box img {
            max-width: 100%;
        }
        .detail_box_no {
            padding: 20px;
            margin-top: 20px;
        }
        .application-btn {
            color: #155bd4;
        }
    </style>
    <div class="page-content" style="margin-left: {pigcms{$page_magin}px;">
        <if condition="$application_detail">
            <div class="application_detail_box">
                <div class="application-detail-top">
                    <div class="application-detail-img">

                    </div>
                    <div class="application-detail-introduce">
                        <div class="application-name-box">
                            {pigcms{$application_detail['application_name']}
                        </div>
                        <div class="application-desc-box">
                            {pigcms{$application_detail['application_desc']}
                        </div>
                        <if condition="!$application_detail['is_has']">
                            <div class="application-btn">
                                当前套餐不含此功能
                            </div>
                            <else/>
                            <if condition="$application_detail['cat_id'] neq 6">
                                <div class="application-btn">
                                    可前去小区后台中体验该功能
                                </div>
                                <else/>
                                <div class="application-btn">
                                    <if condition="$is_add">
                                        <div class="btn-msg" onclick="add_my_application('{pigcms{$_GET['application_id']}')">进入应用</div>
                                        <else/>
                                        <div class="btn-msg" onclick="into_url('{pigcms{$url}')">进入应用</div>
                                    </if>
                                </div>
                            </if>
                        </if>
                    </div>
                </div>
                <if condition="$application_detail['application_detail']">
                    <div class="application-detail-introduce-box">
                        <div class="introduce-box-title">
                            <div class="box-title">
                                应用详情
                            </div>
                        </div>
                        <div class="introduce-box-detail">
                            <div class="detail_box">
                                {pigcms{$application_detail['application_detail']}
                            </div>
                        </div>
                    </div>
                    <else/>
                    <div class="application-detail-introduce-box">
                        <div class="introduce-box-title">
                            <div class="box-title">
                                应用详情
                            </div>
                        </div>
                        <div class="introduce-box-detail">
                            <div class="detail_box_no">
                                产品经理正在抓紧补充中~
                            </div>
                        </div>
                    </div>
                </if>
            </div>
            <else/>
            <div style="text-align: center; padding: 48% 0;">
                暂无详细信息,请联系管理员添加
            </div>
        </if>
    </div>
    <script>
        $(function(){
            var scrollHeight = document.body.scrollHeight;
            var availHeight = window.screen.availHeight-50;
            var height = scrollHeight;
            if (availHeight>scrollHeight) {
                height = availHeight;
            }
            console.log('height',height)
            console.log('scrollHeight',scrollHeight)
            console.log('availHeight',availHeight)
            $('.page-content').css('min-height',height);

            var node_info = $('.ui-nav.clearfix.swiper-wrapper li');
            console.log('length',node_info.length)
            if (node_info.length>0) {
                $('.application_detail_box').css('margin-top','50px');
            }
        })
        function into_url(url) {
            window.location.href=url;
        }


        function add_my_application(application_id) {
            console.log('application_id', application_id);
            $.post("{pigcms{:U('PropertyApplication/add_my_applicat')}",{application_id:application_id},function(result){
                console.log('result', result);
                console.log('status', result.status);
                if (result && result.status==1) {
                    window.top.GenerateRoutes(result.url);
                }else {
                    alert({content: '跳转失败'});
                }
            });
        }
    </script>
<include file="NewPublic:footer"/>