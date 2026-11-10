require('../../common/vendor.js');(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/live_and_video/live/liver/countDown"],{"3cf6":function(n,t,e){"use strict";e.r(t);var c=e("e0f6"),u=e.n(c);for(var o in c)["default"].indexOf(o)<0&&function(n){e.d(t,n,(function(){return c[n]}))}(o);t["default"]=u.a},b336:function(n,t,e){},c647:function(n,t,e){"use strict";var c=e("b336"),u=e.n(c);u.a},c7cc:function(n,t,e){"use strict";var c;e.d(t,"b",(function(){return u})),e.d(t,"c",(function(){return o})),e.d(t,"a",(function(){return c}));var u=function(){var n=this,t=n.$createElement;n._self._c},o=[]},e0f6:function(n,t,e){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var c={data:function(){return{time:3}},onLoad:function(){},props:["show_countDown"],created:function(){this.countDown()},methods:{countDown:function(){var n=this,t=setInterval((function(){n.time<=0?(n.$emit("setShow_countDown",!1),clearInterval(t)):n.time--}),1e3)}}};t.default=c},e7a6:function(n,t,e){"use strict";e.r(t);var c=e("c7cc"),u=e("3cf6");for(var o in u)["default"].indexOf(o)<0&&function(n){e.d(t,n,(function(){return u[n]}))}(o);e("c647");var r,i=e("f0c5"),a=Object(i["a"])(u["default"],c["b"],c["c"],!1,null,null,null,!1,c["a"],r);t["default"]=a.exports}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/live_and_video/live/liver/countDown-create-component',
    {
        'pages/live_and_video/live/liver/countDown-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("e7a6"))
        })
    },
    [['pages/live_and_video/live/liver/countDown-create-component']]
]);
