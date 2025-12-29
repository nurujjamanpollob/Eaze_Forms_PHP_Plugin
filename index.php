<?php
require_once __DIR__ . '/ecfs/src/autoload.php';
use EazeWebIT\Security;

Security::initSession();
$csrf_token = Security::generateCsrfToken();
$nonce = Security::getNonce();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EazeWebIT Contact Form Service</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-color: #f0f0f0;
            --text-color-dark: #333;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #1a1a2e;
            background-image: linear-gradient(45deg, #16222A 0%, #3A6073 100%);
            color: var(--text-color);
            line-height: 1.6;
            overflow-x: hidden;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        header {
            padding: 2rem 0;
            text-align: center;
        }

        header h1 {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .hero {
            text-align: center;
            padding: 3rem 2rem;
        }

        .hero h2 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 2rem auto;
        }

        .cta-button {
            display: inline-block;
            padding: 12px 30px;
            background: var(--primary-color);
            color: #fff;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .cta-button:hover {
            background-color: #0056b3;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            text-align: center;
        }

        .feature-item h3 {
            margin-bottom: 0.5rem;
            font-size: 1.25rem;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 2rem;
        }

        /* Form Styles */
        .form-container {
            max-width: 700px;
            margin: 2rem auto;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 400;
        }

        input[type="text"],
        input[type="email"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            box-sizing: border-box;
            background: rgba(0, 0, 0, 0.2);
            color: var(--text-color);
            font-family: 'Poppins', sans-serif;
        }

        input::placeholder, textarea::placeholder {
            color: rgba(255,255,255,0.5);
        }

        button[type="submit"] {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        .status-message {
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: 8px;
            display: none;
            text-align: center;
            font-weight: 600;
        }

        .status-success {
            background-color: rgba(40, 167, 69, 0.8);
            color: #fff;
        }

        .status-error {
            background-color: rgba(220, 53, 69, 0.8);
            color: #fff;
        }
    </style>
</head>
<body>

<header class="container">
    <h1>EazeWebIT Contact Service</h1>
</header>

<main class="container">
    <section id="hero" class="glass-card">
        <h2>Effortless Contact Forms for Any Website</h2>
        <p>A professional, self-contained PHP/SQLite backend for managing contact form submissions. Simple to embed, powerful to use.</p>
        <a href="#contact" class="cta-button">Try The Demo</a>
    </section>

    <section id="features">
        <h2 class="section-title">Features</h2>
        <div class="features-grid">
            <div class="glass-card feature-item">
                <h3>Zero-Config Setup</h3>
                <p>Automatic database initialization on first run. Get started in minutes.</p>
            </div>
            <div class="glass-card feature-item">
                <h3>Flexible Data Model</h3>
                <p>EAV model allows for custom form fields without any database schema changes.</p>
            </div>
            <div class="glass-card feature-item">
                <h3>Secure & Robust</h3>
                <p>Built-in CSRF protection, rate limiting, and secure file uploads.</p>
            </div>
            <div class="glass-card feature-item">
                <h3>Admin Dashboard</h3>
                <p>A modern, responsive dashboard to view and manage all submissions.</p>
            </div>
        </div>
    </section>

    <section id="contact" class="form-container">
        <div class="glass-card">
            <h2 class="section-title" style="margin-bottom: 1rem;">Contact Us</h2>
            <p style="text-align: center; margin-bottom: 2rem;">This is a live demo. Fill out the form to see it in action.</p>

            <form eaze-contact-form="true" eaze-contact-form-id="landing-page-demo" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" required placeholder="Your Name">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required placeholder="your.email@example.com">
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea name="message" id="message" required placeholder="Your message here..."></textarea>
                </div>
                <div class="form-group">
                    <label for="attachment">Attachment (Optional)</label>
                    <input type="file" name="attachment" id="attachment">
                </div>
                <button type="submit">Send Message</button>
            </form>
            <div id="status" class="status-message"></div>
        </div>
    </section>
</main>

<?php include 'ecfs/public/includes/copyright.php'; ?>

<!-- Include the Form Plugin Script -->
<script src="/ecfs/public/eaze_contact_form.js" nonce="<?= $nonce ?>"></script>

<script nonce="<?= $nonce ?>">
    // Listen to the custom event dispatched by the plugin
    window.addEventListener('eazeContactFormSubmit', function(e) {
        const statusDiv = document.getElementById('status');
        const detail = e.detail;

        statusDiv.style.display = 'block';
        statusDiv.textContent = detail.message;
        statusDiv.className = 'status-message ' + (detail.status === 'success' ? 'status-success' : 'status-error');

        // If success, you might want to clear the form
        if (detail.status === 'success') {
            e.detail.form.reset();
        }

        // Hide the message after 5 seconds
        setTimeout(() => {
            statusDiv.style.display = 'none';
        }, 5000);
    });
</script>

</body>
</html>