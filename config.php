<?php
// MediHub application bootstrap: session only.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
