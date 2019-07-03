<template>
  <div>
    <flat-pickr :class="config.altInputClass" v-model="date" :config="config"></flat-pickr>
  </div>
</template>

<script>
  import flatPickr from 'vue-flatpickr-component'
  import 'flatpickr/dist/flatpickr.css'

  export default {
    name: 'date-picker',

    props: [
      'value',
      'inputId'
    ],

    watch: {
      value (newVal, oldVal) {
        if (newVal && newVal !== oldVal) {
          this.updateInput(newVal)

          this.date = newVal
        }
      },

      date (newVal, oldVal) {
        if (newVal && newVal !== oldVal) {
          this.updateInput(newVal)

          this.$emit('change', newVal)
        }
      }
    },

    data () {
      return {
        date: null,
        config: {
          altInputClass: 'appearance-none block w-full bg-gray-200 text-gray-700 rounded py-3 px-4 h-12',
          dateFormat: 'Y-m-d'
        }
      }
    },

    components: {
      flatPickr
    },

    methods: {
      updateInput (value) {
        if (this.inputId && document.getElementById(this.inputId)) {
          document.getElementById(this.inputId).value = value
        }
      }
    },

    mounted () {
      if (this.value) {
        this.date = this.value
      }
    }
  }
</script>
