import { useState } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import * as adminApi from "../../api/admin";
import { useAdminQuery } from "../hooks";
import { Alert, Badge, Button, PageHeader, Pagination, Table, cellClass, inputClass } from "../ui";
import { formatDateTime } from "../../utils/format";

function Users() {
  const { t, i18n } = useTranslation();
  const [searchText, setSearchText] = useState("");
  const [filters, setFilters] = useState({ search: "", page: 1 });
  const { data, error, loading } = useAdminQuery(adminApi.users, filters);

  return (
    <>
      <PageHeader title={t("admin.nav_users")} />

      <form
        className="mb-4 flex gap-3 rounded-2xl border border-line bg-card p-4 shadow-card"
        onSubmit={(e) => {
          e.preventDefault();
          setFilters({ search: searchText.trim(), page: 1 });
        }}
      >
        <input
          className={inputClass}
          placeholder={t("admin.search_users")}
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
        />
        <Button type="submit">{t("admin.search")}</Button>
      </form>

      <Alert>{error}</Alert>
      {loading && !data && <p className="text-subtle">{t("common.loading")}</p>}
      {data && data.data.length === 0 && <p className="text-subtle">{t("admin.no_users")}</p>}

      {data && data.data.length > 0 && (
        <>
          <p className="mb-2 text-sm text-subtle">{t("admin.results", { count: data.total })}</p>
          <Table
            head={[
              t("auth.name"),
              t("auth.email"),
              t("profile.language"),
              t("admin.nav_tickets"),
              t("admin.registered"),
              "",
            ]}
          >
            {data.data.map((user) => (
              <tr key={user.id} className="hover:bg-canvas">
                <td className={cellClass}>
                  {user.name} {user.is_admin && <Badge color="blue">{t("admin.role_admin")}</Badge>}
                </td>
                <td className={cellClass}>
                  {user.email}
                  {!user.email_verified_at && (
                    <div className="text-xs text-warn">{t("admin.not_verified")}</div>
                  )}
                </td>
                <td className={cellClass}>{user.language?.toUpperCase()}</td>
                <td className={cellClass}>{user.tickets_count}</td>
                <td className={cellClass}>{formatDateTime(user.created_at, i18n.language)}</td>
                <td className={`${cellClass} text-right`}>
                  <Link to={`/admin/users/${user.id}`} className="text-brand">
                    {t("admin.edit")}
                  </Link>
                </td>
              </tr>
            ))}
          </Table>
          <Pagination page={data} onChange={(page) => setFilters({ ...filters, page })} />
        </>
      )}
    </>
  );
}

export default Users;
