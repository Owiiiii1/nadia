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
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function ChatShow({ session }) {
    const { locale = 'en' } = usePage().props;
    const [confirming, setConfirming] = useState(false);
    const text = {
        en: {
            back: 'Back to chats',
            title: 'Chat',
            delete: 'Delete',
            cancel: 'Cancel',
            deleteTitle: 'Delete this chat?',
            deleteBody: 'Messages in this session will be removed.',
            lead: 'Lead',
            visitor: 'Visitor ID',
            source: 'UTM source',
            medium: 'UTM medium',
            campaign: 'UTM campaign',
            referrer: 'Referrer',
            landing: 'Landing page',
            agent: 'User agent',
            tokensIn: 'Tokens in',
            tokensOut: 'Tokens out',
            openLead: 'Open lead',
        },
        ru: {
            back: 'К чатам',
            title: 'Чат',
            delete: 'Удалить',
            cancel: 'Отмена',
            deleteTitle: 'Удалить этот чат?',
            deleteBody: 'Сообщения этой сессии будут удалены.',
            lead: 'Заявка',
            visitor: 'ID посетителя',
            source: 'UTM source',
            medium: 'UTM medium',
            campaign: 'UTM campaign',
            referrer: 'Реферер',
            landing: 'Страница',
            agent: 'User agent',
            tokensIn: 'Входящие токены',
            tokensOut: 'Исходящие токены',
            openLead: 'Открыть заявку',
        },
        uk: {
            back: 'До чатів',
            title: 'Чат',
            delete: 'Видалити',
            cancel: 'Скасувати',
            deleteTitle: 'Видалити цей чат?',
            deleteBody: 'Повідомлення цієї сесії буде видалено.',
            lead: 'Заявка',
            visitor: 'ID відвідувача',
            source: 'UTM source',
            medium: 'UTM medium',
            campaign: 'UTM campaign',
            referrer: 'Реферер',
            landing: 'Сторінка',
            agent: 'User agent',
            tokensIn: 'Вхідні токени',
            tokensOut: 'Вихідні токени',
            openLead: 'Відкрити заявку',
        },
    };
    const t = text[locale] ?? text.en;
    const facts = [
        [t.visitor, session.visitor_id || '—'],
        [t.source, session.utm_source || '—'],
        [t.medium, session.utm_medium || '—'],
        [t.campaign, session.utm_campaign || '—'],
        [t.referrer, session.referrer || '—'],
        [t.landing, session.landing_path || '—'],
        [t.agent, session.user_agent || '—'],
        [t.tokensIn, String(session.tokens_in ?? 0)],
        [t.tokensOut, String(session.tokens_out ?? 0)],
    ];

    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />
            <div className="space-y-4">
                <Link href={route('chats.index')} className="text-sm text-slate-500 hover:text-slate-800">{t.back}</Link>
                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_280px]">
                    <div className="app-widget p-4">
                        <ChatTranscript messages={session.messages || []} />
                    </div>
                    <aside className="app-widget space-y-3 p-4">
                        {facts.map(([label, value]) => (
                            <div key={label}>
                                <p className="text-xs uppercase tracking-wide text-slate-400">{label}</p>
                                <p className="mt-1 break-words text-sm text-slate-800">{value}</p>
                            </div>
                        ))}
                        <div>
                            <p className="text-xs uppercase tracking-wide text-slate-400">{t.lead}</p>
                            {session.lead_id ? (
                                <Link href={route('leads.show', session.lead_id)} className="mt-1 inline-block text-sm underline">
                                    {session.lead_name || t.openLead}
                                </Link>
                            ) : <p className="mt-1 text-sm text-slate-800">—</p>}
                        </div>
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
                                    <Button type="button" variant="destructive" onClick={() => router.delete(route('chats.destroy', session.id))}>
                                        {t.delete}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </aside>
                </div>
            </div>
        </AdminLayout>
    );
}
