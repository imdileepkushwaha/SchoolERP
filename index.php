<?php
// index.php - Main public facing homepage
require_once 'includes/db_connect.php';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>Welcome to SchoolERP</h1>
        <p>A modern, comprehensive, and intelligent school management system designed to streamline administration and enhance the learning experience.</p>
        <div class="hero-actions">
            <a href="about.php" class="btn btn-primary">Learn More</a>
            <a href="contact.php" class="btn btn-outline" style="margin-left: 15px;">Contact Us</a>
        </div>
        
        <div class="hero-stats">
            <div class="stat-item">
                <h3>1500+</h3>
                <p>Happy Students</p>
            </div>
            <div class="stat-item">
                <h3>100+</h3>
                <p>Expert Teachers</p>
            </div>
            <div class="stat-item">
                <h3>50+</h3>
                <p>Awards Won</p>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features">
    <div class="container">
        <h2 class="section-title">Why Choose Us?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-laptop-code feature-icon"></i>
                <h3>Modern Curriculum</h3>
                <p>We provide a state-of-the-art curriculum focused on real-world skills and practical learning.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-chalkboard-teacher feature-icon"></i>
                <h3>Expert Faculty</h3>
                <p>Our teachers are highly qualified professionals dedicated to nurturing student potential.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-shield-alt feature-icon"></i>
                <h3>Secure Environment</h3>
                <p>Safety is our priority with comprehensive security measures across our campus.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
