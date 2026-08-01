<div
    x-data="{
        playSound() {
            const audio = document.getElementById('order-sound-audio');
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(() => {});
            }
        }
    }"
    x-on:play-order-sound.window="playSound()"
    wire:poll.10s="checkNewOrders"
    class="hidden"
    aria-hidden="true"
>
    <audio id="order-sound-audio" preload="auto">
        <source src="{{ asset('sounds/new-order.mp3') }}" type="audio/mpeg">
    </audio>
</div>
