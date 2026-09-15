#!/bin/bash
# 沿用宝塔的外层 flock 锁，使用命令退出码记录真实执行结果。
set -u
if [ -f /www/maintenance/gelinshe.flag ]; then
    exit 0
fi
cd /www/wwwroot/gelinshe || exit 1
/usr/sbin/runuser -u www -- /www/server/php/80/bin/php think crontab
status=$?
printf '[%s] crontab exit=%s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$status"
exit "$status"
