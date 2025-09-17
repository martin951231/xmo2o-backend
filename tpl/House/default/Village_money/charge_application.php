<include file="Public:header"/>
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
    </style>
    <div class="page-content" style="margin-left: {pigcms{$page_magin | default=160}px;">
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
                        <if condition="$application_detail['application_type'] eq 1">
                                <div class="application-btn">
                                    <div class="btn-msg" onclick="add_my_application('{pigcms{$application_detail.application_id}')">使用应用</div>
                                </div>
                            <elseif condition="!$application_detail['is_bind']"/>
                                <div class="application-btn">
                                    <div class="btn-msg" onclick="add_my_application('{pigcms{$application_detail.application_id}')">
                                        <if condition="$application_detail.application_id eq 33">使用应用 <else/>添加到我的应用 </if>
                                    </div>
                                </div>
                            <else/>
                            <div class="application-btn">
                                <div class="application-into-msg" onclick="into_url('{pigcms{$url}')">进入应用</div>
                                &nbsp;&nbsp;&nbsp;&nbsp;
                                <if condition="$application_detail['application_is_not'] eq 0">
                                    <div class="btn-msg del" onclick="remove_my_application('{pigcms{$application_detail.application_id}')">从我的应用移除</div>
                                </if>
                            </div>
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
        function into_url(url) {
            window.location.href=url;
        }
		function add_my_application(application_id) {
			console.log('application_id', application_id);
            $.post("{pigcms{:U('Application/add_my_applicat')}",{application_id:application_id},function(result){
                console.log('add_my_applicat', result)
                if (result && result.status==1) {
                    if(application_id == 33) {
                        var msg = '已将该功能成功添加至左侧导航，可前去体验！';
                    }else{
                        var msg = '已将该功能成功添加至我的应用，可前去体验！';
                    }
                    window.top.msg(1,msg)
                    var v20_url = "{pigcms{$url}";
                    console.log('v20_url', v20_url)
                    window.top.GenerateRoutes(v20_url);
                } else if (result && result.status==2) {
                    if (result.is_v20) {
                        var v20_url = "{pigcms{$url}";
                        console.log('v20_ur1l', v20_url)
                        window.top.GenerateRoutes(v20_url);
                        setTimeout(function() {
                            window.to.reload();
                        }, 1500)
                    } else {
                        window.location.href=result.url;
                    }
                } else if (result.info) {
                    window.top.msg(0,result.info)
                } else {
                    window.top.msg(0,'添加失败')
                }
			});
		}
		function remove_my_application(application_id) {
            console.log('application_id', application_id);
            $.post("{pigcms{:U('Application/remove_my_application')}",{application_id:application_id},function(result){
                if (result && result.status==1) {
                    layer.open({
                        content: '已将该功能成功移出我的应用，如有需要，可再次添加！',
                        shadeClose: false,
                        closeBtn: 0
                        ,btn: ['确定']
                        ,yes:function(index){
                            window.location.reload();
                        }
                    })
                } else if (result && result.status==2 && result.url) {
                    window.location.href=result.url;
                } else if (result.info) {
                    layer.open({
                        content: result.info,
                        shadeClose: false,
                        closeBtn: 0
                        ,btn: ['知道了']
                    })
                } else {
                    layer.open({content: '操作失败'});
                }
            });
        }
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
            $('.page-content').css('height',height);

            var node_info = $('.ui-nav.clearfix.swiper-wrapper li');
            console.log('length',node_info.length)
            if (node_info.length>0) {
                $('.application_detail_box').css('margin-top','50px');
            }
        })
    </script>
<include file="Public:footer"/>