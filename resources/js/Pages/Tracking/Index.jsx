import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

const emptyCode = { name: '', placement: 'head', code: '', is_active: true, sort: 0 };

export default function TrackingIndex({ codes = [], pixels = {} }) {
    const { locale = 'en' } = usePage().props;
    const text = {
        en: {
            title: 'Pixels & codes',
            quick: 'Quick setup',
            meta: 'Meta Pixel ID',
            metaHint: 'Events Manager → Data sources → your pixel. Digits only, 10–20 characters.',
            ga: 'GA4 Measurement ID',
            gaHint: 'Admin → Data streams → Measurement ID, like G-ABC1234.',
            save: 'Save',
            snippets: 'Custom code snippets',
            warning: 'Code is inserted as-is into the public site. Only paste code from trusted providers.',
            add: 'Add',
            edit: 'Edit',
            remove: 'Delete',
            name: 'Name',
            placement: 'Placement',
            active: 'Active',
            sort: 'Sort',
            code: 'Code',
            cancel: 'Cancel',
            confirm: 'Delete this snippet?',
            empty: 'No snippets yet',
            head: 'Head',
            body_start: 'Body start',
            body_end: 'Body end',
        },
        ru: {
            title: 'Пиксели и коды',
            quick: 'Быстрая настройка',
            meta: 'Meta Pixel ID',
            metaHint: 'Events Manager → источники данных → пиксель. Только цифры, 10–20 символов.',
            ga: 'GA4 Measurement ID',
            gaHint: 'Админка → потоки данных → Measurement ID, например G-ABC1234.',
            save: 'Сохранить',
            snippets: 'Свои фрагменты кода',
            warning: 'Code is inserted as-is into the public site. Only paste code from trusted providers.',
            add: 'Добавить',
            edit: 'Изменить',
            remove: 'Удалить',
            name: 'Название',
            placement: 'Место',
            active: 'Активен',
            sort: 'Порядок',
            code: 'Код',
            cancel: 'Отмена',
            confirm: 'Удалить этот фрагмент?',
            empty: 'Фрагментов пока нет',
            head: 'Head',
            body_start: 'Начало body',
            body_end: 'Конец body',
        },
        uk: {
            title: 'Пікселі та коди',
            quick: 'Швидке налаштування',
            meta: 'Meta Pixel ID',
            metaHint: 'Events Manager → джерела даних → піксель. Лише цифри, 10–20 символів.',
            ga: 'GA4 Measurement ID',
            gaHint: 'Адмінка → потоки даних → Measurement ID, наприклад G-ABC1234.',
            save: 'Зберегти',
            snippets: 'Власні фрагменти коду',
            warning: 'Code is inserted as-is into the public site. Only paste code from trusted providers.',
            add: 'Додати',
            edit: 'Змінити',
            remove: 'Видалити',
            name: 'Назва',
            placement: 'Місце',
            active: 'Активний',
            sort: 'Порядок',
            code: 'Код',
            cancel: 'Скасувати',
            confirm: 'Видалити цей фрагмент?',
            empty: 'Фрагментів поки немає',
            head: 'Head',
            body_start: 'Початок body',
            body_end: 'Кінець body',
        },
    };
    const t = text[locale] ?? text.en;
    const pixelForm = useForm({
        meta_pixel_id: pixels.meta_pixel_id || '',
        ga4_measurement_id: pixels.ga4_measurement_id || '',
    });
    const [editing, setEditing] = useState(null);
    const [removing, setRemoving] = useState(null);
    const codeForm = useForm(emptyCode);

    function openEditor(code) {
        codeForm.setData({
            name: code?.name || '',
            placement: code?.placement || 'head',
            code: code?.code || '',
            is_active: code ? Boolean(code.is_active) : true,
            sort: code?.sort ?? 0,
        });
        codeForm.clearErrors();
        setEditing(code || { id: null });
    }

    function saveCode(event) {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => setEditing(null),
        };

        if (editing?.id) {
            codeForm.patch(route('tracking.update', editing.id), options);
        } else {
            codeForm.post(route('tracking.store'), options);
        }
    }

    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />
            <div className="space-y-4">
                <form
                    className="app-widget space-y-3 p-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        pixelForm.put(route('tracking.pixels'), { preserveScroll: true });
                    }}
                >
                    <h2 className="text-sm font-medium text-slate-900">{t.quick}</h2>
                    <label className="block text-sm text-slate-700">
                        {t.meta}
                        <input
                            value={pixelForm.data.meta_pixel_id}
                            onChange={(event) => pixelForm.setData('meta_pixel_id', event.target.value)}
                            className="mt-1 block w-full rounded-md border border-slate-200 px-3 py-2"
                        />
                        <span className="mt-1 block text-xs text-slate-500">{t.metaHint}</span>
                        {pixelForm.errors.meta_pixel_id ? <span className="mt-1 block text-xs text-red-600">{pixelForm.errors.meta_pixel_id}</span> : null}
                    </label>
                    <label className="block text-sm text-slate-700">
                        {t.ga}
                        <input
                            value={pixelForm.data.ga4_measurement_id}
                            onChange={(event) => pixelForm.setData('ga4_measurement_id', event.target.value)}
                            className="mt-1 block w-full rounded-md border border-slate-200 px-3 py-2"
                        />
                        <span className="mt-1 block text-xs text-slate-500">{t.gaHint}</span>
                        {pixelForm.errors.ga4_measurement_id ? <span className="mt-1 block text-xs text-red-600">{pixelForm.errors.ga4_measurement_id}</span> : null}
                    </label>
                    <Button type="submit" disabled={pixelForm.processing}>{t.save}</Button>
                </form>

                <div className="app-widget p-4">
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="text-sm font-medium text-slate-900">{t.snippets}</h2>
                        <Button type="button" onClick={() => openEditor(null)}>{t.add}</Button>
                    </div>
                    <p className="mt-2 text-sm text-amber-800">{t.warning}</p>
                    {codes.length === 0 ? (
                        <p className="mt-4 text-sm text-slate-500">{t.empty}</p>
                    ) : (
                        <table className="mt-4 w-full text-left text-sm">
                            <thead className="text-slate-400">
                                <tr>
                                    <th className="py-1 font-normal">{t.name}</th>
                                    <th className="py-1 font-normal">{t.placement}</th>
                                    <th className="py-1 font-normal">{t.active}</th>
                                    <th className="py-1 font-normal">{t.sort}</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {codes.map((code) => (
                                    <tr key={code.id} className="border-t border-slate-100">
                                        <td className="py-2">{code.name}</td>
                                        <td className="py-2">{t[code.placement]}</td>
                                        <td className="py-2">
                                            <button
                                                type="button"
                                                role="switch"
                                                aria-checked={code.is_active}
                                                onClick={() => router.patch(route('tracking.update', code.id), { is_active: !code.is_active }, { preserveScroll: true })}
                                                className={`rounded-full px-2 py-1 text-xs ${code.is_active ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'}`}
                                            >
                                                {code.is_active ? 'On' : 'Off'}
                                            </button>
                                        </td>
                                        <td className="py-2">{code.sort}</td>
                                        <td className="py-2 text-right">
                                            <button type="button" className="mr-3 text-slate-700 underline" onClick={() => openEditor(code)}>{t.edit}</button>
                                            <button type="button" className="text-red-700 underline" onClick={() => setRemoving(code)}>{t.remove}</button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>

            <Dialog open={editing !== null} onOpenChange={(open) => { if (!open) setEditing(null); }}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editing?.id ? t.edit : t.add}</DialogTitle>
                        <DialogDescription>{t.warning}</DialogDescription>
                    </DialogHeader>
                    <form className="space-y-3" onSubmit={saveCode}>
                        <label className="block text-sm">
                            {t.name}
                            <input value={codeForm.data.name} onChange={(event) => codeForm.setData('name', event.target.value)} className="mt-1 block w-full rounded-md border border-slate-200 px-3 py-2" />
                        </label>
                        <label className="block text-sm">
                            {t.placement}
                            <select value={codeForm.data.placement} onChange={(event) => codeForm.setData('placement', event.target.value)} className="mt-1 block w-full rounded-md border border-slate-200 px-3 py-2">
                                <option value="head">{t.head}</option>
                                <option value="body_start">{t.body_start}</option>
                                <option value="body_end">{t.body_end}</option>
                            </select>
                        </label>
                        <label className="block text-sm">
                            {t.sort}
                            <input type="number" min="0" max="65535" value={codeForm.data.sort} onChange={(event) => codeForm.setData('sort', Number(event.target.value))} className="mt-1 block w-full rounded-md border border-slate-200 px-3 py-2" />
                        </label>
                        <label className="block text-sm">
                            {t.code}
                            <textarea value={codeForm.data.code} onChange={(event) => codeForm.setData('code', event.target.value)} rows={8} className="mt-1 block w-full rounded-md border border-slate-200 px-3 py-2 font-mono text-xs" />
                            {codeForm.errors.code ? <span className="mt-1 block text-xs text-red-600">{codeForm.errors.code}</span> : null}
                        </label>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setEditing(null)}>{t.cancel}</Button>
                            <Button type="submit" disabled={codeForm.processing}>{t.save}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={removing !== null} onOpenChange={(open) => { if (!open) setRemoving(null); }}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t.confirm}</DialogTitle>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setRemoving(null)}>{t.cancel}</Button>
                        <Button
                            type="button"
                            onClick={() => router.delete(route('tracking.destroy', removing.id), {
                                preserveScroll: true,
                                onSuccess: () => setRemoving(null),
                            })}
                        >
                            {t.remove}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
