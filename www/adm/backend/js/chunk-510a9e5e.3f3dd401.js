(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-510a9e5e"],{"08f4":function(t,a,e){"use strict";e("3f7c")},"3f7c":function(t,a,e){},"87a6":function(t,a,e){"use strict";e.r(a);var n=function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("div",[e("el-table",{directives:[{name:"loading",rawName:"v-loading",value:t.loading,expression:"loading"}],staticClass:"list",attrs:{data:t.tableData}},[e("el-table-column",{attrs:{prop:"name",label:"账号",align:"center"}}),e("el-table-column",{attrs:{prop:"ip",label:"ip地址",align:"center"}}),e("el-table-column",{attrs:{prop:"area",label:"登录地址",align:"center"}}),e("el-table-column",{attrs:{prop:"mobile",label:"手机号",align:"center"}}),e("el-table-column",{attrs:{prop:"uptime_date",label:"登录时间",align:"center"}})],1),e("el-pagination",{staticClass:"pagination",attrs:{background:"",layout:"total, prev, pager, next",total:t.count,"current-page":t.page_curren,"hide-on-single-page":!1},on:{"current-change":t.currentChange}})],1)},l=[],c=e("1c1e"),i={data:function(){return{loading:!1,tableData:[],count:0,page_curren:1,data:{config:{status:status}}}},mounted:function(){this.FetchList()},methods:{FetchList:function(){var t=this;this.loading=!0,Object(c["a"])("/record/login/getlist",{page_curren:this.page_curren}).then((function(a){t.loading=!1,t.tableData=a.data.list,t.data=a.data,t.count=a.data.count})).catch((function(){return t.loading=!1}))},currentChange:function(t){this.page_curren=t,this.FetchList()}}},r=i,o=(e("08f4"),e("2877")),u=Object(o["a"])(r,n,l,!1,null,"492cabf2",null);a["default"]=u.exports}}]);
//# sourceMappingURL=chunk-510a9e5e.3f3dd401.js.map