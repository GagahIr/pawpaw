let apexChartsModulePromise = null;

function loadApexCharts() {
    if (!apexChartsModulePromise) {
        apexChartsModulePromise = import('apexcharts').then((mod) => mod.default || mod);
    }

    return apexChartsModulePromise;
}

function formatRupiahFull(value) {
    const numeric = Math.round(Number(value) || 0);

    return 'Rp ' + numeric.toLocaleString('id-ID');
}

function formatRupiahAxis(value) {
    const numeric = Number(value) || 0;

    if (numeric >= 1_000_000_000) {
        const billions = numeric / 1_000_000_000;

        return (Number.isInteger(billions) ? billions.toString() : billions.toFixed(1).replace('.', ',')) + ' M';
    }

    if (numeric >= 1_000_000) {
        const millions = numeric / 1_000_000;

        return (Number.isInteger(millions) ? millions.toString() : millions.toFixed(1).replace('.', ',')) + ' jt';
    }

    if (numeric >= 1_000) {
        const thousands = Math.round(numeric / 1_000);

        return thousands.toLocaleString('id-ID') + ' rb';
    }

    return numeric.toLocaleString('id-ID');
}

function buildTrendOptions(payload) {
    const categories = Array.isArray(payload?.categories) ? payload.categories : [];
    const revenues = Array.isArray(payload?.revenues) ? payload.revenues.map((v) => Number(v) || 0) : [];
    const orders = Array.isArray(payload?.orders) ? payload.orders.map((v) => Number(v) || 0) : [];

    const maxRevenue = Math.max(0, ...revenues);
    const maxOrders = Math.max(0, ...orders);

    const isDense = categories.length > 12;

    return {
        chart: {
            type: 'line',
            height: 320,
            fontFamily: '"Instrument Sans", ui-sans-serif, system-ui, sans-serif',
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: {
                enabled: true,
                speed: 450,
            },
            parentHeightOffset: 0,
        },
        series: [
            {
                name: 'Pendapatan',
                type: 'column',
                data: revenues,
            },
            {
                name: 'Pesanan selesai',
                type: 'line',
                data: orders,
            },
        ],
        colors: ['#DCEBE1', '#1B5E43'],
        stroke: {
            width: [0, 3],
            curve: 'smooth',
        },
        plotOptions: {
            bar: {
                columnWidth: isDense ? '58%' : '38%',
                borderRadius: 8,
                borderRadiusApplication: 'end',
            },
        },
        markers: {
            size: [0, isDense ? 3.5 : 5],
            colors: ['#DCEBE1', '#1B5E43'],
            strokeColors: '#FFFFFF',
            strokeWidth: 2,
            hover: {
                size: 7,
            },
        },
        dataLabels: {
            enabled: false,
        },
        grid: {
            borderColor: '#F4F4F5',
            strokeDashArray: 0,
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: {
                top: 8,
                right: 8,
                bottom: 4,
                left: 8,
            },
        },
        xaxis: {
            categories,
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false },
            labels: {
                rotate: isDense ? -35 : 0,
                hideOverlappingLabels: true,
                style: {
                    colors: '#71717A',
                    fontSize: '12px',
                    fontWeight: 500,
                },
            },
        },
        yaxis: [
            {
                seriesName: 'Pendapatan',
                min: 0,
                max: maxRevenue === 0 ? 4_000_000 : undefined,
                tickAmount: 4,
                forceNiceScale: maxRevenue > 0,
                title: {
                    text: 'Pendapatan (Rp)',
                    style: {
                        color: '#71717A',
                        fontSize: '11px',
                        fontWeight: 600,
                    },
                },
                labels: {
                    style: {
                        colors: '#71717A',
                        fontSize: '12px',
                    },
                    formatter: (val) => formatRupiahAxis(val),
                },
            },
            {
                seriesName: 'Pesanan selesai',
                opposite: true,
                min: 0,
                max: maxOrders === 0 ? 4 : Math.max(4, Math.ceil(maxOrders * 1.2)),
                tickAmount: 4,
                forceNiceScale: maxOrders > 0,
                title: {
                    text: 'Pesanan',
                    style: {
                        color: '#71717A',
                        fontSize: '11px',
                        fontWeight: 600,
                    },
                },
                labels: {
                    style: {
                        colors: '#71717A',
                        fontSize: '12px',
                    },
                    formatter: (val) => Math.round(val).toString(),
                },
            },
        ],
        legend: {
            show: true,
            position: 'bottom',
            horizontalAlign: 'left',
            fontSize: '12px',
            fontWeight: 500,
            labels: {
                colors: '#52525B',
            },
            markers: {
                size: 6,
                shape: 'circle',
                offsetX: -2,
            },
            itemMargin: {
                horizontal: 14,
                vertical: 6,
            },
        },
        tooltip: {
            shared: true,
            intersect: false,
            theme: 'light',
            y: {
                formatter: (value, { seriesIndex }) => {
                    if (seriesIndex === 0) {
                        return formatRupiahFull(value);
                    }

                    return `${Math.round(Number(value) || 0)} pesanan`;
                },
            },
        },
        states: {
            hover: {
                filter: {
                    type: 'darken',
                    value: 0.94,
                },
            },
        },
    };
}

