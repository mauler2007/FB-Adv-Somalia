window.onload = function () {
  // document.body.classList.add('loaded_hiding');
  console.log("before");
  window.setTimeout(function () {
    document.querySelector(".wrapper").style.opacity = 1;
    console.log("after");
    // document.body.classList.add('loaded');
    // document.body.classList.remove('loaded_hiding');
  }, 2000);
};
