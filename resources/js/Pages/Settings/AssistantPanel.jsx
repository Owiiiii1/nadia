import { usePage } from '@inertiajs/react';

export default function AssistantPanel() {
    const { locale = 'en' } = usePage().props;
    const text = {
        en: { body: 'Coming in step 5' },
        ru: { body: 'Coming in step 5' },
        uk: { body: 'Coming in step 5' },
    };
    const t = text[locale] ?? text.en;

    return (
        <div className="app-widget p-4">
            <p className="text-sm text-slate-700">{t.body}</p>
        </div>
    );
}
