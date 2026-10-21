<div id="global-image-lightbox"
     class="fixed inset-0 z-[99999] hidden items-center justify-center p-4 sm:p-6"
     role="dialog"
     aria-modal="true">
    {{-- Backdrop gelap blur --}}
    <div id="lightbox-backdrop"
         class="fixed inset-0 bg-black/90 backdrop-blur-md transition-opacity duration-300 opacity-0 cursor-zoom-out"></div>

    {{-- Container modal foto --}}
    <div id="lightbox-content"
         class="relative max-h-[92vh] max-w-[95vw] flex flex-col items-center z-10 transform transition-all duration-300 scale-95 opacity-0">
        {{-- Tombol Tutup --}}
        <button type="button"
                id="lightbox-close"
                class="absolute -top-12 right-0 text-white/80 hover:text-white bg-white/10 hover:bg-white/20 p-2.5 rounded-full backdrop-blur-md transition cursor-pointer"
                aria-label="Tutup">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Foto Full Layar --}}
        <img id="lightbox-image"
             src=""
             alt="Foto Full"
             class="max-h-[85vh] max-w-[95vw] w-auto h-auto object-contain rounded-xl shadow-2xl border border-white/10 select-none cursor-default" />

        {{-- Caption Judul --}}
        <p id="lightbox-caption" class="mt-3 text-sm text-slate-200 font-medium text-center truncate max-w-xl px-4"></p>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('global-image-lightbox');
        const backdrop = document.getElementById('lightbox-backdrop');
        const content = document.getElementById('lightbox-content');
        const img = document.getElementById('lightbox-image');
        const caption = document.getElementById('lightbox-caption');
        const closeBtn = document.getElementById('lightbox-close');

        function openLightbox(src, title) {
            if (!src) return;
            img.src = src;
            img.alt = title || 'Foto Kendaraan';
            caption.textContent = title || '';
            caption.style.display = title ? 'block' : 'none';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');

            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            });
        }

        function closeLightbox() {
            if (!backdrop || !content) return;
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                img.src = '';
                document.body.classList.remove('overflow-hidden');
            }, 200);
        }

        // Global functions & event listener
        window.openImagePreview = openLightbox;
        window.closeImagePreview = closeLightbox;

        window.addEventListener('open-image-preview', function (e) {
            const detail = e.detail || {};
            openLightbox(detail.src, detail.title);
        });

        if (backdrop) backdrop.addEventListener('click', closeLightbox);
        if (closeBtn) closeBtn.addEventListener('click', closeLightbox);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeLightbox();
            }
        });
    })();
</script>
