<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electripid - Smart Energy Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="index.php">
                <i class="bi bi-lightning-charge-fill me-2"></i>Electripid
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="user/login.php">Login</a></li>
                    <li class="nav-item">
                        <a href="user/register.php" class="btn btn-primary ms-2">
                            <i class="bi bi-person-plus me-1"></i> Sign Up Free
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section id="home" class="hero-section py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-md-6">
                    <h1 class="display-4 fw-bold text-primary mb-4">Smart Energy Management for a Sustainable Future</h1>
                    <p class="fs-5 text-secondary mb-4" style="max-width: 600px;">
                        Track your electricity consumption, get personalized recommendations,
                        and contribute to a greener planet with Electripid.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="user/register.php" class="btn text-white fw-semibold shadow-sm" style="background-color: #00c853;">
                            <i class="bi bi-rocket-takeoff me-2"></i> Get Started Free
                        </a>
                    <a href="#features" class="btn btn-outline-primary">
                        <i class="bi bi-info-circle me-2"></i> Learn More
                    </a>
                </div>
            </div>
        </div>
        </div>
    </section>

    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center fw-bold text-primary mb-5 position-relative section-title">Our Powerful Features</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border shadow-sm feature-card">
                        <div class="card-body p-4">
                            <div class="feature-icon rounded-circle d-flex align-items-center justify-content-center mb-4 text-primary fs-3">
                                <i class="bi bi-lightning-charge-fill"></i>
                            </div>
                            <h4>Real-time Monitoring</h4>
                            <p>Track your electricity usage in real-time with detailed analytics and visual reports.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border shadow-sm feature-card">
                        <div class="card-body p-4">
                            <div class="feature-icon rounded-circle d-flex align-items-center justify-content-center mb-4 text-primary fs-3">
                                <i class="bi bi-robot"></i>
                            </div>
                            <h4>AI-Powered Chatbot</h4>
                            <p>Get instant answers to your energy questions with our intelligent chatbot assistant.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border shadow-sm feature-card">
                        <div class="card-body p-4">
                            <div class="feature-icon rounded-circle d-flex align-items-center justify-content-center mb-4 text-primary fs-3">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <h4>Smart Recommendations</h4>
                            <p>Receive personalized tips to reduce your energy consumption and save money.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border shadow-sm feature-card">
                        <div class="card-body p-4">
                            <div class="feature-icon rounded-circle d-flex align-items-center justify-content-center mb-4 text-primary fs-3">
                                <i class="bi bi-bell-fill"></i>
                            </div>
                            <h4>Smart Reminders</h4>
                            <p>Set reminders for appliance maintenance and energy-saving opportunities.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border shadow-sm feature-card">
                        <div class="card-body p-4">
                            <div class="feature-icon rounded-circle d-flex align-items-center justify-content-center mb-4 text-primary fs-3">
                                <i class="bi bi-cloud-sun-fill"></i>
                            </div>
                            <h4>Weather Alerts</h4>
                            <p>Get alerts about weather conditions that affect your energy consumption.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border shadow-sm feature-card">
                        <div class="card-body p-4">
                            <div class="feature-icon rounded-circle d-flex align-items-center justify-content-center mb-4 text-primary fs-3">
                                <i class="bi bi-heart-fill"></i>
                            </div>
                            <h4>Green Donations</h4>
                            <p>Contribute to environmental causes and support renewable energy projects.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-section text-white pt-4 pb-2">
        <div class="container">
            <!-- Centered Tagline -->
            <div class="row">
                <div class="col-12 text-center mb-4">
                    <h2 class="display-6 fw-bold mb-4">Smart Energy Management for a Sustainable Future</h2>
                    <p class="fs-5 opacity-75">We help you track, optimize, and reduce your electricity consumption.</p>
                </div>
            </div>
        </div>
        </div>
    </section>

    <section id="how-it-works" class="py-5">
        <div class="container">
            <h2 class="text-center fw-bold text-primary mb-5 position-relative section-title">How It Works</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="step-number rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 text-white fw-bold fs-5">1</div>
                        <h4>Sign Up</h4>
                        <p>Create your free account in less than 2 minutes.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="step-number rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 text-white fw-bold fs-5">2</div>
                        <h4>Add Appliances</h4>
                        <p>Enter your appliances and their usage patterns.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="step-number rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 text-white fw-bold fs-5">3</div>
                        <h4>Track & Save</h4>
                        <p>Monitor usage and follow recommendations to save energy.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h4 class="mb-3"><i class="bi bi-lightning-charge-fill me-2"></i>Electripid</h4>
                    <p>Smart energy management for a sustainable future. We help you track, optimize, and reduce your electricity consumption.</p>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <div class="d-flex flex-column">
                        <a href="index.php#home" class="text-white-50 text-decoration-none mb-2 footer-links">Home</a>
                        <a href="index.php#features" class="text-white-50 text-decoration-none mb-2 footer-links">Features</a>
                        <a href="index.php#how-it-works" class="text-white-50 text-decoration-none mb-2 footer-links">How It Works</a>
                        <a href="user/login.php" class="text-white-50 text-decoration-none mb-2 footer-links">Login</a>
                        <a href="user/register.php" class="text-white-50 text-decoration-none mb-2 footer-links">Register</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 mb-4">
                    <h5 class="mb-3">User Area</h5>
                    <div class="d-flex flex-column">
                        <a href="user/login.php" class="text-white-50 text-decoration-none mb-2 footer-links"><i class="bi bi-box-arrow-in-right me-1"></i> User Login</a>
                        <a href="user/register.php" class="text-white-50 text-decoration-none mb-2 footer-links"><i class="bi bi-person-plus me-1"></i> User Register</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 mb-4">
                    <h5 class="mb-3">Contact Us</h5>
                    <div class="d-flex flex-column">
                        <a href="mailto:info@electripid.com" class="text-white-50 text-decoration-none mb-2 footer-links"><i class="bi bi-envelope me-2"></i>info@electripid.com</a>
                        <a href="tel:+1234567890" class="text-white-50 text-decoration-none mb-2 footer-links"><i class="bi bi-phone me-2"></i>+1 (234) 567-890</a>
                        <a href="#" class="text-white-50 text-decoration-none mb-2 footer-links"><i class="bi bi-geo-alt me-2"></i>123 Green Street, Eco City</a>
                    </div>
                </div>
            </div>
            <div class="border-top border-white border-opacity-25 pt-4 mt-5 text-center">
                <p class="text-white-50 mb-0">&copy; 2024 Electripid. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
                navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
            } else {
                navbar.style.backgroundColor = 'white';
                navbar.style.boxShadow = 'none';
            }
        });

        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= (sectionTop - 100)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>

</html>