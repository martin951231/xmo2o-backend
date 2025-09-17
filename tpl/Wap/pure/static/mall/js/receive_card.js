var check_login_url="/wap.php?g=Wap&c=Systemcoupon&a=ajax_check_login";
//领取平台券
var receive_url="/wap.php?g=Wap&c=Systemcoupon&a=had_pull";
//领取商家券
var merchant_receive_url="/wap.php?g=Wap&c=My_card&a=had_pull";

$(function(){	
	$('.receive_card').click(function(){
		var obj=$(this);
		var is_system=obj.data('system');
		is_system==is_system || 1;
		if(is_system==1){	//平台券
			ajax_check_login(obj);	
		}else{		//商家券
			receive_merchant_card(obj);
		}
	});
});
//平台券判断
function ajax_check_login(obj){
	var coupon_id=obj.data('coupon_id');
	if(!coupon_id || isNaN(coupon_id)){
		layer.open({
			content:'网络出错，请刷新后重试',
			btn:['我知道了'],
			yes:function(index){
				layer.close(index);
			}
		});
		return false;
	}
	var coupon_status=obj.data('status');
	var isnew=obj.data('allow_new');
	$.ajax({
		url:check_login_url,
		type:'post',
		dataType:'json',
		data:{isnew:isnew},
		success:function(res){
			if(res.error_code>0){
				layer.open({
					content:res.msg,
					btn:['我知道了'],
					yes:function(index){
						layer.close(index);
						if(res.error_code==1 || res.error_code==6){
							if(!is_wexin_browser){
								window.location.href="./wap.php?g=Wap&c=Login&a=index";
							}else{
								window.location.href="./wap.php?g=Wap&c=My&a=index&from=mall";
							}
						}
					}
				});					
			}else{
				if(coupon_status==1 && obj.data('wxcardid')!='' && is_wexin_browser){
					layer.open({
						title:[
							'是否同步到微信优惠券',
							'background-color:#8DCE16; color:#fff;'
						],
						content: '同步微信优惠券，您可以在微信卡包中查看',
						btn:['是','否'],
						yes:function(index){
							var cardlist=[];
							var i=0;
							var wxcardid=obj.data('wxcardid');
							var sign=obj.data('sign');
							var tickettime=obj.data('tickettime');
							var status=obj.data('status');
							var allow_new=obj.data('allow_new');
							if(wxcardid!='' && status==1 && (is_new && allow_new) || !allow_new){
								cardlist.push({'cardId':wxcardid,'cardExt':'{"code": "", "openid": "", "timestamp":"'+tickettime+'","signature":"'+sign+'"}'});
							}

							if(cardlist){
								wx.addCard({
									cardList:cardlist,
									success:function(res){
										alert('已成功同步到微信卡包');
						  				window.location.reload();
									}
								});
							}
							layer.close(index);
						},
						no:function(index){
							ajax_had_pull(obj);
						}
					});
				}else{
					ajax_had_pull(obj);
				}
			}
		}
	});
}
//领取平台卡券
function ajax_had_pull(obj){
	$.ajax({
		url:receive_url,
		type:'post',
		dataType:'json',
		data:{coupon_id:obj.data('coupon_id'),'get_all':1},
		success:function(res){
			if(res.error_code){
				if(res.error_code==1 || res.error_code==2){
					layer.open({
						content:res.msg,
						btn:['我知道了'],
						yes:function(index){
							layer.close(index);
							//window.location.reload();	
						}
					});					
				}else if(res.error_code==5){
					window.location.href="./wap.php?g=Wap&c=Systemcoupon&a=link&cate_name="+obj.data('cate_name');
				}else if(res.error_code==3){	//已抢光
					layer.open({
						content:res.msg,
						btn:['我知道了'],
						yes:function(index){
							layer.close(index);
						}
					});
					obj.unbind('click').find('span').text('已领完').addClass('disabled');
				}else{
					layer.open({
						content:res.msg,
						btn:['我知道了'],
						yes:function(index){
							layer.close(index);
						}
					});
				}	
			}else{				
				layer.open({
					content:res.msg,
					btn:['我知道了'],
					yes:function(index){
						if(res.coupon.can_get>0){
							console.log('还可以领取'+res.coupon.can_get);
						}else{
							obj.unbind('click').find('span').text('已领取').css({'background':'#ccc'});
						}
						layer.close(index);	
					}
				});
			}
		}
	});
}
//商家券判断
function receive_merchant_card(obj){
	var wxcardid=obj.data('wxcardid');
	var status=obj.data('status');
	if(wxcardid!='' && status==1 && is_wexin_browser){
		layer.open({
			title:[
				'是否同步到微信优惠券',
				'background-color:#8DCE16; color:#fff;'
			],
			content: '同步微信优惠券，您可以在微信卡包中查看',
			btn:['是','否'],
			yes:function(index){
				var cardlist=[];
				var i=0;
				var wxcardid=obj.data('wxcardid');
				var sign=obj.data('sign');
				var tickettime=obj.data('tickettime');
				var status=obj.data('status');
				var allow_new=obj.data('allow_new');
				if(wxcardid!='' && status==1 && (is_new && allow_new) || !allow_new){
					cardlist.push({'cardId':wxcardid,'cardExt':'{"code": "", "openid": "", "timestamp":"'+tickettime+'","signature":"'+sign+'"}'});
				}
				if(cardlist){
					wx.addCard({
						cardList:cardlist,
						success:function(res){
							alert('已成功同步到微信卡包');
			  				window.location.reload();
						}
					});
				}
				layer.close(index);
			},
			no:function(index){
				ajax_merchant_had_pull(obj);
			}
		});
	}else{
		ajax_merchant_had_pull(obj);
	}
}
function ajax_merchant_had_pull(obj){
	$.ajax({
		url:merchant_receive_url,
		type:'post',
		dataType:'json',
		data:{coupon_id:obj.data('coupon_id'),'get_all':1,'coupon_type':'mer'},
		success:function(res){
			if(res.error_code){
				if(obj.data('can_get_num')<=0){
					layer.open({
						content:'数量不足',
						btn:['我知道了'],
						yes:function(index){
							layer.close(index);
						}
					});
				}else{
					if(res.error_code==1 || res.error_code==2){
						layer.open({
							content:res.msg,
							btn:['我知道了'],
							yes:function(index){
								layer.close(index);
								window.location.reload();
							}
						});
					}else if(res.error_code==5){
						layer.open({
							content:res.msg,
							btn:['我知道了'],
							yes:function(index){
								layer.close(index);
							}
						});
						//window.location.href="./wap.php?g=Wap&c=My_card&a=link&mer_id="+obj.data('mer_id')+"&cate_name="+obj.data('cate_name');
					}else if(res.error_code==6 || res.error_code==7){
						if(!is_wexin_browser){
							window.location.href="./wap.php?g=Wap&c=Login&a=index";
						}else{
							window.location.href="./wap.php?g=Wap&c=My&a=index&from=mall";
						}
					}else if(res.error_code==3){
						layer.open({
							content:res.msg,
							btn:['我知道了'],
							yes:function(index){
								layer.close(index);
							}
						});
						obj.unbind('click').find('span').text('已领完').addClass('disabled');
					}else{
						layer.open({
							content:res.msg,
							btn:['我知道了'],
							yes:function(index){
								layer.close(index);
							}
						});
					}
				}
			}else{				
				layer.open({
					content:res.msg,
					btn:['我知道了'],
					yes:function(index){
						if(res.coupon.can_get>0){
							console.log('还可以领取'+res.coupon.can_get);
						}else{
							obj.unbind('click').find('span').text('已领取').css({'background':'#ccc'});
						}
						layer.close(index);
					}
				});
			}
		}
	});
}