/**
 * Custom JS for the theme
 */
import highlights from './js/highlights'
import mobileMenu from './js/mobile-menu'
import reveal from './js/reveal'

/**
 * Custom CSS for the theme
 *
 * @see https://vite.dev/guide/features#css
 */
import './main.css'

/**
 * Run our custom JS when the page is fully loaded.
 */
document.addEventListener('DOMContentLoaded',function() {
  highlights.init()
  mobileMenu.init()
  reveal.init()
})