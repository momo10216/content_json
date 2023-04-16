#!/bin/sh
EXTENSION=$(grep "<name>" *.xml | sed 's/[ \t]*<[\/]*name>[\r\n]*//g' | tr '[:upper:]' '[:lower:]')
VERSION=$(grep "<version>" *.xml | sed 's/[ \t]*<[\/]*version>[\r\n]*//g' | tr '[:upper:]' '[:lower:]')
OBJECTLIST="admin site language js ${EXTENSION}.xml *.php LICENSE"
ZIPFILENAME="./${EXTENSION}-${VERSION}.zip"

if [ -r "${ZIPFILENAME}" ]; then
        rm "${ZIPFILENAME}"
fi
zip -qr "${ZIPFILENAME}" ${OBJECTLIST}
