<template>
  <div class="control">
    <input ref="fpInput" type="text" class="input is-medium" />
    <input type="hidden" v-if="name" :name="name" :value="dateHidden" />
  </div>
</template>

<script>
import moment from 'moment'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'

export default {
  name: 'form-date-picker',

  emits: ['update:modelValue'],

  props: {
    name: {
      type: String,
      default: null
    },

    modelValue: {
      type: String,
      default: () => moment().format('YYYY-MM-DD')
    }
  },

  data () {
    return {
      dateHidden: null,
      fp: null
    }
  },

  watch: {
    modelValue (newVal) {
      if (newVal && this.fp) {
        this.fp.setDate(newVal, false)
        this.dateHidden = moment(newVal).format('YYYY-MM-DD')
      }
    }
  },

  mounted () {
    this.fp = flatpickr(this.$refs.fpInput, {
      dateFormat: 'Y-m-d',
      minDate: moment().subtract(1, 'day').toDate(),
      maxDate: moment().add(4, 'month').toDate(),
      defaultDate: this.modelValue || moment().format('YYYY-MM-DD'),
      onChange: (selectedDates, dateStr) => {
        this.dateHidden = dateStr
        this.$emit('update:modelValue', dateStr)
      }
    })

    if (this.modelValue) {
      this.dateHidden = moment(this.modelValue).format('YYYY-MM-DD')
    }
  },

  unmounted () {
    if (this.fp) {
      this.fp.destroy()
    }
  }
}
</script>
