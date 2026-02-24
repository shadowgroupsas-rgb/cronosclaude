/**
 * La Chingada Restaurant — JavaScript Principal
 * Vanilla JS + Alpine.js para interactividad
 */

'use strict';

// ── Scroll animations (Intersection Observer) ──────────────────────────────
(function initScrollAnimations() {
    const elements = document.querySelectorAll('.fade-in, .fade-in-left, .fade-in-right');

    if (!elements.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target); // Solo animar una vez
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px'
    });

    elements.forEach(el => observer.observe(el));
})();

// ── Navbar scroll behavior ─────────────────────────────────────────────────
(function initNavbar() {
    const navbar = document.getElementById('navbar');
    if (!navbar) return;

    let lastScroll = 0;

    function updateNavbar() {
        const scrollY = window.scrollY;

        if (scrollY > 80) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }

        // Ocultar navbar al bajar, mostrar al subir
        if (scrollY > lastScroll && scrollY > 300) {
            navbar.style.transform = 'translateY(-100%)';
        } else {
            navbar.style.transform = 'translateY(0)';
        }

        lastScroll = scrollY;
    }

    window.addEventListener('scroll', updateNavbar, { passive: true });
    updateNavbar(); // Estado inicial
})();

// ── Menú interactivo (filtros + búsqueda + modal) ─────────────────────────
(function initMenuPage() {
    const menuContainer = document.getElementById('menu-items-container');
    if (!menuContainer) return;

    const filterBtns  = document.querySelectorAll('.filter-btn');
    const searchInput = document.getElementById('menu-search');
    const menuCards   = document.querySelectorAll('.menu-card[data-category]');
    const noResults   = document.getElementById('no-results');
    const categoryTitles = document.querySelectorAll('.menu-category-group');

    let activeFilter = 'all';
    let searchQuery  = '';

    // Filtrar platos
    function filterItems() {
        let visible = 0;

        menuCards.forEach(card => {
            const category = card.dataset.category || '';
            const name = (card.dataset.name || '').toLowerCase();
            const desc = (card.dataset.desc || '').toLowerCase();

            const matchesFilter = activeFilter === 'all' || category === activeFilter;
            const matchesSearch = !searchQuery ||
                name.includes(searchQuery) ||
                desc.includes(searchQuery);

            if (matchesFilter && matchesSearch) {
                card.style.display = '';
                card.style.animation = 'fadeInCard 0.3s ease forwards';
                visible++;
            } else {
                card.style.display = 'none';
            }
        });

        // Mostrar/ocultar títulos de categoría
        categoryTitles.forEach(group => {
            const groupCat = group.dataset.category || '';
            const hasVisible = group.querySelectorAll('.menu-card:not([style*="display: none"])').length > 0;
            const matchesFilter = activeFilter === 'all' || groupCat === activeFilter;
            group.style.display = matchesFilter && hasVisible ? '' : 'none';
        });

        // Mensaje sin resultados
        if (noResults) {
            noResults.style.display = visible === 0 ? 'block' : 'none';
        }
    }

    // Botones de filtro
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeFilter = btn.dataset.filter || 'all';
            filterItems();
        });
    });

    // Buscador en tiempo real
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchQuery = searchInput.value.toLowerCase().trim();
                filterItems();
            }, 250);
        });
    }

    // Modal de detalle
    const modal = document.getElementById('menu-modal');
    const modalClose = document.getElementById('modal-close');

    if (modal) {
        // Abrir modal al hacer clic en una card
        menuContainer.addEventListener('click', (e) => {
            const card = e.target.closest('.menu-card[data-id]');
            if (!card) return;

            const id      = card.dataset.id;
            const name    = card.dataset.name || '';
            const desc    = card.dataset.desc || '';
            const descLong= card.dataset.descLong || desc;
            const price   = card.dataset.price || '';
            const priceOld= card.dataset.priceOld || '';
            const image   = card.dataset.image || '';
            const badges  = (card.dataset.badges || '').split(',').filter(Boolean);
            const category= card.dataset.categoryName || '';

            // Llenar modal
            setModalContent({ id, name, desc: descLong, price, priceOld, image, badges, category });
            openModal(modal);
        });

        // Cerrar modal
        if (modalClose) {
            modalClose.addEventListener('click', () => closeModal(modal));
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeModal(modal);
            }
        });
    }

    function openModal(modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    function setModalContent({ name, desc, price, priceOld, image, badges, category }) {
        const modalImg     = document.getElementById('modal-img');
        const modalName    = document.getElementById('modal-name');
        const modalCat     = document.getElementById('modal-category');
        const modalDesc    = document.getElementById('modal-desc');
        const modalPrice   = document.getElementById('modal-price');
        const modalPriceOld= document.getElementById('modal-price-old');
        const modalBadges  = document.getElementById('modal-badges');

        if (modalImg)   { modalImg.src = image; modalImg.alt = name; }
        if (modalName)  modalName.textContent = name;
        if (modalCat)   modalCat.textContent = category;
        if (modalDesc)  modalDesc.textContent = desc;
        if (modalPrice) modalPrice.textContent = price;

        if (modalPriceOld) {
            modalPriceOld.textContent = priceOld || '';
            modalPriceOld.style.display = priceOld ? '' : 'none';
        }

        if (modalBadges) {
            const badgeLabels = {
                'nuevo': ['Nuevo', 'badge-nuevo'],
                'popular': ['Popular', 'badge-popular'],
                'sin_gluten': ['Sin Gluten', 'badge-sin_gluten'],
                'vegetariano': ['Vegetariano', 'badge-vegetariano'],
                'picante': ['Picante', 'badge-picante'],
            };

            modalBadges.innerHTML = badges.map(b => {
                const [label, cls] = badgeLabels[b] || [b, ''];
                return `<span class="badge ${cls}">${label}</span>`;
            }).join('');
        }
    }
})();

