#!/bin/bash -ex
rm -rf generated-api-schema/ db/generated-classes/ORM/{Base,Map}/
tools/prepare.sh
rsync -rva --delete --delete-excluded --exclude=.git ./ /var/www/sweetskun/
