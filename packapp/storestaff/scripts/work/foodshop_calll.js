var nowtime = null;
var playMp3Tip = null;
setInterval(function(){
	common.http('Storestaff&a=foodshop_call_staff_count', {noTip: true,time:nowtime}, function (data) {
		nowtime = data.time;
		if(data.count > 0){
			// 多个通知，多个弹窗
			if (data.list) {
				for (var i = 0; i < data.list.length; i++) {
					if ( $('#playMp3Tip').length > 0 ) {
						$('#playMp3Tip').attr('src',data.list[i].voice_mp3);
					}else{
						$('body').append('<audio style="display:none;" id="playMp3Tip" controls="true" loop="loop" src="'+data.list[i].voice_mp3+'"></audio>');
					}
					
					$('#playMp3Tip').trigger('play');
						
					/*音乐播放5分钟*/
					playMp3Tip = setTimeout(function(){
						$('#playMp3Tip').trigger('pause');
					}, 300000);

					layer.open({
						type: 1,
						area: ['300px','150px'],
						title: L_('通知上菜'),
						content: '<div style="text-align:center; font-size: 14px; padding: 20px 10px;">'+data.list[i].msg+'</div>',
						zIndex: layer.zIndex, //重点1
						success: function(layero){
						},
						btn: [L_('去确认'),L_('关闭')],
						end: function(index, layero){
							$('#playMp3Tip').trigger('pause');									
							clearTimeout(playMp3Tip);
							playMp3Tip = null;
						},
						yes:function(){
        					openWebviewUrl("foodshop_order_list.html?status=4");
						}
					});
				}
			}
		}
	}, 'json');

},5000);