// ── Formulario de reservas ─────────────────────────────────────────────────
(function initReservasForm() {
    const form = document.getElementById('reserva-form');
    if (!form) return;

    const submitBtn = form.querySelector('[type="submit"]');
    const successMsg = document.getElementById('reserva-success');

    // Validación en tiempo real
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        field.addEventListener('blur', () => validateField(field));
        field.addEventListener('input', () => {
            if (field.classList.contains('error')) validateField(field);
        });
    });

    function validateField(field) {
        const errorEl = field.closest('.form-group')?.querySelector('.form-error');
        let valid = true;
        let message = '';

        if (!field.value.trim()) {
            valid = false;
            message = 'Este campo es requerido';
        } else if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
            valid = false;
            message = 'Ingresa un email válido';
        } else if (field.type === 'tel' && field.value.length < 7) {
            valid = false;
            message = 'Ingresa un teléfono válido';
        } else if (field.name === 'guests' && (parseInt(field.value) < 1 || parseInt(field.value) > 20)) {
            valid = false;
            message = 'Número de personas debe ser entre 1 y 20';
        }

        field.classList.toggle('error', !valid);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.toggle('visible', !valid);
        }

        return valid;
    }

    function validateForm() {
        let allValid = true;
        requiredFields.forEach(field => {
            if (!validateField(field)) allValid = false;
        });
        return allValid;
    }

    // Envío del formulario
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!validateForm()) return;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json();

            if (data.success) {
                form.style.display = 'none';
                if (successMsg) {
                    successMsg.classList.add('show');
                    successMsg.querySelector('.success-name').textContent =
                        formData.get('name') || 'amigo';
                }
            } else {
                showFormError(data.error || 'Ocurrió un error. Por favor intenta nuevamente.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirmar Reserva';
            }
        } catch (err) {
            showFormError('Error de conexión. Verifica tu internet e intenta nuevamente.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirmar Reserva';
        }
    });

    function showFormError(msg) {
        let errorDiv = form.querySelector('.form-submit-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-error form-submit-error';
            form.prepend(errorDiv);
        }
        errorDiv.textContent = msg;
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Calendario — limitar fechas (no se puede reservar en el pasado)
    const dateInput = document.getElementById('reserva-date');
    if (dateInput) {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        dateInput.min = `${yyyy}-${mm}-${dd}`;

        // Máximo 3 meses en el futuro
        const maxDate = new Date();
        maxDate.setMonth(maxDate.getMonth() + 3);
        const mYyyy = maxDate.getFullYear();
        const mMm = String(maxDate.getMonth() + 1).padStart(2, '0');
        const mDd = String(maxDate.getDate()).padStart(2, '0');
        dateInput.max = `${mYyyy}-${mMm}-${mDd}`;
    }
})();

