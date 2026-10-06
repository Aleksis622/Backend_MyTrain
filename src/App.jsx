import { lazy, Suspense } from "react";
import { BrowserRouter, Route, Routes } from "react-router-dom";
import { AuthProvider } from "./context/Auth";
import Navbar from "./components/Navbar";
import Footer from "./components/Footer";
import RequireAuth from "./components/RequireAuth";

import Home from "./pages/Home";
import Trains from "./pages/Trains";
import RoutesPage from "./pages/Routes";
import Stops from "./pages/Stops";

import Login from "./pages/Login";
import Register from "./pages/Register";
import ForgotPassword from "./pages/ForgotPassword";
import ResetPassword from "./pages/ResetPassword";
import EmailVerified from "./pages/EmailVerified";
import Profile from "./pages/Profile";

import Tickets from "./pages/Tickets";
import Payment from "./pages/Payment";
import FakeProvider from "./pages/FakeProvider";
import PaymentSuccess from "./pages/PaymentSuccess";
import PaymentFailed from "./pages/PaymentFailed";

import NotFound from "./pages/NotFound";

// Mapbox is large, so it is only downloaded when the map page is opened.
const TrainMap = lazy(() => import("./pages/Map"));

const loggedIn = (page) => <RequireAuth>{page}</RequireAuth>;

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Navbar />

        <Routes>
          {/* Public */}
          <Route path="/" element={<Home />} />
          <Route path="/trains" element={<Trains />} />
          <Route
            path="/map"
            element={
              <Suspense fallback={<main className="page" />}>
                <TrainMap />
              </Suspense>
            }
          />
          <Route path="/routes" element={<RoutesPage />} />
          <Route path="/stops" element={<Stops />} />

          {/* Account (the backend emails link to /reset-password and /email-verified) */}
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/forgot-password" element={<ForgotPassword />} />
          <Route path="/reset-password" element={<ResetPassword />} />
          <Route path="/email-verified" element={<EmailVerified />} />
          <Route path="/profile" element={loggedIn(<Profile />)} />

          {/* Tickets and payments */}
          <Route path="/tickets" element={loggedIn(<Tickets />)} />
          <Route path="/payment/:ticketId" element={loggedIn(<Payment />)} />
          <Route path="/payment/:paymentId/provider" element={loggedIn(<FakeProvider />)} />
          <Route path="/payment/:paymentId/success" element={loggedIn(<PaymentSuccess />)} />
          <Route path="/payment/:paymentId/failed" element={loggedIn(<PaymentFailed />)} />

          <Route path="*" element={<NotFound />} />
        </Routes>

        <Footer />
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;
