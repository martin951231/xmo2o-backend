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
        .upwarp-nodata{
            text-align: center;
            display:none;
        }
        .downwarp-tip{
            text-align: center;
            display:none;
        }
        .dropload-down{
            height: 0px;
            display: none;
        }
        .tabble{
            padding: 5px 0px;
            background: #fff;
        }
        .tabble .flex_one{line-height: 1.2rem;height: 1.2rem;}
        .b2b_dh_header .flex{ padding: 0px 1.6666rem; height: 100%;}
        .b2b_dh_header img{    width: 1.6rem;height: 1.6rem;border-radius: 50%;}
        .b2b_dh_header .flex_one{margin-left: -1.12rem;text-align: center;}
        .jf_dui_goods dd{
            height:8.5rem;
        }
        .mescroll {
            position: fixed;
            top: 0;
            bottom: 0;
            height: 100%;
            overflow-y: scroll;
            margin-top: 2.64rem;
        }
    </style>
</head>
<body style="background: #F6F8FA;">

<!--begin：头部-->
<div class="b2b_dh_header" style="height:2.64rem">
    <div class="flex">
       <div>
           <img src="{pigcms{$user['avatar']}"/>
       </div>
        <div class="flex_one">
            <p>积分余额</p>
            <div>{pigcms{$score.card_score}</div>
        </div>
    </div>
</div>
<div class="b2b_dh_foot_null" style="height:2.64rem"></div>

<div id="mescroll" class="mescroll">
    <div id="goodsList">
        <dl class="jf_dui_goods clearfix ">
            <dt></dt>
            <div id="data_list">

            </div
        </dl>
    </div>
    <div class="dropload-down">
        <div class="dropload-refresh">

        </div>
    </div>
</div>
<script>
    var page_list_url = "{pigcms{:U('ajax_goods_page_list')}";
    var mer_id = "{pigcms{:$_GET['mer_id']}";
    var score = "{pigcms{$score.card_score}";
    var pageNum = 1;
    $(function(){
        //创建MeScroll对象
        var mescroll = new MeScroll("mescroll", {
            down: {
                auto: true, //是否在初始化完毕之后自动执行下拉回调callback; 默认true
                isBounce: true,
                callback: downCallback //下拉刷新的回调
            },
            up: {
                auto: false, //是否在初始化时以上拉加载的方式自动加载第一页数据; 默认false
                isBounce: true, //此处禁止ios回弹,解析(务必认真阅读,特别是最后一点): http://www.mescroll.com/qa.html#q10
                callback: upCallback, //上拉回调,此处可简写; 相当于 callback: function (page) { upCallback(page); }
                // toTop:{ //配置回到顶部按钮
                //     src : "../res/img/mescroll-totop.png", //默认滚动到1000px显示,可配置offset修改
                //     //offset : 1000
                // }
            }
        });

        /*下拉刷新的回调 */
        function downCallback(){
            //联网加载数据
            getListDataFromNet(pageNum, 1, function(data){
                //联网成功的回调,隐藏下拉刷新的状态
                //mescroll.endSuccess();
                mescroll.endErr();
                //设置列表数据
                setListData(data.list.list, false);
            }, function(){
                //联网失败的回调,隐藏下拉刷新的状态
                mescroll.endErr();
            });
        }

        /*上拉加载的回调 page = {num:1, size:10}; num:当前页 从1开始, size:每页数据条数 */
        function upCallback(page){
            //联网加载数据
            getListDataFromNet(parseInt(page.num)+1, page.size, function(curPageData){
                console.log('curPageData', curPageData);
                //联网成功的回调,隐藏下拉刷新和上拉加载的状态;
                //mescroll会根据传的参数,自动判断列表如果无任何数据,则提示空;列表无下一页数据,则提示无更多数据;
                console.log("page.num="+page.num+", page.size="+page.size+", curPageData.length="+curPageData.length);
                //方法一(推荐): 后台接口有返回列表的总页数 totalPage
                //mescroll.endByPage(curPageData.length, totalPage); //必传参数(当前页的数据个数, 总页数)

                //方法二(推荐): 后台接口有返回列表的总数据量 totalSize
                //mescroll.endBySize(curPageData.length, totalSize); //必传参数(当前页的数据个数, 总数据量)

                //方法三(推荐): 您有其他方式知道是否有下一页 hasNext
                //mescroll.endSuccess(curPageData.length, hasNext); //必传参数(当前页的数据个数, 是否有下一页true/false)

                //方法四 (不推荐),会存在一个小问题:比如列表共有20条数据,每页加载10条,共2页.如果只根据当前页的数据个数判断,则需翻到第三页才会知道无更多数据,如果传了hasNext,则翻到第二页即可显示无更多数据.
                //mescroll.endSuccess(curPageData.length);
                mescroll.endBySize(curPageData.list.list.length, curPageData.count);
                //提示:curPageData.length必传的原因:
                // 1.判断是否有下一页的首要依据: 当传的值小于page.size时,则一定会认为无更多数据.
                // 2.比传入的totalPage, totalSize, hasNext具有更高的判断优先级
                // 3.使配置的noMoreSize生效

                //设置列表数据
                setListData(curPageData.list.list, true);
            }, function(){
                //联网失败的回调,隐藏下拉刷新和上拉加载的状态;
                console.log('net_default');
                mescroll.endErr();
            });
        }

        /*设置列表数据*/
        function setListData(curPageData, isAppend) {
            var html = '';
            for ( var i = 0; i <curPageData.length; i++){
                var newObj =curPageData[i];
                html += '<dd><a class="scroll10241726" href="'+newObj['detail_url']+'">';
                html += '<img src="'+newObj['wap_pic_first']+'">';
                html += '<p>'+newObj['goods_name']+'</p>';
                html += '<span><b>积分</b><strong>'+newObj['payment_pure_integral']+'</strong></span>';
                html += '</a></dd>';
            }
            if (pageNum==1) {
                $('#data_list').html(html);
            } else {
                $('#data_list').append(html);
            }
        }


        function getListDataFromNet(pageNum,pageSize,successCallback,errorCallback) {
            $.ajax({
                type: "POST",
                url: page_list_url,
                data: {"page":pageNum,"score":score,"mer_id":mer_id},
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

    });
</script>

<script>
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
                $('.b2b_ass_con').show();
                $('.goods_list').hide();
            }
        })


        $('.dropload-down').remove();
        var tabLoadEnd = false;
        var page = 2;

    })



    function onSearch(_this) {

        var keywords = $(_this).parents().find('input').val();
        if (keywords){
            $('.clearfix dd').hide();
            $('.b2b_assortment').hide();
            $('.b2b_ass_con').hide();
            $('.goods_list').show();
        }else{
            $('.clearfix dd').show();
            $('.b2b_assortment').show();
            $('.b2b_ass_con').show();
            $('.goods_list').hide();
        }
        var data = {};
        data.store_id = '4518117';
        data.keywords = keywords;
        data.limit = 1;
        data.page = 1;

    }
</script>



</body>
</html>

