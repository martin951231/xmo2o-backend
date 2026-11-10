(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/village-modal/index"],{3767:function(n,t,e){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var u={props:{visible:{type:Boolean,default:!1},villageList:{type:Array,default:function(){return[]}}},watch:{visible:{handler:function(n){this.showModal=n},immediate:!0}},data:function(){return{showModal:!1}},methods:{closeModal:function(){this.$emit("confirm")},chooseVillage:function(n){this.$emit("confirm",n)}}};t.default=u},"95a3":function(n,t,e){"use strict";e.r(t);var u=e("3767"),i=e.n(u);for(var o in u)["default"].indexOf(o)<0&&function(n){e.d(t,n,(function(){return u[n]}))}(o);t["default"]=i.a},a6cc:function(n,t,e){},a99f:function(n,t,e){"use strict";var u=e("a6cc"),i=e.n(u);i.a},b6bd:function(n,t,e){"use strict";e.r(t);var u=e("ff83"),i=e("95a3");for(var o in i)["default"].indexOf(o)<0&&function(n){e.d(t,n,(function(){return i[n]}))}(o);e("a99f");var c,a=e("f0c5"),r=Object(a["a"])(i["default"],u["b"],u["c"],!1,null,"38d78544",null,!1,u["a"],c);t["default"]=r.exports},ff83:function(n,t,e){"use strict";e.d(t,"b",(function(){return i})),e.d(t,"c",(function(){return o})),e.d(t,"a",(function(){return u}));var u={uPopup:function(){return e.e("uview-ui/components/u-popup/u-popup").then(e.bind(null,"d598"))},uIcon:function(){return e.e("uview-ui/components/u-icon/u-icon").then(e.bind(null,"dc05"))}},i=function(){var n=this,t=n.$createElement;n._self._c},o=[]}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/village-modal/index-create-component',
    {
        'components/village-modal/index-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("b6bd"))
        })
    },
    [['components/village-modal/index-create-component']]
]);
