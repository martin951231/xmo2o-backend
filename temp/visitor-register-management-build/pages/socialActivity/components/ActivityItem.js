(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/socialActivity/components/ActivityItem"],{"0d46":function(t,n,e){},"1d5d":function(t,n,e){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var u={name:"ActivityItem",props:{item:{type:Object,required:!0},type:{type:String,default:""}},computed:{statusType:function(){var t={0:"warning",10:"success",20:"error"};return t[this.item.status]||"default"}},methods:{handleClick:function(){this.$emit("click",this.item)}}};n.default=u},"2f2d":function(t,n,e){"use strict";e.r(n);var u=e("f0ac"),i=e("efe6");for(var c in i)["default"].indexOf(c)<0&&function(t){e.d(n,t,(function(){return i[t]}))}(c);e("8a11");var a,r=e("f0c5"),o=Object(r["a"])(i["default"],u["b"],u["c"],!1,null,"c5692aaa",null,!1,u["a"],a);n["default"]=o.exports},"8a11":function(t,n,e){"use strict";var u=e("0d46"),i=e.n(u);i.a},efe6:function(t,n,e){"use strict";e.r(n);var u=e("1d5d"),i=e.n(u);for(var c in u)["default"].indexOf(c)<0&&function(t){e.d(n,t,(function(){return u[t]}))}(c);n["default"]=i.a},f0ac:function(t,n,e){"use strict";e.d(n,"b",(function(){return i})),e.d(n,"c",(function(){return c})),e.d(n,"a",(function(){return u}));var u={uTag:function(){return e.e("uview-ui/components/u-tag/u-tag").then(e.bind(null,"41db"))},uIcon:function(){return e.e("uview-ui/components/u-icon/u-icon").then(e.bind(null,"dc05"))}},i=function(){var t=this,n=t.$createElement;t._self._c},c=[]}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/socialActivity/components/ActivityItem-create-component',
    {
        'pages/socialActivity/components/ActivityItem-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("2f2d"))
        })
    },
    [['pages/socialActivity/components/ActivityItem-create-component']]
]);
