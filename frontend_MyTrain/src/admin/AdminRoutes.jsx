import { Route, Routes } from "react-router-dom";
import AdminLayout from "./AdminLayout";
import Dashboard from "./pages/Dashboard";
import Tickets from "./pages/Tickets";
import TicketDetail from "./pages/TicketDetail";
import Users from "./pages/Users";
import UserEdit from "./pages/UserEdit";
import Trains from "./pages/Trains";
import Activity from "./pages/Activity";
import Account from "./pages/Account";

/**
 * Everything under /admin. Loaded as a separate bundle, only when an admin opens the panel.
 */
function AdminRoutes() {
  return (
    <Routes>
      <Route element={<AdminLayout />}>
        <Route index element={<Dashboard />} />
        <Route path="tickets" element={<Tickets />} />
        <Route path="tickets/:ticketId" element={<TicketDetail />} />
        <Route path="users" element={<Users />} />
        <Route path="users/:userId" element={<UserEdit />} />
        <Route path="trains" element={<Trains />} />
        <Route path="activity" element={<Activity />} />
        <Route path="account" element={<Account />} />
      </Route>
    </Routes>
  );
}

export default AdminRoutes;
