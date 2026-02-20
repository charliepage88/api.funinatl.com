<template>
  <div class="control">
    <input ref="fpInput" type="text" class="input is-medium" />
    <input type="hidden" :name="name" :value="timeHidden" />
  </div>
</template>

<script>
import moment from 'moment'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'

export default {
  name: 'form-time-picker',

  emits: ['update:modelValue'],

  props: ['name', 'modelValue'],

  data () {
    return {
      timeHidden: null,
      fp: null
    }
  },

  watch: {
    modelValue (newVal) {
      if (newVal && this.fp) {
        this.fp.setDate(moment(newVal).toDate(), false)
        this.timeHidden = moment(newVal).format('h:mm A')
      }
    }
  },

  mounted () {
    this.fp = flatpickr(this.$refs.fpInput, {
      enableTime: true,
      noCalendar: true,
      dateFormat: 'h:i K',
      defaultDate: this.modelValue ? moment(this.modelValue).toDate() : null,
      onChange: (selectedDates, dateStr) => {
        this.timeHidden = dateStr
        this.$emit('update:modelValue', dateStr)
      }
    })

    if (this.modelValue) {
      this.timeHidden = moment(this.modelValue).format('h:mm A')
    }
  },

  unmounted () {
    if (this.fp) {
      this.fp.destroy()
    }
  }
}
</script>
