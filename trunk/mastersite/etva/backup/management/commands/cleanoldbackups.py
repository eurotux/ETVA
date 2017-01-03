from django.db import models
from django.core.management.base import BaseCommand, CommandError
from etva.backup.models import Backup

from datetime import datetime, date, time, timedelta

class Command(BaseCommand):
    args = '<days>'
    help = 'Clean old backups'

    def handle(self, *args, **options):
        days = int(args[0])
        
        datenow = datetime.now()
        datedays = datenow - timedelta(days=days)
        backups = Backup.objects.filter(date_created__lt=datedays)
        for backup in backups:
            self.stdout.write('[%s] Delete backup user_id="%s" date="%s" file="%s"\n' % (datenow, backup.user_id, backup.date_created, backup.file))
            backup.file.delete()
            backup.delete()

        self.stdout.write('[%s] Successfully clean backups older than "%s" days (date<"%s")\n' % (datenow, days, datedays))

