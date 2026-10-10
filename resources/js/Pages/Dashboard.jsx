import AdminLayout from '@/Layouts/AdminLayout';
import { Head, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const { locale = 'en' } = usePage().props;
    const text = {
        en: {
            title: 'Dashboard',
            leads: 'Leads',
            visitors: 'Visitors',
            conversion: 'Conversion',
            chats: 'Chats',
        },
        ru: {
            title: 'Дашборд',
            leads: 'Заявки',
            visitors: 'Визиты',
            conversion: 'Конверсия',
            chats: 'Чаты',
        },
        uk: {
            title: 'Дашборд',
            leads: 'Заявки',
            visitors: 'Візити',
            conversion: 'Конверсія',
            chats: 'Чати',
        },
    };
    const t = text[locale] ?? text.en;
    const cards = [t.leads, t.visitors, t.conversion, t.chats];

    return (
        <AdminLayout title={t.title}>
            <Head title="Dashboard" />
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {cards.map((label) => (
                    <div key={label} className="app-widget p-4">
                        <p className="text-sm text-slate-500">{label}</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">—</p>
                    </div>
                ))}
            </div>
        </AdminLayout>
    );
}
