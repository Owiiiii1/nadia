import AdminLayout from '@/Layouts/AdminLayout';
import { Head, usePage } from '@inertiajs/react';

export default function AnalyticsIndex() {
    const { locale = 'en' } = usePage().props;
    const text = {
        en: { title: 'Analytics', body: 'Coming in step 6' },
        ru: { title: 'Аналитика', body: 'Coming in step 6' },
        uk: { title: 'Аналітика', body: 'Coming in step 6' },
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
