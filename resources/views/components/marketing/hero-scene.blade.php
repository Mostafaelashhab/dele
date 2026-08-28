{{--
    The hero illustration: a delivery bike crossing the city.

    Drawn as inline SVG rather than shipped as an image so it stays sharp at
    any size, carries no extra request, and takes its colours from the theme —
    a raster of this would need a second file for light and dark and would
    still be a stock photograph of somebody else's city.

    Everything here is decorative: the page reads identically with it removed,
    so it is hidden from assistive technology entirely.
--}}
<div {{ $attributes->merge(['class' => 'pointer-events-none select-none']) }} aria-hidden="true">
    <svg viewBox="0 0 520 260" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-auto w-full">
        <defs>
            <linearGradient id="hero-road" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0%" stop-color="#f95c13" stop-opacity="0"/>
                <stop offset="45%" stop-color="#f95c13" stop-opacity=".85"/>
                <stop offset="100%" stop-color="#f95c13" stop-opacity="0"/>
            </linearGradient>
            <linearGradient id="hero-glow" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#f95c13" stop-opacity=".18"/>
                <stop offset="100%" stop-color="#f95c13" stop-opacity="0"/>
            </linearGradient>
        </defs>

        {{-- The city behind: rooftops and minarets, kept as flat blocks so
             they read as a skyline rather than competing with the rider. --}}
        <g opacity=".5">
            <rect x="24" y="126" width="34" height="70" rx="3" fill="#1a212b"/>
            <rect x="66" y="102" width="26" height="94" rx="3" fill="#212a36"/>
            <rect x="100" y="140" width="40" height="56" rx="3" fill="#1a212b"/>
            <rect x="380" y="118" width="30" height="78" rx="3" fill="#212a36"/>
            <rect x="418" y="138" width="46" height="58" rx="3" fill="#1a212b"/>
            <rect x="472" y="110" width="24" height="86" rx="3" fill="#212a36"/>

            {{-- A minaret, because this is Banha and not a generic skyline. --}}
            <rect x="150" y="84" width="12" height="112" rx="3" fill="#212a36"/>
            <path d="M156 68l7 16h-14l7-16z" fill="#2a3441"/>
            <rect x="336" y="96" width="10" height="100" rx="3" fill="#212a36"/>
            <path d="M341 82l6 14h-12l6-14z" fill="#2a3441"/>
        </g>

        {{-- Lit windows, a few only: a full grid would draw the eye. --}}
        <g fill="#f95c13" opacity=".28">
            <rect x="72" y="116" width="5" height="6" rx="1"/>
            <rect x="81" y="132" width="5" height="6" rx="1"/>
            <rect x="386" y="132" width="5" height="6" rx="1"/>
            <rect x="429" y="152" width="5" height="6" rx="1"/>
        </g>

        <rect x="0" y="150" width="520" height="60" fill="url(#hero-glow)"/>

        {{-- The road. --}}
        <path d="M0 196h520" stroke="url(#hero-road)" stroke-width="2"/>
        <g stroke="#3d4655" stroke-width="2" stroke-linecap="round" opacity=".5">
            <path d="M40 205h26M92 205h26M144 205h26M400 205h26M452 205h26"/>
        </g>

        {{-- The rider, centred and the only thing at full contrast. --}}
        <g transform="translate(196 118)">
            <path d="M18 66l14-30h30l6 14" stroke="#8492a6" stroke-width="3"
                  stroke-linecap="round" stroke-linejoin="round"/>

            {{-- The parcel on the back — the reason the whole page exists. --}}
            <rect x="6" y="24" width="30" height="26" rx="4" fill="#f95c13"/>
            <path d="M6 34h30M21 24v26" stroke="#0d1117" stroke-width="2" opacity=".35"/>

            <circle cx="46" cy="20" r="9" fill="#d5dae2"/>
            <path d="M40 14a8 8 0 0 1 13 2l-13 3z" fill="#2a3441"/>
            <path d="M44 30l10 6 12-2" stroke="#d5dae2" stroke-width="4"
                  stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M44 32l-2 20" stroke="#d5dae2" stroke-width="4" stroke-linecap="round"/>

            <circle cx="18" cy="66" r="12" fill="none" stroke="#d5dae2" stroke-width="3.5"/>
            <circle cx="76" cy="66" r="12" fill="none" stroke="#d5dae2" stroke-width="3.5"/>
            <circle cx="18" cy="66" r="3" fill="#8492a6"/>
            <circle cx="76" cy="66" r="3" fill="#8492a6"/>
            <path d="M64 52l8-8h8" stroke="#8492a6" stroke-width="3"
                  stroke-linecap="round" stroke-linejoin="round"/>
        </g>

        {{-- A dropped pin ahead of the rider: the destination. --}}
        <g transform="translate(346 96)" opacity=".9">
            <path d="M12 0a12 12 0 0 1 12 12c0 9-12 22-12 22S0 21 0 12A12 12 0 0 1 12 0z"
                  fill="#f95c13"/>
            <circle cx="12" cy="12" r="4.5" fill="#0d1117"/>
        </g>

        {{-- Motion, drawn as trailing lines rather than animated: this sits at
             the top of every page load and should not spend a frame budget. --}}
        <g stroke="#f95c13" stroke-width="2.5" stroke-linecap="round" opacity=".45">
            <path d="M150 150h30M138 164h22M158 178h26"/>
        </g>
    </svg>
</div>
