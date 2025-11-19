<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>分类</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name='apple-touch-fullscreen' content='yes'>
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <link rel="stylesheet" type="text/css"  href="{pigcms{$static_path}integral_mall/css/community_base.css?v=02">
    <link rel="stylesheet" type="text/css"  href="{pigcms{$static_path}integral_mall/css/base.css?v=02">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}integral_mall/css/swiper.min.css?v=02">
    <script src="{pigcms{$static_path}integral_mall/js/rem.js"></script>
    <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
    <script src="{pigcms{$static_path}integral_mall/js/swiper.min.js"></script>
    <script src="{pigcms{$static_path}integral_mall/js/dist/mescroll.min.js"></script>
    <script src="{pigcms{$static_path}integral_mall/js/dist/mescroll.js"></script>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}integral_mall/js/dist/mescroll.css?v=02">
    <style>
        .dropload-down{
            height: 0px;
            display: none;
        }
        .upwarp-nodata{
            text-align: center;
            display:none;
        }
        .downwarp-tip{
            text-align: center;
            display:none;
        } 
        .b2b_ass_con dd .b2b_xhlsj span{
            float: left;
            font-size: 0.4rem;
            color: #fff; 
        }
        .jf_dui_goods dd img{
            float:none;
            margin-top:15px;
        }
        .jf_dui_goods dd p{
            float:none;
            line-height:1.36rem;
            text-align:center;
        }
        .b2b_ass_con dd .b2b_xhlsj{
            padding: 0 5px;
            box-sizing: border-box;
        }
         .jf_dui_goods .b2b_xhlsj{
            padding:0 10px;
        }
        .jf_dui_goods dd span{
            background:#fff;
            margin-top:0;
        }
        .jf_dui_goods dd em{
            float: right;
            width: 1.8rem;
            height: 0.8rem;
            line-height: 0.8rem;
            background: #CAB98D;
            color: #473508;
            text-align: center;
            display: block;
            font-size: 0.42rem;
            border-radius: 0.1rem;
        }
        .b2b_dh_header .b2b_dh_search a{
            line-height:1.04rem;
            height:1.04rem;
            margin-top:0.2rem; 
            width:auto; 
            text-align:center;
            padding:0 10px;
            color:#fff;
            font-size:12px; 
        }
        .b2b_dh_header .b2b_dh_search a.searchData{ 
            background:#FF1821; 
        }
        .b2b_dh_header .b2b_dh_search {
            position: relative;
        } 
        .b2b_dh_header .b2b_dh_search a.del_Data{
            width:20px;
            background:url('{pigcms{$static_path}integral_mall/images/xtb_03.png') center no-repeat;
            background-size:15px auto;
            /* position: absolute;
            right:0;
            top:0;  */
            display:none;
        }
        .b2b_dh_header .b2b_dh_search input{
            width:10.54rem;
        }
        .mescroll{
            height: 100%;
            overflow-y: scroll;
        }
    </style>
</head>
<body style="background: #F6F8FA;">

<!--begin：头部-->
<div class="b2b_dh_header">
    <a href="javascript:history.go(-1)" class="back"></a>
    <div class="b2b_dh_search fr" style="margin-right: 0.2rem; margin-left: 0;">
        <input type="text" name="keywords" id="keywords" value="" placeholder="请输入您要搜索的商品名称" autocomplete="off" />
        <a href="javascript:;" class='searchData'>搜索</a>
        <a href="javascript:;" class='del_Data'></a>  
    </div>

