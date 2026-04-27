# Lab 6 Operations Runbook

This runbook lists practical commands to validate and operate Lab 6 features.

## 1. Apply Migrations

```bash
php artisan migrate
```

Purpose:
- Applies new transfer, audit hardening, and indexing schemas.

## 2. Queue and Scheduler Processes

Run in separate terminals:

```bash
php artisan queue:work
php artisan schedule:work
```

Purpose:
- Executes queued import/export jobs.
- Executes backup/maintenance automation tasks.

## 3. Verify Routes

```bash
php artisan route:list --name=admin.books.export
php artisan route:list --name=admin.orders.export
php artisan route:list --name=admin.users.export
php artisan route:list --name=admin.audit-logs
php artisan route:list --name=api.books
```

Expected:
- Named routes are present for all transfer, audit, and API modules.

## 4. Verify Scheduler Tasks

```bash
php artisan schedule:list
```

Expected key tasks:
- backup:run --only-db
- backup:run --only-files
- backup:clean
- backup:monitor
- maintenance:cleanup-transfer-files
- maintenance:purge-audit-logs
- maintenance:verify-audit-chain
- maintenance:scan-transfer-health
- queue:prune-failed
- maintenance:window-start
- maintenance:window-end

## 5. Manual Command Checks

```bash
php artisan maintenance:scan-transfer-health --stuck-minutes=90
php artisan maintenance:verify-audit-chain --days=30
php artisan maintenance:cleanup-transfer-files --hours=48
php artisan maintenance:purge-audit-logs --days=90
```

Purpose:
- Confirms maintenance commands execute and produce expected operational output.

## 6. API Optimization Validation

Use browser/Postman/curl:

- GET /api/books
- GET /api/books?fields=id,title,price&perPage=10
- GET /api/books?cursor=<cursor_token>
- GET /api/books/{id}?fields=id,title,author

Expected:
- CamelCase keys in payload.
- Cursor pagination metadata.
- ETag header in responses.
- 304 response when sending matching If-None-Match.

## 7. Data Transfer Validation Flow

Books:
1. Admin dashboard -> Import Books -> Upload template file -> Preview -> Confirm queue.
2. Admin dashboard -> Export Books -> configure filters/columns/format -> Start export.

Orders:
1. Admin dashboard -> Export Orders -> configure filters/columns/format -> Start export.

Users:
1. Admin dashboard -> Import Users -> Upload template -> Preview -> Confirm queue.
2. Admin dashboard -> Export Users -> choose redaction options -> Start export.

Expected:
- Entries appear in Data Transfer Jobs with status progression: queued -> processing -> completed/failed.
- Completed exports provide download links.

## 8. Audit Hardening Validation

In admin audit logs:
- Use Export CSV.
- Use Archive Filtered.
- Use Verify Integrity.
- Open a log detail entry.

Expected:
- Checksum fields visible on records created after checksum migration.
- Archive markers shown after archive action.
- Integrity check reports pass/fail summary.

## 9. Performance Index Validation (Database)

Check migration status:

```bash
php artisan migrate:status
```

Optional PostgreSQL check:

```sql
SELECT indexname, indexdef
FROM pg_indexes
WHERE schemaname = 'public'
  AND tablename IN ('users', 'books', 'orders', 'order_items', 'reviews', 'data_transfer_jobs')
ORDER BY tablename, indexname;
```

Expected:
- New named indexes created by migration 2026_04_20_120000_add_performance_indexes_for_lab6.
