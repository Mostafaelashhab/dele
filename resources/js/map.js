import L from 'leaflet';

/**
 * The platform's map layer.
 *
 * Leaflet is bundled rather than pulled from a CDN so the maps work on shared
 * hosting with no third-party script request, and tiles come from
 * OpenStreetMap, which needs no key and no billing account.
 *
 * Markers are built from HTML so they inherit the interface's typography and
 * colours instead of shipping a second set of icon images.
 */

const BANHA_CENTRE = [30.4599, 31.1837];

const TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

/**
 * The credit line.
 *
 * Kept short, but kept. OpenStreetMap data is ODbL-licensed, so attribution
 * is a condition of using it rather than a courtesy, and the public tile
 * servers block origins that remove it. The CSS shrinks it to a discreet
 * corner mark; what it must not do is disappear.
 *
 * A map with no basemap at all — the 'schematic' style — displays no OSM data
 * and therefore carries no credit, which is why the landing page uses it.
 */
const TILE_ATTRIBUTION =
    '<a href="https://www.openstreetmap.org/copyright" rel="noreferrer" target="_blank">&copy; OSM</a>';

/**
 * Builds a marker whose look comes from CSS classes rather than an image.
 */
function divMarker({ variant, label, size = 30, pulse = false }) {
    const classes = ['map-marker', `map-marker-${variant}`];

    if (pulse) {
        classes.push('map-marker-pulse');
    }

    return L.divIcon({
        className: '',
        html: `<div class="${classes.join(' ')}" style="width:${size}px;height:${size}px;position:relative">
                 <span style="font-size:${Math.round(size * 0.42)}px;font-weight:700;line-height:1">${label ?? ''}</span>
               </div>`,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
        popupAnchor: [0, -(size / 2)],
    });
}

/**
 * A zone pin. Grows and gains a ring when it is the one being pointed at.
 */
function zonePinIcon(tone = 'near', active = false) {
    const size = active ? 18 : 12;
    const colour = tone === 'far' ? '#f95c13' : '#1e46d6';

    return L.divIcon({
        className: '',
        html: `<span style="
            display:block;width:${size}px;height:${size}px;border-radius:9999px;
            background:${colour};
            box-shadow:0 0 0 ${active ? 5 : 3}px ${colour}${active ? '40' : '26'},
                       0 1px 3px rgb(13 17 23 / 35%);
            transition:width .15s,height .15s;
        "></span>`,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
    });
}

export class BanhaMap {
    constructor(element, options = {}) {
        this.element = element;
        this.options = options;

        // 'schematic' draws the zones on a brand ground with no basemap: no
        // tile requests, no third-party data, and a cleaner read when the
        // question is coverage rather than streets.
        this.style = options.style ?? 'muted';
        this.isSchematic = this.style === 'schematic';

        element.classList.add(`map-style-${this.style}`);

        this.map = L.map(element, {
            center: options.center ?? BANHA_CENTRE,
            zoom: options.zoom ?? 13,
            zoomControl: options.zoomControl ?? ! this.isSchematic,
            scrollWheelZoom: options.scrollWheelZoom ?? false,
            attributionControl: ! this.isSchematic,
            // A diagram is not a map to be explored, so its viewport is fixed.
            dragging: options.dragging ?? ! this.isSchematic,
            doubleClickZoom: ! this.isSchematic,
            touchZoom: ! this.isSchematic,
            keyboard: ! this.isSchematic,
        });

        if (! this.isSchematic) {
            L.tileLayer(TILE_URL, {
                attribution: TILE_ATTRIBUTION,
                maxZoom: 19,
                // Tiles come straight from OSM; leaving the retina option off
                // halves the requests on the phones riders actually carry.
                detectRetina: false,
            }).addTo(this.map);
        }

        this.markers = new Map();
        this.shapes = [];
        this.routeLine = null;

        // Leaflet mis-measures a container that was hidden or resized while it
        // was off screen, which is exactly what happens inside a Livewire
        // update or a tab. Re-measuring on resize keeps it honest.
        this.resizeObserver = new ResizeObserver(() => this.map.invalidateSize());
        this.resizeObserver.observe(element);
    }

