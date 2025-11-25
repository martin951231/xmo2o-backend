<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>{pigcms{$config['village_group_alias']}</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=yes"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/index.css?t={pigcms{:time()}"/> 
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/weui.min.css"/>
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/mescroll.min.css"/>   
		<style>
			body{background:#f9f9f9;}
            .mescroll{
                position: fixed;
                top: 44px;
                bottom: 0;
                height: auto; /*如设置bottom:50px,则需height:auto才能生效*/
            }
            .pro_salesCon{
                margin-top: 10px;
            }
            .details_box .pro_saleList:last-child{
                border-bottom:none;
            }
		</style> 
	</head>
	<body>
		<div class="top_time"> 
		   <span><input type="text"  id="showDatePickerStart" placeholder="开始时间"></span> 
		   <span style="width:10%">至</span>
		   <span ><input type="text"  id="showDatePickerEnd" placeholder="结束时间"></span>
		   <div class="clear"></div>
		</div>  
        
        <div id="mescroll" class="mescroll">   
            <div class='pro_salesCon'> 
            </div>  
	    </div>  
	</body>
	<script src="{pigcms{:C('JQUERY_FILE')}"></script> 
	<script type="text/javascript" src="{pigcms{$static_path}villagegroup/js/weui.min.js" charset="utf-8"></script> 
    <script type="text/javascript" src="{pigcms{$static_path}villagegroup/js/mescroll.min.js" charset="utf-8"></script>  
    <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script> 
	<script type="text/javascript" src="{pigcms{$static_path}villagegroup/js/base.js" charset="utf-8"></script>  
	<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
	<script> 
    // 获取参数
      function GetRequest(){
      	var url = location.search;
      	var theRequest = {}; 
      	if(url.indexOf("?") != -1){
      		var str = url.substr(1); 
      		strs = str.split("&"); 
      		for(var i = 0; i < strs.length; i ++) {
      			console.log(strs[i].split("=")[1]);
      			theRequest[strs[i].split("=")[0]] = decodeURIComponent(strs[i].split("=")[1]); 
      		}
      	}
      	return theRequest; 
      }
      var urlParam = GetRequest();
      var StartTime=''
      var EndTime=''
      $('#showDatePickerStart').val(urlParam.start_time);
      $('#showDatePickerEnd').val(urlParam.end_time);   
	   
    $('#showDatePickerStart').on('click', function () { 
        weui.datePicker({
            start: 1990,
            end: new Date().getFullYear(),
            onChange: function (result) {  
            },
            onConfirm: function (result) {
                console.log(result);
                result[0].value = result[0].value.toString();
                if(result[1].value < 10){
                    result[1].value = '0'+result[1].value.toString();
                }else{
                    result[1].value = result[1].value.toString();
                }
                if(result[2].value < 10){
                    result[2].value = '0'+result[2].value.toString();
                }else{
                    result[2].value = result[2].value.toString();
                }
                $('#showDatePickerStart').val(result[0].value+ '-' + result[1].value + '-' + result[2].value);
                StartTime =parseInt(result[0].value+ result[1].value +result[2].value)
                if($('#showDatePickerStart').val() != '' && $('#showDatePickerEnd').val() != ''){
                    if(StartTime > EndTime){
                        layer.open({
                            content: '开始时间不能大于结束时间'
                            ,skin: 'msg'
                            ,time: 2 //2秒后自动关闭
                        });
                        $('#showDatePickerStart').val('')
                    }
                } 
                $('.pro_salesCon').html('');
                 downCallback();
                
            }
        });
        
    });

    $('#showDatePickerEnd').on('click', function () {
        weui.datePicker({
            start: 1990,
            end: new Date().getFullYear(),
            onChange: function (result) { 
            },
            onConfirm: function (result) {
                console.log(result);
                result[0].value = result[0].value.toString();
                if(result[1].value < 10){
                    result[1].value = '0'+result[1].value.toString();
                }else{
                    result[1].value = result[1].value.toString();
                }
                if(result[2].value < 10){
                    result[2].value = '0'+result[2].value.toString();
                }else{
                    result[2].value = result[2].value.toString();
                }
                $('#showDatePickerEnd').val(result[0].value+ '-' + result[1].value + '-' + result[2].value);
                EndTime =parseInt(result[0].value+ result[1].value +result[2].value); 
                if($('#showDatePickerStart').val() != '' && $('#showDatePickerEnd').val() != ''){
                    if(StartTime > EndTime){
                        layer.open({
                            content: '结束时间不能小于开始时间'
                            ,skin: 'msg'
                            ,time: 2 //2秒后自动关闭
                        });
                        $('#showDatePickerEnd').val('');
                    }
                }
                
                $('.pro_salesCon').html('');
                downCallback(); 
            }, 
        }); 
    });  
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
            toTop:{ //配置回到顶部按钮
                src : "{pigcms{$static_path}/villagegroup/images/goup.png", //默认滚动到1000px显示,可配置offset修改
                //offset : 1000
            }
        }
    });
    
    /*下拉刷新的回调 */
    function downCallback(){
        console.log('kkkk')
        //联网加载数据
        getListDataFromNet(0, 1, function(data){
			$('.pro_salesCon').empty();
            //联网成功的回调,隐藏下拉刷新的状态
            //mescroll.endSuccess();
            mescroll.endErr();
            //设置列表数据
            setListData(data.data, true);
            mescroll.setPageNum(2);
        }, function(){
            //联网失败的回调,隐藏下拉刷新的状态
            mescroll.endErr();
        });
    }
    
    /*上拉加载的回调 page = {num:1, size:10}; num:当前页 从1开始, size:每页数据条数 */
    function upCallback(page){
        //联网加载数据
        getListDataFromNet(page.num, page.size, function(curPageData){
            console.log(curPageData,'kopoo')
            //联网成功的回调,隐藏下拉刷新和上拉加载的状态;
            //mescroll会根据传的参数,自动判断列表如果无任何数据,则提示空;列表无下一页数据,则提示无更多数据;
            console.log("page.num="+page.num+", page.size="+page.size+", curPageData.length="+curPageData.data.list.length);
            //方法一(推荐): 后台接口有返回列表的总页数 totalPage
            //mescroll.endByPage(curPageData.length, totalPage); //必传参数(当前页的数据个数, 总页数)
    
            //方法二(推荐): 后台接口有返回列表的总数据量 totalSize
            //mescroll.endBySize(curPageData.length, totalSize); //必传参数(当前页的数据个数, 总数据量)
    
            //方法三(推荐): 您有其他方式知道是否有下一页 hasNext
            //mescroll.endSuccess(curPageData.length, hasNext); //必传参数(当前页的数据个数, 是否有下一页true/false)
    
            //方法四 (不推荐),会存在一个小问题:比如列表共有20条数据,每页加载10条,共2页.如果只根据当前页的数据个数判断,则需翻到第三页才会知道无更多数据,如果传了hasNext,则翻到第二页即可显示无更多数据.
            //mescroll.endSuccess(curPageData.length);
            mescroll.endBySize(curPageData.data.list.length, curPageData.data.pagetotal * 10);
            //提示:curPageData.length必传的原因:
            // 1.判断是否有下一页的首要依据: 当传的值小于page.size时,则一定会认为无更多数据.
            // 2.比传入的totalPage, totalSize, hasNext具有更高的判断优先级
            // 3.使配置的noMoreSize生效
			mescroll.setPageNum(page.num + 1);
    
            //设置列表数据
            setListData(curPageData.data, true);
        }, function(){
            //联网失败的回调,隐藏下拉刷新和上拉加载的状态;
            console.log('net_default');
            mescroll.endErr();
        });
    }
    
    /*设置列表数据*/
    function setListData(curPageData, isAppend) { 
        console.log(curPageData,'jjj');
        var html = ''; 
        if(curPageData){
            var dataArray=curPageData.list;   
            for(let i=0; i<dataArray.length;i++){
                 html +='<div class="pro_salesCon_block">'
                     html +='<p>'+dataArray[i].date+ '</p>'
                     html +='<div class="statistics_block" style="padding-top:0"> '
                         html +='<div class="title flexBox"> '
                             html +='<div class>商品名称</div>'
                             html +='<div class>销售总数</div>'
                         html +='</div>'
                         html +='<div class="details_box">' 
                         console.log(dataArray[i].goods.length)
                         if(dataArray[i].goods.length != 0){
                            for(let j=0; j<dataArray[i].goods.length; j++){
                             html +='<div class="title flexBox pro_saleList">'
                                 html +='<a href="#">'+dataArray[i].goods[j].name+'</a>  '
                                 html +='<a href="#">'+dataArray[i].goods[j].num+'</a>'
                             html +='</div>'
                           }   
                         }else{
                            html +='<p style="text-align:center; color:#999">没有数据！</p>' 
                         } 
                         html +='</div>'
                     html +='</div> '
                 html +=' </div> ' 
            }
            $('.pro_salesCon').append(html); 
        }    
    }  
     function getListDataFromNet(pageNum,pageSize,successCallback,errorCallback){ 
        StartTime=$('#showDatePickerStart').val();
        EndTime=$('#showDatePickerEnd').val();   
        var html=''
        $.ajax({
            url:'{pigcms{:U("ajax_pro_sales_details")}',
            type:'post',
            data:{start_time:StartTime,end_time:EndTime,page:pageNum},
            dataType:'json', 
            success: function(data){
                console.log(data);
                successCallback&&successCallback(data);
            },
            error:function(){
                console.log('errorCallback');
                errorCallback&&errorCallback();
            }
        })
    }  
	</script>
</html>