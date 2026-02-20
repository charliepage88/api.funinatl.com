import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
)

export default {
  name: 'line-chart',

  components: { Line },

  props: {
    chartData: {
      type: Object,
      required: true
    },

    chartOptions: {
      type: Object,
      default: () => ({})
    }
  },

  template: '<Line :data="chartData" :options="chartOptions" />'
}
