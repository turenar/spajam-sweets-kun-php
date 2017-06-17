#!/bin/bash -ex
rm -rf \
	db/generated-classes/ORM/Base/ \
	db/generated-classes/ORM/Map/ \
	db/generated-migrations/ \
	generated-api-schema/

tools/prepare.sh
tools/migrate_db.sh
rsync -rva --delete --delete-excluded --exclude=.git ./ /var/www/sweetskun/