    /**
     * Replace every marker in one pass.
     *
     * Livewire re-renders send the whole set, so diffing by key keeps markers
     * that have not moved rather than tearing the layer down and rebuilding it,
     * which would make a moving rider flicker on every poll.
     */
    setMarkers(points = []) {
        const seen = new Set();

        points.forEach((point) => {
            if (!Number.isFinite(point.lat) || !Number.isFinite(point.lng)) {
                return;
            }

            seen.add(point.key);

            const existing = this.markers.get(point.key);

            if (existing) {
                existing.setLatLng([point.lat, point.lng]);

                if (point.popup) {
                    existing.setPopupContent(point.popup);
                }

                return;
            }

            const marker = L.marker([point.lat, point.lng], {
                icon: divMarker({
                    variant: point.variant ?? 'rider',
                    label: point.label ?? '',
                    size: point.size ?? 30,
                    pulse: point.pulse ?? false,
                }),
                title: point.title ?? '',
                keyboard: true,
                alt: point.title ?? point.label ?? '',
            }).addTo(this.map);

            if (point.popup) {
                marker.bindPopup(point.popup);
            }

            if (point.url) {
                marker.on('click', () => {
                    window.Livewire?.navigate
                        ? window.Livewire.navigate(point.url)
                        : (window.location.href = point.url);
                });
            }

            this.markers.set(point.key, marker);
        });

        // Anything the server stopped sending has left the board.
        for (const [key, marker] of this.markers) {
            if (!seen.has(key)) {
                marker.remove();
                this.markers.delete(key);
            }
        }
    }

    /**
     * A straight leg between pickup and dropoff.
     *
     * Deliberately a straight line, not a fabricated road path: the platform
     * does not have real routing yet, and drawing an invented road would
     * misrepresent a distance the pricing engine only estimates.
     */
    setRoute(points = []) {
        if (this.routeLine) {
            this.routeLine.remove();
            this.routeLine = null;
        }

        const valid = points.filter((p) => Number.isFinite(p?.lat) && Number.isFinite(p?.lng));

        if (valid.length < 2) {
            return;
        }

        this.routeLine = L.polyline(
            valid.map((p) => [p.lat, p.lng]),
            { color: '#1e46d6', weight: 2, opacity: 0.55, dashArray: '5 6', lineCap: 'round' },
        ).addTo(this.map);
    }

    /**
     * Draw the operational zones as circles.
     *
     * Used where coverage itself is the subject — the zone editor — and the
     * overlap between neighbouring areas is information rather than noise.
     */
    setZones(zones = []) {
        this.clearZones();

        zones.forEach((zone) => {
            if (!Number.isFinite(zone.lat) || !Number.isFinite(zone.lng)) {
                return;
            }

            const inactive = zone.active === false;

            const circle = L.circle([zone.lat, zone.lng], {
                radius: zone.radius ?? 1500,
                color: inactive ? '#8492a6' : '#1e46d6',
                weight: 1.5,
                opacity: inactive ? 0.45 : 0.75,
                fillColor: inactive ? '#8492a6' : '#1e46d6',
                fillOpacity: inactive ? 0.04 : 0.08,
            }).addTo(this.map);

            if (zone.label) {
                circle.bindTooltip(zone.label, { direction: 'center', className: 'map-zone-label' });
            }

            this.shapes.push(circle);
        });
    }

    /**
     * Zones as pins that respond to a companion list.
     *
     * Drawing every catchment at once is what made the earlier coverage map
     * unreadable: the circles for a dense city centre merge into one blob and
     * bury their own labels. Here only pins are permanent, and a circle is
     * drawn for the single zone the reader is pointing at — so the shape of a
     * catchment is still available, one at a time, where it can be seen.
     */
    setZonePins(zones = [], { onHover } = {}) {
        this.clearZones();
        this.zonePins = new Map();
        this.zoneMeta = new Map();

        zones.forEach((zone) => {
            if (!Number.isFinite(zone.lat) || !Number.isFinite(zone.lng)) {
                return;
            }

            const marker = L.marker([zone.lat, zone.lng], {
                icon: zonePinIcon(zone.tone, false),
                riseOnHover: true,
                keyboard: true,
                alt: zone.label ?? '',
                title: zone.label ?? '',
            }).addTo(this.map);

            marker.bindTooltip(zone.label ?? '', {
                direction: 'top',
                offset: [0, -10],
                className: 'map-zone-label',
                opacity: 1,
            });

            if (onHover) {
                // Pointer and keyboard both drive the same highlight, so the
                // pairing works without a mouse.
                marker.on('mouseover focus', () => onHover(zone.id));
                marker.on('mouseout blur', () => onHover(null));
                marker.on('click', () => onHover(zone.id));
            }

            this.zonePins.set(zone.id, marker);
            this.zoneMeta.set(zone.id, zone);
            this.shapes.push(marker);
        });
    }

