<template>
  <div class="row columns is-multiline" v-if="events.length">
    <div
      class="column has-cursor-pointer is-half-tablet is-one-third-desktop is-one-third-widescreen is-one-third-fullhd"
      v-for="(event, key) in events"
      :key="`event-${event.slug}`"
      :class="{ 'is-primary': event.is_tweetable }"
      @click.prevent="markTweetable(key, event.id)"
    >
      <div class="card large">
        <div class="card-image" v-if="event">
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
            <div class="media-left" v-if="event.location.thumb_small">
              <figure class="image is-128x128">
                <img :alt="event.location.name" :src="event.location.thumb_small">
              </figure>

              <span class="tag is-success is-medium is-w-128">
                {{ event.category.name }}
              </span>
            </div>

            <div class="media-content">
              <h4 class="title is-3 is-size-3-desktop is-size-2-tablet is-size-4-mobile is-capitalized">
                {{ event.name }}
              </h4>

              <h5 class="subtitle is-5 is-size-5-desktop is-size-3-tablet is-size-5-mobile is-capitalized">
                {{ event.location.name }}
              </h5>
            </div>
          </div>

          <div class="content">
            <!-- start/end date -->
            <h4 class="title is-5 is-size-5-mobile has-text-normal has-text-grey-dark has-text-centered" v-if="event.end_date">
              {{ event.start_date | fullDate }} - {{ event.end_date | fullDate }}
            </h4>

            <h4 class="title is-5 is-size-5-mobile has-text-normal has-text-grey-dark has-text-centered" v-else>
              {{ event.start_date | fullDate }}
            </h4>

            <!-- start time/end time -->
            <h4 class="subtitle is-5 is-size-5-mobile has-text-normal has-text-grey-light has-text-centered" v-if="event.end_time">
              {{ event.start_time }} - {{ event.end_time }}
            </h4>

            <h4 class="subtitle is-5 is-size-5-mobile has-text-normal has-text-grey-light has-text-centered" v-else>
              {{ event.start_time }}
            </h4>

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
            <p class="has-text-grey-dark mt-2" v-if="event.short_description">
              {{ event.short_description | truncate(200) }}
            </p>

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
    markTweetable (key, id) {
      
    }
  }
}
</script>

<style lang="sass" scoped>
.event-image
  width: 100%!important
  height: 250px!important
</style>
