(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/newCharge/components/setPlate"],{"3fb51":function(e,n,t){"use strict";t.r(n);var c=t("d4e5"),i=t("6dfac");for(var a in i)["default"].indexOf(a)<0&&function(e){t.d(n,e,(function(){return i[e]}))}(a);t("aee8");var o,r=t("f0c5"),u=Object(r["a"])(i["default"],c["b"],c["c"],!1,null,"4c4d2138",null,!1,c["a"],o);n["default"]=u.exports},"4ec8":function(e,n,t){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var c=function(){t.e("pages/newCharge/components/chooseLicense").then(function(){return resolve(t("fb35"))}.bind(null,t)).catch(t.oe)},i={data:function(){return{brand:""}},components:{chooseLicense:c},props:{brandList:{type:String,default:""}},watch:{brandList:{handler:function(e){var n=this;this.$nextTick((function(){n.$refs.chooseLicense.setPlate(e)}))},immediate:!0}},mounted:function(){},methods:{getBrand:function(e){this.brand=e},closeThis:function(){this.$emit("closeThis")},savePlate:function(){this.$emit("closeThis",this.brand)}}};n.default=i},"6dfac":function(e,n,t){"use strict";t.r(n);var c=t("4ec8"),i=t.n(c);for(var a in c)["default"].indexOf(a)<0&&function(e){t.d(n,e,(function(){return c[e]}))}(a);n["default"]=i.a},aee8:function(e,n,t){"use strict";var c=t("cb87"),i=t.n(c);i.a},cb87:function(e,n,t){},d4e5:function(e,n,t){"use strict";var c;t.d(n,"b",(function(){return i})),t.d(n,"c",(function(){return a})),t.d(n,"a",(function(){return c}));var i=function(){var e=this,n=e.$createElement;e._self._c},a=[]}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/newCharge/components/setPlate-create-component',
    {
        'pages/newCharge/components/setPlate-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("3fb51"))
        })
    },
    [['pages/newCharge/components/setPlate-create-component']]
]);
