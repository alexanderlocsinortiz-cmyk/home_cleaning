<div id="service-confirm-modal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-950/55 px-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="service-confirm-title">
    <div class="w-full max-w-md rounded-[24px] border border-white/70 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.28)]">
        <div class="flex items-start gap-4">
            <div id="service-confirm-icon" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                <i class="fas fa-circle-question"></i>
            </div>
            <div class="min-w-0">
                <h3 id="service-confirm-title" class="text-lg font-extrabold text-slate-950">Confirm Action</h3>
                <p id="service-confirm-message" class="mt-2 text-sm leading-6 text-slate-600">Please confirm this service catalog action.</p>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button type="button" id="service-confirm-cancel" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 px-5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                Cancel
            </button>
            <button type="button" id="service-confirm-submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-bold text-white transition hover:bg-blue-700">
                <i class="fas fa-check"></i>
                <span>Confirm</span>
            </button>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('service-confirm-modal');
            const title = document.getElementById('service-confirm-title');
            const message = document.getElementById('service-confirm-message');
            const icon = document.getElementById('service-confirm-icon');
            const confirmButton = document.getElementById('service-confirm-submit');
            const cancelButton = document.getElementById('service-confirm-cancel');
            let pendingForm = null;

            if (!modal || !title || !message || !icon || !confirmButton || !cancelButton) {
                return;
            }

            const tones = {
                danger: {
                    icon: 'fa-triangle-exclamation',
                    iconClass: 'bg-red-50 text-red-700',
                    buttonClass: 'bg-red-600 hover:bg-red-700',
                },
                warning: {
                    icon: 'fa-power-off',
                    iconClass: 'bg-amber-50 text-amber-700',
                    buttonClass: 'bg-amber-600 hover:bg-amber-700',
                },
                success: {
                    icon: 'fa-rotate-left',
                    iconClass: 'bg-emerald-50 text-emerald-700',
                    buttonClass: 'bg-emerald-600 hover:bg-emerald-700',
                },
                primary: {
                    icon: 'fa-circle-check',
                    iconClass: 'bg-blue-50 text-blue-700',
                    buttonClass: 'bg-blue-600 hover:bg-blue-700',
                },
            };

            function setTone(toneName) {
                const tone = tones[toneName] || tones.primary;

                icon.className = `flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ${tone.iconClass}`;
                icon.innerHTML = `<i class="fas ${tone.icon}"></i>`;
                confirmButton.className = `inline-flex h-11 items-center justify-center gap-2 rounded-xl px-5 text-sm font-bold text-white transition ${tone.buttonClass}`;
            }

            function openModal(form) {
                pendingForm = form;
                title.textContent = form.dataset.confirmTitle || 'Confirm Action';
                message.textContent = form.dataset.confirmMessage || 'Please confirm this service catalog action.';
                confirmButton.querySelector('span').textContent = form.dataset.confirmButton || 'Confirm';
                setTone(form.dataset.confirmTone || 'primary');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                confirmButton.focus();
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                pendingForm = null;
            }

            document.querySelectorAll('form[data-service-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.confirmed === 'true') {
                        return;
                    }

                    event.preventDefault();
                    openModal(form);
                });
            });

            confirmButton.addEventListener('click', function () {
                if (!pendingForm) {
                    closeModal();
                    return;
                }

                const form = pendingForm;
                form.dataset.confirmed = 'true';

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                    window.setTimeout(function () {
                        delete form.dataset.confirmed;
                    }, 0);
                } else {
                    form.submit();
                }
            });

            cancelButton.addEventListener('click', closeModal);

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        });
        </script>
    @endpush
@endonce
