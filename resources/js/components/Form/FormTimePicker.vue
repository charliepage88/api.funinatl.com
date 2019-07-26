<template>
  <div class="control">
    <b-timepicker
      v-model="time"
      hour-format="12"
      size="is-medium"
      icon="clock"
      icon-pack="fas"
      :mobile-native="false"
    />

    <input type="hidden" :name="name" v-model="timeHidden" />
  </div>
</template>

<script>
import moment from 'moment'

export default {
  name: 'form-time-picker',

  props: [
    'name',
    'value'
  ],

  watch: {
    value (newVal, oldVal) {
      if (newVal && (newVal !== oldVal)) {
        this.time = moment(newVal).toDate()
      }
    },

    time (newVal, oldVal) {
      if (newVal) {
        this.timeHidden = moment(newVal).format('h:mm A')
      } else {
        this.timeHidden = null
      }
    }
  },

  data () {
    return {
      time: null,
      timeHidden: null
    }
  },

  mounted () {
    if (this.value && (this.value !== this.time)) {
      this.time = moment(this.value).toDate()
    }
  }
}
</script>
