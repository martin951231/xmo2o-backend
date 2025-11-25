<include file="Public/header"/>
<include file="Public/header_user"/>
        <section class="personal">
            <div class="w1200 clr">
                <div class="score_list">
                    <div class="score_top clr">
                        <div class="img fl">
                            <img src="{pigcms{$scenic_list['user']['avatar']}">
                        </div>
                        <div class="p103">
                            <h2>{pigcms{$scenic_list['guide_name']}</h2>
                            <div id="QuacorGrading" class="clr">
                                <div class="fl input_list">
                                    <input name="1" type="button" />
                                    <input name="2" type="button" />
                                    <input name="3" type="button" />
                                    <input name="4" type="button" />
                                    <input name="5" type="button" />
                                </div>
                                <span id="QuacorGradingValue" class="fl"><b><font id="branch">5</font></b>分</span>
                            </div>
                        </div>
                    </div>
                    <div class="score_end clr">
                        <textarea id="reply" placeholder="导游服务的如何？哪些方面不够好？大家都期待着你的点评"></textarea>
                    </div>
                    <div class="keep clr">
                        <div class="fr">
                            <a href="{pigcms{:U('Scenic_user/aguide_order_list')}"><div class="keep_qx cur fl">取消</div></a>
                            <div class="keep_bc cur fl" id="comment">保存</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
       <script type="text/javascript">
            var GradList = $("#QuacorGrading").find("input");
              for(var di=0;di<parseInt(document.getElementById("QuacorGradingValue").getElementsByTagName("font")[0].innerHTML);di++){
                GradList[di].style.backgroundPosition = 'left center';
              }
              for(var i=0;i < GradList.length;i++){
                GradList[i].onmouseover = function(){
                  for(var Qi=0;Qi<GradList.length;Qi++){
                    GradList[Qi].style.backgroundPosition = 'right center';
                  }
                  for(var Qii=0;Qii<this.name;Qii++){
                    GradList[Qii].style.backgroundPosition = 'left center';
                  }
                  document.getElementById("QuacorGradingValue").innerHTML = '<b><font>'+this.name+'</font></b>分';
                }
              }
	var submit_pj	=	true;
	if(submit_pj == true){
		submit_pj = false;
		$("#comment").click(function(){
			var branch	=	$("#branch").text();
			var reply	=	$("#reply").val();
			if(reply == ''){
				alert('请填写评论');
			}
			$.ajax({
				type : "post",
				url : "{pigcms{:U('comment_json')}",
				dataType : "json",
				data:{
					order_id	:	"{pigcms{$_GET['order_id']}",
					reply_score	:	branch,
					reply_content	:	reply,
					type	:	2,
				},
				async:false,
				success : function(result){
					if(result.errorCode == 0){
						alert('评论成功');
						window.location.href	=	"{pigcms{:U('Scenic_user/aguide_order_list')}";
					}else{
						submit_pj = true;
						alert(result.errorMsg);
					}
				},
				error:function(){
					submit_pj = true;
				}
			})
		})
	}
       </script>
<include file="Public/footer"/>