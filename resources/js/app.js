import './bootstrap';

// Debug untuk Filament modal
document.addEventListener('DOMContentLoaded', function() {
    console.log('App.js loaded');
    
    // Debug untuk Filament actions
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-filament-action]')) {
            console.log('Filament action clicked:', e.target.closest('[data-filament-action]'));
        }
    });
    
    // Debug untuk modal
    document.addEventListener('livewire:load', function() {
        console.log('Livewire loaded');
        
        // Listen untuk modal events
        Livewire.on('openModal', (data) => {
            console.log('Modal opened:', data);
        });
        
        Livewire.on('closeModal', (data) => {
            console.log('Modal closed:', data);
        });
    });
});
