@php
    $theme = $themeColor ?? 'amber';
    $themeClasses = [
        'amber' => [
            'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            'btn' => 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500 shadow-amber-500/20',
            'focus' => 'focus:ring-amber-500 focus:border-amber-500 dark:focus:ring-amber-400',
            'icon' => 'text-amber-500',
        ],
        'blue' => [
            'badge' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            'btn' => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 shadow-blue-500/20',
            'focus' => 'focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400',
            'icon' => 'text-blue-500',
        ],
        'emerald' => [
            'badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            'btn' => 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500 shadow-emerald-500/20',
            'focus' => 'focus:ring-emerald-500 focus:border-emerald-500 dark:focus:ring-emerald-400',
            'icon' => 'text-emerald-500',
        ],
    ][$theme] ?? [
        'badge' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        'btn' => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 shadow-blue-500/20',
        'focus' => 'focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400',
        'icon' => 'text-blue-500',
    ];
@endphp

<!-- Modal Edit Transaksi -->
<div id="editTransactionModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 transition-opacity duration-200" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transform transition-all duration-200">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-800/40">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center {{ $themeClasses['badge'] }} border shadow-xs">
                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                </div>
                <div>
                    <h3 class="font-heading text-base font-bold text-slate-900 dark:text-white" id="modal-title">
                        Edit Transaksi
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        ID Transaksi: #<span id="modalTrxIdDisplay" class="font-mono font-semibold text-slate-700 dark:text-slate-300">-</span>
                    </p>
                </div>
            </div>
            <button type="button" 
                    onclick="closeEditModal()" 
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <!-- Modal Body / Form -->
        <form id="editTransactionForm" onsubmit="handleEditSubmit(event)" class="p-6 space-y-4">
            @csrf
            <input type="hidden" id="edit_trx_id" name="id">

            <!-- Error Alert -->
            <div id="editModalErrorAlert" class="hidden p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-exclamation mt-0.5 text-rose-500"></i>
                    <div id="editModalErrorMessage" class="space-y-1"></div>
                </div>
            </div>

            <!-- Context Info (Invoice & Client) -->
            <div id="modalInvoiceContext" class="hidden p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700 text-xs">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 font-mono font-semibold text-primary-600 dark:text-primary-400">
                            <i class="fa-regular fa-file-lines text-[10px]"></i>
                            <span id="modalInvoiceNo">-</span>
                        </span>
                    </div>
                    <div class="text-slate-600 dark:text-slate-300">
                        Klien: <strong id="modalClientName" class="text-slate-800 dark:text-white">-</strong>
                    </div>
                </div>
            </div>

            <!-- Fields Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- Tanggal Transaksi -->
                <div>
                    <label for="edit_transaction_date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Tanggal Transaksi <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" 
                           id="edit_transaction_date" 
                           name="transaction_date" 
                           required
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 {{ $themeClasses['focus'] }} transition-all shadow-xs">
                </div>

                <!-- Buku Kas / Bank -->
                <div>
                    <label for="edit_cash_reference_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Kas / Rekening Bank <span class="text-rose-500">*</span>
                    </label>
                    <select id="edit_cash_reference_id" 
                            name="cash_reference_id" 
                            required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 {{ $themeClasses['focus'] }} transition-all shadow-xs">
                        @if(isset($allCashReferences))
                            @foreach($allCashReferences as $cr)
                                <option value="{{ $cr->id }}">
                                    {{ $cr->name }} {{ $cr->status_kepemilikan ? '(' . $cr->status_kepemilikan . ')' : '' }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Akun CoA -->
                <div class="sm:col-span-2">
                    <label for="edit_coa_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Chart of Account (CoA) <span class="text-rose-500">*</span>
                    </label>
                    <select id="edit_coa_id" 
                            name="coa_id" 
                            required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 {{ $themeClasses['focus'] }} transition-all shadow-xs">
                        @if(isset($allCoas))
                            @foreach($allCoas as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->code }} - {{ $c->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Debit -->
                <div>
                    <label for="edit_debit_amount" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Debit (Rp) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           id="edit_debit_amount" 
                           name="debit_amount" 
                           step="any"
                           min="0"
                           required
                           class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 {{ $themeClasses['focus'] }} transition-all shadow-xs">
                </div>

                <!-- Kredit -->
                <div>
                    <label for="edit_credit_amount" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Kredit (Rp) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           id="edit_credit_amount" 
                           name="credit_amount" 
                           step="any"
                           min="0"
                           required
                           class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 {{ $themeClasses['focus'] }} transition-all shadow-xs">
                </div>

                <!-- Keterangan -->
                <div class="sm:col-span-2">
                    <label for="edit_description" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Keterangan / Deskripsi Transaksi <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="edit_description" 
                              name="description" 
                              rows="3" 
                              required
                              placeholder="Masukkan deskripsi detail transaksi..."
                              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 {{ $themeClasses['focus'] }} transition-all shadow-xs"></textarea>
                </div>
            </div>

            <!-- Quick Link to Filament -->
            <div class="pt-1 flex items-center justify-between text-xs border-t border-slate-100 dark:border-slate-800">
                <a id="modalFilamentLink" 
                   href="#" 
                   target="_blank" 
                   class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700 dark:text-primary-400 hover:underline font-medium">
                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                    Buka Form Lengkap di Filament Panel
                </a>
            </div>

            <!-- Modal Footer Buttons -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                <button type="button" 
                        onclick="closeEditModal()" 
                        class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-semibold transition-colors shadow-xs">
                    Batal
                </button>
                <button type="submit" 
                        id="saveEditBtn"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-white text-xs font-semibold {{ $themeClasses['btn'] }} transition-all hover:scale-[1.02] active:scale-[0.98] shadow-sm">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Notification -->
<div id="toastNotification" class="hidden fixed bottom-5 right-5 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-xl border border-slate-800 dark:border-slate-200 text-xs font-medium transition-all transform translate-y-3 opacity-0">
    <i id="toastIcon" class="fa-solid fa-circle-check text-emerald-400 dark:text-emerald-600 text-sm"></i>
    <span id="toastMessage">Transaksi berhasil disimpan!</span>
</div>

<script>
    const fromPanel = "{{ $from ?? 'admin' }}";

    function openEditModal(trx) {
        if (!trx) return;

        // Reset error display
        const errAlert = document.getElementById('editModalErrorAlert');
        errAlert.classList.add('hidden');
        document.getElementById('editModalErrorMessage').innerHTML = '';

        // Fill basic fields
        document.getElementById('edit_trx_id').value = trx.id;
        document.getElementById('modalTrxIdDisplay').textContent = trx.id;
        document.getElementById('edit_transaction_date').value = (trx.transaction_date || '').substring(0, 10);
        document.getElementById('edit_cash_reference_id').value = trx.cash_reference_id || '';
        document.getElementById('edit_coa_id').value = trx.coa_id || '';
        document.getElementById('edit_debit_amount').value = trx.debit_amount !== undefined ? trx.debit_amount : 0;
        document.getElementById('edit_credit_amount').value = trx.credit_amount !== undefined ? trx.credit_amount : 0;
        document.getElementById('edit_description').value = trx.description || '';

        // Context invoice/client
        const contextDiv = document.getElementById('modalInvoiceContext');
        if (trx.invoice_number || trx.client_name) {
            document.getElementById('modalInvoiceNo').textContent = trx.invoice_number || 'Tanpa Invoice';
            document.getElementById('modalClientName').textContent = trx.client_name || '-';
            contextDiv.classList.remove('hidden');
        } else {
            contextDiv.classList.add('hidden');
        }

        // Filament link
        const filamentUrl = fromPanel === 'app'
            ? `/app/cash-reports/${trx.id}/edit`
            : `/admin/cash-reports/${trx.id}/edit`;
        document.getElementById('modalFilamentLink').setAttribute('href', filamentUrl);

        // Show modal
        const modal = document.getElementById('editTransactionModal');
        modal.classList.remove('hidden');
    }

    function closeEditModal() {
        const modal = document.getElementById('editTransactionModal');
        modal.classList.add('hidden');
    }

    // Close modal on click outside
    document.getElementById('editTransactionModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('editTransactionModal');
            if (!modal.classList.contains('hidden')) {
                closeEditModal();
            }
        }
    });

    function showToast(message, isSuccess = true) {
        const toast = document.getElementById('toastNotification');
        const toastMsg = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');

        toastMsg.textContent = message;
        if (isSuccess) {
            toastIcon.className = 'fa-solid fa-circle-check text-emerald-400 dark:text-emerald-600 text-sm';
        } else {
            toastIcon.className = 'fa-solid fa-circle-exclamation text-rose-400 dark:text-rose-600 text-sm';
        }

        toast.classList.remove('hidden', 'translate-y-3', 'opacity-0');
        setTimeout(() => {
            toast.classList.add('translate-y-3', 'opacity-0');
            setTimeout(() => toast.classList.add('hidden'), 200);
        }, 3000);
    }

    function handleEditSubmit(e) {
        e.preventDefault();
        const form = document.getElementById('editTransactionForm');
        const saveBtn = document.getElementById('saveEditBtn');
        const originalBtnHtml = saveBtn.innerHTML;

        const csrfToken = document.querySelector('meta[name="csrf-token"]') 
            ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
            : '{{ csrf_token() }}';

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

        const formData = new FormData(form);

        fetch("{{ route('neraca-lajur-piutang.update-transaction') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, data })))
        .then(({ ok, data }) => {
            if (ok && data.success) {
                closeEditModal();
                showToast(data.message || 'Transaksi berhasil diperbarui!');
                // Reload halaman agar seluruh total, rekapitulasi kartu, dan neraca lajur sinkron
                setTimeout(() => {
                    window.location.reload();
                }, 700);
            } else {
                const errAlert = document.getElementById('editModalErrorAlert');
                const errMsg = document.getElementById('editModalErrorMessage');
                errAlert.classList.remove('hidden');

                if (data.errors) {
                    errMsg.innerHTML = Object.values(data.errors).flat().map(err => `<div>• ${err}</div>`).join('');
                } else {
                    errMsg.innerHTML = `<div>• ${data.message || 'Terjadi kesalahan saat menyimpan.'}</div>`;
                }
            }
        })
        .catch(err => {
            console.error('Update error:', err);
            const errAlert = document.getElementById('editModalErrorAlert');
            const errMsg = document.getElementById('editModalErrorMessage');
            errAlert.classList.remove('hidden');
            errMsg.innerHTML = '<div>• Gagal menghubungi server. Silakan coba lagi.</div>';
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        });
    }
</script>
