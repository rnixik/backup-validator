# DB Backup Validation Tool

Restores latest DB backup, runs smoke tests and alerts.

![Alert example in Slack](http://s.getid.org/github/db-validator.png)

## Dependencies

* PHP >= 7.2
* composer
* docker

## Supported features

* Sources: latest file by pattern (e.g. `/var/db/backups/*.dump`)
* DB: PostgreSQL
* Alert channels: Slack
* Smoke tests: comparison result of SQL-query with exact value (`>=`, `<=`, `==`)
* Testing alert system: choose days of week to alert even if backup is valid

## Config example:
```
backups:
  awesome:
    source:
      type: latest
      pattern: /var/backups/db/awesome_*.dump
      not_older_than_hours: 24
    restore:
      type: postgres
      image: "postgres:latest"
      container_name: "awesome-validator"
      database: awesome
      user: awesome_user
      password: awesome_password
      verbose: true
    tests:
      - name: Users count
        sql: "SELECT COUNT(*) FROM users"
        expected_operator: ">="
        expected_value: 1000
      - name: Posts count
        sql: "SELECT COUNT(*) FROM posts"
        expected_operator: ">="
        expected_value: 50
    alerting:
      always_alert:
          days_of_week: [1]
      channels:
        - alert_to_slack

alert_channels:
  alert_to_slack:
    type: Slack
    webhook: "https://hooks.slack.com/services/XXX/YYY/ZZZZZ"
    subject: "Backup '%backup_name%' is invalid!"
    body: "%output%"
    subject_test: "Backup '%backup_name%' is valid and alerting is working"
    body_test: "%output%"

```

## How to run

1. Install composer dependencies `composer install`
2. Create `config.yml` file (you can specify path with `--config=/path/to/other.yml` option)
3. Run `./validate`

Example of crontab:
```
0 10 * * * /usr/bin/php /root/backup-validator/validate >/tmp/cron_backup_validator_out.log 2>/tmp/cron_backup_validator_err.log
```

## License

The MIT Licence
