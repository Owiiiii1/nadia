import ChatTranscript from '@/Components/ChatTranscript';
import AdminLayout from '@/Layouts/AdminLayout';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function LeadShow({ lead, statuses }) {
    const { locale = 'en' } = usePage().props;
    const [confirming, setConfirming] = useState(false);
    const form = useForm({
        status: lead.status,
        admin_note: lead.admin_note || '',
    });
    const text = {
        en: {
            back: 'Back to leads',
            status: 'Status',
            note: 'Admin note',
            save: 'Save',
            delete: 'Delete',
            cancel: 'Cancel',
            deleteTitle: 'Delete this lead?',
            deleteBody: 'This cannot be undone.',
            chat: 'Chat',
            openChat: 'Open chat',
            message: 'Message',
            phone: 'Phone',
            goal: 'Goal',
            source: 'Source',
            created: 'Created',
            updated: 'Updated',
            utmSource: 'UTM source',
            utmMedium: 'UTM medium',
            utmCampaign: 'UTM campaign',
            utmTerm: 'UTM term',
            utmContent: 'UTM content',
            referrer: 'Referrer',
            landing: 'Landing page',
            visitor: 'Visitor ID',
            agent: 'User agent',
        },
        ru: {
            back: 'К списку',
            status: 'Статус',
            note: 'Заметка',
            save: 'Сохранить',
            delete: 'Удалить',
            cancel: 'Отмена',
            deleteTitle: 'Удалить заявку?',
            deleteBody: 'Это нельзя отменить.',
            chat: 'Чат',
            openChat: 'Открыть чат',
            message: 'Сообщение',
            phone: 'Телефон',
            goal: 'Цель',
            source: 'Источник',
            created: 'Создана',
            updated: 'Обновлена',
            utmSource: 'UTM source',
            utmMedium: 'UTM medium',
            utmCampaign: 'UTM campaign',
            utmTerm: 'UTM term',
            utmContent: 'UTM content',
            referrer: 'Реферер',
            landing: 'Страница',
            visitor: 'ID посетителя',
            agent: 'User agent',
        },
        uk: {
            back: 'До списку',
            status: 'Статус',
            note: 'Нотатка',
            save: 'Зберегти',
            delete: 'Видалити',
            cancel: 'Скасувати',
            deleteTitle: 'Видалити заявку?',
            deleteBody: 'Це не можна скасувати.',
            chat: 'Чат',
            openChat: 'Відкрити чат',
            message: 'Повідомлення',
            phone: 'Телефон',
            goal: 'Ціль',
            source: 'Джерело',
            created: 'Створена',
            updated: 'Оновлена',
            utmSource: 'UTM source',
            utmMedium: 'UTM medium',
            utmCampaign: 'UTM campaign',
            utmTerm: 'UTM term',
            utmContent: 'UTM content',
            referrer: 'Реферер',
            landing: 'Сторінка',
            visitor: 'ID відвідувача',
            agent: 'User agent',
        },
    };
    const t = text[locale] ?? text.en;
    const facts = [
        [t.created, lead.created_at],
        [t.updated, lead.updated_at],
        ['Email', lead.email],
        [t.phone, lead.phone || '—'],
        [t.goal, lead.goal_label],
        [t.source, lead.source_label],
        [t.message, lead.message || '—'],
        [t.utmSource, lead.utm_source || '—'],
        [t.utmMedium, lead.utm_medium || '—'],
        [t.utmCampaign, lead.utm_campaign || '—'],
        [t.utmTerm, lead.utm_term || '—'],
        [t.utmContent, lead.utm_content || '—'],
        [t.referrer, lead.referrer || '—'],
        [t.landing, lead.landing_path || '—'],
        [t.visitor, lead.visitor_id || '—'],
        [t.agent, lead.user_agent || '—'],
    ];

    const save = (event) => {
        event.preventDefault();
        form.patch(route('leads.update', lead.id));
    };

    return (
        <AdminLayout title={lead.name}>
            <Head title={lead.name} />
            <div className="space-y-4">
                <Link href={route('leads.index')} className="text-sm text-slate-500 hover:text-slate-800">{t.back}</Link>

                <div className="app-widget grid gap-3 p-4 sm:grid-cols-2">
                    {facts.map(([label, value]) => (
                        <div key={label}>
                            <p className="text-xs uppercase tracking-wide text-slate-400">{label}</p>
                            <p className="mt-1 break-words text-sm text-slate-800">{value}</p>
                        </div>
                    ))}
                </div>

                {lead.chat && (
                    <div className="app-widget space-y-3 p-4">
                        <div className="flex items-center justify-between gap-3">
                            <p className="text-sm font-medium text-slate-800">{t.chat}</p>
                            <Link href={route('chats.show', lead.chat.id)} className="text-sm underline">{t.openChat}</Link>
                        </div>
                        <ChatTranscript messages={lead.chat.messages || []} />
                    </div>
                )}

                <form onSubmit={save} className="app-widget space-y-3 p-4">
                    <label className="block space-y-1 text-sm">
                        <span className="text-slate-500">{t.status}</span>
                        <select
                            value={form.data.status}
                            onChange={(event) => form.setData('status', event.target.value)}
                            className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 text-sm"
                        >
                            {statuses.map((status) => (
                                <option key={status.value} value={status.value}>{status.label}</option>
                            ))}
                        </select>
                    </label>
                    <label className="block space-y-1 text-sm">
                        <span className="text-slate-500">{t.note}</span>
                        <textarea
                            value={form.data.admin_note}
                            onChange={(event) => form.setData('admin_note', event.target.value)}
                            rows={4}
                            className="w-full rounded-lg border border-input bg-transparent px-2.5 py-2 text-sm"
                        />
                    </label>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>{t.save}</Button>
                        <Dialog open={confirming} onOpenChange={setConfirming}>
                            <DialogTrigger asChild>
                                <Button type="button" variant="destructive">{t.delete}</Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>{t.deleteTitle}</DialogTitle>
                                    <DialogDescription>{t.deleteBody}</DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={() => setConfirming(false)}>{t.cancel}</Button>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        onClick={() => router.delete(route('leads.destroy', lead.id))}
                                    >
                                        {t.delete}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
