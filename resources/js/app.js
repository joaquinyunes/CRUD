import Alpine from 'alpinejs';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

window.Alpine = Alpine;
Alpine.start();

gsap.registerPlugin(ScrollTrigger);
window.gsap = gsap;
window.ScrollTrigger = ScrollTrigger;

document.addEventListener('DOMContentLoaded', () => {
    // Entrada de página (no bloqueante: el body ya es visible por defecto)
    gsap.fromTo('body', { opacity: 0 }, { opacity: 1, duration: 0.4, ease: 'power2.out' });

    // Reveal para elementos [data-reveal]
    document.querySelectorAll('[data-reveal]').forEach((el) => {
        const type = el.getAttribute('data-reveal') || 'fade-up';
        const delay = parseFloat(el.dataset.revealDelay || 0);

        let from = { opacity: 0, y: 24 };
        if (type === 'fade-left') from = { opacity: 0, x: -24 };
        if (type === 'fade-right') from = { opacity: 0, x: 24 };
        if (type === 'scale') from = { opacity: 0, scale: 0.95 };

        ScrollTrigger.create({
            trigger: el,
            start: 'top 92%',
            once: true,
            onEnter: () => {
                gsap.fromTo(el, from, {
                    opacity: 1, x: 0, y: 0, scale: 1,
                    duration: 0.6,
                    delay,
                    ease: 'power3.out',
                    onComplete: () => el.classList.add('revealed'),
                });
            },
        });
    });

    // Divider animado
    document.querySelectorAll('.r-divider').forEach((el) => {
        ScrollTrigger.create({
            trigger: el,
            start: 'top 85%',
            once: true,
            onEnter: () => el.classList.add('visible'),
        });
    });

    // Fallback: si algo falla, nunca dejar contenido oculto
    setTimeout(() => {
        document.querySelectorAll('[data-reveal]:not(.revealed)').forEach((el) => {
            el.style.opacity = '1';
            el.style.transform = 'none';
            el.classList.add('revealed');
        });
    }, 1800);
});

/* =========================================================
   Sistema de notificaciones (toasts)
   Uso: window.RhythmToast.success('Guardado')
        window.RhythmToast.error('Error')
   También escucha los flash de sesión inyectados.
   ========================================================= */
window.RhythmToast = (() => {
    let container;

    function ensureContainer() {
        if (container) return container;
        container = document.createElement('div');
        container.id = 'rhythm-toast-container';
        container.style.cssText =
            'position:fixed;top:20px;right:20px;z-index:9999;display:flex;' +
            'flex-direction:column;gap:12px;pointer-events:none;max-width:360px;';
        document.body.appendChild(container);
        return container;
    }

    function show(message, type = 'success') {
        const c = ensureContainer();
        const colors = {
            success: { bg: '#DCE3D3', fg: '#3f5135', icon: 'M5 13l4 4L19 7' },
            error: { bg: '#fbe3e3', fg: '#b42323', icon: 'M6 18L18 6M6 6l12 12' },
            info: { bg: '#efe7da', fg: '#1C2422', icon: 'M12 8v4m0 4h.01' },
        };
        const cfg = colors[type] || colors.info;

        const el = document.createElement('div');
        el.style.cssText =
            `pointer-events:auto;display:flex;align-items:center;gap:10px;` +
            `background:${cfg.bg};color:${cfg.fg};border-radius:14px;padding:12px 16px;` +
            `font-family:var(--font-body,Georgia,serif);font-size:0.9rem;font-weight:500;` +
            `box-shadow:0 12px 40px rgba(28,36,34,0.12);border:1px solid rgba(28,36,34,0.06);` +
            `transform:translateX(120%);opacity:0;transition:transform .45s cubic-bezier(.22,1,.36,1),opacity .45s;`;
        el.innerHTML =
            `<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0">` +
            `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="${cfg.icon}"/></svg>` +
            `<span>${message}</span>`;
        c.appendChild(el);

        requestAnimationFrame(() => {
            el.style.transform = 'translateX(0)';
            el.style.opacity = '1';
        });

        setTimeout(() => {
            el.style.transform = 'translateX(120%)';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 3800);
    }

    return { success: (m) => show(m, 'success'), error: (m) => show(m, 'error'), info: (m) => show(m, 'info') };
})();

// Mostrar toasts desde flash de sesión inyectados en el layout
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-flash]').forEach((el) => {
        const type = el.dataset.flash;
        const msg = el.getAttribute('data-message') || el.textContent.trim();
        if (msg) window.RhythmToast[type === 'error' ? 'error' : 'success'](msg);
        el.remove();
    });
});
