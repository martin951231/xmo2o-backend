<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>发现</title>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name='apple-touch-fullscreen' content='yes' />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="format-detection" content="address=no" />

    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css" />
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.cookie.js?210" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
    <link rel="stylesheet" href="{pigcms{$static_path}css/discover.css?001" />
    <style>
        .operation-btn {
            text-align: right;
            border-top: 1px solid #eee;
            padding: 0.5rem 0 0;
        }
        .operation-btn a.reload {
            color: #06c1ae;
            border-color: #06c1ae;
        }
        .operation-btn a {
            display: inline-block;
            width: 3.95rem;
            height: 1.5rem;
            text-align: center;
            line-height: 1.5rem;
            border: 1px solid #eee;
            border-radius: 0.25rem;
            font-size: 0.65rem;
            margin-right: .75rem;
        }
        .m-top {
            margin-top: 1rem;
        }
        .relate-topic {
            margin-right: 0.6rem;
            margin-top: 0.5rem;
        }
    </style>
</head>

<body onload="loaded()">
<header class="pageSliderHide"><div id="backBtn"></div>
    <if condition='$_GET["status"] eq 5'>
        已拒绝
        <else/>
        待审核
    </if>
    <div id="plus"><img src="{pigcms{$static_path}images/new_my/recharge.png"></div>
