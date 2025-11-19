<include file="Public:header"/>
    <style>
        .left_box {
            display: block;
            position: fixed;
            top: 50px;
            left: 160px;
            bottom: 0;
            width: 120px;
            box-sizing: border-box;
            background: #fff;
        }
        .left_box .box_title {
            padding-left: 20px;
            font-size: 14px;
            height: 50px;
            line-height: 50px;
            box-sizing: border-box;
            border-bottom: 1px solid #f2f2f2;
            cursor: default;
            overflow: hidden;
        }
        .left_box .box_title.active{
           background-color: #f2f2f2;
        }
        .right_content_box {
            margin-left: 120px;
            width: calc(100% - 120px);
            height: 100%;
            margin-top: 0px;
            margin-right: 0px;
            margin-bottom: 0px;
            overflow: hidden;
        }
    </style>
    <div class="page-content" style="margin-left: 160px;">
        <if condition="$application_list && $application_list['application_list']">
            <div>
                <div class="left_box">
                    <div class="box_title">
                        我的应用
                    </div>
                    <volist name="application_list['application_list']" id="item">
                        <if condition="$item && $item['application_id'] eq $application_list['application_id']">
                            <div class="box_title active" data-url="{pigcms{$item.application_house}">
                                {pigcms{$item['application_name']}
                            </div>
                            <else/>
                            <div class="box_title" data-url="{pigcms{$item.application_house}">
                                {pigcms{$item['application_name']}
                            </div>
                        </if>
                    </volist>
                </div>
                <div class="right_content_box">
                    <iframe src="{pigcms{$application_list.url}" width="100%" height="100%" style="border:0;" name="_blank" id="_blank"></iframe>
                </div>
            </div>
            <else/>
            <div style="text-align: center; padding: 30% 0;font-size: 20px;font-weight: bolder;color: #777777;">
                暂无应用，
                <a href="{pigcms{$config['site_url']}/shequ.php?g=House&c=Application&a=index" style="border-bottom:1px solid #2681f3;">
                    请点击前往功能应用中添加
                </a>
            </div>
        </if>
    </div>
    <script>

        $(document).ready(function (){
            var height = document.documentElement.clientHeight;
            console.log('height', height);
            $('.right_content_box').css('height', height);
            $('.left_box .box_title').click(function () {
                $('.left_box .box_title').removeClass('active');
                $(this).addClass('active');
                var url = $(this).data('url');
                var html = '<iframe src="'+url+'" width="100%" height="100%" style="border:0;" name="_blank" id="_blank"></iframe>';
                $('.right_content_box').html(html);
            });


        })


		function add_my_application(application_id) {
			console.log('application_id', application_id);
            $.post("{pigcms{:U('Application/add_my_applicat')}",{application_id:application_id},function(result){
                if (result && result.status==1) {
                    layer.open({
                        content: '添加成功！',
                        shadeClose: false,
                        closeBtn: 0
                        ,btn: ['确定']
                        ,yes:function(index){
                            window.location.reload();
                        }
                    })
                } else if (result.info) {
                    layer.open({
                        content: result.info,
                        shadeClose: false,
                        closeBtn: 0
                        ,btn: ['知道了']
                    })
                } else {
                    layer.open({content: '添加失败'});
                }
			});
		}
		function remove_my_application(application_id) {
            console.log('application_id', application_id);
            $.post("{pigcms{:U('Application/remove_my_application')}",{application_id:application_id},function(result){
                if (result && result.status==1) {
                    layer.open({
                        content: '操作成功！',
                        shadeClose: false,
                        closeBtn: 0
                        ,btn: ['确定']
                        ,yes:function(index){
                            window.location.reload();
                        }
                    })
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
    </script>
<include file="Public:footer"/>