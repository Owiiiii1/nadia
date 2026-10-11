import VisitsChart from '@/Components/VisitsChart';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Dashboard({ leads = 0, visitors = 0, conversion = 0, chats = 0, series = [], recent = [] }) {
    const { locale = 'en' } = usePage().props;
    const text = {
        en: {
            title: 'Dashboard',
            leads: 'Leads',
            visitors: 'Visitors',
            conversion: 'Conversion',
            chats: 'Chats',
            last30: 'last 30 days',
            visits: 'Visits',
            pageviews: 'Pageviews',
            recent: 'Latest leads',
            empty: 'No leads yet',
        },
        ru: {
            title: 'Дашборд',
            leads: 'Заявки',
            visitors: 'Посетители',
            conversion: 'Конверсия',
            chats: 'Чаты',
            last30: 'за 30 дней',
            visits: 'Визиты',
            pageviews: 'Просмотры',
            recent: 'Последние заявки',
            empty: 'Заявок пока нет',
        },
        uk: {
            title: 'Дашборд',
            leads: 'Заявки',
            visitors: 'Відвідувачі',
            conversion: 'Конверсія',
            chats: 'Чати',
            last30: 'за 30 днів',
            visits: 'Візити',
            pageviews: 'Перегляди',
            recent: 'Останні заявки',
            empty: 'Заявок поки немає',
        },
    };
    const t = text[locale] ?? text.en;
    const cards = [
        { label: t.leads, value: String(leads) },
        { label: t.visitors, value: String(visitors) },
        { label: t.conversion, value: `${Number(conversion).toFixed(1)}%` },
        { label: t.chats, value: String(chats) },
    ];

    return (
        <AdminLayout title={t.title}>
            <Head title="Dashboard" />
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {cards.map((card) => (
                    <div key={card.label} className="app-widget p-4">
                        <p className="text-sm text-slate-500">{card.label}</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">{card.value}</p>
                        <p className="mt-1 text-xs text-slate-400">{t.last30}</p>
                    </div>
                ))}
            </div>
            <div className="app-widget mt-4 p-4">
                <VisitsChart series={series} visitorsLabel={t.visits} pageviewsLabel={t.pageviews} />
            </div>
            <div className="app-widget mt-4 p-4">
                <h2 className="text-sm font-medium text-slate-900">{t.recent}</h2>
                {recent.length === 0 ? (
                    <p className="mt-3 text-sm text-slate-500">{t.empty}</p>
                ) : (
                    <ul className="mt-3 divide-y divide-slate-100">
                        {recent.map((lead) => (
                            <li key={lead.id} className="flex items-center justify-between py-2 text-sm">
                                <Link href={`/leads/${lead.id}`} className="font-medium text-slate-900 hover:underline">
                                    {lead.name}
                                </Link>
                                <span className="text-slate-500">{lead.created_at}</span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AdminLayout>
    );
}
