/**
 * WordStockt Landing Page Scripts
 *
 * Features:
 * - Scroll-based color transitions for background orbs
 */

document.addEventListener('DOMContentLoaded', function() {
    initScrollColorTransitions();
});

/**
 * Initialize scroll-based color transitions
 * Changes background gradient and orb colors as user scrolls
 */
function initScrollColorTransitions() {
    const globalGradient = document.querySelector('.global-gradient');
    const orb1 = document.querySelector('.global-orb-1');
    const orb2 = document.querySelector('.global-orb-2');
    const orb3 = document.querySelector('.global-orb-3');

    // Color stops for different scroll positions (hue values)
    const colorStops = [
        { hue: 210, saturation: 70 },  // Blue (hero)
        { hue: 200, saturation: 65 },  // Cyan-blue (features)
        { hue: 180, saturation: 60 },  // Teal (fair play)
        { hue: 220, saturation: 75 },  // Deep blue (CTA)
    ];

    function lerp(start, end, t) {
        return start + (end - start) * t;
    }

    function updateBackgroundColors() {
        const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrollProgress = scrollHeight > 0 ? Math.min(window.scrollY / scrollHeight, 1) : 0;

        // Determine which color segment we're in
        const segments = colorStops.length - 1;
        const segment = Math.min(Math.floor(scrollProgress * segments), segments - 1);
        const segmentProgress = (scrollProgress * segments) - segment;

        const currentColor = colorStops[segment];
        const nextColor = colorStops[Math.min(segment + 1, segments)];

        const hue = lerp(currentColor.hue, nextColor.hue, segmentProgress);
        const saturation = lerp(currentColor.saturation, nextColor.saturation, segmentProgress);

        // Update main gradient
        if (globalGradient) {
            globalGradient.style.background = `
                radial-gradient(ellipse 80% 50% at 20% 30%, hsla(${hue}, ${saturation}%, 50%, 0.12) 0%, transparent 50%),
                radial-gradient(ellipse 60% 40% at 80% 50%, hsla(${hue + 120}, ${saturation - 20}%, 45%, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse 70% 40% at 30% 70%, hsla(${hue}, ${saturation}%, 50%, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse 50% 30% at 70% 90%, hsla(${hue + 30}, ${saturation}%, 55%, 0.08) 0%, transparent 50%)
            `;
        }

        // Update orb colors
        if (orb1) orb1.style.background = `hsla(${hue}, ${saturation}%, 50%, 0.4)`;
        if (orb2) orb2.style.background = `hsla(${hue + 120}, ${saturation - 20}%, 45%, 0.25)`;
        if (orb3) orb3.style.background = `hsla(${hue + 30}, ${saturation}%, 55%, 0.35)`;
    }

    // Throttled scroll handler
    let colorTicking = false;
    window.addEventListener('scroll', function() {
        if (!colorTicking) {
            window.requestAnimationFrame(function() {
                updateBackgroundColors();
                colorTicking = false;
            });
            colorTicking = true;
        }
    });

    // Initial call
    updateBackgroundColors();
}
