(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-52c22955"],{"2e33":function(t,e,n){"use strict";var a=n("2f0c"),r=n.n(a);r.a},"2f0c":function(t,e,n){},"3de9":function(t,e,n){"use strict";n.r(e);var a=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",[n("div",{staticClass:"searchs"},[n("el-form",{staticClass:"form-search",attrs:{inline:!0,model:t.searchs},nativeOn:{submit:function(e){return e.preventDefault(),t.onSubmit(e)}}},[n("el-row",{attrs:{gutter:24}},[n("el-col",[n("el-form-item",{attrs:{label:"账号"}},[n("el-input",{attrs:{placeholder:"姓名/手机号"},model:{value:t.searchs.keyword_search_value,callback:function(e){t.$set(t.searchs,"keyword_search_value",e)},expression:"searchs.keyword_search_value"}})],1),n("el-form-item",{attrs:{label:"状态"}},[n("el-select",{attrs:{clearable:"",placeholder:"请选择"},model:{value:t.searchs.status,callback:function(e){t.$set(t.searchs,"status",e)},expression:"searchs.status"}},t._l(t.config.status,(function(t,e){return n("el-option",{key:e,attrs:{label:t,value:e}})})),1)],1),n("el-form-item",[n("el-button",{attrs:{type:"primary","native-type":"submit"}},[t._v("查询")])],1)],1)],1)],1)],1),n("el-table",{directives:[{name:"loading",rawName:"v-loading",value:t.loading,expression:"loading"}],staticClass:"list",attrs:{data:t.tableData}},[n("el-table-column",{attrs:{prop:"id",label:"编号",align:"center"}}),n("el-table-column",{attrs:{prop:"channel",label:"渠道",align:"center"}}),n("el-table-column",{attrs:{prop:"name",label:"姓名",align:"center"}}),n("el-table-column",{attrs:{prop:"mobile",label:"手机号",align:"center"}}),n("el-table-column",{attrs:{prop:"status_name",label:"状态",align:"center"}}),n("el-table-column",{attrs:{prop:"money",label:"奖励金额",align:"center"}}),n("el-table-column",{attrs:{prop:"uptime_date",label:"添加时间",align:"center"}}),n("el-table-column",{attrs:{prop:"address",align:"center",label:"操作",width:"300"},scopedSlots:t._u([{key:"default",fn:function(e){return[n("el-button",{attrs:{type:"primary",size:"small"},on:{click:function(n){return t.remove(e.row.id)}}},[t._v("删除")]),n("el-button",{attrs:{type:"warning",size:"small"},on:{click:function(n){return t.look(e.row.file)}}},[t._v("查看")]),"D"===e.row.status?n("el-button",{attrs:{type:"success",size:"small"},on:{click:function(n){return t.update(e.row.id,!0)}}},[t._v("通过\n        ")]):t._e(),"D"===e.row.status?n("el-button",{attrs:{type:"danger",size:"small"},on:{click:function(n){return t.update(e.row.id)}}},[t._v("\n          拒绝\n        ")]):t._e()]}}])})],1),n("el-pagination",{staticClass:"pagination",attrs:{background:"",layout:"total, prev, pager, next",total:t.count,"current-page":t.page_curren,"hide-on-single-page":!1},on:{"current-change":t.currentChange}})],1)},r=[],l=(n("8e6e"),n("ac6a"),n("456d"),n("bd86")),s=n("1c1e"),c=n("db51"),o=n("5c96");function i(t,e){var n=Object.keys(t);if(Object.getOwnPropertySymbols){var a=Object.getOwnPropertySymbols(t);e&&(a=a.filter((function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable}))),n.push.apply(n,a)}return n}function u(t){for(var e=1;e<arguments.length;e++){var n=null!=arguments[e]?arguments[e]:{};e%2?i(n,!0).forEach((function(e){Object(l["a"])(t,e,n[e])})):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(n)):i(n).forEach((function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(n,e))}))}return t}var p={data:function(){return{loading:!1,searchs:{keyword_search_value:"",status:""},tableData:[],count:0,page_curren:1,config:{type:null,status:null},id:0}},mounted:function(){this.FetchList()},methods:{remove:function(t){var e=this;this.loading=!0,Object(c["a"])("/user/jzlist/delete/"+t).then((function(){e.loading=!1,e.FetchList()})).catch((function(){return e.loading=!1}))},FetchList:function(){var t=this;this.loading=!0,Object(s["a"])("/user/jzlist/getlist",u({page_curren:this.page_curren},this.searchs)).then((function(e){t.loading=!1,t.count=e.data.count,t.page_curren=Math.floor(e.data.page_curren),t.tableData=e.data.list,t.config=e.data.config})).catch((function(){return t.loading=!1}))},currentChange:function(t){this.page_curren=t,this.FetchList()},onSubmit:function(){this.page_curren=1,this.FetchList()},update:function(t){var e=this,n=arguments.length>1&&void 0!==arguments[1]&&arguments[1];this.loading=!0,Object(s["a"])("/user/jzlist/verify",{id:t,status:n?"Y":"N"}).then((function(){e.loading=!1,e.FetchList(),o["Message"].success("更新成功")})).catch((function(){return e.loading=!1}))},look:function(t){this.$alert('<img style="width: 100%" src="'+t+'">',"分享截图",{dangerouslyUseHTMLString:!0})}}},d=p,g=(n("2e33"),n("2877")),h=Object(g["a"])(d,a,r,!1,null,"a4456c9e",null);e["default"]=h.exports},db51:function(t,e,n){"use strict";var a=n("bc3a"),r=n.n(a),l=n("5c96");e["a"]=function(t){arguments.length>1&&void 0!==arguments[1]&&arguments[1];return t="/v1/api"+t,r.a.delete(t,{headers:{"Access-Control-ALlow-Origin":"*",Authorization:"Bearer "+localStorage.getItem("pstt")}}).then((function(t){return t.data})).then((function(t){return 200===t.code?t:Promise.reject(t.msg)})).catch((function(t){var e=String(t);return l["Message"].error(e),Promise.reject(e)}))}}}]);
//# sourceMappingURL=chunk-52c22955.c2f139ed.js.map