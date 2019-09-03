<script>
import { Line } from 'vue-chartjs'

export default {
  name: 'chart-upcoming-events-slow-days',

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
      let options = JSON.parse(this.optionsJson)

      options.responsive = true
      options.styles = {
        'position': 'relative',
        'height': '400px'
      }
      options.height = 400

      return options
    }
  },

  mounted () {
    this.renderChart(this.chartData, this.chartOptions)
  }
}
</script>
