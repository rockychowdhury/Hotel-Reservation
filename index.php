<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Haven Hotel - Your Perfect Stay</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-hotel"></i>
                <span>Luxury Haven</span>
            </div>
            <ul class="nav-menu">
                <li><a href="#home">Home</a></li>
                <li><a href="#rooms">Rooms</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="nav-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">Welcome to Luxury Haven</h1>
            <p class="hero-subtitle">Experience unparalleled comfort and luxury in the heart of the city</p>
            <div class="hero-buttons">
                <a href="#booking" class="btn btn-primary">Book Now</a>
                <a href="#rooms" class="btn btn-secondary">Explore Rooms</a>
            </div>
        </div>
        <div class="scroll-indicator">
            <div class="scroll-arrow"></div>
        </div>
    </section>

    <!-- Quick Booking Widget -->
    <section id="booking" class="booking-widget">
        <div class="container">
            <div class="booking-form-container">
                <h3>Quick Reservation</h3>
                <form class="booking-form" id="quickBookingForm">
                    <div class="form-group">
                        <label>Check-in Date</label>
                        <input type="date" name="checkin" required>
                    </div>
                    <div class="form-group">
                        <label>Check-out Date</label>
                        <input type="date" name="checkout" required>
                    </div>
                    <div class="form-group">
                        <label>Room Type</label>
                        <select name="room_type" required>
                            <option value="">Select Room Type</option>
                            <option value="1">Single Room - $99.99/night</option>
                            <option value="2">Double Room - $149.99/night</option>
                            <option value="3">Suite - $299.99/night</option>
                            <option value="4">Family Room - $199.99/night</option>
                            <option value="5">Presidential Suite - $599.99/night</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Adults</label>
                            <select name="adults">
                                <option value="1">1 Adult</option>
                                <option value="2">2 Adults</option>
                                <option value="3">3 Adults</option>
                                <option value="4">4 Adults</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Children</label>
                            <select name="children">
                                <option value="0">0 Children</option>
                                <option value="1">1 Child</option>
                                <option value="2">2 Children</option>
                                <option value="3">3 Children</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="window.location.href='reservation.php'">
                        Check Availability
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="container">
            <h2 class="section-title">Hotel Management System</h2>
            <div class="services-grid">
                <div class="service-card" onclick="window.location.href='reservation.php'">
                    <div class="service-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Make Reservation</h3>
                    <p>Book your perfect room with our easy reservation system</p>
                </div>
                
                <div class="service-card" onclick="window.location.href='checkin.php'">
                    <div class="service-icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <h3>Check-in / Check-out</h3>
                    <p>Seamless check-in and check-out process for guests</p>
                </div>
                
                <div class="service-card" onclick="window.location.href='rooms.php'">
                    <div class="service-icon">
                        <i class="fas fa-bed"></i>
                    </div>
                    <h3>Room Management</h3>
                    <p>View and manage room availability and status</p>
                </div>
                
                <div class="service-card" onclick="window.location.href='guests.php'">
                    <div class="service-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Guest Management</h3>
                    <p>Manage guest information and profiles</p>
                </div>
                
                <div class="service-card" onclick="window.location.href='billing.php'">
                    <div class="service-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h3>Billing & Payment</h3>
                    <p>Handle billing, payments, and financial records</p>
                </div>
                
                <div class="service-card" onclick="window.location.href='reports.php'">
                    <div class="service-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Reports & Analytics</h3>
                    <p>View comprehensive reports and analytics</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Why Choose Luxury Haven?</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h4>Free Wi-Fi</h4>
                    <p>High-speed internet throughout the hotel</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-swimming-pool"></i>
                    </div>
                    <h4>Swimming Pool</h4>
                    <p>Relax in our beautiful outdoor pool</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h4>Fine Dining</h4>
                    <p>World-class cuisine at our restaurant</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h4>Valet Parking</h4>
                    <p>Complimentary valet parking service</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="logo">
                        <i class="fas fa-hotel"></i>
                        <span>Luxury Haven</span>
                    </div>
                    <p>Experience luxury and comfort at its finest. Your perfect stay awaits.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="reservation.php">Make Reservation</a></li>
                        <li><a href="rooms.php">View Rooms</a></li>
                        <li><a href="guests.php">Guest Services</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>+1 (555) 123-4567</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>info@luxuryhaven.com</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Luxury Street, City Center</span>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Luxury Haven Hotel. All rights reserved. | DBMS Project</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile navigation toggle
        const navToggle = document.querySelector('.nav-toggle');
        const navMenu = document.querySelector('.nav-menu');

        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            navToggle.classList.toggle('active');
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
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

        // Set minimum date for check-in to today
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="checkin"]').setAttribute('min', today);
        
        // Update checkout minimum date when checkin changes
        document.querySelector('input[name="checkin"]').addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            document.querySelector('input[name="checkout"]').setAttribute('min', checkinDate.toISOString().split('T')[0]);
        });
    </script>
</body>
</html>