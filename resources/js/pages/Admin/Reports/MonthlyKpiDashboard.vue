<template>
    <div class="monthly-kpi-dashboard">
        <!-- 🎯 ORIGINAL DASHBOARD HEADER -->
        <div class="dashboard-header">
            <div class="header-content">
                <div class="title-section">
                    <h1 class="dashboard-title">📊 Monthly Training KPI Report</h1>
                    <p class="period-display">{{ kpiData.period?.period_name || 'Loading...' }}</p>
                </div>

                <div class="header-actions">
                    <button @click="showFilters = !showFilters" class="filter-btn" :class="{ 'active': showFilters }">
                        🔍 Filters
                    </button>

                    <!-- 🎯 Export Buttons -->
                    <div class="export-buttons">
                        <button @click="generateDirectScreenshot" class="export-btn screenshot-btn" :disabled="loading || screenshotLoading">
                            {{ screenshotLoading ? '⏳ Capturing...' : '📸 Screenshot' }}
                        </button>
                        <button @click="exportCsv" class="export-btn csv-btn" :disabled="loading">
                            📋 Export CSV
                        </button>
                    </div>

                    <button @click="refreshData" class="refresh-btn" :disabled="loading">
                        {{ loading ? '⏳' : '🔄' }} Refresh
                    </button>
                </div>
            </div>

            <!-- Filter Panel -->
            <div v-show="showFilters" class="filter-panel">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Month</label>
                        <select v-model="filters.month" @change="applyFilters">
                            <option v-for="month in filterData.months" :key="month.value" :value="month.value">
                                {{ month.label }}
                            </option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Year</label>
                        <select v-model="filters.year" @change="applyFilters">
                            <option v-for="year in filterData.years" :key="year.value" :value="year.value">
                                {{ year.label }}
                            </option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Department</label>
                        <select v-model="filters.department_id" @change="applyFilters">
                            <option value="">All Departments</option>
                            <option v-for="dept in filterData.departments" :key="dept.id" :value="dept.id">
                                {{ dept.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Last Updated -->
            <div class="update-info">
                <span class="last-updated">Last updated: {{ formatDateTime(lastUpdated) }}</span>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div v-if="loading" class="loading-overlay">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Loading KPI Data...</p>
            </div>
        </div>

        <!-- 🎯 ORIGINAL DASHBOARD CONTENT -->
        <div v-else class="dashboard-content">
            <!-- 📊 SECTION 1: Training Delivery Overview -->
            <section class="kpi-section delivery-overview">
                <h2 class="section-title">📊 Training Delivery Overview</h2>
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-icon">📚</span>
                            <span class="kpi-title">Courses Delivered</span>
                        </div>
                        <div class="kpi-value">{{ kpiData.delivery_overview?.courses_delivered || 0 }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-icon">👥</span>
                            <span class="kpi-title">Total Enrolled</span>
                        </div>
                        <div class="kpi-value">{{ kpiData.delivery_overview?.total_enrolled || 0 }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-icon">🎯</span>
                            <span class="kpi-title">Active Participants</span>
                        </div>
                        <div class="kpi-value">{{ kpiData.delivery_overview?.active_participants || 0 }}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-icon">✅</span>
                            <span class="kpi-title">Completion Rate</span>
                        </div>
                        <div class="kpi-value">{{ kpiData.delivery_overview?.completion_rate || 0 }}%</div>
                    </div>
                </div>
            </section>

            <!-- 🎯 SECTION 2: Attendance & Engagement -->
            <section class="kpi-section attendance-engagement">
                <h2 class="section-title">🎯 Attendance & Engagement</h2>
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-icon">📋</span>
                            <span class="kpi-title">Attendance Rate</span>
                        </div>
                        <div class="kpi-value">{{ kpiData.attendance_engagement?.average_attendance_rate || 0 }}%</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-icon">⏱️</span>
                            <span class="kpi-title">Avg Time Spent</span>
                        </div>
                        <div class="kpi-value">{{ kpiData.attendance_engagement?.average_time_spent || 0 }}h</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-icon">🕐</span>
                            <span class="kpi-title">Clock Consistency</span>
                        </div>
                        <div class="kpi-value">{{ kpiData.attendance_engagement?.clocking_consistency || 0 }}%</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-icon">💯</span>
                            <span class="kpi-title">Engagement Score</span>
                        </div>
                        <div class="kpi-value">{{ kpiData.attendance_engagement?.engagement_score || 0 }}%</div>
                    </div>
                </div>
            </section>

            <!-- 📈 SECTION 3: Learning Outcomes -->
            <section class="kpi-section learning-outcomes">
                <h2 class="section-title">📈 Learning Outcomes</h2>
                <div class="kpi-grid">
                    <div class="kpi-card success">
                        <div class="kpi-header">
                            <span class="kpi-icon">✅</span>
                            <span class="kpi-title">Quiz Pass Rate</span>
                        </div>
                        <div class="kpi-value">{{ kpiData.learning_outcomes?.quiz_pass_rate || 0 }}%</div>
                    </div>
                    <div class="kpi-card danger">
                        <div class="kpi-header">
                            <span class="kpi-icon">❌</span>
                            <span class="kpi-title">Quiz Fail Rate</span>
                        </div>
                        <div class="kpi-value">{{ kpiData.learning_outcomes?.quiz_fail_rate || 0 }}%</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-icon">📊</span>
                            <span class="kpi-title">Average Score</span>
                        </div>
                        <div class="kpi-value">{{ kpiData.learning_outcomes?.average_quiz_score || 0 }}%</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-icon">📈</span>
                            <span class="kpi-title">Improvement Rate</span>
                        </div>
                        <div class="kpi-value">{{ kpiData.learning_outcomes?.improvement_rate || 0 }}%</div>
                    </div>
                </div>
            </section>

            <!-- ⭐ SECTION 4: Course Quality & Feedback -->
            <section class="kpi-section feedback-analysis">
                <h2 class="section-title">⭐ Course Quality & Feedback</h2>
                <div class="feedback-grid">
                    <div class="feedback-cards">
                        <div class="kpi-card">
                            <div class="kpi-header">
                                <span class="kpi-icon">⭐</span>
                                <span class="kpi-title">Average Rating</span>
                            </div>
                            <div class="kpi-value">{{ kpiData.feedback_analysis?.average_rating || 0 }}/5</div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-header">
                                <span class="kpi-icon">💬</span>
                                <span class="kpi-title">Total Feedback</span>
                            </div>
                            <div class="kpi-value">{{ kpiData.feedback_analysis?.total_feedback_count || 0 }}</div>
                        </div>
                    </div>
                    <div class="feedback-sentiment">
                        <h3>Feedback Sentiment</h3>
                        <div class="sentiment-display">
                            <div class="sentiment-item">
                                <span class="sentiment-label">😊 Positive:</span>
                                <span class="sentiment-value">{{ kpiData.feedback_analysis?.feedback_sentiment?.positive || 0 }}%</span>
                            </div>
                            <div class="sentiment-item">
                                <span class="sentiment-label">😐 Neutral:</span>
                                <span class="sentiment-value">{{ kpiData.feedback_analysis?.feedback_sentiment?.neutral || 0 }}%</span>
                            </div>
                            <div class="sentiment-item">
                                <span class="sentiment-label">😞 Negative:</span>
                                <span class="sentiment-value">{{ kpiData.feedback_analysis?.feedback_sentiment?.negative || 0 }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 🏆 SECTION 5: Course Performance Analysis -->
            <section class="kpi-section performance-analysis">
                <h2 class="section-title">🏆 Course Performance Analysis</h2>
                <div class="performance-grid">
                    <div class="performance-table">
                        <h3>🥇 Top-Performing Courses</h3>
                        <p class="subtitle">Based on rating & completion</p>
                        <div class="table-container">
                            <table class="performance-table-content">
                                <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Rating</th>
                                    <th>Completion %</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="course in kpiData.performance_analysis?.top_performing_courses || []" :key="course.id" class="table-row">
                                    <td class="course-name">{{ course.name }}</td>
                                    <td class="rating">{{ course.rating }}/5</td>
                                    <td class="completion">{{ course.completion_rate }}%</td>
                                </tr>
                                <tr v-if="!kpiData.performance_analysis?.top_performing_courses?.length">
                                    <td colspan="3" class="no-data">No data available</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="performance-table">
                        <h3>⚠️ Courses Needing Improvement</h3>
                        <p class="subtitle">Based on dropout or low ratings</p>
                        <div class="table-container">
                            <table class="performance-table-content">
                                <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Rating</th>
                                    <th>Issues</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="course in kpiData.performance_analysis?.courses_needing_improvement || []" :key="course.name" class="table-row improvement-needed">
                                    <td class="course-name">{{ course.name }}</td>
                                    <td class="rating low">{{ course.rating || 'N/A' }}</td>
                                    <td class="issues">
                                        <span v-for="issue in course.issues" :key="issue" class="issue-tag">{{ issue }}</span>
                                    </td>
                                </tr>
                                <tr v-if="!kpiData.performance_analysis?.courses_needing_improvement?.length">
                                    <td colspan="3" class="no-data">No courses needing improvement</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 👤 SECTION 6: User Performance Analysis -->
            <section class="kpi-section user-performance">
                <h2 class="section-title">👤 User Performance Analysis</h2>
                <div class="performance-grid">
                    <div class="performance-table">
                        <h3>🌟 Top-Performing Users</h3>
                        <p class="subtitle">Based on evaluation system scores</p>
                        <div class="table-container">
                            <table class="performance-table-content">
                                <thead>
                                <tr>
                                    <th>User Name</th>
                                    <th>Score %</th>
                                    <th>Courses Completed</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="user in kpiData.performance_analysis?.top_performing_users || []" :key="user.name" class="table-row top-performer">
                                    <td class="user-name">{{ user.name }}</td>
                                    <td class="score high">{{ user.score }}%</td>
                                    <td class="courses">{{ user.courses_completed || 0 }}</td>
                                </tr>
                                <tr v-if="!kpiData.performance_analysis?.top_performing_users?.length">
                                    <td colspan="3" class="no-data">No data available</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="performance-table">
                        <h3>📈 Users Needing Support</h3>
                        <p class="subtitle">Based on evaluation system scores</p>
                        <div class="table-container">
                            <table class="performance-table-content">
                                <thead>
                                <tr>
                                    <th>User Name</th>
                                    <th>Score %</th>
                                    <th>Incomplete Courses</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="user in kpiData.performance_analysis?.low_performing_users || []" :key="user.name" class="table-row needs-support">
                                    <td class="user-name">{{ user.name }}</td>
                                    <td class="score low">{{ user.score }}%</td>
                                    <td class="courses">{{ user.courses_incomplete || 0 }}</td>
                                </tr>
                                <tr v-if="!kpiData.performance_analysis?.low_performing_users?.length">
                                    <td colspan="3" class="no-data">No users needing support</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 📈 SECTION 7: Monthly Engagement Trend -->
            <section class="kpi-section engagement-trends">
                <h2 class="section-title">📈 Monthly Engagement Trend</h2>
                <div class="trends-display">
                    <div class="trend-cards">
                        <div class="trend-card current">
                            <div class="trend-header">
                                <span class="trend-icon">📊</span>
                                <span class="trend-title">Current Month Engagement</span>
                            </div>
                            <div class="trend-value">{{ kpiData.engagement_trends?.current_month_engagement || 0 }}%</div>
                            <div class="trend-label">{{ kpiData.period?.period_name || 'Current Period' }}</div>
                        </div>
                        <div class="trend-card previous">
                            <div class="trend-header">
                                <span class="trend-icon">📉</span>
                                <span class="trend-title">Previous Month Engagement</span>
                            </div>
                            <div class="trend-value">{{ kpiData.engagement_trends?.previous_month_engagement || 0 }}%</div>
                            <div class="trend-label">Previous Period</div>
                        </div>
                        <div class="trend-card comparison">
                            <div class="trend-header">
                                <span class="trend-icon">🔄</span>
                                <span class="trend-title">Trend Direction</span>
                            </div>
                            <div class="trend-value" :class="getTrendClass(kpiData.engagement_trends?.trend_direction)">
                                <span class="trend-arrow">{{ getTrendArrow(kpiData.engagement_trends?.trend_direction) }}</span>
                                {{ kpiData.engagement_trends?.trend_direction || 'stable' }}
                            </div>
                            <div class="trend-percentage">{{ kpiData.engagement_trends?.trend_percentage || 0 }}% change</div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export default {
    name: 'MonthlyKpiDashboard',

    props: {
        kpiData: { type: Object, required: true },
        filterData: { type: Object, required: true },
        currentFilters: { type: Object, required: true },
        lastUpdated: { type: String, required: true }
    },

    setup(props) {
        const loading = ref(false)
        const showFilters = ref(false)
        const screenshotLoading = ref(false)

        const filters = reactive({
            month: props.currentFilters.month,
            year: props.currentFilters.year,
            department_id: props.currentFilters.department_id || '',
            course_id: props.currentFilters.course_id || ''
        })

        // ===============================================
        // 🎯 COMPLETELY FIXED SCREENSHOT METHOD
        // ===============================================

        /**
         * 🔥 ZERO OKLCH ERROR SCREENSHOT - SEPARATE WINDOW APPROACH
         */
        const generateDirectScreenshot = async () => {
            try {
                screenshotLoading.value = true

                // Create the clean HTML content as a string
                const reportHtml = createCleanReportHtml()

                // Open a new window with clean content
                const newWindow = window.open('', 'screenshot', 'width=1200,height=800')

                if (!newWindow) {
                    throw new Error('Popup blocked. Please allow popups for this site.')
                }

                // Write clean HTML content to new window
                newWindow.document.write(reportHtml)
                newWindow.document.close()

                // Wait for content to load
                await new Promise(resolve => setTimeout(resolve, 1000))

                // Load html2canvas in the new window
                const script = newWindow.document.createElement('script')
                script.src = 'https://html2canvas.hertzen.com/dist/html2canvas.min.js'
                newWindow.document.head.appendChild(script)

                await new Promise(resolve => {
                    script.onload = resolve
                })

                // Wait a bit more for everything to render
                await new Promise(resolve => setTimeout(resolve, 500))

                // Capture screenshot in the clean window
                const canvas = await newWindow.html2canvas(newWindow.document.body, {
                    backgroundColor: '#ffffff',
                    scale: 1.5,
                    useCORS: true,
                    allowTaint: true,
                    width: newWindow.document.body.scrollWidth,
                    height: newWindow.document.body.scrollHeight
                })

                // Create download link
                const link = document.createElement('a')
                link.download = `KPI_Report_${filters.month}_${filters.year}_${new Date().toISOString().split('T')[0]}.png`
                link.href = canvas.toDataURL('image/png', 0.9)

                // Trigger download
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)

                // Close the popup window
                newWindow.close()

                console.log('✅ Screenshot generated successfully!')
                alert('📸 Screenshot downloaded successfully!')

            } catch (error) {
                console.error('❌ Screenshot failed:', error)
                alert(`Screenshot failed: ${error.message}\n\nPlease try using your browser's built-in screenshot feature.`)
            } finally {
                screenshotLoading.value = false
            }
        }

        /**
         * 🎨 CREATE CLEAN HTML REPORT (NO MODERN CSS)
         */
        const createCleanReportHtml = () => {
            return `
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
        <div class="report-subtitle">${props.kpiData.period?.period_name || 'Current Period'}</div>
        <div class="report-date">Generated on: ${new Date().toLocaleDateString()}</div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📈 Key Performance Indicators</h2>
        <div class="kpi-cards-grid">
            <div class="kpi-card blue">
                <div class="kpi-icon">📚</div>
                <div class="kpi-label">Courses Delivered</div>
                <div class="kpi-value">${props.kpiData.delivery_overview?.courses_delivered || 0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">👥</div>
                <div class="kpi-label">Total Enrolled</div>
                <div class="kpi-value">${props.kpiData.delivery_overview?.total_enrolled || 0}</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">🎯</div>
                <div class="kpi-label">Active Participants</div>
                <div class="kpi-value">${props.kpiData.delivery_overview?.active_participants || 0}</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-value">${props.kpiData.delivery_overview?.completion_rate || 0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">🎯 Engagement & Attendance</h2>
        <div class="metrics-grid">
            <div class="metric-row">
                <div class="metric-label">📋 Attendance Rate:</div>
                <div class="metric-value">${props.kpiData.attendance_engagement?.average_attendance_rate || 0}%</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">⏱️ Average Time Spent:</div>
                <div class="metric-value">${props.kpiData.attendance_engagement?.average_time_spent || 0} hours</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">💯 Engagement Score:</div>
                <div class="metric-value">${props.kpiData.attendance_engagement?.engagement_score || 0}%</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">🕐 Clock Consistency:</div>
                <div class="metric-value">${props.kpiData.attendance_engagement?.clocking_consistency || 0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📈 Learning Outcomes</h2>
        <div class="outcomes-grid">
            <div class="outcome-card success">
                <div class="outcome-label">✅ Quiz Pass Rate</div>
                <div class="outcome-value">${props.kpiData.learning_outcomes?.quiz_pass_rate || 0}%</div>
            </div>
            <div class="outcome-card danger">
                <div class="outcome-label">❌ Quiz Fail Rate</div>
                <div class="outcome-value">${props.kpiData.learning_outcomes?.quiz_fail_rate || 0}%</div>
            </div>
            <div class="outcome-card info">
                <div class="outcome-label">📊 Average Score</div>
                <div class="outcome-value">${props.kpiData.learning_outcomes?.average_quiz_score || 0}%</div>
            </div>
            <div class="outcome-card">
                <div class="outcome-label">📈 Improvement Rate</div>
                <div class="outcome-value">${props.kpiData.learning_outcomes?.improvement_rate || 0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">⭐ Course Quality & Feedback</h2>
        <div class="feedback-container">
            <div class="feedback-summary">
                <div class="feedback-card">
                    <div class="feedback-label">Average Rating</div>
                    <div class="feedback-value">${props.kpiData.feedback_analysis?.average_rating || 0}/5</div>
                </div>
                <div class="feedback-card">
                    <div class="feedback-label">Total Feedback</div>
                    <div class="feedback-value">${props.kpiData.feedback_analysis?.total_feedback_count || 0}</div>
                </div>
            </div>
            <div class="sentiment-container">
                <div class="sentiment-item positive">
                    <span class="sentiment-emoji">😊</span>
                    <span class="sentiment-text">Positive: ${props.kpiData.feedback_analysis?.feedback_sentiment?.positive || 0}%</span>
                </div>
                <div class="sentiment-item neutral">
                    <span class="sentiment-emoji">😐</span>
                    <span class="sentiment-text">Neutral: ${props.kpiData.feedback_analysis?.feedback_sentiment?.neutral || 0}%</span>
                </div>
                <div class="sentiment-item negative">
                    <span class="sentiment-emoji">😞</span>
                    <span class="sentiment-text">Negative: ${props.kpiData.feedback_analysis?.feedback_sentiment?.negative || 0}%</span>
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
                    ${(props.kpiData.performance_analysis?.top_performing_courses || []).slice(0, 5).map((course, index) => `
                        <div class="table-row">
                            <div class="table-cell">${index + 1}. ${course.name}</div>
                            <div class="table-cell">${course.rating}/5</div>
                            <div class="table-cell">${course.completion_rate}%</div>
                        </div>
                    `).join('') || '<div class="table-row"><div class="table-cell">No data available</div><div class="table-cell">-</div><div class="table-cell">-</div></div>'}
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
                    ${(props.kpiData.performance_analysis?.top_performing_users || []).slice(0, 5).map((user, index) => `
                        <div class="table-row">
                            <div class="table-cell">${index + 1}. ${user.name}</div>
                            <div class="table-cell">${user.score}%</div>
                            <div class="table-cell">${user.courses_completed || 0}</div>
                        </div>
                    `).join('') || '<div class="table-row"><div class="table-cell">No data available</div><div class="table-cell">-</div><div class="table-cell">-</div></div>'}
                </div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📊 Monthly Engagement Trend</h2>
        <div class="trend-container">
            <div class="trend-card current">
                <div class="trend-label">Current Month</div>
                <div class="trend-value">${props.kpiData.engagement_trends?.current_month_engagement || 0}%</div>
                <div class="trend-period">${props.kpiData.period?.period_name}</div>
            </div>
            <div class="trend-arrow">
                <div class="arrow-icon">${getTrendArrow(props.kpiData.engagement_trends?.trend_direction)}</div>
                <div class="trend-direction">${props.kpiData.engagement_trends?.trend_direction || 'stable'}</div>
            </div>
            <div class="trend-card">
                <div class="trend-label">Previous Month</div>
                <div class="trend-value">${props.kpiData.engagement_trends?.previous_month_engagement || 0}%</div>
                <div class="trend-period">${props.kpiData.engagement_trends?.trend_percentage || 0}% change</div>
            </div>
        </div>
    </div>

    <div class="report-footer">
        <div class="footer-content">
            <div class="footer-line"><strong>Report Period:</strong> ${props.kpiData.period?.period_name || 'Current Period'}</div>
            <div class="footer-line"><strong>Generated:</strong> ${new Date().toLocaleString()}</div>
            <div class="footer-line"><strong>System:</strong> Training Management Platform</div>
        </div>
    </div>
</body>
</html>`
        }

        // ===============================================
        // 🔧 OTHER METHODS (Keep all existing methods)
        // ===============================================

        const applyFilters = async () => {
            loading.value = true
            try {
                await router.get(route('admin.reports.monthly-kpi'),
                    Object.fromEntries(Object.entries(filters).filter(([key, value]) => value !== '')),
                    { preserveState: true, preserveScroll: true }
                )
            } catch (error) {
                console.error('Error applying filters:', error)
                alert('Error applying filters. Please try again.')
            } finally {
                loading.value = false
            }
        }

        const refreshData = () => { window.location.reload() }

        const exportCsv = () => {
            try {
                loading.value = true
                const params = new URLSearchParams()
                Object.entries(filters).forEach(([key, value]) => {
                    if (value !== '' && value !== null && value !== undefined) {
                        params.append(key, value)
                    }
                })
                const url = route('admin.reports.export-monthly-kpi-csv') + '?' + params.toString()
                window.open(url, '_blank')
            } catch (error) {
                console.error('Error exporting CSV:', error)
                alert('Error exporting CSV. Please try again.')
            } finally {
                setTimeout(() => { loading.value = false }, 1000)
            }
        }

        const formatDateTime = (dateTime) => {
            if (!dateTime) return 'Unknown'
            return new Date(dateTime).toLocaleString()
        }

        const getTrendClass = (direction) => {
            switch (direction) {
                case 'up': return 'trend-up'
                case 'down': return 'trend-down'
                default: return 'trend-stable'
            }
        }

        const getTrendArrow = (direction) => {
            switch (direction) {
                case 'up': return '↗️'
                case 'down': return '↘️'
                default: return '➡️'
            }
        }

        const hasData = computed(() => {
            return props.kpiData && Object.keys(props.kpiData).length > 0
        })

        onMounted(() => {
            console.log('🎯 Monthly KPI Dashboard mounted')
        })

        return {
            loading, showFilters, filters, screenshotLoading,
            applyFilters, refreshData, exportCsv, formatDateTime,
            getTrendClass, getTrendArrow, generateDirectScreenshot, hasData
        }
    }
}
</script>

<style scoped>
/* =============================================== */
/* 🎨 DASHBOARD STYLES (Keep all your existing styles) */
/* =============================================== */

.monthly-kpi-dashboard {
    min-height: 100vh;
    background-color: #f9fafb;
}

/* Header Styles */
.dashboard-header {
    background: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
    z-index: 40;
}

.header-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.dashboard-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 0.25rem 0;
}

.period-display {
    font-size: 0.875rem;
    color: #6b7280;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.filter-btn {
    padding: 0.5rem 1rem;
    background-color: #dbeafe;
    color: #1d4ed8;
    border-radius: 0.5rem;
    border: 1px solid #93c5fd;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-btn:hover {
    background-color: #bfdbfe;
}

.filter-btn.active {
    background-color: #1d4ed8;
    color: white;
}

/* Export Buttons */
.export-buttons {
    display: flex;
    gap: 0.5rem;
}

.export-btn {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.export-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.screenshot-btn {
    background-color: #f3e8ff;
    color: #7c3aed;
    border-color: #c4b5fd;
}

.screenshot-btn:hover:not(:disabled) {
    background-color: #e9d5ff;
}

.csv-btn {
    background-color: #f0f9ff;
    color: #0369a1;
    border-color: #bae6fd;
}

.csv-btn:hover:not(:disabled) {
    background-color: #e0f2fe;
}

.refresh-btn {
    padding: 0.5rem 1rem;
    background-color: #f3f4f6;
    color: #374151;
    border-radius: 0.5rem;
    border: 1px solid #d1d5db;
    cursor: pointer;
    transition: all 0.2s;
}

.refresh-btn:hover:not(:disabled) {
    background-color: #e5e7eb;
}

.refresh-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Filter Panel */
.filter-panel {
    border-top: 1px solid #e5e7eb;
    background-color: #f9fafb;
    padding: 1rem;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    max-width: 1280px;
    margin: 0 auto;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.filter-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

.filter-group select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background-color: white;
    font-size: 0.875rem;
}

.filter-group select:focus {
    outline: none;
    box-shadow: 0 0 0 2px #3b82f6;
    border-color: #3b82f6;
}

/* Update Info */
.update-info {
    border-top: 1px solid #e5e7eb;
    padding: 0.5rem 1rem;
    background-color: #f9fafb;
    font-size: 0.75rem;
    color: #6b7280;
    text-align: center;
    max-width: 1280px;
    margin: 0 auto;
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
}

.loading-spinner {
    background-color: white;
    border-radius: 0.5rem;
    padding: 2rem;
    text-align: center;
}

.spinner {
    width: 3rem;
    height: 3rem;
    border: 2px solid #e5e7eb;
    border-top: 2px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Dashboard Content */
.dashboard-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* KPI Sections */
.kpi-section {
    background-color: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    padding: 1.5rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
}

/* KPI Grids */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.kpi-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
}

.kpi-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.kpi-card.success {
    border-left: 4px solid #10b981;
}

.kpi-card.danger {
    border-left: 4px solid #ef4444;
}

.kpi-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.kpi-icon {
    font-size: 1.5rem;
}

.kpi-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
}

.kpi-value {
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.5rem;
}

/* Feedback Grid */
.feedback-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1.5rem;
}

.feedback-cards {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.feedback-sentiment h3 {
    font-size: 1.125rem;
    font-weight: 500;
    color: #111827;
    margin-bottom: 1rem;
}

.sentiment-display {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.sentiment-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background-color: #f9fafb;
    border-radius: 0.375rem;
}

.sentiment-label {
    font-weight: 500;
}

.sentiment-value {
    font-weight: 600;
    color: #3b82f6;
}

/* Performance Analysis */
.performance-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.performance-table {
    background-color: #f9fafb;
    border-radius: 0.5rem;
    padding: 1rem;
}

.performance-table h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 0.25rem 0;
}

.subtitle {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0 0 1rem 0;
}

.table-container {
    background-color: white;
    border-radius: 0.375rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.performance-table-content {
    width: 100%;
    border-collapse: collapse;
}

.performance-table-content th {
    background-color: #f3f4f6;
    padding: 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.performance-table-content td {
    padding: 0.75rem;
    font-size: 0.875rem;
    border-bottom: 1px solid #f3f4f6;
}

.table-row:last-child td {
    border-bottom: none;
}

.table-row:hover {
    background-color: #f9fafb;
}

.course-name,
.user-name {
    font-weight: 500;
    color: #111827;
}

.rating.high,
.score.high {
    color: #059669;
    font-weight: 600;
}

.rating.low,
.score.low {
    color: #dc2626;
    font-weight: 600;
}

.issue-tag {
    display: inline-block;
    background-color: #fef3c7;
    color: #92400e;
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    margin-right: 0.25rem;
}

.no-data {
    text-align: center;
    color: #6b7280;
    font-style: italic;
}

.top-performer {
    background-color: #f0fdf4;
}

.improvement-needed {
    background-color: #fef3c7;
}

.needs-support {
    background-color: #fef2f2;
}

/* Engagement Trends */
.trends-display {
    max-width: 900px;
}

.trend-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.trend-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
}

.trend-card.current {
    border-left: 4px solid #3b82f6;
}

.trend-card.previous {
    border-left: 4px solid #6b7280;
}

.trend-card.comparison {
    border-left: 4px solid #8b5cf6;
}

.trend-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.trend-icon {
    font-size: 1.25rem;
}

.trend-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
}

.trend-value {
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.25rem;
}

.trend-value.trend-up {
    color: #059669;
}

.trend-value.trend-down {
    color: #dc2626;
}

.trend-value.trend-stable {
    color: #6b7280;
}

.trend-label {
    font-size: 0.875rem;
    color: #6b7280;
}

.trend-percentage {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.trend-arrow {
    margin-right: 0.25rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 1rem;
    }

    .header-actions {
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
    }

    .export-buttons {
        flex-direction: column;
        width: 100%;
    }

    .export-btn {
        justify-content: center;
    }

    .kpi-grid {
        grid-template-columns: 1fr;
    }

    .feedback-grid,
    .performance-grid {
        grid-template-columns: 1fr;
    }

    .trend-cards {
        grid-template-columns: 1fr;
    }

    .performance-table-content th,
    .performance-table-content td {
        padding: 0.5rem;
        font-size: 0.75rem;
    }
}
</style>
