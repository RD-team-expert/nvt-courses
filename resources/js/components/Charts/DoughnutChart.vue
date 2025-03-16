<script setup>
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement, CategoryScale } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale)

const props = defineProps({
  chartData: {
    type: Object,
    required: true
  },
  chartOptions: {
    type: Object,
    default: () => ({
      responsive: true,
      maintainAspectRatio: false
    })
  },
  chartId: {
    type: String,
    default: 'doughnut-chart'
  },
  width: {
    type: Number,
    default: 400
  },
  height: {
    type: Number,
    default: 400
  },
  cssClasses: {
    default: '',
    type: String
  },
  styles: {
    type: Object,
    default: () => ({})
  },
  plugins: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <Doughnut
    :data="chartData"
    :options="chartOptions"
    :chart-id="chartId"
    :width="width"
    :height="height"
    :css-classes="cssClasses"
    :styles="styles"
    :plugins="plugins"
    class="h-full"
  />
</template>