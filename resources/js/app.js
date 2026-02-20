/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

import { createApp } from 'vue'
import moment from 'moment'

const app = createApp({})

app.config.globalProperties.$filters = {
  date: (val) => moment(val).format('MMMM Do YYYY, h:mm a'),
  timeAgo: (val) => moment(val).fromNow(),
  truncate: (text, stop, clamp) => text.slice(0, stop) + (stop < text.length ? clamp || '...' : ''),
  normalDate: (value) => moment(value).format('dddd, MMMM Do'),
  dayOfWeek: (value) => moment(value).format('dddd, MMM Do'),
  friendlyDate: (value) => moment(value).format('dddd, MMM Do'),
  fullDate: (value) => moment(value).format('dddd, MMMM Do'),
  friendlyTime: (value) => moment(value).format('h:mm A')
}

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i);
// files.keys().map(key => app.component(key.split('/').pop().split('.')[0], files(key).default));

// admin components
app.component('admin-filter-events', require('./components/AdminFilterEvents.vue').default);
app.component('admin-filter-locations', require('./components/AdminFilterLocations.vue').default);
app.component('form-time-picker', require('./components/Form/FormTimePicker.vue').default);
app.component('form-date-picker', require('./components/Form/FormDatePicker.vue').default);
app.component('admin-delete-button', require('./components/Common/AdminDeleteButton.vue').default);

// chart components
app.component('chart-events-timeline', require('./components/Charts/ChartEventsTimeline.vue').default);
app.component('chart-upcoming-events-slow-days', require('./components/Charts/ChartUpcomingEventsSlowDays.vue').default);

// admin report components
app.component('report-daily-tweets', require('./components/Reports/ReportDailyTweets.vue').default);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

app.mount('#app');

// bulma navbar burger
if (document.getElementById('appNavMenu')) {
  document.addEventListener('DOMContentLoaded', () => {
    // Get all "navbar-burger" elements
    const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0)

    // Check if there are any navbar burgers
    if ($navbarBurgers.length > 0) {

      // Add a click event on each of them
      $navbarBurgers.forEach( el => {
        el.addEventListener('click', () => {

          // Get the target from the "data-target" attribute
          const target = el.dataset.target
          const $target = document.getElementById(target)

          // Toggle the "is-active" class on both the "navbar-burger" and the "navbar-menu"
          el.classList.toggle('is-active')
          $target.classList.toggle('is-active')

        })
      })
    }
  })
}
