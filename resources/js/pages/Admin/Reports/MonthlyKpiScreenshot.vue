<template>
    <div class="kpi-screenshot-page">
        <!-- 🎯 SCREENSHOT TOOLBAR -->
        <div class="screenshot-toolbar">
            <div class="toolbar-content">
                <h1>📸 KPI Report Screenshot</h1>
                <div class="toolbar-actions">
                    <button @click="captureScreenshot" class="screenshot-btn" :disabled="capturing">
                        {{ capturing ? '⏳ Capturing...' : '📸 Take Screenshot' }}
                    </button>
                    <a :href="backUrl" class="back-btn">← Back to Dashboard</a>
                </div>
            </div>
        </div>

        <!-- 🎯 CLEAN REPORT CONTENT -->
        <div id="screenshot-content" class="report-container">
            <!-- Report Header -->
            <div class="report-header">
                <div class="company-logo">
                    <!-- Add your company logo here if needed -->
                    <div class="logo-placeholder">🏢</div>
                </div>
                <h1 class="report-title">Monthly Training KPI Report</h1>
                <div class="report-subtitle">{{ kpiData.period?.period_name || 'Current Period' }}</div>
                <div class="report-date">Generated: {{ formatDate(new Date()) }}</div>
            </div>

            <!-- KPI Overview Cards -->
            <div class="kpi-overview">
                <h2 class="section-title">📈 Key Performance Indicators</h2>
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-icon">📚</div>
                        <div class="kpi-info">
                            <div class="kpi-label">Courses Delivered</div>
                            <div class="kpi-value">{{ kpiData.delivery_overview?.courses_delivered || 0 }}</div>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon">👥</div>
                        <div class="kpi-info">
                            <div class="kpi-label">Total Enrolled</div>
                            <div class="kpi-value">{{ kpiData.delivery_overview?.total_enrolled || 0 }}</div>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon">🎯</div>
                        <div class="kpi-info">
                            <div class="kpi-label">Active Participants</div>
                            <div class="kpi-value">{{ kpiData.delivery_overview?.active_participants || 0 }}</div>
                        </div>
                    </div>

                    <div class="kpi-card highlight">
                        <div class="kpi-icon">✅</div>
                        <div class="kpi-info">
                            <div class="kpi-label">Completion Rate</div>
                            <div class="kpi-value">{{ kpiData.delivery_overview?.completion_rate || 0 }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Engagement Metrics -->
            <div class="engagement-section">
                <h2 class="section-title">🎯 Engagement & Attendance Metrics</h2>
                <div class="metrics-table">
                    <div class="table-row header">
                        <div class="cell">Metric</div>
                        <div class="cell">Current Value</div>
                        <div class="cell">Status</div>
                    </div>
                    <div class="table-row">
                        <div class="cell">📋 Attendance Rate</div>
                        <div class="cell">{{ kpiData.attendance_engagement?.average_attendance_rate || 0 }}%</div>
                        <div class="cell">{{ getStatus(kpiData.attendance_engagement?.average_attendance_rate || 0, 80) }}</div>
                    </div>
                    <div class="table-row">
                        <div class="cell">⏱️ Average Time Spent</div>
                        <div class="cell">{{ kpiData.attendance_engagement?.average_time_spent || 0 }} hours</div>
                        <div class="cell">{{ getStatus(kpiData.attendance_engagement?.average_time_spent || 0, 20) }}</div>
                    </div>
                    <div class="table-row">
                        <div class="cell">💯 Engagement Score</div>
                        <div class="cell">{{ kpiData.attendance_engagement?.engagement_score || 0 }}%</div>
                        <div class="cell">{{ getStatus(kpiData.attendance_engagement?.engagement_score || 0, 75) }}</div>
                    </div>
                    <div class="table-row">
                        <div class="cell">🔄 Login Frequency</div>
                        <div class="cell">{{ kpiData.attendance_engagement?.login_frequency || 0 }}%</div>
                        <div class="cell">{{ getStatus(kpiData.attendance_engagement?.login_frequency || 0, 70) }}</div>
                    </div>
                </div>
            </div>

            <!-- Learning Outcomes -->
            <div class="learning-section">
                <h2 class="section-title">📈 Learning Outcomes</h2>
                <div class="outcome-cards">
                    <div class="outcome-card success">
                        <div class="outcome-header">
                            <span class="outcome-icon">✅</span>
                            <span class="outcome-label">Quiz Pass Rate</span>
                        </div>
                        <div class="outcome-value">{{ kpiData.learning_outcomes?.quiz_pass_rate || 0 }}%</div>
                    </div>

                    <div class="outcome-card warning">
                        <div class="outcome-header">
                            <span class="outcome-icon">❌</span>
                            <span class="outcome-label">Quiz Fail Rate</span>
                        </div>
                        <div class="outcome-value">{{ kpiData.learning_outcomes?.quiz_fail_rate || 0 }}%</div>
                    </div>

                    <div class="outcome-card">
                        <div class="outcome-header">
                            <span class="outcome-icon">📊</span>
                            <span class="outcome-label">Average Score</span>
                        </div>
                        <div class="outcome-value">{{ kpiData.learning_outcomes?.average_quiz_score || 0 }}%</div>
                    </div>

                    <div class="outcome-card">
                        <div class="outcome-header">
                            <span class="outcome-icon">📈</span>
                            <span class="outcome-label">Improvement Rate</span>
                        </div>
                        <div class="outcome-value">{{ kpiData.learning_outcomes?.improvement_rate || 0 }}%</div>
                    </div>
                </div>
            </div>

            <!-- Feedback Analysis -->
            <div class="feedback-section">
                <h2 class="section-title">⭐ Course Quality & Feedback</h2>
                <div class="feedback-overview">
                    <div class="feedback-summary">
                        <div class="summary-item">
                            <div class="summary-label">Average Rating</div>
                            <div class="summary-value large">{{ kpiData.feedback_analysis?.average_rating || 0 }}<span class="unit">/5</span></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Total Feedback</div>
                            <div class="summary-value">{{ kpiData.feedback_analysis?.total_feedback_count || 0 }}</div>
                        </div>
                    </div>

                    <div class="sentiment-breakdown">
                        <div class="sentiment-item positive">
                            <span class="sentiment-emoji">😊</span>
                            <span class="sentiment-label">Positive</span>
                            <span class="sentiment-percent">{{ kpiData.feedback_analysis?.feedback_sentiment?.positive || 0 }}%</span>
                        </div>
                        <div class="sentiment-item neutral">
                            <span class="sentiment-emoji">😐</span>
                            <span class="sentiment-label">Neutral</span>
                            <span class="sentiment-percent">{{ kpiData.feedback_analysis?.feedback_sentiment?.neutral || 0 }}%</span>
                        </div>
                        <div class="sentiment-item negative">
                            <span class="sentiment-emoji">😞</span>
                            <span class="sentiment-label">Negative</span>
                            <span class="sentiment-percent">{{ kpiData.feedback_analysis?.feedback_sentiment?.negative || 0 }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Tables -->
            <div class="performance-section">
                <div class="performance-grid">
                    <!-- Top Courses -->
                    <div class="performance-table">
                        <h3 class="table-title">🏆 Top Performing Courses</h3>
                        <div class="simple-table">
                            <div class="table-header">
                                <div class="header-cell">Course</div>
                                <div class="header-cell">Rating</div>
                                <div class="header-cell">Completion</div>
                            </div>
                            <div v-for="(course, index) in (kpiData.performance_analysis?.top_performing_courses || []).slice(0, 5)"
                                 :key="course.id" class="table-row">
                                <div class="table-cell">
                                    <span class="rank">{{ index + 1 }}.</span>
                                    {{ course.name }}
                                </div>
                                <div class="table-cell">{{ course.rating }}/5</div>
                                <div class="table-cell">{{ course.completion_rate }}%</div>
                            </div>
                            <div v-if="!(kpiData.performance_analysis?.top_performing_courses?.length)" class="table-row empty">
                                <div class="table-cell">No data available</div>
                                <div class="table-cell">-</div>
                                <div class="table-cell">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Users -->
                    <div class="performance-table">
                        <h3 class="table-title">🌟 Top Performing Users</h3>
                        <div class="simple-table">
                            <div class="table-header">
                                <div class="header-cell">User</div>
                                <div class="header-cell">Score</div>
                                <div class="header-cell">Completed</div>
                            </div>
                            <div v-for="(user, index) in (kpiData.performance_analysis?.top_performing_users || []).slice(0, 5)"
                                 :key="user.name" class="table-row">
                                <div class="table-cell">
                                    <span class="rank">{{ index + 1 }}.</span>
                                    {{ user.name }}
                                </div>
                                <div class="table-cell">{{ user.score }}%</div>
                                <div class="table-cell">{{ user.courses_completed || 0 }}</div>
                            </div>
                            <div v-if="!(kpiData.performance_analysis?.top_performing_users?.length)" class="table-row empty">
                                <div class="table-cell">No data available</div>
                                <div class="table-cell">-</div>
                                <div class="table-cell">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Trend -->
            <div class="trend-section">
                <h2 class="section-title">📊 Monthly Engagement Trend</h2>
                <div class="trend-comparison">
                    <div class="trend-card current">
                        <div class="trend-header">Current Month</div>
                        <div class="trend-value">{{ kpiData.engagement_trends?.current_month_engagement || 0 }}%</div>
                        <div class="trend-label">{{ kpiData.period?.period_name }}</div>
                    </div>

                    <div class="trend-arrow">
                        <span class="arrow-icon">{{ getTrendArrow(kpiData.engagement_trends?.trend_direction) }}</span>
                        <span class="trend-text">{{ kpiData.engagement_trends?.trend_direction || 'stable' }}</span>
                    </div>

                    <div class="trend-card previous">
                        <div class="trend-header">Previous Month</div>
                        <div class="trend-value">{{ kpiData.engagement_trends?.previous_month_engagement || 0 }}%</div>
                        <div class="trend-change">{{ kpiData.engagement_trends?.trend_percentage || 0 }}% change</div>
                    </div>
                </div>
            </div>

            <!-- Report Footer -->
            <div class="report-footer">
                <div class="footer-info">
                    <div class="footer-line">
                        <strong>Report Period:</strong> {{ kpiData.period?.period_name || 'Current Period' }}
                    </div>
                    <div class="footer-line">
                        <strong>Generated:</strong> {{ formatDateTime(new Date()) }}
                    </div>
                    <div class="footer-line">
                        <strong>System:</strong> Training Management Platform
                    </div>
                </div>
            </div>
        </div>

        <!-- Screenshot Preview Modal -->
        <div v-if="screenshotUrl" class="preview-modal" @click="closePreview">
            <div class="preview-content" @click.stop>
                <div class="preview-header">
                    <h3>📸 Screenshot Preview</h3>
                    <button @click="closePreview" class="close-btn">×</button>
                </div>
                <div class="preview-body">
                    <img :src="screenshotUrl" alt="KPI Report Screenshot" class="preview-image" />
                    <div class="preview-actions">
                        <button @click="downloadScreenshot" class="action-btn download">
                            📥 Download
                        </button>
                        <button @click="copyToClipboard" class="action-btn copy">
                            📋 Copy to Clipboard
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'

export default {
    name: 'MonthlyKpiScreenshot',

    props: {
        kpiData: { type: Object, required: true },
        currentFilters: { type: Object, required: true },
        departments: { type: Array, default: () => [] },
        courses: { type: Array, default: () => [] },
        lastUpdated: { type: String, required: true }
    },

    setup(props) {
        const capturing = ref(false)
        const screenshotUrl = ref(null)

        // Computed properties
        const backUrl = computed(() => {
            const params = new URLSearchParams(props.currentFilters)
            return `/admin/reports/monthly-kpi?${params.toString()}`
        })

        // Methods
        const captureScreenshot = async () => {
            try {
                capturing.value = true

                // Load html2canvas if not already loaded
                if (!window.html2canvas) {
                    const script = document.createElement('script')
                    script.src = 'https://html2canvas.hertzen.com/dist/html2canvas.min.js'
                    document.head.appendChild(script)
                    await new Promise(resolve => { script.onload = resolve })
                }

                // Get the content to capture
                const element = document.getElementById('screenshot-content')
                if (!element) {
                    throw new Error('Screenshot content not found')
                }

                // Capture with optimized settings
                const canvas = await window.html2canvas(element, {
                    backgroundColor: '#ffffff',
                    scale: 1.5,
                    useCORS: true,
                    allowTaint: true,
                    width: element.scrollWidth,
                    height: element.scrollHeight,
                    scrollX: 0,
                    scrollY: 0
                })

                // Create blob and URL
                canvas.toBlob((blob) => {
                    if (blob) {
                        screenshotUrl.value = URL.createObjectURL(blob)
                        console.log('✅ Screenshot captured successfully')
                    }
                }, 'image/png', 0.9)

            } catch (error) {
                console.error('❌ Screenshot failed:', error)
                alert('Screenshot failed. Please try using your browser\'s built-in screenshot (Ctrl/Cmd + Shift + S)')
            } finally {
                capturing.value = false
            }
        }

        const downloadScreenshot = () => {
            if (!screenshotUrl.value) return

            const link = document.createElement('a')
            link.href = screenshotUrl.value
            link.download = `KPI_Report_${props.currentFilters.month}_${props.currentFilters.year}.png`
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
        }

        const copyToClipboard = async () => {
            if (!screenshotUrl.value) return

            try {
                const response = await fetch(screenshotUrl.value)
                const blob = await response.blob()
                await navigator.clipboard.write([
                    new ClipboardItem({ 'image/png': blob })
                ])
                alert('📋 Screenshot copied to clipboard!')
            } catch (error) {
                console.error('Copy failed:', error)
                alert('Copy failed. Please download instead.')
            }
        }

        const closePreview = () => {
            if (screenshotUrl.value) {
                URL.revokeObjectURL(screenshotUrl.value)
                screenshotUrl.value = null
            }
        }

        // Utility methods
        const formatDate = (date) => {
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })
        }

        const formatDateTime = (dateTime) => {
            return new Date(dateTime).toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
        }

        const getStatus = (value, threshold) => {
            if (value >= threshold) return '✅ Good'
            if (value >= threshold * 0.7) return '⚠️ Fair'
            return '❌ Poor'
        }

        const getTrendArrow = (direction) => {
            switch (direction) {
                case 'up': return '↗️'
                case 'down': return '↘️'
                default: return '➡️'
            }
        }

        // Lifecycle
        onMounted(() => {
            console.log('📸 KPI Screenshot page loaded')
        })

        return {
            // State
            capturing,
            screenshotUrl,
            backUrl,

            // Methods
            captureScreenshot,
            downloadScreenshot,
            copyToClipboard,
            closePreview,
            formatDate,
            formatDateTime,
            getStatus,
            getTrendArrow
        }
    }
}
</script>

