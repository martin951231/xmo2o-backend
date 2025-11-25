
var address='start_address';
var user_long,user_lat;

$(function () {
  $('.start_address_location').click(function(){
      addressGeocoder = true
      address='start_address';
      get_address();
  })
  $('.end_address_location').click(function(){
      addressGeocoder = true
      address='end_address';
      get_address();
  })

  var start_address = $('#start_address').val()
  var end_address = $('#end_address').val()
  if (!start_address) {
      addressGeocoder = true
      address='start_address';
      get_address();
  } else if (start_address && !end_address) {
      addressGeocoder = true
      address='end_address';
      get_address();
  }
})

function get_address() {
    getUserLocation({
      useHistory: false,
      okFunction: 'getListGeocoderbefore',
      okFunctionParam: [true],
      errorFunction: 'getListGeocoderError',
      errorFunctionParam: [false]
    })
}


function getListGeocoderbefore (type,info) {
  if (type == true) {
    var arguments = info.split(','); 
    getGeoconv(arguments[0], arguments[1])
  }
}

function getListGeocoderError () {
  var addressTipLayer = layer.open({
    content: '未获取到您的位置，请尝试重新获取或者自行填写！',
    btn: ['确定'],
    end: function () {
      layer.close(addressTipLayer)
    }
  })
}

function getGeoconv (lng, lat) {
  if (typeof (is_google_map) != 'undefined') {
    var obj = { result: [{ x: lng, y: lat }] }
    getListGeoconvBack(obj)
  } else {
    $.getJSON('https://api.map.baidu.com/geoconv/v1/?coords=' + lng + ',' + lat + '&from=1&to=5&ak=4c1bb2055e24296bbaef36574877b4e2&callback=getListGeoconvBack&jsoncallback=?')
  }

}

function getListGeoconvBack (obj) {
    isGetLocationByAddress = false
    user_long = obj.result[0].x
    user_lat = obj.result[0].y
    getListGeocoder()
}



function getListGeocoder () {
  if (typeof (is_google_map) != 'undefined') {
    var map
    var service

    user_long = parseFloat(user_long)
    user_lat = parseFloat(user_lat)
    map = new google.maps.Map(document.getElementById('hidden_map'), {
      center: { lat: user_lat, lng: user_long },
      zoom: 15
    })

    var request = {
      location: { lat: user_lat, lng: user_long },
      radius: '500'
    }

    service = new google.maps.places.PlacesService(map)
    service.nearbySearch(request, callback)

    function callback (results, status) {
      if (status == google.maps.places.PlacesServiceStatus.OK) {
        getListGeocoderBack(results)
      }
    }

  } else {
    $.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&callback=getListGeocoderBack&location=' + user_lat + ',' + user_long + '&output=json&pois=1&jsoncallback=?')
  }

}

function getListGeocoderBack (obj) {
  if (typeof (is_google_map) != 'undefined') {
    if (addressGeocoder == false) {
      if (address=='start_address') {

      }
      if (obj[1].name) {
        $('#'+address).val(obj[1].name)
      } else {
        $('#'+address).val(obj[0].name)
      }
    } else {
      var tmpName = obj.length > 1 ? obj[1].name : obj[0].name
      $('#'+address).val(tmpName)
      addressGeocoder = false
    }

  } else {
    if (addressGeocoder == false) {
      if (obj.result.pois.length > 0) {
        $('#'+address).val(obj.result.pois[0].name)
      } else {
        $('#'+address).val(obj.result.addressComponent.street)
      }
    } else {
      // alert(JSON.stringify(obj));
      var tmpName = obj.result.pois.length > 0 ? obj.result.pois[0].name : obj.result.addressComponent.street
      var area = obj.result.addressComponent.district;
      $('#'+address).val(area +' '+ tmpName)
      addressGeocoder = false
    }
  }
}