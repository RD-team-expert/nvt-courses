import{bo as re,g as ne,o as d,w as de,e,a as l,s as X,t as a,n as oe,F as x,i as f,v as ee,ac as le,h as Z,f as ce,r as te,a1 as ge,c as me,A as pe,W as ve}from"./app-Bnpb8Ewe.js";import{_ as be}from"./AdminLayout.vue_vue_type_script_setup_true_lang-CZq7W6Or.js";import{_ as xe}from"./_plugin-vue_export-helper-BJh0FV24.js";import"./AppLayout.vue_vue_type_script_setup_true_lang-FtxCnr4c.js";import"./Button.vue_vue_type_script_setup_true_lang-C2wshWrK.js";import"./index-DiPQZiFL.js";import"./index-BX6UFv7t.js";import"./AppLogoIcon.vue_vue_type_script_setup_true_lang-DqGOqHVP.js";import"./clock-D2EmcLKa.js";const fe={name:"MonthlyKpiDashboard",components:{AdminLayout:be},props:{kpiData:{type:Object,required:!0},filterData:{type:Object,required:!0},currentFilters:{type:Object,required:!0},lastUpdated:{type:String,required:!0}},setup(r){const t=te(!1),i=te(!1),o=te(!1),u=ge({month:r.currentFilters.month,year:r.currentFilters.year,department_id:r.currentFilters.department_id||"",course_id:r.currentFilters.course_id||""}),se=async()=>{try{o.value=!0;const n=$(),c=window.open("","screenshot","width=1200,height=800");if(!c)throw new Error("Popup blocked. Please allow popups for this site.");c.document.write(n),c.document.close(),await new Promise(v=>setTimeout(v,1e3));const m=c.document.createElement("script");m.src="https://html2canvas.hertzen.com/dist/html2canvas.min.js",c.document.head.appendChild(m),await new Promise(v=>{m.onload=v}),await new Promise(v=>setTimeout(v,500));const g=await c.html2canvas(c.document.body,{backgroundColor:"#ffffff",scale:1.5,useCORS:!0,allowTaint:!0,width:c.document.body.scrollWidth,height:c.document.body.scrollHeight}),p=document.createElement("a");p.download=`KPI_Report_${u.month}_${u.year}_${new Date().toISOString().split("T")[0]}.png`,p.href=g.toDataURL("image/png",.9),document.body.appendChild(p),p.click(),document.body.removeChild(p),c.close(),console.log("✅ Screenshot generated successfully!"),alert("📸 Screenshot downloaded successfully!")}catch(n){console.error("❌ Screenshot failed:",n),alert(`Screenshot failed: ${n.message}

Please try using your browser's built-in screenshot feature.`)}finally{o.value=!1}},$=()=>{var n,c,m,g,p,v,k,_,w,D,C,z,P,S,T,F,R,A,E,N,j,L,U,M,I,O,q,K,V,B;return`
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>KPI Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #ffffff;
            color: #212529;
            line-height: 1.6;
            padding: 40px;
        }

        .report-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 3px solid #dee2e6;
        }

        .company-logo {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .report-title {
            font-size: 36px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 10px;
        }

        .report-subtitle {
            font-size: 20px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .report-date {
            font-size: 14px;
            color: #868e96;
        }

        .kpi-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: #212529;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }

        .kpi-cards-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            border: 2px solid #dee2e6;
            background: #f8f9fa;
        }

        .kpi-card.blue {
            border-color: #007bff;
            background: #e3f2fd;
        }

        .kpi-card.green {
            border-color: #28a745;
            background: #e8f5e8;
        }

        .kpi-card.purple {
            border-color: #6f42c1;
            background: #f3e5f5;
        }

        .kpi-card.orange {
            border-color: #fd7e14;
            background: #fff3e0;
        }

        .kpi-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .kpi-label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .kpi-value {
            font-size: 28px;
            font-weight: 700;
            color: #212529;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .metric-row {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .metric-label {
            font-weight: 500;
            color: #495057;
        }

        .metric-value {
            font-weight: 600;
            color: #212529;
            font-size: 18px;
        }

        .outcomes-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .outcome-card {
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            border: 2px solid #dee2e6;
            background: #f8f9fa;
        }

        .outcome-card.success {
            background: #d4edda;
            border-color: #28a745;
        }

        .outcome-card.danger {
            background: #f8d7da;
            border-color: #dc3545;
        }

        .outcome-card.info {
            background: #d1ecf1;
            border-color: #17a2b8;
        }

        .outcome-label {
            font-size: 14px;
            color: #495057;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .outcome-value {
            font-size: 24px;
            font-weight: 700;
            color: #212529;
        }

        .feedback-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        .feedback-summary {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .feedback-card {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
        }

        .feedback-label {
            font-size: 14px;
            color: #495057;
            margin-bottom: 8px;
        }

        .feedback-value {
            font-size: 32px;
            font-weight: 700;
            color: #212529;
        }

        .sentiment-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .sentiment-item {
            border-radius: 8px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 2px solid #dee2e6;
            background: #f8f9fa;
        }

        .sentiment-item.positive {
            background: #d4edda;
            border-color: #28a745;
        }

        .sentiment-item.neutral {
            background: #f8f9fa;
            border-color: #6c757d;
        }

        .sentiment-item.negative {
            background: #f8d7da;
            border-color: #dc3545;
        }

        .sentiment-emoji {
            font-size: 24px;
        }

        .sentiment-text {
            font-weight: 500;
            color: #495057;
            font-size: 16px;
        }

        .tables-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .performance-table-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
        }

        .table-title {
            font-size: 18px;
            font-weight: 600;
            color: #212529;
            margin-bottom: 15px;
        }

        .simple-table {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            overflow: hidden;
        }

        .table-header {
            background: #e9ecef;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            font-weight: 600;
            color: #495057;
        }

        .header-cell {
            padding: 12px 16px;
            border-right: 1px solid #dee2e6;
            font-size: 14px;
        }

        .header-cell:last-child {
            border-right: none;
        }

        .table-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            border-top: 1px solid #e9ecef;
        }

        .table-cell {
            padding: 12px 16px;
            border-right: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            color: #495057;
            font-size: 14px;
        }

        .table-cell:last-child {
            border-right: none;
        }

        .trend-container {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 30px;
            align-items: center;
        }

        .trend-card {
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            border: 2px solid #dee2e6;
            background: #f8f9fa;
        }

        .trend-card.current {
            background: #e3f2fd;
            border-color: #007bff;
        }

        .trend-label {
            font-size: 16px;
            font-weight: 500;
            color: #6c757d;
            margin-bottom: 12px;
        }

        .trend-value {
            font-size: 32px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 8px;
        }

        .trend-period {
            font-size: 14px;
            color: #868e96;
        }

        .trend-arrow {
            text-align: center;
        }

        .arrow-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .trend-direction {
            font-size: 14px;
            font-weight: 500;
            color: #495057;
            text-transform: capitalize;
        }

        .report-footer {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 3px solid #dee2e6;
            text-align: center;
        }

        .footer-content {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            display: inline-block;
        }

        .footer-line {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .kpi-cards-grid, .outcomes-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .metrics-grid, .feedback-container, .tables-container {
                grid-template-columns: 1fr;
            }
            .trend-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="company-logo">🏢</div>
        <h1 class="report-title">Monthly Training KPI Report</h1>
        <div class="report-subtitle">${((n=r.kpiData.period)==null?void 0:n.period_name)||"Current Period"}</div>
        <div class="report-date">Generated on: ${new Date().toLocaleDateString()}</div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📈 Key Performance Indicators</h2>
        <div class="kpi-cards-grid">
            <div class="kpi-card blue">
                <div class="kpi-icon">📚</div>
                <div class="kpi-label">Courses Delivered</div>
                <div class="kpi-value">${((c=r.kpiData.delivery_overview)==null?void 0:c.courses_delivered)||0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">👥</div>
                <div class="kpi-label">Total Enrolled</div>
                <div class="kpi-value">${((m=r.kpiData.delivery_overview)==null?void 0:m.total_enrolled)||0}</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">🎯</div>
                <div class="kpi-label">Active Participants</div>
                <div class="kpi-value">${((g=r.kpiData.delivery_overview)==null?void 0:g.active_participants)||0}</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-value">${((p=r.kpiData.delivery_overview)==null?void 0:p.completion_rate)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">🎯 Engagement & Attendance</h2>
        <div class="metrics-grid">
            <div class="metric-row">
                <div class="metric-label">📋 Attendance Rate:</div>
                <div class="metric-value">${((v=r.kpiData.attendance_engagement)==null?void 0:v.average_attendance_rate)||0}%</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">⏱️ Average Time Spent:</div>
                <div class="metric-value">${((k=r.kpiData.attendance_engagement)==null?void 0:k.average_time_spent)||0} hours</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">💯 Engagement Score:</div>
                <div class="metric-value">${((_=r.kpiData.attendance_engagement)==null?void 0:_.engagement_score)||0}%</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">🕐 Clock Consistency:</div>
                <div class="metric-value">${((w=r.kpiData.attendance_engagement)==null?void 0:w.clocking_consistency)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📈 Learning Outcomes</h2>
        <div class="outcomes-grid">
            <div class="outcome-card success">
                <div class="outcome-label">✅ Quiz Pass Rate</div>
                <div class="outcome-value">${((D=r.kpiData.learning_outcomes)==null?void 0:D.quiz_pass_rate)||0}%</div>
            </div>
            <div class="outcome-card danger">
                <div class="outcome-label">❌ Quiz Fail Rate</div>
                <div class="outcome-value">${((C=r.kpiData.learning_outcomes)==null?void 0:C.quiz_fail_rate)||0}%</div>
            </div>
            <div class="outcome-card info">
                <div class="outcome-label">📊 Average Score</div>
                <div class="outcome-value">${((z=r.kpiData.learning_outcomes)==null?void 0:z.average_quiz_score)||0}%</div>
            </div>
            <div class="outcome-card">
                <div class="outcome-label">📈 Improvement Rate</div>
                <div class="outcome-value">${((P=r.kpiData.learning_outcomes)==null?void 0:P.improvement_rate)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">⭐ Course Quality & Feedback</h2>
        <div class="feedback-container">
            <div class="feedback-summary">
                <div class="feedback-card">
                    <div class="feedback-label">Average Rating</div>
                    <div class="feedback-value">${((S=r.kpiData.feedback_analysis)==null?void 0:S.average_rating)||0}/5</div>
                </div>
                <div class="feedback-card">
                    <div class="feedback-label">Total Feedback</div>
                    <div class="feedback-value">${((T=r.kpiData.feedback_analysis)==null?void 0:T.total_feedback_count)||0}</div>
                </div>
            </div>
            <div class="sentiment-container">
                <div class="sentiment-item positive">
                    <span class="sentiment-emoji">😊</span>
                    <span class="sentiment-text">Positive: ${((R=(F=r.kpiData.feedback_analysis)==null?void 0:F.feedback_sentiment)==null?void 0:R.positive)||0}%</span>
                </div>
                <div class="sentiment-item neutral">
                    <span class="sentiment-emoji">😐</span>
                    <span class="sentiment-text">Neutral: ${((E=(A=r.kpiData.feedback_analysis)==null?void 0:A.feedback_sentiment)==null?void 0:E.neutral)||0}%</span>
                </div>
                <div class="sentiment-item negative">
                    <span class="sentiment-emoji">😞</span>
                    <span class="sentiment-text">Negative: ${((j=(N=r.kpiData.feedback_analysis)==null?void 0:N.feedback_sentiment)==null?void 0:j.negative)||0}%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">🏆 Performance Analysis</h2>
        <div class="tables-container">
            <div class="performance-table-container">
                <h3 class="table-title">🥇 Top-Performing Courses</h3>
                <div class="simple-table">
                    <div class="table-header">
                        <div class="header-cell">Course Name</div>
                        <div class="header-cell">Rating</div>
                        <div class="header-cell">Completion</div>
                    </div>
                    ${(((L=r.kpiData.performance_analysis)==null?void 0:L.top_performing_courses)||[]).slice(0,5).map((b,h)=>`
                        <div class="table-row">
                            <div class="table-cell">${h+1}. ${b.name}</div>
                            <div class="table-cell">${b.rating}/5</div>
                            <div class="table-cell">${b.completion_rate}%</div>
                        </div>
                    `).join("")||'<div class="table-row"><div class="table-cell">No data available</div><div class="table-cell">-</div><div class="table-cell">-</div></div>'}
                </div>
            </div>
            <div class="performance-table-container">
                <h3 class="table-title">🌟 Top-Performing Users</h3>
                <div class="simple-table">
                    <div class="table-header">
                        <div class="header-cell">User Name</div>
                        <div class="header-cell">Score</div>
                        <div class="header-cell">Completed</div>
                    </div>
                    ${(((U=r.kpiData.performance_analysis)==null?void 0:U.top_performing_users)||[]).slice(0,5).map((b,h)=>`
                        <div class="table-row">
                            <div class="table-cell">${h+1}. ${b.name}</div>
                            <div class="table-cell">${b.score}%</div>
                            <div class="table-cell">${b.courses_completed||0}</div>
                        </div>
                    `).join("")||'<div class="table-row"><div class="table-cell">No data available</div><div class="table-cell">-</div><div class="table-cell">-</div></div>'}
                </div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📊 Monthly Engagement Trend</h2>
        <div class="trend-container">
            <div class="trend-card current">
                <div class="trend-label">Current Month</div>
                <div class="trend-value">${((M=r.kpiData.engagement_trends)==null?void 0:M.current_month_engagement)||0}%</div>
                <div class="trend-period">${(I=r.kpiData.period)==null?void 0:I.period_name}</div>
            </div>
            <div class="trend-arrow">
                <div class="arrow-icon">${y((O=r.kpiData.engagement_trends)==null?void 0:O.trend_direction)}</div>
                <div class="trend-direction">${((q=r.kpiData.engagement_trends)==null?void 0:q.trend_direction)||"stable"}</div>
            </div>
            <div class="trend-card">
                <div class="trend-label">Previous Month</div>
                <div class="trend-value">${((K=r.kpiData.engagement_trends)==null?void 0:K.previous_month_engagement)||0}%</div>
                <div class="trend-period">${((V=r.kpiData.engagement_trends)==null?void 0:V.trend_percentage)||0}% change</div>
            </div>
        </div>
    </div>

    <div class="report-footer">
        <div class="footer-content">
            <div class="footer-line"><strong>Report Period:</strong> ${((B=r.kpiData.period)==null?void 0:B.period_name)||"Current Period"}</div>
            <div class="footer-line"><strong>Generated:</strong> ${new Date().toLocaleString()}</div>
            <div class="footer-line"><strong>System:</strong> Training Management Platform</div>
        </div>
    </div>
</body>
</html>`},Q=async()=>{t.value=!0;try{await ve.get(route("admin.reports.monthly-kpi"),Object.fromEntries(Object.entries(u).filter(([n,c])=>c!=="")),{preserveState:!0,preserveScroll:!0})}catch(n){console.error("Error applying filters:",n),alert("Error applying filters. Please try again.")}finally{t.value=!1}},W=()=>{window.location.reload()},H=()=>{try{t.value=!0;const n=new URLSearchParams;Object.entries(u).forEach(([m,g])=>{g!==""&&g!==null&&g!==void 0&&n.append(m,g)});const c=route("admin.reports.export-monthly-kpi-csv")+"?"+n.toString();window.open(c,"_blank")}catch(n){console.error("Error exporting CSV:",n),alert("Error exporting CSV. Please try again.")}finally{setTimeout(()=>{t.value=!1},1e3)}},G=n=>n?new Date(n).toLocaleString():"Unknown",Y=n=>{switch(n){case"up":return"trend-up";case"down":return"trend-down";default:return"trend-stable"}},y=n=>{switch(n){case"up":return"↗️";case"down":return"↘️";default:return"➡️"}},J=me(()=>r.kpiData&&Object.keys(r.kpiData).length>0);return pe(()=>{console.log("🎯 Monthly KPI Dashboard mounted")}),{loading:t,showFilters:i,filters:u,screenshotLoading:o,applyFilters:Q,refreshData:W,exportCsv:H,formatDateTime:G,getTrendClass:Y,getTrendArrow:y,generateDirectScreenshot:se,hasData:J}}},ue={class:"monthly-kpi-dashboard bg-black text-white min-h-screen dark"},he={class:"dashboard-header bg-gray-800 border-b border-gray-600 p-4 sm:p-6"},ye={class:"header-content"},ke={class:"title-section"},_e={class:"period-display text-gray-300 mt-2"},we={class:"header-actions flex flex-wrap gap-3 mt-4"},De={class:"export-buttons flex gap-2"},Ce=["disabled"],ze=["disabled"],Pe=["disabled"],Se={class:"filter-panel bg-gray-700 border border-gray-600 rounded-lg p-4 mt-4"},Te={class:"filter-grid grid grid-cols-1 md:grid-cols-3 gap-4"},Fe={class:"filter-group"},Re=["value"],Ae={class:"filter-group"},Ee=["value"],Ne={class:"filter-group"},je=["value"],Le={class:"update-info mt-4 pt-4 border-t border-gray-600"},Ue={class:"last-updated text-sm text-gray-300"},Me={key:0,class:"loading-overlay fixed inset-0 bg-gray-900 bg-opacity-90 flex items-center justify-center z-50"},Ie={key:1,class:"dashboard-content p-4 sm:p-6 space-y-8"},Oe={class:"kpi-section delivery-overview"},qe={class:"kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"},Ke={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Ve={class:"kpi-value text-3xl font-bold text-white"},Be={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Qe={class:"kpi-value text-3xl font-bold text-white"},We={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},He={class:"kpi-value text-3xl font-bold text-white"},Ge={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Ye={class:"kpi-value text-3xl font-bold text-white"},Je={class:"kpi-section attendance-engagement"},Xe={class:"kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"},Ze={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},$e={class:"kpi-value text-3xl font-bold text-white"},et={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},tt={class:"kpi-value text-3xl font-bold text-white"},st={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},at={class:"kpi-value text-3xl font-bold text-white"},it={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},ot={class:"kpi-value text-3xl font-bold text-white"},rt={class:"kpi-section learning-outcomes"},nt={class:"kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"},dt={class:"kpi-card success bg-gray-800 border border-green-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-green-900/20"},lt={class:"kpi-value text-3xl font-bold text-green-300"},ct={class:"kpi-card danger bg-gray-800 border border-red-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-red-900/20"},gt={class:"kpi-value text-3xl font-bold text-red-300"},mt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},pt={class:"kpi-value text-3xl font-bold text-white"},vt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},bt={class:"kpi-value text-3xl font-bold text-white"},xt={class:"kpi-section feedback-analysis"},ft={class:"feedback-grid grid grid-cols-1 lg:grid-cols-2 gap-8"},ut={class:"feedback-cards grid grid-cols-1 sm:grid-cols-2 gap-6"},ht={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},yt={class:"kpi-value text-3xl font-bold text-white"},kt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},_t={class:"kpi-value text-3xl font-bold text-white"},wt={class:"feedback-sentiment bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},Dt={class:"sentiment-display space-y-3"},Ct={class:"sentiment-item flex justify-between items-center"},zt={class:"sentiment-value font-bold text-green-400"},Pt={class:"sentiment-item flex justify-between items-center"},St={class:"sentiment-value font-bold text-yellow-400"},Tt={class:"sentiment-item flex justify-between items-center"},Ft={class:"sentiment-value font-bold text-red-400"},Rt={class:"kpi-section performance-analysis"},At={class:"performance-grid grid grid-cols-1 lg:grid-cols-2 gap-8"},Et={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},Nt={class:"table-container overflow-x-auto"},jt={class:"performance-table-content w-full"},Lt={class:"divide-y divide-gray-600"},Ut={class:"course-name px-4 py-3 text-sm text-white"},Mt={class:"rating px-4 py-3 text-sm text-white"},It={class:"completion px-4 py-3 text-sm text-white"},Ot={key:0},qt={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},Kt={class:"table-container overflow-x-auto"},Vt={class:"performance-table-content w-full"},Bt={class:"divide-y divide-gray-600"},Qt={class:"course-name px-4 py-3 text-sm text-white"},Wt={class:"rating low px-4 py-3 text-sm text-red-400"},Ht={class:"issues px-4 py-3"},Gt={key:0},Yt={class:"kpi-section user-performance"},Jt={class:"performance-grid grid grid-cols-1 lg:grid-cols-2 gap-8"},Xt={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},Zt={class:"table-container overflow-x-auto"},$t={class:"performance-table-content w-full"},es={class:"divide-y divide-gray-600"},ts={class:"user-name px-4 py-3 text-sm text-white"},ss={class:"score high px-4 py-3 text-sm text-green-400 font-semibold"},as={class:"courses px-4 py-3 text-sm text-white"},is={key:0},os={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},rs={class:"table-container overflow-x-auto"},ns={class:"performance-table-content w-full"},ds={class:"divide-y divide-gray-600"},ls={class:"user-name px-4 py-3 text-sm text-white"},cs={class:"score low px-4 py-3 text-sm text-yellow-400 font-semibold"},gs={class:"courses px-4 py-3 text-sm text-white"},ms={key:0},ps={class:"kpi-section engagement-trends"},vs={class:"trends-display"},bs={class:"trend-cards grid grid-cols-1 md:grid-cols-3 gap-6"},xs={class:"trend-card current bg-gray-800 border border-blue-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-blue-900/20"},fs={class:"trend-value text-3xl font-bold text-blue-300"},us={class:"trend-label text-sm text-gray-300 mt-2"},hs={class:"trend-card previous bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},ys={class:"trend-value text-3xl font-bold text-gray-200"},ks={class:"trend-card comparison bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},_s={class:"trend-arrow mr-2"},ws={class:"trend-percentage text-sm text-gray-300 mt-2"};function Ds(r,t,i,o,u,se){const $=re("AdminLayout");return d(),ne($,{breadcrumbs:r.breadcrumbs},{default:de(()=>{var Q,W,H,G,Y,y,J,n,c,m,g,p,v,k,_,w,D,C,z,P,S,T,F,R,A,E,N,j,L,U,M,I,O,q,K,V,B,b,h,ae;return[e("div",ue,[e("div",he,[e("div",ye,[e("div",ke,[t[10]||(t[10]=e("h1",{class:"dashboard-title text-2xl sm:text-3xl font-bold text-white"},"📊 Monthly Training KPI Report",-1)),e("p",_e,a(((Q=i.kpiData.period)==null?void 0:Q.period_name)||"Loading..."),1)]),e("div",we,[e("button",{onClick:t[0]||(t[0]=s=>o.showFilters=!o.showFilters),class:oe(["filter-btn bg-gray-700 text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors",{"bg-blue-800 text-blue-200":o.showFilters}])}," 🔍 Filters ",2),e("div",De,[e("button",{onClick:t[1]||(t[1]=(...s)=>o.generateDirectScreenshot&&o.generateDirectScreenshot(...s)),class:"export-btn screenshot-btn bg-purple-800 text-purple-200 px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50",disabled:o.loading||o.screenshotLoading},a(o.screenshotLoading?"⏳ Capturing...":"📸 Screenshot"),9,Ce),e("button",{onClick:t[2]||(t[2]=(...s)=>o.exportCsv&&o.exportCsv(...s)),class:"export-btn csv-btn bg-green-800 text-green-200 px-4 py-2 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50",disabled:o.loading}," 📋 Export CSV ",8,ze)]),e("button",{onClick:t[3]||(t[3]=(...s)=>o.refreshData&&o.refreshData(...s)),class:"refresh-btn bg-indigo-800 text-indigo-200 px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50",disabled:o.loading},a(o.loading?"⏳":"🔄")+" Refresh ",9,Pe)])]),X(e("div",Se,[e("div",Te,[e("div",Fe,[t[11]||(t[11]=e("label",{class:"block text-sm font-medium text-gray-200 mb-2"},"Month",-1)),X(e("select",{"onUpdate:modelValue":t[4]||(t[4]=s=>o.filters.month=s),onChange:t[5]||(t[5]=(...s)=>o.applyFilters&&o.applyFilters(...s)),class:"w-full bg-gray-600 border border-gray-500 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400"},[(d(!0),l(x,null,f(i.filterData.months,s=>(d(),l("option",{key:s.value,value:s.value},a(s.label),9,Re))),128))],544),[[ee,o.filters.month]])]),e("div",Ae,[t[12]||(t[12]=e("label",{class:"block text-sm font-medium text-gray-200 mb-2"},"Year",-1)),X(e("select",{"onUpdate:modelValue":t[6]||(t[6]=s=>o.filters.year=s),onChange:t[7]||(t[7]=(...s)=>o.applyFilters&&o.applyFilters(...s)),class:"w-full bg-gray-600 border border-gray-500 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400"},[(d(!0),l(x,null,f(i.filterData.years,s=>(d(),l("option",{key:s.value,value:s.value},a(s.label),9,Ee))),128))],544),[[ee,o.filters.year]])]),e("div",Ne,[t[14]||(t[14]=e("label",{class:"block text-sm font-medium text-gray-200 mb-2"},"Department",-1)),X(e("select",{"onUpdate:modelValue":t[8]||(t[8]=s=>o.filters.department_id=s),onChange:t[9]||(t[9]=(...s)=>o.applyFilters&&o.applyFilters(...s)),class:"w-full bg-gray-600 border border-gray-500 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400"},[t[13]||(t[13]=e("option",{value:""},"All Departments",-1)),(d(!0),l(x,null,f(i.filterData.departments,s=>(d(),l("option",{key:s.id,value:s.id},a(s.name),9,je))),128))],544),[[ee,o.filters.department_id]])])])],512),[[le,o.showFilters]]),e("div",Le,[e("span",Ue,"Last updated: "+a(o.formatDateTime(i.lastUpdated)),1)])]),o.loading?(d(),l("div",Me,t[15]||(t[15]=[e("div",{class:"loading-spinner bg-gray-800 rounded-lg p-8 text-center shadow-2xl border border-gray-600"},[e("div",{class:"spinner inline-block w-8 h-8 border-4 border-gray-600 border-t-blue-400 rounded-full animate-spin mb-4"}),e("p",{class:"text-white"},"Loading KPI Data...")],-1)]))):(d(),l("div",Ie,[e("section",Oe,[t[20]||(t[20]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"📊 Training Delivery Overview",-1)),e("div",qe,[e("div",Ke,[t[16]||(t[16]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📚"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Courses Delivered")],-1)),e("div",Ve,a(((W=i.kpiData.delivery_overview)==null?void 0:W.courses_delivered)||0),1)]),e("div",Be,[t[17]||(t[17]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"👥"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Total Enrolled")],-1)),e("div",Qe,a(((H=i.kpiData.delivery_overview)==null?void 0:H.total_enrolled)||0),1)]),e("div",We,[t[18]||(t[18]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"🎯"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Active Participants")],-1)),e("div",He,a(((G=i.kpiData.delivery_overview)==null?void 0:G.active_participants)||0),1)]),e("div",Ge,[t[19]||(t[19]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"✅"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Completion Rate")],-1)),e("div",Ye,a(((Y=i.kpiData.delivery_overview)==null?void 0:Y.completion_rate)||0)+"%",1)])])]),e("section",Je,[t[25]||(t[25]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"🎯 Attendance & Engagement",-1)),e("div",Xe,[e("div",Ze,[t[21]||(t[21]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📋"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Attendance Rate")],-1)),e("div",$e,a(((y=i.kpiData.attendance_engagement)==null?void 0:y.average_attendance_rate)||0)+"%",1)]),e("div",et,[t[22]||(t[22]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"⏱️"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Avg Time Spent")],-1)),e("div",tt,a(((J=i.kpiData.attendance_engagement)==null?void 0:J.average_time_spent)||0)+"h",1)]),e("div",st,[t[23]||(t[23]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"🕐"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Clock Consistency")],-1)),e("div",at,a(((n=i.kpiData.attendance_engagement)==null?void 0:n.clocking_consistency)||0)+"%",1)]),e("div",it,[t[24]||(t[24]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"💯"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Engagement Score")],-1)),e("div",ot,a(((c=i.kpiData.attendance_engagement)==null?void 0:c.engagement_score)||0)+"%",1)])])]),e("section",rt,[t[30]||(t[30]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"📈 Learning Outcomes",-1)),e("div",nt,[e("div",dt,[t[26]||(t[26]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"✅"),e("span",{class:"kpi-title text-sm font-medium text-green-400"},"Quiz Pass Rate")],-1)),e("div",lt,a(((m=i.kpiData.learning_outcomes)==null?void 0:m.quiz_pass_rate)||0)+"%",1)]),e("div",ct,[t[27]||(t[27]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"❌"),e("span",{class:"kpi-title text-sm font-medium text-red-400"},"Quiz Fail Rate")],-1)),e("div",gt,a(((g=i.kpiData.learning_outcomes)==null?void 0:g.quiz_fail_rate)||0)+"%",1)]),e("div",mt,[t[28]||(t[28]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📊"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Average Score")],-1)),e("div",pt,a(((p=i.kpiData.learning_outcomes)==null?void 0:p.average_quiz_score)||0)+"%",1)]),e("div",vt,[t[29]||(t[29]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📈"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Improvement Rate")],-1)),e("div",bt,a(((v=i.kpiData.learning_outcomes)==null?void 0:v.improvement_rate)||0)+"%",1)])])]),e("section",xt,[t[37]||(t[37]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"⭐ Course Quality & Feedback",-1)),e("div",ft,[e("div",ut,[e("div",ht,[t[31]||(t[31]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"⭐"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Average Rating")],-1)),e("div",yt,a(((k=i.kpiData.feedback_analysis)==null?void 0:k.average_rating)||0)+"/5",1)]),e("div",kt,[t[32]||(t[32]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"💬"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Total Feedback")],-1)),e("div",_t,a(((_=i.kpiData.feedback_analysis)==null?void 0:_.total_feedback_count)||0),1)])]),e("div",wt,[t[36]||(t[36]=e("h3",{class:"text-lg font-semibold text-white mb-4"},"Feedback Sentiment",-1)),e("div",Dt,[e("div",Ct,[t[33]||(t[33]=e("span",{class:"sentiment-label text-gray-300"},"😊 Positive:",-1)),e("span",zt,a(((D=(w=i.kpiData.feedback_analysis)==null?void 0:w.feedback_sentiment)==null?void 0:D.positive)||0)+"%",1)]),e("div",Pt,[t[34]||(t[34]=e("span",{class:"sentiment-label text-gray-300"},"😐 Neutral:",-1)),e("span",St,a(((z=(C=i.kpiData.feedback_analysis)==null?void 0:C.feedback_sentiment)==null?void 0:z.neutral)||0)+"%",1)]),e("div",Tt,[t[35]||(t[35]=e("span",{class:"sentiment-label text-gray-300"},"😞 Negative:",-1)),e("span",Ft,a(((S=(P=i.kpiData.feedback_analysis)==null?void 0:P.feedback_sentiment)==null?void 0:S.negative)||0)+"%",1)])])])])]),e("section",Rt,[t[46]||(t[46]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"🏆 Course Performance Analysis",-1)),e("div",At,[e("div",Et,[t[40]||(t[40]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"🥇 Top-Performing Courses",-1)),t[41]||(t[41]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on rating & completion",-1)),e("div",Nt,[e("table",jt,[t[39]||(t[39]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Course Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Rating"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Completion %")])],-1)),e("tbody",Lt,[(d(!0),l(x,null,f(((T=i.kpiData.performance_analysis)==null?void 0:T.top_performing_courses)||[],s=>(d(),l("tr",{key:s.id,class:"table-row hover:bg-gray-700 transition-colors"},[e("td",Ut,a(s.name),1),e("td",Mt,a(s.rating)+"/5",1),e("td",It,a(s.completion_rate)+"%",1)]))),128)),(R=(F=i.kpiData.performance_analysis)==null?void 0:F.top_performing_courses)!=null&&R.length?Z("",!0):(d(),l("tr",Ot,t[38]||(t[38]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No data available",-1)])))])])])]),e("div",qt,[t[44]||(t[44]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"⚠️ Courses Needing Improvement",-1)),t[45]||(t[45]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on dropout or low ratings",-1)),e("div",Kt,[e("table",Vt,[t[43]||(t[43]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Course Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Rating"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Issues")])],-1)),e("tbody",Bt,[(d(!0),l(x,null,f(((A=i.kpiData.performance_analysis)==null?void 0:A.courses_needing_improvement)||[],s=>(d(),l("tr",{key:s.name,class:"table-row improvement-needed hover:bg-red-900/30 transition-colors"},[e("td",Qt,a(s.name),1),e("td",Wt,a(s.rating||"N/A"),1),e("td",Ht,[(d(!0),l(x,null,f(s.issues,ie=>(d(),l("span",{key:ie,class:"issue-tag inline-block bg-red-800 text-red-200 text-xs px-2 py-1 rounded mr-1 mb-1"},a(ie),1))),128))])]))),128)),(N=(E=i.kpiData.performance_analysis)==null?void 0:E.courses_needing_improvement)!=null&&N.length?Z("",!0):(d(),l("tr",Gt,t[42]||(t[42]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No courses needing improvement",-1)])))])])])])])]),e("section",Yt,[t[55]||(t[55]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"👤 User Performance Analysis",-1)),e("div",Jt,[e("div",Xt,[t[49]||(t[49]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"🌟 Top-Performing Users",-1)),t[50]||(t[50]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on evaluation system scores",-1)),e("div",Zt,[e("table",$t,[t[48]||(t[48]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"User Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Score %"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Courses Completed")])],-1)),e("tbody",es,[(d(!0),l(x,null,f(((j=i.kpiData.performance_analysis)==null?void 0:j.top_performing_users)||[],s=>(d(),l("tr",{key:s.name,class:"table-row top-performer hover:bg-green-900/30 transition-colors"},[e("td",ts,a(s.name),1),e("td",ss,a(s.score)+"%",1),e("td",as,a(s.courses_completed||0),1)]))),128)),(U=(L=i.kpiData.performance_analysis)==null?void 0:L.top_performing_users)!=null&&U.length?Z("",!0):(d(),l("tr",is,t[47]||(t[47]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No data available",-1)])))])])])]),e("div",os,[t[53]||(t[53]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"📈 Users Needing Support",-1)),t[54]||(t[54]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on evaluation system scores",-1)),e("div",rs,[e("table",ns,[t[52]||(t[52]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"User Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Score %"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Incomplete Courses")])],-1)),e("tbody",ds,[(d(!0),l(x,null,f(((M=i.kpiData.performance_analysis)==null?void 0:M.low_performing_users)||[],s=>(d(),l("tr",{key:s.name,class:"table-row needs-support hover:bg-yellow-900/30 transition-colors"},[e("td",ls,a(s.name),1),e("td",cs,a(s.score)+"%",1),e("td",gs,a(s.courses_incomplete||0),1)]))),128)),(O=(I=i.kpiData.performance_analysis)==null?void 0:I.low_performing_users)!=null&&O.length?Z("",!0):(d(),l("tr",ms,t[51]||(t[51]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No users needing support",-1)])))])])])])])]),e("section",ps,[t[60]||(t[60]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"📈 Monthly Engagement Trend",-1)),e("div",vs,[e("div",bs,[e("div",xs,[t[56]||(t[56]=e("div",{class:"trend-header flex items-center mb-4"},[e("span",{class:"trend-icon text-2xl mr-3"},"📊"),e("span",{class:"trend-title text-sm font-medium text-blue-400"},"Current Month Engagement")],-1)),e("div",fs,a(((q=i.kpiData.engagement_trends)==null?void 0:q.current_month_engagement)||0)+"%",1),e("div",us,a(((K=i.kpiData.period)==null?void 0:K.period_name)||"Current Period"),1)]),e("div",hs,[t[57]||(t[57]=e("div",{class:"trend-header flex items-center mb-4"},[e("span",{class:"trend-icon text-2xl mr-3"},"📉"),e("span",{class:"trend-title text-sm font-medium text-gray-300"},"Previous Month Engagement")],-1)),e("div",ys,a(((V=i.kpiData.engagement_trends)==null?void 0:V.previous_month_engagement)||0)+"%",1),t[58]||(t[58]=e("div",{class:"trend-label text-sm text-gray-300 mt-2"},"Previous Period",-1))]),e("div",ks,[t[59]||(t[59]=e("div",{class:"trend-header flex items-center mb-4"},[e("span",{class:"trend-icon text-2xl mr-3"},"🔄"),e("span",{class:"trend-title text-sm font-medium text-gray-300"},"Trend Direction")],-1)),e("div",{class:oe(["trend-value text-2xl font-bold flex items-center",o.getTrendClass((B=i.kpiData.engagement_trends)==null?void 0:B.trend_direction)])},[e("span",_s,a(o.getTrendArrow((b=i.kpiData.engagement_trends)==null?void 0:b.trend_direction)),1),ce(" "+a(((h=i.kpiData.engagement_trends)==null?void 0:h.trend_direction)||"stable"),1)],2),e("div",ws,a(((ae=i.kpiData.engagement_trends)==null?void 0:ae.trend_percentage)||0)+"% change",1)])])])])]))])]}),_:1},8,["breadcrumbs"])}const Ns=xe(fe,[["render",Ds],["__scopeId","data-v-e0ee25ad"]]);export{Ns as default};
