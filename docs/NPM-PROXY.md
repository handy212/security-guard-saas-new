# Nginx Proxy Manager (external reverse proxy)

Use this when **NPM runs on a different server** and GuardOps runs on its own VPS.

```text
Browser ──HTTPS──► NPM server ──HTTP──► GuardOps VPS :8080 (Docker nginx)
                              └──WS────► GuardOps VPS :8081 (Reverb)
```

DNS points at the **NPM server**, not the GuardOps VPS.

---

## 1. DNS (at your registrar / Cloudflare)

| Record | Points to |
|--------|-----------|
| `app.yourdomain.com` | NPM server public IP |
| `*.yourdomain.com` | NPM server public IP |

Tenant subdomains (`demo-security.yourdomain.com`) hit NPM first, then forward to GuardOps.

---

## 2. GuardOps VPS — Docker

Use the NPM proxy compose override (not `docker-compose.prod.yml`, which binds localhost only):

```bash
cd /var/www/guardops
docker compose -f docker-compose.yml -f docker-compose.npm-proxy.yml down
docker compose -f docker-compose.yml -f docker-compose.npm-proxy.yml up -d --build --force-recreate
```

Verify:

```bash
docker ps
# nginx  → 0.0.0.0:8080->80/tcp
# reverb → 0.0.0.0:8081->8081/tcp
# mysql/redis → no public ports
```

### Firewall — allow only NPM

Replace `NPM_SERVER_IP` with your proxy server’s public (or private VPC) IP:

```bash
ufw deny 8080/tcp
ufw deny 8081/tcp
ufw allow from NPM_SERVER_IP to any port 8080 proto tcp
ufw allow from NPM_SERVER_IP to any port 8081 proto tcp
ufw status
```

If both servers share a private network (e.g. `10.0.0.0/24`), use the private IP and allow that instead.

**Skip** host nginx and Certbot on the GuardOps VPS — NPM handles TLS.

---

## 3. GuardOps `.env`

```env
APP_URL=https://app.yourdomain.com
APP_ENV=production
APP_DEBUG=false

TENANCY_BASE_DOMAIN=yourdomain.com

SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=guardops
REVERB_APP_KEY=your-random-key
REVERB_APP_SECRET=your-random-secret
REVERB_HOST=app.yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
```

Rebuild frontend after changing Reverb vars:

```bash
npm ci && npm run build
docker compose exec app php artisan config:cache
```

---

## 4. NPM — Proxy Host (main app)

**Hosts → Proxy Hosts → Add Proxy Host**

| Field | Value |
|-------|-------|
| Domain names | `app.yourdomain.com` |
| Scheme | `http` |
| Forward hostname / IP | GuardOps VPS IP (or private IP) |
| Forward port | `8080` |
| Block common exploits | On |
| Websockets Support | On |
| SSL | Request new certificate (HTTP challenge) |

**Custom locations** tab — add WebSocket route for Reverb:

| Field | Value |
|-------|-------|
| Define location | `app` |
| Scheme | `http` |
| Forward hostname / IP | GuardOps VPS IP |
| Forward port | `8081` |

Or paste in the **Advanced** tab:

```nginx
location /app {
    proxy_pass http://GUARDOPS_VPS_IP:8081;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_read_timeout 60s;
}
```

Replace `GUARDOPS_VPS_IP` with the IP NPM uses to reach GuardOps.

---

## 5. NPM — Wildcard tenants

Add a **second** Proxy Host (or add domains to the first host):

| Field | Value |
|-------|-------|
| Domain names | `*.yourdomain.com` |
| Forward port | `8080` |
| SSL | Wildcard cert (`*.yourdomain.com`) — use **DNS challenge** in NPM |

Same Custom location `/app` → `:8081` as above.

---

## 6. Alternative: separate WebSocket subdomain

Simpler NPM setup — no custom `/app` location:

**Proxy Host 2**

| Field | Value |
|-------|-------|
| Domain | `ws.yourdomain.com` |
| Forward port | `8081` |
| Websockets Support | **On** |
| SSL | Certificate for `ws.yourdomain.com` |

`.env` change:

```env
REVERB_HOST=ws.yourdomain.com
VITE_REVERB_HOST=ws.yourdomain.com
```

Rebuild assets and `php artisan config:cache`.

---

## 7. Health check

From the **NPM server**:

```bash
curl -s http://GUARDOPS_VPS_IP:8080/up
```

From a browser:

- `https://app.yourdomain.com/up` → `{"status":"ok"}`
- `https://app.yourdomain.com` → login page

---

## 8. Troubleshooting

| Symptom | Fix |
|---------|-----|
| 502 from NPM | GuardOps `:8080` down, or UFW blocking NPM IP |
| Login redirect loop / mixed content | `APP_URL` must be `https://...`; trusted proxies enabled |
| WebSockets fail (dispatch room) | Check `/app` location → `:8081`, or use `ws.` subdomain |
| Tenant subdomain 404 | Wildcard `*.yourdomain.com` proxy host in NPM + DNS |
| Session lost on login | `SESSION_SECURE_COOKIE=true`, NPM sends `X-Forwarded-Proto: https` |

---

## Architecture summary

| Server | Role |
|--------|------|
| NPM VPS | Public IP, SSL, reverse proxy |
| GuardOps VPS | Docker (nginx, PHP, MySQL, Redis, queue, Reverb) — no public SSL |

See also `docs/VPS-DEPLOYMENT.md` for initial GuardOps server setup.
