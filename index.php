<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
start_app_session();
$csrf = csrf_token();
$reels = read_reels(true);
$latest_posts = array_slice(read_blog_posts(true), 0, 8);

$banners = [
    ['url' => 'assets/images/banner-foot-problems.jpg', 'alt' => 'Common Foot Problems'],
    ['url' => 'assets/images/banner-insole-milling.jpg', 'alt' => 'Insole Milling Technology'],
    ['url' => 'assets/images/banner-pressure-sensors.jpg', 'alt' => 'Pressure Sensor Technology']
];

$problems = [
    ['Diabetic Neuropathy', 'Nerve damage from high blood sugar leading to tingling, numbness, or burning.', 'assets/images/conditions/diabetic-neuropathy.jpg'],
    ['Foot Ulcers', 'Open sores caused by pressure or injury.', 'assets/images/conditions/foot-ulcers.jpg'],
    ['Calluses & Corns', 'Thickened skin from friction.', 'assets/images/conditions/calluses-corns.jpg'],
    ['Poor Circulation', 'Reduced blood flow to extremities.', 'assets/images/conditions/poor-circulation.jpg'],
    ['Hammer Toes', 'Toe deformities causing abnormal bending.', 'assets/images/conditions/hammer-toes.jpg'],
    ['Bunions', 'Bony bump at the base of the big toe.', 'assets/images/conditions/bunions.jpg'],
    ['Flat Feet', 'Arches collapse, causing inward roll.', 'assets/images/conditions/flat-feet.jpg'],
    ['Charcot Foot', 'Serious condition weakening bones.', 'assets/images/conditions/charcot-foot-updated.jpg'],
    ['Heel Pain', 'Inflammation of plantar fascia.', 'assets/images/conditions/heel-pain.jpg'],
    ['Amputation', 'Custom footwear and insole support after partial foot amputation.', 'assets/images/conditions/amputation.jpg']
];

