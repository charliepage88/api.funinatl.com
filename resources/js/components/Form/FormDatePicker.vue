<template>
  <div class="control">
    <b-datepicker
      v-model="date"
      :min-date="minDate"
      :max-date="maxDate"
      :first-day-of-week="1"
      size="is-medium"
      :mobile-native="false"
      :date-formatter="formatDate"
      @input="update"
    />

    <input type="hidden" v-if="name" :name="name" v-model="dateHidden" />
  </div>
</template>

<script>
import moment from 'moment'

export default {
  name: 'form-date-picker',

  props: {
    name: {
      type: String,
      default: null
    },

    value: {
      type: String,
      default: moment().format('YYYY-MM-DD')
    }
  },

  watch: {
    value (newVal, oldVal) {
      if (newVal && newVal !== oldVal) {
        this.date = moment(newVal).toDate()
      }
    },

    date (newVal, oldVal) {
      if (newVal) {
        this.dateHidden = this.formatDate(newVal)
      } else {
        this.dateHidden = null
      }
    }
  },

  data () {
    return {
      date: null,
      dateHidden: null,
      minDate: moment().subtract(1, 'day').toDate(),
      maxDate: moment().add(4, 'month').toDate()
    }
  },

  methods: {
    formatDate (date) {
      return moment(date).format('YYYY-MM-DD')
    },

    update (value) {
      this.$emit('input', value)
    }
  },

  mounted () {
    if (this.value && this.value !== this.date) {
      this.date = moment(this.value).toDate()
    }
  }
}
</script>
