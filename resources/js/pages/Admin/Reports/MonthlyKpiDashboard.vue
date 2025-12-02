<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="monthly-kpi-dashboard bg-black text-white min-h-screen dark">
            <!-- 🎯 DASHBOARD HEADER -->
            <div class="dashboard-header bg-gray-800 border-b border-gray-600 p-4 sm:p-6">
                <div class="header-content">
                    <div class="title-section">
                        <h1 class="dashboard-title text-2xl sm:text-3xl font-bold text-white">📊 Monthly Training KPI Report</h1>
                        <p class="period-display text-gray-300 mt-2">{{ kpiData.period?.period_name || 'Loading...' }}</p>
                    </div>

                    <div class="header-actions flex flex-wrap gap-3 mt-4">
                        <button @click="showFilters = !showFilters" class="filter-btn bg-gray-700 text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors" :class="{ 'bg-blue-800 text-blue-200': showFilters }">
                            🔍 Filters
                        </button>

                        <!-- 🎯 Export Buttons -->
                        <div class="export-buttons flex gap-2">
                            <button @click="generateDirectScreenshot" class="export-btn screenshot-btn bg-purple-800 text-purple-200 px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50" :disabled="loading || screenshotLoading">
                                {{ screenshotLoading ? '⏳ Capturing...' : '📸 Screenshot' }}
                            </button>
                            <button @click="exportCsv" class="export-btn csv-btn bg-green-800 text-green-200 px-4 py-2 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50" :disabled="loading">
                                📋 Export CSV
                            </button>
                        </div>

                        <button @click="refreshData" class="refresh-btn bg-indigo-800 text-indigo-200 px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50" :disabled="loading">
                            {{ loading ? '⏳' : '🔄' }} Refresh
                        </button>
                    </div>
                </div>

                <!-- Filter Panel -->
                <div v-show="showFilters" class="filter-panel bg-gray-700 border border-gray-600 rounded-lg p-4 mt-4">
                    <div class="filter-grid grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="filter-group">
                            <label class="block text-sm font-medium text-gray-200 mb-2">Month</label>
                            <select v-model="filters.month" @change="applyFilters" class="w-full bg-gray-600 border border-gray-500 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                                <option v-for="month in filterData.months" :key="month.value" :value="month.value">
                                    {{ month.label }}
                                </option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="block text-sm font-medium text-gray-200 mb-2">Year</label>
                            <select v-model="filters.year" @change="applyFilters" class="w-full bg-gray-600 border border-gray-500 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                                <option v-for="year in filterData.years" :key="year.value" :value="year.value">
                                    {{ year.label }}
                                </option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="block text-sm font-medium text-gray-200 mb-2">Department</label>
                            <select v-model="filters.department_id" @change="applyFilters" class="w-full bg-gray-600 border border-gray-500 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400">
                                <option value="">All Departments</option>
                                <option v-for="dept in filterData.departments" :key="dept.id" :value="dept.id">
                                    {{ dept.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Last Updated -->
                <div class="update-info mt-4 pt-4 border-t border-gray-600">
                    <span class="last-updated text-sm text-gray-300">Last updated: {{ formatDateTime(lastUpdated) }}</span>
                </div>
            </div>

            <!-- Loading Overlay -->
            <div v-if="loading" class="loading-overlay fixed inset-0 bg-gray-900 bg-opacity-90 flex items-center justify-center z-50">
                <div class="loading-spinner bg-gray-800 rounded-lg p-8 text-center shadow-2xl border border-gray-600">
                    <div class="spinner inline-block w-8 h-8 border-4 border-gray-600 border-t-blue-400 rounded-full animate-spin mb-4"></div>
                    <p class="text-white">Loading KPI Data...</p>
                </div>
            </div>

            <!-- 🎯 DASHBOARD CONTENT -->
            <div v-else class="dashboard-content p-4 sm:p-6 space-y-8">
                <!-- 📊 SECTION 1: Training Delivery Overview -->
                <section class="kpi-section delivery-overview">
                    <h2 class="section-title text-xl font-semibold text-white mb-6">📊 Training Delivery Overview</h2>
                    <div class="kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">📚</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Courses Delivered</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.delivery_overview?.courses_delivered || 0 }}</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">👥</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Total Enrolled</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.delivery_overview?.total_enrolled || 0 }}</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">🎯</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Active Participants</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.delivery_overview?.active_participants || 0 }}</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">✅</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Completion Rate</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.delivery_overview?.completion_rate || 0 }}%</div>
                        </div>
                    </div>
                </section>

                <!-- 🎯 SECTION 2: Attendance & Engagement -->
                <section class="kpi-section attendance-engagement">
                    <h2 class="section-title text-xl font-semibold text-white mb-6">🎯 Attendance & Engagement</h2>
                    <div class="kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">📋</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Attendance Rate</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.attendance_engagement?.average_attendance_rate || 0 }}%</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">⏱️</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Avg Time Spent</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.attendance_engagement?.average_time_spent || 0 }}h</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">🕐</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Clock Consistency</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.attendance_engagement?.clocking_consistency || 0 }}%</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">💯</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Engagement Score</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.attendance_engagement?.engagement_score || 0 }}%</div>
                        </div>
                    </div>
                </section>

                <!-- � SECTTION 3: Online Course Analytics Overview -->
                <section class="kpi-section online-course-analytics">
                    <h2 class="section-title text-xl font-semibold text-white mb-6">�  Online Course Analytics Overview</h2>
                    <div class="kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                        <div class="kpi-card bg-gray-800 border border-blue-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-blue-900/20">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">💻</span>
                                <span class="kpi-title text-sm font-medium text-blue-400">Online Courses</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-blue-300">{{ kpiData.online_course_analytics?.delivery?.online_courses_delivered || 0 }}</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">📝</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Enrollments</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.online_course_analytics?.delivery?.online_enrollments || 0 }}</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-green-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-green-900/20">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">✅</span>
                                <span class="kpi-title text-sm font-medium text-green-400">Completed</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-green-300">{{ kpiData.online_course_analytics?.delivery?.online_completed || 0 }}</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-purple-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-purple-900/20">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">📊</span>
                                <span class="kpi-title text-sm font-medium text-purple-400">Completion Rate</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-purple-300">{{ kpiData.online_course_analytics?.delivery?.online_completion_rate || 0 }}%</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">👥</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Active Learners</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.online_course_analytics?.delivery?.active_online_learners || 0 }}</div>
                        </div>
                    </div>
                </section>

                <!-- 🎥 SECTION 4: Video Engagement Metrics -->
                <section class="kpi-section video-engagement">
                    <h2 class="section-title text-xl font-semibold text-white mb-6">🎥 Video Engagement Metrics</h2>
                    <div class="kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="kpi-card bg-gray-800 border border-blue-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-blue-900/20">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">▶️</span>
                                <span class="kpi-title text-sm font-medium text-blue-400">Videos Watched</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-blue-300">{{ kpiData.online_course_analytics?.video_engagement?.total_videos_watched || 0 }}</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-green-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-green-900/20">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">✅</span>
                                <span class="kpi-title text-sm font-medium text-green-400">Avg Completion</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-green-300">{{ kpiData.online_course_analytics?.video_engagement?.avg_video_completion || 0 }}%</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">⏱️</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Watch Time</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.online_course_analytics?.video_engagement?.total_watch_time_hours || 0 }}h</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">🔄</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Replays</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.online_course_analytics?.video_engagement?.video_replay_count || 0 }}</div>
                        </div>
                    </div>
                </section>

                <!-- 📈 SECTION 5: Learning Outcomes -->
                <section class="kpi-section learning-outcomes">
                    <h2 class="section-title text-xl font-semibold text-white mb-6">📈 Learning Outcomes</h2>
                    <div class="kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="kpi-card success bg-gray-800 border border-green-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-green-900/20">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">✅</span>
                                <span class="kpi-title text-sm font-medium text-green-400">Quiz Pass Rate</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-green-300">{{ kpiData.learning_outcomes?.quiz_pass_rate || 0 }}%</div>
                        </div>
                        <div class="kpi-card danger bg-gray-800 border border-red-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-red-900/20">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">❌</span>
                                <span class="kpi-title text-sm font-medium text-red-400">Quiz Fail Rate</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-red-300">{{ kpiData.learning_outcomes?.quiz_fail_rate || 0 }}%</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">📊</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Average Score</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.learning_outcomes?.average_quiz_score || 0 }}%</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">📈</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Improvement Rate</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.learning_outcomes?.improvement_rate || 0 }}%</div>
                        </div>
                    </div>
                </section>

                <!-- 📚 SECTION 6: Online Module Progress -->
                <section class="kpi-section module-progress">
                    <h2 class="section-title text-xl font-semibold text-white mb-6">📚 Online Module Progress</h2>
                    <div class="kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">📚</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Total Modules</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.online_course_analytics?.module_progress?.total_modules || 0 }}</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-green-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-green-900/20">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">✅</span>
                                <span class="kpi-title text-sm font-medium text-green-400">Completed</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-green-300">{{ kpiData.online_course_analytics?.module_progress?.completed_modules || 0 }}</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">👤</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Avg Per User</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.online_course_analytics?.module_progress?.avg_modules_per_user || 0 }}</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-purple-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-purple-900/20">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">📈</span>
                                <span class="kpi-title text-sm font-medium text-purple-400">Completion Rate</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-purple-300">{{ kpiData.online_course_analytics?.module_progress?.module_completion_rate || 0 }}%</div>
                        </div>
                    </div>
                </section>

                <!-- ⭐ SECTION 7: Course Quality & Feedback -->
                <section class="kpi-section feedback-analysis">
                    <h2 class="section-title text-xl font-semibold text-white mb-6">⭐ Course Quality & Feedback</h2>
                    <div class="feedback-grid grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="feedback-cards grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                                <div class="kpi-header flex items-center mb-4">
                                    <span class="kpi-icon text-2xl mr-3">⭐</span>
                                    <span class="kpi-title text-sm font-medium text-gray-300">Average Rating</span>
                                </div>
                                <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.feedback_analysis?.average_rating || 0 }}/5</div>
                            </div>
                            <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                                <div class="kpi-header flex items-center mb-4">
                                    <span class="kpi-icon text-2xl mr-3">💬</span>
                                    <span class="kpi-title text-sm font-medium text-gray-300">Total Feedback</span>
                                </div>
                                <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.feedback_analysis?.total_feedback_count || 0 }}</div>
                            </div>
                        </div>
                        <div class="feedback-sentiment bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
                            <h3 class="text-lg font-semibold text-white mb-4">Feedback Sentiment</h3>
                            <div class="sentiment-display space-y-3">
                                <div class="sentiment-item flex justify-between items-center">
                                    <span class="sentiment-label text-gray-300">😊 Positive:</span>
                                    <span class="sentiment-value font-bold text-green-400">{{ kpiData.feedback_analysis?.feedback_sentiment?.positive || 0 }}%</span>
                                </div>
                                <div class="sentiment-item flex justify-between items-center">
                                    <span class="sentiment-label text-gray-300">😐 Neutral:</span>
                                    <span class="sentiment-value font-bold text-yellow-400">{{ kpiData.feedback_analysis?.feedback_sentiment?.neutral || 0 }}%</span>
                                </div>
                                <div class="sentiment-item flex justify-between items-center">
                                    <span class="sentiment-label text-gray-300">😞 Negative:</span>
                                    <span class="sentiment-value font-bold text-red-400">{{ kpiData.feedback_analysis?.feedback_sentiment?.negative || 0 }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ⏱️ SECTION 8: Learning Session Analytics -->
                <section class="kpi-section session-analytics">
                    <h2 class="section-title text-xl font-semibold text-white mb-6">⏱️ Learning Session Analytics</h2>
                    <div class="kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">🎯</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Total Sessions</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.online_course_analytics?.session_analytics?.total_sessions || 0 }}</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-blue-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-blue-900/20">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">⏱️</span>
                                <span class="kpi-title text-sm font-medium text-blue-400">Avg Duration</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-blue-300">{{ kpiData.online_course_analytics?.session_analytics?.avg_session_duration_minutes || 0 }}m</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-green-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-green-900/20">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">👁️</span>
                                <span class="kpi-title text-sm font-medium text-green-400">Attention Score</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-green-300">{{ kpiData.online_course_analytics?.session_analytics?.avg_attention_score || 0 }}%</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">⏰</span>
                                <span class="kpi-title text-sm font-medium text-gray-300">Learning Hours</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-white">{{ kpiData.online_course_analytics?.session_analytics?.total_learning_hours || 0 }}h</div>
                        </div>
                        <div class="kpi-card bg-gray-800 border border-red-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-red-900/20">
                            <div class="kpi-header flex items-center mb-4">
                                <span class="kpi-icon text-2xl mr-3">⚠️</span>
                                <span class="kpi-title text-sm font-medium text-red-400">Suspicious Activity</span>
                            </div>
                            <div class="kpi-value text-3xl font-bold text-red-300">{{ kpiData.online_course_analytics?.session_analytics?.suspicious_activity_count || 0 }}</div>
                        </div>
                    </div>
                </section>

                <!-- 🏆 SECTION 9: Course Performance Analysis -->
                <section class="kpi-section performance-analysis">
                    <h2 class="section-title text-xl font-semibold text-white mb-6">🏆 Course Performance Analysis</h2>
                    <div class="performance-grid grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
                            <h3 class="text-lg font-semibold text-white mb-2">🥇 Top-Performing Courses</h3>
                            <p class="subtitle text-sm text-gray-300 mb-4">Based on rating & completion</p>
                            <div class="table-container overflow-x-auto">
                                <table class="performance-table-content w-full">
                                    <thead>
                                    <tr class="bg-gray-700 border-b border-gray-600">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Course Name</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Rating</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Completion %</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-600">
                                    <tr v-for="course in kpiData.performance_analysis?.top_performing_courses || []" :key="course.id" class="table-row hover:bg-gray-700 transition-colors">
                                        <td class="course-name px-4 py-3 text-sm text-white">{{ course.name }}</td>
                                        <td class="rating px-4 py-3 text-sm text-white">{{ course.rating }}/5</td>
                                        <td class="completion px-4 py-3 text-sm text-white">{{ course.completion_rate }}%</td>
                                    </tr>
                                    <tr v-if="!kpiData.performance_analysis?.top_performing_courses?.length">
                                        <td colspan="3" class="no-data px-4 py-8 text-center text-gray-400">No data available</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
                            <h3 class="text-lg font-semibold text-white mb-2">⚠️ Courses Needing Improvement</h3>
                            <p class="subtitle text-sm text-gray-300 mb-4">Based on dropout or low ratings</p>
                            <div class="table-container overflow-x-auto">
                                <table class="performance-table-content w-full">
                                    <thead>
                                    <tr class="bg-gray-700 border-b border-gray-600">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Course Name</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Rating</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Issues</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-600">
                                    <tr v-for="course in kpiData.performance_analysis?.courses_needing_improvement || []" :key="course.name" class="table-row improvement-needed hover:bg-red-900/30 transition-colors">
                                        <td class="course-name px-4 py-3 text-sm text-white">{{ course.name }}</td>
                                        <td class="rating low px-4 py-3 text-sm text-red-400">{{ course.rating || 'N/A' }}</td>
                                        <td class="issues px-4 py-3">
                                            <span v-for="issue in course.issues" :key="issue" class="issue-tag inline-block bg-red-800 text-red-200 text-xs px-2 py-1 rounded mr-1 mb-1">{{ issue }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="!kpiData.performance_analysis?.courses_needing_improvement?.length">
                                        <td colspan="3" class="no-data px-4 py-8 text-center text-gray-400">No courses needing improvement</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 👤 SECTION 6: User Performance Analysis -->
                <section class="kpi-section user-performance">
                    <h2 class="section-title text-xl font-semibold text-white mb-6">👤 User Performance Analysis</h2>
                    <div class="performance-grid grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
                            <h3 class="text-lg font-semibold text-white mb-2">🌟 Top-Performing Users</h3>
                            <p class="subtitle text-sm text-gray-300 mb-4">Based on evaluation system scores</p>
                            <div class="table-container overflow-x-auto">
                                <table class="performance-table-content w-full">
                                    <thead>
                                    <tr class="bg-gray-700 border-b border-gray-600">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">User Name</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Score %</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Courses Completed</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-600">
                                    <tr v-for="user in kpiData.performance_analysis?.top_performing_users || []" :key="user.name" class="table-row top-performer hover:bg-green-900/30 transition-colors">
                                        <td class="user-name px-4 py-3 text-sm text-white">{{ user.name }}</td>
                                        <td class="score high px-4 py-3 text-sm text-green-400 font-semibold">{{ user.score }}%</td>
                                        <td class="courses px-4 py-3 text-sm text-white">{{ user.courses_completed || 0 }}</td>
                                    </tr>
                                    <tr v-if="!kpiData.performance_analysis?.top_performing_users?.length">
                                        <td colspan="3" class="no-data px-4 py-8 text-center text-gray-400">No data available</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
                            <h3 class="text-lg font-semibold text-white mb-2">📈 Users Needing Support</h3>
                            <p class="subtitle text-sm text-gray-300 mb-4">Based on evaluation system scores</p>
                            <div class="table-container overflow-x-auto">
                                <table class="performance-table-content w-full">
                                    <thead>
                                    <tr class="bg-gray-700 border-b border-gray-600">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">User Name</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Score %</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Incomplete Courses</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-600">
                                    <tr v-for="user in kpiData.performance_analysis?.low_performing_users || []" :key="user.name" class="table-row needs-support hover:bg-yellow-900/30 transition-colors">
                                        <td class="user-name px-4 py-3 text-sm text-white">{{ user.name }}</td>
                                        <td class="score low px-4 py-3 text-sm text-yellow-400 font-semibold">{{ user.score }}%</td>
                                        <td class="courses px-4 py-3 text-sm text-white">{{ user.courses_incomplete || 0 }}</td>
                                    </tr>
                                    <tr v-if="!kpiData.performance_analysis?.low_performing_users?.length">
                                        <td colspan="3" class="no-data px-4 py-8 text-center text-gray-400">No users needing support</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 🏆 SECTION 12: Online Course Top Performers -->
                <section class="kpi-section online-top-performers">
                    <h2 class="section-title text-xl font-semibold text-white mb-6">🏆 Online Course Top Performers</h2>
                    <div class="performance-grid grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
                            <h3 class="text-lg font-semibold text-white mb-2">🥇 Top Online Courses</h3>
                            <p class="subtitle text-sm text-gray-300 mb-4">Based on completion rate & enrollment</p>
                            <div class="table-container overflow-x-auto">
                                <table class="performance-table-content w-full">
                                    <thead>
                                    <tr class="bg-gray-700 border-b border-gray-600">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Course Name</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Completion</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Enrolled</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-600">
                                    <tr v-for="course in kpiData.online_course_analytics?.top_performers?.top_online_courses || []" :key="course.id" class="table-row hover:bg-gray-700 transition-colors">
                                        <td class="course-name px-4 py-3 text-sm text-white">{{ course.name }}</td>
                                        <td class="completion px-4 py-3 text-sm text-green-400 font-semibold">{{ course.completion_rate }}%</td>
                                        <td class="enrolled px-4 py-3 text-sm text-white">{{ course.total_enrolled }}</td>
                                    </tr>
                                    <tr v-if="!kpiData.online_course_analytics?.top_performers?.top_online_courses?.length">
                                        <td colspan="3" class="no-data px-4 py-8 text-center text-gray-400">No data available</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
                            <h3 class="text-lg font-semibold text-white mb-2">🌟 Top Online Learners</h3>
                            <p class="subtitle text-sm text-gray-300 mb-4">Based on courses completed & progress</p>
                            <div class="table-container overflow-x-auto">
                                <table class="performance-table-content w-full">
                                    <thead>
                                    <tr class="bg-gray-700 border-b border-gray-600">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">User Name</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Completed</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-white">Progress</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-600">
                                    <tr v-for="user in kpiData.online_course_analytics?.top_performers?.top_online_learners || []" :key="user.id" class="table-row top-performer hover:bg-green-900/30 transition-colors">
                                        <td class="user-name px-4 py-3 text-sm text-white">{{ user.name }}</td>
                                        <td class="courses px-4 py-3 text-sm text-white">{{ user.courses_completed || 0 }}</td>
                                        <td class="progress px-4 py-3 text-sm text-green-400 font-semibold">{{ user.avg_progress || 0 }}%</td>
                                    </tr>
                                    <tr v-if="!kpiData.online_course_analytics?.top_performers?.top_online_learners?.length">
                                        <td colspan="3" class="no-data px-4 py-8 text-center text-gray-400">No data available</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 📈 SECTION 13: Monthly Engagement Trend -->
                <section class="kpi-section engagement-trends">
                    <h2 class="section-title text-xl font-semibold text-white mb-6">📈 Monthly Engagement Trend</h2>
                    <div class="trends-display">
                        <div class="trend-cards grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="trend-card current bg-gray-800 border border-blue-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-blue-900/20">
                                <div class="trend-header flex items-center mb-4">
                                    <span class="trend-icon text-2xl mr-3">📊</span>
                                    <span class="trend-title text-sm font-medium text-blue-400">Current Month Engagement</span>
                                </div>
                                <div class="trend-value text-3xl font-bold text-blue-300">{{ kpiData.engagement_trends?.current_month_engagement || 0 }}%</div>
                                <div class="trend-label text-sm text-gray-300 mt-2">{{ kpiData.period?.period_name || 'Current Period' }}</div>
                            </div>
                            <div class="trend-card previous bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                                <div class="trend-header flex items-center mb-4">
                                    <span class="trend-icon text-2xl mr-3">📉</span>
                                    <span class="trend-title text-sm font-medium text-gray-300">Previous Month Engagement</span>
                                </div>
                                <div class="trend-value text-3xl font-bold text-gray-200">{{ kpiData.engagement_trends?.previous_month_engagement || 0 }}%</div>
                                <div class="trend-label text-sm text-gray-300 mt-2">Previous Period</div>
                            </div>
                            <div class="trend-card comparison bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750">
                                <div class="trend-header flex items-center mb-4">
                                    <span class="trend-icon text-2xl mr-3">🔄</span>
                                    <span class="trend-title text-sm font-medium text-gray-300">Trend Direction</span>
                                </div>
                                <div class="trend-value text-2xl font-bold flex items-center" :class="getTrendClass(kpiData.engagement_trends?.trend_direction)">
                                    <span class="trend-arrow mr-2">{{ getTrendArrow(kpiData.engagement_trends?.trend_direction) }}</span>
                                    {{ kpiData.engagement_trends?.trend_direction || 'stable' }}
                                </div>
                                <div class="trend-percentage text-sm text-gray-300 mt-2">{{ kpiData.engagement_trends?.trend_percentage || 0 }}% change</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue';

export default {
    name: 'MonthlyKpiDashboard',
    components: { AdminLayout },

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

        // Breadcrumbs for navigation
        const breadcrumbs = computed(() => [
            { label: 'Dashboard', href: route('dashboard') },
            { label: 'Reports', href: route('admin.reports.index') },
            { label: 'Monthly KPI Dashboard', href: null }
        ])

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
        <h2 class="section-title">💻 Online Course Analytics</h2>
        <div class="kpi-cards-grid" style="grid-template-columns: repeat(5, 1fr);">
            <div class="kpi-card blue">
                <div class="kpi-icon">💻</div>
                <div class="kpi-label">Online Courses</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.delivery?.online_courses_delivered || 0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">📝</div>
                <div class="kpi-label">Enrollments</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.delivery?.online_enrollments || 0}</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Completed</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.delivery?.online_completed || 0}</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">📊</div>
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.delivery?.online_completion_rate || 0}%</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon">👥</div>
                <div class="kpi-label">Active Learners</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.delivery?.active_online_learners || 0}</div>
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
        <h2 class="section-title">🎥 Video Engagement Metrics</h2>
        <div class="kpi-cards-grid">
            <div class="kpi-card blue">
                <div class="kpi-icon">▶️</div>
                <div class="kpi-label">Videos Watched</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.video_engagement?.total_videos_watched || 0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Avg Completion</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.video_engagement?.avg_video_completion || 0}%</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">⏱️</div>
                <div class="kpi-label">Watch Time</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.video_engagement?.total_watch_time_hours || 0}h</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">🔄</div>
                <div class="kpi-label">Replays</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.video_engagement?.video_replay_count || 0}</div>
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
        <h2 class="section-title">📚 Online Module Progress</h2>
        <div class="kpi-cards-grid">
            <div class="kpi-card blue">
                <div class="kpi-icon">📚</div>
                <div class="kpi-label">Total Modules</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.module_progress?.total_modules || 0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Completed</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.module_progress?.completed_modules || 0}</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">👤</div>
                <div class="kpi-label">Avg Per User</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.module_progress?.avg_modules_per_user || 0}</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">📈</div>
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.module_progress?.module_completion_rate || 0}%</div>
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
        <h2 class="section-title">⏱️ Learning Session Analytics</h2>
        <div class="kpi-cards-grid" style="grid-template-columns: repeat(5, 1fr);">
            <div class="kpi-card blue">
                <div class="kpi-icon">🎯</div>
                <div class="kpi-label">Total Sessions</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.session_analytics?.total_sessions || 0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">⏱️</div>
                <div class="kpi-label">Avg Duration</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.session_analytics?.avg_session_duration_minutes || 0}m</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">👁️</div>
                <div class="kpi-label">Attention Score</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.session_analytics?.avg_attention_score || 0}%</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">⏰</div>
                <div class="kpi-label">Learning Hours</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.session_analytics?.total_learning_hours || 0}h</div>
            </div>
            <div class="kpi-card danger">
                <div class="kpi-icon">⚠️</div>
                <div class="kpi-label">Suspicious Activity</div>
                <div class="kpi-value">${props.kpiData.online_course_analytics?.session_analytics?.suspicious_activity_count || 0}</div>
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
                    ${(props.kpiData.online_course_analytics?.top_performers?.top_online_courses || []).slice(0, 5).map((course, index) => `
                        <div class="table-row">
                            <div class="table-cell">${index + 1}. ${course.name}</div>
                            <div class="table-cell">${course.completion_rate}%</div>
                            <div class="table-cell">${course.total_enrolled || 0}</div>
                        </div>
                    `).join('') || '<div class="table-row"><div class="table-cell">No data available</div><div class="table-cell">-</div><div class="table-cell">-</div></div>'}
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
                    ${(props.kpiData.online_course_analytics?.top_performers?.top_online_learners || []).slice(0, 5).map((user, index) => `
                        <div class="table-row">
                            <div class="table-cell">${index + 1}. ${user.name}</div>
                            <div class="table-cell">${user.courses_completed}</div>
                            <div class="table-cell">${user.avg_progress}%</div>
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
            loading, showFilters, filters, screenshotLoading, breadcrumbs,
            applyFilters, refreshData, exportCsv, formatDateTime,
            getTrendClass, getTrendArrow, generateDirectScreenshot, hasData
        }
    }
}
</script>

<style scoped>
/* =============================================== */
/* 🎨 DASHBOARD STYLES - Updated with CSS Variables */
/* =============================================== */

.monthly-kpi-dashboard {
    min-height: 100vh;
    background-color: hsl(var(--background));
    color: hsl(var(--foreground));
}

/* Header Styles */
.dashboard-header {
    background: hsl(var(--card));
    box-shadow: 0 1px 3px hsl(var(--border) / 0.1);
    border-bottom: 1px solid hsl(var(--border));
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
    color: hsl(var(--card-foreground));
    margin: 0 0 0.25rem 0;
}

.period-display {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.filter-btn {
    padding: 0.5rem 1rem;
    background-color: hsl(var(--secondary) / 0.1);
    color: hsl(var(--secondary-foreground));
    border-radius: 0.5rem;
    border: 1px solid hsl(var(--border));
    cursor: pointer;
    transition: all 0.2s;
}

.filter-btn:hover {
    background-color: hsl(var(--secondary) / 0.2);
}

.filter-btn.active {
    background-color: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
}

/* Export Buttons */
.export-buttons {
    display: flex;
    gap: 0.5rem;
}

.export-btn {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid hsl(var(--border));
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background-color: hsl(var(--card));
    color: hsl(var(--card-foreground));
}

.export-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.screenshot-btn {
    background-color: hsl(var(--chart-1) / 0.1);
    color: hsl(var(--chart-1));
    border-color: hsl(var(--chart-1) / 0.3);
}

.screenshot-btn:hover:not(:disabled) {
    background-color: hsl(var(--chart-1) / 0.2);
}

.csv-btn {
    background-color: hsl(var(--chart-2) / 0.1);
    color: hsl(var(--chart-2));
    border-color: hsl(var(--chart-2) / 0.3);
}

.csv-btn:hover:not(:disabled) {
    background-color: hsl(var(--chart-2) / 0.2);
}

.refresh-btn {
    padding: 0.5rem 1rem;
    background-color: hsl(var(--secondary));
    color: hsl(var(--secondary-foreground));
    border-radius: 0.5rem;
    border: 1px solid hsl(var(--border));
    cursor: pointer;
    transition: all 0.2s;
}

.refresh-btn:hover:not(:disabled) {
    background-color: hsl(var(--secondary) / 0.8);
}

.refresh-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Filter Panel */
.filter-panel {
    border-top: 1px solid hsl(var(--border));
    background-color: hsl(var(--muted));
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
    color: hsl(var(--foreground));
}

.filter-group select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid hsl(var(--border));
    border-radius: 0.375rem;
    background-color: hsl(var(--background));
    color: hsl(var(--foreground));
    font-size: 0.875rem;
}

.filter-group select:focus {
    outline: none;
    box-shadow: 0 0 0 2px hsl(var(--ring));
    border-color: hsl(var(--ring));
}

/* Update Info */
.update-info {
    border-top: 1px solid hsl(var(--border));
    padding: 0.5rem 1rem;
    background-color: hsl(var(--muted));
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
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
    background-color: hsl(var(--background) / 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
}

.loading-spinner {
    background-color: hsl(var(--card));
    border-radius: 0.5rem;
    padding: 2rem;
    text-align: center;
    border: 1px solid hsl(var(--border));
    color: hsl(var(--card-foreground));
}

.spinner {
    width: 3rem;
    height: 3rem;
    border: 2px solid hsl(var(--muted));
    border-top: 2px solid hsl(var(--primary));
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
    background-color: hsl(var(--card));
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px hsl(var(--border) / 0.1);
    border: 1px solid hsl(var(--border));
    padding: 1.5rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: hsl(var(--card-foreground));
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
    background-color: hsl(var(--card));
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px hsl(var(--border) / 0.1);
    padding: 1.5rem;
    border: 1px solid hsl(var(--border));
    transition: all 0.2s;
}

.kpi-card:hover {
    box-shadow: 0 4px 6px hsl(var(--border) / 0.2);
}

.kpi-card.success {
    border-left: 4px solid hsl(var(--chart-3));
}

.kpi-card.danger {
    border-left: 4px solid hsl(var(--destructive));
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
    color: hsl(var(--muted-foreground));
}

.kpi-value {
    font-size: 2rem;
    font-weight: 700;
    color: hsl(var(--card-foreground));
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
    color: hsl(var(--card-foreground));
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
    background-color: hsl(var(--muted));
    border-radius: 0.375rem;
    border: 1px solid hsl(var(--border));
}

.sentiment-label {
    font-weight: 500;
    color: hsl(var(--foreground));
}

.sentiment-value {
    font-weight: 600;
    color: hsl(var(--primary));
}

/* Performance Analysis */
.performance-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.performance-table {
    background-color: hsl(var(--muted));
    border-radius: 0.5rem;
    padding: 1rem;
    border: 1px solid hsl(var(--border));
}

.performance-table h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    margin: 0 0 0.25rem 0;
}

.subtitle {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    margin: 0 0 1rem 0;
}

.table-container {
    background-color: hsl(var(--card));
    border-radius: 0.375rem;
    overflow: hidden;
    box-shadow: 0 1px 3px hsl(var(--border) / 0.1);
    border: 1px solid hsl(var(--border));
}

.performance-table-content {
    width: 100%;
    border-collapse: collapse;
}

.performance-table-content th {
    background-color: hsl(var(--muted));
    padding: 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
    text-align: left;
    border-bottom: 1px solid hsl(var(--border));
}

.performance-table-content td {
    padding: 0.75rem;
    font-size: 0.875rem;
    border-bottom: 1px solid hsl(var(--border));
    color: hsl(var(--card-foreground));
}

.table-row:last-child td {
    border-bottom: none;
}

.table-row:hover {
    background-color: hsl(var(--accent));
}

.course-name,
.user-name {
    font-weight: 500;
    color: hsl(var(--card-foreground));
}

.rating.high,
.score.high {
    color: hsl(var(--chart-3));
    font-weight: 600;
}

.rating.low,
.score.low {
    color: hsl(var(--destructive));
    font-weight: 600;
}

.issue-tag {
    display: inline-block;
    background-color: hsl(var(--chart-4) / 0.2);
    color: hsl(var(--chart-4));
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    margin-right: 0.25rem;
    border: 1px solid hsl(var(--chart-4) / 0.3);
}

.no-data {
    text-align: center;
    color: hsl(var(--muted-foreground));
    font-style: italic;
}

.top-performer {
    background-color: hsl(var(--chart-3) / 0.1);
}

.improvement-needed {
    background-color: hsl(var(--chart-4) / 0.1);
}

.needs-support {
    background-color: hsl(var(--destructive) / 0.1);
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
    background-color: hsl(var(--card));
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px hsl(var(--border) / 0.1);
    padding: 1.5rem;
    border: 1px solid hsl(var(--border));
}

.trend-card.current {
    border-left: 4px solid hsl(var(--primary));
}

.trend-card.previous {
    border-left: 4px solid hsl(var(--muted-foreground));
}

.trend-card.comparison {
    border-left: 4px solid hsl(var(--chart-1));
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
    color: hsl(var(--muted-foreground));
}

.trend-value {
    font-size: 2rem;
    font-weight: 700;
    color: hsl(var(--card-foreground));
    margin-bottom: 0.25rem;
}

.trend-value.trend-up {
    color: hsl(var(--chart-3));
}

.trend-value.trend-down {
    color: hsl(var(--destructive));
}

.trend-value.trend-stable {
    color: hsl(var(--muted-foreground));
}

.trend-label {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.trend-percentage {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
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
