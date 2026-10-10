import AdminLayout from '@/Layouts/AdminLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { Head, Link, router, usePage } from '@inertiajs/react';

const statusClass = {
    new: 'bg-amber-100 text-amber-800',
    in_progress: 'bg-sky-100 text-sky-800',
    done: 'bg-emerald-100 text-emerald-800',
    spam: 'bg-rose-100 text-rose-800',
};

export default function LeadsIndex({ leads, filters, options }) {
    const { locale = 'en' } = usePage().props;
    const text = {
        en: {
            title: 'Leads',
            date: 'Date',
            name: 'Name',
            email: 'Email',
            phone: 'Phone',
            goal: 'Goal',
            source: 'Source',
            status: 'Status',
            all: 'All',
            today: 'Today',
            d7: '7 days',
            d30: '30 days',
            custom: 'Custom',
            from: 'From',
            to: 'To',
            search: 'Search name, email, or phone',
            apply: 'Apply',
            export: 'Export CSV',
            empty: 'No leads yet.',
            previous: 'Previous',
            next: 'Next',
        },
        ru: {
            title: 'Заявки',
            date: 'Дата',
            name: 'Имя',
            email: 'Email',
            phone: 'Телефон',
            goal: 'Цель',
            source: 'Источник',
            status: 'Статус',
            all: 'Все',
            today: 'Сегодня',
            d7: '7 дней',
            d30: '30 дней',
            custom: 'Свой период',
            from: 'С',
            to: 'По',
            search: 'Поиск по имени, email или телефону',
            apply: 'Показать',
            export: 'Экспорт CSV',
            empty: 'Заявок пока нет.',
            previous: 'Назад',
            next: 'Дальше',
        },
        uk: {
            title: 'Заявки',
            date: 'Дата',
            name: 'Імʼя',
            email: 'Email',
            phone: 'Телефон',
            goal: 'Ціль',
            source: 'Джерело',
            status: 'Статус',
            all: 'Усі',
            today: 'Сьогодні',
            d7: '7 днів',
            d30: '30 днів',
            custom: 'Свій період',
            from: 'Від',
            to: 'До',
            search: 'Пошук за імʼям, email або телефоном',
            apply: 'Показати',
            export: 'Експорт CSV',
            empty: 'Заявок поки немає.',
            previous: 'Назад',
            next: 'Далі',
        },
    };
    const t = text[locale] ?? text.en;
    const rows = leads?.data ?? [];
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    const exportHref = route('leads.export') + (params.toString() ? `?${params.toString()}` : '');

    const apply = (event) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const next = {};

        data.forEach((value, key) => {
            if (String(value).trim() !== '') {
                next[key] = value;
            }
        });

        router.get(route('leads.index'), next, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />
            <div className="space-y-4">
                <form onSubmit={apply} className="app-widget grid gap-3 p-4 md:grid-cols-4">
                    <Select name="status" label={t.status} value={filters.status} allLabel={t.all} options={options.statuses} />
                    <Select name="source" label={t.source} value={filters.source} allLabel={t.all} options={options.sources} />
                    <Select name="goal" label={t.goal} value={filters.goal} allLabel={t.all} options={options.goals} />
                    <label className="space-y-1 text-sm">
                        <span className="text-slate-500">{t.date}</span>
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
                    <label className="space-y-1 text-sm md:col-span-2">
                        <span className="text-slate-500">{t.search}</span>
                        <Input type="search" name="q" defaultValue={filters.q} placeholder={t.search} />
                    </label>
                    <div className="flex items-end gap-2 md:col-span-4">
                        <Button type="submit">{t.apply}</Button>
                        <Button variant="outline" asChild>
                            <a href={exportHref}>{t.export}</a>
                        </Button>
                    </div>
                </form>

                <div className="app-widget p-4">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t.date}</TableHead>
                                <TableHead>{t.name}</TableHead>
                                <TableHead>{t.email}</TableHead>
                                <TableHead>{t.phone}</TableHead>
                                <TableHead>{t.goal}</TableHead>
                                <TableHead>{t.source}</TableHead>
                                <TableHead>{t.status}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-slate-500">{t.empty}</TableCell>
                                </TableRow>
                            )}
                            {rows.map((lead) => (
                                <TableRow key={lead.id}>
                                    <TableCell>{lead.created_at}</TableCell>
                                    <TableCell>
                                        <Link href={route('leads.show', lead.id)} className="font-medium text-slate-900 underline-offset-2 hover:underline">
                                            {lead.name}
                                        </Link>
                                    </TableCell>
                                    <TableCell>{lead.email}</TableCell>
                                    <TableCell>{lead.phone || '—'}</TableCell>
                                    <TableCell>{lead.goal_label}</TableCell>
                                    <TableCell>{lead.source_label}</TableCell>
                                    <TableCell>
                                        <Badge className={statusClass[lead.status] || ''}>{lead.status_label}</Badge>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {leads.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-between text-sm">
                            <span className="text-slate-500">{leads.current_page} / {leads.last_page}</span>
                            <div className="flex gap-2">
                                {leads.prev_page_url && <Link href={leads.prev_page_url} className="rounded-lg border px-3 py-1">{t.previous}</Link>}
                                {leads.next_page_url && <Link href={leads.next_page_url} className="rounded-lg border px-3 py-1">{t.next}</Link>}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}

function Select({ name, label, value, allLabel, options }) {
    return (
        <label className="space-y-1 text-sm">
            <span className="text-slate-500">{label}</span>
            <select name={name} defaultValue={value || ''} className="h-8 w-full rounded-lg border border-input bg-transparent px-2.5 text-sm">
                <option value="">{allLabel}</option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>{option.label}</option>
                ))}
            </select>
        </label>
    );
}
