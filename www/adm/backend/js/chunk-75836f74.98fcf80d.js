(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-75836f74"],{"2d3c":function(e,t,r){},"3c97":function(e,t,r){"use strict";r("2d3c")},fad2:function(e,t,r){"use strict";r.r(t);var n=function(){var e=this,t=e.$createElement,r=e._self._c||t;return r("el-form",{staticClass:"form",attrs:{inline:!0,model:e.form,"label-width":"100px"},nativeOn:{submit:function(t){return t.preventDefault(),e.onSubmit.apply(null,arguments)}}},[r("el-breadcrumb",{staticStyle:{"margin-bottom":"30px"},attrs:{separator:"/"}},[r("el-breadcrumb-item",{attrs:{to:"/"}},[e._v("工作台")]),r("el-breadcrumb-item",{attrs:{to:"/notice"}},[e._v("消息列表")]),r("el-breadcrumb-item",[e._v("详情")])],1),r("el-row",{directives:[{name:"loading",rawName:"v-loading",value:e.loading,expression:"loading"}],attrs:{gutter:24}},[r("el-col",[r("el-form-item",{attrs:{label:"名称"}},[r("el-input",{attrs:{placeholder:"请输入名称"},model:{value:e.form.name,callback:function(t){e.$set(e.form,"name",t)},expression:"form.name"}})],1)],1),r("el-col",[r("el-form-item",{attrs:{label:"vip等级"}},[r("el-select",{attrs:{clearable:"",placeholder:"请选择"},model:{value:e.form.vip_id,callback:function(t){e.$set(e.form,"vip_id",t)},expression:"form.vip_id"}},e._l(e.config.vip,(function(e,t){return r("el-option",{key:t,attrs:{label:e.name,value:e.id}})})),1)],1)],1),r("el-col",[r("el-form-item",{staticStyle:{display:"flex"},attrs:{label:"内容"}},[r("vue-editor",{staticStyle:{flex:"1"},attrs:{useCustomImageHandler:""},on:{"image-added":e.handleImageAdded},model:{value:e.form.content,callback:function(t){e.$set(e.form,"content",t)},expression:"form.content"}})],1)],1),r("el-col",[r("el-form-item",[r("el-button",{attrs:{type:"primary","native-type":"submit"}},[e._v("保存")])],1)],1)],1)],1)},a=[],o=(r("8e6e"),r("ac6a"),r("456d"),r("bd86")),i=r("1c1e"),c=r("5c96"),l=r("5873");function s(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function u(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?s(Object(r),!0).forEach((function(t){Object(o["a"])(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):s(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}var d={components:{VueEditor:l["a"]},data:function(){return{loading:!1,form:{title:"",content:"",type:"vip",vip_id:""},config:[]}},computed:{ssid:function(){return localStorage.getItem("ssid")}},mounted:function(){var e=this;this.loading=!0,Object(i["a"])("/user/notice/config").then((function(t){e.config=t.data,e.loading=!1})).catch((function(){e.loading=!1}))},methods:{handleImageAdded:function(e,t,r,n){var a=new FormData;a.append("file",e),Object(i["a"])("/api/api/upload",a).then((function(e){var a=e.data.file;t.insertEmbed(r,"image",a),n()}))},onSubmit:function(){var e=this;this.loading=!0;var t=this.$router.history.current.params.id;Object(i["a"])("/user/notice/"+(t?"update":"create"),u({id:this.$router.history.current.params.id},this.form)).then((function(){e.loading=!1,c["Message"].success("操作成功"),e.$router.go(-1)})).catch((function(){c["Message"].error("操作失败"),e.loading=!1}))}}},m=d,f=(r("3c97"),r("2877")),p=Object(f["a"])(m,n,a,!1,null,"e04b1298",null);t["default"]=p.exports}}]);
//# sourceMappingURL=chunk-75836f74.98fcf80d.js.map