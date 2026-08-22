import { createRoot } from "react-dom/client";
import Demo from "./components/auth-switch-demo";

const el = document.getElementById("react-auth-switch-root");

if (el) {
  createRoot(el).render(<Demo />);
}
