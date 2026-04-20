window.Api = {
  getToken() {
    return localStorage.getItem("token");
  },
  setToken(token) {
    localStorage.setItem("token", token);
  },
  clearToken() {
    localStorage.removeItem("token");
  },

  errorMessage(payload) {
    if (!payload) return 'Nepavyko';
    if (typeof payload === 'string') return payload;
    return payload.zinute ?? payload.message ?? 'Nepavyko';
  },

  formatDate(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
  },

  async request(method, url, data = null) {
    const headers = { "Accept": "application/json" };

    if (data !== null) headers["Content-Type"] = "application/json";

    const token = this.getToken();
    if (token) headers["Authorization"] = `Bearer ${token}`;

    const res = await fetch(url, {
      method,
      headers,
      body: data !== null ? JSON.stringify(data) : null,
    });

    const ct = (res.headers.get("content-type") || "").toLowerCase();
    const payload = ct.includes("application/json")
      ? await res.json().catch(() => ({}))
      : await res.text().catch(() => "");

    if (res.status === 401) {
      this.clearToken();
      if (window.UI?.setAuthNav) window.UI.setAuthNav(false);
    }

    return { ok: res.ok, status: res.status, payload };
  }
};

window.UI = {
  setAuthNav(isLoggedIn) {
    const navPrisijungti = document.getElementById("navPrisijungti");
    const navRegistruotis = document.getElementById("navRegistruotis");
    const navProfilis = document.getElementById("navProfilis");
    const atsijungtiBtn = document.getElementById("atsijungtiBtn");

    if (!navPrisijungti) return;

    if (isLoggedIn) {
      navPrisijungti.classList.add("hide");
      navRegistruotis.classList.add("hide");
      navProfilis.classList.remove("hide");
      atsijungtiBtn.classList.remove("hide");
    } else {
      navPrisijungti.classList.remove("hide");
      navRegistruotis.classList.remove("hide");
      navProfilis.classList.add("hide");
      atsijungtiBtn.classList.add("hide");
    }
  }
};

window.addEventListener("DOMContentLoaded", () => {
  UI.setAuthNav(!!Api.getToken());

  const btn = document.getElementById("atsijungtiBtn");
  if (btn) {
    btn.addEventListener("click", async () => {
      await Api.request("POST", "/api/atsijungti");
      Api.clearToken();
      UI.setAuthNav(false);
      alert("Atsijungta");
      window.location.href = "/";
    });
  }
});