</div>
<div class="b2b_dh_foot_null"></div>
<!--end：头部-->  
<div class="b2b_assortment">
    <ul class="b2b_ass_tab" id="qdd_tab">
        <li id="0"  <eq name="cat_id" value="0">class="active"</eq>><a <eq name="cat_id" value="0">class="active"</eq>  href="{pigcms{:U('storeCate',array('mer_id'=>$_GET['mer_id']))}">全部</a></li>
        <volist name="category" id="co">
        <li id="{pigcms{$co.cat_id}" <eq name="cat_id" value="$co.cat_id">class="active"</eq>><a  <eq name="cat_id" value="$co.cat_id">class="active"</eq> href="{pigcms{:U('storeCate',array('mer_id'=>$_GET['mer_id'],'cat_id'=>$co['cat_id']))}">{pigcms{$co.cat_name}</a></li>
        </volist>
    </ul>

    <div class="b2b_ass_con b2b_ass_con1" id="qdd_con">
        <dl class="clearfix jf_goods" style="display: block;">
            <dl class="clearfix jf_goods" style="display: block;">
                <div id="mescroll" class="mescroll">
                    <div id="data_list">

                    </div>
                </div>
                <!--<dd>
                    <a href="">
                        <img src="http://statics.76sd.com//data/files/goods/20190115/small_5c3d7c9eca275.jpg" alt="J-侯伯颂 红酒 飘红干红葡萄酒 750ml 12.5%vol （6瓶/箱）" onerror="this.onerror=null;this.src='http://statics.76sd.com/static/b2bwap/image/jf_default.png';">
                        <p class="tl2">J-侯伯颂 红酒 飘红干红葡萄酒 750ml 12.5%vol （6瓶/箱）</p>
                        <div class="b2b_xhlsj">
                            <b>兑换券：156</b>
                            <em>兑换</em>
                        </div>
                    </a>
                </dd>--> 
            </dl> 
        </dl>
    </div>
</div>

<div class="goods_list" style="display: none;">
    <dl class="jf_dui_goods clearfix"> 
    </dl>
</div> 
<!-- <div class="search_box  ">
    <dl class='b2b_ass_con'>

    </dl>
</div>  -->
<script>
    var page_list_url = "{pigcms{:U('ajax_goods_page_list')}";
    var mer_id = "{pigcms{:$_GET['mer_id']}"; 
    $(function(){ 
         // 选项卡
         var aBtn=$('#qdd_tab li');
            aBtn.click(function () {
                aBtn.removeClass();
                $(this).addClass('active');

            }); 
            $('#keywords').on('input',function () {  
                if($(this).val().length == 0){
                    $('.clearfix dd').show();
                    $('.b2b_assortment').show();
                    $('.b2b_ass_con1').show();
                    $('.goods_list').hide(); 
                    $('.search_box').hide();
                } 

            }) 
            $('.dropload-down').remove();
            var tabLoadEnd = false;
            var page = 2; 

        //创建MeScroll对象
        var mescroll = new MeScroll("mescroll", {
            preventDefault: false,
            click: true,
            taps:true,
            down: {
                auto: false, //是否在初始化完毕之后自动执行下拉回调callback; 默认true
                isBounce: false, //此处禁止ios回弹,解析(务必认真阅读,特别是最后一点): http://www.mescroll.com/qa.html#q10
                callback: downCallback //下拉刷新的回调
            },
            up: {
                auto: true, //是否在初始化时以上拉加载的方式自动加载第一页数据; 默认false
                isBounce: true, //此处禁止ios回弹,解析(务必认真阅读,特别是最后一点): http://www.mescroll.com/qa.html#q10
                callback: upCallback, //上拉回调,此处可简写; 相当于 callback: function (page) { upCallback(page); }
                toTop:{ //配置回到顶部按钮
                    src : "../res/img/mescroll-totop.png", //默认滚动到1000px显示,可配置offset修改
                    //offset : 1000
                }
            }
        });

        /*下拉刷新的回调 */
        function downCallback(){
            //联网加载数据
            getListDataFromNet(0, 1, function(data){
                //联网成功的回调,隐藏下拉刷新的状态
                //mescroll.endSuccess();
                mescroll.endErr();
                //设置列表数据
                //setListData(curPageData.list.list, false);
            }, function(){
                //联网失败的回调,隐藏下拉刷新的状态
                mescroll.endErr();
            });
        }

        /*上拉加载的回调 page = {num:1, size:10}; num:当前页 从1开始, size:每页数据条数 */
        function upCallback(page){ 
            //联网加载数据
            getListDataFromNet(page.num, page.size, function(curPageData){
                console.log("page.num="+page.num+", page.size="+page.size+", curPageData.length="+curPageData.length);
                mescroll.endBySize(curPageData.list.list.length, curPageData.count);
                //设置列表数据
                setListData(curPageData.list.list, true);
            }, function(){
                //联网失败的回调,隐藏下拉刷新和上拉加载的状态;
                console.log('net_default');
                mescroll.endErr();
            });

            // 搜索
            getListDataSearch(page.num, page.size, function(curPageData){ 
                console.log("page.num="+page.num+", page.size="+page.size+", curPageData.length="+curPageData.length);
                mescroll.endBySize(curPageData.list.list.length, curPageData.count);
                //设置列表数据
                // setListProData(curPageData.list.list, true);
            }, function(){
                //联网失败的回调,隐藏下拉刷新和上拉加载的状态;
                console.log('net_default');
                mescroll.endErr();
            });


        }

        // 设置搜索商品的列表 
        // function setListProData(curPageData, isAppend) { 
        //     var html = '';
        //     for ( var i = 0; i <curPageData.length; i++){
        //         var newObj =curPageData[i];
        //         html += '<dd><a href="'+newObj['detail_url']+'">';
        //         html += '<img src="'+newObj['wap_pic_first']+'">';
        //         html += '<p class="tl2">'+newObj['goods_name']+'</p>';
        //         html += '<div class="b2b_xhlsj"><b>兑换券：'+newObj['payment_pure_integral']+'</b><em>兑换</em></div></a></dd>';
        //     } 
        //     $('.goods_list .jf_dui_goods').html(html); 
        // }

        /*设置列表数据*/
        function setListData(curPageData, isAppend) {
            var html = '';
            for ( var i = 0; i <curPageData.length; i++){
                var newObj =curPageData[i];
                html += '<dd><a href="'+newObj['detail_url']+'">';
                html += '<img src="'+newObj['wap_pic_first']+'">';
                html += '<p class="tl2">'+newObj['goods_name']+'</p>';
                html += '<div class="b2b_xhlsj"><b>积分：'+newObj['payment_pure_integral']+'</b><em>兑换</em></div></a></dd>';
            }
            console.log(html);
            $('#data_list').append(html);
        } 

        function getListDataFromNet(pageNum,pageSize,successCallback,errorCallback) {
            var cat_id = '{pigcms{$cat_id}';
            $.ajax({
                type: "POST",
                url: page_list_url,
                data: {"page":pageNum,'cat_id':cat_id,"mer_id":mer_id},
                dataType: "json",
                success: function(data){
                    console.log(data);
                    successCallback&&successCallback(data);
                },
                error:function(){
                    console.log('errorCallback');
                    errorCallback&&errorCallback();
                }
            }); 
        } 

        function getListDataSearch(pageNum,pageSize,successCallback,errorCallback) {  
            var location_url ='{pigcms{:U('Integral_mall/ajax_goods_page_list')}';
            var mer_id = "{pigcms{:$_GET['mer_id']}";  
            // var page=1;
            var keywords = $('#keywords').val();
            $.ajax({
                type: "POST",
                url: location_url,
                data: {"keyword":keywords,"mer_id":mer_id,'page':pageNum},
                dataType: "json",
                success: function(data){ 
                    console.log(data); 
                    var curPageData=data.list.list; 
                    var html = '';
                    if(curPageData.length !=0 ){   
                        for ( var i = 0; i <curPageData.length; i++){
                            var newObj =curPageData[i];
                            html += '<dd><a href="'+newObj['detail_url']+'">';
                            html += '<img src="'+newObj['wap_pic_first']+'">';
                            html += '<p class="tl2">'+newObj['goods_name']+'</p>';
                            html += '<div class="b2b_xhlsj"><span style="float:left">积分：'+newObj['payment_pure_integral']+'</span><em>兑换</em></div></a></dd>';
                            $('.goods_list .jf_dui_goods').html(html);  
                       } 
                    }else{ 
                         $('.goods_list .jf_dui_goods').html('<p style="color:#999; text-align:center; padding:40px 0"><img src="{pigcms{$static_path}integral_mall/images/ksjt.png" style="width:128px;" /></p>'); 
       
                    }
                    successCallback&&successCallback(data);  
                    
                },
                error:function(){
                    console.log('errorCallback');
                    errorCallback&&errorCallback(); 
                }
            });    
        } 
        $('.searchData').click(function(){   
            var keywords = $('#keywords').val();
                if (keywords){
                    $('.clearfix dd').hide();
                    $('.b2b_assortment').hide();
                    $('.b2b_ass_con1').hide();
                    $('.goods_list').show();
                    $('.search_box').show();
                }else{
                    $('.clearfix dd').show();
                    $('.b2b_assortment').show();
                    $('.b2b_ass_con1').show();
                    $('.goods_list').hide(); 
                }    
             getListDataSearch();  
        }) 
         
    }); 

</script>
<!-- <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>   -->
</body>
</html>

