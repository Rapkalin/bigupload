const button = document.getElementById("animatedButton");
button.addEventListener("click", addClassAnimation, false);

function addClassAnimation(e) {
    e.preventDefault();
    button.classList.add("animate");

    setTimeout(() => {
        button.classList.remove("animate");
    }, 500) // 1s = 1000ms
}