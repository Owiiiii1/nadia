import AdminLayout from '@/Layouts/AdminLayout';
import { Head, usePage } from '@inertiajs/react';

export default function Dashboard({ leadsCount = null }) {
    const { locale = 'en' } = usePage().props;
    const text = {
        en: {
            title: 'Dashboard',
            leads: 'Leads',
            visitors: 'Visitors',
            conversion: 'Conversion',
            chats: 'Chats',
            last30: 'last 30 days',
        },
        ru: {
            title: 'Дашборд',
            leads: 'Заявки',
            visitors: 'Визиты',
            conversion: 'Конверсия',
            chats: 'Чаты',
            last30: 'за 30 дней',
        },
        uk: {
            title: 'Дашборд',
            leads: 'Заявки',
            visitors: 'Візити',
            conversion: 'Конверсія',
            chats: 'Чати',
            last30: 'за 30 днів',
        },
    };
    const t = text[locale] ?? text.en;
    const cards = [
        { label: t.leads, value: leadsCount === null ? '—' : String(leadsCount), hint: t.last30 },
        { label: t.visitors, value: '—', hint: '' },
        { label: t.conversion, value: '—', hint: '' },
        { label: t.chats, value: '—', hint: '' },
    ];

    return (
        <AdminLayout title={t.title}>
            <Head title="Dashboard" />
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {cards.map((card) => (
                    <div key={card.label} className="app-widget p-4">
                        <p className="text-sm text-slate-500">{card.label}</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">{card.value}</p>
                        {card.hint ? <p className="mt-1 text-xs text-slate-400">{card.hint}</p> : null}
                    </div>
                ))}
            </div>
        </AdminLayout>
    );
}
