<template>
  <line-chart :chartData="chartData" :chartOptions="chartOptions" />
</template>

<script>
import LineChart from './LineChart.js'

export default {
  name: 'chart-upcoming-events-slow-days',

  props: [
    'dataJson',
    'optionsJson'
  ],

  components: {
    LineChart
  },

  computed: {
    chartData () {
      return JSON.parse(this.dataJson)
    },

    chartOptions () {
      return JSON.parse(this.optionsJson)
    }
  }
}
</script>
