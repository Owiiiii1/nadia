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
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AssistantPanel() {
    const { locale = 'en', assistant = {} } = usePage().props;
    const [confirming, setConfirming] = useState(false);
    const form = useForm({
        chat_enabled: Boolean(assistant.chat_enabled),
        greeting: assistant.greeting || '',
        system_prompt: assistant.system_prompt || '',
    });
    const text = {
        en: {
            enabled: 'Chat enabled',
            greeting: 'Greeting',
            prompt: 'System prompt',
            save: 'Save',
            reset: 'Reset to default prompt',
            resetTitle: 'Reset the system prompt?',
            resetBody: 'The current prompt will be replaced with the draft default.',
            cancel: 'Cancel',
            active: 'Active provider',
            warning: 'No active AI provider — chat is hidden on the site',
            ai: 'AI settings',
        },
        ru: {
            enabled: 'Чат включён',
            greeting: 'Приветствие',
            prompt: 'Системный промпт',
            save: 'Сохранить',
            reset: 'Вернуть промпт по умолчанию',
            resetTitle: 'Сбросить системный промпт?',
            resetBody: 'Текущий промпт будет заменён черновиком по умолчанию.',
            cancel: 'Отмена',
            active: 'Активный провайдер',
            warning: 'Нет активного ИИ-провайдера — чат скрыт на сайте',
            ai: 'Настройки AI',
        },
        uk: {
            enabled: 'Чат увімкнено',
            greeting: 'Привітання',
            prompt: 'Системний промпт',
            save: 'Зберегти',
            reset: 'Повернути промпт за замовчуванням',
            resetTitle: 'Скинути системний промпт?',
            resetBody: 'Поточний промпт буде замінено чернеткою за замовчуванням.',
            cancel: 'Скасувати',
            active: 'Активний провайдер',
            warning: 'Немає активного ШІ-провайдера — чат приховано на сайті',
            ai: 'Налаштування AI',
        },
    };
    const t = text[locale] ?? text.en;

    const save = (event) => {
        event.preventDefault();
        form.put(route('settings.assistant.update'));
    };

    return (
        <form onSubmit={save} className="app-widget space-y-4 p-4">
            <label className="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    checked={form.data.chat_enabled}
                    onChange={(event) => form.setData('chat_enabled', event.target.checked)}
                />
                <span>{t.enabled}</span>
            </label>

            <label className="block space-y-1 text-sm">
                <span className="text-slate-500">{t.greeting}</span>
                <input
                    value={form.data.greeting}
                    onChange={(event) => form.setData('greeting', event.target.value)}
                    className="h-9 w-full rounded-lg border border-input bg-transparent px-2.5 text-sm"
                />
                {form.errors.greeting && <span className="text-xs text-red-600">{form.errors.greeting}</span>}
            </label>

            <label className="block space-y-1 text-sm">
                <span className="text-slate-500">{t.prompt}</span>
                <textarea
                    value={form.data.system_prompt}
                    onChange={(event) => form.setData('system_prompt', event.target.value)}
                    rows={22}
                    className="w-full rounded-lg border border-input bg-transparent px-2.5 py-2 font-mono text-xs leading-5"
                />
                {form.errors.system_prompt && <span className="text-xs text-red-600">{form.errors.system_prompt}</span>}
            </label>

            <div className="flex flex-wrap items-center gap-3">
                <Button type="submit" disabled={form.processing}>{t.save}</Button>
                <Dialog open={confirming} onOpenChange={setConfirming}>
                    <DialogTrigger asChild>
                        <Button type="button" variant="outline">{t.reset}</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{t.resetTitle}</DialogTitle>
                            <DialogDescription>{t.resetBody}</DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setConfirming(false)}>{t.cancel}</Button>
                            <Button type="button" onClick={() => router.post(route('settings.assistant.reset'))}>{t.reset}</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
                {assistant.available ? (
                    <p className="text-sm text-slate-600">
                        {t.active}: {assistant.provider_label} / {assistant.model}
                    </p>
                ) : (
                    <p className="text-sm text-amber-700">
                        {t.warning}{' '}
                        <Link href={`${route('settings.index')}?tab=ai`} className="underline">{t.ai}</Link>
                    </p>
                )}
            </div>
        </form>
    );
}
