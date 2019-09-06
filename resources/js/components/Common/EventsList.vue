<template>
  <div class="row columns is-multiline" v-if="events.length">
    <div
      class="column has-cursor-pointer is-half-tablet is-one-third-desktop is-one-third-widescreen is-one-third-fullhd"
      v-for="event in events"
      :key="`event-${event.slug}`"
      @click.prevent="markTweetable(event.id, event.is_tweetable)"
    >
      <div
        class="card large"
        :class="{ 'has-background-primary has-text-white': event.is_tweetable }"
      >
        <div class="card-image" v-if="event && 1 === 2">
          <figure class="image is-visible-mobile">
            <img :alt="event.name" class="event-image" :src="event.thumb_mobile">
          </figure>

          <figure class="image is-visible-tablet">
            <img :alt="event.name" class="event-image" :src="event.thumb_tablet">
          </figure>

          <figure class="image is-visible-computer">
            <img :alt="event.name" class="event-image" :src="event.thumb_desktop">
          </figure>
        </div>

        <div class="card-content">
          <div class="media">
            <div class="media-left" v-if="1 === 2 && event.location.thumb_small">
              <figure class="image is-128x128">
                <img :alt="event.location.name" :src="event.location.thumb_small">
              </figure>

              <span class="tag is-success is-medium is-w-128">
                {{ event.category.name }}
              </span>
            </div>

            <div class="media-content">
              <span class="tag is-success is-medium is-fullwidth has-text-centered mb-1">
                {{ event.category.name }}
              </span>

              <h4 class="title is-3 is-size-3-desktop is-size-5-tablet is-size-4-mobile is-capitalized" :class="{ 'has-text-white': event.is_tweetable }">
                {{ event.name }}
              </h4>

              <h5 class="subtitle is-5 is-size-5-desktop is-size-6-tablet is-size-5-mobile is-capitalized" :class="{ 'has-text-white': event.is_tweetable }">
                {{ event.location.name }}
              </h5>
            </div>
          </div>

          <div class="content">
            <!-- start/end date -->
            <h4 class="title is-5 is-size-5-mobile is-size-6-tablet has-text-normal has-text-centered" v-if="event.end_date" :class="{ 'has-text-white': event.is_tweetable }">
              {{ event.start_date | fullDate }} - {{ event.end_date | fullDate }}
            </h4>

            <h4 class="title is-5 is-size-5-mobile is-size-6-tablet has-text-normal has-text-centered" :class="{ 'has-text-white': event.is_tweetable }" v-else>
              {{ event.start_date | fullDate }}
            </h4>

            <!-- start time/end time -->
            <span class="subtitle is-5 is-size-5-mobile is-size-6-tablet block has-text-centered" v-if="event.end_time" :class="{ 'has-text-white': event.is_tweetable }">
              {{ event.start_time }} - {{ event.end_time }}
            </span>

            <span class="subtitle is-5 is-size-5-mobile is-size-6-tablet block has-text-centered" :class="{ 'has-text-white': event.is_tweetable }" v-else>
              {{ event.start_time }}
            </span>

            <!-- price -->
            <b-button
              type="is-light"
              class="is-centered"
              size="is-large"
            >
              {{ event.price }}
            </b-button>

            <!-- family friendly (if active) -->
            <div class="is-visible-touch" v-if="event.is_family_friendly">
              <b-button
                type="is-warning"
                icon-left="child"
                icon-pack="fas"
                size="is-medium"
                class="mt-1 is-centered"
              >
                Family Friendly
              </b-button>
            </div>

            <!-- descriptions -->
            <p class="mt-2 is-size-7-tablet" v-if="event.short_description">
              {{ event.short_description | truncate(200) }}
            </p>

            <!-- bands list (if any) -->
            <template v-if="event.bands && event.bands.length">
              <strong class="is-centered has-text-centered" :class="{ 'has-text-white': event.is_tweetable }">
                Bands
              </strong>

              <div class="tags is-centered has-text-centered mt-px-5">
                <span
                  v-for="band in event.bands"
                  :key="band.slug"
                  class="tag is-success is-small has-text-white has-no-underline"
                  :aria-label="band.name"
                >
                  {{ band.name }}
                </span>
              </div>
            </template>

            <!-- tags list (if any) - touch -->
            <div class="is-visible-touch tags mb-0 mt-1" v-if="event.tags.length">
              <span
                v-for="tag in event.tags"
                :key="tag.slug"
                class="tag block is-info is-small has-text-white has-no-underline"
                :aria-label="tag.name"
              >
                {{ tag.name }}
              </span>
            </div>

            <!-- tags list (if any) - computer -->
            <div class="is-visible-computer tags absolute bottom-5 left-10 mb-0 mt-1" v-if="event.tags.length">
              <span
                v-for="tag in event.tags"
                :key="tag.slug"
                class="tag block is-info is-small has-text-white has-no-underline"
                :aria-label="tag.name"
              >
                {{ tag.name }}
              </span>
            </div>

            <!-- family friendly (if active) - computer -->
            <div class="absolute bottom-10 right-10 is-visible-computer" v-if="event.is_family_friendly">
              <b-button
                type="is-warning"
                icon-left="child"
                icon-pack="fas"
                size="is-small"
              >
                Family Friendly
              </b-button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'events-list',

  props: {
    events: {
      type: Array
    }
  },

  methods: {
    markTweetable (id, value) {
      this.$emit('markTweetable', id, !value)
    }
  }
}
</script>

<style lang="sass" scoped>
.event-image
  width: 100%!important
  height: 250px!important
</style>
