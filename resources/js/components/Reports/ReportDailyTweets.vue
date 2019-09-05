<template>
  <div class="columns is-multiline">
    <div class="column is-half">
      <h1 class="title is-1">Report - Daily Tweets</h1>
    </div>

    <div class="column is-one-quarter">
      <b-field label="Start Date">
        <form-date-picker
          name="start_date"
          v-model="filters.start_date"
          @input="updateFilter('start_date')"
        />
      </b-field>
    </div>

    <div class="column is-one-quarter">
      <b-field label="End Date">
        <form-date-picker
          name="end_date"
          v-model="filters.end_date"
          @input="updateFilter('end_date')"
        />
      </b-field>
    </div>

    <div class="column is-full" v-if="hasDates">
      <div class="centered-container pl-computer-4 pr-computer-4 pl-handheld-1 pr-handheld-1 pt-0">
        <div v-for="row in dates" :key="row.label">
          <h3
            class="subtitle has-text-centered is-2 mt-4"
          >
            {{ row.label }}
          </h3>

          <template v-if="row.days && row.days.length">
            <div v-for="day in row.days" :key="day.date" :id="`events-${day.date}`">
              <nav class="level">
                <div class="level-left">
                  <h4 class="subtitle is-4 mb-2 mt-mobile-2 mt-tablet-3 mt-computer-3">
                    {{ day.date | dayOfWeek }}
                  </h4>
                </div>
              </nav>

              <events-list :events="day.events" />
            </div>
          </template>
        </div>
      </div>
    </div>

    <b-loading :active.sync="loading" />
  </div>
</template>

<script>
import axios from 'axios'
import isEmpty from 'lodash.isempty'
import FormDatePicker from '../Form/FormDatePicker'
import EventsList from '../Common/EventsList'

export default {
  name: 'report-daily-tweets',

  components: {
    FormDatePicker,
    EventsList
  },

  computed: {
    hasDates () {
      return !isEmpty(this.dates)
    }
  },

  data () {
    return {
      dates: {},
      loading: false,
      filters: {
        start_date: null,
        end_date: null
      },
      form: {

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
    },

    async getReportData () {
      try {
        this.startLoading()

        const resp = await axios.get('/api/reports/daily-tweets')

        if (resp.data.report) {
          this.dates = resp.data.report
        }
      } catch (err) {
        console.error(err)
      } finally {
        this.stopLoading()
      }
    },

    async save () {
      try {
        this.startLoading()

        let form = {
          start_date: this.start_date,
          end_date: this.end_date,
          event_ids: []
        }

        const resp = await axios.post('/api/reports/daily-tweets', form)

        if (resp.data.report) {
          this.dates = resp.data.report
        }
      } catch (err) {
        console.error(err)
      } finally {
        this.stopLoading()
      }
    },

    startLoading () {
      this.loading = true
    },

    stopLoading () {
      this.loading = false
    }
  },

  async mounted () {
    await this.getReportData()

    const urlParams = new URLSearchParams(window.location.search)

    for (let key in this.filters) {
      if (urlParams.get(key)) {
        this.$set(this.filters, key, urlParams.get(key))
      }
    }
  }
}
</script>
