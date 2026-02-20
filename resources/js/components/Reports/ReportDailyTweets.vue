<template>
  <div class="columns is-multiline" style="position: relative">
    <div class="column is-full-tablet is-half-desktop">
      <h1 class="title is-1 is-size-3-mobile is-size-2-tablet">Report - Daily Tweets</h1>
    </div>

    <div class="column is-half-tablet is-one-quarter-desktop">
      <div class="field">
        <label class="label">Start Date</label>
        <form-date-picker
          name="start_date"
          v-model="filters.start_date"
          @update:modelValue="updateFilter('start_date')"
        />
      </div>
    </div>

    <div class="column is-half-tablet is-one-quarter-desktop">
      <div class="field">
        <label class="label">End Date</label>
        <form-date-picker
          name="end_date"
          v-model="filters.end_date"
          @update:modelValue="updateFilter('end_date')"
        />
      </div>
    </div>

    <div class="column is-full" v-if="hasDates">
      <div class="centered-container pl-computer-4 pr-computer-4 pt-0">
        <div v-for="(row, periodKey) in dates" :key="row.label">
          <h3
            class="subtitle has-text-centered is-3 mt-0 mb-0"
          >
            {{ row.label }}
          </h3>

          <template v-if="row.days && row.days.length">
            <div v-for="(day, dayKey) in row.days" :key="day.date" :id="`events-${day.date}`">
              <nav class="level">
                <div class="level-left">
                  <h4 class="subtitle is-4 mb-2 mt-2">
                    {{ $filters.dayOfWeek(day.date) }}
                  </h4>
                </div>
              </nav>

              <events-list :events="day.events" @markTweetable="markTweetable" />

              <template v-if="day.tweetable_event_ids && day.tweetable_event_ids.length">
                <div class="columns">
                  <div class="column is-half">
                    <h4 class="title is-6">Tweet Content</h4>
                    <h5 class="subtitle is-6">For: {{ $filters.dayOfWeek(day.date) }}</h5>
                  </div>

                  <div class="column is-half">
                    <article class="media">
                      <div class="media-content">
                        <div class="field">
                          <p class="control">
                            <textarea class="textarea" v-model="dates[periodKey].days[dayKey].tweet_content" rows="3" maxlength="280" />
                          </p>
                        </div>

                        <button
                          class="button is-primary is-fullwidth"
                          @click.prevent="save(periodKey, dayKey)"
                        >
                          Save
                        </button>
                      </div>
                    </article>
                  </div>
                </div>
              </template>
            </div>
          </template>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-overlay">
      <div class="loading-icon"></div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import moment from 'moment'
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
        start_date: moment().format('YYYY-MM-DD'),
        end_date: moment().add(14, 'days').format('YYYY-MM-DD')
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

        const resp = await axios.get('/api/reports/daily-tweets', {
          params: this.filters
        })

        if (resp.data.report) {
          this.dates = resp.data.report
        }
      } catch (err) {
        console.error(err)
      } finally {
        this.stopLoading()
      }
    },

    async save (periodKey, dayKey) {
      try {
        this.startLoading()

        let date = this.dates[periodKey].days[dayKey]

        let form = {
          start_date: this.filters.start_date,
          end_date: this.filters.end_date,
          event_ids: date.event_ids,
          tweetable_event_ids: date.tweetable_event_ids,
          tweet_content: date.tweet_content
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
    },

    markTweetable (id, value) {
      if (!this.hasDates) {
        return false
      }

      for (let periodKey in this.dates) {
        let days = this.dates[periodKey].days

        for (let dayKey in days) {
          let tweetableEventIds = []
          let day = this.dates[periodKey].days[dayKey]
          let tweet = day.tweet_content
          let generateTweet = true
          let events = this.dates[periodKey].days[dayKey].events

          if (generateTweet) {
            tweet = `Happy Monday Atlanta! Today we've got `
          }

          for (let eventKey in events) {
            let event = events[eventKey]

            if (event.id === id) {
              tweetableEventIds.push(event.id)

              this.dates[periodKey].days[dayKey].events[eventKey].is_tweetable = value

              if (generateTweet) {
                tweet += `${event.name} at ${event.location.name}, `
              }
            } else if (event.is_tweetable) {
              tweetableEventIds.push(event.id)

              if (generateTweet) {
                tweet += `${event.name} at ${event.location.name}, `
              }
            }
          }

          if (generateTweet) {
            tweet = tweet.slice(0, -2)
            tweet += ', and more!'
          }

          this.dates[periodKey].days[dayKey].tweetable_event_ids = tweetableEventIds
          this.dates[periodKey].days[dayKey].tweet_content = tweet
        }
      }
    }
  },

  async mounted () {
    await this.getReportData()

    const urlParams = new URLSearchParams(window.location.search)

    for (let key in this.filters) {
      if (urlParams.get(key)) {
        this.filters[key] = urlParams.get(key)
      }
    }
  }
}
</script>

<style scoped>
.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.6);
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading-icon {
  width: 3em;
  height: 3em;
  border: 4px solid #dbdbdb;
  border-top-color: #485fc7;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
