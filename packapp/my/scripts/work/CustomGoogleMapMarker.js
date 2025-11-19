var markerArr = {}; 
function CustomMarker(label, map, latlng, args, info) {  
	if(!markerArr[label]){
		markerArr[label] = {
			'div': document.createElement('div'),
			'overlay': new google.maps.OverlayView(),
			'args': args,
			'info': info,
			'map': map,
			'latlng': latlng
		};   
		markerArr[label].onRemove = function(){ 
			if (div){
				div.parentNode.removeChild(div);
				div = null;
			}
           
		}; 
        
		markerArr[label].getPosition = function(){
			return markerArr[label].latlng;	
		}; 

		markerArr[label].overlay.setMap(map); 
		var label_marker = markerArr[label];
		var div = label_marker.div;   
		
		google.maps.event.addDomListener(div, "click", function(event) { 
			google.maps.event.trigger(label_marker, "click");
		}); 
 

		label_marker.overlay.draw=function(){   
			div.className = 'marker'; 
			div.style.position = 'absolute';
			div.style.cursor = 'pointer'; 
			// 店铺
			if(label_marker.args == 1){
				div.className = 'marker1'; 
				div.innerHTML='<div class="storeInfo"><img src="'+label_marker.info+'"><div class="sanjiao"></div></div><div  class="user_yuan user_yuan1" style="margin-left:20px;"><img src="images/yuan.png"></div>'  
				
			}else if(label_marker.args == 2){ //用户 
				$('.marker1').hide();
				div.innerHTML='<div class="avatar"><div class="sanjiao"></div><img src="'+label_marker.info+'"></div><div  class="user_yuan"><img src="images/yuan.png"></div>'
				div.style.zIndex='99999'; 
					
			}else if(label_marker.args == 3){ //配送员   
                div.className = 'deliverClass';  
				div.innerHTML='<div class="Distribution flex"><div class="DistributionTxt"><h3>'+label_marker.info.noticeTxt+'</h3><p><L_>距</L_>'+label_marker.info.txtInfo+'：<label style="color:#FFD36F">'+label_marker.info.distance+'</label></p></div></div><div class="driver_img"><img src="images/driver.png" style="width:50px;"></div>'
				
			}
            var panes = markerArr[label].overlay.getPanes();
            if(label_marker.args == 3){
             $('.deliverClass').remove() 
            }
            
            if(label_marker.args == 1){
              	$('.marker1').remove();
            } 
		        panes.overlayImage.appendChild(div); 
			var point = this.getProjection().fromLatLngToDivPixel(label_marker.latlng.latlng);   
			if (point) {
				div.style.left = (point.x - 10) + 'px';
				div.style.top = (point.y - 20) + 'px';
			}	
		} 
		  
	}else{
		var label_marker = markerArr[label];
		var div = label_marker.div;
		if(latlng){
			markerArr[label].latlng = latlng;
		} 
		// var point = label_marker.getProjection().fromLatLngToDivPixel(label_marker.latlng);
		// // var point = markerArr[label].getProjection().fromLatLngToDivPixel(markerArr[label].latlng);   
		// if (point) {
		// 	div.style.left = (point.x - 10) + 'px';
		// 	div.style.top = (point.y - 20) + 'px';
		// }	

	}  
	
}