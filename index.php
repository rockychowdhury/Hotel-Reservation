<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Haven Hotel - Your Perfect Stay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#e74c3c',
                        secondary: '#3498db',
                        dark: '#2c3e50',
                        light: '#ecf0f1'
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.8s ease-out',
                        'bounce-slow': 'bounce 2s infinite',
                        'slide-in-left': 'slideInLeft 0.6s ease-out',
                        'slide-in-right': 'slideInRight 0.6s ease-out',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .hero-bg {
            background: linear-gradient(to bottom right, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)),
            url('https://i.ibb.co/9m7Cwr0G/1407953244000-177513283.jpg') center/cover;
        }
        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .gradient-text {
            background: linear-gradient(135deg, #e74c3c, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 glass-effect shadow-lg transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-hotel text-primary text-2xl"></i>
                    <span class="text-xl font-bold text-dark">Luxury Haven</span>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#home" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Home</a>
                    <a href="#services" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Services</a>
                    <a href="#about" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">About</a>
                    <a href="#contact" class="text-gray-700 hover:text-primary transition-colors duration-300 font-medium">Contact</a>
                    <a href="reservation.php" class="bg-primary text-white px-6 py-2 rounded-full hover:bg-red-600 transition-all duration-300 transform hover:scale-105 shadow-lg">Book Now</a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button id="mobile-menu-btn" class="text-gray-700 hover:text-primary focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white shadow-lg">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="#home" class="block px-3 py-2 text-gray-700 hover:text-primary transition-colors">Home</a>
                <a href="#services" class="block px-3 py-2 text-gray-700 hover:text-primary transition-colors">Services</a>
                <a href="#about" class="block px-3 py-2 text-gray-700 hover:text-primary transition-colors">About</a>
                <a href="#contact" class="block px-3 py-2 text-gray-700 hover:text-primary transition-colors">Contact</a>
                <a href="reservation.php" class="block mx-3 mt-3 bg-primary text-white text-center py-2 rounded-full hover:bg-red-600 transition-colors">Book Now</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-bg min-h-screen flex items-center justify-center relative">
        <div class="absolute inset-0 bg-black bg-opacity-20"></div>
        <div class="relative z-10 text-center text-white px-4 max-w-4xl mx-auto">
            <h1 class="text-5xl md:text-7xl font-bold mb-6 animate-fade-in-up">Welcome to <span class="block gradient-text">Luxury Haven</span></h1>
            <p class="text-xl md:text-2xl mb-8 opacity-90 animate-fade-in-up animation-delay-200">Experience unparalleled comfort and luxury in the heart of the city</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center animate-fade-in-up animation-delay-400">
                <a href="#booking" class="bg-primary hover:bg-red-600 text-white px-8 py-4 rounded-full text-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-2xl">
                    Book Your Stay
                </a>
                <a href="#rooms" class="border-2 border-white text-white hover:bg-white hover:text-dark px-8 py-4 rounded-full text-lg font-semibold transition-all duration-300 transform hover:scale-105">
                    View Rooms
                </a>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce-slow">
            <div class="w-6 h-6 border-r-2 border-b-2 border-white transform rotate-45"></div>
        </div>
    </section>



    <!-- Management System Services -->
    <section id="services" class="py-20 bg-gradient-to-r from-gray-50 to-blue-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-dark mb-4">Hotel Management System</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Streamlined operations for the perfect guest experience</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Reservation Management -->
                <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 cursor-pointer group" onclick="window.location.href='reservation.php'">
                    <div class="w-16 h-16 bg-gradient-to-r from-primary to-red-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-calendar-check text-2xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-dark mb-4">Make Reservation</h3>
                    <p class="text-gray-600 mb-6">Easy online booking system with real-time availability</p>
                    <div class="flex items-center text-primary font-semibold">
                        <span>Book Now</span>
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                    </div>
                </div>

                <!-- Check-in/Check-out -->
                <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 cursor-pointer group" onclick="window.location.href='checkin.php'">
                    <div class="w-16 h-16 bg-gradient-to-r from-secondary to-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-door-open text-2xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-dark mb-4">Check-in / Check-out</h3>
                    <p class="text-gray-600 mb-6">Seamless arrival and departure process management</p>
                    <div class="flex items-center text-secondary font-semibold">
                        <span>Manage</span>
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                    </div>
                </div>

                <!-- Room Management -->
                <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 cursor-pointer group" onclick="window.location.href='rooms.php'">
                    <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-bed text-2xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-dark mb-4">Room Management</h3>
                    <p class="text-gray-600 mb-6">Monitor room status, availability, and maintenance</p>
                    <div class="flex items-center text-green-600 font-semibold">
                        <span>View Rooms</span>
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                    </div>
                </div>

                <!-- Guest Management -->
                <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 cursor-pointer group" onclick="window.location.href='guests.php'">
                    <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-users text-2xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-dark mb-4">Guest Management</h3>
                    <p class="text-gray-600 mb-6">Comprehensive guest profiles and service history</p>
                    <div class="flex items-center text-purple-600 font-semibold">
                        <span>Manage Guests</span>
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                    </div>
                </div>

                <!-- Billing & Payment -->
                <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 cursor-pointer group" onclick="window.location.href='billing.php'">
                    <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-receipt text-2xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-dark mb-4">Billing & Payment</h3>
                    <p class="text-gray-600 mb-6">Automated billing and secure payment processing</p>
                    <div class="flex items-center text-orange-600 font-semibold">
                        <span>View Billing</span>
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Hotel Features -->
    <section id="about" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-dark mb-4">Why Choose Luxury Haven?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Discover the amenities and services that make us the preferred choice</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center group">
                    <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-wifi text-3xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold text-dark mb-2">Free Wi-Fi</h4>
                    <p class="text-gray-600">High-speed internet throughout the hotel premises</p>
                </div>

                <div class="text-center group">
                    <div class="w-20 h-20 bg-gradient-to-r from-teal-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-swimming-pool text-3xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold text-dark mb-2">Swimming Pool</h4>
                    <p class="text-gray-600">Relax in our beautiful rooftop infinity pool</p>
                </div>

                <div class="text-center group">
                    <div class="w-20 h-20 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-utensils text-3xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold text-dark mb-2">Fine Dining</h4>
                    <p class="text-gray-600">World-class cuisine at our award-winning restaurant</p>
                </div>

                <div class="text-center group">
                    <div class="w-20 h-20 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-car text-3xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold text-dark mb-2">Valet Parking</h4>
                    <p class="text-gray-600">Complimentary valet parking for all guests</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-20 bg-gradient-to-r from-primary to-red-600 text-white">
        <div class="max-w-4xl mx-auto text-center px-4">
            <h2 class="text-4xl md:text-5xl font-bold mb-6">Ready for Your Perfect Stay?</h2>
            <p class="text-xl mb-8 opacity-90">Book now and experience luxury like never before</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="reservation.php" class="bg-white text-primary px-8 py-4 rounded-full text-lg font-semibold hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 shadow-lg">
                    Make Reservation
                </a>
                <a href="#contact" class="border-2 border-white text-white hover:bg-white hover:text-primary px-8 py-4 rounded-full text-lg font-semibold transition-all duration-300 transform hover:scale-105">
                    Contact Us
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="bg-dark text-white py-16">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Hotel Info -->
                <div class="lg:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-hotel text-primary text-3xl"></i>
                        <span class="text-2xl font-bold">Luxury Haven</span>
                    </div>
                    <p class="text-gray-300 mb-6 max-w-md">Experience luxury and comfort at its finest. Your perfect stay awaits at the heart of the city with world-class amenities and exceptional service.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-primary rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-primary rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-primary rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-primary rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-xl font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="reservation.php" class="text-gray-300 hover:text-primary transition-colors">Make Reservation</a></li>
                        <li><a href="rooms.php" class="text-gray-300 hover:text-primary transition-colors">View Rooms</a></li>
                        <li><a href="guests.php" class="text-gray-300 hover:text-primary transition-colors">Guest Services</a></li>
                        <li><a href="checkin.php" class="text-gray-300 hover:text-primary transition-colors">Check-in/Check-out</a></li>
                        <li><a href="billing.php" class="text-gray-300 hover:text-primary transition-colors">Billing</a></li>
                        <li><a href="#about" class="text-gray-300 hover:text-primary transition-colors">About Us</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-xl font-bold mb-4">Contact Info</h4>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-phone text-primary"></i>
                            <span class="text-gray-300">+1 (555) 123-4567</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-envelope text-primary"></i>
                            <span class="text-gray-300">info@luxuryhaven.com</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                            <span class="text-gray-300">123 Luxury Street<br>Downtown District<br>New York, NY 10001</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-clock text-primary"></i>
                            <span class="text-gray-300">24/7 Front Desk Service</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Newsletter Signup -->
            <div class="border-t border-gray-600 mt-12 pt-8">
                <div class="max-w-md mx-auto text-center">
                    <h4 class="text-xl font-bold mb-4">Stay Updated</h4>
                    <p class="text-gray-300 mb-4">Subscribe to our newsletter for exclusive offers and updates</p>
                    <form class="flex flex-col sm:flex-row gap-3">
                        <input type="email" placeholder="Enter your email" 
                               class="flex-1 px-4 py-3 rounded-full bg-gray-700 text-white placeholder-gray-400 border border-gray-600 focus:border-primary focus:outline-none transition-colors">
                        <button type="submit" 
                                class="bg-primary hover:bg-red-600 text-white px-6 py-3 rounded-full font-semibold transition-colors whitespace-nowrap">
                            Subscribe
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-gray-600 mt-12 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-gray-300 text-center md:text-left mb-4 md:mb-0">
                        <p>&copy; 2024 Luxury Haven Hotel. All rights reserved.</p>
                    </div>
                    <div class="flex flex-wrap justify-center md:justify-end space-x-6 text-sm">
                        <a href="#" class="text-gray-300 hover:text-primary transition-colors">Privacy Policy</a>
                        <a href="#" class="text-gray-300 hover:text-primary transition-colors">Terms of Service</a>
                        <a href="#" class="text-gray-300 hover:text-primary transition-colors">Cookie Policy</a>
                        <a href="#" class="text-gray-300 hover:text-primary transition-colors">Accessibility</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="fixed bottom-8 right-8 bg-primary hover:bg-red-600 text-white w-12 h-12 rounded-full shadow-lg opacity-0 invisible transition-all duration-300 transform hover:scale-110 z-50">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- JavaScript -->
    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
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
                    // Close mobile menu if open
                    mobileMenu.classList.add('hidden');
                }
            });
        });

        // Back to top button
        const backToTopBtn = document.getElementById('backToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.remove('opacity-0', 'invisible');
                backToTopBtn.classList.add('opacity-100', 'visible');
            } else {
                backToTopBtn.classList.add('opacity-0', 'invisible');
                backToTopBtn.classList.remove('opacity-100', 'visible');
            }
        });

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Set minimum date for booking form
        const today = new Date().toISOString().split('T')[0];
        const checkinInput = document.querySelector('input[name="checkin"]');
        const checkoutInput = document.querySelector('input[name="checkout"]');
        
        if (checkinInput && checkoutInput) {
            checkinInput.setAttribute('min', today);
            
            checkinInput.addEventListener('change', function() {
                const checkinDate = new Date(this.value);
                checkinDate.setDate(checkinDate.getDate() + 1);
                const minCheckout = checkinDate.toISOString().split('T')[0];
                checkoutInput.setAttribute('min', minCheckout);
                
                if (checkoutInput.value && checkoutInput.value <= this.value) {
                    checkoutInput.value = minCheckout;
                }
            });
        }

        // Navbar background on scroll
        const navbar = document.querySelector('nav');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 100) {
                navbar.classList.add('backdrop-blur-md');
            } else {
                navbar.classList.remove('backdrop-blur-md');
            }
        });

        // Newsletter form submission
        const newsletterForm = document.querySelector('footer form');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const email = this.querySelector('input[type="email"]').value;
                if (email) {
                    alert('Thank you for subscribing to our newsletter!');
                    this.reset();
                }
            });
        }

        // Animation on scroll (simple implementation)
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.group, .animate-fade-in-up').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>