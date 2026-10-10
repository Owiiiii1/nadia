import AdminLayout from '@/Layouts/AdminLayout';
import { Head, usePage } from '@inertiajs/react';

export default function TrackingIndex() {
    const { locale = 'en' } = usePage().props;
    const text = {
        en: { title: 'Pixels & codes', body: 'Coming in step 7' },
        ru: { title: 'Пиксели и коды', body: 'Coming in step 7' },
        uk: { title: 'Пікселі та коди', body: 'Coming in step 7' },
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
