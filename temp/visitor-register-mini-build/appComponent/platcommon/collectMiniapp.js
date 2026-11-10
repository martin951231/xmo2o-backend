(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["appComponent/platcommon/collectMiniapp"],{"26b9":function(t,n,e){"use strict";var o;e.d(n,"b",(function(){return a})),e.d(n,"c",(function(){return c})),e.d(n,"a",(function(){return o}));var a=function(){var t=this,n=t.$createElement;t._self._c},c=[]},"2ac8":function(t,n,e){"use strict";(function(t,o){Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var a,c=e("1ce6"),i={},u={name:"collectMiniapp",data:function(){return{isShow:!1,totalH:0,offsetTop:10}},props:["appScene"],watch:{appScene:{immediate:!0,handler:function(t){}}},mounted:function(){a=this,i=t.getSystemInfoSync();c.getNavigationBarStyle(a,i);a.offsetTop=a.totalH+5,console.log("collect_this",a)},methods:{hideHandle:function(){this.isShow=!1,o.setStorageSync("showCollectMiniapp",1)}}};n.default=u}).call(this,e("bc2e")["default"],e("543d")["default"])},"31ce":function(t,n,e){"use strict";e.r(n);var o=e("2ac8"),a=e.n(o);for(var c in o)["default"].indexOf(c)<0&&function(t){e.d(n,t,(function(){return o[t]}))}(c);n["default"]=a.a},"5d1d":function(t,n,e){"use strict";var o=e("9291"),a=e.n(o);a.a},9291:function(t,n,e){},a3055:function(t,n,e){"use strict";e.r(n);var o=e("26b9"),a=e("31ce");for(var c in a)["default"].indexOf(c)<0&&function(t){e.d(n,t,(function(){return a[t]}))}(c);e("5d1d");var i,u=e("f0c5"),l=Object(u["a"])(a["default"],o["b"],o["c"],!1,null,null,null,!1,o["a"],i);n["default"]=l.exports}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'appComponent/platcommon/collectMiniapp-create-component',
    {
        'appComponent/platcommon/collectMiniapp-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("a3055"))
        })
    },
    [['appComponent/platcommon/collectMiniapp-create-component']]
]);
