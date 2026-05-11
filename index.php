<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
start_app_session();
$csrf = csrf_token();

$shorts_ids = ['yiTjZajndfE', 'tuW4r1IArDY', '6Y_5UTtf_CI', 'K8ZBQpI09VA', 'cnZ7ghJ59hM', 'cF9bWTC2ImM'];

$banners = [
    ['url' => 'assets/images/banner-foot-problems.jpg', 'alt' => 'Common Foot Problems'],
    ['url' => 'assets/images/banner-insole-milling.jpg', 'alt' => 'Insole Milling Technology'],
    ['url' => 'assets/images/banner-pressure-sensors.jpg', 'alt' => 'Pressure Sensor Technology']
];

$problems = [
    ['Diabetic Neuropathy', 'Nerve damage from high blood sugar leading to tingling, numbness, or burning.', 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&q=80&w=400'],
    ['Foot Ulcers', 'Open sores caused by pressure or injury.', 'assets/images/open-source/diabetic-foot-ulcer.jpg'],
    ['Calluses & Corns', 'Thickened skin from friction.', 'assets/images/open-source/corn-callus.jpg'],
    ['Poor Circulation', 'Reduced blood flow to extremities.', 'https://images.unsplash.com/photo-1559839734-2b71f1e3c7e5?auto=format&fit=crop&q=80&w=400'],
    ['Hammer Toes', 'Toe deformities causing abnormal bending.', 'https://upload.wikimedia.org/wikipedia/commons/5/59/Hammer_toes.jpg'],
    ['Bunions', 'Bony bump at the base of the big toe.', 'assets/images/open-source/bunion.jpg'],
    ['Flat Feet', 'Arches collapse, causing inward roll.', 'assets/images/open-source/flat-foot.jpg'],
    ['Charcot Foot', 'Serious condition weakening bones.', 'assets/images/open-source/charcot-foot.jpg'],
    ['Heel Pain', 'Inflammation of plantar fascia.', 'assets/images/open-source/heel-spur.png'],
    ['Ingrown Toenails', 'Nail edges growing into skin.', 'https://images.unsplash.com/photo-1519415943484-9fa1873496d4?auto=format&fit=crop&q=80&w=400']
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flexi Feet | Custom Diabetic & Orthopaedic Footwear Malaysia</title>
    <meta name="description" content="Flexi Feet Sdn Bhd specializes in custom-made diabetic and orthopaedic shoes using 3D Italian foot scanning technology in Sentul, Kuala Lumpur.">
    
    <!-- Open Graph / SEO -->
    <meta property="og:title" content="Flexi Feet | Custom Diabetic & Orthopaedic Footwear">
    <meta property="og:description" content="Medically approved, handcrafted custom shoes for diabetic neuropathy and orthopaedic comfort.">
    <meta property="og:image" content="assets/images/flexi-feet-logo.png">
    <meta property="og:type" content="website">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<header>
    <div class="nav-container">
        <a href="#home" class="logo">
            <img src="assets/images/flexi-feet-logo.png" alt="Flexi Feet Sdn Bhd">
        </a>
        <nav>
            <ul>
                <li><a href="#about">About</a></li>
                <li><a href="#products">Products</a></li>
                <li><a href="#technology">Technology</a></li>
                <li><a href="#conditions">Conditions</a></li>
                <li><a href="#process">Process</a></li>
            </ul>
        </nav>
        <div class="burger-menu" id="burger-toggle">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <a href="#booking" class="cta-button">Book a Fitting</a>
    </div>
</header>

<main>
    <section id="home" class="hero">
        <div class="container hero-content">
            <div class="hero-text reveal">
                <h1><strong>Custom-Made</strong> Diabetic Shoes <em>for Unmatched Comfort & Protection</em></h1>
                <p class="subheadline">Designed with care. Crafted for your health. Personalized to fit your life.</p>
                <p class="intro-text">At Flexi Feet Sdn Bhd, we specialize in designing and manufacturing custom diabetic & Orthopaedic footwear tailored to the unique needs of your feet. Our shoes are clinically supportive, medically approved, and stylish—because you deserve comfort without compromise.</p>
                <div class="hero-actions">
                    <a href="#booking" class="cta-button">Book Now</a>
                    <a href="#products" style="margin-left: 20px; font-weight: 500;">View Collection &rarr;</a>
                </div>
            </div>
            <div class="hero-visual reveal">
                <img src="assets/images/shoe-web.png" alt="Custom Diabetic Shoe" class="hero-image">
            </div>
        </div>
    </section>

    <!-- Hero Banner Slider -->
    <section class="hero-slider-section reveal">
        <div class="swiper hero-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($banners as $banner): ?>
                    <div class="swiper-slide">
                        <img src="<?= $banner['url'] ?>" alt="<?= $banner['alt'] ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </section>

    <section id="about" class="container reveal">
        <h2 class="section-title">About Flexi Feet</h2>
        <p class="section-subtitle">Dedicated to improving your quality of life through specialized diabetic and orthopaedic foot care solutions.</p>
        <div class="grid-3">
            <div class="card reveal hover-lift">
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3>Our Mission</h3>
                <ul class="check-list">
                    <li>Empower patients with premium footwear</li>
                    <li>Drastically reduce foot complications</li>
                    <li>Improve mobility & daily comfort</li>
                </ul>
            </div>
            <div class="card reveal hover-lift">
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3>Who We Are</h3>
                <ul class="check-list">
                    <li>Medical-professional-led design</li>
                    <li>Expertise in diabetic biomechanics</li>
                    <li>Malaysian-crafted precision</li>
                </ul>
            </div>
            <div class="card reveal hover-lift">
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h3>Why Choose Us</h3>
                <ul class="check-list">
                    <li>Latest 3D Italian Foot Scanner</li>
                    <li>Personalized fittings & assessments</li>
                    <li>Quick Delivery & Handcrafted</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Reels Style Shorts Slider -->
    <section id="shorts" class="shorts-section reveal">
        <div class="container">
            <h2 class="section-title">Flexi Stories</h2>
            <p class="section-subtitle" style="color: rgba(255,255,255,0.7);">Watch how our custom footwear solutions change lives through these short insights.</p>
            <div class="reels-container">
                <div class="swiper shorts-swiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($shorts_ids as $id): ?>
                            <div class="swiper-slide">
                                <iframe src="https://www.youtube.com/embed/<?= $id ?>?autoplay=0&loop=1&playlist=<?= $id ?>&controls=0&modestbranding=1" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </div>
    </section>

    <section id="products" class="container reveal">
        <div class="scanning-grid">
            <div class="scanning-text">
                <h2 style="font-size: 32px; margin-bottom: 20px;">Custom Diabetic Shoes</h2>
                <p>Our custom shoes are made to accommodate common diabetic foot issues such as neuropathy, poor circulation, and foot deformities. Choose from a wide range of styles, widths, and features:</p>
                <ul class="check-list" style="margin-top: 20px;">
                    <li>Orthotic-Friendly Soles</li>
                    <li>Extra Depth & Width Options</li>
                    <li>Seamless Interior to Prevent Irritation</li>
                    <li>Breathable, Durable Materials</li>
                </ul>
                <div style="margin-top: 40px;">
                    <h4 style="margin-bottom: 20px;">Styles Available:</h4>
                    <div class="grid-3" style="grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div class="card hover-lift" style="padding: 15px; text-align: center;">
                            <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&q=80&w=400" alt="Walking Shoes" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 10px;">
                            <p style="font-weight: 700; font-size: 14px; color: var(--logo-navy);">Walking Shoes</p>
                        </div>
                        <div class="card hover-lift" style="padding: 15px; text-align: center;">
                            <img src="https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?auto=format&fit=crop&q=80&w=400" alt="Dress Shoes" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 10px;">
                            <p style="font-weight: 700; font-size: 14px; color: var(--logo-navy);">Dress Shoes</p>
                        </div>
                        <div class="card hover-lift" style="padding: 15px; text-align: center;">
                            <img src="https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&q=80&w=400" alt="Sandals" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 10px;">
                            <p style="font-weight: 700; font-size: 14px; color: var(--logo-navy);">Sandals</p>
                        </div>
                        <div class="card hover-lift" style="padding: 15px; text-align: center;">
                            <img src="https://images.unsplash.com/photo-1579154235823-149b068bc22c?auto=format&fit=crop&q=80&w=400" alt="Boots" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 10px;" loading="lazy">
                            <p style="font-weight: 700; font-size: 14px; color: var(--logo-navy);">Boots</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hero-visual">
                <img src="https://images.unsplash.com/photo-1556155092-490a1ba16284?auto=format&fit=crop&q=80&w=1000" alt="Shoe Craftsmanship" class="scanning-image">
            </div>
        </div>
    </section>

    <section id="technology" class="scanning-section reveal">
        <div class="container scanning-grid">
            <div class="hero-visual">
                <img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&q=80&w=1000" alt="Advanced 3D Foot Scanner" class="scanning-image" loading="lazy">
            </div>
            <div class="scanning-text">
                <h2 style="font-size: 32px; margin-bottom: 20px;">Advanced Foot Scanning Technology</h2>
                <p>At Flexi Feet, we use state-of-the-art foot scanning technology to capture the unique contours, pressure points, and dimensions of your feet. Whether you're managing diabetic neuropathy, foot deformities, or simply seeking better support, our scanning system ensures your custom shoes fit like no other.</p>
                <p style="margin-top: 15px;">A foot scanner is a digital imaging device that captures 3D images of your feet. It collects data such as:</p>
                <ul class="check-list" style="margin-top: 15px;">
                    <li>Foot length & width</li>
                    <li>Arch height & shape</li>
                    <li>Pressure distribution</li>
                    <li>Gait (walking pattern)</li>
                    <li>Weight-bearing vs. non-weight-bearing dimensions</li>
                </ul>
            </div>
        </div>
    </section>

    <section id="conditions" class="container reveal">
        <h2 class="section-title">Common Foot Problems We Address</h2>
        <p class="section-subtitle">We specialize in identifying and treating a wide range of conditions that affect diabetic and orthopaedic health.</p>
        <div class="problems-grid">
            <?php foreach ($problems as $index => $problem): ?>
                <div class="problem-card">
                    <img src="<?= $problem[2] ?>" alt="<?= e($problem[0]) ?>" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-bottom: 15px;">
                    <h3><?= ($index + 1) . '. ' . e($problem[0]) ?></h3>
                    <p><?= e($problem[1]) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="process" class="container reveal">
        <h2 class="section-title">How It Works</h2>
        <p class="section-subtitle">Your journey to perfect comfort is simple, professional, and personalized at every step.</p>
        <div class="grid-3">
            <div class="card" style="text-align: center;">
                <div style="font-size: 40px; font-weight: 800; color: var(--apple-blue); margin-bottom: 20px;">1</div>
                <h3>Schedule a Consultation</h3>
                <p>Meet with one of our foot specialists virtually or in person.</p>
            </div>
            <div class="card" style="text-align: center;">
                <div style="font-size: 40px; font-weight: 800; color: var(--apple-blue); margin-bottom: 20px;">2</div>
                <h3>Get a Custom Fitting</h3>
                <p>We measure every detail of your feet using 3D scans or molds.</p>
            </div>
            <div class="card" style="text-align: center;">
                <div style="font-size: 40px; font-weight: 800; color: var(--apple-blue); margin-bottom: 20px;">3</div>
                <h3>Choose Your Style</h3>
                <p>Pick from a variety of fashionable and functional designs.</p>
            </div>
        </div>
    </section>

    <section id="location" class="container reveal">
        <h2 class="section-title">Visit Our Specialist Center</h2>
        <p class="section-subtitle">Located in the heart of Sentul, our facility is equipped with the latest diagnostic technology.</p>
        <div class="grid-3" style="grid-template-columns: 1fr 2fr; gap: 40px; align-items: center;">
            <div class="contact-details-card">
                <div class="card" style="padding: 30px; border-radius: var(--radius-lg);">
                    <h3 style="margin-bottom: 20px;">Contact Information</h3>
                    <div style="display: grid; gap: 20px;">
                        <div>
                            <p style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Address</p>
                            <p style="font-size: 15px;"><?= nl2br(e(BUSINESS_ADDRESS)) ?></p>
                        </div>
                        <div>
                            <p style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Phone</p>
                            <a href="tel:<?= str_replace(' ', '', BUSINESS_PHONE) ?>" style="font-size: 18px; font-weight: 600; color: var(--apple-blue);"><?= e(BUSINESS_PHONE) ?></a>
                        </div>
                        <div>
                            <p style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Email</p>
                            <a href="mailto:<?= e(BUSINESS_EMAIL) ?>" style="font-size: 15px;"><?= e(BUSINESS_EMAIL) ?></a>
                        </div>
                        <div style="display: flex; gap: 15px; margin-top: 10px;">
                            <a href="https://www.instagram.com/flexifeetmalaysia/" target="_blank" class="cta-button" style="padding: 8px 12px; background: var(--apple-gray-100); color: var(--text) !important;">IG</a>
                            <a href="https://www.facebook.com/flexifeetmalaysia" target="_blank" class="cta-button" style="padding: 8px 12px; background: var(--apple-gray-100); color: var(--text) !important;">FB</a>
                            <a href="https://www.youtube.com/@flexifeetmalaysia" target="_blank" class="cta-button" style="padding: 8px 12px; background: var(--apple-gray-100); color: var(--text) !important;">YT</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="map-container" style="border-radius: var(--radius-lg); overflow: hidden; height: 400px; box-shadow: var(--shadow-lg);">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3983.655823190847!2d101.68832677582522!3d3.203362152516484!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc380720442385%3A0xc3f7a1f59995555e!2sResidency%20Awani%202!5e0!3m2!1sen!2smy!4v1715360000000!5m2!1sen!2smy" 
                    width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </section>

    <section id="booking" class="booking-section">
        <div class="container booking-container">
            <div class="booking-text">
                <h2 style="font-size: 48px; margin-bottom: 30px; color: white;">Apply for an Appointment</h2>
                <p style="font-size: 18px; opacity: 0.9; margin-bottom: 40px;">Getting your custom footwear is a seamless 3-step process. Start by submitting your request below.</p>
                
                <div class="appointment-steps" style="margin-bottom: 40px;">
                    <div style="display: flex; gap: 20px; margin-bottom: 30px;">
                        <div style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 50%; background: var(--logo-cyan); display: flex; justify-content: center; align-items: center; font-weight: 700; color: var(--logo-navy);">1</div>
                        <div>
                            <h4 style="color: white; margin-bottom: 5px;">Submit Your Request</h4>
                            <p style="font-size: 14px; opacity: 0.8;">Fill out the form with your contact details and preferred visit time.</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px; margin-bottom: 30px;">
                        <div style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 50%; background: var(--logo-cyan); display: flex; justify-content: center; align-items: center; font-weight: 700; color: var(--logo-navy);">2</div>
                        <div>
                            <h4 style="color: white; margin-bottom: 5px;">Expert Consultation</h4>
                            <p style="font-size: 14px; opacity: 0.8;">Our specialist team will review your request and call you within 24 hours to finalize your slot.</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px;">
                        <div style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 50%; background: var(--logo-cyan); display: flex; justify-content: center; align-items: center; font-weight: 700; color: var(--logo-navy);">3</div>
                        <div>
                            <h4 style="color: white; margin-bottom: 5px;">Visit Our Center</h4>
                            <p style="font-size: 14px; opacity: 0.8;">Experience our 3D scanning technology and get your personalized foot assessment.</p>
                        </div>
                    </div>
                </div>

                <div class="opening-hours" style="background: rgba(255,255,255,0.05); padding: 25px; border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.1);">
                    <h4 style="color: var(--logo-cyan); margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Official Opening Hours
                    </h4>
                    <div style="display: grid; gap: 8px; font-size: 14px; opacity: 0.9;">
                        <div style="display: flex; justify-content: space-between;"><span>Monday – Friday</span><span>9:00 AM – 6:00 PM</span></div>
                        <div style="display: flex; justify-content: space-between;"><span>Saturday</span><span>9:00 AM – 1:00 PM</span></div>
                        <div style="display: flex; justify-content: space-between; color: #ff3b30;"><span>Sunday</span><span>Closed</span></div>
                    </div>
                </div>
            </div>
            <div class="booking-form">
                <form action="api/booking.php" method="POST" data-booking-form>
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" required placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" required placeholder="+60 12-345 6789">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="john@example.com">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Preferred Date</label>
                            <input type="date" name="preferred_date" id="booking-date" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Preferred Time</label>
                            <input type="time" name="preferred_time" id="booking-time" required min="09:00" max="18:00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Visit Type</label>
                        <select name="visit_type" required>
                            <option value="">Select visit type</option>
                            <option>Foot Assessment</option>
                            <option>Custom Shoes / Footwear Fitting</option>
                            <option>Customised Insole Assessment</option>
                            <option>Pressure Sensor Scan</option>
                            <option>Follow-up</option>
                            <option>General Consultation</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Message / Notes</label>
                        <textarea name="notes" rows="4" placeholder="Tell us more about your needs..."></textarea>
                    </div>
                    <button type="submit" class="submit-btn">Request Appointment</button>
                    <p id="form-message" style="margin-top: 15px; font-size: 14px; text-align: center;"></p>
                </form>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-info">
                <img src="assets/images/flexi-feet-logo.png" alt="Flexi Feet" style="height: 40px; margin-bottom: 20px;">
                <p style="color: var(--text-muted); max-width: 300px; margin-bottom: 20px;">Custom-made diabetic and orthopaedic footwear in Kuala Lumpur. Medically approved, clinically supportive.</p>
                <div style="font-size: 14px; color: var(--text);">
                    <p><strong>Flexi Feet Sdn Bhd</strong></p>
                    <p><?= e(BUSINESS_ADDRESS) ?></p>
                    <p style="margin-top: 10px;"><?= e(BUSINESS_PHONE) ?></p>
                </div>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#products">Our Products</a></li>
                    <li><a href="#technology">Technology</a></li>
                    <li><a href="#conditions">Conditions</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Legal</h4>
                <ul>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="admin/login.php">Admin Login</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Flexi Feet Sdn Bhd. All rights reserved.</p>
            <p>Designed with medical precision in Kuala Lumpur.</p>
        </div>
    </div>
</footer>

<div class="scroll-top" id="scroll-top">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
</div>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="assets/app.js"></script>
</body>
</html>
