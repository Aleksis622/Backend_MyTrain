import { useTranslation } from "react-i18next";

/*
 * Small Tailwind building blocks shared by the admin pages.
 * Colors come from the site palette (see styles/admin.css): brand, ink, subtle, line, canvas...
 */

export function PageHeader({ title, children }) {
  return (
    <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
      <h1 className="m-0 text-2xl font-extrabold tracking-tight text-ink">{title}</h1>
      {children}
    </div>
  );
}

export function Card({ className = "", children }) {
  return <div className={`rounded-2xl border border-line bg-card p-5 shadow-card ${className}`}>{children}</div>;
}

const BUTTON_VARIANTS = {
  primary: "bg-brand text-white hover:bg-brand-dark",
  secondary: "border border-line bg-card text-ink hover:bg-canvas",
  danger: "bg-bad text-white hover:brightness-90",
  success: "bg-ok text-white hover:brightness-90",
};

export function Button({ variant = "primary", className = "", ...props }) {
  return (
    <button
      type="button"
      className={`cursor-pointer rounded-[10px] border-0 px-4 py-2 font-[inherit] text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-50 ${BUTTON_VARIANTS[variant]} ${className}`}
      {...props}
    />
  );
}

const BADGE_COLORS = {
  green: "bg-ok-soft text-ok",
  amber: "bg-warn-soft text-warn",
  red: "bg-bad-soft text-bad",
  gray: "bg-canvas text-subtle",
  blue: "bg-brand-soft text-brand",
};

export function Badge({ color = "gray", children }) {
  return (
    <span className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap ${BADGE_COLORS[color]}`}>
      {children}
    </span>
  );
}

const TICKET_COLORS = { paid: "green", pending: "amber", cancelled: "red", refunded: "gray" };

export function TicketStatusBadge({ status }) {
  const { t } = useTranslation();
  return <Badge color={TICKET_COLORS[status] ?? "gray"}>{t(`admin.ticket_status.${status}`)}</Badge>;
}

export const inputClass =
  "w-full rounded-[10px] border border-line bg-card px-3 py-2 font-[inherit] text-sm text-ink focus:border-brand focus:outline-none focus:ring-3 focus:ring-brand-soft";

export function Field({ label, children }) {
  return (
    <label className="flex flex-col gap-1 text-sm font-medium text-subtle">
      {label}
      {children}
    </label>
  );
}

export function Alert({ type = "error", children }) {
  if (!children) return null;
  const colors = type === "error" ? "bg-bad-soft text-bad" : "bg-ok-soft text-ok";
  return <p className={`my-3 rounded-[10px] px-4 py-2 text-sm font-medium ${colors}`}>{children}</p>;
}

// Wrapper that lets wide tables scroll sideways on small screens.
export function Table({ head, children }) {
  return (
    <div className="overflow-x-auto rounded-2xl border border-line bg-card shadow-card">
      <table className="w-full border-collapse text-left text-sm">
        <thead className="bg-canvas text-xs uppercase tracking-wide text-subtle">
          <tr>
            {head.map((label, i) => (
              <th key={i} className="px-4 py-3 font-semibold">
                {label}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-line">{children}</tbody>
      </table>
    </div>
  );
}

export const cellClass = "px-4 py-3 align-middle";

// "Previous / Page 2 of 5 / Next" under a paginated Laravel list.
export function Pagination({ page, onChange }) {
  const { t } = useTranslation();
  if (!page || page.last_page <= 1) return null;

  return (
    <div className="mt-4 flex items-center justify-between text-sm text-subtle">
      <Button variant="secondary" disabled={page.current_page <= 1} onClick={() => onChange(page.current_page - 1)}>
        ← {t("admin.previous")}
      </Button>
      <span>{t("admin.page_of", { page: page.current_page, total: page.last_page })}</span>
      <Button
        variant="secondary"
        disabled={page.current_page >= page.last_page}
        onClick={() => onChange(page.current_page + 1)}
      >
        {t("admin.next")} →
      </Button>
    </div>
  );
}
