import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useAttendance(clockings = []) {
  const isLoading = ref(false);
  const isSubmitting = ref(false);
  const showClockOutModal = ref(false);
  const clockOutForm = ref({
    rating: 3,
    comment: ''
  });

  const activeSession = computed(() => {
    return clockings.some(c => c.clock_out === null);
  });

  const activeSessionData = computed(() => {
    return clockings.find(c => c.clock_out === null) || {};
  });

  const recentClockings = computed(() => {
    // Get the 5 most recent clockings
    return [...clockings]
      .sort((a, b) => new Date(b.clock_in) - new Date(a.clock_in))
      .slice(0, 5);
  });

  function formatDate(dateString) {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleString();
  }

  function formatDateShort(dateString) {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit'
    });
  }

  function formatDuration(minutes) {
    if (!minutes) return '—';
    
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    
    if (hours > 0) {
      return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
  }

  async function clockIn() {
    try {
      isLoading.value = true;
      router.post('/clock-in', {}, {
        onSuccess: () => {
          console.log('Clock in successful');
        },
        onError: (errors) => {
          console.error('Clock in failed:', errors);
          alert('Failed to clock in. Please try again.');
        },
        onFinish: () => {
          isLoading.value = false;
        }
      });
    } catch (error) {
      console.error('Clock in exception:', error);
      isLoading.value = false;
    }
  }

  function openClockOutDialog() {
    showClockOutModal.value = true;
    clockOutForm.value = {
      rating: 3,
      comment: ''
    };
  }

  async function submitClockOut() {
    if (!clockOutForm.value.rating) {
      alert('Please provide a rating');
      return;
    }

    try {
      isSubmitting.value = true;
      router.post('/clock-out', {
        rating: clockOutForm.value.rating,
        comment: clockOutForm.value.comment
      }, {
        onSuccess: () => {
          console.log('Clock out successful');
          showClockOutModal.value = false;
        },
        onError: (errors) => {
          console.error('Clock out failed:', errors);
          alert('Failed to clock out. Please try again.');
        },
        onFinish: () => {
          isSubmitting.value = false;
        }
      });
    } catch (error) {
      console.error('Clock out exception:', error);
      isSubmitting.value = false;
    }
  }

  return {
    isLoading,
    isSubmitting,
    showClockOutModal,
    clockOutForm,
    activeSession,
    activeSessionData,
    recentClockings,
    formatDate,
    formatDateShort,
    formatDuration,
    clockIn,
    openClockOutDialog,
    submitClockOut
  };
}