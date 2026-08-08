@props([
    'id',
    'percent',
    'gradientClass' => 'from-emerald-600 via-emerald-500 to-green-400',
    'glowShadow' => 'shadow-[0_0_12px_rgba(16,185,129,0.6)]',
    'ringClass' => 'ring-amber-500/40',
    'height' => 'h-3.5 sm:h-4',
    'dropletColor' => 'rgba(220,38,38,0.92)',
    'liquid' => true,
])

{{--
    Shared "dark fantasy" HP/Mana bar used by every combat screen (Adventure, Dungeons,
    PvP Arena, GvG) so the chip-damage wipe + simmering liquid/embers + hit shake/splash
    behave identically everywhere instead of being re-implemented per screen.

    Structure: a lagging bone-white "chip" layer sits under the colored fill layer. Both
    animate to the same target width, but the fill snaps fast while the chip trails
    behind on a slower, delayed transition - the gap between them is what makes the
    "how much HP was just lost" wipe (right-to-left) readable. `CombatBarFX.hit(id)`
    (defined once below) is called from each screen's own turn-played handler to shake
    the bar and spray a few blood/arcane droplets at the fill's edge on impact.
--}}

@once
    <style>
        .hp-fill-layer { transition: width 180ms cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(255,255,255,0.2), inset 0 -4px 7px rgba(0,0,0,0.6); }
        .hp-chip-layer { transition: width 650ms cubic-bezier(0.65, 0, 0.35, 1); transition-delay: 220ms; }

        /* A single slow, low-contrast gleam drifting across the surface - just enough to
           read as "liquid catching dim light", not a sparkly/busy shimmer. */
        .hp-fill-layer.liquid-fx {
            animation: liquidPulse 4s ease-in-out infinite;
        }
        .hp-fill-layer.liquid-fx::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(100deg, transparent 35%, rgba(255,255,255,0.16) 48%, rgba(255,255,255,0.22) 50%, rgba(255,255,255,0.16) 52%, transparent 65%);
            background-size: 260% 100%;
            animation: liquidGleam 5.5s ease-in-out infinite;
            mix-blend-mode: overlay;
            pointer-events: none;
        }

        @keyframes liquidGleam {
            0% { background-position: 140% 0; }
            100% { background-position: -140% 0; }
        }
        @keyframes liquidPulse {
            0%, 100% { filter: brightness(1); }
            50% { filter: brightness(1.06); }
        }

        @keyframes hpBarShake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-3px); }
            40% { transform: translateX(3px); }
            60% { transform: translateX(-2px); }
            80% { transform: translateX(2px); }
        }
        .hp-bar-shake { animation: hpBarShake 320ms ease-in-out; }

        @keyframes hpBarHealGlow {
            0% { filter: brightness(1); }
            35% { filter: brightness(1.7) saturate(1.4); }
            100% { filter: brightness(1); }
        }
        .hp-bar-heal-glow { animation: hpBarHealGlow 550ms ease-out; }
    </style>
    <script>
        // Shared across every combat screen (adventure/dungeon/arena/guild-war): each
        // screen's own turn-played handler calls CombatBarFX.hit(barId) at the moment
        // of impact to shake the bar and spray a few blood/arcane droplets at its edge.
        window.CombatBarFX = window.CombatBarFX || {
            // Odświeża pasek bez requestu do serwera - używane przez lokalne odtwarzanie
            // tur (Adventure PVE), które ma już cały log walki i tylko animuje go w JS.
            setPercent(barId, percent) {
                const bar = document.getElementById(barId);
                if (!bar) return;
                const pct = Math.max(0, Math.min(100, percent));
                const fill = bar.querySelector('.hp-fill-layer');
                const chip = bar.querySelector('.hp-chip-layer');
                if (fill) fill.style.width = `${pct}%`;
                if (chip) chip.style.width = `${pct}%`;
            },
            hit(barId) {
                const bar = document.getElementById(barId);
                if (!bar) return;

                bar.classList.remove('hp-bar-shake');
                void bar.offsetWidth;
                bar.classList.add('hp-bar-shake');

                const fill = bar.querySelector('.hp-fill-layer');
                if (!fill) return;

                const barRect = bar.getBoundingClientRect();
                const fillRect = fill.getBoundingClientRect();
                const edgeX = Math.max(barRect.left, fillRect.right);
                const edgeY = barRect.top + barRect.height / 2;
                const dropletColor = bar.dataset.dropletColor || 'rgba(220,38,38,0.92)';

                for (let i = 0; i < 5; i++) {
                    const size = 3 + Math.random() * 3.5;
                    const d = document.createElement('div');
                    // Teardrop shape (asymmetric radius, rotated) reads as a blood/ichor
                    // drip rather than a bright glittery dot.
                    const rot = Math.round(Math.random() * 360);
                    d.style.cssText = `position:fixed; left:${edgeX}px; top:${edgeY}px; width:${size}px; height:${size}px; border-radius:65% 65% 65% 0; transform: rotate(${rot}deg); background:${dropletColor}; pointer-events:none; z-index:250; box-shadow:0 0 3px rgba(0,0,0,0.5);`;
                    document.body.appendChild(d);

                    const angle = (Math.random() * Math.PI) - (Math.PI / 2);
                    const dist = 10 + Math.random() * 18;
                    const dx = Math.cos(angle) * dist;
                    const dy = -Math.abs(Math.sin(angle) * dist) - 3;

                    const anim = d.animate([
                        { transform: `translate(0px, 0px) rotate(${rot}deg)`, opacity: 0.95 },
                        { transform: `translate(${dx}px, ${dy + 26}px) rotate(${rot}deg)`, opacity: 0 }
                    ], { duration: 450 + Math.random() * 200, easing: 'cubic-bezier(0.35,0.6,0.4,1)', fill: 'forwards' });
                    anim.onfinish = () => d.remove();
                    setTimeout(() => { if (d.parentNode) d.remove(); }, 850);
                }
            },
            // Odwrotność hit(): zamiast krwi tryskającej NA ZEWNĄTRZ z krawędzi ubytku,
            // szmaragdowe iskry unoszą się DO GÓRY znad paska, a nad nim wypływa
            // liczba "+ilość" - wizualny sygnał leczenia (mikstura HP/Mana poza walką).
            heal(barId, amount) {
                const bar = document.getElementById(barId);
                if (!bar) return;

                bar.classList.remove('hp-bar-heal-glow');
                void bar.offsetWidth;
                bar.classList.add('hp-bar-heal-glow');

                const barRect = bar.getBoundingClientRect();

                if (amount) {
                    const txt = document.createElement('div');
                    txt.textContent = `+${Math.round(amount)}`;
                    txt.style.cssText = `position:fixed; left:${barRect.left + barRect.width / 2}px; top:${barRect.top}px; transform: translate(-50%, 0); color:#34d399; font-weight:900; font-size:13px; text-shadow:0 1px 3px rgba(0,0,0,0.9); pointer-events:none; z-index:260;`;
                    document.body.appendChild(txt);
                    const textAnim = txt.animate([
                        { transform: 'translate(-50%, 0px)', opacity: 1 },
                        { transform: 'translate(-50%, -30px)', opacity: 0 }
                    ], { duration: 900, easing: 'cubic-bezier(0.2,0.8,0.3,1)', fill: 'forwards' });
                    textAnim.onfinish = () => txt.remove();
                    setTimeout(() => { if (txt.parentNode) txt.remove(); }, 1000);
                }

                for (let i = 0; i < 6; i++) {
                    const size = 3 + Math.random() * 3;
                    const d = document.createElement('div');
                    const startX = barRect.left + Math.random() * barRect.width;
                    const startY = barRect.top + barRect.height / 2;
                    d.style.cssText = `position:fixed; left:${startX}px; top:${startY}px; width:${size}px; height:${size}px; border-radius:50%; background:rgba(52,211,153,0.95); pointer-events:none; z-index:250; box-shadow:0 0 4px rgba(16,185,129,0.8);`;
                    document.body.appendChild(d);

                    const dx = (Math.random() - 0.5) * 14;
                    const dy = -(14 + Math.random() * 16);

                    const anim = d.animate([
                        { transform: 'translate(0px, 0px)', opacity: 0.95 },
                        { transform: `translate(${dx}px, ${dy}px)`, opacity: 0 }
                    ], { duration: 500 + Math.random() * 250, easing: 'cubic-bezier(0.2,0.8,0.3,1)', fill: 'forwards' });
                    anim.onfinish = () => d.remove();
                    setTimeout(() => { if (d.parentNode) d.remove(); }, 900);
                }
            }
        };
    </script>
@endonce

<div id="{{ $id }}" data-droplet-color="{{ $dropletColor }}"
    class="relative {{ $height }} w-full rounded-full bg-black/80 ring-1 {{ $ringClass }} shadow-inner overflow-hidden">
    <div class="hp-chip-layer absolute inset-y-0.5 left-0.5 rounded-full bg-slate-300/80" style="width: {{ $percent }}%"></div>
    <div class="hp-fill-layer {{ $liquid ? 'liquid-fx' : '' }} absolute inset-y-0.5 left-0.5 rounded-full bg-gradient-to-r {{ $gradientClass }} {{ $glowShadow }}" style="width: {{ $percent }}%"></div>
</div>
