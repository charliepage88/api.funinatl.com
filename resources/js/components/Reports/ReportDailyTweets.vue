<template>
  <div class="columns is-multiline">
    <div class="column is-full">
      <h1 class="title is-1">Report - Daily Tweets</h1>
    </div>

    <div class="column">
      <b-field label="Start Date">
        <form-date-picker
          name="start_date"
          v-model="filters.start_date"
          @input="updateFilter('start_date')"
        />
      </b-field>
    </div>

    <div class="column">
      <b-field label="End Date">
        <form-date-picker
          name="end_date"
          v-model="filters.end_date"
          @input="updateFilter('end_date')"
        />
      </b-field>
    </div>

    <div class="column is-full">
      <div class="tile is-ancestor" v-if="hasDates">
        <div class="tile is-parent is-12" v-for="(date, events) in dates" :key="date">
          <article class="tile is-child box">
            <p class="title">{{ date }}</p>
            
            <pre>{{ event }}</pre>
          </article>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import isEmpty from 'lodash.isempty'

export default {
  name: 'report-daily-tweets',

  props: [
    'reportJson'
  ],

  computed: {
    dates () {
      return JSON.parse(this.reportJson)
    },

    hasDates () {
      return !isEmpty(this.dates)
    }
  },

  data () {
    return {
      filters: {
        start_date: null,
        end_date: null
      },
      urlParams: new URLSearchParams(window.location.search)
    }
  },

  methods: {
    filterUpdate (key) {
      let value = this[key]

      if (this.urlParams.get(key)) {
        if (!value || value === null || value === 'null') {
          this.urlParams.delete(key)
        } else {
          this.urlParams.set(key, value)
        }
      } else {
        this.urlParams.append(key, value)
      }

      let query = this.urlParams.toString()

      if (!query.length) {
        window.location = window.location.pathname
      } else {
        window.location = `${window.location.pathname}?${query}`
      }
    }
  },

  mounted () {
    const urlParams = new URLSearchParams(window.location.search)

    for (let key in this.filters) {
      if (urlParams.get(key)) {
        this.$set(this.filters, key, urlParams.get(key))
      }
    }
  }
}
</script>
