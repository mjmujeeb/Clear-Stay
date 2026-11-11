# ClearStay 4.1 Production Environment Variables Reference

| Variable Name | Required | Default / Format | Description |
|:---|:---:|:---|:---|
| `APP_NAME` | No | `"ClearStay Enterprise"` | Application branding displayed in emails, tabs, and headers. |
| `APP_ENV` | **Yes** | `production` | Environment mode. Controls debug outputs and error masking. |
| `APP_KEY` | **Yes** | `base64:...` (32 bytes) | Cryptographic key used to encrypt cookies, session IDs, and PII. |
| `APP_DEBUG` | **Yes** | `false` | Must NEVER be `true` in production to prevent stack trace leaks. |
| `APP_URL` | **Yes** | `https://your-domain.com` | Base public URL for generating canonical links and redirects. |
| `DB_CONNECTION` | **Yes** | `mysql` / `pgsql` / `sqlite` | Database driver to connect with. |
| `DB_HOST` | **Yes** | IP or hostname | Database host address. |
| `DB_PORT` | **Yes** | `3306` (MySQL) / `5432` (PG) | Relational database port. |
| `DB_DATABASE` | **Yes** | `clearstay_prod` | Target database schema name. |
| `DB_USERNAME` | **Yes** | String | Database authentication username. |
| `DB_PASSWORD` | **Yes** | String | Database authentication secret password. |
| `SESSION_DRIVER` | **Yes** | `database` or `redis` | Session persistence engine. |
| `CACHE_STORE` | **Yes** | `database` or `redis` | Cache storage backend. |
| `QUEUE_CONNECTION` | **Yes** | `database` or `redis` | Background queue processor for OTA synchronization. |
| `DEPLOY_BYPASS_TOKEN` | **Yes** | Random hex string | Secure token enabling authorized staff to bypass maintenance mode. |
