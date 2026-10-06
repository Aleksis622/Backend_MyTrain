import i18n from "i18next";
import { initReactI18next } from "react-i18next";

import en from "./en.json";
import lv from "./lv.json";
import ru from "./ru.json";

export const LANGUAGES = ["lv", "en", "ru"];

const savedLang = localStorage.getItem("lang");

i18n.use(initReactI18next).init({
  resources: {
    lv: { translation: lv },
    en: { translation: en },
    ru: { translation: ru },
  },
  lng: LANGUAGES.includes(savedLang) ? savedLang : "lv",
  fallbackLng: "en",
  interpolation: { escapeValue: false }, // React already escapes output
});

// Remember the choice and keep <html lang> correct.
i18n.on("languageChanged", (lang) => {
  localStorage.setItem("lang", lang);
  document.documentElement.lang = lang;
});
document.documentElement.lang = i18n.language;

export default i18n;