function buildContributionOptions(payload) {
    const hasData = Boolean(payload?.has_data);
    const items = Array.isArray(payload?.items) ? payload.items : [];
    const totalRevenue = Number(payload?.total_revenue) || 0;

    if (!hasData || totalRevenue <= 0) {
        return {
            chart: {
                type: 'donut',
                height: 205,
                width: '100%',
                fontFamily: '"Instrument Sans", ui-sans-serif, system-ui, sans-serif',
                sparkline: { enabled: false },
                animations: { enabled: false },
            },
            series: [1],
            labels: ['Belum ada transaksi'],
            colors: ['#E4E4E7'],
            stroke: {
                width: 0,
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                show: false,
            },
            tooltip: {
                enabled: false,
            },
            states: {
                hover: { filter: { type: 'none' } },
                active: { filter: { type: 'none' } },
            },
            plotOptions: {
                pie: {
                    expandOnClick: false,
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '11px',
                                fontWeight: 500,
                                color: '#71717A',
                                offsetY: -6,
                            },
                            value: {
                                show: true,
                                fontSize: '15px',
                                fontWeight: 800,
                                color: '#18181B',
                                offsetY: 4,
                                formatter: () => 'Rp 0',
                            },
                            total: {
                                show: true,
                                showAlways: true,
                                label: 'Total',
                                fontSize: '11px',
                                fontWeight: 500,
                                color: '#71717A',
                                formatter: () => 'Rp 0',
                            },
                        },
                    },
                },
            },
        };
    }

    const series = items.map((item) => Number(item.revenue) || 0);
    const labels = items.map((item) => item.label);
    const colors = items.map((item) => item.color);

    return {
        chart: {
            type: 'donut',
            height: 205,
            width: '100%',
            fontFamily: '"Instrument Sans", ui-sans-serif, system-ui, sans-serif',
            animations: {
                enabled: true,
                speed: 400,
            },
        },
        series,
        labels,
        colors,
        stroke: {
            width: 2,
            colors: ['#FFFFFF'],
        },
        dataLabels: {
            enabled: false,
        },
        legend: {
            show: false,
        },
        tooltip: {
            enabled: true,
            theme: 'light',
            y: {
                formatter: (val) => {
                    const pct = totalRevenue > 0 ? ((Number(val) / totalRevenue) * 100).toFixed(1).replace('.0', '').replace('.', ',') : '0';

                    return `${formatRupiahFull(val)} (${pct}%)`;
                },
            },
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '11px',
                            fontWeight: 500,
                            color: '#71717A',
                            offsetY: -5,
                        },
                        value: {
                            show: true,
                            fontSize: '18px',
                            fontWeight: 800,
                            color: '#18181B',
                            offsetY: 5,
                            formatter: (val) => {
                                const numeric = Number(val);
                                if (!Number.isFinite(numeric) || totalRevenue <= 0) {
                                    return '100%';
                                }
                                const pct = ((numeric / totalRevenue) * 100).toFixed(1).replace('.0', '').replace('.', ',');

                                return `${pct}%`;
                            },
                        },
                        total: {
                            show: true,
                            showAlways: true,
                            label: 'Total',
                            fontSize: '11px',
                            fontWeight: 500,
                            color: '#71717A',
                            formatter: () => '100%',
                        },
                    },
                },
            },
        },
    };
}

function createReactiveApexComponent(optionsBuilder) {
    return () => ({
        chart: null,
        observer: null,
        lastPayloadRaw: null,
        isDestroyed: false,

        async initChart(initialRaw) {
            this.isDestroyed = false;
            await this.renderOrUpdate(initialRaw);

            this.observer = new MutationObserver(() => {
                this.renderOrUpdate(this.$el.dataset.payload);
            });

            this.observer.observe(this.$el, {
                attributes: true,
                attributeFilter: ['data-payload'],
            });
        },

        async renderOrUpdate(rawPayload) {
            if (!rawPayload || rawPayload === this.lastPayloadRaw || this.isDestroyed) {
                return;
            }

            let parsed;
            try {
                parsed = JSON.parse(rawPayload);
            } catch {
                return;
            }

            const ApexCharts = await loadApexCharts();
            if (this.isDestroyed || !this.$refs.canvas) {
                return;
            }

            this.lastPayloadRaw = rawPayload;
            const options = optionsBuilder(parsed);

            if (this.chart) {
                try {
                    this.chart.destroy();
                } catch {
                    // Ignore cleanup errors during rapid updates
                }
                this.chart = null;
            }

            this.$refs.canvas.innerHTML = '';
            this.chart = new ApexCharts(this.$refs.canvas, options);
            await this.chart.render();
        },

        destroy() {
            this.isDestroyed = true;
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
            if (this.chart) {
                try {
                    this.chart.destroy();
                } catch {
                    // Ignore teardown errors
                }
                this.chart = null;
            }
        },
    });
}

function registerOwnerDashboardCharts() {
    if (!window.Alpine || window.__pawpawOwnerDashboardChartsRegistered) {
        return;
    }

    window.__pawpawOwnerDashboardChartsRegistered = true;

    window.Alpine.data('ownerTrendChart', createReactiveApexComponent(buildTrendOptions));
    window.Alpine.data('ownerContributionChart', createReactiveApexComponent(buildContributionOptions));
}

document.addEventListener('alpine:init', registerOwnerDashboardCharts);

if (typeof window !== 'undefined' && window.Alpine) {
    registerOwnerDashboardCharts();
}
