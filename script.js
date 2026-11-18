function searchMedicine() {
  let searchValue = document
    .getElementById("medicineSearch")
    .value.toLowerCase();

  let cards = document.querySelectorAll(".medicine-card");

  cards.forEach(function (card) {
    let medicineName = card.querySelector("h4").textContent.toLowerCase();

    if (medicineName.includes(searchValue)) {
      card.style.display = "block";
    } else {
      card.style.display = "none";
    }
  });
}


/* ================= SLIDER ================= */

function nextMedicine() {
  const container = document.getElementById("medicineContainer");

  container.scrollBy({
    left: 250,
    behavior: "smooth",
  });
}

function previousMedicine() {
  const container = document.getElementById("medicineContainer");

  container.scrollBy({
    left: -250,
    behavior: "smooth",
  });
}


/* ================= LOGIN VALIDATION ================= */

function validateLogin() {

  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;

  const emailError = document.getElementById("emailError");
  const passwordError = document.getElementById("passwordError");

  emailError.textContent = "";
  passwordError.textContent = "";

  let valid = true;


  // Email validation
  if (email === "") {

    emailError.textContent = "Email is required.";
    valid = false;

  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {

    emailError.textContent = "Please enter a valid email address.";
    valid = false;

  }


  // Password validation
  if (password === "") {

    passwordError.textContent = "Password is required.";
    valid = false;

  } else if (password.length < 6) {

    passwordError.textContent =
      "Password must be at least 6 characters.";

    valid = false;

  }


  return valid;
}


/* ================= PASSWORD SHOW / HIDE ================= */

function togglePassword(inputId, icon) {

  const input = document.getElementById(inputId);

  if (input.type === "password") {

    input.type = "text";

    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");

  } else {

    input.type = "password";

    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");

  }
}


/* ================= REGISTER VALIDATION ================= */

function validateRegister() {

    const fullName = document.getElementById("fullname").value.trim();
    const username = document.getElementById("username").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const phone = document.getElementById("phone").value.trim();
    const address = document.getElementById("address").value.trim();

    const fullNameError = document.getElementById("fullNameError");
    const usernameError = document.getElementById("usernameError");
    const emailError = document.getElementById("registerEmailError");
    const passwordError = document.getElementById("registerPasswordError");
    const confirmPasswordError = document.getElementById("confirmPasswordError");
    const phoneError = document.getElementById("phoneError");
    const addressError = document.getElementById("addressError");

    // Clear previous errors
    fullNameError.textContent = "";
    usernameError.textContent = "";
    emailError.textContent = "";
    passwordError.textContent = "";
    confirmPasswordError.textContent = "";
    phoneError.textContent = "";
    addressError.textContent = "";

    let valid = true;


    /* ---------- Full Name ---------- */

    if (fullName === "") {

        fullNameError.textContent = "Full name is required.";
        valid = false;

    } else if (fullName.length < 2) {

        fullNameError.textContent =
            "Full name must contain at least 2 characters.";

        valid = false;
    }


    /* ---------- Username ---------- */

    if (username === "") {

        usernameError.textContent = "Username is required.";
        valid = false;

    } else if (username.length < 3) {

        usernameError.textContent =
            "Username must contain at least 3 characters.";

        valid = false;
    }


    /* ---------- Email ---------- */

    if (email === "") {

        emailError.textContent = "Email is required.";
        valid = false;

    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {

        emailError.textContent =
            "Please enter a valid email address.";

        valid = false;
    }


    /* ---------- Password ---------- */

    if (password === "") {

        passwordError.textContent = "Password is required.";
        valid = false;

    } else if (password.length < 8) {

        passwordError.textContent =
            "Password must be at least 8 characters.";

        valid = false;
    }


    /* ---------- Confirm Password ---------- */

    if (confirmPassword === "") {

        confirmPasswordError.textContent =
            "Please confirm your password.";

        valid = false;

    } else if (password !== confirmPassword) {

        confirmPasswordError.textContent =
            "Passwords do not match.";

        valid = false;
    }


    /* ---------- Phone ---------- */

    if (phone === "") {

        phoneError.textContent =
            "Phone number is required.";

        valid = false;

    } else if (!/^[0-9]{10}$/.test(phone)) {

        phoneError.textContent =
            "Phone number must contain exactly 10 digits.";

        valid = false;
    }


    /* ---------- Address ---------- */

    if (address === "") {

        addressError.textContent =
            "Address is required.";

        valid = false;
    }


    return valid;
}

/* =========================================
   SHOW DASHBOARD SECTION
========================================= */

function showSection(sectionId, clickedLink) {

    const sections =
        document.querySelectorAll(".dashboard-section");

    sections.forEach(function(section) {

        section.classList.add("hidden-section");

    });


    const selectedSection =
        document.getElementById(sectionId);

    if (selectedSection) {

        selectedSection.classList.remove("hidden-section");

    }


    /* Remove active */

    const links =
        document.querySelectorAll(".sidebar-link");

    links.forEach(function(link) {

        link.classList.remove("active");

    });


    /* Add active */

    if (clickedLink) {

        clickedLink.classList.add("active");

    }

}


/* =========================================
   SEARCH MEDICINE
========================================= */

function searchMedicine() {

    const searchInput =
        document.getElementById("medicineSearch");

    const value =
        searchInput.value.trim();


    if (value === "") {

        alert("Please enter a medicine name.");

        return;

    }


    alert(
        "Searching for medicine: " + value
    );

}


/* =========================================
   VIEW ORDER
========================================= */

function viewOrder(orderId) {

    alert(
        "Viewing Order #" + orderId
    );

}


/* =========================================
   UPLOAD PRESCRIPTION
========================================= */

function uploadPrescription() {

    const file =
        document.getElementById(
            "prescriptionFile"
        ).files[0];


    if (!file) {

        alert(
            "Please select a prescription file."
        );

        return;

    }


    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "application/pdf"
    ];


    if (!allowedTypes.includes(file.type)) {

        alert(
            "Only JPG, PNG and PDF files are allowed."
        );

        return;

    }


    alert(
        "Prescription selected successfully."
    );

}


/* =========================================
   SUBMIT INQUIRY
========================================= */

function submitInquiry() {

    const subject =
        document.getElementById(
            "inquirySubject"
        ).value.trim();


    const message =
        document.getElementById(
            "inquiryMessage"
        ).value.trim();


    if (subject === "") {

        alert("Please enter a subject.");

        return;

    }


    if (message === "") {

        alert("Please enter your message.");

        return;

    }


    alert(
        "Your inquiry has been submitted."
    );

}


/* =========================================
   SUBMIT FEEDBACK
========================================= */

function submitFeedback() {

    const message =
        document.getElementById(
            "feedbackMessage"
        ).value.trim();


    if (message === "") {

        alert(
            "Please enter your feedback."
        );

        return;

    }


    alert(
        "Thank you for your feedback!"
    );

}