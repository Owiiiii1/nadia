import VisitsChart from '@/Components/VisitsChart';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const periods = ['today', '7d', '30d', '90d', 'custom'];

export default function AnalyticsIndex({ filters, empty, cards = [], series = [], referrers = [], utm = {}, devices = [], browsers = [], oses = [] }) {
    const { locale = 'en' } = usePage().props;
    const text = {
        en: {
            title: 'Analytics',
            today: 'Today', '7d': '7d', '30d': '30d', '90d': '90d', custom: 'Custom',
            from: 'From', to: 'To', apply: 'Apply',
            empty: 'No data for this period yet',
            visitors: 'Visitors', sessions: 'Sessions', pageviews: 'Pageviews', leads: 'Leads',
            conversion: 'Conversion', chat_opens: 'Chat opens', chat_leads: 'Chat leads',
            referrers: 'Referrers', source: 'UTM source', medium: 'UTM medium', campaign: 'UTM campaign',
            devices: 'Devices', browsers: 'Browsers', os: 'OS', visits: 'Visits', direct: 'Direct / none',
        },
        ru: {
            title: 'Аналитика',
            today: 'Сегодня', '7d': '7д', '30d': '30д', '90d': '90д', custom: 'Свой',
            from: 'С', to: 'По', apply: 'Показать',
            empty: 'За этот период данных ещё нет',
            visitors: 'Посетители', sessions: 'Сессии', pageviews: 'Просмотры', leads: 'Заявки',
            conversion: 'Конверсия', chat_opens: 'Открытия чата', chat_leads: 'Заявки из чата',
            referrers: 'Источники', source: 'UTM source', medium: 'UTM medium', campaign: 'UTM campaign',
            devices: 'Устройства', browsers: 'Браузеры', os: 'ОС', visits: 'Визиты', direct: 'Прямой / нет',
        },
        uk: {
            title: 'Аналітика',
            today: 'Сьогодні', '7d': '7д', '30d': '30д', '90d': '90д', custom: 'Свій',
            from: 'З', to: 'По', apply: 'Показати',
            empty: 'За цей період даних ще немає',
            visitors: 'Відвідувачі', sessions: 'Сесії', pageviews: 'Перегляди', leads: 'Заявки',
            conversion: 'Конверсія', chat_opens: 'Відкриття чату', chat_leads: 'Заявки з чату',
            devices: 'Пристрої', browsers: 'Браузери', os: 'ОС', visits: 'Візити', direct: 'Прямий / немає',
        },
    };
    const t = text[locale] ?? text.en;
    const [from, setFrom] = useState(filters?.from ?? '');
    const [to, setTo] = useState(filters?.to ?? '');

    function openPeriod(period) {
        router.get('/analytics', { period }, { preserveState: true });
    }

    function labelOf(row) {
        return row.label === 'Direct / none' ? t.direct : row.label;
    }

    function delta(value) {
        if (value === null || value === undefined) {
            return '—';
        }

        const number = Number(value);
        const sign = number > 0 ? '▲ ' : number < 0 ? '▼ ' : '';

        return `${sign}${number.toFixed(1)}%`;
    }

    function cardValue(card) {
        if (card.key === 'conversion') {
            return `${Number(card.value).toFixed(1)}%`;
        }

        return String(card.value);
    }

    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />
            <div className="mb-4 flex flex-wrap items-end gap-2">
                {periods.map((period) => (
                    <button
                        key={period}
                        type="button"
                        onClick={() => openPeriod(period)}
                        className={`rounded-md px-3 py-1.5 text-sm ${filters?.period === period ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200'}`}
                    >
                        {t[period]}
                    </button>
                ))}
                <form
                    className="flex flex-wrap items-end gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get('/analytics', { period: 'custom', from, to }, { preserveState: true });
                    }}
                >
                    <label className="text-xs text-slate-500">
                        {t.from}
                        <input type="date" value={from} onChange={(event) => setFrom(event.target.value)} className="mt-1 block rounded-md border-slate-200 text-sm" />
                    </label>
                    <label className="text-xs text-slate-500">
                        {t.to}
                        <input type="date" value={to} onChange={(event) => setTo(event.target.value)} className="mt-1 block rounded-md border-slate-200 text-sm" />
                    </label>
                    <button type="submit" className="rounded-md bg-white px-3 py-1.5 text-sm text-slate-700 ring-1 ring-slate-200">
                        {t.apply}
                    </button>
                </form>
            </div>

            {empty ? (
                <div className="app-widget p-4">
                    <p className="text-sm text-slate-700">{t.empty}</p>
                </div>
            ) : (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {cards.map((card) => (
                            <div key={card.key} className="app-widget p-4">
                                <p className="text-sm text-slate-500">{t[card.key]}</p>
                                <p className="mt-2 text-2xl font-semibold text-slate-900">{cardValue(card)}</p>
                                <p className="mt-1 text-xs text-slate-400">{delta(card.delta)}</p>
                            </div>
                        ))}
                    </div>
                    <div className="app-widget mt-4 p-4">
                        <VisitsChart series={series} visitorsLabel={t.visitors} pageviewsLabel={t.pageviews} />
                    </div>
                    <div className="mt-4 grid gap-4 lg:grid-cols-2">
                        <RankTable title={t.referrers} rows={referrers} visits={t.visits} leads={t.leads} labelOf={labelOf} />
                        <RankTable title={t.source} rows={utm.source ?? []} visits={t.visits} leads={t.leads} />
                        <RankTable title={t.medium} rows={utm.medium ?? []} visits={t.visits} leads={t.leads} />
                        <RankTable title={t.campaign} rows={utm.campaign ?? []} visits={t.visits} leads={t.leads} />
                        <SimpleTable title={t.devices} rows={devices} visits={t.visits} />
                        <SimpleTable title={t.browsers} rows={browsers} visits={t.visits} />
                        <SimpleTable title={t.os} rows={oses} visits={t.visits} />
                    </div>
                </>
            )}
        </AdminLayout>
    );
}

function RankTable({ title, rows, visits, leads, labelOf = (row) => row.label }) {
    return (
        <div className="app-widget p-4">
            <h2 className="text-sm font-medium text-slate-900">{title}</h2>
            <table className="mt-3 w-full text-left text-sm">
                <thead>
                    <tr className="text-slate-400">
                        <th className="py-1 font-normal" />
                        <th className="py-1 text-right font-normal">{visits}</th>
                        <th className="py-1 text-right font-normal">{leads}</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr key={row.label} className="border-t border-slate-100">
                            <td className="py-1.5">{labelOf(row)}</td>
                            <td className="py-1.5 text-right">{row.visits}</td>
                            <td className="py-1.5 text-right">{row.leads}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function SimpleTable({ title, rows, visits }) {
    return (
        <div className="app-widget p-4">
            <h2 className="text-sm font-medium text-slate-900">{title}</h2>
            <table className="mt-3 w-full text-left text-sm">
                <tbody>
                    {rows.map((row) => (
                        <tr key={row.label} className="border-t border-slate-100">
                            <td className="py-1.5">{row.label}</td>
                            <td className="py-1.5 text-right">{row.visits} {visits}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
