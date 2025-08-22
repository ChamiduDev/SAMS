<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAMS - Student Academic Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #244b3fff;
            --secondary-color: #2d6a4f;
            --accent-color: #3b82f6;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f8fafc;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: fixed;
            width: 100%;
            z-index: 1000;
        }

        .navbar.scrolled {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .nav-link {
            font-weight: 500;
            color: var(--text-dark) !important;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 50%;
            background: var(--primary-color);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
            color: white;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #2d6a4f 0%, #0c351bff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="white" opacity="0.1"/><circle cx="80" cy="40" r="1" fill="white" opacity="0.1"/><circle cx="40" cy="80" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.3rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            max-width: 600px;
        }

        .btn-cta {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
        }

        .btn-cta:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .btn-cta.primary {
            background: white;
            color: var(--primary-color);
        }

        .btn-cta.primary:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Statistics */
        .stats {
            background: var(--white);
            padding: 80px 0;
            margin-top: -50px;
            position: relative;
            z-index: 3;
        }

        .stat-card {
            text-align: center;
            padding: 40px 30px;
            border-radius: 20px;
            background: var(--white);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stat-label {
            color: var(--text-light);
            font-weight: 500;
            font-size: 1.1rem;
        }

        /* Courses Section */
        .courses {
            padding: 100px 0;
            background: var(--bg-light);
        }

        .section-title {
            font-size: 2.8rem;
            font-weight: 700;
            color: var(--text-dark);
            text-align: center;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            color: var(--text-light);
            font-size: 1.2rem;
            text-align: center;
            margin-bottom: 4rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .course-card {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: none;
            margin-bottom: 30px;
        }

        .course-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .course-image {
            height: 250px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            position: relative;
            overflow: hidden;
        }

        .course-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.1);
        }

        .course-image i {
            position: relative;
            z-index: 2;
        }

        .course-body {
            padding: 30px;
        }

        .course-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .course-description {
            color: var(--text-light);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .course-duration {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .course-level {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* News Section */
        .news {
            padding: 100px 0;
            background: var(--white);
        }

        .news-card {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .news-date {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .news-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .news-excerpt {
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Contact Section */
        .contact {
            padding: 100px 0;
            background: var(--bg-light);
        }

        .contact-form {
            background: var(--white);
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-control {
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 15px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        /* Footer */
        .footer {
            background: var(--text-dark);
            color: white;
            padding: 50px 0 30px;
        }

        .footer-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
        }

        .footer-text {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 30px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
            margin-bottom: 10px;
        }

        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }

        .social-links a {
            display: inline-block;
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 45px;
            color: white;
            margin-right: 15px;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--primary-color);
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            margin-top: 30px;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-up {
            animation: fadeInUp 0.8s ease forwards;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
            
            .section-title {
                font-size: 2.2rem;
            }
            
            .contact-form {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <i class="fas fa-graduation-cap me-2"></i>SAMS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#courses">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#news">News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="public/login.php" class="btn btn-login">
                        <i class="fas fa-user-graduate me-2"></i>Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content animate-fade-up">
                        <h1>Shape Your Future with SAMS</h1>
                        <p>Experience world-class education through our comprehensive Student Academic Management System. Join thousands of students in their journey to success.</p>
                        <div>
                            <a href="public/login.php" class="btn-cta primary">
                                <i class="fas fa-user-graduate me-2"></i>login
                            </a>
                            <a href="#courses" class="btn-cta">
                                <i class="fas fa-book me-2"></i>Explore Courses
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-image animate-fade-up">
                        <i class="fas fa-university" style="font-size: 10rem; color: rgba(255,255,255,0.2);"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="stats">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-number">2,850</div>
                        <div class="stat-label">Active Students</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-number">150</div>
                        <div class="stat-label">Expert Faculty</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-number">45</div>
                        <div class="stat-label">Courses</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-number">95%</div>
                        <div class="stat-label">Success Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Courses Section -->
    <section class="courses" id="courses">
        <div class="container">
            <div class="section-title">Featured Courses</div>
            <div class="section-subtitle">Discover our most popular programs designed to launch your career</div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="course-card">
                        <div class="course-image">
                            <i class="fas fa-palette"></i>
                        </div>
                        <div class="course-body">
                            <h5 class="course-title">Graphic Design Mastery</h5>
                            <p class="course-description">Master the fundamentals of graphic design, from typography and color theory to digital illustration and brand identity creation.</p>
                            <div class="course-meta">
                                <span class="course-duration">
                                    <i class="fas fa-clock me-1"></i>16 Weeks
                                </span>
                                <span class="course-level">Beginner</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="course-card">
                        <div class="course-image" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="course-body">
                            <h5 class="course-title">Full Stack Development</h5>
                            <p class="course-description">Learn modern web development with React, Node.js, and database management. Build complete applications from scratch.</p>
                            <div class="course-meta">
                                <span class="course-duration">
                                    <i class="fas fa-clock me-1"></i>24 Weeks
                                </span>
                                <span class="course-level">Intermediate</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="course-card">
                        <div class="course-image" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="course-body">
                            <h5 class="course-title">UI/UX Design</h5>
                            <p class="course-description">Create intuitive and beautiful user experiences. Learn design thinking, prototyping, and user research methodologies.</p>
                            <div class="course-meta">
                                <span class="course-duration">
                                    <i class="fas fa-clock me-1"></i>20 Weeks
                                </span>
                                <span class="course-level">Intermediate</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="course-card">
                        <div class="course-image" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="course-body">
                            <h5 class="course-title">Data Science & Analytics</h5>
                            <p class="course-description">Dive into data analysis, machine learning, and statistical modeling using Python and modern data science tools.</p>
                            <div class="course-meta">
                                <span class="course-duration">
                                    <i class="fas fa-clock me-1"></i>28 Weeks
                                </span>
                                <span class="course-level">Advanced</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="course-card">
                        <div class="course-image" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div class="course-body">
                            <h5 class="course-title">Digital Marketing</h5>
                            <p class="course-description">Master social media marketing, SEO, content strategy, and digital advertising to grow businesses online.</p>
                            <div class="course-meta">
                                <span class="course-duration">
                                    <i class="fas fa-clock me-1"></i>18 Weeks
                                </span>
                                <span class="course-level">Beginner</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="course-card">
                        <div class="course-image" style="background: linear-gradient(135deg, #a8edea, #fed6e3);">
                            <i class="fas fa-video"></i>
                        </div>
                        <div class="course-body">
                            <h5 class="course-title">Video Production</h5>
                            <p class="course-description">Learn professional video creation, editing, and post-production techniques for film, web, and social media.</p>
                            <div class="course-meta">
                                <span class="course-duration">
                                    <i class="fas fa-clock me-1"></i>22 Weeks
                                </span>
                                <span class="course-level">Intermediate</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- News Section -->
    <section class="news" id="news">
        <div class="container">
            <div class="section-title">Latest News & Updates</div>
            <div class="section-subtitle">Stay informed with the latest developments and announcements</div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="news-card">
                        <div class="news-date">
                            <i class="fas fa-calendar me-1"></i>August 18, 2025
                        </div>
                        <h5 class="news-title">New AI & Machine Learning Program Launch</h5>
                        <p class="news-excerpt">We're thrilled to announce our comprehensive AI and Machine Learning certification program, designed to meet the growing industry demand for AI specialists.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="news-card">
                        <div class="news-date">
                            <i class="fas fa-calendar me-1"></i>August 15, 2025
                        </div>
                        <h5 class="news-title">Student Showcase Exhibition 2025</h5>
                        <p class="news-excerpt">Join us for our annual student showcase where creative talents display their outstanding projects and achievements from across all departments.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="news-card">
                        <div class="news-date">
                            <i class="fas fa-calendar me-1"></i>August 12, 2025
                        </div>
                        <h5 class="news-title">Industry Partnership with TechCorp</h5>
                        <p class="news-excerpt">We've established a strategic partnership with leading technology companies to provide exclusive internship opportunities and job placements for our graduates.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="news-card">
                        <div class="news-date">
                            <i class="fas fa-calendar me-1"></i>August 10, 2025
                        </div>
                        <h5 class="news-title">Virtual Reality Learning Lab Opens</h5>
                        <p class="news-excerpt">Our state-of-the-art VR learning laboratory is now open, offering immersive educational experiences across multiple disciplines.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="news-card">
                        <div class="news-date">
                            <i class="fas fa-calendar me-1"></i>August 8, 2025
                        </div>
                        <h5 class="news-title">Scholarship Program Extended</h5>
                        <p class="news-excerpt">Due to overwhelming response, we're extending our merit-based scholarship program deadline to September 15th, 2025.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="news-card">
                        <div class="news-date">
                            <i class="fas fa-calendar me-1"></i>August 5, 2025
                        </div>
                        <h5 class="news-title">Green Campus Initiative Launch</h5>
                        <p class="news-excerpt">SAMS commits to sustainability with our new green campus initiative, featuring solar panels, recycling programs, and eco-friendly practices.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <div class="section-title">Get in Touch</div>
            <div class="section-subtitle">Have questions? We'd love to hear from you</div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="contact-form">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastName" required>
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label">Subject</label>
                                <select class="form-control" id="subject" required>
                                    <option value="">Choose a subject</option>
                                    <option value="admission">Admission Inquiry</option>
                                    <option value="course">Course Information</option>
                                    <option value="technical">Technical Support</option>
                                    <option value="partnership">Partnership</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="6" required placeholder="Tell us how we can help you..."></textarea>
                            </div>
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand">
                        <i class="fas fa-graduation-cap me-2"></i>SAMS
                    </div>
                    <p class="footer-text">Empowering students through innovative academic management and world-class education. Your success is our mission.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="text-white mb-4">Quick Links</h5>
                    <div class="footer-links">
                        <a href="#home">Home</a>
                        <a href="#courses">Courses</a>
                        <a href="#news">News</a>
                        <a href="#contact">Contact</a>
                        <a href="student_portal.php">Student Portal</a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="text-white mb-4">Programs</h5>
                    <div class="footer-links">
                        <a href="#">Graphic Design</a>
                        <a href="#">Web Development</a>
                        <a href="#">UI/UX Design</a>
                        <a href="#">Data Science</a>
                        <a href="#">Digital Marketing</a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="text-white mb-4">Support</h5>
                    <div class="footer-links">
                        <a href="#">Help Center</a>
                        <a href="#">Student Resources</a>
                        <a href="#">Technical Support</a>
                        <a href="#">Academic Calendar</a>
                        <a href="#">Library</a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="text-white mb-4">About</h5>
                    <div class="footer-links">
                        <a href="#">Our Story</a>
                        <a href="#">Faculty</a>
                        <a href="#">Careers</a>
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 SAMS - Student Academic Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offsetTop = target.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Contact form handling
        document.querySelector('.contact-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = {
                firstName: document.getElementById('firstName').value,
                lastName: document.getElementById('lastName').value,
                email: document.getElementById('email').value,
                subject: document.getElementById('subject').value,
                message: document.getElementById('message').value
            };

            // Simple validation
            if (!formData.firstName || !formData.lastName || !formData.email || !formData.subject || !formData.message) {
                alert('Please fill in all required fields.');
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(formData.email)) {
                alert('Please enter a valid email address.');
                return;
            }

            // Simulate form submission
            const submitBtn = document.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            submitBtn.disabled = true;

            // Simulate API call
            setTimeout(() => {
                alert('Thank you for your message! We will get back to you soon.');
                this.reset();
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.course-card, .news-card, .stat-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(50px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });

        // Counter animation for statistics
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent.replace(/[^0-9]/g, ''));
                const suffix = counter.textContent.replace(/[0-9,]/g, '');
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counter.textContent = target.toLocaleString() + suffix;
                        clearInterval(timer);
                    } else {
                        counter.textContent = Math.floor(current).toLocaleString() + suffix;
                    }
                }, 40);
            });
        }

        // Trigger counter animation when stats section is in view
        const statsObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const statsSection = document.querySelector('.stats');
        if (statsSection) {
            statsObserver.observe(statsSection);
        }

        // Mobile menu close on link click
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                    bsCollapse.hide();
                }
            });
        });

        // Course card hover effect with 3D transform
        document.querySelectorAll('.course-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-15px) rotateX(5deg)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) rotateX(0deg)';
            });
        });

        // Parallax effect for hero section
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            if (hero) {
                const rate = scrolled * -0.5;
                hero.style.transform = `translateY(${rate}px)`;
            }
        });

        // Loading animation
        window.addEventListener('load', () => {
            document.body.style.opacity = '1';
            document.body.style.transition = 'opacity 0.5s ease';
        });

        // Set initial body opacity
        document.body.style.opacity = '0';
    </script>
</body>
</html>