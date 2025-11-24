import{bo as Ke,g as Be,o as r,w as Qe,e,a as n,s as te,t as a,n as qe,F as u,i as x,v as ae,ac as We,h as ie,f as $e,r as oe,a1 as He,c as Ge,A as Ye,W as Je}from"./app-MXdxrpBP.js";import{_ as Xe}from"./AdminLayout.vue_vue_type_script_setup_true_lang-Cko1X1nL.js";import{_ as Ze}from"./_plugin-vue_export-helper-BDH3hSXr.js";import"./AppLayout.vue_vue_type_script_setup_true_lang-tiastS8M.js";import"./Button.vue_vue_type_script_setup_true_lang-BKr8K4x3.js";import"./index-s_lgDGGZ.js";import"./index-DAJ2Iuju.js";import"./AppLogoIcon.vue_vue_type_script_setup_true_lang-DOvyjOLF.js";import"./clock-3FpBgrtN.js";const et={name:"MonthlyKpiDashboard",components:{AdminLayout:Xe},props:{kpiData:{type:Object,required:!0},filterData:{type:Object,required:!0},currentFilters:{type:Object,required:!0},lastUpdated:{type:String,required:!0}},setup(i){const t=oe(!1),o=oe(!1),d=oe(!1),h=He({month:i.currentFilters.month,year:i.currentFilters.year,department_id:i.currentFilters.department_id||"",course_id:i.currentFilters.course_id||""}),de=async()=>{try{d.value=!0;const l=se(),c=window.open("","screenshot","width=1200,height=800");if(!c)throw new Error("Popup blocked. Please allow popups for this site.");c.document.write(l),c.document.close(),await new Promise(b=>setTimeout(b,1e3));const g=c.document.createElement("script");g.src="https://html2canvas.hertzen.com/dist/html2canvas.min.js",c.document.head.appendChild(g),await new Promise(b=>{g.onload=b}),await new Promise(b=>setTimeout(b,500));const p=await c.html2canvas(c.document.body,{backgroundColor:"#ffffff",scale:1.5,useCORS:!0,allowTaint:!0,width:c.document.body.scrollWidth,height:c.document.body.scrollHeight}),m=document.createElement("a");m.download=`KPI_Report_${h.month}_${h.year}_${new Date().toISOString().split("T")[0]}.png`,m.href=p.toDataURL("image/png",.9),document.body.appendChild(m),m.click(),document.body.removeChild(m),c.close(),console.log("✅ Screenshot generated successfully!"),alert("📸 Screenshot downloaded successfully!")}catch(l){console.error("❌ Screenshot failed:",l),alert(`Screenshot failed: ${l.message}

Please try using your browser's built-in screenshot feature.`)}finally{d.value=!1}},se=()=>{var l,c,g,p,m,b,_,y,w,D,C,P,S,z,T,A,F,R,N,E,j,L,O,M,U,I,V,q,K,B,Q,W,$,s,H,le,re,ne,ce,ve,pe,ge,me,be,ue,xe,fe,he,ke,_e,ye,we,De,Ce,Pe,Se,ze,Te,Ae,Fe,Re,Ne,Ee,je,Le,Oe,Me,Ue,Ie,Ve;return`
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

        .kpi-card.danger {
            border-color: #dc3545;
            background: #f8d7da;
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
        <div class="report-subtitle">${((l=i.kpiData.period)==null?void 0:l.period_name)||"Current Period"}</div>
        <div class="report-date">Generated on: ${new Date().toLocaleDateString()}</div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📈 Key Performance Indicators</h2>
        <div class="kpi-cards-grid">
            <div class="kpi-card blue">
                <div class="kpi-icon">📚</div>
                <div class="kpi-label">Courses Delivered</div>
                <div class="kpi-value">${((c=i.kpiData.delivery_overview)==null?void 0:c.courses_delivered)||0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">👥</div>
                <div class="kpi-label">Total Enrolled</div>
                <div class="kpi-value">${((g=i.kpiData.delivery_overview)==null?void 0:g.total_enrolled)||0}</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">🎯</div>
                <div class="kpi-label">Active Participants</div>
                <div class="kpi-value">${((p=i.kpiData.delivery_overview)==null?void 0:p.active_participants)||0}</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-value">${((m=i.kpiData.delivery_overview)==null?void 0:m.completion_rate)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">💻 Online Course Analytics</h2>
        <div class="kpi-cards-grid" style="grid-template-columns: repeat(5, 1fr);">
            <div class="kpi-card blue">
                <div class="kpi-icon">💻</div>
                <div class="kpi-label">Online Courses</div>
                <div class="kpi-value">${((_=(b=i.kpiData.online_course_analytics)==null?void 0:b.delivery)==null?void 0:_.online_courses_delivered)||0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">📝</div>
                <div class="kpi-label">Enrollments</div>
                <div class="kpi-value">${((w=(y=i.kpiData.online_course_analytics)==null?void 0:y.delivery)==null?void 0:w.online_enrollments)||0}</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Completed</div>
                <div class="kpi-value">${((C=(D=i.kpiData.online_course_analytics)==null?void 0:D.delivery)==null?void 0:C.online_completed)||0}</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">📊</div>
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-value">${((S=(P=i.kpiData.online_course_analytics)==null?void 0:P.delivery)==null?void 0:S.online_completion_rate)||0}%</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon">👥</div>
                <div class="kpi-label">Active Learners</div>
                <div class="kpi-value">${((T=(z=i.kpiData.online_course_analytics)==null?void 0:z.delivery)==null?void 0:T.active_online_learners)||0}</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">🎯 Engagement & Attendance</h2>
        <div class="metrics-grid">
            <div class="metric-row">
                <div class="metric-label">📋 Attendance Rate:</div>
                <div class="metric-value">${((A=i.kpiData.attendance_engagement)==null?void 0:A.average_attendance_rate)||0}%</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">⏱️ Average Time Spent:</div>
                <div class="metric-value">${((F=i.kpiData.attendance_engagement)==null?void 0:F.average_time_spent)||0} hours</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">💯 Engagement Score:</div>
                <div class="metric-value">${((R=i.kpiData.attendance_engagement)==null?void 0:R.engagement_score)||0}%</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">🕐 Clock Consistency:</div>
                <div class="metric-value">${((N=i.kpiData.attendance_engagement)==null?void 0:N.clocking_consistency)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">🎥 Video Engagement Metrics</h2>
        <div class="kpi-cards-grid">
            <div class="kpi-card blue">
                <div class="kpi-icon">▶️</div>
                <div class="kpi-label">Videos Watched</div>
                <div class="kpi-value">${((j=(E=i.kpiData.online_course_analytics)==null?void 0:E.video_engagement)==null?void 0:j.total_videos_watched)||0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Avg Completion</div>
                <div class="kpi-value">${((O=(L=i.kpiData.online_course_analytics)==null?void 0:L.video_engagement)==null?void 0:O.avg_video_completion)||0}%</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">⏱️</div>
                <div class="kpi-label">Watch Time</div>
                <div class="kpi-value">${((U=(M=i.kpiData.online_course_analytics)==null?void 0:M.video_engagement)==null?void 0:U.total_watch_time_hours)||0}h</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">🔄</div>
                <div class="kpi-label">Replays</div>
                <div class="kpi-value">${((V=(I=i.kpiData.online_course_analytics)==null?void 0:I.video_engagement)==null?void 0:V.video_replay_count)||0}</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📈 Learning Outcomes</h2>
        <div class="outcomes-grid">
            <div class="outcome-card success">
                <div class="outcome-label">✅ Quiz Pass Rate</div>
                <div class="outcome-value">${((q=i.kpiData.learning_outcomes)==null?void 0:q.quiz_pass_rate)||0}%</div>
            </div>
            <div class="outcome-card danger">
                <div class="outcome-label">❌ Quiz Fail Rate</div>
                <div class="outcome-value">${((K=i.kpiData.learning_outcomes)==null?void 0:K.quiz_fail_rate)||0}%</div>
            </div>
            <div class="outcome-card info">
                <div class="outcome-label">📊 Average Score</div>
                <div class="outcome-value">${((B=i.kpiData.learning_outcomes)==null?void 0:B.average_quiz_score)||0}%</div>
            </div>
            <div class="outcome-card">
                <div class="outcome-label">📈 Improvement Rate</div>
                <div class="outcome-value">${((Q=i.kpiData.learning_outcomes)==null?void 0:Q.improvement_rate)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📚 Online Module Progress</h2>
        <div class="kpi-cards-grid">
            <div class="kpi-card blue">
                <div class="kpi-icon">📚</div>
                <div class="kpi-label">Total Modules</div>
                <div class="kpi-value">${(($=(W=i.kpiData.online_course_analytics)==null?void 0:W.module_progress)==null?void 0:$.total_modules)||0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Completed</div>
                <div class="kpi-value">${((H=(s=i.kpiData.online_course_analytics)==null?void 0:s.module_progress)==null?void 0:H.completed_modules)||0}</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">👤</div>
                <div class="kpi-label">Avg Per User</div>
                <div class="kpi-value">${((re=(le=i.kpiData.online_course_analytics)==null?void 0:le.module_progress)==null?void 0:re.avg_modules_per_user)||0}</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">📈</div>
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-value">${((ce=(ne=i.kpiData.online_course_analytics)==null?void 0:ne.module_progress)==null?void 0:ce.module_completion_rate)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">⭐ Course Quality & Feedback</h2>
        <div class="feedback-container">
            <div class="feedback-summary">
                <div class="feedback-card">
                    <div class="feedback-label">Average Rating</div>
                    <div class="feedback-value">${((ve=i.kpiData.feedback_analysis)==null?void 0:ve.average_rating)||0}/5</div>
                </div>
                <div class="feedback-card">
                    <div class="feedback-label">Total Feedback</div>
                    <div class="feedback-value">${((pe=i.kpiData.feedback_analysis)==null?void 0:pe.total_feedback_count)||0}</div>
                </div>
            </div>
            <div class="sentiment-container">
                <div class="sentiment-item positive">
                    <span class="sentiment-emoji">😊</span>
                    <span class="sentiment-text">Positive: ${((me=(ge=i.kpiData.feedback_analysis)==null?void 0:ge.feedback_sentiment)==null?void 0:me.positive)||0}%</span>
                </div>
                <div class="sentiment-item neutral">
                    <span class="sentiment-emoji">😐</span>
                    <span class="sentiment-text">Neutral: ${((ue=(be=i.kpiData.feedback_analysis)==null?void 0:be.feedback_sentiment)==null?void 0:ue.neutral)||0}%</span>
                </div>
                <div class="sentiment-item negative">
                    <span class="sentiment-emoji">😞</span>
                    <span class="sentiment-text">Negative: ${((fe=(xe=i.kpiData.feedback_analysis)==null?void 0:xe.feedback_sentiment)==null?void 0:fe.negative)||0}%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">⏱️ Learning Session Analytics</h2>
        <div class="kpi-cards-grid" style="grid-template-columns: repeat(5, 1fr);">
            <div class="kpi-card blue">
                <div class="kpi-icon">🎯</div>
                <div class="kpi-label">Total Sessions</div>
                <div class="kpi-value">${((ke=(he=i.kpiData.online_course_analytics)==null?void 0:he.session_analytics)==null?void 0:ke.total_sessions)||0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">⏱️</div>
                <div class="kpi-label">Avg Duration</div>
                <div class="kpi-value">${((ye=(_e=i.kpiData.online_course_analytics)==null?void 0:_e.session_analytics)==null?void 0:ye.avg_session_duration_minutes)||0}m</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">👁️</div>
                <div class="kpi-label">Attention Score</div>
                <div class="kpi-value">${((De=(we=i.kpiData.online_course_analytics)==null?void 0:we.session_analytics)==null?void 0:De.avg_attention_score)||0}%</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">⏰</div>
                <div class="kpi-label">Learning Hours</div>
                <div class="kpi-value">${((Pe=(Ce=i.kpiData.online_course_analytics)==null?void 0:Ce.session_analytics)==null?void 0:Pe.total_learning_hours)||0}h</div>
            </div>
            <div class="kpi-card danger">
                <div class="kpi-icon">⚠️</div>
                <div class="kpi-label">Suspicious Activity</div>
                <div class="kpi-value">${((ze=(Se=i.kpiData.online_course_analytics)==null?void 0:Se.session_analytics)==null?void 0:ze.suspicious_activity_count)||0}</div>
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
                    ${(((Te=i.kpiData.performance_analysis)==null?void 0:Te.top_performing_courses)||[]).slice(0,5).map((v,f)=>`
                        <div class="table-row">
                            <div class="table-cell">${f+1}. ${v.name}</div>
                            <div class="table-cell">${v.rating}/5</div>
                            <div class="table-cell">${v.completion_rate}%</div>
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
                    ${(((Ae=i.kpiData.performance_analysis)==null?void 0:Ae.top_performing_users)||[]).slice(0,5).map((v,f)=>`
                        <div class="table-row">
                            <div class="table-cell">${f+1}. ${v.name}</div>
                            <div class="table-cell">${v.score}%</div>
                            <div class="table-cell">${v.courses_completed||0}</div>
                        </div>
                    `).join("")||'<div class="table-row"><div class="table-cell">No data available</div><div class="table-cell">-</div><div class="table-cell">-</div></div>'}
                </div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">🏆 Online Course Top Performers</h2>
        <div class="tables-container">
            <div class="performance-table-container">
                <h3 class="table-title">🥇 Top Online Courses</h3>
                <div class="simple-table">
                    <div class="table-header">
                        <div class="header-cell">Course Name</div>
                        <div class="header-cell">Completion</div>
                        <div class="header-cell">Enrolled</div>
                    </div>
                    ${(((Re=(Fe=i.kpiData.online_course_analytics)==null?void 0:Fe.top_performers)==null?void 0:Re.top_online_courses)||[]).slice(0,5).map((v,f)=>`
                        <div class="table-row">
                            <div class="table-cell">${f+1}. ${v.name}</div>
                            <div class="table-cell">${v.completion_rate}%</div>
                            <div class="table-cell">${v.enrolled}</div>
                        </div>
                    `).join("")||'<div class="table-row"><div class="table-cell">No data available</div><div class="table-cell">-</div><div class="table-cell">-</div></div>'}
                </div>
            </div>
            <div class="performance-table-container">
                <h3 class="table-title">🌟 Top Online Learners</h3>
                <div class="simple-table">
                    <div class="table-header">
                        <div class="header-cell">User Name</div>
                        <div class="header-cell">Completed</div>
                        <div class="header-cell">Progress</div>
                    </div>
                    ${(((Ee=(Ne=i.kpiData.online_course_analytics)==null?void 0:Ne.top_performers)==null?void 0:Ee.top_online_learners)||[]).slice(0,5).map((v,f)=>`
                        <div class="table-row">
                            <div class="table-cell">${f+1}. ${v.name}</div>
                            <div class="table-cell">${v.courses_completed}</div>
                            <div class="table-cell">${v.avg_progress}%</div>
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
                <div class="trend-value">${((je=i.kpiData.engagement_trends)==null?void 0:je.current_month_engagement)||0}%</div>
                <div class="trend-period">${(Le=i.kpiData.period)==null?void 0:Le.period_name}</div>
            </div>
            <div class="trend-arrow">
                <div class="arrow-icon">${k((Oe=i.kpiData.engagement_trends)==null?void 0:Oe.trend_direction)}</div>
                <div class="trend-direction">${((Me=i.kpiData.engagement_trends)==null?void 0:Me.trend_direction)||"stable"}</div>
            </div>
            <div class="trend-card">
                <div class="trend-label">Previous Month</div>
                <div class="trend-value">${((Ue=i.kpiData.engagement_trends)==null?void 0:Ue.previous_month_engagement)||0}%</div>
                <div class="trend-period">${((Ie=i.kpiData.engagement_trends)==null?void 0:Ie.trend_percentage)||0}% change</div>
            </div>
        </div>
    </div>

    <div class="report-footer">
        <div class="footer-content">
            <div class="footer-line"><strong>Report Period:</strong> ${((Ve=i.kpiData.period)==null?void 0:Ve.period_name)||"Current Period"}</div>
            <div class="footer-line"><strong>Generated:</strong> ${new Date().toLocaleString()}</div>
            <div class="footer-line"><strong>System:</strong> Training Management Platform</div>
        </div>
    </div>
</body>
</html>`},G=async()=>{t.value=!0;try{await Je.get(route("admin.reports.monthly-kpi"),Object.fromEntries(Object.entries(h).filter(([l,c])=>c!=="")),{preserveState:!0,preserveScroll:!0})}catch(l){console.error("Error applying filters:",l),alert("Error applying filters. Please try again.")}finally{t.value=!1}},Y=()=>{window.location.reload()},J=()=>{try{t.value=!0;const l=new URLSearchParams;Object.entries(h).forEach(([g,p])=>{p!==""&&p!==null&&p!==void 0&&l.append(g,p)});const c=route("admin.reports.export-monthly-kpi-csv")+"?"+l.toString();window.open(c,"_blank")}catch(l){console.error("Error exporting CSV:",l),alert("Error exporting CSV. Please try again.")}finally{setTimeout(()=>{t.value=!1},1e3)}},X=l=>l?new Date(l).toLocaleString():"Unknown",Z=l=>{switch(l){case"up":return"trend-up";case"down":return"trend-down";default:return"trend-stable"}},k=l=>{switch(l){case"up":return"↗️";case"down":return"↘️";default:return"➡️"}},ee=Ge(()=>i.kpiData&&Object.keys(i.kpiData).length>0);return Ye(()=>{console.log("🎯 Monthly KPI Dashboard mounted")}),{loading:t,showFilters:o,filters:h,screenshotLoading:d,applyFilters:G,refreshData:Y,exportCsv:J,formatDateTime:X,getTrendClass:Z,getTrendArrow:k,generateDirectScreenshot:de,hasData:ee}}},tt={class:"monthly-kpi-dashboard bg-black text-white min-h-screen dark"},it={class:"dashboard-header bg-gray-800 border-b border-gray-600 p-4 sm:p-6"},st={class:"header-content"},at={class:"title-section"},ot={class:"period-display text-gray-300 mt-2"},dt={class:"header-actions flex flex-wrap gap-3 mt-4"},lt={class:"export-buttons flex gap-2"},rt=["disabled"],nt=["disabled"],ct=["disabled"],vt={class:"filter-panel bg-gray-700 border border-gray-600 rounded-lg p-4 mt-4"},pt={class:"filter-grid grid grid-cols-1 md:grid-cols-3 gap-4"},gt={class:"filter-group"},mt=["value"],bt={class:"filter-group"},ut=["value"],xt={class:"filter-group"},ft=["value"],ht={class:"update-info mt-4 pt-4 border-t border-gray-600"},kt={class:"last-updated text-sm text-gray-300"},_t={key:0,class:"loading-overlay fixed inset-0 bg-gray-900 bg-opacity-90 flex items-center justify-center z-50"},yt={key:1,class:"dashboard-content p-4 sm:p-6 space-y-8"},wt={class:"kpi-section delivery-overview"},Dt={class:"kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"},Ct={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Pt={class:"kpi-value text-3xl font-bold text-white"},St={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},zt={class:"kpi-value text-3xl font-bold text-white"},Tt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},At={class:"kpi-value text-3xl font-bold text-white"},Ft={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Rt={class:"kpi-value text-3xl font-bold text-white"},Nt={class:"kpi-section attendance-engagement"},Et={class:"kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"},jt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Lt={class:"kpi-value text-3xl font-bold text-white"},Ot={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Mt={class:"kpi-value text-3xl font-bold text-white"},Ut={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},It={class:"kpi-value text-3xl font-bold text-white"},Vt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},qt={class:"kpi-value text-3xl font-bold text-white"},Kt={class:"kpi-section learning-outcomes"},Bt={class:"kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"},Qt={class:"kpi-card success bg-gray-800 border border-green-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-green-900/20"},Wt={class:"kpi-value text-3xl font-bold text-green-300"},$t={class:"kpi-card danger bg-gray-800 border border-red-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-red-900/20"},Ht={class:"kpi-value text-3xl font-bold text-red-300"},Gt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Yt={class:"kpi-value text-3xl font-bold text-white"},Jt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Xt={class:"kpi-value text-3xl font-bold text-white"},Zt={class:"kpi-section feedback-analysis"},ei={class:"feedback-grid grid grid-cols-1 lg:grid-cols-2 gap-8"},ti={class:"feedback-cards grid grid-cols-1 sm:grid-cols-2 gap-6"},ii={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},si={class:"kpi-value text-3xl font-bold text-white"},ai={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},oi={class:"kpi-value text-3xl font-bold text-white"},di={class:"feedback-sentiment bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},li={class:"sentiment-display space-y-3"},ri={class:"sentiment-item flex justify-between items-center"},ni={class:"sentiment-value font-bold text-green-400"},ci={class:"sentiment-item flex justify-between items-center"},vi={class:"sentiment-value font-bold text-yellow-400"},pi={class:"sentiment-item flex justify-between items-center"},gi={class:"sentiment-value font-bold text-red-400"},mi={class:"kpi-section performance-analysis"},bi={class:"performance-grid grid grid-cols-1 lg:grid-cols-2 gap-8"},ui={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},xi={class:"table-container overflow-x-auto"},fi={class:"performance-table-content w-full"},hi={class:"divide-y divide-gray-600"},ki={class:"course-name px-4 py-3 text-sm text-white"},_i={class:"rating px-4 py-3 text-sm text-white"},yi={class:"completion px-4 py-3 text-sm text-white"},wi={key:0},Di={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},Ci={class:"table-container overflow-x-auto"},Pi={class:"performance-table-content w-full"},Si={class:"divide-y divide-gray-600"},zi={class:"course-name px-4 py-3 text-sm text-white"},Ti={class:"rating low px-4 py-3 text-sm text-red-400"},Ai={class:"issues px-4 py-3"},Fi={key:0},Ri={class:"kpi-section user-performance"},Ni={class:"performance-grid grid grid-cols-1 lg:grid-cols-2 gap-8"},Ei={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},ji={class:"table-container overflow-x-auto"},Li={class:"performance-table-content w-full"},Oi={class:"divide-y divide-gray-600"},Mi={class:"user-name px-4 py-3 text-sm text-white"},Ui={class:"score high px-4 py-3 text-sm text-green-400 font-semibold"},Ii={class:"courses px-4 py-3 text-sm text-white"},Vi={key:0},qi={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},Ki={class:"table-container overflow-x-auto"},Bi={class:"performance-table-content w-full"},Qi={class:"divide-y divide-gray-600"},Wi={class:"user-name px-4 py-3 text-sm text-white"},$i={class:"score low px-4 py-3 text-sm text-yellow-400 font-semibold"},Hi={class:"courses px-4 py-3 text-sm text-white"},Gi={key:0},Yi={class:"kpi-section engagement-trends"},Ji={class:"trends-display"},Xi={class:"trend-cards grid grid-cols-1 md:grid-cols-3 gap-6"},Zi={class:"trend-card current bg-gray-800 border border-blue-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-blue-900/20"},es={class:"trend-value text-3xl font-bold text-blue-300"},ts={class:"trend-label text-sm text-gray-300 mt-2"},is={class:"trend-card previous bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},ss={class:"trend-value text-3xl font-bold text-gray-200"},as={class:"trend-card comparison bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},os={class:"trend-arrow mr-2"},ds={class:"trend-percentage text-sm text-gray-300 mt-2"};function ls(i,t,o,d,h,de){const se=Ke("AdminLayout");return r(),Be(se,{breadcrumbs:i.breadcrumbs},{default:Qe(()=>{var G,Y,J,X,Z,k,ee,l,c,g,p,m,b,_,y,w,D,C,P,S,z,T,A,F,R,N,E,j,L,O,M,U,I,V,q,K,B,Q,W,$;return[e("div",tt,[e("div",it,[e("div",st,[e("div",at,[t[10]||(t[10]=e("h1",{class:"dashboard-title text-2xl sm:text-3xl font-bold text-white"},"📊 Monthly Training KPI Report",-1)),e("p",ot,a(((G=o.kpiData.period)==null?void 0:G.period_name)||"Loading..."),1)]),e("div",dt,[e("button",{onClick:t[0]||(t[0]=s=>d.showFilters=!d.showFilters),class:qe(["filter-btn bg-gray-700 text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors",{"bg-blue-800 text-blue-200":d.showFilters}])}," 🔍 Filters ",2),e("div",lt,[e("button",{onClick:t[1]||(t[1]=(...s)=>d.generateDirectScreenshot&&d.generateDirectScreenshot(...s)),class:"export-btn screenshot-btn bg-purple-800 text-purple-200 px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50",disabled:d.loading||d.screenshotLoading},a(d.screenshotLoading?"⏳ Capturing...":"📸 Screenshot"),9,rt),e("button",{onClick:t[2]||(t[2]=(...s)=>d.exportCsv&&d.exportCsv(...s)),class:"export-btn csv-btn bg-green-800 text-green-200 px-4 py-2 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50",disabled:d.loading}," 📋 Export CSV ",8,nt)]),e("button",{onClick:t[3]||(t[3]=(...s)=>d.refreshData&&d.refreshData(...s)),class:"refresh-btn bg-indigo-800 text-indigo-200 px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50",disabled:d.loading},a(d.loading?"⏳":"🔄")+" Refresh ",9,ct)])]),te(e("div",vt,[e("div",pt,[e("div",gt,[t[11]||(t[11]=e("label",{class:"block text-sm font-medium text-gray-200 mb-2"},"Month",-1)),te(e("select",{"onUpdate:modelValue":t[4]||(t[4]=s=>d.filters.month=s),onChange:t[5]||(t[5]=(...s)=>d.applyFilters&&d.applyFilters(...s)),class:"w-full bg-gray-600 border border-gray-500 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400"},[(r(!0),n(u,null,x(o.filterData.months,s=>(r(),n("option",{key:s.value,value:s.value},a(s.label),9,mt))),128))],544),[[ae,d.filters.month]])]),e("div",bt,[t[12]||(t[12]=e("label",{class:"block text-sm font-medium text-gray-200 mb-2"},"Year",-1)),te(e("select",{"onUpdate:modelValue":t[6]||(t[6]=s=>d.filters.year=s),onChange:t[7]||(t[7]=(...s)=>d.applyFilters&&d.applyFilters(...s)),class:"w-full bg-gray-600 border border-gray-500 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400"},[(r(!0),n(u,null,x(o.filterData.years,s=>(r(),n("option",{key:s.value,value:s.value},a(s.label),9,ut))),128))],544),[[ae,d.filters.year]])]),e("div",xt,[t[14]||(t[14]=e("label",{class:"block text-sm font-medium text-gray-200 mb-2"},"Department",-1)),te(e("select",{"onUpdate:modelValue":t[8]||(t[8]=s=>d.filters.department_id=s),onChange:t[9]||(t[9]=(...s)=>d.applyFilters&&d.applyFilters(...s)),class:"w-full bg-gray-600 border border-gray-500 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400"},[t[13]||(t[13]=e("option",{value:""},"All Departments",-1)),(r(!0),n(u,null,x(o.filterData.departments,s=>(r(),n("option",{key:s.id,value:s.id},a(s.name),9,ft))),128))],544),[[ae,d.filters.department_id]])])])],512),[[We,d.showFilters]]),e("div",ht,[e("span",kt,"Last updated: "+a(d.formatDateTime(o.lastUpdated)),1)])]),d.loading?(r(),n("div",_t,t[15]||(t[15]=[e("div",{class:"loading-spinner bg-gray-800 rounded-lg p-8 text-center shadow-2xl border border-gray-600"},[e("div",{class:"spinner inline-block w-8 h-8 border-4 border-gray-600 border-t-blue-400 rounded-full animate-spin mb-4"}),e("p",{class:"text-white"},"Loading KPI Data...")],-1)]))):(r(),n("div",yt,[e("section",wt,[t[20]||(t[20]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"📊 Training Delivery Overview",-1)),e("div",Dt,[e("div",Ct,[t[16]||(t[16]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📚"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Courses Delivered")],-1)),e("div",Pt,a(((Y=o.kpiData.delivery_overview)==null?void 0:Y.courses_delivered)||0),1)]),e("div",St,[t[17]||(t[17]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"👥"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Total Enrolled")],-1)),e("div",zt,a(((J=o.kpiData.delivery_overview)==null?void 0:J.total_enrolled)||0),1)]),e("div",Tt,[t[18]||(t[18]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"🎯"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Active Participants")],-1)),e("div",At,a(((X=o.kpiData.delivery_overview)==null?void 0:X.active_participants)||0),1)]),e("div",Ft,[t[19]||(t[19]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"✅"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Completion Rate")],-1)),e("div",Rt,a(((Z=o.kpiData.delivery_overview)==null?void 0:Z.completion_rate)||0)+"%",1)])])]),e("section",Nt,[t[25]||(t[25]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"🎯 Attendance & Engagement",-1)),e("div",Et,[e("div",jt,[t[21]||(t[21]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📋"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Attendance Rate")],-1)),e("div",Lt,a(((k=o.kpiData.attendance_engagement)==null?void 0:k.average_attendance_rate)||0)+"%",1)]),e("div",Ot,[t[22]||(t[22]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"⏱️"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Avg Time Spent")],-1)),e("div",Mt,a(((ee=o.kpiData.attendance_engagement)==null?void 0:ee.average_time_spent)||0)+"h",1)]),e("div",Ut,[t[23]||(t[23]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"🕐"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Clock Consistency")],-1)),e("div",It,a(((l=o.kpiData.attendance_engagement)==null?void 0:l.clocking_consistency)||0)+"%",1)]),e("div",Vt,[t[24]||(t[24]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"💯"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Engagement Score")],-1)),e("div",qt,a(((c=o.kpiData.attendance_engagement)==null?void 0:c.engagement_score)||0)+"%",1)])])]),e("section",Kt,[t[30]||(t[30]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"📈 Learning Outcomes",-1)),e("div",Bt,[e("div",Qt,[t[26]||(t[26]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"✅"),e("span",{class:"kpi-title text-sm font-medium text-green-400"},"Quiz Pass Rate")],-1)),e("div",Wt,a(((g=o.kpiData.learning_outcomes)==null?void 0:g.quiz_pass_rate)||0)+"%",1)]),e("div",$t,[t[27]||(t[27]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"❌"),e("span",{class:"kpi-title text-sm font-medium text-red-400"},"Quiz Fail Rate")],-1)),e("div",Ht,a(((p=o.kpiData.learning_outcomes)==null?void 0:p.quiz_fail_rate)||0)+"%",1)]),e("div",Gt,[t[28]||(t[28]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📊"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Average Score")],-1)),e("div",Yt,a(((m=o.kpiData.learning_outcomes)==null?void 0:m.average_quiz_score)||0)+"%",1)]),e("div",Jt,[t[29]||(t[29]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📈"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Improvement Rate")],-1)),e("div",Xt,a(((b=o.kpiData.learning_outcomes)==null?void 0:b.improvement_rate)||0)+"%",1)])])]),e("section",Zt,[t[37]||(t[37]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"⭐ Course Quality & Feedback",-1)),e("div",ei,[e("div",ti,[e("div",ii,[t[31]||(t[31]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"⭐"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Average Rating")],-1)),e("div",si,a(((_=o.kpiData.feedback_analysis)==null?void 0:_.average_rating)||0)+"/5",1)]),e("div",ai,[t[32]||(t[32]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"💬"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Total Feedback")],-1)),e("div",oi,a(((y=o.kpiData.feedback_analysis)==null?void 0:y.total_feedback_count)||0),1)])]),e("div",di,[t[36]||(t[36]=e("h3",{class:"text-lg font-semibold text-white mb-4"},"Feedback Sentiment",-1)),e("div",li,[e("div",ri,[t[33]||(t[33]=e("span",{class:"sentiment-label text-gray-300"},"😊 Positive:",-1)),e("span",ni,a(((D=(w=o.kpiData.feedback_analysis)==null?void 0:w.feedback_sentiment)==null?void 0:D.positive)||0)+"%",1)]),e("div",ci,[t[34]||(t[34]=e("span",{class:"sentiment-label text-gray-300"},"😐 Neutral:",-1)),e("span",vi,a(((P=(C=o.kpiData.feedback_analysis)==null?void 0:C.feedback_sentiment)==null?void 0:P.neutral)||0)+"%",1)]),e("div",pi,[t[35]||(t[35]=e("span",{class:"sentiment-label text-gray-300"},"😞 Negative:",-1)),e("span",gi,a(((z=(S=o.kpiData.feedback_analysis)==null?void 0:S.feedback_sentiment)==null?void 0:z.negative)||0)+"%",1)])])])])]),e("section",mi,[t[46]||(t[46]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"🏆 Course Performance Analysis",-1)),e("div",bi,[e("div",ui,[t[40]||(t[40]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"🥇 Top-Performing Courses",-1)),t[41]||(t[41]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on rating & completion",-1)),e("div",xi,[e("table",fi,[t[39]||(t[39]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Course Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Rating"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Completion %")])],-1)),e("tbody",hi,[(r(!0),n(u,null,x(((T=o.kpiData.performance_analysis)==null?void 0:T.top_performing_courses)||[],s=>(r(),n("tr",{key:s.id,class:"table-row hover:bg-gray-700 transition-colors"},[e("td",ki,a(s.name),1),e("td",_i,a(s.rating)+"/5",1),e("td",yi,a(s.completion_rate)+"%",1)]))),128)),(F=(A=o.kpiData.performance_analysis)==null?void 0:A.top_performing_courses)!=null&&F.length?ie("",!0):(r(),n("tr",wi,t[38]||(t[38]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No data available",-1)])))])])])]),e("div",Di,[t[44]||(t[44]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"⚠️ Courses Needing Improvement",-1)),t[45]||(t[45]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on dropout or low ratings",-1)),e("div",Ci,[e("table",Pi,[t[43]||(t[43]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Course Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Rating"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Issues")])],-1)),e("tbody",Si,[(r(!0),n(u,null,x(((R=o.kpiData.performance_analysis)==null?void 0:R.courses_needing_improvement)||[],s=>(r(),n("tr",{key:s.name,class:"table-row improvement-needed hover:bg-red-900/30 transition-colors"},[e("td",zi,a(s.name),1),e("td",Ti,a(s.rating||"N/A"),1),e("td",Ai,[(r(!0),n(u,null,x(s.issues,H=>(r(),n("span",{key:H,class:"issue-tag inline-block bg-red-800 text-red-200 text-xs px-2 py-1 rounded mr-1 mb-1"},a(H),1))),128))])]))),128)),(E=(N=o.kpiData.performance_analysis)==null?void 0:N.courses_needing_improvement)!=null&&E.length?ie("",!0):(r(),n("tr",Fi,t[42]||(t[42]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No courses needing improvement",-1)])))])])])])])]),e("section",Ri,[t[55]||(t[55]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"👤 User Performance Analysis",-1)),e("div",Ni,[e("div",Ei,[t[49]||(t[49]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"🌟 Top-Performing Users",-1)),t[50]||(t[50]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on evaluation system scores",-1)),e("div",ji,[e("table",Li,[t[48]||(t[48]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"User Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Score %"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Courses Completed")])],-1)),e("tbody",Oi,[(r(!0),n(u,null,x(((j=o.kpiData.performance_analysis)==null?void 0:j.top_performing_users)||[],s=>(r(),n("tr",{key:s.name,class:"table-row top-performer hover:bg-green-900/30 transition-colors"},[e("td",Mi,a(s.name),1),e("td",Ui,a(s.score)+"%",1),e("td",Ii,a(s.courses_completed||0),1)]))),128)),(O=(L=o.kpiData.performance_analysis)==null?void 0:L.top_performing_users)!=null&&O.length?ie("",!0):(r(),n("tr",Vi,t[47]||(t[47]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No data available",-1)])))])])])]),e("div",qi,[t[53]||(t[53]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"📈 Users Needing Support",-1)),t[54]||(t[54]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on evaluation system scores",-1)),e("div",Ki,[e("table",Bi,[t[52]||(t[52]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"User Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Score %"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Incomplete Courses")])],-1)),e("tbody",Qi,[(r(!0),n(u,null,x(((M=o.kpiData.performance_analysis)==null?void 0:M.low_performing_users)||[],s=>(r(),n("tr",{key:s.name,class:"table-row needs-support hover:bg-yellow-900/30 transition-colors"},[e("td",Wi,a(s.name),1),e("td",$i,a(s.score)+"%",1),e("td",Hi,a(s.courses_incomplete||0),1)]))),128)),(I=(U=o.kpiData.performance_analysis)==null?void 0:U.low_performing_users)!=null&&I.length?ie("",!0):(r(),n("tr",Gi,t[51]||(t[51]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No users needing support",-1)])))])])])])])]),e("section",Yi,[t[60]||(t[60]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"📈 Monthly Engagement Trend",-1)),e("div",Ji,[e("div",Xi,[e("div",Zi,[t[56]||(t[56]=e("div",{class:"trend-header flex items-center mb-4"},[e("span",{class:"trend-icon text-2xl mr-3"},"📊"),e("span",{class:"trend-title text-sm font-medium text-blue-400"},"Current Month Engagement")],-1)),e("div",es,a(((V=o.kpiData.engagement_trends)==null?void 0:V.current_month_engagement)||0)+"%",1),e("div",ts,a(((q=o.kpiData.period)==null?void 0:q.period_name)||"Current Period"),1)]),e("div",is,[t[57]||(t[57]=e("div",{class:"trend-header flex items-center mb-4"},[e("span",{class:"trend-icon text-2xl mr-3"},"📉"),e("span",{class:"trend-title text-sm font-medium text-gray-300"},"Previous Month Engagement")],-1)),e("div",ss,a(((K=o.kpiData.engagement_trends)==null?void 0:K.previous_month_engagement)||0)+"%",1),t[58]||(t[58]=e("div",{class:"trend-label text-sm text-gray-300 mt-2"},"Previous Period",-1))]),e("div",as,[t[59]||(t[59]=e("div",{class:"trend-header flex items-center mb-4"},[e("span",{class:"trend-icon text-2xl mr-3"},"🔄"),e("span",{class:"trend-title text-sm font-medium text-gray-300"},"Trend Direction")],-1)),e("div",{class:qe(["trend-value text-2xl font-bold flex items-center",d.getTrendClass((B=o.kpiData.engagement_trends)==null?void 0:B.trend_direction)])},[e("span",os,a(d.getTrendArrow((Q=o.kpiData.engagement_trends)==null?void 0:Q.trend_direction)),1),$e(" "+a(((W=o.kpiData.engagement_trends)==null?void 0:W.trend_direction)||"stable"),1)],2),e("div",ds,a((($=o.kpiData.engagement_trends)==null?void 0:$.trend_percentage)||0)+"% change",1)])])])])]))])]}),_:1},8,["breadcrumbs"])}const xs=Ze(et,[["render",ls],["__scopeId","data-v-7768ad6e"]]);export{xs as default};
