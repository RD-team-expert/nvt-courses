import{d as b,l as y,o as r,w as i,e,t as o,b as c,u as m,P as f,j as x,n as g,a,F as w,h as v}from"./app-qcpNlNnA.js";import{_}from"./AppLayout.vue_vue_type_script_setup_true_lang-DmfzPR57.js";import"./AppLogoIcon.vue_vue_type_script_setup_true_lang-CzZNeL1v.js";import"./index-7ZZPBW3g.js";const D={class:"container px-4 mx-auto py-4 sm:py-6 max-w-7xl"},C={class:"flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6"},j={class:"text-xl sm:text-2xl font-bold"},k={class:"flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 w-full sm:w-auto"},E={class:"grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6"},L={class:"md:col-span-2 bg-white rounded-lg shadow overflow-hidden"},M={class:"p-4 sm:p-6"},B={class:"flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 gap-2 sm:gap-0"},N=["innerHTML"],P={class:"grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm"},S={class:"font-medium"},$={class:"font-medium"},T={class:"font-medium capitalize"},V={class:"font-medium"},z={class:"bg-white rounded-lg shadow overflow-hidden"},F={class:"p-4 sm:p-6"},H={key:0},A={class:"divide-y divide-gray-200"},I={class:"flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 sm:gap-0"},O={class:"font-medium"},U={class:"text-sm text-gray-500"},q={key:1,class:"text-center py-4 text-gray-500"},R=b({__name:"Show",props:{course:Object},setup(t){const d=t,p=l=>({pending:"Pending",in_progress:"In Progress",completed:"Completed"})[l]||l,u=l=>l?new Date(l).toLocaleDateString():"—",h=[{name:"Dashboard",href:route("dashboard")},{name:"Course Management",href:route("admin.courses.index")},{name:d.course.name,href:route("admin.courses.show",d.course.id)}];return(l,s)=>(r(),y(_,{breadcrumbs:h},{default:i(()=>[e("div",D,[e("div",C,[e("h1",j,o(t.course.name),1),e("div",k,[c(m(f),{href:`/admin/courses/${t.course.id}/edit`,class:"inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 transition-colors w-full sm:w-auto"},{default:i(()=>s[0]||(s[0]=[x(" Edit Course ")])),_:1},8,["href"]),c(m(f),{href:`/admin/courses/${t.course.id}`,method:"delete",as:"button",class:"inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors w-full sm:w-auto",confirm:"Are you sure you want to delete this course?","confirm-button":"Delete","cancel-button":"Cancel"},{default:i(()=>s[1]||(s[1]=[x(" Delete Course ")])),_:1},8,["href"])])]),e("div",E,[e("div",L,[e("div",M,[e("div",B,[s[2]||(s[2]=e("h2",{class:"text-lg sm:text-xl font-semibold"},"Course Details",-1)),e("span",{class:g(["px-3 py-1 text-sm rounded-full self-start sm:self-auto w-fit",{"bg-green-100 text-green-800":t.course.status==="in_progress","bg-yellow-100 text-yellow-800":t.course.status==="pending","bg-blue-100 text-blue-800":t.course.status==="completed"}])},o(p(t.course.status)),3)]),e("div",{class:"prose prose-sm sm:prose-lg max-w-none mb-6",innerHTML:t.course.description},null,8,N),e("div",P,[e("div",null,[s[3]||(s[3]=e("p",{class:"text-gray-500 mb-1"},"Start Date",-1)),e("p",S,o(u(t.course.start_date)),1)]),e("div",null,[s[4]||(s[4]=e("p",{class:"text-gray-500 mb-1"},"End Date",-1)),e("p",$,o(u(t.course.end_date)),1)]),e("div",null,[s[5]||(s[5]=e("p",{class:"text-gray-500 mb-1"},"Level",-1)),e("p",T,o(t.course.level||"—"),1)]),e("div",null,[s[6]||(s[6]=e("p",{class:"text-gray-500 mb-1"},"Duration",-1)),e("p",V,o(t.course.duration?`${t.course.duration} hours`:"—"),1)])])])]),e("div",z,[e("div",F,[s[7]||(s[7]=e("h2",{class:"text-lg sm:text-xl font-semibold mb-4"},"Enrolled Users",-1)),t.course.users&&t.course.users.length>0?(r(),a("div",H,[e("ul",A,[(r(!0),a(w,null,v(t.course.users,n=>(r(),a("li",{key:n.id,class:"py-3"},[e("div",I,[e("div",null,[e("p",O,o(n.name),1),e("p",U,o(n.email),1)]),e("span",{class:g(["px-2 py-1 text-xs rounded-full self-start sm:self-auto w-fit",{"bg-green-100 text-green-800":n.pivot.user_status==="completed","bg-yellow-100 text-yellow-800":n.pivot.user_status==="enrolled"}])},o(n.pivot.user_status==="completed"?"Completed":"Enrolled"),3)])]))),128))])])):(r(),a("div",q," No users enrolled in this course yet. "))])])])])]),_:1}))}});export{R as default};
