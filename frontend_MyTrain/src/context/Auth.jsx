import { createContext, useCallback, useContext, useEffect, useState } from "react";
import * as authApi from "../api/auth";

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const refreshUser = useCallback(
    () =>
      authApi
        .me()
        .then((res) => setUser(res.data))
        .catch(() => setUser(null)),
    []
  );

  // On page load: is there already a logged-in session?
  useEffect(() => {
    refreshUser().finally(() => setLoading(false));
  }, [refreshUser]);

  // The backend logs the user in on register too, so both return the user.
  const login = async (email, password) => {
    const res = await authApi.login(email, password);
    setUser(res.data.user);
    return res.data.user;
  };

  const register = async (fields) => {
    const res = await authApi.register(fields);
    setUser(res.data.user);
  };

  const logout = async () => {
    await authApi.logout();
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout, refreshUser }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
