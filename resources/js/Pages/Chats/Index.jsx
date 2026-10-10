import AdminLayout from '@/Layouts/AdminLayout';
import { Head, usePage } from '@inertiajs/react';

export default function ChatsIndex() {
    const { locale = 'en' } = usePage().props;
    const text = {
        en: { title: 'Chats', body: 'Coming in step 5' },
        ru: { title: 'Чаты', body: 'Coming in step 5' },
        uk: { title: 'Чати', body: 'Coming in step 5' },
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
