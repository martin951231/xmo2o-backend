<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>积分商城</title>
    <meta name="description" content="" />
    <meta content="" name="keywords">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name='apple-touch-fullscreen' content='yes'>
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <link rel="stylesheet" type="text/css"  href="{pigcms{$static_path}integral_mall/css/community_base.css?v=02">
    <link rel="stylesheet" type="text/css"  href="{pigcms{$static_path}integral_mall/css/base.css?v=03">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}integral_mall/css/swiper.min.css?v=02">
    <script src="{pigcms{$static_path}integral_mall/js/rem.js"></script>
    <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
    <script src="{pigcms{$static_path}integral_mall/js/swiper.min.js"></script>
    <script src="{pigcms{$static_path}integral_mall/js/dist/mescroll.min.js"></script>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}integral_mall/js/dist/mescroll.css?v=02">

 <style>
     .swiper-pagination-bullet {
         width: 0.4rem;
         height: 0.4rem;
     }
     .mescroll{
        height: auto; /*如设置bottom:50px,则需height:auto才能生效*/
     }
     .upwarp-nodata{
         text-align: center;
         display:none !important;
     }
     .downwarp-tip{
         text-align: center;
         display:none !important;
     }
     .jf_banner,.swiper-slide, .swiper-wrapper{
		 width:100%!important;
	 }
	 .jf_dui_goods, .jf_duigood{
		position: relative;
	 }
     #goodsList{
		 padding-bottom:20px;
	 }

	 .mescroll{
		position: fixed;
		top: 0;
		bottom: 0;
		height: 100%; /*如设置bottom:50px,则需height:auto才能生效*/
		overflow-y: scroll;
	}
    #data_list {
        height: 100%;
    }
 </style>
</head>
<body style="background: #fff;">
<div id="mescroll" class="mescroll">
<notempty name="top_advert">
<div class="jf_banner">
    <div class="swiper-container">
        <div class="swiper-wrapper">
            <volist name="top_advert" id="vo">
            <div class="swiper-slide">
                <a href="{pigcms{$vo.url}">
                    <img src="{pigcms{$vo.img_url}"/>
                </a>
            </div>
            </volist>
        </div>
        <div class="swiper-pagination jf_swp_buttons"></div>
    </div>
