import { CartesianGrid, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

export default function VisitsChart({ series = [], visitorsLabel = 'Visitors', pageviewsLabel = 'Pageviews' }) {
    return (
        <div className="h-60 w-full">
            <ResponsiveContainer width="100%" height="100%">
                <LineChart data={series} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                    <CartesianGrid stroke="#e2e8f0" vertical={false} />
                    <XAxis dataKey="date" tick={{ fontSize: 12 }} minTickGap={24} />
                    <YAxis allowDecimals={false} tick={{ fontSize: 12 }} width={32} />
                    <Tooltip />
                    <Line type="monotone" dataKey="visitors" name={visitorsLabel} stroke="#0f172a" strokeWidth={2} dot={false} />
                    <Line type="monotone" dataKey="pageviews" name={pageviewsLabel} stroke="#64748b" strokeWidth={2} dot={false} />
                </LineChart>
            </ResponsiveContainer>
        </div>
    );
}
