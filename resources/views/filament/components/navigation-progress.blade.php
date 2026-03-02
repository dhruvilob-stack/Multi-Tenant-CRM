<style>
    #fi-nav-progress {
        position: fixed;
        inset: 0 0 auto 0;
        height: 3px;
        width: 100%;
        z-index: 9999;
        pointer-events: none;
    }

    #fi-nav-progress .fi-nav-progress-bar {
        height: 100%;
        width: 0%;
        opacity: 0;
        transform-origin: left center;
        background: linear-gradient(90deg, #22c55e, #06b6d4, #3b82f6);
        box-shadow: 0 0 12px rgba(6, 182, 212, 0.5);
        transition: width 0.16s linear, opacity 0.2s ease;
    }

    .dark #fi-nav-progress .fi-nav-progress-bar {
        background: linear-gradient(90deg, #fde68a, #facc15, #f59e0b);
        box-shadow: 0 0 12px rgba(250, 204, 21, 0.5);
    }
</style>

<div id="fi-nav-progress" x-data="{
    active: false,
    progress: 0,
    timer: null,
    start() {
        this.active = true;
        this.progress = 8;
        clearInterval(this.timer);
        this.timer = setInterval(() => {
            if (this.progress < 90) {
                this.progress += Math.max(1, (90 - this.progress) / 8);
            }
        }, 120);
    },
    done() {
        this.progress = 100;
        clearInterval(this.timer);
        setTimeout(() => {
            this.active = false;
            this.progress = 0;
        }, 220);
    },
}" x-on:livewire:navigate.window="start()" x-on:livewire:navigated.window="done()">
    <div class="fi-nav-progress-bar" :style="`width: ${progress}%; opacity: ${active ? 1 : 0};`"></div>
</div>

