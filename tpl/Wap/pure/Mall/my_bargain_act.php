<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Expires" content="-1">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Pragma" content="no-cache">
    <meta name="format-detection" content="telephone=no">
    <meta charset="utf-8">
    <title>我的砍价</title>
    <link href="{pigcms{$static_path}mall/css/frozenui.css"  rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/index.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/cate_common.css" rel="stylesheet">
    <link href="{pigcms{$static_path}mall/css/pullToRefresh.css" rel="stylesheet">
    <script src="{pigcms{$static_path}mall/js/jquery.min.js"></script>
    <style type="text/css">
        #pageLoadTip{display:none !important;}
    body{background:#fff}
    a.btn{
        float: right;
        background: red;
        text-align: center;
        color: #fff;
        border-radius: 40px;
        line-height: normal;
        margin: 5px 0;
        font-size: 12px;
        padding: 5px 10px;
    }
    a.btn.buy{
        background: #dedede;
    }
    .clock span{
        background-color: #000;
        color: #fff;
        padding: 0 2px;
    }
    .goods_name{
        white-space: nowrap;
        overflow: hidden;
        text-overflow:ellipsis;
        width:2.2rem;
    }
    </style>
</head>
<body>
    <div class="">
        <div class=" content" >
            <div id="my_can" class=" bg_f my_can">
                <div class="demo-block ping_tuan ping_kan mb_0 " id="wrapper" style="top:0px">
                    <ul class="ui-list ui-border-tb mb_0" id="ui_list">

                    </ul>
                </div>
            </div>
        </div>
        <div>
            <div class="footerMenu wap footer">
                <ul class="flex">
                    <li class="flex_one">
                        <a class="can" href="javascript:;" data-url="{pigcms{:U('Mall/cut_goods_list')}">
                            <div class="flex w100 h100"  style="justify-content: center;">
                                <em class="cut_good" style="margin: unset;width:18px;height:18px"></em>
                                <p style="padding:3px 0px 0px 3px">砍价商品</p>
                            </div>
                        </a>
                    </li>
                    <li class="flex_one">
                        <a href="javascript:;" class="can juan active" data-url="{pigcms{:U('Mall/my_bargain_act')}">
                            <div class="flex w100 h100" style="justify-content: center;">
                                <em class="my_act" style="margin: unset;width:18px;height:18px"></em>
                                <p style="padding:3px 0px 0px 3px">我的砍价</p>
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
            <li class="block">
                <& if(_item.status==0){ &>
                    <a href="<&= _item.url &>">
                <& }else if(_item.status==1){ &>
                    <a href="<&= _item.buy_url &>">
                <& }else if(_item.status==2){ &>
                    <a href="<&= _item.new_url &>">                    
                <& } &>
                <div class="flex">
                    <div class="ui-list-img-square">
                        <span>
                            <img src="<&= _item.image &>" class="w100 h100">
                        </span>
                    </div>
                    <div class="ui-list-info ui-border-t flex_one">
                        <h4 class="ui-nowrap font_size16 goods_name">
                            <&= _item.name &>
                            <& if(_item.spec_str){ &>
                                <span class='font_size13 color_7'>(<&= _item.spec_str &>)</span>
                            <& } &>
                        </h4>
                        <p class="ui-nowrap money" style="margin:3px 0px">
                            <& if(_item.status==0 || _item.status==1){ &>
                            <span class="font_size14 color_7 rel"><span>已砍</span>{pigcms{$config['Currency_symbol']}<&= _item.bar_total_price &></span>
                            <span class="font_size14 font-weight ml10"><span>还剩</span>{pigcms{$config['Currency_symbol']}<&= _item.bar_discount_price &></span>
                            <& }else{ &>
                            <span style="height:19px;display:block;"></span>
                            <& } &>
                        </p>
                        <p>
                            <& if(_item.status==0){ &>
                                <& if(_item.bar_discount_price==0){ &>
                                    <span class="font_size14 color_7 color_red">砍价成功！</span>
                                    <a href="<&= _item.url &>" class='btn'>去购买</a>
                                <& }else{ &>
                                    <b class='clock time<&= _item.goods_id &>'  data-clock="<&= _item.time_clock &>" <&= View.times(_item.time_clock,_item.goods_id ) &>>
                                        <span class='h joinh'>00</span>
                                        :
                                        <span class='m joinm'>00</span>
                                        :
                                        <span class='s joins'>00</span>后结束
                                    </b>
                                    <a href="<&= _item.url &>" class='btn'>继续砍价</a>
                                <& } &>                                
                            <& }else if(_item.status==1){ &>        
                                <span class="font_size14 color_7 color_red">砍价成功！</span>
                                <a href="javascript:;" class='btn buy'>已购买</a>
                            <& }else if(_item.status==2){ &>
                                <span class="font_size14 color_7">砍价失败</span>
                                <a href="<&= _item.new_url &>" class='btn'>重新砍价</a>     
                            <& } &>                                
                        </p>
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
    <script src="{pigcms{$static_path}mall/js/swiper.min.js"></script>
    <script src="{pigcms{$static_path}mall/js/droprefresh.js"></script>
    <script src="{pigcms{$static_path}mall/js/pullToRefresh.js"></script>


    <script>
        var url="{pigcms{:U('Mall/my_bargain_act')}";
        var View={
            init:function(){
                this.initList();
                this.initClick()
            },
            setting:{
                page_size:6,
                total_pages:0,
                pageNo: 1
            },
            isEmpty: function(list){
                var b = View.setting.pageNo == 1 && !list.length;
                return b;
            },
            times:function(time,id){
                APP.timer({
                    time: time*1*1000,
                    onBackTime:function(d){
                        if(d[1] > 0) {
                            $(".time"+id).find(".joinh").html(d[1]);
                        } else {

                        }
                        $(".time"+id).find(".joinm").html(d[2]);
                        $(".time"+id).find(".joins").html(d[3]);
                    }
                })
            },
            initList:function(){
                var _self=this;
                $("#wrapper").css('height',($(window).height()-50)+'px')
                refresher.init({
                    id:"wrapper",
                    pullDownAction:Refresh,
                    pullUpAction:Load
                });
                function Refresh(){
                    _self.ajax_data(false,true);
                }
                function Load(){
                    _self.ajax_data(true,true);
                }
                _self.ajax_data(false,true);
            },
            ajax_data:function(type,refresh){
                var _this=this;
                if(type){
                    _this.setting.pageNo++
                }else{
                    _this.setting.pageNo=1
                }
                $.post(url,{page:_this.setting.pageNo},function(data){
                    var res=data;
                    if(typeof(res)=='string'){
                        res=$.parseJSON(data)
                    }
                    if(!res || res.length==0){
                        res=[]
                    }
                    var tp = Simplite.getTemplate('list');
                    var compiledTpl = Simplite.compile(tp)(res);
                    if(type){
                        $('#wrapper ul').append(compiledTpl);
                    }else{
                        $('#wrapper ul').html(compiledTpl);
                    }
                    refresher.isEmpty(res,_this.setting.pageNo)
                    refresher.isNomore(res,_this.setting.pageNo,'','',res.length==0?true:false)
                    setTimeout(function(){
                        myScroll.refresh();
                    },500)
                });
            },
            initClick:function(){
                $(document).on('click', '.footerMenu a', function(event){
                    if($(this).hasClass('active')){
                        return false;
                    }
                    APP.fnUrlReplace($(this).data('url'))
                });
            }
        }
        var time_arr=[];
        window.View=View;
        View.init();
    </script>
    <!-- 微信分享 -->
    <script type="text/javascript">
        window.shareData = {
            "moduleName":"Mall",
            "moduleID":"0",
            "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
            "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Mall/cut_goods_list')}",
            "tTitle": "推荐您多款砍价商品",
            "tContent": "点击查看更多进行中砍价商品"
        };
    </script>
    {pigcms{$shareScript}
</body>
</html>