<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta http-equiv="Expires" content="-1" />
    <meta http-equiv="Cache-Control" content="no-cache" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta name="format-detection" content="telephone=no" />
    <meta charset="utf-8">
    <title>我的拼团</title>
    <link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/pullToRefresh.css" rel="stylesheet" />
    <link href="{pigcms{$static_path}mall/css/cate_common.css" rel="stylesheet" />
    <script src="{pigcms{$static_path}mall/js/jquery.min.js"></script>
    <style>
        #pageLoadTip{display:none !important;}
        body{background:#fff}
        .again_buy {
            background: -webkit-linear-gradient(left, #EC4341, #FD8E6F); /* Safari 5.1 - 6.0 */
            background: -o-linear-gradient(right, #EC4341, #FD8E6F); /* Opera 11.1 - 12.0 */
            background: -moz-linear-gradient(right, #EC4341, #FD8E6F); /* Firefox 3.6 - 15 */
            background: linear-gradient(to right, #EC4341, #FD8E6F); /* 标准的语法 */
        }

        .ui-header {
            top: 0;
            height: 45px;
            line-height: 45px;
            position: fixed;
            width: 100%;
            z-index: 100;
            left: 0;
            text-align:center
        }
        .pd-t45{padding-top:45px}
        .ping:after{
            border-top: 4px solid #f23030;
            top: 40px;
            content: '';
            position: absolute;
            width: 22%;
            height: 1px;
            left: 39%;
        }
        .ping{
            color:#f23030
        }
        .content{
            margin-top: 45px;
        }
        .go_url.btn{
            font-size: 12px;
            padding: 5px 10px;
            color: #fff;
            border: none;
            border-radius: 24px;
        }
        .bg_green{
            background: -webkit-linear-gradient(left, #00CC76, #6FFFC1); /* Safari 5.1 - 6.0 */
            background: -o-linear-gradient(right, #00CC76, #6FFFC1); /* Opera 11.1 - 12.0 */
            background: -moz-linear-gradient(right, #00CC76, #6FFFC1); /* Firefox 3.6 - 15 */
            background: linear-gradient(to right, #00CC76, #6FFFC1); /* 标准的语法 */
        }

        .ui-list-img-square {
            width: 85px;
            height: 85px;
        }
        #refresh {
            position: absolute;
            width: 100%;
            overflow: hidden;
            left: 0px;
            top: 45px;
            bottom: 50px;
            right: 0px;
        }
        .ping_tuan .pin_num span{
            padding:2px 6px;
        }
    </style>
</head>
<body>
    <div class="" >
        <div id="header" class="ui-header my_ping bg_f">
            <div class="flex">
                <div class="flex_one rel ping cursor" data-status=''>全部</div>
                <div class="flex_one rel cursor" data-status='0'>进行中</div>
                <div class="flex_one rel cursor"  data-status='1'>已拼成</div>
                <div class="flex_one rel cursor"  data-status='2'>已过期</div>
            </div>
        </div>
        <div class="content" >
            <div id="my_ping" class="my_ping ">
                <div class="demo-block ping_tuan mb_0" id="refresh">
                    <ul class="ui-list ui-border-tb mb_0" id="ui_list">
                    </ul>
                </div>
            </div>
        </div>
        <div>
            <div class="footerMenu wap footer ui-border-t">
                <ul class="flex">
                    <li class="flex_one ui-border-r">
                        <a class="can" href="javascript:;" data-url="{pigcms{:U('Mall/group_goods_list')}">
                            <div class="flex w100 h100"  style="justify-content: center;">
                                <em class="group_good" style="margin: unset;width:18px;height:18px"></em>
                                <p style="padding:3px 0px 0px 3px">拼团商品</p>
                            </div>
                        </a>
                    </li>
                    <li class="flex_one">
                        <a href="javascript:;" class="juan active" data-url="{pigcms{:U('Mall/my_group_act')}">
                            <div class="flex w100 h100" style="justify-content: center;">
                                <em class="my_act" style="margin: unset;width:18px;height:18px"></em>
                                <p style="padding:3px 0px 0px 3px">我的拼团</p>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <script type="text/html" id="list">
        <& for(var i=0;i< _this.length;i++) {&>
            <& var _item=_this[i] &>
            <li class="block ui-border-t">
                <a href="<&= _item.order_url &>&from=my_group_act">
                    <div class="flex flex_baseline">
                        <div class="ui-list-img-square">
                            <span>
                                <img src="<&= _item.image &>" class="w100 h100">
                            </span>
                        </div>
                        <div class="ui-list-info  flex_one res">
                            <div class="flex flex_baseline">
                                <div>
                                    <h4 class="ui-nowrap-multi  font_size16  goods_name flex_one"><&= _item.name &></h4>
                                    <p class="ui-nowrap pin_num inline color_red" style="margin-bottom: -5px;"><span class="color_red inline bg_none"><&= _item.complete_num &>人团</span><span class="color_red inline bg_none" style="padding:0px 0px 0px 10px">¥<span class="font_size18 bg_none pl0"><&= _item.price &></span></span></p>
                                    <div class="demo-block">
                                        <& if(_item.status==0){ &>
                                        <span class='btn again_buy cursor go_url' data-url="<&= _item.group_url &>"> 拼团详情</span>
                                        <& }else{ &>
                                        <span class='btn again_buy cursor go_url' data-url="{pigcms{:U('Mall/detail')}&goods_id=<&= _item.goods_id &>&group_id=<&= _item.group_id &>"> 再次购买</span>
                                        <span class='btn bg_green cursor go_url' style="    margin-left: 8px;" data-url="<&= _item.group_url &>&from=my_group_act"> 拼团详情</span>
                                        <& } &>
                                    </div>
                                </div>
                                
                                <div ><img src="{pigcms{$static_path}mall/images/<&=_item.status==2?'is_out.png':_item.status==0?'is_going.png':'is_success.png' &>"/></div>

                            </div>

                        </div>
                    </div>
                </a>
            </li>
        <& } &>
        <& if(View.isEmpty(_this)){&>
        <li class="w100 align_center ml_0" >
            <div class="weui-loadmore  w100" >
                <div><img src="{pigcms{$static_path}mall/images/noresult.png"/></div>
                <span class="weui-loadmore__tips" style="color: #999">暂无数据</span>
            </div>
        </li>
        <&}&>
    </script>
    <script>var $home=false</script>
    <script src="{pigcms{$static_path}mall/js/cate_common.js"></script>
        <script src="{pigcms{$static_path}mall/js/simplite.js"></script>
        <script src="{pigcms{$static_path}mall/js/droprefresh.js"></script>
        <script src="{pigcms{$static_path}mall/js/pullToRefresh.js"></script>
        <script>
            var url="{pigcms{:U('Mall/my_group_act')}";
            var  View={
                init:function(){
                    this.showList();
                    this.checkStatus();
                    this.checkClick();
                },
                setting:{
                    status:'',
                    pageNo:1,
                    page_size:10
                },
                isEmpty: function(list){
                    var b = View.setting.pageNo == 1 && !list.length;
                    if(b){
                        $(".ui-list").removeClass("ui-border-tb")
                    }else{
                        $(".ui-list").addClass("ui-border-tb")
                    }

                    return b;
                },
                showList:function(){
                    var _self=this;
                    //初始化
                    this.get_good_list(false,false,true);


                    refresher.init({
                        id:"refresh",
                        pullDownAction:Refresh,
                        pullUpAction:Load
                    });
                    function Refresh(){
                        //刷新
                        _self.get_good_list(false,true,true);
                    }
                    function Load(){
                        //加载更多
                        _self.get_good_list(true,true,true);
                    }
                },
                checkStatus:function(){
                    var _self=this;
                    $('.flex .flex_one.rel').on('click',function(){
                        if($(this).hasClass('ping')){
                            return false;
                        }
                        $(this).addClass('ping').siblings().removeClass('ping');
                            _self.setting.status=$(this).data('status');
                        //点击
                        _self.get_good_list(false,true,false);
                    });
                },
                get_good_list:function(type,fresh,load){
                    var _this=this;
                    if(type){
                        _this.setting.pageNo++
                    }else{
                        _this.setting.pageNo=1;

                    }
                    $.post(url,{page:_this.setting.pageNo,limit:_this.setting.page_size,status:_this.setting.status},function(data){
                        if(typeof(data)=='string'){
                            data=$.parseJSON(data)
                        }
                        if(data &&  data!=null && data.length>0){
                        }else{
                            data=[]
                        }
                        var tp = Simplite.getTemplate('list');
                        var compiledTpl = Simplite.compile(tp)(data);
                        if(type){
                            $('#my_ping ul').append(compiledTpl);
                        }else{
                            $('#my_ping ul').html(compiledTpl);
                        }
                        refresher.isEmpty(data,_this.setting.pageNo);
                        refresher.isNomore(data,_this.setting.pageNo,'','',data.length===0?true:false);
                        setTimeout(function(){
                            myScroll.refresh();
                            if(!load && fresh){
                                $("html,body").animate({"scrollTop":0},300);
                            }
                        },500)


                    });
                },
                checkClick: function () {
                    $(document).on('click', '.footerMenu a', function(event){
                        if($(this).hasClass('active')){
                            return false;
                        }
                        APP.fnUrlReplace($(this).data('url'))
                    });
                    $(document).on('click', '.go_url', function(event){
                        event.preventDefault();
                        event.stopPropagation();
                       window.location.href=$(this).attr('data-url')
                    });
                }
            }
            window.View=View;
            View.init();
        </script>
        <!-- 微信分享 -->
        <script type="text/javascript">
            window.shareData = {
                "moduleName":"Mall",
                "moduleID":"0",
                "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
                "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/group_goods_list')}",
                "tTitle": "推荐您多款拼团商品",
                "tContent": "点击查看更多进行中拼团商品"
            };
        </script>
        {pigcms{$shareScript}
    </body>
    </html>