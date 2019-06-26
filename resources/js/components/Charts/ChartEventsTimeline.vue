<script>
import { Line } from 'vue-chartjs'

export default {
  name: 'chart-events-timeline',

  extends: Line,

  props: [
    'dataJson',
    'optionsJson'
  ],

  computed: {
    chartData () {
      return JSON.parse(this.dataJson)
    },

    chartOptions () {
      return JSON.parse(this.optionsJson)
    }
  },

  mounted () {
    this.renderChart(this.chartData, this.chartOptions)
  }
}
</script>
