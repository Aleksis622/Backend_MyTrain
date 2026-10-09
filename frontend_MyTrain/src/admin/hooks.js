import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { getErrorMessage } from "../api/api";

/**
 * Loads data for an admin page: useAdminQuery(adminApi.users, { search, page }).
 * "fetcher" must be a module-level function (e.g. from api/admin.js); it gets "params" as its argument.
 * Returns { data, error, loading, reload, setData }. Old data stays visible while reloading.
 */
export function useAdminQuery(fetcher, params) {
  const { i18n } = useTranslation();
  const key = JSON.stringify(params ?? null);
  const [version, setVersion] = useState(0);
  const request = `${key}#${version}`;
  const [result, setResult] = useState({ request: null, data: null, error: "" });

  useEffect(() => {
    let cancelled = false;

    fetcher(JSON.parse(key) ?? undefined)
      .then((res) => !cancelled && setResult({ request, data: res.data, error: "" }))
      .catch(
        (err) =>
          !cancelled &&
          setResult((previous) => ({
            request,
            data: previous.data,
            error: getErrorMessage(err, i18n.t("common.error")),
          })),
      );

    return () => {
      cancelled = true;
    };
  }, [fetcher, key, request, i18n]);

  return {
    data: result.data,
    error: result.request === request ? result.error : "",
    loading: result.request !== request,
    reload: () => setVersion((v) => v + 1),
    setData: (data) => setResult((previous) => ({ ...previous, data })),
  };
}

/**
 * Runs a button action (save, refund, delete...) with a busy flag and a success/error message.
 * run(() => api.call(), "Saved") returns the response, or null when it failed.
 */
export function useAdminAction() {
  const { t } = useTranslation();
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState(null); // { type: "success" | "error", text }

  const run = async (action, successText) => {
    setBusy(true);
    setMessage(null);
    try {
      const response = await action();
      if (successText) setMessage({ type: "success", text: successText });
      return response;
    } catch (err) {
      setMessage({ type: "error", text: getErrorMessage(err, t("common.error")) });
      return null;
    } finally {
      setBusy(false);
    }
  };

  return { busy, message, run };
}
