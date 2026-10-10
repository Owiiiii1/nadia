export default function ChatTranscript({ messages = [] }) {
    return (
        <div className="space-y-3">
            {messages.map((message) => (
                <div key={message.id} className={message.role === 'user' ? 'ml-10 text-right' : 'mr-10'}>
                    <div
                        className={
                            message.role === 'user'
                                ? 'inline-block max-w-full rounded-2xl bg-slate-900 px-3 py-2 text-left text-sm text-white whitespace-pre-wrap'
                                : 'inline-block max-w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-left text-sm text-slate-800 whitespace-pre-wrap'
                        }
                    >
                        {message.content}
                    </div>
                    <p className="mt-1 text-xs text-slate-400">{message.created_at}</p>
                    {message.error && <p className="text-xs text-red-600">Error</p>}
                </div>
            ))}
        </div>
    );
}
