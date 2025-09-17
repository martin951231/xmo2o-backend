<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>收入记录</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/weui.min.css?t={pigcms{:time()}"/>
		<style type="text/css">
			body{
				padding-top: 100px;
			}

			.header-box{
				color: #fff;
				padding: 20px 15px;
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100px;
				box-sizing: border-box;
				z-index: 9;
			}

			.time-box{
				position: fixed;
				top: 100px;
				bottom: 0;
				left: 0;
				right: 0;
				background-color:#fff;
				transform: translateY(-100%);
				transition: all .5s;
			}
			.time-box.active{
				transform: translateY(0);
			}
			.time-box .month, .time-box .date{
				margin: 0 15px;
			}

			.time-box .date{
				display: none
			}

			.time-box .date>span{
				width: 40px;
				text-align: center;
				line-height: 40px;
			}

			.time-box .date>div{
				width: calc( 50% - 20px);
			}

			.time-box .month .showDatePicker,.time-box .date .showDatePicker{
				border-bottom: 1px solid #efefef;
				line-height: 40px;
				height: 40px;
				text-align: center;
			}

			.time-box .month .showDatePicker.active, .time-box .date .showDatePicker.active{
				border-bottom: 1px solid #fe4e27;
				color: #fe4e27;
			}

			.time-box .top-box{
				padding: 20px 15px;
			}
			.time-box .btn{
				display: inline-block;
				border: 1px solid #e5e5e5;
				background:#f5f5f5;
				line-height: 25px;
				height: 25px;
				border-radius: 15px;
				font-size: 12px;
				padding: 0 10px;
				color: #333;
			}

			.time-box .btn .icon{
				color: #999;
				font-weight: bold;
				font-size: 14px;
				margin-left: 5px;
			}

			.list .item img{
				width: 40px;
				height: 40px;
				border-radius: 50%;
				float: left;
				padding:15px;
			}

			.list .item .info{
				padding: 15px 0;
				border-bottom: 1px solid #efefef;
				margin-left: 70px;
				margin-right: 15px;
				box-sizing: border-box;
			}

			.list .item:last-child .info{
				border: none
			}

			.showDatePicker .text{
				color: #999;
			}

		</style>
	</head>
	<body ontouchstart>
		<div class="ks-clear bg-cover header-box" style="background-image: url({pigcms{$static_path}villagegroup/images/textBg.jpg);">
			<div class="fl">
				<div>
					<span class="beginDate">2018-9</span><span class="hidden endDate">&nbsp;至&nbsp;2018-11-29</span>
				</div>
				<div>收入：{pigcms{$config['Currency_symbol']}<span class="font_size22 font_b" id="money">{pigcms{$money|floatval}</span> </div>
			</div>
			<div class="fr pd10 calendar"><i class="icon iconfont font_size22">&#xe613;</i></div>
		</div>

		<div class="ks-clear time-box" >
			<div class="top-box">
				<div class="btn select">按<span>月</span>选择<i class="icon iconfont">&#xe614;</i></div>
				<div class="fr btn complete">完成</div>
			</div>
			
			<div class="month"><div id='month' class="w100 showDatePicker"  name="begin"><span class="text">选择月份</span></div></div>
			<div class="date">
				<div class="fl"><div id='begin' class="w100 showDatePicker"  name="begin"><span class="text">开始日期</span></div></div>
				<span class="fl color_9">至</span>
				<div class="fl"><div id='end' class="w100 showDatePicker" name="end" ><span class="text">结束日期</span></div></div>
			</div>
		</div>
		
		<div class="list">
			
		</div>

		


		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
    	<script src="{pigcms{$static_path}villagegroup/js/weui.min.js"></script>
    	<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript">
			var page = 1;

			var now = new Date();
			var selectDate = { 
				begin: '',
				end: '',
				month:now.getFullYear() + '-' + (now.getMonth()*1+1)
			}
			var selectType = true; // true表示按月选择，false表示按日选择


			$(function(){
				FastClick.attach(document.body);

				// 标记选择的时间
				list()
				$(window).scroll(function(){
					if($(window).scrollTop() == $(document).height() - $(window).height()){
						$('#mais').remove();
						var jia	=	'';
						jia	+=	'<div id="jia" class="text-center m-t m-b">正在加载</div>';
						$('.list').append(jia);
						if($('#is_null').length < 1){
							
							list();
						}else{
							$('#jia').remove();
						}
					}else if($(window).scrollTop()==0){
						// window.location.reload();
					}
				});
				

				var today = new Date()
				$('.header-box .beginDate').text(today.getFullYear() + '-' + (today.getMonth() + 1 ) )

				// WEUI的datepicker日期选择组件

				$('.showDatePicker').on('click', function () {
					var id = $(this).attr('id')
					var today = new Date()
					var defaultValue = [today.getFullYear(), today.getMonth() + 1, today.getDate()]
					if ($(this).html().indexOf('span') == -1) {
						$('.time-box .showDatePicker').removeClass('active')
						$(this).addClass('active')
						defaultValue = $(this).html().split('-')
					}
					
			        weui.datePicker({
			            start: '2010-1-1',
			            end: today.getFullYear(),
			            defaultValue: defaultValue, // 默认显示当天日期
			            onChange: function (result) {
			            	// 按月选择，隐藏日期列
			                if (id == 'month') {
			                	$('.weui-picker .weui-picker__bd .weui-picker__group').eq(2).hide();
								
			                }
			            },
			            onConfirm: function (result) {
			                if(id == 'month') {
			                	monthArr = [result[0].value, result[1].value]
			                	$('#' + id).html(result[0] + '-' + result[1])
			                	$('#' + id).addClass('active')
			                	selectDate.end = ''
			                } else {
			                	$('#' + id).html(result[0] + '-' + result[1] + '-' + result[2])
			                	selectDate.month = ''
			                	selectDate[id] = result[0] + '-' + result[1] + '-' + result[2]
			                	
			                	$('.time-box .date .showDatePicker').removeClass('active')
								$('#' + id).addClass('active')
			                	console.log(selectDate);
			                }
			           
			            },
			            id: id,
			        });

			    });

				// 点击日历图标
			  	$(document).on('click', '.calendar', function () {
			  		
			  		if($('.time-box').attr('class').indexOf('active') == -1) {
			  			$('.time-box').addClass('active')
			  		} else {
			  			$('.time-box').removeClass('active')
			  		}
			  	})

			  	// 点击完成按钮
			  	$(document).on('click', '.complete', function () {
			  		page = 1
			  		$('.time-box').removeClass('active')
			  		if (selectType && $('#month').html().indexOf('span') == -1 ) {
			  			selectDate.month = $('#month').html()
			  			selectDate.begin = ''
			  			selectDate.end = ''

			  			$('.header-box .beginDate').text(selectDate.month)
			  			$('.header-box .endDate').hide()
			  			console.log('333333333', selectDate)
			  			list(true)
			  		} else if( $('#begin').html() && $('#end').html().indexOf('span') == -1) {
			  			selectDate.begin = $('#begin').html()
			  			selectDate.end = $('#end').html()
			  			selectDate.month = ''
			  			$('.header-box .beginDate').text(selectDate.begin)
			  			$('.header-box .endDate').text(' 至 ' + selectDate.end).show()
			  			console.log('333333333', selectDate)
			  			list(true)
			  		}
			  		
					
			  	})

			  	// 点击按月选择切换按钮
			  	$(document).on('click', '.time-box .select', function () {
			  		selectType = !selectType
			  		if (selectType) {
			  			$(this).find('span').html('月')
			  			$('.time-box .date').hide()
			  			$('.time-box .month').show()
			  		} else {
			  			$(this).find('span').html('日')
			  			$('.time-box .date').show()
			  			$('.time-box .month').hide()
			  		}
			  		
			  	})
			})
			
			
			function list(new_page){

				$.ajax({
					type : "post",
					url : "{pigcms{:U('transaction_json')}",
					dataType : "json",
					data:{
						page:page,
						month: selectDate.month,
						begin: selectDate.begin,
						end: selectDate.end,
					},
					async:false,
					success : function(result){
						var rideList	=	'';
						if(result){
							var	ride_list	=	result.money_list;
							var	money	=	result.money;
					
							
							$('#money').html(parseFloat(money).toFixed(2))
							if(ride_list){
								var	ride_list_length	=	ride_list.length;
								page++;
								for(var x=0;x<ride_list_length;x++){
									
									
									rideList+='<div class="item">';
									rideList+='	<a href="{pigcms{:U('rewardDetail')}&reward_id='+ride_list[x].pigcms_id+'">';

                                    if (ride_list[x]['user_info'] && ride_list[x]['user_info']['avatar']) {
                                        rideList+='		<img src="'+ride_list[x]['user_info']['avatar']+'">';
                                    } else {
                                        rideList+='		<img src="{pigcms{$static_path}images/new_my/pic-default.png">';
                                    }

									rideList+='		<div class="info">';
									rideList+='			<div class="title">';
									rideList+='				<span>'+ride_list[x].desc+'</span>';
									rideList+='				<span class="fr font_size20">+'+ride_list[x].money+'</span>';
									rideList+='			</div>';
									rideList+='			<div class="font_size12 color_9">'+ride_list[x].time_s+'</div>';
									rideList+='		</div>';
									rideList+='	</a>';
									rideList+='</div>';
									
									
							
								}
								if(ride_list_length <= 9){
									rideList	+=	'<div id="is_null" style="text-align:center;padding:10px 0;">没有记录了</div>';
								}else{
									rideList	+=	'<div id="mais" style="text-align:center;padding:10px 0;">上拉会有更多记录哦</div>';
								}
							}else{
								rideList	+=	'<div id="is_null" style="text-align:center;padding:10px 0;">没有记录了</div>';
							}
						}else{
							rideList	+=	'<div id="is_null" style="text-align:center;padding:10px 0;">没有记录了</div>';
						}
						$('#jia').remove();
						if(new_page){
							$('.list').html('')
						}
						$('.list').append(rideList);
					},
					error:function(){
						alert('页面错误，请联系管理员');
					}
				})
			}

			


		</script>
	</body>
</html>