#!/bin/bash

API_PATH="../api"

echo -e "\e[34mCleaning lint cache...\e[39m\n"
docker compose exec api phpstan clear-result-cache

echo -e "\e[34mRun the linter\e[39m\n"

# Test PHPStan existence
docker compose exec -T api phpstan --version
if [ $? -ne 0 ]; then
    echo -e "\n\e[31mPHPStan n'est pas installé ou pas accessible dans le conteneur.\e[39m"
    exit 1
fi

# Run PHPStan inside the PHP container
docker compose exec -T api phpstan analyse --level=8 --memory-limit=512M --debug src/ tests/

if [ $? -eq 0 ]; then
    echo -e "\n\e[32mLinting successful ! No issues found.\e[39m"
    exit 0
else
    echo -e "\n\e[31mLinting failed. Please fix the issues above.\e[39m"
    exit 1
fi
