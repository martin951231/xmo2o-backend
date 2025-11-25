$(function(){
	var nowtime = null;
	var playMp3Tip = null;
	setInterval(function(){
		$.post('/store.php?g=Merchant&c=Store&a=foodshop_call_staff_count', {time:nowtime}, function(response){
			var data = response.data;
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
							area: ['400px','180px'],
							title:L_('通知上菜'),
							content: '<div style="text-align:center; font-size: 14px; margin-top: 15px;">'+data.list[i].msg+'<br/>'+L_('点击按钮均可关闭声音。')+'</div>',
							zIndex: layer.zIndex //重点1 
							,success: function(layero){
								layer.setTop(layero); //重点2
							},
							btn: [L_('立即去查看'),L_('关闭')] //按钮
							,
							yes:function(){
								location.href = foodshop_url;
							}, 
							end: function(index, layero){
								$('#playMp3Tip').trigger('pause');									
								clearTimeout(playMp3Tip);
							}
						});
					}
				}
			}
		}, 'json');

	},5000);
});