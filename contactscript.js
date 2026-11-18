/* =========================================
   INQUIRY FORM VALIDATION
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    const inquiryForm = document.querySelector(".inquiry-form form");

    if (!inquiryForm) {
        return;
    }

    inquiryForm.addEventListener("submit", function (event) {

        const subject = document
            .getElementById("subject")
            .value
            .trim();

        const message = document
            .getElementById("message")
            .value
            .trim();

        /* Clear previous messages */
        const oldMessage = document.querySelector(".inquiry-message");

        if (oldMessage) {
            oldMessage.remove();
        }

        /* Subject validation */
        if (subject === "") {

            event.preventDefault();

            showInquiryMessage(
                "Please enter a subject.",
                "error"
            );

            document.getElementById("subject").focus();

            return;
        }

        /* Message validation */
        if (message === "") {

            event.preventDefault();

            showInquiryMessage(
                "Please enter your message.",
                "error"
            );

            document.getElementById("message").focus();

            return;
        }

        /* Message length */
        if (message.length < 5) {

            event.preventDefault();

            showInquiryMessage(
                "Message must contain at least 5 characters.",
                "error"
            );

            document.getElementById("message").focus();

            return;
        }

        /*
         * Do NOT use event.preventDefault()
         * here.
         *
         * PHP will receive the POST request
         * and save the inquiry into MySQL.
         */

    });

});


/* =========================================
   SHOW INQUIRY MESSAGE
========================================= */

function showInquiryMessage(text, type) {

    const form = document.querySelector(".inquiry-form form");

    const message = document.createElement("p");

    message.className = "inquiry-message " + type;

    message.textContent = text;

    form.parentNode.insertBefore(message, form);

}


/* =========================================
   CLEAR MESSAGE WHEN USER TYPES
========================================= */

document.addEventListener("input", function (event) {

    if (
        event.target.id === "subject" ||
        event.target.id === "message"
    ) {

        const message =
            document.querySelector(".inquiry-message");

        if (message) {
            message.remove();
        }

    }

});