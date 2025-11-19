<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>{pigcms{:L_("砍价活动列表")}</title>
    <link href="{pigcms{$static_path}diypage/css/customField.css" type="text/css" rel="stylesheet"/>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <script type="text/javascript">
        $(function () {
            // 设置高度
            $('.js-modal iframe', parent.document).height($('body').height());

            // 关闭
            $('.modal-header .close').live("click", function () {
                parent.login_box_close();
            });

            // 选取
            $('button.js-choose').live('click', function () {
                <?php if($_GET['type'] == 'more'){ ?>
                if ($(this).hasClass('btn-primary')) {
                    $(this).removeClass('btn-primary').html('{pigcms{:L_("选取")}');
                } else {
                    $(this).addClass('btn-primary').html('{pigcms{:L_("取消")}');
                }
                if ($('.js-choose.btn-primary').size() > 0) {
                    $('.js-confirm-choose').show();
                } else {
                    $('.js-confirm-choose').hide();
                }
                <?php }else{ ?>
                parent.login_box_after('{pigcms{$_GET.number}', 'good', $(this).data('title'), $(this).data('link_url'));
                <?php } ?>
            });

            // 确定选取
            $('.js-confirm-choose').live('click', function () {
                var data_arr = [];
                $.each($('.js-choose.btn-primary'), function (i, item) {
                    data_arr[i] = {
                        'id': $(item).data('id'),
                        'act_id': $(item).data('act_id'),
                        'atype': $(item).data('atype'),
                        'goods_id': $(item).data('goods_id'),
                        'url': $(this).data('link_url')
                    };
                });

                parent.widget_box_after('<?php echo $_GET['number'];?>', data_arr);

            });

            // 分页
            var click = false;
            $('.js-page-list a').live('click', function (e) {
                if (!$(this).hasClass('active')) {
                    if (click) {
                        return false;
                    }
                    click = true;
                    var input_val = $('.js-modal-search-input').val();
                    console.log('input_val3', input_val);
                    $('body').html('<div class="loading-more"><span></span></div>');
                    $('body').load("{pigcms{:U('activity_info',array('store_id'=>$now_store['store_id'], 'huodong_type' => 'bargain', 'type'=>'ajax','hide_others'=>isset($hide_others)?$hide_others:'0','number' => $_GET['number']))}", {
                        page: $(this).data('page-num'),
                        'keyword': input_val
                    }, function () {
                        $('.js-modal iframe', parent.document).height($('body').height());
                    });
                    click = false;
                }
            });

            // 搜索
            $('.js-modal-search').live('click', function (e) {
                if (click) {
                    return false;
                }
                click = true;
                var input_val = $('.js-modal-search-input').val();
                console.log('input_val', input_val);
                $('body').html('<div class="loading-more"><span></span></div>');
                $('body').load("{pigcms{:U('activity_info',array('store_id'=>$now_store['store_id'], 'huodong_type' => 'bargain', 'type'=>'ajax','hide_others'=>isset($hide_others)?$hide_others:'0','number' => $_GET['number']))}", {'keyword': input_val}, function () {
                    $('.js-modal iframe', parent.document).height($('body').height());
                });
                click = false;
                return false;
            });

            //回车提交搜索
            $(window).keydown(function (event) {
                if (event.keyCode == 13 && $('.js-modal-search-input').is(':focus')) {
                    if (click) {
                        return false;
                    }
                    click = true;
                    var input_val = $('.js-modal-search-input').val();
                    console.log('input_val1', input_val);
                    $('body').html('<div class="loading-more"><span></span></div>');
                    $('body').load("{pigcms{:U('activity_info',array('store_id'=>$now_store['store_id'], 'huodong_type' => 'bargain', 'type'=>'ajax','hide_others'=>isset($hide_others)?$hide_others:'0','number' => $_GET['number']))}", {'keyword': input_val}, function () {
                        $('.js-modal iframe', parent.document).height($('body').height());
                    });
                    click = false;
                    return false;
                }
            });

            if ("{pigcms{$keyword}") {
                $('.js-modal-search-input').val("{pigcms{$keyword}");
            }

        });
    </script>
</head>
<body style="background-color:#ffffff;">


<div class="modal-header">
    <a class="close js-news-modal-dismiss">×</a>
    <!-- {pigcms{:L_("顶部")}tab -->
    <ul class="module-nav modal-tab">
        <li class="active"><a href="javascript:void(0);" class="js-modal-tab">{pigcms{:L_("砍价活动列表")}</a></li>
    </ul>
</div>
<div class="modal-body">
    <div class="tab-content">
        <div id="js-module-feature" class="tab-pane module-feature active">
            <table class="table">
                <colgroup>
                    <col class="modal-col-title">
                    <col class="modal-col-time" span="2">
                    <col class="modal-col-action">
                </colgroup>
                <!-- 表格头部 -->
                <thead>
                <tr>
                    <th class="title" style="background-color:#f5f5f5;">
                        <div class="td-cont">
                            <span>活动ID</span>
                        </div>
                    </th>
                    <th class="title" style="background-color:#f5f5f5; display: none ">
                        <div class="td-cont">
                            <span>活动actID</span>
                        </div>
                    </th>
                    <th class="title" style="background-color:#f5f5f5;">
                        <div class="td-cont">
                            <span>砍价商品名称</span> <a class="js-update" href="javascript:window.location.reload();">刷新</a>
                        </div>
                    </th>
                    <th class="time" style="background-color:#f5f5f5;">
                        <div class="td-cont">
                            <span>开始时间</span>
                        </div>
                    </th>
                    <th class="time" style="background-color:#f5f5f5;">
                        <div class="td-cont">
                            <span>结束时间</span>
                        </div>
                    </th>
                    <th class="opts" style="background-color:#f5f5f5;">
                        <div class="td-cont" style="padding:7px 0 3px 10px;">
                            <form class="form-search" onsubmit="return false;">
                                <div class="input-append">
                                    <input class="input-small js-modal-search-input" value="{pigcms{$keyword}"
                                           type="text" style="border-radius:4px 0px 0px 4px;"/><a
                                            href="javascript:void(0);" class="btn js-fetch-page js-modal-search"
                                            style="color:white;border-radius:0 4px 4px 0;margin-left:0px;">搜</a>
                                </div>
                            </form>
                        </div>
                    </th>
                </tr>
                </thead>
                <!-- 表格数据区 -->
                <tbody>
                <?php foreach ($bargain_list as $bargain) { ?>
                    <tr>
                        <td class="title">
                            <div class="td-cont">
                                <span><?php echo $bargain['id']; ?></span>
                            </div>
                        </td>
                        <td class="title" style="display: none">
                            <div class="td-cont">
                                <span><?php echo $bargain['act_id']; ?></span>
                            </div>
                        </td>
                        <td class="title" style="max-width:300px;">
                            <div class="td-cont">
                                <a target="_blank" class="new_window"
                                   href="javascript:"><?php echo $bargain['name']; ?></a>
                            </div>
                        </td>
                        <td class="time">
                            <div class="td-cont">
                                <span><?php echo date('Y-m-d H:i:s', $bargain['start_time']); ?></span>
                            </div>
                        </td>
                        <td class="time">
                            <div class="td-cont">
                                <span><?php echo date('Y-m-d H:i:s', $bargain['end_time']); ?></span>
                            </div>
                        </td>
                        <td class="opts">
                            <div class="td-cont">
                                <button class="btn js-choose" data-link_url="<?php echo $bargain['link_url']; ?>"
                                        data-goods_id="<?php echo $bargain['goods_id']; ?>"
                                        data-id="<?php echo $bargain['id']; ?>"
                                        data-act_id="<?php echo $bargain['act_id']; ?>"
                                        data-atype="5" <?php if ($bargain['selected'] == 1) {
                                    echo 'disabled';
                                } ?>><?php if ($bargain['selected'] == 1) {
                                        echo "已选过";
                                    } else {
                                        echo "选取";
                                    } ?></button>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>

            </table>
        </div>
    </div>
</div>
<div class="modal-footer">
    <div style="display:none;" class="js-confirm-choose left">
        <input type="button" class="btn btn-primary" value="确定使用">
    </div>
    <div class="pagenavi js-page-list" style="margin-top:0;padding-top:2px;">{pigcms{$bargain_page}</div>
</div>
</body>
</html>