// ── Formulario de contacto ─────────────────────────────────────────────────
(function initContactoForm() {
    const form = document.getElementById('contacto-form');
    if (!form) return;

    const submitBtn = form.querySelector('[type="submit"]');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json();

            // Limpiar alertas previas
            form.querySelectorAll('.alert').forEach(a => a.remove());

            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${data.success ? 'success' : 'error'}`;
            alertDiv.textContent = data.success
                ? '¡Mensaje enviado! Te responderemos a la brevedad.'
                : (data.error || 'Error al enviar. Intenta nuevamente.');
            form.prepend(alertDiv);

            if (data.success) {
                form.reset();
            }
        } catch {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-error';
            alertDiv.textContent = 'Error de conexión. Verifica tu internet.';
            form.prepend(alertDiv);
        }

        submitBtn.disabled = false;
        submitBtn.textContent = 'Enviar Mensaje';
    });
})();

// ── Galería lightbox ──────────────────────────────────────────────────────
(function initGalleryLightbox() {
    const galeria = document.querySelector('.galeria-grid');
    if (!galeria) return;

    const items = galeria.querySelectorAll('.galeria-item');
    if (!items.length) return;

    // Crear lightbox
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <button class="lightbox-close" aria-label="Cerrar">&times;</button>
        <button class="lightbox-prev" aria-label="Anterior">&#8592;</button>
        <button class="lightbox-next" aria-label="Siguiente">&#8594;</button>
        <div class="lightbox-content">
            <img src="" alt="" class="lightbox-img">
        </div>
    `;
    document.body.appendChild(lightbox);

    // CSS del lightbox (inyectar dinámicamente)
    const style = document.createElement('style');
    style.textContent = `
        .lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.92);
            z-index: 3000;
            align-items: center;
            justify-content: center;
        }
        .lightbox.active { display: flex; }
        .lightbox-content { max-width: 90vw; max-height: 85vh; }
        .lightbox-img { max-width: 100%; max-height: 85vh; object-fit: contain; border-radius: 8px; }
        .lightbox-close, .lightbox-prev, .lightbox-next {
            position: fixed;
            background: rgba(255,255,255,.15);
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
            backdrop-filter: blur(4px);
        }
        .lightbox-close { top: 20px; right: 20px; font-size: 1.8rem; }
        .lightbox-prev  { left: 20px; top: 50%; transform: translateY(-50%); }
        .lightbox-next  { right: 20px; top: 50%; transform: translateY(-50%); }
        .lightbox-close:hover, .lightbox-prev:hover, .lightbox-next:hover {
            background: rgba(255,255,255,.3);
        }
    `;
    document.head.appendChild(style);

    const images = Array.from(items).map(item => ({
        src: item.querySelector('img')?.src || '',
        alt: item.querySelector('img')?.alt || ''
    }));

    let currentIndex = 0;
    const lightboxImg = lightbox.querySelector('.lightbox-img');

    function showImage(index) {
        currentIndex = (index + images.length) % images.length;
        lightboxImg.src = images[currentIndex].src;
        lightboxImg.alt = images[currentIndex].alt;
    }

    function openLightbox(index) {
        showImage(index);
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    items.forEach((item, i) => {
        item.addEventListener('click', () => openLightbox(i));
        item.style.cursor = 'pointer';
    });

    lightbox.querySelector('.lightbox-close').addEventListener('click', closeLightbox);
    lightbox.querySelector('.lightbox-prev').addEventListener('click', () => showImage(currentIndex - 1));
    lightbox.querySelector('.lightbox-next').addEventListener('click', () => showImage(currentIndex + 1));

    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox || e.target === lightbox.querySelector('.lightbox-content')) {
            closeLightbox();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (!lightbox.classList.contains('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') showImage(currentIndex - 1);
        if (e.key === 'ArrowRight') showImage(currentIndex + 1);
    });
})();

// ── Smooth scroll para links de anclaje ───────────────────────────────────
document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', (e) => {
        const id = link.getAttribute('href').slice(1);
        const target = document.getElementById(id);
        if (target) {
            e.preventDefault();
            const navbarHeight = parseInt(
                getComputedStyle(document.documentElement).getPropertyValue('--navbar-height')
            ) || 80;
            const top = target.getBoundingClientRect().top + window.scrollY - navbarHeight;
            window.scrollTo({ top, behavior: 'smooth' });
        }
    });
});

// Animación CSS para cards del menú
const menuCardStyle = document.createElement('style');
menuCardStyle.textContent = `
    @keyframes fadeInCard {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
`;
document.head.appendChild(menuCardStyle);
