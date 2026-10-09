import { lazy, Suspense } from "react";
import { BrowserRouter, Navigate, Route, Routes } from "react-router-dom";
import { AuthProvider } from "./context/Auth";
import Navbar from "./components/Navbar";
import Footer from "./components/Footer";
import RequireAuth from "./components/RequireAuth";
import RequireAdmin from "./admin/RequireAdmin";

import Home from "./pages/Home";
import Departures from "./pages/Departures";

import Login from "./pages/Login";
import Register from "./pages/Register";
import ForgotPassword from "./pages/ForgotPassword";
import ResetPassword from "./pages/ResetPassword";
import EmailVerified from "./pages/EmailVerified";
import Profile from "./pages/profile/Profile";
import Account from "./pages/profile/Account";
import Tickets from "./pages/profile/Tickets";

import Payment from "./pages/Payment";
import PaymentResult from "./pages/PaymentResult";

import NotFound from "./pages/NotFound";

const TrainMap = lazy(() => import("./pages/Map"));
// The admin panel (and its Tailwind CSS) is a separate bundle, only loaded for admins.
const AdminRoutes = lazy(() => import("./admin/AdminRoutes"));

const loggedIn = (page) => <RequireAuth>{page}</RequireAuth>;

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Navbar />

        <Routes>
          
          <Route path="/" element={<Home />} />
          <Route
            path="/map"
            element={
              <Suspense fallback={<main className="page" />}>
                <TrainMap />
              </Suspense>
            }
          />
          <Route path="/departures/:stopId?" element={<Departures />} />

          
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/forgot-password" element={<ForgotPassword />} />
          <Route path="/reset-password" element={<ResetPassword />} />
          <Route path="/email-verified" element={<EmailVerified />} />

          
          <Route path="/profile" element={loggedIn(<Profile />)}>
            <Route index element={<Account />} />
            <Route path="tickets" element={<Tickets />} />
          </Route>

          
          <Route path="/trains" element={<Navigate to="/" replace />} />
          <Route path="/tickets" element={<Navigate to="/profile/tickets" replace />} />
          <Route path="/stops" element={<Navigate to="/departures" replace />} />
          <Route path="/routes" element={<Navigate to="/map" replace />} />

          {/* Payments: Stripe Checkout sends the user back to the result page */}
          <Route path="/payment/:ticketId" element={loggedIn(<Payment />)} />
          <Route path="/payment/:paymentId/result" element={loggedIn(<PaymentResult />)} />

          <Route
            path="/admin/*"
            element={
              <RequireAdmin>
                <Suspense fallback={<main className="page" />}>
                  <AdminRoutes />
                </Suspense>
              </RequireAdmin>
            }
          />

          <Route path="*" element={<NotFound />} />
        </Routes>

        <Footer />
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;
