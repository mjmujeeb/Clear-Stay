# ClearStay - AWS Production Infrastructure Architecture Guide

This document explains the AWS cloud architecture and configuration for ClearStay, specifically structured for technical interviews and production deployment.

---

## 1. High-Level AWS Architecture Diagram

```
                 [ Route 53 DNS ]
                         |
           (HTTPS 443 / SSL via Let's Encrypt)
                         v
       [ AWS EC2 / Lightsail (Ubuntu 22.04 LTS) ]
         ├── Nginx (Reverse Proxy & Static Cache)
         ├── PHP 8.3-FPM (Application Execution)
         ├── Supervisor (clearstay-worker daemon)
         ├── Cron (Nightly Backup & Task Schedule)
         ├── UFW Firewall & Fail2ban (Brute-force shield)
         └── Local NVMe SSD (Session / Fast file cache)
                         |
      +------------------+------------------+------------------+
      |                                     |                  |
      v                                     v                  v
[ AWS RDS MySQL 8.0 ]            [ AWS S3 Bucket ]     [ AWS SES ]
- Multi-AZ Deployment            - Public CSV exports  - Transactional
- Automated daily snapshots      - Gzipped DB backups    Confirmation Emails
- Row-Level Locking Engine       - Encrypted at rest   - SPF / DKIM signed
```

---

## 2. Component Specifications

### 2.1 AWS EC2 / Lightsail
- **Instance Size**: `t3.medium` (2 vCPU, 4GB RAM) or Lightsail 4GB tier.
- **OS**: Ubuntu 22.04 LTS / 24.04 LTS.
- **Security Group**:
  - `Inbound 22 (SSH)`: Restricted to your office/VPN IP.
  - `Inbound 80 (HTTP)`: Open (redirects to 443).
  - `Inbound 443 (HTTPS)`: Open to world.
  - `Outbound`: All traffic allowed.

### 2.2 AWS RDS for MySQL
- **Engine**: MySQL 8.0.
- **Storage**: gp3 General Purpose SSD.
- **Transactions**: Default isolation level `REPEATABLE READ`.
- **Row Locking**: Supports `SELECT ... FOR UPDATE` utilized in `ReservationService::changeDatesWithRowLock()` to prevent concurrent booking conflicts.
- **Security**: Bound to private VPC subnet; only accepts incoming traffic on port 3306 from the EC2 Security Group.

### 2.3 AWS S3 (Simple Storage Service)
- **Bucket**: `clearstay-assets-production`
- **Uses**:
  1. Offsite database backup storage (`/var/backups/clearstay/db_*.sql.gz` pushed nightly).
  2. Hotel guest document storage and CSV import templates.
- **Lifecycle Policy**: Move backups older than 30 days to S3 Glacier, expire after 90 days.

### 2.4 AWS SES (Simple Email Service)
- **Use**: Transactional booking confirmations, date change notices, and staff alerts.
- **Configuration in `.env`**:
  ```env
  MAIL_MAILER=ses
  AWS_ACCESS_KEY_ID=AKIA...
  AWS_SECRET_ACCESS_KEY=...
  AWS_DEFAULT_REGION=ap-northeast-1  # Tokyo region
  MAIL_FROM_ADDRESS=reservations@clearstay.jp
  MAIL_FROM_NAME="ClearStay Front Desk"
  ```
- **Deliverability**: Verified domain with Route 53 DKIM, SPF (`v=spf1 include:amazonses.com ~all`), and DMARC.

### 2.5 AWS Route 53 (DNS)
- **Hosted Zone**: `clearstay.jp` (or `clearstay.yourdomain.com`).
- **A Record**: Points with low TTL to EC2 Elastic IP.
- **CNAME Records**: Route 53 automated validation records for AWS SES DKIM.

---

## 3. Future Scalability Roadmap (Tier 2 Architecture)

| Phase | Technology | Architecture Function |
|---|---|---|
| **Real-Time Board** | **Laravel Reverb / WebSockets** | Push instant room state updates to front desk screens without 10-second polling |
| **Distributed Jobs** | **AWS SQS** | High-throughput distributed queue for bulk CSV imports and external OTA webhooks |
| **High Performance** | **Amazon ElastiCache (Redis)** | Sub-millisecond session storage and distributed cache across multiple web instances |
| **High Availability** | **AWS ALB + RDS Read Replica** | Application Load Balancer distributing to autoscaling EC2 group with read-only RDS queries |
| **Observability** | **Amazon CloudWatch** | Alarms for CPU spikes, failed login surges, disk usage, and queue lag metrics |
