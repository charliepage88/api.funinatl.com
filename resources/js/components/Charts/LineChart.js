import { Line } from 'vue-chartjs'

export default {
  extends: Line,

  props: {
    chartData: {
      type: Object|Array
    },

    chartOptions: {
      type: Object|Array
    },

    height: {
      type: String|Number,
      default: 200
    }
  },

  mounted () {
    this.renderChart(this.chartData, this.chartOptions)
  }
}
