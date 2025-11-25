<!DOCTYPE html>
<html lang="en">
<head>
    <title>信息发布</title>
    <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js"></script>
<include file="Public:classify_header" />
<style>
.round { border-radius: 4px;}
.visualSelect {
 width: 100%;
 padding: .45rem .5rem .25rem .75rem;
 margin-bottom: .875rem;
 border: 1px solid #ddd;
 border-radius: 4px;
 color: #bbb;
}

.title_info {
    text-align: center;
    font-weight: bolder;
    font-size: 18px;
    padding: 20px 0 0;
}

.title_msg {
    text-align: center;
    font-size: 18px;
    padding: 20px 0;
}


.title_tip {
    text-align: center;
    font-size: 16px;
    padding: 10px 0;
}

.layermcont {
    padding: 0;
}

.layermchild {
    width: 66%;
}

.tip_info_list {
    border-top: 1px solid #f0f0f0;
    padding: 10px 20px;
}

.money_info {
    float: right;
}
</style>

<form id="mpostForm" name="mpostForm" method="post"> 
	<if condition="$fabuset['isupimg'] eq 1">
		<div class="picNote">
			还可上传<em class="upload_count">8</em>张图片，已上传<em class="upload_num">0</em>张(非必填)
		</div>
		<div class="imgGroup content-padded uploadNum" id="uploadNum">
			<ul class="row upload_list" id="upload_list">
				<li class="col-25 JSimgUpbtn">
					<div class="rect filePut pr">
						<!--input class="pa" id="coverPhotoPut" type="file" accept="image/*"-->
						<input type="file" accept="image/jpg,image/jpeg,image/png,image/gif" id="fileImage" name="">
						<label class="uploadCoverBtn" for="coverPhotoPut"></label>
					</div>
				</li>
			</ul>
		</div>
		
		
		<div class="picNote1">
			清晰、实拍的照片更有利完成交易哟~
		</div>
	</if>
	<div class=" formRow">
		<div class="list-block">
			<ul>
				<li>
					<div class="item-content">
						<div class="item-inner">
							<div class="item-title label">标        题</div>
							<div class="item-input">
								<input type="text" name="tname" placeholder="请输入标题">
							</div>
						</div>
					</div>
				</li>
				<li>
					<div class="item-content">
						<div class="item-inner">
							<div class="item-title label">联  系  人</div>
							<div class="item-input">
								<input type="text" name="lxname" placeholder="请输入联系人">
							</div>
						</div>
					</div>
				</li>
				<li>
					<div class="item-content">
						<div class="item-inner">
							<div class="item-title label">电        话</div>
							<div class="item-input">
								<input type="text" name="lxtel" placeholder="只许填写固定电话和手机号">
							</div>
						</div>
					</div>
				</li>
			</ul>
		</div>
	</div>


	<div class=" formRow">

	<if condition="!empty($catfield)">
		<div class="list-block" id="list_block">
			<ul>
			<volist name="catfield" id="vv" key="kk">
				<if condition="$vv['type'] eq 1" >
					<li class="type1">
						<div class="item-content">
							<div class="item-inner">
								<div class="item-title label"><php>if($vv['iswrite']>0)echo '<strong style="color:red;">*</strong>';</php>{pigcms{$vv['name']}</div>
								<div class="item-input">
									<input name="input[{pigcms{$kk}][vv]"  value="" type="text" <php>if($vv['inarr']==1)echo 'onkeyup="value=clearNoNum(this.value)" placeholder="请填数字"';</php> <php>if(($vv['inarr']==1) && !empty($vv['inunit'])){echo 'class="inputtext01"';}else{echo 'class="inputtext02"';}</php>/> <php>if(($vv['inarr']==1) && !empty($vv['inunit']))echo "&nbsp;".$vv['inunit'];</php>
									 <input name="input[{pigcms{$kk}][tn]"  value="{pigcms{$vv['name']}"  type="hidden" />
									 <input name="input[{pigcms{$kk}][unit]"  value="{pigcms{$vv['inunit']}"  type="hidden" />
									 <input name="input[{pigcms{$kk}][inarr]"  value="{pigcms{$vv['inarr']}"  type="hidden" />
									 <input name="input[{pigcms{$kk}][input]"  value="{pigcms{$vv['input']}"  type="hidden" />
									 <input name="input[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
									 <input name="input[{pigcms{$kk}][isfilter]"  value="{pigcms{$vv['isfilter']}"  type="hidden" />
									 <input name="input[{pigcms{$kk}][type]"  value="1"  type="hidden" />
								</div>
							</div>
						</div>
					</li>
				<elseif condition="$vv['type'] eq 2" />
					<li class="align-top type2">
						<div class="item-content">
							<div class="item-inner">
								<div class="item-title label">
									<php>if($vv['iswrite']>0)echo '<strong style="color:red;">*</strong>';</php>{pigcms{$vv['name']}
								</div>
								<div class="item-input radioStyle">
									<div class="radioStyle-wrap">
										<volist name="vv['opt']" id="opt">
											<label><input type="radio" name="input[{pigcms{$kk}][vv]" value="{pigcms{$opt}">{pigcms{$opt}</label>
										</volist>
										<input name="input[{pigcms{$kk}][tn]"  value="{pigcms{$vv['name']}"  type="hidden" />
										<input name="input[{pigcms{$kk}][input]"  value="{pigcms{$vv['input']}"  type="hidden" />
										<input name="input[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
										<input name="input[{pigcms{$kk}][isfilter]"  value="{pigcms{$vv['isfilter']}"  type="hidden" />
										<input name="input[{pigcms{$kk}][type]"  value="2"  type="hidden" />
									</div>
								</div>
							</div>
						</div>
					</li>
				<elseif condition="$vv['type'] eq 3" />
					<li class="align-top type3">
						<div class="item-content">
							<div class="item-inner">
								<div class="item-title label fuliTitle">
									{pigcms{$vv['name']}<br />
									<label for="selectAll">
										<input id="selectAll" type="checkbox"/>全选
									</label>
								</div>
								<div class="item-input">
									<div class="row">
										<volist name="vv['opt']" id="opt">
											<i class="col-33">{pigcms{$opt}</i>
											<input name="input[{pigcms{$kk}][vv][]" type="checkbox"  value="{pigcms{$opt}"/>
										</volist>
									</div>
									<input name="input[{pigcms{$kk}][tn]"  value="{pigcms{$vv['name']}"  type="hidden" />
									<input name="input[{pigcms{$kk}][input]"  value="{pigcms{$vv['input']}"  type="hidden" />
									<input name="input[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
									<input name="input[{pigcms{$kk}][isfilter]"  value="{pigcms{$vv['isfilter']}"  type="hidden" />
									<input name="input[{pigcms{$kk}][type]"  value="3"  type="hidden" />
								</div>
							</div>
						</div>
					</li>
				 <elseif condition="$vv['type'] eq 4" />
					<li class="align-top type4" style="height:4rem;">
						<div class="item-content">
							<div class="item-inner" style="postition:fixed">
								<div class="item-title label">{pigcms{$vv['name']}</div>
								<div class="item-input">
									<select name="input[{pigcms{$kk}][vv]" style="height:2.2rem; width:100%; display:block;" autofocus="true">
										<option value="" >请选择</option>
										<volist name="vv['opt']" id="opt">
										 <option value="{pigcms{$opt}">{pigcms{$opt}</option>
										</volist>
									</select>
									<input name="input[{pigcms{$kk}][tn]"  value="{pigcms{$vv['name']}"  type="hidden" />
									<input name="input[{pigcms{$kk}][input]"  value="{pigcms{$vv['input']}"  type="hidden" />
									<input name="input[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
									<input name="input[{pigcms{$kk}][isfilter]"  value="{pigcms{$vv['isfilter']}"  type="hidden" />
									<input name="input[{pigcms{$kk}][type]"  value="4"  type="hidden" />
								</div>
							</div>
						</div>
					</li>
				  <elseif condition="$vv['type'] eq 5" />
					<li class="align-top type5">
						<div class="item-content">
							<div class="item-inner">
								<div class="item-title label">{pigcms{$vv['name']}<br /><php>if($vv['iswrite']>0)echo '（选填）';</php></div>
								<div class="item-input">
									<textarea name="input[{pigcms{$kk}][vv]"></textarea>
									<input name="input[{pigcms{$kk}][tn]"  value="{pigcms{$vv['name']}"  type="hidden" />
									<input name="input[{pigcms{$kk}][input]"  value="{pigcms{$vv['input']}"  type="hidden" />
									<input name="input[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
									<input name="input[{pigcms{$kk}][isfilter]"  value="{pigcms{$vv['isfilter']}"  type="hidden" />
									<input name="input[{pigcms{$kk}][type]"  value="5"  type="hidden" />
								</div>
							</div>
						</div>
					</li>
					<elseif condition="$vv['type'] eq 6" />
					<li class="align-top type6">
						<div class="item-content">
							<div class="item-inner">
								<div class="item-title label"><php>if($vv['iswrite']>0)echo '<strong style="color:red;">*</strong>';</php>{pigcms{$vv['name']}<br /></div>
								<div class="item-input">
										<dd class="dd-padding kv-line" id="color-gray">
											<i class="icon-location" data-node="icon"></i><span class="color-gray" data-node="addAddress"><?php if(isset($now_adress['adress'])): ?><?php echo $now_adress['adress']; ?><?php else : ?><img src="{pigcms{$static_path}images/location.png" style=" width:25px; height:25px"/>点击选择位置<?php endif; ?></span> <i class="right_arrow"></i>
										</dd>
										<input name="input[{pigcms{$kk}][vv]" value="{pigcms{$now_adress['adress']}"  type="hidden" />
										<input name="input[{pigcms{$kk}][tn]"  value="{pigcms{$vv['name']}"  type="hidden" />
										<input name="input[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
										<input name="input[{pigcms{$kk}][input]"  value="{pigcms{$vv['input']}"  type="hidden" />
										<input name="input[{pigcms{$kk}][isfilter]"  value="{pigcms{$vv['isfilter']}"  type="hidden" />
										<input name="input[{pigcms{$kk}][url]"  value="{pigcms{$vv['url']}"  type="hidden" />
										<input name="input[{pigcms{$kk}][type]"  value="6"  type="hidden" />

								</div>
							</div>
						</div>
					</li>
				  </if>
			</volist>
			
				
			</ul>
		</div>
	</if>
	
	<if condition="$fabuset['is_transaction_config'] eq 0">
	<div class="list-block">
		<ul>
		<li class="align-top">
						<div class="item-content">
							<div class="item-inner">
								<div class="item-title label">
									担保支付
								</div>
								<div class="item-input radioStyle">
									<div class="radioStyle-wrap">
										<label><input type="radio" name="is_assure" value="1">是</label>
										<label><input type="radio" name="is_assure" value="0" checked="checked">否</label>
										<input type="hidden" name="is_assure_d" value="0"/>
									</div>
								</div>
							</div>
						</div>
					</li>
					
					<li style="display:none">
						<div class="item-content">
							<div class="item-inner">
								<div class="item-title label">担保金额</div>
								<div class="item-input">
									<input name="assure_money" value="" type="text" onkeyup="value=value.replace(/[^1234567890]+/g,'')" placeholder="请填数字" class="inputtext01"> 
								</div>
							</div>
						</div>
					</li>
		</ul>
	</div>
	</if>
	</div>

	<div class=" formRow">
		<div class="list-block">
			<ul>
				<li class="align-top">
					<div class="item-content">
						<div class="item-inner">
							<div class="item-title label">说明描述<br />（选填）</div>
							<div class="item-input">
								<textarea id="Content" name="description" class="myborder"  placeholder="写上一些想要说明的内容" style="width: 90%;"></textarea>
							</div>
						</div>
					</div>
				</li>
			</ul>
		</div>
	</div>
	
	<div class="release">
		<input type="hidden" id="Pic" name="" /> 
		<input type="hidden" name="cid" value="{pigcms{$cid}" />
        <input type="hidden" name="fcid" value="{pigcms{$fcid}" />
        <input type="hidden" name="pfcid" value="{pigcms{$_GET.pfcid}" />

        <if condition="($fabuTmp['reward_type'] eq 2 || $fabuTmp['reward_type'] eq 4) && $fabuTmp['reward_publish'] gt 0">
            <button onclick='article_reward_pay("{pigcms{$_GET.cid}", "{pigcms{$_GET.fcid}")'
                    class="btn btn-larger btn-block" style="background-color: #06c1ae;font-size: 16px;">
                确认发布（{pigcms{$config['Currency_symbol']}{pigcms{$fabuTmp.reward_publish}）
            </button>
            <else/>
            <button type="submit">确认发布</button>
        </if>
	</div>
	</form>
<include file="Public:classify_footer" />
	<script src="{pigcms{:C('JQUERY_FILE')}"></script>
<script src="{pigcms{$static_path}js/jquery.cookie.js"></script>
	<script>
    var load = false;
    var click = false;
     var set_info=$.cookie('data_info')
  $(function(){
  	var uer_adress=$.cookie('user_address');
  	var cookie = uer_adress==undefined?'':JSON.parse($.cookie('user_address'))
	  var $list_block=$("#list_block ul>li")
	  if($list_block.length>0){
	       if(set_info!=null&& set_info!='null'){
               if($("#color-gray").length>0){
                   var _cookie=JSON.parse($.cookie('user_address'))
				   console.log(_cookie)
                   $("#color-gray span").html(_cookie.adress)
               }
               set_info=JSON.parse(set_info)
	           var $list_input=set_info.inputs;
               if(set_info.img && set_info.img.length>0){
                   for(var n=0;n<set_info.img.length;n++){
                       var html='<li class="col-25 JSimg upload_item" id="imgShow'+'(n+1)" style="height: 86px;"><div class="rect pr"><img src="'+set_info.img[n].image+'" url="'+set_info.img[n].image+'"><i class="pa fa  fa-close imgDelete upload_delete"></i></div><input type="hidden" name="inputimg[]" value="'+set_info.img[n].value+'"></li>'
				         $("#upload_list").append(html)
				   }
			   }
               $('input[name="tname"]').val(set_info.tname);
               $('input[name="lxname"]').val(set_info.lxname);
               $('input[name="lxtel"]').val(set_info.lxtel);
               $('#Content').val(set_info.description);
               $('input[name="assure_money"]').val(set_info.assure_money);
               $('input[name="is_assure"]').eq(parseInt(set_info.is_assure)).attr('checked','checked');
               if(set_info.is_assure==1){
                   $('input[name="assure_money"]').parents('li').show()
			   }

               for(var i=0;i<$list_block.length;i++){
                   if($list_block.eq(i).hasClass("type1")){
                       $list_block.eq(i).find('input[type!=hidden]').val($list_input[i])
                   }else if($list_block.eq(i).hasClass("type2")){

                   }else if($list_block.eq(i).hasClass("type3")){
                       $list_block.eq(i).find('#selectAll').prop('checked',$list_input[i].selectAll)
					   if(!$list_input[i].selectAll){
                           if($list_block.eq(i).find('.item-input input[type=checkbox]').length>0){
                               for(var n=0;n<$list_input[i].checked_one.length;n++){
                                   if($list_input[i].checked_one[n]){
                                       $list_block.eq(i).find('.item-input i').eq(n).addClass("on")
                                       $list_block.eq(i).find('.item-input input').eq(n).prop("checked",true)
								   }
                               }

                           }
					   }else{
                           $list_block.eq(i).find('.item-input i').addClass("on")
                           $list_block.eq(i).find('.item-input input').prop("checked",true)
					   }
                   }else if($list_block.eq(i).hasClass("type4")){
                       if($list_input[i]!=''){
                            for(var d=0;d<$list_block.eq(i).find("select option").length;d++){
                                  if($list_block.eq(i).find("select option").eq(d).val()==$list_input[i]){
                                      $list_block.eq(i).find("select option").attr("selected",true)
								  }
							}
					   }
                   }else if($list_block.eq(i).hasClass("type5")){
                       $list_block.eq(i).find('textarea').val($list_input[i])
                   }else{

                   }
               }
               $.cookie('data_info', null);
           }

	  }
     $(".imgGroup ul li .JSimg").find(".imgDelete").click(function(){
       $(this).parent().parent().remove();
     });
     $(".upload_delete").live('click',function(){
     	var len = $('.upload_item').length;
		$('.upload_num').html(len);
		$('.upload_count').html(8 - len);
     })
      $("#color-gray").click(function(){

          var detail = new Object();
          // detail.name = $('input[name="name"]').val();
          // detail.province = $('input[name="province"]').val();
          // detail.area = $('input[name="area"]').val();
          // detail.city = $('input[name="city"]').val();
          detail.cid = $('input[name="cid"]').val();
          detail.fcid = $('input[name="fcid"]').val();
          detail.pfcid = $('input[name="pfcid"]').val();
          detail.id = $('input[name="adress_id"]').val();
          detail.longitude = cookie.longitude;
          detail.latitude = cookie.latitude;

          $.cookie("user_address", JSON.stringify(detail));
          var data_info=new Object();
          data_info.inputs=[];
          data_info.img=[];
          data_info.tname = $('input[name="tname"]').val();
          data_info.longitude = cookie.longitude;
          data_info.latitude =  cookie.latitude;
          data_info.lxname = $('input[name="lxname"]').val();
          data_info.lxtel = $('input[name="lxtel"]').val();
          data_info.description = $('#Content').val();
          data_info.is_assure = $('input[name="is_assure_d"]').val();
          data_info.assure_money = $('input[name="assure_money"]').val();
          if($("#upload_list li").length>1){
              for(var w=1;w<$("#upload_list li").length;w++){
                  var img={
                      'image':$("#upload_list li").eq(w).find("img").attr('src'),
					  'value':$("#upload_list li").eq(w).find("input").val()
                  }
                  data_info.img.push(img);
			  }

		  }
          for(var i=0;i<$list_block.length;i++){
              if($list_block.eq(i).hasClass("type1")){
                  data_info.inputs.push($list_block.eq(i).find('input[type!=hidden]').val())
			  }else if($list_block.eq(i).hasClass("type2")){
                  data_info.inputs.push($list_block.eq(i).find('input[type=radio]').attr('checked'))
			  }else if($list_block.eq(i).hasClass("type3")){
                  var checked_one=[]
				  if(!$list_block.eq(i).find('#selectAll').prop('checked')){
                      if($list_block.eq(i).find('.item-input input[type=checkbox]').length>0){
                          for(var n=0;n<$list_block.eq(i).find('.item-input input[type=checkbox]').length;n++){
                              checked_one.push($list_block.eq(i).find('.item-input input[type=checkbox]').eq(n).prop('checked'))
                          }

                      }
				  }

                   var checked={
                       'selectAll': $list_block.eq(i).find('#selectAll').prop('checked'),
					   'checked_one':checked_one
				   }
                  data_info.inputs.push(checked)
              }else if($list_block.eq(i).hasClass("type4")){
                  data_info.inputs.push($list_block.eq(i).find('select').val())
              }else if($list_block.eq(i).hasClass("type5")){
                  data_info.inputs.push($list_block.eq(i).find('textarea').val())
              }else{
                  data_info.inputs.push('暂无')
			  }
		  }
          $.cookie("data_info", JSON.stringify(data_info));
          location.href = "{pigcms{:U('Classify/adres_map', $params)}&cid="+detail.cid+"&fcid="+detail.fcid+"&pfcid="+detail.pfcid;
      });
      $('.formRow .item-input .row i').click(function(){
          if($(this).hasClass("on")){
              $(this).removeClass("on");
			  $(this).next('input').attr('checked',false);
          }else{
              $(this).addClass("on");
			  $(this).next('input').attr('checked',true);
          }
      });
      $('#selectAll').on('change',function (e) {
          e.preventDefault();
          if ($('#selectAll').is(":checked")){
              $('.formRow .item-input .row i').addClass("on");
			  $('.formRow .item-input .row input').attr('checked',true);
          } else {
              $('.formRow .item-input .row i').removeClass("on");
			  $('.formRow .item-input .row input').removeAttr('checked');
          }
      });

      /* $("#areaSelect").cityPicker({
          value: ['天津', '河东区']
          //value: ['四川', '内江', '东兴区']
      }); */

	  if ($(".upload_list").length) {
			var imgUpload = new ImgUpload({
				fileInput: "#fileImage",
				container: "#upload_list",
				countNum: "#uploadNum",
				url:location.protocol+"//" + location.hostname + "/wap.php?g=Wap&c=Classify&a=ajaxImgUpload"
			})
		}

		$('#uploadNum').click(function(){
			if($(".upload_list li").length > 8){
				alert('最多只能上传8张图片！')
				return false;
			}
		});


		$(".upload_list li").change(function(){
			var len = $('.upload_item').length;
			$('.upload_num').html(len);
			$('.upload_count').html(8 - len);
		})

		$('input[name="is_assure"]').change(function(){
			var val = $(this).val();
            $('input[name="is_assure_d"]').val(val);
			var obj = $('input[name="assure_money"]').parents('li')
			if(val==1){
				obj.show();
			}else{
				obj.hide();
			}
		});
  });
  
  function clearNoNum(value){
    //清除"数字"和"."以外的字符

    value = value.replace(/[^\d.]/g,"");

    //验证第一个字符是数字而不是
    value = value.replace(/^\./g,"");

    //只保留第一个. 清除多余的
    value = value.replace(/\.{2,}/g,".");
    value = value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");

    //只能输入两个小数
    value = value.replace(/^(\-)*(\d+)\.(\d\d).*$/,'$1$2.$3');
	return value;
}


  $('#mpostForm').submit(function(){
      if (load) {
          load = false;
          return false;
      }
      console.log('提交------load----');
	  var url = "{pigcms{:U('Classify/fabuTosave',array('cid'=>$cid))}";
	  $.post(url,$(this).serialize(),function(data){
	      if (data['status']==0) {
              layer.open({content: '<div  class="title_tip">' + data.msg + '</div>',skin: 'msg',time: 1});
          } else if(data['status']==-1){
			  location.href="{pigcms{:U('Login/index')}";
		  }else if(data['status']==1){
			  var redirect_url = location.protocol+"//" + location.hostname + "/wap.php?g=Wap&c=Classify&a=myfabu";
			  redirect_url +='&uid={pigcms{$uid}';
			  location.href = redirect_url;
		  } else if (data['status'] == 2) {
              layer.open({
                  content: '<div  class="title_info">' + data.msg + '</div><br>' +
                  '<div class="tip_info_list">账户余额（{pigcms{$config['Currency_txt']}）：<div class="money_info">{pigcms{$config['Currency_symbol']}' + data.info.now_money + '</div></div>' +
                  '<div class="tip_info_list" style="color: #06c1ae;">打赏金额（{pigcms{$config['Currency_txt']}）：<div class="money_info">{pigcms{$config['Currency_symbol']}' + data.info.reward_money + '</div></div>' +
                  '<div class="tip_info_list" style="color: #FF658E;">还需充值（{pigcms{$config['Currency_txt']}）：<div class="money_info">{pigcms{$config['Currency_symbol']}' + data.info.difference + '</div></div>'
                  , btn: ['确定', '取消']
                  , yes: function (index) {
                      location.href = "{pigcms{:U('My/recharge',array('label'=>'wap_portal_article_'))}{pigcms{$article['aid']}";
                  }
              });
          }else if (data['status'] == 3) {
              layer.open({
                  content: '<div  class="title_info">' + data.msg + '</div><br>' +
                  '<div class="tip_info_list">账户余额（{pigcms{$config['Currency_txt']}）：<div class="money_info">{pigcms{$config['Currency_symbol']}' + data.info.now_money + '</div></div>' +
                  '<div class="tip_info_list" style="color: #06c1ae;">打赏金额（{pigcms{$config['Currency_txt']}）：<div class="money_info">{pigcms{$config['Currency_symbol']}' + data.info.reward_money + '</div></div>' +
                  '<div class="tip_info_list" style="color: #FF658E;">还需充值（{pigcms{$config['Currency_txt']}）：<div class="money_info">{pigcms{$config['Currency_symbol']}' + data.info.difference + '</div></div>'
                  , btn: ['确定', '取消']
                  , yes: function (index) {
                      location.href = "{pigcms{:U('My/recharge',array('label'=>'wap_portal_article_'))}{pigcms{$article['aid']}";
                  }
              });
          }
      },'json')
	  return false;
  });

  function article_reward_pay(cid, fcid) {
      if (click) return false;
      click = true;
      load = true;
      setTimeout(function () {
          if (click) {
              console.log('change')
              click = false;
              load = false;
          }
      }, 2000);
      console.log('分类id----------', cid);
      console.log('分类父id----------', fcid);
      console.log('shuju---------', $('#mpostForm').serialize());
      var fabu_reward_pay_order = "{pigcms{:U('Classify/fabu_reward_pay_order')}";
      $.post(fabu_reward_pay_order, $('#mpostForm').serialize(), function (data) {
          console.log('支付信息-》  ', data)
          load = false;
          if (data.error == 3) {
              layer.open({
                  content: '<div  class="title_info">' + data.msg + '</div><br>' +
                  '<div class="tip_info_list">账户余额（{pigcms{$config['Currency_txt']}）：<div class="money_info">{pigcms{$config['Currency_symbol']}' + data.info.now_money + '</div></div>' +
                  '<div class="tip_info_list" style="color: #06c1ae;">打赏金额（{pigcms{$config['Currency_txt']}）：<div class="money_info">{pigcms{$config['Currency_symbol']}' + data.info.reward_money + '</div></div>' +
                  '<div class="tip_info_list" style="color: #FF658E;">还需充值（{pigcms{$config['Currency_txt']}）：<div class="money_info">{pigcms{$config['Currency_symbol']}' + data.info.difference + '</div></div>'
                  , btn: ['确定', '取消']
                  , yes: function (index) {
                      location.href = "{pigcms{:U('My/recharge',array('label'=>'wap_portal_article_'))}{pigcms{$article['aid']}";
                  }
              });
          } else if (data.error == 1 || data.error == 2) {
              layer.open({
                  content: '<div  class="title_info">' + data.msg + '</div>'
                  , btn: ['确定']
                  , yes: function (index) {
                      window.location.href = window.location.href;
                  }
              });
          } else if (data.error == 5) {
              layer.open({
                  content: '<div  class="title_info">打赏</div><br>' +
                  '<div class="tip_info_list">账户余额（{pigcms{$config['Currency_txt']}）：<div class="money_info">{pigcms{$config['Currency_symbol']}' + data.info.now_money + '</div></div>' +
                  '<div class="tip_info_list" style="color: #06c1ae;">打赏金额（{pigcms{$config['Currency_txt']}）：<div class="money_info">{pigcms{$config['Currency_symbol']}' + data.info.reward_money + '</div></div>'
                  , btn: ['立即支付', '取消']
                  , yes: function (index) {
                      $('#mpostForm').submit();
                  }
              });
          } else {
              if (data.code == 2) {
                  layer.open({
                      content: '<div  class="title_msg">请先登录</div>'
                      , btn: ['去登录']
                      , yes: function (index) {
                          location.href = "{pigcms{:U('Login/index')}";
                      }
                  });
              } else {
                  layer.open({
                      content: '<div  class="title_msg">' + data.msg + '</div>'
                      , btn: ['确定']
                  });
              }
          }
      }, 'json');
  }
</script>
</body>
</html>