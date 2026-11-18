import { createReactiveApexComponent, formatRupiahAxis, formatRupiahFull } from './owner-dashboard-charts';

const FONT_FAMILY = '"Instrument Sans", ui-sans-serif, system-ui, sans-serif';
const BRAND_GREEN = '#1B5E43';
const MUTED_GREY = '#A1A1AA';

function toNumbers(list) {
    return Array.isArray(list) ? list.map((value) => Number(value) || 0) : [];
}

function buildRevenueOptions(payload) {
    const categories = Array.isArray(payload?.categories) ? payload.categories : [];
    const revenues = toNumbers(payload?.revenues);
    const maxRevenue = Math.max(0, ...revenues);
    const lastIndex = revenues.length - 1;

    return {
        chart: {
            type: 'line',
            height: 300,
            fontFamily: FONT_FAMILY,
            toolbar: { show: false },
            zoom: { enabled: false },
            parentHeightOffset: 0,
            animations: { enabled: true, speed: 450 },
        },
        series: [{ name: 'Pendapatan', data: revenues }],
        colors: [BRAND_GREEN],
        stroke: { width: 3, curve: 'smooth' },
        markers: {
            size: 3.5,
            colors: [BRAND_GREEN],
            strokeColors: '#FFFFFF',
            strokeWidth: 2,
            hover: { size: 7 },
            discrete: [
                { seriesIndex: 0, dataPointIndex: lastIndex - 1, fillColor: MUTED_GREY, strokeColor: '#FFFFFF', size: 6 },
                { seriesIndex: 0, dataPointIndex: lastIndex, fillColor: BRAND_GREEN, strokeColor: '#FFFFFF', size: 7 },
            ],
        },
        dataLabels: { enabled: false },
        grid: {
            borderColor: '#F4F4F5',
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: { top: 8, right: 8, bottom: 4, left: 8 },
        },
        xaxis: {
            categories,
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false },
            labels: {
                rotate: -35,
                hideOverlappingLabels: true,
                style: { colors: '#71717A', fontSize: '12px', fontWeight: 500 },
            },
        },
        yaxis: {
            min: 0,
            max: maxRevenue === 0 ? 1_000_000 : undefined,
            tickAmount: 4,
            forceNiceScale: maxRevenue > 0,
            labels: {
                style: { colors: '#71717A', fontSize: '12px' },
                formatter: (value) => formatRupiahAxis(value),
            },
        },
        legend: { show: false },
        tooltip: {
            theme: 'light',
            y: { formatter: (value) => formatRupiahFull(value) },
        },
    };
}

function buildPieOptions(payload) {
    const labels = Array.isArray(payload?.labels) ? payload.labels : [];
    const values = toNumbers(payload?.values);
    const colors = Array.isArray(payload?.colors) ? payload.colors : [];
    const total = Number(payload?.total) || 0;
    const hasData = Boolean(payload?.has_data) && total > 0;

    return {
        chart: {
            type: 'donut',
            height: 220,
            width: '100%',
            fontFamily: FONT_FAMILY,
            animations: { enabled: hasData, speed: 400 },
        },
        series: hasData ? values : [1],
        labels: hasData ? labels : ['Belum ada data'],
        colors: hasData ? colors : ['#E4E4E7'],
        stroke: { width: hasData ? 2 : 0, colors: ['#FFFFFF'] },
        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: {
            enabled: hasData,
            theme: 'light',
            y: { formatter: (value) => `${Math.round(Number(value) || 0)} mitra` },
        },
        states: hasData
            ? {}
            : { hover: { filter: { type: 'none' } }, active: { filter: { type: 'none' } } },
        plotOptions: {
            pie: {
                expandOnClick: false,
                donut: {
                    size: '68%',
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
                            fontSize: '20px',
                            fontWeight: 800,
                            color: '#18181B',
                            offsetY: 4,
                            formatter: (value) => `${Math.round(Number(value) || 0)}`,
                        },
                        total: {
                            show: true,
                            showAlways: true,
                            label: 'Total',
                            fontSize: '11px',
                            fontWeight: 500,
                            color: '#71717A',
                            formatter: () => `${hasData ? total : 0}`,
                        },
                    },
                },
            },
        },
    };
}

function buildBarOptions(payload) {
    const labels = Array.isArray(payload?.labels) ? payload.labels : [];
    const values = toNumbers(payload?.values);
    const colors = Array.isArray(payload?.colors) ? payload.colors : [];
    const maxValue = Math.max(0, ...values);

    return {
        chart: {
            type: 'bar',
            height: 260,
            fontFamily: FONT_FAMILY,
            toolbar: { show: false },
            parentHeightOffset: 0,
            animations: { enabled: true, speed: 400 },
        },
        series: [{ name: payload?.series_name || 'Jumlah', data: values }],
        colors,
        plotOptions: {
            bar: {
                distributed: true,
                columnWidth: '52%',
                borderRadius: 8,
                borderRadiusApplication: 'end',
                dataLabels: { position: 'top' },
            },
        },
        dataLabels: {
            enabled: true,
            offsetY: -22,
            style: { fontSize: '12px', fontWeight: 700, colors: ['#3F3F46'] },
            formatter: (value) => `${Math.round(Number(value) || 0)}`,
        },
        grid: {
            borderColor: '#F4F4F5',
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: { top: 16, right: 8, bottom: 0, left: 8 },
        },
        xaxis: {
            categories: labels,
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false },
            labels: {
                style: { colors: '#71717A', fontSize: '12px', fontWeight: 500 },
            },
        },
        yaxis: {
            min: 0,
            max: maxValue === 0 ? 4 : Math.max(4, Math.ceil(maxValue * 1.2)),
            tickAmount: 4,
            forceNiceScale: maxValue > 0,
            labels: {
                style: { colors: '#71717A', fontSize: '12px' },
                formatter: (value) => `${Math.round(Number(value) || 0)}`,
            },
        },
        legend: { show: false },
        tooltip: {
            theme: 'light',
            y: { formatter: (value) => `${Math.round(Number(value) || 0)}` },
        },
    };
}

function registerSuperAdminDashboardCharts() {
    if (!window.Alpine || window.__pawpawSuperAdminDashboardChartsRegistered) {
        return;
    }

    window.__pawpawSuperAdminDashboardChartsRegistered = true;

    window.Alpine.data('superAdminRevenueChart', createReactiveApexComponent(buildRevenueOptions));
    window.Alpine.data('superAdminPieChart', createReactiveApexComponent(buildPieOptions));
    window.Alpine.data('superAdminBarChart', createReactiveApexComponent(buildBarOptions));
}

document.addEventListener('alpine:init', registerSuperAdminDashboardCharts);

if (typeof window !== 'undefined' && window.Alpine) {
    registerSuperAdminDashboardCharts();
}
