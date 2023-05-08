let lastY = window.scrollY

window.addEventListener('scroll', () => {
  if (window.scrollY === lastY) {
    return
  }
  document.body.classList.toggle('scrolled-down', window.scrollY > lastY)
  document.body.classList.toggle('scrolled-up', window.scrollY < lastY)
  lastY = window.scrollY
})