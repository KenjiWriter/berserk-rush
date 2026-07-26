@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" class="fixed top-4 right-4 z-[9999] bg-stone-900 border-2 border-emerald-500 text-emerald-200 rounded-xl p-4 shadow-2xl max-w-md backdrop-blur-md transition-all duration-500">
        <div class="flex items-start gap-3">
            <div class="text-emerald-400 text-xl flex-shrink-0">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="flex-1 font-semibold text-sm leading-relaxed">{{ session('success') }}</div>
            <button @click="show = false" class="text-emerald-400 hover:text-emerald-200 text-xs p-1">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 8000)" class="fixed top-4 right-4 z-[9999] bg-stone-900 border-2 border-red-500 text-red-200 rounded-xl p-4 shadow-2xl max-w-md backdrop-blur-md transition-all duration-500">
        <div class="flex items-start gap-3">
            <div class="text-red-400 text-xl flex-shrink-0">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="flex-1 font-semibold text-sm leading-relaxed">{{ session('error') }}</div>
            <button @click="show = false" class="text-red-400 hover:text-red-200 text-xs p-1">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
@endif
