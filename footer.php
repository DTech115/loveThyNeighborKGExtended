<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Footer</title>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

/* THE PAGE LAYOUT */

html, body {
    height: 100%;
    margin: 0;
    font-family: Arial, sans-serif;
}

.page-wrapper {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.main-content {
    min-height: 100vh;
}

/* THE FOOTER */


.site-footer {
    background: linear-gradient(to bottom, #ffffff, #f7f9fc);
    border-top: 1px solid rgba(0, 74, 173, 0.15);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
}


/* Top Accent Line */
.footer-top-line {
    width: 100%;
    height: 4px;
    background: linear-gradient(
        90deg,
        rgb(0, 74, 173)
    );
}

/* SHADOW to be above the line */
.footer-top-line::before {
    content: "";
    position: absolute;
    top: -10px; /* moves shadow above */
    left: 0;
    width: 100%;
    height: 10px;

    box-shadow: 0 -6px 10px rgba(0, 0, 0, 0.08);
}

/* Container */
.footer-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 50px 20px 30px;
    text-align: center;
}

/* Logo to match the card style */

.footer-logo {
    width: 90px;
    height: 90px;
    object-fit: cover;
    border-radius: 50%;
    padding: 6px;
    background: white;
    border: 1px solid rgba(0, 74, 173, 0.15);

    /* base shadow */
    box-shadow: 0 8px 20px rgba(0, 74, 173, 0.08);

    transition: all 0.25s ease;
}

.footer-logo:hover {
    transform: translateY(-5px) scale(1.05);

    /* hover glow like cards */
    box-shadow:
        0 12px 24px rgba(0, 74, 173, 0.15),
        0 0 0 3px rgba(0, 74, 173, 0.08);
}

/* TEXT */

.footer-title {
    margin: 15px 0 5px;
    font-size: 24px;
    font-weight: bold;
    color: rgb(0, 74, 173);
}

.footer-subtitle {
    margin: 0;
    font-size: 14px;
    color: #6b7280;
}

/* THE SOCIAL ICON CARDS */

.footer-socials {
    display: flex;
    justify-content: center;
    gap: 18px;
    margin: 15px 0 25px;
}

.social-icon {
    width: 30px;
    height: 30px;

    background: rgb(0, 74, 173); /* BLUE BOX */
    color: white; /* WHITE ICON */

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 12px;
    font-size: 15px;
    text-decoration: none;

    transition: 0.3s ease;
}

/* HOVER MATCHES THE DASHBOARD CARDS */
.social-icon:hover {
    transform: translateY(-6px) scale(1.08);

    background: linear-gradient(to bottom, #ffffff, #f0f6ff);
    color: rgb(0, 74, 173); /* icon turns blue */

    box-shadow:
        0 14px 26px rgba(0, 74, 173, 0.18),
        0 0 0 3px rgba(0, 74, 173, 0.08);
}

/* FOOTER BOTTOM */

.footer-bottom {
    border-top: 1px solid rgba(0,0,0,0.06);
    padding-top: 18px;
}

.footer-bottom p {
    margin: 0;
    font-size: 13px;
    color: #7b8190;
}

</style>
</head>

<body>
    <!-- FOOTER -->
    <footer class="site-footer">

        <div class="footer-top-line"></div>

        <div class="footer-container">

            <!-- Logo -->
            <img src="images/LoveThyNeighbor_logo2.jpeg" class="footer-logo">

            <!-- Title -->
            <h2 class="footer-title">Love Thy Neighbor</h2>
            <p class="footer-subtitle">
                King George County Community Food Pantry
            </p>

            <!-- Social Icons -->
            <div class="footer-socials">
                <a href="https://www.kgfood.org/" class="social-icon"><i class="fas fa-home"></i></a>
                <a href="https://www.facebook.com/kglovethyneighbor/" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.instagram.com/love_thy_neighbor_kg/" class="social-icon"><i class="fab fa-instagram"></i></a>
                <a href="https://www.youtube.com/@lovethyneighborkg2741" class="social-icon"><i class="fab fa-youtube"></i></a>
                <a href="https://www.linkedin.com/company/love-thy-neighbor-king-george/" class="social-icon"><i class="fab fa-linkedin"></i></a>
                <a href="https://www.kgfood.org/contact" class="social-icon"><i class="fas fa-envelope"></i></a>
                <a href="https://www.kgfood.org/contact" class="social-icon"><i class="fas fa-phone"></i></a>
            </div>

            <!-- Bottom -->
            <div class="footer-bottom">
                <p>© 2026 Love Thy Neighbor • All Rights Reserved</p>
            </div>

        </div>

    </footer>

</div>

</body>
</html>