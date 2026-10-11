import { Button } from '@/Components/ui/button';
import { router, useForm, usePage } from '@inertiajs/react';

export default function NotificationsPanel() {
    const { locale = 'en', notifications = {} } = usePage().props;
    const text = {
        en: {
            telegram: 'Telegram',
            email: 'Email',
            enabled: 'Send new leads',
            botMissing: 'The Telegram bot is not connected.',
            botLink: 'Open the Telegram tab',
            connect: 'Connect Telegram account',
            instruction: 'Open @',
            send: 'and send:',
            recipients: 'Recipients',
            remove: 'Remove',
            test: 'Send test',
            none: 'No recipients yet',
            emails: 'Recipient emails (up to 5)',
            add: 'Add email',
            save: 'Save',
            mailMissing: 'Email transport is not configured on the server',
            lastError: 'Last error',
        },
        ru: {
            telegram: 'Telegram',
            email: 'Почта',
            enabled: 'Присылать новые заявки',
            botMissing: 'Бот Telegram не подключён.',
            botLink: 'Открыть вкладку Telegram',
            connect: 'Привязать аккаунт Telegram',
            instruction: 'Откройте @',
            send: 'и отправьте:',
            recipients: 'Получатели',
            remove: 'Убрать',
            test: 'Тестовое сообщение',
            none: 'Получателей пока нет',
            emails: 'Почты получателей (до 5)',
            add: 'Добавить почту',
            save: 'Сохранить',
            mailMissing: 'Email transport is not configured on the server',
            lastError: 'Последняя ошибка',
        },
        uk: {
            telegram: 'Telegram',
            email: 'Пошта',
            enabled: 'Надсилати нові заявки',
            botMissing: 'Бот Telegram не підключений.',
            botLink: 'Відкрити вкладку Telegram',
            connect: 'Привʼязати акаунт Telegram',
            instruction: 'Відкрийте @',
            send: 'і надішліть:',
            recipients: 'Отримувачі',
            remove: 'Прибрати',
            test: 'Тестове повідомлення',
            none: 'Отримувачів поки немає',
            emails: 'Пошти отримувачів (до 5)',
            add: 'Додати пошту',
            save: 'Зберегти',
            mailMissing: 'Email transport is not configured on the server',
            lastError: 'Остання помилка',
        },
    };
    const t = text[locale] ?? text.en;
    const form = useForm({
        telegram_enabled: Boolean(notifications.telegram_enabled),
        email_enabled: Boolean(notifications.email_enabled),
        emails: notifications.emails?.length ? notifications.emails : [''],
    });

    function save(event) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            emails: data.emails.map((email) => email.trim()).filter(Boolean),
        })).put(route('settings.notifications.update'), { preserveScroll: true });
    }

    return (
        <form className="space-y-4" onSubmit={save}>
            {notifications.test_notice ? <p className="text-sm text-slate-700">{notifications.test_notice}</p> : null}
            <section className="app-widget space-y-3 p-4">
                <h2 className="text-sm font-medium text-slate-900">{t.telegram}</h2>
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={form.data.telegram_enabled} onChange={(event) => form.setData('telegram_enabled', event.target.checked)} />
                    {t.enabled}
                </label>
                {notifications.bot_connected ? null : (
                    <p className="text-sm text-amber-800">
                        {t.botMissing}{' '}
                        <button type="button" className="underline" onClick={() => router.get(route('settings.index'), { tab: 'telegram' })}>{t.botLink}</button>
                    </p>
                )}
                <Button type="button" variant="outline" disabled={!notifications.bot_connected} onClick={() => router.post(route('settings.notifications.telegram.connect'), {}, { preserveScroll: true })}>
                    {t.connect}
                </Button>
                {notifications.bind_code ? (
                    <p className="text-sm text-slate-700">
                        {t.instruction}{notifications.bind_username} {t.send} <code>/start {notifications.bind_code}</code>
                    </p>
                ) : null}
                <h3 className="text-xs uppercase tracking-wide text-slate-500">{t.recipients}</h3>
                {(notifications.recipients || []).length === 0 ? <p className="text-sm text-slate-500">{t.none}</p> : (
                    <ul className="space-y-2">
                        {notifications.recipients.map((recipient) => (
                            <li key={recipient.chat_id} className="flex items-center justify-between text-sm">
                                <span>{recipient.name}{recipient.username ? ` (@${recipient.username})` : ''}</span>
                                <button type="button" className="underline" onClick={() => router.delete(route('settings.notifications.telegram.remove'), { data: { chat_id: recipient.chat_id }, preserveScroll: true })}>{t.remove}</button>
                            </li>
                        ))}
                    </ul>
                )}
                <Button type="button" variant="outline" onClick={() => router.post(route('settings.notifications.telegram.test'), {}, { preserveScroll: true })}>{t.test}</Button>
                {notifications.telegram_error ? <p className="text-xs text-red-700">{t.lastError}: {notifications.telegram_error}</p> : null}
            </section>

            <section className="app-widget space-y-3 p-4">
                <h2 className="text-sm font-medium text-slate-900">{t.email}</h2>
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={form.data.email_enabled} onChange={(event) => form.setData('email_enabled', event.target.checked)} />
                    {t.enabled}
                </label>
                {notifications.mail_configured ? null : <p className="text-sm text-amber-800">{t.mailMissing}</p>}
                <div className="space-y-2">
                    <p className="text-sm text-slate-700">{t.emails}</p>
                    {form.data.emails.map((email, index) => (
                        <input
                            key={index}
                            type="email"
                            value={email}
                            onChange={(event) => {
                                const next = [...form.data.emails];
                                next[index] = event.target.value;
                                form.setData('emails', next);
                            }}
                            className="block w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                        />
                    ))}
                    {form.data.emails.length < 5 ? (
                        <button type="button" className="text-sm underline" onClick={() => form.setData('emails', [...form.data.emails, ''])}>{t.add}</button>
                    ) : null}
                    {form.errors.emails ? <p className="text-xs text-red-600">{form.errors.emails}</p> : null}
                </div>
                <Button type="button" variant="outline" onClick={() => router.post(route('settings.notifications.email.test'), {}, { preserveScroll: true })}>{t.test}</Button>
                {notifications.email_error ? <p className="text-xs text-red-700">{t.lastError}: {notifications.email_error}</p> : null}
            </section>
            <Button type="submit" disabled={form.processing}>{t.save}</Button>
        </form>
    );
}
