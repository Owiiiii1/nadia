import AdminLayout from '@/Layouts/AdminLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function ChatsIndex({ sessions, filters = {} }) {
    const { locale = 'en' } = usePage().props;
    const text = {
        en: {
            title: 'Chats',
            date: 'Last message',
            messages: 'Messages',
            preview: 'First message',
            lead: 'Lead',
            period: 'Period',
            all: 'All',
            today: 'Today',
            d7: '7 days',
            d30: '30 days',
            custom: 'Custom',
            from: 'From',
            to: 'To',
            onlyLead: 'Only with a lead',
            apply: 'Apply',
            empty: 'No chats yet.',
            previous: 'Previous',
            next: 'Next',
        },
        ru: {
            title: 'Чаты',
            date: 'Последнее сообщение',
            messages: 'Сообщения',
            preview: 'Первое сообщение',
            lead: 'Заявка',
            period: 'Период',
            all: 'Все',
            today: 'Сегодня',
            d7: '7 дней',
            d30: '30 дней',
            custom: 'Свой',
            from: 'С',
            to: 'По',
            onlyLead: 'Только с заявкой',
            apply: 'Применить',
            empty: 'Чатов пока нет.',
            previous: 'Назад',
            next: 'Дальше',
        },
        uk: {
            title: 'Чати',
            date: 'Останнє повідомлення',
            messages: 'Повідомлення',
            preview: 'Перше повідомлення',
            lead: 'Заявка',
            period: 'Період',
            all: 'Усі',
            today: 'Сьогодні',
            d7: '7 днів',
            d30: '30 днів',
            custom: 'Свій',
            from: 'З',
            to: 'По',
            onlyLead: 'Лише із заявкою',
            apply: 'Застосувати',
            empty: 'Чатів поки немає.',
            previous: 'Назад',
            next: 'Далі',
        },
    };
    const t = text[locale] ?? text.en;
    const rows = sessions?.data ?? [];

    const apply = (event) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const next = {};

        data.forEach((value, key) => {
            if (String(value).trim() !== '') {
                next[key] = value;
            }
        });

        router.get(route('chats.index'), next, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />
            <div className="space-y-4">
                <form onSubmit={apply} className="app-widget grid gap-3 p-4 md:grid-cols-4">
                    <label className="space-y-1 text-sm">
                        <span className="text-slate-500">{t.period}</span>
                        <select name="period" defaultValue={filters.period || 'all'} className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 text-sm">
                            <option value="all">{t.all}</option>
                            <option value="today">{t.today}</option>
                            <option value="7d">{t.d7}</option>
                            <option value="30d">{t.d30}</option>
                            <option value="custom">{t.custom}</option>
                        </select>
                    </label>
                    <label className="space-y-1 text-sm">
                        <span className="text-slate-500">{t.from}</span>
                        <Input type="date" name="from" defaultValue={filters.from} />
                    </label>
                    <label className="space-y-1 text-sm">
                        <span className="text-slate-500">{t.to}</span>
                        <Input type="date" name="to" defaultValue={filters.to} />
                    </label>
                    <label className="flex items-end gap-2 pb-2 text-sm">
                        <input type="checkbox" name="lead" value="1" defaultChecked={filters.lead === '1'} />
                        <span>{t.onlyLead}</span>
                    </label>
                    <div className="md:col-span-4">
                        <Button type="submit">{t.apply}</Button>
                    </div>
                </form>

                <div className="app-widget p-4">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t.date}</TableHead>
                                <TableHead>{t.messages}</TableHead>
                                <TableHead>{t.preview}</TableHead>
                                <TableHead>{t.lead}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="text-slate-500">{t.empty}</TableCell>
                                </TableRow>
                            )}
                            {rows.map((session) => (
                                <TableRow key={session.id}>
                                    <TableCell>
                                        <Link href={route('chats.show', session.id)} className="font-medium text-slate-900 underline-offset-2 hover:underline">
                                            {session.last_message_at || '—'}
                                        </Link>
                                    </TableCell>
                                    <TableCell>{session.messages_count}</TableCell>
                                    <TableCell className="max-w-xs truncate">{session.preview || '—'}</TableCell>
                                    <TableCell>
                                        {session.lead_id ? (
                                            <Link href={route('leads.show', session.lead_id)}>
                                                <Badge>{t.lead}</Badge>
                                            </Link>
                                        ) : '—'}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {sessions.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-between text-sm">
                            <span className="text-slate-500">{sessions.current_page} / {sessions.last_page}</span>
                            <div className="flex gap-2">
                                {sessions.prev_page_url && <Link href={sessions.prev_page_url} className="rounded-lg border px-3 py-1">{t.previous}</Link>}
                                {sessions.next_page_url && <Link href={sessions.next_page_url} className="rounded-lg border px-3 py-1">{t.next}</Link>}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
