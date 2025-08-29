<?php
// File: landing.php
session_start();
include 'db.php';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    // Redirect based on role
    if($_SESSION['role'] == 'customer') {
        header("Location: customer_dashboard.php");
    } else {
        header("Location: admin_dashboard.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title> - Student Event Attendance Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary-color:rgb(88, 76, 249);
      --secondary-color: #1A263A;
      --white: #FFFFFF;
      --light-gray: #F5F5F7;
      --text-color: #444;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body {
      color: var(--text-color);
      line-height: 1.6;
    }
    
    .container {
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }
    
    /* Header */
    header {
      background-color: var(--white);
      padding: 20px 0;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .logo {
      font-size: 24px;
      font-weight: 700;
      color: var(--primary-color);
    }
    
    .nav-links {
      display: flex;
      gap: 30px;
    }
    
    .nav-links a {
      text-decoration: none;
      color: var(--text-color);
      font-weight: 500;
    }
    
    .auth-buttons {
      display: flex;
      gap: 15px;
    }
    
    .btn {
      padding: 10px 20px;
      border-radius: 5px;
      font-weight: 500;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      color: var(--white);
      border: none;
    }
    
    .btn-secondary {
      background-color: var(--white);
      color: var(--primary-color);
      border: 1px solid var(--primary-color);
    }
    
    /* Hero Section */
    .hero {
      background: linear-gradient(135deg, var(--primary-color) 0%,rgb(88, 76, 249) 100%);
      color: var(--white);
      padding: 80px 0;
    }
    
    .hero-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .hero-text {
      max-width: 500px;
    }
    
    .hero-text h1 {
      font-size: 42px;
      line-height: 1.2;
      margin-bottom: 20px;
    }
    
    .hero-text p {
      font-size: 18px;
      margin-bottom: 30px;
    }
    
    .hero-image {
      background-color: var(--secondary-color);
      width: 500px;
      height: 350px;
      border-radius: 10px;
      display: flex;
      justify-content: center;
      align-items: center;
      color: var(--white);
      font-size: 32px;
      font-weight: 700;
    }
    
    /* How It Works */
    .how-it-works {
      padding: 80px 0;
      text-align: center;
    }
    
    .section-title {
      font-size: 32px;
      color: var(--secondary-color);
      margin-bottom: 15px;
    }
    
    .section-subtitle {
      font-size: 18px;
      color: #666;
      margin-bottom: 50px;
    }
    
    .steps {
      display: flex;
      justify-content: space-between;
      gap: 30px;
      margin-top: 50px;
    }
    
    .step {
      flex: 1;
    }
    
    .step-number {
      background-color: var(--primary-color);
      color: var(--white);
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 24px;
      font-weight: 600;
      margin: 0 auto 20px;
    }
    
    .step h3 {
      font-size: 22px;
      margin-bottom: 15px;
      color: var(--secondary-color);
    }
    
    /* Features */
    .features {
      padding: 80px 0;
      text-align: center;
      background-color: var(--light-gray);
    }
    
    .feature-cards {
      display: flex;
      justify-content: space-between;
      gap: 30px;
      margin-top: 50px;
    }
    
    .feature-card {
      background-color: var(--white);
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      flex: 1;
      text-align: left;
    }
    
    .feature-icon {
      background-color: #E7F0FF;
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 20px;
      color: var(--primary-color);
      font-size: 24px;
    }
    
    .feature-card h3 {
      font-size: 20px;
      margin-bottom: 15px;
      color: var(--secondary-color);
    }
    
    /* CTA */
    .cta {
      background: linear-gradient(135deg, var(--primary-color) 0%,rgb(88, 76, 249) 100%);
      color: var(--white);
      padding: 60px 0;
      text-align: center;
    }
    
    .cta h2 {
      font-size: 32px;
      margin-bottom: 15px;
    }
    
    .cta p {
      font-size: 18px;
      margin-bottom: 30px;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .cta-buttons {
      display: flex;
      justify-content: center;
      gap: 20px;
    }
    
    /* Footer */
    footer {
      background-color: var(--secondary-color);
      color: var(--white);
      padding: 60px 0 30px;
    }
    
    .footer-content {
      display: flex;
      justify-content: space-between;
      margin-bottom: 40px;
    }
    
    .footer-column {
      flex: 1;
    }
    
    .footer-column h3 {
      font-size: 18px;
      margin-bottom: 20px;
    }
    
    .footer-column ul {
      list-style: none;
    }
    
    .footer-column ul li {
      margin-bottom: 10px;
    }
    
    .footer-column ul li a {
      color: #CCC;
      text-decoration: none;
    }
    
    .copyright {
      text-align: center;
      color: #999;
      padding-top: 20px;
      border-top: 1px solid #333;
    }

    /* Make responsive for smaller screens */
    @media (max-width: 768px) {
      .hero-content {
        flex-direction: column;
        text-align: center;
      }
      
      .hero-text {
        margin-bottom: 30px;
      }
      
      .hero-image {
        width: 100%;
        max-width: 400px;
        height: 250px;
      }
      
      .steps, .feature-cards, .footer-content {
        flex-direction: column;
        gap: 40px;
      }
      
      .nav-links {
        display: none;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="container">
      <nav>
        <div class="logo">EventTrackPro</div>
        <div class="nav-links">
          <a href="#features">Features</a>
          <a href="#how-it-works">How it works</a>
          <a href="#contact">Contact</a>
        </div>
        <div class="auth-buttons">
          <a href="index.php" class="btn btn-primary">Login</a>
          <a href="register.php" class="btn btn-secondary">Register</a>
        </div>
      </nav>
    </div>
  </header>

  <section class="hero">
    <div class="container">
      <div class="hero-content">
        <div class="hero-text">
          <h1>Event attendance management made simple</h1>
          <p>Track attendance with powerful tools for a seamless experience.</p>
          <a href="index.php" class="btn btn-primary">Get Started</a>
        </div>
        <div class="hero-image">
          <i class="fas fa-calendar-check" style="font-size: 80px;"></i>
        </div>
      </div>
    </div>
  </section>

  <section class="features" id="features">
    <div class="container">
      <h2 class="section-title">Powerful Features</h2>
      <p class="section-subtitle">Everything you need to manage attendance</p>
      
      <div class="feature-cards">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-calendar-alt"></i>
          </div>
          <h3>Event Management</h3>
          <p>Create, update, and manage events with ease. Set schedules, locations, and attendance requirements.</p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <h3>Attendance Tracking</h3>
          <p>Track student attendance in real-time. Students can mark their presence, and teachers can review and edit records.</p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-file-import"></i>
          </div>
          <h3>CSV Import & API</h3>
          <p>Import attendance data from CSV files or connect to Google Sheets API for automated data fetching.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="how-it-works" id="how-it-works">
    <div class="container">
      <h2 class="section-title">How It Works</h2>
      <p class="section-subtitle">Simple and straightforward process</p>
      
      <div class="steps">
        <div class="step">
          <div class="step-number">1</div>
          <h3>Create Events</h3>
          <p>Teachers create events with details like date, time, location, and expected attendees.</p>
        </div>
        
        <div class="step">
          <div class="step-number">2</div>
          <h3>Mark Attendance</h3>
          <p>Students mark their attendance at events, or teachers can record attendance manually.</p>
        </div>
        
        <div class="step">
          <div class="step-number">3</div>
          <h3>Generate Reports</h3>
          <p>Access comprehensive attendance reports and analytics for insights.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="cta">
    <div class="container">
      <h2>Ready to Get Started?</h2>
      <p>Join educational institutions that trust EventTrackPro for their attendance management needs.</p>
      <div class="cta-buttons">
        <a href="index.php" class="btn btn-primary">Login Now</a>
        <a href="register.php" class="btn btn-secondary">Register</a>
      </div>
    </div>
  </section>

  <footer id="contact">
    <div class="container">
      <div class="footer-content">
        <div class="footer-column">
          <h3>EventTrackPro</h3>
          <p>The complete solution for student event attendance management.</p>
        </div>
        
        <div class="footer-column">
          <h3>Quick Links</h3>
          <ul>
            <li><a href="landing.php">Home</a></li>
            <li><a href="#features">Features</a></li>
            <li><a href="#how-it-works">How it Works</a></li>
            <li><a href="#contact">Contact</a></li>
          </ul>
        </div>
        
        <div class="footer-column">
          <h3>Support</h3>
          <ul>
            <li><a href="#">Help Center</a></li>
            <li><a href="#">Documentation</a></li>
            <li><a href="#">API Reference</a></li>
          </ul>
        </div>
        
        <div class="footer-column">
          <h3>Contact Us</h3>
          <p><i class="fas fa-envelope"></i> vasusru@eventtrackpro.com</p>
          <p><i class="fas fa-phone"></i> 0123456789</p>
        </div>
      </div>
      
      <div class="copyright">
        © <?php echo date('Y'); ?> EventTrackPro. All rights reserved.
      </div>
    </div>
  </footer>
</body>
</html>