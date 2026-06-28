document.addEventListener('DOMContentLoaded', () => {
    // Fail-safe: only enable hidden states if JS loads
    document.body.classList.add('js-enabled');
    // Reveal animations on scroll
    const revealElements = document.querySelectorAll('.reveal');
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
            }
        });
    }, { threshold: 0.1 });

    revealElements.forEach(el => revealObserver.observe(el));

    // Smooth scroll for nav links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({
                    top: target.offsetTop - 80,
                    behavior: 'smooth'
                });
                
                // Close mobile nav if open
                document.querySelector('nav')?.classList.remove('active');
                document.getElementById('burger-toggle')?.classList.remove('active');
            }
        });
    });

    // Mobile Nav Toggle
    const burgerToggle = document.getElementById('burger-toggle');
    const nav = document.querySelector('nav');
    if (burgerToggle) {
        burgerToggle.addEventListener('click', () => {
            nav?.classList.toggle('active');
            burgerToggle.classList.toggle('active');
        });
    }

    // Scroll Top Visibility
    const scrollTop = document.getElementById('scroll-top');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 500) {
            scrollTop?.classList.add('active');
        } else {
            scrollTop?.classList.remove('active');
        }
    });

    scrollTop?.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Swiper: Hero Banner
    if (document.querySelector('.hero-swiper')) {
        new Swiper('.hero-swiper', {
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.hero-pagination',
                clickable: true,
            },
        });
    }

    if (document.querySelector('.blog-preview-swiper')) {
        new Swiper('.blog-preview-swiper', {
            slidesPerView: 1.1,
            spaceBetween: 18,
            watchOverflow: true,
            pagination: {
                el: '.blog-preview-pagination',
                clickable: true,
            },
            breakpoints: {
                700: { slidesPerView: 2, spaceBetween: 22 },
                1024: { slidesPerView: 3, spaceBetween: 26 }
            }
        });
    }

    // Swiper: Shorts (Reels Style)
    if (document.querySelector('.shorts-swiper')) {
        const shortsSwiper = new Swiper('.shorts-swiper', {
            loop: true,
            centeredSlides: true,
            slidesPerView: 1.15,
            spaceBetween: 14,
            watchOverflow: true,
            autoplay: {
                delay: 3200,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },
            pagination: {
                el: '.shorts-pagination',
                clickable: true,
            },
            navigation: {
                prevEl: '.shorts-nav-prev',
                nextEl: '.shorts-nav-next',
            },
            breakpoints: {
                640: { slidesPerView: 1.8, spaceBetween: 18 },
                768: { slidesPerView: 2.35, spaceBetween: 24 },
                1024: { slidesPerView: 'auto', spaceBetween: 30 }
            },
        });
    }

    // FAQ accordion, search, and filters
    const faqRoot = document.querySelector('[data-faq]');
    if (faqRoot) {
        const faqItems = Array.from(faqRoot.querySelectorAll('[data-faq-item]'));
        const faqSearch = faqRoot.querySelector('[data-faq-search]');
        const faqEmpty = faqRoot.querySelector('[data-faq-empty]');
        const faqFilters = Array.from(faqRoot.querySelectorAll('[data-faq-filter]'));
        let activeFilter = 'all';

        const setFaqItem = (item, shouldOpen) => {
            const answer = item.querySelector('.faq-answer');
            const question = item.querySelector('.faq-question');

            item.classList.toggle('active', shouldOpen);
            question?.setAttribute('aria-expanded', String(shouldOpen));

            if (answer) {
                answer.hidden = !shouldOpen;
            }
        };

        const applyFaqFilters = () => {
            const query = faqSearch?.value.trim().toLowerCase() || '';
            let visibleCount = 0;

            faqItems.forEach(item => {
                const itemText = item.textContent.toLowerCase();
                const categoryMatches = activeFilter === 'all' || item.dataset.category === activeFilter;
                const searchMatches = !query || itemText.includes(query);
                const isVisible = categoryMatches && searchMatches;

                item.hidden = !isVisible;
                if (isVisible) {
                    visibleCount += 1;
                } else {
                    setFaqItem(item, false);
                }
            });

            if (faqEmpty) {
                faqEmpty.hidden = visibleCount > 0;
            }
        };

        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            question?.addEventListener('click', () => {
                const shouldOpen = !item.classList.contains('active');
                setFaqItem(item, shouldOpen);
            });
        });

        faqSearch?.addEventListener('input', applyFaqFilters);

        faqFilters.forEach(filter => {
            filter.addEventListener('click', () => {
                activeFilter = filter.dataset.faqFilter || 'all';
                faqFilters.forEach(item => item.classList.toggle('active', item === filter));
                applyFaqFilters();
            });
        });

        faqRoot.querySelector('[data-faq-expand]')?.addEventListener('click', () => {
            faqItems.filter(item => !item.hidden).forEach(item => setFaqItem(item, true));
        });

        faqRoot.querySelector('[data-faq-collapse]')?.addEventListener('click', () => {
            faqItems.forEach(item => setFaqItem(item, false));
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            nav?.classList.remove('active');
            burgerToggle?.classList.remove('active');
        }
    });

    // Advanced Booking Validation
    const bookingDate = document.getElementById('booking-date');
    const bookingTime = document.getElementById('booking-time');

    const validateBooking = () => {
        const date = new Date(bookingDate.value);
        const day = date.getUTCDay(); // 0 = Sunday, 6 = Saturday
        const time = bookingTime.value;

        if (day === 0) {
            alert('Sorry, we are closed on Sundays. Please select another day.');
            bookingDate.value = '';
            return false;
        }

        if (day === 6) { // Saturday
            if (time && (time < '09:00' || time > '13:00')) {
                alert('On Saturdays, we are only open from 9:00 AM to 1:00 PM.');
                bookingTime.value = '';
                return false;
            }
        } else { // Mon-Fri
            if (time && (time < '09:00' || time > '18:00')) {
                alert('Our working hours are from 9:00 AM to 6:00 PM.');
                bookingTime.value = '';
                return false;
            }
        }
        return true;
    };

    bookingDate?.addEventListener('change', validateBooking);
    bookingTime?.addEventListener('change', validateBooking);

    // Booking form handling
    const bookingForm = document.querySelector('[data-booking-form]');
    if (bookingForm) {
        bookingForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!validateBooking()) return;

            const submitBtn = bookingForm.querySelector('.submit-btn');
            const messageEl = document.getElementById('form-message');
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            messageEl.textContent = '';
            
            try {
                const formData = new FormData(bookingForm);
                const response = await fetch(bookingForm.action, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.ok) {
                    messageEl.style.color = '#34c759';
                    messageEl.textContent = 'Appointment request sent successfully! We will contact you soon.';
                    bookingForm.reset();
                } else {
                    throw new Error(result.message || 'Something went wrong.');
                }
            } catch (error) {
                messageEl.style.color = '#ff3b30';
                messageEl.textContent = error.message;
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Request Appointment';
            }
        });
    }

    // Header transparency on scroll
    const header = document.querySelector('header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.1)';
        } else {
            header.style.boxShadow = 'none';
        }
    });
});
