# ClearStay 4.1 Production Engineering Runbook

**Audience**: DevOps, SRE & Web Application Engineers  
**Scope**: Operational incident response, database migrations, zero-downtime maintenance.  

---

## 1. Routine Deployment Workflow

```bash
# Step 1: Validate repository integrity and tests locally
composer validate --strict
php artisan test

# Step 2: Push changes safely to GitHub main branch
git push origin main

# Step 3: Trigger cloud build (Render / Railway / Fly.io / AWS)
# The configured GitHub Actions CI or PaaS webhook builds the Dockerfile.

# Step 4: Execute database schema migrations safely
php artisan migrate --force

# Step 5: Optimize cached route and configuration catalogues
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 6: Verify healthcheck endpoint
curl -s -f https://your-deployed-domain.com/up
```

---

## 2. Common Operational Incidents & Triage

### Incident A: Healthcheck Returns 500
- **Symptoms**: `/up` endpoint returns HTTP 500; orchestrator marks container unhealthy.
- **Cause**: Database connection failure or unrun migration.
- **Triage**:
  ```bash
  # Check connection from container
  php artisan db:show
  # Verify environment variables
  php artisan about --only=environment
  ```

### Incident B: OTA Synchronization Queue Latency
- **Symptoms**: Outbox sync logs report delayed transmission to Temairazu.
- **Triage**:
  ```bash
  # Check failed jobs in queue
  php artisan queue:failed
  # Retry all failed synchronization payloads
  php artisan queue:retry all
  ```
