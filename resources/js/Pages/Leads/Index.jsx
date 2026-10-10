import AdminLayout from '@/Layouts/AdminLayout';
import { Head, usePage } from '@inertiajs/react';

export default function LeadsIndex() {
    const { locale = 'en' } = usePage().props;
    const text = {
        en: { title: 'Leads', body: 'Coming in step 4' },
        ru: { title: 'Заявки', body: 'Coming in step 4' },
        uk: { title: 'Заявки', body: 'Coming in step 4' },
    };
    const t = text[locale] ?? text.en;

    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />
            <div className="app-widget p-4">
                <p className="text-sm text-slate-700">{t.body}</p>
            </div>
        </AdminLayout>
    );
}