$faqs = [
    ['DO I WANT to FIX APPOINTMENT?', 'Its always better to fix an appointment. To save your time.'],
    ['Does your shop have parking?', 'Yes, we have parking lot.'],
    ['Does your shop located in ground floor?', 'Yes, we are in ground floor'],
    ['How long it will take for initial consultation?', '20 to 30 mins'],
    ['How long will it take to receive my custom-made diabetic shoes?', '3 to 4 weeks'],
    ['If I order insole, can I insert into my existing sports shoe?', 'We use latest 3D technology; our insole will be slim and rigid Eva material. You can use in existing shoes. No need changes your shoe or to buy one size bigger.'],
    ['Can I get appointment on Sunday?', 'Can, but prior appointment and depends on staff availability.'],
    ['Home visit possible?', 'Home visit possible prior appointment. And there will be extra RM 200 travel cost inside Kl'],
    ['In Malaysia what are the places you provide service?', 'Our main branch is in KL -Sentul. We travel Ipoh, JB (Kulai) Monthly once.'],
    ['Payment method?', 'Card / qr / account transfer'],
    ['Payment terms?', '50% deposit while placing order, balance 50% while delivery.'],
    ['What is your return policy for custom products?', 'Because every pair is uniquely tailored to your specifications, we do not accept standard returns or exchanges for change of mind.'],
    ['What happens if my custom shoes do not fit properly?', 'If the fit is off, we will either reimburse you for minor adjustments made at a local cobbler or remake the pair for you free of charge'],
    ['What materials do you use?', 'We source premium, durable materials including top-grain French leather. Insoles we use EVA from Italy']
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <?php render_seo_tags(
        'Custom Diabetic Shoes & Orthopaedic Insoles Malaysia | Flexi Feet',
        'Flexi Feet in Sentul, Kuala Lumpur creates custom diabetic shoes, offload insoles, flat feet insoles and diabetic socks using 3D foot assessment.',
        '',
        'assets/images/shoe-web.png'
    ); ?>
    <?php render_json_ld([
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => BUSINESS_NAME,
        'url' => absolute_url(''),
        'telephone' => BUSINESS_PHONE,
        'email' => BUSINESS_EMAIL,
        'address' => BUSINESS_ADDRESS,
        'image' => absolute_url('assets/images/flexi-feet-logo.png'),
        'areaServed' => ['Kuala Lumpur', 'Sentul', 'Malaysia'],
        'sameAs' => [
            'https://www.instagram.com/flexifeetmalaysia/',
            'https://www.facebook.com/flexifeetmalaysia',
            'https://www.youtube.com/@flexifeetmalaysia'
        ],
        'makesOffer' => [
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Custom diabetic shoes']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Custom offload insoles']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => '3D foot assessment']]
        ]
    ]); ?>
    <?php render_google_analytics(); ?>

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
                <li><a href="blog.php">Blog</a></li>
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
            <div class="swiper-pagination hero-pagination"></div>
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

    <?php if (!empty($reels)): ?>
    <section id="shorts" class="shorts-section reveal">
        <div class="container">
            <h2 class="section-title">Flexi Stories</h2>
            <p class="section-subtitle" style="color: rgba(255,255,255,0.7);">Watch how our custom footwear solutions change lives through these short insights.</p>
            <div class="reels-container">
                <div class="swiper shorts-swiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($reels as $reel): ?>
                            <div class="swiper-slide">
                                <a class="shorts-card" href="<?= e($reel['url']) ?>" target="_blank" rel="noopener" aria-label="Open <?= e($reel['title'] ?: 'Flexi Feet Reel') ?>">
                                    <img src="<?= e($reel['thumbnail']) ?>" alt="<?= e($reel['title'] ?: 'Flexi Feet Instagram Reel') ?>" loading="lazy">
                                    <span class="shorts-play" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                    </span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="shorts-nav shorts-nav-prev" type="button" aria-label="Previous Short">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button class="shorts-nav shorts-nav-next" type="button" aria-label="Next Short">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                    <div class="swiper-pagination shorts-pagination"></div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section id="products" class="container reveal">
        <div class="scanning-grid">
            <div class="scanning-text">
                <h2 style="font-size: 32px; margin-bottom: 20px;">Custom Diabetic Shoes</h2>
                <p>Our custom shoes are made to accommodate common diabetic foot issues such as neuropathy, poor circulation, and foot deformities. Choose from a wide range of styles, widths, and features:</p>
                <ul class="check-list" style="margin-top: 20px;">
                    <li>Diabetic custom-made offload shoes</li>
                    <li>custom made offload Insole</li>
                    <li>custom made flat feet insole</li>
                    <li>Diabetic socks</li>
                </ul>
            </div>
            <div class="product-styles-panel">
                <h4>Styles Available</h4>
                <div class="product-styles-grid">
                    <div class="product-style-card hover-lift">
                        <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&q=80&w=500" alt="Walking Shoes" loading="lazy">
                    </div>
                    <div class="product-style-card hover-lift">
                        <img src="https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?auto=format&fit=crop&q=80&w=500" alt="Dress Shoes" loading="lazy">
                    </div>
                    <div class="product-style-card hover-lift">
                        <img src="https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&q=80&w=500" alt="Sandals" loading="lazy">
                    </div>
                    <div class="product-style-card hover-lift">
                        <img src="https://images.unsplash.com/photo-1608256246200-53e635b5b65f?auto=format&fit=crop&q=80&w=500" alt="Boots" loading="lazy">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="technology" class="scanning-section reveal">
        <div class="container scanning-grid">
            <div class="hero-visual">
                <img src="assets/images/foot-scanning-technology.png" alt="Advanced 3D foot scanning pressure map" class="scanning-image" loading="lazy">
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
                    <img src="<?= $problem[2] ?>" alt="<?= e($problem[0]) ?>" loading="lazy">
                    <h3><?= e($problem[0]) ?></h3>
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
                        <input type="email" name="email" required placeholder="john@example.com">
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

    <?php if (!empty($latest_posts)): ?>
    <section id="blog-preview" class="container reveal blog-preview-section">
        <div class="section-heading-row">
            <div>
                <h2 class="section-title">Latest Foot Care Guides</h2>
                <p class="section-subtitle">SEO-focused guides for diabetic shoes, custom insoles, flat feet support, and safer daily walking.</p>
            </div>
            <a href="blog.php" class="read-more">View All Articles</a>
        </div>
        <div class="swiper blog-preview-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($latest_posts as $post): ?>
                    <article class="swiper-slide blog-card">
                        <?php if (!empty($post['featured_image'])): ?>
                            <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" loading="lazy">
                        <?php endif; ?>
                        <div class="blog-card-body">
                            <span class="blog-date"><?= e(date('M j, Y', strtotime($post['published_at'] ?: $post['created_at']))) ?></span>
                            <h2><a href="blog-post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h2>
                            <p><?= e($post['excerpt']) ?></p>
                            <a class="read-more" href="blog-post.php?slug=<?= e($post['slug']) ?>">Read More</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination blog-preview-pagination"></div>
        </div>
    </section>
    <?php endif; ?>

    <section id="faq" class="container reveal faq-section">
        <h2 class="section-title">Q & A</h2>
        <p class="section-subtitle">Find quick answers about appointments, visits, payment, and custom footwear care.</p>
        <div class="faq-shell" data-faq>
            <div class="faq-tools">
                <label class="faq-search" for="faq-search">
                    <span aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
                    </span>
                    <input id="faq-search" type="search" placeholder="Search questions" autocomplete="off" data-faq-search>
                </label>
                <div class="faq-actions">
                    <button type="button" class="faq-tool-btn" data-faq-expand>Expand all</button>
                    <button type="button" class="faq-tool-btn" data-faq-collapse>Collapse all</button>
                </div>
            </div>
            <div class="faq-filters" aria-label="FAQ filters">
                <button type="button" class="faq-filter active" data-faq-filter="all">All</button>
                <button type="button" class="faq-filter" data-faq-filter="appointment">Appointments</button>
                <button type="button" class="faq-filter" data-faq-filter="product">Products</button>
                <button type="button" class="faq-filter" data-faq-filter="payment">Payment</button>
                <button type="button" class="faq-filter" data-faq-filter="visit">Visits</button>
            </div>
            <div class="faq-grid">
                <?php foreach ($faqs as $index => $faq): ?>
                    <?php
                        $faqText = strtolower($faq[0] . ' ' . $faq[1]);
                        $category = 'product';
                        if (strpos($faqText, 'parking') !== false || strpos($faqText, 'ground floor') !== false || strpos($faqText, 'home visit') !== false || strpos($faqText, 'malaysia') !== false || strpos($faqText, 'travel') !== false) {
                            $category = 'visit';
                        } elseif (strpos($faqText, 'appointment') !== false || strpos($faqText, 'consultation') !== false || strpos($faqText, 'sunday') !== false) {
                            $category = 'appointment';
                        } elseif (strpos($faqText, 'payment') !== false || strpos($faqText, 'deposit') !== false || strpos($faqText, 'card') !== false || strpos($faqText, 'transfer') !== false) {
                            $category = 'payment';
                        }
                    ?>
                    <article class="faq-item<?= $index === 0 ? ' active' : '' ?>" data-faq-item data-category="<?= e($category) ?>">
                        <h3>
                            <button
                                type="button"
                                class="faq-question"
                                id="faq-question-<?= $index ?>"
                                aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                                aria-controls="faq-answer-<?= $index ?>"
                            >
                                <span><?= e($faq[0]) ?></span>
                                <span class="faq-toggle" aria-hidden="true"></span>
                            </button>
                        </h3>
                        <div
                            class="faq-answer"
                            id="faq-answer-<?= $index ?>"
                            role="region"
                            aria-labelledby="faq-question-<?= $index ?>"
                            <?= $index === 0 ? '' : 'hidden' ?>
                        >
                            <p><?= e($faq[1]) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
                <p class="faq-empty" data-faq-empty hidden>No matching questions yet. Try another keyword or category.</p>
            </div>
        </div>
    </section>

    <section id="location" class="container reveal">
        <h2 class="section-title">Visit Our Specialist Center</h2>
        <p class="section-subtitle">Located in the heart of Sentul, our facility is equipped with the latest diagnostic technology.</p>
        <div class="grid-3" style="grid-template-columns: 1fr 2fr; gap: 40px; align-items: center;">
            <div class="contact-details-card">
                <div class="card" style="padding: 30px; border-radius: var(--radius-lg);">
                    <img src="assets/images/flexi-feet-logo.png" alt="Flexi Feet Sdn Bhd" class="contact-card-logo">
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
                        <div class="social-icons" aria-label="Social links">
                            <a href="https://www.instagram.com/flexifeetmalaysia/" target="_blank" rel="noopener" class="social-icon" aria-label="Instagram">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/></svg>
                            </a>
                            <a href="https://www.facebook.com/flexifeetmalaysia" target="_blank" rel="noopener" class="social-icon" aria-label="Facebook">
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 8.5V6.75c0-.7.24-1.05 1.16-1.05H17V2.25A24 24 0 0 0 14.32 2C11.67 2 9.86 3.62 9.86 6.59V8.5H7v3.86h2.86V22h4.17v-9.64h2.84l.45-3.86H14z"/></svg>
                            </a>
                            <a href="https://www.youtube.com/@flexifeetmalaysia" target="_blank" rel="noopener" class="social-icon" aria-label="YouTube">
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23 7.1a3.02 3.02 0 0 0-2.13-2.14C18.99 4.45 12 4.45 12 4.45s-6.99 0-8.87.51A3.02 3.02 0 0 0 1 7.1 31.5 31.5 0 0 0 .5 12 31.5 31.5 0 0 0 1 16.9a3.02 3.02 0 0 0 2.13 2.14c1.88.51 8.87.51 8.87.51s6.99 0 8.87-.51A3.02 3.02 0 0 0 23 16.9a31.5 31.5 0 0 0 .5-4.9A31.5 31.5 0 0 0 23 7.1zM9.75 15.4V8.6L15.7 12l-5.95 3.4z"/></svg>
                            </a>
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
</main>

