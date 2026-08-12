@if(session()->has('success'))
<div class="fixed top-4 right-4 z-50 animate-fade-in">
    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-lg flex items-center space-x-3 max-w-md">
        <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
            </div>
        </div>
        <div class="flex-1">
            <p class="text-green-800 font-medium">{{ session('success') }}</p>
        </div>
        <button onclick="this.parentElement.parentElement.remove()" class="text-green-600 hover:text-green-800">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
</div>
@endif

@if(session()->has('error'))
<div class="fixed top-4 right-4 z-50 animate-fade-in">
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-lg flex items-center space-x-3 max-w-md">
        <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
            </div>
        </div>
        <div class="flex-1">
            <p class="text-red-800 font-medium">{{ session('error') }}</p>
        </div>
        <button onclick="this.parentElement.parentElement.remove()" class="text-red-600 hover:text-red-800">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
</div>
@endif

@if(session()->has('warning'))
<div class="fixed top-4 right-4 z-50 animate-fade-in">
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg shadow-lg flex items-center space-x-3 max-w-md">
        <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-600"></i>
            </div>
        </div>
        <div class="flex-1">
            <p class="text-yellow-800 font-medium">{{ session('warning') }}</p>
        </div>
        <button onclick="this.parentElement.parentElement.remove()" class="text-yellow-600 hover:text-yellow-800">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
</div>
@endif

@if(session()->has('info'))
<div class="fixed top-4 right-4 z-50 animate-fade-in">
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg shadow-lg flex items-center space-x-3 max-w-md">
        <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <i data-lucide="info" class="w-5 h-5 text-blue-600"></i>
            </div>
        </div>
        <div class="flex-1">
            <p class="text-blue-800 font-medium">{{ session('info') }}</p>
        </div>
        <button onclick="this.parentElement.parentElement.remove()" class="text-blue-600 hover:text-blue-800">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
</div>
@endif

@if($errors->any())
<div class="fixed top-4 right-4 z-50 animate-fade-in">
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-lg max-w-md">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
                </div>
            </div>
            <div class="flex-1">
                <p class="text-red-800 font-medium">Por favor, corrige los siguientes errores:</p>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endif

<script>
// Auto-remove notifications after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const notifications = document.querySelectorAll('.animate-fade-in');
    notifications.forEach(function(notification) {
        setTimeout(function() {
            notification.remove();
        }, 5000);
    });
    
    // Re-initialize Lucide icons for dynamic content
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
