import { useState } from "react";
import { useTranslation } from "react-i18next";
import * as adminApi from "../../api/admin";
import ActivityList from "../ActivityList";
import { useAdminQuery } from "../hooks";
import { Alert, Card, PageHeader, Pagination } from "../ui";

const TYPES = ["", "ticket", "user", "train", "account"];

/**
 * Everything admins did in the panel, newest first, filterable by kind.
 */
function Activity() {
  const { t } = useTranslation();
  const [filters, setFilters] = useState({ type: "", page: 1 });
  const { data, error, loading } = useAdminQuery(adminApi.activity, filters);

  return (
    <>
      <PageHeader title={t("admin.nav_activity")} />
      <p className="-mt-3 mb-5 text-sm text-subtle">{t("admin.activity_hint")}</p>

      <div className="mb-4 flex flex-wrap gap-2">
        {TYPES.map((type) => (
          <button
            key={type || "all"}
            type="button"
            onClick={() => setFilters({ type, page: 1 })}
            className={`cursor-pointer rounded-full border px-4 py-1.5 font-[inherit] text-sm font-semibold ${
              filters.type === type ? "border-brand bg-brand text-white" : "border-line bg-card text-subtle hover:text-ink"
            }`}
          >
            {type ? t(`admin.activity_types.${type}`) : t("admin.all")}
          </button>
        ))}
      </div>

      <Alert>{error}</Alert>
      {loading && !data && <p className="text-subtle">{t("common.loading")}</p>}

      {data && (
        <>
          <Card className="py-2">
            <ActivityList actions={data.data} showAdmin />
          </Card>
          <Pagination page={data} onChange={(page) => setFilters({ ...filters, page })} />
        </>
      )}
    </>
  );
}

export default Activity;
