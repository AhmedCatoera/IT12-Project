/* Dashboard bar chart: sales booked per day for the last 14 days. Each bar links to
 * that day's sales report. */
import { peso } from '../lib/format';

export default function SalesChart({ days }) {
  const max = Math.max(1, ...days.map((d) => d.total));
  const short = (n) => (n >= 1000 ? `${(n / 1000).toFixed(1)}k` : Math.round(n).toLocaleString('en-PH'));

  return (
    <div className="grid h-[150px] grid-flow-col auto-cols-[minmax(0,1fr)] items-end gap-2 pt-2 max-md:h-[120px] max-md:gap-1" role="img" aria-label="Sales booked per day for the last 14 days">
      {days.map((day) => (
        <a key={day.date} href={day.url} title={`${day.label}: ${peso(day.total)}`}
          className="group flex h-full min-w-0 flex-col items-center justify-end text-ink no-underline hover:no-underline">
          {day.total > 0 && <b className="mb-1 text-2xs font-semibold tabular-nums text-ink-2">{short(day.total)}</b>}
          <i className={`block w-full max-w-9 min-h-[3px] rounded-t-[6px] rounded-b-[3px] transition-opacity group-hover:opacity-80 ${day.total > 0 ? 'bg-linear-to-b from-brand-light to-brand' : 'bg-surface-3'}`}
            style={{ height: `${Math.max(3, Math.round((day.total / max) * 100))}%` }} />
          <small className={`mt-1.5 whitespace-nowrap text-2xs max-md:text-[10px] ${day.isToday ? 'font-semibold text-brand' : 'text-ink-3'}`}>
            <span className="max-md:hidden">{day.isToday ? 'Today' : day.label}</span>
            <span className="md:hidden">{day.short}</span>
          </small>
        </a>
      ))}
    </div>
  );
}
