#!/bin/bash
# INSTALL WP-CAMOO-SMS DEPENDENCIES
# -------------------------------------------------------------------------
# Copyright (c) 2019 CAMOO SARL
# -------------------------------------------------------------------------
PHP=$*
# shellcheck disable=SC2006
COMPOSER=$(which composer)

# Find a CLI version of PHP
IN_PHP=(php php-cli /usr/local/bin/php)
getCliPhp() {
  for TESTEXEC in "${IN_PHP[@]}"; do
    # shellcheck disable=SC2006
    SAPI=$(echo "<?= PHP_SAPI ?>" | $TESTEXEC 2>/dev/null)
    if [ "$SAPI" = "cli" ]; then
      echo "$TESTEXEC"
      return
    fi
  done
  echo "Failed to find a CLI version of PHP; falling back to system standard php executable" >&2
  echo "php"
}
if [ -z "$PHP" ]; then
  PHP=$(getCliPhp)
fi

if [ -z "$COMPOSER" ]; then
  echo "Composer not found!"
  echo "['FAILED']"
  exit 0
fi

${PHP} "${COMPOSER}" install

echo "[DONE]"
exit 0
