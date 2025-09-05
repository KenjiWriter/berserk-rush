@if (session('success'))
    <div class="fixed top-4 right-4 z-50 bg-green-100 border-2 border-green-600 rounded-lg p-4 shadow-lg">
        <div class="flex items-center">
            <div class="text-green-600 mr-3">✅</div>
            <div class="font-semibold text-green-800">{{ session('success') }}</div>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="fixed top-4 right-4 z-50 bg-red-100 border-2 border-red-600 rounded-lg p-4 shadow-lg">
        <div class="flex items-center">
            <div class="text-red-600 mr-3">❌</div>
            <div class="font-semibold text-red-800">{{ session('error') }}</div>
        </div>
    </div>
@endif