<footer>
    <div class="container">
        <div class="compact-footer">
            <div class="footer-legal">
                <p><a href="#">Privacy Policy</a> <a href="#">Terms of Service</a></p>
                <p>&copy; <?= date('Y') ?> Flexi Feet Sdn Bhd. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<div class="scroll-top" id="scroll-top">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
</div>

<section class="support-bot" data-support-bot aria-label="Flexi Feet support bot">
    <button class="support-bot-toggle" type="button" data-support-toggle aria-expanded="false">
        <span>Support</span>
    </button>
    <div class="support-bot-panel" data-support-panel hidden>
        <div class="support-bot-header">
            <div>
                <strong>Flexi Feet Support Agent</strong>
                <span>Grounded in Flexi Feet services, blogs, bookings, and issue tickets.</span>
            </div>
            <button type="button" data-support-toggle aria-label="Close support">×</button>
        </div>
        <div class="support-bot-messages" data-support-messages>
            <div class="bot-message">Ask about custom diabetic shoes, offload insoles, foot scanning, diabetic socks, appointment booking, or report a website/service issue.</div>
        </div>
        <div class="support-bot-actions">
            <button type="button" data-support-mode="booking">Book Fitting</button>
            <button type="button" data-support-mode="ticket">Create Ticket</button>
        </div>
        <form class="support-detail-form" data-support-detail hidden>
            <strong data-support-detail-title>Details</strong>
            <input type="hidden" name="action" value="">
            <input type="text" name="name" placeholder="Name">
            <input type="email" name="email" placeholder="Email">
            <input type="tel" name="phone" placeholder="Phone">
            <input type="date" name="preferred_date" data-booking-field>
            <input type="time" name="preferred_time" data-booking-field>
            <select name="visit_type" data-booking-field>
                <option>Foot Assessment</option>
                <option>Custom Shoes / Footwear Fitting</option>
                <option>Customised Insole Assessment</option>
                <option>Pressure Sensor Scan</option>
                <option>Follow-up</option>
            </select>
            <input type="text" name="subject" placeholder="Issue subject" data-ticket-field>
            <textarea name="message" rows="3" placeholder="Message"></textarea>
            <button type="submit">Submit</button>
        </form>
        <form class="support-bot-form" data-support-form>
            <textarea name="message" rows="2" placeholder="Ask about services, blogs, booking, or report an issue" required></textarea>
            <button type="submit">Send</button>
        </form>
    </div>