</header>
<div id="container" style="top:50px;">
    <div id="wrapper">
        <div id="scroller" class="village_my">
            <div id="scroller-pullDown">
                <span id="down-icon" class="icon-double-angle-down pull-down-icon"></span>
                <span id="pullDown-msg" class="pull-down-msg">下拉刷新</span>
            </div>

            <ul class="bbs-list">
                <if condition='$discover_msg_list["list"]'>
                    <volist name='discover_msg_list["list"]' id='dml'>
                        <li >
                            <div class="row bbs-my-top">
                                <div class="col-20 bbs-my-avather" onclick="location.href='{pigcms{:U('look_discover_msg',array('discover_id'=>$dml['discover_id']))}'">

                                    <if condition='$dml["avatar"] neq ""'>
                                        <img src="{pigcms{$dml['avatar']}" width="100px" height="100px" />
                                        <else/>
                                        <img src="{pigcms{$default_avatar}" width="100px" height="100px" />
                                    </if>
                                </div>
                                <div class="col-80 bbs-top-info" >
                                    <p onclick="location.href='{pigcms{:U('look_discover_msg',array('discover_id'=>$dml['discover_id']))}'">
                                        {pigcms{$dml['nickname']}
                                        <span>{pigcms{$dml['add_time']|date='Y-m-d',###}</span>
                                    </p>
                                    <!--  <p onclick="location.href='{pigcms{:U('look_discover_msg',array('discover_id'=>$dml['discover_id']))}'"><span>{pigcms{$dml['add_time']|date='Y-m-d',###}</span></p> -->

                                </div>
                                <div class="col-100" onclick="location.href='{pigcms{:U('look_discover_msg',array('discover_id'=>$dml['discover_id']))}'">
                                    <div class="bbs-desc">
                                        {pigcms{:msubstr($dml['discover_content'],0,50)}
                                    </div>
                                </div>

                                <if condition='$dml["discover_img"]'>
                                    <div class="col-100 " onclick="location.href='{pigcms{:U('look_discover_msg',array('discover_id'=>$dml['discover_id']))}'">
                                        <div class="bbs-image">
                                            <ul>
                                                <volist name='dml["discover_img"]' id='img'>
                                                    <li><img src="{pigcms{$img}" /></li>
                                                </volist>
                                            </ul>
                                        </div>
                                    </div>
                                </if>

                                <if condition='!empty($dml["url_info"])'>
                                    <div class="relate-topic" onclick="location.href='{pigcms{$dml.url_info.url}'">
                                        <img src="{pigcms{$dml['url_info']['title_pic']}">
                                        <p>{pigcms{$dml['url_info']['title']}</p>
                                    </div>
                                </if>



                                <div class="col-100 clear-both m-top" onclick="location.href='{pigcms{:U('look_discover_msg',array('discover_id'=>$dml['discover_id']))}'">
                                    <div class="bbs-foot">
                                        <p>来自：<span>{pigcms{$dml['type_name']}</span></p>
                                    </div>
                                </div>

                                <div class="col-100 discover-bottom" onclick="location.href='{pigcms{:U('look_discover_msg',array('discover_id'=>$dml['discover_id']))}'">
                                    <div class="bbs-foot">
                                        <p>
                                            状态：
                                            <span>
                                                <if condition='$_GET["status"] eq 5'>
                                                    已拒绝({pigcms{:msubstr($dml['discover_refusal'],0,10)})
                                                    <else/>
                                                    待审核
                                                </if>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="operation-btn">
                                    <a href="/wap.php?g=Wap&c=Discover&a=discover_msg_edit&discover_id={pigcms{$dml.discover_id}" class="reload">
                                        编辑
                                    </a>
                                    <a data-discover_id="{pigcms{$dml.discover_id}" href="javascript:void(0)" class="delete">
                                        删除
                                    </a>
                                </div>
                            </div>
                        </li>
                    </volist>
                    <else />
                    <li>
                        <div class="row bbs-my-top">
                            <div class="col-100">
                                <div class="bbs-foot" style="margin: 0;text-align: center;">
                                    <p style="text-align:center">暂无信息</p>
                                </div>
                            </div>
                        </div>
                    </li>
                </if>
            </ul>

            <if condition='count($discover_msg_list["list"]) egt 6'>
                <div id="scroller-pullUp">
                    <span id="up-icon" class="icon-double-angle-up pull-up-icon"></span>
                    <span id="pullUp-msg" class="pull-up-msg">上拉刷新</span>
                </div>
            </if>

            <li class="no_more_info" style="display: none;">
                <div class="row bbs-my-top">
                    <div class="col-100">
                        <div class="bbs-foot" style="margin: 0;text-align: center;">
                            <p style="text-align:center">暂无更多信息</p>
                        </div>
                    </div>
                </div>
            </li>
        </div>

    </div>
</div>
{pigcms{$shareScript}
<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/iscroll2.js?444" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
<script type="text/javascript">

    var aa = 1;
    $('.user_collect').click(function(e){
        var discover_id = $(this).data('discover_id');
        var collection = $(this).data('collection');
        console.log('collection--------', collection);
        if(aa == 1){
            if (!collection) {
                collection_msg(discover_id, this);
            } else {
                cancel_collection_msg(discover_id, this);
            }
        }
    });
    // 收藏
    function collection_msg(discover_id, this_node){
        aa =2;
        var collection_msg_url = "{pigcms{:U('collection_msg')}";
        $.post(collection_msg_url,{'discover_id':discover_id},function(data){
            console.log('收藏返回信息：', data);
            aa = 1;
            if(data.status==0){
                layer.open({
                    content: data.msg,
                    btn: ['确定'],
                    shadeClose: false
                });
            }else{
                layer.open({
                    content: '收藏成功！',
                    btn: ['确定'],
                    shadeClose: false
                });
                $(this_node).data('collection',true);
                var collection_num_val = parseInt($(this_node).find('.collection_num_val')[0].innerText) + 1;
                $(this_node).find('.collection_num_val')[0].innerText = collection_num_val;
                $(this_node).find('.collect_img')[0].src = "{pigcms{$static_path}" + 'discover/images/icon/ft4_a.png?t=001';
                $(this_node).css('color', '#06c1ae')
                console.log('收藏返回信息1：', $(this_node).find(".collect_img")[0]);
            }
        },'json')
    }
    // 取消收藏
    function cancel_collection_msg(discover_id, this_node){
        aa =2;
        var cancel_collection_msg_url = "{pigcms{:U('cancel_collection_msg')}";
        $.post(cancel_collection_msg_url,{'discover_id':discover_id},function(data){
            console.log('取消收藏返回信息：', data);
            aa = 1;
            if(data.status==0){
                layer.open({
                    content: data.msg,
                    btn: ['确定'],
                    shadeClose: false
                });
            }else{
                layer.open({
                    content: '取消收藏成功！',
                    btn: ['确定'],
                    shadeClose: false
                });
                $(this_node).data('collection',false);
                var collection_num_val = parseInt($(this_node).find('.collection_num_val')[0].innerText) - 1;
                if (collection_num_val < 0) collection_num_val = 0;
                $(this_node).find('.collection_num_val')[0].innerText = collection_num_val;
                $(this_node).find('.collect_img')[0].src = "{pigcms{$static_path}" + 'discover/images/icon/ft4.png?t=001';
                $(this_node).css('color', '#333')
            }
        },'json')
    }

    var bb=1;
    $('.user_likes').click(function(e){
        var discover_id = $(this).data('discover_id');
        var likes = $(this).data('likes');
        console.log('likes--------', likes)
        if(bb == 1){
            if (!likes) {
                likes_msg(discover_id, this);
            } else {
                cancel_likes_msg(discover_id, this);
            }
        }
    });
    // 点赞
    function likes_msg(discover_id, this_node){
        bb =2;
        var likes_msg_url = "{pigcms{:U('likes_msg')}";
        $.post(likes_msg_url,{'discover_id':discover_id},function(data){
            console.log('点赞返回信息：', data);
            bb = 1;
            if(data.status==0){
                layer.open({
                    content: data.msg,
                    btn: ['确定'],
                    shadeClose: false
                });
            }else{
                layer.open({
                    content: '点赞成功！',
                    btn: ['确定'],
                    shadeClose: false
                });
                $(this_node).data('likes',true)
                var likes_num_val = parseInt($(this_node).find('.likes_num_val')[0].innerText) + 1;
                $(this_node).find('.likes_num_val')[0].innerText = likes_num_val;
                $(this_node).find('.likes_img')[0].src = "{pigcms{$static_path}" + 'discover/images/icon/likes_a.png?t=001';
                $(this_node).css('color', '#06c1ae');
                $(this_node).data('likes',true)
            }
        },'json')
    }
    // 取消点赞
    function cancel_likes_msg(discover_id, this_node){
        aa =2;
        var cancel_likes_msg_url = "{pigcms{:U('cancel_likes_msg')}";
        $.post(cancel_likes_msg_url,{'discover_id':discover_id},function(data){
            console.log('取消点赞返回信息：', data);
            aa = 1;
            if(data.status==0){
                layer.open({
                    content: data.msg,
                    btn: ['确定'],
                    shadeClose: false
                });
            }else{
                layer.open({
                    content: '取消点赞成功！',
                    btn: ['确定'],
                    shadeClose: false
                });
                $(this_node).data('likes',false)
                var likes_num_val = parseInt($(this_node).find('.likes_num_val')[0].innerText) - 1;
                if (likes_num_val < 0) likes_num_val = 0;
                $(this_node).find('.likes_num_val')[0].innerText = likes_num_val;
                $(this_node).find('.likes_img')[0].src = "{pigcms{$static_path}" + 'discover/images/icon/likes.png?t=001';
                $(this_node).css('color', '#333');
            }
        },'json')
    }


    // 删除
    $('.delete').click(function(e){
        if(aa == 1){
            aa =2;
            var discover_id = $(this).data('discover_id');
            console.log('删除的信息id', discover_id);
            layer.open({
                content:'确认删除？',
                btn: ['确定','取消'],
                yes:function(){
                    var del_discover_msg = "{pigcms{:U('del_discover_msg')}";
                    $.post(del_discover_msg,{'discover_id':discover_id},function(data){
                        console.log('删除返回信息：', data);
                        aa = 1;
                        if(data.status==0){
                            layer.open({
                                content: data.msg,
                                btn: ['确定'],
                                shadeClose: false
                            });
                        }else{
                            layer.open({
                                content: '删除成功！',
                                btn: ['确定'],
                                shadeClose: false
                            });
                            window.location.reload();
                        }
                    },'json')
                },
                no: function(){
                    aa = 1;
                }
            });
        }
    });


    function loaded() {
        var myScroll,
            upIcon = $("#up-icon"),
            downIcon = $("#down-icon");
        myScroll = new IScroll('#wrapper', {
            probeType: 3,
            disableMouse: true,
            disablePointer: true,
            mouseWheel: false,
            scrollX: false,
            scrollY: true,
            click: iScrollClick(),
            scrollbars: false,
            useTransform: false,
            useTransition: false
        });

        myScroll.on("scroll", function() {
            var y = this.y,
                maxY = this.maxScrollY - y,
                downHasClass = downIcon.hasClass("reverse_icon"),
                upHasClass = upIcon.hasClass("reverse_icon");

            if(y >= 40) {
                //!downHasClass && downIcon.addClass("reverse_icon");
                //return "";
                location.reload();
            } else if(y < 40 && y > 0) {
                downHasClass && downIcon.removeClass("reverse_icon");
                return "";
            }

            if(maxY >= 40) {
                !upHasClass && upIcon.addClass("reverse_icon");
                return "";
            } else if(maxY < 40 && maxY >= 0) {
                //upHasClass && upIcon.removeClass("reverse_icon");
                return "";
            }
        });




        myScroll.on("slideDown", function() {
            if(this.y > 40) {
                upIcon.removeClass("reverse_icon")
            }
        });

        var discover_index_json_url = "{pigcms{:U('my_check_discover_list_json')}";
        var static_path = "{pigcms{$static_path}";
        var page=1;
        var more = true;
        var status = "{pigcms{$_GET['status']}";
        myScroll.on("slideUp", function() {
            if(this.maxScrollY - this.y > 40) {
                if (!more) {
                    return false;
                }
                page++;
                $.post(discover_index_json_url,{'page':page, 'status': status},function(data){
                    console.log('返回的数据', data);
                    if(!data.errorCode){
                        var shtml = '';
                        var discover_msg = data['result'];
                        var discover_msg_list = data['result']['list'];
                        if (!discover_msg_list) {
                            myScroll.refresh();
                            more = false;
                            $('.no_more_info')[0].style.display = 'block';
                            return false;
                        }
                        $('.no_more_info')[0].style.display = 'none';
                        more = true;
                        for(var i in discover_msg_list){
                            shtml += '<li>';
                            shtml += '<div class="row bbs-my-top">';
                            shtml += '<div class="col-20 bbs-my-avather" onclick="location.href=\'/wap.php?g=Wap&c=Discover&a=look_discover_msg&discover_id='+discover_msg_list[i]['discover_id']+'\'">';
                            if (discover_msg_list[i]['avatar']) {
                                shtml += '<img src="'+discover_msg_list[i]['avatar']+'" width="100px" height="100px">';
                            } else {
                                shtml += '<img src="'+discover_msg['default_avatar']+'" width="100px" height="100px">';
                            }
                            shtml += '</div>';

                            shtml += '<div class="col-80 bbs-top-info" onclick="location.href=\'/wap.php?g=Wap&c=Discover&a=look_discover_msg&discover_id='+discover_msg_list[i]['discover_id']+'\'">';
                            shtml += '<p>'+discover_msg_list[i]['nickname']+'<span>'+discover_msg_list[i]['add_time']+'</span>'+'</p>';



                            shtml += '</div>';
                            shtml += '<div class="col-100">';
                            shtml += '<div class="bbs-desc">';
                            shtml += '<p>'+ discover_msg_list[i]['discover_content'].substr(0,50)+'</p>';
                            shtml += '</div>';
                            shtml += '</div>';

                            if(discover_msg_list[i]['discover_img']){
                                var discover_img_arr = discover_msg_list[i]['discover_img'];
                                shtml += '<div class="col-100" onclick="location.href=\'/wap.php?g=Wap&c=Discover&a=look_discover_msg&discover_id='+discover_msg_list[i]['discover_id']+'\'">';
                                shtml += '<div class="bbs-image">';
                                shtml += '<ul>';
                                for(var j in discover_img_arr){
                                    shtml += '<li><img src="'+discover_img_arr[j]+'"></li>';
                                }
                                shtml += '</ul></div>';
                                shtml += '</div>';
                            }

                            // 链接
                            if (discover_msg_list[i]['url_info']) {
                                var url = discover_msg_list[i]['url_info']['url'];
                                shtml += '<div class="relate-topic" onclick="location.href=\''+url+'\'">';
                                shtml += '<img src="' + discover_msg_list[i]['url_info']['title_pic'] + '">';
                                shtml += '<p>' + discover_msg_list[i]['url_info']['title'] + '</p>';
                                shtml += '</div>';
                            }


                            // 来自-分类
                            shtml += '<div class="col-100 clear-both m-top" onclick="location.href=\'/wap.php?g=Wap&c=Discover&a=look_discover_msg&discover_id='+discover_msg_list[i]['discover_id']+'\'">';
                            shtml += '<div class="bbs-foot">';

                            if(discover_msg_list[i]['type_name']){
                                shtml += '<p>来自：<span>'+discover_msg_list[i]['type_name']+'</span></p>';
                            }

                            shtml += '</div>';
                            shtml += '</div>';

                            // 状态
                            shtml += '<div class="col-100 discover-bottom" onclick="location.href=\'/wap.php?g=Wap&c=Discover&a=look_discover_msg&discover_id='+discover_msg_list[i]['discover_id']+'\'">';
                            shtml += '<div class="bbs-foot">';

                            if(status == 5){
                                var discover_refusal = '';
                                if (discover_msg_list[i]['discover_refusal'].length > 10) {
                                    discover_refusal = discover_msg_list[i]['discover_refusal'].substring(0,10) + '...';
                                } else {
                                    discover_refusal = discover_msg_list[i]['discover_refusal'];
                                }
                                shtml += '<p>状态：<span>已拒绝('+discover_refusal+')</span></p>';
                            } else {
                                shtml += '<p>状态：<span>待审核</span></p>';
                            }

                            shtml += '</div>';
                            shtml += '</div>';

                            shtml += '<div class="operation-btn">';
                            shtml += '<a  onclick="location.href=\'/wap.php?g=Wap&c=Discover&a=discover_msg_edit&discover_id='+discover_msg_list[i]['discover_id']+'\'" class="reload">编辑</a>';
                            shtml += '<a data-discover_id="'+discover_msg_list[i]['discover_id']+'" href="javascript:void(0)" class="delete">删除</a>';
                            shtml += '</div>';

                            shtml += '</div></li>';
                        }
                        $('.bbs-list').append(shtml);
                        myScroll.refresh();

                        $('.user_collect').click(function(e){
                            var discover_id = $(this).data('discover_id');
                            var collection = $(this).data('collection');
                            console.log('collection--------', collection);
                            if(aa == 1){
                                if (!collection) {
                                    collection_msg(discover_id, this);
                                } else {
                                    cancel_collection_msg(discover_id, this);
                                }
                            }
                        });

                        $('.user_likes').click(function(e){
                            var discover_id = $(this).data('discover_id');
                            var likes = $(this).data('likes');
                            console.log('likes--------', likes)
                            if(bb == 1){
                                if (!likes) {
                                    likes_msg(discover_id, this);
                                } else {
                                    cancel_likes_msg(discover_id, this);
                                }
                            }
                        });


                        // 删除
                        $('.delete').click(function(e){
                            if(aa == 1){
                                aa =2;
                                var discover_id = $(this).data('discover_id');
                                console.log('删除的信息id', discover_id);
                                layer.open({
                                    content:'确认删除？',
                                    btn: ['确定','取消'],
                                    yes:function(){
                                        var del_discover_msg = "{pigcms{:U('del_discover_msg')}";
                                        $.post(del_discover_msg,{'discover_id':discover_id},function(data){
                                            console.log('删除返回信息：', data);
                                            aa = 1;
                                            if(data.status==0){
                                                layer.open({
                                                    content: data.msg,
                                                    btn: ['确定'],
                                                    shadeClose: false
                                                });
                                            }else{
                                                layer.open({
                                                    content: '删除成功！',
                                                    btn: ['确定'],
                                                    shadeClose: false
                                                });
                                                window.location.reload();
                                            }
                                        },'json')
                                    },
                                    no: function(){
                                        aa = 1;
                                    }
                                });
                            }
                        });
                    }


                },'json')
            }
        });
    }


    $('#plus').click(function(){
        location.href = "{pigcms{:U('add_discover_msg')}"
    });



    $('#backBtn').click(function(){
        if(motify.checkApp() && motify.getLifeAppVersion() >= 200){
            if(motify.checkAndroid()){
                window.lifepasslogin.webViewGoBack();
            }else{
                iosCommon.iosFunction('webViewGoBack', {name:'webViewGoBack'})
            }
        }else{
            window.history.go(-1);
        }
    });
</script>

</html>
</body>

</html>