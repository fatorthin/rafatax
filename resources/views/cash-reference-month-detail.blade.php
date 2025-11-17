<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $cashReference->name }} - {{ $monthName }} {{ $year }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        /* Custom styling for Select2 to match Tailwind */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 22px;
            color: #374151;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #3b82f6;
            outline: 2px solid transparent;
            outline-offset: 2px;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .select2-dropdown {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
        }
    </style>
</head>

<body class="h-full">
    <div class="min-h-full">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                            Transactions - {{ $cashReference->name }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-600">{{ $monthName }} {{ $year }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ url('/admin/cash-references/' . $cashReference->id . '/monthly') }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Monthly View
                        </a>
                        <button type="button" onclick="openCreateModal()"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <i class="fas fa-plus mr-2"></i> Add Transaction
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <main>
            <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                <!-- Success/Error Messages -->
                <div id="alertContainer"></div>

                @if (session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Previous Balance Card -->
                <div class="mb-6 overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-6 py-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Saldo Awal</h3>
                                <p class="text-sm text-gray-600">Balance from previous month</p>
                            </div>
                            <div class="text-2xl font-bold {{ $prevBalance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format((float) $prevBalance, 2, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                                            Date</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">CoA</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">CoA Name
                                        </th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                            Description</th>
                                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Debit
                                        </th>
                                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Credit
                                        </th>
                                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Balance
                                        </th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse($transactionsWithBalance as $transaction)
                                        <tr class="hover:bg-gray-50">
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                                {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d-M-Y') }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
                                                {{ $transaction->coa->code ?? '-' }}
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-700">
                                                {{ $transaction->coa->name ?? '-' }}
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-700 max-w-xs">
                                                {{ $transaction->description }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-right text-gray-700">
                                                {{ number_format((float) $transaction->debit_amount, 2, ',', '.') }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-right text-gray-700">
                                                {{ number_format((float) $transaction->credit_amount, 2, ',', '.') }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-right font-semibold {{ $transaction->running_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ number_format((float) $transaction->running_balance, 2, ',', '.') }}
                                            </td>
                                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" onclick="openEditModal({{ $transaction->id }})" class="text-blue-600 hover:text-blue-900" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" onclick="confirmDelete({{ $transaction->id }})" class="text-red-600 hover:text-red-900" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-3 py-8 text-center text-sm text-gray-500">
                                                No transactions found for this month.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr class="font-semibold">
                                        <td colspan="4" class="py-3.5 pl-4 pr-3 text-right text-sm text-gray-900 sm:pl-6">
                                            Total:
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-sm text-right text-gray-900">
                                            {{ number_format((float) $totalDebit, 2, ',', '.') }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-sm text-right text-gray-900">
                                            {{ number_format((float) $totalCredit, 2, ',', '.') }}
                                        </td>
                                        <td colspan="2" class="px-3 py-3.5 text-sm text-gray-900"></td>
                                    </tr>
                                    <tr class="font-bold text-lg">
                                        <td colspan="6" class="py-3.5 pl-4 pr-3 text-right text-sm text-gray-900 sm:pl-6">
                                            Ending Balance:
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-right {{ $endingBalance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format((float) $endingBalance, 2, ',', '.') }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Transaction Modal -->
    <div id="createModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button type="button" onclick="closeCreateModal()" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div>
                    <div class="text-center sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                            <i class="fas fa-plus-circle text-blue-600 mr-2"></i>Add New Transaction
                        </h3>
                    </div>
                    <form id="createTransactionForm" class="mt-4">
                        @csrf
                        <!-- Error Alert -->
                        <div id="formErrors" class="hidden mb-4 rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">There were errors with your
                                        submission</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <ul id="errorList" class="list-disc list-inside space-y-1"></ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <!-- Transaction Date -->
                            <div>
                                <label for="transaction_date" class="block text-sm font-medium text-gray-700">
                                    Transaction Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="transaction_date" id="transaction_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <!-- CoA -->
                            <div>
                                <label for="coa_id" class="block text-sm font-medium text-gray-700">
                                    Chart of Account <span class="text-red-500">*</span>
                                </label>
                                <select name="coa_id" id="coa_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">Select CoA...</option>
                                    @foreach ($coaList as $coa)
                                        <option value="{{ $coa->id }}">{{ $coa->code }} -
                                            {{ $coa->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">
                                    Description <span class="text-red-500">*</span>
                                </label>
                                <textarea name="description" id="description" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                            </div>

                            <!-- Debit Amount -->
                            <div>
                                <label for="debit_amount" class="block text-sm font-medium text-gray-700">
                                    Debit Amount <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="debit_amount" id="debit_amount" step="0.01" min="0" value="0" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <!-- Credit Amount -->
                            <div>
                                <label for="credit_amount" class="block text-sm font-medium text-gray-700">
                                    Credit Amount <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="credit_amount" id="credit_amount" step="0.01" min="0" value="0" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit" id="saveBtn"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                                <i class="fas fa-save mr-2"></i>Save
                            </button>
                            <button type="button" onclick="submitForm(true)" id="saveAnotherBtn"
                                class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:w-auto sm:text-sm">
                                <i class="fas fa-plus mr-2"></i>Save & Create Another
                            </button>
                            <button type="button" onclick="closeCreateModal()"
                                class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Transaction Modal -->
    <div id="editModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button type="button" onclick="closeEditModal()" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div>
                    <div class="text-center sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                            <i class="fas fa-edit text-blue-600 mr-2"></i>Edit Transaction
                        </h3>
                    </div>
                    <form id="editTransactionForm" class="mt-4">
                        @csrf
                        <input type="hidden" id="edit_transaction_id" name="transaction_id">

                        <!-- Error Alert -->
                        <div id="editFormErrors" class="hidden mb-4 rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">There were errors with your
                                        submission</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <ul id="editErrorList" class="list-disc list-inside space-y-1"></ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <!-- Transaction Date -->
                            <div>
                                <label for="edit_transaction_date" class="block text-sm font-medium text-gray-700">
                                    Transaction Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="transaction_date" id="edit_transaction_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <!-- CoA -->
                            <div>
                                <label for="edit_coa_id" class="block text-sm font-medium text-gray-700">
                                    Chart of Account <span class="text-red-500">*</span>
                                </label>
                                <select name="coa_id" id="edit_coa_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">Select CoA...</option>
                                    @foreach ($coaList as $coa)
                                        <option value="{{ $coa->id }}">{{ $coa->code }} -
                                            {{ $coa->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="edit_description" class="block text-sm font-medium text-gray-700">
                                    Description <span class="text-red-500">*</span>
                                </label>
                                <textarea name="description" id="edit_description" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                            </div>

                            <!-- Debit Amount -->
                            <div>
                                <label for="edit_debit_amount" class="block text-sm font-medium text-gray-700">
                                    Debit Amount <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="debit_amount" id="edit_debit_amount" step="0.01" min="0" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <!-- Credit Amount -->
                            <div>
                                <label for="edit_credit_amount" class="block text-sm font-medium text-gray-700">
                                    Credit Amount <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="credit_amount" id="edit_credit_amount" step="0.01" min="0" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit" id="updateBtn"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                                <i class="fas fa-save mr-2"></i>Update
                            </button>
                            <button type="button" onclick="closeEditModal()"
                                class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Delete Transaction
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Are you sure you want to delete this transaction? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <form id="deleteForm" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Delete
                        </button>
                    </form>
                    <button type="button" onclick="closeDeleteModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Select2 for CoA dropdown
        function initializeSelect2() {
            $('#coa_id').select2({
                placeholder: 'Select CoA...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#createModal')
            });
        }

        // Initialize Select2 for Edit Modal
        function initializeEditSelect2() {
            $('#edit_coa_id').select2({
                placeholder: 'Select CoA...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#editModal')
            });
        }

        // Show notification function
        function showNotification(message, type = 'success') {
            const alertContainer = document.getElementById('alertContainer');
            const bgColor = type === 'success' ? 'bg-green-50' : 'bg-red-50';
            const textColor = type === 'success' ? 'text-green-800' : 'text-red-800';
            const iconColor = type === 'success' ? 'text-green-400' : 'text-red-400';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            const alertHtml = `
                <div class="mb-4 rounded-md ${bgColor} p-4 notification-alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas ${icon} ${iconColor}"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium ${textColor}">${message}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <div class="-mx-1.5 -my-1.5">
                                <button type="button" onclick="this.closest('.notification-alert').remove()"
                                    class="inline-flex rounded-md p-1.5 ${textColor} hover:bg-opacity-20 focus:outline-none">
                                    <span class="sr-only">Dismiss</span>
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            alertContainer.innerHTML = alertHtml;

            // Auto dismiss after 5 seconds
            setTimeout(() => {
                const alert = alertContainer.querySelector('.notification-alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);

            // Scroll to top
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Check if returning from edit page
        document.addEventListener('DOMContentLoaded', function() {
            const editingId = localStorage.getItem('editingTransaction');
            const urlParams = new URLSearchParams(window.location.search);

            if (editingId && !urlParams.has('edited')) {
                // User returned from edit, show success notification
                localStorage.removeItem('editingTransaction');
                showNotification('Transaction updated successfully!', 'success');
            }

            // Check if we need to reopen create modal (after "Save & Create Another")
            if (sessionStorage.getItem('reopenCreateModal') === 'true') {
                sessionStorage.removeItem('reopenCreateModal');
                setTimeout(() => {
                    openCreateModal();
                }, 500);
            }
        });

        // Edit Modal Functions
        function openEditModal(transactionId) {
            const modal = document.getElementById('editModal');
            modal.classList.remove('hidden');

            // Show loading state
            document.getElementById('updateBtn').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
            document.getElementById('updateBtn').disabled = true;

            // Fetch transaction data
            fetch(`/cash-reference/transaction/${transactionId}/edit`, {
                    headers: {
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const transaction = data.transaction;
                        document.getElementById('edit_transaction_id').value = transaction.id;
                        document.getElementById('edit_transaction_date').value = transaction.transaction_date;
                        document.getElementById('edit_description').value = transaction.description;
                        document.getElementById('edit_debit_amount').value = transaction.debit_amount;
                        document.getElementById('edit_credit_amount').value = transaction.credit_amount;

                        // Initialize Select2 and set value
                        setTimeout(() => {
                            initializeEditSelect2();
                            $('#edit_coa_id').val(transaction.coa_id).trigger('change');
                        }, 100);

                        // Re-enable button
                        document.getElementById('updateBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Update';
                        document.getElementById('updateBtn').disabled = false;
                    } else {
                        showNotification('Failed to load transaction data', 'error');
                        closeEditModal();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while loading transaction', 'error');
                    closeEditModal();
                });
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.add('hidden');
            // Destroy Select2 when closing modal
            if ($('#edit_coa_id').hasClass('select2-hidden-accessible')) {
                $('#edit_coa_id').select2('destroy');
            }
            resetEditForm();
        }

        function resetEditForm() {
            document.getElementById('editTransactionForm').reset();
            document.getElementById('editFormErrors').classList.add('hidden');
            document.getElementById('editErrorList').innerHTML = '';
            // Clear Select2 selection
            if ($('#edit_coa_id').hasClass('select2-hidden-accessible')) {
                $('#edit_coa_id').val(null).trigger('change');
            }
        }

        // Handle edit form submission
        document.getElementById('editTransactionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitEditForm();
        });

        function submitEditForm() {
            const form = document.getElementById('editTransactionForm');
            const formData = new FormData(form);
            const updateBtn = document.getElementById('updateBtn');
            const transactionId = document.getElementById('edit_transaction_id').value;

            // Disable button during submission
            updateBtn.disabled = true;
            updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';

            fetch(`/cash-reference/transaction/${transactionId}/update`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-HTTP-Method-Override': 'PUT'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeEditModal();
                        showNotification('Transaction updated successfully!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showEditErrors(data.errors || {
                            general: [data.message]
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showEditErrors({
                        general: ['An error occurred. Please try again.']
                    });
                })
                .finally(() => {
                    // Re-enable button
                    updateBtn.disabled = false;
                    updateBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Update';
                });
        }

        function showEditErrors(errors) {
            const errorDiv = document.getElementById('editFormErrors');
            const errorList = document.getElementById('editErrorList');
            errorList.innerHTML = '';

            Object.keys(errors).forEach(key => {
                errors[key].forEach(error => {
                    const li = document.createElement('li');
                    li.textContent = error;
                    errorList.appendChild(li);
                });
            });

            errorDiv.classList.remove('hidden');
            // Scroll to top of modal
            document.querySelector('#editModal .inline-block').scrollTop = 0;
        }

        // Create Modal Functions
        function openCreateModal() {
            const modal = document.getElementById('createModal');
            modal.classList.remove('hidden');
            resetForm();
            // Set default date to today
            document.getElementById('transaction_date').value = new Date().toISOString().split('T')[0];
            // Initialize Select2 after modal is shown
            setTimeout(() => {
                initializeSelect2();
            }, 100);
        }

        function closeCreateModal() {
            const modal = document.getElementById('createModal');
            modal.classList.add('hidden');
            // Destroy Select2 when closing modal
            if ($('#coa_id').hasClass('select2-hidden-accessible')) {
                $('#coa_id').select2('destroy');
            }
            resetForm();
        }

        function resetForm() {
            document.getElementById('createTransactionForm').reset();
            document.getElementById('formErrors').classList.add('hidden');
            document.getElementById('errorList').innerHTML = '';
            // Reset to 0
            document.getElementById('debit_amount').value = '0';
            document.getElementById('credit_amount').value = '0';
            // Clear Select2 selection
            if ($('#coa_id').hasClass('select2-hidden-accessible')) {
                $('#coa_id').val(null).trigger('change');
            }
        }

        // Handle form submission
        document.getElementById('createTransactionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(false);
        });

        function submitForm(createAnother) {
            const form = document.getElementById('createTransactionForm');
            const formData = new FormData(form);
            const saveBtn = document.getElementById('saveBtn');
            const saveAnotherBtn = document.getElementById('saveAnotherBtn');

            // Disable buttons during submission
            saveBtn.disabled = true;
            saveAnotherBtn.disabled = true;

            if (createAnother) {
                saveAnotherBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            } else {
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            }

            fetch('/cash-reference/{{ $cashReference->id }}/transaction/store?year={{ $year }}&month={{ $month }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (createAnother) {
                            // Close modal, show notification, reload page, then reopen modal
                            closeCreateModal();
                            showNotification('Transaction created successfully! Creating another...', 'success');

                            // Reload page and reopen modal after reload
                            setTimeout(() => {
                                // Store flag to reopen modal after reload
                                sessionStorage.setItem('reopenCreateModal', 'true');
                                window.location.reload();
                            }, 1000);
                        } else {
                            // Close modal, show notification and reload page
                            closeCreateModal();
                            showNotification('Transaction created successfully!', 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        }
                    } else {
                        showErrors(data.errors || {
                            general: [data.message]
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrors({
                        general: ['An error occurred. Please try again.']
                    });
                })
                .finally(() => {
                    // Re-enable buttons
                    saveBtn.disabled = false;
                    saveAnotherBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Save';
                    saveAnotherBtn.innerHTML = '<i class="fas fa-plus mr-2"></i>Save & Create Another';
                });
        }

        function showErrors(errors) {
            const errorDiv = document.getElementById('formErrors');
            const errorList = document.getElementById('errorList');
            errorList.innerHTML = '';

            Object.keys(errors).forEach(key => {
                errors[key].forEach(error => {
                    const li = document.createElement('li');
                    li.textContent = error;
                    errorList.appendChild(li);
                });
            });

            errorDiv.classList.remove('hidden');
            // Scroll to top of modal
            document.querySelector('#createModal .inline-block').scrollTop = 0;
        }

        // Delete Modal Functions
        function confirmDelete(transactionId) {
            const modal = document.getElementById('deleteModal');
            const form = document.getElementById('deleteForm');
            form.action =
                `/cash-reference/transaction/${transactionId}/delete?year={{ $year }}&month={{ $month }}`;
            modal.classList.remove('hidden');
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
        }

        // Close modals when clicking outside
        document.getElementById('createModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeCreateModal();
            }
        });

        document.getElementById('editModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        document.getElementById('deleteModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>

</html>
