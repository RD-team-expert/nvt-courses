import { mount, createLocalVue } from '@vue/test-utils'
import OnlineCourseEvaluation from '@/resources/js/Pages/Admin/Evaluations/OnlineCourseEvaluation.vue'
import UserEvaluation from '@/resources/js/Pages/Admin/Evaluations/UserEvaluation.vue'
import Notifications from '@/resources/js/Pages/Admin/Evaluations/Notifications.vue'
import flushPromises from 'flush-promises'

// Mock the performance levels data
const performanceLevels = [
  {
    level: 1,
    label: 'Outstanding',
    description: 'Consistently exceeds expectations',
    min_score: 13,
    max_score: 15,
    color: 'green',
    badge_class: 'badge-success'
  },
  {
    level: 2,
    label: 'Reliable',
    description: 'Meets expectations consistently',
    min_score: 10,
    max_score: 12,
    color: 'blue',
    badge_class: 'badge-info'
  },
  {
    level: 3,
    label: 'Developing',
    description: 'Meets some expectations, needs improvement',
    min_score: 7,
    max_score: 9,
    color: 'yellow',
    badge_class: 'badge-warning'
  },
  {
    level: 4,
    label: 'Underperforming',
    description: 'Does not meet expectations',
    min_score: 0,
    max_score: 6,
    color: 'red',
    badge_class: 'badge-danger'
  }
]

describe('Performance Level Components', () => {
  const localVue = createLocalVue()
  
  describe('OnlineCourseEvaluation.vue', () => {
    test('renders performance level badges with correct colors', async () => {
      const propsData = {
        course: { id: 1, title: 'Test Course' },
        users: [{ id: 1, name: 'Test User' }],
        performanceLevels: performanceLevels
      }
      
      const wrapper = mount(OnlineCourseEvaluation, {
        localVue,
        propsData,
        stubs: ['inertia-link']
      })
      
      // Wait for component to load
      await flushPromises()
      
      // Test that performance level badges render with correct classes
      const outstandingBadge = wrapper.find('.badge-success')
      expect(outstandingBadge.exists()).toBe(true)
      expect(outstandingBadge.text()).toContain('Outstanding')
      
      const reliableBadge = wrapper.find('.badge-info')
      expect(reliableBadge.exists()).toBe(true)
      expect(reliableBadge.text()).toContain('Reliable')
      
      const developingBadge = wrapper.find('.badge-warning')
      expect(developingBadge.exists()).toBe(true)
      expect(developingBadge.text()).toContain('Developing')
      
      const underperformingBadge = wrapper.find('.badge-danger')
      expect(underperformingBadge.exists()).toBe(true)
      expect(underperformingBadge.text()).toContain('Underperforming')
    })
    
    test('maps scores to correct performance levels', async () => {
      const wrapper = mount(OnlineCourseEvaluation, {
        localVue,
        propsData: {
          course: { id: 1, title: 'Test Course' },
          users: [{ id: 1, name: 'Test User' }],
          performanceLevels: performanceLevels
        },
        stubs: ['inertia-link']
      })
      
      // Test score to performance level mapping
      expect(wrapper.vm.getPerformanceLevelForScore(14)).toBe(1) // Outstanding
      expect(wrapper.vm.getPerformanceLevelForScore(11)).toBe(2) // Reliable
      expect(wrapper.vm.getPerformanceLevelForScore(8)).toBe(3)  // Developing
      expect(wrapper.vm.getPerformanceLevelForScore(5)).toBe(4)  // Underperforming
    })
  })
  
  describe('UserEvaluation.vue', () => {
    test('displays performance level dropdown instead of monetary amount', async () => {
      const wrapper = mount(UserEvaluation, {
        localVue,
        propsData: {
          user: { id: 1, name: 'Test User' },
          performanceLevels: performanceLevels
        },
        stubs: ['inertia-link']
      })
      
      // Check for performance level dropdown
      const dropdown = wrapper.find('select[name="performance_level"]')
      expect(dropdown.exists()).toBe(true)
      
      // Check dropdown options
      const options = dropdown.findAll('option')
      expect(options.length).toBe(5) // 4 levels + default option
      
      // Check option values and text
      const outstandingOption = options.at(1)
      expect(outstandingOption.attributes('value')).toBe('1')
      expect(outstandingOption.text()).toContain('Outstanding')
      expect(outstandingOption.text()).toContain('13-15')
    })
    
    test('updates performance level when score changes', async () => {
      const wrapper = mount(UserEvaluation, {
        localVue,
        propsData: {
          user: { id: 1, name: 'Test User' },
          performanceLevels: performanceLevels
        },
        stubs: ['inertia-link']
      })
      
      // Set total score to 14
      wrapper.vm.form.total_score = 14
      await wrapper.vm.$nextTick()
      
      // Check that performance level was updated to Outstanding
      expect(wrapper.vm.form.performance_level).toBe(1)
      
      // Change score to 11
      wrapper.vm.form.total_score = 11
      await wrapper.vm.$nextTick()
      
      // Check that performance level was updated to Reliable
      expect(wrapper.vm.form.performance_level).toBe(2)
    })
  })
  
  describe('Notifications.vue', () => {
    test('renders performance level badges in notifications', async () => {
      const notifications = [
        {
          id: 1,
          user: { name: 'Test User' },
          total_score: 14,
          performance_level: 1
        }
      ]
      
      const wrapper = mount(Notifications, {
        localVue,
        propsData: {
          notifications,
          performanceLevels: performanceLevels
        },
        stubs: ['inertia-link']
      })
      
      // Check for performance level badge
      const badge = wrapper.find('.badge-success')
      expect(badge.exists()).toBe(true)
      expect(badge.text()).toContain('Outstanding')
    })
    
    test('filters notifications by performance level', async () => {
      const notifications = [
        {
          id: 1,
          user: { name: 'User 1' },
          total_score: 14,
          performance_level: 1 // Outstanding
        },
        {
          id: 2,
          user: { name: 'User 2' },
          total_score: 11,
          performance_level: 2 // Reliable
        },
        {
          id: 3,
          user: { name: 'User 3' },
          total_score: 8,
          performance_level: 3 // Developing
        }
      ]
      
      const wrapper = mount(Notifications, {
        localVue,
        propsData: {
          notifications,
          performanceLevels
        },
        stubs: ['inertia-link']
      })
      
      // Filter by Outstanding
      wrapper.vm.filterByPerformanceLevel(1)
      await wrapper.vm.$nextTick()
      
      // Check that only Outstanding notifications are shown
      expect(wrapper.vm.filteredNotifications.length).toBe(1)
      expect(wrapper.vm.filteredNotifications[0].id).toBe(1)
      
      // Filter by Reliable
      wrapper.vm.filterByPerformanceLevel(2)
      await wrapper.vm.$nextTick()
      
      // Check that only Reliable notifications are shown
      expect(wrapper.vm.filteredNotifications.length).toBe(1)
      expect(wrapper.vm.filteredNotifications[0].id).toBe(2)
    })
  })
})