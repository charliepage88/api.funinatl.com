<template>
  <a :href="url" @click.prevent="clickOnButton" class="button is-danger">
    <span class="icon">
      <i class="fas fa-trash"></i>
    </span>
    <span>Delete</span>
  </a>
</template>

<script>
export default {
  name: 'admin-delete-button',

  props: [
    'url'
  ],

  methods: {
    clickOnButton () {
      let message = 'Are you sure you want to delete this item?'

      if (confirm(message)) {
        window.location = this.url
      } else {
        return false
      }
    }
  }
}
</script>
