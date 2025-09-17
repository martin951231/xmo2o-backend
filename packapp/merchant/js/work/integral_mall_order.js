$(function(e){
var client = common.checkAndroidApp()  ?  2 : (common.checkIosApp() ? 1 : 0);
var store_id=$.getUrlParam('store_id');
//拆分来源
var be_date='';
var enddate='';
var mask = mui.createMask();
var searchtype='goods_name';//订单号
var searchstatus='0';//订单状态
var page=1;
var keyword='';//输入框搜索值

var order_fromList=[];//订单来源
var statusList=[];//订单状态
var pay_typeList=[];//支付方式
var is_open_pick='';

var is_change='';
var page2=1;
//蒙层点击关闭
mui('body').on('tap','.mui-backdrop',function(e){
	mask.close();
	$('.stock').hide();
});
mui('body').on('tap','.stock .del',function(e){
	mask.close();
	$('.stock').hide();
});

// $('body').on('click','.store ul li',function(e){
//     var goods_id=$(this).attr('data-id');
//     var goods_source_type=$(this).attr('data-goods_source_type');
//     if (goods_source_type==3) {
//         openWindow({
//             url:'market_virtual_push.html?goods_id='+goods_id,
//             id:'market_virtual_push'
//         });
// 	}
//
// });


addMages(1);

 $('body').off('keypress','#find_value').on('keypress','#find_value',function(event){
     keyword=$(this).val();
 	 if(event.keyCode ==13) {
 	 	document.getElementById('masgs').innerHTML='';
 	 	page=1;
 	 	addMages(page,keyword,searchtype,searchstatus,be_date,enddate)
 	 }

 });

function xiala(a,b,c){
	order_fromList=[];
	$.each(a,function(i,val){
		var iten={'value':i,'text':val};
		order_fromList.push(iten);
	});
	statusList=[];
	$.each(b,function(i,val){
		var iten={'value':i,'text':val};
		if(i=="-1"){
			statusList.unshift(iten);
		}else{
			statusList.push(iten);
		}
		
	});
	pay_typeList=[];
	$.each(c,function(i,val){
		var iten={'value':i,'text':val};
		pay_typeList.push(iten);
	});
}


var height1=$(window)[0].innerHeight;
function addMages(page1,keyword,searchtype,searchstatus,be_date,enddate){
		common.http('Merchantapp&a=integral_mall_order',{'client':client,'page':page1,'keyword':keyword,'searchtype':searchtype,'searchstatus':searchstatus},function(data){
				console.log(data);
				if(data.orders.length==0){
					$('.pullup').html(L_('没有更多数据啦'));
					$('.loading').hide();
					$('.pullup').show();
					//$('#masgs').html('');
				}else{
					data.orders.length<20&&$('.pullup').html(L_('没有更多数据啦'));
					$('.loading').hide();
					$('.pullup').show();
					laytpl(document.getElementById('listTpl').innerHTML).render(data.orders, function(html){
						//console.log(html);
						$('#masgs').append(html);
						$('.loading').hide();
						$('.pullup').show();
						setTimeout(function () {
                            reload_msg();
                        },800);
					});	
					if(data.orders.length>19){
						var flag = false;
						// 上拉加载下拉刷新数据
						$(window).scroll(function(e) {
				            e.stopPropagation();
				              if(flag){
							      //数据加载中
							      return false;
							    }
							   
				            //上拉加载
				            if ($(document).scrollTop() == $(document).height() - height1) {
				            	$('.pullup').hide();
				            	$('.loading').show();
				            	flag = true;
				                page++;
                                addMages(page,keyword,searchtype,searchstatus,be_date,enddate);
				                $('.pullup').show();
				            	$('.loading').hide();
				            }
				        });
					}
				}
			});
		
}

var handler=	function () {
        event.preventDefault();
        event.stopPropagation();
    };

    function OpenMask ()
    {
        document.body.addEventListener('touchmove',handler,false);
        document.body.addEventListener('wheel',handler,false);
    };
    
 	function CloseMask(){
        document.body.removeEventListener('touchmove',handler,false);
        document.body.removeEventListener('wheel',handler,false);
    };

$("#find_value").focus(function(){
 	 OpenMask();
 	$("#find_value").blur(function(){
	 	CloseMask();
	});
});


function reload_msg() {
    mui('body').on('tap','.integral_edit',function () {
    	console.log('integral_edit');
        var goods_source_type = $(this).data('goods_source_type');
        var goods_id = $(this).data('goods_id');
        console.log('goods_id',goods_id);
        console.log('goods_source_type',goods_source_type);
        if (goods_source_type==3) {
            openWindow({
                url:'market_virtual_push.html?goods_id='+goods_id,
                id:'market_virtual_push'
            });
        } else {
            openWindow({
                url:'market_real_push.html?goods_id='+goods_id,
                id:'market_real_push'
            });
		}
    });
    mui('body').on('tap','.integral_del',function () {
        var goods_id = $(this).data('goods_id');
        layer.open({
            title:'提示'
            ,content: '是否确定删除该商品，删除后不可恢复！'
            ,btn: ['确定', '取消']
            ,yes: function(index){
                common.http('merchantapp&a=integral_mall_del',{'client':client,'goods_id':goods_id},function(data){
					window.location.reload();
				});
                layer.close(index);
            }
        });
    });
    mui('body').on('tap','.virtual_wholesale_mer',function(e){
        var name = $(this).data('merchant_name');
        layer.open({
            content: '请联系商家【' + name + '】进行发货！'
            ,btn: ['确定']
            ,yes: function(index){
                layer.close(index);
            }
        });
    });
    mui('body').on('tap','.integral_order_detail',function(e){
        var order_id = $(this).data('order_id');
        openWindow({
            url:'integral_mall_order_detail.html?order_id='+order_id,
            id:'integral_mall_order_detail'
        });
    });
    mui('body').on('tap','.integral_push',function(e){
        var order_id = $(this).data('order_id');
        openWindow({
            url:'integral_mall_order_detail.html?order_id='+order_id+'&type=push',
            id:'integral_mall_order_detail'
        });
    });
    mui('body').on('tap','.integral_self_mention',function(e){
        var order_id = $(this).data('order_id');
        openWindow({
            url:'integral_mall_order_detail.html?order_id='+order_id+'&type=self',
            id:'integral_mall_order_detail'
        });
    });
}





//下拉框筛选
(function($, doc) {
	$.init();



	mui('body').on('tap','#selects',function(e){//订单号
		document.activeElement.blur();
		var _getParam = function(obj, param) {
			return obj[param] || '';
		};
		//普通示例
		var userPicker = new $.PopPicker();

		userPicker.setData(
			[{
				'text':L_('商品名称'),
				'value':'goods_name'
			},
			{
				'text':L_('商品编号'),
				'value':'goods_id'
			}]
		);
		userPicker.show(function(items) {
			document.getElementById('selectsText').innerHTML = items[0].text;
			searchtype=items[0].value;
			document.getElementById('masgs').innerHTML='';
			page=1;
			console.log('items', items);
			addMages(page,keyword,searchtype,searchstatus,be_date,enddate);
		});
		replace_html_lang();
	});
	 mui('body').on('tap','#status1',function(e){//订单状态
		document.activeElement.blur();
		var _getParam = function(obj, param) {
			return obj[param] || '';
		};
		//普通示例
		var userPicker = new $.PopPicker();

		 userPicker.setData(
			 [{
				 'text':L_('全部'),
				 'value':'0'
			 },
			 {
				 'text':L_('进行状态'),
				 'value':'1'
			 },
			 {
				 'text':L_('关闭状态（或已结束）'),
				 'value':'2'
			 }]
		 );
		// userPicker.pickers[0].setSelectedValue(prinks_index);
		userPicker.show(function(items) {
			document.getElementById('statusText').innerHTML = items[0].text;
			searchstatus=items[0].value;
			document.getElementById('masgs').innerHTML='';
			page=1;
			addMages(page,keyword,searchtype,searchstatus,be_date,enddate);
		});
		replace_html_lang();
	});
    mui('body').on('tap','#add_goods',function(e){//添加积分商城商品
        openWindow({
            url:'market_integral_add.html',
            id:'market_integral_add'
        });
    });
})(mui, document);



(function($, doc){
	$.init();

	// $('.begin_date')[0].addEventListener('tap',function(){
	// 	document.activeElement.blur();
	// 	var timeValue=document.getElementById('stime').value;
	// 	// var optionsJson = '{"type":"time","value":"2012-01-01 '+timeValue+'"}';
	// 	var optionsJson = '{"type":"date","beginYear":2014,"beginMonth":5,"beginDay":1,"endYear":2025}';
	// 	var options = JSON.parse(optionsJson);
	// 	var id = this.getAttribute('id');
	// 	var picker = new $.DtPicker(options);
	// 	picker.setSelectedValue(timeValue);
	// 	picker.show(function(rs) {
	// 		$('#stime')[0].value = rs.text;
	// 		be_date=rs.text;
	// 		picker.dispose();
	// 		enddate=document.getElementById('etime').value;
	// 		document.getElementById('masgs').innerHTML='';
	// 		page=1;
	// 		if(enddate!=''){
	// 			addMages(page,keyword,searchtype,searchstatus,be_date,enddate);
	// 		}else{
	// 			mui.toast(L_('请选择结束日期'));
	// 		}
	//
	// 	});
	// });
	// $('.end_date')[0].addEventListener('tap',function(){
	// 	document.activeElement.blur();
	// 	var timeValue=document.getElementById('etime').value;
	// 	var optionsJson = '{"type":"date","beginYear":2014,"endYear":2025}';
	// 	var options = JSON.parse(optionsJson);
	// 	var id = this.getAttribute('id');
	// 	var picker = new $.DtPicker(options);
	// 	picker.setSelectedValue(timeValue);
	// 	picker.show(function(rs) {
	// 		$('#etime')[0].value = rs.text;
	// 		picker.dispose();
	// 		be_date=document.getElementById('stime').value;
	// 		enddate=rs.text;
	// 		document.getElementById('masgs').innerHTML='';
	// 		page=1;
	// 		if(be_date!=''){
	// 			addMages(page,keyword,searchtype,searchstatus,be_date,enddate);
	// 		}else{
	// 			mui.toast(L_('请选择开始日期'));
	// 		}
	//
	// 	});
	// });
})(mui, document);
});