document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.querySelector(".fn_switch_client_info");
    const clientInfo = document.querySelector(".client_info");

    if (toggle && clientInfo) {
        toggle.addEventListener("click", function () {
            clientInfo.classList.toggle("active");
        });
    }
});
