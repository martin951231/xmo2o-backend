require('../common/vendor.js');(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/my/components/longTextOver"],{"6b20":function(t,n,e){"use strict";var u;e.d(n,"b",(function(){return a})),e.d(n,"c",(function(){return i})),e.d(n,"a",(function(){return u}));var a=function(){var t=this,n=t.$createElement;t._self._c;t._isMounted||(t.e0=function(n){t.status=!t.status})},i=[]},"80f6":function(t,n,e){"use strict";var u=e("ede1"),a=e.n(u);a.a},c4a5:function(t,n,e){"use strict";e.r(n);var u=e("6b20"),a=e("d26a");for(var i in a)["default"].indexOf(i)<0&&function(t){e.d(n,t,(function(){return a[t]}))}(i);e("80f6");var c,r=e("f0c5"),o=Object(r["a"])(a["default"],u["b"],u["c"],!1,null,null,null,!1,u["a"],c);n["default"]=o.exports},d26a:function(t,n,e){"use strict";e.r(n);var u=e("d6a0"),a=e.n(u);for(var i in u)["default"].indexOf(i)<0&&function(t){e.d(n,t,(function(){return u[t]}))}(i);n["default"]=a.a},d6a0:function(t,n,e){"use strict";(function(t){Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var e={data:function(){return{status:!0,active:!1,message:""}},props:{longTextContent:{type:String,default:function(){return""}},lineHeight:{type:Number,default:72}},mounted:function(){var n=this;n.$nextTick((function(){n.status=!0,n.active=!1;var e=t.createSelectorQuery().in(n);e.select("#spanBox").boundingClientRect((function(e){var u=t.upx2px(n.lineHeight);e.height>u&&(n.active=!0)})).exec()}))}};n.default=e}).call(this,e("543d")["default"])},ede1:function(t,n,e){}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/my/components/longTextOver-create-component',
    {
        'pages/my/components/longTextOver-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("c4a5"))
        })
    },
    [['pages/my/components/longTextOver-create-component']]
]);
