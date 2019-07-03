<template>
  <div class="flex flex-wrap">
    <div class="w-1/4 text-left mr-4">
      <h1 class="bg-brand font-bold text-3xl md:text-5xl px-3 mt-0">Locations</h1>
    </div>

    <div class="w-1/5 mr-4 mt-4" v-if="categories.length">
      <div class="relative">
        <select
          class="block appearance-none w-full bg-gray-200 border border-gray-200 text-gray-700 py-3 px-4 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          v-model="category_id"
          @change="filterUpdate('category_id')"
        >
          <option value="null">Choose Category</option>
          <option
            v-for="category in categories"
            :key="category.id"
            :value="category.id"
          >{{ category.name }}</option>
        </select>

        <div
          class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
        >
          <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
            <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
          </svg>
        </div>
      </div>
    </div>

    <div class="w-auto mr-16 mt-4">
      <div class="relative">
        <select
          class="block appearance-none w-full bg-gray-200 border border-gray-200 text-gray-700 py-3 px-4 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          v-model="source"
          @change="filterUpdate('source')"
        >
          <option value="null">Choose Source</option>
          <option value="submission">Submission</option>
          <option value="custom">Custom</option>
        </select>

        <div
          class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
        >
          <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
            <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
          </svg>
        </div>
      </div>
    </div>

    <div class="w-auto mt-6" v-if="createLocationUrl">
      <a
        :href="createLocationUrl"
        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded"
      >
        <i class="fa fa-plus mr-1"></i>
        <span>Create Location</span>
      </a>
    </div>
  </div>
</template>

<script>
export default {
  name: 'admin-filter-locations',

  props: [
    'categoriesJson',
    'createLocationUrl'
  ],

  computed: {
    categories () {
      return JSON.parse(this.categoriesJson)
    }
  },

  data () {
    return {
      category_id: null,
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

    if (urlParams.get('source')) {
      this.source = urlParams.get('source')
    }
  }
}
</script>
