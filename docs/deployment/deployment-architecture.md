# ClearStay 4.1 Production Cloud Deployment Architecture

**Target Deployment Platforms**: Railway / Render / Fly.io / AWS ECS / Google Cloud Run  
**Architecture Paradigm**: Containerized Stateless Web Nodes with Managed Relational Storage & Redis Queue  

---

## 1. High-Level Architectural Topology

```
             [ Global CDN / Edge Proxy (Cloudflare / Cloud Front) ]
                                    │
                         (HTTPS / SSL Termination)
                                    ▼
       [ Load Balancer / Ingress Router (Port 8080 - HTTP/2) ]
                                    │
               ┌────────────────────┴────────────────────┐
               ▼                                         ▼
   [ ClearStay Web Container #1 ]             [ ClearStay Web Container #2 ]
   - Alpine Linux + PHP 8.3-FPM               - Alpine Linux + PHP 8.3-FPM
   - Nginx Reverse Proxy                      - Nginx Reverse Proxy
   - OPcache (Precompiled Bytecode)           - OPcache (Precompiled Bytecode)
   - Healthcheck: GET /up                     - Healthcheck: GET /up
               │                                         │
               ├────────────────────┬────────────────────┤
               ▼                    ▼                    ▼
     [ Managed Relational DB ] [ Managed Redis 7 ] [ Cloud Storage ]
     - MySQL 8.0 / Postgres    - Queue Workers     - MP4/WebM Videos
     - Multi-AZ Replication    - Realtime Locks    - Subtitle Files
     - Daily Snapshots         - Session Store     - Generated PDFs
```

---

## 2. Infrastructure Components

1. **Ingress & TLS**: Automatic HTTPS termination with Let's Encrypt certificates managed by the cloud PaaS (Railway, Render, Fly.io).
2. **Application Containers**: Packaged via Dockerfile with Alpine Linux, PHP 8.3-FPM, Nginx, and Supervisor. Zero external runtime dependencies.
3. **Database Layer**: Managed MySQL 8.0 or PostgreSQL with connection pooling. Migrations are executed during release phase (`php artisan migrate --force`).
4. **Queue & Background Worker**: Supervisor manages queue workers executing outbox synchronization tasks asynchronously without slowing user transactions.
