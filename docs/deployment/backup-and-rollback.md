# ClearStay 4.1 Backup, Disaster Recovery & Rollback Protocol

---

## 1. Zero-Downtime Deployment & Rollback Strategy

ClearStay utilizes atomic release switching and safe fast-forward deployments to prevent partial state bugs.

### Rolling Release Sequence:
1. Cloud container orchestrator builds new Docker image from verified git commit.
2. Container starts up in isolated staging pod.
3. Health check probes `GET /up` until HTTP 200 is confirmed.
4. Database migrations execute non-destructively:
   ```bash
   php artisan migrate --force
   ```
5. Ingress traffic is shifted 100% to new container instance; old container gracefully drains active requests.

### Immediate 1-Click Rollback:
If an unexpected anomaly occurs post-deployment:
1. **Container Rollback**: In Railway / Render / Fly dashboard, click "Rollback to Previous Deployment". Traffic immediately reverts to previous container image in < 15 seconds.
2. **Database Rollback (if schema changed)**:
   ```bash
   php artisan migrate:rollback --step=1
   ```
3. **Emergency Maintenance Mode with Dynamic Bypass**:
   ```bash
   php artisan down --secret="${DEPLOY_BYPASS_TOKEN}" --render="errors.503"
   ```

---

## 2. Automated Backup Policy

1. **Database Snapshots**: Cloud platform performs automated daily snapshots retained for 30 days. Point-in-time recovery (PITR) is enabled.
2. **Local Manual Backup Command**:
   ```bash
   # Export MySQL dump
   mysqldump -u clearstay_user -p clearstay_db > storage/app/backups/backup_$(date +%Y%m%d_%H%M%S).sql
   ```
