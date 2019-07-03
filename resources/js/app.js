/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

// require('./bootstrap');

window.Vue = require('vue');

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i);
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default));

// admin components
Vue.component('admin-filter-events', require('./components/AdminFilterEvents.vue').default);
Vue.component('admin-filter-locations', require('./components/AdminFilterLocations.vue').default);
Vue.component('date-picker', require('./components/DatePicker.vue').default);

// chart components
Vue.component('chart-events-timeline', require('./components/Charts/ChartEventsTimeline.vue').default);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
});

/*Toggle dropdown list*/
/*https://gist.github.com/slavapas/593e8e50cf4cc16ac972afcbad4f70c8*/

var userMenuDiv = document.getElementById("userMenu");
var userMenu = document.getElementById("userButton");

var navMenuDiv = document.getElementById("nav-content");
var navMenu = document.getElementById("nav-toggle");

document.onclick = check;

function check(e){
  var target = (e && e.target) || (event && event.srcElement);

  //User Menu
  if (!checkParent(target, userMenuDiv)) {
  // click NOT on the menu
  if (checkParent(target, userMenu)) {
    // click on the link
    if (userMenuDiv.classList.contains("invisible")) {
    userMenuDiv.classList.remove("invisible");
    } else {userMenuDiv.classList.add("invisible");}
  } else {
    // click both outside link and outside menu, hide menu
    userMenuDiv.classList.add("invisible");
  }
  }

  //Nav Menu
  if (!checkParent(target, navMenuDiv)) {
  // click NOT on the menu
  if (checkParent(target, navMenu)) {
    // click on the link
    if (navMenuDiv.classList.contains("hidden")) {
    navMenuDiv.classList.remove("hidden");
    } else {navMenuDiv.classList.add("hidden");}
  } else {
    // click both outside link and outside menu, hide menu
    navMenuDiv.classList.add("hidden");
  }
  }

}

function checkParent(t, elm) {
  while(t.parentNode) {
  if( t == elm ) {return true;}
  t = t.parentNode;
  }
  return false;
}