</div>
</notempty>
<notempty name="slider">
    <ul class="jf_iocn" <if condition="count($slider) elt 4">style="height: 3.5rem;"</if>>
    <volist name="slider" id="so">
        <li>
            <a href="{pigcms{$so.url}">
                <i style=" background: url({pigcms{$so.img_url}) no-repeat center center;background-size: 2.24rem 2.24rem;"></i>
                <p>{pigcms{$so.name}</p>
            </a>
        </li>
    </volist>
</ul>
</notempty>
<notempty name="middle_advert">
<a href="{pigcms{$middle_advert[0]['url']}">
<div class="jf_dui" style="background: url({pigcms{$middle_advert[0]['img_url']}) no-repeat center top; background-size: 15rem 6.16rem;">
</div>
</a>
</notempty>
<div id="goodsList">
    <dl class="jf_dui_goods clearfix ">
        <dt></dt>
        <div id="data_list">

        </div>
        <!--<dd>
            <a class="scroll10241726" href="/store/goodsDetail?id=10241726&amp;store_id=4518117&amp;page=1">
                <img src="http://statics.76sd.com//data/files/goods/20190114/small_5c3bfc120be13.jpg" onerror="this.onerror=null;this.src='http://statics.76sd.com/static/b2bwap/image/jf_default.png';">
                <em class="zi_ca">商城</em>
                <p>J-海尔/Haier 空气净化器杀菌除霾除二手烟除过敏原除净化器KJ180F-A180 白色 (无质量问题不支持退换货)</p>
                <span>
                    <b>兑换券</b>
                    <strong>3680</strong>
                </span>
            </a>
        </dd>-->
    </dl>
</div>
<div class="dropload-down">
    <div class="dropload-refresh">

    </div>
</div>
</div>

<div class="jf_footer_bar">
    <ul class="clearfix">
        <li>
            <a href="">
                <i class="jf_home"></i>
                <p class="zi_b8">首页</p>
            </a>
        </li>
        <li class="pr_wd">
            <a href="{pigcms{:U('storeCate',array('mer_id'=>$_GET['mer_id']))}">
                <i></i>
                <p>分类</p>
            </a>
        </li>
        <li>
            <a href="{pigcms{:U('order_list',array('mer_id'=>$_GET['mer_id']))}">
                <i></i>
                <p>订单</p>
            </a>
        </li>
        <li class="pr_wd">
            <a href="{pigcms{:U('my_center',array('mer_id'=>$_GET['mer_id']))}">
                <i></i>
            </a>
        </li>
    </ul>
</div>
<div class="footer_null"></div>


<!-- begin：回到顶部-->
<a href="javascript:;" id="backup"></a>
<!-- end：回到顶部-->
<script>
    var page_list_url = "{pigcms{:U('ajax_goods_page_list')}";
    var mer_id = "{pigcms{:$_GET['mer_id']}";
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
                auto: false, //是否在初始化时以上拉加载的方式自动加载第一页数据; 默认
                isBounce: true, //此处禁止ios回弹,解析(务必认真阅读,特别是最后一点): http://www.mescroll.com/qa.html#q10
                callback: upCallback, //上拉回调,此处可简写; 相当于 callback: function (page) { upCallback(page); }
                // toTop:{ //配置回到顶部按钮
                //     src : "{pigcms{$static_public}/minirefresh/images/minirefresh-totop.png", //默认滚动到1000px显示,可配置offset修改
                //     offset : 1000
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
            //console.log(curPageData);
            var html = '';
            for ( var i = 0; i <curPageData.length; i++){
                var newObj =curPageData[i];
                html += '<dd><a class="scroll10241726" href="'+newObj['detail_url']+'">';
                html += '<img src="'+newObj['wap_pic_first']+'">';
                //html += '<em class="zi_ca">商城</em>';
                html += '<p>'+newObj['goods_name']+'</p>';
                html += '<span><b>积分</b><strong>'+newObj['payment_pure_integral']+'</strong></span></a></dd>';
            }
            if (pageNum==1) {
                $('#data_list').html(html);
            } else {
                $('#data_list').append(html);
            }
//            var listDom=document.getElementById("newsList");
//            for (var i = 0; i < curPageData.length; i++) {
//                var newObj=curPageData[i];
//
//                var str='<p>'+newObj.title+'</p>';
//                str+='<p class="new-content">'+newObj.content+'</p>';
//                var liDom=document.createElement("li");
//                liDom.innerHTML=str;
//
//                if (isAppend) {
//                    listDom.appendChild(liDom);//加在列表的后面,上拉加载
//                } else{
//                    listDom.insertBefore(liDom, listDom.firstChild);//加在列表的前面,下拉刷新
//                }
//            }
        }

        /*联网加载列表数据
         在您的实际项目中,请参考官方写法: http://www.mescroll.com/api.html#tagUpCallback
         请忽略getListDataFromNet的逻辑,这里仅仅是在本地模拟分页数据,本地演示用
         实际项目以您服务器接口返回的数据为准,无需本地处理分页.
         * */
        //var downIndex=0;
        function getListDataFromNet(pageNum,pageSize,successCallback,errorCallback) {
            $.ajax({
                type: "POST",
                url: page_list_url,
                data: {"page":pageNum,"mer_id":mer_id},
                dataType: "json",
                success: function(data){
                    console.log('data_253', data);
                    console.log('successCallback', successCallback);
                    successCallback&&successCallback(data);
                },
                error:function(){
                    console.log('errorCallback');
                    errorCallback&&errorCallback();
                }
            });
            //延时一秒,模拟联网
//            setTimeout(function () {
//                try{
//                    var newArr=[];
//                    if(pageNum==0){
//                        //此处模拟下拉刷新返回的数据
//                        downIndex++;
//                        var newObj={title:"【新增新闻"+downIndex+"】 新增新闻的标题", content:"新增新闻的内容"};
//                        newArr.push(newObj);
//                    }else{
//                        //此处模拟上拉加载返回的数据
//                        for (var i = 0; i < pageSize; i++) {
//                            var upIndex=(pageNum-1)*pageSize+i+1;
//                            var newObj={title:"【新闻"+upIndex+"】 标题标题标题标题标题标题", content:"内容内容内容内容内容内容内容内容内容内容"};
//                            newArr.push(newObj);
//                        }
//                    }
//                    //联网成功的回调
//                    successCallback&&successCallback(newArr);
//                }catch(e){
//                    //联网失败的回调
//                    errorCallback&&errorCallback();
//                }
//            },1000)
        }

    });
</script>
<script>
     var View={
          init:function(){
              this.swipers();
              this.initFresh()
          },
         swipers:function(){
             var swiper = new Swiper('.swiper-container', {
                 pagination: '.swiper-pagination',
                 nextButton: '.swiper-button-next',
                 prevButton: '.swiper-button-prev',
                 paginationClickable: true,
                 spaceBetween: 0,
                 centeredSlides: true, 
                 autoplayDisableOnInteraction: false,
				 autoplay: 2500,
                 loop:true
             });
         },
         initFresh:function(){
             // 回到顶部
             $(window).scroll(function(){
                 var $scrollTop = document.documentElement.scrollTop || window.pageYOffset || document.body.scrollTop;
                 if($scrollTop >500){
                     $("#backup").show();
                 }else{
                     $("#backup").hide();
                 }
             })
             $("#backup").click(function(){
                 $('body,html').animate({scrollTop:0},200);
                 return false;
             })
         }
     }
     window.View=View;
     View.init()

</script>
</body>
</html>

