import { useEffect, useState } from "react";
import { Link, useOutletContext, useSearchParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { all as getTickets, cancel as cancelTicket, refund as refundTicket } from "../../api/tickets";
import { getErrorMessage } from "../../api/api";
import TicketCard from "./TicketCard";

const SCOPES = ["upcoming", "past", "cancelled"];

/**
 * "My tickets" tab: Upcoming / Past / Cancelled (?show=past), 10 at a time with "Load more".
 */
function Tickets() {
  const { t } = useTranslation();
  const { overview, reloadOverview } = useOutletContext();
  const [searchParams, setSearchParams] = useSearchParams();
  const scope = SCOPES.includes(searchParams.get("show")) ? searchParams.get("show") : "upcoming";

  // Bumped after cancel / refund to load the list again from page 1.
  const [version, setVersion] = useState(0);
  const request = `${scope}#${version}`;
  const [list, setList] = useState({ request: null, tickets: [], page: 1, lastPage: 1, error: "" });
  const [loadingMore, setLoadingMore] = useState(false);
  const [actionError, setActionError] = useState("");

  useEffect(() => {
    let cancelled = false;

    getTickets({ scope, page: 1 })
      .then(
        ({ data }) =>
          !cancelled &&
          setList({ request, tickets: data.data, page: data.current_page, lastPage: data.last_page, error: "" }),
      )
      .catch(
        (err) =>
          !cancelled &&
          setList({ request, tickets: [], page: 1, lastPage: 1, error: getErrorMessage(err, t("common.error")) }),
      );

    return () => {
      cancelled = true;
    };
  }, [scope, request, t]);

  const loading = list.request !== request;

  const loadMore = async () => {
    setLoadingMore(true);
    try {
      const { data } = await getTickets({ scope, page: list.page + 1 });
      setList((previous) => ({
        ...previous,
        tickets: [...previous.tickets, ...data.data],
        page: data.current_page,
        lastPage: data.last_page,
      }));
    } catch (err) {
      setActionError(getErrorMessage(err, t("common.error")));
    } finally {
      setLoadingMore(false);
    }
  };

  const runAction = async (confirmText, action) => {
    if (!window.confirm(confirmText)) return;

    setActionError("");
    try {
      await action();
      setVersion((v) => v + 1);
      reloadOverview();
    } catch (err) {
      setActionError(getErrorMessage(err, t("common.error")));
    }
  };

  return (
    <section>
      <div className="segmented" role="tablist">
        {SCOPES.map((name) => (
          <button
            key={name}
            type="button"
            role="tab"
            aria-selected={scope === name}
            className={scope === name ? "active" : ""}
            onClick={() => setSearchParams(name === "upcoming" ? {} : { show: name })}
          >
            {t(`profile.tab_${name}`)}
            {overview && <span className="segmented-count">{overview.counts[name]}</span>}
          </button>
        ))}
      </div>

      {(list.error || actionError) && <p className="alert alert-error">{list.error || actionError}</p>}
      {loading && <p className="muted">{t("common.loading")}</p>}

      {!loading && list.tickets.length === 0 && !list.error && (
        <div className="card empty-state">
          <p className="muted">{t(`profile.empty_${scope}`)}</p>
          {scope === "upcoming" && (
            <Link className="btn" to="/">
              {t("profile.find_train")}
            </Link>
          )}
        </div>
      )}

      {!loading && (
        <div className="card-list">
          {list.tickets.map((ticket) => (
            <TicketCard
              key={ticket.id}
              ticket={ticket}
              upcoming={scope === "upcoming"}
              onCancel={(tk) => runAction(t("tickets.confirm_cancel"), () => cancelTicket(tk.id))}
              onRefund={(tk) => runAction(t("tickets.confirm_refund"), () => refundTicket(tk.id))}
            />
          ))}
        </div>
      )}

      {!loading && list.page < list.lastPage && (
        <div className="load-more">
          <button className="btn btn-outline" onClick={loadMore} disabled={loadingMore}>
            {loadingMore ? t("common.loading") : t("profile.load_more")}
          </button>
        </div>
      )}
    </section>
  );
}

export default Tickets;
