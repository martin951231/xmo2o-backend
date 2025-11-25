
var refresher = {
	info:{
		"pullDownLable":"",
		"pullingDownLable":"",
		"pullUpLable":"",
		"pullingUpLable":"",
		"loadingLable":"",
		"loadingAll":"已加载全部",
		'isNomore':false,
		 'isEmpty':false
	},
	init:function(parameter){
		var wrapper=document.getElementById(parameter.id);
		var div=document.createElement("div");
		div.id="scrollers";
		wrapper.appendChild(div);

		var scroller=document.getElementById("scrollers");
		var list=document.querySelector("#"+parameter.id+" #ui_list")
		scroller.insertBefore(list,scroller.childNodes[0]);

		var pullDown=document.createElement("div");
		pullDown.id="pullDown";
		var loader=document.createElement("div");
		loader.className="loader";
		for(var i=0;i<3;i++){
			var span=document.createElement("span");
			loader.appendChild(span);
		}
		pullDown.appendChild(loader);

		var pullDownLabel=document.createElement("div");
		pullDownLabel.className="pullDownLabel";
		pullDown.appendChild(pullDownLabel);

		scroller.insertBefore(pullDown,scroller.childNodes[0]);

		var pullUp=document.createElement("div");
		pullUp.id="pullUp";
		var loader=document.createElement("div");
		loader.className="loader";
		var hs='<i class="more-loading"></i><p class="weui-loadmore__tips">正在加载</p>'
		/*for(var i=0;i<3;i++){
			var span=document.createElement("span");
			loader.appendChild(span);
		}*/
		loader.innerHTML=hs;
		pullUp.appendChild(loader);

		var pullUpLabel=document.createElement("div");
		pullUpLabel.className="pullUpLabel";
		var content=document.createTextNode(refresher.info.pullUpLable);
		pullUpLabel.appendChild(content);
		pullUp.appendChild(pullUpLabel);

		scroller.appendChild(pullUp);
		//create dom above
		//create dom ,you can wirte it yourself
		var pullDownEl = document.getElementById('pullDown');
		var pullDownOffset = pullDownEl.offsetHeight;
		var pullUpEl = document.getElementById('pullUp');
		var pullUpOffset =pullUpEl.offsetHeight;
		//parameter
		this.scrollIt(parameter,pullDownEl,pullDownOffset,pullUpEl,pullUpOffset);
	},
	scrollIt:function(parameter,pullDownEl,pullDownOffset,pullUpEl, pullUpOffset){
		myScroll = new iScroll(parameter.id, {
			useTransition: true,
			vScrollbar: false, //hide the iscroll v bar
			topOffset: pullDownOffset,
			onRefresh: function (data) {
				refresher.onRelease(pullDownEl,pullUpEl,data);
			},
			onScrollMove: function () {
				refresher.onScrolling(this,pullDownEl,pullUpEl,pullUpOffset,parameter.pullScrolllTop);//element
			},
			onScrollEnd: function () {
				if($("#toTop").length>0 && $("#toTop").hasClass("disabled")) {
					if($("#refresh").size()==0){
						$("html,body").animate({"scrollTop":0},100);
					}else{
						$('#refresh #scrollers').css({
							"transform": "translate(0px, -40px)",
							"transition-duration": "100ms"
						})
					}
					$("#toTop").removeClass("disabled")
				}else{
					refresher.onPulling(pullDownEl,parameter.pullDownAction,pullUpEl,parameter.pullUpAction,parameter.pullScrolllTop,this,pullUpOffset);
				}

			},
		});
		setTimeout(function(){pullDownEl.querySelector('.pullDownLabel').innerHTML = refresher.info.pullDownLable},300);
		document.addEventListener('touchmove', function (e) { e.preventDefault(); }, false);
	},
	//things loader css on scrolling,you can wirte it yourself
	onScrolling:function(e,pullDownEl,pullUpEl,pullUpOffset,pullScrolllTop){
		console.log(e.y)
			if (pullScrolllTop){
				if(e.y<=-200){
					pullScrolllTop(true);
				}else{
					pullScrolllTop(false);
				}

			}

		if (e.y>-(pullUpOffset)) {
			pullDownEl.className = '';
			pullDownEl.querySelector('.pullDownLabel').innerHTML = refresher.info.pullDownLable;
			e.minScrollY =-pullUpOffset;
		}
		if (e.y > 0) {
			pullDownEl.className = 'flip';
			pullDownEl.querySelector('.loader').style.display="block"
			pullDownEl.querySelector('.pullDownLabel').innerHTML = refresher.info.pullingDownLable;
			e.minScrollY = 0;
		}
			if ( e.scrollerH < e.wrapperH && e.y < (e.minScrollY-pullUpOffset) || e.scrollerH > e.wrapperH && e.y< (e.maxScrollY - pullUpOffset) ) {
				pullUpEl.className = 'flip';
                   if(!refresher.info.isNomore && !refresher.info.isEmpty){
					   pullUpEl.querySelector('.loader').style.display="block"
					//   document.getElementById("pullUp").style.display="block";
					   pullUpEl.querySelector('.pullUpLabel').innerHTML =refresher.info.pullingUpLable;
				   }
			}
			if (e.scrollerH < e.wrapperH && e.y > (e.minScrollY-pullUpOffset) && pullUpEl.className.match('flip') || e.scrollerH > e.wrapperH && e.y > (e.maxScrollY - pullUpOffset) && pullUpEl.className.match('flip')) {
				document.getElementById("pullUp").style.display="none";
				pullUpEl.className = '';
				pullUpEl.querySelector('.pullUpLabel').innerHTML =  refresher.info.pullUpLable;

			}


	},
	//things loader css on release,you can wirte it yourself
	onRelease:function (pullDownEl,pullUpEl,is_all){
		if($("#toTop").length>0 && $("#toTop").hasClass("disabled")) {
			if($("#refresh").size()==0){
				$("html,body").animate({"scrollTop":0},100);
			}else{
				$('#refresh #scrollers').css({
					"transform": "translate(0px, -40px)",
					"transition-duration": "100ms"
				})
			}
			$("#toTop").removeClass("disabled")
		}
		if (pullDownEl.className.match('loading')) {
			pullDownEl.className = '';
			//pullDownEl.querySelector('.pullDownLabel').innerHTML = refresher.info.pullDownLable;
			pullDownEl.querySelector('.loader').style.display="none"
			//pullDownEl.style.lineHeight=pullDownEl.offsetHeight+"px";
		}
		if (pullUpEl.className.match('loading')) {
			pullUpEl.className = '';
				pullUpEl.querySelector('.pullUpLabel').innerHTML =(refresher.info.isEmpty && !refresher.info.isNomore)?'':refresher.info.isNomore? refresher.info.loadingAll:refresher.info.pullUpLable;
				pullUpEl.querySelector('.loader').style.display="none"
				//pullUpEl.style.lineHeight=pullDownEl.offsetHeight+"px";

		}else{
			pullUpEl.querySelector('.pullUpLabel').innerHTML =(refresher.info.isEmpty && !refresher.info.isNomore)?'':refresher.info.isNomore? refresher.info.loadingAll:refresher.info.pullUpLable;
			pullUpEl.querySelector('.loader').style.display="none"
			//pullUpEl.style.lineHeight=pullDownEl.offsetHeight+"px";
		}
	},
	//things loader css on pulling,you can wirte it yourself
	onPulling:function (pullDownEl,pullDownAction,pullUpEl,pullUpAction,pullScrolllTop,e,pullUpOffset){
		console.log(e.y)
		if(e.scrollerH > e.wrapperH && e.y==e.maxScrollY ){
			if(!refresher.info.isNomore && !refresher.info.isEmpty){
				pullUpEl.className = 'flip'
				pullUpEl.querySelector('.loader').style.display="block"
			}
		}
		if (pullScrolllTop){
			if(e.y<=-200){
				pullScrolllTop(true);
			}else{
				pullScrolllTop(false);
			}

		}
		if (pullDownEl.className.match('flip')) {
			pullDownEl.className = 'loading';
		//	pullDownEl.querySelector('.pullDownLabel').innerHTML =refresher.info.loadingLable;
			pullDownEl.querySelector('.loader').style.display="block"
			pullDownEl.style.lineHeight="45px";
			if (pullDownAction)
				pullDownAction();	// Execute custom function (ajax call?)
		}
		if (pullUpEl.className.match('flip')) {
			pullUpEl.className = 'loading';
              if(!refresher.info.isNomore &&  !refresher.info.isEmpty){
				  //pullUpEl.querySelector('.pullUpLabel').innerHTML = refresher.info.loadingLable;
				  pullUpEl.querySelector('.loader').style.display="block"
				  pullUpEl.style.lineHeight="45px";
				  if (pullDownAction ){
					  setTimeout(function(){
						  pullUpAction();
					  },200)

				  }

			  }

		}
	},
	isNomore:function(list,pageNo,total_pages,page_size,no_more){
		if(no_more!=undefined){
			if(pageNo==1){
				refresher.info.isNomore=false
			}else{
				refresher.info.isNomore=no_more
			}

		}else{
			var b =total_pages>0 && ((pageNo >= total_pages) || (list.length < page_size)) ;
			if(b){
				refresher.info.isNomore=true
			}else{
				refresher.info.isNomore=false
			}
		}

		console.log(refresher.info.isNomore)
	},
	isEmpty: function(list,pageNo){
		var b = pageNo == 1 && !list.length;
		if(b){
			refresher.info.isEmpty=true;
			//$('.pullUpLabel').html('');
		}else{
			refresher.info.isEmpty=false
		}
		console.log(refresher.info.isEmpty)

	},
	showDetail:function(){

	}
}
