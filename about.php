<?php
// MediHub PHP page.
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>MediHub - About Us</title>

    <!-- Bootstrap 5 CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />

    <!-- Font Awesome -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    />

    <!-- About Page CSS -->
    <link rel="stylesheet" href="AboutStyle.css" />
  </head>

  <body>
    <!-- =========================================
         HEADER
    ========================================== -->

    <header class="main-header">

      <div class="header-top">

        <!-- Logo -->

        <div class="logo-area">
          <img
            src="images/logo.jpeg"
            alt="MediHub Logo"
          />
        </div>

        <!-- Brand -->

        <div class="brand-name">
          <h1>MediHub</h1>
        </div>

        <!-- Header Actions -->

        <div class="header-actions">

          <a href="#" class="login-btn">
            Login/Register
          </a>

          <a href="#" class="cart-icon">
            <i class="fa-solid fa-cart-shopping"></i>
          </a>

        </div>

      </div>


      <!-- Navigation -->

      <nav class="navigation">

        <a href="Home.php">
          Home
        </a>

        <span>|</span>
        <a href="search_medicine.php">
          Search Medicine
        </a>


        <span>|</span>
        <a href="about.php">
          About Us
        </a>
        

        <span>|</span>

        <a href="contact.php">
          Contact Us
        </a>

      </nav>

    </header>


    <!-- =========================================
         HERO SECTION
    ========================================== -->

    <section class="about-hero">

      <div class="about-hero-overlay">

        <h2>
          About Our<br />
          MediHub
        </h2>

      </div>

    </section>


    <!-- =========================================
         DESCRIPTION
    ========================================== -->

    <section class="about-description py-5">

      <div class="container">

        <h2 class="about-section-title text-center">
          MediHub Description
        </h2>

        <div class="description-content">

          <p>
            Welcome to MediHub, your trusted partner in health and
            well-being. We are a dedicated healthcare facility in Sri Lanka,
            committed to providing accessible, high-quality, and
            patient-centered care to our community.
          </p>

          <p>
            Our journey began with a simple but profound vision: to make
            modern, compassionate healthcare available to all.
          </p>

        </div>

      </div>

    </section>


    <!-- =========================================
         MISSION & VISION
    ========================================== -->

    <section class="mission-vision py-5">

      <div class="container">

        <h2 class="about-section-title text-center">
          Our Mission & Vision
        </h2>


        <div class="row g-4 justify-content-center">

          <!-- Mission -->

          <div class="col-12 col-lg-6">

            <div class="info-card h-100">

              <div class="info-icon">
                <i class="fa-solid fa-bullseye"></i>
              </div>

              <h2>
                Mission
              </h2>

              <p>
                Our mission is to enhance the health and quality of life
                for the individuals and families we serve.
              </p>

              <p>
                We achieve this by offering comprehensive medical services,
                leading with medical excellence, and fostering a culture
                of compassion, respect, and innovation.
              </p>

              <p>
                We are dedicated to promoting health education and
                preventative care, ensuring a healthier future for all
                Sri Lankans.
              </p>

            </div>

          </div>


          <!-- Vision -->

          <div class="col-12 col-lg-6">

            <div class="info-card h-100">

              <div class="info-icon">
                <i class="fa-solid fa-eye"></i>
              </div>

              <h2>
                Vision
              </h2>

              <p>
                Our vision is to be the leading healthcare hub in Sri
                Lanka, recognized for its commitment to clinical
                excellence, patient-centered care, and community service.
              </p>

              <p>
                We strive to create a network of integrated healthcare
                services that are modern, accessible, and inclusive,
                creating a profound and lasting impact on the health
                landscape of Sri Lanka.
              </p>

            </div>

          </div>

        </div>

      </div>

    </section>


    <!-- =========================================
         FOOTER
    ========================================== -->

    <footer class="footer">

      <div class="container">

        <div class="row footer-content g-4">

          <!-- Address -->

          <div class="col-12 col-md-4 footer-column address">

            <img
              src="images/logo.jpeg"
              alt="MediHub Logo"
            />

            <p>
              No.78, Baseline<br />
              Road, Colombo 09, Sri Lanka
            </p>

          </div>


          <!-- Contact -->

          <div class="col-12 col-md-4 footer-column">

            <p>
              Phone : +94 11 234 5678
            </p>

            <p>
              +94 77 456 7890
            </p>

            <p>
              +94 71 987 6543
            </p>

            <p>
              Email : info@medihub.lk
            </p>

          </div>


          <!-- Social Media -->

          <div class="col-12 col-md-4 footer-column">

            <p>
              Facebook : facebook.com/medihub.lk
            </p>

            <p>
              Instagram : instagram.com/medihub.lk
            </p>

            <p>
              Whatsapp : +94 77 456 7890
            </p>

          </div>

        </div>

      </div>


      <!-- Copyright -->

      <div class="copyright">

        Copyright © 2026 MediHub Pharmacy.
        All Rights Reserved by: CSE5015

      </div>

    </footer>


    <!-- Bootstrap 5 JS -->

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    ></script>

    <script src="script.js"></script>

  </body>
</html>
