const BASE = '/adminflow'; 

// ── Helper fetch ────────────────────────────────────────────
async function apiFetch(endpoint, method = 'GET', body = null) {
  const opts = {
    method,
    credentials: 'include',   //cookie
    headers: { 'Content-Type': 'application/json' },
  };
  if (body) opts.body = JSON.stringify(body);

  const res  = await fetch(BASE + endpoint, opts);
  const json = await res.json();

  if (!json.ok) {
    showT(json.msg || 'Erro desconhecido', 'er');
    throw new Error(json.msg);
  }
  return json.data;
}

// ── AUTH ────────────────────────────────────────────────────
const Auth = {
  login:  (login, pass)    => apiFetch('/auth.php', 'POST', { action:'login', login, pass }),
  logout: ()               => apiFetch('/auth.php?action=logout'),
  check:  ()               => apiFetch('/auth.php?action=check'),
  changePass: (old_pass, new_pass) =>
    apiFetch('/auth.php', 'POST', { action:'change_pass', old_pass, new_pass }),
};

// ── FUNCIONÁRIOS ────────────────────────────────────────────
const FuncAPI = {
  list:        ()           => apiFetch('/funcionarios.php?action=list'),
  get:         (id)         => apiFetch(`/funcionarios.php?action=get&id=${id}`),
  create:      (data)       => apiFetch('/funcionarios.php', 'POST', { action:'create', ...data }),
  update:      (data)       => apiFetch('/funcionarios.php', 'POST', { action:'update', ...data }),
  delete:      (id)         => apiFetch('/funcionarios.php', 'POST', { action:'delete', id }),
  toggleOnline:(id)         => apiFetch('/funcionarios.php', 'POST', { action:'toggle_online', id }),
  setOnline:   (id, online) => apiFetch('/funcionarios.php', 'POST', { action:'set_online', id, online }),
  horasMes:    (func_id)    => apiFetch(`/funcionarios.php?action=horas_mes&func_id=${func_id}`),
};

// ── DIVISÕES ────────────────────────────────────────────────
const DivAPI = {
  list:   ()     => apiFetch('/divisoes.php?action=list'),
  get:    (id)   => apiFetch(`/divisoes.php?action=get&id=${id}`),
  create: (data) => apiFetch('/divisoes.php', 'POST', { action:'create', ...data }),
  update: (data) => apiFetch('/divisoes.php', 'POST', { action:'update', ...data }),
  toggle: (id)   => apiFetch('/divisoes.php', 'POST', { action:'toggle', id }),
  delete: (id)   => apiFetch('/divisoes.php', 'POST', { action:'delete', id }),
};

// ── TICKETS ─────────────────────────────────────────────────
const TkAPI = {
  list:    ()     => apiFetch('/tickets.php?action=list'),
  create:  (data) => apiFetch('/tickets.php', 'POST', { action:'create', ...data }),
  resolve: (id)   => apiFetch('/tickets.php', 'POST', { action:'resolve', id }),
  delete:  (id)   => apiFetch('/tickets.php', 'POST', { action:'delete', id }),
};

// ── BATE-PONTO ──────────────────────────────────────────────
const BpAPI = {
  registar:      (funcionario_id, tipo, obs='') =>
    apiFetch('/bateponto.php', 'POST', { action:'registar', funcionario_id, tipo, observacao: obs }),
  turnoCompleto: (funcionario_id, horas)        =>
    apiFetch('/bateponto.php', 'POST', { action:'turno_completo', funcionario_id, horas }),
  hoje:          (func_id = null) =>
    apiFetch('/bateponto.php?action=hoje' + (func_id ? `&func_id=${func_id}` : '')),
  historico:     (func_id = null, limit = 100) =>
    apiFetch('/bateponto.php?action=historico' + (func_id ? `&func_id=${func_id}` : '') + `&limit=${limit}`),
  resumoMes:     () => apiFetch('/bateponto.php?action=resumo_mes'),
};

// ── CATEGORIAS ──────────────────────────────────────────────
const CatAPI = {
  list:   ()     => apiFetch('/categorias.php?action=list'),
  create: (data) => apiFetch('/categorias.php', 'POST', { action:'create', ...data }),
  update: (data) => apiFetch('/categorias.php', 'POST', { action:'update', ...data }),
  delete: (id)   => apiFetch('/categorias.php', 'POST', { action:'delete', id }),
};

// ── REGISTROS ───────────────────────────────────────────────
const RegAPI = {
  list:   (page=1, limit=20) => apiFetch(`/registros.php?action=list&page=${page}&limit=${limit}`),
  create: (data)             => apiFetch('/registros.php', 'POST', { action:'create', ...data }),
  update: (data)             => apiFetch('/registros.php', 'POST', { action:'update', ...data }),
  delete: (id)               => apiFetch('/registros.php', 'POST', { action:'delete', id }),
};

// ── EMAIL ───────────────────────────────────────────────────
const EmailAPI = {
  send: (func_id, assunto, mensagem) =>
    apiFetch('/email.php', 'POST', { action:'send', func_id, assunto, mensagem }),
};

// ── INICIALIZAÇÃO (substitui bootApp) ───────────────────────

async function doLoginAPI() {
  const login = document.getElementById('l-user').value.trim().toLowerCase();
  const pass  = document.getElementById('l-pass').value;
  try {
    const user = await Auth.login(login, pass);
    // Valida role vs tab seleccionado
    if (ltabMode === 'admin' && user.role !== 'admin') {
      document.getElementById('lerr').style.display = 'block'; return;
    }
    if (ltabMode === 'func' && user.role !== 'func') {
      document.getElementById('lerr').style.display = 'block'; return;
    }
    session = user;
    document.getElementById('lerr').style.display = 'none';
    bootApp();
  } catch {
    document.getElementById('lerr').style.display = 'block';
  }
}

// ── LOGOUT com API ───────────────────────────────────────────
async function doLogoutAPI() {
  try { await Auth.logout(); } catch {}
  doLogout(); // função original do HTML
}
