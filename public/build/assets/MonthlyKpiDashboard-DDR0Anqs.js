import{a as d,o as r,e,g as Z,t as i,n as ae,L as ne,F as f,h as b,i as ee,f as $,j as oe,r as te,ba as le,c as de,B as re,W as ce}from"./app-iYB6CK9s.js";import{_ as ve}from"./_plugin-vue_export-helper-DlAUqK2U.js";const pe={name:"MonthlyKpiDashboard",props:{kpiData:{type:Object,required:!0},filterData:{type:Object,required:!0},currentFilters:{type:Object,required:!0},lastUpdated:{type:String,required:!0}},setup(o){const t=te(!1),a=te(!1),n=te(!1),k=le({month:o.currentFilters.month,year:o.currentFilters.year,department_id:o.currentFilters.department_id||"",course_id:o.currentFilters.course_id||""}),se=async()=>{try{n.value=!0;const l=Q(),c=window.open("","screenshot","width=1200,height=800");if(!c)throw new Error("Popup blocked. Please allow popups for this site.");c.document.write(l),c.document.close(),await new Promise(g=>setTimeout(g,1e3));const p=c.document.createElement("script");p.src="https://html2canvas.hertzen.com/dist/html2canvas.min.js",c.document.head.appendChild(p),await new Promise(g=>{p.onload=g}),await new Promise(g=>setTimeout(g,500));const v=await c.html2canvas(c.document.body,{backgroundColor:"#ffffff",scale:1.5,useCORS:!0,allowTaint:!0,width:c.document.body.scrollWidth,height:c.document.body.scrollHeight}),m=document.createElement("a");m.download=`KPI_Report_${k.month}_${k.year}_${new Date().toISOString().split("T")[0]}.png`,m.href=v.toDataURL("image/png",.9),document.body.appendChild(m),m.click(),document.body.removeChild(m),c.close(),console.log("✅ Screenshot generated successfully!"),alert("📸 Screenshot downloaded successfully!")}catch(l){console.error("❌ Screenshot failed:",l),alert(`Screenshot failed: ${l.message}

Please try using your browser's built-in screenshot feature.`)}finally{n.value=!1}},Q=()=>{var l,c,p,v,m,g,y,x,w,D,C,P,z,S,T,F,R,E,N,A,j,U,L,M,I,O,q,K,V,B;return`
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
        <div class="report-subtitle">${((l=o.kpiData.period)==null?void 0:l.period_name)||"Current Period"}</div>
        <div class="report-date">Generated on: ${new Date().toLocaleDateString()}</div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📈 Key Performance Indicators</h2>
        <div class="kpi-cards-grid">
            <div class="kpi-card blue">
                <div class="kpi-icon">📚</div>
                <div class="kpi-label">Courses Delivered</div>
                <div class="kpi-value">${((c=o.kpiData.delivery_overview)==null?void 0:c.courses_delivered)||0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">👥</div>
                <div class="kpi-label">Total Enrolled</div>
                <div class="kpi-value">${((p=o.kpiData.delivery_overview)==null?void 0:p.total_enrolled)||0}</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">🎯</div>
                <div class="kpi-label">Active Participants</div>
                <div class="kpi-value">${((v=o.kpiData.delivery_overview)==null?void 0:v.active_participants)||0}</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-value">${((m=o.kpiData.delivery_overview)==null?void 0:m.completion_rate)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">🎯 Engagement & Attendance</h2>
        <div class="metrics-grid">
            <div class="metric-row">
                <div class="metric-label">📋 Attendance Rate:</div>
                <div class="metric-value">${((g=o.kpiData.attendance_engagement)==null?void 0:g.average_attendance_rate)||0}%</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">⏱️ Average Time Spent:</div>
                <div class="metric-value">${((y=o.kpiData.attendance_engagement)==null?void 0:y.average_time_spent)||0} hours</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">💯 Engagement Score:</div>
                <div class="metric-value">${((x=o.kpiData.attendance_engagement)==null?void 0:x.engagement_score)||0}%</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">🕐 Clock Consistency:</div>
                <div class="metric-value">${((w=o.kpiData.attendance_engagement)==null?void 0:w.clocking_consistency)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📈 Learning Outcomes</h2>
        <div class="outcomes-grid">
            <div class="outcome-card success">
                <div class="outcome-label">✅ Quiz Pass Rate</div>
                <div class="outcome-value">${((D=o.kpiData.learning_outcomes)==null?void 0:D.quiz_pass_rate)||0}%</div>
            </div>
            <div class="outcome-card danger">
                <div class="outcome-label">❌ Quiz Fail Rate</div>
                <div class="outcome-value">${((C=o.kpiData.learning_outcomes)==null?void 0:C.quiz_fail_rate)||0}%</div>
            </div>
            <div class="outcome-card info">
                <div class="outcome-label">📊 Average Score</div>
                <div class="outcome-value">${((P=o.kpiData.learning_outcomes)==null?void 0:P.average_quiz_score)||0}%</div>
            </div>
            <div class="outcome-card">
                <div class="outcome-label">📈 Improvement Rate</div>
                <div class="outcome-value">${((z=o.kpiData.learning_outcomes)==null?void 0:z.improvement_rate)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">⭐ Course Quality & Feedback</h2>
        <div class="feedback-container">
            <div class="feedback-summary">
                <div class="feedback-card">
                    <div class="feedback-label">Average Rating</div>
                    <div class="feedback-value">${((S=o.kpiData.feedback_analysis)==null?void 0:S.average_rating)||0}/5</div>
                </div>
                <div class="feedback-card">
                    <div class="feedback-label">Total Feedback</div>
                    <div class="feedback-value">${((T=o.kpiData.feedback_analysis)==null?void 0:T.total_feedback_count)||0}</div>
                </div>
            </div>
            <div class="sentiment-container">
                <div class="sentiment-item positive">
                    <span class="sentiment-emoji">😊</span>
                    <span class="sentiment-text">Positive: ${((R=(F=o.kpiData.feedback_analysis)==null?void 0:F.feedback_sentiment)==null?void 0:R.positive)||0}%</span>
                </div>
                <div class="sentiment-item neutral">
                    <span class="sentiment-emoji">😐</span>
                    <span class="sentiment-text">Neutral: ${((N=(E=o.kpiData.feedback_analysis)==null?void 0:E.feedback_sentiment)==null?void 0:N.neutral)||0}%</span>
                </div>
                <div class="sentiment-item negative">
                    <span class="sentiment-emoji">😞</span>
                    <span class="sentiment-text">Negative: ${((j=(A=o.kpiData.feedback_analysis)==null?void 0:A.feedback_sentiment)==null?void 0:j.negative)||0}%</span>
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
                    ${(((U=o.kpiData.performance_analysis)==null?void 0:U.top_performing_courses)||[]).slice(0,5).map((u,_)=>`
                        <div class="table-row">
                            <div class="table-cell">${_+1}. ${u.name}</div>
                            <div class="table-cell">${u.rating}/5</div>
                            <div class="table-cell">${u.completion_rate}%</div>
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
                    ${(((L=o.kpiData.performance_analysis)==null?void 0:L.top_performing_users)||[]).slice(0,5).map((u,_)=>`
                        <div class="table-row">
                            <div class="table-cell">${_+1}. ${u.name}</div>
                            <div class="table-cell">${u.score}%</div>
                            <div class="table-cell">${u.courses_completed||0}</div>
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
                <div class="trend-value">${((M=o.kpiData.engagement_trends)==null?void 0:M.current_month_engagement)||0}%</div>
                <div class="trend-period">${(I=o.kpiData.period)==null?void 0:I.period_name}</div>
            </div>
            <div class="trend-arrow">
                <div class="arrow-icon">${h((O=o.kpiData.engagement_trends)==null?void 0:O.trend_direction)}</div>
                <div class="trend-direction">${((q=o.kpiData.engagement_trends)==null?void 0:q.trend_direction)||"stable"}</div>
            </div>
            <div class="trend-card">
                <div class="trend-label">Previous Month</div>
                <div class="trend-value">${((K=o.kpiData.engagement_trends)==null?void 0:K.previous_month_engagement)||0}%</div>
                <div class="trend-period">${((V=o.kpiData.engagement_trends)==null?void 0:V.trend_percentage)||0}% change</div>
            </div>
        </div>
    </div>

    <div class="report-footer">
        <div class="footer-content">
            <div class="footer-line"><strong>Report Period:</strong> ${((B=o.kpiData.period)==null?void 0:B.period_name)||"Current Period"}</div>
            <div class="footer-line"><strong>Generated:</strong> ${new Date().toLocaleString()}</div>
            <div class="footer-line"><strong>System:</strong> Training Management Platform</div>
        </div>
    </div>
</body>
</html>`},W=async()=>{t.value=!0;try{await ce.get(route("admin.reports.monthly-kpi"),Object.fromEntries(Object.entries(k).filter(([l,c])=>c!=="")),{preserveState:!0,preserveScroll:!0})}catch(l){console.error("Error applying filters:",l),alert("Error applying filters. Please try again.")}finally{t.value=!1}},H=()=>{window.location.reload()},G=()=>{try{t.value=!0;const l=new URLSearchParams;Object.entries(k).forEach(([p,v])=>{v!==""&&v!==null&&v!==void 0&&l.append(p,v)});const c=route("admin.reports.export-monthly-kpi-csv")+"?"+l.toString();window.open(c,"_blank")}catch(l){console.error("Error exporting CSV:",l),alert("Error exporting CSV. Please try again.")}finally{setTimeout(()=>{t.value=!1},1e3)}},Y=l=>l?new Date(l).toLocaleString():"Unknown",J=l=>{switch(l){case"up":return"trend-up";case"down":return"trend-down";default:return"trend-stable"}},h=l=>{switch(l){case"up":return"↗️";case"down":return"↘️";default:return"➡️"}},X=de(()=>o.kpiData&&Object.keys(o.kpiData).length>0);return re(()=>{console.log("🎯 Monthly KPI Dashboard mounted")}),{loading:t,showFilters:a,filters:k,screenshotLoading:n,applyFilters:W,refreshData:H,exportCsv:G,formatDateTime:Y,getTrendClass:J,getTrendArrow:h,generateDirectScreenshot:se,hasData:X}}},me={class:"monthly-kpi-dashboard"},ge={class:"dashboard-header"},ue={class:"header-content"},fe={class:"title-section"},be={class:"period-display"},ke={class:"header-actions"},_e={class:"export-buttons"},he=["disabled"],ye=["disabled"],xe=["disabled"],we={class:"filter-panel"},De={class:"filter-grid"},Ce={class:"filter-group"},Pe=["value"],ze={class:"filter-group"},Se=["value"],Te={class:"filter-group"},Fe=["value"],Re={class:"update-info"},Ee={class:"last-updated"},Ne={key:0,class:"loading-overlay"},Ae={key:1,class:"dashboard-content"},je={class:"kpi-section delivery-overview"},Ue={class:"kpi-grid"},Le={class:"kpi-card"},Me={class:"kpi-value"},Ie={class:"kpi-card"},Oe={class:"kpi-value"},qe={class:"kpi-card"},Ke={class:"kpi-value"},Ve={class:"kpi-card"},Be={class:"kpi-value"},Qe={class:"kpi-section attendance-engagement"},We={class:"kpi-grid"},He={class:"kpi-card"},Ge={class:"kpi-value"},Ye={class:"kpi-card"},Je={class:"kpi-value"},Xe={class:"kpi-card"},Ze={class:"kpi-value"},$e={class:"kpi-card"},et={class:"kpi-value"},tt={class:"kpi-section learning-outcomes"},st={class:"kpi-grid"},it={class:"kpi-card success"},at={class:"kpi-value"},nt={class:"kpi-card danger"},ot={class:"kpi-value"},lt={class:"kpi-card"},dt={class:"kpi-value"},rt={class:"kpi-card"},ct={class:"kpi-value"},vt={class:"kpi-section feedback-analysis"},pt={class:"feedback-grid"},mt={class:"feedback-cards"},gt={class:"kpi-card"},ut={class:"kpi-value"},ft={class:"kpi-card"},bt={class:"kpi-value"},kt={class:"feedback-sentiment"},_t={class:"sentiment-display"},ht={class:"sentiment-item"},yt={class:"sentiment-value"},xt={class:"sentiment-item"},wt={class:"sentiment-value"},Dt={class:"sentiment-item"},Ct={class:"sentiment-value"},Pt={class:"kpi-section performance-analysis"},zt={class:"performance-grid"},St={class:"performance-table"},Tt={class:"table-container"},Ft={class:"performance-table-content"},Rt={class:"course-name"},Et={class:"rating"},Nt={class:"completion"},At={key:0},jt={class:"performance-table"},Ut={class:"table-container"},Lt={class:"performance-table-content"},Mt={class:"course-name"},It={class:"rating low"},Ot={class:"issues"},qt={key:0},Kt={class:"kpi-section user-performance"},Vt={class:"performance-grid"},Bt={class:"performance-table"},Qt={class:"table-container"},Wt={class:"performance-table-content"},Ht={class:"user-name"},Gt={class:"score high"},Yt={class:"courses"},Jt={key:0},Xt={class:"performance-table"},Zt={class:"table-container"},$t={class:"performance-table-content"},es={class:"user-name"},ts={class:"score low"},ss={class:"courses"},is={key:0},as={class:"kpi-section engagement-trends"},ns={class:"trends-display"},os={class:"trend-cards"},ls={class:"trend-card current"},ds={class:"trend-value"},rs={class:"trend-label"},cs={class:"trend-card previous"},vs={class:"trend-value"},ps={class:"trend-card comparison"},ms={class:"trend-arrow"},gs={class:"trend-percentage"};function us(o,t,a,n,k,se){var Q,W,H,G,Y,J,h,X,l,c,p,v,m,g,y,x,w,D,C,P,z,S,T,F,R,E,N,A,j,U,L,M,I,O,q,K,V,B,u,_;return r(),d("div",me,[e("div",ge,[e("div",ue,[e("div",fe,[t[10]||(t[10]=e("h1",{class:"dashboard-title"},"📊 Monthly Training KPI Report",-1)),e("p",be,i(((Q=a.kpiData.period)==null?void 0:Q.period_name)||"Loading..."),1)]),e("div",ke,[e("button",{onClick:t[0]||(t[0]=s=>n.showFilters=!n.showFilters),class:ae(["filter-btn",{active:n.showFilters}])}," 🔍 Filters ",2),e("div",_e,[e("button",{onClick:t[1]||(t[1]=(...s)=>n.generateDirectScreenshot&&n.generateDirectScreenshot(...s)),class:"export-btn screenshot-btn",disabled:n.loading||n.screenshotLoading},i(n.screenshotLoading?"⏳ Capturing...":"📸 Screenshot"),9,he),e("button",{onClick:t[2]||(t[2]=(...s)=>n.exportCsv&&n.exportCsv(...s)),class:"export-btn csv-btn",disabled:n.loading}," 📋 Export CSV ",8,ye)]),e("button",{onClick:t[3]||(t[3]=(...s)=>n.refreshData&&n.refreshData(...s)),class:"refresh-btn",disabled:n.loading},i(n.loading?"⏳":"🔄")+" Refresh ",9,xe)])]),Z(e("div",we,[e("div",De,[e("div",Ce,[t[11]||(t[11]=e("label",null,"Month",-1)),Z(e("select",{"onUpdate:modelValue":t[4]||(t[4]=s=>n.filters.month=s),onChange:t[5]||(t[5]=(...s)=>n.applyFilters&&n.applyFilters(...s))},[(r(!0),d(f,null,b(a.filterData.months,s=>(r(),d("option",{key:s.value,value:s.value},i(s.label),9,Pe))),128))],544),[[ee,n.filters.month]])]),e("div",ze,[t[12]||(t[12]=e("label",null,"Year",-1)),Z(e("select",{"onUpdate:modelValue":t[6]||(t[6]=s=>n.filters.year=s),onChange:t[7]||(t[7]=(...s)=>n.applyFilters&&n.applyFilters(...s))},[(r(!0),d(f,null,b(a.filterData.years,s=>(r(),d("option",{key:s.value,value:s.value},i(s.label),9,Se))),128))],544),[[ee,n.filters.year]])]),e("div",Te,[t[14]||(t[14]=e("label",null,"Department",-1)),Z(e("select",{"onUpdate:modelValue":t[8]||(t[8]=s=>n.filters.department_id=s),onChange:t[9]||(t[9]=(...s)=>n.applyFilters&&n.applyFilters(...s))},[t[13]||(t[13]=e("option",{value:""},"All Departments",-1)),(r(!0),d(f,null,b(a.filterData.departments,s=>(r(),d("option",{key:s.id,value:s.id},i(s.name),9,Fe))),128))],544),[[ee,n.filters.department_id]])])])],512),[[ne,n.showFilters]]),e("div",Re,[e("span",Ee,"Last updated: "+i(n.formatDateTime(a.lastUpdated)),1)])]),n.loading?(r(),d("div",Ne,t[15]||(t[15]=[e("div",{class:"loading-spinner"},[e("div",{class:"spinner"}),e("p",null,"Loading KPI Data...")],-1)]))):(r(),d("div",Ae,[e("section",je,[t[20]||(t[20]=e("h2",{class:"section-title"},"📊 Training Delivery Overview",-1)),e("div",Ue,[e("div",Le,[t[16]||(t[16]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"📚"),e("span",{class:"kpi-title"},"Courses Delivered")],-1)),e("div",Me,i(((W=a.kpiData.delivery_overview)==null?void 0:W.courses_delivered)||0),1)]),e("div",Ie,[t[17]||(t[17]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"👥"),e("span",{class:"kpi-title"},"Total Enrolled")],-1)),e("div",Oe,i(((H=a.kpiData.delivery_overview)==null?void 0:H.total_enrolled)||0),1)]),e("div",qe,[t[18]||(t[18]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"🎯"),e("span",{class:"kpi-title"},"Active Participants")],-1)),e("div",Ke,i(((G=a.kpiData.delivery_overview)==null?void 0:G.active_participants)||0),1)]),e("div",Ve,[t[19]||(t[19]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"✅"),e("span",{class:"kpi-title"},"Completion Rate")],-1)),e("div",Be,i(((Y=a.kpiData.delivery_overview)==null?void 0:Y.completion_rate)||0)+"%",1)])])]),e("section",Qe,[t[25]||(t[25]=e("h2",{class:"section-title"},"🎯 Attendance & Engagement",-1)),e("div",We,[e("div",He,[t[21]||(t[21]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"📋"),e("span",{class:"kpi-title"},"Attendance Rate")],-1)),e("div",Ge,i(((J=a.kpiData.attendance_engagement)==null?void 0:J.average_attendance_rate)||0)+"%",1)]),e("div",Ye,[t[22]||(t[22]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"⏱️"),e("span",{class:"kpi-title"},"Avg Time Spent")],-1)),e("div",Je,i(((h=a.kpiData.attendance_engagement)==null?void 0:h.average_time_spent)||0)+"h",1)]),e("div",Xe,[t[23]||(t[23]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"🕐"),e("span",{class:"kpi-title"},"Clock Consistency")],-1)),e("div",Ze,i(((X=a.kpiData.attendance_engagement)==null?void 0:X.clocking_consistency)||0)+"%",1)]),e("div",$e,[t[24]||(t[24]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"💯"),e("span",{class:"kpi-title"},"Engagement Score")],-1)),e("div",et,i(((l=a.kpiData.attendance_engagement)==null?void 0:l.engagement_score)||0)+"%",1)])])]),e("section",tt,[t[30]||(t[30]=e("h2",{class:"section-title"},"📈 Learning Outcomes",-1)),e("div",st,[e("div",it,[t[26]||(t[26]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"✅"),e("span",{class:"kpi-title"},"Quiz Pass Rate")],-1)),e("div",at,i(((c=a.kpiData.learning_outcomes)==null?void 0:c.quiz_pass_rate)||0)+"%",1)]),e("div",nt,[t[27]||(t[27]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"❌"),e("span",{class:"kpi-title"},"Quiz Fail Rate")],-1)),e("div",ot,i(((p=a.kpiData.learning_outcomes)==null?void 0:p.quiz_fail_rate)||0)+"%",1)]),e("div",lt,[t[28]||(t[28]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"📊"),e("span",{class:"kpi-title"},"Average Score")],-1)),e("div",dt,i(((v=a.kpiData.learning_outcomes)==null?void 0:v.average_quiz_score)||0)+"%",1)]),e("div",rt,[t[29]||(t[29]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"📈"),e("span",{class:"kpi-title"},"Improvement Rate")],-1)),e("div",ct,i(((m=a.kpiData.learning_outcomes)==null?void 0:m.improvement_rate)||0)+"%",1)])])]),e("section",vt,[t[37]||(t[37]=e("h2",{class:"section-title"},"⭐ Course Quality & Feedback",-1)),e("div",pt,[e("div",mt,[e("div",gt,[t[31]||(t[31]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"⭐"),e("span",{class:"kpi-title"},"Average Rating")],-1)),e("div",ut,i(((g=a.kpiData.feedback_analysis)==null?void 0:g.average_rating)||0)+"/5",1)]),e("div",ft,[t[32]||(t[32]=e("div",{class:"kpi-header"},[e("span",{class:"kpi-icon"},"💬"),e("span",{class:"kpi-title"},"Total Feedback")],-1)),e("div",bt,i(((y=a.kpiData.feedback_analysis)==null?void 0:y.total_feedback_count)||0),1)])]),e("div",kt,[t[36]||(t[36]=e("h3",null,"Feedback Sentiment",-1)),e("div",_t,[e("div",ht,[t[33]||(t[33]=e("span",{class:"sentiment-label"},"😊 Positive:",-1)),e("span",yt,i(((w=(x=a.kpiData.feedback_analysis)==null?void 0:x.feedback_sentiment)==null?void 0:w.positive)||0)+"%",1)]),e("div",xt,[t[34]||(t[34]=e("span",{class:"sentiment-label"},"😐 Neutral:",-1)),e("span",wt,i(((C=(D=a.kpiData.feedback_analysis)==null?void 0:D.feedback_sentiment)==null?void 0:C.neutral)||0)+"%",1)]),e("div",Dt,[t[35]||(t[35]=e("span",{class:"sentiment-label"},"😞 Negative:",-1)),e("span",Ct,i(((z=(P=a.kpiData.feedback_analysis)==null?void 0:P.feedback_sentiment)==null?void 0:z.negative)||0)+"%",1)])])])])]),e("section",Pt,[t[46]||(t[46]=e("h2",{class:"section-title"},"🏆 Course Performance Analysis",-1)),e("div",zt,[e("div",St,[t[40]||(t[40]=e("h3",null,"🥇 Top-Performing Courses",-1)),t[41]||(t[41]=e("p",{class:"subtitle"},"Based on rating & completion",-1)),e("div",Tt,[e("table",Ft,[t[39]||(t[39]=e("thead",null,[e("tr",null,[e("th",null,"Course Name"),e("th",null,"Rating"),e("th",null,"Completion %")])],-1)),e("tbody",null,[(r(!0),d(f,null,b(((S=a.kpiData.performance_analysis)==null?void 0:S.top_performing_courses)||[],s=>(r(),d("tr",{key:s.id,class:"table-row"},[e("td",Rt,i(s.name),1),e("td",Et,i(s.rating)+"/5",1),e("td",Nt,i(s.completion_rate)+"%",1)]))),128)),(F=(T=a.kpiData.performance_analysis)==null?void 0:T.top_performing_courses)!=null&&F.length?$("",!0):(r(),d("tr",At,t[38]||(t[38]=[e("td",{colspan:"3",class:"no-data"},"No data available",-1)])))])])])]),e("div",jt,[t[44]||(t[44]=e("h3",null,"⚠️ Courses Needing Improvement",-1)),t[45]||(t[45]=e("p",{class:"subtitle"},"Based on dropout or low ratings",-1)),e("div",Ut,[e("table",Lt,[t[43]||(t[43]=e("thead",null,[e("tr",null,[e("th",null,"Course Name"),e("th",null,"Rating"),e("th",null,"Issues")])],-1)),e("tbody",null,[(r(!0),d(f,null,b(((R=a.kpiData.performance_analysis)==null?void 0:R.courses_needing_improvement)||[],s=>(r(),d("tr",{key:s.name,class:"table-row improvement-needed"},[e("td",Mt,i(s.name),1),e("td",It,i(s.rating||"N/A"),1),e("td",Ot,[(r(!0),d(f,null,b(s.issues,ie=>(r(),d("span",{key:ie,class:"issue-tag"},i(ie),1))),128))])]))),128)),(N=(E=a.kpiData.performance_analysis)==null?void 0:E.courses_needing_improvement)!=null&&N.length?$("",!0):(r(),d("tr",qt,t[42]||(t[42]=[e("td",{colspan:"3",class:"no-data"},"No courses needing improvement",-1)])))])])])])])]),e("section",Kt,[t[55]||(t[55]=e("h2",{class:"section-title"},"👤 User Performance Analysis",-1)),e("div",Vt,[e("div",Bt,[t[49]||(t[49]=e("h3",null,"🌟 Top-Performing Users",-1)),t[50]||(t[50]=e("p",{class:"subtitle"},"Based on evaluation system scores",-1)),e("div",Qt,[e("table",Wt,[t[48]||(t[48]=e("thead",null,[e("tr",null,[e("th",null,"User Name"),e("th",null,"Score %"),e("th",null,"Courses Completed")])],-1)),e("tbody",null,[(r(!0),d(f,null,b(((A=a.kpiData.performance_analysis)==null?void 0:A.top_performing_users)||[],s=>(r(),d("tr",{key:s.name,class:"table-row top-performer"},[e("td",Ht,i(s.name),1),e("td",Gt,i(s.score)+"%",1),e("td",Yt,i(s.courses_completed||0),1)]))),128)),(U=(j=a.kpiData.performance_analysis)==null?void 0:j.top_performing_users)!=null&&U.length?$("",!0):(r(),d("tr",Jt,t[47]||(t[47]=[e("td",{colspan:"3",class:"no-data"},"No data available",-1)])))])])])]),e("div",Xt,[t[53]||(t[53]=e("h3",null,"📈 Users Needing Support",-1)),t[54]||(t[54]=e("p",{class:"subtitle"},"Based on evaluation system scores",-1)),e("div",Zt,[e("table",$t,[t[52]||(t[52]=e("thead",null,[e("tr",null,[e("th",null,"User Name"),e("th",null,"Score %"),e("th",null,"Incomplete Courses")])],-1)),e("tbody",null,[(r(!0),d(f,null,b(((L=a.kpiData.performance_analysis)==null?void 0:L.low_performing_users)||[],s=>(r(),d("tr",{key:s.name,class:"table-row needs-support"},[e("td",es,i(s.name),1),e("td",ts,i(s.score)+"%",1),e("td",ss,i(s.courses_incomplete||0),1)]))),128)),(I=(M=a.kpiData.performance_analysis)==null?void 0:M.low_performing_users)!=null&&I.length?$("",!0):(r(),d("tr",is,t[51]||(t[51]=[e("td",{colspan:"3",class:"no-data"},"No users needing support",-1)])))])])])])])]),e("section",as,[t[60]||(t[60]=e("h2",{class:"section-title"},"📈 Monthly Engagement Trend",-1)),e("div",ns,[e("div",os,[e("div",ls,[t[56]||(t[56]=e("div",{class:"trend-header"},[e("span",{class:"trend-icon"},"📊"),e("span",{class:"trend-title"},"Current Month Engagement")],-1)),e("div",ds,i(((O=a.kpiData.engagement_trends)==null?void 0:O.current_month_engagement)||0)+"%",1),e("div",rs,i(((q=a.kpiData.period)==null?void 0:q.period_name)||"Current Period"),1)]),e("div",cs,[t[57]||(t[57]=e("div",{class:"trend-header"},[e("span",{class:"trend-icon"},"📉"),e("span",{class:"trend-title"},"Previous Month Engagement")],-1)),e("div",vs,i(((K=a.kpiData.engagement_trends)==null?void 0:K.previous_month_engagement)||0)+"%",1),t[58]||(t[58]=e("div",{class:"trend-label"},"Previous Period",-1))]),e("div",ps,[t[59]||(t[59]=e("div",{class:"trend-header"},[e("span",{class:"trend-icon"},"🔄"),e("span",{class:"trend-title"},"Trend Direction")],-1)),e("div",{class:ae(["trend-value",n.getTrendClass((V=a.kpiData.engagement_trends)==null?void 0:V.trend_direction)])},[e("span",ms,i(n.getTrendArrow((B=a.kpiData.engagement_trends)==null?void 0:B.trend_direction)),1),oe(" "+i(((u=a.kpiData.engagement_trends)==null?void 0:u.trend_direction)||"stable"),1)],2),e("div",gs,i(((_=a.kpiData.engagement_trends)==null?void 0:_.trend_percentage)||0)+"% change",1)])])])])]))])}const ks=ve(pe,[["render",us],["__scopeId","data-v-17976516"]]);export{ks as default};
