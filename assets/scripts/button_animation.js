const button = document.querySelector(".btn-bglpd");
button.addEventListener("click", addClassAnimation, false);

function addClassAnimation(e) {
    console.log('HERE IS THE EVENT:::', e);
    e.preventDefault();
    button.classList.add("animate");

}