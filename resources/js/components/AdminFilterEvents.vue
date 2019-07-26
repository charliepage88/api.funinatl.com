<template>
  <div class="columns is-multiline">
    <div class="column is-full">
      <h1 class="title is-1">Events</h1>
    </div>

    <div class="column" v-if="categories.length">
      <b-field>
        <b-select
          v-model="category_id"
          icon="tasks"
          icon-pack="fas"
          size="is-large"
          @input="filterUpdate('category_id')"
          class="is-fullwidth"
        >
          <option value="null">Choose Category</option>
          <option
            v-for="category in categories"
            :key="category.id"
            :value="category.id"
          >
            {{ category.name }}
          </option>
        </b-select>
      </b-field>
    </div>

    <div class="column">
      <b-field>
        <b-select
          v-model="location_id"
          icon="tasks"
          icon-pack="fas"
          size="is-large"
          @input="filterUpdate('location_id')"
          class="is-fullwidth"
        >
          <option value="null">Choose Location</option>
          <option
            v-for="location in locations"
            :key="location.id"
            :value="location.id"
          >
            {{ location.name }}
          </option>
        </b-select>
      </b-field>
    </div>

    <div class="column">
      <b-field>
        <b-select
          v-model="source"
          icon="cogs"
          icon-pack="fas"
          size="is-large"
          @input="filterUpdate('source')"
          class="is-fullwidth"
        >
          <option value="null">Choose Source</option>
          <option value="provider">Provider</option>
          <option value="submission">Submission</option>
          <option value="custom">Custom</option>
        </b-select>
      </b-field>
    </div>

    <div class="column is-narrow has-text-right" v-if="createEventUrl">
      <a
        :href="createEventUrl"
        class="button is-success is-large is-fullwidth-mobile"
      >
        <span class="icon">
          <i class="fas fa-plus"></i>
        </span>
        <span>Create Event</span>
      </a>
    </div>
  </div>
</template>

<script>
export default {
  name: 'admin-filter-events',

  props: [
    'categoriesJson',
    'locationsJson',
    'createEventUrl'
  ],

  computed: {
    categories () {
      return JSON.parse(this.categoriesJson)
    },

    locations () {
      return JSON.parse(this.locationsJson)
    }
  },

  data () {
    return {
      category_id: null,
      location_id: null,
      source: null,
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

    if (urlParams.get('category_id')) {
      this.category_id = urlParams.get('category_id')
    }

    if (urlParams.get('location_id')) {
      this.location_id = urlParams.get('location_id')
    }

    if (urlParams.get('source')) {
      this.source = urlParams.get('source')
    }
  }
}
</script>