<style scoped>
/* =============================================== */
/* 🎨 CLEAN SCREENSHOT STYLES */
/* =============================================== */

* {
    box-sizing: border-box;
}

.kpi-screenshot-page {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #f8fafc;
    min-height: 100vh;
}

/* Toolbar */
.screenshot-toolbar {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 20px 0;
    position: sticky;
    top: 0;
    z-index: 100;
}

.toolbar-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.toolbar-content h1 {
    font-size: 24px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.toolbar-actions {
    display: flex;
    gap: 12px;
}

.screenshot-btn {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.screenshot-btn:hover:not(:disabled) {
    background: #2563eb;
}

.screenshot-btn:disabled {
    background: #94a3b8;
    cursor: not-allowed;
}

.back-btn {
    background: #f1f5f9;
    color: #475569;
    text-decoration: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 500;
    transition: background 0.2s;
}

.back-btn:hover {
    background: #e2e8f0;
}

/* Report Container */
.report-container {
    max-width: 1200px;
    margin: 0 auto;
    background: white;
    padding: 40px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Report Header */
.report-header {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 3px solid #e2e8f0;
}

.logo-placeholder {
    font-size: 48px;
    margin-bottom: 20px;
}

.report-title {
    font-size: 36px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 10px 0;
}

.report-subtitle {
    font-size: 20px;
    color: #64748b;
    margin-bottom: 10px;
}

.report-date {
    font-size: 14px;
    color: #94a3b8;
}

/* Section Titles */
.section-title {
    font-size: 24px;
    font-weight: 600;
    color: #1e293b;
    margin: 40px 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
}

/* KPI Grid */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

.kpi-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.kpi-card.highlight {
    background: #eff6ff;
    border-color: #93c5fd;
}

.kpi-icon {
    font-size: 32px;
    width: 48px;
    text-align: center;
}

.kpi-info {
    flex: 1;
}

.kpi-label {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 4px;
}

.kpi-value {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
}

/* Metrics Table */
.metrics-table {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 40px;
}

.table-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    border-bottom: 1px solid #f1f5f9;
}

.table-row.header {
    background: #f8fafc;
    font-weight: 600;
}

.table-row:last-child {
    border-bottom: none;
}

.cell {
    padding: 16px;
    display: flex;
    align-items: center;
    border-right: 1px solid #f1f5f9;
}

.cell:last-child {
    border-right: none;
}

/* Learning Outcomes */
.outcome-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

.outcome-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
}

.outcome-card.success {
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.outcome-card.warning {
    background: #fef3c7;
    border-color: #fcd34d;
}

.outcome-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 12px;
}

.outcome-icon {
    font-size: 20px;
}

.outcome-label {
    font-size: 14px;
    color: #64748b;
}

.outcome-value {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

/* Feedback Section */
.feedback-overview {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 30px;
    margin-bottom: 40px;
}

.feedback-summary {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 30px;
}

.summary-item {
    text-align: center;
    margin-bottom: 20px;
}

.summary-item:last-child {
    margin-bottom: 0;
}

.summary-label {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 8px;
}

.summary-value {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
}

.summary-value.large {
    font-size: 48px;
}

.unit {
    font-size: 24px;
    color: #94a3b8;
}

.sentiment-breakdown {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.sentiment-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.sentiment-item.positive {
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.sentiment-item.neutral {
    background: #f9fafb;
    border-color: #e2e8f0;
}

.sentiment-item.negative {
    background: #fef2f2;
    border-color: #fecaca;
}

.sentiment-emoji {
    font-size: 24px;
    width: 32px;
}

.sentiment-label {
    flex: 1;
    font-weight: 500;
    color: #374151;
}

.sentiment-percent {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
}

/* Performance Tables */
.performance-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.performance-table {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
}

.table-title {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 15px 0;
}

.simple-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.table-header {
    background: #f1f5f9;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    font-weight: 600;
    color: #475569;
}

.header-cell {
    padding: 12px 16px;
    border-right: 1px solid #e2e8f0;
}

.header-cell:last-child {
    border-right: none;
}

.table-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    border-top: 1px solid #f1f5f9;
}

.table-row.empty {
    grid-template-columns: 1fr;
}

.table-cell {
    padding: 12px 16px;
    border-right: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    color: #374151;
}

.table-cell:last-child {
    border-right: none;
}

.rank {
    font-weight: 600;
    color: #6366f1;
    margin-right: 8px;
}

/* Trend Section */
.trend-comparison {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 30px;
    align-items: center;
    margin-bottom: 40px;
}

.trend-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
}

.trend-card.current {
    background: #eff6ff;
    border-color: #93c5fd;
}

.trend-header {
    font-size: 16px;
    font-weight: 500;
    color: #64748b;
    margin-bottom: 12px;
}

.trend-value {
    font-size: 36px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
}

.trend-label {
    font-size: 14px;
    color: #94a3b8;
}

.trend-change {
    font-size: 14px;
    color: #64748b;
}

.trend-arrow {
    text-align: center;
}

.arrow-icon {
    font-size: 32px;
    display: block;
    margin-bottom: 8px;
}

.trend-text {
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
    text-transform: capitalize;
}

/* Report Footer */
.report-footer {
    margin-top: 50px;
    padding-top: 30px;
    border-top: 3px solid #e2e8f0;
    text-align: center;
}

.footer-info {
    background: #f8fafc;
    border-radius: 8px;
    padding: 20px;
    display: inline-block;
}

.footer-line {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 5px;
}

.footer-line:last-child {
    margin-bottom: 0;
}

/* Preview Modal */
.preview-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
}

.preview-content {
    background: white;
    border-radius: 12px;
    max-width: 90vw;
    max-height: 90vh;
    overflow: hidden;
}

.preview-header {
    padding: 20px 30px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
}

.preview-header h3 {
    margin: 0;
    font-size: 18px;
    color: #1e293b;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    color: #64748b;
    cursor: pointer;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.close-btn:hover {
    background: #e2e8f0;
}

.preview-body {
    padding: 30px;
    text-align: center;
    max-height: 70vh;
    overflow-y: auto;
}

.preview-image {
    max-width: 100%;
    max-height: 60vh;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 20px;
}

.preview-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.action-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.action-btn.download {
    background: #10b981;
    color: white;
}

.action-btn.download:hover {
    background: #059669;
}

.action-btn.copy {
    background: #3b82f6;
    color: white;
}

.action-btn.copy:hover {
    background: #2563eb;
}

/* Responsive */
@media (max-width: 768px) {
    .report-container {
        padding: 20px;
    }

    .kpi-grid {
        grid-template-columns: 1fr 1fr;
    }

    .table-row {
        grid-template-columns: 1fr;
    }

    .feedback-overview {
        grid-template-columns: 1fr;
    }

    .performance-grid {
        grid-template-columns: 1fr;
    }

    .trend-comparison {
        grid-template-columns: 1fr;
    }

    .outcome-cards {
        grid-template-columns: 1fr 1fr;
    }
}
</style>