</section>

<a class="whatsapp-chat" href="https://wa.me/60166055477?text=inquiry%20from%20flexifeet.new%20web%20portal" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M16.04 3C8.87 3 3.04 8.8 3.04 15.92c0 2.28.6 4.51 1.74 6.47L3 29l6.79-1.76a13.05 13.05 0 0 0 6.25 1.59c7.17 0 13-5.8 13-12.92S23.21 3 16.04 3zm0 23.64c-1.93 0-3.82-.52-5.48-1.5l-.39-.23-4.03 1.04 1.08-3.91-.25-.4a10.61 10.61 0 0 1-1.63-5.72c0-5.91 4.8-10.73 10.7-10.73s10.7 4.82 10.7 10.73-4.8 10.72-10.7 10.72zm5.87-8.03c-.32-.16-1.9-.94-2.2-1.05-.3-.1-.51-.16-.73.16-.21.32-.84 1.05-1.03 1.27-.19.21-.38.24-.7.08-.32-.16-1.36-.5-2.59-1.6a9.7 9.7 0 0 1-1.79-2.22c-.19-.32-.02-.5.14-.66.15-.15.32-.38.49-.57.16-.19.21-.32.32-.54.1-.21.05-.4-.03-.56-.08-.16-.73-1.75-1-2.4-.26-.63-.53-.54-.73-.55h-.62c-.21 0-.56.08-.86.4-.3.32-1.13 1.1-1.13 2.67s1.16 3.1 1.32 3.31c.16.21 2.29 3.48 5.54 4.88.77.33 1.38.53 1.85.68.78.25 1.48.21 2.04.13.62-.09 1.9-.78 2.17-1.53.27-.75.27-1.4.19-1.53-.08-.13-.29-.21-.61-.37z"/></svg>
</a>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="assets/app.js"></script>
</body>
</html>
