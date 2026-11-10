require('../../common/vendor.js');(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/shop_new/component/contactBox/contactBox"],{"65f2":function(t,n,e){"use strict";e.r(n);var o=e("8d3c"),a=e("7eb5");for(var c in a)["default"].indexOf(c)<0&&function(t){e.d(n,t,(function(){return a[t]}))}(c);e("ba88");var u,r=e("f0c5"),i=Object(r["a"])(a["default"],o["b"],o["c"],!1,null,"9b418994",null,!1,o["a"],u);n["default"]=i.exports},"7ea4":function(t,n,e){"use strict";(function(t){Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var e={props:{phone:{type:String,default:""},contactUrl:{type:String,default:""},domain:{type:String,default:""}},computed:{list:function(){return this.phone?this.phone.split(" "):[]}},methods:{callPhone:function(){var n=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"";t.makePhoneCall({phoneNumber:n})},goUrl:function(t){this.common.parseUrl(t)},closeMask:function(t){this.$emit("closeMask",t)}}};n.default=e}).call(this,e("543d")["default"])},"7eb5":function(t,n,e){"use strict";e.r(n);var o=e("7ea4"),a=e.n(o);for(var c in o)["default"].indexOf(c)<0&&function(t){e.d(n,t,(function(){return o[t]}))}(c);n["default"]=a.a},"8d3c":function(t,n,e){"use strict";var o;e.d(n,"b",(function(){return a})),e.d(n,"c",(function(){return c})),e.d(n,"a",(function(){return o}));var a=function(){var t=this,n=t.$createElement;t._self._c},c=[]},b97e:function(t,n,e){},ba88:function(t,n,e){"use strict";var o=e("b97e"),a=e.n(o);a.a}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/shop_new/component/contactBox/contactBox-create-component',
    {
        'pages/shop_new/component/contactBox/contactBox-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("65f2"))
        })
    },
    [['pages/shop_new/component/contactBox/contactBox-create-component']]
]);
