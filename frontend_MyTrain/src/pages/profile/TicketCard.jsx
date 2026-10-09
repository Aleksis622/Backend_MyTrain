import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { TrainStatusBadge } from "../../components/TrainStatus";
import { formatDay, formatPrice } from "../../utils/format";
import TripLine from "./TripLine";

/**
 * One ticket, shaped like a boarding pass: the trip on the left, status / price / code on the stub.
 * upcoming: show the train's live status (delays only matter before the trip).
 */
function TicketCard({ ticket, upcoming, onCancel, onRefund }) {
  const { t, i18n } = useTranslation();
  const { journey, train_status: trainStatus } = ticket;
  const status = ticket.status;
  const trainCancelled = trainStatus?.status === "cancelled";
  const delay = trainStatus?.status === "delayed" ? trainStatus.delay_minutes : 0;
  const showTrainStatus = upcoming && (status === "pending" || status === "paid") && trainStatus;

  return (
    <article className={`ticket-pass is-${status}`}>
      <div className="ticket-pass-main">
        <div className="ticket-pass-top">
          <strong>{formatDay(journey?.departure_time, i18n.language)}</strong>
          <span className="muted">
            {t("profile.train")} {journey?.trip?.route?.route_short_name || journey?.trip_id}
          </span>
        </div>

        {journey && (
          <TripLine journey={journey} delay={showTrainStatus ? delay : 0} cancelled={showTrainStatus && trainCancelled} />
        )}

        {showTrainStatus && (
          <TrainStatusBadge status={trainStatus.status} delay={trainStatus.delay_minutes} reason={trainStatus.reason} />
        )}
      </div>

      <div className="ticket-pass-stub">
        <span className={`badge badge-${status}`}>{t(`tickets.status.${status}`)}</span>
        <span className="ticket-pass-price">{formatPrice(ticket.price, ticket.currency)}</span>
        <span className="ticket-pass-code" title={t("tickets.code")}>
          {ticket.ticket_code}
        </span>

        {status === "pending" && upcoming && (
          <div className="ticket-pass-actions">
            <Link className="btn" to={`/payment/${ticket.id}`}>
              {t("tickets.pay")}
            </Link>
            <button className="btn btn-outline" onClick={() => onCancel(ticket)}>
              {t("tickets.cancel")}
            </button>
          </div>
        )}

        {/* the server decides: until departure, or any time when the train was cancelled */}
        {ticket.refundable && (
          <div className="ticket-pass-actions">
            <button className="btn btn-outline" onClick={() => onRefund(ticket)}>
              {t("tickets.refund")}
            </button>
          </div>
        )}
      </div>
    </article>
  );
}

export default TicketCard;
