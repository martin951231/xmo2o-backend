 

var storeList=[]
var selectTimeTypeArr=[]
var typeArr = []
var storeArr = []
var type,selectTimeType,order_sort,store_id;
var page = 1
var fristLoad = true
var is_extending = false

$(document).ready(function(){  
	$('.ul_proSelect li').each(function(index,element){ 
	  if($(this).hasClass('cur')){   
		  order_sort = $(this).data('second_type') 
		  initData();
	  }
	})
	
	$('.ul_proSelect li').click(function(){
		$(this).addClass('cur').siblings().removeClass('cur')
		order_sort = $(this).data('second_type')
		initData()
	})
})

function initData(){
	var param={
		'type':type, 
		'selectTimeType':selectTimeType,
		'order_sort':order_sort,
		'store_id':store_id,
		'page':page 
	} 
	common.http('Merchantapp&a=statisticsGoods',param,function(data){    
		 for(var i in data.selectTimeTypeArr) {   
		    selectTimeTypeArr.push(
		    	{
		    		value: i,
		    		text: data.selectTimeTypeArr[i]	
		    	}
		    )
		 }  
		 
		 for(var j in data.typeArr) {
		    // console.log(data.typeArr)
		    typeArr.push(
		    	{
		    		value: j,
		    		text: data.typeArr[j]	
		    	}
		    )
		 }
	      
		 data.storeArr.forEach(function(element){
		 	storeArr.push(
		 		{
		 			value: element.store_id,
		 			text: element.name	
		 		}
		 	)
		 })  
		 if(storeArr.length  == 0){
			$('#showPickerStoreBox').remove()
		 }
		 is_extending = data.goods_list.has_more
		 var goods_listHtml=''
		 if(data.goods_list && data.goods_list.goods_date.length>0){ 
			 data.goods_list.goods_date.forEach(function(element,index){  
				  goods_listHtml += '<li><span class="number">'+element.ranking+'</span>'
				 	 goods_listHtml += ' <a href="javascript:;"> '
				 		goods_listHtml += '<h3>'+ element.name
						   if(element.spec){
							  goods_listHtml += '('+ element.spec +')' 
						   } 
						 goods_listHtml += '</h3>'
				 		goods_listHtml += '<div class="li_details">'
							if(order_sort =='sale_money'){
								goods_listHtml += '<span class="span_xxs">销售额：'+element.sale_money+'</span>'
							}else{
								goods_listHtml += '<span class="">销售额：'+element.sale_money+'</span>'
							}
				 			
							if(order_sort =='is_goods'){
								goods_listHtml += '<span class="span_zz span_xxs">赞：'+element.is_goods+'</span>'
							}  
							if(order_sort =='sale_count'){
								goods_listHtml += '<span class="span_xl span_xxs">销量：'+element.sale_count+'</span>'
							}else{
								goods_listHtml += '<span class="span_xl">销量：'+element.sale_count+'</span>'
							}
							
							if(order_sort =='sale_percent'){
								goods_listHtml += '<span class="span_xlb span_xxs">销量占比：'+element.sale_percent+'</span>'
							}else{
								goods_listHtml += '<span class="span_xlb">销量占比：'+element.sale_percent+'</span>'
							} 
				 			goods_listHtml += '<div class="clear"></div>'
				 		goods_listHtml += '</div> '
				 	goods_listHtml += '</a> '
				goods_listHtml += ' </li>'
			 }) 
		 } 
		 if(goods_listHtml){
			 if(page ==1 ){
				$('.ul_businessAnalysisPro').html(goods_listHtml) 
			 }else{
				$('.ul_businessAnalysisPro').append(goods_listHtml) 
			 }
			 if(!is_extending && page > 1){
			 	$('.ul_businessAnalysisPro').append('<p class="noData">别拉了,没有数据了</p>') 
			 } 
		 }else{ 
			$('.ul_businessAnalysisPro').html('<p class="noTip"><img src="images/no_tip.png" /><br>暂无商品</p>')
		 }   
		 if(fristLoad){
		 	initLoad()
		 	selectTimeType = selectTimeTypeArr[0].value
		 	type = typeArr[0].value
		 	store_id = storeArr[0]?storeArr[0].value:''
			fristLoad = false
		 }
		  
	});
	
} 

// 下拉 
$(window).scroll(function () { 
    var scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
    if (scrollTop + window.innerHeight + 20 >= document.documentElement.offsetHeight) {  
        if (is_extending) { 
            is_extending = false;
            page++;
            initData(page);
        }
    }
}); 
 
function initLoad(){
	(function($, doc) {
		$.init();
		$.ready(function() {
			/**
			 * 获取对象属性的值
			 * 主要用于过滤三级联动中，可能出现的最低级的数据不存在的情况，实际开发中需要注意这一点；
			 * @param {Object} obj 对象
			 * @param {String} param 属性名
			 */
			var _getParam = function(obj, param) {
				return obj[param] || '';
			};
			//普通示例
			console.log(selectTimeTypeArr,'888')
			var userPicker = new $.PopPicker();
			userPicker.setData(selectTimeTypeArr);
			var showUserPickerButton = doc.getElementById('showUserPicker');
			var userResult = doc.getElementById('showUserPicker'); 
			userResult.innerText = selectTimeTypeArr[0].text;
			showUserPickerButton.addEventListener('tap', function(event) {
				userPicker.show(function(items) { 
					console.log(items)
					userResult.innerText = items[0].text;
					selectTimeType = items[0].value
					initData()
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);
			//-----------------------------------------
			var showPickerSelect = new $.PopPicker();
			showPickerSelect.setData(typeArr);
			console.log(typeArr,'9999')
			var showUserPickerButton2 = doc.getElementById('showPickerSelect');
			var userResult2 = doc.getElementById('showPickerSelect');
			userResult2.innerText = typeArr[0].text;
			showUserPickerButton2.addEventListener('tap', function(event) {
				showPickerSelect.show(function(items) { 
					console.log(items)
					userResult2.innerText = items[0].text;
					type = items[0].value
					if(items[0].value == 'meal'){
						jQuery('.is_goods').hide()
					}else{
						jQuery('.is_goods').show()
					}
					initData()
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);
			
			// 店铺选择 
			if(storeArr.length == 0){
				return
			}
			var showPickerStore = new $.PopPicker(); 
			showPickerStore.setData(storeArr);
			var showUserPickerButton3 = doc.getElementById('showPickerStore');
			var userResult3 = doc.getElementById('showPickerStore');
			userResult3.innerText = storeArr[0].text;
			showUserPickerButton3.addEventListener('tap', function(event) {
				showPickerStore.show(function(items) { 
					console.log(items)
					userResult3.innerText = items[0].text;
					store_id = items[0].value
					initData()
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);
			
		 
		});
	})(mui, document);
}	
 