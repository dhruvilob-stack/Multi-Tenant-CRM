@php
    use Illuminate\Support\Facades\Storage;

    $disk = Storage::disk(config('filament.default_filesystem_disk', 'local'));
    $images = array_values(array_filter((array) ($getState() ?? [])));

    $urls = collect($images)->map(function ($path) use ($disk) {
        try {
            return $disk->temporaryUrl($path, now()->addMinutes(30));
        } catch (\Throwable) {
            try {
                return $disk->url($path);
            } catch (\Throwable) {
                return null;
            }
        }
    })->filter()->values()->all();
@endphp

@if (count($urls) === 0)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-6 text-sm text-gray-500 dark:text-gray-400">
        No product images uploaded yet.
    </div>
@else
    <div
        x-data="{
            i: 0,
            total: {{ count($urls) }},
            startX: 0,
            endX: 0,
            swipeThreshold: 40,
            onTouchStart(e) { this.startX = e.changedTouches[0].clientX },
            onTouchEnd(e) {
                this.endX = e.changedTouches[0].clientX
                const delta = this.endX - this.startX
                if (Math.abs(delta) < this.swipeThreshold) return
                if (delta < 0) this.i = (this.i + 1) % this.total
                if (delta > 0) this.i = (this.i - 1 + this.total) % this.total
            }
        }"
        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-4"
    >
        <div
            class="relative overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-900"
            @touchstart.passive="onTouchStart($event)"
            @touchend.passive="onTouchEnd($event)"
        >
            <template x-for="(src, idx) in @js($urls)" :key="idx">
                <a
                    x-show="i === idx"
                    {{-- x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-x-4"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 -translate-x-4" --}}
                    :href="src"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex h-72 w-full items-center justify-center p-4"
                >
                    <img
                        :src="src"
                        alt="Product image"
                        class="h-56 w-auto max-w-full rounded-md object-contain shadow-sm"
                    />
                </a>
            </template>

            <button
                type="button"
                class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 dark:bg-gray-800/90 px-3 py-1 text-lg shadow"
                @click="i = (i - 1 + total) % total"
            >
                ‹
            </button>

            <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 dark:bg-gray-800/90 px-3 py-1 text-lg shadow"
                @click="i = (i + 1) % total"
            >
                ›
            </button>
        </div>

        <div class="flex w-full justify-center gap-2 overflow-x-auto pb-1">
            <template x-for="(src, idx) in @js($urls)" :key="`thumb-${idx}`">
                <button type="button" class="shrink-0" @click="i = idx">
                    <img
                        :src="src"
                        class="h-16 w-16 rounded-md object-cover border"
                        :class="i === idx ? 'border-primary-500 ring-2 ring-primary-300/50' : 'border-gray-300 dark:border-gray-600'"
                        alt="Thumbnail"
                    />
                </button>
            </template>
        </div>
    </div>
@endif