    /**
     * Bring one zone forward, or clear the highlight when given null.
     */
    highlightZone(zoneId) {
        if (!this.zonePins) {
            return;
        }

        if (this.activeCircle) {
            this.activeCircle.remove();
            this.activeCircle = null;
        }

        this.zonePins.forEach((marker, id) => {
            const zone = this.zoneMeta.get(id);
            const isActive = id === zoneId;

            marker.setIcon(zonePinIcon(zone?.tone, isActive));
            marker.setZIndexOffset(isActive ? 1000 : 0);

            if (isActive) {
                marker.openTooltip();
            } else {
                marker.closeTooltip();
            }
        });

        const zone = this.zoneMeta.get(zoneId);

        if (!zone) {
            return;
        }

        this.activeCircle = L.circle([zone.lat, zone.lng], {
            radius: zone.radius ?? 1500,
            color: '#1e46d6',
            weight: 2,
            opacity: 0.8,
            fillColor: '#1e46d6',
            fillOpacity: 0.1,
            interactive: false,
        }).addTo(this.map);

        // Bring the catchment into view without yanking the frame around.
        this.map.panInside(this.activeCircle.getBounds(), { padding: [28, 28], animate: true });
    }

    clearZones() {
        this.shapes.forEach((shape) => shape.remove());
        this.shapes = [];

        if (this.activeCircle) {
            this.activeCircle.remove();
            this.activeCircle = null;
        }

        this.zonePins = null;
        this.zoneMeta = null;
    }

    /**
     * Frame everything currently on the map.
     */
    fit({ padding = 48, maxZoom = 16 } = {}) {
        const layers = [...this.markers.values()];

        if (this.routeLine) {
            layers.push(this.routeLine);
        }

        layers.push(...this.shapes);

        if (layers.length === 0) {
            return;
        }

        const group = L.featureGroup(layers);
        const bounds = group.getBounds();

        if (!bounds.isValid()) {
            return;
        }

        this.map.fitBounds(bounds, { padding: [padding, padding], maxZoom });
    }

    /**
     * Let the operator drop a pin — used when a business is locating a customer
     * that has no saved coordinates.
     */
    onPick(callback) {
        this.map.on('click', (event) => {
            callback({
                lat: Number(event.latlng.lat.toFixed(6)),
                lng: Number(event.latlng.lng.toFixed(6)),
            });
        });
    }

    destroy() {
        this.resizeObserver?.disconnect();
        this.map.remove();
    }
}

/**
 * Alpine component backing the <x-ui.map> Blade component.
 *
 * Reads its data from the element and re-reads it whenever Livewire replaces
 * the payload, so a polled dashboard moves its markers without a page redraw.
 */
export function mapComponent() {
    return {
        instance: null,

        initMap(config = {}) {
            this.instance = new BanhaMap(this.$refs.canvas, config);

            this.render(config);

            if (config.pickable) {
                this.instance.onPick((point) => {
                    this.$dispatch('map-picked', point);
                });
            }

            // Re-render after every Livewire round trip: the payload lives in a
            // data attribute, so a poll that changes marker positions is picked
            // up without re-creating the map.
            this.$watch('payload', () => this.render(this.payload));
        },

        render(config) {
            if (!this.instance) {
                return;
            }

            if (config.zonePins?.length) {
                this.instance.setZonePins(config.zonePins, {
                    onHover: (id) => this.$dispatch('zone-hover', { id }),
                });
            } else {
                this.instance.setZones(config.zones ?? []);
            }
            this.instance.setMarkers(config.markers ?? []);
            this.instance.setRoute(config.route ?? []);

            if (config.fit !== false) {
                this.instance.fit({ maxZoom: config.maxZoom ?? 16 });
            }
        },

        highlight(zoneId) {
            this.instance?.highlightZone(zoneId);
        },

        destroy() {
            this.instance?.destroy();
            this.instance = null;
        },
    };
}

window.BanhaMap = BanhaMap;
window.mapComponent = mapComponent;
