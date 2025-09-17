<if condition="$list">
	<volist name="list" id="vo">
		<a href="javascript:void(0);" class="weui-media-box weui-media-box_appmsg cmt_p comment"  data-pubid="{pigcms{$vo.reply_id}" data-authorid="{pigcms{$vo.uid}" data-author="{pigcms{$vo.nickname}">
			<div class="weui-media-box__hd" class="left">
				<img class="weui-media-box__thumb" src="{pigcms{$vo.avatar|replace_file_domain|default='./static/images/user_avatar.jpg'}"/>
				<div class="info">
					<if condition="$vo['reply_uid']">
						<div class="title">
							<span>{pigcms{$vo.nickname}</span>
							<span>回复</span> 
							<span>{pigcms{$vo.reply_nickname} :</span>
						</div>
					<else/>
						<div class="title"><span>{pigcms{$vo.nickname}: </span></div>
					</if>
					<div class="f13 c9 add_time">{pigcms{:tmspan($vo['add_time'])} </div>
				</div>
			</div>
			<div class="weui-media-box__bd box__desc" class="right">
				<p class="weui-media-box__desc">{pigcms{$vo.content}</p>
			</div>
		</a>
	</volist>
<else/>
	<div class="weui-cell">
		<div class="weui-cell__bd">
			<p class="c9 f14">暂无评论</p>
		</div>
	</div>
</if>