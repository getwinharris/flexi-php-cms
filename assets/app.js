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

    const burgerToggle = document.getElementById('burger-toggle');
    const nav = document.querySelector('nav');
    const setMobileMenu = (open) => {
        nav?.classList.toggle('active', open);
        burgerToggle?.classList.toggle('active', open);
        burgerToggle?.setAttribute('aria-expanded', String(open));
    };

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
                setMobileMenu(false);
            }
        });
    });

    // Mobile Nav Toggle
    if (burgerToggle) {
        burgerToggle.addEventListener('click', () => {
            setMobileMenu(!burgerToggle.classList.contains('active'));
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

    // Swiper: Product Styles
    if (document.querySelector('.product-styles-swiper')) {
        new Swiper('.product-styles-swiper', {
            loop: true,
            slidesPerView: 1.15,
            spaceBetween: 16,
            watchOverflow: true,
            autoplay: {
                delay: 2800,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },
            pagination: {
                el: '.product-pagination',
                clickable: true,
            },
            navigation: {
                prevEl: '.product-nav-prev',
                nextEl: '.product-nav-next',
            },
            breakpoints: {
                640: { slidesPerView: 2.2, spaceBetween: 18 },
                768: { slidesPerView: 3, spaceBetween: 22 },
                1024: { slidesPerView: 'auto', spaceBetween: 22 }
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
            setMobileMenu(false);
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

    const supportPanel = document.querySelector('[data-support-panel]');
    const supportMessages = document.querySelector('[data-support-messages]');
    const supportDetail = document.querySelector('[data-support-detail]');
    const supportDetailTitle = document.querySelector('[data-support-detail-title]');
    const supportAvailability = document.querySelector('[data-availability-status]');
    const supportDate = supportDetail?.querySelector('[name="preferred_date"]');
    const supportTime = supportDetail?.querySelector('[name="preferred_time"]');
    const formatSlots = (slots = []) => slots.map((slot) => typeof slot === 'string' ? slot : `${slot.date} ${slot.time}`).join(', ');
    const bookingConversation = {
        active: false,
        step: '',
        data: {},
        slots: [],
    };
    const bookingPrompts = {
        visit_type: 'What is the booking for? You can answer: Foot Assessment, Custom Shoes / Footwear Fitting, Customised Insole Assessment, Pressure Sensor Scan, or Follow-up.',
        name: 'What name should we put on the appointment request?',
        phone: 'What phone number should the team use to contact you?',
        email: 'What email address should we use for the confirmation?',
        preferred_date: 'What date would you prefer? Please use YYYY-MM-DD.',
        preferred_time: 'Which available time works for you?',
    };
    const nextBookingStep = () => ['visit_type', 'name', 'phone', 'email', 'preferred_date', 'preferred_time'].find((field) => !bookingConversation.data[field]) || '';
    const startBookingConversation = () => {
        bookingConversation.active = true;
        bookingConversation.data = {};
        bookingConversation.slots = [];
        bookingConversation.step = 'visit_type';
        supportDetail?.setAttribute('hidden', '');
    };
    const fetchAvailabilityForChat = async (date) => {
        const body = new FormData();
        body.append('action', 'availability');
        body.append('preferred_date', date);
        const response = await fetch('api/support-bot.php', { method: 'POST', body });
        return response.json();
    };
    const submitChatBooking = async () => {
        const body = new FormData();
        body.append('action', 'booking');
        Object.entries(bookingConversation.data).forEach(([key, value]) => body.append(key, value));
        body.append('notes', 'Booked through guided support chat.');
        const response = await fetch('api/support-bot.php', { method: 'POST', body });
        return response.json();
    };
    const handleBookingConversation = async (message) => {
        const step = bookingConversation.step || nextBookingStep();
        if (!step) return false;

        if (step === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(message)) {
            appendSupportMessage('Please send a valid email address so the confirmation can reach you.', 'bot');
            return true;
        }
        if (step === 'preferred_date' && !/^\d{4}-\d{2}-\d{2}$/.test(message)) {
            appendSupportMessage('Please send the date in YYYY-MM-DD format, for example 2026-07-10.', 'bot');
            return true;
        }
        if (step === 'preferred_time' && bookingConversation.slots.length && !bookingConversation.slots.includes(message)) {
            appendSupportMessage(`Please choose one of these available times: ${formatSlots(bookingConversation.slots)}`, 'bot');
            return true;
        }

        bookingConversation.data[step] = message;

        if (step === 'preferred_date') {
            const availability = await fetchAvailabilityForChat(message);
            if (!availability.open || !Array.isArray(availability.available_slots) || !availability.available_slots.length) {
                delete bookingConversation.data.preferred_date;
                appendSupportMessage('No available slots for that date. Please send another working day in YYYY-MM-DD format.', 'bot');
                return true;
            }
            bookingConversation.slots = availability.available_slots;
            bookingConversation.step = 'preferred_time';
            appendSupportMessage(`Available times are: ${formatSlots(availability.recommended || availability.available_slots.slice(0, 5))}. Which time should I use?`, 'bot');
            return true;
        }

        const next = nextBookingStep();
        if (next) {
            bookingConversation.step = next;
            appendSupportMessage(bookingPrompts[next], 'bot');
            return true;
        }

        const result = await submitChatBooking();
        appendSupportMessage(result.ok ? `${result.message} ${result.appointment_id || ''}` : result.message, 'bot');
        if (result.ok) {
            bookingConversation.active = false;
            bookingConversation.step = '';
            bookingConversation.data = {};
            bookingConversation.slots = [];
        } else {
            if (result.available_slots || result.recommended) {
                delete bookingConversation.data.preferred_time;
                bookingConversation.slots = result.available_slots || (result.recommended || []).map((slot) => slot.time || slot);
                bookingConversation.step = 'preferred_time';
            } else {
                bookingConversation.step = nextBookingStep();
            }
        }
        return true;
    };
    const setSupportAvailability = (message, slots = []) => {
        if (!supportAvailability) return;
        supportAvailability.hidden = false;
        supportAvailability.textContent = slots.length ? `${message} ${formatSlots(slots)}` : message;
    };
    const loadSupportAvailability = async () => {
        if (!supportDate || !supportTime || !supportDate.value) return;
        const body = new FormData();
        body.append('action', 'availability');
        body.append('preferred_date', supportDate.value);
        const response = await fetch('api/support-bot.php', { method: 'POST', body });
        const result = await response.json();
        supportTime.innerHTML = '<option value="">Choose an available time</option>';
        if (result.open && Array.isArray(result.available_slots) && result.available_slots.length) {
            result.available_slots.forEach((slot) => {
                const option = document.createElement('option');
                option.value = slot;
                option.textContent = slot;
                supportTime.appendChild(option);
            });
            setSupportAvailability('Recommended available times:', result.recommended || result.available_slots.slice(0, 5));
        } else {
            setSupportAvailability('No available slots for this date. Try another working day.');
        }
    };
    const setSupportMode = (mode) => {
        if (!supportDetail) return;
        supportDetail.removeAttribute('hidden');
        supportDetail.querySelector('[name="action"]').value = mode;
        supportDetail.querySelectorAll('[data-booking-field]').forEach((field) => field.hidden = mode !== 'booking');
        supportDetail.querySelectorAll('[data-ticket-field]').forEach((field) => field.hidden = mode !== 'ticket');
        if (supportAvailability) supportAvailability.hidden = mode !== 'booking';
        if (supportDetailTitle) {
            supportDetailTitle.textContent = mode === 'booking' ? 'What would you like to book?' : 'Support ticket details';
        }
        if (mode === 'booking') {
            bookingConversation.active = false;
            appendSupportMessage('What would you like to book for? Choose a service, then add your name, phone, email, preferred date, and one of the recommended available times.', 'bot');
            loadSupportAvailability();
        }
    };
    const sendSupportFeedback = async (button, rating) => {
        const feedback = button.closest('[data-support-feedback]');
        if (!feedback || feedback.dataset.sent === 'true') return;
        feedback.dataset.sent = 'true';
        feedback.querySelectorAll('button').forEach((item) => {
            item.disabled = true;
            item.classList.toggle('active', item === button);
        });
        const body = new FormData();
        body.append('action', 'feedback');
        body.append('rating', rating);
        body.append('response_id', feedback.dataset.responseId || '');
        body.append('intent', feedback.dataset.intent || '');
        body.append('language', feedback.dataset.language || 'en');
        body.append('message', feedback.dataset.message || '');
        try {
            await fetch('api/support-bot.php', { method: 'POST', body });
            const status = feedback.querySelector('[data-feedback-status]');
            if (status) status.textContent = 'Saved';
        } catch (error) {
            const status = feedback.querySelector('[data-feedback-status]');
            if (status) status.textContent = 'Not saved';
        }
    };
    const appendSupportMessage = (text, type = 'bot', suggestions = [], meta = {}) => {
        if (!supportMessages) return;
        const item = document.createElement('div');
        item.className = `${type}-message`;
        item.textContent = text;
        supportMessages.appendChild(item);
        suggestions.forEach((suggestion) => {
            const link = document.createElement('a');
            link.className = 'bot-suggestion';
            link.href = suggestion.url;
            link.textContent = suggestion.title;
            supportMessages.appendChild(link);
        });
        if (type === 'bot' && meta.response_id) {
            const feedback = document.createElement('div');
            feedback.className = 'support-feedback';
            feedback.dataset.supportFeedback = '';
            feedback.dataset.responseId = meta.response_id;
            feedback.dataset.intent = meta.intent || '';
            feedback.dataset.language = meta.language || 'en';
            feedback.dataset.message = meta.message || '';
            feedback.innerHTML = `
                <span>Was this helpful?</span>
                <button type="button" data-feedback-rating="like" aria-label="Like this response">+</button>
                <button type="button" data-feedback-rating="dislike" aria-label="Dislike this response">-</button>
                <small data-feedback-status></small>
            `;
            feedback.querySelectorAll('button').forEach((button) => {
                button.addEventListener('click', () => sendSupportFeedback(button, button.dataset.feedbackRating));
            });
            supportMessages.appendChild(feedback);
        }
        supportMessages.scrollTop = supportMessages.scrollHeight;
    };

    document.querySelectorAll('[data-support-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const isHidden = supportPanel?.hasAttribute('hidden');
            if (isHidden) supportPanel?.removeAttribute('hidden');
            else supportPanel?.setAttribute('hidden', '');
            document.querySelector('[data-support-toggle]')?.setAttribute('aria-expanded', String(isHidden));
        });
    });

    document.querySelector('[data-support-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const message = form.message.value.trim();
        if (!message) return;
        appendSupportMessage(message, 'user');
        form.reset();
        if (bookingConversation.active) {
            await handleBookingConversation(message);
            return;
        }
        const body = new FormData();
        body.append('action', 'message');
        body.append('message', message);
        const response = await fetch('api/support-bot.php', { method: 'POST', body });
        const result = await response.json();
        appendSupportMessage(result.reply || result.message, 'bot', result.suggestions || [], {
            response_id: result.response_id,
            intent: result.intent,
            language: result.language,
            message,
        });
        if (result.intent === 'booking') startBookingConversation();
        if (result.intent === 'ticket') setSupportMode('ticket');
    });

    supportDate?.addEventListener('change', loadSupportAvailability);

    document.querySelectorAll('[data-support-mode]').forEach((button) => {
        button.addEventListener('click', () => {
            setSupportMode(button.dataset.supportMode);
        });
    });

    supportDetail?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const body = new FormData(form);
        const response = await fetch('api/support-bot.php', { method: 'POST', body });
        const result = await response.json();
        if (!result.ok && (result.available_slots || result.recommended)) {
            setSupportAvailability(result.message, result.available_slots || result.recommended);
        }
        appendSupportMessage(result.ok ? `${result.message} ${result.appointment_id || result.ticket_id || ''}` : result.message, result.ok ? 'bot' : 'bot');
        if (result.ok) {
            form.reset();
            form.setAttribute('hidden', '');
            if (supportTime) supportTime.innerHTML = '<option value="">Choose an available time</option>';
        }
    });
});
