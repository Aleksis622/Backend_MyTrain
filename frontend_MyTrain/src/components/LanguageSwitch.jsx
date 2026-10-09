import { useTranslation } from "react-i18next";
import { useAuth } from "../context/Auth";
import { changeLanguage } from "../api/auth";
import { LANGUAGES } from "../lang";

/**
 * LV / EN / RU buttons, used in the site navbar and the admin top bar.
 */
function LanguageSwitch() {
  const { i18n } = useTranslation();
  const { user } = useAuth();

  const switchLanguage = (lang) => {
    i18n.changeLanguage(lang);

    // Also save it on the account, so backend emails use the same language.
    if (user) {
      changeLanguage(lang).catch(() => {});
    }
  };

  return (
    <div className="lang-switch">
      {LANGUAGES.map((lang) => (
        <button
          key={lang}
          type="button"
          className={i18n.language === lang ? "active" : ""}
          onClick={() => switchLanguage(lang)}
        >
          {lang.toUpperCase()}
        </button>
      ))}
    </div>
  );
}

export default LanguageSwitch;
